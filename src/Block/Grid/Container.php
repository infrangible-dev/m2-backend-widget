<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Grid;

use Exception;
use FeWeDev\Base\Arrays;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Grid;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Container extends Grid\Container
{
    /** @var string */
    protected $gridContentBlockClassName;

    /** @var string */
    protected $moduleKey;

    /** @var string */
    protected $objectName;

    /** @var string */
    protected $objectField;

    /** @var string */
    protected $objectRegistryKey;

    /** @var bool */
    protected $allowAdd = true;

    /** @var bool */
    protected $allowEdit = true;

    /** @var bool */
    protected $allowView = true;

    /** @var bool */
    protected $allowDelete = true;

    /** @var bool */
    protected $allowMassDelete = true;

    /** @var bool */
    protected $allowExport = true;

    /** @var bool */
    protected $addRowAction = true;

    /** @var string */
    protected $modelClass;

    /** @var string */
    protected $collectionClass;

    /** @var string */
    protected $addUrlRoute;

    /** @var array */
    protected $addUrlParams;

    /** @var string */
    protected $gridUrlRoute;

    /** @var array */
    protected $gridUrlParams;

    /** @var string */
    protected $editUrlRoute;

    /** @var array */
    protected $editUrlParams;

    /** @var string */
    protected $viewUrlRoute;

    /** @var array */
    protected $viewUrlParams;

    /** @var string */
    protected $deleteUrlRoute;

    /** @var array */
    protected $deleteUrlParams;

    /** @var string */
    protected $massDeleteUrlRoute;

    /** @var array */
    protected $massDeleteUrlParams;

    /** @var string */
    protected $massExportUrlRoute;

    /** @var array */
    protected $massExportUrlParams;

    /** @var string */
    protected $backUrlRoute;

    /** @var array */
    protected $backUrlParams;

    /** @var bool */
    protected $showFiltersButton = true;

    /** @var bool */
    protected $showColumnsButton = true;

    public function __construct(Context $context, Arrays $arrays, array $data = [])
    {
        $this->gridContentBlockClassName = $arrays->getValue(
            $data,
            'grid_content_block_class_name'
        );
        $this->moduleKey = $arrays->getValue(
            $data,
            'module_key',
            'adminhtml'
        );
        $this->objectName = $arrays->getValue(
            $data,
            'object_name',
            'empty'
        );
        $this->objectField = $arrays->getValue(
            $data,
            'object_field',
            'id'
        );
        $this->objectRegistryKey = $arrays->getValue(
            $data,
            'object_registry_key'
        );
        $this->allowAdd = $arrays->getValue(
            $data,
            'allow_add',
            true
        );
        $this->allowEdit = $arrays->getValue(
            $data,
            'allow_edit',
            true
        );
        $this->allowView = $arrays->getValue(
            $data,
            'allow_view',
            false
        );
        $this->allowDelete = $arrays->getValue(
            $data,
            'allow_delete',
            true
        );
        $this->allowMassDelete = $arrays->getValue(
            $data,
            'allow_mass_delete',
            true
        );
        $this->allowExport = $arrays->getValue(
            $data,
            'allow_export',
            false
        );
        $this->addRowAction = $arrays->getValue(
            $data,
            'add_row_action',
            true
        );
        $this->modelClass = $arrays->getValue(
            $data,
            'model_class'
        );
        $this->collectionClass = $arrays->getValue(
            $data,
            'collection_class'
        );
        $this->addUrlRoute = $arrays->getValue(
            $data,
            'add_url_route',
            '*/*/add'
        );
        $this->addUrlParams = $arrays->getValue(
            $data,
            'add_url_params',
            []
        );
        $this->gridUrlRoute = $arrays->getValue(
            $data,
            'grid_url_route',
            '*/*/grid'
        );
        $this->gridUrlParams = $arrays->getValue(
            $data,
            'grid_url_params',
            []
        );
        $this->editUrlRoute = $arrays->getValue(
            $data,
            'edit_url_route',
            '*/*/edit'
        );
        $this->editUrlParams = $arrays->getValue(
            $data,
            'edit_url_params',
            []
        );
        $this->viewUrlRoute = $arrays->getValue(
            $data,
            'view_url_route',
            '*/*/view'
        );
        $this->viewUrlParams = $arrays->getValue(
            $data,
            'view_url_params',
            []
        );
        $this->deleteUrlRoute = $arrays->getValue(
            $data,
            'delete_url_route',
            '*/*/delete'
        );
        $this->deleteUrlParams = $arrays->getValue(
            $data,
            'delete_url_params',
            []
        );
        $this->massDeleteUrlRoute = $arrays->getValue(
            $data,
            'mass_delete_url_route',
            '*/*/massDelete'
        );
        $this->massDeleteUrlParams = $arrays->getValue(
            $data,
            'mass_delete_url_params',
            []
        );
        $this->massExportUrlRoute = $arrays->getValue(
            $data,
            'mass_export_url_route',
            '*/*/massExport'
        );
        $this->massExportUrlParams = $arrays->getValue(
            $data,
            'mass_export_url_params',
            []
        );
        $this->backUrlRoute = $arrays->getValue(
            $data,
            'back_url_route'
        );
        $this->backUrlParams = $arrays->getValue(
            $data,
            'back_url_params',
            []
        );
        $this->showFiltersButton = $arrays->getValue(
            $data,
            'show_filters_button',
            true
        );
        $this->showColumnsButton = $arrays->getValue(
            $data,
            'show_columns_button',
            true
        );

        $this->_blockGroup = $this->moduleKey;
        $this->_controller = sprintf(
            'Adminhtml_%s',
            str_replace(
                '\\',
                '_',
                $this->objectName
            )
        );
        $this->_headerText = sprintf(
            '%s > %s',
            $arrays->getValue(
                $data,
                'title',
                'Container Widget Header'
            ),
            __('Manage')
        );

        parent::__construct(
            $context,
            $data
        );

        if (! $this->allowAdd) {
            $this->removeButton('add');
        }

        if (! empty($this->backUrlRoute)) {
            $this->_addBackButton();
        }
    }

    /**
     * @throws Exception
     */
    protected function _prepareLayout(): \Magento\Backend\Block\Widget\Container
    {
        if (! class_exists($this->gridContentBlockClassName)) {
            throw new Exception(
                sprintf(
                    'Could not find grid content block class: %s',
                    $this->gridContentBlockClassName
                )
            );
        }

        $block = $this->getLayout()->createBlock(
            $this->gridContentBlockClassName,
            $this->_controller . '.grid',
            [
                'data' => [
                    'module_key'             => $this->moduleKey,
                    'object_name'            => $this->objectName,
                    'object_field'           => $this->objectField,
                    'object_registry_key'    => $this->objectRegistryKey,
                    'allow_edit'             => $this->allowEdit,
                    'allow_view'             => $this->allowView,
                    'allow_delete'           => $this->allowDelete,
                    'allow_mass_delete'      => $this->allowMassDelete,
                    'allow_export'           => $this->allowExport,
                    'add_row_action'         => $this->addRowAction,
                    'model_class'            => $this->modelClass,
                    'collection_class'       => $this->collectionClass,
                    'grid_url_route'         => $this->gridUrlRoute,
                    'grid_url_params'        => $this->gridUrlParams,
                    'edit_url_route'         => $this->editUrlRoute,
                    'edit_url_params'        => $this->editUrlParams,
                    'view_url_route'         => $this->viewUrlRoute,
                    'view_url_params'        => $this->viewUrlParams,
                    'delete_url_route'       => $this->deleteUrlRoute,
                    'delete_url_params'      => $this->deleteUrlParams,
                    'mass_delete_url_route'  => $this->massDeleteUrlRoute,
                    'mass_delete_url_params' => $this->massDeleteUrlParams,
                    'mass_export_url_route'  => $this->massExportUrlRoute,
                    'mass_export_url_params' => $this->massExportUrlParams,
                    'show_filters_button'    => $this->showFiltersButton,
                    'show_columns_button'    => $this->showColumnsButton
                ]
            ]
        );

        if ($block instanceof AbstractBlock) {
            if ($block instanceof Grid) {
                $block->setSaveParametersInSession(true);
            }

            $this->setChild(
                'grid',
                $block
            );
        }

        return \Magento\Backend\Block\Widget\Container::_prepareLayout();
    }

    public function getCreateUrl(): string
    {
        return $this->getUrl(
            $this->addUrlRoute,
            $this->addUrlParams
        );
    }

    public function getBackUrl(): string
    {
        return $this->getUrl(
            $this->backUrlRoute,
            $this->backUrlParams
        );
    }
}
