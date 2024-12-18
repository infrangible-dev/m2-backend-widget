<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Customer;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CustomerGroup extends AbstractRenderer
{
    /** @var Variables */
    protected $variables;

    /** @var Customer */
    protected $customerHelper;

    public function __construct(
        Context $context,
        Variables $variables,
        Customer $customerHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );

        $this->variables = $variables;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @throws \Exception
     */
    public function render(DataObject $row): string
    {
        $column = $this->_column;

        $customerGroupIds = $row->getData($column->getData('index'));

        $categoryGroupCodes = [];

        if (! $this->variables->isEmpty($customerGroupIds)) {
            $customerGroupIds = explode(
                ',',
                $customerGroupIds
            );

            foreach ($customerGroupIds as $customerGroupId) {
                $customerGroup = $this->customerHelper->loadCustomerGroup($this->variables->intValue($customerGroupId));

                if ($customerGroup->getCode()) {
                    $categoryGroupCodes[] = $customerGroup->getCode();
                }
            }
        }

        natcasesort($categoryGroupCodes);

        return implode(
            '<br />',
            $categoryGroupCodes
        );
    }
}
