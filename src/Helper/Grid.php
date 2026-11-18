<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Helper;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\CustomerGroup;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\Description;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\Product;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\ProductOption as ProductOptionRenderer;
use Infrangible\Core\Helper\Customer;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Template;
use Infrangible\Core\Model\Config\Source\Attribute;
use Infrangible\Core\Model\Config\Source\Attribute\AddressAttributeCode;
use Infrangible\Core\Model\Config\Source\Attribute\CustomerAttributeCode;
use Infrangible\Core\Model\Config\Source\Attribute\ProductAttributeCode;
use Infrangible\Core\Model\Config\Source\Attribute\SortBy;
use Infrangible\Core\Model\Config\Source\AttributeSet;
use Infrangible\Core\Model\Config\Source\Categories;
use Infrangible\Core\Model\Config\Source\CmsBlock;
use Infrangible\Core\Model\Config\Source\CmsPage;
use Infrangible\Core\Model\Config\Source\Directory\Region;
use Infrangible\Core\Model\Config\Source\Directory\RegionAny;
use Infrangible\Core\Model\Config\Source\EntityType;
use Infrangible\Core\Model\Config\Source\Operator;
use Infrangible\Core\Model\Config\Source\Payment\ActiveMethods;
use Infrangible\Core\Model\Config\Source\TypeId;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store;

/**
 * Adds the columns of the grids of the admin.
 *
 * @noinspection PhpDeprecationInspection The helper adds the columns of Magento's legacy grid.
 */
class Grid
{
    /** @var Collection */
    protected Collection $customerGroupCollection;

    /**
     * @param Template $templateHelper
     * @param Variables $variables
     * @param Customer $customerHelper
     * @param Instances $instanceHelper
     * @param Yesno $sourceYesNo
     * @param Store $sourceStore
     * @param CmsPage $sourceCmsPage
     * @param CmsBlock $sourceCmsBlock
     * @param TypeId $sourceTypeIds
     * @param Categories $sourceCategories
     * @param Operator $sourceOperator
     * @param Country $sourceCountry
     * @param Region $sourceRegion
     * @param RegionAny $sourceRegionAny
     * @param ActiveMethods $sourcePaymentActiveMethods
     * @param Attribute $sourceAttributes
     * @param AttributeSet $sourceAttributeSets
     * @param EntityType $sourceEntityTypes
     * @param ProductAttributeCode $sourceProductAttributeCode
     * @param CustomerAttributeCode $sourceCustomerAttributeCode
     * @param AddressAttributeCode $sourceAddressAttributeCode
     * @param SortBy $sourceAttributeSortBy
     */
    public function __construct(
        protected Template $templateHelper,
        protected Variables $variables,
        protected Customer $customerHelper,
        protected Instances $instanceHelper,
        protected Yesno $sourceYesNo,
        protected Store $sourceStore,
        protected CmsPage $sourceCmsPage,
        protected CmsBlock $sourceCmsBlock,
        protected TypeId $sourceTypeIds,
        protected Categories $sourceCategories,
        protected Operator $sourceOperator,
        protected Country $sourceCountry,
        protected Region $sourceRegion,
        protected RegionAny $sourceRegionAny,
        protected ActiveMethods $sourcePaymentActiveMethods,
        protected Attribute $sourceAttributes,
        protected AttributeSet $sourceAttributeSets,
        protected EntityType $sourceEntityTypes,
        protected ProductAttributeCode $sourceProductAttributeCode,
        protected CustomerAttributeCode $sourceCustomerAttributeCode,
        protected AddressAttributeCode $sourceAddressAttributeCode,
        protected SortBy $sourceAttributeSortBy
    ) {
        $this->customerGroupCollection = $this->customerHelper->getCustomerGroupCollection();
    }

    /**
     * Returns the column id.
     *
     * @param string $objectFieldName
     *
     * @return string
     */
    protected function getColumnId(string $objectFieldName): string
    {
        return $objectFieldName === 'action' ? sprintf(
            '%sgridcolumn',
            $objectFieldName
        ) : $objectFieldName;
    }

