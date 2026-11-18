<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Form;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use Infrangible\BackendWidget\Block\Form;
use Infrangible\BackendWidget\Data\Form\Element\Back;
use Infrangible\BackendWidget\Data\Form\Element\Submit;
use Infrangible\Core\Helper\Block;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

abstract class Tab extends Form
{
    /** @var string */
    protected string $indexUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $indexUrlParams;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Arrays $arrays
     * @param \Infrangible\Core\Helper\Registry $registryHelper
     * @param \Infrangible\BackendWidget\Helper\Form $formHelper
     * @param Json $json
     * @param Block $blockHelper
     * @param array $data
     *
     * @phpstan-param array<int|string, mixed> $data
     * @noinspection PhpDeprecationInspection Magento's legacy form, the parent class, needs the registry.
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Arrays $arrays,
        \Infrangible\Core\Helper\Registry $registryHelper,
        \Infrangible\BackendWidget\Helper\Form $formHelper,
        protected Json $json,
        protected Block $blockHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $arrays,
            $registryHelper,
            $formHelper,
            $data
        );

        $this->indexUrlRoute = $this->stringArgument($data, 'index_url_route');
        $this->indexUrlParams = $this->arrayArgument($data, 'index_url_params');
    }

    /**
     * Hook to add or change fields after the default fields were added.
     *
     * @param \Magento\Framework\Data\Form $form
     *
     * @return void
     */
    protected function followUpFields(\Magento\Framework\Data\Form $form): void
    {
        $fieldSet = $form->addFieldset(
            'actions',
            ['legend' => __('Actions'), 'class' => 'actions']
        );

        $fieldSet->addField(
            $this->parentObjectKey,
            'hidden',
            [
                'name'  => $this->parentObjectKey,
                'value' => $this->parentObjectValue
            ]
        );

        $fieldSet->addField(
            'save',
            Submit::class,
            [
                'title'          => __('Save'),
                'class'          => 'action-columns action-primary',
                'data-mage-init' => $this->_escaper->escapeHtmlAttr(
                    (string)$this->json->encode(
                        [
                            'button' => [
                                'event'  => 'save',
                                'target' => sprintf(
                                    '#%s',
                                    $this->getScalarString($form->getDataUsingMethod('id'))
                                )
                            ]
                        ]
                    )
                )
            ]
        );

        $fieldSet->addField(
            'back',
            Back::class,
            [
                'title'     => __('Back'),
                'class'     => 'action-columns action-secondary',
                'buttonUrl' => $this->getUrl(
                    $this->gridUrlRoute,
                    $this->gridUrlParams
                )
            ]
        );
    }

    /**
     * Renders the block as HTML.
     *
     * @return string
     * @throws LocalizedException
     */
    public function toHtml(): string
    {
        $formHtml = parent::toHtml();

        /** @var Tab\Form $containerBlock */
        $containerBlock = $this->blockHelper->createLayoutBlock(
            $this->getLayout(),
            Tab\Form::class
        );

        $containerBlock->setObjectName($this->objectName);
        $containerBlock->setFormId($this->getScalarString($this->getForm()->getDataUsingMethod('id')));
        $containerBlock->setFormHtml($formHtml);

        return $containerBlock->toHtml();
    }

    /**
     * Returns the value as string, an empty string if it is no scalar value.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function getScalarString(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
