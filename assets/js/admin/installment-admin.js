/**
 * Installment Admin JavaScript
 * 
 * JavaScript functionality for installment features in WordPress admin
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initInstallmentAdmin();
    });

    /**
     * Initialize installment admin functionality
     */
    function initInstallmentAdmin() {
        initAccordionFunctionality();
        initInstallmentStatusSaver();
        initTooltips();
    }

    /**
     * Initialize accordion functionality for installment orders
     */
    function initAccordionFunctionality() {
        const accordionButtons = $('.view-installment-status-btn');
        
        accordionButtons.on('click', function() {
            const $button = $(this);
            const orderId = $button.data('order-id');
            const $content = $('#installment-status-' + orderId);
            const $icon = $button.find('.btn-icon svg');
            
            if ($content.length === 0) {
                console.warn('Installment status content not found for order:', orderId);
                return;
            }
            
            if ($content.is(':visible')) {
                // Close accordion
                $content.slideUp(300, function() {
                    $content.hide();
                });
                $icon.css('transform', 'rotate(0deg)');
                $button.removeClass('active');
            } else {
                // Open accordion
                $content.show().slideDown(300);
                $icon.css('transform', 'rotate(180deg)');
                $button.addClass('active');
            }
        });
    }

    /**
     * Initialize installment status saver
     */
    function initInstallmentStatusSaver() {
        $('#beban_save_installment_status').on('click', function() {
            const $button = $(this);
            const $select = $('#beban_installment_status');
            const orderId = $button.data('order-id') || getOrderIdFromUrl();
            const status = $select.val();
            const nonce = $button.data('nonce');
            
            if (!orderId || !status || !nonce) {
                alert('خطا: اطلاعات ناقص است');
                return;
            }
            
            // Disable button during request
            $button.prop('disabled', true).text('در حال ذخیره...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'beban_save_installment_status',
                    order_id: orderId,
                    installment_status: status,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        showAdminNotice('وضعیت اقساط با موفقیت ذخیره شد.', 'success');
                        
                        // Update status display if exists
                        updateStatusDisplay(orderId, status);
                    } else {
                        showAdminNotice('خطا در ذخیره وضعیت: ' + (response.data || 'خطای نامشخص'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    showAdminNotice('خطا در ارتباط با سرور.', 'error');
                },
                complete: function() {
                    // Re-enable button
                    $button.prop('disabled', false).text('ذخیره');
                }
            });
        });
    }

    /**
     * Initialize tooltips for admin elements
     */
    function initTooltips() {
        // Add tooltips to status indicators
        $('.status-indicator').each(function() {
            const $this = $(this);
            const tooltip = $this.data('tooltip');
            
            if (tooltip) {
                $this.attr('title', tooltip);
            }
        });
    }

    /**
     * Get order ID from URL parameters
     */
    function getOrderIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id') || urlParams.get('post');
    }

    /**
     * Show admin notice
     */
    function showAdminNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Remove existing notices
        $('.notice').remove();
        
        // Add new notice
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $notice.remove();
            });
        }, 5000);
    }

    /**
     * Update status display in the page
     */
    function updateStatusDisplay(orderId, status) {
        const statusTexts = {
            'First': 'قسط اول',
            'Second': 'قسط دوم',
            'Both': 'تکمیل شده'
        };
        
        const statusColors = {
            'First': '#d63638',
            'Second': '#ff8c00',
            'Both': '#00a32a'
        };
        
        // Update status in meta box
        const $statusDisplay = $('.beban-installment-status-display');
        if ($statusDisplay.length) {
            $statusDisplay.text(statusTexts[status] || status);
            $statusDisplay.css('color', statusColors[status] || '#666');
        }
        
        // Update status in orders list if on that page
        const $statusColumn = $('.column-beban_installment_status span[data-order-id="' + orderId + '"]');
        if ($statusColumn.length) {
            $statusColumn.text(statusTexts[status] || status);
            $statusColumn.css('color', statusColors[status] || '#666');
        }
    }

    /**
     * Utility function to format currency
     */
    function formatCurrency(amount, currency = 'IRR') {
        return new Intl.NumberFormat('fa-IR', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }

    /**
     * Utility function to format date
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('fa-IR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(date);
    }

    // Export functions for global access if needed
    window.BebanInstallmentAdmin = {
        showNotice: showAdminNotice,
        formatCurrency: formatCurrency,
        formatDate: formatDate,
        updateStatusDisplay: updateStatusDisplay
    };

})(jQuery);
