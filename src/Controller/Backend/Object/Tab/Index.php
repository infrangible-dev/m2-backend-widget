<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object\Tab;

use Magento\Backend\Block\Widget\Grid\Extended;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Index extends Grid
{
    protected function renderBlock(Extended $block): string
    {
        return sprintf(
            '<div id="tab_%s"><div data-role="messages"></div><div>%s</div></div>',
            $this->getObjectName(),
            $block->toHtml()
        );
    }
}
