<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\PhpStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\ObjectType;

/**
 * Checked exceptions that a method lets through have to be declared in its own @throws tag.
 *
 * PHPStan inherits the @throws tag of the parent method (for example of the constructor of Magento's models), PhpStorm
 * does not: it reports "PHPDoc comment doesn't contain all the necessary @throws tags" for the method. Exceptions that
 * are not declared at all are reported by PHPStan itself (exceptions.check.missingCheckedExceptionInThrows).
 *
 * @implements Rule<MethodReturnStatementsNode>
 */
class OwnThrowsRule implements Rule
{
    public function __construct(
        private readonly FileTypeMapper $fileTypeMapper,
        private readonly DefaultExceptionTypeResolver $exceptionTypeResolver
    ) {
    }

    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }

    /**
     * @param MethodReturnStatementsNode $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $method = $node->getMethodReflection();
        $inheritedThrowType = $method->getThrowType();

        if ($inheritedThrowType === null) {
            return [];
        }

        $ownThrowType = null;
        $docComment = $node->getDocComment();

        if ($docComment !== null) {
            $phpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
                $scope->getFile(),
                $scope->getClassReflection()?->getName(),
                $scope->getTraitReflection()?->getName(),
                $method->getName(),
                $docComment->getText()
            );

            $ownThrowType = $phpDoc->getThrowsTag()?->getType();
        }

        $errors = [];
        $reported = [];

        foreach ($node->getStatementResult()->getThrowPoints() as $throwPoint) {
            if (! $throwPoint->isExplicit()) {
                continue;
            }

            foreach ($throwPoint->getType()->getObjectClassNames() as $className) {
                if (isset($reported[ $className ]) ||
                    ! $this->exceptionTypeResolver->isCheckedException($className, $throwPoint->getScope())) {

                    continue;
                }

                $exceptionType = new ObjectType($className);

                // not declared at all: PHPStan reports it itself
                if (! $inheritedThrowType->isSuperTypeOf($exceptionType)->yes()) {
                    continue;
                }

                if ($ownThrowType !== null && $ownThrowType->isSuperTypeOf($exceptionType)->yes()) {
                    continue;
                }

                $reported[ $className ] = true;

                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Method %s::%s() throws checked exception %s, which is only declared in the PHPDoc of a parent ' .
                        'method; PhpStorm requires it in the @throws tag of the method itself.',
                        $method->getDeclaringClass()->getDisplayName(),
                        $method->getName(),
                        $className
                    )
                )->identifier('infrangible.ownThrows')->line($throwPoint->getNode()->getStartLine())->build();
            }
        }

        return $errors;
    }
}
