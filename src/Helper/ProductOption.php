<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Helper;

use Infrangible\Core\Helper\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory;
use Magento\Framework\Data\Collection;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ProductOption
{
    /** @var Product */
    protected $productHelper;

    /** @var CollectionFactory */
    protected $optionValueCollectionFactory;

    public function __construct(
        Product $productHelper,
        CollectionFactory $optionValueCollectionFactory
    ) {
        $this->productHelper = $productHelper;
        $this->optionValueCollectionFactory = $optionValueCollectionFactory;
    }

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

            $optionValues = [];

            /** @var Value $productOptionValue */
            foreach ($productOptionValues as $productOptionValue) {
                $optionValues[] = [
                    'value' => $productOptionValue->getId(),
                    'label' => $productOptionValue->getTitle()
                ];
            }

            $options[] = [
                'value' => $optionValues,
                'label' => $productOption->getTitle()
            ];
        }

        return $options;
    }

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
