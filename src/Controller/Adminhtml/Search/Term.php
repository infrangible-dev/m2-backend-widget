<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Adminhtml\Search;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Term extends Base
{
    /**
     * @throws LocalizedException
     */
    public function execute(): void
    {
        $request = $this->getRequest();

        $term = $request->getParam('term');
        $searchCollection = $request->getParam('search_collection');
        $searchFields = $request->getParam('search_fields');
        $searchExpressions = $request->getParam('search_expressions');
        $searchConditions = $request->getParam('search_conditions');
        $searchLimit = $request->getParam(
            'search_limit',
            8
        );
        $resultId = $request->getParam('result_id');
        $resultValue = $request->getParam('result_value');
        $resultLabel = $request->getParam('result_label');

        $collection = $this->instanceHelper->getInstance($searchCollection);

        if ($collection instanceof \Magento\Eav\Model\Entity\Collection\AbstractCollection) {
            $entityTypeCode = $collection->getEntity()->getEntityType()->getEntityTypeCode();

            if ($searchExpressions) {
                $searchExpressions = $this->json->decode($searchExpressions);

                foreach ($searchExpressions as $searchExpressionAlias => $searchExpression) {
                    $attributeCodes = $this->getExpressionAttributeCodes(
                        $entityTypeCode,
                        $searchExpression
                    );

                    /** @noinspection PhpParamsInspection */
                    $collection->addExpressionAttributeToSelect(
                        $searchExpressionAlias,
                        $searchExpression,
                        $attributeCodes
                    );
                }
            }

            if ($searchFields) {
                $filterAttributeConditions = [];

                $searchFields = explode(
                    ',',
                    $searchFields
                );

                foreach ($searchFields as $searchField) {
                    $filterAttributeConditions[] = [
                        'attribute' => $searchField,
                        'like'      => sprintf(
                            '%%%s%%',
                            $term
                        )
                    ];
                }

                $collection->addAttributeToFilter($filterAttributeConditions);
            }

            if ($searchConditions) {
                $searchConditions = $this->json->decode($searchConditions);

                foreach ($searchConditions as $attributeCode => $condition) {
                    $collection->addAttributeToFilter(
                        $attributeCode,
                        $condition
                    );
                }
            }

            $resultAttributeCodes = $this->getResultAttributeCodes(
                $entityTypeCode,
                $resultId,
                $resultValue,
                $resultLabel
            );

            foreach ($resultAttributeCodes as $resultAttributeCode) {
                $collection->addAttributeToSelect(
                    $resultAttributeCode,
                    'left'
                );
            }
        } elseif ($collection instanceof AbstractCollection) {
            if ($searchExpressions) {
                $searchExpressions = $this->json->decode($searchExpressions);

                foreach ($searchExpressions as $searchExpressionAlias => $searchExpression) {
                    $expressionFields = $this->getExpressionFieldNames($searchExpression);

                    $fields = [];

                    foreach ($expressionFields as $expressionField) {
                        $fields[ $expressionField ] = sprintf(
                            'main_table.%s',
                            $expressionField
                        );
                    }

                    /** @noinspection PhpParamsInspection */
                    $collection->addExpressionFieldToSelect(
                        $searchExpressionAlias,
                        $searchExpression,
                        $fields
                    );
                }
            }

            if ($searchFields) {
                $searchFields = explode(
                    ',',
                    $searchFields
                );

                $filterFields = [];
                $filterConditions = [];

                foreach ($searchFields as $searchField) {
                    if ($searchExpressions && array_key_exists(
                            $searchField,
                            $searchExpressions
                        )) {
                        $searchFieldValue = $searchExpressions[ $searchField ];

                        $expressionFields = $this->getExpressionFieldNames($searchFieldValue);

                        foreach ($expressionFields as $expressionField) {
                            $searchFieldValue = str_replace(
                                '{{' . $expressionField . '}}',
                                sprintf(
                                    'main_table.%s',
                                    $expressionField
                                ),
                                $searchFieldValue
                            );
                        }

                        $searchFieldValue = new \Zend_Db_Expr($searchFieldValue);
                    } else {
                        $searchFieldValue = $searchField;
                    }

                    $filterFields[ $searchField ] = $searchFieldValue;
                    $filterConditions[ $searchField ] = [
                        'like' => sprintf(
                            '%%%s%%',
                            $term
                        )
                    ];
                }

                $collection->addFieldToFilter(
                    $filterFields,
                    $filterConditions
                );
            }

            if ($searchConditions) {
                $searchConditions = $this->json->decode($searchConditions);

                foreach ($searchConditions as $attributeCode => $condition) {
                    $collection->addFieldToFilter(
                        $attributeCode,
                        $condition
                    );
                }
            }
        }

        $collection->getSelect()->limit($searchLimit);

        $result = [];

        /** @var AbstractModel $item */
        foreach ($collection as $item) {
            $id = $this->replaceExpressionAttributeCodes(
                $resultId,
                $item
            );

            $value = $this->replaceExpressionAttributeCodes(
                $resultValue,
                $item
            );

            $label = $this->replaceExpressionAttributeCodes(
                $resultLabel,
                $item
            );

            $result[] = ['id' => $id, 'value' => $value, 'label' => $label];
        }

        $this->setResponseValues($result);
    }

    protected function getResultAttributeCodes(
        string $entityTypeCode,
        string $idExpression,
        string $valueExpression,
        string $labelExpression
    ): array {
        return array_merge(
            $this->getExpressionAttributeCodes(
                $entityTypeCode,
                $idExpression
            ),
            $this->getExpressionAttributeCodes(
                $entityTypeCode,
                $valueExpression
            ),
            $this->getExpressionAttributeCodes(
                $entityTypeCode,
                $labelExpression
            )
        );
    }
}
