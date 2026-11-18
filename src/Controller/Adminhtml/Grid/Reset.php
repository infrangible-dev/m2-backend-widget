<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Controller\Adminhtml\Grid;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Infrangible\BackendWidget\Helper\Session;

class Reset extends Action
{
    /** Every admin user may reset the columns of the grids they see; stored in their own session. */
    public const ADMIN_RESOURCE = 'Magento_Backend::admin';

    /**
     * @param Context $context
     * @param Session $sessionHelper
     */
    public function __construct(
        Context $context,
        protected Session $sessionHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Executes the action.
     *
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $this->sessionHelper->resetHiddenFieldList();

        $this->messageManager->addSuccessMessage(__('Successfully reset all columns selections.')->render());

        return $this->_redirect('adminhtml/system_account/index');
    }
}
