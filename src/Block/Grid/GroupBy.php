<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid;

use Exception;
use Infrangible\BackendWidget\Block\Grid;
use Magento\Framework\DB\Select;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Zend_Db_Expr;

abstract class GroupBy extends Grid
{
    /**
     * Hook to change the collection after it was prepared.
     *
     * @param AbstractDb $collection
     *
     * @return void
     */
    protected function followUpCollection(AbstractDb $collection): void
    {
        $columns = [];

        foreach ($this->getGroupByFieldNames() as $groupByColumn) {
            if (preg_match('/(.*)gridcolumn$/', $groupByColumn, $matches)) {
                $groupByColumn = $matches[ 1 ];
            }

            $columns[] = $groupByColumn;
        }

        if (count($columns) > 0) {
            $select = $collection->getSelect();

            $select->reset(Select::COLUMNS);
            $select->columns($columns);
            $select->columns([new Zend_Db_Expr('COUNT(*) AS row_count')]);
            $select->group($columns);
        }
    }

    /**
     * Hook to add or change fields after the default fields were added.
     *
     * @return void
     * @throws Exception
     */
    protected function followUpFields(): void
    {
        parent::followUpFields();

        if (count($this->getGroupByFieldNames()) > 0) {
            $this->addColumn('row_count', [
                'header'           => __('Count'),
                'index'            => 'row_count',
                'type'             => 'number',
                'column_css_class' => 'data-grid-td',
                'filter'           => false
            ]);

            foreach ($this->getNotGroupableFieldNames() as $fieldName) {
                $this->removeColumn($fieldName);
            }
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
        $fields = parent::getFieldsBlock();

        $fields->setGroupByFieldList($this->getGroupByFieldList());

        $fieldNames = $this->getGroupByFieldNames();

        if (count($fieldNames) > 0) {
            $fields->setActiveGroupByFieldList($fieldNames);
        }

        return $fields;
    }

    /**
     * Returns the names of the fields to group by.
     *
     * The names are sent base64-encoded and comma-separated in the parameter group_by. Only plain column names are
     * accepted, because they are added to the query.
     *
     * @return array
     * @phpstan-return array<int, string>
     */
    protected function getGroupByFieldNames(): array
    {
        $groupBy = $this->getParam('group_by');

        if (! is_string($groupBy) || $groupBy === '') {
            return [];
        }

        $fieldNames = [];

        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- the grid encodes the field names with base64
        foreach (explode(',', (string)base64_decode($groupBy)) as $fieldName) {
            $fieldName = trim($fieldName);

            if (preg_match('/^\w+$/', $fieldName)) {
                $fieldNames[] = $fieldName;
            }
        }

        return $fieldNames;
    }

    /**
     * Returns the group by field list.
     *
     * @return array
     * @phpstan-return array<string, string>
     * @throws LocalizedException
     */
    protected function getGroupByFieldList(): array
    {
        $notGroupableFieldNames = array_flip($this->getNotGroupableFieldNames());

        $fieldList = $this->getFieldList();

        foreach ($fieldList as $name => $label) {
            if (array_key_exists($name, $notGroupableFieldNames) || $name === 'row_count') {
                unset($fieldList[ $name ]);
            }
        }

        return $fieldList;
    }

    /**
     * Returns the not groupable field names.
     *
     * @return array
     * @phpstan-return array<int, string>
     */
    abstract public function getNotGroupableFieldNames(): array;
}
