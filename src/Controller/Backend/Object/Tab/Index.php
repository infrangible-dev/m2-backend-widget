<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object\Tab;

use Magento\Backend\Block\Widget\Grid\Extended;

abstract class Index extends Grid
{
    /**
     * Renders the block.
     *
     * @param Extended $block
     *
     * @return string
     */
    protected function renderBlock(Extended $block): string
    {
        return sprintf(
            '<div id="tab_%s"><div data-role="messages"></div><div>%s</div></div>',
            $this->getObjectName(),
            $block->toHtml()
        );
    }

    /**
     * Checks whether it is tab.
     *
     * @return bool
     */
    protected function isTab(): bool
    {
        return true;
    }
}
