<?php /** @noinspection ALL */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Select;

/**
 * Mass actions of the grids of the admin.
 *
 * @noinspection PhpDeprecationInspection The class builds on Magento's legacy grid widget.
 */
class MassAction extends \Magento\Backend\Block\Widget\Grid\Massaction\Extended
{
    /**
     * Get grid ids in JSON format
     *
     * @return string
     */
    public function getGridIdsJson(): string
    {
        $parentBlock = $this->getParentBlock();

        if ($parentBlock instanceof \Infrangible\BackendWidget\Block\Grid) {
            if (! $this->getUseSelectAll()) {
                return '';
            }

            $allIdsCollection = clone $parentBlock->getCollection();

            $massActionIdField = $this->getData('massaction_id_field');

            if (! is_string($massActionIdField) || $massActionIdField === '') {
                $massActionIdField = (string)$parentBlock->getMassactionIdField();
            }

            if ($allIdsCollection instanceof AbstractDb) {
                $idsSelect = clone $allIdsCollection->getSelect();

                $idsSelect->reset(Select::ORDER);
                $idsSelect->reset(Select::LIMIT_COUNT);
                $idsSelect->reset(Select::LIMIT_OFFSET);
                $idsSelect->reset(Select::COLUMNS);

                $massActionOriginalObjectField = $parentBlock->getMassActionOriginalObjectField();

                if ($massActionOriginalObjectField) {
                    $idsSelect->columns([$massActionIdField => $massActionOriginalObjectField]);
                } else {
                    $idsSelect->columns($massActionIdField);
                }

                $idList = $allIdsCollection->getConnection()->fetchCol($idsSelect);
            } else {
                $idList = $allIdsCollection->setPageSize(0)->getColumnValues($massActionIdField);
            }

            return implode(',', $idList);
        }

        return parent::getGridIdsJson();
    }
}
