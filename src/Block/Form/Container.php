<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Form;

use Exception;
use FeWeDev\Base\Arrays;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Container extends \Magento\Backend\Block\Widget\Form\Container
{
    /** @var Arrays */
    protected $arrays;

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
    protected $gridUrlRoute;

    /** @var array */
    protected $gridUrlParams;

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

    /** @var string */
    private $editFormId;

    public function __construct(
        Context $context,
        Arrays $arrays,
        Registry $registryHelper,
        Session $session,
        array $data = []
    ) {
        $this->arrays = $arrays;
        $this->registryHelper = $registryHelper;
        $this->session = $session;

        $this->moduleKey = $arrays->getValue(
            $data,
            'module_key',
            'adminhtml'
        );
        $this->objectName = $arrays->getValue(
            $data,
            'object_name',
            'empty'
        );
        $this->objectField = $arrays->getValue(
            $data,
            'object_field',
            'id'
        );
        $this->objectRegistryKey = $arrays->getValue(
            $data,
            'object_registry_key'
        );
        $this->objectTitle = $arrays->getValue(
            $data,
            'title',
            'Container Widget Header'
        );
        $this->allowAdd = $arrays->getValue(
            $data,
            'allow_add',
            true
        );
        $this->allowEdit = $arrays->getValue(
            $data,
            'allow_edit',
            true
        );
        $this->allowView = $arrays->getValue(
            $data,
            'allow_view',
            false
        );
        $this->allowDelete = $arrays->getValue(
            $data,
            'allow_delete',
            true
        );
        $this->allowExport = $arrays->getValue(
            $data,
            'allow_export',
            true
        );
        $this->gridUrlRoute = $arrays->getValue(
            $data,
            'grid_url_route',
            '*/*/grid'
        );
        $this->gridUrlParams = $arrays->getValue(
            $data,
            'grid_url_params',
            []
        );
        $this->saveUrlRoute = $arrays->getValue(
            $data,
            'save_url_route',
            '*/*/save'
        );
        $this->saveUrlParams = $arrays->getValue(
            $data,
            'save_url_params',
            []
        );
        $this->deleteUrlRoute = $arrays->getValue(
            $data,
            'delete_url_route',
            '*/*/delete'
        );
        $this->deleteUrlParams = $arrays->getValue(
            $data,
            'delete_url_params',
            []
        );
        $this->indexUrlRoute = $arrays->getValue(
            $data,
            'index_url_route'
        );
        $this->indexUrlParams = $arrays->getValue(
            $data,
            'index_url_params',
            []
        );
        $this->formContentBlockType = $arrays->getValue(
            $data,
            'form_content_block_type'
        );

        $this->editFormId = $this->getFormId();

        parent::__construct(
            $context,
            $data
        );
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
        $this->_controller = sprintf(
            'Adminhtml\%s',
            $this->objectName
        );
        $this->_mode = 'edit';

        parent::_construct();

        $this->removeButton('save');

        $this->addButton(
            'save',
            [
                'label'          => __('Save'),
                'class'          => 'save primary',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => [
                            'event'  => 'save',
                            'target' => sprintf(
                                '#%s',
                                $this->editFormId
                            )
                        ]
                    ],
                ]
            ],
            1
        );

        if ($this->allowEdit) {
            $this->_headerText = sprintf(
                '%s > %s',
                $this->objectTitle,
                $this->getObject()->getId() ? __('Edit') : __('Add')
            );
        } elseif ($this->allowView) {
            $this->_headerText = sprintf(
                '%s > %s',
                $this->objectTitle,
                __('View')
            );
        }

        if (! $this->allowEdit) {
            $this->removeButton('reset');
            $this->removeButton('save');
        }

        if (! $this->allowDelete) {
            $this->removeButton('delete');
        }
    }

    protected function getFormId(): string
    {
        return 'edit_form';
    }

    protected function getObject(): AbstractModel
    {
        if ($this->object === null) {
            $this->object = $this->registryHelper->registry($this->objectRegistryKey);
        }

        return $this->object;
    }

    /**
     * @throws Exception
     */
    protected function _prepareLayout(): AbstractBlock
    {
        if ($this->formContentBlockType === null && $this->_blockGroup && $this->_controller) {
            $this->formContentBlockType = sprintf(
                '%s\Block\%s\%s\%s',
                str_replace(
                    '_',
                    '\\',
                    $this->_blockGroup
                ),
                str_replace(
                    '_',
                    '\\',
                    $this->_controller
                ),
                ucfirst($this->_mode),
                $this->allowEdit ? 'Form' : 'View'
            );

            if (! class_exists($this->formContentBlockType)) {
                $this->formContentBlockType = sprintf(
                    '%s\Block\%s\%s',
                    str_replace(
                        '_',
                        '\\',
                        $this->_blockGroup
                    ),
                    str_replace(
                        '_',
                        '\\',
                        $this->_controller
                    ),
                    $this->allowEdit ? 'Form' : 'View'
                );
            }
        }

        if ($this->formContentBlockType === null) {
            throw new Exception('No block class defined');
        }

        if (! class_exists($this->formContentBlockType)) {
            throw new Exception(
                sprintf(
                    'Could not find block class: %s',
                    $this->formContentBlockType
                )
            );
        }

        /** @var AbstractBlock $block */
        $block = $this->getLayout()->createBlock(
            $this->formContentBlockType,
            '',
            [
                'data' => [
                    'module_key'          => $this->moduleKey,
                    'object_name'         => $this->objectName,
                    'object_field'        => $this->objectField,
                    'object_registry_key' => $this->objectRegistryKey,
                    'grid_url_route'      => $this->gridUrlRoute,
                    'grid_url_params'     => $this->gridUrlParams,
                    'save_url_route'      => $this->saveUrlRoute,
                    'save_url_params'     => $this->saveUrlParams,
                    'allow_add'           => $this->allowAdd,
                    'allow_edit'          => $this->allowEdit,
                    'allow_view'          => $this->allowView,
                    'edit_form_id'        => $this->editFormId
                ]
            ]
        );

        $this->setChild(
            'form',
            $block
        );

        return \Magento\Backend\Block\Widget\Container::_prepareLayout();
    }

    /**
     * @throws Exception
     */
    public function getDeleteUrl(): string
    {
        $deleteUrlParams = $this->deleteUrlParams;

        $deleteUrlParams[ $this->_objectId ] = (int)$this->getRequest()->getParam($this->_objectId);

        return $this->getUrl(
            $this->deleteUrlRoute,
            $deleteUrlParams
        );
    }

    public function getBackUrl(): string
    {
        return $this->getUrl(
            $this->indexUrlRoute,
            $this->indexUrlParams
        );
    }

    public function getFormHtml(): string
    {
        $formHtml = parent::getFormHtml();

        $this->session->unsetData($this->getFormSessionKey($this->getObject()));

        return $formHtml;
    }

    protected function getFormSessionKey(?AbstractModel $object = null): string
    {
        return sprintf(
            '%s_form_%s',
            $this->objectRegistryKey,
            $object && $object->getId() ? $object->getId() : 'add'
        );
    }
}
