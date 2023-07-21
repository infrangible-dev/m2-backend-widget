<?php

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Description
    extends AbstractRenderer
{
    /**
     * Renders grid column
     *
     * @param DataObject $row
     *
     * @return  string
     */
    public function render(DataObject $row): string
    {
        $column = $this->getColumn();

        $value = $row->getData($column->getData('index'));

        if ( ! empty($value)) {
            return sprintf('<textarea style="width: %s; height: %s; color: #000; border: 0; background: transparent" readonly="readonly">%s</textarea>',
                $column->getData('width'), $column->getData('height'), htmlspecialchars($value));
        }

        return $value;
    }
}
