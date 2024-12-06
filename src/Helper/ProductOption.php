<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Helper;

use Infrangible\Core\Helper\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ProductOption
{
    /** @var Product */
    protected $productHelper;

    public function __construct(Product $productHelper)
    {
        $this->productHelper = $productHelper;
    }

    public function getProductOptionValues(int $productId): array
    {
        $product = $this->productHelper->loadProduct($productId);

        $options = [['value' => '', 'label' => __('No selection')]];

        /** @var Option $productOption */
        foreach ($product->getProductOptionsCollection() as $productOption) {
            $optionValues = [];

            /** @var Value $productOptionValue */
            foreach ($productOption->getValues() as $productOptionValue) {
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
}