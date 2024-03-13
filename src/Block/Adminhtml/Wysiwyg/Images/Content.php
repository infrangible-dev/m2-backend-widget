<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Adminhtml\Wysiwyg\Images;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Content
    extends \Magento\Cms\Block\Adminhtml\Wysiwyg\Images\Content
{
    /**
     * New directory action target URL
     *
     * @return string
     */
    public function getOnInsertUrl(): string
    {
        return $this->getUrl('*/*/onInsert');
    }
}
