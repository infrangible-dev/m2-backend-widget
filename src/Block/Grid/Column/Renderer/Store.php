<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Magento\Backend\Block\Context;

/**
 * Renders the store of a row in a grid.
 *
 * @noinspection PhpDeprecationInspection The class builds on Magento's legacy grid widget.
 */
class Store extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Store
{
    /**
     * @param Context                                             $context
     * @param \Magento\Store\Model\System\Store                   $systemStore
     * @param \Infrangible\BackendWidget\Model\Store\System\Store $infrangibleSystemStore
     * @param array                                               $data
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\System\Store $systemStore,
        protected \Infrangible\BackendWidget\Model\Store\System\Store $infrangibleSystemStore,
        array $data = []
    ) {
        parent::__construct($context, $systemStore, $data);

        $this->_skipAllStoresLabel = true;
        $this->_skipEmptyStoresLabel = true;
    }

    /**
     * Returns the store model.
     *
     * @return \Magento\Store\Model\System\Store
     */
    protected function _getStoreModel(): \Magento\Store\Model\System\Store
    {
        return $this->infrangibleSystemStore;
    }
}
