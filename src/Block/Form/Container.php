<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Form;

use FeWeDev\Base\Arrays;
use Infrangible\BackendWidget\Block\LayoutArguments;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;
use RuntimeException;

class Container extends \Magento\Backend\Block\Widget\Form\Container
{
    use LayoutArguments;

    /** @var string */
    protected string $moduleKey;

    /** @var string */
    protected string $objectName;

    /** @var string */
    protected string $objectField;

    /** @var string */
    protected string $objectTitle;

    /** @var string */
    protected string $objectRegistryKey;

    /** @var bool */
    protected bool $allowAdd = true;

    /** @var bool */
    protected bool $allowEdit = true;

    /** @var bool */
    protected bool $allowView = false;

    /** @var bool */
    protected bool $allowDelete = true;

    /** @var bool */
    protected bool $allowExport = true;

    /** @var string */
    protected string $gridUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $gridUrlParams;

    /** @var string */
    protected string $saveUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $saveUrlParams;

    /** @var string */
    protected string $deleteUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $deleteUrlParams;

    /** @var string */
    protected string $indexUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $indexUrlParams;

    /** @var string */
    protected string $formContentBlockType;

    /** @var AbstractModel|null */
    private ?AbstractModel $object = null;

    /** @var string */
    private string $editFormId;

    /**
     * @param Context $context
     * @param Arrays $arrays
     * @param Registry $registryHelper
     * @param Session $session
     * @param array $data
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        protected Arrays $arrays,
        protected Registry $registryHelper,
        protected Session $session,
        array $data = []
    ) {
        $this->moduleKey = $this->stringArgument($data, 'module_key', 'adminhtml');
        $this->objectName = $this->stringArgument($data, 'object_name', 'empty');
        $this->objectField = $this->stringArgument($data, 'object_field', 'id');
        $this->objectRegistryKey = $this->stringArgument($data, 'object_registry_key');
        $this->objectTitle = $this->stringArgument($data, 'title', 'Container Widget Header');
        $this->allowAdd = $this->boolArgument($data, 'allow_add', true);
        $this->allowEdit = $this->boolArgument($data, 'allow_edit', true);
        $this->allowView = $this->boolArgument($data, 'allow_view');
        $this->allowDelete = $this->boolArgument($data, 'allow_delete', true);
        $this->allowExport = $this->boolArgument($data, 'allow_export', true);
        $this->gridUrlRoute = $this->stringArgument($data, 'grid_url_route', '*/*/grid');
        $this->gridUrlParams = $this->arrayArgument($data, 'grid_url_params');
        $this->saveUrlRoute = $this->stringArgument($data, 'save_url_route', '*/*/save');
        $this->saveUrlParams = $this->arrayArgument($data, 'save_url_params');
        $this->deleteUrlRoute = $this->stringArgument($data, 'delete_url_route', '*/*/delete');
        $this->deleteUrlParams = $this->arrayArgument($data, 'delete_url_params');
        $this->indexUrlRoute = $this->stringArgument($data, 'index_url_route');
        $this->indexUrlParams = $this->arrayArgument($data, 'index_url_params');
        $this->formContentBlockType = $this->stringArgument($data, 'form_content_block_type');

        $this->editFormId = $this->getFormId();

        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Constructor.
     *
     * @return void
     */
    protected function _construct(): void
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

    /**
     * Returns the form id.
     *
     * @return string
     */
    protected function getFormId(): string
    {
        return 'edit_form';
    }

    /**
     * Returns the object.
     *
     * @return AbstractModel
     * @throws RuntimeException
     */
    protected function getObject(): AbstractModel
    {
        if ($this->object === null) {
            $object = $this->registryHelper->registry($this->objectRegistryKey);

            if (! $object instanceof AbstractModel) {
                throw new RuntimeException(
                    sprintf(
                        'No object registered with key: %s',
                        $this->objectRegistryKey
                    )
                );
            }

            $this->object = $object;
        }

        return $this->object;
    }

    /**
     * Prepares the layout.
     *
     * @return AbstractBlock
     * @throws LocalizedException
     */
    protected function _prepareLayout(): AbstractBlock
    {
        if ($this->formContentBlockType === '' && $this->_blockGroup && $this->_controller) {
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

        if ($this->formContentBlockType === '') {
            throw new \LogicException('No block class defined');
        }

        if (! is_a($this->formContentBlockType, BlockInterface::class, true)) {
            throw new \LogicException(
                sprintf(
                    'Could not find block class %s that implements %s',
                    $this->formContentBlockType,
                    BlockInterface::class
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
     * Returns the delete url.
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        $deleteUrlParams = $this->deleteUrlParams;

        $objectId = $this->getRequest()->getParam($this->_objectId);

        $deleteUrlParams[ $this->_objectId ] = is_scalar($objectId) ? (int)$objectId : 0;

        return $this->getUrl(
            $this->deleteUrlRoute,
            $deleteUrlParams
        );
    }

    /**
     * Returns the back url.
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl(
            $this->indexUrlRoute,
            $this->indexUrlParams
        );
    }

    /**
     * Returns the form html.
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
     * Returns the form session key.
     *
     * @param AbstractModel|null $object
     *
     * @return string
     */
    protected function getFormSessionKey(?AbstractModel $object = null): string
    {
        return sprintf(
            '%s_form_%s',
            $this->objectRegistryKey,
            $object && is_scalar($object->getId()) ? (string)$object->getId() : 'add'
        );
    }
}
