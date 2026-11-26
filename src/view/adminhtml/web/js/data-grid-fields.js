
/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'underscore',
    'prototype',
    'domReady!'
], function ($, modal, _) {
    'use strict';

    // noinspection JSValidateJSDoc
    $.widget('mage.dataGridColumns', {
        options: {
            formKey: '',
            ajaxUrl: '',
            dataGridId: '',
            jsObjectName: '',
            fieldList: [],
            groupByFieldList: [],
            activeGroupByFieldList: []
        },

        _create: function () {
            const dataGridColumns = this;

            this.dataGrid = $('div[data-grid-id]');
            this.dataGridColumnsList = $('.data-grid-columns');
            this.dataGridColumnsCheckboxes = $('.data-grid-column-checkbox', this.dataGridColumnsList);

            if (this.dataGridColumnsList.length === 0) {
                return;
            }

            this.dataGridColumnsModal = modal({
                type: 'popup',
                responsive: true,
                modalClass: 'data-grid-fields-popup',
                title: $.mage.__('Show columns'),
                buttons: []
            }, this.dataGridColumnsList);

            this._eachColumn(function (columnCheckbox, fieldName, columnClassName, columnHeader) {
                if (columnHeader.length && !columnHeader.hasClass('hidden')) {
                    columnCheckbox.attr('checked', true);
                }

                columnCheckbox.on('change', function () {
                    const columnList = $('.' + columnClassName, $('table.data-grid', dataGridColumns.dataGrid));

                    if (columnList.length) {
                        columnList.toggleClass('hidden');

                        dataGridColumns._updateButtonText();
                        dataGridColumns._saveSelection();
                    }
                });
            });

            this.dataGridColumnsButtonTextValue = $('span', $('.action-columns', this.dataGrid)).text();

            this._updateButtonText();
        },

        _init: function () {
            const dataGridColumns = this,
                filterRow = $('table.data-grid thead tr.data-grid-filters', this.dataGrid),
                observer = new MutationObserver(function () {
                    dataGridColumns._init();
                    dataGridColumns._updateButtonText();
                    observer.disconnect();
                });
            let hasFilters = false;

            $('.action-columns', this.dataGrid).on('click', function () {
                if (dataGridColumns.dataGridColumnsModal) {
                    dataGridColumns.dataGridColumnsModal.openModal();
                }
            });

            observer.observe(this.dataGrid[0], {
                attributes: false,
                childList: true,
                characterData: false
            });

            $('input.admin__control-text, select.admin__control-select', filterRow).each(function () {
                if (!_.isEmpty($(this).val())) {
                    hasFilters = true;
                }
            });

            if (hasFilters) {
                filterRow.addClass('active');
            }

            $('.action-filters', this.dataGrid).on('click', function () {
                filterRow.toggleClass('active');
            });

            this._eachColumn(function (columnCheckbox, fieldName, columnClassName, columnHeader) {
                if (columnHeader.length && $.inArray(fieldName, dataGridColumns.options.groupByFieldList) !== -1) {
                    dataGridColumns._addGroupByIcon(fieldName, columnHeader);
                }
            });
        },

        /**
         * Adds the icon to the column header that groups the grid by the field of the column, or ends the grouping.
         *
         * @param {string} fieldName
         * @param {jQuery} columnHeader
         */
        _addGroupByIcon: function (fieldName, columnHeader) {
            const dataGridColumns = this,
                groupedByIcon = $('<i>');

            groupedByIcon.data('group_by', fieldName);
            groupedByIcon.addClass('group_by-' + fieldName);

            if ($.inArray(fieldName, this.options.activeGroupByFieldList) !== -1) {
                groupedByIcon.addClass('active');
            }

            groupedByIcon.attr('title', $.mage.__('Group By'));
            columnHeader.append(groupedByIcon);

            groupedByIcon.on('click', function () {
                const groupBy = $(this).data('group_by'),
                    index = $.inArray(groupBy, dataGridColumns.options.activeGroupByFieldList),
                    grid = window[dataGridColumns.options.jsObjectName];

                if (index === -1) {
                    dataGridColumns.options.activeGroupByFieldList.push(groupBy);
                } else {
                    dataGridColumns.options.activeGroupByFieldList.splice(index, 1);
                }

                // Base64 of Magento's mage/adminhtml/tools.js, which the admin pages load
                grid.reload(grid.addVarToUrl('group_by',
                    window.Base64.encode(dataGridColumns.options.activeGroupByFieldList.join(','))));

                return false;
            });
        },

        /**
         * Calls the callback for every column of the column list with its checkbox, field name, class name and
         * header in the grid.
         */
        _eachColumn: function (callback) {
            const dataGridColumns = this;

            this.dataGridColumnsCheckboxes.each(function () {
                const columnCheckbox = $(this),
                    fieldName = dataGridColumns._getFieldName(columnCheckbox),
                    columnClassName = 'col-' + fieldName;

                if (fieldName) {
                    callback(columnCheckbox, fieldName, columnClassName,
                        $('th.' + columnClassName, $('table.data-grid', dataGridColumns.dataGrid)));
                }
            });
        },

        /**
         * Returns the field name of the column of the checkbox, null if the checkbox has no ID.
         */
        _getFieldName: function (columnCheckbox) {
            const columnId = columnCheckbox.attr('id');

            return columnId ? columnId.replace('data-grid-column-', '') : null;
        },

        _updateButtonText: function () {
            const dataGridColumnsButton = $('.action-columns', this.dataGrid),
                dataGridColumnsButtonText = $('span', dataGridColumnsButton),
                checkedDataGridColumnsCheckboxes = $('.data-grid-column-checkbox:checked', this.dataGridColumnsList),
                hiddenCount = this.dataGridColumnsCheckboxes.length - checkedDataGridColumnsCheckboxes.length;

            if (hiddenCount > 0) {
                dataGridColumnsButtonText.text(this.dataGridColumnsButtonTextValue +
                    ' (' + hiddenCount + ' ' + $.mage.__('hidden') + ')');
            } else {
                dataGridColumnsButtonText.text(this.dataGridColumnsButtonTextValue);
            }
        },

        _saveSelection: function () {
            const hiddenFieldList = [],
                uncheckedDataGridColumnsCheckboxes =
                    $('.data-grid-column-checkbox:not(:checked)', this.dataGridColumnsList),
                dataGridColumns = this,
                data = {
                    data_grid_id: this.options.dataGridId,
                    form_key: this.options.formKey,
                    hidden_field_list: hiddenFieldList
                };

            uncheckedDataGridColumnsCheckboxes.each(function () {
                const fieldName = dataGridColumns._getFieldName($(this));

                if (fieldName) {
                    hiddenFieldList.push(fieldName);
                }
            });

            $.ajax({
                url: this.options.ajaxUrl,
                dataType: 'json',
                data: data
            });
        }
    });

    return $.mage.dataGridColumns;
});
