<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Category;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Categories extends AbstractRenderer
{
    use EscapedValues;

    /**
     * @param Context $context
     * @param Variables $variables
     * @param Category $categoryHelper
     * @param array $data
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        protected Variables $variables,
        protected Category $categoryHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Renders.
     *
     * @param DataObject $row
     *
     * @return string
     * @throws Exception
     */
    public function render(DataObject $row): string
    {
        $categoryNames = [];

        foreach ($this->getIds($row->getData($this->getColumnIndex())) as $categoryId) {
            $category = $this->categoryHelper->loadCategory($categoryId);

            $categoryPathIds = $category->getPathIds();
            array_shift($categoryPathIds);
            array_pop($categoryPathIds);

            $categoryName = [];

            foreach ($categoryPathIds as $categoryPathId) {
                $pathCategory = $this->categoryHelper->loadCategory($this->variables->intValue($categoryPathId));

                $categoryName[] = $this->variables->stringValue($pathCategory->getName());
            }

            $categoryName[] = $this->variables->stringValue($category->getName());

            $categoryNames[] = implode(
                ' > ',
                $categoryName
            );
        }

        return $this->escapeValues($categoryNames);
    }
}
