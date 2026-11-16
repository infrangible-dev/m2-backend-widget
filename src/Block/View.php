<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block;

abstract class View extends Form
{
    /**
     * Constructor.
     *
     * @return void
     */
    protected function _construct(): void
    {
        parent::_construct();

        $this->setReadOnlyAll(true);
    }
}
