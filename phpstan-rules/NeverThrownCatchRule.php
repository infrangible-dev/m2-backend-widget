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
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * A catch of a checked exception that none of the calls in the try block declares in throws is dead.
 *
 * PhpStorm reports it as "Exception is never thrown in the corresponding try block"; PHPStan does not, because it
 * assumes that calls without throws can throw anything. The rule is conservative: it only reports a catch if every
 * call of the try block can be resolved, and never catches of Exception or Throwable, which PhpStorm accepts as well.
 * Like in PhpStorm, "@noinspection PhpRedundantCatchClauseInspection" on the try or the method suppresses it.
 *
 * @implements Rule<TryCatch>
 */
readonly class NeverThrownCatchRule implements Rule
{
    /** Exceptions that may be caught without a declaration. */
    private const GENERIC = ['exception', 'throwable'];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private DefaultExceptionTypeResolver $exceptionTypeResolver
    ) {
    }

    public function getNodeType(): string
    {
        return TryCatch::class;
    }

    /**
     * @param TryCatch $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isSuppressed($node, $scope)) {
            return [];
        }

        $thrownTypes = $this->getThrownTypes($node->stmts, $scope);

        if ($thrownTypes === null) {
            return [];
        }

        $errors = [];

        foreach ($node->catches as $catch) {
            foreach ($catch->types as $typeName) {
                $className = $scope->resolveName($typeName);

                if (in_array(strtolower(ltrim($className, '\\')), self::GENERIC, true) ||
                    ! $this->reflectionProvider->hasClass($className) ||
                    ! $this->exceptionTypeResolver->isCheckedException($className, $scope)) {

                    continue;
                }

                if ($this->isThrown(new ObjectType($className), $thrownTypes)) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Exception %s is never thrown in the corresponding try block: no call declares it in @throws.',
                        $className
                    )
                )->identifier('infrangible.neverThrownCatch')->line($catch->getStartLine())->build();
            }
        }

        return $errors;
    }

    /**
     * Checks if the catch is suppressed for PhpStorm with "@noinspection PhpRedundantCatchClauseInspection" in the
     * comment of the try or the PHPDoc of the method, for example for exceptions that Magento throws without
     * declaring them.
     *
     * @param TryCatch $node
     * @param Scope $scope
     *
     * @return bool
     */
    private function isSuppressed(TryCatch $node, Scope $scope): bool
    {
        $comments = array_map(static fn($comment): string => $comment->getText(), $node->getComments());
        $function = $scope->getFunction();

        if ($function !== null && $function->getDocComment() !== null) {
            $comments[] = $function->getDocComment();
        }

        foreach ($comments as $comment) {
            if (preg_match('/@noinspection\b[^\n]*\bPhpRedundantCatchClauseInspection\b/', $comment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if one of the thrown exceptions is the caught exception, a subclass or a parent class of it.
     *
     * @param Type $catchType
     * @param list<Type> $thrownTypes
     *
     * @return bool
     */
    private function isThrown(Type $catchType, array $thrownTypes): bool
    {
        foreach ($thrownTypes as $thrownType) {
            if (! $thrownType->isSuperTypeOf($catchType)->no() || ! $catchType->isSuperTypeOf($thrownType)->no()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the exceptions that the calls and throw expressions of the statements declare, or null if a call cannot
     * be resolved.
     *
     * @param Node[] $statements
     * @param Scope $scope
     *
     * @return list<Type>|null
     */
    private function getThrownTypes(array $statements, Scope $scope): ?array
    {
        $types = [];
        $nodeFinder = new NodeFinder();

        $nodes = $nodeFinder->find(
            $statements,
            static fn(Node $node): bool => $node instanceof Expr\MethodCall ||
                $node instanceof Expr\NullsafeMethodCall ||
                $node instanceof Expr\StaticCall || $node instanceof Expr\New_ || $node instanceof Expr\FuncCall ||
                $node instanceof Expr\Throw_ || $node instanceof Node\Stmt\Throw_ ||
                $node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction
        );

        $closures = array_filter(
            $nodes,
            static fn(Node $node): bool => $node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction
        );

        foreach ($nodes as $node) {
            if ($node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction ||
                $this->isInside($node, $closures)) {

                continue;
            }

            $type = $this->getThrowType($node, $scope);

            if ($type === false) {
                return null;
            }

            if ($type !== null) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * Checks if the node is part of one of the closures, whose code does not run in the try block.
     *
     * @param Node $node
     * @param Node[] $closures
     *
     * @return bool
     */
    private function isInside(Node $node, array $closures): bool
    {
        foreach ($closures as $closure) {
            if ($node->getStartFilePos() >= $closure->getStartFilePos() &&
                $node->getEndFilePos() <= $closure->getEndFilePos()) {

                return true;
            }
        }

        return false;
    }

    /**
     * Returns the declared exceptions of the call or throw expression, null if it declares none, or false if it cannot
     * be resolved.
     *
     * @param Node $node
     * @param Scope $scope
     *
     * @return Type|null|false
     */
    private function getThrowType(Node $node, Scope $scope): Type|null|false
    {
        if ($node instanceof Expr\Throw_ || $node instanceof Node\Stmt\Throw_) {
            $thrown = $node->expr;
            $thrownClass = $thrown instanceof Expr\New_ ? $thrown->class : null;

            return $thrownClass instanceof Name ? new ObjectType($scope->resolveName($thrownClass)) : false;
        }

        if ($node instanceof Expr\New_) {
            if (! $node->class instanceof Name) {
                return false;
            }

            $className = $scope->resolveName($node->class);

            if (! $this->reflectionProvider->hasClass($className)) {
                return false;
            }

            $class = $this->reflectionProvider->getClass($className);

            return $class->hasConstructor() ? $class->getConstructor()->getThrowType() : null;
        }

        if ($node instanceof Expr\FuncCall) {
            if (! $node->name instanceof Name || ! $this->reflectionProvider->hasFunction($node->name, $scope)) {
                return false;
            }

            return $this->reflectionProvider->getFunction($node->name, $scope)->getThrowType();
        }

        if (! $node instanceof Expr\MethodCall && ! $node instanceof Expr\NullsafeMethodCall &&
            ! $node instanceof Expr\StaticCall) {

            return false;
        }

        $name = $node->name;

        if (! $name instanceof Node\Identifier) {
            return false;
        }

        if ($node instanceof Expr\StaticCall) {
            $class = $node->class;

            if (! $class instanceof Name) {
                return false;
            }

            $type = $scope->resolveTypeByName($class);
        } else {
            $variable = $node->var;
            $variableName = $variable instanceof Expr\Variable ? $variable->name : null;

            // variables that are assigned in the try block are unknown here
            if (is_string($variableName) && ! $scope->hasVariableType($variableName)->yes()) {
                return false;
            }

            $type = $scope->getType($variable);
        }

        $methodName = $name->toString();

        if (! $type->hasMethod($methodName)->yes()) {
            return false;
        }

        return $type->getMethod($methodName, $scope)->getThrowType();
    }
}
