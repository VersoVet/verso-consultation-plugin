<?php
/**
 * Plugin Name: Verso Consultation Form
 * Description: Simple consultation form - sends email to consultations@verso-vet.com
 * Version: 2.0.0
 * Author: Verso Vet
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shortcode pour afficher le formulaire
add_shortcode('verso_consultation_form', 'verso_render_form');

function verso_render_form() {
    ob_start();
    ?>
    <div style="max-width: 800px; margin: 40px auto; padding: 20px;">
        <h1>🏥 Demande de Consultation</h1>
        <p>Troubles locomoteurs, imagerie, chirurgie. Nos équipes vous répondront sous 48 heures.</p>

        <form id="verso-form" method="POST" enctype="multipart/form-data" style="background: #f5f5f5; padding: 30px; border-radius: 8px;">
            <?php wp_nonce_field('verso_form', 'verso_nonce'); ?>
            <input type="hidden" name="action" value="verso_submit">

            <!-- PROPRIÉTAIRE - TOUJOURS VISIBLE ET REQUIS -->
            <h3>👤 Coordonnées du Propriétaire/Contact *</h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label><strong>Nom *</strong></label>
                    <input type="text" name="owner_nom" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label><strong>Prénom *</strong></label>
                    <input type="text" name="owner_prenom" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label><strong>Email *</strong></label>
                    <input type="email" name="owner_email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label><strong>Téléphone *</strong></label>
                    <input type="tel" name="owner_telephone" required placeholder="+33..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label><strong>Adresse *</strong></label>
                <textarea name="owner_address" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; min-height: 60px;" placeholder="Rue, code postal, ville"></textarea>
            </div>

            <!-- VÉTÉRINAIRE RÉFÉRANT - TOUJOURS VISIBLE, OPTIONNEL -->
            <h3>🏥 Vétérinaire Référant (Optionnel)</h3>
            <p style="font-size: 14px; color: #666;">Si la demande est guidée par un vétérinaire, remplissez ses coordonnées ci-dessous</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label>Nom</label>
                    <input type="text" name="vet_nom" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label>Prénom</label>
                    <input type="text" name="vet_prenom" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label>Clinique</label>
                    <input type="text" name="vet_clinique" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label>Téléphone</label>
                    <input type="tel" name="vet_telephone" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label>Email</label>
                    <input type="email" name="vet_email" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label>Adresse</label>
                    <input type="text" name="vet_address" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <!-- ANIMAL -->
            <h3>🐾 Patient Animal *</h3>

            <div style="margin-bottom: 20px;">
                <label><strong>Nom du Patient *</strong></label>
                <input type="text" name="animal_nom" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Rex, Minou, etc.">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label><strong>Espèce *</strong></label>
                    <select name="animal_espece" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">-- Sélectionnez --</option>
                        <option value="Chien">🐕 Chien</option>
                        <option value="Chat">🐈 Chat</option>
                        <option value="Lapin">🐰 Lapin</option>
                        <option value="NAC">🦗 NAC</option>
                        <option value="Cheval">🐴 Cheval</option>
                    </select>
                </div>
                <div>
                    <label>Race</label>
                    <input type="text" name="animal_race" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <!-- CONSULTATION -->
            <h3>📋 Consultation *</h3>

            <div style="margin-bottom: 20px;">
                <label><strong>Motif *</strong></label>
                <textarea name="motif" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; min-height: 120px;" placeholder="Décrivez le problème..."></textarea>
            </div>

            <!-- FICHIERS -->
            <h3>📎 Documents (Optionnel)</h3>
            <p style="font-size: 14px;">PDF, JPG, PNG, TIFF - Max 50 MB total</p>

            <div style="margin-bottom: 20px;">
                <input type="file" name="documents" multiple accept=".pdf,.jpg,.jpeg,.png,.tiff" style="width: 100%; padding: 10px;">
                <div id="verso-files-list" style="margin-top: 10px; font-size: 14px; color: #666;"></div>
            </div>

            <!-- SUBMIT -->
            <button type="submit" style="background: #2196F3; color: white; padding: 12px 30px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; width: 100%;">
                📤 Envoyer la Demande
            </button>

            <div id="verso-message" style="margin-top: 20px; padding: 15px; border-radius: 4px; display: none;"></div>
        </form>

        <div style="margin-top: 40px; padding: 20px; background: #e3f2fd; border-radius: 4px; text-align: center;">
            <p><strong>📧 Ou contactez-nous directement:</strong></p>
            <p><a href="mailto:consultations@verso-vet.com">consultations@verso-vet.com</a></p>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Preview de fichiers sélectionnés
        $('input[name="documents"]').on('change', function() {
            var fileList = '';
            if (this.files && this.files.length > 0) {
                fileList = '<strong>Fichiers sélectionnés:</strong><br>';
                for (var i = 0; i < this.files.length; i++) {
                    var size = (this.files[i].size / 1024 / 1024).toFixed(2);
                    fileList += '• ' + this.files[i].name + ' (' + size + ' MB)<br>';
                }
            }
            $('#verso-files-list').html(fileList);
        });

        $('#verso-form').on('submit', function(e) {
            e.preventDefault();
            var form = this;
            var msg = $('#verso-message');

            // Créer FormData pour supporter les fichiers
            var formData = new FormData(form);

            // Supprimer le nonce car REST API n'en a pas besoin
            formData.delete('verso_nonce');
            formData.delete('action');

            // Soumettre à l'endpoint REST API
            fetch('/wp-json/verso/v1/consultation', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    msg.html('✅ ' + data.message).css('background', '#c8e6c9').css('color', '#2e7d32').show();
                    form.reset();
                    $('#verso-files-list').html('');
                    setTimeout(() => msg.fadeOut(), 5000);
                } else {
                    msg.html('❌ ' + (data.message || 'Erreur')).css('background', '#ffcdd2').css('color', '#c62828').show();
                }
            })
            .catch(error => {
                msg.html('❌ Erreur de connexion: ' + error.message).css('background', '#ffcdd2').css('color', '#c62828').show();
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// Traiter la soumission du formulaire
add_action('wp_ajax_verso_submit', 'verso_handle_submit');
add_action('wp_ajax_nopriv_verso_submit', 'verso_handle_submit');

function verso_handle_submit() {
    // Vérifier le nonce
    if (!isset($_POST['verso_nonce']) || !wp_verify_nonce($_POST['verso_nonce'], 'verso_form')) {
        wp_send_json_error('Erreur de sécurité');
    }

    // Récupérer et valider les données
    $owner_nom = sanitize_text_field($_POST['owner_nom'] ?? '');
    $owner_prenom = sanitize_text_field($_POST['owner_prenom'] ?? '');
    $owner_email = sanitize_email($_POST['owner_email'] ?? '');
    $owner_telephone = sanitize_text_field($_POST['owner_telephone'] ?? '');
    $animal_nom = sanitize_text_field($_POST['animal_nom'] ?? '');
    $animal_espece = sanitize_text_field($_POST['animal_espece'] ?? '');
    $animal_race = sanitize_text_field($_POST['animal_race'] ?? '');
    $motif = sanitize_textarea_field($_POST['motif'] ?? '');

    // Valider les champs obligatoires
    if (!$owner_nom || !$owner_prenom || !$owner_email || !$owner_telephone || !$animal_nom || !$animal_espece || !$motif) {
        wp_send_json_error('Veuillez remplir tous les champs obligatoires');
    }

    // Préparer le contenu de l'email
    $email_subject = sprintf('[Verso Vet] Nouvelle demande - %s (%s)', $animal_nom, $animal_espece);

    $email_body = sprintf(
        "Nouvelle demande de consultation reçue\n\n" .
        "PROPRIÉTAIRE/CONTACT:\n" .
        "Nom: %s\n" .
        "Prénom: %s\n" .
        "Email: %s\n" .
        "Téléphone: %s\n\n" .
        "PATIENT:\n" .
        "Nom: %s\n" .
        "Espèce: %s\n" .
        "Race: %s\n\n" .
        "MOTIF DE CONSULTATION:\n" .
        "%s\n\n" .
        "VÉTÉRINAIRE RÉFÉRANT (si fourni):\n" .
        "Nom: %s\n" .
        "Clinique: %s\n" .
        "Email: %s\n" .
        "Téléphone: %s\n",
        $owner_nom,
        $owner_prenom,
        $owner_email,
        $owner_telephone,
        $animal_nom,
        $animal_espece,
        $animal_race ?: '(non spécifié)',
        $motif,
        sanitize_text_field($_POST['vet_nom'] ?? '(non fourni)'),
        sanitize_text_field($_POST['vet_clinique'] ?? '(non fourni)'),
        sanitize_email($_POST['vet_email'] ?? '(non fourni)'),
        sanitize_text_field($_POST['vet_telephone'] ?? '(non fourni)')
    );

    // Envoyer l'email
    $result = wp_mail('consultations@verso-vet.com', $email_subject, $email_body);

    if ($result) {
        wp_send_json_success('Demande envoyée avec succès! Vous recevrez une confirmation par email.');
    } else {
        wp_send_json_error('Erreur lors de l\'envoi de l\'email');
    }
}

// Créer la page au moment de l'activation
register_activation_hook(__FILE__, function() {
    $page = get_page_by_path('demande-consultation');
    if (!$page) {
        wp_insert_post([
            'post_title' => 'Demande de consultation',
            'post_content' => '[verso_consultation_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'demande-consultation'
        ]);
    }
});
?>
