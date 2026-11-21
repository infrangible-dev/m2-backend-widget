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
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A suppression of unused parameters on a method that cannot have an unused parameter in the sense of PhpStorm.
 *
 * PhpStorm does not report unused parameters of methods that override or implement a method of a parent class or an
 * interface (the signature is given), and of course not if the method uses all its parameters. A
 * "@noinspection PhpUnusedParameterInspection" in the docblock of such a method is reported by PhpStorm as
 * "Redundant suppression".
 *
 * @implements Rule<InClassMethodNode>
 */
class RedundantUnusedParameterSuppressionRule implements Rule
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
        $docComment = $method->getDocComment();

        if ($docComment === null ||
            preg_match('/@noinspection\s+[^\n]*\b' . self::INSPECTION . '\b/', $docComment->getText()) !== 1) {

            return [];
        }

        $methodName = $method->name->toString();

        $reason = match (true) {
            $this->overridesMethod($scope, $methodName) => 'overrides or implements a parent method',
            $this->usesAllParameters($method) => 'uses all its parameters',
            default => null
        };

        if ($reason === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'The suppression of %s is redundant: %s() %s, PhpStorm reports no unused parameter.',
                    self::INSPECTION,
                    $methodName,
                    $reason
                )
            )->identifier('infrangible.redundantUnusedParameterSuppression')->build()
        ];
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

        if ($classReflection === null || strtolower($methodName) === '__construct') {
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

        return false;
    }

    /**
     * Checks if the body of the method uses all parameters of the method.
     *
     * @param Node\Stmt\ClassMethod $method
     *
     * @return bool
     */
    private function usesAllParameters(Node\Stmt\ClassMethod $method): bool
    {
        if ($method->stmts === null || count($method->params) === 0) {
            return false;
        }

        $nodeFinder = new NodeFinder();

        $usedNames = [];

        foreach ($nodeFinder->findInstanceOf($method->stmts, Expr\Variable::class) as $variable) {
            if ($variable instanceof Expr\Variable) {
                $usedNames[] = $variable->name;
            }
        }

        foreach ($method->params as $param) {
            $variable = $param->var;

            if ($variable instanceof Expr\Variable && is_string($variable->name) &&
                ! in_array($variable->name, $usedNames, true)) {

                return false;
            }
        }

        return true;
    }
}
