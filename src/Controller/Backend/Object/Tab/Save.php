<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object\Tab;

use Infrangible\BackendWidget\Helper\Action;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Save extends \Infrangible\BackendWidget\Controller\Backend\Object\Save
{
    use Ajax;

    /** @var Action */
    protected $actionHelper;

    public function __construct(
        Registry $registryHelper,
        Instances $instanceHelper,
        Context $context,
        LoggerInterface $logging,
        Session $session,
        Action $actionHelper
    ) {
        parent::__construct(
            $registryHelper,
            $instanceHelper,
            $context,
            $logging,
            $session
        );

        $this->actionHelper = $actionHelper;
    }

    protected function addSuccessMessage(string $message): void
    {
        $this->addResponseValue(
            'message',
            $message
        );
    }

    protected function addErrorMessage(string $message): void
    {
        $this->setResponseCode(500);
        $this->addResponseValue(
            'message',
            $message
        );
    }

    protected function redirect(string $path, array $arguments): void
    {
        if ($this->getResponseCode() === 200) {
            $this->setResponseCode(302);
        }

        $this->addResponseValue(
            'location',
            $this->getUrl(
                $path,
                $arguments
            )
        );
    }

    protected function prepareResponse(): void
    {
        $this->actionHelper->processResponse(
            $this->getResponseCode(),
            $this->getResponseValues()
        );
    }

    abstract protected function getParentObjectKey(): string;

    abstract protected function getParentObjectValueKey(): string;

    protected function getGridUrlParams(): array
    {
        $gridUrlParams = parent::getGridUrlParams();

        $gridUrlParams[ $this->getParentObjectValueKey() ] = $this->getRequest()->getParam($this->getParentObjectKey());

        return $gridUrlParams;
    }

    protected function getRedirectUrlRoute(): string
    {
        return $this->getGridUrlRoute();
    }

    protected function getRedirectUrlParams(): array
    {
        return $this->getGridUrlParams();
    }
}
