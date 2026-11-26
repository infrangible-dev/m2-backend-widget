// noinspection JSUnresolvedFunction

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
(function () {
    'use strict';

    /**
     * Loads the values of the option selected in the source element into the target element (updateSelectOptions() is
     * declared in product-options.js, which the same layout loads). Called by the forms of Helper\Form.
     *
     * @param {string} targetElementUrl
     * @param {string} sourceElementId
     * @param {string} targetElementId
     */
    window.updateOptionTypesFormElement = function (targetElementUrl, sourceElementId, targetElementId) {
        require(['jquery'], function ($) {
            window.updateSelectOptions(
                decodeURIComponent(targetElementUrl) + 'option_id/' + $('#' + sourceElementId).val(),
                targetElementId
            );
        });
    };
})();
