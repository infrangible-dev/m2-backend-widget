<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Controller\Adminhtml\Search;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Helper\Search;
use Infrangible\Core\Controller\Adminhtml\Json;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Instances;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

/**
 * Base class of the controllers that search for the autocomplete form element. They only accept the search parameters
 * that the form element signed.
 */
abstract class Base extends Json
{
    /**
     * @param Arrays $arrays
     * @param \FeWeDev\Base\Json $json
     * @param Context $context
     * @param LoggerInterface $logging
     * @param Instances $instanceHelper
     * @param Attribute $attributeHelper
     * @param Search $searchHelper
     * @param Variables $variables
     * @param Escaper $escaper
     */
    public function __construct(
        Arrays $arrays,
        \FeWeDev\Base\Json $json,
        Context $context,
        LoggerInterface $logging,
        protected Instances $instanceHelper,
        protected Attribute $attributeHelper,
        protected Search $searchHelper,
        protected Variables $variables,
        protected Escaper $escaper
    ) {
        parent::__construct(
            $arrays,
            $json,
            $context,
            $logging
        );
    }

    /**
     * Returns the signed search parameters of the request.
     *
     * @param array $names
     *
     * @phpstan-param array<int, string> $names
     *
     * @return array
     * @phpstan-return array<string, string>
     * @throws NotFoundException if the parameters do not match their signature
     */
    protected function getSignedParameters(array $names): array
    {
        $request = $this->getRequest();

        $parameters = [];

        foreach ($names as $name) {
            $value = $request->getParam($name);

            if ($value !== null) {
                $parameters[ $name ] = $this->variables->stringValue($value);
            }
        }

        $signature = $request->getParam(Search::PARAM_SIGNATURE);

        if (! is_string($signature) || ! $this->searchHelper->isValid($parameters, $signature)) {
            throw new NotFoundException(__('Invalid search'));
        }

        return $parameters;
    }

    /**
     * Returns the collection to search in.
     *
     * @param string $collectionClass
     *
     * @return object
     * @throws NotFoundException
     */
    protected function getCollection(string $collectionClass): object
    {
        if ($collectionClass === '' || ! class_exists($collectionClass)) {
            throw new NotFoundException(__('Invalid search'));
        }

        $collection = $this->instanceHelper->getInstance($collectionClass);

        if ($collection === null) {
            throw new NotFoundException(__('Invalid search'));
        }

        return $collection;
    }

    /**
     * Returns the codes of the attributes used in the expression.
     *
     * @param string $entityTypeCode
     * @param string $expression
     * @param string $openingDelimiter
     * @param string $closingDelimiter
     *
     * @return array
     * @phpstan-return array<int, string>
     */
    protected function getExpressionAttributeCodes(
        string $entityTypeCode,
        string $expression,
        string $openingDelimiter = '{{',
        string $closingDelimiter = '}}'
    ): array {
        $fieldNames = $this->getExpressionFieldNames(
            $expression,
            $openingDelimiter,
            $closingDelimiter
        );

        $attributeCodes = [];

        foreach ($fieldNames as $fieldName) {
            try {
                $this->attributeHelper->getAttribute(
                    $entityTypeCode,
                    $fieldName
                );
            } catch (\Exception) {
                // not an attribute, but a field of the entity table
                continue;
            }

            $attributeCodes[] = $fieldName;
        }

        return $attributeCodes;
    }

    /**
     * Returns the names of the fields used in the expression.
     *
     * @param string $expression
     * @param string $openingDelimiter
     * @param string $closingDelimiter
     *
     * @return array
     * @phpstan-return array<int, string>
     */
    protected function getExpressionFieldNames(
        string $expression,
        string $openingDelimiter = '{{',
        string $closingDelimiter = '}}'
    ): array {
        $fieldNames = [];

        preg_match_all(
            sprintf(
                '/%s(\w+)%s/i',
                preg_quote($openingDelimiter, '/'),
                preg_quote($closingDelimiter, '/')
            ),
            $expression,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $fieldNames[] = $match[ 1 ];
        }

        return $fieldNames;
    }

    /**
     * Replaces the field names in the expression with the values of the item.
     *
     * @param string $expression
     * @param AbstractModel $item
     * @param bool $escapeHtml escape the values of the item, if the result is used as HTML
     * @param string $openingDelimiter
     * @param string $closingDelimiter
     *
     * @return string
     */
    protected function replaceExpressionAttributeCodes(
        string $expression,
        AbstractModel $item,
        bool $escapeHtml = false,
        string $openingDelimiter = '{{',
        string $closingDelimiter = '}}'
    ): string {
        return (string)preg_replace_callback(
            sprintf(
                '/%s(\w+)%s/i',
                preg_quote($openingDelimiter, '/'),
                preg_quote($closingDelimiter, '/')
            ),
            function (array $matches) use ($item, $escapeHtml): string {
                $value = $item->getDataUsingMethod($matches[ 1 ]);

                $value = $value === null ? '' : $this->variables->stringValue($value);

                if (! $escapeHtml) {
                    return $value;
                }

                /** @var string|string[] $escapedValue Magento before 2.4.9 declares string|array */
                $escapedValue = $this->escaper->escapeHtml($value);

                return is_string($escapedValue) ? $escapedValue : '';
            },
            $expression
        );
    }
}
