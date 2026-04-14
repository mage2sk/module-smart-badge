/**
 * Icon Picker Form Element
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery'
], function (Abstract, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'Panth_SmartBadge/form/element/icon-picker'
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            return this;
        },

        /**
         * Open icon picker modal (placeholder for future implementation)
         */
        openIconPicker: function () {
            // Placeholder for icon picker modal
            // Can be enhanced later with FontAwesome icon grid
            alert('Icon picker modal will be implemented. For now, enter emoji or FontAwesome class (e.g., fa-star)');
        }
    });
});
