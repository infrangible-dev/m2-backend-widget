<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

/**
 * A property or method of the value of a property that only exists after an instanceof check of the property.
 *
 * PHPStan narrows the type of "$node->expr" after "$node->expr instanceof Assign", PhpStorm only narrows variables: it
 * reports "$node->expr->var" as "Potentially polymorphic call. The code may be inoperable depending on the actual class
 * instance passed as the argument." when the declared type of the property (Expr) has no "var". Assign the property to
 * a variable before the check instead; also the assignment of the checked property to a variable after the check,
 * which PhpStorm types with the declared type. PhpStorm narrows the properties of $this, and null checks are no
 * polymorphism.
 *
 * @implements Rule<Expr>
 */
readonly class NarrowedPropertyAccessRule implements Rule
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Expr $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof Expr\Assign) {
            return $node->var instanceof Expr\Variable ? $this->processAssign($node->expr, $scope) : [];
        }

        if (! ($node instanceof Expr\PropertyFetch || $node instanceof Expr\NullsafePropertyFetch ||
            $node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall)) {

            return [];
        }

        $member = $node->name;
        $property = $this->getProperty($node->var, $scope);

        if (! $member instanceof Node\Identifier || $property === null) {
            return [];
        }

        [$propertyName, $declaredType] = $property;

        $memberName = $member->toString();
        $method = $node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall;
        $narrowedType = $scope->getType($node->var);

        $declared = $method ? $declaredType->hasMethod($memberName) : $declaredType->hasProperty($memberName);
        $narrowed = $method ? $narrowedType->hasMethod($memberName) : $narrowedType->hasProperty($memberName);

        if ($declared->yes() || ! $narrowed->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    '%s %s only exists after the instanceof check of the property $%s, PhpStorm only narrows ' .
                    'variables. Assign $%s to a variable before the check.',
                    $method ? 'Method' : 'Property',
                    $method ? $memberName . '()' : '$' . $memberName,
                    $propertyName,
                    $propertyName
                )
            )->identifier('infrangible.narrowedPropertyAccess')->build()
        ];
    }

    /**
     * Reports the assignment of a property to a variable after an instanceof check of the property: PhpStorm gives the
     * variable the declared type of the property.
     *
     * @param Expr $value the assigned value
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    private function processAssign(Expr $value, Scope $scope): array
    {
        $property = $this->getProperty($value, $scope);

        if ($property === null) {
            return [];
        }

        [$propertyName, $declaredType] = $property;

        $narrowedType = TypeCombinator::removeNull($scope->getType($value));

        if ($narrowedType->isSuperTypeOf($declaredType)->yes() ||
            ! $declaredType->isSuperTypeOf($narrowedType)->yes()) {

            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'The property $%s is assigned after its instanceof check, PhpStorm gives the variable the ' .
                    'declared type %s. Assign it to the variable before the check.',
                    $propertyName,
                    $declaredType->describe(VerbosityLevel::typeOnly())
                )
            )->identifier('infrangible.narrowedPropertyAccess')->build()
        ];
    }

    /**
     * Returns the name and the declared type without null of the property that the expression fetches, or null for
     * other expressions, properties of $this (which PhpStorm narrows) and unknown properties.
     *
     * @param Expr $expression
     * @param Scope $scope
     *
     * @return array{0: string, 1: Type}|null
     */
    private function getProperty(Expr $expression, Scope $scope): ?array
    {
        if (! ($expression instanceof Expr\PropertyFetch || $expression instanceof Expr\NullsafePropertyFetch)) {
            return null;
        }

        $name = $expression->name;
        $object = $expression->var;

        if (! $name instanceof Node\Identifier || ($object instanceof Expr\Variable && $object->name === 'this')) {
            return null;
        }

        $declaredType = $this->getDeclaredPropertyType($scope->getType($object), $name->toString(), $scope);

        // a null check is no polymorphism
        return $declaredType === null ? null : [$name->toString(), TypeCombinator::removeNull($declaredType)];
    }

    /**
     * Returns the declared type of the property of the object type, or null if it is unknown.
     *
     * @param Type $objectType
     * @param string $propertyName
     * @param Scope $scope
     *
     * @return Type|null
     */
    private function getDeclaredPropertyType(Type $objectType, string $propertyName, Scope $scope): ?Type
    {
        $types = [];

        foreach ($objectType->getObjectClassNames() as $className) {
            if (! $this->reflectionProvider->hasClass($className)) {
                return null;
            }

            $class = $this->reflectionProvider->getClass($className);

            if (! $class->hasProperty($propertyName)) {
                return null;
            }

            $types[] = $class->getProperty($propertyName, $scope)->getReadableType();
        }

        return count($types) === 0 ? null : TypeCombinator::union(...$types);
    }
}
