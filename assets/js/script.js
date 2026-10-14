// Auto calculate totals in job form
function calculateTotals() {
    var plateCount = parseFloat($('#plate_count').val()) || 0;
    var plateRate = parseFloat($('#plate_rate').val()) || 0;
    var titleQty = parseFloat($('#title_qty').val()) || 0;
    var titleRate = parseFloat($('#title_rate').val()) || 0;
    var gstPercent = parseFloat($('#gst_percent').val()) || 0;
    
    var plateTotal = plateCount * plateRate;
    var titleTotal = titleQty * titleRate;
    var totalAmount = plateTotal + titleTotal;
    var gstAmount = totalAmount * (gstPercent / 100);
    var netAmount = totalAmount + gstAmount;
    
    $('#plate_total_display').text('₹' + plateTotal.toFixed(2));
    $('#title_total_display').text('₹' + titleTotal.toFixed(2));
    $('#total_amount_display').text('₹' + totalAmount.toFixed(2));
    $('#gst_amount_display').text('₹' + gstAmount.toFixed(2));
    $('#net_amount_display').text('₹' + netAmount.toFixed(2));
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this?');
}

// Print invoice
function printInvoice() {
    window.print();
}

// Date picker helper
$(document).ready(function() {
    // Initialize date inputs with today
    $('input[type="date"]').each(function() {
        if (!$(this).val()) {
            $(this).val(new Date().toISOString().split('T')[0]);
        }
    });
    
    // Auto calculate on change for job form
    $('#plate_count, #plate_rate, #title_qty, #title_rate, #gst_percent').on('change keyup', function() {
        if (typeof calculateTotals === 'function') {
            calculateTotals();
        }
    });
    
    // Auto calculate on load
    if (typeof calculateTotals === 'function') {
        calculateTotals();
    }
    
    // Toggle sidebar on mobile
    $('.navbar-toggler').on('click', function() {
        $('.sidebar').toggleClass('show');
    });
    
    // Auto close alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});

// Stock calculation
function checkStock() {
    var sizeId = $('#size_id').val();
    var gsmId = $('#gsm_id').val();
    
    if (sizeId && gsmId) {
        $.ajax({
            url: '../api/get_stock.php',
            method: 'POST',
            data: {size_id: sizeId, gsm_id: gsmId},
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#current_stock').text(response.balance);
                }
            },
            error: function() {
                $('#current_stock').text('Error fetching stock');
            }
        });
    }
}

// Export to CSV function
function exportTableToCSV(tableId, filename) {
    var csv = [];
    var rows = document.querySelectorAll('#' + tableId + ' tr');
    
    for (var i = 0; i < rows.length; i++) {
        var row = [];
        var cols = rows[i].querySelectorAll('td, th');
        for (var j = 0; j < cols.length; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        csv.push(row.join(','));
    }
    
    var csvContent = csv.join('\n');
    var link = document.createElement('a');
    link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent));
    link.setAttribute('download', filename + '_' + new Date().toISOString().slice(0,10) + '.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}   

// Sidebar dropdown toggle with animation
$(document).ready(function() {
    // Handle sidebar dropdown clicks with proper toggle using native Bootstrap 5 Collapse
    $('.sidebar .list-group-item[data-bs-toggle="collapse"]').on('click', function(e) {
        e.preventDefault();
        var targetId = $(this).attr('href');
        var targetEl = document.querySelector(targetId);
        if (!targetEl) return;
        
        var bsCollapse = bootstrap.Collapse.getOrCreateInstance(targetEl);
        var isExpanded = $(this).attr('aria-expanded') === 'true';
        
        // Close other open menus
        $('.sidebar .list-group-item[data-bs-toggle="collapse"]').not(this).each(function() {
            var otherTargetId = $(this).attr('href');
            var otherTargetEl = document.querySelector(otherTargetId);
            if (otherTargetEl && otherTargetEl.classList.contains('show')) {
                var otherBsCollapse = bootstrap.Collapse.getOrCreateInstance(otherTargetEl);
                otherBsCollapse.hide();
                $(this).attr('aria-expanded', 'false');
            }
        });
        
        // Toggle current menu
        if (isExpanded) {
            bsCollapse.hide();
            $(this).attr('aria-expanded', 'false');
        } else {
            bsCollapse.show();
            $(this).attr('aria-expanded', 'true');
        }
    });
    
    // Prevent collapse when clicking on sub-menu items
    $('.sidebar .collapse .list-group-item').on('click', function(e) {
        e.stopPropagation();
        var href = $(this).attr('href');
        if (href && href !== '#') {
            window.location.href = href;
        }
    });
});