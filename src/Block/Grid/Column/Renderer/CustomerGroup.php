<?php

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Tofex\Help\Variables;
use Infrangible\Core\Helper\Customer;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CustomerGroup
    extends AbstractRenderer
{
    /** @var Variables */
    protected $variableHelper;

    /** @var Customer */
    protected $customerHelper;

    /**
     * @param Context   $context
     * @param Variables $variableHelper
     * @param Customer  $customerHelper
     * @param array     $data
     */
    public function __construct(
        Context $context,
        Variables $variableHelper,
        Customer $customerHelper,
        array $data = [])
    {
        parent::__construct($context, $data);

        $this->variableHelper = $variableHelper;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @param DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row): string
    {
        $column = $this->_column;

        $customerGroupIds = $row->getData($column->getData('index'));

        $categoryGroupCodes = [];

        if ( ! $this->variableHelper->isEmpty($customerGroupIds)) {
            $customerGroupIds = explode(',', $customerGroupIds);

            foreach ($customerGroupIds as $customerGroupId) {
                $customerGroup = $this->customerHelper->loadCustomerGroup($customerGroupId);

                if ($customerGroup->getCode()) {
                    $categoryGroupCodes[] = $customerGroup->getCode();
                }
            }
        }

        natcasesort($categoryGroupCodes);

        return implode('<br />', $categoryGroupCodes);
    }
}
