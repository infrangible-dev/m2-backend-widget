<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Helper;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Block\Config\Form\DateIso;
use Infrangible\BackendWidget\Block\Config\Form\Value;
use Infrangible\BackendWidget\Block\Config\Form\Wysiwyg;
use Infrangible\BackendWidget\Data\Form\Element\Autocomplete;
use Infrangible\BackendWidget\Data\Form\Element\Discount;
use Infrangible\BackendWidget\Data\Form\Element\Integer;
use Infrangible\BackendWidget\Data\Form\Element\Price;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Customer;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Template;
use Infrangible\Core\Helper\Url;
use Infrangible\Core\Model\Config\Source\Attribute;
use Infrangible\Core\Model\Config\Source\Attribute\AddressAttributeCode;
use Infrangible\Core\Model\Config\Source\Attribute\CustomerAttributeCode;
use Infrangible\Core\Model\Config\Source\Attribute\Product\Filterable;
use Infrangible\Core\Model\Config\Source\Attribute\ProductAttributeCode;
use Infrangible\Core\Model\Config\Source\Attribute\SortBy;
use Infrangible\Core\Model\Config\Source\AttributeSet;
use Infrangible\Core\Model\Config\Source\Categories;
use Infrangible\Core\Model\Config\Source\CmsBlock;
use Infrangible\Core\Model\Config\Source\CmsPage;
use Infrangible\Core\Model\Config\Source\Directory\Region;
use Infrangible\Core\Model\Config\Source\Directory\RegionAny;
use Infrangible\Core\Model\Config\Source\EntityType;
use Infrangible\Core\Model\Config\Source\Operator;
use Infrangible\Core\Model\Config\Source\Payment\ActiveMethods;
use Infrangible\Core\Model\Config\Source\TypeId;
use IntlDateFormatter;
use Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Config\Model\Config\Source\Website;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Customer\Model\Config\Source\FilterConditionType;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\System\Store;
use Magento\Theme\Model\Theme\Source\Theme;

class Form
{
    /** @var Collection */
    protected Collection $customerGroupCollection;

    /** @var string */
    protected string $dateFormatIso;

    /**
     * @param Variables $variables
     * @param Arrays $arrays
     * @param Template $templateHelper
     * @param Url $urlHelper
     * @param Customer $customerHelper
     * @param \Infrangible\Core\Helper\Attribute $attributeHelper
     * @param Instances $instanceHelper
     * @param Session $adminhtmlSession
     * @param FormFactory $formFactory
     * @param Yesno $sourceYesNo
     * @param Website $sourceWebsite
     * @param Store $sourceStore
     * @param \Infrangible\BackendWidget\Model\Store\System\Store $sourceStoreWithAdmin
     * @param CmsBlock $sourceCmsBlock
     * @param CmsPage $sourceCmsPage
     * @param TypeId $sourceTypeIds
     * @param Categories $sourceCategories
     * @param Operator $sourceOperator
     * @param Country $sourceCountry
     * @param Region $sourceRegion
     * @param RegionAny $sourceRegionAny
     * @param ActiveMethods $sourcePaymentActiveMethods
     * @param Attribute $sourceAttributes
     * @param AttributeSet $sourceAttributeSets
     * @param EntityType $sourceEntityTypes
     * @param ProductAttributeCode $sourceProductAttributeCode
     * @param CustomerAttributeCode $sourceCustomerAttributeCode
     * @param AddressAttributeCode $sourceAddressAttributeCode
     * @param SortBy $sourceAttributeSortBy
     * @param Theme $sourceThemes
     * @param Attribute\Product $sourceAttributeProduct
     * @param Filterable $sourceAttributeProductFilterable
     * @param TimezoneInterface $localeDate
     * @param Type $productType
     * @param Config $wysiwygConfig
     * @param Escaper $escaper
     * @param ProductOption $productOptionHelper
     * @param FilterConditionType $sourceFilterConditionType
     */
    public function __construct(
        protected Variables $variables,
        protected Arrays $arrays,
        protected Template $templateHelper,
        protected Url $urlHelper,
        protected Customer $customerHelper,
        protected \Infrangible\Core\Helper\Attribute $attributeHelper,
        protected Instances $instanceHelper,
        protected Session $adminhtmlSession,
        protected FormFactory $formFactory,
        protected Yesno $sourceYesNo,
        protected Website $sourceWebsite,
        protected Store $sourceStore,
        protected \Infrangible\BackendWidget\Model\Store\System\Store $sourceStoreWithAdmin,
        protected CmsBlock $sourceCmsBlock,
        protected CmsPage $sourceCmsPage,
        protected TypeId $sourceTypeIds,
        protected Categories $sourceCategories,
        protected Operator $sourceOperator,
        protected Country $sourceCountry,
        protected Region $sourceRegion,
        protected RegionAny $sourceRegionAny,
        protected ActiveMethods $sourcePaymentActiveMethods,
        protected Attribute $sourceAttributes,
        protected AttributeSet $sourceAttributeSets,
        protected EntityType $sourceEntityTypes,
        protected ProductAttributeCode $sourceProductAttributeCode,
        protected CustomerAttributeCode $sourceCustomerAttributeCode,
        protected AddressAttributeCode $sourceAddressAttributeCode,
        protected SortBy $sourceAttributeSortBy,
        protected Theme $sourceThemes,
        protected Attribute\Product $sourceAttributeProduct,
        protected Filterable $sourceAttributeProductFilterable,
        protected TimezoneInterface $localeDate,
        protected Type $productType,
        protected Config $wysiwygConfig,
        protected Escaper $escaper,
        protected ProductOption $productOptionHelper,
        protected FilterConditionType $sourceFilterConditionType
    ) {
        $this->customerGroupCollection = $this->customerHelper->getCustomerGroupCollection();
        /** @noinspection PhpParamsInspection Magento passes the type to IntlDateFormatter, which expects an integer */
        // @phpstan-ignore argument.type (Magento passes the type to IntlDateFormatter, which expects an integer)
        $this->dateFormatIso = $this->localeDate->getDateTimeFormat(IntlDateFormatter::MEDIUM);
    }

    /**
     * Creates the simple form.
     *
     * @param string $formId
     * @param string|null $action
     * @param string $method
     *
     * @return \Magento\Framework\Data\Form
     * @throws LocalizedException
     */
    public function createSimpleForm(
        string $formId,
        ?string $action = null,
        string $method = 'post'
    ): \Magento\Framework\Data\Form {
        return $this->formFactory->create(
            ['data' => ['id' => $formId, 'action' => $action, 'method' => $method]]
        );
    }

