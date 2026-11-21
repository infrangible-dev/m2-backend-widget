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
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A foreach loop that only fills an array, which was empty before the loop, with one value per element can be an
 * array_map() call.
 *
 * PhpStorm reports it as "Loop can be converted to an 'array_map()' call". Reported are loops directly after
 * "$x = [];" whose only statement is "$x[] = ..." or "$x[$key] = ..." with the key of the loop, where the value does
 * not use the key and the array itself, and only loops over arrays (array_map() does not take other iterables).
 *
 * The statements before a loop are only known to its method, the type of the loop only in the scope of the loop: the
 * rule finds the loops when PHPStan passes the method, before its statements, and checks their type afterwards.
 *
 * @implements Rule<Node>
 */
class LoopToArrayMapRule implements Rule
{
    /** @var \WeakMap<Stmt\Foreach_, true> loops that only fill an empty array */
    private \WeakMap $mapLoops;

    public function __construct()
    {
        $this->mapLoops = new \WeakMap();
    }

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
        if ($node instanceof Stmt\Foreach_) {
            if (! isset($this->mapLoops[ $node ]) || ! $scope->getType($node->expr)->isArray()->yes()) {
                return [];
            }

            return [
                RuleErrorBuilder::message('The loop can be converted to an array_map() call.')
                    ->identifier('infrangible.loopToArrayMap')
                    ->build()
            ];
        }

        if ($node instanceof Stmt\ClassMethod || $node instanceof Stmt\Function_) {
            $this->findMapLoops($node);
        }

        return [];
    }

    /**
     * Remembers the loops of the method that only fill an empty array.
     *
     * @param Stmt\ClassMethod|Stmt\Function_ $node
     *
     * @return void
     */
    private function findMapLoops(Stmt\ClassMethod|Stmt\Function_ $node): void
    {
        $nodeFinder = new NodeFinder();

        $statementLists = [$node->getStmts() ?? []];

        foreach ($nodeFinder->find($node->getStmts() ?? [], static fn(Node $child): bool => true) as $child) {
            $statements = $this->getStatements($child);

            if ($statements !== null) {
                $statementLists[] = $statements;
            }
        }

        foreach ($statementLists as $statements) {
            $statements = array_values($statements);

            foreach ($statements as $index => $statement) {
                if ($index > 0 && $statement instanceof Stmt\Foreach_ &&
                    $this->isMapLoop($statements[ $index - 1 ], $statement)) {

                    $this->mapLoops[ $statement ] = true;
                }
            }
        }
    }

    /**
     * Returns the statements of a node that contains statements (blocks of control structures, closures), or null.
     *
     * @param Node $node
     *
     * @return Node\Stmt[]|null
     */
    private function getStatements(Node $node): ?array
    {
        return match (true) {
            $node instanceof Stmt\If_, $node instanceof Stmt\ElseIf_, $node instanceof Stmt\Else_,
            $node instanceof Stmt\For_, $node instanceof Stmt\Foreach_, $node instanceof Stmt\While_,
            $node instanceof Stmt\Do_, $node instanceof Stmt\TryCatch, $node instanceof Stmt\Catch_,
            $node instanceof Stmt\Finally_, $node instanceof Stmt\Case_, $node instanceof Expr\Closure => $node->stmts,
            default => null
        };
    }

    /**
     * Checks if the loop only fills the array that the previous statement set to an empty array.
     *
     * @param Node $previous
     * @param Stmt\Foreach_ $loop
     *
     * @return bool
     */
    private function isMapLoop(Node $previous, Stmt\Foreach_ $loop): bool
    {
        if ($loop->byRef || ! $loop->valueVar instanceof Expr\Variable || count($loop->stmts) !== 1) {
            return false;
        }

        $arrayName = $this->getEmptyArrayName($previous);
        $statement = $loop->stmts[ 0 ];
        $assign = $statement instanceof Stmt\Expression ? $statement->expr : null;
        $target = $assign instanceof Expr\Assign ? $assign->var : null;

        if ($arrayName === null || ! $assign instanceof Expr\Assign || ! $target instanceof Expr\ArrayDimFetch) {
            return false;
        }

        $array = $target->var;
        $dimension = $target->dim;
        $key = $loop->keyVar;

        if (! $array instanceof Expr\Variable || $array->name !== $arrayName) {
            return false;
        }

        $keyName = $key instanceof Expr\Variable && is_string($key->name) ? $key->name : null;

        if ($dimension !== null &&
            ! ($keyName !== null && $dimension instanceof Expr\Variable && $dimension->name === $keyName)) {

            return false;
        }

        $usedNames = [];

        foreach ((new NodeFinder())->findInstanceOf($assign->expr, Expr\Variable::class) as $variable) {
            if ($variable instanceof Expr\Variable) {
                $usedNames[] = $variable->name;
            }
        }

        return ! in_array($arrayName, $usedNames, true) &&
            ($keyName === null || ! in_array($keyName, $usedNames, true));
    }

    /**
     * Returns the name of the variable that the statement sets to an empty array, or null.
     *
     * @param Node $statement
     *
     * @return string|null
     */
    private function getEmptyArrayName(Node $statement): ?string
    {
        $assign = $statement instanceof Stmt\Expression ? $statement->expr : null;

        if (! $assign instanceof Expr\Assign) {
            return null;
        }

        $variable = $assign->var;
        $value = $assign->expr;

        if (! $variable instanceof Expr\Variable || ! is_string($variable->name) || ! $value instanceof Expr\Array_ ||
            count($value->items) !== 0) {

            return null;
        }

        return $variable->name;
    }
}
