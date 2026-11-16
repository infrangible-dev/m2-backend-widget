<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Helper;

use FeWeDev\Base\Arrays;
use Magento\Framework\Exception\NotFoundException;

class Session
{
    /**
     * @param Arrays                              $arrays
     * @param \Magento\Backend\Model\Session      $backendSession
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        protected Arrays $arrays,
        protected \Magento\Backend\Model\Session $backendSession,
        protected \Magento\Backend\Model\Auth\Session $authSession
    ) {
    }

    /**
     * Returns the hidden field list.
     *
     * @param string $dataGridId
     *
     * @return array
     * @phpstan-return array<int, string>
     * @throws NotFoundException
     */
    public function getHiddenFieldList(string $dataGridId): array
    {
        $hiddenFieldLists = $this->backendSession->getData('data_grid_hidden_field_lists');

        if ($hiddenFieldLists === null) {
            throw new NotFoundException(__('No data for grid with id: %1', $dataGridId));
        }

        return $this->getFieldNames(
            is_array($hiddenFieldLists) ? $this->arrays->getValue(
                $hiddenFieldLists,
                $dataGridId,
                []
            ) : []
        );
    }

    /**
     * Returns the list of field names in the value, an empty list if it is no list.
     *
     * @param mixed $value
     *
     * @return array
     * @phpstan-return array<int, string>
     */
    public function getFieldNames(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(
            array_map(
                'strval',
                array_filter(
                    $value,
                    'is_scalar'
                )
            )
        );
    }

    /**
     * Saves the hidden field list.
     *
     * @param string $dataGridId
     * @param array  $hiddenFieldList
     *
     * @phpstan-param array<int, string> $hiddenFieldList
     *
     * @return void
     */
    public function saveHiddenFieldList(string $dataGridId, array $hiddenFieldList): void
    {
        $hiddenFieldLists = $this->backendSession->getData('data_grid_hidden_field_lists');

        if (! is_array($hiddenFieldLists)) {
            $hiddenFieldLists = [];
        }

        $hiddenFieldLists[$dataGridId] = $hiddenFieldList;

        /** @noinspection PhpUndefinedMethodInspection */
        // @phpstan-ignore method.notFound (the session passes it to its storage)
        $this->backendSession->setDataGridHiddenFieldLists($hiddenFieldLists);
    }

    /**
     * Resets the hidden field list.
     *
     * @return void
     */
    public function resetHiddenFieldList(): void
    {
        /** @noinspection PhpUndefinedMethodInspection */
        // @phpstan-ignore method.notFound (the session passes it to its storage)
        $this->backendSession->unsDataGridHiddenFieldLists();
    }
}
