<?php /** @noinspection PhpDeprecationInspection */

namespace Infrangible\BackendWidget\Block\Grid;

use Exception;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Infrangible\BackendWidget\Block\Grid;
use Zend_Db_Expr;
use Zend_Db_Select;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class GroupBy
    extends Grid
{
    /**
     * @param AbstractDb $collection
     */
    protected function followUpCollection(AbstractDb $collection)
    {
        $groupBy = $this->getParam('group_by');

        if ( ! $this->variableHelper->isEmpty($groupBy)) {
            $groupBy = base64_decode($groupBy);

            $select = $collection->getSelect();

            $groupByColumns = explode(',', $groupBy);

            $columns = [];

            foreach ($groupByColumns as $groupByColumn) {
                if (preg_match('/(.*)gridcolumn$/', $groupByColumn, $matches)) {
                    $groupByColumn = $matches[ 1 ];
                }

                $columns[] = $groupByColumn;
            }

            $select->reset(Zend_Db_Select::COLUMNS);
            $select->columns($columns);
            $select->columns([new Zend_Db_Expr('COUNT(*) AS row_count')]);
            $select->group($columns);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function followUpFields()
    {
        parent::followUpFields();

        $groupBy = $this->getParam('group_by');

        if ( ! $this->variableHelper->isEmpty($groupBy)) {
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
     * @return Fields
     * @throws LocalizedException
     */
    protected function getFieldsBlock(): Fields
    {
        $fields = parent::getFieldsBlock();

        $fields->setGroupByFieldList($this->getGroupByFieldList());

        $groupBy = $this->getParam('group_by');

        if ( ! $this->variableHelper->isEmpty($groupBy)) {
            $groupBy = base64_decode($groupBy);

            $fieldNames = explode(',', $groupBy);

            $fields->setActiveGroupByFieldList($fieldNames);
        }

        return $fields;
    }

    /**
     * @return array
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
     * @return array
     */
    abstract public function getNotGroupableFieldNames(): array;
}
