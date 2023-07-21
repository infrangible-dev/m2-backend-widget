<?php

namespace Infrangible\BackendWidget\Block\Grid;

use Magento\Backend\Block\Template;
use Magento\Framework\App\Request\Http;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Fields
    extends Template
{
    /** @var string */
    private $dataGridId;

    /** @var string */
    private $jsObjectName;

    /** @var string[] */
    private $fieldList = [];

    /** @var string[] */
    private $groupByFieldList = [];

    /** @var string[] */
    private $activeGroupByFieldList = [];

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('Infrangible_BackendWidget::grid/fields.phtml');

        parent::_construct();
    }

    /**
     * @return string
     */
    public function getDataGridId(): string
    {
        return $this->dataGridId;
    }

    /**
     * @param string $dataGridId
     */
    public function setDataGridId(string $dataGridId): void
    {
        $this->dataGridId = $dataGridId;
    }

    /**
     * @return string
     */
    public function getJsObjectName(): string
    {
        return $this->jsObjectName;
    }

    /**
     * @param string $jsObjectName
     */
    public function setJsObjectName(string $jsObjectName): void
    {
        $this->jsObjectName = $jsObjectName;
    }

    /**
     * @return string[]
     */
    public function getFieldList(): array
    {
        return $this->fieldList;
    }

    /**
     * @param string[] $fieldList
     */
    public function setFieldList(array $fieldList): void
    {
        $this->fieldList = $fieldList;
    }

    /**
     * @return string[]
     */
    public function getGroupByFieldList(): array
    {
        return $this->groupByFieldList;
    }

    /**
     * @param string[] $groupByFieldList
     */
    public function setGroupByFieldList(array $groupByFieldList): void
    {
        $this->groupByFieldList = $groupByFieldList;
    }

    /**
     * @return string[]
     */
    public function getActiveGroupByFieldList(): array
    {
        return $this->activeGroupByFieldList;
    }

    /**
     * @param string[] $activeGroupByFieldList
     */
    public function setActiveGroupByFieldList(array $activeGroupByFieldList): void
    {
        $this->activeGroupByFieldList = $activeGroupByFieldList;
    }

    /**
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('infrangible_backendwidget/grid/fields');
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        /** @var Http $request */
        $request = $this->getRequest();

        return $request->isAjax();
    }

    /**
     * @return string
     */
    protected function _toHtml(): string
    {
        return ! $this->isAjax() ? parent::_toHtml() : '';
    }
}
