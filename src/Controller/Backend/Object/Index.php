<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object;

use Infrangible\BackendWidget\Block\Grid\Container;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\Page;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Index extends Table
{
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

    protected function getGridBlockType(): string
    {
        return Container::class;
    }

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

    abstract protected function allowAdd(): bool;

    protected function getAddUrlRoute(): string
    {
        return '*/*/add';
    }

    protected function getAddUrlParams(): array
    {
        return [];
    }

    protected function getBackUrlRoute(): ?string
    {
        return null;
    }

    protected function getBackUrlParams(): array
    {
        return [];
    }

    protected function isTab(): bool
    {
        return false;
    }
}
