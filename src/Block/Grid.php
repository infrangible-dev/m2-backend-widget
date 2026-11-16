<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

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
use Magento\Framework\Phrase;
use Magento\Framework\Validator\UniversalFactory;
use RuntimeException;
use Zend_Db_Expr;

/**
 * Base class of the admin grids of objects.
 *
 * @noinspection PhpDeprecationInspection Magento's legacy grid, which this class extends, needs the deprecated column.
 *
 * @phpstan-type JoinValue array{
 *     table_name: string,
 *     join_fields: array<int|string, string>,
 *     result_fields: array<int|string, string>,
 *     table_alias: string|null,
 *     conditions: array<int, string>|null,
 *     required: bool
 * }
 */
abstract class Grid extends Extended
{
    use LayoutArguments;
    use ObjectUrlArguments;

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
    protected bool $allowMassDelete = false;

    /** @var bool */
    protected bool $allowExport = true;

    /** @var bool */
    protected bool $addRowAction = true;

    /** @var string */
    protected string $modelClass;

    /** @var string */
    protected string $collectionClass;

    /** @var string */
    protected string $gridUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $gridUrlParams;

    /** @var string */
    protected string $addUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $addUrlParams;

    /** @var bool */
    protected bool $showFiltersButton = true;

    /** @var bool */
    protected bool $showColumnsButton = true;

    /** @var bool */
    protected bool $isTab = false;

    /**
     * @var array
     * @phpstan-var array<string, array<string, mixed>>
     */
    private array $actions = [];

    /**
     * @var array
     * @phpstan-var array<string, array{label: string, url: string, confirm?: Phrase}>
     */
    private array $massActions = [];

    /** @var AbstractModel|null */
    private ?AbstractModel $object = null;

    /**
     * @var array
     * @phpstan-var array<int, JoinValue>
     */
    private array $joinValues = [];

    /**
     * @var array
     * @phpstan-var array<string, string>
     */
    private array $joinAttributeValues = [];

