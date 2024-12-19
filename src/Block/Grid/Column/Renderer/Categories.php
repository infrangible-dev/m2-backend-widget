<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Category;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Categories extends AbstractRenderer
{
    /** @var Variables */
    protected $variables;

    /** @var Category */
    protected $categoryHelper;

    public function __construct(
        Context $context,
        Variables $variables,
        Category $categoryHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );

        $this->variables = $variables;
        $this->categoryHelper = $categoryHelper;
    }

    /**
     * @throws \Exception
     */
    public function render(DataObject $row): string
    {
        $column = $this->_column;

        $categoryIds = $row->getData($column->getData('index'));

        $categoryNames = [];

        if (! $this->variables->isEmpty($categoryIds)) {
            if (! is_array($categoryIds)) {
                $categoryIds = explode(
                    ',',
                    $categoryIds
                );
            }

            foreach ($categoryIds as $categoryId) {
                $category = $this->categoryHelper->loadCategory($this->variables->intValue($categoryId));

                $categoryPathIds = $category->getPathIds();
                array_shift($categoryPathIds);
                array_pop($categoryPathIds);

                $categoryName = [];

                foreach ($categoryPathIds as $categoryPathId) {
                    $pathCategory = $this->categoryHelper->loadCategory($categoryPathId);

                    $categoryName[] = $pathCategory->getName();
                }

                $categoryName[] = $category->getName();

                $categoryNames[] = implode(
                    ' > ',
                    $categoryName
                );
            }
        }

        return implode(
            '<br />',
            $categoryNames
        );
    }
}
