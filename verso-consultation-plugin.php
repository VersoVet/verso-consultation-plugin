<?php
/**
 * Plugin Name: Verso Consultation Form
 * Description: Professional consultation form with email notifications and secure file uploads
 * Version: 3.1.0
 * Author: Verso Vet
 */

if (!defined('ABSPATH')) {
    exit;
}

// Upload directory configuration
define('VERSO_UPLOAD_SUBDIR', 'verso-consultations');
const VERSO_MAX_FILES = 5;
const VERSO_MAX_FILE_SIZE = 10485760; // 10 MB
const VERSO_MAX_TOTAL_SIZE = 31457280; // 30 MB

// Register AJAX handlers (for both logged in and non-logged in users)
add_action('wp_ajax_verso_submit_consultation', 'verso_handle_consultation_ajax');
add_action('wp_ajax_nopriv_verso_submit_consultation', 'verso_handle_consultation_ajax');

// Initialize upload directory with security (called on activation + lazy init)
function verso_init_upload_dir() {
    $upload_dir = wp_upload_dir();
    if (!isset($upload_dir['basedir']) || empty($upload_dir['basedir'])) {
        return; // Upload dir not available
    }

    $verso_dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . VERSO_UPLOAD_SUBDIR;

    if (!is_dir($verso_dir)) {
        // Create directory
        $created = wp_mkdir_p($verso_dir);
        if (!$created || !is_dir($verso_dir)) {
            error_log('verso-consultation: Failed to create directory ' . $verso_dir);
            return;
        }

        // Write .htaccess (suppress errors, continue if fails)
        $htaccess_content = "deny from all\nOptions -Indexes\n";
        $htaccess_result = @file_put_contents(
            $verso_dir . DIRECTORY_SEPARATOR . '.htaccess',
            $htaccess_content
        );
        if ($htaccess_result === false) {
            error_log('verso-consultation: Failed to write .htaccess');
        }

        // Write index.php stub (suppress errors, continue if fails)
        $index_content = "<?php\n// Silence is golden\n";
        $index_result = @file_put_contents(
            $verso_dir . DIRECTORY_SEPARATOR . 'index.php',
            $index_content
        );
        if ($index_result === false) {
            error_log('verso-consultation: Failed to write index.php');
        }
    }
}

// Sanitize uploaded filename to prevent traversal attacks
function verso_sanitize_filename($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];

    if (!in_array($ext, $allowed_exts, true)) {
        $ext = 'bin';
    }

    $base = pathinfo($filename, PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $base);
    $base = substr($base, 0, 50);

    return $base . '.' . $ext;
}

