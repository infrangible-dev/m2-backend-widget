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

class ProductOption extends AbstractRenderer
{
    use EscapedValues;

    /**
     * Renders.
     *
     * @param DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row): string
    {
        return $this->escapeValue($row->getData($this->getColumnIndex() . '_title'));
    }
}
