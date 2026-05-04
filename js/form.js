/**
 * Verso Consultation Form Handler
 */

jQuery(document).ready(function ($) {
    const form = $('#verso-consultation-form');
    const messageEl = $('#form-message');

    // Handle submitter type change
    $('input[name="submitter_type"]').on('change', function () {
        const type = $(this).val();

        if (type === 'vet') {
            $('#vet-section').removeClass('verso-hidden');
            $('#owner-section').addClass('verso-hidden');
            // Make vet fields required
            $('#vet-section input[name], #vet-section select[name]').each(function () {
                const name = $(this).attr('name');
                if (name === 'vet_nom' || name === 'vet_clinique' || name === 'vet_email' || name === 'vet_telephone') {
                    $(this).prop('required', true);
                }
            });
            $('#owner-section input, #owner-section select').prop('required', false);
        } else if (type === 'owner') {
            $('#owner-section').removeClass('verso-hidden');
            $('#vet-section').addClass('verso-hidden');
            // Make owner name required
            $('#owner-section input[name="owner_nom"], #owner-section input[name="owner_prenom"]').prop('required', true);
            $('#owner-section input[name="owner_email"], #owner-section input[name="owner_telephone"]').prop('required', false);
            $('#vet-section input, #vet-section select').prop('required', false);
        }
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

        const maxSize = 50 * 1024 * 1024; // 50 MB
        let totalSize = 0;

        $.each(files, function (i, file) {
            totalSize += file.size;

            const size = formatFileSize(file.size);
            const fileHtml = `
                <div class="file-item">
                    <span>📄</span>
                    <span class="file-name">${escapeHtml(file.name)}</span>
                    <span class="file-size">${size}</span>
                </div>
            `;
            preview.append(fileHtml);
        });

        // Show total size warning if over limit
        if (totalSize > maxSize) {
            preview.append(
                '<div style="color: #d32f2f; font-size: 0.9rem; margin-top: 0.5rem;">⚠️ Taille totale dépasse 50 MB</div>'
            );
        }
    });

    // Form submission
    form.on('submit', function (e) {
        e.preventDefault();

        // Validate form
        form.addClass('was-validated');

        // Basic validation
        const submitterType = $('input[name="submitter_type"]:checked').val();
        const animalNom = $('#animal_nom').val().trim();
        const animalEspece = $('#animal_espece').val();
        const motif = $('#motif').val().trim();

        if (!submitterType) {
            showMessage('Veuillez sélectionner votre type', 'error');
            return;
        }

        if (!animalNom || !animalEspece) {
            showMessage('Veuillez remplir les données du patient', 'error');
            return;
        }

        if (!motif) {
            showMessage('Veuillez décrire le motif de la consultation', 'error');
            return;
        }

        if (submitterType === 'vet') {
            const vetNom = $('#vet_nom').val().trim();
            const vetClinic = $('#vet_clinique').val().trim();
            const vetEmail = $('#vet_email').val().trim();
            const vetPhone = $('#vet_telephone').val().trim();

            if (!vetNom || !vetClinic || !vetEmail || !vetPhone) {
                showMessage('Veuillez remplir toutes les infos de votre clinique', 'error');
                return;
            }
        }

        if (submitterType === 'owner') {
            const ownerNom = $('#owner_nom').val().trim();
            const ownerPrenom = $('#owner_prenom').val().trim();

            if (!ownerNom || !ownerPrenom) {
                showMessage('Veuillez entrer votre nom et prénom', 'error');
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
        const formData = new FormData(form[0]);

        // Add AJAX indicator
        const submitBtn = form.find('button[type="submit"]');
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
                form[0].reset();
                form.removeClass('was-validated');
                $('#file-preview').empty();
                $('#vet-section').addClass('verso-hidden');
                $('#owner-section').addClass('verso-hidden');

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
