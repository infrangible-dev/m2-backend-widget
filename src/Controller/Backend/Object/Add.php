<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Magento\Framework\App\ResponseInterface;

abstract class Add extends Base
{
    /**
     * Executes the action.
     *
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $this->_forward('edit');

        return $this->getResponse();
    }
}