// Safely delete consultation upload directory (strict path confinement)
function verso_safe_delete_consultation_dir($uuid) {
    // Step 1: Validate UUID format (regex prevents ../../../etc/passwd injection)
    if (!preg_match('/^verso-\d{10}-[a-f0-9]{8}$/', $uuid)) {
        return; // Invalid UUID = absolute refusal
    }

    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . VERSO_UPLOAD_SUBDIR;
    $target_dir = $base_dir . DIRECTORY_SEPARATOR . $uuid;

    // Step 2: Resolve real paths (eliminates symlinks and ..)
    $real_base = realpath($base_dir);
    $real_target = realpath($target_dir);

    if ($real_base === false || $real_target === false) {
        return; // Non-existent path = no action
    }

    // Step 3: Strict confinement check (prevent traversal)
    if (strpos($real_target, $real_base . DIRECTORY_SEPARATOR) !== 0) {
        return; // Traversal attempt detected = absolute refusal
    }

    // Step 4: Delete files only (no recursive subdirs)
    $files = glob($real_target . DIRECTORY_SEPARATOR . '*');
    if ($files !== false) {
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    @rmdir($real_target);
}

// Process and validate file uploads
function verso_process_file_uploads($uuid) {
    $uploaded_files = array();

    if (!isset($_FILES['fichiers'])) {
        return $uploaded_files;
    }

    $upload_dir = wp_upload_dir();
    $verso_dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . VERSO_UPLOAD_SUBDIR . DIRECTORY_SEPARATOR . $uuid;

    // Validate upload count
    $file_count = count($_FILES['fichiers']['name']);
    if ($file_count > VERSO_MAX_FILES) {
        wp_send_json_error('Trop de fichiers (maximum ' . VERSO_MAX_FILES . ')');
    }

    // Allowed MIME types
    $allowed_mimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    $total_size = 0;

    for ($i = 0; $i < $file_count; ++$i) {
        $tmp_name = $_FILES['fichiers']['tmp_name'][$i];
        $name = $_FILES['fichiers']['name'][$i];
        $size = $_FILES['fichiers']['size'][$i];

        // Skip empty slots
        if (empty($tmp_name) || empty($name)) {
            continue;
        }

        // Validate individual file size
        if ($size > VERSO_MAX_FILE_SIZE) {
            wp_send_json_error('Fichier trop volumineux : ' . $name . ' (maximum 10 MB)');
        }

        // Validate total size
        $total_size += $size;
        if ($total_size > VERSO_MAX_TOTAL_SIZE) {
            wp_send_json_error('Taille totale excessive (maximum 30 MB)');
        }

        // Validate MIME type
        $mime_type = mime_content_type($tmp_name);
        if (!in_array($mime_type, $allowed_mimes, true)) {
            wp_send_json_error('Type de fichier non autorisé : ' . $name);
        }

        // Create UUID directory if needed
        if (!is_dir($verso_dir)) {
            wp_mkdir_p($verso_dir);
        }

        // Sanitize filename and add index for uniqueness
        $safe_name = verso_sanitize_filename($name);
        $safe_name = pathinfo($safe_name, PATHINFO_FILENAME) . '_' . $i . '.' . pathinfo($safe_name, PATHINFO_EXTENSION);

        // Move uploaded file
        $dest_path = $verso_dir . DIRECTORY_SEPARATOR . $safe_name;
        if (!move_uploaded_file($tmp_name, $dest_path)) {
            wp_send_json_error('Erreur lors de l\'enregistrement du fichier : ' . $name);
        }

        // Record file metadata
        $uploaded_files[] = [
            'original_name' => $name,
            'stored_name' => $safe_name,
            'mime_type' => $mime_type,
            'size' => $size,
        ];
    }

    return $uploaded_files;
}

function verso_handle_consultation_ajax() {
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

    // Process file uploads (validates and returns file metadata)
    $uploaded_files = verso_process_file_uploads($uuid);

    // Build email content
    $email_body = verso_build_email_body(
        $owner_nom, $owner_prenom, $owner_email, $owner_telephone, $owner_address,
        $vet_nom, $vet_prenom, $vet_clinique, $vet_email, $vet_telephone,
        $animal_nom, $animal_espece, $animal_race, $motif, $uuid, $uploaded_files
    );

    // Build JSON attachment data with file metadata (NEW v3.1.0)
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
        'files'           => $uploaded_files,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // Write JSON to temp file in uploads dir
    $upload_dir = wp_upload_dir();
    $json_path = $upload_dir['basedir'] . '/verso_' . $uuid . '.json';
    file_put_contents($json_path, $consultation_data);

    // Build email attachments array (consultation.json + uploaded files)
    $attachments = [$json_path];
    if (!empty($uploaded_files)) {
        $verso_files_dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . VERSO_UPLOAD_SUBDIR . DIRECTORY_SEPARATOR . $uuid;
        foreach ($uploaded_files as $file_info) {
            $file_path = $verso_files_dir . DIRECTORY_SEPARATOR . $file_info['stored_name'];
            if (is_file($file_path)) {
                $attachments[] = $file_path;
            }
        }
    }

    // Send email with all attachments
    $to = 'consultations@verso-vet.com';
    $subject = "[Verso Vet] Demande {$uuid} - {$animal_nom} ({$animal_espece})";
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: Verso Vet <consultations@verso-vet.com>',
        'Reply-To: ' . sanitize_email($owner_email),
    ];

    $result = wp_mail($to, $subject, $email_body, $headers, $attachments);

    // Delete temp JSON file
    @unlink($json_path);

    // Safely delete consultation upload directory (cleanup regardless of email result)
    verso_safe_delete_consultation_dir($uuid);

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
    $uuid, $owner_nom, $owner_prenom, $owner_email, $owner_telephone, $owner_address,
    $vet_nom, $vet_prenom, $vet_clinique, $vet_email, $vet_telephone,
    $animal_nom, $animal_espece, $animal_race, $motif
) {
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
    $owner_nom, $owner_prenom, $owner_email, $owner_telephone, $owner_address,
    $vet_nom, $vet_prenom, $vet_clinique, $vet_email, $vet_telephone,
    $animal_nom, $animal_espece, $animal_race, $motif, $uuid, $uploaded_files = []
) {
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

    // List uploaded files (NEW v3.1.0)
    if (!empty($uploaded_files)) {
        $body .= "─── DOCUMENTS JOINTS ──────────────────────\n";
        foreach ($uploaded_files as $file) {
            $size_kb = round($file['size'] / 1024, 1);
            $body .= "• " . $file['original_name'] . " ({$size_kb} KB)\n";
        }
        $body .= "\n";
    }

    $body .= "───────────────────────────────────────────\n";
    $body .= "Pièces jointes : consultation.json";
    if (!empty($uploaded_files)) {
        $body .= " + " . count($uploaded_files) . " fichier(s)";
    }
    $body .= "\n(données complètes pour traitement automatique)\n";

    return $body;
}

// Create tables and upload directory on plugin activation
register_activation_hook(__FILE__, 'verso_activate_plugin');

function verso_activate_plugin() {
    global $wpdb;

    // Initialize upload directory with security
    verso_init_upload_dir();

    // Create database table
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

    // Create or update consultation request page with form HTML
    verso_create_consultation_page();
}

