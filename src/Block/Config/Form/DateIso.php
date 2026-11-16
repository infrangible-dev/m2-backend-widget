<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Config\Form;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use Magento\Framework\Data\Form\Element\Date;

class DateIso extends Date
{
    /**
     * Set date value
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue(mixed $value): DateIso
    {
        if (empty($value)) {
            // @phpstan-ignore assign.propertyType (Magento's date element uses an empty string for no value)
            $this->_value = '';

            return $this;
        }

        if ($value instanceof DateTimeInterface) {
            $this->_value = DateTime::createFromInterface($value);

            return $this;
        }

        try {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $this->_value = (new DateTime())->setTimestamp($this->_toTimestamp((int)$value));
            } elseif (is_string($value) && $this->isDate($value)) {
                $this->_value = new DateTime($value, new DateTimeZone('UTC'));
            } else {
                // @phpstan-ignore assign.propertyType (Magento's date element uses an empty string for no value)
                $this->_value = '';
            }
        } catch (Exception) {
            // @phpstan-ignore assign.propertyType (Magento's date element uses an empty string for no value)
            $this->_value = '';
        }

        return $this;
    }

    /**
     * Check if a string is a date value
     *
     * @param string $value
     *
     * @return bool
     */
    private function isDate(string $value): bool
    {
        $date = date_parse($value);

        return ! empty($date[ 'year' ]) && ! empty($date[ 'month' ]) && ! empty($date[ 'day' ]);
    }

    /**
     * Returns the date value as string.
     *
     * The format can be given, otherwise the format of the element is used.
     *
     * @param string $format (compatible with Zend_Date)
     *
     * @return string
     */
    public function getValue(mixed $format = null): string
    {
        if (! $this->_value instanceof DateTimeInterface) {
            return '';
        }

        $format ??= $this->getData('format');

        return (string)IntlDateFormatter::formatObject(
            $this->localeDate->date($this->_value),
            is_string($format) || is_int($format) ? $format : null
        );
    }
}
