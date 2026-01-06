/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - Forms Handler
 * -------------------------------------------------------------------------
 * Handles AJAX submission for plugin forms
 */

$(document).ready(function() {
    
    // Profile permissions form handler
    $(document).on('submit', '#profile-form-flowbpmn', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const formData = $form.serialize();
        const $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
        
        // Disable submit button
        $submitBtn.prop('disabled', true);
        
        $.ajax({
            url: CFG_GLPI.root_doc + '/plugins/flowbpmn/ajax/profile.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayAjaxMessageAfterRedirect();
                    // Show success message using GLPI's toast
                    if (typeof glpi_toast_info === 'function') {
                        glpi_toast_info(response.message);
                    }
                } else {
                    if (typeof glpi_toast_error === 'function') {
                        glpi_toast_error(response.error || 'Unknown error');
                    } else {
                        alert(response.error || 'Unknown error');
                    }
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.error || 'Server error occurred';
                if (typeof glpi_toast_error === 'function') {
                    glpi_toast_error(error);
                } else {
                    alert(error);
                }
            },
            complete: function() {
                // Re-enable submit button
                $submitBtn.prop('disabled', false);
            }
        });
        
        return false;
    });
    
    // Config form handler
    $(document).on('submit', '#config-form-flowbpmn', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const formData = $form.serialize();
        const $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
        
        // Disable submit button
        $submitBtn.prop('disabled', true);
        
        $.ajax({
            url: CFG_GLPI.root_doc + '/plugins/flowbpmn/ajax/config.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (typeof glpi_toast_info === 'function') {
                        glpi_toast_info(response.message);
                    } else {
                        alert(response.message);
                    }
                } else {
                    if (typeof glpi_toast_error === 'function') {
                        glpi_toast_error(response.error || 'Unknown error');
                    } else {
                        alert(response.error || 'Unknown error');
                    }
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.error || 'Server error occurred';
                if (typeof glpi_toast_error === 'function') {
                    glpi_toast_error(error);
                } else {
                    alert(error);
                }
            },
            complete: function() {
                // Re-enable submit button
                $submitBtn.prop('disabled', false);
            }
        });
        
        return false;
    });
    
});
