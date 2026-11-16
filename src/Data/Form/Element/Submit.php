<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Data\Form\Element;

class Submit extends \Magento\Framework\Data\Form\Element\Submit
{
    use ButtonHtml;

    /**
     * Returns the html attributes.
     *
     * @return array
     * @phpstan-return array<int, string>
     */
    public function getHtmlAttributes(): array
    {
        return array_merge(
            parent::getHtmlAttributes(),
            ['data-form-role', 'data-mage-init']
        );
    }

    /**
     * Returns the element html.
     *
     * @return string
     */
    public function getElementHtml(): string
    {
        $html = $this->getButtonHtml();

        $afterElementHtml = $this->getAfterElementHtml();
        if ($afterElementHtml) {
            $html .= $this->getAfterElementLabelHtml($afterElementHtml);
        }

        return $html;
    }
}
