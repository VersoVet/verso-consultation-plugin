<?php
/**
 * Plugin Name: Verso Consultation Form
 * Description: Professional consultation form with email notifications
 * Version: 3.0.0
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

    // Build JSON attachment data
    $consultation_data = json_encode([
        'uuid'            => $uuid,
        'submitted_at'    => current_time('c'),
        'owner_nom'       => $owner_nom,
        'owner_prenom'    => $owner_prenom,
        'owner_email'     => $owner_email,
        'owner_telephone' => $owner_telephone,
        'owner_address'   => $owner_address,
        'vet_nom'         => $vet_nom,
        'vet_prenom'      => $vet_prenom,
        'vet_clinique'    => $vet_clinique,
        'vet_email'       => $vet_email,
        'vet_telephone'   => $vet_telephone,
        'animal_nom'      => $animal_nom,
        'animal_espece'   => $animal_espece,
        'animal_race'     => $animal_race,
        'motif'           => $motif,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // Write JSON to temp file in uploads dir
    $upload_dir = wp_upload_dir();
    $json_path = $upload_dir['basedir'] . '/verso_' . $uuid . '.json';
    file_put_contents($json_path, $consultation_data);

    // Send email with attachment (5th wp_mail param)
    $to = 'consultations@verso-vet.com';
    $subject = "[Verso Vet] Demande {$uuid} - {$animal_nom} ({$animal_espece})";
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . sanitize_email($owner_email),
        'Reply-To: ' . sanitize_email($owner_email),
    ];

    $result = wp_mail($to, $subject, $email_body, $headers, [$json_path]);

    // Delete temp file
    @unlink($json_path);

    if ($result) {
        // Store data in database
        verso_store_consultation_in_db($uuid, $owner_nom, $owner_prenom, $owner_email, $owner_telephone, $owner_address,
            $vet_nom, $vet_prenom, $vet_clinique, $vet_email, $vet_telephone,
            $animal_nom, $animal_espece, $animal_race, $motif);

        wp_send_json_success([
            'message' => 'Demande envoyée avec succès',
            'uuid' => $uuid
        ]);
    } else {
        wp_send_json_error('Erreur lors de l\'envoi. Veuillez réessayer.');
    }
}

function verso_store_consultation_in_db(
    string $uuid, string $owner_nom, string $owner_prenom, string $owner_email, string $owner_telephone, string $owner_address,
    string $vet_nom, string $vet_prenom, string $vet_clinique, string $vet_email, string $vet_telephone,
    string $animal_nom, string $animal_espece, string $animal_race, string $motif
): void {
    global $wpdb;

    // Create table if it doesn't exist
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'verso_consultations';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        uuid varchar(50) NOT NULL UNIQUE,
        owner_nom varchar(100),
        owner_prenom varchar(100),
        owner_email varchar(100),
        owner_telephone varchar(20),
        owner_address longtext,
        animal_nom varchar(100),
        animal_espece varchar(100),
        animal_race varchar(100),
        motif longtext,
        vet_nom varchar(100),
        vet_prenom varchar(100),
        vet_clinique varchar(200),
        vet_email varchar(100),
        vet_telephone varchar(20),
        status varchar(50) DEFAULT 'new',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Insert data
    $wpdb->insert($table_name, [
        'uuid' => $uuid,
        'owner_nom' => $owner_nom,
        'owner_prenom' => $owner_prenom,
        'owner_email' => $owner_email,
        'owner_telephone' => $owner_telephone,
        'owner_address' => $owner_address,
        'animal_nom' => $animal_nom,
        'animal_espece' => $animal_espece,
        'animal_race' => $animal_race,
        'motif' => $motif,
        'vet_nom' => $vet_nom,
        'vet_prenom' => $vet_prenom,
        'vet_clinique' => $vet_clinique,
        'vet_email' => $vet_email,
        'vet_telephone' => $vet_telephone,
        'status' => 'new'
    ]);
}

function verso_build_email_body(
    string $owner_nom, string $owner_prenom, string $owner_email, string $owner_telephone, string $owner_address,
    string $vet_nom, string $vet_prenom, string $vet_clinique, string $vet_email, string $vet_telephone,
    string $animal_nom, string $animal_espece, string $animal_race, string $motif, string $uuid
): string {
    $body = "Nouvelle demande de consultation\n\n";
    $body .= "Référence : {$uuid}\n";
    $body .= "Date       : " . current_time('Y-m-d H:i:s') . "\n\n";

    $body .= "─── PROPRIÉTAIRE ──────────────────────────\n";
    $body .= "Nom    : {$owner_prenom} {$owner_nom}\n";
    $body .= "Email  : {$owner_email}\n";
    $body .= "Tél    : {$owner_telephone}\n";
    $body .= "Adresse: {$owner_address}\n\n";

    $body .= "─── ANIMAL ────────────────────────────────\n";
    $body .= "Nom    : {$animal_nom}\n";
    $body .= "Espèce : {$animal_espece}\n";
    if (!empty($animal_race)) {
        $body .= "Race   : {$animal_race}\n";
    }
    $body .= "\n";

    if (!empty($vet_nom)) {
        $body .= "─── VÉTÉRINAIRE RÉFÉRANT ──────────────────\n";
        $body .= "{$vet_prenom} {$vet_nom}";
        if (!empty($vet_clinique)) {
            $body .= " — {$vet_clinique}";
        }
        $body .= "\n";
        if (!empty($vet_email)) {
            $body .= "Email  : {$vet_email}\n";
        }
        if (!empty($vet_telephone)) {
            $body .= "Tél    : {$vet_telephone}\n";
        }
        $body .= "\n";
    }

    $body .= "─── MOTIF DE CONSULTATION ─────────────────\n";
    $body .= $motif . "\n\n";

    $body .= "───────────────────────────────────────────\n";
    $body .= "Pièce jointe : consultation.json\n";
    $body .= "(données complètes pour traitement automatique)\n";

    return $body;
}

// Create tables on plugin activation
register_activation_hook(__FILE__, 'verso_activate_plugin');

function verso_activate_plugin(): void {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'verso_consultations';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        uuid varchar(50) NOT NULL UNIQUE,
        owner_nom varchar(100),
        owner_prenom varchar(100),
        owner_email varchar(100),
        owner_telephone varchar(20),
        owner_address longtext,
        animal_nom varchar(100),
        animal_espece varchar(100),
        animal_race varchar(100),
        motif longtext,
        vet_nom varchar(100),
        vet_prenom varchar(100),
        vet_clinique varchar(200),
        vet_email varchar(100),
        vet_telephone varchar(20),
        status varchar(50) DEFAULT 'new',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Enqueue form JavaScript and styles
add_action('wp_enqueue_scripts', 'verso_enqueue_scripts');

function verso_enqueue_scripts(): void {
    // Only enqueue on the consultation page
    if (!is_page('demande-de-consultation') && !is_page('demande-consultation')) {
        return;
    }

    // Enqueue jQuery (WordPress includes it)
    wp_enqueue_script('jquery');

    // Enqueue our form handler
    wp_enqueue_script(
        'verso-form-handler',
        plugin_dir_url(__FILE__) . 'js/form.js',
        ['jquery'],
        '3.0.0',
        true
    );

    // Localize script with AJAX URL
    wp_localize_script('verso-form-handler', 'versoConsultation', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'action' => 'verso_submit_consultation'
    ]);

    // Enqueue styles
    wp_enqueue_style(
        'verso-form-style',
        plugin_dir_url(__FILE__) . 'css/style.css',
        [],
        '3.0.0'
    );
}
