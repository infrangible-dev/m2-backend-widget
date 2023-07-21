<?php

namespace Infrangible\BackendWidget\Block\Config\Form;

use Magento\Backend\Block\Widget\Button;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Escaper;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\LayoutInterface;
use Infrangible\Core\Helper\Url;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Wysiwyg
    extends Text
{
    /** @var Url */
    protected $urlHelper;

    /** @var Manager */
    protected $moduleManager;

    /** @var Config */
    protected $config;

    /** @var LayoutInterface */
    protected $layout;

    /**
     * @param Factory           $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper           $escaper
     * @param Url               $urlHelper
     * @param Manager           $moduleManager
     * @param Config            $config
     * @param Context           $context
     * @param array             $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        Url $urlHelper,
        Manager $moduleManager,
        Config $config,
        Context $context,
        array $data = [])
    {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);

        $this->urlHelper = $urlHelper;

        $this->moduleManager = $moduleManager;
        $this->config = $config;
        $this->layout = $context->getLayout();
    }

    /**
     * @return string|null
     */
    public function getAfterElementHtml(): ?string
    {
        $html = parent::getAfterElementHtml();

        if ($this->getIsWysiwygEnabled()) {
            $disabled = $this->getData('disabled') || $this->getReadonly();

            $label = $this->hasData('button_label') ? $this->getData('button_label') : __('Select Image...');

            $button = $this->layout->createBlock(Button::class, '', [
                'data' => [
                    'label'    => $label,
                    'type'     => 'button',
                    'disabled' => $disabled,
                    'class'    => 'add-image plugin',
                    'onclick'  => sprintf('MediabrowserUtility.openDialog(\'%s\')',
                        $this->urlHelper->getBackendUrl('infrangible_backendwidget/wysiwyg_images/index',
                            ['target_element_id' => $this->getHtmlId()]))
                ]
            ]);

            $html .= $button->toHtml();
        }

        return $html;
    }

    /**
     * Check whether wysiwyg enabled or not
     *
     * @return bool
     */
    public function getIsWysiwygEnabled(): bool
    {
        if ($this->moduleManager->isEnabled('Magento_Cms')) {
            return $this->config->isEnabled();
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getHtmlAttributes(): array
    {
        $attributes = parent::getHtmlAttributes();

        $attributes[] = 'data-force_static_path';

        return $attributes;
    }
}
