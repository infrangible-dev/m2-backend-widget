<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Test\Unit\Block\Grid;

use Infrangible\BackendWidget\Block\Grid\GroupBy;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Grid with the abstract methods implemented, only used in the tests.
 */
class TestGroupBy extends GroupBy
{
    /**
     * Returns no fields that can not be grouped.
     *
     * @return array
     * @phpstan-return array<int, string>
     */
    public function getNotGroupableFieldNames(): array
    {
        return [];
    }

    /**
     * Does not prepare the collection.
     *
     * @param AbstractDb $collection
     *
     * @return void
     */
    protected function prepareCollection(AbstractDb $collection): void // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    {
    }

    /**
     * Does not add fields.
     *
     * @return void
     */
    protected function prepareFields(): void // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    {
    }

    /**
     * Returns no hidden fields.
     *
     * @return string[]
     */
    protected function getHiddenFieldNames(): array
    {
        return [];
    }
}
