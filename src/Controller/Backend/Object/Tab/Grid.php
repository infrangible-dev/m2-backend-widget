<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object\Tab;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Grid extends \Infrangible\BackendWidget\Controller\Backend\Object\Grid
{
    protected function getGridBlockData(): array
    {
        return array_merge(
            parent::getGridBlockData(),
            [
                'allow_add'           => $this->allowAdd(),
                'add_url_route'       => $this->getAddUrlRoute(),
                'add_url_params'      => $this->getAddUrlParams(),
                'parent_object_key'   => $this->getParentObjectKey(),
                'parent_object_value' => $this->getRequest()->getParam($this->getParentObjectValueKey())
            ]
        );
    }

    abstract protected function allowAdd(): bool;

    protected function allowMassDelete(): bool
    {
        return false;
    }

    protected function addRowAction(): bool
    {
        return false;
    }

    protected function getAddUrlRoute(): string
    {
        return '*/*/add';
    }

    protected function getAddUrlParams(): array
    {
        return [$this->getParentObjectKey() => $this->getRequest()->getParam($this->getParentObjectValueKey())];
    }

    protected function getEditUrlParams(): array
    {
        $editUrlParams = parent::getEditUrlParams();

        $editUrlParams[ $this->getParentObjectKey() ] = $this->getRequest()->getParam($this->getParentObjectValueKey());

        return $editUrlParams;
    }

    protected function getViewUrlParams(): array
    {
        $viewUrlParams = parent::getViewUrlParams();

        $viewUrlParams[ $this->getParentObjectKey() ] = $this->getRequest()->getParam($this->getParentObjectValueKey());

        return $viewUrlParams;
    }

    protected function getGridUrlParams(): array
    {
        $gridUrlParams = parent::getGridUrlParams();

        $gridUrlParams[ $this->getParentObjectKey() ] = $this->getRequest()->getParam($this->getParentObjectValueKey());

        return $gridUrlParams;
    }

    protected function getDeleteUrlParams(): array
    {
        $deleteUrlParams = parent::getDeleteUrlParams();

        $deleteUrlParams[ $this->getParentObjectKey() ] =
            $this->getRequest()->getParam($this->getParentObjectValueKey());

        return $deleteUrlParams;
    }

    abstract protected function getParentObjectKey(): string;

    abstract protected function getParentObjectValueKey(): string;

    protected function showFiltersButton(): bool
    {
        return false;
    }

    protected function showColumnsButton(): bool
    {
        return false;
    }

    protected function getGridType(): string
    {
        return sprintf(
            'Tab\%s',
            parent::getGridType()
        );
    }

    protected function isTab(): bool
    {
        return true;
    }
}
