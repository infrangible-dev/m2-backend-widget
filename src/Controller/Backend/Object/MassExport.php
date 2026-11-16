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
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

abstract class MassExport extends Edit
{
    /**
     * @param Registry $registryHelper
     * @param Instances $instanceHelper
     * @param Context $context
     * @param Session $session
     * @param LoggerInterface $logging
     * @param RawFactory $rawFactory
     */
    public function __construct(
        Registry $registryHelper,
        Instances $instanceHelper,
        Context $context,
        Session $session,
        protected LoggerInterface $logging,
        protected RawFactory $rawFactory
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
     * @return Raw|ResponseInterface
     * @throws LocalizedException
     */
    public function execute(): Raw|ResponseInterface
    {
        $paramName = $this->getObjectField();

        if (empty($paramName)) {
            $paramName = 'id';
        }

        $ids = $this->getRequest()->getParam($paramName);

        if (is_array($ids)) {
            $ids = array_unique($ids);

            // phpcs:ignore Magento2.Functions.DiscouragedFunction -- in-memory stream, not a file
            $dataSink = fopen(
                'php://temp',
                'w+'
            );

            if ($dataSink === false) {
                throw new \RuntimeException('Could not open temporary stream for the export');
            }

            $header = $this->getExportHeader();
            if (count($header) > 0) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction -- in-memory stream, not a file
                fputcsv(
                    $dataSink,
                    $header
                );
            }

            $fields = $this->getExportFields();

            try {
                foreach ($ids as $id) {
                    $object = $this->getObjectInstance();
                    $objectResource = $this->getObjectResourceInstance();

                    if ($this->initObjectWithObjectField()) {
                        $objectResource->load(
                            $object,
                            $id,
                            $this->getObjectField()
                        );
                    } else {
                        $objectResource->load(
                            $object,
                            $id
                        );
                    }

                    if ($object->getId() == $id) {
                        $this->beforeExport($object);

                        $data = [];
                        foreach ($fields as $key) {
                            $value = $object->getData($key);

                            $data[] = $value === null || is_scalar($value) ? $value : (string)json_encode($value);
                        }
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- in-memory stream, not a file
                        fputcsv(
                            $dataSink,
                            $data
                        );

                        $this->afterExport($object);
                    }
                }

                // transfer data
                $rawData = $this->rawFactory->create();
                rewind($dataSink);
                // phpcs:ignore Magento2.Functions.DiscouragedFunction -- in-memory stream, not a file
                $rawData->setContents((string)stream_get_contents($dataSink));
                $rawData->setHeader(
                    'Content-Type:',
                    'text/csv; charset=UTF-8',
                    true
                );
                // phpcs:ignore Magento2.Functions.DiscouragedFunction -- in-memory stream, not a file
                fclose($dataSink);

                return $rawData;
            } catch (Exception $exception) {
                $this->addErrorMessage($exception->getMessage());
                $this->logging->error($exception);
            }
        } else {
            $this->addErrorMessage(__('Please select at least one item.')->render());
        }

        $this->redirect(
            $this->getIndexUrlRoute(),
            $this->getIndexUrlParams()
        );

        return $this->getResponse();
    }

    /**
     * Hook to change the data of the object before it is exported.
     *
     * @param AbstractModel $object
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- hook
    protected function beforeExport(AbstractModel $object): void
    {
    }

    /**
     * Hook to change the object after it was exported, for example to set an export status.
     *
     * If the object was changed in beforeExport(), it should be restored, for example with
     * $object->setData($object->getOrigData()).
     *
     * @param AbstractModel $object
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- hook
    protected function afterExport(AbstractModel $object): void
    {
    }

    /**
     * Returns the fields to export, by default all fields of the database table.
     *
     * @return array
     * @throws LocalizedException
     * @phpstan-return array<int, string>
     */
    protected function getExportFields(): array
    {
        /** @noinspection PhpDeprecationInspection */
        $item = $this->getObjectInstance()->getCollection()->fetchItem();

        $data = $item instanceof DataObject ? $item->getData() : [];

        return is_array($data) ? array_map('strval', array_keys($data)) : [];
    }

    /**
     * Returns the header of the export, which should match the export fields and can be translated.
     *
     * @return array
     * @throws LocalizedException
     * @phpstan-return array<int, string>
     */
    protected function getExportHeader(): array
    {
        // base implementation: raw database fields
        return $this->getExportFields();
    }
}
