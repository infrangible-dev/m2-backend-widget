<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Test\Unit\Block;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the conversion of the layout arguments of the blocks to the types of their properties.
 */
class LayoutArgumentsTest extends TestCase
{
    /**
     * Returns an object that makes the methods of the trait public.
     *
     * @return TestLayoutArguments
     */
    private function createReader(): TestLayoutArguments
    {
        return new TestLayoutArguments();
    }

    /**
     * Tests that string arguments are read case-insensitively and converted to string.
     *
     * @return void
     */
    public function testStringArgument(): void
    {
        $reader = $this->createReader();

        $data = ['Object_Name' => 'Product', 'limit' => 8, 'empty' => null];

        $this->assertSame('Product', $reader->stringArgument($data, 'object_name'));
        $this->assertSame('8', $reader->stringArgument($data, 'limit'));
        $this->assertSame('default', $reader->stringArgument($data, 'missing', 'default'));
        $this->assertSame('default', $reader->stringArgument($data, 'empty', 'default'));
        $this->assertNull($reader->nullableStringArgument($data, 'missing'));
        $this->assertSame('Product', $reader->nullableStringArgument($data, 'object_name'));
    }

    /**
     * Tests that a string argument that is no scalar value is rejected.
     *
     * @return void
     */
    public function testStringArgumentRejectsArrays(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createReader()->stringArgument(['object_name' => ['Product']], 'object_name');
    }

    /**
     * Returns layout values and the boolean they stand for.
     *
     * @return array
     * @phpstan-return array<string, array{mixed, bool}>
     */
    public static function getBooleanValues(): array
    {
        return [
            'boolean true'  => [true, true],
            'boolean false' => [false, false],
            'string 1'      => ['1', true],
            'string 0'      => ['0', false],
            'string true'   => ['true', true],
            'string false'  => [' False ', false],
            'string no'     => ['no', false],
            'empty string'  => ['', false],
            'integer'       => [1, true]
        ];
    }

    /**
     * Tests the conversion of layout values to booleans.
     *
     * @dataProvider getBooleanValues
     *
     * @param mixed $value
     * @param bool  $expected
     *
     * @return void
     */
    #[DataProvider('getBooleanValues')]
    public function testBoolArgument(mixed $value, bool $expected): void
    {
        $this->assertSame(
            $expected,
            $this->createReader()->boolArgument(
                ['allow_add' => $value],
                'allow_add',
                ! $expected
            )
        );
    }

    /**
     * Tests that missing booleans and arrays get their default value.
     *
     * @return void
     */
    public function testDefaultValues(): void
    {
        $reader = $this->createReader();

        $this->assertTrue($reader->boolArgument([], 'allow_add', true));
        $this->assertSame(['id' => 1], $reader->arrayArgument([], 'url_params', ['id' => 1]));
        $this->assertSame(['id' => 2], $reader->arrayArgument(['url_params' => ['id' => 2]], 'url_params'));
    }

    /**
     * Tests that an array argument that is no array is rejected.
     *
     * @return void
     */
    public function testArrayArgumentRejectsStrings(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createReader()->arrayArgument(['url_params' => 'id'], 'url_params');
    }
}
