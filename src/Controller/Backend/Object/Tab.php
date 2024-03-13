<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Controller\Backend\Object;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Tab
    extends Index
{
    /**
     * @return void
     */
    public function execute()
    {
        $block = $this->createBlock();

        $response = $this->getResponse();

        $response->setBody($block->toHtml());
    }
}
