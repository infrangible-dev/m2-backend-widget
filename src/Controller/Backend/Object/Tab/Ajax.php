<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object\Tab;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
trait Ajax
{
    /** @var int */
    private $responseCode = 200;

    /** @var array */
    private $responseValues = [];

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    public function getResponseValues(): array
    {
        return $this->responseValues;
    }

    protected function getResponseValue(string $key)
    {
        if (isset($this->responseValues[ $key ])) {
            return $this->responseValues[ $key ];
        }

        return null;
    }

    public function setResponseValues(array $responseValues): void
    {
        $this->responseValues = $responseValues;
    }

    protected function addResponseValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->addResponseValue(
                $key,
                $value
            );
        }
    }

    protected function addResponseValue(string $key, $value): void
    {
        $this->responseValues[ $key ] = $value;
    }

    protected function resetResponseValues()
    {
        $this->responseValues = [];
    }
}