    /**
     * Adds the text column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addTextColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'index'            => $objectFieldName,
                'type'             => 'text',
                'column_css_class' => 'data-grid-td'
            ]
        );
    }

    /**
     * Adds the text column with filter.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param string $filterIndex
     *
     * @return void
     * @throws Exception
     */
    public function addTextColumnWithFilter(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $filterIndex
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'index'            => $objectFieldName,
                'filter_index'     => $filterIndex,
                'type'             => 'text',
                'column_css_class' => 'data-grid-td'
            ]
        );
    }

    /**
     * Adds the text column with filter condition.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param array $callback
     *
     * @phpstan-param array{object, string} $callback
     *
     * @return void
     * @throws Exception
     */
    public function addTextColumnWithFilterCondition(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $callback
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'                    => $label,
                'index'                     => $objectFieldName,
                'type'                      => 'text',
                'column_css_class'          => 'data-grid-td',
                'filter_condition_callback' => $callback
            ]
        );
    }

    /**
     * Adds the text column with renderer.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param string $renderer
     *
     * @return void
     * @throws Exception
     */
    public function addTextColumnWithRenderer(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $renderer
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'index'            => $objectFieldName,
                'type'             => 'text',
                'filter'           => false,
                'sortable'         => false,
                'renderer'         => $renderer,
                'column_css_class' => 'data-grid-td'
            ]
        );
    }

    /**
     * Adds the number column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addNumberColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'index'            => $objectFieldName,
                'type'             => 'number',
                'column_css_class' => 'data-grid-td'
            ]
        );
    }

    /**
     * Adds the number column with filter.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param string $filterIndex
     *
     * @return void
     * @throws Exception
     */
    public function addNumberColumnWithFilter(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $filterIndex
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'index'            => $objectFieldName,
                'filter_index'     => $filterIndex,
                'type'             => 'number',
                'column_css_class' => 'data-grid-td'
            ]
        );
    }

    /**
     * Adds the number column with filter condition.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param array $callback
     *
     * @phpstan-param array{object, string} $callback
     *
     * @return void
     * @throws Exception
     */
    public function addNumberColumnWithFilterCondition(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $callback
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'                    => $label,
                'index'                     => $objectFieldName,
                'type'                      => 'number',
                'column_css_class'          => 'data-grid-td',
                'filter_condition_callback' => $callback
            ]
        );
    }

    /**
     * Adds the price column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addPriceColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'index'            => $objectFieldName,
                'type'             => 'price',
                'column_css_class' => 'data-grid-td'
            ]
        );
    }

    /**
     * Adds the options column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param string|null $after
     *
     * @phpstan-param array<int|string, mixed> $options
     *
     * @return void
     * @throws Exception
     */
    public function addOptionsColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        ?string $after = null
    ): void {
        $config = [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'index'            => $objectFieldName,
            'options'          => $options
        ];

        if ($after) {
            $config[ 'after' ] = $after;
        }

        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            $config
        );
    }

    /**
     * Adds the options class column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param string $className
     * @param string|null $after
     *
     * @return void
     * @throws Exception
     */
    public function addOptionsClassColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $className,
        ?string $after = null
    ): void {
        $this->addOptionsClassCallbackColumn(
            $grid,
            $objectFieldName,
            $label,
            $className,
            'toOptions',
            [],
            $after
        );
    }

    /**
     * Adds the options class callback column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param string $className
     * @param string $methodName
     * @param array $parameters
     * @param string|null $after
     *
     * @phpstan-param array<int|string, mixed> $parameters
     *
     * @return void
     * @throws Exception
     */
    public function addOptionsClassCallbackColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $className,
        string $methodName,
        array $parameters = [],
        ?string $after = null
    ): void {
        /** @var OptionSourceInterface $optionsClass */
        $optionsClass = $this->instanceHelper->getSingleton($className);

        if (method_exists(
            $optionsClass,
            $methodName
        )) {
            $options = $optionsClass->$methodName(...$parameters);
        } else {
            throw new \LogicException(
                sprintf(
                    'Options class: %s does not implement method: %s',
                    $className,
                    $methodName
                )
            );
        }

        $config = [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'index'            => $objectFieldName,
            'options'          => $options
        ];

        if ($after) {
            $config[ 'after' ] = $after;
        }

        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            $config
        );
    }

    /**
     * Adds the options column with filter.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param string $filterIndex
     *
     * @phpstan-param array<int|string, mixed> $options
     *
     * @return void
     * @throws Exception
     */
    public function addOptionsColumnWithFilter(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        string $filterIndex
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'options',
                'column_css_class' => 'data-grid-td',
                'index'            => $objectFieldName,
                'filter_index'     => $filterIndex,
                'options'          => $options
            ]
        );
    }

    /**
     * Adds the options column with filter condition.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param array $callback
     *
     * @phpstan-param array<int|string, mixed> $options
     * @phpstan-param array{object, string} $callback
     *
     * @return void
     * @throws Exception
     */
    public function addOptionsColumnWithFilterCondition(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        array $callback
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'                    => $label,
                'type'                      => 'options',
                'column_css_class'          => 'data-grid-td',
                'index'                     => $objectFieldName,
                'options'                   => $options,
                'filter_condition_callback' => $callback
            ]
        );
    }

    /**
     * Adds the options column with filter condition and renderer.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param array $callback
     * @param string $renderer
     *
     * @phpstan-param array<int|string, mixed> $options
     * @phpstan-param array{object, string} $callback
     *
     * @return void
     * @throws Exception
     */
    public function addOptionsColumnWithFilterConditionAndRenderer(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        array $callback,
        string $renderer
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'                    => $label,
                'type'                      => 'options',
                'column_css_class'          => 'data-grid-td',
                'index'                     => $objectFieldName,
                'options'                   => $options,
                'filter_condition_callback' => $callback,
                'renderer'                  => $renderer
            ]
        );
    }

    /**
     * Adds the options column with filter condition and frame.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param array $filterCallback
     * @param array $frameCallback
     *
     * @phpstan-param array<int|string, mixed> $options
     * @phpstan-param array{object, string} $filterCallback
     * @phpstan-param array{object, string} $frameCallback
     *
     * @return void
     * @throws Exception
     */
    public function addOptionsColumnWithFilterConditionAndFrame(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        array $filterCallback,
        array $frameCallback
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'                    => $label,
                'type'                      => 'options',
                'column_css_class'          => 'data-grid-td',
                'index'                     => $objectFieldName,
                'options'                   => $options,
                'filter_condition_callback' => $filterCallback,
                'frame_callback'            => $frameCallback
            ]
        );
    }

    /**
     * Adds the options column with frame.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param array $callback
     *
     * @phpstan-param array<int|string, mixed> $options
     * @phpstan-param array{object, string} $callback
     *
     * @return void
     * @throws Exception
     */
    public function addOptionsColumnWithFrame(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        array $callback
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'options',
                'column_css_class' => 'data-grid-td',
                'index'            => $objectFieldName,
                'options'          => $options,
                'frame_callback'   => $callback
            ]
        );
    }

    /**
     * Adds the date column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addDateColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'date',
                'column_css_class' => 'data-grid-td date',
                'index'            => $objectFieldName
            ]
        );
    }

    /**
     * Adds the datetime column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addDatetimeColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'datetime',
                'column_css_class' => 'data-grid-td time',
                'index'            => $objectFieldName
            ]
        );
    }

    /**
     * Adds the yes no column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addYesNoColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceYesNo->toArray()
        );
    }

    /**
     * Adds the yes no column after.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param string $after
     *
     * @return void
     * @throws Exception
     */
    public function addYesNoColumnAfter(Extended $grid, string $objectFieldName, string $label, string $after): void
    {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceYesNo->toArray(),
            $after
        );
    }

    /**
     * Adds the yes no column with filter condition.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param array $callback
     *
     * @phpstan-param array{object, string} $callback
     *
     * @return void
     * @throws Exception
     */
    public function addYesNoColumnWithFilterCondition(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $callback
    ): void {
        $this->addOptionsColumnWithFilterCondition(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceYesNo->toArray(),
            $callback
        );
    }

    /**
     * Adds the website name column.
     *
     * @param \Infrangible\BackendWidget\Block\Grid $grid
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
     * @throws Exception
     */
    public function addWebsiteNameColumn(
        \Infrangible\BackendWidget\Block\Grid $grid,
        string $objectFieldName,
        ?string $label = null
    ): void {
        $grid->addJoinValues(
            'store_website',
            [$objectFieldName => 'website_id'],
            ['website_name' => 'name'],
            'website'
        );

        if ($this->variables->isEmpty($label)) {
            $label = __('Website')->render();
        }

        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'text',
                'column_css_class' => 'data-grid-td',
                'index'            => 'website_name',
                'filter_index'     => 'website.name'
            ]
        );
    }

    /**
     * Adds the store column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
     * @throws Exception
     */
    public function addStoreColumn(Extended $grid, string $objectFieldName, ?string $label = null): void
    {
        if (empty($label)) {
            $label = __('Store View')->render();
        }

        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'options',
                'column_css_class' => 'data-grid-td',
                'index'            => $objectFieldName,
                'options'          => $this->sourceStore->getStoreOptionHash(),
                'sortable'         => false
            ]
        );
    }

    /**
     * Adds the store structure column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
     * @throws Exception
     */
    public function addStoreStructureColumn(Extended $grid, string $objectFieldName, ?string $label = null): void
    {
        if (empty($label)) {
            $label = __('Store View')->render();
        }

        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'                    => $label,
                'index'                     => $objectFieldName,
                'type'                      => 'store',
                'column_css_class'          => 'data-grid-td',
                'store_all'                 => true,
                'store_view'                => true,
                'sortable'                  => false,
                'filter_condition_callback' => [$grid, 'filterStoreCondition']
            ]
        );
    }

    /**
     * Adds the store with admin structure column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
     * @throws Exception
     */
    public function addStoreWithAdminStructureColumn(
        Extended $grid,
        string $objectFieldName,
        ?string $label = null
    ): void {
        if (empty($label)) {
            $label = __('Store View')->render();
        }

        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'                    => $label,
                'index'                     => $objectFieldName,
                'type'                      => 'store_admin',
                'column_css_class'          => 'data-grid-td',
                'filter'                    => \Infrangible\BackendWidget\Block\Grid\Column\Filter\Store::class,
                'renderer'                  => \Infrangible\BackendWidget\Block\Grid\Column\Renderer\Store::class,
                'store_all'                 => true,
                'store_view'                => true,
                'sortable'                  => false,
                'filter_condition_callback' => [$grid, 'filterStoreCondition']
            ]
        );
    }

    /**
     * Adds the cms page column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
     * @throws Exception
     */
    public function addCmsPageColumn(Extended $grid, string $objectFieldName, ?string $label = null): void
    {
        if (empty($label)) {
            $label = __('Page')->render();
        }

        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'options',
                'column_css_class' => 'data-grid-td',
                'index'            => $objectFieldName,
                'options'          => $this->sourceCmsPage->toOptions(),
                'sortable'         => false
            ]
        );
    }

    /**
     * Adds the cms block column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
     * @throws Exception
     */
    public function addCmsBlockColumn(Extended $grid, string $objectFieldName, ?string $label = null): void
    {
        if (empty($label)) {
            $label = __('Block')->render();
        }

        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'options',
                'column_css_class' => 'data-grid-td',
                'index'            => $objectFieldName,
                'options'          => $this->sourceCmsBlock->toOptions(),
                'sortable'         => false
            ]
        );
    }

    /**
     * Adds the type id column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addTypeIdColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceTypeIds->toOptions()
        );
    }

    /**
     * Adds the template column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addTemplateColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'options',
                'column_css_class' => 'data-grid-td',
                'options'          => $this->templateHelper->getAllTemplates(),
                'sortable'         => false,
                'index'            => $objectFieldName,
                'renderer'         => \Infrangible\BackendWidget\Block\Grid\Column\Renderer\Template::class
            ]
        );
    }

    /**
     * Adds the categories column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addCategoriesColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'                    => $label,
                'index'                     => $objectFieldName,
                'type'                      => 'options',
                'column_css_class'          => 'data-grid-td',
                'options'                   => $this->sourceCategories->toOptions(),
                'sortable'                  => false,
                'renderer'                  => \Infrangible\BackendWidget\Block\Grid\Column\Renderer\Categories::class,
                'filter_condition_callback' => [$grid, 'filterInSet']
            ]
        );
    }

    /**
     * Adds the description column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param string $width
     * @param string $height
     *
     * @return void
     * @throws Exception
     */
    public function addDescriptionColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $width = '100%',
        string $height = '15px'
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'text',
                'column_css_class' => 'data-grid-td',
                'width'            => $width,
                'height'           => $height,
                'sortable'         => false,
                'index'            => $objectFieldName,
                'renderer'         => Description::class
            ]
        );
    }

    /**
     * Adds the operator column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addOperatorColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceOperator->toOptions()
        );
    }

    /**
     * Adds the country column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addCountryColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $optionArray = $this->sourceCountry->toOptionArray();

        $options = [];

        foreach ($optionArray as $option) {
            $options[ $option[ 'value' ] ] = $option[ 'label' ];
        }

        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $options
        );
    }

    /**
     * Adds the region column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addRegionColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceRegion->toOptions()
        );
    }

    /**
     * Adds the region any column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addRegionAnyColumn(Extended $grid, string $objectFieldName, string $label): void
    {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceRegionAny->toOptions()
        );
    }

    /**
     * Adds the customer group column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
     * @throws Exception
     */
    public function addCustomerGroupColumn(Extended $grid, string $objectFieldName, ?string $label = null): void
    {
        if ($this->variables->isEmpty($label)) {
            $label = __('Customer Group')->render();
        }

        $this->customerGroupCollection->getSelect()->order('customer_group_code ASC');
        $this->customerGroupCollection->loadData();

        $customerGroups = [];

        /** @var Group $customerGroup */
        foreach ($this->customerGroupCollection as $customerGroup) {
            $customerGroups[ $customerGroup->getId() ] = $customerGroup->getCode();
        }

        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $customerGroups
        );
    }

    /**
     * Adds the customer groups column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
     * @throws Exception
     */
    public function addCustomerGroupsColumn(Extended $grid, string $objectFieldName, ?string $label = null): void
    {
        if ($this->variables->isEmpty($label)) {
            $label = __('Customer Group')->render();
        }

        $this->customerGroupCollection->getSelect()->order('customer_group_code ASC');
        $this->customerGroupCollection->loadData();

        $customerGroups = [];

        /** @var Group $customerGroup */
        foreach ($this->customerGroupCollection as $customerGroup) {
            $customerGroups[ $customerGroup->getId() ] = $customerGroup->getCode();
        }

        $this->addOptionsColumnWithFilterConditionAndRenderer(
            $grid,
            $objectFieldName,
            $label,
            $customerGroups,
            [$grid, 'filterInSet'],
            CustomerGroup::class
        );
    }

    /**
     * Adds the payment active methods.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $allStores
     * @param bool $withDefault
     *
     * @return void
     * @throws Exception
     */
    public function addPaymentActiveMethods(
        Extended $grid,
        string $objectFieldName,
        ?string $label = null,
        bool $allStores = false,
        bool $withDefault = true
    ): void {
        if ($this->variables->isEmpty($label)) {
            $label = __('Payment Method')->render();
        }

        $this->sourcePaymentActiveMethods->setAllStores($allStores);
        $this->sourcePaymentActiveMethods->setWithDefault($withDefault);

        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourcePaymentActiveMethods->toOptions()
        );
    }

    /**
     * Adds the eav attribute column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param bool $customer
     * @param bool $address
     * @param bool $category
     * @param bool $product
     *
     * @return void
     * @throws Exception
     */
    public function addEavAttributeColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'options',
                'column_css_class' => 'data-grid-td',
                'index'            => $objectFieldName,
                'options'          => $this->sourceAttributes->toOptionsWithEntities(
                    $customer,
                    $address,
                    $category,
                    $product
                )
            ]
        );
    }

    /**
     * Adds the eav attribute value column.
     *
     * @param \Infrangible\BackendWidget\Block\Grid $grid
     * @param string $valueFieldName
     * @param string $attributeFieldName
     * @param string $label
     * @param bool $multiValue
     *
     * @return void
     * @throws Exception
     */
    public function addEavAttributeValueColumn(
        \Infrangible\BackendWidget\Block\Grid $grid,
        string $valueFieldName,
        string $attributeFieldName,
        string $label,
        bool $multiValue = false
    ): void {
        $objectFieldValueName = sprintf(
            '%s_value',
            $valueFieldName
        );

        $grid->addColumn(
            $this->getColumnId($valueFieldName),
            [
                'header'                    => $label,
                'index'                     => $objectFieldValueName,
                'type'                      => 'text',
                'column_css_class'          => 'data-grid-td',
                'filter_condition_callback' => [$grid, 'filterEavAttributeOptionValue']
            ]
        );

        if ($multiValue) {
            $grid->addJoinAttributeMultiValues(
                $valueFieldName,
                $attributeFieldName
            );
        } else {
            $grid->addJoinAttributeValues(
                $valueFieldName,
                $attributeFieldName
            );
        }
    }

    /**
     * Adds the eav attribute set column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param bool $customer
     * @param bool $address
     * @param bool $category
     * @param bool $product
     *
     * @return void
     * @throws Exception
     */
    public function addEavAttributeSetColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'options',
                'column_css_class' => 'data-grid-td',
                'index'            => $objectFieldName,
                'options'          => $this->sourceAttributeSets->toOptionsWithEntities(
                    $customer,
                    $address,
                    $category,
                    $product
                )
            ]
        );
    }

    /**
     * Adds the eav entity type column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     * @param bool $customer
     * @param bool $address
     * @param bool $category
     * @param bool $product
     *
     * @return void
     * @throws Exception
     */
    public function addEavEntityTypeColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'           => $label,
                'type'             => 'options',
                'column_css_class' => 'data-grid-td',
                'index'            => $objectFieldName,
                'options'          => $this->sourceEntityTypes->toOptionsWithEntities(
                    $customer,
                    $address,
                    $category,
                    $product
                )
            ]
        );
    }

    /**
     * Adds the product attribute code column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addProductAttributeCodeColumn(
        Extended $grid,
        string $objectFieldName,
        string $label
    ): void {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceProductAttributeCode->toOptions()
        );
    }

    /**
     * Adds the customer attribute code column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addCustomerAttributeCodeColumn(
        Extended $grid,
        string $objectFieldName,
        string $label
    ): void {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceCustomerAttributeCode->toOptions()
        );
    }

    /**
     * Adds the address attribute code column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addAddressAttributeCodeColumn(
        Extended $grid,
        string $objectFieldName,
        string $label
    ): void {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceAddressAttributeCode->toOptions()
        );
    }

    /**
     * Adds the attribute sort by column.
     *
     * @param Extended $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addAttributeSortByColumn(
        Extended $grid,
        string $objectFieldName,
        string $label
    ): void {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceAttributeSortBy->toOptions()
        );
    }

    /**
     * Adds the customer name column.
     *
     * @param \Infrangible\BackendWidget\Block\Grid $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addCustomerNameColumn(
        \Infrangible\BackendWidget\Block\Grid $grid,
        string $objectFieldName,
        string $label
    ): void {
        $columnTableAlias = sprintf(
            '%s_customer',
            $objectFieldName
        );

        $grid->addJoinValues(
            'customer_entity',
            [$objectFieldName => 'entity_id'],
            [
                sprintf(
                    '%s_firstname',
                    $objectFieldName
                ) => sprintf(
                    '%s.firstname',
                    $columnTableAlias
                ),
                sprintf(
                    '%s_lastname',
                    $objectFieldName
                ) => sprintf(
                    '%s.lastname',
                    $columnTableAlias
                )
            ],
            $columnTableAlias
        );

        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'                    => $label,
                'index'                     => $objectFieldName,
                'type'                      => 'text',
                'column_css_class'          => 'data-grid-td',
                'sortable'                  => false,
                'renderer'                  => \Infrangible\BackendWidget\Block\Grid\Column\Renderer\Customer::class,
                'filter_condition_callback' => [$grid, 'filterCustomerName']
            ]
        );
    }

    /**
     * Adds the product name column.
     *
     * @param \Infrangible\BackendWidget\Block\Grid $grid
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addProductNameColumn(
        \Infrangible\BackendWidget\Block\Grid $grid,
        string $objectFieldName,
        string $label
    ): void {
        $valueTableAlias = sprintf(
            '%s_name',
            $objectFieldName
        );

        $attributeTableAlias = sprintf(
            '%s_name_attribute',
            $objectFieldName
        );

        $grid->addJoinValues(
            'catalog_product_entity_varchar',
            [
                $objectFieldName => 'entity_id',
                sprintf(
                    '%s.store_id = 0',
                    $valueTableAlias
                )
            ],
            [
                sprintf(
                    '%s_product_name',
                    $objectFieldName
                ) => sprintf(
                    '%s.value',
                    $valueTableAlias
                )
            ],
            $valueTableAlias,
            [
                sprintf(
                    '%s.attribute_code = "name"',
                    $attributeTableAlias
                )
            ]
        );

        $grid->addJoinValues(
            'eav_attribute',
            [
                sprintf(
                    '%s.attribute_id',
                    $valueTableAlias
                ) => 'attribute_id'
            ],
            [],
            $attributeTableAlias
        );

        $grid->addColumn(
            $this->getColumnId($objectFieldName),
            [
                'header'                    => $label,
                'index'                     => $objectFieldName,
                'type'                      => 'text',
                'column_css_class'          => 'data-grid-td',
                'sortable'                  => false,
                'renderer'                  => Product::class,
                'filter_condition_callback' => [$grid, 'filterProductName']
            ]
        );
    }

    /**
     * Adds the product option column.
     *
     * @param \Infrangible\BackendWidget\Block\Grid $grid
     * @param string $valueFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addProductOptionColumn(
        \Infrangible\BackendWidget\Block\Grid $grid,
        string $valueFieldName,
        string $label
    ): void {
        $grid->addColumn(
            $this->getColumnId($valueFieldName),
            [
                'header'                    => $label,
                'index'                     => $valueFieldName,
                'type'                      => 'text',
                'column_css_class'          => 'data-grid-td',
                'sortable'                  => false,
                'renderer'                  => ProductOptionRenderer::class,
                'filter_condition_callback' => [$grid, 'filterProductOption']
            ]
        );

        $productOptionTitleAliasName = sprintf(
            '%s_cpot',
            $valueFieldName
        );

        $grid->addJoinValues(
            'catalog_product_option_title',
            [
                $valueFieldName => sprintf(
                    '%s.option_id',
                    $productOptionTitleAliasName
                ),
                sprintf(
                    '%s.store_id = 0',
                    $productOptionTitleAliasName
                )
            ],
            [
                sprintf(
                    '%s_title',
                    $valueFieldName
                ) => sprintf(
                    '%s.title',
                    $productOptionTitleAliasName
                )
            ],
            $productOptionTitleAliasName,
            [],
            false
        );
    }

    /**
     * Adds the product option value column.
     *
     * @param \Infrangible\BackendWidget\Block\Grid $grid
     * @param string $valueFieldName
     * @param string $label
     *
     * @return void
     * @throws Exception
     */
    public function addProductOptionValueColumn(
        \Infrangible\BackendWidget\Block\Grid $grid,
        string $valueFieldName,
        string $label
    ): void {
        $grid->addColumn(
            $this->getColumnId($valueFieldName),
            [
                'header'                    => $label,
                'index'                     => $valueFieldName,
                'type'                      => 'text',
                'column_css_class'          => 'data-grid-td',
                'sortable'                  => false,
                'renderer'                  => ProductOptionRenderer::class,
                'filter_condition_callback' => [$grid, 'filterProductOptionValue']
            ]
        );

        $productOptionTypeTitleAliasName = sprintf(
            '%s_cpott',
            $valueFieldName
        );

        $grid->addJoinValues(
            'catalog_product_option_type_title',
            [
                $valueFieldName => sprintf(
                    '%s.option_type_id',
                    $productOptionTypeTitleAliasName
                ),
                sprintf(
                    '%s.store_id = 0',
                    $productOptionTypeTitleAliasName
                )
            ],
            [
                sprintf(
                    '%s_title',
                    $valueFieldName
                ) => sprintf(
                    '%s.title',
                    $productOptionTypeTitleAliasName
                )
            ],
            $productOptionTypeTitleAliasName,
            [],
            false
        );
    }
}
