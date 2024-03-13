<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Plugin\Backend\Block\System\Account;

use Magento\Framework\View\LayoutInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Edit
{
    /**
     * @param \Magento\Backend\Block\System\Account\Edit $subject
     * @param LayoutInterface                            $layout
     *
     * @return array
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
