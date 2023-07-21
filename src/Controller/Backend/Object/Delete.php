<?php

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Delete
    extends Edit
{
    /** @var LoggerInterface */
    protected $logging;

    /**
     * @param Registry        $registryHelper
     * @param Context         $context
     * @param Instances       $instanceHelper
     * @param LoggerInterface $logging
     * @param Session         $session
     */
    public function __construct(
        Registry $registryHelper,
        Context $context,
        Instances $instanceHelper,
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
        if ( ! $this->allowDelete()) {
            $this->_redirect($this->getIndexUrlRoute(), $this->getIndexUrlParams());

            return;
        }

        $object = $this->initObject();

        if ( ! $object) {
            $this->_redirect($this->getIndexUrlRoute(), $this->getIndexUrlParams());

            return;
        }

        $objectResource = $this->getObjectResourceInstance();

        try {
            $this->beforeDelete($object);

            $objectResource->delete($object);

            $this->afterDelete($object);

            $this->getMessageManager()->addSuccessMessage($this->getObjectDeletedMessage());
        } catch (Exception $exception) {
            $this->logging->error($exception);

            $this->getMessageManager()->addErrorMessage($exception->getMessage());
        }

        $this->_redirect($this->getIndexUrlRoute(), $this->getIndexUrlParams());
    }

    /**
     * @param AbstractModel $object
     */
    protected function beforeDelete(AbstractModel $object)
    {
    }

    /**
     * @param AbstractModel $object
     */
    protected function afterDelete(AbstractModel $object)
    {
    }

    /**
     * @return string
     */
    abstract protected function getObjectDeletedMessage(): string;
}
