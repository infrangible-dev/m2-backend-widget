<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Data\Form\Element;

use FeWeDev\Base\Json;
use Infrangible\Core\Helper\Url;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Escaper;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Autocomplete extends Text
{
    /** @var Json */
    protected $json;

    /** @var Url */
    protected $urlHelper;

    /** @var SecureHtmlRenderer */
    protected $secureRenderer;

    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        Json $json,
        Url $urlHelper,
        SecureHtmlRenderer $secureRenderer,
        $data = []
    ) {
        parent::__construct(
            $factoryElement,
            $factoryCollection,
            $escaper,
            $data
        );

        $this->json = $json;
        $this->urlHelper = $urlHelper;
        $this->secureRenderer = $secureRenderer;
    }

    public function getHtml(): string
    {
        $this->addClass('select admin__control-select');

        return parent::getHtml();
    }

    public function getValueHtmlId(): string
    {
        return $this->_escaper->escapeHtml(
            $this->getForm()->getData('html_id_prefix') . $this->getData('value_html_id') .
            $this->getForm()->getData('html_id_suffix')
        );
    }

    public function getElementHtml(): string
    {
        $valueHtml = sprintf(
            '<input type="hidden" id="%s" name="%s" class="%s" value="%s" %s %s/>',
            $this->getHtmlId(),
            $this->getName(),
            $this->getData('required') ? 'required-entry _required' : '',
            $this->getData('value'),
            $this->_getUiId(),
            $this->serialize($this->getHtmlAttributes())
        );

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

        $class = $this->getData('class');

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

    public function getAfterElementHtml()
    {
        $htmlId = $this->getHtmlId();
        $valueHtmlId = $this->getValueHtmlId();

        $searchUrlParams = [
            'search_collection' => $this->getData('search_collection'),
            'result_id'         => $this->getData('result_id'),
            'result_value'      => $this->getData('result_value'),
            'result_label'      => $this->getData('result_label')
        ];

        if ($this->getData('search_fields')) {
            $searchUrlParams[ 'search_fields' ] = implode(
                ',',
                $this->getData('search_fields')
            );
        }

        if ($this->getData('search_expressions')) {
            $searchUrlParams[ 'search_expressions' ] = $this->json->encode($this->getData('search_expressions'));
        }

        if ($this->getData('search_conditions')) {
            $searchUrlParams[ 'search_conditions' ] = $this->json->encode($this->getData('search_conditions'));
        }

        $searchUrl = $this->urlHelper->getBackendUrl(
            'infrangible_backendwidget/search/term',
            $searchUrlParams
        );

        $valueUrlParams = [
            'object_id'         => $this->getData('object_id'),
            'search_collection' => $this->getData('search_collection'),
            'result_value'      => $this->getData('result_value')
        ];

        $valueUrl = $this->urlHelper->getBackendUrl(
            'infrangible_backendwidget/search/value',
            $valueUrlParams
        );

        $scriptString = <<<script
        require(['jquery', 'jquery/ui'], function($) {
            var cache = [];
            $('#$htmlId').autocomplete({
                minLength: 2,
                create: function() {
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
