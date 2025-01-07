<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Exception;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Save extends Edit
{
    /** @var LoggerInterface */
    protected $logging;

    public function __construct(
        Registry $registryHelper,
        Instances $instanceHelper,
        Context $context,
        LoggerInterface $logging,
        Session $session
    ) {
        parent::__construct(
            $registryHelper,
            $instanceHelper,
            $context,
            $session
        );

        $this->logging = $logging;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $object = $this->initObject();

        if (! $object) {
            $this->redirect(
                $this->getRedirectUrlRoute(),
                $this->getRedirectUrlParams()
            );

            $this->prepareResponse();

            return;
        }

        /** @var Http $request */
        $request = $this->getRequest();

        if (! $request->isPost()) {
            $this->redirect(
                $this->getRedirectUrlRoute(),
                $this->getRedirectUrlParams()
            );

            $this->prepareResponse();

            return;
        }

        $postData = $request->getPost();

        $formSessionKey = $this->getFormSessionKey($object);

        $this->session->setData(
            $formSessionKey,
            $postData
        );

        $isCreate = $object->isObjectNew();

        $object->addData($request->getPost()->toArray());

        $objectResource = $this->getObjectResourceInstance();

        try {
            $this->beforeSave($object);

            $objectResource->save($object);

            $this->afterSave($object);

            if ($isCreate) {
                $this->addSuccessMessage($this->getObjectCreatedMessage());
            } else {
                $this->addSuccessMessage($this->getObjectUpdatedMessage());
            }
        } catch (Exception $exception) {
            $this->logging->error($exception);

            $this->addErrorMessage($exception->getMessage());

            if ($object->getId()) {
                $editUrlParams = $this->getEditUrlParams();

                $editUrlParams[ 'id' ] = $object->getId();
                $editUrlParams[ '_current' ] = true;

                $this->redirect(
                    $this->getEditUrlRoute(),
                    $editUrlParams
                );
            } else {
                $addUrlParams = $this->getAddUrlParams();

                $addUrlParams[ '_current' ] = true;

                $this->redirect(
                    $this->getAddUrlRoute(),
                    $addUrlParams
                );
            }

            $this->prepareResponse();

            return;
        }

        $this->session->unsetData($formSessionKey);

        if ($this->getRequest()->getParam(
            'back',
            false
        )) {
            $editUrlParams = $this->getEditUrlParams();

            $editUrlParams[ 'id' ] = $object->getId();
            $editUrlParams[ '_current' ] = true;

            $this->redirect(
                $this->getEditUrlRoute(),
                $editUrlParams
            );
        } else {
            $this->redirect(
                $this->getRedirectUrlRoute(),
                $this->getRedirectUrlParams()
            );
        }

        $this->prepareResponse();
    }

    protected function getRedirectUrlRoute(): string
    {
        return $this->getIndexUrlRoute();
    }

    protected function getRedirectUrlParams(): array
    {
        return $this->getIndexUrlParams();
    }

    protected function beforeSave(AbstractModel $object)
    {
    }

    protected function afterSave(AbstractModel $object)
    {
    }

    abstract protected function getObjectCreatedMessage(): string;

    abstract protected function getObjectUpdatedMessage(): string;

    protected function getAddUrlRoute(): string
    {
        return '*/*/add';
    }

    protected function getAddUrlParams(): array
    {
        return [];
    }

    protected function getEditUrlRoute(): string
    {
        return '*/*/edit';
    }

    protected function getEditUrlParams(): array
    {
        return [];
    }
}
