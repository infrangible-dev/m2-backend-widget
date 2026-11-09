// noinspection JSUnresolvedFunction

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
function updateOptionTypesFormElement(targetElementUrl, sourceElementId, targetElementId) {
    require(['jquery'], function ($) {
        const sourceElement = $('#' + sourceElementId);
        const sourceElementValue = sourceElement.val();
        $.get({
            url: decodeURIComponent(targetElementUrl) + 'option_id/' + sourceElementValue,
            success: function (json) {
                if (json.success && json.success === false) {
                    throw json.error;
                }
                const targetElement = $('#' + targetElementId).get(0);
                targetElement.replaceChildren();
                if (json.options) {
                    $.each(json.options, function (index, attributeOption) {
                        const option = document.createElement('option');
                        option.value = attributeOption.value;
                        option.text = attributeOption.label;
                        targetElement.appendChild(option);
                    });
                }
            }.bind(this)
        });
    });
}
