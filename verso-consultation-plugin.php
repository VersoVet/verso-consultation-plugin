<?php
/**
 * Plugin Name: Verso Consultation Form
 * Description: Professional consultation form with AJAX email notifications
 * Version: 2.4.0
 * Author: Verso Vet
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register AJAX handlers (for both logged in and non-logged in users)
add_action('wp_ajax_verso_submit_consultation', 'verso_handle_consultation_ajax');
add_action('wp_ajax_nopriv_verso_submit_consultation', 'verso_handle_consultation_ajax');

function verso_handle_consultation_ajax(): void {
    // Get and sanitize POST data
    $owner_nom = isset($_POST['owner_nom']) ? sanitize_text_field($_POST['owner_nom']) : '';
    $owner_prenom = isset($_POST['owner_prenom']) ? sanitize_text_field($_POST['owner_prenom']) : '';
    $owner_email = isset($_POST['owner_email']) ? sanitize_email($_POST['owner_email']) : '';
    $owner_telephone = isset($_POST['owner_telephone']) ? sanitize_text_field($_POST['owner_telephone']) : '';
    $owner_address = isset($_POST['owner_address']) ? sanitize_textarea_field($_POST['owner_address']) : '';

    $vet_nom = isset($_POST['vet_nom']) ? sanitize_text_field($_POST['vet_nom']) : '';
    $vet_prenom = isset($_POST['vet_prenom']) ? sanitize_text_field($_POST['vet_prenom']) : '';
    $vet_clinique = isset($_POST['vet_clinique']) ? sanitize_text_field($_POST['vet_clinique']) : '';
    $vet_email = isset($_POST['vet_email']) ? sanitize_email($_POST['vet_email']) : '';
    $vet_telephone = isset($_POST['vet_telephone']) ? sanitize_text_field($_POST['vet_telephone']) : '';

    $animal_nom = isset($_POST['animal_nom']) ? sanitize_text_field($_POST['animal_nom']) : '';
    $animal_espece = isset($_POST['animal_espece']) ? sanitize_text_field($_POST['animal_espece']) : '';
    $animal_race = isset($_POST['animal_race']) ? sanitize_text_field($_POST['animal_race']) : '';
    $motif = isset($_POST['motif']) ? sanitize_textarea_field($_POST['motif']) : '';

    // Validate required fields
    if (empty($owner_nom) || empty($owner_prenom) || empty($owner_email) || empty($animal_nom) || empty($motif)) {
        wp_send_json_error('Veuillez remplir tous les champs obligatoires');
    }

    if (!is_email($owner_email)) {
        wp_send_json_error('Email propriétaire invalide');
    }

    // Generate UUID
    $uuid = 'verso-' . time() . '-' . substr(md5(uniqid()), 0, 8);

    // Build email content
    $email_body = verso_build_email_body(
        $owner_nom, $owner_prenom, $owner_email, $owner_telephone, $owner_address,
        $vet_nom, $vet_prenom, $vet_clinique, $vet_email, $vet_telephone,
        $animal_nom, $animal_espece, $animal_race, $motif, $uuid
    );

    // Send email
    $to = 'consultations@verso-vet.com';
    $subject = "[Verso Vet] Nouvelle demande - {$animal_nom} ({$animal_espece})";
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . sanitize_email($owner_email),
        'Reply-To: ' . sanitize_email($owner_email),
    ];

    $result = wp_mail($to, $subject, $email_body, $headers);

    if ($result) {
        wp_send_json_success([
            'message' => 'Demande envoyée avec succès',
            'uuid' => $uuid
        ]);
    } else {
        wp_send_json_error('Erreur lors de l\'envoi. Veuillez réessayer.');
    }
}

function verso_build_email_body(
    string $owner_nom, string $owner_prenom, string $owner_email, string $owner_telephone, string $owner_address,
    string $vet_nom, string $vet_prenom, string $vet_clinique, string $vet_email, string $vet_telephone,
    string $animal_nom, string $animal_espece, string $animal_race, string $motif, string $uuid
): string {
    $body = "Nouvelle demande de consultation reçue\n\n";

    $body .= "═══════════════════════════════════════════\n";
    $body .= "PROPRIÉTAIRE/CONTACT\n";
    $body .= "═══════════════════════════════════════════\n";
    $body .= "Nom: " . $owner_nom . "\n";
    $body .= "Prénom: " . $owner_prenom . "\n";
    $body .= "Email: " . $owner_email . "\n";
    $body .= "Téléphone: " . $owner_telephone . "\n";
    $body .= "Adresse: " . $owner_address . "\n\n";

    if (!empty($vet_nom)) {
        $body .= "═══════════════════════════════════════════\n";
        $body .= "VÉTÉRINAIRE RÉFÉRANT\n";
        $body .= "═══════════════════════════════════════════\n";
        $body .= "Nom: " . $vet_nom . "\n";
        $body .= "Prénom: " . $vet_prenom . "\n";
        if (!empty($vet_clinique)) {
            $body .= "Clinique: " . $vet_clinique . "\n";
        }
        if (!empty($vet_email)) {
            $body .= "Email: " . $vet_email . "\n";
        }
        if (!empty($vet_telephone)) {
            $body .= "Téléphone: " . $vet_telephone . "\n";
        }
        $body .= "\n";
    }

    $body .= "═══════════════════════════════════════════\n";
    $body .= "PATIENT ANIMAL\n";
    $body .= "═══════════════════════════════════════════\n";
    $body .= "Nom: " . $animal_nom . "\n";
    $body .= "Espèce: " . $animal_espece . "\n";
    if (!empty($animal_race)) {
        $body .= "Race: " . $animal_race . "\n";
    }
    $body .= "\n";

    $body .= "═══════════════════════════════════════════\n";
    $body .= "MOTIF DE CONSULTATION\n";
    $body .= "═══════════════════════════════════════════\n";
    $body .= $motif . "\n\n";

    $body .= "═══════════════════════════════════════════\n";
    $body .= "MÉTADONNÉES\n";
    $body .= "═══════════════════════════════════════════\n";
    $body .= "UUID: " . $uuid . "\n";
    $body .= "Date: " . current_time('Y-m-d H:i:s') . "\n";
    $body .= "Site: " . get_site_url() . "\n";

    return $body;
}
