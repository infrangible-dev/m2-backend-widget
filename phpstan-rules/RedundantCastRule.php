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
use PhpParser\Node\Expr\Cast;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * A cast of a value that already has the type of the cast is redundant.
 *
 * PhpStorm reports it as "Type cast is redundant". Like PhpStorm, the declared types count, also those of
 * docblocks. For a call of a method with a body and without native return type, PhpStorm also uses the types that the
 * body returns, which PHPStan does not know: such a cast is only reported if the native type already has the type of
 * the cast (else PhpStorm would report a suppression as redundant, for example for Order\Item::getProductOptions(),
 * which declares an array but returns null for items without options). PHP's functions count with their declared
 * types of PhpStorm's stubs, which PHPStan narrows by their arguments: a function whose result can be false or null
 * there (base64_decode() without $strict is string|false, for PHPStan a string) is not reported, see
 * phpstorm-throws.php. If a declared type differs at runtime and
 * PhpStorm still reports the cast, keep it and add "@noinspection PhpCastIsUnnecessaryInspection" with a reason on the
 * line, the line before, the method or the file.
 *
 * @implements Rule<Cast>
 */
class RedundantCastRule implements Rule
{
    private const INSPECTION = 'PhpCastIsUnnecessaryInspection';

    /** @var array<string, list<string>> */
    private array $fileLines = [];

    /** @var array<string, int>|null functions whose result can be false or null, as keys */
    private ?array $nullableReturns = null;

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return Cast::class;
    }

    /**
     * @param Cast $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $castType = $this->getCastTypeName($node);

        if ($castType === null) {
            return [];
        }

        $type = $scope->getType($node->expr);

        if (! $this->hasType($node, $type)->yes() || $this->isSuppressed($node, $scope)) {
            return [];
        }

        if (! $this->hasType($node, $scope->getNativeType($node->expr))->yes() &&
            $this->callsMethodWithBody($node->expr, $scope)) {

            return [];
        }

        if ($this->callsNullableFunction($node->expr, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Casting to %s is redundant, the value already has the type %s.',
                    $castType,
                    $type->describe(VerbosityLevel::typeOnly())
                )
            )->identifier('infrangible.redundantCast')->build()
        ];
    }

    /**
     * Returns the name of the type of the cast, or null for casts that are not checked (object, unset).
     *
     * @param Cast $node
     *
     * @return string|null
     */
    private function getCastTypeName(Cast $node): ?string
    {
        return match (true) {
            $node instanceof Cast\Int_ => 'int',
            $node instanceof Cast\Double => 'float',
            $node instanceof Cast\String_ => 'string',
            $node instanceof Cast\Bool_ => 'bool',
            $node instanceof Cast\Array_ => 'array',
            default => null
        };
    }

    /**
     * Checks if the value has the type of the cast.
     *
     * @param Cast $node
     * @param Type $type
     *
     * @return TrinaryLogic
     */
    private function hasType(Cast $node, Type $type): TrinaryLogic
    {
        return match (true) {
            $node instanceof Cast\Int_ => $type->isInteger(),
            $node instanceof Cast\Double => $type->isFloat(),
            $node instanceof Cast\String_ => $type->isString(),
            $node instanceof Cast\Bool_ => $type->isBoolean(),
            default => $type->isArray()
        };
    }

    /**
     * Checks if the expression calls a PHP function whose result can be false or null in the stubs of PhpStorm.
     *
     * @param Expr $expression
     * @param Scope $scope
     *
     * @return bool
     */
    private function callsNullableFunction(Expr $expression, Scope $scope): bool
    {
        if (! $expression instanceof Expr\FuncCall || ! $expression->name instanceof Node\Name ||
            ! $this->reflectionProvider->hasFunction($expression->name, $scope)) {

            return false;
        }

        if ($this->nullableReturns === null) {
            /** @var array{nullable_returns: list<string>} $stubs */
            $stubs = require __DIR__ . '/phpstorm-throws.php';

            $this->nullableReturns = array_flip($stubs[ 'nullable_returns' ]);
        }

        $functionName = strtolower($this->reflectionProvider->getFunction($expression->name, $scope)->getName());

        return isset($this->nullableReturns[ $functionName ]);
    }

    /**
     * Checks if the expression calls a method with a body, which is not abstract and not declared in an interface.
     *
     * @param Expr $expression
     * @param Scope $scope
     *
     * @return bool
     */
    private function callsMethodWithBody(Expr $expression, Scope $scope): bool
    {
        if (! ($expression instanceof Expr\MethodCall || $expression instanceof Expr\NullsafeMethodCall ||
            $expression instanceof Expr\StaticCall)) {

            return false;
        }

        $name = $expression->name;
        $class = $expression instanceof Expr\StaticCall ? $expression->class : null;

        if (! $name instanceof Node\Identifier) {
            return false;
        }

        $type = $expression instanceof Expr\StaticCall ?
            ($class instanceof Node\Name ? $scope->resolveTypeByName($class) : null) :
            $scope->getType($expression->var);

        if ($type === null || ! $type->hasMethod($name->toString())->yes()) {
            return false;
        }

        $method = $type->getMethod($name->toString(), $scope);
        $abstract = $method->isAbstract();

        return ! $method->getDeclaringClass()->isInterface() &&
            ! ($abstract instanceof TrinaryLogic ? $abstract->yes() : $abstract);
    }

    /**
     * Checks if the inspection is suppressed on the line of the cast, the line before, the method or the file.
     *
     * @param Cast $node
     * @param Scope $scope
     *
     * @return bool
     */
    private function isSuppressed(Cast $node, Scope $scope): bool
    {
        $lines = $this->getFileLines($scope->getFile());
        $line = $node->getStartLine();

        foreach ([$line - 1, $line - 2] as $index) {
            if (isset($lines[ $index ]) && $this->suppresses($lines[ $index ])) {
                return true;
            }
        }

        $function = $scope->getFunction();
        $docComment = $function?->getDocComment();

        if ($docComment !== null && $this->suppresses($docComment)) {
            return true;
        }

        foreach ($lines as $fileLine) {
            if (preg_match('/^\s*((abstract|final|readonly)\s+)*(class|interface|trait|enum)\s/', $fileLine) === 1) {
                break;
            }

            if ($this->suppresses($fileLine)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the text contains a @noinspection tag for redundant casts or for all inspections.
     *
     * @param string $text
     *
     * @return bool
     */
    private function suppresses(string $text): bool
    {
        return preg_match('/@noinspection\s+[^\n]*\b(' . self::INSPECTION . '|ALL)\b/', $text) === 1;
    }

    /**
     * Returns the lines of the file.
     *
     * @param string $file
     *
     * @return list<string>
     */
    private function getFileLines(string $file): array
    {
        if (! isset($this->fileLines[ $file ])) {
            $content = @file_get_contents($file);

            $this->fileLines[ $file ] = $content === false ? [] : explode("\n", $content);
        }

        return $this->fileLines[ $file ];
    }
}
