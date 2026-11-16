<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Controller\Adminhtml\Attribute\Option;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use Infrangible\Core\Controller\Adminhtml\Ajax;
use Infrangible\Core\Helper\Attribute;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Values extends Ajax
{
    /**
     * @param Arrays $arrays
     * @param Json $json
     * @param Attribute $eavAttributeHelper
     * @param Context $context
     * @param LoggerInterface $logging
     */
    public function __construct(
        Arrays $arrays,
        Json $json,
        protected Attribute $eavAttributeHelper,
        Context $context,
        LoggerInterface $logging
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
     * @throws LocalizedException
     */
    public function execute(): ResponseInterface
    {
        $attributeId = $this->getRequest()->getParam('attribute_id');

        $attribute = $this->eavAttributeHelper->getAttribute(
            Product::ENTITY,
            is_scalar($attributeId) ? (string)$attributeId : ''
        );

        if ($attribute->usesSource()) {
            $valueOptions = $attribute->getSource()->getAllOptions();

            $this->addResponseValue(
                'options',
                $valueOptions
            );
        }

        return $this->getResponse();
    }
}
