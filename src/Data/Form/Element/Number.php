<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Data\Form\Element;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Number extends AbstractElement
{
    /** @var SecureHtmlRenderer */
    protected $secureRenderer;

    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        SecureHtmlRenderer $secureRenderer,
        $data = []
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

    public function getHtmlAttributes(): array
    {
        $htmlAttributes = parent::getHtmlAttributes();

        $htmlAttributes[] = 'max';
        $htmlAttributes[] = 'min';
        $htmlAttributes[] = 'pattern';
        $htmlAttributes[] = 'step';

        return $htmlAttributes;
    }

    public function getHtml(): string
    {
        $this->addClass('input-text admin__control-text');

        return parent::getHtml();
    }

    public function getElementHtml(): string
    {
        $elementHtml = parent::getElementHtml();

        if ($this->getData('prefix')) {
            $htmlId = $this->getHtmlId();
            $prefix = $this->getData('prefix');

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
