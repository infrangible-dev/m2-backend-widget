<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Block\Grid\Fields;
use Infrangible\BackendWidget\Block\Grid\MassAction;
use Infrangible\BackendWidget\Block\Grid\Tab\Add;
use Infrangible\BackendWidget\Helper\Session;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Widget\Grid\Massaction\AbstractMassaction;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Validator\UniversalFactory;
use Zend_Db_Expr;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Grid extends Extended
{
    /** @var Database */
    protected $databaseHelper;

    /** @var Variables */
    protected $variables;

    /** @var Registry */
    protected $registryHelper;

    /** @var \Infrangible\BackendWidget\Helper\Grid */
    protected $gridHelper;

    /** @var Session */
    protected $sessionHelper;

    /** @var Arrays */
    protected $arrays;

    /** @var UniversalFactory */
    protected $universalFactory;

    /** @var Config */
    protected $eavConfig;

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
    protected $allowMassDelete = false;

    /** @var bool */
    protected $allowExport = true;

    /** @var bool */
    protected $addRowAction = true;

    /** @var string */
    protected $modelClass;

    /** @var string */
    protected $collectionClass;

    /** @var string */
    protected $gridUrlRoute;

    /** @var array */
    protected $gridUrlParams;

    /** @var string */
    protected $addUrlRoute;

    /** @var array */
    protected $addUrlParams;

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

    /** @var bool */
    protected $showFiltersButton = true;

    /** @var bool */
    protected $showColumnsButton = true;

    /** @var array */
    private $actions = [];

    /** @var array */
    private $massActions = [];

    /** @var AbstractModel */
    private $object;

    /** @var array */
    private $joinValues = [];

    /** @var array */
    private $joinAttributeValues = [];

    /** @var array */
    private $joinAttributeMultiValues = [];

    public function __construct(
        Context $context,
        Data $backendHelper,
        Database $databaseHelper,
        Arrays $arrays,
        Variables $variables,
        Registry $registryHelper,
        \Infrangible\BackendWidget\Helper\Grid $gridHelper,
        Session $sessionHelper,
        UniversalFactory $universalFactory,
        Config $eavConfig,
        array $data = []
    ) {
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
            false
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
            false
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

        parent::__construct(
            $context,
            $backendHelper,
            $data
        );

        $this->databaseHelper = $databaseHelper;
        $this->variables = $variables;
        $this->registryHelper = $registryHelper;
        $this->gridHelper = $gridHelper;
        $this->sessionHelper = $sessionHelper;
        $this->arrays = $arrays;
        $this->universalFactory = $universalFactory;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @throws FileSystemException
     * @noinspection PhpDocRedundantThrowsInspection
     * @noinspection RedundantSuppression
     */
    public function _construct(): void
    {
        parent::_construct();

        $this->setData(
            'id',
            $this->getGridId()
        );

        $this->setSaveParametersInSession(true);
        $this->setData(
            'use_ajax',
            true
        );
        $this->setVarNamePage('p');

        $this->setMassactionBlockName(MassAction::class);
    }

    protected function getGridId(): string
    {
        return sprintf(
            'adminhtml_%s_%s_grid',
            $this->moduleKey,
            preg_replace(
                '/[^a-z0-9_]*/i',
                '',
                $this->objectName
            )
        );
    }

    /**
     * @throws LocalizedException
     */
    protected function _prepareFilterButtons(): void
    {
        if ($this->allowAdd) {
            /** @var Button $addButton */
            $addButton = $this->getLayout()->createBlock(Button::class);

            $addButton->addData([
                'label'      => __('Add'),
                'class'      => 'action-columns action-primary',
                'after_html' => $this->getAddButtonBlock($addButton)->toHtml()
            ]);

            $this->setChild(
                'add_button',
                $addButton
            );
        }

        if ($this->showFiltersButton) {
            /** @var Button $filtersButton */
            $filtersButton = $this->getLayout()->createBlock(Button::class);

            $filtersButton->setData([
                'label' => __('Show filters'),
                'class' => 'action-filters action-tertiary'
            ]);

            $this->setChild(
                'filters_button',
                $filtersButton
            );
        }

        if ($this->showColumnsButton) {
            /** @var Button $columnsButton */
            $columnsButton = $this->getLayout()->createBlock(Button::class);

            $columnsButton->setData([
                'label'      => __('Show columns'),
                'class'      => 'action-columns action-tertiary',
                'after_html' => $this->getFieldsBlock()->toHtml()
            ]);

            $this->setChild(
                'columns_button',
                $columnsButton
            );
        }

        parent::_prepareFilterButtons();
    }

    /**
     * @throws LocalizedException
     */
    protected function getFieldsBlock(): Fields
    {
        /** @var Fields $fields */
        $fields = $this->getLayout()->createBlock(Fields::class);

        $fields->setDataGridId($this->getHtmlId());
        $fields->setJsObjectName($this->getJsObjectName());
        $fields->setFieldList($this->getFieldList());

        return $fields;
    }

    /**
     * @throws LocalizedException
     */
    protected function getFieldList(): array
    {
        $fieldList = [];

        foreach ($this->getColumnSet()->getChildNames() as $childName) {
            $column = $this->getLayout()->getBlock($childName);

            if ($column instanceof Column) {
                if (! $column->getData('is_system')) {
                    $name = $column->getData('id');
                    $label = $column->getData('header');

                    if (! $this->variables->isEmpty($name) && ! $this->variables->isEmpty($label)) {
                        $fieldList[ $name ] = $label;
                    }
                }
            }
        }

        return $fieldList;
    }

    /**
     * @throws LocalizedException
     */
    protected function getAddButtonBlock(Button $addButton): Add
    {
        /** @var Add $add */
        $add = $this->getLayout()->createBlock(Add::class);

        $add->setButtonId($addButton->getId());
        $add->setButtonUrl(
            $this->getUrl(
                $this->addUrlRoute,
                $this->addUrlParams
            )
        );
        $add->setGridId($this->getId());

        return $add;
    }

    /**
     * @throws Exception
     */
    protected function _prepareCollection(): Extended
    {
        if ($this->collectionClass) {
            if (! class_exists($this->collectionClass)) {
                throw new Exception(
                    sprintf(
                        'Could not find collection class: %s',
                        $this->collectionClass
                    )
                );
            }

            $collection = $this->universalFactory->create($this->collectionClass);
        } else {
            if (! $this->modelClass || ! class_exists($this->modelClass)) {
                throw new Exception(
                    sprintf(
                        'Could not find model class: %s',
                        $this->modelClass
                    )
                );
            }

            $model = $this->universalFactory->create($this->modelClass);

            if (! $model instanceof AbstractModel) {
                throw new Exception(
                    sprintf(
                        'Model class: %s does not implement class: %s',
                        $this->modelClass,
                        AbstractModel::class
                    )
                );
            }

            $collection = $model->getCollection();
        }

        if (! $collection instanceof AbstractDb) {
            throw new Exception(
                sprintf(
                    'Collection class: %s does not implement class: %s',
                    get_class($collection),
                    AbstractDb::class
                )
            );
        }

        $this->prepareCollection($collection);
        $this->followUpCollection($collection);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    abstract protected function prepareCollection(AbstractDb $collection): void;

    protected function followUpCollection(AbstractDb $collection): void
    {
    }

    /**
     * @param Collection $collection
     */
    public function setCollection($collection): void
    {
        if ($collection instanceof AbstractDb) {
            foreach ($this->joinAttributeValues as $valueColumnName => $attributeColumnName) {
                $optionTableAlias = sprintf(
                    'eao_%s',
                    $valueColumnName
                );
                $optionValueTableAlias = sprintf(
                    'eaov_%s',
                    $valueColumnName
                );
                $optionValueColumnName = sprintf(
                    '%s_value',
                    $valueColumnName
                );

                $collection->getSelect()->joinLeft(
                    [$optionTableAlias => $this->databaseHelper->getTableName('eav_attribute_option')],
                    sprintf(
                        '%s.attribute_id = main_table.%s and %s.option_id = main_table.%s',
                        $optionTableAlias,
                        $attributeColumnName,
                        $optionTableAlias,
                        $valueColumnName
                    ),
                    ''
                );

                $collection->getSelect()->joinLeft(
                    [$optionValueTableAlias => $this->databaseHelper->getTableName('eav_attribute_option_value')],
                    sprintf(
                        '%s.option_id = %s.option_id and %s.store_id = 0',
                        $optionValueTableAlias,
                        $optionTableAlias,
                        $optionValueTableAlias
                    ),
                    [
                        sprintf(
                            'IF(%s.value IS NULL, main_table.%s, %s.value) as %s',
                            $optionValueTableAlias,
                            $valueColumnName,
                            $optionValueTableAlias,
                            $optionValueColumnName
                        )
                    ]
                );
            }

            foreach ($this->joinAttributeMultiValues as $valueColumnName => $attributeColumnName) {
                $optionTableAlias = sprintf(
                    'eao_%s',
                    $valueColumnName
                );
                $optionValueTableAlias = sprintf(
                    'eaov_%s',
                    $valueColumnName
                );
                $optionValueColumnName = sprintf(
                    '%s_value',
                    $valueColumnName
                );

                $collection->getSelect()->joinLeft(
                    [$optionTableAlias => $this->databaseHelper->getTableName('eav_attribute_option')],
                    sprintf(
                        '%s.attribute_id = main_table.%s and FIND_IN_SET(%s.option_id, main_table.%s) > 0',
                        $optionTableAlias,
                        $attributeColumnName,
                        $optionTableAlias,
                        $valueColumnName
                    ),
                    ''
                );

                $collection->getSelect()->joinLeft(
                    [$optionValueTableAlias => $this->databaseHelper->getTableName('eav_attribute_option_value')],
                    sprintf(
                        '%s.option_id = %s.option_id and %s.store_id = 0',
                        $optionValueTableAlias,
                        $optionTableAlias,
                        $optionValueTableAlias
                    ),
                    [
                        sprintf(
                            'IF(%s.value IS NULL, main_table.%s, GROUP_CONCAT(%s.value)) as %s',
                            $optionValueTableAlias,
                            $valueColumnName,
                            $optionValueTableAlias,
                            $optionValueColumnName
                        )
                    ]
                );
            }
        }

        $this->addJoinValuesToCollection(
            $collection,
            $this->joinValues
        );

        parent::setCollection($collection);
    }

    protected function addJoinValuesToCollection(Collection $collection, array $joinValues): void
    {
        if ($collection instanceof AbstractDb && ! $this->variables->isEmpty($joinValues)) {
            foreach ($joinValues as $joinValue) {
                $tableName = $this->arrays->getValue(
                    $joinValue,
                    'table_name'
                );
                $joinFields = $this->arrays->getValue(
                    $joinValue,
                    'join_fields'
                );
                $resultFields = $this->arrays->getValue(
                    $joinValue,
                    'result_fields'
                );
                $tableAlias = $this->arrays->getValue(
                    $joinValue,
                    'table_alias'
                );
                $conditions = $this->arrays->getValue(
                    $joinValue,
                    'conditions'
                );
                $required = $this->arrays->getValue(
                    $joinValue,
                    'required',
                    true
                );

                if ($this->variables->isEmpty($tableAlias)) {
                    $tableAlias = $tableName;
                }

                $joinConditions = [];

                foreach ($joinFields as $mainTableFieldName => $joinTableFieldName) {
                    if (is_numeric($mainTableFieldName)) {
                        $joinConditions[] = $joinTableFieldName;
                    } else {
                        if (! str_contains(
                            $mainTableFieldName,
                            '.'
                        )) {
                            $mainTableFieldName = sprintf(
                                'main_table.%s',
                                $mainTableFieldName
                            );
                        }

                        if (! str_contains(
                            $joinTableFieldName,
                            '.'
                        )) {
                            $joinTableFieldName = sprintf(
                                '%s.%s',
                                $tableAlias,
                                $joinTableFieldName
                            );
                        }

                        $joinConditions[] = sprintf(
                            '%s = %s',
                            $mainTableFieldName,
                            $joinTableFieldName
                        );
                    }
                }

                if ($required) {
                    /** @noinspection PhpParamsInspection, RedundantSuppression, PhpPossiblePolymorphicInvocationInspection */
                    $collection->join([$tableAlias => $tableName],
                        implode(
                            ' AND ',
                            $joinConditions
                        ),
                        $resultFields);
                } else {
                    $collection->getSelect()->joinLeft([$tableAlias => $tableName],
                        implode(
                            ' AND ',
                            $joinConditions
                        ),
                        $resultFields);
                }

                if ($conditions && is_array($conditions)) {
                    foreach ($conditions as $condition) {
                        $collection->getSelect()->where($condition);
                    }
                }
            }
        }
    }

    public function addJoinValues(
        string $tableName,
        array $joinFields,
        array $resultFields = [],
        ?string $tableAlias = null,
        ?array $conditions = null,
        bool $required = true
    ): void {
        $this->joinValues[] = [
            'table_name'    => $tableName,
            'join_fields'   => $joinFields,
            'result_fields' => $resultFields,
            'table_alias'   => $tableAlias,
            'conditions'    => $conditions,
            'required'      => $required
        ];
    }

    /**
     * @throws LocalizedException
     */
    protected function addProductToCollection(
        AbstractDb $collection,
        array $eavAttributes = []
    ): void {
        $select = $collection->getSelect();

        $select->join(['product' => $this->databaseHelper->getTableName('catalog_product_entity')],
            'main_table.product_id = product.entity_id',
            ['attribute_set_id', 'type_id', 'sku']);

        foreach ($eavAttributes as $eavAttributeName) {
            $eavAttribute = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $eavAttributeName
            );

            $tableAlias = sprintf(
                'eav_attribute_%s',
                $eavAttributeName
            );

            $select->join([$tableAlias => $eavAttribute->getBackendTable()],
                sprintf(
                    'main_table.product_id = %s.entity_id AND %s.attribute_id = %d AND %s.store_id = 0',
                    $tableAlias,
                    $tableAlias,
                    $eavAttribute->getId(),
                    $tableAlias
                ),
                [
                    sprintf(
                        'product_%s',
                        $eavAttributeName
                    ) => 'value'
                ]);
        }
    }

    /**
     * @throws Exception
     */
    protected function _prepareColumns(): Extended
    {
        $this->prepareFields();
        $this->followUpFields();

        try {
            $hiddenFieldNames = $this->sessionHelper->getHiddenFieldList($this->getHtmlId());
        } catch (NotFoundException $exception) {
            $hiddenFieldNames = $this->getHiddenFieldNames();
        }

        foreach ($hiddenFieldNames as $hiddenFieldName) {
            $this->setColumnHidden($hiddenFieldName);
        }

        $this->addActionColumn();

        return parent::_prepareColumns();
    }

    protected function setColumnHidden(string $hiddenFieldName): void
    {
        /** @var Column $column */
        $column = $this->getColumnSet()->getChildBlock($hiddenFieldName);

        if ($column) {
            $headerCssClasses = $column->getData('header_css_class');

            if ($headerCssClasses) {
                $headerCssClasses .= ' hidden';
            } else {
                $headerCssClasses = 'hidden';
            }

            $column->setData(
                'header_css_class',
                $headerCssClasses
            );

            $rowCssClasses = $column->getData('column_css_class');

            if ($rowCssClasses) {
                $rowCssClasses .= ' hidden';
            } else {
                $rowCssClasses = 'hidden';
            }

            $column->setData(
                'column_css_class',
                $rowCssClasses
            );
        }
    }

    abstract protected function prepareFields(): void;

    protected function followUpFields(): void
    {
    }

    /**
     * @return string[]
     */
    abstract protected function getHiddenFieldNames(): array;

    /**
     * @throws Exception
     */
    protected function addTextColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addTextColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addTextColumnWithFilter(string $objectFieldName, string $label, string $filterIndex): void
    {
        $this->gridHelper->addTextColumnWithFilter(
            $this,
            $objectFieldName,
            $label,
            $filterIndex
        );
    }

    /**
     * @throws Exception
     */
    protected function addTextColumnWithFilterCondition(string $objectFieldName, string $label, $callback): void
    {
        $this->gridHelper->addTextColumnWithFilterCondition(
            $this,
            $objectFieldName,
            $label,
            $callback
        );
    }

    /**
     * @throws Exception
     */
    protected function addTextColumnWithRenderer(string $objectFieldName, string $label, $renderer): void
    {
        $this->gridHelper->addTextColumnWithRenderer(
            $this,
            $objectFieldName,
            $label,
            $renderer
        );
    }

    /**
     * @throws Exception
     */
    protected function addNumberColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addNumberColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addNumberColumnWithFilter(string $objectFieldName, string $label, string $filterIndex): void
    {
        $this->gridHelper->addNumberColumnWithFilter(
            $this,
            $objectFieldName,
            $label,
            $filterIndex
        );
    }

    /**
     * @throws Exception
     */
    protected function addNumberColumnWithFilterCondition(string $objectFieldName, string $label, $callback): void
    {
        $this->gridHelper->addNumberColumnWithFilterCondition(
            $this,
            $objectFieldName,
            $label,
            $callback
        );
    }

    /**
     * @throws Exception
     */
    protected function addPriceColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addPriceColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addOptionsColumn(string $objectFieldName, string $label, array $options): void
    {
        $this->gridHelper->addOptionsColumn(
            $this,
            $objectFieldName,
            $label,
            $options
        );
    }

    /**
     * @throws Exception
     */
    protected function addOptionsClassColumn(
        string $objectFieldName,
        string $label,
        string $className,
        $after = null
    ): void {
        $this->gridHelper->addOptionsClassColumn(
            $this,
            $objectFieldName,
            $label,
            $className,
            $after
        );
    }

    /**
     * @throws Exception
     */
    protected function addOptionsClassCallbackColumn(
        string $objectFieldName,
        string $label,
        string $className,
        string $methodName,
        array $parameters = [],
        $after = null
    ): void {
        $this->gridHelper->addOptionsClassCallbackColumn(
            $this,
            $objectFieldName,
            $label,
            $className,
            $methodName,
            $parameters,
            $after
        );
    }

    /**
     * @throws Exception
     */
    protected function addOptionsColumnWithFilter(
        string $objectFieldName,
        string $label,
        array $options,
        string $filterIndex
    ): void {
        $this->gridHelper->addOptionsColumnWithFilter(
            $this,
            $objectFieldName,
            $label,
            $options,
            $filterIndex
        );
    }

    /**
     * @throws Exception
     */
    protected function addOptionsColumnWithFilterCondition(
        string $objectFieldName,
        string $label,
        array $options,
        $callback
    ): void {
        $this->gridHelper->addOptionsColumnWithFilterCondition(
            $this,
            $objectFieldName,
            $label,
            $options,
            $callback
        );
    }

    /**
     * @throws Exception
     */
    protected function addOptionsColumnWithFilterConditionAndFrame(
        string $objectFieldName,
        string $label,
        array $options,
        $filterCallback,
        $frameCallback
    ): void {
        $this->gridHelper->addOptionsColumnWithFilterConditionAndFrame(
            $this,
            $objectFieldName,
            $label,
            $options,
            $filterCallback,
            $frameCallback
        );
    }

    /**
     * @throws Exception
     */
    protected function addOptionsColumnWithFrame(
        string $objectFieldName,
        string $label,
        array $options,
        $callback
    ): void {
        $this->gridHelper->addOptionsColumnWithFrame(
            $this,
            $objectFieldName,
            $label,
            $options,
            $callback
        );
    }

    /**
     * @throws Exception
     */
    protected function addDateColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addDateColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addDatetimeColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addDatetimeColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addYesNoColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addYesNoColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addYesNoColumnAfter(string $objectFieldName, string $label, string $after): void
    {
        $this->gridHelper->addYesNoColumnAfter(
            $this,
            $objectFieldName,
            $label,
            $after
        );
    }

    /**
     * @throws Exception
     */
    protected function addYesNoColumnWithFilterCondition(string $objectFieldName, string $label, $callback): void
    {
        $this->gridHelper->addYesNoColumnWithFilterCondition(
            $this,
            $objectFieldName,
            $label,
            $callback
        );
    }

    /**
     * @throws Exception
     */
    protected function addWebsiteNameColumn(string $objectFieldName, ?string $label = null): void
    {
        $this->gridHelper->addWebsiteNameColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addStoreColumn(string $objectFieldName, ?string $label = null): void
    {
        $this->gridHelper->addStoreColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addStoreStructureColumn(string $objectFieldName, ?string $label = null): void
    {
        $this->gridHelper->addStoreStructureColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addStoreWithAdminStructureColumn(string $objectFieldName, ?string $label = null): void
    {
        $this->gridHelper->addStoreWithAdminStructureColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addTypeIdColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addTypeIdColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    public function addCmsPageColumn(string $objectFieldName, ?string $label = null): void
    {
        $this->gridHelper->addCmsPageColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    public function addCmsBlockColumn(string $objectFieldName, ?string $label = null): void
    {
        $this->gridHelper->addCmsBlockColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addTemplateColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addTemplateColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    public function addCategoriesColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addCategoriesColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addDescriptionColumn(
        string $objectFieldName,
        string $label,
        string $width = '100%',
        string $height = '15px'
    ): void {
        $this->gridHelper->addDescriptionColumn(
            $this,
            $objectFieldName,
            $label,
            $width,
            $height
        );
    }

    /**
     * @throws Exception
     */
    protected function addOperatorColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addOperatorColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addCountryColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addCountryColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addRegionColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addRegionColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addRegionAnyColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addRegionAnyColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    public function addCustomerGroupColumn(string $objectFieldName, ?string $label = null): void
    {
        $this->gridHelper->addCustomerGroupColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    public function addCustomerGroupsColumn(string $objectFieldName, ?string $label = null): void
    {
        $this->gridHelper->addCustomerGroupsColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    public function addPaymentActiveMethods(
        string $objectFieldName,
        ?string $label = null,
        bool $allStores = false,
        bool $withDefault = true
    ): void {
        $this->gridHelper->addPaymentActiveMethods(
            $this,
            $objectFieldName,
            $label,
            $allStores,
            $withDefault
        );
    }

    public function filterStoreCondition(
        AbstractCollection $collection,
        Column $column
    ): void {
        $filter = $column->getFilter();

        $value = $filter->getDataUsingMethod('value');

        if ($this->variables->isEmpty($value)) {
            return;
        }

        if (method_exists(
            $collection,
            'addStoreFilter'
        )) {
            $collection->addStoreFilter($value);
        }
    }

    public function filterInSet(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        $value = $filter->getDataUsingMethod('value');

        if ($this->variables->isEmpty($value)) {
            return;
        }

        $collection->getSelect()->where(
            new Zend_Db_Expr(
                sprintf(
                    'FIND_IN_SET("%s", main_table.%s)',
                    $value,
                    $column->getData('index')
                )
            )
        );
    }

    public function addAction(
        string $actionId,
        string $label,
        string $urlPath,
        bool $confirm = false,
        array $urlParams = []
    ): void {
        $objectField = $this->getObjectField();

        if (empty($objectField)) {
            $objectField = 'id';
        }

        if ($confirm) {
            $this->actions[ $actionId ] = [
                'caption' => $label,
                'url'     => ['base' => $urlPath, 'params' => $urlParams],
                'field'   => $objectField,
                'confirm' => __('Are you sure?')
            ];
        } else {
            $this->actions[ $actionId ] = [
                'caption' => $label,
                'url'     => ['base' => $urlPath, 'params' => $urlParams],
                'field'   => $objectField
            ];
        }
    }

    /**
     * @throws Exception
     */
    protected function addActionColumn(): void
    {
        if ($this->addRowAction) {
            if ($this->allowEdit) {
                $this->addAction(
                    'edit',
                    __('Edit')->render(),
                    $this->editUrlRoute,
                    false,
                    $this->editUrlParams
                );
            } elseif ($this->allowView) {
                $this->addAction(
                    'view',
                    __('View')->render(),
                    $this->viewUrlRoute,
                    false,
                    $this->viewUrlParams
                );
            }
        }

        if ($this->allowDelete) {
            $this->addAction(
                'delete',
                __('Delete')->render(),
                $this->deleteUrlRoute,
                true,
                $this->deleteUrlParams
            );
        }

        if (! empty($this->actions)) {
            uasort(
                $this->actions,
                [$this, 'sortActions']
            );

            $objectField = $this->getObjectField();

            if (empty($objectField)) {
                $objectField = 'id';
            }

            $this->addColumn(
                'action',
                [
                    'header'    => __('Action'),
                    'type'      => 'action',
                    'getter'    => sprintf(
                        'get%s',
                        str_replace(
                            ' ',
                            '',
                            ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $objectField
                                )
                            )
                        )
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'index'     => $objectField,
                    'is_system' => true,
                    'actions'   => $this->actions
                ]
            );
        }
    }

    protected function sortActions(array $action1, array $action2): int
    {
        $label1 = array_key_exists(
            'caption',
            $action1
        ) ? $action1[ 'caption' ] : '';
        $label2 = array_key_exists(
            'caption',
            $action2
        ) ? $action2[ 'caption' ] : '';

        return strcasecmp(
            $label1,
            $label2
        );
    }

    public function getGridUrl(): string
    {
        $gridUrlParams = $this->gridUrlParams;

        $gridUrlParams[ '_current' ] = true;

        return $this->getUrl(
            $this->gridUrlRoute,
            $gridUrlParams
        );
    }

    /**
     * @param DataObject $item
     *
     * @return string|bool
     */
    public function getRowUrl($item)
    {
        $objectField = $this->getObjectField();

        if (empty($objectField)) {
            $objectField = 'id';
        }

        $editUrlParams = $this->editUrlParams;

        $editUrlParams[ $objectField ] = $item->getData($objectField);

        $viewUrlParams = $this->viewUrlParams;

        $viewUrlParams[ $objectField ] = $item->getData($objectField);

        return $this->allowEdit ? $this->getUrl(
            $this->editUrlRoute,
            $editUrlParams
        ) : ($this->allowView ? $this->getUrl(
            $this->viewUrlRoute,
            $viewUrlParams
        ) : false);
    }

    public function addMassAction(
        string $actionId,
        string $label,
        string $urlPath,
        bool $confirm = false,
        array $urlParams = []
    ): void {
        if ($confirm) {
            $this->massActions[ $actionId ] = [
                'label'   => $label,
                'url'     => $this->getUrl(
                    $urlPath,
                    $urlParams
                ),
                'confirm' => __('Are you sure?')
            ];
        } else {
            $this->massActions[ $actionId ] = [
                'label' => $label,
                'url'   => $this->getUrl(
                    $urlPath,
                    $urlParams
                )
            ];
        }
    }

    protected function _prepareMassaction(): Grid
    {
        if ($this->allowMassDelete) {
            $this->addMassAction(
                'delete',
                __('Delete')->render(),
                $this->massDeleteUrlRoute,
                true,
                $this->massDeleteUrlParams
            );
        }

        if ($this->allowExport) {
            $this->addMassAction(
                'export',
                __('Export')->render(),
                $this->massExportUrlRoute,
                false,
                $this->massExportUrlParams
            );
        }

        if (! empty($this->massActions)) {
            $objectField = $this->getObjectField();

            if (empty($objectField)) {
                $objectField = 'id';
            }

            $this->setMassactionIdField($objectField);

            /** @var AbstractMassaction $massActionBlock */
            $massActionBlock = $this->getMassactionBlock();

            $massActionBlock->setData(
                'form_field_name',
                $objectField
            );

            uasort(
                $this->massActions,
                [$this, 'sortMassActions']
            );

            foreach ($this->massActions as $itemId => $item) {
                $massActionBlock->addItem(
                    $itemId,
                    $item
                );
            }
        }

        return $this;
    }

    protected function _prepareMassactionColumn(): Grid
    {
        parent::_prepareMassactionColumn();

        /** @var Column $massActionColumn */
        $massActionColumn = $this->getColumnSet()->getChildBlock('massaction');

        if ($massActionColumn) {
            $massActionColumn->setData(
                'use_index',
                true
            );
        }

        return $this;
    }

    protected function sortMassActions(array $action1, array $action2): int
    {
        $label1 = array_key_exists(
            'label',
            $action1
        ) ? $action1[ 'label' ] : '';
        $label2 = array_key_exists(
            'label',
            $action2
        ) ? $action2[ 'label' ] : '';

        return strcasecmp(
            $label1,
            $label2
        );
    }

    protected function getObjectField(): ?string
    {
        return $this->objectField;
    }

    public function getMassActionOriginalObjectField(): ?string
    {
        return null;
    }

    protected function getObject(): AbstractModel
    {
        if ($this->object === null) {
            $this->object = $this->registryHelper->registry($this->objectRegistryKey);
        }

        return $this->object;
    }

    /**
     * @throws Exception
     */
    protected function addEavAttributeColumn(
        string $objectFieldName,
        string $label,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
        $this->gridHelper->addEavAttributeColumn(
            $this,
            $objectFieldName,
            $label,
            $customer,
            $address,
            $category,
            $product
        );
    }

    /**
     * @throws Exception
     */
    protected function addEavAttributeValueColumn(
        string $valueFieldName,
        string $attributeFieldName,
        string $label,
        bool $multiValue = false
    ): void {
        $this->gridHelper->addEavAttributeValueColumn(
            $this,
            $valueFieldName,
            $attributeFieldName,
            $label,
            $multiValue
        );
    }

    /**
     * @throws Exception
     */
    protected function addEavAttributeSetColumn(
        string $objectFieldName,
        string $label,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
        $this->gridHelper->addEavAttributeSetColumn(
            $this,
            $objectFieldName,
            $label,
            $customer,
            $address,
            $category,
            $product
        );
    }

    /**
     * @throws Exception
     */
    protected function addEavEntityTypeColumn(
        string $objectFieldName,
        string $label,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
        $this->gridHelper->addEavEntityTypeColumn(
            $this,
            $objectFieldName,
            $label,
            $customer,
            $address,
            $category,
            $product
        );
    }

    /**
     * @throws Exception
     */
    protected function addProductAttributeCodeColumn(
        string $objectFieldName,
        string $label
    ): void {
        $this->gridHelper->addProductAttributeCodeColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addCustomerAttributeCodeColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addCustomerAttributeCodeColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addAddressAttributeCodeColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addAddressAttributeCodeColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addAttributeSortByColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addAttributeSortByColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    /**
     * @throws Exception
     */
    protected function addCustomerNameColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addCustomerNameColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    public function filterCustomerName(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        $condition = $filter->getCondition();

        $objectFieldName = $column->getData('index');

        $columnTableAlias = sprintf(
            '%s_customer',
            $objectFieldName
        );

        $collection->addFieldToFilter(
            new Zend_Db_Expr(
                sprintf(
                    'CONCAT(%s.firstname, " ", %s.lastname)',
                    $columnTableAlias,
                    $columnTableAlias
                )
            ),
            $condition
        );
    }

    /**
     * @throws Exception
     */
    protected function addProductNameColumn(string $objectFieldName, string $label): void
    {
        $this->gridHelper->addProductNameColumn(
            $this,
            $objectFieldName,
            $label
        );
    }

    public function filterProductName(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        $condition = $filter->getCondition();

        $objectFieldName = $column->getData('index');

        $valueTableAlias = sprintf(
            '%s_name',
            $objectFieldName
        );

        $collection->addFieldToFilter(
            sprintf(
                '%s.value',
                $valueTableAlias
            ),
            $condition
        );
    }

    /**
     * @throws Exception
     */
    protected function addProductOptionColumn(
        string $valueFieldName,
        string $label
    ): void {
        $this->gridHelper->addProductOptionColumn(
            $this,
            $valueFieldName,
            $label
        );
    }

    public function filterProductOption(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        $condition = $filter->getCondition();

        $objectFieldName = $column->getData('index');

        $productOptionTitleAliasName = sprintf(
            '%s_cpot',
            $objectFieldName
        );

        $collection->addFieldToFilter(
            sprintf(
                '%s.title',
                $productOptionTitleAliasName
            ),
            $condition
        );
    }

    /**
     * @throws Exception
     */
    protected function addProductOptionValueColumn(
        string $valueFieldName,
        string $label
    ): void {
        $this->gridHelper->addProductOptionValueColumn(
            $this,
            $valueFieldName,
            $label
        );
    }

    public function filterProductOptionValue(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        $condition = $filter->getCondition();

        $objectFieldName = $column->getData('index');

        $productOptionTypeTitleAliasName = sprintf(
            '%s_cpott',
            $objectFieldName
        );

        $collection->addFieldToFilter(
            sprintf(
                '%s.title',
                $productOptionTypeTitleAliasName
            ),
            $condition
        );
    }

    public function addJoinAttributeValues(string $valueFieldName, string $attributeFieldName): void
    {
        $this->joinAttributeValues[ $valueFieldName ] = $attributeFieldName;
    }

    public function addJoinAttributeMultiValues(string $valueFieldName, string $attributeFieldName): void
    {
        $this->joinAttributeMultiValues[ $valueFieldName ] = $attributeFieldName;
    }

    public function filterEavAttributeOptionValue(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        $condition = $filter->getCondition();

        if (is_array($condition) && array_key_exists(
                'like',
                $condition
            )) {
            /** @var Zend_Db_Expr $expression */
            $expression = $condition[ 'like' ];

            $value = $expression->__toString();

            $optionValueColumnName = $column->getData('index');
            $valueColumnName = preg_replace(
                '/_value$/',
                '',
                $optionValueColumnName
            );
            $optionValueTableAlias = sprintf(
                'eaov_%s',
                $valueColumnName
            );

            $collection->getSelect()->where(
                sprintf(
                    'IF(%s.value IS NULL, main_table.%s, %s.value) like %s',
                    $optionValueTableAlias,
                    $valueColumnName,
                    $optionValueTableAlias,
                    $value
                )
            );
        }
    }

    public function getMainButtonsHtml(): string
    {
        $html = $this->getChildHtml('add_button');

        $html .= parent::getMainButtonsHtml();

        if ($this->getFilterVisibility()) {
            $html .= $this->getChildHtml('filters_button');
            $html .= $this->getChildHtml('columns_button');
        }

        return $html;
    }
}
