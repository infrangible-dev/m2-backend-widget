<?php

namespace Infrangible\BackendWidget\Block\Config\Form;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use Magento\Framework\Data\Form\Element\Date;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class DateIso
    extends Date
{
    /**
     * Set date value
     *
     * @param mixed $value
     *
     * @return $this
     * @throws Exception
     */
    public function setValue($value): DateIso
    {
        if (empty($value)) {
            $this->_value = '';

            return $this;
        }

        if ($value instanceof DateTimeInterface) {
            $this->_value = $value;

            return $this;
        }

        try {
            if (preg_match('/^[0-9]+$/', $value)) {
                $this->_value = (new DateTime())->setTimestamp($this->_toTimestamp($value));
            } else if (is_string($value) && $this->isDate($value)) {
                $this->_value = new DateTime($value, new DateTimeZone('UTC'));
            } else {
                $this->_value = '';
            }
        } catch (Exception $exception) {
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
     * Get date value as string.
     * Format can be specified, or it will be taken from $this->getFormat()
     *
     * @param string $format (compatible with Zend_Date)
     *
     * @return string
     */
    public function getValue($format = null): string
    {
        if (empty($this->_value)) {
            return '';
        }

        if (null === $format) {
            $format = $this->getData('format');
        }

        return IntlDateFormatter::formatObject($this->localeDate->date($this->_value), $format);
    }
}
