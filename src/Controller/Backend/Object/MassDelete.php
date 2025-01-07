<?php

declare(strict_types=1);

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
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class MassDelete
    extends Edit
{
    /** @var LoggerInterface */
    protected $logging;

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

    public function execute(): void
    {
        $paramName = $this->getObjectField();

        if (empty($paramName)) {
            $paramName = 'id';
        }

        $ids = $this->getRequest()->getParam($paramName);

        if ( ! is_array($ids) && $ids !== null && $ids !== '') {
            $ids = explode(',', $ids);
        }

        if (is_array($ids)) {
            $ids = array_unique($ids);

            $counter = 0;

            try {
                foreach ($ids as $id) {
                    $object = $this->getObjectInstance();
                    $objectResource = $this->getObjectResourceInstance();

                    if ($this->initObjectWithObjectField()) {
                        $objectResource->load($object, $id, $this->getObjectField());
                    } else {
                        $objectResource->load($object, $id);
                    }

                    if ($object->getId() == $id) {
                        $this->beforeDelete($object);

                        $objectResource->delete($object);

                        $this->afterDelete($object);

                        $counter++;
                    }
                }

                $this->addSuccessMessage(sprintf($this->getObjectsDeletedMessage(), $counter));
            } catch (Exception $exception) {
                $this->addErrorMessage($exception->getMessage());

                $this->logging->error($exception);
            }
        } else {
            $this->addErrorMessage(__('Please select at least one item.')->render());
        }

        $this->redirect($this->getIndexUrlRoute(), $this->getIndexUrlParams());
    }

    protected function beforeDelete(AbstractModel $object)
    {
    }

    /**
     * @throws Exception
     */
    protected function afterDelete(AbstractModel $object)
    {
    }

    abstract protected function getObjectsDeletedMessage(): string;
}
