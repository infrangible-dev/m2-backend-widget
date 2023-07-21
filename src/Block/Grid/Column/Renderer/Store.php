<?php /** @noinspection PhpDeprecationInspection */

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Magento\Backend\Block\Context;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Store
    extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Store
{
    /** @var \Infrangible\BackendWidget\Model\Store\System\Store */
    protected $infrangibleSystemStore;

    /**
     * @param Context                                             $context
     * @param \Magento\Store\Model\System\Store                   $systemStore
     * @param \Infrangible\BackendWidget\Model\Store\System\Store $infrangibleSystemStore
     * @param array                                               $data
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\System\Store $systemStore,
        \Infrangible\BackendWidget\Model\Store\System\Store $infrangibleSystemStore,
        array $data = [])
    {
        parent::__construct($context, $systemStore, $data);

        $this->infrangibleSystemStore = $infrangibleSystemStore;

        $this->_skipAllStoresLabel = true;
        $this->_skipEmptyStoresLabel = true;
    }

    /**
     * @return \Magento\Store\Model\System\Store
     */
    protected function _getStoreModel()
    {
        return $this->infrangibleSystemStore;
    }
}
