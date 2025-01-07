<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Plugin\Backend\Block\Widget\Grid\Column\Renderer;

use FeWeDev\Base\Arrays;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Action
{
    /** @var Arrays */
    protected $arrays;

    public function __construct(Arrays $arrays)
    {
        $this->arrays = $arrays;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function beforeGetUrl(
        \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action $subject,
        $route = '',
        $params = []
    ): array {
        foreach ($params as $key => $value) {
            if (is_array($value) && $this->arrays->isAssociative($value)) {
                unset($params[ $key ]);

                foreach ($value as $valueKey => $valueValue) {
                    $params[ $valueKey ] = $valueValue;
                }
            }
        }

        return [$route, $params];
    }
}
