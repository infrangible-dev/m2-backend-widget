<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class Template extends AbstractRenderer
{
    use EscapedValues;

    /**
     * @param Context                                            $context
     * @param TemplateFactory                                    $templateFactory
     * @param \Magento\Email\Model\ResourceModel\TemplateFactory $templateResourceFactory
     * @param array                                              $data
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        protected TemplateFactory $templateFactory,
        protected \Magento\Email\Model\ResourceModel\TemplateFactory $templateResourceFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Renders.
     *
     * @param DataObject $row
     *
     * @return string
     * @throws LocalizedException
     */
    public function render(DataObject $row): string
    {
        $templateId = $row->getData('template_id');

        $mailTemplate = $this->templateFactory->create();

        $this->templateResourceFactory->create()->load($mailTemplate, $templateId);

        return $mailTemplate->getId() ? $this->escapeValue($mailTemplate->getTemplateCode()) : '';
    }
}