    /**
     * @var array
     * @phpstan-var array<string, string>
     */
    private array $joinAttributeMultiValues = [];

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param Database $databaseHelper
     * @param Arrays $arrays
     * @param Variables $variables
     * @param Registry $registryHelper
     * @param \Infrangible\BackendWidget\Helper\Grid $gridHelper
     * @param Session $sessionHelper
     * @param UniversalFactory $universalFactory
     * @param Config $eavConfig
     * @param array $data
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        protected Database $databaseHelper,
        protected Arrays $arrays,
        protected Variables $variables,
        protected Registry $registryHelper,
        protected \Infrangible\BackendWidget\Helper\Grid $gridHelper,
        protected Session $sessionHelper,
        protected UniversalFactory $universalFactory,
        protected Config $eavConfig,
        array $data = []
    ) {
        $this->moduleKey = $this->stringArgument($data, 'module_key', 'adminhtml');
        $this->objectName = $this->stringArgument($data, 'object_name', 'empty');
        $this->objectField = $this->stringArgument($data, 'object_field', 'id');
        $this->objectRegistryKey = $this->stringArgument($data, 'object_registry_key');
        $this->allowAdd = $this->boolArgument($data, 'allow_add');
        $this->allowEdit = $this->boolArgument($data, 'allow_edit', true);
        $this->allowView = $this->boolArgument($data, 'allow_view');
        $this->allowDelete = $this->boolArgument($data, 'allow_delete', true);
        $this->allowMassDelete = $this->boolArgument($data, 'allow_mass_delete');
        $this->allowExport = $this->boolArgument($data, 'allow_export');
        $this->addRowAction = $this->boolArgument($data, 'add_row_action', true);
        $this->modelClass = $this->stringArgument($data, 'model_class');
        $this->collectionClass = $this->stringArgument($data, 'collection_class');
        $this->gridUrlRoute = $this->stringArgument($data, 'grid_url_route', '*/*/grid');
        $this->gridUrlParams = $this->arrayArgument($data, 'grid_url_params');
        $this->addUrlRoute = $this->stringArgument($data, 'add_url_route', '*/*/add');
        $this->addUrlParams = $this->arrayArgument($data, 'add_url_params');
        $this->initObjectUrlArguments($data);
        $this->showFiltersButton = $this->boolArgument($data, 'show_filters_button', true);
        $this->showColumnsButton = $this->boolArgument($data, 'show_columns_button', true);
        $this->isTab = $this->boolArgument($data, 'is_tab', true);

        parent::__construct(
            $context,
            $backendHelper,
            $data
        );
    }

    /**
     * Constructor.
     *
     * @return void
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

    /**
     * Returns the grid id.
     *
     * @return string
     */
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
     * Prepares the filter buttons.
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareFilterButtons(): void
    {
        if ($this->allowAdd) {
            /** @var Button $addButton */
            $addButton = $this->getLayout()->createBlock(Button::class);

            $addButton->addData(
                [
                    'label'      => __('Add'),
                    'class'      => 'action-columns action-primary',
                    'after_html' => $this->getAddButtonBlock($addButton)->toHtml()
                ]
            );

            $this->setChild(
                'add_button',
                $addButton
            );
        }

        if ($this->showFiltersButton) {
            /** @var Button $filtersButton */
            $filtersButton = $this->getLayout()->createBlock(Button::class);

            $filtersButton->setData(
                [
                    'label' => __('Show filters'),
                    'class' => 'action-filters action-tertiary'
                ]
            );

            $this->setChild(
                'filters_button',
                $filtersButton
            );
        }

        if ($this->showColumnsButton) {
            /** @var Button $columnsButton */
            $columnsButton = $this->getLayout()->createBlock(Button::class);

            $columnsButton->setData(
                [
                    'label'      => __('Show columns'),
                    'class'      => 'action-columns action-tertiary',
                    'after_html' => $this->getFieldsBlock()->toHtml()
                ]
            );

            $this->setChild(
                'columns_button',
                $columnsButton
            );
        }

        parent::_prepareFilterButtons();

        if (! $this->showFiltersButton && ! $this->isTab) {
            $this->getLayout()->unsetChild(
                $this->getNameInLayout(),
                'reset_filter_button'
            );
            $this->getLayout()->unsetChild(
                $this->getNameInLayout(),
                'search_button'
            );
        }
    }

    /**
     * Returns the fields block.
     *
     * @return Fields
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
     * Returns the field list.
     *
     * @return array
     * @phpstan-return array<string, string>
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
                        $fieldList[ $this->variables->stringValue($name) ] = $this->variables->stringValue($label);
                    }
                }
            }
        }

        return $fieldList;
    }

    /**
     * Returns the add button block.
     *
     * @param Button $addButton
     *
     * @return Add
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
     * Prepares the collection.
     *
     * @return Extended
     * @throws LocalizedException
     */
    protected function _prepareCollection(): Extended
    {
        if ($this->collectionClass) {
            if (! class_exists($this->collectionClass)) {
                throw new \LogicException(
                    sprintf(
                        'Could not find collection class: %s',
                        $this->collectionClass
                    )
                );
            }

            /** @var object $collection */
            $collection = $this->universalFactory->create($this->collectionClass);
        } else {
            if (! $this->modelClass || ! class_exists($this->modelClass)) {
                throw new \LogicException(
                    sprintf(
                        'Could not find model class: %s',
                        $this->modelClass
                    )
                );
            }

            /** @var object $model */
            $model = $this->universalFactory->create($this->modelClass);

            if (! $model instanceof AbstractModel) {
                throw new \LogicException(
                    sprintf(
                        'Model class: %s does not implement class: %s',
                        $this->modelClass,
                        AbstractModel::class
                    )
                );
            }

            $collection = $model->getCollection();
        }

        if (! $collection instanceof Collection) {
            throw new \LogicException(
                sprintf(
                    'Collection class: %s does not extend class: %s',
                    $collection::class,
                    Collection::class
                )
            );
        }

        if ($collection instanceof AbstractDb) {
            $this->prepareCollection($collection);
            $this->followUpCollection($collection);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepares the collection.
     *
     * @param AbstractDb $collection
     *
     * @return void
     */
    abstract protected function prepareCollection(AbstractDb $collection): void;

    /**
     * Hook to change the collection after it was prepared.
     *
     * @param AbstractDb $collection
     *
     * @return void
     */
    protected function followUpCollection(AbstractDb $collection): void // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    {
    }

    /**
     * Sets the collection.
     *
     * @param Collection $collection
     *
     * @return void
     */
    public function setCollection(mixed $collection): void
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

    /**
     * Adds the join values to collection.
     *
     * @param Collection $collection
     * @param array $joinValues
     *
     * @phpstan-param array<int, JoinValue> $joinValues
     *
     * @return void
     */
    protected function addJoinValuesToCollection(Collection $collection, array $joinValues): void
    {
        if ($collection instanceof AbstractDb && ! $this->variables->isEmpty($joinValues)) {
            foreach ($joinValues as $joinValue) {
                $tableName = $joinValue[ 'table_name' ];
                $joinFields = $joinValue[ 'join_fields' ];
                $resultFields = $joinValue[ 'result_fields' ];
                $tableAlias = $joinValue[ 'table_alias' ];
                $conditions = $joinValue[ 'conditions' ];
                $required = $joinValue[ 'required' ];

                if ($tableAlias === null || $tableAlias === '') {
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

                if ($required && $collection instanceof AbstractCollection) {
                    $collection->join(
                        [$tableAlias => $tableName],
                        implode(
                            ' AND ',
                            $joinConditions
                        ),
                        $resultFields
                    );
                } elseif ($required) {
                    $collection->getSelect()->join(
                        [$tableAlias => $tableName],
                        implode(
                            ' AND ',
                            $joinConditions
                        ),
                        $resultFields
                    );
                } else {
                    $collection->getSelect()->joinLeft(
                        [$tableAlias => $tableName],
                        implode(
                            ' AND ',
                            $joinConditions
                        ),
                        $resultFields
                    );
                }

                if ($conditions !== null) {
                    foreach ($conditions as $condition) {
                        $collection->getSelect()->where($condition);
                    }
                }
            }
        }
    }

    /**
     * Adds the join values.
     *
     * @param string $tableName
     * @param array $joinFields
     * @param array $resultFields
     * @param string|null $tableAlias
     * @param array|null $conditions
     * @param bool $required
     *
     * @phpstan-param array<int|string, string> $joinFields
     * @phpstan-param array<int|string, string> $resultFields
     * @phpstan-param array<int, string>|null $conditions
     *
     * @return void
     */
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
     * Adds the product to collection.
     *
     * @param AbstractDb $collection
     * @param array $eavAttributes
     *
     * @phpstan-param array<int, string> $eavAttributes
     *
     * @return void
     * @throws LocalizedException
     */
    protected function addProductToCollection(
        AbstractDb $collection,
        array $eavAttributes = []
    ): void {
        $select = $collection->getSelect();

        $select->join(
            ['product' => $this->databaseHelper->getTableName('catalog_product_entity')],
            'main_table.product_id = product.entity_id',
            ['attribute_set_id', 'type_id', 'sku']
        );

        foreach ($eavAttributes as $eavAttributeName) {
            $eavAttribute = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $eavAttributeName
            );

            $tableAlias = sprintf(
                'eav_attribute_%s',
                $eavAttributeName
            );

            $select->join(
                [$tableAlias => $eavAttribute->getBackendTable()],
                sprintf(
                    'main_table.product_id = %s.entity_id AND %s.attribute_id = %d AND %s.store_id = 0',
                    $tableAlias,
                    $tableAlias,
                    $this->variables->intValue($eavAttribute->getId()),
                    $tableAlias
                ),
                [
                    sprintf(
                        'product_%s',
                        $eavAttributeName
                    ) => 'value'
                ]
            );
        }
    }

    /**
     * Prepares the columns.
     *
     * @return Extended
     * @throws Exception
     */
    protected function _prepareColumns(): Extended
    {
        $this->prepareFields();
        $this->followUpFields();

        try {
            $hiddenFieldNames = $this->sessionHelper->getHiddenFieldList($this->getHtmlId());
        } catch (NotFoundException) {
            $hiddenFieldNames = $this->getHiddenFieldNames();
        }

        foreach ($hiddenFieldNames as $hiddenFieldName) {
            $this->setColumnHidden($hiddenFieldName);
        }

        $this->addActionColumn();

        return parent::_prepareColumns();
    }

    /**
     * Sets the column hidden.
     *
     * @param string $hiddenFieldName
     *
     * @return void
     */
    protected function setColumnHidden(string $hiddenFieldName): void
    {
        $column = $this->getColumnSet()->getChildBlock($hiddenFieldName);

        if ($column instanceof Column) {
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

    /**
     * Prepares the fields.
     *
     * @return void
     */
    abstract protected function prepareFields(): void;

    /**
     * Hook to add or change fields after the default fields were added.
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- hook
    protected function followUpFields(): void
    {
    }

    /**
     * Returns the hidden field names.
     *
     * @return string[]
     */
    abstract protected function getHiddenFieldNames(): array;

    /**
     * Adds the text column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the text column with filter.
     *
     * @param string $objectFieldName
     * @param string $label
     * @param string $filterIndex
     *
     * @return void
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
     * Adds the text column with filter condition.
     *
     * @param string $objectFieldName
     * @param string $label
     * @param array $callback
     *
     * @phpstan-param array{object, string} $callback
     *
     * @return void
     * @throws Exception
     */
    protected function addTextColumnWithFilterCondition(string $objectFieldName, string $label, array $callback): void
    {
        $this->gridHelper->addTextColumnWithFilterCondition(
            $this,
            $objectFieldName,
            $label,
            $callback
        );
    }

    /**
     * Adds the text column with renderer.
     *
     * @param string $objectFieldName
     * @param string $label
     * @param string $renderer
     *
     * @return void
     * @throws Exception
     */
    protected function addTextColumnWithRenderer(string $objectFieldName, string $label, string $renderer): void
    {
        $this->gridHelper->addTextColumnWithRenderer(
            $this,
            $objectFieldName,
            $label,
            $renderer
        );
    }

    /**
     * Adds the number column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the number column with filter.
     *
     * @param string $objectFieldName
     * @param string $label
     * @param string $filterIndex
     *
     * @return void
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
     * Adds the number column with filter condition.
     *
     * @param string $objectFieldName
     * @param string $label
     * @param array $callback
     *
     * @phpstan-param array{object, string} $callback
     *
     * @return void
     * @throws Exception
     */
    protected function addNumberColumnWithFilterCondition(string $objectFieldName, string $label, array $callback): void
    {
        $this->gridHelper->addNumberColumnWithFilterCondition(
            $this,
            $objectFieldName,
            $label,
            $callback
        );
    }

    /**
     * Adds the price column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the options column.
     *
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     *
     * @phpstan-param array<int|string, mixed> $options
     *
     * @return void
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
     * Adds the options class column.
     *
     * @param string $objectFieldName
     * @param string $label
     * @param string $className
     * @param string|null $after
     *
     * @return void
     * @throws Exception
     */
    protected function addOptionsClassColumn(
        string $objectFieldName,
        string $label,
        string $className,
        ?string $after = null
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
     * Adds the options class callback column.
     *
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
    protected function addOptionsClassCallbackColumn(
        string $objectFieldName,
        string $label,
        string $className,
        string $methodName,
        array $parameters = [],
        ?string $after = null
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
     * Adds the options column with filter.
     *
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
     * Adds the options column with filter condition.
     *
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
    protected function addOptionsColumnWithFilterCondition(
        string $objectFieldName,
        string $label,
        array $options,
        array $callback
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
     * Adds the options column with filter condition and frame.
     *
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
    protected function addOptionsColumnWithFilterConditionAndFrame(
        string $objectFieldName,
        string $label,
        array $options,
        array $filterCallback,
        array $frameCallback
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
     * Adds the options column with frame.
     *
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
    protected function addOptionsColumnWithFrame(
        string $objectFieldName,
        string $label,
        array $options,
        array $callback
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
     * Adds the date column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the datetime column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the yes no column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the yes no column after.
     *
     * @param string $objectFieldName
     * @param string $label
     * @param string $after
     *
     * @return void
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
     * Adds the yes no column with filter condition.
     *
     * @param string $objectFieldName
     * @param string $label
     * @param array $callback
     *
     * @phpstan-param array{object, string} $callback
     *
     * @return void
     * @throws Exception
     */
    protected function addYesNoColumnWithFilterCondition(string $objectFieldName, string $label, array $callback): void
    {
        $this->gridHelper->addYesNoColumnWithFilterCondition(
            $this,
            $objectFieldName,
            $label,
            $callback
        );
    }

    /**
     * Adds the website name column.
     *
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
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
     * Adds the store column.
     *
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
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
     * Adds the store structure column.
     *
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
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
     * Adds the store with admin structure column.
     *
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
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
     * Adds the type id column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the cms page column.
     *
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
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
     * Adds the cms block column.
     *
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
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
     * Adds the template column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the categories column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the description column.
     *
     * @param string $objectFieldName
     * @param string $label
     * @param string $width
     * @param string $height
     *
     * @return void
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
     * Adds the operator column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the country column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the region column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the region any column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the customer group column.
     *
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
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
     * Adds the customer groups column.
     *
     * @param string $objectFieldName
     * @param string|null $label
     *
     * @return void
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
     * Adds the payment active methods.
     *
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $allStores
     * @param bool $withDefault
     *
     * @return void
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

    /**
     * Filters the store condition.
     *
     * @param AbstractCollection $collection
     * @param Column $column
     *
     * @return void
     */
    public function filterStoreCondition(
        AbstractCollection $collection,
        Column $column
    ): void {
        $filter = $column->getFilter();

        if ($filter === false) {
            return;
        }

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

    /**
     * Filters the in set.
     *
     * @param AbstractCollection $collection
     * @param Column $column
     *
     * @return void
     */
    public function filterInSet(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        if ($filter === false) {
            return;
        }

        $value = $filter->getDataUsingMethod('value');

        if ($this->variables->isEmpty($value)) {
            return;
        }

        $connection = $collection->getConnection();

        $collection->getSelect()->where(
            sprintf(
                'FIND_IN_SET(?, %s)',
                $connection->quoteIdentifier(
                    sprintf(
                        'main_table.%s',
                        $this->variables->stringValue($column->getData('index'))
                    )
                )
            ),
            $this->variables->stringValue($value)
        );
    }

    /**
     * Adds the action.
     *
     * @param string $actionId
     * @param string $label
     * @param string $urlPath
     * @param bool $confirm
     * @param array $urlParams
     *
     * @phpstan-param array<int|string, mixed> $urlParams
     *
     * @return void
     */
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
     * Adds the action column.
     *
     * @return void
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
                $this->sortActions(...)
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

    /**
     * Sorts the actions.
     *
     * @param array $action1
     * @param array $action2
     *
     * @phpstan-param array<int|string, mixed> $action1
     * @phpstan-param array<int|string, mixed> $action2
     *
     * @return int
     */
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

    /**
     * Returns the grid url.
     *
     * @return string
     */
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
     * Returns the row url.
     *
     * @param DataObject $item
     *
     * @return string|bool
     */
    public function getRowUrl(mixed $item): string|bool
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

    /**
     * Adds the mass action.
     *
     * @param string $actionId
     * @param string $label
     * @param string $urlPath
     * @param bool $confirm
     * @param array $urlParams
     *
     * @phpstan-param array<int|string, mixed> $urlParams
     *
     * @return void
     */
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

    /**
     * Prepares the mass actions.
     *
     * @return Grid
     */
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
                $this->sortMassActions(...)
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

    /**
     * Prepares the massaction column.
     *
     * @return Grid
     */
    protected function _prepareMassactionColumn(): Grid
    {
        parent::_prepareMassactionColumn();

        $massActionColumn = $this->getColumnSet()->getChildBlock('massaction');

        if ($massActionColumn instanceof Column) {
            $massActionColumn->setData(
                'use_index',
                true
            );
        }

        return $this;
    }

    /**
     * Sorts the mass actions.
     *
     * @param array $action1
     * @param array $action2
     *
     * @phpstan-param array<int|string, mixed> $action1
     * @phpstan-param array<int|string, mixed> $action2
     *
     * @return int
     */
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

    /**
     * Returns the object field.
     *
     * @return string|null
     */
    protected function getObjectField(): ?string
    {
        return $this->objectField;
    }

    /**
     * Returns the mass action original object field.
     *
     * @return string|null
     */
    public function getMassActionOriginalObjectField(): ?string
    {
        return null;
    }

    /**
     * Returns the object.
     *
     * @return AbstractModel
     * @throws RuntimeException
     */
    protected function getObject(): AbstractModel
    {
        if ($this->object === null) {
            $object = $this->registryHelper->registry($this->objectRegistryKey);

            if (! $object instanceof AbstractModel) {
                throw new \RuntimeException(
                    sprintf(
                        'No object registered with key: %s',
                        $this->objectRegistryKey
                    )
                );
            }

            $this->object = $object;
        }

        return $this->object;
    }

    /**
     * Adds the eav attribute column.
     *
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
     * Adds the eav attribute value column.
     *
     * @param string $valueFieldName
     * @param string $attributeFieldName
     * @param string $label
     * @param bool $multiValue
     *
     * @return void
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
     * Adds the eav attribute set column.
     *
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
     * Adds the eav entity type column.
     *
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
     * Adds the product attribute code column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the customer attribute code column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the address attribute code column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the attribute sort by column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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
     * Adds the customer name column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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

    /**
     * Filters the customer name.
     *
     * @param AbstractCollection $collection
     * @param Column $column
     *
     * @return void
     * @throws LocalizedException
     */
    public function filterCustomerName(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        if ($filter === false) {
            return;
        }

        $condition = $filter->getCondition();

        $objectFieldName = $this->variables->stringValue($column->getData('index'));

        $columnTableAlias = sprintf(
            '%s_customer',
            $objectFieldName
        );

        $collection->addFieldToFilter(
            // @phpstan-ignore argument.type (Magento uses expressions as field without quoting them)
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
     * Adds the product name column.
     *
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
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

    /**
     * Filters the product name.
     *
     * @param AbstractCollection $collection
     * @param Column $column
     *
     * @return void
     * @throws LocalizedException
     */
    public function filterProductName(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        if ($filter === false) {
            return;
        }

        $condition = $filter->getCondition();

        $objectFieldName = $this->variables->stringValue($column->getData('index'));

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
     * Adds the product option column.
     *
     * @param string $valueFieldName
     * @param string $label
     *
     * @return void
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

    /**
     * Filters the product option.
     *
     * @param AbstractCollection $collection
     * @param Column $column
     *
     * @return void
     * @throws LocalizedException
     */
    public function filterProductOption(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        if ($filter === false) {
            return;
        }

        $condition = $filter->getCondition();

        $objectFieldName = $this->variables->stringValue($column->getData('index'));

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
     * Adds the product option value column.
     *
     * @param string $valueFieldName
     * @param string $label
     *
     * @return void
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

    /**
     * Filters the product option value.
     *
     * @param AbstractCollection $collection
     * @param Column $column
     *
     * @return void
     * @throws LocalizedException
     */
    public function filterProductOptionValue(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        if ($filter === false) {
            return;
        }

        $condition = $filter->getCondition();

        $objectFieldName = $this->variables->stringValue($column->getData('index'));

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

    /**
     * Adds the join attribute values.
     *
     * @param string $valueFieldName
     * @param string $attributeFieldName
     *
     * @return void
     */
    public function addJoinAttributeValues(string $valueFieldName, string $attributeFieldName): void
    {
        $this->joinAttributeValues[ $valueFieldName ] = $attributeFieldName;
    }

    /**
     * Adds the join attribute multi values.
     *
     * @param string $valueFieldName
     * @param string $attributeFieldName
     *
     * @return void
     */
    public function addJoinAttributeMultiValues(string $valueFieldName, string $attributeFieldName): void
    {
        $this->joinAttributeMultiValues[ $valueFieldName ] = $attributeFieldName;
    }

    /**
     * Filters the eav attribute option value.
     *
     * @param AbstractCollection $collection
     * @param Column $column
     *
     * @return void
     */
    public function filterEavAttributeOptionValue(AbstractCollection $collection, Column $column): void
    {
        $filter = $column->getFilter();

        if ($filter === false) {
            return;
        }

        $condition = $filter->getCondition();

        if (is_array($condition) && array_key_exists(
            'like',
            $condition
        )) {
            /** @var Zend_Db_Expr $expression */
            $expression = $condition[ 'like' ];

            $value = $expression->__toString();

            $optionValueColumnName = $this->variables->stringValue($column->getData('index'));
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

    /**
     * Returns the main buttons html.
     *
     * @return string
     */
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
