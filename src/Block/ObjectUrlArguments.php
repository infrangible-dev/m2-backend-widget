<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block;

/**
 * The routes and parameters of the URLs to edit, view, delete and export objects, read from the layout arguments.
 */
trait ObjectUrlArguments
{
    /** @var string */
    protected string $editUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $editUrlParams;

    /** @var string */
    protected string $viewUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $viewUrlParams;

    /** @var string */
    protected string $deleteUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $deleteUrlParams;

    /** @var string */
    protected string $massDeleteUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $massDeleteUrlParams;

    /** @var string */
    protected string $massExportUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $massExportUrlParams;

    /**
     * Reads the routes and parameters of the URLs from the layout arguments.
     *
     * @param array $data
     *
     * @phpstan-param array<int|string, mixed> $data
     *
     * @return void
     */
    protected function initObjectUrlArguments(array $data): void
    {
        $this->editUrlRoute = $this->stringArgument($data, 'edit_url_route', '*/*/edit');
        $this->editUrlParams = $this->arrayArgument($data, 'edit_url_params');
        $this->viewUrlRoute = $this->stringArgument($data, 'view_url_route', '*/*/view');
        $this->viewUrlParams = $this->arrayArgument($data, 'view_url_params');
        $this->deleteUrlRoute = $this->stringArgument($data, 'delete_url_route', '*/*/delete');
        $this->deleteUrlParams = $this->arrayArgument($data, 'delete_url_params');
        $this->massDeleteUrlRoute = $this->stringArgument($data, 'mass_delete_url_route', '*/*/massDelete');
        $this->massDeleteUrlParams = $this->arrayArgument($data, 'mass_delete_url_params');
        $this->massExportUrlRoute = $this->stringArgument($data, 'mass_export_url_route', '*/*/massExport');
        $this->massExportUrlParams = $this->arrayArgument($data, 'mass_export_url_params');
    }
}
