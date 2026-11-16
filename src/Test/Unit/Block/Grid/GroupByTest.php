<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Test\Unit\Block\Grid;

use Infrangible\BackendWidget\Block\Grid\GroupBy;
use Magento\Backend\Model\Session;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests that only plain column names from the request are used to group the grid, because they are added to the
 * query.
 */
class GroupByTest extends TestCase
{
    /**
     * Returns a grid without its dependencies that gets the parameter group_by from the request.
     *
     * @param string|null $groupBy
     *
     * @return GroupBy
     * @throws \ReflectionException
     */
    private function createGrid(?string $groupBy): GroupBy
    {
        $grid = (new \ReflectionClass(TestGroupBy::class))->newInstanceWithoutConstructor();

        $grid->setData(
            'id',
            'grid'
        );

        $request = $this->createStub(Http::class);
        $request->method('has')->willReturn($groupBy !== null);
        $request->method('getParam')->willReturn($groupBy);

        $requestProperty = new \ReflectionProperty(GroupBy::class, '_request');
        $requestProperty->setValue($grid, $request);

        $sessionProperty = new \ReflectionProperty(GroupBy::class, '_backendSession');
        $sessionProperty->setValue($grid, $this->createStub(Session::class));

        return $grid;
    }

    /**
     * Returns the encoded parameter and the field names to group by.
     *
     * @return array
     * @phpstan-return array<string, array{string|null, array<int, string>}>
     */
    public static function getGroupByParameters(): array
    {
        return [
            'no parameter'    => [null, []],
            'empty parameter' => ['', []],
            'plain names'     => [base64_encode('name, sku'), ['name', 'sku']],
            'grid column'     => [base64_encode('namegridcolumn'), ['namegridcolumn']],
            'expression'      => [base64_encode('name,(SELECT password FROM admin_user)'), ['name']],
            'qualified name'  => [base64_encode('main_table.name'), []],
            'no base64'       => ['%%%', []]
        ];
    }

    /**
     * Tests that only plain column names are accepted.
     *
     * @dataProvider getGroupByParameters
     *
     * @param string|null $groupBy
     * @param array $expected
     *
     * @phpstan-param array<int, string> $expected
     *
     * @return void
     * @throws \ReflectionException
     */
    #[DataProvider('getGroupByParameters')]
    public function testGetGroupByFieldNames(?string $groupBy, array $expected): void
    {
        $method = new \ReflectionMethod(GroupBy::class, 'getGroupByFieldNames');

        $this->assertSame($expected, $method->invoke($this->createGrid($groupBy)));
    }
}
