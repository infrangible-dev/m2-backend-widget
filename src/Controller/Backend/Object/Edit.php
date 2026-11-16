<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Infrangible\BackendWidget\Block\Form\Container;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\App\Action\Context;
use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;

abstract class Edit extends Base
{
    /**
     * @param Registry $registryHelper
     * @param Instances $instanceHelper
     * @param Context $context
     * @param Session $session
     */
    public function __construct(
        protected Registry $registryHelper,
        protected Instances $instanceHelper,
        Context $context,
        protected Session $session
    ) {
        parent::__construct($context);
    }

    /**
     * Executes the action.
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $object = $this->initEditableObject();

        if ($object === null) {
            return $this->getResponse();
        }

        $this->initAction();

        $blockClassName = $this->getFormBlockType();

        if (! is_a($blockClassName, BlockInterface::class, true)) {
            throw new \LogicException(
                sprintf(
                    'Could not find block class %s that implements %s',
                    $blockClassName,
                    BlockInterface::class
                )
            );
        }

        /** @var AbstractBlock $block */
        $block = $this->_view->getLayout()->createBlock(
            $blockClassName,
            '',
            [
                'data' => [
                    'module_key'              => $this->getModuleKey(),
                    'object_name'             => $this->getObjectName(),
                    'object_field'            => $this->getObjectField(),
                    'object_registry_key'     => $this->getObjectRegistryKey(),
                    'title'                   => $this->getTitle(),
                    'allow_add'               => $this->allowAdd(),
                    'allow_edit'              => $this->allowEdit(),
                    'allow_view'              => $this->allowView(),
                    'allow_delete'            => $this->allowDelete(),
                    'allow_export'            => $this->allowExport(),
                    'grid_url_route'          => $this->getGridUrlRoute(),
                    'grid_url_params'         => $this->getGridUrlParams(),
                    'save_url_route'          => $this->getSaveUrlRoute(),
                    'save_url_params'         => $this->getSaveUrlParams(),
                    'delete_url_route'        => $this->getDeleteUrlRoute(),
                    'delete_url_params'       => $this->getDeleteUrlParams(),
                    'index_url_route'         => $this->getIndexUrlRoute(),
                    'index_url_params'        => $this->getIndexUrlParams(),
                    'form_content_block_type' => $this->getFormContentBlockType()
                ]
            ]
        );

        $this->_addContent($block);

        if ($this->allowEdit()) {
            $this->finishAction($object->getId() ? __('Edit')->render() : __('Add')->render());
        } elseif ($this->allowView()) {
            $this->finishAction(__('View')->render());
        }

        $page = $this->_view->getPage();

        $page->getConfig()->addBodyClass('infrangible-backend-widget');

