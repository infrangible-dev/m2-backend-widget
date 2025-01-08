<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Adminhtml\Product;

use Magento\Backend\Block\Template;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Tab extends Template
{
    protected $_template = 'Infrangible_BackendWidget::catalog/product/tab.phtml';

    /** @var string */
    private $tabId;

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

    public function getTabId(): string
    {
        return $this->tabId;
    }

    abstract public function getTabUrl(): string;
}
