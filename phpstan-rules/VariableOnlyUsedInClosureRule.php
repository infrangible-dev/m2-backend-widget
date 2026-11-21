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
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A variable that is assigned once and then only passed by value to one closure with "use".
 *
 * PhpStorm reports it as "Variable is only used inside closure": the assignment belongs into the closure. Arrow
 * functions take variables implicitly, a variable used in one counts as used outside of closures.
 *
 * @implements Rule<Node>
 */
class VariableOnlyUsedInClosureRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ($node instanceof Stmt\ClassMethod || $node instanceof Stmt\Function_ ||
            $node instanceof Expr\Closure)) {

            return [];
        }

        /** @var array<string, int> $assignLines line of the only assignment, by variable name */
        $assignLines = [];
        /** @var array<string, int> $assignCounts */
        $assignCounts = [];
        /** @var array<string, int> $useCounts uses outside of closures, without the assignments */
        $useCounts = [];
        /** @var array<string, int> $closureCounts closures that take the variable by value */
        $closureCounts = [];

        foreach ($node->getStmts() ?? [] as $statement) {
            $assign = $statement instanceof Stmt\Expression ? $statement->expr : null;
            $variable = $assign instanceof Expr\Assign ? $assign->var : null;
            $name = $variable instanceof Expr\Variable ? $variable->name : null;

            if ($assign instanceof Expr\Assign && is_string($name)) {
                $assignLines[ $name ] = $statement->getStartLine();
                $assignCounts[ $name ] = ($assignCounts[ $name ] ?? 0) + 1;

                $this->collect($assign->expr, $useCounts, $closureCounts);

                continue;
            }

            $this->collect($statement, $useCounts, $closureCounts);
        }

        $parameterNames = [];

        foreach ($node->getParams() as $param) {
            $variable = $param->var;

            if ($variable instanceof Expr\Variable && is_string($variable->name)) {
                $parameterNames[] = $variable->name;
            }
        }

        $errors = [];

        foreach ($assignLines as $name => $line) {
            if ($assignCounts[ $name ] === 1 && ! isset($useCounts[ $name ]) &&
                ($closureCounts[ $name ] ?? 0) === 1 && ! in_array($name, $parameterNames, true)) {

                $errors[] = RuleErrorBuilder::message(
                    sprintf('$%s is only used inside a closure, assign it there.', $name)
                )->identifier('infrangible.variableOnlyUsedInClosure')->line($line)->build();
            }
        }

        return $errors;
    }

    /**
     * Counts the variables of the node outside of closures, and the variables that closures take by value.
     *
     * @param Node $node
     * @param array<string, int> $useCounts
     * @param array<string, int> $closureCounts
     *
     * @return void
     */
    private function collect(Node $node, array &$useCounts, array &$closureCounts): void
    {
        if ($node instanceof Expr\Closure) {
            foreach ($node->uses as $use) {
                if (! is_string($use->var->name)) {
                    continue;
                }

                if ($use->byRef) {
                    $useCounts[ $use->var->name ] = ($useCounts[ $use->var->name ] ?? 0) + 1;
                } else {
                    $closureCounts[ $use->var->name ] = ($closureCounts[ $use->var->name ] ?? 0) + 1;
                }
            }

            return;
        }

        if ($node instanceof Expr\Variable) {
            $name = is_string($node->name) ? $node->name : '';

            $useCounts[ $name ] = ($useCounts[ $name ] ?? 0) + 1;
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;

            foreach (is_array($subNode) ? $subNode : [$subNode] as $child) {
                if ($child instanceof Node) {
                    $this->collect($child, $useCounts, $closureCounts);
                }
            }
        }
    }
}
