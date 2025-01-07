<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Form\Tab;

use Magento\Backend\Block\Template;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Form extends Template
{
    /** @var string */
    private $objectName;

    /** @var string */
    private $formId;

    /** @var string */
    private $formHtml;

    protected function _construct(): void
    {
        $this->setTemplate('Infrangible_BackendWidget::form/tab/form.phtml');

        parent::_construct();
    }

    public function getObjectName(): string
    {
        return $this->objectName;
    }

    public function setObjectName(string $objectName): void
    {
        $this->objectName = $objectName;
    }

    public function getFormId(): string
    {
        return $this->formId;
    }

    public function setFormId(string $formId): void
    {
        $this->formId = $formId;
    }

    public function getFormHtml(): string
    {
        return $this->formHtml;
    }

    public function setFormHtml(string $formHtml): void
    {
        $this->formHtml = $formHtml;
    }
}
