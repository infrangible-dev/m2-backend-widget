<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Helper;

use Infrangible\Core\Helper\Product;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory;
use Magento\Framework\Data\Collection;

class ProductOption
{
    /**
     * @param Product $productHelper
     * @param CollectionFactory $optionValueCollectionFactory
     */
    public function __construct(
        protected Product $productHelper,
        protected CollectionFactory $optionValueCollectionFactory
    ) {
    }

    /**
     * Returns the product options.
     *
     * @param int $productId
     * @param bool $includeWithValues
     * @param bool $excludeWithoutValues
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function getProductOptions(int $productId, bool $includeWithValues, bool $excludeWithoutValues): array
    {
        $product = $this->productHelper->loadProduct($productId);

        $options = [['value' => '', 'label' => __('No selection')]];

        /** @var Option $productOption */
        foreach ($product->getProductOptionsCollection() as $productOption) {
            $productOptionValues = $productOption->getValues();

            if (! $includeWithValues && $productOptionValues !== null) {
                continue;
            }

            if ($excludeWithoutValues && $productOptionValues === null) {
                continue;
            }

            $options[] = [
                'value' => $productOption->getId(),
                'label' => $productOption->getTitle()
            ];
        }

        return $options;
    }

    /**
     * Returns the product option values.
     *
     * @param int $productId
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function getProductOptionValues(int $productId): array
    {
        $product = $this->productHelper->loadProduct($productId);

        $options = [['value' => '', 'label' => __('No selection')]];

        /** @var Option $productOption */
        foreach ($product->getProductOptionsCollection() as $productOption) {
            $productOptionValues = $productOption->getValues();

            if ($productOptionValues === null) {
                continue;
            }

            $options[] = [
                'value' => array_map(
                    static fn(ProductCustomOptionValuesInterface $productOptionValue): array => [
                        'value' => $productOptionValue->getOptionTypeId(),
                        'label' => $productOptionValue->getTitle()
                    ],
                    $productOptionValues
                ),
                'label' => $productOption->getTitle()
            ];
        }

        return $options;
    }

    /**
     * Returns the product option type values.
     *
     * @param int $optionId
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function getProductOptionTypeValues(int $optionId): array
    {
        $collection = $this->optionValueCollectionFactory->create();

        $collection->addTitleToResult(0);

        $collection->addOptionToFilter($optionId);

        $collection->setOrder(
            'sort_order',
            Collection::SORT_ORDER_ASC
        );

        $collection->setOrder(
            'title',
            Collection::SORT_ORDER_ASC
        );

        $options = [['value' => '', 'label' => __('No selection')]];

        /** @var Value $productOptionValue */
        foreach ($collection as $productOptionValue) {
            $options[] = [
                'value' => $productOptionValue->getOptionTypeId(),
                'label' => $productOptionValue->getTitle()
            ];
        }

        return $options;
    }
}
