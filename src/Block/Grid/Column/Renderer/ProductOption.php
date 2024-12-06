<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ProductOption extends AbstractRenderer
{
    /**
     * @throws \Exception
     */
    public function render(DataObject $row): string
    {
        $column = $this->getColumn();

        $value = $row->getData(
            sprintf(
                '%s_title',
                $column->getData('index')
            )
        );

        return $value ? : '';
    }
}