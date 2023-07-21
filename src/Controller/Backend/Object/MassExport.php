<?php

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Registry;

/**
 * @author      Philipp Adler
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class MassExport
    extends Edit
{
    /** @var LoggerInterface */
    protected $logging;

    /** @var RawFactory */
    protected $rawFactory;

    /**
     * @param Registry        $registryHelper
     * @param Instances       $instanceHelper
     * @param Context         $context
     * @param Session         $session
     * @param LoggerInterface $logging
     * @param RawFactory      $rawFactory
     */
    public function __construct(
        Registry $registryHelper,
        Instances $instanceHelper,
        Context $context,
        Session $session,
        LoggerInterface $logging,
        RawFactory $rawFactory)
    {
        parent::__construct($registryHelper, $instanceHelper, $context, $session);

        $this->logging = $logging;
        $this->rawFactory = $rawFactory;
    }

    /**
     * @return Raw|void
     * @throws Exception
     */
    public function execute()
    {
        $paramName = $this->getObjectField();

        if (empty($paramName)) {
            $paramName = 'id';
        }

        $ids = $this->getRequest()->getParam($paramName);

        if (is_array($ids)) {
            $ids = array_unique($ids);

            $dataSink = fopen('php://temp', 'w+');

            $header = $this->getExportHeader();
            if (count($header) > 0) {
                fputcsv($dataSink, $header);
            }

            $fields = $this->getExportFields();

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
                        $this->beforeExport($object);

                        $data = [];
                        foreach ($fields as $key) {
                            $data[] = $object->getData($key);
                        }
                        fputcsv($dataSink, $data);

                        $this->afterExport($object);
                    }
                }

                // transfer data
                $rawData = $this->rawFactory->create();
                rewind($dataSink);
                $rawData->setContents(stream_get_contents($dataSink));
                $rawData->setHeader('Content-Type:', 'text/csv; charset=UTF-8', true);
                fclose($dataSink);

                return $rawData;
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
                $this->logging->error($exception);
            }
        } else {
            $this->messageManager->addErrorMessage(__('Please select at least one item.'));
        }

        $this->_redirect($this->getIndexUrlRoute(), $this->getIndexUrlParams());
    }

    /**
     * modify object fields/data before export
     *
     * @param AbstractModel $object
     */
    protected function beforeExport(AbstractModel $object)
    {
    }

    /**
     * modify object after export (ie set export status)
     * if object was changed in beforeExport() it should be restored (ie $object->setData($object->getOrigData()))
     *
     * @param AbstractModel $object
     */
    protected function afterExport(AbstractModel $object)
    {
    }

    /**
     * list of export fields, used to get data via getData
     *
     * @return array
     * @throws Exception
     */
    protected function getExportFields(): array
    {
        // base implementation: raw database fields
        /** @noinspection PhpDeprecationInspection */
        return array_keys($this->getObjectInstance()->getCollection()->fetchItem()->getData());
    }

    /**
     * list of header fields (should match export fields, can be used for translations)
     *
     * @return array
     * @throws Exception
     */
    protected function getExportHeader(): array
    {
        // base implementation: raw database fields
        return $this->getExportFields();
    }
}
