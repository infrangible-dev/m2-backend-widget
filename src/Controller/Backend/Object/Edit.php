<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Exception;
use Infrangible\BackendWidget\Block\Form\Container;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\App\Action\Context;
use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\Page;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Edit extends Base
{
    /** @var Registry */
    protected $registryHelper;

    /** @var Instances */
    protected $instanceHelper;

    /** @var Session */
    protected $session;

    public function __construct(
        Registry $registryHelper,
        Instances $instanceHelper,
        Context $context,
        Session $session
    ) {
        parent::__construct($context);

        $this->registryHelper = $registryHelper;
        $this->instanceHelper = $instanceHelper;

        $this->session = $session;
    }

    /**
     * @return Page|void
     * @throws Exception
     */
    public function execute()
    {
        $object = $this->initObject();

        if (! $object) {
            $this->redirect(
                $this->getIndexUrlRoute(),
                $this->getIndexUrlParams()
            );

            return;
        }

        if ($object->getId() && ! $this->allowEdit() && ! $this->allowView()) {
            $this->redirect(
                $this->getIndexUrlRoute(),
                $this->getIndexUrlParams()
            );

            return;
        }

        $this->initAction();

        /** @var AbstractBlock $block */
        $block = $this->_view->getLayout()->createBlock(
            $this->getFormBlockType(),
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

    protected function getFormSessionKey(?AbstractModel $object = null): string
    {
        return sprintf(
            '%s_form_%s',
            $this->getObjectRegistryKey(),
            $object && $object->getId() ? $object->getId() : 'add'
        );
    }

    /**
     * @return string[]
     */
    protected function getInitActionLayoutHandles(): array
    {
        $handles = parent::getInitActionLayoutHandles();

        $handles[] = 'infrangible_backendwidget_form_wysiwyg';

        return $handles;
    }

    /**
     * @throws Exception
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
                    $id
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

    protected function initObjectWithObjectField(): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    protected function getObjectInstance(): AbstractModel
    {
        $modelClass = $this->getModelClass();

        if (! class_exists($modelClass)) {
            throw new Exception(
                sprintf(
                    'Could not find model class: %s',
                    $modelClass
                )
            );
        }

        $object = $this->instanceHelper->getInstance($modelClass);

        if (! $object instanceof AbstractModel) {
            throw new Exception(
                sprintf(
                    'Class muss be instance of Magento\Framework\Model\AbstractModel: %s',
                    $modelClass
                )
            );
        }

        return $object;
    }

    /**
     * @return AbstractDb|AbstractEntity
     * @throws Exception
     */
    protected function getObjectResourceInstance()
    {
        $modelResourceClass = $this->getModelResourceClass();

        if (! class_exists($modelResourceClass)) {
            throw new Exception(
                sprintf(
                    'Could not find model resource class: %s',
                    $modelResourceClass
                )
            );
        }

        $objectResource = $this->instanceHelper->getInstance($modelResourceClass);

        if (! $objectResource instanceof AbstractDb && ! $objectResource instanceof AbstractEntity) {
            throw new Exception(
                sprintf(
                    'Class muss be instance of Magento\Framework\Model\ResourceModel\Db\AbstractModel or Magento\Eav\Model\Entity\AbstractEntity: %s',
                    $modelResourceClass
                )
            );
        }

        return $objectResource;
    }

    abstract protected function getObjectNotFoundMessage(): string;

    protected function getFormBlockType(): string
    {
        return Container::class;
    }

    protected function getFormContentBlockType(): ?string
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

    protected function getFormType(): string
    {
        return $this->allowEdit() ? 'Form' : 'View';
    }

    abstract protected function allowAdd(): bool;

    abstract protected function allowEdit(): bool;

    abstract protected function allowView(): bool;

    abstract protected function allowDelete(): bool;

    protected function allowExport(): bool
    {
        return false;
    }

    protected function getGridUrlRoute(): string
    {
        return '*/*/grid';
    }

    protected function getGridUrlParams(): array
    {
        return [];
    }

    protected function getSaveUrlRoute(): string
    {
        return '*/*/save';
    }

    protected function getSaveUrlParams(): array
    {
        return [];
    }

    protected function getIndexUrlRoute(): string
    {
        return '*/*/index';
    }

    protected function getIndexUrlParams(): array
    {
        return [];
    }
}
