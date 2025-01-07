<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Data\Form\Element;

use Infrangible\Core\Helper\Block;
use Magento\Framework\Data\Form\Element\Button;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Back extends Button
{
    /** @var Block */
    protected $blockHelper;

    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        Block $blockHelper,
        $data = []
    ) {
        parent::__construct(
            $factoryElement,
            $factoryCollection,
            $escaper,
            $data
        );

        $this->blockHelper = $blockHelper;
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
        if (! $afterElementHtml) {
            $afterElementHtml = $this->blockHelper->renderBlock(
                \Infrangible\BackendWidget\Block\Form\Back::class,
                [],
                [
                    'buttonId'  => $this->getHtmlId(),
                    'buttonUrl' => $this->getData('buttonUrl'),
                    'formId'    => $this->getForm()->getDataUsingMethod('id')
                ]
            );
        }

        return $html . '<label class="addafter" for="' . $htmlId . '">' . $afterElementHtml . '</label>';
    }
}
