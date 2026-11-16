<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Plugin\Backend\Block\System\Account;

use Magento\Framework\View\LayoutInterface;

class Edit
{
    /**
     * Adds a button to the account settings that resets the column selection of all grids.
     *
     * @param \Magento\Backend\Block\System\Account\Edit $subject
     * @param LayoutInterface                            $layout
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function beforeSetLayout(\Magento\Backend\Block\System\Account\Edit $subject, LayoutInterface $layout): array
    {
        $subject->addButton('reset_columns_selection', [
            'label'   => __('Reset Columns Selection'),
            'onclick' => sprintf('setLocation(\'%s\')', $subject->getUrl('infrangible_backendwidget/grid/reset')),
            'class'   => 'action-secondary'
        ], -1, 10);

        return [$layout];
    }
}
