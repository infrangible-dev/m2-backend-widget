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
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A test double of PHPUnit created inside a closure.
 *
 * Inside a closure, PhpStorm does not resolve the template type of createStub() and createMock() for generated classes
 * like factories, and reports "Expected parameter of type 'XFactory', 'object&Stub|Stub' provided". Create the objects
 * that the closure returns before it, for example in a helper method, and return them with willReturn() instead of a
 * callback (passing the test doubles with "use" would be reported as "Variable is only used inside closure").
 *
 * @implements Rule<Expr>
 */
class TestDoubleInClosureRule implements Rule
{
    private const METHODS = [
        'createstub',
        'createmock',
        'createpartialmock',
        'createconfiguredmock',
        'createconfiguredstub',
        'getmockbuilder'
    ];

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
        $name = $node instanceof Expr\MethodCall || $node instanceof Expr\StaticCall ? $node->name : null;

        if (! $name instanceof Node\Identifier || ! in_array($name->toLowerString(), self::METHODS, true) ||
            ! $scope->isInAnonymousFunction()) {

            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Create the test double of %s() outside of the closure, for example in a helper method: ' .
                    'PhpStorm does not resolve its type inside a closure.',
                    $name->toString()
                )
            )->identifier('infrangible.testDoubleInClosure')->build()
        ];
    }
}
