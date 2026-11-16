<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

// phpcs:ignore Magento2.NamingConvention.ReservedWords -- kept for the modules that extend these controllers
namespace Infrangible\BackendWidget\Controller\Backend\Object\Tab;

trait Ajax
{
    /** @var int */
    private int $responseCode = 200;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    private array $responseValues = [];

    /**
     * Returns the response code.
     *
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * Sets the response code.
     *
     * @param int $responseCode
     *
     * @return void
     */
    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    /**
     * Returns the response values.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function getResponseValues(): array
    {
        return $this->responseValues;
    }

    /**
     * Returns the response value.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function getResponseValue(string $key): mixed
    {
        return $this->responseValues[ $key ] ?? null;
    }

    /**
     * Sets the response values.
     *
     * @param array $responseValues
     *
     * @phpstan-param array<int|string, mixed> $responseValues
     *
     * @return void
     */
    public function setResponseValues(array $responseValues): void
    {
        $this->responseValues = $responseValues;
    }

    /**
     * Adds the response values.
     *
     * @param array $values
     *
     * @phpstan-param array<int|string, mixed> $values
     *
     * @return void
     */
    protected function addResponseValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->addResponseValue(
                (string)$key,
                $value
            );
        }
    }

    /**
     * Adds the response value.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    protected function addResponseValue(string $key, mixed $value): void
    {
        $this->responseValues[ $key ] = $value;
    }

    /**
     * Resets the response values.
     *
     * @return void
     */
    protected function resetResponseValues(): void
    {
        $this->responseValues = [];
    }

    /**
     * Adds the success message.
     *
     * @param string $message
     *
     * @return void
     */
    protected function addSuccessMessage(string $message): void
    {
        $this->addResponseValue(
            'message',
            $message
        );
    }

    /**
     * Adds the error message.
     *
     * @param string $message
     *
     * @return void
     */
    protected function addErrorMessage(string $message): void
    {
        $this->setResponseCode(500);
        $this->addResponseValue(
            'message',
            $message
        );
    }

    /**
     * Redirects.
     *
     * @param string $path
     * @param array $arguments
     *
     * @phpstan-param array<int|string, mixed> $arguments
     *
     * @return void
     */
    protected function redirect(string $path, array $arguments): void
    {
        if ($this->getResponseCode() === 200) {
            $this->setResponseCode(302);
        }

        $this->addResponseValue(
            'location',
            $this->getUrl(
                $path,
                $arguments
            )
        );
    }

    /**
     * Prepares the response.
     *
     * @return void
     */
    protected function prepareResponse(): void
    {
        $this->actionHelper->processResponse(
            $this->getResponseCode(),
            $this->getResponseValues()
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

    /**
     * Returns the redirect url route.
     *
     * @return string
     */
    protected function getRedirectUrlRoute(): string
    {
        return $this->getGridUrlRoute();
    }

    /**
     * Returns the redirect url params.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getRedirectUrlParams(): array
    {
        return $this->getGridUrlParams();
    }
}
