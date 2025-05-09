<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Helper;

use Exception;
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

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Form
{
    /** @var Variables */
    protected $variables;

    /** @var Arrays */
    protected $arrays;

    /** @var Template */
    protected $templateHelper;

    /** @var Url */
    protected $urlHelper;

    /** @var Customer */
    protected $customerHelper;

    /** @var \Infrangible\Core\Helper\Attribute */
    protected $attributeHelper;

    /** @var Instances */
    protected $instanceHelper;

    /** @var Session */
    protected $adminhtmlSession;

    /** @var FormFactory */
    protected $formFactory;

    /** @var Yesno */
    protected $sourceYesNo;

    /** @var Website */
    protected $sourceWebsite;

    /** @var Store */
    protected $sourceStore;

    /** @var \Infrangible\BackendWidget\Model\Store\System\Store */
    protected $sourceStoreWithAdmin;

    /** @var CmsBlock */
    protected $sourceCmsBlock;

    /** @var CmsPage */
    protected $sourceCmsPage;

    /** @var TypeId */
    protected $sourceTypeIds;

    /** @var Categories */
    protected $sourceCategories;

    /** @var Operator */
    protected $sourceOperator;

    /** @var Country */
    protected $sourceCountry;

    /** @var Region */
    protected $sourceRegion;

    /** @var RegionAny */
    protected $sourceRegionAny;

    /** @var ActiveMethods */
    protected $sourcePaymentActiveMethods;

    /** @var Attribute */
    protected $sourceAttributes;

    /** @var AttributeSet */
    protected $sourceAttributeSets;

    /** @var EntityType */
    protected $sourceEntityTypes;

    /** @var ProductAttributeCode */
    protected $sourceProductAttributeCode;

    /** @var CustomerAttributeCode */
    protected $sourceCustomerAttributeCode;

    /** @var AddressAttributeCode */
    protected $sourceAddressAttributeCode;

    /** @var SortBy */
    protected $sourceAttributeSortBy;

    /** @var Theme */
    protected $sourceThemes;

    /** @var Attribute\Product */
    protected $sourceAttributeProduct;

    /** @var Filterable */
    protected $sourceAttributeProductFilterable;

    /** @var Collection */
    protected $customerGroupCollection;

    /** @var TimezoneInterface */
    protected $localeDate;

    /** @var string */
    protected $dateFormatIso;

    /** @var Type */
    protected $productType;

    /** @var Config */
    protected $wysiwygConfig;

    /** @var Escaper */
    protected $escaper;

    /** @var ProductOption */
    protected $productOptionHelper;

    /** @var FilterConditionType */
    protected $sourceFilterConditionType;

    public function __construct(
        Variables $variables,
        Arrays $arrays,
        Template $templateHelper,
        Url $urlHelper,
        Customer $customerHelper,
        \Infrangible\Core\Helper\Attribute $attributeHelper,
        Instances $instanceHelper,
        Session $adminhtmlSession,
        FormFactory $formFactory,
        Yesno $sourceYesNo,
        Website $sourceWebsite,
        Store $sourceStore,
        \Infrangible\BackendWidget\Model\Store\System\Store $sourceStoreWithAdmin,
        CmsBlock $sourceCmsBlock,
        CmsPage $sourceCmsPage,
        TypeId $sourceTypeIds,
        Categories $sourceCategories,
        Operator $sourceOperator,
        Country $sourceCountry,
        Region $sourceRegion,
        RegionAny $sourceRegionAny,
        ActiveMethods $sourcePaymentActiveMethods,
        Attribute $sourceAttributes,
        AttributeSet $sourceAttributeSets,
        EntityType $sourceEntityTypes,
        ProductAttributeCode $sourceProductAttributeCode,
        CustomerAttributeCode $sourceCustomerAttributeCode,
        AddressAttributeCode $sourceAddressAttributeCode,
        SortBy $sourceAttributeSortBy,
        Theme $sourceThemes,
        Attribute\Product $sourceAttributeProduct,
        Filterable $sourceAttributeProductFilterable,
        TimezoneInterface $localeDate,
        Type $productType,
        Config $wysiwygConfig,
        Escaper $escaper,
        ProductOption $productOptionHelper,
        FilterConditionType $sourceFilterConditionType
    ) {
        $this->variables = $variables;
        $this->arrays = $arrays;
        $this->templateHelper = $templateHelper;
        $this->urlHelper = $urlHelper;
        $this->customerHelper = $customerHelper;
        $this->attributeHelper = $attributeHelper;
        $this->instanceHelper = $instanceHelper;
        $this->adminhtmlSession = $adminhtmlSession;
        $this->formFactory = $formFactory;
        $this->sourceYesNo = $sourceYesNo;
        $this->sourceWebsite = $sourceWebsite;
        $this->sourceStore = $sourceStore;
        $this->sourceStoreWithAdmin = $sourceStoreWithAdmin;
        $this->sourceCmsBlock = $sourceCmsBlock;
        $this->sourceCmsPage = $sourceCmsPage;
        $this->sourceTypeIds = $sourceTypeIds;
        $this->sourceCategories = $sourceCategories;
        $this->sourceOperator = $sourceOperator;
        $this->sourceCountry = $sourceCountry;
        $this->sourceRegion = $sourceRegion;
        $this->sourceRegionAny = $sourceRegionAny;
        $this->sourcePaymentActiveMethods = $sourcePaymentActiveMethods;
        $this->sourceAttributes = $sourceAttributes;
        $this->sourceAttributeSets = $sourceAttributeSets;
        $this->sourceEntityTypes = $sourceEntityTypes;
        $this->sourceProductAttributeCode = $sourceProductAttributeCode;
        $this->sourceCustomerAttributeCode = $sourceCustomerAttributeCode;
        $this->sourceAddressAttributeCode = $sourceAddressAttributeCode;
        $this->sourceAttributeSortBy = $sourceAttributeSortBy;
        $this->sourceThemes = $sourceThemes;
        $this->sourceAttributeProduct = $sourceAttributeProduct;
        $this->sourceAttributeProductFilterable = $sourceAttributeProductFilterable;
        $this->customerGroupCollection = $this->customerHelper->getCustomerGroupCollection();
        $this->localeDate = $localeDate;
        $this->dateFormatIso = $localeDate->getDateTimeFormat(IntlDateFormatter::MEDIUM);
        $this->productType = $productType;
        $this->wysiwygConfig = $wysiwygConfig;
        $this->escaper = $escaper;
        $this->productOptionHelper = $productOptionHelper;
        $this->sourceFilterConditionType = $sourceFilterConditionType;
    }

    /**
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

    public function getFieldValue(
        string $objectRegistryKey,
        string $objectFieldName,
        $defaultValue = null,
        ?AbstractModel $object = null,
        ?string $splitObjectValueSeparator = null
    ) {
        $formData = $this->adminhtmlSession->getData(
            sprintf(
                '%s_form_%s',
                $objectRegistryKey,
                $object && $object->getId() ? $object->getId() : 'add'
            )
        );

        if (is_object($formData) && method_exists(
                $formData,
                'toArray'
            )) {
            $formData = $formData->toArray();
        }

        if ($this->variables->isEmpty($formData)) {
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
                    $objectValue
                );
            }

            return $objectValue;
        }

        return $defaultValue;
    }

    /**
     * @param bool|string|null $after
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
        $after = false
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
     * @param bool|string|null $after
     */
    public function addSimpleTextField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
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
     * @param bool|string|null $after
     */
    public function addSimpleTextFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $value,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
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
     * @param bool|string|null $after
     */
    public function addSimpleTextFieldWithValueAndNote(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $value,
        ?string $note = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
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
     * @param bool|string|null $after
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
        $after = false
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
     * @param bool|string|null $after
     */
    public function addSimpleTextFieldWithValueAndNoteAndClass(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $value = null,
        ?string $note = null,
        ?string $class = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            $objectFieldName,
            'text',
            $config,
            $after
        );
    }

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

    public function addSimpleTextareaFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $value,
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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            $objectFieldName,
            'textarea',
            $config
        );
    }

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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            $objectFieldName,
            'textarea',
            $config
        );
    }

    /**
     * @param bool|string|null $after
     */
    public function addOptionsField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $options,
        $defaultValue,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false,
        $onChange = null
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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        if (! $this->variables->isEmpty($onChange)) {
            $config[ 'onchange' ] = $onChange;
        }

        $fieldSet->addField(
            $objectFieldName,
            'select',
            $config,
            $after
        );
    }

    /**
     * @param bool|string|null $after
     */
    public function addSimpleOptionsField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
    ): void {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'values'   => $options,
            'required' => $required
        ];

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            $objectFieldName,
            'select',
            $config,
            $after
        );
    }

    /**
     * @param bool|string|null $after
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
        $after = false
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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            $objectFieldName,
            'select',
            $config,
            $after
        );
    }

    /**
     * @param bool|string|null $after
     *
     * @throws Exception
     */
    public function addOptionsClassField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        string $className,
        $defaultValue,
        ?AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
    ): void {
        /** @var OptionSourceInterface $optionsClass */
        $optionsClass = $this->instanceHelper->getSingleton($className);

        if (method_exists(
            $optionsClass,
            'toOptionArray'
        )) {
            $options = $optionsClass->toOptionArray();
        } else {
            throw new Exception(
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

    public function addOptionsMultiSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $options,
        $defaultValue,
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

    public function addSimpleOptionsMultiSelectField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        array $options,
        $value = null,
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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            $objectFieldName,
            'multiselect',
            $config
        );
    }

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

    public function addSimpleYesNoField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
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

    public function addYesNoWithDefaultField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        $defaultValue,
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

    public function addWebsiteSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            'website_id',
            'select',
            $config
        );
    }

    public function addWebsiteMultiselectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
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

    public function addWebsiteMultiselectFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        $value = null,
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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            'website_id',
            'multiselect',
            $config
        );
    }

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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $field = $fieldSet->addField(
            $objectFieldName,
            'multiselect',
            $config
        );

        /** @var Element $renderer */
        $renderer = $layout->createBlock(Element::class);

        if ($renderer) {
            $field->setRenderer($renderer);
        }
    }

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

    public function addStoreMultiselectFieldWithValue(
        LayoutInterface $layout,
        Fieldset $fieldSet,
        string $objectFieldName,
        ?string $label = null,
        $value = null,
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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $field = $fieldSet->addField(
            $objectFieldName,
            'multiselect',
            $config
        );

        /** @var Element $renderer */
        $renderer = $layout->createBlock(Element::class);

        if ($renderer) {
            $field->setRenderer($renderer);
        }
    }

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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $field = $fieldSet->addField(
            $objectFieldName,
            'select',
            $config
        );

        /** @var Element $renderer */
        $renderer = $layout->createBlock('Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element');

        if ($renderer) {
            $field->setRenderer($renderer);
        }
    }

    public function addCmsBlockSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        $defaultValue = null,
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

    public function addCmsPageSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        ?string $label = null,
        $defaultValue = null,
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

    public function addTypeIdField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        $defaultValue = null,
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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            $objectFieldName,
            'date_iso',
            $config
        );
    }

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

        $this->customerGroupCollection->getSelect()->order('customer_group_code ASC');
        $this->customerGroupCollection->loadData();

        $customerGroups = [['value' => '', 'label' => __('--Please Select--')->render()]];

        /** @var Group $customerGroup */
        foreach ($this->customerGroupCollection as $customerGroup) {
            $customerGroups[] = ['value' => $customerGroup->getId(), 'label' => $customerGroup->getCode()];
        }

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

        $this->customerGroupCollection->getSelect()->order('customer_group_code ASC');
        $this->customerGroupCollection->loadData();

        $customerGroups = [['value' => '', 'label' => __('--Please Select--')->render()]];

        /** @var Group $customerGroup */
        foreach ($this->customerGroupCollection as $customerGroup) {
            $customerGroups[] = ['value' => $customerGroup->getId(), 'label' => $customerGroup->getCode()];
        }

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
     * @throws LocalizedException
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

        if ($readOnly) {
            $config[ 'readonly' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' read-only';
            } else {
                $config[ 'css_class' ] = 'read-only';
            }
        }

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            $objectFieldName,
            'apply',
            $config
        );
    }

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
                'values'   => $this->sourceAttributes->toOptionArray(),
                'required' => $required,
                'onchange' => implode(
                    ';',
                    $onChange
                )
            ]
        );
    }

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
                'values'   => $this->sourceAttributeProduct->toOptionArray(),
                'required' => $required,
                'onchange' => implode(
                    ';',
                    $onChange
                )
            ]
        );
    }

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
                'values'   => $this->sourceAttributeProductFilterable->toOptionArray(),
                'required' => $required,
                'onchange' => implode(
                    ';',
                    $onChange
                )
            ]
        );
    }

    /**
     * @throws Exception
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
                    $attributeId
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
     * @param bool|string|null $after
     */
    public function addCheckboxField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        $value,
        ?AbstractModel $object = null,
        bool $disabled = false,
        $after = false
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

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            $objectFieldName,
            'checkbox',
            $config,
            $after
        );
    }

    /**
     * @param bool|string|null $after
     */
    public function addSimpleCheckboxField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $value,
        bool $checked = false,
        bool $disabled = false,
        $after = false
    ): void {
        $config = [
            'name'      => $objectFieldName,
            'label'     => $label,
            'title'     => $label,
            'value'     => $value,
            'checked'   => $checked,
            'css_class' => 'admin__field-checkbox'
        ];

        if ($disabled) {
            $config[ 'disabled' ] = true;
            if (array_key_exists(
                'css_class',
                $config
            )) {
                $config[ 'css_class' ] .= ' disabled';
            } else {
                $config[ 'css_class' ] = 'disabled';
            }
        }

        $fieldSet->addField(
            $objectFieldName,
            'checkbox',
            $config,
            $after
        );
    }

    /**
     * @param bool|string|null $after
     */
    public function addValueField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        ?AbstractModel $object = null,
        $after = false
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
            $after
        );
    }

    public function addButtonField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $value,
        $onClick = null,
        $dataMageInit = null
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
            json_encode(
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

    public function addSimpleIntegerField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $value = null,
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
     * @throws Exception
     */
    public function addProductOptionField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        $parentObjectValue,
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
     * @throws Exception
     */
    public function addProductOptionFieldWithTypeValues(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        $parentObjectValue,
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
     * @throws Exception
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
     * @throws Exception
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

    public function addSimpleDateFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $value,
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

    public function addSimpleDateTimeFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        $value,
        bool $required = false
    ): void {
        $dateFormat = $this->localeDate->getDateFormat();
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

    public function addSimpleFilterConditionTypeField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
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
}
