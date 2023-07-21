<?php /** @noinspection PhpDeprecationInspection */

namespace Infrangible\BackendWidget\Block;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Registry;
use Tofex\Help\Arrays;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2023 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Form
    extends Generic
{
    /** @var \Infrangible\Core\Helper\Registry */
    protected $registryHelper;

    /** @var \Infrangible\BackendWidget\Helper\Form */
    protected $formHelper;

    /** @var string */
    protected $moduleKey;

    /** @var string */
    protected $objectName;

    /** @var string */
    protected $objectField;

    /** @var string */
    protected $objectRegistryKey;

    /** @var string */
    protected $saveUrlRoute;

    /** @var array */
    protected $saveUrlParams;

    /** @var bool */
    protected $allowAdd = true;

    /** @var bool */
    protected $allowEdit = true;

    /** @var bool */
    protected $allowView = false;

    /** @var AbstractModel */
    private $object;

    /** @var bool */
    private $readOnlyAll = false;

    /** @var bool */
    private $disableAll = false;

    /**
     * @param Context                                $context
     * @param Registry                               $registry
     * @param FormFactory                            $formFactory
     * @param Arrays                                 $arrayHelper
     * @param \Infrangible\Core\Helper\Registry      $registryHelper
     * @param \Infrangible\BackendWidget\Helper\Form $formHelper
     * @param array                                  $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Arrays $arrayHelper,
        \Infrangible\Core\Helper\Registry $registryHelper,
        \Infrangible\BackendWidget\Helper\Form $formHelper,
        array $data = [])
    {
        $this->moduleKey = $arrayHelper->getValue($data, 'module_key', 'adminhtml');
        $this->objectName = $arrayHelper->getValue($data, 'object_name', 'empty');
        $this->objectField = $arrayHelper->getValue($data, 'object_field', 'id');
        $this->objectRegistryKey = $arrayHelper->getValue($data, 'object_registry_key');
        $this->saveUrlRoute = $arrayHelper->getValue($data, 'save_url_route', '*/*/save');
        $this->saveUrlParams = $arrayHelper->getValue($data, 'save_url_params', []);
        $this->allowAdd = $arrayHelper->getValue($data, 'allow_add', true);
        $this->allowEdit = $arrayHelper->getValue($data, 'allow_edit', true);
        $this->allowView = $arrayHelper->getValue($data, 'allow_view', false);

        $this->registryHelper = $registryHelper;
        $this->formHelper = $formHelper;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return AbstractModel
     */
    protected function getObject(): AbstractModel
    {
        if ($this->object === null) {
            $this->object = $this->registryHelper->registry($this->objectRegistryKey);
        }

        return $this->object;
    }

    /**
     * @return bool
     */
    protected function isReadOnlyAll(): bool
    {
        return $this->readOnlyAll;
    }

    /**
     * @param bool $readOnlyAll
     */
    protected function setReadOnlyAll(bool $readOnlyAll): void
    {
        $this->readOnlyAll = $readOnlyAll;
    }

    /**
     * @return bool
     */
    protected function isDisableAll(): bool
    {
        return $this->disableAll;
    }

    /**
     * @param bool $disableAll
     */
    protected function setDisableAll(bool $disableAll): void
    {
        $this->disableAll = $disableAll;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return \Magento\Backend\Block\Widget\Form
     * @throws LocalizedException
     */
    protected function _prepareForm(): \Magento\Backend\Block\Widget\Form
    {
        $form = $this->createForm();

        $this->prepareFields($form);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @param \Magento\Framework\Data\Form $form
     */
    abstract protected function prepareFields(\Magento\Framework\Data\Form $form);

    /**
     * @return \Magento\Framework\Data\Form
     * @throws LocalizedException
     */
    protected function createForm(): \Magento\Framework\Data\Form
    {
        return $this->formHelper->createPostForm($this->allowAdd || $this->allowEdit ? $this->saveUrlRoute : '',
            $this->allowAdd || $this->allowEdit ? $this->saveUrlParams : [], $this->isUploadForm(), 'edit_form',
            preg_replace('/[^a-z0-9_]*/i', '', $this->objectName), $this->getObject(), $this->getObjectField());
    }

    /**
     * @return bool
     */
    protected function isUploadForm(): bool
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function getObjectField(): ?string
    {
        return $this->objectField;
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addTextField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addTextField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $after
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addTextFieldAfter(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $after,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addTextFieldAfter($fieldSet, $this->objectRegistryKey, $objectFieldName, $label, $after,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addTextareaField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addTextareaField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $comment
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addTextareaWithCommentField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $comment,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addTextareaWithCommentField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $comment, $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param array    $options
     * @param mixed    $defaultValue
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addOptionsField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        $defaultValue,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addOptionsField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label, $options,
            $defaultValue, $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $className
     * @param mixed    $defaultValue
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     *
     * @throws Exception
     */
    protected function addOptionsClassField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $className,
        $defaultValue,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addOptionsClassField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $className, $defaultValue, $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectFieldName
     * @param string             $label
     * @param array              $options
     * @param mixed              $defaultValue
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addOptionsMultiSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        $defaultValue,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addOptionsMultiSelectField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $options, $defaultValue, $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addYesNoField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addYesNoField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $after
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addYesNoFieldAfter(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $after,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addYesNoFieldAfter($fieldSet, $this->objectRegistryKey, $objectFieldName, $label, $after,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param mixed    $defaultValue
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addYesNoWithDefaultField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $defaultValue,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addYesNoWithDefaultField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $defaultValue, $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    protected function addWebsiteSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addWebsiteSelectField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $this->isReadOnlyAll() ? true : $readOnly, $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    protected function addWebsiteMultiselectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addWebsiteMultiselectField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $this->isReadOnlyAll() ? true : $readOnly, $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $readOnly
     * @param bool        $disabled
     * @param bool        $all
     */
    protected function addStoreSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        bool $readOnly = false,
        bool $disabled = false,
        bool $all = true)
    {
        try {
            $this->formHelper->addStoreSelectField($this->getLayout(), $fieldSet, $this->objectRegistryKey,
                $objectFieldName, $label, $this->getObject(), $this->isReadOnlyAll() ? true : $readOnly,
                $this->isDisableAll() ? true : $disabled, $all);
        } catch (LocalizedException $exception) {
            $this->_logger->error($exception);
        }
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    protected function addStoreMultiselectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        bool $readOnly = false,
        bool $disabled = false)
    {
        try {
            $this->formHelper->addStoreMultiselectField($this->getLayout(), $fieldSet, $this->objectRegistryKey,
                $objectFieldName, $label, $this->getObject(), $this->isReadOnlyAll() ? true : $readOnly,
                $this->isDisableAll() ? true : $disabled);
        } catch (LocalizedException $exception) {
            $this->_logger->error($exception);
        }
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $required
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    protected function addStoreWithAdminSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        bool $required = true,
        bool $readOnly = false,
        bool $disabled = false)
    {
        try {
            $this->formHelper->addStoreWithAdminSelectField($this->getLayout(), $fieldSet, $this->objectRegistryKey,
                $objectFieldName, $label, $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
                $this->isDisableAll() ? true : $disabled);
        } catch (LocalizedException $exception) {
            $this->_logger->error($exception);
        }
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param null        $defaultValue
     * @param bool        $required
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    protected function addCmsBlockSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        $defaultValue = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addCmsBlockSelectField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $defaultValue, $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param null        $defaultValue
     * @param bool        $required
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    protected function addCmsPageSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        $defaultValue = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addCmsPageSelectField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $defaultValue, $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string      $label
     * @param string|null $defaultValue
     * @param bool        $required
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    protected function addTypeIdField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $defaultValue = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addTypeIdField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label, $defaultValue,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addTemplateField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addTemplateField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    public function addCategoriesField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addCategoriesField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addOperatorField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addOperatorField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addDateIsoField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = true,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addDateIsoField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     */
    protected function addFileField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = true)
    {
        $this->formHelper->addFileField($fieldSet, $objectFieldName, $label, $required);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addCountryField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addCountryField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    protected function addRegionField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addRegionField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     */
    protected function addImageField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false)
    {
        $this->formHelper->addImageField($fieldSet, $objectFieldName, $label, $this->getObject(), $required);
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $required
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    public function addCustomerGroupField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addCustomerGroupField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $required
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    public function addCustomerGroupMultiSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addCustomerGroupMultiSelectField($fieldSet, $this->objectRegistryKey, $objectFieldName,
            $label, $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $required
     * @param bool        $readOnly
     * @param bool        $disabled
     * @param bool        $allStores
     * @param bool        $withDefault
     *
     * @throws LocalizedException
     */
    public function addPaymentActiveMethodsField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool $allStores = false,
        bool $withDefault = true)
    {
        $this->formHelper->addPaymentActiveMethodsField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled, $allStores, $withDefault);
    }

    /**
     * @param Fieldset    $fieldSet
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $required
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    public function addProductTypeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addProductTypeField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     */
    protected function addWysiwygField(Fieldset $fieldSet, string $objectFieldName, string $label)
    {
        $this->formHelper->addWysiwygField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject());
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     */
    protected function addEditorField(Fieldset $fieldSet, string $objectFieldName, string $label)
    {
        $this->formHelper->addEditorField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject());
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $customer
     * @param bool     $address
     * @param bool     $category
     * @param bool     $product
     */
    protected function addEavAttributeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true)
    {
        $this->formHelper->addEavAttributeField($this->getObject(), $fieldSet, $this->objectRegistryKey,
            $objectFieldName, $label, $required, $customer, $address, $category, $product);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param array    $targetFieldNames
     * @param bool     $required
     * @param bool     $multiSelect
     */
    protected function addEavAttributeFieldWithUpdate(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $multiSelect = false)
    {
        $this->formHelper->addEavAttributeFieldWithUpdate($this->getObject(), $this->objectName, $fieldSet,
            $this->objectRegistryKey, $objectFieldName, $label, $targetFieldNames, $required, $multiSelect);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectAttributeFieldName
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $multiSelect
     *
     * @throws Exception
     */
    protected function addEavAttributeValueField(
        Fieldset $fieldSet,
        string $objectAttributeFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $multiSelect = false)
    {
        $this->formHelper->addEavAttributeValueField($this->getObject(), $fieldSet, $this->objectRegistryKey,
            $objectAttributeFieldName, $objectFieldName, $label, $required, $multiSelect);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $customer
     * @param bool     $address
     * @param bool     $category
     * @param bool     $product
     */
    protected function addEavAttributeSetField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true)
    {
        $this->formHelper->addEavAttributeSetField($this->getObject(), $fieldSet, $this->objectRegistryKey,
            $objectFieldName, $label, $required, $customer, $address, $category, $product);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $customer
     * @param bool     $address
     * @param bool     $category
     * @param bool     $product
     */
    protected function addEavEntityTypeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true)
    {
        $this->formHelper->addEavEntityTypeField($this->getObject(), $fieldSet, $this->objectRegistryKey,
            $objectFieldName, $label, $required, $customer, $address, $category, $product);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     */
    public function addProductAttributeCodeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false)
    {
        $this->formHelper->addProductAttributeCodeField($this->getObject(), $fieldSet, $this->objectRegistryKey,
            $objectFieldName, $label, $required);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     */
    public function addCustomerAttributeCodeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false)
    {
        $this->formHelper->addCustomerAttributeCodeField($this->getObject(), $fieldSet, $this->objectRegistryKey,
            $objectFieldName, $label, $required);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     */
    public function addAddressAttributeCodeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false)
    {
        $this->formHelper->addAddressAttributeCodeField($this->getObject(), $fieldSet, $this->objectRegistryKey,
            $objectFieldName, $label, $required);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $multiSelect
     */
    public function addAttributeSortByField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $multiSelect = false)
    {
        $this->formHelper->addAttributeSortByField($this->getObject(), $fieldSet, $this->objectRegistryKey,
            $objectFieldName, $label, $required, $multiSelect);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param mixed    $value
     * @param bool     $disabled
     */
    protected function addCheckboxField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $value,
        bool $disabled = false)
    {
        $this->formHelper->addCheckboxField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label, $value,
            $this->getObject(), $this->isDisableAll() ? true : $disabled);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     */
    protected function addValueField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label)
    {
        $this->formHelper->addValueField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject());
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $value
     * @param mixed    $onClick
     * @param mixed    $dataMageInit
     */
    protected function addButtonField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $value,
        $onClick = null,
        $dataMageInit = null)
    {
        $this->formHelper->addButtonField($fieldSet, $objectFieldName, $label, $value, $onClick, $dataMageInit);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $value
     * @param string   $urlPath
     * @param array    $urlParameters
     */
    protected function addIframeButtonField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $value,
        string $urlPath,
        array $urlParameters = [])
    {
        $this->formHelper->addIframeButtonField($fieldSet, $this->objectName, $this->objectField, $objectFieldName,
            $label, $value, $urlPath, $urlParameters, $this->getObject());
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     * @param bool     $readOnly
     * @param bool     $disabled
     */
    public function addThemeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false)
    {
        $this->formHelper->addThemeField($fieldSet, $this->objectRegistryKey, $objectFieldName, $label,
            $this->getObject(), $required, $readOnly, $disabled);
    }
}
