<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Base extends Action
{
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed($this->getAclResourceName());
    }

    protected function getAclResourceName(): string
    {
        return sprintf(
            '%s::%s',
            $this->getModuleKey(),
            $this->getResourceKey()
        );
    }

    abstract protected function getResourceKey(): string;

    protected function initAction(): void
    {
        $this->_view->loadLayout($this->getInitActionLayoutHandles());
    }

    /**
     * @return string[]
     */
    protected function getInitActionLayoutHandles(): array
    {
        return ['default', 'styles'];
    }

    protected function finishAction(string $action): void
    {
        $page = $this->_view->getPage();

        $page->getConfig()->getTitle()->prepend(
            sprintf(
                '%s > %s',
                $this->getTitle(),
                $action
            )
        );

        if ($page instanceof Page) {
            $page->setActiveMenu($this->getActiveMenu());
            $page->addBreadcrumb(
                $this->getTitle(),
                $this->getTitle()
            );
            $page->addBreadcrumb(
                $action,
                $action
            );
        }
    }

    protected function getActiveMenu(): string
    {
        return sprintf(
            '%s::%s',
            $this->getModuleKey(),
            $this->getMenuKey()
        );
    }

    abstract protected function getModuleKey(): string;

    abstract protected function getMenuKey(): string;

    abstract protected function getTitle(): string;

    protected function getModelClass(): string
    {
        return sprintf(
            '%s\Model\%s',
            str_replace(
                '_',
                '\\',
                $this->getModuleKey()
            ),
            str_replace(
                '_',
                '\\',
                $this->getObjectName()
            )
        );
    }

    protected function getModelResourceClass(): string
    {
        return sprintf(
            '%s\Model\ResourceModel\%s',
            str_replace(
                '_',
                '\\',
                $this->getModuleKey()
            ),
            str_replace(
                '_',
                '\\',
                $this->getObjectName()
            )
        );
    }

    abstract protected function getObjectName(): string;

    protected function getObjectField(): ?string
    {
        return null;
    }

    protected function getObjectRegistryKey(): string
    {
        return sprintf(
            'current_%s',
            strtolower(
                str_replace(
                    '\\',
                    '_',
                    $this->getObjectName()
                )
            )
        );
    }

    protected function getDeleteUrlRoute(): string
    {
        return '*/*/delete';
    }

    protected function getDeleteUrlParams(): array
    {
        return [];
    }

    protected function addSuccessMessage(string $message): void
    {
        $this->getMessageManager()->addSuccessMessage($message);
    }

    protected function addErrorMessage(string $message): void
    {
        $this->getMessageManager()->addErrorMessage($message);
    }

    protected function redirect(string $path, array $arguments): void
    {
        $this->_redirect(
            $path,
            $arguments
        );
    }

    protected function prepareResponse(): void
    {
    }
}
