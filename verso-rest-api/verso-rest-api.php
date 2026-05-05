<?php
/**
 * Plugin Name: Verso REST API
 * Description: Endpoint REST pour formulaire Verso - Envoie les demandes par email avec documents
 * Version: 2.1.0
 * Author: Verso Vet
 */

// Ajouter action pour capturer les uploads de fichiers
add_action('rest_api_init', function() {
    // Endpoint POST /wp-json/verso/v1/consultation
    register_rest_route('verso/v1', '/consultation', array(
        'methods' => 'POST',
        'callback' => 'verso_handle_consultation_submit',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Traite une soumission de demande de consultation.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function verso_handle_consultation_submit($request) {
    // Récupérer les paramètres
    $params = $request->get_json_params() ?? array();

    // Fallback pour multipart/form-data
    if (empty($params) || !isset($params['owner_nom'])) {
        $params = $_POST;
    }

    // Valider et nettoyer les données
    $owner_nom = sanitize_text_field($params['owner_nom'] ?? '');
    $owner_prenom = sanitize_text_field($params['owner_prenom'] ?? '');
    $owner_email = sanitize_email($params['owner_email'] ?? '');
    $owner_telephone = sanitize_text_field($params['owner_telephone'] ?? '');
    $vet_nom = sanitize_text_field($params['vet_nom'] ?? '');
    $vet_prenom = sanitize_text_field($params['vet_prenom'] ?? '');
    $vet_clinique = sanitize_text_field($params['vet_clinique'] ?? '');
    $vet_email = sanitize_email($params['vet_email'] ?? '');
    $vet_telephone = sanitize_text_field($params['vet_telephone'] ?? '');
    $animal_nom = sanitize_text_field($params['animal_nom'] ?? '');
    $animal_espece = sanitize_text_field($params['animal_espece'] ?? '');
    $animal_race = sanitize_text_field($params['animal_race'] ?? '');
    $motif = sanitize_textarea_field($params['motif'] ?? '');

    // Vérifier les champs obligatoires
    if (!$owner_nom || !$animal_nom || !$animal_espece || !$motif) {
        return new WP_REST_Response(
            array('message' => 'Champs obligatoires manquants'),
            400
        );
    }

    // Générer UUID unique
    $uuid = 'verso_' . wp_generate_uuid4();

    // Traiter les fichiers uploadés
    $documents = array();
    if (!empty($_FILES['documents'])) {
        $documents = verso_handle_file_uploads($uuid, $_FILES['documents']);
    }

    // Construire et envoyer l'email
    $subject = sprintf('[Verso Vet] Nouvelle demande - %s (%s)', $animal_nom, $animal_espece);
    $message = verso_build_email_message(
        $owner_nom, $owner_prenom, $owner_email, $owner_telephone,
        $vet_nom, $vet_prenom, $vet_clinique, $vet_email, $vet_telephone,
        $animal_nom, $animal_espece, $animal_race, $motif, $documents, $uuid
    );
    $headers = array('Content-Type: text/html; charset=UTF-8');

    $result = wp_mail('consultations@verso-vet.com', $subject, $message, $headers);

    if ($result) {
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => 'Demande envoyée avec succès! Vous recevrez une confirmation par email.',
                'uuid' => $uuid
            ),
            200
        );
    } else {
        return new WP_REST_Response(
            array('success' => false, 'message' => 'Erreur lors de l\'envoi'),
            500
        );
    }
}

/**
 * Traite et stocke les fichiers uploadés.
 *
 * @param string $uuid Identifiant unique
 * @param array $files Tableau $_FILES['documents']
 * @return array Infos fichiers
 */
function verso_handle_file_uploads($uuid, $files) {
    $documents = array();
    $upload_dir = wp_upload_dir();
    $verso_dir = $upload_dir['basedir'] . '/verso-consultations/' . $uuid;

    if (!file_exists($verso_dir)) {
        wp_mkdir_p($verso_dir);
    }

    $allowed_exts = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'tiff');

    // Handle both single file and multiple files
    $files_list = array();
    if (isset($files['name'])) {
        if (is_array($files['name'])) {
            // Multiple files
            $files_list = $files;
        } else {
            // Single file - convert to array format
            $files_list = array(
                'name' => array($files['name']),
                'type' => array($files['type'] ?? ''),
                'size' => array($files['size'] ?? 0),
                'tmp_name' => array($files['tmp_name'] ?? ''),
                'error' => array($files['error'] ?? UPLOAD_ERR_NO_FILE)
            );
        }

        // Process each file
        if (isset($files_list['name'])) {
            $count = is_array($files_list['name']) ? count($files_list['name']) : 1;
            for ($i = 0; $i < $count; $i++) {
                // Skip if error or missing
                if (!isset($files_list['error'][$i])) {
                    continue;
                }
                if ($files_list['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                if (empty($files_list['tmp_name'][$i])) {
                    continue;
                }

                $file_name = sanitize_file_name($files_list['name'][$i]);
                $file_tmp = $files_list['tmp_name'][$i];
                $file_size = $files_list['size'][$i];

                // Vérifier extension
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_exts, true)) {
                    continue;
                }

                // Vérifier taille (50MB max)
                if ($file_size > 50 * 1024 * 1024) {
                    continue;
                }

                // Copier le fichier
                $dest_file = $verso_dir . '/' . $file_name;
                if (@copy($file_tmp, $dest_file)) {
                    @chmod($dest_file, 0644);
                    $documents[] = array(
                        'name' => $file_name,
                        'size' => $file_size,
                        'url' => $upload_dir['baseurl'] . '/verso-consultations/' . $uuid . '/' . $file_name
                    );
                }
            }
        }
    }

    return $documents;
}

