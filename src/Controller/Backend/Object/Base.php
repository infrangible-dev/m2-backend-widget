<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;

abstract class Base extends Action
{
    /**
     * Checks whether it is allowed.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed($this->getAclResourceName());
    }

    /**
     * Returns the name of the ACL resource.
     *
     * @return string
     */
    protected function getAclResourceName(): string
    {
        return sprintf(
            '%s::%s',
            $this->getModuleKey(),
            $this->getResourceKey()
        );
    }

    /**
     * Returns the resource key.
     *
     * @return string
     */
    abstract protected function getResourceKey(): string;

    /**
     * Initializes the action.
     *
     * @return void
     */
    protected function initAction(): void
    {
        // @phpstan-ignore argument.type (Magento also accepts a list of layout handles)
        $this->_view->loadLayout($this->getInitActionLayoutHandles());
    }

    /**
     * Returns the init action layout handles.
     *
     * @return string[]
     */
    protected function getInitActionLayoutHandles(): array
    {
        return ['default', 'styles'];
    }

    /**
     * Finishes the action.
     *
     * @param string $action
     *
     * @return void
     */
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

    /**
     * Returns the active menu.
     *
     * @return string
     */
    protected function getActiveMenu(): string
    {
        return sprintf(
            '%s::%s',
            $this->getModuleKey(),
            $this->getMenuKey()
        );
    }

    /**
     * Returns the module key.
     *
     * @return string
     */
    abstract protected function getModuleKey(): string;

    /**
     * Returns the menu key.
     *
     * @return string
     */
    abstract protected function getMenuKey(): string;

    /**
     * Returns the title.
     *
     * @return string
     */
    abstract protected function getTitle(): string;

    /**
     * Returns the model class.
     *
     * @return string
     */
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

    /**
     * Returns the model resource class.
     *
     * @return string
     */
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

    /**
     * Returns the object name.
     *
     * @return string
     */
    abstract protected function getObjectName(): string;

    /**
     * Returns the object field.
     *
     * @return string|null
     */
    protected function getObjectField(): ?string
    {
        return null;
    }

    /**
     * Returns the object registry key.
     *
     * @return string
     */
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

    /**
     * Returns the delete url route.
     *
     * @return string
     */
    protected function getDeleteUrlRoute(): string
    {
        return '*/*/delete';
    }

    /**
     * Returns the delete url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getDeleteUrlParams(): array
    {
        return [];
    }

    /**
     * Adds the success message.
     *
     * @param string $message
     *
     * @return void
     */
    protected function addSuccessMessage(string $message): void
    {
        $this->getMessageManager()->addSuccessMessage($message);
    }

    /**
     * Adds the error message.
     *
     * @param string $message
     *
     * @return void
     */
    protected function addErrorMessage(string $message): void
    {
        $this->getMessageManager()->addErrorMessage($message);
    }

    /**
     * Redirects.
     *
     * @param string $path
     * @param array $arguments
     *
     * @phpstan-param array<int|string, mixed> $arguments
     *
     * @return void
     */
    protected function redirect(string $path, array $arguments): void
    {
        $this->_redirect(
            $path,
            $arguments
        );
    }

    /**
     * Prepares the response.
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- hook
    protected function prepareResponse(): void
    {
    }
}
