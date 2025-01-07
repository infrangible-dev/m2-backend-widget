<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Exception;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Delete extends Edit
{
    /** @var LoggerInterface */
    protected $logging;

    public function __construct(
        Registry $registryHelper,
        Context $context,
        Instances $instanceHelper,
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
     * @throws Exception
     */
    public function execute(): void
    {
        if (! $this->allowDelete()) {
            $this->redirect(
                $this->getRedirectUrlRoute(),
                $this->getRedirectUrlParams()
            );

            $this->prepareResponse();

            return;
        }

        $object = $this->initObject();

        if (! $object) {
            $this->redirect(
                $this->getRedirectUrlRoute(),
                $this->getRedirectUrlParams()
            );

            $this->prepareResponse();

            return;
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
    }

    protected function getRedirectUrlRoute(): string
    {
        return $this->getIndexUrlRoute();
    }

    protected function getRedirectUrlParams(): array
    {
        return $this->getIndexUrlParams();
    }

    protected function beforeDelete(AbstractModel $object)
    {
    }

    protected function afterDelete(AbstractModel $object)
    {
    }

    abstract protected function getObjectDeletedMessage(): string;
}
