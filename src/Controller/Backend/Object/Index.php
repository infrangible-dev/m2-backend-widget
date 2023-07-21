<?php

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\Page;
use Infrangible\BackendWidget\Block\Grid\Container;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Index
    extends Base
{
    /**
     * @return Page|void
     */
    public function execute()
    {
        $this->initAction();

        $block = $this->createBlock();

        $this->_addContent($block);

        $this->finishAction(__('Manage'));

        $page = $this->_view->getPage();

        $page->getConfig()->addBodyClass('infrangible-backend-widget');

        return $page;
    }

    /**
     * @return AbstractBlock
     */
    protected function createBlock(): AbstractBlock
    {
        /** @var AbstractBlock $block */
        $block = $this->_view->getLayout()->createBlock($this->getGridBlockType(), '', [
            'data' => [
                'module_key'             => $this->getModuleKey(),
                'object_name'            => $this->getObjectName(),
                'object_field'           => $this->getObjectField(),
                'object_registry_key'    => $this->getObjectRegistryKey(),
                'title'                  => $this->getTitle(),
                'allow_add'              => $this->allowAdd(),
                'allow_edit'             => $this->allowEdit(),
                'allow_view'             => $this->allowView(),
                'allow_delete'           => $this->allowDelete(),
                'allow_export'           => $this->allowExport(),
                'model_class'            => $this->getModelClass(),
                'add_url_route'          => $this->getAddUrlRoute(),
                'add_url_params'         => $this->getAddUrlParams(),
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
                'back_url_route'         => $this->getBackUrlRoute(),
                'back_url_params'        => $this->getBackUrlParams()
            ]
        ]);

        return $block;
    }

    /**
     * @return string
     */
    protected function getGridBlockType(): string
    {
        return Container::class;
    }

    /**
     * @return bool
     */
    abstract protected function allowAdd(): bool;

    /**
     * @return bool
     */
    abstract protected function allowEdit(): bool;

    /**
     * @return bool
     */
    abstract protected function allowView(): bool;

    /**
     * @return bool
     */
    abstract protected function allowDelete(): bool;

    /**
     * @return bool
     */
    protected function allowExport(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getAddUrlRoute(): string
    {
        return '*/*/add';
    }

    /**
     * @return array
     */
    protected function getAddUrlParams(): array
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getGridUrlRoute(): string
    {
        return '*/*/grid';
    }

    /**
     * @return array
     */
    protected function getGridUrlParams(): array
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getEditUrlRoute(): string
    {
        return '*/*/edit';
    }

    /**
     * @return array
     */
    protected function getEditUrlParams(): array
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getViewUrlRoute(): string
    {
        return '*/*/view';
    }

    /**
     * @return array
     */
    protected function getViewUrlParams(): array
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getMassDeleteUrlRoute(): string
    {
        return '*/*/massDelete';
    }

    /**
     * @return array
     */
    protected function getMassDeleteUrlParams(): array
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getMassExportUrlRoute(): string
    {
        return '*/*/massExport';
    }

    /**
     * @return array
     */
    protected function getMassExportUrlParams(): array
    {
        return [];
    }

    /**
     * @return string|null
     */
    protected function getBackUrlRoute(): ?string
    {
        return null;
    }

    /**
     * @return array
     */
    protected function getBackUrlParams(): array
    {
        return [];
    }
}
