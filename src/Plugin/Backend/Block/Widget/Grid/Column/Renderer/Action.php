<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Plugin\Backend\Block\Widget\Grid\Column\Renderer;

use FeWeDev\Base\Arrays;

class Action
{
    /**
     * @param Arrays $arrays
     */
    public function __construct(protected Arrays $arrays)
    {
    }

    /**
     * Flattens associative arrays in the URL parameters of grid action links.
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action $subject
     * @param mixed $route
     * @param mixed $params
     *
     * @noinspection PhpUnusedParameterInspection
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     * @noinspection PhpDeprecationInspection The plugin extends the action column of Magento's legacy grid.
     */
    public function beforeGetUrl(
        \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action $subject,
        mixed $route = '',
        mixed $params = []
    ): array {
        if (! is_array($params)) {
            return [$route, $params];
        }

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
