/**
 * Accessibility Guard — Admin JavaScript
 */
(function($) {
    'use strict';

    var WPAG = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.wpag-scan-page', this.scanPage);
            $(document).on('click', '.wpag-scan-all', this.scanAll);
            $(document).on('click', '.wpag-clear-results', this.clearResults);
            $(document).on('click', '.wpag-toggle-element', this.toggleElement);
            $(document).on('click', '#wpag-generate-statement', this.generateStatement);
        },

        /**
         * Scan a single page.
         */
        scanPage: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var originalText = $btn.text();

            $btn.prop('disabled', true).text(wpag_data.scanning_text);

            $.post(wpag_data.ajax_url, {
                action: 'wpag_scan_page',
                nonce: wpag_data.nonce,
                post_id: postId
            }, function(response) {
                $btn.prop('disabled', false).text(wpag_data.rescan_text || originalText);

                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || wpag_data.scan_failed);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text(originalText);
                alert(wpag_data.request_failed);
            });
        },

        /**
         * Scan all pages in batches.
         */
        scanAll: function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true);

            WPAG.showProgress();
            WPAG.scanBatch(0);
        },

        /**
         * Recursive batch scanning.
         */
        scanBatch: function(offset) {
            $.post(wpag_data.ajax_url, {
                action: 'wpag_scan_all',
                nonce: wpag_data.nonce,
                offset: offset
            }, function(response) {
                if (response.success) {
                    var data = response.data;
                    var progress = Math.min(100, Math.round((data.offset / data.total) * 100));
                    WPAG.updateProgress(progress, data.offset, data.total);

                    if (!data.complete) {
                        WPAG.scanBatch(data.offset);
                    } else {
                        WPAG.updateProgress(100, data.total, data.total);
                        $('.wpag-progress-text').text(wpag_data.complete_text);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                } else {
                    alert(response.data.message || wpag_data.scan_failed);
                    WPAG.hideProgress();
                    $('.wpag-scan-all').prop('disabled', false);
                }
            }).fail(function() {
                alert(wpag_data.request_failed);
                WPAG.hideProgress();
                $('.wpag-scan-all').prop('disabled', false);
            });
        },

        /**
         * Toggle element snippet (short/full).
         */
        toggleElement: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $td = $btn.closest('td');
            var $short = $td.find('.wpag-element-short');
            var $full = $td.find('.wpag-element-full');

            if ($full.is(':visible')) {
                $full.hide();
                $short.show();
                $btn.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
            } else {
                $short.hide();
                $full.show();
                $btn.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
            }
        },

        /**
         * Clear all scan results.
         */
        clearResults: function(e) {
            e.preventDefault();

            if (!confirm(wpag_data.confirm_clear)) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.post(wpag_data.ajax_url, {
                action: 'wpag_clear_results',
                nonce: wpag_data.nonce
            }, function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || wpag_data.clear_failed);
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                alert(wpag_data.request_failed);
            });
        },

        /**
         * Show the progress bar.
         */
        showProgress: function() {
            $('.wpag-progress').show();
            WPAG.updateProgress(0, 0, 0);
        },

        /**
         * Hide the progress bar.
         */
        hideProgress: function() {
            $('.wpag-progress').hide();
        },

        /**
         * Update progress bar.
         */
        updateProgress: function(percent, current, total) {
            $('.wpag-progress-fill').css('width', percent + '%');
            if (total > 0) {
                $('.wpag-progress-text').text(current + ' / ' + total + ' pages (' + percent + '%)');
            }
        },

        /**
         * Generate accessibility statement page.
         */
        generateStatement: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $spinner = $('#wpag-statement-spinner');
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            $.post(wpag_data.ajax_url, {
                action: 'wpag_generate_statement',
                wpag_statement_nonce_field: $('#wpag_statement_nonce_field').val(),
                org_name: $('#wpag_org_name').val(),
                email: $('#wpag_email').val(),
                conformance: $('#wpag_conformance').val(),
                limitations: $('#wpag_limitations').val()
            }, function(response) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || wpag_data.error_generating);
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                alert(wpag_data.request_failed);
            });
        }
    };

    $(document).ready(function() {
        WPAG.init();
    });

})(jQuery);
