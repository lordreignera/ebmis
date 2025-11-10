/**
 * Universal export function for all reports
 * @param {string} format - The export format (csv, excel, pdf)
 * @param {string} reportType - Optional report type identifier
 */
function exportReport(format, reportType = null) {
    // Get the main form on the page
    var form = document.querySelector('form[method="GET"]');
    
    if (!form) {
        console.error('No form found for export');
        return;
    }
    
    // Get current form data
    var formData = new FormData(form);
    
    // Add download parameter
    formData.append('download', format);
    
    // Create a new form for download
    var downloadForm = document.createElement('form');
    downloadForm.method = 'GET';
    downloadForm.action = form.action;
    downloadForm.target = '_blank'; // Open in new tab/window
    
    // Add all form data as hidden inputs
    for (var pair of formData.entries()) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = pair[0];
        input.value = pair[1];
        downloadForm.appendChild(input);
    }
    
    // Submit the form
    document.body.appendChild(downloadForm);
    downloadForm.submit();
    document.body.removeChild(downloadForm);
}

// Alternative method using URL parameters
function exportReportByUrl(format) {
    var currentUrl = new URL(window.location);
    currentUrl.searchParams.set('download', format);
    
    // Open in new window to trigger download
    window.open(currentUrl.toString(), '_blank');
}