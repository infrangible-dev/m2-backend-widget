<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object\Tab;

abstract class Grid extends \Infrangible\BackendWidget\Controller\Backend\Object\Grid
{
    /**
     * Returns the grid block data.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getGridBlockData(): array
    {
        return array_merge(
            parent::getGridBlockData(),
            [
                'allow_add'           => $this->allowAdd(),
                'add_url_route'       => $this->getAddUrlRoute(),
                'add_url_params'      => $this->getAddUrlParams(),
                'parent_object_key'   => $this->getParentObjectKey(),
                'parent_object_value' => $this->getRequest()->getParam($this->getParentObjectValueKey())
            ]
        );
    }

    /**
     * Allows the add.
     *
     * @return bool
     */
    abstract protected function allowAdd(): bool;

    /**
     * Allows the mass delete.
     *
     * @return bool
     */
    protected function allowMassDelete(): bool
    {
        return false;
    }

    /**
     * Adds the row action.
     *
     * @return bool
     */
    protected function addRowAction(): bool
    {
        return false;
    }

    /**
     * Returns the add url route.
     *
     * @return string
     */
    protected function getAddUrlRoute(): string
    {
        return '*/*/add';
    }

    /**
     * Returns the add url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getAddUrlParams(): array
    {
        return [$this->getParentObjectKey() => $this->getRequest()->getParam($this->getParentObjectValueKey())];
    }

    /**
     * Returns the edit url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getEditUrlParams(): array
    {
        $editUrlParams = parent::getEditUrlParams();

        $editUrlParams[ $this->getParentObjectKey() ] = $this->getRequest()->getParam($this->getParentObjectValueKey());

        return $editUrlParams;
    }

    /**
     * Returns the view url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getViewUrlParams(): array
    {
        $viewUrlParams = parent::getViewUrlParams();

        $viewUrlParams[ $this->getParentObjectKey() ] = $this->getRequest()->getParam($this->getParentObjectValueKey());

        return $viewUrlParams;
    }

    /**
     * Returns the grid url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getGridUrlParams(): array
    {
        $gridUrlParams = parent::getGridUrlParams();

        $gridUrlParams[ $this->getParentObjectKey() ] = $this->getRequest()->getParam($this->getParentObjectValueKey());

        return $gridUrlParams;
    }

    /**
     * Returns the delete url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getDeleteUrlParams(): array
    {
        $deleteUrlParams = parent::getDeleteUrlParams();

        $deleteUrlParams[ $this->getParentObjectKey() ] =
            $this->getRequest()->getParam($this->getParentObjectValueKey());

        return $deleteUrlParams;
    }

    /**
     * Returns the parent object key.
     *
     * @return string
     */
    abstract protected function getParentObjectKey(): string;

    /**
     * Returns the parent object value key.
     *
     * @return string
     */
    abstract protected function getParentObjectValueKey(): string;

    /**
     * Shows the filters button.
     *
     * @return bool
     */
    protected function showFiltersButton(): bool
    {
        return false;
    }

    /**
     * Shows the columns button.
     *
     * @return bool
     */
    protected function showColumnsButton(): bool
    {
        return false;
    }

    /**
     * Returns the grid type.
     *
     * @return string
     */
    protected function getGridType(): string
    {
        return sprintf(
            'Tab\%s',
            parent::getGridType()
        );
    }

    /**
     * Checks whether it is tab.
     *
     * @return bool
     */
    protected function isTab(): bool
    {
        return true;
    }
}
