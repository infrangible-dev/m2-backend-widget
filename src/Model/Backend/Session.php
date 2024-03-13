<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Model\Backend;

use Magento\Framework\Session\Storage;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Session
    extends \Magento\Backend\Model\Session
{
    /**
     * Unset data from the object.
     *
     * @param null|string|array $key
     *
     * @return Session
     */
    public function unsetData($key = null): Session
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
     * @return $this
     */
    public function setData($key, $value = null): Session
    {
        if ($this->storage instanceof Storage) {
            $this->storage->setData($key, $value);
        }

        return $this;
    }
}
