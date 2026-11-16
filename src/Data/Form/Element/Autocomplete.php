<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Data\Form\Element;

use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Helper\Search;
use Infrangible\Core\Helper\Url;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Escaper;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Autocomplete extends Text
{
    /** @var SecureHtmlRenderer */
    protected SecureHtmlRenderer $secureRenderer;

    /**
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param Json $json
     * @param Url $urlHelper
     * @param SecureHtmlRenderer $secureRenderer
     * @param Search $searchHelper
     * @param Variables $variables
     * @param array $data
     *
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        protected Json $json,
        protected Url $urlHelper,
        SecureHtmlRenderer $secureRenderer,
        protected Search $searchHelper,
        protected Variables $variables,
        array $data = []
    ) {
        parent::__construct(
            $factoryElement,
            $factoryCollection,
            $escaper,
            $data
        );
        $this->secureRenderer = $secureRenderer;
    }

    /**
     * Returns the html.
     *
     * @return string
     */
    public function getHtml(): string
    {
        $this->addClass('select admin__control-select');

        return $this->variables->stringValue(parent::getHtml());
    }

    /**
     * Returns the value html id.
     *
     * @return string
     */
    public function getValueHtmlId(): string
    {
        return $this->_escaper->escapeHtmlAttr(
            $this->getStringValue($this->getForm()->getData('html_id_prefix')) .
            $this->getStringData('value_html_id') .
            $this->getStringValue($this->getForm()->getData('html_id_suffix'))
        );
    }

    /**
     * Returns the element html.
     *
     * @return string
     */
    public function getElementHtml(): string
    {
        $valueHtml = '<input type="hidden" id="' . $this->getHtmlId() . '" name="' .
            $this->getStringValue($this->getName()) . '" class="' .
            ($this->getData('required') ? 'required-entry _required' : '') . '" value="' .
            $this->_escaper->escapeHtmlAttr($this->getStringData('value')) . '" ' . $this->_getUiId() . ' ' .
            $this->serialize($this->getHtmlAttributes()) . '/>';

        $events = array_filter(
            $this->getHtmlAttributes(),
            fn(string $attribute): bool => mb_strpos(
                $attribute,
                'on'
            ) === 0
        );

        foreach ($events as $event) {
            $eventShort = mb_substr(
                $event,
                2
            );
            $methodName = 'getOn' . $eventShort;
            $eventListener = $this->$methodName();

            if ($eventListener) {
                $valueHtml .= $this->secureRenderer->renderEventListenerAsTag(
                    $event,
                    $eventListener,
                    sprintf(
                        "*[formelementhookid='%s']",
                        $this->getStringData('formelementhookid')
                    )
                );
            }
        }

        $this->setData(
            'value_name',
            $this->getData('name')
        );
        $this->setData(
            'value_html_id',
            $this->getData('html_id')
        );
        $this->setData(
            'name',
            $this->getData('name') . '_autocomplete'
        );
        $this->setData(
            'html_id',
            $this->getData('html_id') . '_autocomplete'
        );

        $this->setData(
            'required',
            false
        );

        $class = $this->getStringData('class');

        $class = str_replace(
            'required-entry',
            '',
            $class
        );
        $class = str_replace(
            '_required',
            '',
            $class
        );

        $this->setData(
            'class',
            $class
        );

        $this->unsetData('formelementhookid');
        $this->unsetData('value');

        return parent::getElementHtml() . $valueHtml;
    }

    /**
     * Returns the value of the data key as string.
     *
     * @param string $key
     *
     * @return string
     */
    private function getStringData(string $key): string
    {
        return $this->getStringValue($this->getData($key));
    }

    /**
     * Returns the value as string, an empty string for null.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function getStringValue(mixed $value): string
    {
        return $value === null ? '' : $this->variables->stringValue($value);
    }

    /**
     * Returns the script of the autocomplete as HTML after the element.
     *
     * @return string
     */
    public function getAfterElementHtml(): string
    {
        $htmlId = $this->getHtmlId();
        $valueHtmlId = $this->getValueHtmlId();

        $searchUrlParams = [
            'search_collection' => $this->getStringData('search_collection'),
            'result_id'         => $this->getStringData('result_id'),
            'result_value'      => $this->getStringData('result_value'),
            'result_label'      => $this->getStringData('result_label')
        ];

        $searchFields = $this->getData('search_fields');

        if (is_array($searchFields) && count($searchFields) > 0) {
            $searchUrlParams[ 'search_fields' ] = implode(
                ',',
                array_map(
                    $this->variables->stringValue(...),
                    $searchFields
                )
            );
        }

        if ($this->getData('search_expressions')) {
            $searchUrlParams[ 'search_expressions' ] = (string)$this->json->encode(
                $this->getData('search_expressions')
            );
        }

        if ($this->getData('search_conditions')) {
            $searchUrlParams[ 'search_conditions' ] = (string)$this->json->encode($this->getData('search_conditions'));
        }

        if ($this->getData('search_limit')) {
            $searchUrlParams[ 'search_limit' ] = $this->getStringData('search_limit');
        }

        $searchUrl = $this->urlHelper->getBackendUrl(
            'infrangible_backendwidget/search/term',
            $this->searchHelper->sign($searchUrlParams)
        );

        $valueUrlParams = [
            'object_id'         => $this->getStringData('object_id'),
            'search_collection' => $this->getStringData('search_collection'),
            'result_value'      => $this->getStringData('result_value')
        ];

        $valueUrl = $this->urlHelper->getBackendUrl(
            'infrangible_backendwidget/search/value',
            $this->searchHelper->sign($valueUrlParams)
        );

        $scriptString = <<<script
        require(['jquery', 'jquery/ui'], function($) {
            var cache = [];
            $('#$htmlId').autocomplete({
                minLength: 2,
                create: function() {
                    $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                        var itemId = item.id;
                        var itemValue = item.value;
                        var itemNode = $('<li>', {
                            click: function() {
                                $('#$valueHtmlId').val(itemId);
                                $('#$valueHtmlId').trigger('change');
                                $('#$htmlId').val(itemValue);
                                $('#$htmlId').addClass('selected');
                                $('#$htmlId').autocomplete('close');
                            }
                        });
                        itemNode.addClass('ui-menu-item');
                        itemNode.data('ui-autocomplete-item', item);
                        itemNode.appendTo(ul);
                        var itemLabelNode = $('<div>');
                        itemLabelNode.addClass('ui-menu-item-wrapper');
                        itemLabelNode.append(item.label);
                        itemLabelNode.appendTo(itemNode);
                        return itemNode
                    };
                    $.getJSON('$valueUrl', {}, function(data, status, xhr) {
                        $('#$htmlId').val(data.value);
                        $('#$htmlId').addClass('selected');
                    });
                },
                open: function() {
                    $('ul.ui-menu').width($(this).innerWidth());
                },
                source: function(request, response) {
                    $('#$valueHtmlId').val('');
                    $('#$htmlId').removeClass('selected');
                    var term = request.term;
                    $('#$htmlId').attr('data-term', term);
                    if (term in cache) {
                        response(cache[term]);
                        return;
                    }
                    $.getJSON('$searchUrl', request, function(data, status, xhr) {
                        cache[term] = data;
                        response(data);
                    });
                },
                select: function(event, ui) {
                    $('#$valueHtmlId').val(ui.item.id);
                    $('#$valueHtmlId').trigger('change');
                    $('#$htmlId').addClass('selected');
                }
            });
            $('#$htmlId').on('click', function() {
                $(this).autocomplete('search', $(this).attr('data-term'));
            });
            $('#$htmlId').on('input', function() {
                if (this.value.trim().length < 2) {
                    $('#$valueHtmlId').val('');
                    $('#$htmlId').removeClass('selected');
                }
            });
        });
script;

        return /* @noEscape */ $this->secureRenderer->renderTag(
            'script',
            [],
            $scriptString,
            false
        );
    }
}
