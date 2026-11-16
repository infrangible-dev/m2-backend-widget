<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Form\Tab;

use Magento\Backend\Block\Template;

class Form extends Template
{
    /** @var string */
    private $objectName;

    /** @var string */
    private $formId;

    /** @var string */
    private $formHtml;

    /**
     * Constructor.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->setTemplate('Infrangible_BackendWidget::form/tab/form.phtml');

        parent::_construct();
    }

    /**
     * Returns the object name.
     *
     * @return string
     */
    public function getObjectName(): string
    {
        return $this->objectName;
    }

    /**
     * Sets the object name.
     *
     * @param string $objectName
     *
     * @return void
     */
    public function setObjectName(string $objectName): void
    {
        $this->objectName = $objectName;
    }

    /**
     * Returns the form id.
     *
     * @return string
     */
    public function getFormId(): string
    {
        return $this->formId;
    }

    /**
     * Sets the form id.
     *
     * @param string $formId
     *
     * @return void
     */
    public function setFormId(string $formId): void
    {
        $this->formId = $formId;
    }

    /**
     * Returns the form html.
     *
     * @return string
     */
    public function getFormHtml(): string
    {
        return $this->formHtml;
    }

    /**
     * Sets the form html.
     *
     * @param string $formHtml
     *
     * @return void
     */
    public function setFormHtml(string $formHtml): void
    {
        $this->formHtml = $formHtml;
    }
}
