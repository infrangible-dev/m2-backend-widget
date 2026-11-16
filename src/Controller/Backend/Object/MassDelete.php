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

abstract class MassDelete extends Edit
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
        parent::__construct($registryHelper, $instanceHelper, $context, $session);
    }

    /**
     * Executes the action.
     *
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $paramName = $this->getObjectField();

        if (empty($paramName)) {
            $paramName = 'id';
        }

        $ids = $this->getRequest()->getParam($paramName);

        if (is_string($ids) && $ids !== '') {
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

        return $this->getResponse();
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
     * Returns the objects deleted message.
     *
     * @return string
     */
    abstract protected function getObjectsDeletedMessage(): string;
}
