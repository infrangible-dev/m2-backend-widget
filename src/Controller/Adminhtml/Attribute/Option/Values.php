<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Adminhtml\Attribute\Option;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use Infrangible\Core\Controller\Adminhtml\Ajax;
use Infrangible\Core\Helper\Attribute;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
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
     * @param Arrays          $arrays
     * @param Json            $json
     * @param Attribute       $eavAttributeHelper
     */
    public function __construct(
        Arrays $arrays,
        Json $json,
        Attribute $eavAttributeHelper,
        Context $context,
        LoggerInterface $logging
    ) {
        parent::__construct($arrays, $json, $context, $logging);

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
