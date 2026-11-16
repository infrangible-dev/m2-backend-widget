<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\BlockInterface;

abstract class Grid extends Table
{
    /**
     * Executes the action.
     *
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $this->initAction();

        $blockClassName = $this->getGridContentBlockClass();

        if (! is_a($blockClassName, BlockInterface::class, true)) {
            throw new \LogicException(
                sprintf(
                    'Could not find block class %s that implements %s',
                    $blockClassName,
                    BlockInterface::class
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

        if ($response instanceof Http) {
            $response->setBody($this->renderBlock($block));
        }

        return $this->getResponse();
    }

    /**
     * Renders the block.
     *
     * @param Extended $block
     *
     * @return string
     */
    protected function renderBlock(Extended $block): string
    {
        return $block->toHtml();
    }

    /**
     * Returns the block name.
     *
     * @return string
     */
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

    /**
     * Checks whether it is tab.
     *
     * @return bool
     */
    protected function isTab(): bool
    {
        return false;
    }
}
