<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Model\Backend;

use Magento\Framework\Session\Storage;

class Session extends \Magento\Backend\Model\Session
{
    /**
     * Unset data from the object.
     *
     * @param null|string|array $key
     *
     * @phpstan-param null|string|array<int|string, mixed> $key
     *
     * @return Session
     */
    public function unsetData(mixed $key = null): Session
    {
        if ($this->storage instanceof Storage) {
            $this->storage->unsetData($key);
        }

        return $this;
    }

    /**
     * Overwrite data in the object.
     *
     * The $key parameter can be string or array.
     * If $key is string, the attribute value will be overwritten by $value
     *
     * If $key is an array, it will overwrite all the data in the object.
     *
     * @param string|array $key
     * @param mixed        $value
     *
     * @phpstan-param string|array<int|string, mixed> $key
     *
     * @return $this
     */
    public function setData(mixed $key, mixed $value = null): Session
    {
        if ($this->storage instanceof Storage) {
            $this->storage->setData($key, $value);
        }

        return $this;
    }
}
