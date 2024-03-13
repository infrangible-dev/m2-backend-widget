<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Adminhtml\Grid;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use Infrangible\BackendWidget\Helper\Session;
use Infrangible\Core\Controller\Adminhtml\Ajax;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Fields
    extends Ajax
{
    /** @var Session */
    protected $sessionHelper;

    /**
     * @param Arrays          $arrays
     * @param Json            $json
     * @param Context         $context
     * @param LoggerInterface $logging
     * @param Session         $sessionHelper
     */
    public function __construct(
        Arrays $arrays,
        Json $json,
        Context $context,
        LoggerInterface $logging,
        Session $sessionHelper
    ) {
        parent::__construct($arrays, $json, $context, $logging);

        $this->sessionHelper = $sessionHelper;
    }

    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $request = $this->getRequest();

        $dataGridId = $request->getParam('data_grid_id');
        $hiddenFieldList = $request->getParam('hidden_field_list', []);

        $this->sessionHelper->saveHiddenFieldList($dataGridId, $hiddenFieldList);

        $this->setSuccessResponse(__('Successfully stored hidden field list')->render());

        return $this->getResponse();
    }
}
