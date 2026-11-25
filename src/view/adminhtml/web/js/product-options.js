// noinspection JSUnresolvedFunction

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

/**
 * Replaces the options of the select element with those that the URL returns for the value of the source element
 * (options with a value list become option groups).
 */
function updateSelectOptions(url, targetElementId) {
    require(['jquery'], function ($) {
        $.get({
            url: url,
            success: function (json) {
                const targetElement = $('#' + targetElementId).get(0);

                targetElement.replaceChildren();

                $.each(json.options || [], function (index, attributeOption) {
                    if (typeof attributeOption.value === 'object' && attributeOption.value !== null) {
                        const optionGroup = document.createElement('optgroup');
                        optionGroup.label = attributeOption.label;
                        targetElement.appendChild(optionGroup);

                        $.each(attributeOption.value, function (index, groupOption) {
                            appendSelectOption(optionGroup, groupOption);
                        });
                    } else {
                        appendSelectOption(targetElement, attributeOption);
                    }
                });
            }
        });
    });
}

/**
 * Appends an option with the value and label to the select element or option group.
 */
function appendSelectOption(parentElement, attributeOption) {
    const option = document.createElement('option');

    option.value = attributeOption.value;
    option.text = attributeOption.label;

    parentElement.appendChild(option);
}

/**
 * Loads the options of the product selected in the source element into the target element.
 */
function updateProductOptionsFormElement(targetElementUrl, sourceElementId, targetElementId) {
    require(['jquery'], function ($) {
        updateSelectOptions(
            decodeURIComponent(targetElementUrl) + 'product_id/' + $('#' + sourceElementId).val(),
            targetElementId
        );
    });
}
