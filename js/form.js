/**
 * Verso Consultation Form Handler
 */

jQuery(document).ready(function ($) {
    const messageEl = $('#form-message');

    // Initialize: Owner fields are always required, Vet fields are optional
    $(document).ready(function () {
        // Owner fields always required
        $('#owner-section input[name="owner_nom"], #owner-section input[name="owner_prenom"], #owner-section input[name="owner_email"], #owner-section input[name="owner_telephone"]').prop('required', true);

        // Vet fields optional (not required)
        $('#vet-section input[name], #vet-section select[name]').prop('required', false);
    });

    // Handle file input changes
    $('#fichiers').on('change', function () {
        const files = this.files;
        const preview = $('#file-preview');
        preview.empty();

        if (files.length === 0) {
            preview.html('');
            return;
        }

        const maxFiles = 10;
        const maxSizePerFile = 5 * 1024 * 1024; // 5 MB
        const maxTotalSize = 50 * 1024 * 1024; // 50 MB
        let totalSize = 0;
        let hasErrors = false;

        // Check file count
        if (files.length > maxFiles) {
            preview.append(
                '<div style="color: #d32f2f; font-size: 0.9rem; margin-bottom: 10px;">❌ Maximum ' + maxFiles + ' fichiers autorisés. Vous en avez sélectionné ' + files.length + '</div>'
            );
            hasErrors = true;
        }

        $.each(files, function (i, file) {
            totalSize += file.size;
            const size = formatFileSize(file.size);

            // Check individual file size
            const isOversized = file.size > maxSizePerFile;
            const sizeColor = isOversized ? '#d32f2f' : '#666';
            const icon = isOversized ? '⚠️' : '📄';

            const fileHtml = `
                <div class="file-item" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #f5f5f5; border-radius: 4px; margin-bottom: 8px; border-left: 3px solid ${isOversized ? '#d32f2f' : '#1c2445'};">
                    <span style="font-size: 16px;">${icon}</span>
                    <span class="file-name" style="flex: 1; word-break: break-word; color: #333;">${escapeHtml(file.name)}</span>
                    <span class="file-size" style="font-size: 0.85rem; color: ${sizeColor}; font-weight: 600; white-space: nowrap;">${size}</span>
                </div>
            `;
            preview.append(fileHtml);

            if (isOversized) {
                hasErrors = true;
            }
        });

        // Show warnings
        if (totalSize > maxTotalSize) {
            preview.append(
                '<div style="color: #d32f2f; font-size: 0.9rem; margin-top: 10px; padding: 8px; background: #fff3cd; border-left: 3px solid #d32f2f; border-radius: 2px;">⚠️ Taille totale dépasse 50 MB</div>'
            );
            hasErrors = true;
        }

        if (hasErrors && files.length <= maxFiles) {
            preview.append(
                '<div style="color: #d32f2f; font-size: 0.9rem; margin-top: 10px;">❌ Certains fichiers dépassent 5 MB - Veuillez corriger avant envoi</div>'
            );
        }
    });

    // Form submission via button click (no form tag)
    $(document).on('click', '#verso-submit-btn', function (e) {
        e.preventDefault();

        // Validate file uploads before submitting
        const fileInput = document.getElementById('fichiers');
        if (fileInput && fileInput.files.length > 0) {
            const maxFiles = 10;
            const maxSizePerFile = 5 * 1024 * 1024; // 5 MB
            const maxTotalSize = 50 * 1024 * 1024; // 50 MB
            let totalSize = 0;

            // Check file count
            if (fileInput.files.length > maxFiles) {
                showMessage('❌ Maximum ' + maxFiles + ' fichiers autorisés', 'error');
                return;
            }

            // Check individual and total sizes
            for (let i = 0; i < fileInput.files.length; i++) {
                const file = fileInput.files[i];

                if (file.size > maxSizePerFile) {
                    showMessage('❌ Le fichier "' + file.name + '" dépasse 5 MB', 'error');
                    return;
                }

                totalSize += file.size;
            }

            if (totalSize > maxTotalSize) {
                showMessage('❌ La taille totale des fichiers dépasse 50 MB', 'error');
                return;
            }
        }

        // Validate animal data
        const animalNom = $('#animal_nom').val().trim();
        const animalEspece = $('#animal_espece').val();
        const motif = $('#motif').val().trim();

        if (!animalNom || !animalEspece) {
            showMessage('Veuillez remplir les données du patient', 'error');
            return;
        }

        if (!motif) {
            showMessage('Veuillez décrire le motif de la consultation', 'error');
            return;
        }

        // Always validate owner information (required)
        const ownerNom = $('#owner_nom').val().trim();
        const ownerPrenom = $('#owner_prenom').val().trim();
        const ownerEmail = $('#owner_email').val().trim();
        const ownerPhone = $('#owner_telephone').val().trim();
        const ownerAdresse = $('#owner_adresse').val().trim();
        const ownerCP = $('#owner_code_postal').val().trim();
        const ownerVille = $('#owner_ville').val().trim();

        if (!ownerNom || !ownerPrenom || !ownerEmail || !ownerPhone || !ownerAdresse || !ownerCP || !ownerVille) {
            showMessage('Veuillez remplir toutes les coordonnées du propriétaire (adresse complète requise)', 'error');
            return;
        }

        // Vet information is optional - just check if partially filled
        const vetNom = $('#vet_nom').val().trim();
        const vetClinic = $('#vet_clinique').val().trim();
        const vetEmail = $('#vet_email').val().trim();
        const vetPhone = $('#vet_telephone').val().trim();

        // If ANY vet field is filled, require all vet fields
        const vetFieldsFilled = vetNom || vetClinic || vetEmail || vetPhone;
        if (vetFieldsFilled) {
            if (!vetNom || !vetClinic || !vetEmail || !vetPhone) {
                showMessage('Si vous remplissez les infos du vétérinaire, complétez tous les champs requis', 'error');
                return;
            }
        }

        // Submit form
        submitForm();
    });

    /**
     * Submit form via AJAX
     */
    function submitForm() {
        // Collect form data without form element
        const formData = buildFormData();

        // Add AJAX indicator
        const submitBtn = $('#verso-submit-btn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html(
            '<span class="verso-loading-spinner"></span>Envoi en cours...'
        );

        $.ajax({
            url: versoConsultation.ajaxUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 60000,
            success: function (response) {
                showMessage(
                    '✅ ' + response.message,
                    'success'
                );
                resetFormFields();
                $('#file-preview').empty();
                // Keep owner section visible, hide vet section after submission
                $('#owner-section').removeClass('verso-hidden');
                $('#vet-section').addClass('verso-hidden');

                // Scroll to message
                $('html, body').animate({
                    scrollTop: messageEl.offset().top - 100
                }, 500);

                // Reset form after 3 seconds
                setTimeout(() => {
                    messageEl.fadeOut();
                }, 3000);
            },
            error: function (xhr, status, error) {
                let message = 'Une erreur est survenue. Veuillez réessayer.';

                if (xhr.status === 400) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        message = '❌ ' + (response.message || message);
                    } catch (e) {
                        message = '❌ Données invalides';
                    }
                } else if (xhr.status === 403) {
                    message = '❌ Erreur de sécurité (CSRF)';
                } else if (xhr.status === 500) {
                    message = '❌ Erreur serveur. Veuillez contacter consultations@verso-vet.com';
                } else if (status === 'timeout') {
                    message = '❌ Délai d\'attente dépassé. Veuillez réessayer.';
                }

                showMessage(message, 'error');

                // Log full error for debugging
                console.error('Form submission error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    error: error,
                    response: xhr.responseText
                });
            },
            complete: function () {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * Build FormData from all form fields on the page
     * (no form element dependency)
     */
    function buildFormData() {
        const fd = new FormData();
        fd.append('action', 'verso_submit_consultation');

        // Collect text, email, tel, select, textarea fields
        $('input[name]:not([type="file"]):not([type="submit"]):not([type="button"]):not([type="reset"]), select[name], textarea[name]').each(function() {
            fd.append($(this).attr('name'), $(this).val() || '');
        });

        // Collect multiple files
        const fileInput = document.getElementById('fichiers');
        if (fileInput && fileInput.files) {
            Array.from(fileInput.files).forEach(function(file) {
                fd.append('fichiers[]', file);
            });
        }

        return fd;
    }

    /**
     * Reset all form fields on the page
     * (no form element dependency)
     */
    function resetFormFields() {
        // Reset text/email/tel inputs
        $('input[name]:not([type="file"]):not([type="submit"]):not([type="button"]):not([type="reset"])').val('');

        // Reset selects (set to first option)
        $('select[name]').prop('selectedIndex', 0);

        // Reset textareas
        $('textarea[name]').val('');

        // Reset file input
        const fileInput = document.getElementById('fichiers');
        if (fileInput) fileInput.value = '';

        // Clear file preview
        $('#file-preview').empty();
    }

    /**
     * Show message
     *
     * @param {string} message Message text
     * @param {string} type Type (success or error)
     */
    function showMessage(message, type) {
        messageEl
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .fadeIn();

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function () {
                messageEl.fadeOut();
            }, 5000);
        }
    }

    /**
     * Format file size
     *
     * @param {number} bytes File size in bytes
     * @returns {string} Formatted size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Escape HTML
     *
     * @param {string} text Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
});
