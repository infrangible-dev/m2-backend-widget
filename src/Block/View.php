<?php

namespace Infrangible\BackendWidget\Block;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class View
    extends Form
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setReadOnlyAll(true);
    }
}
