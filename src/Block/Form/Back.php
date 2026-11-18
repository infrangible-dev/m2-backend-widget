<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Form;

use Magento\Backend\Block\Template;

class Back extends Template
{
    /**
     * @param Template\Context $context
     * @param array $data
     * @param string|null $buttonId
     * @param string|null $buttonUrl
     * @param string|null $formId
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        array $data = [],
        private ?string $buttonId = null,
        private ?string $buttonUrl = null,
        private ?string $formId = null
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Constructor.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->setTemplate('Infrangible_BackendWidget::form/back.phtml');

        parent::_construct();
    }

    /**
     * Returns the button id.
     *
     * @return string
     */
    public function getButtonId(): string
    {
        return $this->buttonId ?? '';
    }

    /**
     * Sets the button id.
     *
     * @param string $buttonId
     *
     * @return void
     */
    public function setButtonId(string $buttonId): void
    {
        $this->buttonId = $buttonId;
    }

    /**
     * Returns the button url.
     *
     * @return string
     */
    public function getButtonUrl(): string
    {
        return $this->buttonUrl ?? '';
    }

    /**
     * Sets the button url.
     *
     * @param string $buttonUrl
     *
     * @return void
     */
    public function setButtonUrl(string $buttonUrl): void
    {
        $this->buttonUrl = $buttonUrl;
    }

    /**
     * Returns the form id.
     *
     * @return string
     */
    public function getFormId(): string
    {
        return $this->formId ?? '';
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
}
