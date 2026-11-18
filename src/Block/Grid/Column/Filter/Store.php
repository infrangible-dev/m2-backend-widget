<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid\Column\Filter;

use Magento\Backend\Block\Context;
use Magento\Framework\DB\Helper;

class Store extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Store
{
    /**
     * @param Context                                             $context
     * @param Helper                                              $resourceHelper
     * @param \Magento\Store\Model\System\Store                   $systemStore
     * @param \Infrangible\BackendWidget\Model\Store\System\Store $infrangibleSystemStore
     * @param array                                               $data
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        Helper $resourceHelper,
        \Magento\Store\Model\System\Store $systemStore,
        protected \Infrangible\BackendWidget\Model\Store\System\Store $infrangibleSystemStore,
        array $data = []
    ) {
        parent::__construct($context, $resourceHelper, $systemStore, $data);
    }

    /**
     * Render HTML of the element
     *
     * @return string
     */
    public function getHtml(): string
    {
        $websiteCollection = $this->infrangibleSystemStore->getWebsiteCollection();
        $groupCollection = $this->infrangibleSystemStore->getGroupCollection();
        $storeCollection = $this->infrangibleSystemStore->getStoreCollection();

        $html = '<select class="admin__control-select" name="' .
            $this->_escaper->escapeHtml($this->_getHtmlName()) . '" ' .
            $this->getColumn()->getData('validate_class') . $this->getUiId('filter', $this->_getHtmlName()) . '>';

        $value = $this->getColumn()->getData('value');

        $html .= '<option value=""' . (! $value ? ' selected="selected"' : '') . '></option>';

        foreach ($websiteCollection as $website) {
            $websiteShow = false;

            foreach ($groupCollection as $group) {
                if ($group->getWebsiteId() != $website->getId()) {
                    continue;
                }

                $groupShow = false;

                foreach ($storeCollection as $store) {
                    if ($store->getGroupId() != $group->getId()) {
                        continue;
                    }

                    if (! $websiteShow) {
                        $websiteShow = true;

                        $html .= sprintf(
                            '<optgroup label="%s"></optgroup>',
                            $this->_escaper->escapeHtml((string)$website->getName())
                        );
                    }

                    if (! $groupShow) {
                        $groupShow = true;

                        $html .= sprintf(
                            '<optgroup label="&nbsp;&nbsp;&nbsp;&nbsp;%s">',
                            $this->_escaper->escapeHtml((string)$group->getName())
                        );
                    }

                    $value = $this->getData('value');

                    $selected = $value == $store->getId() ? ' selected="selected"' : '';

                    $html .= '<option value="' . $this->_escaper->escapeHtmlAttr((string)$store->getId()) . '"' .
                        $selected . '>&nbsp;&nbsp;&nbsp;&nbsp;' .
                        $this->_escaper->escapeHtml((string)$store->getName()) . '</option>';
                }
                if ($groupShow) {
                    $html .= '</optgroup>';
                }
            }
        }

        if ($this->getColumn()->getData('display_deleted')) {
            $selected = $this->getData('value') == '_deleted_' ? ' selected' : '';

            $html .= '<option value="_deleted_"' . $selected . '>' .
                $this->_escaper->escapeHtml((string)__('[ deleted ]')) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }
}
