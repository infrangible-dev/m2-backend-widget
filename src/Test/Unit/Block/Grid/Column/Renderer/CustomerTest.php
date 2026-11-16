<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Test\Unit\Block\Grid\Column\Renderer;

use Infrangible\BackendWidget\Block\Grid\Column\Renderer\Customer;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\Description;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\Product;
use Magento\Backend\Block\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use PHPUnit\Framework\TestCase;

/**
 * Tests that the grid column renderers escape the values of the rows, which can come from customers.
 */
class CustomerTest extends TestCase
{
    /**
     * Returns a block context with a real escaper.
     *
     * @return Context
     */
    private function createContext(): Context
    {
        $context = $this->createStub(Context::class);

        $context->method('getEscaper')->willReturn(new Escaper());

        return $context;
    }

    /**
     * Returns a column with the index and other data.
     *
     * @param array $data
     *
     * @phpstan-param array<string, mixed> $data
     *
     * @return \Magento\Backend\Block\Widget\Grid\Column
     * @noinspection PhpDeprecationInspection, PhpFullyQualifiedNameUsageInspection The renderers of the legacy grid
     *               need the deprecated column.
     */
    private function createColumn(array $data): \Magento\Backend\Block\Widget\Grid\Column
    {
        $column = $this->createStub(\Magento\Backend\Block\Widget\Grid\Column::class);

        $column->method('getData')->willReturnCallback(
            fn(string $key = '') => $data[ $key ] ?? null
        );

        return $column;
    }

    /**
     * Tests that the names of customers are escaped.
     *
     * @return void
     */
    public function testRenderEscapesCustomerName(): void
    {
        $renderer = new Customer($this->createContext());

        $renderer->setColumn($this->createColumn(['index' => 'customer_id']));

        $row = new DataObject(
            [
                'customer_id_firstname' => '<script>alert(1)</script>',
                'customer_id_lastname'  => 'Doe & Sons'
            ]
        );

        $this->assertSame(
            '&lt;script&gt;alert(1)&lt;/script&gt; Doe &amp; Sons',
            $renderer->render($row)
        );
    }

    /**
     * Tests that the names of products are escaped.
     *
     * @return void
     */
    public function testRenderEscapesProductName(): void
    {
        $renderer = new Product($this->createContext());

        $renderer->setColumn($this->createColumn(['index' => 'product_id']));

        $this->assertSame(
            '&lt;b&gt;Shirt&lt;/b&gt;',
            $renderer->render(new DataObject(['product_id_product_name' => '<b>Shirt</b>']))
        );
    }

    /**
     * Tests that the description and the size of the text area are escaped.
     *
     * @return void
     */
    public function testRenderEscapesDescription(): void
    {
        $renderer = new Description($this->createContext());

        $renderer->setColumn(
            $this->createColumn(['index' => 'description', 'width' => '100%"><script>', 'height' => '50px'])
        );

        $html = $renderer->render(new DataObject(['description' => '</textarea><script>alert(1)</script>']));

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;/textarea&gt;&lt;script&gt;', $html);
        $this->assertSame('', $renderer->render(new DataObject([])));
    }
}