function verso_create_consultation_page() {
    global $wpdb;

    // Check if page exists
    $page = $wpdb->get_row("SELECT ID FROM {$wpdb->posts} WHERE post_name='demande-de-consultation' AND post_type='page' LIMIT 1");

    $form_html = '<div id="verso-form" class="verso-consultation-form" style="max-width: 800px; margin: 20px 0;">
    <h2 style="margin-bottom: 20px; color: #1c2445;">Formulaire de Demande de Consultation</h2>

    <form id="verso-form" method="POST" enctype="multipart/form-data">
        <!-- SECTION 1: PROPRIÉTAIRE -->
        <div id="owner-section" style="margin-bottom: 35px;">
            <h3 style="background: #1c2445; color: white; padding: 12px 16px; border-radius: 4px; margin: 0 0 20px 0; font-size: 16px;">1. Informations Propriétaire *</h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Nom *</label>
                    <input type="text" name="owner_nom" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Prénom *</label>
                    <input type="text" name="owner_prenom" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Email *</label>
                <input type="email" name="owner_email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Téléphone *</label>
                    <input type="tel" name="owner_telephone" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Adresse *</label>
                    <input type="text" name="owner_adresse" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
            </div>
        </div>

        <!-- SECTION 2: VÉTÉRINAIRE -->
        <div id="vet-section" style="margin-bottom: 35px;">
            <h3 style="background: #1c2445; color: white; padding: 12px 16px; border-radius: 4px; margin: 0 0 20px 0; font-size: 16px;">2. Vétérinaire Suivi (Optionnel)</h3>
            <p style="font-size: 14px; color: #666; margin: 0 0 15px 0;">Renseignez si vous êtes suivi par un vétérinaire</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Nom Clinique</label>
                    <input type="text" name="vet_clinique" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Vétérinaire</label>
                    <input type="text" name="vet_nom" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
            </div>
        </div>

        <!-- SECTION 3: PATIENT/ANIMAL -->
        <div id="animal-section" style="margin-bottom: 35px;">
            <h3 style="background: #1c2445; color: white; padding: 12px 16px; border-radius: 4px; margin: 0 0 20px 0; font-size: 16px;">3. Patient Animal *</h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Nom *</label>
                    <input type="text" name="animal_nom" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Espèce *</label>
                    <select name="animal_espece" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                        <option value="">-- Sélectionner --</option>
                        <option value="Chien">Chien</option>
                        <option value="Chat">Chat</option>
                        <option value="Oiseau">Oiseau</option>
                        <option value="Lapin">Lapin</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Race</label>
                <input type="text" name="animal_race" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
        </div>

        <!-- SECTION 4: MOTIF -->
        <div id="motif-section" style="margin-bottom: 35px;">
            <h3 style="background: #1c2445; color: white; padding: 12px 16px; border-radius: 4px; margin: 0 0 20px 0; font-size: 16px;">4. Motif de la Consultation *</h3>

            <label style="display: block; margin-bottom: 8px; font-weight: bold;">Décrivez le motif *</label>
            <textarea name="motif" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; min-height: 120px; font-family: sans-serif;"></textarea>
        </div>

        <!-- SECTION 5: PIÈCES JOINTES -->
        <div style="margin-bottom: 35px;"><h3 style="background: #1c2445; color: white; padding: 12px 16px; border-radius: 4px; margin: 0 0 20px 0; font-size: 16px;">5. Pièces Jointes (Optionnel)</h3><p style="font-size: 14px; color: #666; margin: 0 0 15px 0;">Joignez des photos ou documents utiles (max 5 fichiers, 10 MB chacun)</p><label style="display: block; margin-bottom: 8px; font-weight: bold;">Fichiers</label><input type="file" id="fichiers" name="fichiers" multiple style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"><div id="file-preview" style="margin-top: 10px;"></div></div>

        <!-- SUBMIT -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <button type="submit" style="padding: 12px; background: #1c2445; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; transition: background 0.3s;">Envoyer la Demande</button>
            <button type="reset" style="padding: 12px; background: #ccc; color: #333; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">Réinitialiser</button>
        </div>
    </form>

    <div id="form-message" style="margin-top: 20px; padding: 12px; border-radius: 4px; display: none;"></div>
</div>';

    if ($page) {
        // Update existing page
        wp_update_post([
            'ID'           => $page->ID,
            'post_content' => $form_html,
            'post_status'  => 'publish',
        ]);
    } else {
        // Create new page
        wp_insert_post([
            'post_title'   => 'Demande de Consultation',
            'post_name'    => 'demande-de-consultation',
            'post_content' => $form_html,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}

// Enqueue form JavaScript and styles
add_action('wp_enqueue_scripts', 'verso_enqueue_scripts');

function verso_enqueue_scripts() {
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
        '3.1.0',
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
        '3.1.0'
    );
}
