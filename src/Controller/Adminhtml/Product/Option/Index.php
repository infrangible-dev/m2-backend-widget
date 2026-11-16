<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Controller\Adminhtml\Product\Option;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Helper\ProductOption;
use Infrangible\Core\Controller\Adminhtml\Ajax;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;

class Index extends Ajax
{
    /**
     * @param Arrays $arrays
     * @param Json $json
     * @param Context $context
     * @param LoggerInterface $logging
     * @param Variables $variables
     * @param ProductOption $productOptionHelper
     */
    public function __construct(
        Arrays $arrays,
        Json $json,
        Context $context,
        LoggerInterface $logging,
        protected Variables $variables,
        protected ProductOption $productOptionHelper
    ) {
        parent::__construct(
            $arrays,
            $json,
            $context,
            $logging
        );
    }

    /**
     * Executes the action.
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function execute(): ResponseInterface
    {
        $productId = $this->getRequest()->getParam('product_id');
        $includeWithValues = boolval(
            $this->getRequest()->getParam(
                'include_with_values',
                0
            )
        );
        $excludeWithoutValues = boolval(
            $this->getRequest()->getParam(
                'exclude_without_values',
                0
            )
        );

        $options = $this->productOptionHelper->getProductOptions(
            $this->variables->intValue($productId),
            $includeWithValues,
            $excludeWithoutValues
        );

        $this->addResponseValue(
            'options',
            $options
        );

        return $this->getResponse();
    }
}