    /**
     * Creates the post form.
     *
     * @param string $saveUrlRoute
     * @param array $saveUrlParams
     * @param bool $isUpload
     * @param string $formId
     * @param string|null $htmlIdPrefix
     * @param AbstractModel|null $object
     * @param string|null $objectField
     *
     * @phpstan-param array<int|string, mixed> $saveUrlParams
     *
     * @return \Magento\Framework\Data\Form
     * @throws LocalizedException
     */
    public function createPostForm(
        string $saveUrlRoute,
        array $saveUrlParams,
        bool $isUpload = false,
        string $formId = 'edit_form',
        ?string $htmlIdPrefix = null,
        ?AbstractModel $object = null,
        ?string $objectField = null
    ): \Magento\Framework\Data\Form {
        if (empty($objectField)) {
            $objectField = 'id';
        }

        if ($object && $object->getId()) {
            $saveUrlParams[ $objectField ] = $object->getId();
        }

        $form = $this->createSimpleForm(
            $formId,
            $this->urlHelper->getBackendUrl(
                $saveUrlRoute,
                $saveUrlParams
            )
        );

        $form->setData(
            'use_container',
            true
        );

        if ($isUpload) {
            $form->setData(
                'enctype',
                'multipart/form-data'
            );
        }

        if (! $this->variables->isEmpty($htmlIdPrefix)) {
            $form->setData(
                'html_id_prefix',
                sprintf(
                    '%s_',
                    $htmlIdPrefix
                )
            );
        }

        return $form;
    }

    /**
     * Returns the field value.
     *
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param mixed $defaultValue
     * @param AbstractModel|null $object
     * @param string|null $splitObjectValueSeparator
     *
     * @return mixed
     */
    public function getFieldValue(
        string $objectRegistryKey,
        string $objectFieldName,
        mixed $defaultValue = null,
        ?AbstractModel $object = null,
        ?string $splitObjectValueSeparator = null
    ): mixed {
        $formData = $this->adminhtmlSession->getData(
            sprintf(
                '%s_form_%s',
                $objectRegistryKey,
                $object && $object->getId() ? $this->variables->stringValue($object->getId()) : 'add'
            )
        );

        if (is_object($formData) && method_exists(
            $formData,
            'toArray'
        )) {
            $formData = $formData->toArray();
        }

        if (! is_array($formData)) {
            $formData = [];
        }

        if (array_key_exists(
            $objectFieldName,
            $formData
        )) {
            return $this->arrays->getValue(
                $formData,
                $objectFieldName
            );
        }

        if ($object instanceof AbstractModel && $object->getId()) {
            $objectValue = $object->getDataUsingMethod($objectFieldName);

            if (! $this->variables->isEmpty($splitObjectValueSeparator)) {
                $objectValue = explode(
                    ',',
                    $this->variables->stringValue($objectValue)
                );
            }

            return $objectValue;
        }

        return $defaultValue;
    }