/**
 * Construit l'email HTML avec tous les détails.
 *
 * @return string HTML du message email
 */
function verso_build_email_message($owner_nom, $owner_prenom, $owner_email, $owner_telephone,
                                    $vet_nom, $vet_prenom, $vet_clinique, $vet_email, $vet_telephone,
                                    $animal_nom, $animal_espece, $animal_race, $motif, $documents, $uuid) {

    $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        h1 { color: #0066cc; border-bottom: 2px solid #0066cc; padding-bottom: 10px; }
        .section { margin: 20px 0; padding: 15px; border-left: 4px solid #0066cc; background: #f5f5f5; }
        .section-title { font-size: 16px; font-weight: bold; color: #0066cc; margin-bottom: 10px; }
        .field { margin: 8px 0; }
        .label { font-weight: bold; color: #333; }
        .doc-link { display: block; margin: 8px 0; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0066cc; font-weight: bold; }
        .doc-link:hover { background: #f0f0f0; }
        .footer { margin-top: 30px; font-size: 12px; color: #999; border-top: 1px solid #ddd; padding-top: 15px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Nouvelle Demande de Consultation</h1>

        <div class='section'>
            <div class='section-title'>👤 Propriétaire / Contact</div>
            <div class='field'><span class='label'>Nom:</span> " . esc_html($owner_nom . ' ' . $owner_prenom) . "</div>
            <div class='field'><span class='label'>Email:</span> <a href='mailto:" . esc_html($owner_email) . "'>" . esc_html($owner_email) . "</a></div>";

    if ($owner_telephone) {
        $html .= "<div class='field'><span class='label'>Téléphone:</span> " . esc_html($owner_telephone) . "</div>";
    }
    $html .= "</div>";

    if ($vet_nom || $vet_clinique) {
        $html .= "<div class='section'>
            <div class='section-title'>🏥 Vétérinaire Référant</div>";
        if ($vet_nom || $vet_prenom) {
            $html .= "<div class='field'><span class='label'>Nom:</span> " . esc_html($vet_nom . ' ' . $vet_prenom) . "</div>";
        }
        if ($vet_clinique) {
            $html .= "<div class='field'><span class='label'>Clinique:</span> " . esc_html($vet_clinique) . "</div>";
        }
        if ($vet_email) {
            $html .= "<div class='field'><span class='label'>Email:</span> <a href='mailto:" . esc_html($vet_email) . "'>" . esc_html($vet_email) . "</a></div>";
        }
        if ($vet_telephone) {
            $html .= "<div class='field'><span class='label'>Téléphone:</span> " . esc_html($vet_telephone) . "</div>";
        }
        $html .= "</div>";
    }

    $html .= "<div class='section'>
            <div class='section-title'>🐾 Patient Animal</div>
            <div class='field'><span class='label'>Nom:</span> " . esc_html($animal_nom) . "</div>
            <div class='field'><span class='label'>Espèce:</span> " . esc_html($animal_espece) . "</div>";

    if ($animal_race) {
        $html .= "<div class='field'><span class='label'>Race:</span> " . esc_html($animal_race) . "</div>";
    }
    $html .= "</div>";

    $html .= "<div class='section'>
            <div class='section-title'>📝 Motif de Consultation</div>
            <div style='background: white; padding: 10px; border-radius: 4px;'>" . nl2br(esc_html($motif)) . "</div>
        </div>";

    if (!empty($documents)) {
        $html .= "<div class='section'>
            <div class='section-title'>📎 Documents Attachés (" . count($documents) . ")</div>";
        foreach ($documents as $doc) {
            $size_formatted = verso_format_size($doc['size']);
            $html .= "<a href='" . esc_url($doc['url']) . "' class='doc-link'>📄 " . esc_html($doc['name']) . " (" . $size_formatted . ")</a>";
        }
        $html .= "</div>";
    }

    $html .= "<div class='footer'>
            <p><strong>ID de demande:</strong> " . esc_html($uuid) . "</p>
            <p>Date: " . current_time('d/m/Y H:i:s') . "</p>
            <p><em>Email automatique généré par Verso Vet - Ne pas répondre directement</em></p>
        </div>
    </div>
</body>
</html>";

    return $html;
}

/**
 * Formate une taille de fichier en format lisible.
 *
 * @param int $bytes Taille en bytes
 * @return string Taille formatée
 */
function verso_format_size($bytes) {
    if ($bytes === 0) {
        return '0 B';
    }
    $k = 1024;
    $sizes = array('B', 'KB', 'MB', 'GB');
    $i = intval(floor(log($bytes, $k)));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
