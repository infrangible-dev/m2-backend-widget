<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Adminhtml\Product;

use Magento\Backend\Block\Template;

abstract class Tab extends Template
{
    /** @var string */
    protected $_template = 'Infrangible_BackendWidget::catalog/product/tab.phtml';

    /** @var string */
    private string $tabId;

    /**
     * Constructor.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->tabId = sprintf(
            'product_tab_%s',
            rand(
                1000000,
                9999999
            )
        );

        parent::_construct();
    }

    /**
     * Returns the tab id.
     *
     * @return string
     */
    public function getTabId(): string
    {
        return $this->tabId;
    }

    /**
     * Returns the tab url.
     *
     * @return string
     */
    abstract public function getTabUrl(): string;
}
