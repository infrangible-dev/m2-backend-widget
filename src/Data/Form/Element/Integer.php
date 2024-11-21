<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Data\Form\Element;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Integer extends Number
{
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        SecureHtmlRenderer $secureRenderer,
        $data = []
    ) {
        parent::__construct(
            $factoryElement,
            $factoryCollection,
            $escaper,
            $secureRenderer,
            $data
        );

        $this->setData(
            'step',
            '1'
        );
        $this->setData(
            'pattern',
            '^[0-9]+$'
        );
    }
}
