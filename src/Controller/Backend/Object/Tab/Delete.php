<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object\Tab;

use Infrangible\BackendWidget\Helper\Action;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;

abstract class Delete extends \Infrangible\BackendWidget\Controller\Backend\Object\Delete
{
    use Ajax;

    /**
     * @param Registry $registryHelper
     * @param Context $context
     * @param Instances $instanceHelper
     * @param LoggerInterface $logging
     * @param Session $session
     * @param Action $actionHelper
     */
    public function __construct(
        Registry $registryHelper,
        Context $context,
        Instances $instanceHelper,
        LoggerInterface $logging,
        Session $session,
        protected Action $actionHelper
    ) {
        parent::__construct(
            $registryHelper,
            $context,
            $instanceHelper,
            $logging,
            $session
        );
    }
}
