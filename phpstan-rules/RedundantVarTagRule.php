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
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;

/**
 * An inline var tag of an assignment that specifies the type that the assigned value already has.
 *
 * PhpStorm reports it as "var tag specifies the type already inferred from source code" (with "@" before "var"). Like
 * PhpStorm, the declared types count, also those of docblocks, for example a var tag "Item[] $items" above
 * "$items = $quote->getAllVisibleItems();".
 * Not for calls of functions with template types, whose declaration can depend on the version of Magento.
 *
 * @implements Rule<Stmt\Expression>
 */
readonly class RedundantVarTagRule implements Rule
{
    public function __construct(
        private FileTypeMapper $fileTypeMapper,
        private ReflectionProvider $reflectionProvider
    ) {
    }

    public function getNodeType(): string
    {
        return Stmt\Expression::class;
    }

    /**
     * @param Stmt\Expression $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $docComment = $node->getDocComment();
        $assign = $node->expr;

        if ($docComment === null || ! $assign instanceof Expr\Assign) {
            return [];
        }

        $variable = $assign->var;
        $variableName = $variable instanceof Expr\Variable ? $variable->name : null;

        if (! is_string($variableName)) {
            return [];
        }

        $varTags = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $scope->getClassReflection()?->getName(),
            $scope->getTraitReflection()?->getName(),
            $scope->getFunction()?->getName(),
            $docComment->getText()
        )->getVarTags();

        if (! isset($varTags[ $variableName ])) {
            return [];
        }

        if ($this->callsGenericFunction($assign->expr, $scope)) {
            return [];
        }

        $varType = $varTags[ $variableName ]->getType();
        $valueType = $scope->getType($assign->expr);

        if (! $varType->isSuperTypeOf($valueType)->yes() || ! $valueType->isSuperTypeOf($varType)->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'The @var tag of $%s is redundant, the assigned value already has the type %s.',
                    $variableName,
                    $valueType->describe(VerbosityLevel::typeOnly())
                )
            )->identifier('infrangible.redundantVarTag')->build()
        ];
    }

    /**
     * Checks if the expression calls a function or method with template types, whose result type depends on its
     * arguments.
     *
     * Such a declaration can be new in a version of Magento (LayoutInterface::createBlock() returns the created class
     * since 2.4.9), the tag is needed for the older versions.
     *
     * @param Expr $expression
     * @param Scope $scope
     *
     * @return bool
     */
    private function callsGenericFunction(Expr $expression, Scope $scope): bool
    {
        $reflection = null;
        $name = $expression instanceof Expr\MethodCall || $expression instanceof Expr\StaticCall ?
            $expression->name : null;
        $class = $expression instanceof Expr\StaticCall ? $expression->class : null;

        if ($expression instanceof Expr\MethodCall && $name instanceof Node\Identifier) {
            $type = $scope->getType($expression->var);

            if ($type->hasMethod($name->toString())->yes()) {
                $reflection = $type->getMethod($name->toString(), $scope);
            }
        } elseif ($class instanceof Node\Name && $name instanceof Node\Identifier) {
            $type = $scope->resolveTypeByName($class);

            if ($type->hasMethod($name->toString())->yes()) {
                $reflection = $type->getMethod($name->toString(), $scope);
            }
        } elseif ($expression instanceof Expr\FuncCall && $expression->name instanceof Node\Name &&
            $this->reflectionProvider->hasFunction($expression->name, $scope)) {

            $reflection = $this->reflectionProvider->getFunction($expression->name, $scope);
        }

        if ($reflection === null) {
            return false;
        }

        foreach ($reflection->getVariants() as $variant) {
            if (! $variant->getTemplateTypeMap()->isEmpty()) {
                return true;
            }
        }

        return false;
    }
}
