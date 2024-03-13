// noinspection JSUnresolvedFunction

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
function updateEavAttributeFormElement(targetElementUrl, sourceElementId, targetElementId, multiSelect) {
    require(['jquery'], function ($) {
        var sourceElement = $('#' + sourceElementId).get(0);
        var sourceElementValue = sourceElement.options[sourceElement.selectedIndex].value;
        $.get(
            {
                url: decodeURIComponent(targetElementUrl) + 'attribute_id/' + sourceElementValue,
                success: function (json) {
                    if (json.success && json.success === false) {
                        throw json.error;
                    }
                    var targetElement = $('#' + targetElementId).get(0);
                    var replaceTargetElement;
                    if (json.options) {
                        if (targetElement.type === 'text') {
                            replaceTargetElement = document.createElement('select');
                            replaceTargetElement.setAttribute('id', targetElement.getAttribute('id'));
                            if (multiSelect) {
                                replaceTargetElement.setAttribute('multiple', 'multiple');
                                replaceTargetElement.setAttribute('size', '10');
                                replaceTargetElement.setAttribute('name', targetElement.getAttribute('name') + '[]');
                                replaceTargetElement.setAttribute('class', targetElement.getAttribute('class').replace(/input-text/, 'select multiselect').replace(/admin__control-text/, 'admin__control-multiselect'));
                            } else {
                                replaceTargetElement.setAttribute('name', targetElement.getAttribute('name'));
                                replaceTargetElement.setAttribute('class', targetElement.getAttribute('class').replace(/input-text/, 'select').replace(/admin__control-text/, 'admin__control-select'));
                            }
                            replaceTargetElement.setAttribute('data-ui-id', targetElement.getAttribute('data-ui-id').replace(/element-text/, 'element-select'));
                            targetElement.replaceWith(replaceTargetElement);
                            targetElement = replaceTargetElement;
                        } else {
                            var optionNumber;
                            for (optionNumber = targetElement.options.length - 1; optionNumber >= 0; optionNumber--) {
                                targetElement.remove(optionNumber);
                            }
                        }
                        $.each(json.options, function (index, attributeOption) {
                            var option = document.createElement('option');
                            option.value = attributeOption.value;
                            option.text = attributeOption.label;
                            targetElement.appendChild(option);
                        });
                    } else {
                        if (targetElement.type !== 'text') {
                            replaceTargetElement = document.createElement('input');
                            replaceTargetElement.setAttribute('type', 'text');
                            replaceTargetElement.setAttribute('id', targetElement.getAttribute('id'));
                            replaceTargetElement.setAttribute('name', targetElement.getAttribute('name'));
                            replaceTargetElement.setAttribute('class', targetElement.getAttribute('class').replace(/select/, 'input-text').replace(/admin__control-select/, 'admin__control-text'));
                            replaceTargetElement.setAttribute('data-ui-id', targetElement.getAttribute('data-ui-id').replace(/element-select/, 'element-text'));
                            targetElement.replaceWith(replaceTargetElement);
                            targetElement = replaceTargetElement;
                        }
                    }
                }.bind(this)
            });
    });
}
