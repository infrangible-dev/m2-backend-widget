<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Grid;

use Exception;
use FeWeDev\Base\Arrays;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Grid;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Container
    extends Grid\Container
{
    /** @var string */
    protected $moduleKey;

    /** @var string */
    protected $objectName;

    /** @var string */
    protected $objectField;

    /** @var bool */
    protected $allowAdd = true;

    /** @var bool */
    protected $allowEdit = true;

    /** @var bool */
    protected $allowView = true;

    /** @var bool */
    protected $allowDelete = true;

    /** @var bool */
    protected $allowExport = true;

    /** @var string */
    protected $modelClass;

    /** @var string */
    protected $addUrlRoute;

    /** @var array */
    protected $addUrlParams;

    /** @var string */
    protected $gridUrlRoute;

    /** @var array */
    protected $gridUrlParams;

    /** @var string */
    protected $editUrlRoute;

    /** @var array */
    protected $editUrlParams;

    /** @var string */
    protected $viewUrlRoute;

    /** @var array */
    protected $viewUrlParams;

    /** @var string */
    protected $deleteUrlRoute;

    /** @var array */
    protected $deleteUrlParams;

    /** @var string */
    protected $massDeleteUrlRoute;

    /** @var array */
    protected $massDeleteUrlParams;

    /** @var string */
    protected $massExportUrlRoute;

    /** @var array */
    protected $massExportUrlParams;

    /** @var string */
    protected $backUrlRoute;

    /** @var array */
    protected $backUrlParams;

    /**
     * @param Context $context
     * @param Arrays  $arrays
     * @param array   $data
     */
    public function __construct(Context $context, Arrays $arrays, array $data = [])
    {
        $this->moduleKey = $arrays->getValue($data, 'module_key', 'adminhtml');
        $this->objectName = $arrays->getValue($data, 'object_name', 'empty');
        $this->objectField = $arrays->getValue($data, 'object_field', 'id');
        $this->allowAdd = $arrays->getValue($data, 'allow_add', true);
        $this->allowEdit = $arrays->getValue($data, 'allow_edit', true);
        $this->allowView = $arrays->getValue($data, 'allow_view', true);
        $this->allowDelete = $arrays->getValue($data, 'allow_delete', true);
        $this->allowExport = $arrays->getValue($data, 'allow_export', true);
        $this->modelClass = $arrays->getValue($data, 'model_class', true);
        $this->addUrlRoute = $arrays->getValue($data, 'add_url_route', '*/*/add');
        $this->addUrlParams = $arrays->getValue($data, 'add_url_params', []);
        $this->gridUrlRoute = $arrays->getValue($data, 'grid_url_route', '*/*/grid');
        $this->gridUrlParams = $arrays->getValue($data, 'grid_url_params', []);
        $this->editUrlRoute = $arrays->getValue($data, 'edit_url_route', '*/*/edit');
        $this->editUrlParams = $arrays->getValue($data, 'edit_url_params', []);
        $this->viewUrlRoute = $arrays->getValue($data, 'view_url_route', '*/*/view');
        $this->viewUrlParams = $arrays->getValue($data, 'view_url_params', []);
        $this->deleteUrlRoute = $arrays->getValue($data, 'delete_url_route', '*/*/delete');
        $this->deleteUrlParams = $arrays->getValue($data, 'delete_url_params', []);
        $this->massDeleteUrlRoute = $arrays->getValue($data, 'mass_delete_url_route', '*/*/massDelete');
        $this->massDeleteUrlParams = $arrays->getValue($data, 'mass_delete_url_params', []);
        $this->massExportUrlRoute = $arrays->getValue($data, 'mass_export_url_route', '*/*/massExport');
        $this->massExportUrlParams = $arrays->getValue($data, 'mass_export_url_params', []);
        $this->backUrlRoute = $arrays->getValue($data, 'back_url_route');
        $this->backUrlParams = $arrays->getValue($data, 'back_url_params', []);

        $this->_blockGroup = $this->moduleKey;
        $this->_controller = sprintf('Adminhtml_%s', $this->objectName);
        $this->_headerText =
            sprintf('%s > %s', $arrays->getValue($data, 'title', 'Container Widget Header'), __('Manage'));

        parent::__construct($context, $data);

        if (!$this->allowAdd) {
            $this->removeButton('add');
        }

        if (!empty($this->backUrlRoute)) {
            $this->_addBackButton();
        }
    }

    /**
     * @return \Magento\Backend\Block\Widget\Container
     * @throws Exception
     */
    protected function _prepareLayout(): \Magento\Backend\Block\Widget\Container
    {
        $blockClassName = sprintf(
            '%s\Block\%s\Grid',
            str_replace('_', '\\', $this->_blockGroup),
            str_replace('_', '\\', $this->_controller)
        );

        if (!class_exists($blockClassName)) {
            throw new Exception(sprintf('Could not find block class: %s', $blockClassName));
        }

        $block = $this->getLayout()->createBlock($blockClassName, $this->_controller.'.grid', [
            'data' => [
                'module_key'             => $this->moduleKey,
                'object_name'            => $this->objectName,
                'object_field'           => $this->objectField,
                'allow_edit'             => $this->allowEdit,
                'allow_view'             => $this->allowView,
                'allow_delete'           => $this->allowDelete,
                'allow_export'           => $this->allowExport,
                'model_class'            => $this->modelClass,
                'grid_url_route'         => $this->gridUrlRoute,
                'grid_url_params'        => $this->gridUrlParams,
                'edit_url_route'         => $this->editUrlRoute,
                'edit_url_params'        => $this->editUrlParams,
                'view_url_route'         => $this->viewUrlRoute,
                'view_url_params'        => $this->viewUrlParams,
                'delete_url_route'       => $this->deleteUrlRoute,
                'delete_url_params'      => $this->deleteUrlParams,
                'mass_delete_url_route'  => $this->massDeleteUrlRoute,
                'mass_delete_url_params' => $this->massDeleteUrlParams,
                'mass_export_url_route'  => $this->massExportUrlRoute,
                'mass_export_url_params' => $this->massExportUrlParams,
            ]
        ]);

        if ($block instanceof AbstractBlock) {
            if ($block instanceof Grid) {
                $block->setSaveParametersInSession(true);
            }

            $this->setChild('grid', $block);
        }

        return \Magento\Backend\Block\Widget\Container::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getCreateUrl(): string
    {
        return $this->getUrl($this->addUrlRoute, $this->addUrlParams);
    }

    /**
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl($this->backUrlRoute, $this->backUrlParams);
    }
}
