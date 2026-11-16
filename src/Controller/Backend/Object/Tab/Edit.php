<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object\Tab;

use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;

abstract class Edit extends \Infrangible\BackendWidget\Controller\Backend\Object\Edit
{
    /**
     * Executes the action.
     *
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $object = $this->initEditableObject();

        if ($object === null) {
            return $this->getResponse();
        }

        $blockClassName = $this->getFormContentBlockType();

        if (! is_a($blockClassName, BlockInterface::class, true)) {
            throw new \LogicException(
                sprintf(
                    'Could not find block class %s that implements %s',
                    $blockClassName,
                    BlockInterface::class
                )
            );
        }

        /** @var AbstractBlock $block */
        $block = $this->_view->getLayout()->createBlock(
            $blockClassName,
            '',
            [
                'data' => [
                    'module_key'          => $this->getModuleKey(),
                    'object_name'         => $this->getObjectName(),
                    'object_field'        => $this->getObjectField(),
                    'object_registry_key' => $this->getObjectRegistryKey(),
                    'grid_url_route'      => $this->getGridUrlRoute(),
                    'grid_url_params'     => $this->getGridUrlParams(),
                    'save_url_route'      => $this->getSaveUrlRoute(),
                    'save_url_params'     => $this->getSaveUrlParams(),
                    'allow_add'           => $this->allowAdd(),
                    'allow_edit'          => $this->allowEdit(),
                    'allow_view'          => $this->allowView(),
                    'parent_object_key'   => $this->getParentObjectKey(),
                    'parent_object_value' => $this->getRequest()->getParam($this->getParentObjectKey())
                ]
            ]
        );

        $response = $this->getResponse();

        if ($response instanceof Http) {
            $response->setBody($block->toHtml());
        }

        return $this->getResponse();
    }

    /**
     * Returns the form type.
     *
     * @return string
     */
    protected function getFormType(): string
    {
        return sprintf(
            'Tab\%s',
            parent::getFormType()
        );
    }

    /**
     * Returns the parent object key.
     *
     * @return string
     */
    abstract protected function getParentObjectKey(): string;

    /**
     * Returns the parent object value key.
     *
     * @return string
     */
    abstract protected function getParentObjectValueKey(): string;

    /**
     * Returns the grid url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getGridUrlParams(): array
    {
        $gridUrlParams = parent::getGridUrlParams();

        $gridUrlParams[ $this->getParentObjectValueKey() ] = $this->getRequest()->getParam($this->getParentObjectKey());

        return $gridUrlParams;
    }
}
