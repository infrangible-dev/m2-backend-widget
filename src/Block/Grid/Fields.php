<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid;

use Magento\Backend\Block\Template;
use Magento\Framework\App\Request\Http;

class Fields extends Template
{
    /** @var string */
    private $dataGridId;

    /** @var string */
    private $jsObjectName;

    /** @var string[] */
    private array $fieldList = [];

    /** @var string[] */
    private array $groupByFieldList = [];

    /** @var string[] */
    private array $activeGroupByFieldList = [];

    /**
     * Constructor.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->setTemplate('Infrangible_BackendWidget::grid/fields.phtml');

        parent::_construct();
    }

    /**
     * Returns the data grid id.
     *
     * @return string
     */
    public function getDataGridId(): string
    {
        return $this->dataGridId;
    }

    /**
     * Sets the data grid id.
     *
     * @param string $dataGridId
     *
     * @return void
     */
    public function setDataGridId(string $dataGridId): void
    {
        $this->dataGridId = $dataGridId;
    }

    /**
     * Returns the JavaScript object name.
     *
     * @return string
     */
    public function getJsObjectName(): string
    {
        return $this->jsObjectName;
    }

    /**
     * Sets the JavaScript object name.
     *
     * @param string $jsObjectName
     *
     * @return void
     */
    public function setJsObjectName(string $jsObjectName): void
    {
        $this->jsObjectName = $jsObjectName;
    }

    /**
     * Returns the field list.
     *
     * @return string[]
     */
    public function getFieldList(): array
    {
        return $this->fieldList;
    }

    /**
     * Sets the field list.
     *
     * @param string[] $fieldList
     *
     * @return void
     */
    public function setFieldList(array $fieldList): void
    {
        $this->fieldList = $fieldList;
    }

    /**
     * Returns the group by field list.
     *
     * @return string[]
     */
    public function getGroupByFieldList(): array
    {
        return $this->groupByFieldList;
    }

    /**
     * Sets the group by field list.
     *
     * @param string[] $groupByFieldList
     *
     * @return void
     */
    public function setGroupByFieldList(array $groupByFieldList): void
    {
        $this->groupByFieldList = $groupByFieldList;
    }

    /**
     * Returns the active group by field list.
     *
     * @return string[]
     */
    public function getActiveGroupByFieldList(): array
    {
        return $this->activeGroupByFieldList;
    }

    /**
     * Sets the active group by field list.
     *
     * @param string[] $activeGroupByFieldList
     *
     * @return void
     */
    public function setActiveGroupByFieldList(array $activeGroupByFieldList): void
    {
        $this->activeGroupByFieldList = $activeGroupByFieldList;
    }

    /**
     * Returns the ajax url.
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('infrangible_backendwidget/grid/fields');
    }

    /**
     * Checks whether it is ajax.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        /** @var Http $request */
        $request = $this->getRequest();

        return $request->isAjax();
    }

    /**
     * Renders the block as HTML.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        return ! $this->isAjax() ? parent::_toHtml() : '';
    }
}
