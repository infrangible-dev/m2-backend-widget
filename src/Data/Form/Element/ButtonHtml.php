<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Data\Form\Element;

/**
 * Renders form elements as a button with the title as label.
 */
trait ButtonHtml
{
    /**
     * Returns the HTML of the button.
     *
     * The HTML before the element and the script after it are included, the HTML after the element is not.
     *
     * @return string
     */
    protected function getButtonHtml(): string
    {
        $html = '';
        $htmlId = $this->getHtmlId();

        $beforeElementHtml = $this->getBeforeElementHtml();
        if ($beforeElementHtml) {
            $html .= sprintf(
                '<label class="addbefore" for="%s">%s</label>',
                $htmlId,
                $this->toHtmlString($beforeElementHtml)
            );
        }

        $title = $this->_escaper->escapeHtmlAttr($this->toHtmlString($this->getData('title')));

        $html .= '<button type="button" id="' . $htmlId . '" title="' . $title . '" ' . $this->_getUiId() . ' ' .
            $this->serialize($this->getHtmlAttributes()) . '><span>' . $title . '</span></button>';

        $afterElementJs = $this->getAfterElementJs();
        if ($afterElementJs) {
            $html .= $this->toHtmlString($afterElementJs);
        }

        return $html;
    }

    /**
     * Returns the label with the HTML after the element.
     *
     * @param mixed $afterElementHtml
     *
     * @return string
     */
    protected function getAfterElementLabelHtml(mixed $afterElementHtml): string
    {
        return sprintf(
            '<label class="addafter" for="%s">%s</label>',
            $this->getHtmlId(),
            $this->toHtmlString($afterElementHtml)
        );
    }

    /**
     * Converts a value to string, for example a phrase.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function toHtmlString(mixed $value): string
    {
        return is_scalar($value) || $value instanceof \Stringable ? (string)$value : '';
    }
}
