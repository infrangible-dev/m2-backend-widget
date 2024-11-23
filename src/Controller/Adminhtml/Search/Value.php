<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Adminhtml\Search;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Value extends Base
{
    /**
     * @throws LocalizedException
     */
    public function execute(): void
    {
        $request = $this->getRequest();

        $objectId = $request->getParam('object_id');
        $searchCollection = $request->getParam('search_collection');
        $resultValue = $request->getParam('result_value');

        $collection = $this->instanceHelper->getInstance($searchCollection);

        if ($collection instanceof AbstractDb) {
            $collection->addFieldToFilter(
                $collection->getIdFieldName(),
                $objectId
            );

            if ($collection instanceof AbstractCollection) {
                $entityTypeCode = $collection->getEntity()->getEntityType()->getEntityTypeCode();

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
}
