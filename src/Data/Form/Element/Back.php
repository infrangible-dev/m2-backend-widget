<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Data\Form\Element;

use Infrangible\Core\Helper\Block;
use Magento\Framework\Data\Form\Element\Button;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

class Back extends Button
{
    use ButtonHtml;

    /**
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param Block $blockHelper
     * @param array $data
     *
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        protected Block $blockHelper,
        array $data = []
    ) {
        parent::__construct(
            $factoryElement,
            $factoryCollection,
            $escaper,
            $data
        );
    }

    /**
     * Returns the element html.
     *
     * @return string
     */
    public function getElementHtml(): string
    {
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

        return $this->getButtonHtml() . $this->getAfterElementLabelHtml($afterElementHtml);
    }
}
