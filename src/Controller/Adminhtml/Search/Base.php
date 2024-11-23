<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Adminhtml\Search;

use FeWeDev\Base\Arrays;
use Infrangible\Core\Controller\Adminhtml\Json;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Instances;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Base extends Json
{
    /** @var Instances */
    protected $instanceHelper;

    /** @var Attribute */
    protected $attributeHelper;

    public function __construct(
        Arrays $arrays,
        \FeWeDev\Base\Json $json,
        Context $context,
        LoggerInterface $logging,
        Instances $instanceHelper,
        Attribute $attributeHelper
    ) {
        parent::__construct(
            $arrays,
            $json,
            $context,
            $logging
        );

        $this->instanceHelper = $instanceHelper;
        $this->attributeHelper = $attributeHelper;
    }

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

                $attributeCodes[] = $fieldName;
            } catch (\Exception $exception) {
            }
        }

        return $attributeCodes;
    }

    protected function getExpressionFieldNames(
        string $expression,
        string $openingDelimiter = '{{',
        string $closingDelimiter = '}}'
    ): array {
        $fieldNames = [];

        preg_match_all(
            sprintf(
                '/%s(\w+)%s/i',
                $openingDelimiter,
                $closingDelimiter
            ),
            $expression,
            $matches,
            PREG_SET_ORDER
        );

        if ($matches) {
            foreach ($matches as $match) {
                $fieldNames[] = $match[ 1 ];
            }
        }

        return $fieldNames;
    }

    protected function replaceExpressionAttributeCodes(
        string $expression,
        AbstractModel $item,
        string $openingDelimiter = '{{',
        string $closingDelimiter = '}}'
    ): string {
        return preg_replace_callback(
            sprintf(
                '/%s(\w+)%s/i',
                $openingDelimiter,
                $closingDelimiter
            ),
            function ($matches) use ($item) {
                return $item->getDataUsingMethod($matches[ 1 ]);
            },
            $expression
        );
    }
}
