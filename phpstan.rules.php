<?php

declare(strict_types=1);

/**
 * PHPStan rules for the conventions of PhpStorm that PHPStan does not check itself (classes in phpstan-rules).
 *
 * Included by phpstan.neon as configuration, because PHPStan creates the rules before it loads the bootstrap files.
 *
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

require_once __DIR__ . '/phpstan-rules/DefaultArgumentRule.php';
require_once __DIR__ . '/phpstan-rules/NeverThrownCatchRule.php';
require_once __DIR__ . '/phpstan-rules/OwnThrowsRule.php';

return [
    'rules' => [
        // a literal as last argument that is the default value of the parameter
        Infrangible\PhpStan\DefaultArgumentRule::class,
        // a catch of a checked exception that no call of the try block declares
        Infrangible\PhpStan\NeverThrownCatchRule::class,
        // checked exceptions declared only in the @throws tag of a parent method
        Infrangible\PhpStan\OwnThrowsRule::class
    ]
];
