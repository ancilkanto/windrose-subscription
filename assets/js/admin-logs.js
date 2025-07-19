/**
 * Windrose Subscription Logs Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Initialize the logs page
    initLogsPage();
    
    function initLogsPage() {
        // Initialize modal functionality
        initModal();
        
        // Initialize filters
        initFilters();
        
        // Initialize table interactions
        initTableInteractions();
        
        // Initialize auto-refresh (optional)
        initAutoRefresh();
    }
    
    /**
     * Initialize modal functionality
     */
    function initModal() {
        // View log details
        $(document).on('click', '.view-log-details', function(e) {
            e.preventDefault();
            var logId = $(this).data('log-id');
            var button = $(this);
            
            console.log('View details clicked for log ID:', logId);
            
            // Show loading state
            button.prop('disabled', true).text('Loading...');
            
            // Load log details via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_log_details',
                    log_id: logId,
                    nonce: windrose_logs.nonce
                },
                success: function(response) {
                    console.log('AJAX response:', response);
                    if (response.success) {
                        $('#log-details-content').html(response.data.html);
                        $('#log-details-modal').addClass('show');
                        console.log('Modal should be visible now');
                    } else {
                        showNotification('Error loading log details: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', error);
                    showNotification('Network error occurred: ' + error, 'error');
                },
                complete: function() {
                    // Reset button state
                    button.prop('disabled', false).text('View Details');
                }
            });
        });
        
        // Close modal
        $(document).on('click', '.windrose-modal-close', function() {
            console.log('Close modal clicked');
            closeModal();
        });
        
        // Close modal when clicking outside
        $(document).on('click', '#log-details-modal', function(e) {
            if (e.target === this) {
                console.log('Close modal - clicked outside');
                closeModal();
            }
        });
        
        // Close modal with Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#log-details-modal').hasClass('show')) {
                console.log('Close modal - Escape key');
                closeModal();
            }
        });
    }
    
    /**
     * Close modal
     */
    function closeModal() {
        console.log('Closing modal');
        $('#log-details-modal').removeClass('show');
        setTimeout(function() {
            $('#log-details-content').empty();
        }, 300);
    }
    
    /**
     * Initialize filters
     */
    function initFilters() {
        // Auto-submit form on filter change
        $('.windrose-logs-filters select').on('change', function() {
            $(this).closest('form').submit();
        });
        
        // Date range validation
        $('#date_from, #date_to').on('change', function() {
            var dateFrom = $('#date_from').val();
            var dateTo = $('#date_to').val();
            
            if (dateFrom && dateTo && dateFrom > dateTo) {
                showNotification('From date cannot be after To date', 'warning');
                $(this).val('');
            }
        });
        
        // Search with debounce
        var searchTimeout;
        $('#search').on('input', function() {
            clearTimeout(searchTimeout);
            var searchTerm = $(this).val();
            
            searchTimeout = setTimeout(function() {
                if (searchTerm.length >= 3 || searchTerm.length === 0) {
                    $('#search').closest('form').submit();
                }
            }, 500);
        });
    }
    
    /**
     * Initialize table interactions
     */
    function initTableInteractions() {
        // Row hover effects
        $('.windrose-logs-table tbody tr').hover(
            function() {
                $(this).addClass('hover');
            },
            function() {
                $(this).removeClass('hover');
            }
        );
        
        // Sortable columns (if needed)
        $('.windrose-logs-table th[data-sortable]').on('click', function() {
            var column = $(this).data('column');
            var currentOrder = $(this).data('order') || 'asc';
            var newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            // Update URL and reload
            var url = new URL(window.location);
            url.searchParams.set('orderby', column);
            url.searchParams.set('order', newOrder);
            window.location.href = url.toString();
        });
        
        // Bulk actions (if needed)
        $('#bulk-action-selector-top').on('change', function() {
            var action = $(this).val();
            if (action) {
                $('#doaction').prop('disabled', false);
            } else {
                $('#doaction').prop('disabled', true);
            }
        });
    }
    
    /**
     * Initialize auto-refresh (optional)
     */
    function initAutoRefresh() {
        // Auto-refresh every 30 seconds if enabled
        if (windrose_logs.auto_refresh) {
            setInterval(function() {
                refreshLogs();
            }, 30000);
        }
    }
    
    /**
     * Refresh logs data
     */
    function refreshLogs() {
        $.ajax({
            url: window.location.href,
            type: 'GET',
            success: function(response) {
                // Extract table content from response
                var newTable = $(response).find('.windrose-logs-table').html();
                $('.windrose-logs-table').html(newTable);
                
                // Update statistics
                var newStats = $(response).find('.windrose-logs-stats').html();
                $('.windrose-logs-stats').html(newStats);
                
                // Show notification
                showNotification('Logs refreshed', 'success');
            },
            error: function() {
                showNotification('Failed to refresh logs', 'error');
            }
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        // Remove existing notifications
        $('.windrose-notification').remove();
        
        // Create notification
        var notification = $('<div class="windrose-notification windrose-notification-' + type + '">' + message + '</div>');
        
        // Add to page
        $('body').append(notification);
        
        // Show notification
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    /**
     * Export logs (if needed)
     */
    function exportLogs(format) {
        var currentUrl = new URL(window.location);
        currentUrl.searchParams.set('export', format);
        
        // Create temporary form and submit
        var form = $('<form method="post" action="' + currentUrl.toString() + '"></form>');
        $('body').append(form);
        form.submit();
        form.remove();
    }
    
    /**
     * Initialize export buttons
     */
    $(document).on('click', '.export-logs', function(e) {
        e.preventDefault();
        var format = $(this).data('format');
        exportLogs(format);
    });
    
    /**
     * Initialize refresh button
     */
    $(document).on('click', '.refresh-logs', function(e) {
        e.preventDefault();
        refreshLogs();
    });
    
    /**
     * Initialize clear filters button
     */
    $(document).on('click', '.clear-filters', function(e) {
        e.preventDefault();
        window.location.href = windrose_logs.base_url;
    });
    
    /**
     * Initialize pagination
     */
    $(document).on('click', '.windrose-pagination a', function(e) {
        // Add loading state
        $('.windrose-logs-table').addClass('loading');
    });
    
    /**
     * Initialize status badge tooltips
     */
    $('.status-badge').each(function() {
        var status = $(this).text();
        var tooltip = getStatusTooltip(status);
        if (tooltip) {
            $(this).attr('title', tooltip);
        }
    });
    
    /**
     * Get status tooltip text
     */
    function getStatusTooltip(status) {
        var tooltips = {
            'Initiated': 'Payment process has been initiated',
            'Intention Created': 'Paymob intention has been created successfully',
            'Payment Processing': 'Payment is being processed by Paymob',
            'Success': 'Payment completed successfully',
            'Failed': 'Payment failed during processing',
            'Token Error': 'No payment token found for customer'
        };
        
        return tooltips[status] || '';
    }
    
    /**
     * Initialize keyboard shortcuts
     */
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + R to refresh
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            refreshLogs();
        }
        
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            $('#search').focus();
        }
    });
    
    /**
     * Initialize responsive table
     */
    function initResponsiveTable() {
        if ($(window).width() < 768) {
            $('.windrose-logs-table').addClass('responsive');
        } else {
            $('.windrose-logs-table').removeClass('responsive');
        }
    }
    
    // Handle window resize
    $(window).on('resize', function() {
        initResponsiveTable();
    });
    
    // Initialize on load
    initResponsiveTable();
    
    /**
     * Initialize loading states
     */
    $(document).on('submit', '.windrose-logs-filters form', function() {
        $('.windrose-logs-table').addClass('loading');
    });
    
    /**
     * Remove loading state when page loads
     */
    $(window).on('load', function() {
        $('.windrose-logs-table').removeClass('loading');
    });
    
});

