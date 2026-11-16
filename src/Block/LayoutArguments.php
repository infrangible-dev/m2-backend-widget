<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block;

/**
 * Reads the arguments that the layout passes to a block in its data array and converts them to the type of the
 * property they are stored in. Keys are compared case-insensitively like in FeWeDev\Base\Arrays::getValue().
 */
trait LayoutArguments
{
    /**
     * Returns the argument as string, or the default value if it is missing or null.
     *
     * @param array  $data
     * @param string $key
     * @param string $defaultValue
     *
     * @phpstan-param array<int|string, mixed> $data
     *
     * @return string
     */
    protected function stringArgument(array $data, string $key, string $defaultValue = ''): string
    {
        $value = $this->argument($data, $key);

        return $value === null ? $defaultValue : $this->scalarString($value, $key);
    }

    /**
     * Returns the argument as string, or null if it is missing.
     *
     * @param array  $data
     * @param string $key
     *
     * @phpstan-param array<int|string, mixed> $data
     *
     * @return string|null
     */
    protected function nullableStringArgument(array $data, string $key): ?string
    {
        $value = $this->argument($data, $key);

        return $value === null ? null : $this->scalarString($value, $key);
    }

    /**
     * Returns the argument as boolean.
     *
     * The layout passes booleans of xsi:type="string" arguments as "1", "true", "0" or "false".
     *
     * @param array  $data
     * @param string $key
     * @param bool   $defaultValue
     *
     * @phpstan-param array<int|string, mixed> $data
     *
     * @return bool
     */
    protected function boolArgument(array $data, string $key, bool $defaultValue = false): bool
    {
        $value = $this->argument($data, $key);

        if ($value === null) {
            return $defaultValue;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return !in_array(strtolower(trim($value)), ['', '0', 'false', 'no'], true);
        }

        return (bool)$value;
    }

    /**
     * Returns the argument as array, or the default value if it is missing or null.
     *
     * @param array  $data
     * @param string $key
     * @param array  $defaultValue
     *
     * @phpstan-param array<int|string, mixed> $data
     * @phpstan-param array<int|string, mixed> $defaultValue
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function arrayArgument(array $data, string $key, array $defaultValue = []): array
    {
        $value = $this->argument($data, $key);

        if ($value === null) {
            return $defaultValue;
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException(sprintf('Layout argument "%s" has to be an array', $key));
        }

        return $value;
    }

    /**
     * Returns the value of the argument, or null if it is missing.
     *
     * @param array  $data
     * @param string $key
     *
     * @phpstan-param array<int|string, mixed> $data
     *
     * @return mixed
     */
    private function argument(array $data, string $key): mixed
    {
        foreach ($data as $dataKey => $value) {
            if (strcasecmp((string)$dataKey, $key) === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Converts a scalar argument value to string.
     *
     * @param mixed  $value
     * @param string $key
     *
     * @return string
     */
    private function scalarString(mixed $value, string $key): string
    {
        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string)$value;
        }

        throw new \InvalidArgumentException(sprintf('Layout argument "%s" has to be a string', $key));
    }
}
