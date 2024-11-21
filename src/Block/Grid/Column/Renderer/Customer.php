<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Customer extends AbstractRenderer
{
    /**
     * @throws \Exception
     */
    public function render(DataObject $row): string
    {
        return sprintf(
            '%s %s',
            $row->getData('firstname'),
            $row->getData('lastname')
        );
    }
}
