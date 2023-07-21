<?php

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\Page;
use Infrangible\BackendWidget\Block\Form\Container;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Edit
    extends Base
{
    /** @var Registry */
    protected $registryHelper;

    /** @var Instances */
    protected $instanceHelper;

    /** @var Session */
    protected $session;

    /**
     * @param Registry  $registryHelper
     * @param Instances $instanceHelper
     * @param Context   $context
     * @param Session   $session
     */
    public function __construct(
        Registry $registryHelper,
        Instances $instanceHelper,
        Context $context,
        Session $session)
    {
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

        if ( ! $object) {
            $this->_redirect($this->getIndexUrlRoute(), $this->getIndexUrlParams());

            return;
        }

        if ($object->getId() && ! $this->allowEdit() && ! $this->allowView()) {
            $this->_redirect($this->getIndexUrlRoute(), $this->getIndexUrlParams());

            return;
        }

        $this->initAction();

        /** @var AbstractBlock $block */
        $block = $this->_view->getLayout()->createBlock($this->getFormBlockType(), '', [
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
                'save_url_route'          => $this->getSaveUrlRoute(),
                'save_url_params'         => $this->getSaveUrlParams(),
                'delete_url_route'        => $this->getDeleteUrlRoute(),
                'delete_url_params'       => $this->getDeleteUrlParams(),
                'index_url_route'         => $this->getIndexUrlRoute(),
                'index_url_params'        => $this->getIndexUrlParams(),
                'form_content_block_type' => $this->getFormContentBlockType()
            ]
        ]);

        $this->_addContent($block);

        if ($this->allowEdit()) {
            $this->finishAction($object->getId() ? __('Edit') : __('Add'));
        } else if ($this->allowView()) {
            $this->finishAction(__('View'));
        }

        $page = $this->_view->getPage();

        $page->getConfig()->addBodyClass('infrangible-backend-widget');

        return $page;
    }

    /**
     * @param AbstractModel|null $object
     *
     * @return string
     */
    protected function getFormSessionKey(AbstractModel $object = null): string
    {
        return sprintf('%s_form_%s', $this->getObjectRegistryKey(),
            $object && $object->getId() ? $object->getId() : 'add');
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
     * @return AbstractModel|null
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
                $objectResource->load($object, $id, $this->getObjectField());
            } else {
                $objectResource->load($object, $id);
            }

            if ( ! $object->getId()) {
                $message = sprintf($this->getObjectNotFoundMessage(), $id);

                $this->getMessageManager()->addErrorMessage($message);

                return null;
            }
        }

        $this->registryHelper->register($this->getObjectRegistryKey(), $object);

        return $object;
    }

    /**
     * @return bool
     */
    protected function initObjectWithObjectField(): bool
    {
        return true;
    }

    /**
     * @return AbstractModel
     * @throws Exception
     */
    protected function getObjectInstance(): AbstractModel
    {
        $modelClass = $this->getModelClass();

        if ( ! class_exists($modelClass)) {
            throw new Exception(sprintf('Could not find model class: %s', $modelClass));
        }

        $object = $this->instanceHelper->getInstance($modelClass);

        if ( ! $object instanceof AbstractModel) {
            throw new Exception(sprintf('Class muss be instance of Magento\Framework\Model\AbstractModel: %s',
                $modelClass));
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

        if ( ! class_exists($modelResourceClass)) {
            throw new Exception(sprintf('Could not find model resource class: %s', $modelResourceClass));
        }

        $objectResource = $this->instanceHelper->getInstance($modelResourceClass);

        if ( ! $objectResource instanceof AbstractDb && ! $objectResource instanceof AbstractEntity) {
            throw new Exception(sprintf('Class muss be instance of Magento\Framework\Model\ResourceModel\Db\AbstractModel or Magento\Eav\Model\Entity\AbstractEntity: %s',
                $modelResourceClass));
        }

        return $objectResource;
    }

    /**
     * @return string
     */
    abstract protected function getObjectNotFoundMessage(): string;

    /**
     * @return string
     */
    protected function getFormBlockType(): string
    {
        return Container::class;
    }

    /**
     * @return string|null
     */
    protected function getFormContentBlockType(): ?string
    {
        return null;
    }

    /**
     * @return bool
     */
    abstract protected function allowAdd(): bool;

    /**
     * @return bool
     */
    abstract protected function allowEdit(): bool;

    /**
     * @return bool
     */
    abstract protected function allowView(): bool;

    /**
     * @return bool
     */
    abstract protected function allowDelete(): bool;

    /**
     * @return bool
     */
    protected function allowExport(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getSaveUrlRoute(): string
    {
        return '*/*/save';
    }

    /**
     * @return array
     */
    protected function getSaveUrlParams(): array
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getIndexUrlRoute(): string
    {
        return '*/*/index';
    }

    /**
     * @return array
     */
    protected function getIndexUrlParams(): array
    {
        return [];
    }
}
