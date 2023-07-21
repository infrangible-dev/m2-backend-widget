<?php /** @noinspection PhpDeprecationInspection */

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Exception;
use Magento\Backend\Block\Widget\Grid\Extended;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Grid
    extends Base
{
    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $this->initAction();

        $blockName = sprintf('adminhtml_%s', preg_replace('~[^a-z0-9_]*~i', '', $this->getObjectName())) . '.grid';

        $blockClassName = $this->getGridContentBlockClass();

        if ( ! class_exists($blockClassName)) {
            throw new Exception(sprintf('Could not find block class: %s', $blockClassName));
        }

        /** @var Extended $block */
        $block = $this->_view->getLayout()->createBlock($blockClassName, $blockName, [
            'data' => [
                'module_key'             => $this->getModuleKey(),
                'object_name'            => $this->getObjectName(),
                'object_field'           => $this->getObjectField(),
                'allow_edit'             => $this->allowEdit(),
                'allow_view'             => $this->allowView(),
                'allow_delete'           => $this->allowDelete(),
                'allow_export'           => $this->allowExport(),
                'model_class'            => $this->getModelClass(),
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
            ]
        ]);

        $response = $this->getResponse();

        $response->setBody($block->toHtml());
    }

    /**
     * @return string
     */
    protected function getGridContentBlockClass(): string
    {
        return sprintf('%s\Block\Adminhtml\%s\Grid', str_replace('_', '\\', $this->getModuleKey()),
            str_replace('_', '\\', $this->getObjectName()));
    }

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
}
