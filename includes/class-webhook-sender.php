<?php
/**
 * Webhook Sender - Sends consultation data to Skill API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Verso_Webhook_Sender {
    const ENDPOINT = 'consultation';
    const NAMESPACE = 'verso/v1';

    /**
     * Register REST endpoint
     */
    public static function register_endpoint() {
        register_rest_route(
            self::NAMESPACE,
            self::ENDPOINT,
            [
                'methods'             => 'POST',
                'callback'            => [__CLASS__, 'handle_submission'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    /**
     * Handle consultation submission
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response
     */
    public static function handle_submission(WP_REST_Request $request) {
        // Verify nonce
        $nonce = $request->get_param('nonce');
        if (!wp_verify_nonce($nonce, 'verso_consultation')) {
            error_log('Verso: Invalid nonce - ' . $nonce);
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Nonce invalide'],
                403
            );
        }

        // Validate required fields
        $submitter_type = sanitize_text_field($request->get_param('submitter_type'));
        if (!in_array($submitter_type, ['vet', 'owner'])) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Type de soumettant invalide'],
                400
            );
        }

        // Get and validate animal data
        $animal_nom = sanitize_text_field($request->get_param('animal_nom'));
        $animal_espece = sanitize_text_field($request->get_param('animal_espece'));

        if (empty($animal_nom) || empty($animal_espece)) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Données animal incomplètes'],
                400
            );
        }

        // Get and validate consultation data
        $motif = sanitize_textarea_field($request->get_param('motif'));
        $specialite = sanitize_text_field($request->get_param('specialite'));

        if (empty($motif)) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Motif manquant'],
                400
            );
        }

        // Default specialite if not provided
        if (empty($specialite)) {
            $specialite = 'Troubles Locomoteurs';
        }

        // Get UUID from form
        $uuid = sanitize_text_field($request->get_param('uuid'));
        if (!$uuid) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'UUID manquant'],
                400
            );
        }

        try {
            // Build consultation request
            $consultation_data = self::build_consultation_request(
                $uuid,
                $submitter_type,
                $request
            );

            // Handle file uploads
            $file_urls = self::handle_file_uploads($uuid);

            if (!empty($file_urls)) {
                $consultation_data['fichiers'] = $file_urls;
            }

            // Store consultation locally (no webhook - Soma will pull via API)
            $stored = self::store_consultation_locally($consultation_data);

            if (!$stored) {
                return new WP_REST_Response(
                    ['success' => false, 'message' => 'Erreur lors du stockage de la demande'],
                    500
                );
            }

            // Send confirmation email to contact
            self::send_confirmation_email($consultation_data);

            return new WP_REST_Response(
                [
                    'success' => true,
                    'message' => 'Demande envoyée avec succès! Vous recevrez une confirmation par email.',
                    'uuid' => $uuid,
                ],
                200
            );
        } catch (Exception $e) {
            error_log('Verso Consultation Error: ' . $e->getMessage());
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Build consultation request from form data
     *
     * @param string $uuid UUID
     * @param string $submitter_type Type (vet or owner)
     * @param WP_REST_Request $request Request
     * @return array Consultation request data
     */
    private static function build_consultation_request($uuid, $submitter_type, WP_REST_Request $request) {
        // Build VET info if provided (now optional even for owner submissions)
        $vet = null;
        $vet_nom = sanitize_text_field($request->get_param('vet_nom'));
        if (!empty($vet_nom)) {
            $vet = [
                'nom' => $vet_nom,
                'prenom' => sanitize_text_field($request->get_param('vet_prenom')),
                'clinique' => sanitize_text_field($request->get_param('vet_clinique')),
                'email' => sanitize_email($request->get_param('vet_email')),
                'telephone' => sanitize_text_field($request->get_param('vet_telephone')),
                'adresse' => sanitize_text_field($request->get_param('vet_adresse')),
            ];
        }

        // Build OWNER info
        $owner = [
            'nom' => sanitize_text_field($request->get_param('owner_nom')),
            'prenom' => sanitize_text_field($request->get_param('owner_prenom')),
            'email' => sanitize_email($request->get_param('owner_email')),
            'telephone' => sanitize_text_field($request->get_param('owner_telephone')),
        ];

        // Build ANIMAL info
        $animal = [
            'nom' => sanitize_text_field($request->get_param('animal_nom')),
            'espece' => sanitize_text_field($request->get_param('animal_espece')),
            'race' => sanitize_text_field($request->get_param('animal_race')),
            'sexe' => sanitize_text_field($request->get_param('animal_sexe')),
            'date_naissance' => sanitize_text_field($request->get_param('animal_date_naissance')),
            'puce' => sanitize_text_field($request->get_param('animal_puce')),
            'poids' => floatval($request->get_param('animal_poids')) ?: null,
        ];

        return [
            'uuid' => $uuid,
            'submitter_type' => $submitter_type,
            'vet' => $vet,
            'owner' => array_filter($owner) ?: null,
            'animal' => array_filter($animal),
            'motif' => sanitize_textarea_field($request->get_param('motif')),
            'specialite' => sanitize_text_field($request->get_param('specialite')),
            'urgence' => (bool) $request->get_param('urgence'),
            'traitements_en_cours' => sanitize_textarea_field($request->get_param('traitements')),
            'fichiers' => [],
        ];
    }

    /**
     * Handle file uploads to /wp-content/uploads/consultations/{uuid}/
     *
     * @param string $uuid Consultation UUID
     * @return array File URLs
     */
    private static function handle_file_uploads($uuid) {
        $files = isset($_FILES['fichiers']) ? $_FILES['fichiers'] : [];
        if (empty($files['name'][0])) {
            return [];
        }

        // Create UUID directory
        $upload_dir = wp_upload_dir();
        $consult_dir = $upload_dir['basedir'] . '/consultations/' . $uuid;

        if (!wp_mkdir_p($consult_dir)) {
            throw new Exception('Cannot create upload directory');
        }

        $file_urls = [];
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'tiff', 'dcm'];
        $max_size = 50 * 1024 * 1024; // 50 MB

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Validate file type
            $filename = sanitize_file_name($files['name'][$i]);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_types)) {
                continue;
            }

            // Validate file size
            if ($files['size'][$i] > $max_size) {
                continue;
            }

            // Move uploaded file
            $dest = $consult_dir . '/' . $filename;
            if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                // Generate web-accessible URL
                $file_url = $upload_dir['baseurl'] . '/consultations/' . $uuid . '/' . $filename;
                $file_urls[] = $file_url;
            }
        }

        return $file_urls;
    }

    /**
     * Store consultation locally (no webhook - Soma will pull via API)
     *
     * @param array $data Consultation data
     * @return bool Success
     */
    private static function store_consultation_locally($data) {
        global $wpdb;

        // Create consultations table if it doesn't exist
        $table_name = $wpdb->prefix . 'verso_consultations';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                uuid VARCHAR(255) NOT NULL UNIQUE,
                submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                data_json LONGTEXT NOT NULL,
                processed BOOLEAN DEFAULT FALSE,
                processed_at DATETIME NULL,
                KEY idx_processed (processed)
            ) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        // Insert consultation into local database
        $result = $wpdb->insert(
            $table_name,
            [
                'uuid'      => $data['uuid'],
                'data_json' => wp_json_encode($data),
            ]
        );

        if (!$result) {
            error_log('Verso: Failed to store consultation locally: ' . $wpdb->last_error);
            return false;
        }

        error_log('Verso: Consultation ' . $data['uuid'] . ' stored locally');
        return true;
    }

    /**
     * Send webhook notification email to skill monitoring service
     *
     * @param array $data Consultation data
     */
    private static function send_confirmation_email($data) {
        $animal_name = $data['animal']['nom'] ?? 'Patient';
        $submitter = 'Propriétaire';

        if ($data['submitter_type'] === 'vet' && !empty($data['vet']['clinique'])) {
            $submitter = 'Vétérinaire - ' . $data['vet']['clinique'];
        }

        // Email pour l'équipe Verso Vet (human readable)
        $admin_subject = sprintf(
            '[Verso Vet] Nouvelle demande - %s (%s)',
            $animal_name,
            $data['specialite']
        );

        $admin_body = sprintf(
            "Nouvelle demande de consultation reçue\n\n" .
            "UUID: %s\n" .
            "Type: %s\n" .
            "Patient: %s (%s)\n" .
            "Spécialité: %s\n" .
            "Motif: %s\n\n" .
            "Accédez au dashboard: %s/dashboard",
            $data['uuid'],
            $submitter,
            $animal_name,
            $data['animal']['espece'],
            $data['specialite'],
            $data['motif'],
            get_option('verso_consultation_skill_url', 'http://10.0.0.44:8092')
        );

        $admin_result = wp_mail('consultations@verso-vet.com', $admin_subject, $admin_body);
        error_log('Verso: Admin email to consultations@verso-vet.com - ' . ($admin_result ? 'SUCCESS' : 'FAILED'));

        // Email pour le système de monitoring du skill (parseable)
        // Sujet contient l'UUID pour extraction facile
        $webhook_subject = sprintf(
            'VERSO_WEBHOOK UUID:%s TYPE:%s ANIMAL:%s',
            $data['uuid'],
            $data['submitter_type'],
            sanitize_title($animal_name)
        );

        $webhook_body = sprintf(
            "UUID: %s\n" .
            "Type: %s\n" .
            "Animal: %s (%s)\n" .
            "Motif: %s\n" .
            "Website: verso-vet.com",
            $data['uuid'],
            $data['submitter_type'],
            $animal_name,
            $data['animal']['espece'] ?? 'Unknown',
            $data['motif']
        );

        // Get webhook email address from options
        $webhook_email = get_option('verso_consultation_webhook_email');

        // Ensure email is set to consultations@verso-vet.com
        if (empty($webhook_email)) {
            $webhook_email = 'consultations@verso-vet.com';
            update_option('verso_consultation_webhook_email', $webhook_email);
            error_log('Verso: Setting webhook email to ' . $webhook_email);
        }

        $webhook_result = wp_mail($webhook_email, $webhook_subject, $webhook_body);
        error_log('Verso: Webhook email to ' . $webhook_email . ' - ' . ($webhook_result ? 'SUCCESS' : 'FAILED'));
    }
}
