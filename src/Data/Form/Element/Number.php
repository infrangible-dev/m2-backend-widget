<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Data\Form\Element;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Number extends AbstractElement
{
    /** @var SecureHtmlRenderer */
    protected SecureHtmlRenderer $secureRenderer;

    /**
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param SecureHtmlRenderer $secureRenderer
     * @param array $data
     *
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        SecureHtmlRenderer $secureRenderer,
        array $data = []
    ) {
        parent::__construct(
            $factoryElement,
            $factoryCollection,
            $escaper,
            $data
        );

        $this->secureRenderer = $secureRenderer;

        $this->setType('number');
        $this->setData(
            'ext_type',
            'textfield'
        );
    }

    /**
     * Returns the html attributes.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function getHtmlAttributes(): array
    {
        $htmlAttributes = parent::getHtmlAttributes();

        $htmlAttributes[] = 'max';
        $htmlAttributes[] = 'min';
        $htmlAttributes[] = 'pattern';
        $htmlAttributes[] = 'step';

        return $htmlAttributes;
    }

    /**
     * Returns the html.
     *
     * @return string
     */
    public function getHtml(): string
    {
        $this->addClass('input-text admin__control-text');

        $html = parent::getHtml();

        return is_string($html) ? $html : '';
    }

    /**
     * Returns the element html.
     *
     * @return string
     */
    public function getElementHtml(): string
    {
        $elementHtml = parent::getElementHtml();

        if ($this->getData('prefix')) {
            $htmlId = $this->getHtmlId();
            $prefix = $this->getData('prefix');
            /** @var string|string[] $prefix Magento before 2.4.9 declares string|array */
            $prefix = $this->_escaper->escapeHtml(
                is_scalar($prefix) || $prefix instanceof \Stringable ? (string)$prefix : ''
            );
            $prefix = is_string($prefix) ? $prefix : '';

            $addOnHtml = <<<addon
            <label class="admin__addon-prefix" for="$htmlId">
                <span>$prefix</span>
            </label>
addon;

            $scriptString = <<<script
        require(['jquery', 'jquery/ui'], function($) {
            $('#$htmlId').parent().addClass('admin__control-addon');
        });
script;

            return $elementHtml . /* @noEscape */ $this->secureRenderer->renderTag(
                'script',
                [],
                $scriptString,
                false
            ) . $addOnHtml;
        } else {
            return $elementHtml;
        }
    }
}
