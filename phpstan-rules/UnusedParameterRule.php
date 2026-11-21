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
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;

/**
 * A parameter of a method that the method does not use.
 *
 * PhpStorm reports it as "Unused parameter '$x'. The parameter's value is not used anywhere.", except for methods that
 * override or implement a method of a parent class or an interface, whose signature is given. Methods with a given
 * signature that PhpStorm does not know, like the before, after and around methods of Magento plugins, need
 * "@noinspection PhpUnusedParameterInspection" in their docblock. Promoted constructor parameters are properties and
 * count as used, methods without statements are hooks for subclasses and are not checked.
 *
 * @implements Rule<InClassMethodNode>
 */
class UnusedParameterRule implements Rule
{
    private const INSPECTION = 'PhpUnusedParameterInspection';

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * @param InClassMethodNode $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $method = $node->getOriginalNode();

        // methods without statements are hooks for subclasses, which use the parameters
        $statements = array_filter(
            $method->stmts ?? [],
            static fn(Node\Stmt $statement): bool => ! $statement instanceof Node\Stmt\Nop
        );

        if (count($statements) === 0 || count($method->params) === 0 || $this->isSuppressed($method) ||
            $this->overridesMethod($scope, $method->name->toString())) {

            return [];
        }

        $usedNames = $this->getUsedNames($statements);
        $errors = [];

        foreach ($method->params as $param) {
            $variable = $param->var;

            if ($param->flags !== 0 || ! $variable instanceof Expr\Variable || ! is_string($variable->name) ||
                in_array($variable->name, $usedNames, true)) {

                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Parameter $%s of %s() is not used. Remove it, or add "@noinspection %s" to the docblock if ' .
                    'the signature is given (for example a method of a plugin).',
                    $variable->name,
                    $method->name->toString(),
                    self::INSPECTION
                )
            )->identifier('infrangible.unusedParameter')->line($param->getStartLine())->build();
        }

        return $errors;
    }

    /**
     * Returns the names of the variables used in the statements, also in compact() and with func_get_args() all.
     *
     * @param Node\Stmt[] $statements
     *
     * @return list<string|null> null if all parameters are used (func_get_args() or variable variables)
     */
    private function getUsedNames(array $statements): array
    {
        $nodeFinder = new NodeFinder();
        $names = [];

        foreach ($nodeFinder->findInstanceOf($statements, Expr\Variable::class) as $variable) {
            if ($variable instanceof Expr\Variable) {
                $names[] = is_string($variable->name) ? $variable->name : null;
            }
        }

        foreach ($nodeFinder->findInstanceOf($statements, Expr\FuncCall::class) as $call) {
            if (! $call instanceof Expr\FuncCall) {
                continue;
            }

            $callName = $call->name;

            if (! $callName instanceof Node\Name) {
                continue;
            }

            $functionName = $callName->toLowerString();

            if (in_array($functionName, ['func_get_args', 'func_get_arg', 'get_defined_vars'], true)) {
                $names[] = null;
            }

            if ($functionName === 'compact') {
                foreach ($nodeFinder->findInstanceOf($call->getArgs(), String_::class) as $string) {
                    if ($string instanceof String_) {
                        $names[] = $string->value;
                    }
                }
            }
        }

        // variable variables can use every parameter
        return in_array(null, $names, true) ? [null] : $names;
    }

    /**
     * Checks if the method overrides or implements a method of a parent class or an interface.
     *
     * @param Scope $scope
     * @param string $methodName
     *
     * @return bool
     */
    private function overridesMethod(Scope $scope, string $methodName): bool
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return false;
        }

        $parentClass = $classReflection->getParentClass();

        if ($parentClass !== null && $parentClass->hasMethod($methodName)) {
            return true;
        }

        foreach ($classReflection->getInterfaces() as $interface) {
            if ($interface->hasMethod($methodName)) {
                return true;
            }
        }

        foreach ($classReflection->getTraits(true) as $trait) {
            if (! $trait->hasNativeMethod($methodName)) {
                continue;
            }

            // PHPStan 1.x returns a bool, 2.x a TrinaryLogic
            $abstract = $trait->getNativeMethod($methodName)->isAbstract();

            if ($abstract instanceof TrinaryLogic ? $abstract->yes() : $abstract) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the docblock of the method suppresses the inspection.
     *
     * @param Node\Stmt\ClassMethod $method
     *
     * @return bool
     */
    private function isSuppressed(Node\Stmt\ClassMethod $method): bool
    {
        $docComment = $method->getDocComment();

        return $docComment !== null &&
            preg_match('/@noinspection\s+[^\n]*\b(' . self::INSPECTION . '|ALL)\b/', $docComment->getText()) === 1;
    }
}
