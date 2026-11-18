<?php /** @noinspection PhpDeprecationInspection Magento's controller, the parent class, needs the registry. */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Controller\Adminhtml\Wysiwyg\Images;

use Infrangible\Core\Helper\Stores;
use Magento\Backend\App\Action\Context;
use Magento\Cms\Helper\Wysiwyg\Images;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;

class OnInsert extends \Magento\Cms\Controller\Adminhtml\Wysiwyg\Images\OnInsert
{
    /**
     * @param Stores     $storeHelper
     * @param Images     $imagesHelper
     * @param Context    $context
     * @param Registry   $coreRegistry
     * @param RawFactory $resultRawFactory
     */
    public function __construct(
        protected Stores $storeHelper,
        protected Images $imagesHelper,
        Context $context,
        Registry $coreRegistry,
        RawFactory $resultRawFactory
    ) {
        parent::__construct($context, $coreRegistry, $resultRawFactory);
    }

    /**
     * Fire when select image
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $filename = $this->getRequest()->getParam('filename');

        $filename = $this->imagesHelper->idDecode(is_string($filename) ? $filename : '');

        $fileUrl = $this->imagesHelper->getCurrentUrl() . $filename;

        $mediaPath = str_replace($this->storeHelper->getMediaUrl(), '', $fileUrl);

        $resultRaw = $this->resultRawFactory->create();

        return $resultRaw->setContents($mediaPath);
    }
}