/**
 * Global functions for external use
 */
window.WindroseLogs = {
    refresh: function() {
        location.reload();
    },
    
    export: function(format) {
        var currentUrl = new URL(window.location);
        currentUrl.searchParams.set('export', format);
        window.location.href = currentUrl.toString();
    },
    
    showNotification: function(message, type) {
        // Implementation for external notification calls
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        }
    }
};

/**
 * Copy text to clipboard
 */
function copyToClipboard(button, text) {
    // Create a temporary textarea element
    var textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    
    // Select and copy the text
    textarea.select();
    document.execCommand('copy');
    
    // Remove the temporary element
    document.body.removeChild(textarea);
    
    // Update button text and style
    var originalText = button.textContent;
    button.textContent = 'Copied!';
    button.classList.add('copied');
    
    // Reset button after 2 seconds
    setTimeout(function() {
        button.textContent = originalText;
        button.classList.remove('copied');
    }, 2000);
    
    // Show notification
    if (typeof showNotification === 'function') {
        showNotification('JSON copied to clipboard!', 'success');
    }
}

/**
 * Format JSON with syntax highlighting
 */
function formatJSON(jsonString) {
    try {
        var obj = JSON.parse(jsonString);
        return JSON.stringify(obj, null, 2);
    } catch (e) {
        return jsonString;
    }
} 