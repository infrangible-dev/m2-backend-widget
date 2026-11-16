<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Adminhtml\Wysiwyg\Images;

class Content extends \Magento\Cms\Block\Adminhtml\Wysiwyg\Images\Content
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
