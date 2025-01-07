<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object\Tab;

use Magento\Framework\View\Element\AbstractBlock;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Edit extends \Infrangible\BackendWidget\Controller\Backend\Object\Edit
{
    public function execute(): void
    {
        $object = $this->initObject();

        if (! $object) {
            $this->redirect(
                $this->getIndexUrlRoute(),
                $this->getIndexUrlParams()
            );

            return;
        }

        if ($object->getId() && ! $this->allowEdit() && ! $this->allowView()) {
            $this->redirect(
                $this->getIndexUrlRoute(),
                $this->getIndexUrlParams()
            );

            return;
        }

        $blockClassName = $this->getFormContentBlockType();

        if (! class_exists($blockClassName)) {
            throw new \Exception(
                sprintf(
                    'Could not find block class: %s',
                    $blockClassName
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

        $response->setBody($block->toHtml());
    }

    protected function getFormType(): string
    {
        return sprintf(
            'Tab\%s',
            parent::getFormType()
        );
    }

    abstract protected function getParentObjectKey(): string;

    abstract protected function getParentObjectValueKey(): string;

    protected function getGridUrlParams(): array
    {
        $gridUrlParams = parent::getGridUrlParams();

        $gridUrlParams[ $this->getParentObjectValueKey() ] = $this->getRequest()->getParam($this->getParentObjectKey());

        return $gridUrlParams;
    }
}