        return $page;
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
            $this->getObjectRegistryKey(),
            $object && is_scalar($object->getId()) ? (string)$object->getId() : 'add'
        );
    }

    /**
     * Returns the init action layout handles.
     *
     * @return string[]
     */
    protected function getInitActionLayoutHandles(): array
    {
        $handles = parent::getInitActionLayoutHandles();

        $handles[] = 'infrangible_backendwidget_form_wysiwyg';

        return $handles;
    }

    /**
     * Initializes the object to edit or view, or redirects to the index.
     *
     * Redirects if the object was not found or may neither be edited nor viewed.
     *
     * @return AbstractModel|null
     */
    protected function initEditableObject(): ?AbstractModel
    {
        $object = $this->initObject();

        if ($object === null || ($object->getId() && ! $this->allowEdit() && ! $this->allowView())) {
            $this->redirect(
                $this->getIndexUrlRoute(),
                $this->getIndexUrlParams()
            );

            return null;
        }

        return $object;
    }

    /**
     * Initializes the object.
     *
     * @return AbstractModel|null
     */
    protected function initObject(): ?AbstractModel
    {
        $object = $this->getObjectInstance();
        $objectResource = $this->getObjectResourceInstance();

        $paramName = $this->getObjectField();

        if (empty($paramName)) {
            $paramName = 'id';
        }

        $id = $this->getRequest()->getParam($paramName);

        if ($id) {
            if ($this->initObjectWithObjectField() && ! $objectResource instanceof AbstractEntity) {
                $objectResource->load(
                    $object,
                    $id,
                    $this->getObjectField()
                );
            } else {
                $objectResource->load(
                    $object,
                    $id
                );
            }

            if (! $object->getId()) {
                $message = sprintf(
                    $this->getObjectNotFoundMessage(),
                    is_scalar($id) ? (string)$id : ''
                );

                $this->addErrorMessage($message);

                return null;
            }
        }

        $this->registryHelper->register(
            $this->getObjectRegistryKey(),
            $object
        );

        return $object;
    }

    /**
     * Initializes the object, loaded by the given object field.
     *
     * @return bool
     */
    protected function initObjectWithObjectField(): bool
    {
        return true;
    }

    /**
     * Returns the object instance.
     *
     * @return AbstractModel
     */
    protected function getObjectInstance(): AbstractModel
    {
        $modelClass = $this->getModelClass();

        if (! class_exists($modelClass)) {
            throw new \LogicException(
                sprintf(
                    'Could not find model class: %s',
                    $modelClass
                )
            );
        }

        $object = $this->instanceHelper->getInstance($modelClass);

        if (! $object instanceof AbstractModel) {
            throw new \LogicException(
                sprintf(
                    'Class must be instance of %s: %s',
                    AbstractModel::class,
                    $modelClass
                )
            );
        }

        return $object;
    }

    /**
     * Returns the object resource instance.
     *
     * @return AbstractDb|AbstractEntity
     */
    protected function getObjectResourceInstance(): AbstractDb|AbstractEntity
    {
        $modelResourceClass = $this->getModelResourceClass();

        if (! class_exists($modelResourceClass)) {
            throw new \LogicException(
                sprintf(
                    'Could not find model resource class: %s',
                    $modelResourceClass
                )
            );
        }

        $objectResource = $this->instanceHelper->getInstance($modelResourceClass);

        if (! $objectResource instanceof AbstractDb && ! $objectResource instanceof AbstractEntity) {
            throw new \LogicException(
                sprintf(
                    'Class must be instance of %s or %s: %s',
                    AbstractDb::class,
                    AbstractEntity::class,
                    $modelResourceClass
                )
            );
        }

        return $objectResource;
    }

    /**
     * Returns the object not found message.
     *
     * @return string
     */
    abstract protected function getObjectNotFoundMessage(): string;

    /**
     * Returns the form block type.
     *
     * @return string
     */
    protected function getFormBlockType(): string
    {
        return Container::class;
    }

    /**
     * Returns the form content block type.
     *
     * @return string
     */
    protected function getFormContentBlockType(): string
    {
        return sprintf(
            '%s\Block\Adminhtml\%s\%s',
            str_replace(
                '_',
                '\\',
                $this->getModuleKey()
            ),
            str_replace(
                '_',
                '\\',
                $this->getObjectName()
            ),
            $this->getFormType()
        );
    }

    /**
     * Returns the form type.
     *
     * @return string
     */
    protected function getFormType(): string
    {
        return $this->allowEdit() ? 'Form' : 'View';
    }

    /**
     * Allows the add.
     *
     * @return bool
     */
    abstract protected function allowAdd(): bool;

    /**
     * Allows the edit.
     *
     * @return bool
     */
    abstract protected function allowEdit(): bool;

    /**
     * Allows the view.
     *
     * @return bool
     */
    abstract protected function allowView(): bool;

    /**
     * Allows the delete.
     *
     * @return bool
     */
    abstract protected function allowDelete(): bool;

    /**
     * Allows the export.
     *
     * @return bool
     */
    protected function allowExport(): bool
    {
        return false;
    }

    /**
     * Returns the grid url route.
     *
     * @return string
     */
    protected function getGridUrlRoute(): string
    {
        return '*/*/grid';
    }

    /**
     * Returns the grid url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getGridUrlParams(): array
    {
        return [];
    }

    /**
     * Returns the save url route.
     *
     * @return string
     */
    protected function getSaveUrlRoute(): string
    {
        return '*/*/save';
    }

    /**
     * Returns the save url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getSaveUrlParams(): array
    {
        return [];
    }

    /**
     * Returns the index url route.
     *
     * @return string
     */
    protected function getIndexUrlRoute(): string
    {
        return '*/*/index';
    }

    /**
     * Returns the index url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getIndexUrlParams(): array
    {
        return [];
    }
}
