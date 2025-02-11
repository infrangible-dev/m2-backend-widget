<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block;

use Exception;
use FeWeDev\Base\Arrays;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Registry;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Form extends Generic
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
    protected $gridUrlRoute;

    /** @var array */
    protected $gridUrlParams;

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

    /** @var string */
    protected $editFormId;

    /** @var AbstractModel */
    private $object;

    /** @var bool */
    private $readOnlyAll = false;

    /** @var bool */
    private $disableAll = false;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Arrays $arrays,
        \Infrangible\Core\Helper\Registry $registryHelper,
        \Infrangible\BackendWidget\Helper\Form $formHelper,
        array $data = []
    ) {
        $this->moduleKey = $arrays->getValue(
            $data,
            'module_key',
            'adminhtml'
        );
        $this->objectName = $arrays->getValue(
            $data,
            'object_name',
            'empty'
        );
        $this->objectField = $arrays->getValue(
            $data,
            'object_field',
            'id'
        );
        $this->objectRegistryKey = $arrays->getValue(
            $data,
            'object_registry_key'
        );
        $this->gridUrlRoute = $arrays->getValue(
            $data,
            'grid_url_route',
            '*/*/grid'
        );
        $this->gridUrlParams = $arrays->getValue(
            $data,
            'grid_url_params',
            []
        );
        $this->saveUrlRoute = $arrays->getValue(
            $data,
            'save_url_route',
            '*/*/save'
        );
        $this->saveUrlParams = $arrays->getValue(
            $data,
            'save_url_params',
            []
        );
        $this->allowAdd = $arrays->getValue(
            $data,
            'allow_add',
            true
        );
        $this->allowEdit = $arrays->getValue(
            $data,
            'allow_edit',
            true
        );
        $this->allowView = $arrays->getValue(
            $data,
            'allow_view',
            false
        );
        $this->editFormId = $arrays->getValue(
            $data,
            'edit_form_id',
            sprintf(
                'edit_form_%d',
                rand(
                    1000000,
                    9999999
                )
            )
        );

        $this->registryHelper = $registryHelper;
        $this->formHelper = $formHelper;

        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $data
        );
    }

    protected function getObject(): AbstractModel
    {
        if ($this->object === null) {
            $this->object = $this->registryHelper->registry($this->objectRegistryKey);
        }

        return $this->object;
    }

    protected function isReadOnlyAll(): bool
    {
        return $this->readOnlyAll;
    }

    protected function setReadOnlyAll(bool $readOnlyAll): void
    {
        $this->readOnlyAll = $readOnlyAll;
    }

    protected function isDisableAll(): bool
    {
        return $this->disableAll;
    }

    protected function setDisableAll(bool $disableAll): void
    {
        $this->disableAll = $disableAll;
    }

    /**
     * @throws LocalizedException
     */
    protected function _prepareForm(): \Magento\Backend\Block\Widget\Form
    {
        $form = $this->createForm();

        $this->prepareFields($form);
        $this->followUpFields($form);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    abstract protected function prepareFields(\Magento\Framework\Data\Form $form);

    protected function followUpFields(\Magento\Framework\Data\Form $form)
    {
    }

    /**
     * @throws LocalizedException
     */
    protected function createForm(): \Magento\Framework\Data\Form
    {
        return $this->formHelper->createPostForm(
            $this->allowAdd || $this->allowEdit ? $this->saveUrlRoute : '',
            $this->allowAdd || $this->allowEdit ? $this->saveUrlParams : [],
            $this->isUploadForm(),
            $this->editFormId,
            preg_replace(
                '/[^a-z0-9_]*/i',
                '',
                $this->objectName
            ),
            $this->getObject(),
            $this->getObjectField()
        );
    }

    protected function isUploadForm(): bool
    {
        return false;
    }

    public function getObjectField(): ?string
    {
        return $this->objectField;
    }

    protected function addTextField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addTextField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addTextFieldAfter(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $after,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addTextFieldAfter(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $after,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addTextareaField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addTextareaField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addTextareaWithCommentField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $comment,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addTextareaWithCommentField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $comment,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addOptionsField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        $defaultValue,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addOptionsField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $options,
            $defaultValue,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    /**
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
        bool $disabled = false
    ) {
        $this->formHelper->addOptionsClassField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $className,
            $defaultValue,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    public function addOptionsMultiSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        $defaultValue,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addOptionsMultiSelectField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $options,
            $defaultValue,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addYesNoField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addYesNoField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addYesNoFieldAfter(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $after,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addYesNoFieldAfter(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $after,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addYesNoWithDefaultField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $defaultValue,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addYesNoWithDefaultField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $defaultValue,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addWebsiteSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addWebsiteSelectField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addWebsiteMultiselectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addWebsiteMultiselectField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addStoreSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $readOnly = false,
        bool $disabled = false,
        bool $all = true
    ) {
        try {
            $this->formHelper->addStoreSelectField(
                $this->getLayout(),
                $fieldSet,
                $this->objectRegistryKey,
                $objectFieldName,
                $label,
                $this->getObject(),
                $this->isReadOnlyAll() ? true : $readOnly,
                $this->isDisableAll() ? true : $disabled,
                $all
            );
        } catch (LocalizedException $exception) {
            $this->_logger->error($exception);
        }
    }

    protected function addStoreMultiselectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        try {
            $this->formHelper->addStoreMultiselectField(
                $this->getLayout(),
                $fieldSet,
                $this->objectRegistryKey,
                $objectFieldName,
                $label,
                $this->getObject(),
                $this->isReadOnlyAll() ? true : $readOnly,
                $this->isDisableAll() ? true : $disabled
            );
        } catch (LocalizedException $exception) {
            $this->_logger->error($exception);
        }
    }

    protected function addStoreWithAdminSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $required = true,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        try {
            $this->formHelper->addStoreWithAdminSelectField(
                $this->getLayout(),
                $fieldSet,
                $this->objectRegistryKey,
                $objectFieldName,
                $label,
                $this->getObject(),
                $required,
                $this->isReadOnlyAll() ? true : $readOnly,
                $this->isDisableAll() ? true : $disabled
            );
        } catch (LocalizedException $exception) {
            $this->_logger->error($exception);
        }
    }

    protected function addCmsBlockSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        $defaultValue = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addCmsBlockSelectField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $defaultValue,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addCmsPageSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        $defaultValue = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addCmsPageSelectField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $defaultValue,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addTypeIdField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $defaultValue = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addTypeIdField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $defaultValue,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addTemplateField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addTemplateField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    public function addCategoriesField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addCategoriesField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addOperatorField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addOperatorField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addDateIsoField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = true,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addDateIsoField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addFileField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = true
    ) {
        $this->formHelper->addFileField(
            $fieldSet,
            $objectFieldName,
            $label,
            $required
        );
    }

    protected function addCountryField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addCountryField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addRegionField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addRegionField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addRegionAnyField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addRegionAnyField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addImageField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false
    ) {
        $this->formHelper->addImageField(
            $fieldSet,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    public function addCustomerGroupField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addCustomerGroupField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    public function addCustomerGroupMultiSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addCustomerGroupMultiSelectField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    /**
     * @throws LocalizedException
     */
    public function addPaymentActiveMethodsField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool $allStores = false,
        bool $withDefault = true
    ) {
        $this->formHelper->addPaymentActiveMethodsField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled,
            $allStores,
            $withDefault
        );
    }

    public function addProductTypeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addProductTypeField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addWysiwygField(Fieldset $fieldSet, string $objectFieldName, string $label)
    {
        $this->formHelper->addWysiwygField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject()
        );
    }

    protected function addEditorField(Fieldset $fieldSet, string $objectFieldName, string $label)
    {
        $this->formHelper->addEditorField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject()
        );
    }

    protected function addEavAttributeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ) {
        $this->formHelper->addEavAttributeField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $required,
            $customer,
            $address,
            $category,
            $product
        );
    }

    protected function addEavAttributeFieldWithUpdate(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $multiSelect = false
    ) {
        $this->formHelper->addEavAttributeFieldWithUpdate(
            $this->getObject(),
            $this->objectName,
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $targetFieldNames,
            $required,
            $multiSelect
        );
    }

    protected function addEavAttributeProductField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addEavAttributeProductField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $readOnly,
            $disabled
        );
    }

    protected function addEavAttributeProductFilterableField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addEavAttributeProductFilterableField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $readOnly,
            $disabled
        );
    }

    protected function addEavAttributeProductFieldWithUpdate(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $multiSelect = false
    ) {
        $this->formHelper->addEavAttributeProductFieldWithUpdate(
            $this->getObject(),
            $this->objectName,
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $targetFieldNames,
            $required,
            $multiSelect
        );
    }

    protected function addEavAttributeProductFilterableFieldWithUpdate(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $multiSelect = false
    ) {
        $this->formHelper->addEavAttributeProductFilterableFieldWithUpdate(
            $this->getObject(),
            $this->objectName,
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $targetFieldNames,
            $required,
            $multiSelect
        );
    }

    /**
     * @throws Exception
     */
    protected function addEavAttributeValueField(
        Fieldset $fieldSet,
        string $objectAttributeFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $multiSelect = false
    ) {
        $this->formHelper->addEavAttributeValueField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectAttributeFieldName,
            $objectFieldName,
            $label,
            $required,
            $multiSelect
        );
    }

    protected function addEavAttributeSetField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ) {
        $this->formHelper->addEavAttributeSetField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $required,
            $customer,
            $address,
            $category,
            $product
        );
    }

    protected function addEavEntityTypeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ) {
        $this->formHelper->addEavEntityTypeField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $required,
            $customer,
            $address,
            $category,
            $product
        );
    }

    public function addProductAttributeCodeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addProductAttributeCodeField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $required
        );
    }

    public function addCustomerAttributeCodeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addCustomerAttributeCodeField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $required
        );
    }

    public function addAddressAttributeCodeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addAddressAttributeCodeField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $required
        );
    }

    public function addAttributeSortByField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $multiSelect = false
    ) {
        $this->formHelper->addAttributeSortByField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $required,
            $multiSelect
        );
    }

    protected function addCheckboxField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $value,
        bool $disabled = false
    ) {
        $this->formHelper->addCheckboxField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $value,
            $this->getObject(),
            $this->isDisableAll() ? true : $disabled
        );
    }

    protected function addValueField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label
    ) {
        $this->formHelper->addValueField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject()
        );
    }

    protected function addButtonField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $value,
        $onClick = null,
        $dataMageInit = null
    ) {
        $this->formHelper->addButtonField(
            $fieldSet,
            $objectFieldName,
            $label,
            $value,
            $onClick,
            $dataMageInit
        );
    }

    protected function addIframeButtonField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $value,
        string $urlPath,
        array $urlParameters = []
    ) {
        $this->formHelper->addIframeButtonField(
            $fieldSet,
            $this->objectName,
            $this->objectField,
            $objectFieldName,
            $label,
            $value,
            $urlPath,
            $urlParameters,
            $this->getObject()
        );
    }

    public function addThemeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addThemeField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $readOnly,
            $disabled
        );
    }

    public function addCustomerNameField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addCustomerNameField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    public function addProductNameField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addProductNameField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    public function addProductNameFieldWithProductOptions(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false
    ) {
        $this->formHelper->addProductNameFieldWithProductOptions(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $targetFieldNames,
            $this->objectName,
            $this->getObject(),
            $required
        );
    }

    public function addProductNameFieldWithProductOptionValues(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false
    ) {
        $this->formHelper->addProductNameFieldWithProductOptionValues(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $targetFieldNames,
            $this->objectName,
            $this->getObject(),
            $required
        );
    }

    public function addProductNameFieldWithProductOptionsAndValues(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $optionTargetFieldNames,
        array $optionValueTargetFieldNames,
        bool $required = false
    ) {
        $this->formHelper->addProductNameFieldWithProductOptionsAndValues(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $optionTargetFieldNames,
            $optionValueTargetFieldNames,
            $this->objectName,
            $this->getObject(),
            $required
        );
    }

    public function addPriceField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addPriceField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    public function addDiscountField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addDiscountField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    public function addIntegerField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addIntegerField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    /**
     * @throws Exception
     */
    protected function addProductOptionField(
        Fieldset $fieldSet,
        string $objectProductIdFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addProductOptionField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectProductIdFieldName,
            $objectFieldName,
            $label,
            $required
        );
    }

    /**
     * @throws Exception
     */
    protected function addProductOptionValueField(
        Fieldset $fieldSet,
        string $objectProductIdFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addProductOptionValueField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectProductIdFieldName,
            $objectFieldName,
            $label,
            $required
        );
    }

    protected function addFilterConditionTypeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addFilterConditionTypeField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required,
            $this->isReadOnlyAll() ? true : $readOnly,
            $this->isDisableAll() ? true : $disabled
        );
    }
}
