<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Form;

use Magento\Backend\Block\Template;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Back extends Template
{
    /** @var string */
    private $buttonId;

    /** @var string */
    private $buttonUrl;

    /** @var string */
    private $formId;

    public function __construct(
        Template\Context $context,
        array $data = [],
        ?Data $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null,
        ?string $buttonId = null,
        ?string $buttonUrl = null,
        ?string $formId = null
    ) {
        parent::__construct(
            $context,
            $data,
            $jsonHelper,
            $directoryHelper
        );

        $this->buttonId = $buttonId;
        $this->buttonUrl = $buttonUrl;
        $this->formId = $formId;
    }

    protected function _construct(): void
    {
        $this->setTemplate('Infrangible_BackendWidget::form/back.phtml');

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

    public function getFormId(): string
    {
        return $this->formId;
    }

    public function setFormId(string $formId): void
    {
        $this->formId = $formId;
    }
}
