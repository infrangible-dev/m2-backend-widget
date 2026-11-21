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
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * An instanceof check of a value whose declared type is an interface or abstract class of the module against the only
 * class of the module that implements it, also as assertInstanceOf() of PHPUnit.
 *
 * PhpStorm knows all classes of the project and reports such a check as "Condition is always 'true' because '$x' is
 * evaluated at this point" ("Assertion is always 'true'" for assertInstanceOf()); PHPStan only knows the declared
 * type. Instead of the check, the interface should declare the methods that the code needs.
 *
 * @implements Rule<Expr>
 */
class OnlyImplementationInstanceofRule implements Rule
{
    /** @var array<string, list<string>> classes declared in the files of a module, by module directory */
    private array $moduleClasses = [];

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
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
        $check = $this->getCheck($node);

        if ($check === null) {
            return [];
        }

        [$checkedName, $checkedExpression] = $check;

        $checkedClassName = $scope->resolveName($checkedName);
        $declaredClassNames = $scope->getType($checkedExpression)->getObjectClassNames();

        if (count($declaredClassNames) !== 1 || ! $this->reflectionProvider->hasClass($checkedClassName) ||
            ! $this->reflectionProvider->hasClass($declaredClassNames[ 0 ])) {

            return [];
        }

        $declaredClass = $this->reflectionProvider->getClass($declaredClassNames[ 0 ]);
        $checkedClass = $this->reflectionProvider->getClass($checkedClassName);

        if (! ($declaredClass->isInterface() || $declaredClass->isAbstract()) || $checkedClass->isInterface() ||
            ! $checkedClass->isSubclassOf($declaredClass->getName())) {

            return [];
        }

        $moduleDirectory = $this->getModuleDirectory($scope->getFile());

        if ($moduleDirectory === null) {
            return [];
        }

        $moduleClassNames = $this->getModuleClasses($moduleDirectory);

        if (! in_array($declaredClass->getName(), $moduleClassNames, true)) {
            return [];
        }

        foreach ($moduleClassNames as $moduleClassName) {
            if ($moduleClassName === $checkedClass->getName() ||
                ! $this->reflectionProvider->hasClass($moduleClassName)) {

                continue;
            }

            $moduleClass = $this->reflectionProvider->getClass($moduleClassName);

            if (! $moduleClass->isInterface() && ! $moduleClass->isAbstract() &&
                $moduleClass->isSubclassOf($declaredClass->getName()) &&
                ! $moduleClass->isSubclassOf($checkedClass->getName())) {

                return [];
            }
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Instanceof %s is always true: it is the only class of the module that implements %s. Declare ' .
                    'the needed methods in %s instead.',
                    $checkedClass->getName(),
                    $declaredClass->getName(),
                    $declaredClass->getName()
                )
            )->identifier('infrangible.onlyImplementationInstanceof')->build()
        ];
    }

    /**
     * Returns the class and the value of an instanceof check or of an assertInstanceOf() call of PHPUnit, or null.
     *
     * @param Expr $node
     *
     * @return array{0: Name, 1: Expr}|null
     */
    private function getCheck(Expr $node): ?array
    {
        if ($node instanceof Instanceof_) {
            $checkedClass = $node->class;

            return $checkedClass instanceof Name ? [$checkedClass, $node->expr] : null;
        }

        if (! ($node instanceof Expr\MethodCall || $node instanceof Expr\StaticCall)) {
            return null;
        }

        $name = $node->name;

        if (! $name instanceof Node\Identifier || $name->toLowerString() !== 'assertinstanceof' ||
            $node->isFirstClassCallable()) {

            return null;
        }

        $arguments = $node->getArgs();
        $classConstant = count($arguments) >= 2 ? $arguments[ 0 ]->value : null;

        if (! $classConstant instanceof Expr\ClassConstFetch) {
            return null;
        }

        $class = $classConstant->class;
        $constantName = $classConstant->name;

        return $class instanceof Name && $constantName instanceof Node\Identifier &&
            $constantName->toLowerString() === 'class' ? [$class, $arguments[ 1 ]->value] : null;
    }

    /**
     * Returns the directory of the module of the file: the nearest directory above it with a composer.json.
     *
     * @param string $file
     *
     * @return string|null
     */
    private function getModuleDirectory(string $file): ?string
    {
        $directory = dirname($file);

        while ($directory !== dirname($directory)) {
            if (is_file($directory . '/composer.json')) {
                return $directory;
            }

            $directory = dirname($directory);
        }

        return null;
    }

    /**
     * Returns the names of the classes, interfaces and traits declared in the PHP files of the module.
     *
     * @param string $moduleDirectory
     *
     * @return list<string>
     */
    private function getModuleClasses(string $moduleDirectory): array
    {
        if (isset($this->moduleClasses[ $moduleDirectory ])) {
            return $this->moduleClasses[ $moduleDirectory ];
        }

        $classNames = [];
        $sourceDirectory = is_dir($moduleDirectory . '/src') ? $moduleDirectory . '/src' : $moduleDirectory;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDirectory, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/vendor/')) {
                continue;
            }

            $content = (string)file_get_contents($file->getPathname());

            $namespace = preg_match('/^namespace\s+([^;\s]+)\s*;/m', $content, $matches) === 1 ? $matches[ 1 ] : '';

            if (preg_match_all(
                '/^\s*(?:(?:abstract|final|readonly)\s+)*(?:class|interface|trait|enum)\s+(\w+)/m',
                $content,
                $matches
            )) {
                foreach ($matches[ 1 ] as $className) {
                    $classNames[] = ltrim($namespace . '\\' . $className, '\\');
                }
            }
        }

        return $this->moduleClasses[ $moduleDirectory ] = $classNames;
    }
}
