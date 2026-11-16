<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Infrangible\BackendWidget\Block\Grid\Container;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\Page;

abstract class Index extends Table
{
    /**
     * Executes the action.
     *
     * @return Page
     */
    public function execute(): Page
    {
        $this->initAction();

        $block = $this->createBlock();

        $this->_addContent($block);

        $this->finishAction(__('Manage')->render());

        $page = $this->_view->getPage();

        $page->getConfig()->addBodyClass('infrangible-backend-widget');

        return $page;
    }

    /**
     * Creates the block.
     *
     * @return AbstractBlock
     */
    protected function createBlock(): AbstractBlock
    {
        /** @var AbstractBlock $block */
        $block = $this->_view->getLayout()->createBlock(
            $this->getGridBlockType(),
            '',
            [
                'data' => $this->getGridBlockData()
            ]
        );

        return $block;
    }

    /**
     * Returns the grid block type.
     *
     * @return string
     */
    protected function getGridBlockType(): string
    {
        return Container::class;
    }

    /**
     * Returns the grid block data.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getGridBlockData(): array
    {
        return array_merge(
            parent::getGridBlockData(),
            [
                'grid_content_block_class_name' => $this->getGridContentBlockClass(),
                'title'                         => $this->getTitle(),
                'allow_add'                     => $this->allowAdd(),
                'add_url_route'                 => $this->getAddUrlRoute(),
                'add_url_params'                => $this->getAddUrlParams(),
                'back_url_route'                => $this->getBackUrlRoute(),
                'back_url_params'               => $this->getBackUrlParams()
            ]
        );
    }

    /**
     * Allows the add.
     *
     * @return bool
     */
    abstract protected function allowAdd(): bool;

    /**
     * Returns the add url route.
     *
     * @return string
     */
    protected function getAddUrlRoute(): string
    {
        return '*/*/add';
    }

    /**
     * Returns the add url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getAddUrlParams(): array
    {
        return [];
    }

    /**
     * Returns the back url route.
     *
     * @return string|null
     */
    protected function getBackUrlRoute(): ?string
    {
        return null;
    }

    /**
     * Returns the back url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getBackUrlParams(): array
    {
        return [];
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
