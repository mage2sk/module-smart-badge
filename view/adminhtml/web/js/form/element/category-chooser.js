/**
 * Category Chooser Form Element
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery'
], function (Abstract, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'Panth_SmartBadge/form/element/category-chooser'
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            return this;
        },

        /**
         * Open category chooser (placeholder for future implementation)
         */
        openCategoryChooser: function () {
            // Placeholder for category tree widget
            // Can be enhanced later with Magento's category tree
            alert('Category chooser will be implemented. For now, enter comma-separated category IDs (e.g., 1,2,3)');
        }
    });
});
