<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Test\Unit\Block;

use Infrangible\BackendWidget\Block\LayoutArguments;

/**
 * Makes the methods of the trait public, only used in the tests.
 */
class TestLayoutArguments
{
    use LayoutArguments {
        stringArgument as public;
        nullableStringArgument as public;
        boolArgument as public;
        arrayArgument as public;
    }
}
