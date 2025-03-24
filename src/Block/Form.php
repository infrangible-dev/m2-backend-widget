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
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
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

    /** @var string */
    protected $parentObjectKey;

    /** @var string */
    protected $parentObjectValue;

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
        $this->parentObjectKey = $arrays->getValue(
            $data,
            'parent_object_key'
        );
        $this->parentObjectValue = $arrays->getValue(
            $data,
            'parent_object_value'
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
        $this->_eventManager->dispatch(
            ltrim(
                strtolower(
                    preg_replace(
                        '/[A-Z]([A-Z](?![a-z]))*/',
                        '_$0',
                        sprintf(
                            '%s_%s',
                            $this->moduleKey,
                            $this->objectName
                        )
                    )
                ),
                '_'
            ),
            ['block' => $this, 'form' => $form]
        );
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

    public function addTextField(
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

    public function addTextFieldAfter(
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

    public function addTextareaField(
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

    public function addTextareaWithCommentField(
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

    public function addOptionsField(
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
    public function addOptionsClassField(
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

    public function addYesNoField(
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

    public function addYesNoDefaultField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        int $defaultValue = 1,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->formHelper->addYesNoDefaultField(
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

    public function addYesNoFieldAfter(
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

    public function addYesNoWithDefaultField(
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

    public function addWebsiteSelectField(
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

    public function addWebsiteMultiselectField(
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

    public function addStoreSelectField(
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

    public function addStoreMultiselectField(
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

    public function addStoreWithAdminSelectField(
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

    public function addCmsBlockSelectField(
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

    public function addCmsPageSelectField(
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

    public function addTypeIdField(
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

    public function addTemplateField(
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

    public function addOperatorField(
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

    public function addDateIsoField(
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

    public function addFileField(
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

    public function addCountryField(
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

    public function addRegionField(
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

    public function addRegionAnyField(
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

    public function addImageField(
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

    public function addWysiwygField(Fieldset $fieldSet, string $objectFieldName, string $label)
    {
        $this->formHelper->addWysiwygField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject()
        );
    }

    public function addEditorField(Fieldset $fieldSet, string $objectFieldName, string $label)
    {
        $this->formHelper->addEditorField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject()
        );
    }

    public function addEavAttributeField(
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

    public function addEavAttributeFieldWithUpdate(
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

    public function addEavAttributeProductField(
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

    public function addEavAttributeProductFilterableField(
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

    public function addEavAttributeProductFieldWithUpdate(
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

    public function addEavAttributeProductFilterableFieldWithUpdate(
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
    public function addEavAttributeValueField(
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

    public function addEavAttributeSetField(
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

    public function addEavEntityTypeField(
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

    public function addCheckboxField(
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

    public function addValueField(
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

    public function addButtonField(
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

    public function addIframeButtonField(
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
        bool $required = false,
        bool $includeWithValues = false
    ) {
        $this->formHelper->addProductNameFieldWithProductOptions(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $targetFieldNames,
            $this->objectName,
            $this->getObject(),
            $required,
            $includeWithValues
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
        bool $required = false,
        bool $includeWithValues = false
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
            $required,
            $includeWithValues
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
    public function addProductOptionField(
        Fieldset $fieldSet,
        string $objectProductIdFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $includeWithValues = false
    ) {
        $this->formHelper->addProductOptionField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $this->parentObjectValue,
            $objectProductIdFieldName,
            $objectFieldName,
            $label,
            $required,
            $includeWithValues
        );
    }

    /**
     * @throws Exception
     */
    public function addProductOptionFieldWithTypeValues(
        Fieldset $fieldSet,
        string $objectProductIdFieldName,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false
    ) {
        $this->formHelper->addProductOptionFieldWithTypeValues(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $this->parentObjectValue,
            $objectProductIdFieldName,
            $objectFieldName,
            $label,
            $targetFieldNames,
            $this->objectName,
            $required
        );
    }

    /**
     * @throws Exception
     */
    public function addProductOptionValueField(
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

    /**
     * @throws Exception
     */
    public function addProductOptionTypeValueField(
        Fieldset $fieldSet,
        string $objectOptionIdFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $this->formHelper->addProductOptionTypeValueField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectOptionIdFieldName,
            $objectFieldName,
            $label,
            $required
        );
    }

    public function addFilterConditionTypeField(
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
