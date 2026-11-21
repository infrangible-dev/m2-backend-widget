<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Controller\Adminhtml\Search;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Searches the entries that match the term entered in an autocomplete form element.
 */
class Term extends Base
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
                'search_collection',
                'search_fields',
                'search_expressions',
                'search_conditions',
                'search_limit',
                'result_id',
                'result_value',
                'result_label'
            ]
        );

        $term = trim($this->variables->stringValue($this->getRequest()->getParam('term', '')));
        $searchFields = $this->getSearchFields($parameters[ 'search_fields' ] ?? '');
        $searchExpressions = $this->decodeStrings($parameters[ 'search_expressions' ] ?? '');
        $searchConditions = $this->decodeConditions($parameters[ 'search_conditions' ] ?? '');
        $searchLimit = (int)($parameters[ 'search_limit' ] ?? 8);
        $resultId = $parameters[ 'result_id' ] ?? '';
        $resultValue = $parameters[ 'result_value' ] ?? '';
        $resultLabel = $parameters[ 'result_label' ] ?? '';

        $collection = $this->getCollection($parameters[ 'search_collection' ] ?? '');

        if ($collection instanceof \Magento\Eav\Model\Entity\Collection\AbstractCollection) {
            $entityTypeCode = $this->variables->stringValue(
                $collection->getEntity()->getEntityType()->getEntityTypeCode()
            );

            foreach ($searchExpressions as $searchExpressionAlias => $searchExpression) {
                /** @noinspection PhpParamsInspection Magento also accepts a list of attribute codes */
                $collection->addExpressionAttributeToSelect(
                    $searchExpressionAlias,
                    $searchExpression,
                    // @phpstan-ignore argument.type (Magento also accepts a list of attribute codes)
                    $this->getExpressionAttributeCodes(
                        $entityTypeCode,
                        $searchExpression
                    )
                );
            }

            if (count($searchFields) > 0) {
                $collection->addAttributeToFilter(
                    array_map(
                        static fn(string $searchField): array => [
                            'attribute' => $searchField,
                            'like'      => sprintf('%%%s%%', $term)
                        ],
                        $searchFields
                    )
                );
            }

            foreach ($searchConditions as $attributeCode => $condition) {
                $collection->addAttributeToFilter(
                    $attributeCode,
                    $condition
                );
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
            foreach ($searchExpressions as $searchExpressionAlias => $searchExpression) {
                $fields = [];

                foreach ($this->getExpressionFieldNames($searchExpression) as $expressionField) {
                    $fields[ $expressionField ] = sprintf(
                        'main_table.%s',
                        $expressionField
                    );
                }

                $collection->addExpressionFieldToSelect(
                    $searchExpressionAlias,
                    $searchExpression,
                    $fields
                );
            }

            if (count($searchFields) > 0) {
                $filterFields = [];
                $filterConditions = [];

                foreach ($searchFields as $searchField) {
                    if (array_key_exists(
                        $searchField,
                        $searchExpressions
                    )) {
                        $searchFieldValue = $searchExpressions[ $searchField ];

                        foreach ($this->getExpressionFieldNames($searchFieldValue) as $expressionField) {
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

            foreach ($searchConditions as $attributeCode => $condition) {
                $collection->addFieldToFilter(
                    $attributeCode,
                    $condition
                );
            }
        } else {
            throw new NotFoundException(__('Invalid search'));
        }

        $collection->getSelect()->limit($searchLimit);

        $result = [];

        foreach ($collection as $item) {
            if (! $item instanceof AbstractModel) {
                continue;
            }

            $result[] = [
                'id'    => $this->replaceExpressionAttributeCodes(
                    $resultId,
                    $item
                ),
                'value' => $this->replaceExpressionAttributeCodes(
                    $resultValue,
                    $item
                ),
                'label' => $this->replaceExpressionAttributeCodes(
                    $resultLabel,
                    $item,
                    true
                )
            ];
        }

        $this->setResponseValues($result);

        return $this->getResponse();
    }

    /**
     * Returns the codes of the attributes used in the result expressions.
     *
     * @param string $entityTypeCode
     * @param string $idExpression
     * @param string $valueExpression
     * @param string $labelExpression
     *
     * @return array
     * @phpstan-return array<int, string>
     */
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

    /**
     * Returns the fields to search in from the comma-separated list.
     *
     * @param string $searchFields
     *
     * @return array
     * @phpstan-return array<int, string>
     */
    private function getSearchFields(string $searchFields): array
    {
        return array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        $searchFields
                    )
                ),
                static fn(string $searchField): bool => $searchField !== ''
            )
        );
    }

    /**
     * Decodes the JSON object of strings, for example the search expressions by alias.
     *
     * @param string $encodedValue
     *
     * @return array
     * @phpstan-return array<string, string>
     */
    private function decodeStrings(string $encodedValue): array
    {
        $values = [];

        foreach ($this->decode($encodedValue) as $key => $value) {
            $values[ (string)$key ] = $this->variables->stringValue($value);
        }

        return $values;
    }

    /**
     * Decodes the JSON object of the search conditions by field name.
     *
     * @param string $encodedValue
     *
     * @return array
     * @phpstan-return array<string, array<int|string, mixed>|string>
     */
    private function decodeConditions(string $encodedValue): array
    {
        $conditions = [];

        foreach ($this->decode($encodedValue) as $key => $value) {
            $conditions[ (string)$key ] = is_array($value) ? $value : $this->variables->stringValue($value);
        }

        return $conditions;
    }

    /**
     * Decodes a JSON object.
     *
     * @param string $encodedValue
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    private function decode(string $encodedValue): array
    {
        if ($encodedValue === '') {
            return [];
        }

        $value = $this->json->decode($encodedValue);

        return is_array($value) ? $value : [];
    }
}
