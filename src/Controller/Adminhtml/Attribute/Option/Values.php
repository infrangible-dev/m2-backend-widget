<?php

namespace Infrangible\BackendWidget\Controller\Adminhtml\Attribute\Option;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;
use Infrangible\Core\Controller\Adminhtml\Ajax;
use Infrangible\Core\Helper\Attribute;
use Tofex\Help\Arrays;
use Tofex\Help\Json;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Values
    extends Ajax
{
    /** @var Attribute */
    protected $eavAttributeHelper;

    /**
     * @param Context         $context
     * @param LoggerInterface $logging
     * @param Arrays          $arrayHelper
     * @param Json            $jsonHelper
     * @param Attribute       $eavAttributeHelper
     */
    public function __construct(
        Arrays $arrayHelper,
        Json $jsonHelper,
        Attribute $eavAttributeHelper,
        Context $context,
        LoggerInterface $logging)
    {
        parent::__construct($arrayHelper, $jsonHelper, $context, $logging);

        $this->eavAttributeHelper = $eavAttributeHelper;
    }

    /**
     * @return ResponseInterface
     * @throws Exception
     */
    public function execute(): ResponseInterface
    {
        $attributeId = $this->getRequest()->getParam('attribute_id');

        $attribute = $this->eavAttributeHelper->getAttribute(Product::ENTITY, $attributeId);

        if ($attribute->usesSource()) {
            $valueOptions = $attribute->getSource()->getAllOptions();

            $this->addResponseValue('options', $valueOptions);
        }

        return $this->getResponse();
    }
}
