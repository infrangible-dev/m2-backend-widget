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
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A literal as last argument of a call that is the default value of the parameter can be left out.
 *
 * PhpStorm reports it as "Argument matches the parameter's default value". Only literals are checked (null, true,
 * false, numbers, texts, empty arrays), not variables or constants that happen to have the value.
 *
 * @implements Rule<Expr\CallLike>
 */
class DefaultArgumentRule implements Rule
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return Expr\CallLike::class;
    }

    /**
     * @param Expr\CallLike $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isFirstClassCallable()) {
            return [];
        }

        $arguments = $node->getArgs();

        if (count($arguments) === 0) {
            return [];
        }

        foreach ($arguments as $argument) {
            if ($argument->unpack || $argument->name !== null) {
                return [];
            }
        }

        $parametersAcceptor = $this->getParametersAcceptor($node, $scope);

        if ($parametersAcceptor === null) {
            return [];
        }

        $parameters = $parametersAcceptor->getParameters();
        $index = count($arguments) - 1;

        if (! isset($parameters[ $index ]) || $parameters[ $index ]->isVariadic()) {
            return [];
        }

        $argument = $arguments[ $index ];
        $defaultValue = $parameters[ $index ]->getDefaultValue();

        if ($defaultValue === null || ! $this->isLiteral($argument->value) ||
            ! $defaultValue->equals($scope->getType($argument->value))) {

            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Argument #%d $%s matches the default value of the parameter and can be left out.',
                    $index + 1,
                    $parameters[ $index ]->getName()
                )
            )->identifier('infrangible.defaultArgument')->line($argument->getStartLine())->build()
        ];
    }

    /**
     * Checks if the expression is a literal: null, true, false, a number, a text or an empty array.
     *
     * @param Expr $expression
     *
     * @return bool
     */
    private function isLiteral(Expr $expression): bool
    {
        if ($expression instanceof Node\Scalar\LNumber || $expression instanceof Node\Scalar\DNumber ||
            ($expression instanceof Node\Scalar\String_)) {

            return true;
        }

        if ($expression instanceof Expr\UnaryMinus) {
            return $expression->expr instanceof Node\Scalar\LNumber || $expression->expr instanceof Node\Scalar\DNumber;
        }

        if ($expression instanceof Expr\ConstFetch) {
            return in_array(strtolower($expression->name->toString()), ['null', 'true', 'false'], true);
        }

        return $expression instanceof Expr\Array_ && count($expression->items) === 0;
    }

    /**
     * Returns the parameters of the called method, function or constructor, or null if it cannot be resolved or has
     * several variants.
     *
     * @param Expr\CallLike $node
     * @param Scope $scope
     *
     * @return ParametersAcceptor|null
     */
    private function getParametersAcceptor(Expr\CallLike $node, Scope $scope): ?ParametersAcceptor
    {
        $variants = null;

        if ($node instanceof Expr\FuncCall) {
            if ($node->name instanceof Name && $this->reflectionProvider->hasFunction($node->name, $scope)) {
                $variants = $this->reflectionProvider->getFunction($node->name, $scope)->getVariants();
            }
        } elseif ($node instanceof Expr\New_) {
            if ($node->class instanceof Name) {
                $className = $scope->resolveName($node->class);

                if ($this->reflectionProvider->hasClass($className)) {
                    $class = $this->reflectionProvider->getClass($className);

                    $variants = $class->hasConstructor() ? $class->getConstructor()->getVariants() : null;
                }
            }
        } elseif ($node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall ||
            $node instanceof Expr\StaticCall) {

            if ($node->name instanceof Node\Identifier) {
                $type = $node instanceof Expr\StaticCall ?
                    ($node->class instanceof Name ? $scope->resolveTypeByName($node->class) : null) :
                    $scope->getType($node->var);

                if ($type !== null && $type->hasMethod($node->name->toString())->yes()) {
                    $variants = $type->getMethod($node->name->toString(), $scope)->getVariants();
                }
            }
        }

        return $variants !== null && count($variants) === 1 ? $variants[ 0 ] : null;
    }
}
