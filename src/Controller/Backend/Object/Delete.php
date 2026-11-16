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
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

abstract class Delete extends Edit
{
    /**
     * @param Registry $registryHelper
     * @param Context $context
     * @param Instances $instanceHelper
     * @param LoggerInterface $logging
     * @param Session $session
     */
    public function __construct(
        Registry $registryHelper,
        Context $context,
        Instances $instanceHelper,
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
        if (! $this->allowDelete()) {
            $this->redirect(
                $this->getRedirectUrlRoute(),
                $this->getRedirectUrlParams()
            );

            $this->prepareResponse();

            return $this->getResponse();
        }

        $object = $this->initObject();

        if (! $object) {
            $this->redirect(
                $this->getRedirectUrlRoute(),
                $this->getRedirectUrlParams()
            );

            $this->prepareResponse();

            return $this->getResponse();
        }

        $objectResource = $this->getObjectResourceInstance();

        try {
            $this->beforeDelete($object);

            $objectResource->delete($object);

            $this->afterDelete($object);

            $this->addSuccessMessage($this->getObjectDeletedMessage());
        } catch (Exception $exception) {
            $this->logging->error($exception);

            $this->addErrorMessage($exception->getMessage());
        }

        $this->redirect(
            $this->getRedirectUrlRoute(),
            $this->getRedirectUrlParams()
        );

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
     * Hook that runs before the object is deleted.
     *
     * @param AbstractModel $object
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- hook
    protected function beforeDelete(AbstractModel $object): void
    {
    }

    /**
     * Hook that runs after the object was deleted.
     *
     * @param AbstractModel $object
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- hook
    protected function afterDelete(AbstractModel $object): void
    {
    }

    /**
     * Returns the object deleted message.
     *
     * @return string
     */
    abstract protected function getObjectDeletedMessage(): string;
}
