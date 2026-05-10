/**
 * Verso Consultation Form Handler
 */

jQuery(document).ready(function ($) {
    const messageEl = $('#form-message');
    let selectedFiles = [];

    // Initialize: Owner fields are always required, Vet fields are optional
    $(document).ready(function () {
        // Owner fields always required
        $('#owner-section input[name="owner_nom"], #owner-section input[name="owner_prenom"], #owner-section input[name="owner_email"], #owner-section input[name="owner_telephone"]').prop('required', true);

        // Vet fields optional (not required)
        $('#vet-section input[name], #vet-section select[name]').prop('required', false);
    });

    // File management UI
    $('#add-file-btn').on('click', function (e) {
        e.preventDefault();
        $('#fichiers-hidden').click();
    });

    // Handle file input changes
    $('#fichiers-hidden').on('change', function () {
        const newFiles = Array.from(this.files);

        if (newFiles.length === 0) return;

        newFiles.forEach(function (file) {
            addFileToList(file);
        });

        // Reset the hidden input
        $(this).val('');
        updateActualFileInput();
    });

    /**
     * Add file to selected files list with progress animation
     */
    function addFileToList(file) {
        const maxFiles = 10;
        const maxSizePerFile = 5 * 1024 * 1024; // 5 MB

        // Check if we already have this file
        if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
            showMessage('Ce fichier est déjà sélectionné', 'error');
            return;
        }

        // Check file count
        if (selectedFiles.length >= maxFiles) {
            showMessage('❌ Vous avez atteint le maximum de ' + maxFiles + ' fichiers', 'error');
            return;
        }

        // Check file size
        if (file.size > maxSizePerFile) {
            showMessage('❌ Le fichier "' + file.name + '" dépasse 5 MB', 'error');
            return;
        }

        // Add to selected files
        const fileIndex = selectedFiles.length;
        selectedFiles.push(file);

        // Show file list container
        $('#file-list').show();

        // Simulate progress
        const fileId = 'file-item-' + fileIndex;
        const fileHtml = `
            <div id="${fileId}" style="margin-bottom:12px; padding:12px; background:white; border-left:4px solid #e74c3c; border-radius:2px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <div style="flex:1;">
                        <div style="font-weight:600; color:#333; word-break:break-word;">📄 ${escapeHtml(file.name)}</div>
                        <div style="font-size:0.85rem; color:#666;">${formatFileSize(file.size)}</div>
                    </div>
                    <button type="button" class="remove-file-btn" data-index="${fileIndex}" style="background-color:#f0f0f0; color:#d32f2f; padding:6px 12px; border:none; border-radius:2px; cursor:pointer; font-size:12px; font-weight:600; white-space:nowrap; margin-left:10px;">✕ Retirer</button>
                </div>
                <div style="width:100%; height:4px; background:#e0e0e0; border-radius:2px; overflow:hidden;">
                    <div class="progress-bar" style="width:0%; height:100%; background:#e74c3c; transition:width 0.1s linear;"></div>
                </div>
            </div>
        `;

        $('#file-list').append(fileHtml);

        // Simulate progress bar
        const progressBar = $('#' + fileId + ' .progress-bar');
        let progress = 0;
        const interval = setInterval(function () {
            progress += Math.random() * 30;
            if (progress > 100) progress = 100;
            progressBar.css('width', progress + '%');

            if (progress >= 100) {
                clearInterval(interval);
                setTimeout(function () {
                    // After progress completes, show as done
                    $('#' + fileId).css('border-left-color', '#4caf50');
                    progressBar.css('background-color', '#4caf50');
                }, 300);
            }
        }, 100);

        // Handle remove button click
        $(document).off('click', '.remove-file-btn[data-index="' + fileIndex + '"]');
        $(document).on('click', '.remove-file-btn[data-index="' + fileIndex + '"]', function (e) {
            e.preventDefault();
            removeFileAtIndex(fileIndex);
        });

        updateFilePreview();
    }

    /**
     * Remove file at given index
     */
    function removeFileAtIndex(index) {
        selectedFiles.splice(index, 1);

        // Rebuild file list
        rebuildFileList();
        updateActualFileInput();
        updateFilePreview();

        if (selectedFiles.length === 0) {
            $('#file-list').hide();
        }
    }

    /**
     * Rebuild entire file list
     */
    function rebuildFileList() {
        $('#file-list').empty();

        if (selectedFiles.length === 0) {
            $('#file-list').hide();
            return;
        }

        selectedFiles.forEach(function (file, index) {
            const fileId = 'file-item-' + index;
            const fileHtml = `
                <div id="${fileId}" style="margin-bottom:12px; padding:12px; background:white; border-left:4px solid #4caf50; border-radius:2px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <div style="flex:1;">
                            <div style="font-weight:600; color:#333; word-break:break-word;">✓ ${escapeHtml(file.name)}</div>
                            <div style="font-size:0.85rem; color:#666;">${formatFileSize(file.size)}</div>
                        </div>
                        <button type="button" class="remove-file-btn" data-index="${index}" style="background-color:#f0f0f0; color:#d32f2f; padding:6px 12px; border:none; border-radius:2px; cursor:pointer; font-size:12px; font-weight:600; white-space:nowrap; margin-left:10px;">✕ Retirer</button>
                    </div>
                    <div style="width:100%; height:4px; background:#e0e0e0; border-radius:2px; overflow:hidden;">
                        <div class="progress-bar" style="width:100%; height:100%; background:#4caf50;"></div>
                    </div>
                </div>
            `;
            $('#file-list').append(fileHtml);

            $(document).off('click', '.remove-file-btn[data-index="' + index + '"]');
            $(document).on('click', '.remove-file-btn[data-index="' + index + '"]', function (e) {
                e.preventDefault();
                removeFileAtIndex(index);
            });
        });

        $('#file-list').show();
    }

    /**
     * Update actual file input with current selected files
     */
    function updateActualFileInput() {
        if (selectedFiles.length === 0) {
            $('#fichiers').val('');
            return;
        }

        // Use DataTransfer to create a FileList-like object
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(function (file) {
            dataTransfer.items.add(file);
        });
        document.getElementById('fichiers').files = dataTransfer.files;
    }

    /**
     * Update file preview summary
     */
    function updateFilePreview() {
        const preview = $('#file-preview');
        preview.empty();

        if (selectedFiles.length === 0) {
            preview.html('');
            return;
        }

        let totalSize = 0;
        selectedFiles.forEach(function (file) {
            totalSize += file.size;
        });

        const maxTotalSize = 50 * 1024 * 1024; // 50 MB
        const summary = `
            <div style="padding:10px; background:#e3f2fd; border-left:3px solid #1c2445; border-radius:2px; color:#333; font-size:0.9rem;">
                <strong>✓ ${selectedFiles.length} fichier(s)</strong> sélectionné(s) (${formatFileSize(totalSize)})
                ${totalSize > maxTotalSize ? '<br/><span style="color:#d32f2f;">❌ Taille totale dépasse 50 MB</span>' : ''}
            </div>
        `;
        preview.append(summary);
    }


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
                // WordPress wraps response in { success: true, data: {...} }
                const data = response.data || response;

                // Populate confirmation section
                $('#conf-owner-name').text(data.owner_prenom + ' ' + data.owner_nom);
                $('#conf-animal-name').text(data.animal_nom);
                $('#conf-owner-email').text(data.owner_email);
                $('#conf-uuid').text(data.uuid);

                // Reset selectedFiles array and file list UI
                selectedFiles = [];
                $('#file-list').empty().hide();
                $('#file-preview').empty();
                resetFormFields();

                // Hide form section
                $('#verso-form-section').hide();

                // Show confirmation section
                $('#verso-confirmation').show();

                // Scroll to confirmation
                $('html, body').animate({
                    scrollTop: $('#verso-confirmation').offset().top - 100
                }, 600);
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

    /**
     * Handle "Submit another request" button
     */
    $(document).on('click', '#new-consultation-btn', function (e) {
        e.preventDefault();

        // Reset selectedFiles and form
        selectedFiles = [];
        $('#file-list').empty().hide();
        $('#file-preview').empty();
        resetFormFields();

        // Hide confirmation, show form
        $('#verso-confirmation').hide();
        $('#verso-form-section').show();

        // Scroll to form top
        $('html, body').animate({
            scrollTop: $('#verso-form-section').offset().top - 100
        }, 600);
    });
});
