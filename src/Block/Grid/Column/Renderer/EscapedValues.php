<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

/**
 * Helps grid column renderers to output the values of a row as escaped HTML. Renderers return HTML, so every value
 * that comes from the database has to be escaped.
 */
trait EscapedValues
{
    /**
     * Returns the index of the column, which is the prefix of the row data the renderer shows.
     *
     * @return string
     */
    protected function getColumnIndex(): string
    {
        $index = $this->getColumn()->getData('index');

        return is_scalar($index) ? (string)$index : '';
    }

    /**
     * Returns the value as escaped HTML, an empty string if it is no scalar value.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function escapeValue(mixed $value): string
    {
        $escapedValue = $this->_escaper->escapeHtml(is_scalar($value) ? (string)$value : '');

        return is_string($escapedValue) ? $escapedValue : '';
    }

    /**
     * Returns the values as escaped HTML, one per line.
     *
     * @param array $values
     *
     * @phpstan-param array<int, mixed> $values
     *
     * @return string
     */
    protected function escapeValues(array $values): string
    {
        return implode(
            '<br />',
            array_map(
                [$this, 'escapeValue'],
                $values
            )
        );
    }

    /**
     * Returns the list of ids in the value, which can be an array or a comma-separated list.
     *
     * @param mixed $value
     *
     * @return array
     * @phpstan-return array<int, int>
     */
    protected function getIds(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(
                ',',
                $value
            );
        } elseif (is_int($value)) {
            $value = [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $id) {
            if (is_int($id) || (is_string($id) && ctype_digit(trim($id)))) {
                $ids[] = (int)$id;
            }
        }

        return $ids;
    }
}
