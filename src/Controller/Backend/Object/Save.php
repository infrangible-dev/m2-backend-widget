<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Exception;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

abstract class Save extends Edit
{
    /**
     * @param Registry $registryHelper
     * @param Instances $instanceHelper
     * @param Context $context
     * @param LoggerInterface $logging
     * @param Session $session
     */
    public function __construct(
        Registry $registryHelper,
        Instances $instanceHelper,
        Context $context,
        protected LoggerInterface $logging,
        Session $session
    ) {
        parent::__construct(
            $registryHelper,
            $instanceHelper,
            $context,
            $session
        );
    }

    /**
     * Executes the action.
     *
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $object = $this->initObject();

        if (! $object) {
            $this->redirect(
                $this->getRedirectUrlRoute(),
                $this->getRedirectUrlParams()
            );

            $this->prepareResponse();

            return $this->getResponse();
        }

        /** @var Http $request */
        $request = $this->getRequest();

        if (! $request->isPost()) {
            $this->redirect(
                $this->getRedirectUrlRoute(),
                $this->getRedirectUrlParams()
            );

            $this->prepareResponse();

            return $this->getResponse();
        }

        $postData = $request->getPostValue();

        if (! is_array($postData)) {
            $postData = [];
        }

        $formSessionKey = $this->getFormSessionKey($object);

        $this->session->setData(
            $formSessionKey,
            $postData
        );

        $isCreate = $object->isObjectNew();

        $object->addData($postData);

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

            return $this->getResponse();
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

        return $this->getResponse();
    }

    /**
     * Returns the redirect url route.
     *
     * @return string
     */
    protected function getRedirectUrlRoute(): string
    {
        return $this->getIndexUrlRoute();
    }

    /**
     * Returns the redirect url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getRedirectUrlParams(): array
    {
        return $this->getIndexUrlParams();
    }

    /**
     * Hook that runs before the object is saved.
     *
     * @param AbstractModel $object
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- hook
    protected function beforeSave(AbstractModel $object): void
    {
    }

    /**
     * Hook that runs after the object was saved.
     *
     * @param AbstractModel $object
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- hook
    protected function afterSave(AbstractModel $object): void
    {
    }

    /**
     * Returns the object created message.
     *
     * @return string
     */
    abstract protected function getObjectCreatedMessage(): string;

    /**
     * Returns the object updated message.
     *
     * @return string
     */
    abstract protected function getObjectUpdatedMessage(): string;

    /**
     * Returns the add url route.
     *
     * @return string
     */
    protected function getAddUrlRoute(): string
    {
        return '*/*/add';
    }

    /**
     * Returns the add url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getAddUrlParams(): array
    {
        return [];
    }

    /**
     * Returns the edit url route.
     *
     * @return string
     */
    protected function getEditUrlRoute(): string
    {
        return '*/*/edit';
    }

    /**
     * Returns the edit url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getEditUrlParams(): array
    {
        return [];
    }
}
