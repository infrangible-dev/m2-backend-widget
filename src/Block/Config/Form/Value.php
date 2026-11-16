<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Config\Form;

use Magento\Framework\Data\Form\Element\Text;

class Value extends Text
{
    /**
     * Returns the element html.
     *
     * @return string
     */
    public function getElementHtml(): string
    {
        $html = '';

        $value = $this->getDataUsingMethod('value');

        if (is_array($value)) {
            foreach ($value as $valueValue) {
                $html .= $this->getValueHtml($this->_escape($valueValue));
            }
        } else {
            $html .= $this->getValueHtml($this->getEscapedValue());
        }

        return $html;
    }

    /**
     * Returns the value html.
     *
     * @param mixed $value
     *
     * @return string
     */
    public function getValueHtml(mixed $value): string
    {
        return '<div id="' . $this->getHtmlId() . '" ' . $this->serialize($this->getHtmlAttributes()) . '>' .
            (is_scalar($value) || $value instanceof \Stringable ? (string)$value : '') . '</div>';
    }
}
