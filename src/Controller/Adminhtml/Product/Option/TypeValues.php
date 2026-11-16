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

class TypeValues extends Ajax
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
        $optionId = $this->getRequest()->getParam('option_id');

        $options = $this->productOptionHelper->getProductOptionTypeValues($this->variables->intValue($optionId));

        $this->addResponseValue(
            'options',
            $options
        );

        return $this->getResponse();
    }
}
