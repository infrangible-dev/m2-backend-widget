<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid;

use FeWeDev\Base\Arrays;
use Infrangible\BackendWidget\Block\LayoutArguments;
use Infrangible\BackendWidget\Block\ObjectUrlArguments;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Grid;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;

class Container extends Grid\Container
{
    use LayoutArguments;
    use ObjectUrlArguments;

    /** @var string */
    protected string $gridContentBlockClassName;

    /** @var string */
    protected string $moduleKey;

    /** @var string */
    protected string $objectName;

    /** @var string */
    protected string $objectField;

    /** @var string */
    protected string $objectRegistryKey;

    /** @var bool */
    protected bool $allowAdd = true;

    /** @var bool */
    protected bool $allowEdit = true;

    /** @var bool */
    protected bool $allowView = true;

    /** @var bool */
    protected bool $allowDelete = true;

    /** @var bool */
    protected bool $allowMassDelete = true;

    /** @var bool */
    protected bool $allowExport = true;

    /** @var bool */
    protected bool $addRowAction = true;

    /** @var string */
    protected string $modelClass;

    /** @var string */
    protected string $collectionClass;

    /** @var string */
    protected string $addUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $addUrlParams;

    /** @var string */
    protected string $gridUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $gridUrlParams;

    /** @var string */
    protected string $backUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $backUrlParams;

    /** @var bool */
    protected bool $showFiltersButton = true;

    /** @var bool */
    protected bool $showColumnsButton = true;

    /** @var bool */
    protected bool $isTab = false;

    /**
     * @param Context $context
     * @param Arrays $arrays
     * @param array $data
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        protected Arrays $arrays,
        array $data = []
    ) {
        $this->gridContentBlockClassName = $this->stringArgument($data, 'grid_content_block_class_name');
        $this->moduleKey = $this->stringArgument($data, 'module_key', 'adminhtml');
        $this->objectName = $this->stringArgument($data, 'object_name', 'empty');
        $this->objectField = $this->stringArgument($data, 'object_field', 'id');
        $this->objectRegistryKey = $this->stringArgument($data, 'object_registry_key');
        $this->allowAdd = $this->boolArgument($data, 'allow_add', true);
        $this->allowEdit = $this->boolArgument($data, 'allow_edit', true);
        $this->allowView = $this->boolArgument($data, 'allow_view');
        $this->allowDelete = $this->boolArgument($data, 'allow_delete', true);
        $this->allowMassDelete = $this->boolArgument($data, 'allow_mass_delete', true);
        $this->allowExport = $this->boolArgument($data, 'allow_export');
        $this->addRowAction = $this->boolArgument($data, 'add_row_action', true);
        $this->modelClass = $this->stringArgument($data, 'model_class');
        $this->collectionClass = $this->stringArgument($data, 'collection_class');
        $this->addUrlRoute = $this->stringArgument($data, 'add_url_route', '*/*/add');
        $this->addUrlParams = $this->arrayArgument($data, 'add_url_params');
        $this->gridUrlRoute = $this->stringArgument($data, 'grid_url_route', '*/*/grid');
        $this->gridUrlParams = $this->arrayArgument($data, 'grid_url_params');
        $this->initObjectUrlArguments($data);
        $this->backUrlRoute = $this->stringArgument($data, 'back_url_route');
        $this->backUrlParams = $this->arrayArgument($data, 'back_url_params');
        $this->showFiltersButton = $this->boolArgument($data, 'show_filters_button', true);
        $this->showColumnsButton = $this->boolArgument($data, 'show_columns_button', true);
        $this->isTab = $this->boolArgument($data, 'is_tab');

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
            $this->stringArgument(
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
     * Prepares the layout.
     *
     * @return \Magento\Backend\Block\Widget\Container
     * @throws LocalizedException
     */
    protected function _prepareLayout(): \Magento\Backend\Block\Widget\Container
    {
        if (! is_a($this->gridContentBlockClassName, BlockInterface::class, true)) {
            throw new \LogicException(
                sprintf(
                    'Could not find block class %s that implements %s',
                    $this->gridContentBlockClassName,
                    BlockInterface::class
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
                    'show_columns_button'    => $this->showColumnsButton,
                    'is_tab'                 => $this->isTab
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

    /**
     * Returns the create url.
     *
     * @return string
     */
    public function getCreateUrl(): string
    {
        return $this->getUrl(
            $this->addUrlRoute,
            $this->addUrlParams
        );
    }

    /**
     * Returns the back url.
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl(
            $this->backUrlRoute,
            $this->backUrlParams
        );
    }
}
