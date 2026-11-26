// noinspection JSUnresolvedFunction

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
(function () {
    'use strict';

    /**
     * Replaces the text field with a select element (a multiselect if multiSelect is set) with the same ID and name.
     *
     * @param {HTMLInputElement} textElement
     * @param {boolean} multiSelect
     * @returns {HTMLSelectElement}
     */
    const replaceWithSelect = function (textElement, multiSelect) {
            const selectElement = document.createElement('select'),
                textClass = textElement.getAttribute('class');

            selectElement.setAttribute('id', textElement.getAttribute('id'));

            if (multiSelect) {
                selectElement.setAttribute('multiple', 'multiple');
                selectElement.setAttribute('size', '10');
                selectElement.setAttribute('name', textElement.getAttribute('name') + '[]');
                selectElement.setAttribute('class', textClass
                    .replace(/input-text/, 'select multiselect')
                    .replace(/admin__control-text/, 'admin__control-multiselect'));
            } else {
                selectElement.setAttribute('name', textElement.getAttribute('name'));
                selectElement.setAttribute('class', textClass
                    .replace(/input-text/, 'select')
                    .replace(/admin__control-text/, 'admin__control-select'));
            }

            selectElement.setAttribute('data-ui-id', textElement.getAttribute('data-ui-id')
                .replace(/element-text/, 'element-select'));
            textElement.replaceWith(selectElement);

            return selectElement;
        },

        /**
         * Replaces the select element with a text field with the same ID and name.
         *
         * @param {HTMLSelectElement} selectElement
         */
        replaceWithText = function (selectElement) {
            const textElement = document.createElement('input');

            textElement.setAttribute('type', 'text');
            textElement.setAttribute('id', selectElement.getAttribute('id'));
            textElement.setAttribute('name', selectElement.getAttribute('name'));
            textElement.setAttribute('class', selectElement.getAttribute('class')
                .replace(/select/, 'input-text')
                .replace(/admin__control-select/, 'admin__control-text'));
            textElement.setAttribute('data-ui-id', selectElement.getAttribute('data-ui-id')
                .replace(/element-select/, 'element-text'));
            selectElement.replaceWith(textElement);
        },

        /**
         * Shows the options in the target element: a text field becomes a select element, the options of a select
         * element are replaced.
         *
         * @param {jQuery} $
         * @param {HTMLInputElement|HTMLSelectElement} targetElement
         * @param {Array<{value: string, label: string}>} attributeOptions
         * @param {boolean} multiSelect
         */
        showOptions = function ($, targetElement, attributeOptions, multiSelect) {
            let selectElement = targetElement,
                optionNumber;

            if (targetElement.type === 'text') {
                selectElement = replaceWithSelect(targetElement, multiSelect);
            } else {
                for (optionNumber = selectElement.options.length - 1; optionNumber >= 0; optionNumber--) {
                    selectElement.remove(optionNumber);
                }
            }

            $.each(attributeOptions, function (index, attributeOption) {
                const option = document.createElement('option');

                option.value = attributeOption.value;
                option.text = attributeOption.label;
                selectElement.appendChild(option);
            });
        };

    /**
     * Loads the options of the attribute selected in the source element into the target element; the target element
     * becomes a text field for attributes without options. Called by the forms of Helper\Form.
     *
     * @param {string} targetElementUrl
     * @param {string} sourceElementId
     * @param {string} targetElementId
     * @param {boolean} multiSelect
     */
    window.updateEavAttributeFormElement = function (targetElementUrl, sourceElementId, targetElementId, multiSelect) {
        require(['jquery'], function ($) {
            const sourceElement = $('#' + sourceElementId).get(0),
                sourceElementValue = sourceElement.options[sourceElement.selectedIndex].value;

            $.get({
                url: decodeURIComponent(targetElementUrl) + 'attribute_id/' + sourceElementValue,
                success: function (json) {
                    const targetElement = $('#' + targetElementId).get(0);

                    if (json.success && json.success === false) {
                        throw json.error;
                    }

                    if (json.options) {
                        showOptions($, targetElement, json.options, multiSelect);
                    } else if (targetElement.type !== 'text') {
                        replaceWithText(targetElement);
                    }
                }
            });
        });
    };
})();
