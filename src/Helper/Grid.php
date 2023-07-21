<?php /** @noinspection PhpDeprecationInspection */

namespace Infrangible\BackendWidget\Helper;

use Exception;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\CustomerGroup;
use Infrangible\BackendWidget\Block\Grid\Column\Renderer\Description;
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
use Infrangible\Core\Model\Config\Source\EntityType;
use Infrangible\Core\Model\Config\Source\Operator;
use Infrangible\Core\Model\Config\Source\Payment\ActiveMethods;
use Infrangible\Core\Model\Config\Source\TypeId;
use Tofex\Help\Variables;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Grid
{
    /** @var Template */
    protected $templateHelper;

    /** @var Variables */
    protected $variableHelper;

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

    /**
     * @param Template              $templateHelper
     * @param Variables             $variableHelper
     * @param Customer              $customerHelper
     * @param Instances             $instanceHelper
     * @param Yesno                 $sourceYesNo
     * @param Store                 $sourceStore
     * @param CmsPage               $sourceCmsPage
     * @param CmsBlock              $sourceCmsBlock
     * @param TypeId                $sourceTypeIds
     * @param Categories            $sourceCategories
     * @param Operator              $sourceOperator
     * @param Country               $sourceCountry
     * @param ActiveMethods         $sourcePaymentActiveMethods
     * @param Attribute             $sourceAttributes
     * @param AttributeSet          $sourceAttributeSets
     * @param EntityType            $sourceEntityTypes
     * @param ProductAttributeCode  $sourceProductAttributeCode
     * @param CustomerAttributeCode $sourceCustomerAttributeCode
     * @param AddressAttributeCode  $sourceAddressAttributeCode
     * @param SortBy                $sourceAttributeSortBy
     */
    public function __construct(
        Template $templateHelper,
        Variables $variableHelper,
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
        ActiveMethods $sourcePaymentActiveMethods,
        Attribute $sourceAttributes,
        AttributeSet $sourceAttributeSets,
        EntityType $sourceEntityTypes,
        ProductAttributeCode $sourceProductAttributeCode,
        CustomerAttributeCode $sourceCustomerAttributeCode,
        AddressAttributeCode $sourceAddressAttributeCode,
        SortBy $sourceAttributeSortBy)
    {
        $this->templateHelper = $templateHelper;
        $this->variableHelper = $variableHelper;
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

    /**
     * @param string $objectFieldName
     *
     * @return string
     */
    protected function getColumnId(string $objectFieldName): string
    {
        return $objectFieldName === 'action' ? sprintf('%sgridcolumn', $objectFieldName) : $objectFieldName;
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addTextColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'index'            => $objectFieldName,
            'type'             => 'text',
            'column_css_class' => 'data-grid-td'
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $filterIndex
     *
     * @throws Exception
     */
    public function addTextColumnWithFilter(Extended $grid, string $objectFieldName, string $label, string $filterIndex)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'index'            => $objectFieldName,
            'filter_index'     => $filterIndex,
            'type'             => 'text',
            'column_css_class' => 'data-grid-td'
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param mixed    $callback
     *
     * @throws Exception
     */
    public function addTextColumnWithFilterCondition(Extended $grid, string $objectFieldName, string $label, $callback)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'                    => $label,
            'index'                     => $objectFieldName,
            'type'                      => 'text',
            'column_css_class'          => 'data-grid-td',
            'filter_condition_callback' => $callback
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $renderer
     *
     * @throws Exception
     */
    public function addTextColumnWithRenderer(Extended $grid, string $objectFieldName, string $label, string $renderer)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'index'            => $objectFieldName,
            'type'             => 'text',
            'filter'           => false,
            'sortable'         => false,
            'renderer'         => $renderer,
            'column_css_class' => 'data-grid-td'
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addNumberColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'index'            => $objectFieldName,
            'type'             => 'number',
            'column_css_class' => 'data-grid-td'
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $filterIndex
     *
     * @throws Exception
     */
    public function addNumberColumnWithFilter(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $filterIndex)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'index'            => $objectFieldName,
            'filter_index'     => $filterIndex,
            'type'             => 'number',
            'column_css_class' => 'data-grid-td'
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param mixed    $callback
     *
     * @throws Exception
     */
    public function addNumberColumnWithFilterCondition(
        Extended $grid,
        string $objectFieldName,
        string $label,
        $callback)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'                    => $label,
            'index'                     => $objectFieldName,
            'type'                      => 'number',
            'column_css_class'          => 'data-grid-td',
            'filter_condition_callback' => $callback
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addPriceColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'index'            => $objectFieldName,
            'type'             => 'price',
            'column_css_class' => 'data-grid-td'
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param array    $options
     * @param mixed    $after
     *
     * @throws Exception
     */
    public function addOptionsColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        $after = null)
    {
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

        $grid->addColumn($this->getColumnId($objectFieldName), $config);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $className
     * @param mixed    $after
     *
     * @throws Exception
     */
    public function addOptionsClassColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $className,
        $after = null)
    {
        $this->addOptionsClassCallbackColumn($grid, $objectFieldName, $label, $className, 'toOptions', [], $after);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $className
     * @param string   $methodName
     * @param array    $parameters
     * @param mixed    $after
     *
     * @throws Exception
     */
    public function addOptionsClassCallbackColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $className,
        string $methodName,
        array $parameters = [],
        $after = null)
    {
        /** @var OptionSourceInterface $optionsClass */
        $optionsClass = $this->instanceHelper->getSingleton($className);

        if (method_exists($optionsClass, $methodName)) {
            $options = call_user_func_array([$optionsClass, $methodName], $parameters);
        } else {
            throw new Exception(sprintf('Options class: %s does not implement method: %s', $className, $methodName));
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

        $grid->addColumn($this->getColumnId($objectFieldName), $config);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param array    $options
     * @param string   $filterIndex
     *
     * @throws Exception
     */
    public function addOptionsColumnWithFilter(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        string $filterIndex)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'index'            => $objectFieldName,
            'filter_index'     => $filterIndex,
            'options'          => $options
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param array    $options
     * @param mixed    $callback
     *
     * @throws Exception
     */
    public function addOptionsColumnWithFilterCondition(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        $callback)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'                    => $label,
            'type'                      => 'options',
            'column_css_class'          => 'data-grid-td',
            'index'                     => $objectFieldName,
            'options'                   => $options,
            'filter_condition_callback' => $callback
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param array    $options
     * @param mixed    $callback
     * @param string   $renderer
     *
     * @throws Exception
     */
    public function addOptionsColumnWithFilterConditionAndRenderer(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        $callback,
        string $renderer)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'                    => $label,
            'type'                      => 'options',
            'column_css_class'          => 'data-grid-td',
            'index'                     => $objectFieldName,
            'options'                   => $options,
            'filter_condition_callback' => $callback,
            'renderer'                  => $renderer
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param array    $options
     * @param mixed    $filterCallback
     * @param mixed    $frameCallback
     *
     * @throws Exception
     */
    public function addOptionsColumnWithFilterConditionAndFrame(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        $filterCallback,
        $frameCallback)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'                    => $label,
            'type'                      => 'options',
            'column_css_class'          => 'data-grid-td',
            'index'                     => $objectFieldName,
            'options'                   => $options,
            'filter_condition_callback' => $filterCallback,
            'frame_callback'            => $frameCallback
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param array    $options
     * @param mixed    $callback
     *
     * @throws Exception
     */
    public function addOptionsColumnWithFrame(
        Extended $grid,
        string $objectFieldName,
        string $label,
        array $options,
        $callback)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'index'            => $objectFieldName,
            'options'          => $options,
            'frame_callback'   => $callback
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addDateColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'date',
            'column_css_class' => 'data-grid-td date',
            'index'            => $objectFieldName
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addDatetimeColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'datetime',
            'column_css_class' => 'data-grid-td time',
            'index'            => $objectFieldName
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addYesNoColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $this->addOptionsColumn($grid, $objectFieldName, $label, $this->sourceYesNo->toArray());
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $after
     *
     * @throws Exception
     */
    public function addYesNoColumnAfter(Extended $grid, string $objectFieldName, string $label, string $after)
    {
        $this->addOptionsColumn($grid, $objectFieldName, $label, $this->sourceYesNo->toArray(), $after);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param mixed    $callback
     *
     * @throws Exception
     */
    public function addYesNoColumnWithFilterCondition(Extended $grid, string $objectFieldName, string $label, $callback)
    {
        $this->addOptionsColumnWithFilterCondition($grid, $objectFieldName, $label, $this->sourceYesNo->toArray(),
            $callback);
    }

    /**
     * @param Extended    $grid
     * @param string      $objectFieldName
     * @param string|null $label
     *
     * @throws Exception
     */
    public function addWebsiteNameColumn(Extended $grid, string $objectFieldName, string $label = null)
    {
        if ($this->variableHelper->isEmpty($label)) {
            $label = __('Website');
        }

        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'text',
            'column_css_class' => 'data-grid-td',
            'index'            => 'website_name',
            'filter_index'     => 'website.name'
        ]);
    }

    /**
     * @param Extended    $grid
     * @param string      $objectFieldName
     * @param string|null $label
     *
     * @throws Exception
     */
    public function addStoreColumn(Extended $grid, string $objectFieldName, string $label = null)
    {
        if (empty($label)) {
            $label = __('Store View');
        }

        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'index'            => $objectFieldName,
            'options'          => $this->sourceStore->getStoreOptionHash(false),
            'sortable'         => false
        ]);
    }

    /**
     * @param Extended    $grid
     * @param string      $objectFieldName
     * @param string|null $label
     *
     * @throws Exception
     */
    public function addStoreStructureColumn(Extended $grid, string $objectFieldName, string $label = null)
    {
        if (empty($label)) {
            $label = __('Store View');
        }

        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'                    => $label,
            'index'                     => $objectFieldName,
            'type'                      => 'store',
            'column_css_class'          => 'data-grid-td',
            'store_all'                 => true,
            'store_view'                => true,
            'sortable'                  => false,
            'filter_condition_callback' => [$grid, 'filterStoreCondition']
        ]);
    }

    /**
     * @param Extended    $grid
     * @param string      $objectFieldName
     * @param string|null $label
     *
     * @throws Exception
     */
    public function addStoreWithAdminStructureColumn(Extended $grid, string $objectFieldName, string $label = null)
    {
        if (empty($label)) {
            $label = __('Store View');
        }

        $grid->addColumn($this->getColumnId($objectFieldName), [
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
        ]);
    }

    /**
     * @param Extended    $grid
     * @param string      $objectFieldName
     * @param string|null $label
     *
     * @throws Exception
     */
    public function addCmsPageColumn(Extended $grid, string $objectFieldName, string $label = null)
    {
        if (empty($label)) {
            $label = __('Page');
        }

        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'index'            => $objectFieldName,
            'options'          => $this->sourceCmsPage->toOptions(),
            'sortable'         => false
        ]);
    }

    /**
     * @param Extended    $grid
     * @param string      $objectFieldName
     * @param string|null $label
     *
     * @throws Exception
     */
    public function addCmsBlockColumn(Extended $grid, string $objectFieldName, string $label = null)
    {
        if (empty($label)) {
            $label = __('Block');
        }

        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'index'            => $objectFieldName,
            'options'          => $this->sourceCmsBlock->toOptions(),
            'sortable'         => false
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addTypeIdColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $this->addOptionsColumn($grid, $objectFieldName, $label, $this->sourceTypeIds->toOptions());
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addTemplateColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'options'          => $this->templateHelper->getAllTemplates(),
            'sortable'         => false,
            'index'            => $objectFieldName,
            'renderer'         => \Infrangible\BackendWidget\Block\Grid\Column\Renderer\Template::class
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addCategoriesColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'                    => $label,
            'index'                     => $objectFieldName,
            'type'                      => 'options',
            'column_css_class'          => 'data-grid-td',
            'options'                   => $this->sourceCategories->toOptions(),
            'sortable'                  => false,
            'renderer'                  => \Infrangible\BackendWidget\Block\Grid\Column\Renderer\Categories::class,
            'filter_condition_callback' => [$grid, 'filterInSet']
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $width
     * @param string   $height
     *
     * @throws Exception
     */
    public function addDescriptionColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        string $width = '100%',
        string $height = '15px')
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'text',
            'column_css_class' => 'data-grid-td',
            'width'            => $width,
            'height'           => $height,
            'sortable'         => false,
            'index'            => $objectFieldName,
            'renderer'         => Description::class
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addOperatorColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $this->addOptionsColumn($grid, $objectFieldName, $label, $this->sourceOperator->toOptions());
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addCountryColumn(Extended $grid, string $objectFieldName, string $label)
    {
        $optionArray = $this->sourceCountry->toOptionArray(false);

        $options = [];

        foreach ($optionArray as $option) {
            $options[ $option[ 'value' ] ] = $option[ 'label' ];
        }

        $this->addOptionsColumn($grid, $objectFieldName, $label, $options);
    }

    /**
     * @param Extended    $grid
     * @param string      $objectFieldName
     * @param string|null $label
     *
     * @throws Exception
     */
    public function addCustomerGroupColumn(Extended $grid, string $objectFieldName, string $label = null)
    {
        if ($this->variableHelper->isEmpty($label)) {
            $label = __('Customer Group');
        }

        $this->customerGroupCollection->getSelect()->order('customer_group_code ASC');
        $this->customerGroupCollection->loadData();

        $customerGroups = [];

        /** @var Group $customerGroup */
        foreach ($this->customerGroupCollection as $customerGroup) {
            $customerGroups[ $customerGroup->getId() ] = $customerGroup->getCode();
        }

        $this->addOptionsColumn($grid, $objectFieldName, $label, $customerGroups);
    }

    /**
     * @param Extended    $grid
     * @param string      $objectFieldName
     * @param string|null $label
     *
     * @throws Exception
     */
    public function addCustomerGroupsColumn(Extended $grid, string $objectFieldName, string $label = null)
    {
        if ($this->variableHelper->isEmpty($label)) {
            $label = __('Customer Group');
        }

        $this->customerGroupCollection->getSelect()->order('customer_group_code ASC');
        $this->customerGroupCollection->loadData();

        $customerGroups = [];

        /** @var Group $customerGroup */
        foreach ($this->customerGroupCollection as $customerGroup) {
            $customerGroups[ $customerGroup->getId() ] = $customerGroup->getCode();
        }

        $this->addOptionsColumnWithFilterConditionAndRenderer($grid, $objectFieldName, $label, $customerGroups,
            [$grid, 'filterInSet'], CustomerGroup::class);
    }

    /**
     * @param Extended    $grid
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $allStores
     * @param bool        $withDefault
     *
     * @throws Exception
     */
    public function addPaymentActiveMethods(
        Extended $grid,
        string $objectFieldName,
        string $label = null,
        bool $allStores = false,
        bool $withDefault = true)
    {
        if ($this->variableHelper->isEmpty($label)) {
            $label = __('Payment Method');
        }

        $this->sourcePaymentActiveMethods->setAllStores($allStores);
        $this->sourcePaymentActiveMethods->setWithDefault($withDefault);

        $this->addOptionsColumn($grid, $objectFieldName, $label, $this->sourcePaymentActiveMethods->toOptions());
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $customer
     * @param bool     $address
     * @param bool     $category
     * @param bool     $product
     *
     * @throws Exception
     */
    public function addEavAttributeColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'index'            => $objectFieldName,
            'options'          => $this->sourceAttributes->toOptionsWithEntities($customer, $address, $category,
                $product)
        ]);
    }

    /**
     * @param \Infrangible\BackendWidget\Block\Grid $grid
     * @param string                          $valueFieldName
     * @param string                          $attributeFieldName
     * @param string                          $label
     * @param bool                            $multiValue
     *
     * @throws Exception
     */
    public function addEavAttributeValueColumn(
        \Infrangible\BackendWidget\Block\Grid $grid,
        string $valueFieldName,
        string $attributeFieldName,
        string $label,
        bool $multiValue = false)
    {
        $objectFieldValueName = sprintf('%s_value', $valueFieldName);

        $grid->addColumn($this->getColumnId($valueFieldName), [
            'header'                    => $label,
            'index'                     => $objectFieldValueName,
            'type'                      => 'text',
            'column_css_class'          => 'data-grid-td',
            'filter_condition_callback' => [$grid, 'filterEavAttributeOptionValue']
        ]);

        if ($multiValue) {
            $grid->addJoinAttributeMultiValues($valueFieldName, $attributeFieldName);
        } else {
            $grid->addJoinAttributeValues($valueFieldName, $attributeFieldName);
        }
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $customer
     * @param bool     $address
     * @param bool     $category
     * @param bool     $product
     *
     * @throws Exception
     */
    public function addEavAttributeSetColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'index'            => $objectFieldName,
            'options'          => $this->sourceAttributeSets->toOptionsWithEntities($customer, $address, $category,
                $product)
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $customer
     * @param bool     $address
     * @param bool     $category
     * @param bool     $product
     *
     * @throws Exception
     */
    public function addEavEntityTypeColumn(
        Extended $grid,
        string $objectFieldName,
        string $label,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true)
    {
        $grid->addColumn($this->getColumnId($objectFieldName), [
            'header'           => $label,
            'type'             => 'options',
            'column_css_class' => 'data-grid-td',
            'index'            => $objectFieldName,
            'options'          => $this->sourceEntityTypes->toOptionsWithEntities($customer, $address, $category,
                $product)
        ]);
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addProductAttributeCodeColumn(
        Extended $grid,
        string $objectFieldName,
        string $label)
    {
        $this->addOptionsColumn($grid, $objectFieldName, $label, $this->sourceProductAttributeCode->toOptions());
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addCustomerAttributeCodeColumn(
        Extended $grid,
        string $objectFieldName,
        string $label)
    {
        $this->addOptionsColumn($grid, $objectFieldName, $label, $this->sourceCustomerAttributeCode->toOptions());
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addAddressAttributeCodeColumn(
        Extended $grid,
        string $objectFieldName,
        string $label)
    {
        $this->addOptionsColumn($grid, $objectFieldName, $label, $this->sourceAddressAttributeCode->toOptions());
    }

    /**
     * @param Extended $grid
     * @param string   $objectFieldName
     * @param string   $label
     *
     * @throws Exception
     */
    public function addAttributeSortByColumn(
        Extended $grid,
        string $objectFieldName,
        string $label)
    {
        $this->addOptionsColumn($grid, $objectFieldName, $label, $this->sourceAttributeSortBy->toOptions());
    }
}
