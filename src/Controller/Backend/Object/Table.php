<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object;

abstract class Table extends Base
{
    /**
     * Returns the grid block data.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getGridBlockData(): array
    {
        return [
            'module_key'             => $this->getModuleKey(),
            'object_name'            => $this->getObjectName(),
            'object_field'           => $this->getObjectField(),
            'object_registry_key'    => $this->getObjectRegistryKey(),
            'allow_edit'             => $this->allowEdit(),
            'allow_view'             => $this->allowView(),
            'allow_delete'           => $this->allowDelete(),
            'allow_mass_delete'      => $this->allowMassDelete(),
            'allow_export'           => $this->allowExport(),
            'add_row_action'         => $this->addRowAction(),
            'model_class'            => $this->getModelClass(),
            'collection_class'       => $this->getCollectionClass(),
            'grid_url_route'         => $this->getGridUrlRoute(),
            'grid_url_params'        => $this->getGridUrlParams(),
            'edit_url_route'         => $this->getEditUrlRoute(),
            'edit_url_params'        => $this->getEditUrlParams(),
            'view_url_route'         => $this->getViewUrlRoute(),
            'view_url_params'        => $this->getViewUrlParams(),
            'delete_url_route'       => $this->getDeleteUrlRoute(),
            'delete_url_params'      => $this->getDeleteUrlParams(),
            'mass_delete_url_route'  => $this->getMassDeleteUrlRoute(),
            'mass_delete_url_params' => $this->getMassDeleteUrlParams(),
            'mass_export_url_route'  => $this->getMassExportUrlRoute(),
            'mass_export_url_params' => $this->getMassExportUrlParams(),
            'show_filters_button'    => $this->showFiltersButton(),
            'show_columns_button'    => $this->showColumnsButton(),
            'is_tab'                 => $this->isTab()
        ];
    }

    /**
     * Allows the edit.
     *
     * @return bool
     */
    abstract protected function allowEdit(): bool;

    /**
     * Allows the view.
     *
     * @return bool
     */
    abstract protected function allowView(): bool;

    /**
     * Allows the delete.
     *
     * @return bool
     */
    abstract protected function allowDelete(): bool;

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
     * Allows the export.
     *
     * @return bool
     */
    protected function allowExport(): bool
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
        return true;
    }

    /**
     * Returns the grid url route.
     *
     * @return string
     */
    protected function getGridUrlRoute(): string
    {
        return '*/*/grid';
    }

    /**
     * Returns the grid url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getGridUrlParams(): array
    {
        return [];
    }

    /**
     * Returns the edit url route.
     *
     * @return string
     */
    protected function getEditUrlRoute(): string
    {
        return '*/*/edit';
    }

    /**
     * Returns the edit url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getEditUrlParams(): array
    {
        return [];
    }

    /**
     * Returns the view url route.
     *
     * @return string
     */
    protected function getViewUrlRoute(): string
    {
        return '*/*/view';
    }

    /**
     * Returns the view url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getViewUrlParams(): array
    {
        return [];
    }

    /**
     * Returns the mass delete url route.
     *
     * @return string
     */
    protected function getMassDeleteUrlRoute(): string
    {
        return '*/*/massDelete';
    }

    /**
     * Returns the mass delete url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getMassDeleteUrlParams(): array
    {
        return [];
    }

    /**
     * Returns the mass export url route.
     *
     * @return string
     */
    protected function getMassExportUrlRoute(): string
    {
        return '*/*/massExport';
    }

    /**
     * Returns the mass export url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getMassExportUrlParams(): array
    {
        return [];
    }

    /**
     * Returns the collection class.
     *
     * @return string|null
     */
    protected function getCollectionClass(): ?string
    {
        return null;
    }

    /**
     * Shows the filters button.
     *
     * @return bool
     */
    protected function showFiltersButton(): bool
    {
        return true;
    }

    /**
     * Shows the columns button.
     *
     * @return bool
     */
    protected function showColumnsButton(): bool
    {
        return true;
    }

    /**
     * Returns the grid content block class.
     *
     * @return string
     */
    protected function getGridContentBlockClass(): string
    {
        return sprintf(
            '%s\Block\Adminhtml\%s\%s',
            str_replace(
                '_',
                '\\',
                $this->getModuleKey()
            ),
            str_replace(
                '_',
                '\\',
                $this->getObjectName()
            ),
            $this->getGridType()
        );
    }

    /**
     * Returns the grid type.
     *
     * @return string
     */
    protected function getGridType(): string
    {
        return 'Grid';
    }

    /**
     * Checks whether it is tab.
     *
     * @return bool
     */
    abstract protected function isTab(): bool;
}
