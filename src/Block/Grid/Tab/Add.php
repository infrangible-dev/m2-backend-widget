<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Grid\Tab;

use Magento\Backend\Block\Template;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Add extends Template
{
    /** @var string */
    private $buttonId;

    /** @var string */
    private $buttonUrl;

    /** @var string */
    private $gridId;

    protected function _construct(): void
    {
        $this->setTemplate('Infrangible_BackendWidget::grid/tab/add.phtml');

        parent::_construct();
    }

    public function getButtonId(): string
    {
        return $this->buttonId;
    }

    public function setButtonId(string $buttonId): void
    {
        $this->buttonId = $buttonId;
    }

    public function getButtonUrl(): string
    {
        return $this->buttonUrl;
    }

    public function setButtonUrl(string $buttonUrl): void
    {
        $this->buttonUrl = $buttonUrl;
    }

    public function getGridId(): string
    {
        return $this->gridId;
    }

    public function setGridId(string $gridId): void
    {
        $this->gridId = $gridId;
    }
}
