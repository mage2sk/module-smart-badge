/**
 * Badge Preview Container Component
 * Live preview of badge as form fields are updated
 */
define([
    'Magento_Ui/js/form/components/insert-form',
    'uiRegistry',
    'ko'
], function (InsertForm, registry, ko) {
    'use strict';

    return InsertForm.extend({
        defaults: {
            template: 'Panth_SmartBadge/form/badge-preview',
            previewMode: 'product',
            listens: {
                '${ $.provider }:data': 'onDataChange'
            }
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();

            this.badgeData = ko.observable({
                badge_text: 'SALE',
                badge_color: '#ef4444',
                badge_icon: '🔥',
                animation: 'pulse',
                badge_image: '',
                position_product: 'top-left',
                position_category: 'top-left',
                use_same_position: true,
                position_all: 'top-left'
            });

            this.previewMode = ko.observable('product');

            return this;
        },

        /**
         * Called when form data changes
         */
        onDataChange: function (data) {
            if (data && data.data) {
                this.badgeData(Object.assign(this.badgeData(), data.data));
            }
        },

        /**
         * Switch preview mode
         */
        setPreviewMode: function (mode) {
            this.previewMode(mode);
        },

        /**
         * Get badge style
         */
        getBadgeStyle: function () {
            var data = this.badgeData();
            var style = {
                'background-color': data.badge_color || '#ef4444',
                'color': this.getContrastColor(data.badge_color || '#ef4444')
            };

            // Apply badge_style if exists
            if (data.badge_style && typeof data.badge_style === 'object') {
                if (data.badge_style.borderRadius) {
                    style['border-radius'] = data.badge_style.borderRadius + 'px';
                }
                if (data.badge_style.opacity) {
                    style['opacity'] = data.badge_style.opacity / 100;
                }
            }

            return this.objectToStyleString(style);
        },

        /**
         * Get badge position class
         */
        getBadgePositionClass: function () {
            var data = this.badgeData();
            var mode = this.previewMode();

            if (data.use_same_position) {
                return 'badge-position-' + (data.position_all || 'top-left');
            }

            if (mode === 'product') {
                return 'badge-position-' + (data.position_product || 'top-left');
            } else {
                return 'badge-position-' + (data.position_category || 'top-left');
            }
        },

        /**
         * Get animation class
         */
        getAnimationClass: function () {
            var data = this.badgeData();
            return data.animation || '';
        },

        /**
         * Get badge text
         */
        getBadgeText: function () {
            var data = this.badgeData();
            return data.badge_text || 'SALE';
        },

        /**
         * Get badge icon
         */
        getBadgeIcon: function () {
            var data = this.badgeData();
            return data.badge_icon || '🔥';
        },

        /**
         * Check if icon is FontAwesome
         */
        isFontAwesome: function () {
            var icon = this.getBadgeIcon();
            return icon && icon.startsWith('fa-');
        },

        /**
         * Convert object to style string
         */
        objectToStyleString: function (obj) {
            return Object.keys(obj).map(function(key) {
                return key + ': ' + obj[key];
            }).join('; ');
        },

        /**
         * Get contrast color for text (white or black)
         */
        getContrastColor: function (hexColor) {
            if (!hexColor) return '#ffffff';

            // Remove # if present
            hexColor = hexColor.replace('#', '');

            // Convert to RGB
            var r = parseInt(hexColor.substr(0, 2), 16);
            var g = parseInt(hexColor.substr(2, 2), 16);
            var b = parseInt(hexColor.substr(4, 2), 16);

            // Calculate luminance
            var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

            return luminance > 0.5 ? '#000000' : '#ffffff';
        }
    });
});
