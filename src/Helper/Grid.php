<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Helper;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\CustomerGroup;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\Description;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\Product;
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
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Grid
{
    /** @var Template */
    protected $templateHelper;

    /** @var Variables */
    protected $variables;

    /** @var Customer */
    protected $customerHelper;

    /** @var Instances */
    protected $instanceHelper;

    /** @var Yesno */
    protected $sourceYesNo;

    /** @var Store */
    protected $sourceStore;

    /** @var CmsPage */
    protected $sourceCmsPage;

    /** @var CmsBlock */
    protected $sourceCmsBlock;

    /** @var TypeId */
    protected $sourceTypeIds;

    /** @var Categories */
    protected $sourceCategories;

    /** @var Operator */
    protected $sourceOperator;

    /** @var Country */
    protected $sourceCountry;

    /** @var Region */
    protected $sourceRegion;

    /** @var RegionAny */
    protected $sourceRegionAny;

    /** @var ActiveMethods */
    protected $sourcePaymentActiveMethods;

    /** @var Attribute */
    protected $sourceAttributes;

    /** @var AttributeSet */
    protected $sourceAttributeSets;

    /** @var EntityType */
    protected $sourceEntityTypes;

    /** @var ProductAttributeCode */
    protected $sourceProductAttributeCode;

    /** @var CustomerAttributeCode */
    protected $sourceCustomerAttributeCode;

    /** @var AddressAttributeCode */
    protected $sourceAddressAttributeCode;

    /** @var SortBy */
    protected $sourceAttributeSortBy;

    /** @var Collection */
    protected $customerGroupCollection;

    public function __construct(
        Template $templateHelper,
        Variables $variables,
        Customer $customerHelper,
        Instances $instanceHelper,
        Yesno $sourceYesNo,
        Store $sourceStore,
        CmsPage $sourceCmsPage,
        CmsBlock $sourceCmsBlock,
        TypeId $sourceTypeIds,
        Categories $sourceCategories,
        Operator $sourceOperator,
        Country $sourceCountry,
        Region $sourceRegion,
        RegionAny $sourceRegionAny,
        ActiveMethods $sourcePaymentActiveMethods,
        Attribute $sourceAttributes,
        AttributeSet $sourceAttributeSets,
        EntityType $sourceEntityTypes,
        ProductAttributeCode $sourceProductAttributeCode,
        CustomerAttributeCode $sourceCustomerAttributeCode,
        AddressAttributeCode $sourceAddressAttributeCode,
        SortBy $sourceAttributeSortBy
    ) {
        $this->templateHelper = $templateHelper;
        $this->variables = $variables;
        $this->customerHelper = $customerHelper;
        $this->instanceHelper = $instanceHelper;

        $this->sourceYesNo = $sourceYesNo;
        $this->sourceStore = $sourceStore;
        $this->sourceCmsPage = $sourceCmsPage;
        $this->sourceCmsBlock = $sourceCmsBlock;
        $this->sourceTypeIds = $sourceTypeIds;
        $this->sourceCategories = $sourceCategories;
        $this->sourceOperator = $sourceOperator;
        $this->sourceCountry = $sourceCountry;
        $this->sourceRegion = $sourceRegion;
        $this->sourceRegionAny = $sourceRegionAny;
        $this->sourcePaymentActiveMethods = $sourcePaymentActiveMethods;
        $this->sourceAttributes = $sourceAttributes;
        $this->sourceAttributeSets = $sourceAttributeSets;
        $this->sourceEntityTypes = $sourceEntityTypes;
        $this->sourceProductAttributeCode = $sourceProductAttributeCode;
        $this->sourceCustomerAttributeCode = $sourceCustomerAttributeCode;
        $this->sourceAddressAttributeCode = $sourceAddressAttributeCode;
        $this->sourceAttributeSortBy = $sourceAttributeSortBy;

        $this->customerGroupCollection = $this->customerHelper->getCustomerGroupCollection();
    }

    protected function getColumnId(string $objectFieldName): string
    {
        return $objectFieldName === 'action' ? sprintf(
            '%sgridcolumn',
            $objectFieldName
        ) : $objectFieldName;
    }

    /**
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
     * @throws Exception
     */
    public function addTextColumnWithFilterCondition(
        Extended $grid,
        string $objectFieldName,
        string $label,
        $callback
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
     * @throws Exception
     */
    public function addNumberColumnWithFilterCondition(
        Extended $grid,
        string $objectFieldName,
        string $label,
        $callback
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
     * @throws Exception
     */
    public function addOptionsColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        $after = null
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
     * @throws Exception
     */
    public function addOptionsClassColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $className,
        $after = null
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
     * @throws Exception
     */
    public function addOptionsClassCallbackColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $className,
        string $methodName,
        array $parameters = [],
        $after = null
    ): void {
        /** @var OptionSourceInterface $optionsClass */
        $optionsClass = $this->instanceHelper->getSingleton($className);

        if (method_exists(
            $optionsClass,
            $methodName
        )) {
            $options = call_user_func_array([$optionsClass, $methodName],
                $parameters);
        } else {
            throw new Exception(
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
     * @throws Exception
     */
    public function addOptionsColumnWithFilterCondition(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        $callback
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
     * @throws Exception
     */
    public function addOptionsColumnWithFilterConditionAndRenderer(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        $callback,
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
     * @throws Exception
     */
    public function addOptionsColumnWithFilterConditionAndFrame(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        $filterCallback,
        $frameCallback
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
     * @throws Exception
     */
    public function addOptionsColumnWithFrame(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        $callback
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
     * @throws Exception
     */
    public function addYesNoColumnWithFilterCondition(
        Extended $grid,
        string $objectFieldName,
        string $label,
        $callback
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
     * @throws Exception
     */
    public function addWebsiteNameColumn(Extended $grid, string $objectFieldName, ?string $label = null): void
    {
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
     * @throws Exception
     */
    public function addTypeIdColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceTypeIds->toOptions()
        );
    }

    /**
     * @throws Exception
     */
    public function addTemplateColumn(Extended $grid, string $objectFieldName, string $label)
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
     * @throws Exception
     */
    public function addCategoriesColumn(Extended $grid, string $objectFieldName, string $label)
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
     * @throws Exception
     */
    public function addOperatorColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceOperator->toOptions()
        );
    }

    /**
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
     * @throws Exception
     */
    public function addRegionColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceRegion->toOptions()
        );
    }

    /**
     * @throws Exception
     */
    public function addRegionAnyColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $this->addOptionsColumn(
            $grid,
            $objectFieldName,
            $label,
            $this->sourceRegionAny->toOptions()
        );
    }

    /**
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
     * @throws Exception
     */
    public function addCustomerNameColumn(Extended $grid, string $objectFieldName, string $label)
    {
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
     * @throws Exception
     */
    public function addProductNameColumn(Extended $grid, string $objectFieldName, string $label)
    {
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
}
