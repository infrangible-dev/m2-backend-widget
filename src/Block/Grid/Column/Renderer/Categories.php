<?php

namespace Infrangible\BackendWidget\Block\Grid\Column\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Infrangible\Core\Helper\Category;
use Tofex\Help\Variables;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Categories
    extends AbstractRenderer
{
    /** @var Variables */
    protected $variableHelper;

    /** @var Category */
    protected $categoryHelper;

    /**
     * @param Context   $context
     * @param Variables $variableHelper
     * @param Category  $categoryHelper
     * @param array     $data
     */
    public function __construct(
        Context $context,
        Variables $variableHelper,
        Category $categoryHelper,
        array $data = [])
    {
        parent::__construct($context, $data);

        $this->variableHelper = $variableHelper;
        $this->categoryHelper = $categoryHelper;
    }

    /**
     * @param DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row): string
    {
        $column = $this->_column;

        $categoryIds = $row->getData($column->getData('index'));

        $categoryNames = [];

        if ( ! $this->variableHelper->isEmpty($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);

            foreach ($categoryIds as $categoryId) {
                $category = $this->categoryHelper->loadCategory($categoryId);

                $categoryPathIds = $category->getPathIds();
                array_shift($categoryPathIds);
                array_pop($categoryPathIds);

                $categoryName = [];

                foreach ($categoryPathIds as $categoryPathId) {
                    $pathCategory = $this->categoryHelper->loadCategory($categoryPathId);

                    $categoryName[] = $pathCategory->getName();
                }

                $categoryName[] = $category->getName();

                $categoryNames[] = implode(' > ', $categoryName);
            }
        }

        return implode('<br />', $categoryNames);
    }
}
