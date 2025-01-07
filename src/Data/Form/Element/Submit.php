<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Data\Form\Element;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Submit extends \Magento\Framework\Data\Form\Element\Submit
{
    public function getHtmlAttributes(): array
    {
        return array_merge(
            parent::getHtmlAttributes(),
            ['data-form-role', 'data-mage-init']
        );
    }

    public function getElementHtml(): string
    {
        $html = '';
        $htmlId = $this->getHtmlId();

        $beforeElementHtml = $this->getBeforeElementHtml();
        if ($beforeElementHtml) {
            $html .= '<label class="addbefore" for="' . $htmlId . '">' . $beforeElementHtml . '</label>';
        }

        $html .= sprintf(
            '<button type="button" id="%s" title="%s" %s %s><span>%s</span></button>',
            $this->getHtmlId(),
            $this->getData('title'),
            $this->_getUiId(),
            $this->serialize($this->getHtmlAttributes()),
            $this->getData('title')
        );

        $afterElementJs = $this->getAfterElementJs();
        if ($afterElementJs) {
            $html .= $afterElementJs;
        }

        $afterElementHtml = $this->getAfterElementHtml();
        if ($afterElementHtml) {
            $html .= '<label class="addafter" for="' . $htmlId . '">' . $afterElementHtml . '</label>';
        }

        return $html;
    }
}
