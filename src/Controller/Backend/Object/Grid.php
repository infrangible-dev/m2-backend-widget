<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Exception;
use Magento\Backend\Block\Widget\Grid\Extended;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Grid extends Table
{
    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $this->initAction();

        $blockClassName = $this->getGridContentBlockClass();

        if (! class_exists($blockClassName)) {
            throw new Exception(
                sprintf(
                    'Could not find block class: %s',
                    $blockClassName
                )
            );
        }

        /** @var Extended $block */
        $block = $this->_view->getLayout()->createBlock(
            $blockClassName,
            $this->getBlockName(),
            [
                'data' => $this->getGridBlockData()
            ]
        );

        $response = $this->getResponse();

        $response->setBody($this->renderBlock($block));
    }

    protected function renderBlock(Extended $block): string
    {
        return $block->toHtml();
    }

    protected function getBlockName(): string
    {
        return sprintf(
                'adminhtml_%s',
                preg_replace(
                    '~[^a-z0-9_]*~i',
                    '',
                    $this->getObjectName()
                )
            ) . '.grid';
    }
}
