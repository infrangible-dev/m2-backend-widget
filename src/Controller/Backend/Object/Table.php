<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Table extends Base
{
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
            'show_columns_button'    => $this->showColumnsButton()
        ];
    }

    abstract protected function allowEdit(): bool;

    abstract protected function allowView(): bool;

    abstract protected function allowDelete(): bool;

    protected function allowMassDelete(): bool
    {
        return false;
    }

    protected function allowExport(): bool
    {
        return false;
    }

    protected function addRowAction(): bool
    {
        return true;
    }

    protected function getGridUrlRoute(): string
    {
        return '*/*/grid';
    }

    protected function getGridUrlParams(): array
    {
        return [];
    }

    protected function getEditUrlRoute(): string
    {
        return '*/*/edit';
    }

    protected function getEditUrlParams(): array
    {
        return [];
    }

    protected function getViewUrlRoute(): string
    {
        return '*/*/view';
    }

    protected function getViewUrlParams(): array
    {
        return [];
    }

    protected function getMassDeleteUrlRoute(): string
    {
        return '*/*/massDelete';
    }

    protected function getMassDeleteUrlParams(): array
    {
        return [];
    }

    protected function getMassExportUrlRoute(): string
    {
        return '*/*/massExport';
    }

    protected function getMassExportUrlParams(): array
    {
        return [];
    }

    protected function getCollectionClass(): ?string
    {
        return null;
    }

    protected function showFiltersButton(): bool
    {
        return true;
    }

    protected function showColumnsButton(): bool
    {
        return true;
    }

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

    protected function getGridType(): string
    {
        return 'Grid';
    }
}
