// noinspection JSUnresolvedFunction

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
function updateProductOptionsFormElement(targetElementUrl, sourceElementId, targetElementId) {
    require(['jquery'], function ($) {
        var sourceElement = $('#' + sourceElementId);
        var sourceElementValue = sourceElement.val();
        $.get({
            url: decodeURIComponent(targetElementUrl) + 'product_id/' + sourceElementValue,
            success: function (json) {
                if (json.success && json.success === false) {
                    throw json.error;
                }
                var targetElement = $('#' + targetElementId).get(0);
                targetElement.replaceChildren();
                if (json.options) {
                    $.each(json.options, function (index, attributeOption) {
                        if (typeof attributeOption.value === 'object' && attributeOption.value !== null) {
                            var optionGroup = document.createElement('optgroup');
                            optionGroup.label = attributeOption.label;
                            targetElement.appendChild(optionGroup);
                            $.each(attributeOption.value, function (index, attributeOption) {
                                var option = document.createElement('option');
                                option.value = attributeOption.value;
                                option.text = attributeOption.label;
                                optionGroup.appendChild(option);
                            });
                        } else {
                            var option = document.createElement('option');
                            option.value = attributeOption.value;
                            option.text = attributeOption.label;
                            targetElement.appendChild(option);
                        }
                    });
                }
            }.bind(this)
        });
    });
}
