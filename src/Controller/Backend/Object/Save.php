<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Save
    extends Edit
{
    /** @var LoggerInterface */
    protected $logging;

    /**
     * @param Registry        $registryHelper
     * @param Instances       $instanceHelper
     * @param Context         $context
     * @param LoggerInterface $logging
     * @param Session         $session
     */
    public function __construct(
        Registry $registryHelper,
        Instances $instanceHelper,
        Context $context,
        LoggerInterface $logging,
        Session $session)
    {
        parent::__construct($registryHelper, $instanceHelper, $context, $session);

        $this->logging = $logging;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $object = $this->initObject();

        if ( ! $object) {
            $this->_redirect($this->getIndexUrlRoute(), $this->getIndexUrlParams());

            return;
        }

        /** @var Http $request */
        $request = $this->getRequest();

        if ( ! $request->isPost()) {
            $this->_redirect($this->getIndexUrlRoute(), $this->getIndexUrlParams());

            return;
        }

        $postData = $request->getPost();

        $formSessionKey = $this->getFormSessionKey($object);

        $this->session->setData($formSessionKey, $postData);

        $isCreate = $object->isObjectNew();

        $object->addData($request->getPost()->toArray());

        $objectResource = $this->getObjectResourceInstance();

        try {
            $this->beforeSave($object);

            $objectResource->save($object);

            $this->afterSave($object);

            if ($isCreate) {
                $this->getMessageManager()->addSuccessMessage($this->getObjectCreatedMessage());
            } else {
                $this->getMessageManager()->addSuccessMessage($this->getObjectUpdatedMessage());
            }
        } catch (Exception $exception) {
            $this->logging->error($exception);

            $this->getMessageManager()->addErrorMessage($exception->getMessage());

            if ($object->getId()) {
                $editUrlParams = $this->getEditUrlParams();

                $editUrlParams[ 'id' ] = $object->getId();
                $editUrlParams[ '_current' ] = true;

                $this->_redirect($this->getEditUrlRoute(), $editUrlParams);
            } else {
                $addUrlParams = $this->getAddUrlParams();

                $addUrlParams[ '_current' ] = true;

                $this->_redirect($this->getAddUrlRoute(), $addUrlParams);
            }

            return;
        }

        $this->session->unsetData($formSessionKey);

        if ($this->getRequest()->getParam('back', false)) {
            $editUrlParams = $this->getEditUrlParams();

            $editUrlParams[ 'id' ] = $object->getId();
            $editUrlParams[ '_current' ] = true;

            $this->_redirect($this->getEditUrlRoute(), $editUrlParams);
        } else {
            $this->_redirect($this->getIndexUrlRoute(), $this->getIndexUrlParams());
        }
    }

    /**
     * @param AbstractModel $object
     */
    protected function beforeSave(AbstractModel $object)
    {
    }

    /**
     * @param AbstractModel $object
     */
    protected function afterSave(AbstractModel $object)
    {
    }

    /**
     * @return string
     */
    abstract protected function getObjectCreatedMessage(): string;

    /**
     * @return string
     */
    abstract protected function getObjectUpdatedMessage(): string;

    /**
     * @return string
     */
    protected function getAddUrlRoute(): string
    {
        return '*/*/add';
    }

    /**
     * @return array
     */
    protected function getAddUrlParams(): array
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getEditUrlRoute(): string
    {
        return '*/*/edit';
    }

    /**
     * @return array
     */
    protected function getEditUrlParams(): array
    {
        return [];
    }
}
