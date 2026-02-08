/**
 * HeyMag Chat - Admin JavaScript
 *
 * @package HeyMag_Chat
 */

(function($) {
    'use strict';

    /**
     * HeyMag Admin module
     */
    var HeyMagAdmin = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initColorPicker();
            this.updateWidgetPreview();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Test connection button
            $('#heymag-test-connection').on('click', this.testConnection.bind(this));

            // Sync products button
            $('#heymag-sync-products').on('click', this.syncProducts.bind(this));

            // Widget preview updates
            $('#heymag_primary_color, #heymag_button_text, #heymag_position').on(
                'change input',
                this.updateWidgetPreview.bind(this)
            );

            // Token validation on input
            $('#heymag_widget_token').on('input', this.validateTokenFormat.bind(this));

            // Tab navigation
            $('.heymag-tab').on('click', this.switchTab.bind(this));
        },

        /**
         * Initialize color picker
         */
        initColorPicker: function() {
            if ($.fn.wpColorPicker) {
                $('#heymag_primary_color').wpColorPicker({
                    change: function(event, ui) {
                        HeyMagAdmin.updateWidgetPreview();
                    }
                });
            }
        },

        /**
         * Test API connection
         */
        testConnection: function(e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var $status = $('#heymag-connection-status');
            var originalText = $button.text();

            $button
                .prop('disabled', true)
                .html('<span class="heymag-spinner"></span> Testing...');

            $.ajax({
                url: heymag_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'heymag_test_connection',
                    nonce: heymag_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status
                            .removeClass('disconnected pending')
                            .addClass('connected')
                            .html(
                                '<span class="heymag-status-dot"></span> Connected to ' +
                                (response.data.business_name || 'HeyMag')
                            );

                        HeyMagAdmin.showNotice('success', 'Successfully connected to HeyMag!');
                    } else {
                        $status
                            .removeClass('connected pending')
                            .addClass('disconnected')
                            .html(
                                '<span class="heymag-status-dot"></span> ' +
                                (response.data.message || 'Connection failed')
                            );

                        HeyMagAdmin.showNotice('error', response.data.message || 'Connection test failed');
                    }
                },
                error: function(xhr, status, error) {
                    $status
                        .removeClass('connected pending')
                        .addClass('disconnected')
                        .html('<span class="heymag-status-dot"></span> Connection error');

                    HeyMagAdmin.showNotice('error', 'Network error. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Sync products with HeyMag
         */
        syncProducts: function(e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var $progress = $('#heymag-sync-progress');
            var $progressFill = $progress.find('.heymag-progress-fill');
            var $progressText = $progress.find('.heymag-progress-text');
            var originalText = $button.text();

            $button
                .prop('disabled', true)
                .html('<span class="heymag-spinner"></span> Syncing...');

            $progress.show();
            $progressFill.css('width', '10%');
            $progressText.text('Starting sync...');

            $.ajax({
                url: heymag_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'heymag_sync_products',
                    nonce: heymag_admin.nonce,
                    force: true
                },
                success: function(response) {
                    $progressFill.css('width', '100%');

                    if (response.success) {
                        var data = response.data;
                        $progressText.text(
                            'Sync complete: ' + data.synced + ' of ' + data.total + ' products synced'
                        );

                        HeyMagAdmin.showNotice(
                            data.failed > 0 ? 'warning' : 'success',
                            'Synced ' + data.synced + ' products' +
                            (data.failed > 0 ? ' (' + data.failed + ' failed)' : '')
                        );

                        // Update last sync time
                        $('#heymag-last-sync').text('Just now');
                    } else {
                        $progressText.text('Sync failed: ' + (response.data.message || 'Unknown error'));
                        HeyMagAdmin.showNotice('error', response.data.message || 'Sync failed');
                    }
                },
                error: function(xhr, status, error) {
                    $progressFill.css('width', '0%');
                    $progressText.text('Sync failed: Network error');
                    HeyMagAdmin.showNotice('error', 'Network error during sync. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);

                    // Hide progress bar after delay
                    setTimeout(function() {
                        $progress.slideUp();
                    }, 3000);
                }
            });
        },

        /**
         * Validate token format
         */
        validateTokenFormat: function(e) {
            var $input = $(e.currentTarget);
            var token = $input.val().trim();
            var $feedback = $input.next('.heymag-token-feedback');

            if (!$feedback.length) {
                $feedback = $('<span class="heymag-token-feedback"></span>');
                $input.after($feedback);
            }

            if (!token) {
                $feedback.removeClass('valid invalid').text('');
                return;
            }

            if (/^wgt_[a-z0-9]+$/.test(token)) {
                $feedback
                    .removeClass('invalid')
                    .addClass('valid')
                    .text('✓ Valid format');
            } else {
                $feedback
                    .removeClass('valid')
                    .addClass('invalid')
                    .text('Token should start with "wgt_"');
            }
        },

        /**
         * Update widget preview
         */
        updateWidgetPreview: function() {
            var color = $('#heymag_primary_color').val() ||
                        $('.wp-color-result').css('background-color') ||
                        '#2563EB';
            var buttonText = $('#heymag_button_text').val() || 'Chat with us';
            var position = $('#heymag_position').val() || 'bottom-right';

            var $preview = $('.heymag-widget-preview-button');

            $preview
                .css('background-color', color)
                .text(buttonText);

            // Update position indicator
            $('.heymag-position-indicator').text(position.replace('-', ' '));
        },

        /**
         * Switch tabs
         */
        switchTab: function(e) {
            e.preventDefault();

            var $tab = $(e.currentTarget);
            var target = $tab.data('tab');

            // Update tab states
            $('.heymag-tab').removeClass('active');
            $tab.addClass('active');

            // Show/hide content
            $('.heymag-tab-content').hide();
            $('#heymag-tab-' + target).show();
        },

        /**
         * Show notice
         */
        showNotice: function(type, message) {
            var $notice = $(
                '<div class="heymag-notice ' + type + '">' +
                    '<span>' + message + '</span>' +
                '</div>'
            );

            // Remove existing notices
            $('.heymag-notice').remove();

            // Add new notice
            $('.heymag-admin-header').after($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        HeyMagAdmin.init();
    });

})(jQuery);
