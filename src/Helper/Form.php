<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Block\Config\Form\DateIso;
use Infrangible\BackendWidget\Block\Config\Form\Value;
use Infrangible\BackendWidget\Block\Config\Form\Wysiwyg;
use Infrangible\BackendWidget\Model\Backend\Session;
use Infrangible\Core\Helper\Customer;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Template;
use Infrangible\Core\Helper\Url;
use Infrangible\Core\Model\Config\Source\Attribute;
use Infrangible\Core\Model\Config\Source\Attribute\AddressAttributeCode;
use Infrangible\Core\Model\Config\Source\Attribute\CustomerAttributeCode;
use Infrangible\Core\Model\Config\Source\Attribute\ProductAttributeCode;
use Infrangible\Core\Model\Config\Source\Attribute\SortBy;
use Infrangible\Core\Model\Config\Source\AttributeSet;
use Infrangible\Core\Model\Config\Source\Categories;
use Infrangible\Core\Model\Config\Source\CmsBlock;
use Infrangible\Core\Model\Config\Source\CmsPage;
use Infrangible\Core\Model\Config\Source\EntityType;
use Infrangible\Core\Model\Config\Source\Operator;
use Infrangible\Core\Model\Config\Source\Payment\ActiveMethods;
use Infrangible\Core\Model\Config\Source\TypeId;
use IntlDateFormatter;
use Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Config\Model\Config\Source\Website;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\ResourceModel\Address\Attribute\Source\Region;
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
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
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

    /** @var Collection */
    protected $customerGroupCollection;

    /** @var string */
    protected $dateFormatIso;

    /** @var Type */
    protected $productType;

    /** @var Config */
    protected $wysiwygConfig;

    /** @var Escaper */
    protected $escaper;

    /**
     * @param Variables                          $variables
     * @param Arrays                             $arrays
     * @param Template                           $templateHelper
     * @param Url                                $urlHelper
     * @param Customer                           $customerHelper
     * @param \Infrangible\Core\Helper\Attribute $attributeHelper
     * @param Instances                          $instanceHelper
     * @param Session                            $adminhtmlSession
     * @param FormFactory                        $formFactory
     * @param Yesno                              $sourceYesNo
     * @param Website                            $sourceWebsite
     * @param Store                              $sourceStore
     * @param \Infrangible\BackendWidget\Model\Store\System\Store $sourceStoreWithAdmin
     * @param CmsBlock                                            $sourceCmsBlock
     * @param CmsPage                                             $sourceCmsPage
     * @param TypeId                                              $sourceTypeIds
     * @param Categories                                          $sourceCategories
     * @param Operator                                            $sourceOperator
     * @param Country                                             $sourceCountry
     * @param Region                                              $sourceRegion
     * @param ActiveMethods                                       $sourcePaymentActiveMethods
     * @param Attribute                                           $sourceAttributes
     * @param AttributeSet                                        $sourceAttributeSets
     * @param EntityType                                          $sourceEntityTypes
     * @param ProductAttributeCode                                $sourceProductAttributeCode
     * @param CustomerAttributeCode                               $sourceCustomerAttributeCode
     * @param AddressAttributeCode                                $sourceAddressAttributeCode
     * @param SortBy                                              $sourceAttributeSortBy
     * @param Theme                                               $sourceThemes
     * @param TimezoneInterface                                   $localeDate
     * @param Type                                                $productType
     * @param Config                                              $wysiwygConfig
     * @param Escaper                                             $escaper
     */
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
        ActiveMethods $sourcePaymentActiveMethods,
        Attribute $sourceAttributes,
        AttributeSet $sourceAttributeSets,
        EntityType $sourceEntityTypes,
        ProductAttributeCode $sourceProductAttributeCode,
        CustomerAttributeCode $sourceCustomerAttributeCode,
        AddressAttributeCode $sourceAddressAttributeCode,
        SortBy $sourceAttributeSortBy,
        Theme $sourceThemes,
        TimezoneInterface $localeDate,
        Type $productType,
        Config $wysiwygConfig,
        Escaper $escaper
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
        $this->sourcePaymentActiveMethods = $sourcePaymentActiveMethods;
        $this->sourceAttributes = $sourceAttributes;
        $this->sourceAttributeSets = $sourceAttributeSets;
        $this->sourceEntityTypes = $sourceEntityTypes;
        $this->sourceProductAttributeCode = $sourceProductAttributeCode;
        $this->sourceCustomerAttributeCode = $sourceCustomerAttributeCode;
        $this->sourceAddressAttributeCode = $sourceAddressAttributeCode;
        $this->sourceAttributeSortBy = $sourceAttributeSortBy;
        $this->sourceThemes = $sourceThemes;
        $this->customerGroupCollection = $this->customerHelper->getCustomerGroupCollection();
        $this->dateFormatIso = $localeDate->getDateTimeFormat(IntlDateFormatter::MEDIUM);
        $this->productType = $productType;
        $this->wysiwygConfig = $wysiwygConfig;
        $this->escaper = $escaper;
    }

    /**
     * @param string             $saveUrlRoute
     * @param array              $saveUrlParams
     * @param bool               $isUpload
     * @param string             $formId
     * @param string|null        $htmlIdPrefix
     * @param AbstractModel|null $object
     * @param string|null        $objectField
     *
     * @return \Magento\Framework\Data\Form
     * @throws LocalizedException
     */
    public function createPostForm(
        string $saveUrlRoute,
        array $saveUrlParams,
        bool $isUpload = false,
        string $formId = 'edit_form',
        string $htmlIdPrefix = null,
        AbstractModel $object = null,
        string $objectField = null
    ): \Magento\Framework\Data\Form {
        if (empty($objectField)) {
            $objectField = 'id';
        }

        $form = $this->formFactory->create();

        if ($object && $object->getId()) {
            $saveUrlParams[$objectField] = $object->getId();
        }

        $form->setData('id', $formId);
        $form->setData('action', $this->urlHelper->getBackendUrl($saveUrlRoute, $saveUrlParams));
        $form->setData('method', 'post');
        $form->setData('use_container', true);

        if ($isUpload) {
            $form->setData('enctype', 'multipart/form-data');
        }

        if (!$this->variables->isEmpty($htmlIdPrefix)) {
            $form->setData('html_id_prefix', sprintf('%s_', $htmlIdPrefix));
        }

        return $form;
    }

    /**
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param mixed              $defaultValue
     * @param AbstractModel|null $object
     * @param string|null        $splitObjectValueSeparator
     *
     * @return mixed
     */
    public function getFieldValue(
        string $objectRegistryKey,
        string $objectFieldName,
        $defaultValue = null,
        AbstractModel $object = null,
        string $splitObjectValueSeparator = null
    ) {
        $formData = $this->adminhtmlSession->getData(
            sprintf(
                '%s_form_%s',
                $objectRegistryKey,
                $object && $object->getId() ? $object->getId() : 'add'
            )
        );

        if (is_object($formData) && method_exists($formData, 'toArray')) {
            $formData = $formData->toArray();
        }

        if ($this->variables->isEmpty($formData)) {
            $formData = [];
        }

        if (array_key_exists($objectFieldName, $formData)) {
            return $this->arrays->getValue($formData, $objectFieldName);
        }

        if ($object instanceof AbstractModel && $object->getId()) {
            $objectValue = $object->getDataUsingMethod($objectFieldName);

            if (!$this->variables->isEmpty($splitObjectValueSeparator)) {
                $objectValue = explode(',', $objectValue);
            }

            return $objectValue;
        }

        return $defaultValue;
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     * @param mixed              $after
     */
    public function addTextField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
    ) {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, '', $object),
            'required' => $required
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $fieldSet->addField($objectFieldName, 'text', $config, $after);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param string             $after
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addTextFieldAfter(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        string $after,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addTextareaField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, '', $object),
            'required' => $required
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $fieldSet->addField($objectFieldName, 'textarea', $config);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param string             $comment
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addTextareaWithCommentField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        string $comment,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $config = [
            'name'               => $objectFieldName,
            'label'              => $label,
            'value'              => $this->getFieldValue($objectRegistryKey, $objectFieldName, '', $object),
            'required'           => $required,
            'after_element_html' => sprintf('<div>%s</div>', nl2br($comment))
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $fieldSet->addField($objectFieldName, 'textarea', $config);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param array              $options
     * @param mixed              $defaultValue
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     * @param mixed              $after
     */
    public function addOptionsField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $options,
        $defaultValue,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
    ) {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, $defaultValue, $object),
            'values'   => $options,
            'required' => $required
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $fieldSet->addField($objectFieldName, 'select', $config, $after);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param string             $className
     * @param mixed              $defaultValue
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     * @param mixed              $after
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
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        $after = false
    ) {
        /** @var OptionSourceInterface $optionsClass */
        $optionsClass = $this->instanceHelper->getSingleton($className);

        if (method_exists($optionsClass, 'toOptionArray')) {
            $options = $optionsClass->toOptionArray();
        } else {
            throw new Exception(sprintf('Options class: %s does not implement method: toOptions', $className));
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
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
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        array $options,
        $defaultValue,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, $defaultValue, $object),
            'values'   => $options,
            'required' => $required
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $fieldSet->addField($objectFieldName, 'multiselect', $config);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addYesNoField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectFieldName
     * @param string             $objectRegistryKey
     * @param string             $label
     * @param string             $after
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addYesNoFieldAfter(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        string $after,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param mixed              $defaultValue
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addYesNoWithDefaultField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        $defaultValue,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param AbstractModel|null $object
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addWebsiteSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        AbstractModel $object = null,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        if ($this->variables->isEmpty($label)) {
            $label = __('Website')->render();
        }

        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, 0, $object),
            'values'   => $this->sourceWebsite->toOptionArray(),
            'required' => true
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $fieldSet->addField('website_id', 'select', $config);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param AbstractModel|null $object
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addWebsiteMultiselectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        AbstractModel $object = null,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->addWebsiteMultiselectFieldWithValue(
            $fieldSet,
            $objectFieldName,
            $this->getFieldValue($objectRegistryKey, $objectFieldName, 0, $object),
            $label,
            $readOnly,
            $disabled
        );
    }

    /**
     * @param Fieldset    $fieldSet
     * @param mixed       $value
     * @param string      $objectFieldName
     * @param string|null $label
     * @param bool        $readOnly
     * @param bool        $disabled
     */
    public function addWebsiteMultiselectFieldWithValue(
        Fieldset $fieldSet,
        string $objectFieldName,
        $value = null,
        string $label = null,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $fieldSet->addField('website_id', 'multiselect', $config);
    }

    /**
     * @param LayoutInterface    $layout
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param AbstractModel|null $object
     * @param bool               $readOnly
     * @param bool               $disabled
     * @param bool               $all
     */
    public function addStoreSelectField(
        LayoutInterface $layout,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        AbstractModel $object = null,
        bool $readOnly = false,
        bool $disabled = false,
        bool $all = true
    ) {
        if (empty($label)) {
            $label = __('Store View')->render();
        }

        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, 0, $object),
            'values'   => $this->sourceStore->getStoreValuesForForm(false, $all),
            'required' => true
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $field = $fieldSet->addField($objectFieldName, 'multiselect', $config);

        /** @var Element $renderer */
        $renderer = $layout->createBlock(Element::class);

        if ($renderer) {
            $field->setRenderer($renderer);
        }
    }

    /**
     * @param LayoutInterface    $layout
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param AbstractModel|null $object
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addStoreMultiselectField(
        LayoutInterface $layout,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        AbstractModel $object = null,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        $this->addStoreMultiselectFieldWithValue(
            $layout,
            $fieldSet,
            $objectFieldName,
            $label,
            $this->getFieldValue($objectRegistryKey, $objectFieldName, 0, $object),
            $readOnly,
            $disabled
        );
    }

    /**
     * @param LayoutInterface $layout
     * @param Fieldset        $fieldSet
     * @param string          $objectFieldName
     * @param string|null     $label
     * @param mixed           $value
     * @param bool            $readOnly
     * @param bool            $disabled
     */
    public function addStoreMultiselectFieldWithValue(
        LayoutInterface $layout,
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label = null,
        $value = null,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        if (empty($label)) {
            $label = __('Store View')->render();
        }

        $config = [
            'name'     => sprintf('%s[]', $objectFieldName),
            'label'    => $label,
            'title'    => $label,
            'value'    => $value,
            'values'   => $this->sourceStore->getStoreValuesForForm(false, true),
            'required' => true
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $field = $fieldSet->addField($objectFieldName, 'multiselect', $config);

        /** @var Element $renderer */
        $renderer = $layout->createBlock(Element::class);

        if ($renderer) {
            $field->setRenderer($renderer);
        }
    }

    /**
     * @param LayoutInterface    $layout
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addStoreWithAdminSelectField(
        LayoutInterface $layout,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        AbstractModel $object = null,
        bool $required = true,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        if (empty($label)) {
            $label = __('Store View')->render();
        }

        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'title'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, 0, $object),
            'values'   => $this->sourceStoreWithAdmin->getStoreValuesForForm(),
            'required' => $required
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $field = $fieldSet->addField($objectFieldName, 'select', $config);

        /** @var Element $renderer */
        $renderer = $layout->createBlock('Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element');

        if ($renderer) {
            $field->setRenderer($renderer);
        }
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param null               $defaultValue
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addCmsBlockSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        $defaultValue = null,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param null               $defaultValue
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addCmsPageSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        $defaultValue = null,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param mixed              $defaultValue
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addTypeIdField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        $defaultValue = null,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addTemplateField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addCategoriesField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addOperatorField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addDateIsoField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = true,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        // convert the date to local time
        $fieldSet->addType('date_iso', DateIso::class);

        $config = [
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object),
            'format'   => $this->dateFormatIso,
            'required' => $required
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $fieldSet->addField($objectFieldName, 'date_iso', $config);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param bool     $required
     */
    public function addFileField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        bool $required = true
    ) {
        $fieldSet->addField($objectFieldName, 'file', [
            'name'      => $objectFieldName,
            'label'     => $label,
            'class'     => 'disable',
            'required'  => $required,
            'css_class' => 'admin__field-file'
        ]);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addCountryField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addRegionField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     */
    public function addImageField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false
    ) {
        $fieldSet->addField($objectFieldName, 'image', [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $object && $object->getId() ? $object->getDataUsingMethod($objectFieldName) : null,
            'required' => $required
        ]);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addCustomerGroupField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addCustomerGroupMultiSelectField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     * @param bool               $allStores
     * @param bool               $withDefault
     *
     * @throws LocalizedException
     */
    public function addPaymentActiveMethodsField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false,
        bool $allStores = false,
        bool $withDefault = true
    ) {
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
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string|null        $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addProductTypeField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label = null,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
        if ($this->variables->isEmpty($label)) {
            $label = __('Apply To')->render();
        }

        $config = [
            'name'        => sprintf('%s[]', $objectFieldName),
            'label'       => $label,
            'value'       => $this->getFieldValue($objectRegistryKey, $objectFieldName, 'all', $object),
            'values'      => $this->productType->getOptions(),
            'mode_labels' => [
                'all'    => __('All Product Types')->render(),
                'custom' => __('Selected Product Types')->render()
            ],
            'required'    => $required
        ];

        if ($readOnly) {
            $config['readonly'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' read-only';
            } else {
                $config['css_class'] = 'read-only';
            }
        }

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $fieldSet->addField($objectFieldName, 'apply', $config);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     */
    public function addWysiwygField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null
    ) {
        $fieldSet->addType('wysiwyg', Wysiwyg::class);

        $fieldSet->addField($objectFieldName, 'wysiwyg', [
            'name'  => $objectFieldName,
            'label' => $label,
            'value' => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object)
        ]);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     */
    public function addEditorField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null
    ) {
        $fieldSet->addField($objectFieldName, 'editor', [
            'name'   => $objectFieldName,
            'label'  => $label,
            'state'  => 'html',
            'value'  => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object),
            'style'  => 'height: 400px;',
            'config' => $this->wysiwygConfig->getConfig()
        ]);
    }

    /**
     * @param AbstractModel $object
     * @param Fieldset      $fieldSet
     * @param string        $objectRegistryKey
     * @param string        $objectFieldName
     * @param string        $label
     * @param bool          $required
     * @param bool          $customer
     * @param bool          $address
     * @param bool          $category
     * @param bool          $product
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
    ) {
        $fieldSet->addField($objectFieldName, 'select', [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object),
            'values'   => $this->sourceAttributes->toOptionArrayWithEntities($customer, $address, $category, $product),
            'required' => $required
        ]);
    }

    /**
     * @param AbstractModel $object
     * @param Fieldset      $fieldSet
     * @param string        $objectFieldName
     * @param string        $label
     * @param bool          $required
     * @param bool          $customer
     * @param bool          $address
     * @param bool          $category
     * @param bool          $product
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
    ) {
        $fieldSet->addField($objectFieldName, 'multiselect', [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $object->getDataUsingMethod($objectFieldName),
            'values'   => $this->sourceAttributes->toOptionArrayWithEntities($customer, $address, $category, $product),
            'required' => $required
        ]);
    }

    /**
     * @param AbstractModel $object
     * @param string        $objectName
     * @param Fieldset      $fieldSet
     * @param string        $objectRegistryKey
     * @param string        $objectFieldName
     * @param string        $label
     * @param array         $targetFieldNames
     * @param bool          $required
     * @param bool          $multiSelect
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
    ) {
        $onChangeFieldId = sprintf('%s_%s', $objectName, $objectFieldName);

        $onChange = [];

        foreach ($targetFieldNames as $targetFieldName) {
            $targetFieldId = sprintf('%s_%s', $objectName, $targetFieldName);

            $onChange[] = $this->getUpdateEavAttributeFormElementJs($onChangeFieldId, $targetFieldId, $multiSelect);
        }

        $fieldSet->addField($objectFieldName, 'select', [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object),
            'values'   => $this->sourceAttributes->toOptionArray(),
            'required' => $required,
            'onchange' => implode(';', $onChange)
        ]);
    }

    /**
     * @param AbstractModel $object
     * @param Fieldset      $fieldSet
     * @param string        $objectRegistryKey
     * @param string        $objectAttributeFieldName
     * @param string        $objectFieldName
     * @param string        $label
     * @param bool          $required
     * @param bool          $multiSelect
     *
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
    ) {
        $valueOptions = [];

        if ($object->getId()) {
            $attributeId = $object->getDataUsingMethod($objectAttributeFieldName);

            if ($attributeId) {
                $attribute = $this->attributeHelper->getAttribute(Product::ENTITY, $attributeId);

                $valueOptions = $attribute->getSource()->getAllOptions();
            }
        }

        if ($this->variables->isEmpty($valueOptions)) {
            $this->addTextField($fieldSet, $objectRegistryKey, $objectFieldName, $label, $object, $required);
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
     * @param AbstractModel $object
     * @param Fieldset      $fieldSet
     * @param string        $objectRegistryKey
     * @param string        $objectFieldName
     * @param string        $label
     * @param bool          $required
     * @param bool          $customer
     * @param bool          $address
     * @param bool          $category
     * @param bool          $product
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
    ) {
        $fieldSet->addField($objectFieldName, 'select', [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object),
            'values'   => $this->sourceAttributeSets->toOptionArrayWithEntities(
                $customer,
                $address,
                $category,
                $product
            ),
            'required' => $required
        ]);
    }

    /**
     * @param AbstractModel $object
     * @param Fieldset      $fieldSet
     * @param string        $objectRegistryKey
     * @param string        $objectFieldName
     * @param string        $label
     * @param bool          $required
     * @param bool          $customer
     * @param bool          $address
     * @param bool          $category
     * @param bool          $product
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
    ) {
        $fieldSet->addField($objectFieldName, 'select', [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object),
            'values'   => $this->sourceEntityTypes->toOptionArrayWithEntities($customer, $address, $category, $product),
            'required' => $required
        ]);
    }

    /**
     * @param AbstractModel $object
     * @param Fieldset      $fieldSet
     * @param string        $objectRegistryKey
     * @param string        $objectFieldName
     * @param string        $label
     * @param bool          $required
     */
    public function addProductAttributeCodeField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $fieldSet->addField($objectFieldName, 'select', [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object),
            'values'   => $this->sourceProductAttributeCode->toOptionArray(),
            'required' => $required
        ]);
    }

    /**
     * @param AbstractModel $object
     * @param Fieldset      $fieldSet
     * @param string        $objectRegistryKey
     * @param string        $objectFieldName
     * @param string        $label
     * @param bool          $required
     */
    public function addCustomerAttributeCodeField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $fieldSet->addField($objectFieldName, 'select', [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object),
            'values'   => $this->sourceCustomerAttributeCode->toOptionArray(),
            'required' => $required
        ]);
    }

    /**
     * @param AbstractModel $object
     * @param Fieldset      $fieldSet
     * @param string        $objectRegistryKey
     * @param string        $objectFieldName
     * @param string        $label
     * @param bool          $required
     */
    public function addAddressAttributeCodeField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false
    ) {
        $fieldSet->addField($objectFieldName, 'select', [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object),
            'values'   => $this->sourceAddressAttributeCode->toOptionArray(),
            'required' => $required
        ]);
    }

    /**
     * @param AbstractModel $object
     * @param Fieldset      $fieldSet
     * @param string        $objectRegistryKey
     * @param string        $objectFieldName
     * @param string        $label
     * @param bool          $required
     * @param bool          $multiSelect
     */
    public function addAttributeSortByField(
        AbstractModel $object,
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        bool $required = false,
        bool $multiSelect = false
    ) {
        $fieldSet->addField($objectFieldName, $multiSelect ? 'multiselect' : 'select', [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object),
            'values'   => $this->sourceAttributeSortBy->toOptionArray(),
            'required' => $required
        ]);
    }

    /**
     * @param string $sourceElementId
     * @param string $targetElementId
     * @param bool   $multiSelect
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
            var_export($multiSelect, true)
        );
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param mixed              $value
     * @param AbstractModel|null $object
     * @param bool               $disabled
     * @param mixed              $after
     */
    public function addCheckboxField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        $value,
        AbstractModel $object = null,
        bool $disabled = false,
        $after = false
    ) {
        $fieldValue = $this->getFieldValue($objectRegistryKey, $objectFieldName, null, $object);

        $config = [
            'name'      => $objectFieldName,
            'label'     => $label,
            'title'     => $label,
            'value'     => $value,
            'checked'   => $fieldValue == $value,
            'css_class' => 'admin__field-checkbox'
        ];

        if ($disabled) {
            $config['disabled'] = true;
            if (array_key_exists('css_class', $config)) {
                $config['css_class'] .= ' disabled';
            } else {
                $config['css_class'] = 'disabled';
            }
        }

        $fieldSet->addField($objectFieldName, 'checkbox', $config, $after);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param mixed              $after
     */
    public function addValueField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        $after = false
    ) {
        $fieldSet->addType('value', Value::class);

        $config = [
            'name'     => $objectFieldName,
            'label'    => $label,
            'value'    => $this->getFieldValue($objectRegistryKey, $objectFieldName, '', $object),
            'required' => false
        ];

        $fieldSet->addField($objectFieldName, 'value', $config, $after);
    }

    /**
     * @param Fieldset $fieldSet
     * @param string   $objectFieldName
     * @param string   $label
     * @param string   $value
     * @param mixed    $onClick
     * @param mixed    $dataMageInit
     */
    public function addButtonField(
        Fieldset $fieldSet,
        string $objectFieldName,
        string $label,
        string $value,
        $onClick = null,
        $dataMageInit = null
    ) {
        $config = [
            'name'      => $objectFieldName,
            'label'     => $label,
            'value'     => $value,
            'css_class' => 'admin__field-button'
        ];

        if ($dataMageInit) {
            $config['onclick'] = $onClick;
        }

        if ($dataMageInit) {
            $config['data-mage-init'] = $dataMageInit;
        }

        $fieldSet->addField($objectFieldName, 'button', $config);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectName
     * @param string             $objectField
     * @param string             $objectFieldName
     * @param string             $label
     * @param string             $value
     * @param string             $urlPath
     * @param array              $urlParameters
     * @param AbstractModel|null $object
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
        AbstractModel $object = null
    ) {
        if ($object) {
            $objectId = $object->getDataUsingMethod($objectField);

            if ($objectId) {
                $urlParameters[$objectField] = $objectId;
            }
        }

        $dataMageInit = $this->escaper->escapeHtml(
            json_encode([
                            'infrangible/iframe-button' => [
                                'buttonId' => sprintf('%s_%s', $objectName, $objectFieldName),
                                'src'      => $this->urlHelper->getBackendUrl($urlPath, $urlParameters),
                                'title'    => $label
                            ],
                        ])
        );

        $this->addButtonField($fieldSet, $objectFieldName, $label, $value, null, $dataMageInit);
    }

    /**
     * @param Fieldset           $fieldSet
     * @param string             $objectRegistryKey
     * @param string             $objectFieldName
     * @param string             $label
     * @param AbstractModel|null $object
     * @param bool               $required
     * @param bool               $readOnly
     * @param bool               $disabled
     */
    public function addThemeField(
        Fieldset $fieldSet,
        string $objectRegistryKey,
        string $objectFieldName,
        string $label,
        AbstractModel $object = null,
        bool $required = false,
        bool $readOnly = false,
        bool $disabled = false
    ) {
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
}
