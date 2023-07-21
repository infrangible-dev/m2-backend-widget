<?php

namespace Infrangible\BackendWidget\Helper;

use Magento\Framework\Exception\NotFoundException;
use Tofex\Help\Arrays;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Session
{
    /** @var Arrays */
    protected $arrayHelper;

    /** @var \Magento\Backend\Model\Session */
    protected $backendSession;

    /** @var \Magento\Backend\Model\Auth\Session */
    protected $authSession;

    /**
     * @param Arrays                              $arrayHelper
     * @param \Magento\Backend\Model\Session      $backendSession
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        Arrays $arrayHelper,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Backend\Model\Auth\Session $authSession)
    {
        $this->arrayHelper = $arrayHelper;

        $this->backendSession = $backendSession;
        $this->authSession = $authSession;
    }

    /**
     * @param string $dataGridId
     *
     * @return array
     * @throws NotFoundException
     */
    public function getHiddenFieldList(string $dataGridId): array
    {
        $hiddenFieldLists = $this->backendSession->getData('data_grid_hidden_field_lists');

        if ($hiddenFieldLists === null) {
            throw new NotFoundException(__('No data for grid with id: %1', $dataGridId));
        }

        return is_array($hiddenFieldLists) ? $this->arrayHelper->getValue($hiddenFieldLists, $dataGridId, []) : [];
    }

    /**
     * @param string $dataGridId
     * @param array  $hiddenFieldList
     */
    public function saveHiddenFieldList(string $dataGridId, array $hiddenFieldList): void
    {
        $hiddenFieldLists = $this->backendSession->getData('data_grid_hidden_field_lists');

        if ($hiddenFieldLists === null) {
            $hiddenFieldLists = [];
        }

        $hiddenFieldLists[ $dataGridId ] = $hiddenFieldList;

        /** @noinspection PhpUndefinedMethodInspection */
        $this->backendSession->setDataGridHiddenFieldLists($hiddenFieldLists);
    }

    /**
     * @return void
     */
    public function resetHiddenFieldList(): void
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->backendSession->unsDataGridHiddenFieldLists();
    }
}
