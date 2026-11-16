<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block;

use FeWeDev\Base\Arrays;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Registry;
use RuntimeException;

abstract class Form extends Generic
{
    use LayoutArguments;

    /** @var string */
    protected string $moduleKey;

    /** @var string */
    protected string $objectName;

    /** @var string */
    protected string $objectField;

    /** @var string */
    protected string $objectRegistryKey;

    /** @var string */
    protected string $gridUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $gridUrlParams;

    /** @var string */
    protected string $saveUrlRoute;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    protected array $saveUrlParams;

    /** @var bool */
    protected bool $allowAdd = true;

    /** @var bool */
    protected bool $allowEdit = true;

    /** @var bool */
    protected bool $allowView = false;

    /** @var string */
    protected string $editFormId;

    /** @var string */
    protected string $parentObjectKey;

    /** @var string */
    protected string $parentObjectValue;

    /** @var AbstractModel|null */
    private ?AbstractModel $object = null;

    /** @var bool */
    private bool $readOnlyAll = false;

    /** @var bool */
    private bool $disableAll = false;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Arrays $arrays
     * @param \Infrangible\Core\Helper\Registry $registryHelper
     * @param \Infrangible\BackendWidget\Helper\Form $formHelper
     * @param array $data
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        protected Arrays $arrays,
        protected \Infrangible\Core\Helper\Registry $registryHelper,
        protected \Infrangible\BackendWidget\Helper\Form $formHelper,
        array $data = []
    ) {
        $this->moduleKey = $this->stringArgument($data, 'module_key', 'adminhtml');
        $this->objectName = $this->stringArgument($data, 'object_name', 'empty');
        $this->objectField = $this->stringArgument($data, 'object_field', 'id');
        $this->objectRegistryKey = $this->stringArgument($data, 'object_registry_key');
        $this->gridUrlRoute = $this->stringArgument($data, 'grid_url_route', '*/*/grid');
        $this->gridUrlParams = $this->arrayArgument($data, 'grid_url_params');
        $this->saveUrlRoute = $this->stringArgument($data, 'save_url_route', '*/*/save');
        $this->saveUrlParams = $this->arrayArgument($data, 'save_url_params');
        $this->allowAdd = $this->boolArgument($data, 'allow_add', true);
        $this->allowEdit = $this->boolArgument($data, 'allow_edit', true);
        $this->allowView = $this->boolArgument($data, 'allow_view');
        $this->editFormId = $this->stringArgument(
            $data,
            'edit_form_id',
            uniqid('edit_form_')
        );
        $this->parentObjectKey = $this->stringArgument($data, 'parent_object_key');
        $this->parentObjectValue = $this->stringArgument($data, 'parent_object_value');

        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $data
        );
    }

    /**
     * Returns the object.
     *
     * @return AbstractModel
     * @throws RuntimeException
     */
    protected function getObject(): AbstractModel
    {
        if ($this->object === null) {
            $object = $this->registryHelper->registry($this->objectRegistryKey);

            if (! $object instanceof AbstractModel) {
                throw new RuntimeException(
                    sprintf(
                        'No object registered with key: %s',
                        $this->objectRegistryKey
                    )
                );
            }

            $this->object = $object;
        }

        return $this->object;
    }

    /**
     * Checks whether it is read only all.
     *
     * @return bool
     */
    protected function isReadOnlyAll(): bool
    {
        return $this->readOnlyAll;
    }

    /**
     * Sets the read only all.
     *
     * @param bool $readOnlyAll
     *
     * @return void
     */
    protected function setReadOnlyAll(bool $readOnlyAll): void
    {
        $this->readOnlyAll = $readOnlyAll;
    }

    /**
     * Checks whether it is disable all.
     *
     * @return bool
     */
    protected function isDisableAll(): bool
    {
        return $this->disableAll;
    }

    /**
     * Sets the disable all.
     *
     * @param bool $disableAll
     *
     * @return void
     */
    protected function setDisableAll(bool $disableAll): void
    {
        $this->disableAll = $disableAll;
    }

    /**
     * Prepares the form.
     *
     * @return \Magento\Backend\Block\Widget\Form
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

    /**
     * Prepares the fields.
     *
     * @param \Magento\Framework\Data\Form $form
     *
     * @return void
     */
    abstract protected function prepareFields(\Magento\Framework\Data\Form $form): void;

    /**
     * Hook to add or change fields after the default fields were added.
     *
     * @param \Magento\Framework\Data\Form $form
     *
     * @return void
     */
    protected function followUpFields(\Magento\Framework\Data\Form $form): void
    {
        $this->_eventManager->dispatch(
            ltrim(
                strtolower(
                    (string)preg_replace(
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
     * Creates the form.
     *
     * @return \Magento\Framework\Data\Form
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

    /**
     * Checks whether it is upload form.
     *
     * @return bool
     */
    protected function isUploadForm(): bool
    {
        return false;
    }

    /**
     * Returns the object field.
     *
     * @return string|null
     */
    public function getObjectField(): ?string
    {
        return $this->objectField;
    }

    /**
     * Adds the text field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTextField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the text field after.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param string $after
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTextFieldAfter(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $after,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the textarea field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTextareaField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the textarea with comment field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param string $comment
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTextareaWithCommentField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $comment,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the options field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param mixed $defaultValue
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @phpstan-param array<int|string, mixed> $options
     *
     * @return void
     */
    public function addOptionsField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        mixed $defaultValue,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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
     * Adds the options class field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param string $className
     * @param mixed $defaultValue
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addOptionsClassField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $className,
        mixed $defaultValue,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the options multi select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param mixed $defaultValue
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @phpstan-param array<int|string, mixed> $options
     *
     * @return void
     */
    public function addOptionsMultiSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        mixed $defaultValue,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the yes no field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addYesNoField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the yes no default field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param int $defaultValue
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addYesNoDefaultField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        int $defaultValue = 1,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the yes no field after.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param string $after
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addYesNoFieldAfter(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $after,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the yes no with default field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $defaultValue
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addYesNoWithDefaultField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        mixed $defaultValue,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the website select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addWebsiteSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the website multiselect field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addWebsiteMultiselectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the store select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool $all
     *
     * @return void
     */
    public function addStoreSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $readOnly = false,
        bool $disabled = false,
        bool $all = true
    ): void {
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

    /**
     * Adds the store multiselect field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addStoreMultiselectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the store with admin select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addStoreWithAdminSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $required = true,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the cms block select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param mixed $defaultValue
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCmsBlockSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        mixed $defaultValue = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the cms page select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param mixed $defaultValue
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCmsPageSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        mixed $defaultValue = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the type id field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param string|null $defaultValue
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTypeIdField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        ?string $defaultValue = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the template field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTemplateField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the categories field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCategoriesField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the operator field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addOperatorField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the date iso field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addDateIsoField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = true,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the file field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addFileField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = true
    ): void {
        $this->formHelper->addFileField(
            $fieldSet,
            $objectFieldName,
            $label,
            $required
        );
    }

    /**
     * Adds the country field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCountryField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the region field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addRegionField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the region any field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addRegionAnyField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the image field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     *
     * @return void
     */
    public function addImageField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false
    ): void {
        $this->formHelper->addImageField(
            $fieldSet,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    /**
     * Adds the customer group field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCustomerGroupField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the customer group multi select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCustomerGroupMultiSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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
     * Adds the payment active methods field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool $allStores
     * @param bool $withDefault
     *
     * @return void
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
    ): void {
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

    /**
     * Adds the product type field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addProductTypeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the wysiwyg field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     */
    public function addWysiwygField(Fieldset $fieldSet, string $objectFieldName, string $label): void
    {
        $this->formHelper->addWysiwygField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject()
        );
    }

    /**
     * Adds the editor field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     */
    public function addEditorField(Fieldset $fieldSet, string $objectFieldName, string $label): void
    {
        $this->formHelper->addEditorField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject()
        );
    }

    /**
     * Adds the eav attribute field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $customer
     * @param bool $address
     * @param bool $category
     * @param bool $product
     *
     * @return void
     */
    public function addEavAttributeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
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

    /**
     * Adds the eav attribute field with update.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $targetFieldNames
     * @param bool $required
     * @param bool $multiSelect
     *
     * @phpstan-param array<int, string> $targetFieldNames
     *
     * @return void
     */
    public function addEavAttributeFieldWithUpdate(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $multiSelect = false
    ): void {
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

    /**
     * Adds the eav attribute product field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addEavAttributeProductField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the eav attribute product filterable field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addEavAttributeProductFilterableField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the eav attribute product field with update.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $targetFieldNames
     * @param bool $required
     * @param bool $multiSelect
     *
     * @phpstan-param array<int, string> $targetFieldNames
     *
     * @return void
     */
    public function addEavAttributeProductFieldWithUpdate(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $multiSelect = false
    ): void {
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

    /**
     * Adds the eav attribute product filterable field with update.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $targetFieldNames
     * @param bool $required
     * @param bool $multiSelect
     *
     * @phpstan-param array<int, string> $targetFieldNames
     *
     * @return void
     */
    public function addEavAttributeProductFilterableFieldWithUpdate(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $multiSelect = false
    ): void {
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
     * Adds the eav attribute value field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectAttributeFieldName
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $multiSelect
     *
     * @return void
     * @throws LocalizedException
     */
    public function addEavAttributeValueField(
        Fieldset $fieldSet,
        string $objectAttributeFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $multiSelect = false
    ): void {
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

    /**
     * Adds the eav attribute set field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $customer
     * @param bool $address
     * @param bool $category
     * @param bool $product
     *
     * @return void
     */
    public function addEavAttributeSetField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
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

    /**
     * Adds the eav entity type field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $customer
     * @param bool $address
     * @param bool $category
     * @param bool $product
     *
     * @return void
     */
    public function addEavEntityTypeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
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

    /**
     * Adds the product attribute code field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addProductAttributeCodeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $this->formHelper->addProductAttributeCodeField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $required
        );
    }

    /**
     * Adds the customer attribute code field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addCustomerAttributeCodeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $this->formHelper->addCustomerAttributeCodeField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $required
        );
    }

    /**
     * Adds the address attribute code field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addAddressAttributeCodeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $this->formHelper->addAddressAttributeCodeField(
            $this->getObject(),
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $required
        );
    }

    /**
     * Adds the attribute sort by field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $multiSelect
     *
     * @return void
     */
    public function addAttributeSortByField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $multiSelect = false
    ): void {
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

    /**
     * Adds the checkbox field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $value
     * @param bool $disabled
     *
     * @return void
     */
    public function addCheckboxField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        mixed $value,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the value field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     *
     * @return void
     */
    public function addValueField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label
    ): void {
        $this->formHelper->addValueField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject()
        );
    }

    /**
     * Adds the button field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param string $value
     * @param mixed $onClick
     * @param mixed $dataMageInit
     *
     * @return void
     */
    public function addButtonField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $value,
        mixed $onClick = null,
        mixed $dataMageInit = null
    ): void {
        $this->formHelper->addButtonField(
            $fieldSet,
            $objectFieldName,
            $label,
            $value,
            $onClick,
            $dataMageInit
        );
    }

    /**
     * Adds the iframe button field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param string $value
     * @param string $urlPath
     * @param array $urlParameters
     *
     * @phpstan-param array<int|string, mixed> $urlParameters
     *
     * @return void
     */
    public function addIframeButtonField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $value,
        string $urlPath,
        array $urlParameters = []
    ): void {
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

    /**
     * Adds the theme field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addThemeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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

    /**
     * Adds the customer name field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addCustomerNameField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $this->formHelper->addCustomerNameField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    /**
     * Adds the product name field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addProductNameField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $this->formHelper->addProductNameField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    /**
     * Adds the product name field with product options.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $targetFieldNames
     * @param bool $required
     * @param bool $includeWithValues
     *
     * @phpstan-param array<int, string> $targetFieldNames
     *
     * @return void
     */
    public function addProductNameFieldWithProductOptions(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $includeWithValues = false
    ): void {
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

    /**
     * Adds the product name field with product option values.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $targetFieldNames
     * @param bool $required
     *
     * @phpstan-param array<int, string> $targetFieldNames
     *
     * @return void
     */
    public function addProductNameFieldWithProductOptionValues(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false
    ): void {
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

    /**
     * Adds the product name field with product options and values.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $optionTargetFieldNames
     * @param array $optionValueTargetFieldNames
     * @param bool $required
     * @param bool $includeWithValues
     *
     * @phpstan-param array<int, string> $optionTargetFieldNames
     * @phpstan-param array<int, string> $optionValueTargetFieldNames
     *
     * @return void
     */
    public function addProductNameFieldWithProductOptionsAndValues(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $optionTargetFieldNames,
        array $optionValueTargetFieldNames,
        bool $required = false,
        bool $includeWithValues = false
    ): void {
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

    /**
     * Adds the price field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addPriceField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $this->formHelper->addPriceField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    /**
     * Adds the discount field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addDiscountField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $this->formHelper->addDiscountField(
            $fieldSet,
            $this->objectRegistryKey,
            $objectFieldName,
            $label,
            $this->getObject(),
            $required
        );
    }

    /**
     * Adds the integer field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addIntegerField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
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
     * Adds the product option field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectProductIdFieldName
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $includeWithValues
     *
     * @return void
     */
    public function addProductOptionField(
        Fieldset $fieldSet,
        string $objectProductIdFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $includeWithValues = false
    ): void {
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
     * Adds the product option field with type values.
     *
     * @param Fieldset $fieldSet
     * @param string $objectProductIdFieldName
     * @param string $objectFieldName
     * @param string $label
     * @param array $targetFieldNames
     * @param bool $required
     *
     * @phpstan-param array<int, string> $targetFieldNames
     *
     * @return void
     */
    public function addProductOptionFieldWithTypeValues(
        Fieldset $fieldSet,
        string $objectProductIdFieldName,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false
    ): void {
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
     * Adds the product option value field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectProductIdFieldName
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addProductOptionValueField(
        Fieldset $fieldSet,
        string $objectProductIdFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
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
     * Adds the product option type value field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectOptionIdFieldName
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addProductOptionTypeValueField(
        Fieldset $fieldSet,
        string $objectOptionIdFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
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

    /**
     * Adds the filter condition type field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addFilterConditionTypeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
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
