<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Test\Unit\Block\Grid;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Block\Grid\GroupBy;
use Infrangible\BackendWidget\Helper\Grid;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Session;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Filesystem;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validator\UniversalFactory;
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
     */
    private function createGrid(?string $groupBy): GroupBy
    {
        $request = $this->createStub(Http::class);
        $request->method('has')->willReturn($groupBy !== null);
        $request->method('getParam')->willReturn($groupBy);

        // Magento's backend template gets the JSON and directory helpers from the object manager
        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback($this->createStubOfType(...));
        ObjectManager::setInstance($objectManager);

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getBackendSession')->willReturn($this->createStub(Session::class));
        $context->method('getFilesystem')->willReturn($this->createStub(Filesystem::class));

        /** @noinspection PhpDeprecationInspection, PhpFullyQualifiedNameUsageInspection Magento's grid needs it */
        $backendHelper = $this->createStub(\Magento\Backend\Helper\Data::class);

        $grid = new TestGroupBy(
            $context,
            $backendHelper,
            $this->createStub(Database::class),
            $this->createStub(Arrays::class),
            $this->createStub(Variables::class),
            $this->createStub(Registry::class),
            $this->createStub(Grid::class),
            $this->createStub(\Infrangible\BackendWidget\Helper\Session::class),
            $this->createStub(UniversalFactory::class),
            $this->createStub(Config::class)
        );

        $grid->setData(
            'id',
            'grid'
        );

        return $grid;
    }

    /**
     * Returns a stub of the requested class, as the object manager does.
     *
     * @param string $type
     * @phpstan-param class-string $type
     *
     * @return object
     */
    private function createStubOfType(string $type): object
    {
        return $this->createStub($type);
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
