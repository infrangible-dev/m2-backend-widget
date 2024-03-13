<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Model\Store\System;

use Magento\Store\Model\Group;
use Magento\Store\Model\Website;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Store
    extends \Magento\Store\Model\System\Store
{
    /**
     * Load/Reload Website collection
     *
     * @return \Magento\Store\Model\System\Store
     */
    protected function _loadWebsiteCollection(): \Magento\Store\Model\System\Store
    {
        $this->_websiteCollection = $this->_storeManager->getWebsites(true);

        ksort($this->_websiteCollection);

        return $this;
    }

    /**
     * Load/Reload Group collection
     *
     * @return \Magento\Store\Model\System\Store
     */
    protected function _loadGroupCollection(): \Magento\Store\Model\System\Store
    {
        $this->_groupCollection = [];

        foreach ($this->_storeManager->getWebsites(true) as $website) {
            foreach ($website->getGroups() as $group) {
                $this->_groupCollection[ $group->getId() ] = $group;
            }
        }

        ksort($this->_groupCollection);

        return $this;
    }

    /**
     * Load/Reload Store collection
     *
     * @return \Magento\Store\Model\System\Store
     */
    protected function _loadStoreCollection(): \Magento\Store\Model\System\Store
    {
        $this->_storeCollection = $this->_storeManager->getStores(true);

        ksort($this->_storeCollection);

        return $this;
    }

    /**
     * Retrieve stores structure
     *
     * @param bool  $isAll
     * @param array $storeIds
     * @param array $groupIds
     * @param array $websiteIds
     *
     * @return array
     */
    public function getStoresStructure($isAll = false, $storeIds = [], $groupIds = [], $websiteIds = []): array
    {
        $out = [];

        $websites = $this->getWebsiteCollection();

        if ($isAll) {
            $out[] = ['value' => 0, 'label' => __('All Store Views')];
        }

        /** @var Website $website */
        foreach ($websites as $website) {
            $websiteId = $website->getId();

            if ($websiteIds && ! in_array($websiteId, $websiteIds)) {
                continue;
            }

            $out[ $websiteId ] = ['value' => $websiteId, 'label' => $website->getName()];

            /** @var Group $group */
            foreach ($website->getGroups() as $group) {
                $groupId = $group->getId();

                if ($groupIds && ! in_array($groupId, $groupIds)) {
                    continue;
                }

                $out[ $websiteId ][ 'children' ][ $groupId ] = ['value' => $groupId, 'label' => $group->getName()];

                /** @var \Magento\Store\Model\Store $store */
                foreach ($website->getStores() as $store) {
                    if ($store->getGroupId() != $groupId) {
                        continue;
                    }

                    $storeId = $store->getId();

                    if ($storeIds && ! in_array($storeId, $storeIds)) {
                        continue;
                    }

                    $out[ $websiteId ][ 'children' ][ $groupId ][ 'children' ][ $storeId ] = [
                        'value' => $storeId,
                        'label' => $store->getName(),
                    ];
                }

                if (empty($out[ $websiteId ][ 'children' ][ $groupId ][ 'children' ])) {
                    unset($out[ $websiteId ][ 'children' ][ $groupId ]);
                }
            }
            if (empty($out[ $websiteId ][ 'children' ])) {
                unset($out[ $websiteId ]);
            }
        }

        return $out;
    }
}
