<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\DataObject;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Template
    extends AbstractRenderer
{
    /** @var TemplateFactory */
    protected $templateFactory;

    /** @var \Magento\Email\Model\ResourceModel\TemplateFactory */
    protected $templateResourceFactory;

    /**
     * @param Context                                            $context
     * @param TemplateFactory                                    $templateFactory
     * @param \Magento\Email\Model\ResourceModel\TemplateFactory $templateResourceFactory
     * @param array                                              $data
     */
    public function __construct(
        Context $context,
        TemplateFactory $templateFactory,
        \Magento\Email\Model\ResourceModel\TemplateFactory $templateResourceFactory,
        array $data = [])
    {
        parent::__construct($context, $data);

        $this->templateFactory = $templateFactory;
        $this->templateResourceFactory = $templateResourceFactory;
    }

    /**
     * @param DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row): string
    {
        $templateId = $row->getData('template_id');

        $mailTemplate = $this->templateFactory->create();

        $this->templateResourceFactory->create()->load($mailTemplate, $templateId);

        return $mailTemplate->getId() ? $mailTemplate->getTemplateCode() : '';
    }
}
