/**
 * Product Chooser Form Element
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery'
], function (Abstract, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'Panth_SmartBadge/form/element/product-chooser'
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            return this;
        },

        /**
         * Open product chooser (placeholder for future implementation)
         */
        openProductChooser: function () {
            // Placeholder for product chooser grid widget
            // Can be enhanced later with Magento's product grid
            alert('Product chooser will be implemented. For now, enter comma-separated product IDs (e.g., 1,2,3)');
        }
    });
});
