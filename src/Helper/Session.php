<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Helper;

use FeWeDev\Base\Arrays;
use Magento\Framework\Exception\NotFoundException;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Session
{
    /** @var Arrays */
    protected $arrays;

    /** @var \Magento\Backend\Model\Session */
    protected $backendSession;

    /** @var \Magento\Backend\Model\Auth\Session */
    protected $authSession;

    /**
     * @param Arrays                              $arrays
     * @param \Magento\Backend\Model\Session      $backendSession
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        Arrays $arrays,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        $this->arrays = $arrays;

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

        return is_array($hiddenFieldLists) ? $this->arrays->getValue($hiddenFieldLists, $dataGridId, []) : [];
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

        $hiddenFieldLists[$dataGridId] = $hiddenFieldList;

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
