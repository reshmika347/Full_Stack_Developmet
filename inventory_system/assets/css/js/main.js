// Main JavaScript file

$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-toggle="popover"]').popover();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
    
    // Confirm delete actions
    $('.delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
    
    // Sidebar toggle on mobile
    $('#sidebarToggle').on('click', function(e) {
        e.preventDefault();
        $('.sidebar-wrapper').toggleClass('active');
        $('.main-content').toggleClass('active');
    });
    
    // Search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.searchable-table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});

// Format currency
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Format date
function formatDate(date) {
    var d = new Date(date);
    return d.getFullYear() + '-' + 
           String(d.getMonth() + 1).padStart(2, '0') + '-' + 
           String(d.getDate()).padStart(2, '0');
}

// Show notification
function showNotification(message, type = 'info') {
    var alertClass = 'alert-' + type;
    var html = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
               message +
               '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
               '<span aria-hidden="true">&times;</span>' +
               '</button>' +
               '</div>';
    
    $('#notification-area').html(html);
    
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

// Print function
function printContent(elementId) {
    var printContent = document.getElementById(elementId).innerHTML;
    var originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

// Export to CSV
function exportToCSV(data, filename) {
    var csv = '';
    
    // Get headers
    var headers = Object.keys(data[0]);
    csv += headers.join(',') + '\n';
    
    // Get data
    data.forEach(function(row) {
        var values = headers.map(function(header) {
            return '"' + row[header] + '"';
        });
        csv += values.join(',') + '\n';
    });
    
    // Download
    var blob = new Blob([csv], { type: 'text/csv' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}