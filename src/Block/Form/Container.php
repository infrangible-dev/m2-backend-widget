<?php /** @noinspection PhpDeprecationInspection */

namespace Infrangible\BackendWidget\Block\Form;

use Exception;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\View\Element\AbstractBlock;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Registry;
use Tofex\Help\Arrays;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Container
    extends \Magento\Backend\Block\Widget\Form\Container
{
    /** @var Arrays */
    protected $arrayHelper;

    /** @var Registry */
    protected $registryHelper;

    /** @var Session */
    protected $session;

    /** @var string */
    protected $moduleKey;

    /** @var string */
    protected $objectName;

    /** @var string */
    protected $objectField;

    /** @var string */
    protected $objectTitle;

    /** @var string */
    protected $objectRegistryKey;

    /** @var bool */
    protected $allowAdd = true;

    /** @var bool */
    protected $allowEdit = true;

    /** @var bool */
    protected $allowView = false;

    /** @var bool */
    protected $allowDelete = true;

    /** @var bool */
    protected $allowExport = true;

    /** @var string */
    protected $saveUrlRoute;

    /** @var array */
    protected $saveUrlParams;

    /** @var string */
    protected $deleteUrlRoute;

    /** @var array */
    protected $deleteUrlParams;

    /** @var string */
    protected $indexUrlRoute;

    /** @var array */
    protected $indexUrlParams;

    /** @var string */
    protected $formContentBlockType;

    /** @var AbstractModel */
    private $object;

    /**
     * @param Context  $context
     * @param Arrays   $arrayHelper
     * @param Registry $registryHelper
     * @param Session  $session
     * @param array    $data
     */
    public function __construct(
        Context $context,
        Arrays $arrayHelper,
        Registry $registryHelper,
        Session $session,
        array $data = [])
    {
        $this->arrayHelper = $arrayHelper;
        $this->registryHelper = $registryHelper;

        $this->session = $session;

        $this->moduleKey = $arrayHelper->getValue($data, 'module_key', 'adminhtml');
        $this->objectName = $arrayHelper->getValue($data, 'object_name', 'empty');
        $this->objectField = $arrayHelper->getValue($data, 'object_field', 'id');
        $this->objectRegistryKey = $arrayHelper->getValue($data, 'object_registry_key');
        $this->objectTitle = $arrayHelper->getValue($data, 'title', 'Container Widget Header');
        $this->allowAdd = $arrayHelper->getValue($data, 'allow_add', true);
        $this->allowEdit = $arrayHelper->getValue($data, 'allow_edit', true);
        $this->allowView = $arrayHelper->getValue($data, 'allow_view', false);
        $this->allowDelete = $arrayHelper->getValue($data, 'allow_delete', true);
        $this->allowExport = $arrayHelper->getValue($data, 'allow_export', true);
        $this->saveUrlRoute = $arrayHelper->getValue($data, 'save_url_route', '*/*/save');
        $this->saveUrlParams = $arrayHelper->getValue($data, 'save_url_params', []);
        $this->deleteUrlRoute = $arrayHelper->getValue($data, 'delete_url_route', '*/*/delete');
        $this->deleteUrlParams = $arrayHelper->getValue($data, 'delete_url_params', []);
        $this->indexUrlRoute = $arrayHelper->getValue($data, 'index_url_route');
        $this->indexUrlParams = $arrayHelper->getValue($data, 'index_url_params', []);
        $this->formContentBlockType = $arrayHelper->getValue($data, 'form_content_block_type');

        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        if ($this->objectField) {
            $this->_objectId = $this->objectField;
        }

        $this->_blockGroup = $this->moduleKey;
        $this->_controller = sprintf('Adminhtml\%s', $this->objectName);
        $this->_mode = 'edit';

        parent::_construct();

        if ($this->allowEdit) {
            $this->_headerText =
                sprintf('%s > %s', $this->objectTitle, $this->getObject()->getId() ? __('Edit') : __('Add'));
        } else if ($this->allowView) {
            $this->_headerText = sprintf('%s > %s', $this->objectTitle, __('View'));
        }

        if ( ! $this->allowEdit) {
            $this->removeButton('reset');
            $this->removeButton('save');
        }

        if ( ! $this->allowDelete) {
            $this->removeButton('delete');
        }
    }

    /**
     * @return AbstractModel
     */
    protected function getObject(): AbstractModel
    {
        if ($this->object === null) {
            $this->object = $this->registryHelper->registry($this->objectRegistryKey);
        }

        return $this->object;
    }

    /**
     * @return AbstractBlock
     * @throws Exception
     */
    protected function _prepareLayout(): AbstractBlock
    {
        if ($this->formContentBlockType === null && $this->_blockGroup && $this->_controller) {
            $this->formContentBlockType = sprintf('%s\Block\%s\%s\%s', str_replace('_', '\\', $this->_blockGroup),
                str_replace('_', '\\', $this->_controller), ucfirst($this->_mode), $this->allowEdit ? 'Form' : 'View');

            if ( ! class_exists($this->formContentBlockType)) {
                $this->formContentBlockType = sprintf('%s\Block\%s\%s', str_replace('_', '\\', $this->_blockGroup),
                    str_replace('_', '\\', $this->_controller), $this->allowEdit ? 'Form' : 'View');
            }
        }

        if ($this->formContentBlockType === null) {
            throw new Exception('No block class defined');
        }

        if ( ! class_exists($this->formContentBlockType)) {
            throw new Exception(sprintf('Could not find block class: %s', $this->formContentBlockType));
        }

        /** @var AbstractBlock $block */
        $block = $this->getLayout()->createBlock($this->formContentBlockType, '', [
            'data' => [
                'module_key'          => $this->moduleKey,
                'object_name'         => $this->objectName,
                'object_field'        => $this->objectField,
                'object_registry_key' => $this->objectRegistryKey,
                'save_url_route'      => $this->saveUrlRoute,
                'save_url_params'     => $this->saveUrlParams,
                'allow_add'           => $this->allowAdd,
                'allow_edit'          => $this->allowEdit,
                'allow_view'          => $this->allowView
            ]
        ]);

        $this->setChild('form', $block);

        return \Magento\Backend\Block\Widget\Container::_prepareLayout();
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDeleteUrl(): string
    {
        $deleteUrlParams = $this->deleteUrlParams;

        $deleteUrlParams[ $this->_objectId ] = (int)$this->getRequest()->getParam($this->_objectId);

        return $this->getUrl($this->deleteUrlRoute, $deleteUrlParams);
    }

    /**
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl($this->indexUrlRoute, $this->indexUrlParams);
    }

    /**
     * Get form HTML.
     *
     * @return string
     */
    public function getFormHtml(): string
    {
        $formHtml = parent::getFormHtml();

        $this->session->unsetData($this->getFormSessionKey($this->getObject()));

        return $formHtml;
    }

    /**
     * @param AbstractModel|null $object
     *
     * @return string
     */
    protected function getFormSessionKey(AbstractModel $object = null): string
    {
        return sprintf('%s_form_%s', $this->objectRegistryKey, $object && $object->getId() ? $object->getId() : 'add');
    }
}
