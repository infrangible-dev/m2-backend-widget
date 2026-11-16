<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid\Tab;

use Magento\Backend\Block\Template;

class Add extends Template
{
    /** @var string */
    private $buttonId;

    /** @var string */
    private $buttonUrl;

    /** @var string */
    private $gridId;

    /**
     * Constructor.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->setTemplate('Infrangible_BackendWidget::grid/tab/add.phtml');

        parent::_construct();
    }

    /**
     * Returns the button id.
     *
     * @return string
     */
    public function getButtonId(): string
    {
        return $this->buttonId;
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
        return $this->buttonUrl;
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
     * Returns the grid id.
     *
     * @return string
     */
    public function getGridId(): string
    {
        return $this->gridId;
    }

    /**
     * Sets the grid id.
     *
     * @param string $gridId
     *
     * @return void
     */
    public function setGridId(string $gridId): void
    {
        $this->gridId = $gridId;
    }
}
