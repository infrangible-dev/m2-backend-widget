<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Controller\Adminhtml\Search;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Model\AbstractModel;

/**
 * Returns the value that an autocomplete form element shows for the selected entry.
 */
class Value extends Base
{
    /**
     * Executes the action.
     *
     * @return ResponseInterface
     * @throws LocalizedException
     * @throws NotFoundException
     */
    public function execute(): ResponseInterface
    {
        $parameters = $this->getSignedParameters(
            [
                'object_id',
                'search_collection',
                'result_value'
            ]
        );

        $objectId = $parameters[ 'object_id' ] ?? '';
        $resultValue = $parameters[ 'result_value' ] ?? '';

        $collection = $this->getCollection($parameters[ 'search_collection' ] ?? '');

        if ($collection instanceof AbstractDb) {
            $collection->addFieldToFilter(
                $collection->getIdFieldName(),
                $objectId
            );

            if ($collection instanceof AbstractCollection) {
                $entityTypeCode = $this->variables->stringValue(
                    $collection->getEntity()->getEntityType()->getEntityTypeCode()
                );

                $resultAttributeCodes = $this->getExpressionAttributeCodes(
                    $entityTypeCode,
                    $resultValue
                );

                foreach ($resultAttributeCodes as $resultAttributeCode) {
                    $collection->addAttributeToSelect(
                        $resultAttributeCode,
                        'left'
                    );
                }

                $item = $collection->getFirstItem();

                if ($item instanceof AbstractModel) {
                    $this->addResponseValue(
                        'value',
                        $this->replaceExpressionAttributeCodes(
                            $resultValue,
                            $item
                        )
                    );
                }
            }
        }

        return $this->getResponse();
    }
}
