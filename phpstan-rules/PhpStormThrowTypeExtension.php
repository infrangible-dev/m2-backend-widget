<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\PhpStan;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Adds the checked exceptions that the PHP stubs of PhpStorm declare to the throw types of PHP's functions and methods.
 *
 * PhpStorm requires them in the throws tag of the calling method (it reports "PHPDoc comment doesn't contain all the
 * necessary throws tags"), PHPStan does not know some of them, for example DOMException of DOMDocument::saveXML().
 * With this extension, PHPStan reports them as missing as well. The exceptions are listed in phpstorm-throws.php, which
 * tools/generate-phpstorm-throws.py generates from the stubs.
 */
class PhpStormThrowTypeExtension implements
    DynamicFunctionThrowTypeExtension,
    DynamicMethodThrowTypeExtension,
    DynamicStaticMethodThrowTypeExtension
{
    /** @var array{functions: array<string, list<string>>, methods: array<string, list<string>>}|null */
    private ?array $throws = null;

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return isset($this->getThrows()['functions'][ strtolower($functionReflection->getName()) ]);
    }

    public function getThrowTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $funcCall,
        Scope $scope
    ): ?Type {
        return $this->combine(
            $functionReflection->getThrowType(),
            $this->getThrows()['functions'][ strtolower($functionReflection->getName()) ] ?? []
        );
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return isset($this->getThrows()['methods'][ $this->getMethodKey($methodReflection) ]);
    }

    public function getThrowTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        return $this->getMethodThrowType($methodReflection);
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $this->isMethodSupported($methodReflection);
    }

    /**
     * Also called for "new", with the constructor as method.
     *
     * @param MethodReflection $methodReflection
     * @param StaticCall|New_ $methodCall
     * @param Scope $scope
     *
     * @return Type|null
     */
    public function getThrowTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall|New_ $methodCall,
        Scope $scope
    ): ?Type {
        return $this->getMethodThrowType($methodReflection);
    }

    /**
     * Returns the throw type of the method with the exceptions of the stubs.
     *
     * @param MethodReflection $methodReflection
     *
     * @return Type|null
     */
    private function getMethodThrowType(MethodReflection $methodReflection): ?Type
    {
        return $this->combine(
            $methodReflection->getThrowType(),
            $this->getThrows()['methods'][ $this->getMethodKey($methodReflection) ] ?? []
        );
    }

    /**
     * Returns the key of the method in the list: lower-case declaring class and method.
     *
     * @param MethodReflection $methodReflection
     *
     * @return string
     */
    private function getMethodKey(MethodReflection $methodReflection): string
    {
        return strtolower($methodReflection->getDeclaringClass()->getName() . '::' . $methodReflection->getName());
    }

    /**
     * Returns the throw type with the exceptions that exist in the analysed PHP version.
     *
     * @param Type|null $throwType
     * @param list<string> $exceptionNames
     *
     * @return Type|null
     */
    private function combine(?Type $throwType, array $exceptionNames): ?Type
    {
        $types = $throwType === null ? [] : [$throwType];

        foreach ($exceptionNames as $exceptionName) {
            if ($this->reflectionProvider->hasClass($exceptionName)) {
                $types[] = new ObjectType($this->reflectionProvider->getClass($exceptionName)->getName());
            }
        }

        return count($types) === 0 ? null : TypeCombinator::union(...$types);
    }

    /**
     * Returns the exceptions of the stubs.
     *
     * @return array{functions: array<string, list<string>>, methods: array<string, list<string>>}
     */
    private function getThrows(): array
    {
        if ($this->throws === null) {
            /** @var array{functions: array<string, list<string>>, methods: array<string, list<string>>} $throws */
            $throws = require __DIR__ . '/phpstorm-throws.php';

            $this->throws = $throws;
        }

        return $this->throws;
    }
}