    /**
     * Adds the text field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addTextField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $this->addSimpleTextFieldWithValue(
            $fieldSet,
            $objectFieldName,
            $label,
            $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                '',
                $object
            ),
            $required,
            $readOnly,
            $disabled,
            $after
        );
    }

    /**
     * Adds the simple text field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addSimpleTextField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $this->addSimpleTextFieldWithValueAndNoteAndClass(
            $fieldSet,
            $objectFieldName,
            $label,
            null,
            null,
            null,
            $required,
            $readOnly,
            $disabled,
            $after
        );
    }

    /**
     * Adds the simple text field with value.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $value
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addSimpleTextFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        mixed $value,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $this->addSimpleTextFieldWithValueAndNoteAndClass(
            $fieldSet,
            $objectFieldName,
            $label,
            $value,
            null,
            null,
            $required,
            $readOnly,
            $disabled,
            $after
        );
    }

    /**
     * Adds the simple text field with value and note.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $value
     * @param string|null $note
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addSimpleTextFieldWithValueAndNote(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        mixed $value,
        ?string $note = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $this->addSimpleTextFieldWithValueAndNoteAndClass(
            $fieldSet,
            $objectFieldName,
            $label,
            $value,
            $note,
            null,
            $required,
            $readOnly,
            $disabled,
            $after
        );
    }

    /**
     * Adds the simple text field with note and class.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param string|null $note
     * @param string|null $class
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addSimpleTextFieldWithNoteAndClass(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        ?string $note = null,
        ?string $class = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $this->addSimpleTextFieldWithValueAndNoteAndClass(
            $fieldSet,
            $objectFieldName,
            $label,
            null,
            $note,
            $class,
            $required,
            $readOnly,
            $disabled,
            $after
        );
    }

    /**
     * Adds the simple text field with value and note and class.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $value
     * @param string|null $note
     * @param string|null $class
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addSimpleTextFieldWithValueAndNoteAndClass(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        mixed $value = null,
        ?string $note = null,
        ?string $class = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'required' => $required
        ];

        if ($value) {
            $config[ 'value' ] = $value;
        }

        if ($note) {
            $config[ 'note' ] = $note;
        }

        if ($class) {
            $config[ 'class' ] = $class;
        }

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $fieldSet->addField(
            $objectFieldName,
            'text',
            $config,
            $after // @phpstan-ignore argument.type (Magento also accepts the id of the field to add it after)
        );
    }

    /**
     * Adds the text field after.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param string $after
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTextFieldAfter(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        string $after,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addTextField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $object,
            $required,
            $readOnly,
            $disabled,
            $after
        );
    }

    /**
     * Adds the textarea field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTextareaField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addSimpleTextareaFieldWithValue(
            $fieldSet,
            $objectFieldName,
            $label,
            $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                '',
                $object
            ),
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the simple textarea field with value.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $value
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addSimpleTextareaFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        mixed $value,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'required' => $required
        ];

        if ($value) {
            $config[ 'value' ] = $value;
        }

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $fieldSet->addField(
            $objectFieldName,
            'textarea',
            $config
        );
    }

    /**
     * Adds the textarea with comment field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param string $comment
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTextareaWithCommentField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        string $comment,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $config = [
            'name'               => $objectFieldName,
            'label'              => $label,
            'value'              => $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                '',
                $object
            ),
            'required'           => $required,
            'after_element_html' => sprintf(
                '<div>%s</div>',
                nl2br($comment)
            )
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $fieldSet->addField(
            $objectFieldName,
            'textarea',
            $config
        );
    }

    /**
     * Adds the options field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param mixed $defaultValue
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     * @param mixed $onChange
     *
     * @phpstan-param array<int|string, mixed> $options
     *
     * @return void
     */
    public function addOptionsField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $options,
        mixed $defaultValue,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false,
        mixed $onChange = null
    ): void {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'value'    => $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                $defaultValue,
                $object
            ),
            'values'   => $options,
            'required' => $required
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        if (! $this->variables->isEmpty($onChange)) {
            $config[ 'onchange' ] = $onChange;
        }

        $fieldSet->addField(
            $objectFieldName,
            'select',
            $config,
            $after // @phpstan-ignore argument.type (Magento also accepts the id of the field to add it after)
        );
    }

    /**
     * Adds the simple options field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @phpstan-param array<int|string, mixed> $options
     *
     * @return void
     */
    public function addSimpleOptionsField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'values'   => $options,
            'required' => $required
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $fieldSet->addField(
            $objectFieldName,
            'select',
            $config,
            $after // @phpstan-ignore argument.type (Magento also accepts the id of the field to add it after)
        );
    }

    /**
     * Adds the simple options field with note.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param string|null $note
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @phpstan-param array<int|string, mixed> $options
     *
     * @return void
     */
    public function addSimpleOptionsFieldWithNote(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        ?string $note = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'values'   => $options,
            'required' => $required
        ];

        if ($note) {
            $config[ 'note' ] = $note;
        }

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $fieldSet->addField(
            $objectFieldName,
            'select',
            $config,
            $after // @phpstan-ignore argument.type (Magento also accepts the id of the field to add it after)
        );
    }

    /**
     * Adds the options class field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param string $className
     * @param mixed $defaultValue
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addOptionsClassField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        string $className,
        mixed $defaultValue,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        /** @var OptionSourceInterface $optionsClass */
        $optionsClass = $this->instanceHelper->getSingleton($className);

        if (method_exists(
            $optionsClass,
            'toOptionArray'
        )) {
            $options = $optionsClass->toOptionArray();
        } else {
            throw new \LogicException(
                sprintf(
                    'Options class: %s does not implement method: toOptions',
                    $className
                )
            );
        }

        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $options,
            $defaultValue,
            $object,
            $required,
            $readOnly,
            $disabled,
            $after
        );
    }

    /**
     * Adds the options multi select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
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
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $options,
        mixed $defaultValue,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addSimpleOptionsMultiSelectField(
            $fieldSet,
            $objectFieldName,
            $label,
            $options,
            $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                $defaultValue,
                $object
            ),
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the simple options multi select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param array $options
     * @param mixed $value
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @phpstan-param array<int|string, mixed> $options
     *
     * @return void
     */
    public function addSimpleOptionsMultiSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        mixed $value = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'value'    => $value,
            'values'   => $options,
            'required' => $required
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $fieldSet->addField(
            $objectFieldName,
            'multiselect',
            $config
        );
    }

    /**
     * Adds the yes no field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addYesNoField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceYesNo->toOptionArray(),
            1,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the yes no default field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param int $defaultValue
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addYesNoDefaultField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        int $defaultValue = 1,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceYesNo->toOptionArray(),
            $defaultValue,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the simple yes no field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addSimpleYesNoField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $this->addSimpleOptionsField(
            $fieldSet,
            $objectFieldName,
            $label,
            $this->sourceYesNo->toOptionArray(),
            $required,
            $readOnly,
            $disabled,
            $after
        );
    }

    /**
     * Adds the yes no field after.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param string $after
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addYesNoFieldAfter(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        string $after,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceYesNo->toOptionArray(),
            1,
            $object,
            $required,
            $readOnly,
            $disabled,
            $after
        );
    }

    /**
     * Adds the yes no with default field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $defaultValue
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addYesNoWithDefaultField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        mixed $defaultValue,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceYesNo->toOptionArray(),
            $defaultValue,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the website select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param AbstractModel|null $object
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addWebsiteSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        ?AbstractModel $object = null,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        if ($this->variables->isEmpty($label)) {
            $label = __('Website')->render();
        }

        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                0,
                $object
            ),
            'values'   => $this->sourceWebsite->toOptionArray(),
            'required' => true
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $fieldSet->addField(
            'website_id',
            'select',
            $config
        );
    }

    /**
     * Adds the website multiselect field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param AbstractModel|null $object
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addWebsiteMultiselectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        ?AbstractModel $object = null,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addWebsiteMultiselectFieldWithValue(
            $fieldSet,
            $objectFieldName,
            $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                0,
                $object
            ),
            $label,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the website multiselect field with value.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param mixed $value
     * @param string|null $label
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addWebsiteMultiselectFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        mixed $value = null,
        ?string $label = null,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        if ($this->variables->isEmpty($label)) {
            $label = __('Website')->render();
        }

        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $value,
            'values'   => $this->sourceWebsite->toOptionArray(),
            'required' => true
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $fieldSet->addField(
            'website_id',
            'multiselect',
            $config
        );
    }

    /**
     * Adds the store select field.
     *
     * @param LayoutInterface $layout
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param AbstractModel|null $object
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool $all
     *
     * @return void
     */
    public function addStoreSelectField(
        LayoutInterface $layout,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        ?AbstractModel $object = null,
        bool $readOnly = false,
        bool $disabled = false,
        bool $all = true
    ): void {
        if (empty($label)) {
            $label = __('Store View')->render();
        }

        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'value'    => $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                0,
                $object
            ),
            'values'   => $this->sourceStore->getStoreValuesForForm(
                false,
                $all
            ),
            'required' => true
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $field = $fieldSet->addField(
            $objectFieldName,
            'multiselect',
            $config
        );

        /** @var Element $renderer */
        $renderer = $layout->createBlock(Element::class);

        $field->setRenderer($renderer);
    }

    /**
     * Adds the store multiselect field.
     *
     * @param LayoutInterface $layout
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param AbstractModel|null $object
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addStoreMultiselectField(
        LayoutInterface $layout,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        ?AbstractModel $object = null,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addStoreMultiselectFieldWithValue(
            $layout,
            $fieldSet,
            $objectFieldName,
            $label,
            $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                0,
                $object
            ),
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the store multiselect field with value.
     *
     * @param LayoutInterface $layout
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string|null $label
     * @param mixed $value
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addStoreMultiselectFieldWithValue(
        LayoutInterface $layout,
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        mixed $value = null,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        if (empty($label)) {
            $label = __('Store View')->render();
        }

        $config = [
            'name'     => sprintf(
                '%s[]',
                $objectFieldName
            ),
            'label'    => $label,
            'title'    => $label,
            'value'    => $value,
            'values'   => $this->sourceStore->getStoreValuesForForm(
                false,
                true
            ),
            'required' => true
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $field = $fieldSet->addField(
            $objectFieldName,
            'multiselect',
            $config
        );

        /** @var Element $renderer */
        $renderer = $layout->createBlock(Element::class);

        $field->setRenderer($renderer);
    }

    /**
     * Adds the store with admin select field.
     *
     * @param LayoutInterface $layout
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addStoreWithAdminSelectField(
        LayoutInterface $layout,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        ?AbstractModel $object = null,
        bool $required = true,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        if (empty($label)) {
            $label = __('Store View')->render();
        }

        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'value'    => $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                0,
                $object
            ),
            'values'   => $this->sourceStoreWithAdmin->getStoreValuesForForm(),
            'required' => $required
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $field = $fieldSet->addField(
            $objectFieldName,
            'select',
            $config
        );

        /** @var Element $renderer */
        $renderer = $layout->createBlock(Element::class);

        $field->setRenderer($renderer);
    }

    /**
     * Adds the cms block select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param mixed $defaultValue
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCmsBlockSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        mixed $defaultValue = null,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        if (empty($label)) {
            $label = __('Block')->render();
        }

        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceCmsBlock->getAllOptions(),
            $defaultValue,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the cms page select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param mixed $defaultValue
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCmsPageSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        mixed $defaultValue = null,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        if (empty($label)) {
            $label = __('Page')->render();
        }

        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceCmsPage->getAllOptions(),
            $defaultValue,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the type id field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $defaultValue
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTypeIdField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        mixed $defaultValue = null,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceTypeIds->toOptionArray(),
            $defaultValue,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the template field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addTemplateField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->templateHelper->getAllTemplates(),
            null,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the categories field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCategoriesField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsMultiSelectField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceCategories->toOptionArray(),
            null,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the operator field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addOperatorField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceOperator->toOptionArray(),
            '==',
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the date iso field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addDateIsoField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = true,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        // convert the date to local time
        $fieldSet->addType(
            'date_iso',
            DateIso::class
        );

        $config = [
            'label'    => $label,
            'value'    => $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                null,
                $object
            ),
            'format'   => $this->dateFormatIso,
            'required' => $required
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $fieldSet->addField(
            $objectFieldName,
            'date_iso',
            $config
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
        $fieldSet->addField(
            $objectFieldName,
            'file',
            [
                'name'      => $objectFieldName,
                'label'     => $label,
                'class'     => 'disable',
                'required'  => $required,
                'css_class' => 'admin__field-file'
            ]
        );
    }

    /**
     * Adds the country field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCountryField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceCountry->toOptionArray(),
            null,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the region field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addRegionField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceRegion->toOptionArray(),
            null,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the region any field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addRegionAnyField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceRegionAny->toOptionArray(),
            null,
            $object,
            $required,
            $readOnly,
            $disabled
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
        $fieldSet->addField(
            $objectFieldName,
            'image',
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'value'    => $object && $object->getId() ? $object->getDataUsingMethod($objectFieldName) : null,
                'required' => $required
            ]
        );
    }

    /**
     * Adds the customer group field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCustomerGroupField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        if ($this->variables->isEmpty($label)) {
            $label = __('Customer Group')->render();
        }

        $customerGroups = $this->getCustomerGroupOptions();

        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $customerGroups,
            null,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the customer group multi select field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addCustomerGroupMultiSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        if ($this->variables->isEmpty($label)) {
            $label = __('Customer Group')->render();
        }

        $customerGroups = $this->getCustomerGroupOptions();

        $this->addOptionsMultiSelectField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $customerGroups,
            null,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the payment active methods field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param AbstractModel|null $object
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
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool $allStores = false,
        bool $withDefault = true
    ): void {
        if ($this->variables->isEmpty($label)) {
            $label = __('Payment Method')->render();
        }

        $this->sourcePaymentActiveMethods->setAllStores($allStores);
        $this->sourcePaymentActiveMethods->setWithDefault($withDefault);

        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourcePaymentActiveMethods->toOptionArray(),
            null,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the product type field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string|null $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addProductTypeField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        if ($this->variables->isEmpty($label)) {
            $label = __('Apply To')->render();
        }

        $config = [
            'name'        => sprintf(
                '%s[]',
                $objectFieldName
            ),
            'label'       => $label,
            'value'       => $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                'all',
                $object
            ),
            'values'      => $this->productType->getOptions(),
            'mode_labels' => [
                'all'    => __('All Product Types')->render(),
                'custom' => __('Selected Product Types')->render()
            ],
            'required'    => $required
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            $readOnly,
            $disabled
        );

        $fieldSet->addField(
            $objectFieldName,
            'apply',
            $config
        );
    }

    /**
     * Adds the wysiwyg field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     *
     * @return void
     */
    public function addWysiwygField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null
    ): void {
        $fieldSet->addType(
            'wysiwyg',
            Wysiwyg::class
        );

        $fieldSet->addField(
            $objectFieldName,
            'wysiwyg',
            [
                'name'  => $objectFieldName,
                'label' => $label,
                'value' => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    null,
                    $object
                )
            ]
        );
    }

    /**
     * Adds the editor field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     *
     * @return void
     */
    public function addEditorField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            'editor',
            [
                'name'   => $objectFieldName,
                'label'  => $label,
                'state'  => 'html',
                'value'  => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    null,
                    $object
                ),
                'style'  => 'height: 400px;',
                'config' => $this->wysiwygConfig->getConfig()
            ]
        );
    }

    /**
     * Adds the eav attribute field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
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
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            'select',
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'value'    => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    null,
                    $object
                ),
                'values'   => $this->sourceAttributes->toOptionArrayWithEntities(
                    $customer,
                    $address,
                    $category,
                    $product
                ),
                'required' => $required
            ]
        );
    }

    /**
     * Adds the eav attribute multiselect field.
     *
     * @param AbstractModel $object
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
    protected function addEavAttributeMultiselectField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            'multiselect',
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'value'    => $object->getDataUsingMethod($objectFieldName),
                'values'   => $this->sourceAttributes->toOptionArrayWithEntities(
                    $customer,
                    $address,
                    $category,
                    $product
                ),
                'required' => $required
            ]
        );
    }

    /**
     * Adds the eav attribute field with update.
     *
     * @param AbstractModel $object
     * @param string $objectName
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
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
        AbstractModel $object,
        string $objectName,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $multiSelect = false
    ): void {
        $this->addEavAttributeSelectFieldWithUpdate(
            $object,
            $objectName,
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $targetFieldNames,
            $this->sourceAttributes->toOptionArray(),
            $required,
            $multiSelect
        );
    }

    /**
     * Adds the eav attribute product field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addEavAttributeProductField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceAttributeProduct->toOptionArray(),
            null,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the eav attribute product field with update.
     *
     * @param AbstractModel $object
     * @param string $objectName
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
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
        AbstractModel $object,
        string $objectName,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $multiSelect = false
    ): void {
        $this->addEavAttributeSelectFieldWithUpdate(
            $object,
            $objectName,
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $targetFieldNames,
            $this->sourceAttributeProduct->toOptionArray(),
            $required,
            $multiSelect
        );
    }

    /**
     * Adds the eav attribute product filterable field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addEavAttributeProductFilterableField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceAttributeProductFilterable->toOptionArray(),
            null,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the eav attribute product filterable field with update.
     *
     * @param AbstractModel $object
     * @param string $objectName
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
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
        AbstractModel $object,
        string $objectName,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        bool $required = false,
        bool $multiSelect = false
    ): void {
        $this->addEavAttributeSelectFieldWithUpdate(
            $object,
            $objectName,
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $targetFieldNames,
            $this->sourceAttributeProductFilterable->toOptionArray(),
            $required,
            $multiSelect
        );
    }

    /**
     * Adds the eav attribute value field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
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
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectAttributeFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $multiSelect = false
    ): void {
        $valueOptions = [];

        if ($object->getId()) {
            $attributeId = $object->getDataUsingMethod($objectAttributeFieldName);

            if ($attributeId) {
                $attribute = $this->attributeHelper->getAttribute(
                    Product::ENTITY,
                    $this->variables->stringValue($attributeId)
                );

                $valueOptions = $attribute->getSource()->getAllOptions();
            }
        }

        if ($this->variables->isEmpty($valueOptions)) {
            $this->addTextField(
                $fieldSet,
                $objectRegistryKey,
                $objectFieldName,
                $label,
                $object,
                $required
            );
        } else {
            if ($multiSelect) {
                $this->addOptionsMultiSelectField(
                    $fieldSet,
                    $objectRegistryKey,
                    $objectFieldName,
                    $label,
                    $valueOptions,
                    null,
                    $object,
                    $required
                );
            } else {
                $this->addOptionsField(
                    $fieldSet,
                    $objectRegistryKey,
                    $objectFieldName,
                    $label,
                    $valueOptions,
                    null,
                    $object,
                    $required
                );
            }
        }
    }

    /**
     * Adds the eav attribute set field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
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
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            'select',
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'value'    => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    null,
                    $object
                ),
                'values'   => $this->sourceAttributeSets->toOptionArrayWithEntities(
                    $customer,
                    $address,
                    $category,
                    $product
                ),
                'required' => $required
            ]
        );
    }

    /**
     * Adds the eav entity type field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
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
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $customer = false,
        bool $address = false,
        bool $category = false,
        bool $product = true
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            'select',
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'value'    => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    null,
                    $object
                ),
                'values'   => $this->sourceEntityTypes->toOptionArrayWithEntities(
                    $customer,
                    $address,
                    $category,
                    $product
                ),
                'required' => $required
            ]
        );
    }

    /**
     * Adds the product attribute code field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addProductAttributeCodeField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            'select',
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'value'    => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    null,
                    $object
                ),
                'values'   => $this->sourceProductAttributeCode->toOptionArray(),
                'required' => $required
            ]
        );
    }

    /**
     * Adds the customer attribute code field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addCustomerAttributeCodeField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            'select',
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'value'    => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    null,
                    $object
                ),
                'values'   => $this->sourceCustomerAttributeCode->toOptionArray(),
                'required' => $required
            ]
        );
    }

    /**
     * Adds the address attribute code field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addAddressAttributeCodeField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            'select',
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'value'    => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    null,
                    $object
                ),
                'values'   => $this->sourceAddressAttributeCode->toOptionArray(),
                'required' => $required
            ]
        );
    }

    /**
     * Adds the attribute sort by field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $multiSelect
     *
     * @return void
     */
    public function addAttributeSortByField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $multiSelect = false
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            $multiSelect ? 'multiselect' : 'select',
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'value'    => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    null,
                    $object
                ),
                'values'   => $this->sourceAttributeSortBy->toOptionArray(),
                'required' => $required
            ]
        );
    }

    /**
     * Returns the update eav attribute form element JavaScript.
     *
     * @param string $sourceElementId
     * @param string $targetElementId
     * @param bool $multiSelect
     *
     * @return string
     */
    protected function getUpdateEavAttributeFormElementJs(
        string $sourceElementId,
        string $targetElementId,
        bool $multiSelect = false
    ): string {
        return sprintf(
            'updateEavAttributeFormElement(\'%s\', \'%s\', \'%s\', %s);',
            urlencode($this->urlHelper->getBackendUrl('infrangible_backendwidget/attribute_option/values')),
            $sourceElementId,
            $targetElementId,
            var_export(
                $multiSelect,
                true
            )
        );
    }

    /**
     * Adds the checkbox field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $value
     * @param AbstractModel|null $object
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addCheckboxField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        mixed $value,
        ?AbstractModel $object = null,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $fieldValue = $this->getFieldValue(
            $objectRegistryKey,
            $objectFieldName,
            null,
            $object
        );

        $config = [
            'name'      => $objectFieldName,
            'label'     => $label,
            'title'     => $label,
            'value'     => $value,
            'checked'   => $fieldValue == $value,
            'css_class' => 'admin__field-checkbox'
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            false,
            $disabled
        );

        $fieldSet->addField(
            $objectFieldName,
            'checkbox',
            $config,
            $after // @phpstan-ignore argument.type (Magento also accepts the id of the field to add it after)
        );
    }

    /**
     * Adds the simple checkbox field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $value
     * @param bool $checked
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addSimpleCheckboxField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        mixed $value,
        bool $checked = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $config = [
            'name'      => $objectFieldName,
            'label'     => $label,
            'title'     => $label,
            'value'     => $value,
            'checked'   => $checked,
            'css_class' => 'admin__field-checkbox'
        ];

        $config = $this->addReadOnlyAndDisabled(
            $config,
            false,
            $disabled
        );

        $fieldSet->addField(
            $objectFieldName,
            'checkbox',
            $config,
            $after // @phpstan-ignore argument.type (Magento also accepts the id of the field to add it after)
        );
    }

    /**
     * Adds the value field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addValueField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool|string|null $after = false
    ): void {
        $fieldSet->addType(
            'value',
            Value::class
        );

        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                '',
                $object
            ),
            'required' => false
        ];

        $fieldSet->addField(
            $objectFieldName,
            'value',
            $config,
            $after // @phpstan-ignore argument.type (Magento also accepts the id of the field to add it after)
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
        $config = [
            'name'      => $objectFieldName,
            'label'     => $label,
            'value'     => $value,
            'css_class' => 'admin__field-button'
        ];

        if ($dataMageInit) {
            $config[ 'onclick' ] = $onClick;
        }

        if ($dataMageInit) {
            $config[ 'data-mage-init' ] = $dataMageInit;
        }

        $fieldSet->addField(
            $objectFieldName,
            'button',
            $config
        );
    }

    /**
     * Adds the iframe button field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectName
     * @param string $objectField
     * @param string $objectFieldName
     * @param string $label
     * @param string $value
     * @param string $urlPath
     * @param array $urlParameters
     * @param AbstractModel|null $object
     *
     * @phpstan-param array<int|string, mixed> $urlParameters
     *
     * @return void
     */
    public function addIframeButtonField(
        Fieldset $fieldSet,
        string $objectName,
        string $objectField,
        string $objectFieldName,
        string $label,
        string $value,
        string $urlPath,
        array $urlParameters = [],
        ?AbstractModel $object = null
    ): void {
        if ($object) {
            $objectId = $object->getDataUsingMethod($objectField);

            if ($objectId) {
                $urlParameters[ $objectField ] = $objectId;
            }
        }

        $dataMageInit = $this->escaper->escapeHtml(
            (string)json_encode(
                [
                    'infrangible/iframe-button' => [
                        'buttonId' => sprintf(
                            '%s_%s',
                            $objectName,
                            $objectFieldName
                        ),
                        'src'      => $this->urlHelper->getBackendUrl(
                            $urlPath,
                            $urlParameters
                        ),
                        'title'    => $label
                    ],
                ]
            )
        );

        $this->addButtonField(
            $fieldSet,
            $objectFieldName,
            $label,
            $value,
            null,
            $dataMageInit
        );
    }

    /**
     * Adds the theme field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addThemeField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceThemes->toOptionArray(),
            null,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the customer name field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     *
     * @return void
     */
    public function addCustomerNameField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false
    ): void {
        $fieldValue = $this->getFieldValue(
            $objectRegistryKey,
            $objectFieldName,
            '',
            $object
        );

        $fieldSet->addField(
            $objectFieldName,
            Autocomplete::class,
            [
                'name'               => $objectFieldName,
                'label'              => $label,
                'search_collection'  => \Magento\Customer\Model\ResourceModel\Customer\Collection::class,
                'search_fields'      => ['name'],
                'search_expressions' => ['name' => 'CONCAT({{firstname}}, " ", {{lastname}})'],
                'result_id'          => '{{id}}',
                'result_value'       => '{{firstname}} {{lastname}}',
                'result_label'       => '{{firstname}} {{lastname}}',
                'required'           => $required,
                'value'              => $fieldValue,
                'object_id'          => $fieldValue
            ]
        );
    }

    /**
     * Adds the product name field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param string|null $onChange
     *
     * @return void
     */
    public function addProductNameField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        ?string $onChange = null
    ): void {
        $fieldValue = $this->getFieldValue(
            $objectRegistryKey,
            $objectFieldName,
            '',
            $object
        );

        $config = [
            'name'              => $objectFieldName,
            'label'             => $label,
            'search_collection' => \Magento\Catalog\Model\ResourceModel\Product\Collection::class,
            'search_fields'     => ['sku', 'name'],
            'search_conditions' => [
                'status'     => ['eq' => Status::STATUS_ENABLED],
                'visibility' => ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]
            ],
            'result_id'         => '{{id}}',
            'result_value'      => '{{name}}',
            'result_label'      => '{{name}} ({{sku}})',
            'required'          => $required,
            'value'             => $fieldValue,
            'object_id'         => $fieldValue
        ];

        if (! $this->variables->isEmpty($onChange)) {
            $config[ 'onchange' ] = $onChange;
        }

        $fieldSet->addField(
            $objectFieldName,
            Autocomplete::class,
            $config
        );
    }

    /**
     * Adds the product name field with product options.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param array $targetFieldNames
     * @param string $objectName
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $includeWithValues
     *
     * @phpstan-param array<int, string> $targetFieldNames
     *
     * @return void
     */
    public function addProductNameFieldWithProductOptions(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        string $objectName,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $includeWithValues = false
    ): void {
        $onChangeFieldId = sprintf(
            '%s_%s',
            $objectName,
            $objectFieldName
        );

        $onChange = [];

        foreach ($targetFieldNames as $targetFieldName) {
            $targetFieldId = sprintf(
                '%s_%s',
                $objectName,
                $targetFieldName
            );

            $onChange[] = $this->getUpdateProductOptionsFormElementJs(
                $onChangeFieldId,
                $targetFieldId,
                $includeWithValues
            );
        }

        $this->addProductNameField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $object,
            $required,
            implode(
                ';',
                $onChange
            )
        );
    }

    /**
     * Adds the product name field with product option values.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param array $targetFieldNames
     * @param string $objectName
     * @param AbstractModel|null $object
     * @param bool $required
     *
     * @phpstan-param array<int, string> $targetFieldNames
     *
     * @return void
     */
    public function addProductNameFieldWithProductOptionValues(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        string $objectName,
        ?AbstractModel $object = null,
        bool $required = false
    ): void {
        $onChangeFieldId = sprintf(
            '%s_%s',
            $objectName,
            $objectFieldName
        );

        $onChange = [];

        foreach ($targetFieldNames as $targetFieldName) {
            $targetFieldId = sprintf(
                '%s_%s',
                $objectName,
                $targetFieldName
            );

            $onChange[] = $this->getUpdateProductOptionValuesFormElementJs(
                $onChangeFieldId,
                $targetFieldId
            );
        }

        $this->addProductNameField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $object,
            $required,
            implode(
                ';',
                $onChange
            )
        );
    }

    /**
     * Adds the product name field with product options and values.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param array $optionTargetFieldNames
     * @param array $optionValueTargetFieldNames
     * @param string $objectName
     * @param AbstractModel|null $object
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
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $optionTargetFieldNames,
        array $optionValueTargetFieldNames,
        string $objectName,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $includeWithValues = false
    ): void {
        $onChangeFieldId = sprintf(
            '%s_%s',
            $objectName,
            $objectFieldName
        );

        $onChange = [];

        foreach ($optionTargetFieldNames as $targetFieldName) {
            $targetFieldId = sprintf(
                '%s_%s',
                $objectName,
                $targetFieldName
            );

            $onChange[] = $this->getUpdateProductOptionsFormElementJs(
                $onChangeFieldId,
                $targetFieldId,
                $includeWithValues
            );
        }

        foreach ($optionValueTargetFieldNames as $targetFieldName) {
            $targetFieldId = sprintf(
                '%s_%s',
                $objectName,
                $targetFieldName
            );

            $onChange[] = $this->getUpdateProductOptionValuesFormElementJs(
                $onChangeFieldId,
                $targetFieldId
            );
        }

        $this->addProductNameField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $object,
            $required,
            implode(
                ';',
                $onChange
            )
        );
    }

    /**
     * Adds the price field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     *
     * @return void
     */
    public function addPriceField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            Price::class,
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'required' => $required,
                'value'    => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    '',
                    $object
                )
            ]
        );
    }

    /**
     * Adds the discount field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     *
     * @return void
     */
    public function addDiscountField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            Discount::class,
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'required' => $required,
                'value'    => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    '',
                    $object
                )
            ]
        );
    }

    /**
     * Adds the integer field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     *
     * @return void
     */
    public function addIntegerField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false
    ): void {
        $this->addSimpleIntegerField(
            $fieldSet,
            $objectFieldName,
            $label,
            $this->getFieldValue(
                $objectRegistryKey,
                $objectFieldName,
                '',
                $object
            ),
            $required
        );
    }

    /**
     * Adds the simple integer field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $value
     * @param bool $required
     *
     * @return void
     */
    public function addSimpleIntegerField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        mixed $value = null,
        bool $required = false
    ): void {
        $fieldSet->addField(
            $objectFieldName,
            Integer::class,
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'required' => $required,
                'value'    => $value
            ]
        );
    }

    /**
     * Returns the update product options form element JavaScript.
     *
     * @param string $sourceElementId
     * @param string $targetElementId
     * @param bool $includeWithValues
     *
     * @return string
     */
    protected function getUpdateProductOptionsFormElementJs(
        string $sourceElementId,
        string $targetElementId,
        bool $includeWithValues
    ): string {
        return sprintf(
            'updateProductOptionsFormElement(\'%s\', \'%s\', \'%s\');',
            urlencode(
                $this->urlHelper->getBackendUrl(
                    'infrangible_backendwidget/product_option',
                    ['include_with_values' => $includeWithValues]
                )
            ),
            $sourceElementId,
            $targetElementId
        );
    }

    /**
     * Returns the update product option values form element JavaScript.
     *
     * @param string $sourceElementId
     * @param string $targetElementId
     *
     * @return string
     */
    protected function getUpdateProductOptionValuesFormElementJs(
        string $sourceElementId,
        string $targetElementId
    ): string {
        return sprintf(
            'updateProductOptionsFormElement(\'%s\', \'%s\', \'%s\');',
            urlencode($this->urlHelper->getBackendUrl('infrangible_backendwidget/product_option/values')),
            $sourceElementId,
            $targetElementId
        );
    }

    /**
     * Adds the product option field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param mixed $parentObjectValue
     * @param string $objectProductIdFieldName
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $includeWithValues
     * @param bool $excludeWithoutValues
     * @param string|null $onChange
     *
     * @return void
     */
    public function addProductOptionField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        mixed $parentObjectValue,
        string $objectProductIdFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $includeWithValues = false,
        bool $excludeWithoutValues = false,
        ?string $onChange = null
    ): void {
        $valueOptions = [];

        if ($object->getId()) {
            $productId = $object->getDataUsingMethod($objectProductIdFieldName);
        } elseif ($parentObjectValue) {
            $productId = $parentObjectValue;
        } else {
            $productId = null;
        }

        if ($productId) {
            $valueOptions = $this->productOptionHelper->getProductOptions(
                $this->variables->intValue($productId),
                $includeWithValues,
                $excludeWithoutValues
            );
        }

        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $valueOptions,
            null,
            $object,
            $required,
            false,
            false,
            false,
            $onChange
        );
    }

    /**
     * Adds the product option field with type values.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param mixed $parentObjectValue
     * @param string $objectProductIdFieldName
     * @param string $objectFieldName
     * @param string $label
     * @param array $targetFieldNames
     * @param string $objectName
     * @param bool $required
     *
     * @phpstan-param array<int, string> $targetFieldNames
     *
     * @return void
     */
    public function addProductOptionFieldWithTypeValues(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        mixed $parentObjectValue,
        string $objectProductIdFieldName,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        string $objectName,
        bool $required = false
    ): void {
        $onChangeFieldId = sprintf(
            '%s_%s',
            $objectName,
            $objectFieldName
        );

        $onChange = [];

        foreach ($targetFieldNames as $targetFieldName) {
            $targetFieldId = sprintf(
                '%s_%s',
                $objectName,
                $targetFieldName
            );

            $onChange[] = $this->getUpdateProductOptionTypeValuesFormElementJs(
                $onChangeFieldId,
                $targetFieldId
            );
        }

        $this->addProductOptionField(
            $object,
            $fieldSet,
            $objectRegistryKey,
            $parentObjectValue,
            $objectProductIdFieldName,
            $objectFieldName,
            $label,
            $required,
            true,
            true,
            implode(
                ';',
                $onChange
            )
        );
    }

    /**
     * Returns the update product option type values form element JavaScript.
     *
     * @param string $sourceElementId
     * @param string $targetElementId
     *
     * @return string
     */
    protected function getUpdateProductOptionTypeValuesFormElementJs(
        string $sourceElementId,
        string $targetElementId
    ): string {
        return sprintf(
            'updateOptionTypesFormElement(\'%s\', \'%s\', \'%s\');',
            urlencode($this->urlHelper->getBackendUrl('infrangible_backendwidget/product_option/typeValues')),
            $sourceElementId,
            $targetElementId
        );
    }

    /**
     * Adds the product option value field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectProductIdFieldName
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addProductOptionValueField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectProductIdFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $valueOptions = [];

        if ($object->getId()) {
            $productId = $object->getDataUsingMethod($objectProductIdFieldName);

            if ($productId) {
                $valueOptions =
                    $this->productOptionHelper->getProductOptionValues($this->variables->intValue($productId));
            }
        }

        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $valueOptions,
            null,
            $object,
            $required
        );
    }

    /**
     * Adds the product option type value field.
     *
     * @param AbstractModel $object
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectOptionIdFieldName
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     *
     * @return void
     */
    public function addProductOptionTypeValueField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectOptionIdFieldName,
        string $objectFieldName,
        string $label,
        bool $required = false
    ): void {
        $valueOptions = [];

        if ($object->getId()) {
            $optionId = $object->getDataUsingMethod($objectOptionIdFieldName);

            if ($optionId) {
                $valueOptions =
                    $this->productOptionHelper->getProductOptionTypeValues($this->variables->intValue($optionId));
            }
        }

        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $valueOptions,
            null,
            $object,
            $required
        );
    }

    /**
     * Adds the simple date field with value.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $value
     * @param bool $required
     *
     * @return void
     */
    public function addSimpleDateFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        mixed $value,
        bool $required = false
    ): void {
        $dateFormat = $this->localeDate->getDateFormat();

        $config = [
            'name'        => $objectFieldName,
            'label'       => $label,
            'title'       => $label,
            'date_format' => $dateFormat,
            'required'    => $required
        ];

        if ($value) {
            $config[ 'value' ] = $value;
        }

        $fieldSet->addField(
            $objectFieldName,
            'date',
            $config
        );
    }

    /**
     * Adds the simple date time field with value.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param mixed $value
     * @param bool $required
     *
     * @return void
     */
    public function addSimpleDateTimeFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        mixed $value,
        bool $required = false
    ): void {
        $dateFormat = $this->localeDate->getDateFormat();
        /** @noinspection PhpParamsInspection Magento passes the type to IntlDateFormatter, which expects an integer */
        // @phpstan-ignore argument.type (Magento passes the type to IntlDateFormatter, which expects an integer)
        $timeFormat = $this->localeDate->getTimeFormat(IntlDateFormatter::SHORT);

        $config = [
            'name'        => $objectFieldName,
            'label'       => $label,
            'title'       => $label,
            'date_format' => $dateFormat,
            'time_format' => $timeFormat,
            'required'    => $required
        ];

        if ($value) {
            $config[ 'value' ] = $value;
        }

        $fieldSet->addField(
            $objectFieldName,
            'date',
            $config
        );
    }

    /**
     * Adds the filter condition type field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param AbstractModel|null $object
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @return void
     */
    public function addFilterConditionTypeField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ): void {
        $this->addOptionsField(
            $fieldSet,
            $objectRegistryKey,
            $objectFieldName,
            $label,
            $this->sourceFilterConditionType->toOptionArray(),
            0,
            $object,
            $required,
            $readOnly,
            $disabled
        );
    }

    /**
     * Adds the simple filter condition type field.
     *
     * @param Fieldset $fieldSet
     * @param string $objectFieldName
     * @param string $label
     * @param bool $required
     * @param bool $readOnly
     * @param bool $disabled
     * @param bool|string|null $after
     *
     * @return void
     */
    public function addSimpleFilterConditionTypeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool|string|null $after = false
    ): void {
        $this->addSimpleOptionsField(
            $fieldSet,
            $objectFieldName,
            $label,
            $this->sourceFilterConditionType->toOptionArray(),
            $required,
            $readOnly,
            $disabled,
            $after
        );
    }

    /**
     * Returns the configuration of a field with the settings for read-only and disabled fields added.
     *
     * @param array $config
     * @param bool $readOnly
     * @param bool $disabled
     *
     * @phpstan-param array<string, mixed> $config
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    private function addReadOnlyAndDisabled(array $config, bool $readOnly, bool $disabled): array
    {
        $cssClasses = [];

        if (array_key_exists(
            'css_class',
            $config
        ) && is_string($config[ 'css_class' ]) && $config[ 'css_class' ] !== '') {
            $cssClasses[] = $config[ 'css_class' ];
        }

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            $cssClasses[] = 'read-only';
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            $cssClasses[] = 'disabled';
        }

        if (count($cssClasses) > 0) {
            $config[ 'css_class' ] = implode(
                ' ',
                $cssClasses
            );
        }

        return $config;
    }

    /**
     * Adds a select field of attributes that updates the options of the target fields when it is changed.
     *
     * @param AbstractModel $object
     * @param string $objectName
     * @param Fieldset $fieldSet
     * @param string $objectRegistryKey
     * @param string $objectFieldName
     * @param string $label
     * @param array $targetFieldNames
     * @param array $values
     * @param bool $required
     * @param bool $multiSelect
     *
     * @phpstan-param array<int, string> $targetFieldNames
     * @phpstan-param array<int|string, mixed> $values
     *
     * @return void
     */
    private function addEavAttributeSelectFieldWithUpdate(
        AbstractModel $object,
        string $objectName,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $targetFieldNames,
        array $values,
        bool $required,
        bool $multiSelect
    ): void {
        $onChangeFieldId = sprintf(
            '%s_%s',
            $objectName,
            $objectFieldName
        );

        $onChange = [];

        foreach ($targetFieldNames as $targetFieldName) {
            $targetFieldId = sprintf(
                '%s_%s',
                $objectName,
                $targetFieldName
            );

            $onChange[] = $this->getUpdateEavAttributeFormElementJs(
                $onChangeFieldId,
                $targetFieldId,
                $multiSelect
            );
        }

        $fieldSet->addField(
            $objectFieldName,
            'select',
            [
                'name'     => $objectFieldName,
                'label'    => $label,
                'value'    => $this->getFieldValue(
                    $objectRegistryKey,
                    $objectFieldName,
                    null,
                    $object
                ),
                'values'   => $values,
                'required' => $required,
                'onchange' => implode(
                    ';',
                    $onChange
                )
            ]
        );
    }

    /**
     * Returns the customer groups as options, sorted by code.
     *
     * @return array
     * @phpstan-return array<int, array{value: mixed, label: string}>
     */
    private function getCustomerGroupOptions(): array
    {
        $this->customerGroupCollection->getSelect()->order('customer_group_code ASC');
        $this->customerGroupCollection->loadData();

        $customerGroups = [['value' => '', 'label' => __('--Please Select--')->render()]];

        /** @var Group $customerGroup */
        foreach ($this->customerGroupCollection as $customerGroup) {
            $customerGroups[] = ['value' => $customerGroup->getId(), 'label' => $customerGroup->getCode()];
        }

        return $customerGroups;
    }
}
