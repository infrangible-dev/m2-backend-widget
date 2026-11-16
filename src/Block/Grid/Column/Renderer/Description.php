<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Description extends AbstractRenderer
{
    use EscapedValues;

    /**
     * Renders the value in a read-only text area.
     *
     * @param DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row): string
    {
        $value = $row->getData($this->getColumnIndex());

        if (empty($value)) {
            return '';
        }

        $column = $this->getColumn();

        return sprintf(
            '<textarea style="width: %s; height: %s; color: #000; border: 0; background: transparent" ' .
            'readonly="readonly">%s</textarea>',
            $this->escapeValue($column->getData('width')),
            $this->escapeValue($column->getData('height')),
            $this->escapeValue($value)
        );
    }
}
