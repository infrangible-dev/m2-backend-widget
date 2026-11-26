// noinspection JSUnresolvedFunction

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
(function () {
    'use strict';

    /**
     * Appends an option with the value and label to the select element or option group.
     *
     * @param {HTMLSelectElement|HTMLOptGroupElement} parentElement
     * @param {{value: string, label: string}} attributeOption
     */
    const appendSelectOption = function (parentElement, attributeOption) {
            const option = document.createElement('option');

            option.value = attributeOption.value;
            option.text = attributeOption.label;

            parentElement.appendChild(option);
        },

        /**
         * Appends an option to the select element, or an option group with its options for an option with a value
         * list.
         *
         * @param {jQuery} $
         * @param {HTMLSelectElement} selectElement
         * @param {{value: (string|Array|Object), label: string}} attributeOption
         */
        appendAttributeOption = function ($, selectElement, attributeOption) {
            const optionGroup = typeof attributeOption.value === 'object' && attributeOption.value !== null ?
                document.createElement('optgroup') : null;

            if (optionGroup === null) {
                appendSelectOption(selectElement, attributeOption);

                return;
            }

            optionGroup.label = attributeOption.label;
            selectElement.appendChild(optionGroup);

            $.each(attributeOption.value, function (index, groupOption) {
                appendSelectOption(optionGroup, groupOption);
            });
        };

    /**
     * Replaces the options of the select element with those that the URL returns for the value of the source element
     * (options with a value list become option groups). Also used by product-option-values.js.
     *
     * @param {string} url
     * @param {string} targetElementId
     */
    window.updateSelectOptions = function (url, targetElementId) {
        require(['jquery'], function ($) {
            $.get({
                url: url,
                success: function (json) {
                    const targetElement = $('#' + targetElementId).get(0);

                    targetElement.replaceChildren();

                    $.each(json.options || [], function (index, attributeOption) {
                        appendAttributeOption($, targetElement, attributeOption);
                    });
                }
            });
        });
    };

    /**
     * Loads the options of the product selected in the source element into the target element. Called by the forms of
     * Helper\Form.
     *
     * @param {string} targetElementUrl
     * @param {string} sourceElementId
     * @param {string} targetElementId
     */
    window.updateProductOptionsFormElement = function (targetElementUrl, sourceElementId, targetElementId) {
        require(['jquery'], function ($) {
            window.updateSelectOptions(
                decodeURIComponent(targetElementUrl) + 'product_id/' + $('#' + sourceElementId).val(),
                targetElementId
            );
        });
    };
})();
