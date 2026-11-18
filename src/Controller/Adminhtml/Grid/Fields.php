<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Controller\Adminhtml\Grid;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use Infrangible\BackendWidget\Helper\Session;
use Infrangible\Core\Controller\Adminhtml\Ajax;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;

class Fields extends Ajax
{
    /** Every admin user may change the columns of the grids they see; stored in their own session. */
    public const ADMIN_RESOURCE = 'Magento_Backend::admin';

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
        protected Session $sessionHelper
    ) {
        parent::__construct($arrays, $json, $context, $logging);
    }

    /**
     * Executes the action.
     *
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $request = $this->getRequest();

        $dataGridId = $request->getParam('data_grid_id');
        $hiddenFieldList = $request->getParam('hidden_field_list', []);

        $this->sessionHelper->saveHiddenFieldList(
            is_scalar($dataGridId) ? (string)$dataGridId : '',
            $this->sessionHelper->getFieldNames($hiddenFieldList)
        );

        $this->setSuccessResponse(__('Successfully stored hidden field list')->render());

        return $this->getResponse();
    }
}
