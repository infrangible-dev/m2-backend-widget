<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Adminhtml\Product\Option;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Helper\ProductOption;
use Infrangible\Core\Controller\Adminhtml\Ajax;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Index extends Ajax
{
    /** @var Variables */
    protected $variables;

    /** @var ProductOption */
    protected $productOptionHelper;

    public function __construct(
        Arrays $arrays,
        Json $json,
        Context $context,
        LoggerInterface $logging,
        Variables $variables,
        ProductOption $productOptionHelper
    ) {
        parent::__construct(
            $arrays,
            $json,
            $context,
            $logging
        );

        $this->variables = $variables;
        $this->productOptionHelper = $productOptionHelper;
    }

    /**
     * @throws \Exception
     */
    public function execute(): ResponseInterface
    {
        $productId = $this->getRequest()->getParam('product_id');

        $options = $this->productOptionHelper->getProductOptions($this->variables->intValue($productId));

        $this->addResponseValue(
            'options',
            $options
        );

        return $this->getResponse();
    }
}