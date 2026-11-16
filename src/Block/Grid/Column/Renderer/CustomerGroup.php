<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Customer;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class CustomerGroup extends AbstractRenderer
{
    use EscapedValues;

    /**
     * @param Context $context
     * @param Variables $variables
     * @param Customer $customerHelper
     * @param array $data
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        protected Variables $variables,
        protected Customer $customerHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Renders.
     *
     * @param DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row): string
    {
        $customerGroupCodes = [];

        foreach ($this->getIds($row->getData($this->getColumnIndex())) as $customerGroupId) {
            $customerGroup = $this->customerHelper->loadCustomerGroup($customerGroupId);

            if ($customerGroup->getCode()) {
                $customerGroupCodes[] = $customerGroup->getCode();
            }
        }

        natcasesort($customerGroupCodes);

        return $this->escapeValues(array_values($customerGroupCodes));
    }
}
