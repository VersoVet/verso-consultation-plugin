<?php
/**
 * Gestion des uploads de fichiers pour les demandes de consultation
 */

class Verso_File_Handler {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Enregistre le custom post type pour les consultations
     */
    public function register_post_type() {
        register_post_type('verso_consultation', array(
            'label' => 'Consultations',
            'public' => false,
            'show_in_rest' => true,
            'rest_base' => 'verso-consultations',
            'supports' => array('title', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false
        ));

        register_rest_field('verso_consultation', 'consultation_data', array(
            'get_callback' => array($this, 'get_consultation_data'),
            'schema' => array(
                'type' => 'object',
                'description' => 'Données complètes de la consultation'
            )
        ));
    }

    /**
     * Récupère les données complètes d'une consultation
     */
    public function get_consultation_data($post) {
        $meta = get_post_meta($post['id']);

        return array(
            'id' => $post['id'],
            'uuid' => get_post_meta($post['id'], '_verso_uuid', true),
            'status' => get_post_meta($post['id'], '_verso_status', true),
            'created' => $post['date'],
            'owner' => array(
                'nom' => get_post_meta($post['id'], '_verso_owner_nom', true),
                'prenom' => get_post_meta($post['id'], '_verso_owner_prenom', true),
                'email' => get_post_meta($post['id'], '_verso_owner_email', true),
                'telephone' => get_post_meta($post['id'], '_verso_owner_telephone', true),
                'address' => get_post_meta($post['id'], '_verso_owner_address', true)
            ),
            'animal' => array(
                'nom' => get_post_meta($post['id'], '_verso_animal_nom', true),
                'espece' => get_post_meta($post['id'], '_verso_animal_espece', true),
                'race' => get_post_meta($post['id'], '_verso_animal_race', true)
            ),
            'consultation' => array(
                'motif' => get_post_meta($post['id'], '_verso_motif', true)
            ),
            'veterinaire' => array(
                'nom' => get_post_meta($post['id'], '_verso_vet_nom', true),
                'prenom' => get_post_meta($post['id'], '_verso_vet_prenom', true),
                'clinique' => get_post_meta($post['id'], '_verso_vet_clinique', true),
                'email' => get_post_meta($post['id'], '_verso_vet_email', true),
                'telephone' => get_post_meta($post['id'], '_verso_vet_telephone', true)
            ),
            'files' => get_post_meta($post['id'], '_verso_file_urls', true) ?: array()
        );
    }

    /**
     * Enregistre les routes REST pour les fichiers
     */
    public function register_routes() {
        // Upload de fichier
        register_rest_route('verso/v1', '/upload', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_upload'),
            'permission_callback' => '__return_true'
        ));

        // Suppression de fichier
        register_rest_route('verso/v1', '/delete/(?P<uuid>[^/]+)/(?P<filename>[^/]+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'handle_delete'),
            'permission_callback' => '__return_true'
        ));

        // Soumission de consultation
        register_rest_route('verso/v1', '/consultation', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_consultation'),
            'permission_callback' => '__return_true'
        ));

        // Lister les consultations (avec filtres)
        register_rest_route('verso/v1', '/consultations', array(
            'methods' => 'GET',
            'callback' => array($this, 'list_consultations'),
            'permission_callback' => array($this, 'check_api_key')
        ));

        // Obtenir une consultation spécifique
        register_rest_route('verso/v1', '/consultations/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_consultation'),
            'permission_callback' => array($this, 'check_api_key')
        ));

        // Obtenir les fichiers d'une consultation
        register_rest_route('verso/v1', '/consultations/(?P<id>\d+)/files', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_consultation_files'),
            'permission_callback' => array($this, 'check_api_key')
        ));

        // Mettre à jour le statut d'une consultation
        register_rest_route('verso/v1', '/consultations/(?P<id>\d+)/status', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_consultation_status'),
            'permission_callback' => array($this, 'check_api_key')
        ));
    }

    /**
     * Vérifie la clé API pour les endpoints du dashboard
     */
    public function check_api_key($request) {
        $api_key = $request->get_header('X-Verso-API-Key');
        $stored_key = get_option('verso_api_key');

        if (!$stored_key || !$api_key || $api_key !== $stored_key) {
            return false;
        }
        return true;
    }

    /**
     * Traite l'upload d'un fichier
     */
    public function handle_upload($request) {
        // Récupérer les paramètres
        $uuid = sanitize_text_field($request->get_param('uuid'));

        if (empty($uuid)) {
            return new WP_REST_Response(
                array('error' => 'UUID manquant'),
                400
            );
        }

        // Récupérer les fichiers
        $files = $request->get_file_params();

        if (empty($files) || empty($files['file'])) {
            return new WP_REST_Response(
                array('error' => 'Aucun fichier fourni'),
                400
            );
        }

        $file = $files['file'];

        // Vérifications de sécurité
        $allowed_types = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'tiff');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_types, true)) {
            return new WP_REST_Response(
                array('error' => 'Type de fichier non autorisé'),
                400
            );
        }

        // Vérifier la taille (50MB max)
        if ($file['size'] > 50 * 1024 * 1024) {
            return new WP_REST_Response(
                array('error' => 'Fichier trop volumineux (max 50MB)'),
                400
            );
        }

        // Créer le répertoire de stockage
        $upload_dir = wp_upload_dir();
        $verso_dir = $upload_dir['basedir'] . '/verso-consultations/' . $uuid;

        if (!file_exists($verso_dir)) {
            wp_mkdir_p($verso_dir);
        }

        // Sanitiser le nom du fichier
        $filename = sanitize_file_name($file['name']);
        $dest_file = $verso_dir . '/' . $filename;

        // Vérifier que le fichier n'existe pas déjà
        $counter = 1;
        $base_name = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        while (file_exists($dest_file)) {
            $filename = $base_name . '_' . $counter . '.' . $extension;
            $dest_file = $verso_dir . '/' . $filename;
            $counter++;
        }

        // Copier le fichier
        if (!@move_uploaded_file($file['tmp_name'], $dest_file)) {
            return new WP_REST_Response(
                array('error' => 'Erreur lors de l\'upload du fichier'),
                500
            );
        }

        @chmod($dest_file, 0644);

        // Retourner les informations du fichier
        return new WP_REST_Response(
            array(
                'success' => true,
                'filename' => $filename,
                'size' => filesize($dest_file),
                'url' => $upload_dir['baseurl'] . '/verso-consultations/' . $uuid . '/' . $filename,
                'uuid' => $uuid
            ),
            200
        );
    }

    /**
     * Traite la suppression d'un fichier
     */
    public function handle_delete($request) {
        $uuid = sanitize_text_field($request->get_param('uuid'));
        $filename = sanitize_text_field($request->get_param('filename'));

        if (empty($uuid) || empty($filename)) {
            return new WP_REST_Response(
                array('error' => 'Paramètres manquants'),
                400
            );
        }

        // Construire le chemin
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/verso-consultations/' . $uuid . '/' . $filename;

        // Vérifier que le fichier existe et est dans le bon répertoire
        if (!file_exists($file_path)) {
            return new WP_REST_Response(
                array('error' => 'Fichier non trouvé'),
                404
            );
        }

        // Vérifier que le chemin est bien dans verso-consultations
        $verso_base = $upload_dir['basedir'] . '/verso-consultations/';
        if (strpos(realpath($file_path), realpath($verso_base)) !== 0) {
            return new WP_REST_Response(
                array('error' => 'Accès refusé'),
                403
            );
        }

        // Supprimer le fichier
        if (!@unlink($file_path)) {
            return new WP_REST_Response(
                array('error' => 'Erreur lors de la suppression'),
                500
            );
        }

        // Nettoyer le répertoire s'il est vide
        $uuid_dir = $upload_dir['basedir'] . '/verso-consultations/' . $uuid;
        if (is_dir($uuid_dir)) {
            $files = @scandir($uuid_dir);
            if (empty($files) || count($files) <= 2) { // . et ..
                @rmdir($uuid_dir);
            }
        }

        return new WP_REST_Response(
            array('success' => true, 'message' => 'Fichier supprimé'),
            200
        );
    }

    /**
     * Traite la soumission d'une demande de consultation
     */
    public function handle_consultation($request) {
        // Récupérer les données JSON
        $params = $request->get_json_params();

        // Valider les champs obligatoires
        $owner_nom = sanitize_text_field($params['owner_nom'] ?? '');
        $owner_prenom = sanitize_text_field($params['owner_prenom'] ?? '');
        $owner_email = sanitize_email($params['owner_email'] ?? '');
        $owner_telephone = sanitize_text_field($params['owner_telephone'] ?? '');
        $owner_address = sanitize_textarea_field($params['owner_address'] ?? '');
        $animal_nom = sanitize_text_field($params['animal_nom'] ?? '');
        $animal_espece = sanitize_text_field($params['animal_espece'] ?? '');
        $animal_race = sanitize_text_field($params['animal_race'] ?? '');
        $motif = sanitize_textarea_field($params['motif'] ?? '');

        if (!$owner_nom || !$owner_prenom || !$owner_email || !$owner_telephone || !$animal_nom || !$animal_espece || !$motif) {
            return new WP_REST_Response(
                array('error' => 'Veuillez remplir tous les champs obligatoires'),
                400
            );
        }

        // Récupérer les vétérinaires optionnels
        $vet_nom = sanitize_text_field($params['vet_nom'] ?? '');
        $vet_prenom = sanitize_text_field($params['vet_prenom'] ?? '');
        $vet_clinique = sanitize_text_field($params['vet_clinique'] ?? '');
        $vet_telephone = sanitize_text_field($params['vet_telephone'] ?? '');
        $vet_email = sanitize_email($params['vet_email'] ?? '');

        // Récupérer les URLs de fichiers
        $file_urls = isset($params['file_urls']) && is_array($params['file_urls']) ? $params['file_urls'] : array();
        $uuid = sanitize_text_field($params['uuid'] ?? '');

        // Créer le post de consultation
        $post_title = sprintf('%s %s - %s (%s)', $owner_nom, $owner_prenom, $animal_nom, $animal_espece);
        $post_id = wp_insert_post(array(
            'post_title' => $post_title,
            'post_type' => 'verso_consultation',
            'post_status' => 'publish'
        ));

        if (!$post_id) {
            return new WP_REST_Response(
                array('error' => 'Erreur lors de la création de la consultation'),
                500
            );
        }

        // Stocker les métadonnées
        update_post_meta($post_id, '_verso_uuid', $uuid);
        update_post_meta($post_id, '_verso_status', 'new');
        update_post_meta($post_id, '_verso_owner_nom', $owner_nom);
        update_post_meta($post_id, '_verso_owner_prenom', $owner_prenom);
        update_post_meta($post_id, '_verso_owner_email', $owner_email);
        update_post_meta($post_id, '_verso_owner_telephone', $owner_telephone);
        update_post_meta($post_id, '_verso_owner_address', $owner_address);
        update_post_meta($post_id, '_verso_animal_nom', $animal_nom);
        update_post_meta($post_id, '_verso_animal_espece', $animal_espece);
        update_post_meta($post_id, '_verso_animal_race', $animal_race);
        update_post_meta($post_id, '_verso_motif', $motif);
        update_post_meta($post_id, '_verso_vet_nom', $vet_nom);
        update_post_meta($post_id, '_verso_vet_prenom', $vet_prenom);
        update_post_meta($post_id, '_verso_vet_clinique', $vet_clinique);
        update_post_meta($post_id, '_verso_vet_email', $vet_email);
        update_post_meta($post_id, '_verso_vet_telephone', $vet_telephone);
        update_post_meta($post_id, '_verso_file_urls', $file_urls);

        // Construire le contenu de l'email
        $email_subject = sprintf('[Verso Vet] Nouvelle demande - %s (%s)', $animal_nom, $animal_espece);

        $email_body = "Nouvelle demande de consultation reçue\n\n";
        $email_body .= "═══════════════════════════════════════════\n";
        $email_body .= "PROPRIÉTAIRE/CONTACT\n";
        $email_body .= "═══════════════════════════════════════════\n";
        $email_body .= sprintf("Nom: %s\n", $owner_nom);
        $email_body .= sprintf("Prénom: %s\n", $owner_prenom);
        $email_body .= sprintf("Email: %s\n", $owner_email);
        $email_body .= sprintf("Téléphone: %s\n", $owner_telephone);
        $email_body .= sprintf("Adresse: %s\n\n", $owner_address);

        $email_body .= "═══════════════════════════════════════════\n";
        $email_body .= "PATIENT ANIMAL\n";
        $email_body .= "═══════════════════════════════════════════\n";
        $email_body .= sprintf("Nom: %s\n", $animal_nom);
        $email_body .= sprintf("Espèce: %s\n", $animal_espece);
        $email_body .= sprintf("Race: %s\n\n", $animal_race ?: '(non spécifié)');

        $email_body .= "═══════════════════════════════════════════\n";
        $email_body .= "MOTIF DE CONSULTATION\n";
        $email_body .= "═══════════════════════════════════════════\n";
        $email_body .= sprintf("%s\n\n", $motif);

        if ($vet_nom || $vet_clinique || $vet_email) {
            $email_body .= "═══════════════════════════════════════════\n";
            $email_body .= "VÉTÉRINAIRE RÉFÉRANT\n";
            $email_body .= "═══════════════════════════════════════════\n";
            $email_body .= sprintf("Nom: %s\n", $vet_nom ?: '(non fourni)');
            $email_body .= sprintf("Clinique: %s\n", $vet_clinique ?: '(non fourni)');
            $email_body .= sprintf("Email: %s\n", $vet_email ?: '(non fourni)');
            $email_body .= sprintf("Téléphone: %s\n\n", $vet_telephone ?: '(non fourni)');
        }

        if (!empty($file_urls)) {
            $email_body .= "═══════════════════════════════════════════\n";
            $email_body .= "DOCUMENTS JOINTS (" . count($file_urls) . " fichier(s))\n";
            $email_body .= "═══════════════════════════════════════════\n";
            foreach ($file_urls as $idx => $url) {
                $email_body .= sprintf("%d. %s\n", $idx + 1, $url);
            }
            $email_body .= "\n";
        }

        $email_body .= "═══════════════════════════════════════════\n";
        $email_body .= sprintf("ID Consultation: %d\n", $post_id);
        $email_body .= sprintf("UUID: %s\n", $uuid);
        $email_body .= "Demande reçue le: " . current_time('Y-m-d H:i:s') . "\n";
        $email_body .= "═══════════════════════════════════════════\n";

        // Envoyer l'email
        wp_mail('consultations@verso-vet.com', $email_subject, $email_body);

        $message = sprintf(
            'Demande envoyée avec succès! %s fichier(s) reçu(s). Vous recevrez une confirmation par email.',
            count($file_urls)
        );
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => $message,
                'id' => $post_id,
                'uuid' => $uuid
            ),
            201
        );
    }

    /**
     * Liste les consultations avec filtres optionnels
     */
    public function list_consultations($request) {
        $per_page = intval($request->get_param('per_page')) ?? 20;
        $paged = intval($request->get_param('paged')) ?? 1;
        $status = sanitize_text_field($request->get_param('status'));
        $search = sanitize_text_field($request->get_param('search'));

        $args = array(
            'post_type' => 'verso_consultation',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        if ($status) {
            $args['meta_query'] = array(
                array(
                    'key' => '_verso_status',
                    'value' => $status
                )
            );
        }

        if ($search) {
            $args['s'] = $search;
        }

        $query = new WP_Query($args);
        $consultations = array();

        foreach ($query->posts as $post) {
            $consultations[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'status' => get_post_meta($post->ID, '_verso_status', true),
                'created' => $post->post_date_gmt,
                'owner' => array(
                    'nom' => get_post_meta($post->ID, '_verso_owner_nom', true),
                    'email' => get_post_meta($post->ID, '_verso_owner_email', true)
                ),
                'animal' => array(
                    'nom' => get_post_meta($post->ID, '_verso_animal_nom', true),
                    'espece' => get_post_meta($post->ID, '_verso_animal_espece', true)
                ),
                'file_count' => count(get_post_meta($post->ID, '_verso_file_urls', true) ?: array()),
                'url' => get_rest_url(null, 'verso/v1/consultations/' . $post->ID)
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $consultations,
                'pagination' => array(
                    'total' => $query->found_posts,
                    'pages' => $query->max_num_pages,
                    'current_page' => $paged,
                    'per_page' => $per_page
                )
            ),
            200
        );
    }

    /**
     * Obtient une consultation spécifique
     */
    public function get_consultation($request) {
        $id = intval($request->get_param('id'));

        $post = get_post($id);
        if (!$post || $post->post_type !== 'verso_consultation') {
            return new WP_REST_Response(
                array('error' => 'Consultation non trouvée'),
                404
            );
        }

        $consultation_data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => get_post_meta($post->ID, '_verso_status', true),
            'uuid' => get_post_meta($post->ID, '_verso_uuid', true),
            'created' => $post->post_date_gmt,
            'owner' => array(
                'nom' => get_post_meta($post->ID, '_verso_owner_nom', true),
                'prenom' => get_post_meta($post->ID, '_verso_owner_prenom', true),
                'email' => get_post_meta($post->ID, '_verso_owner_email', true),
                'telephone' => get_post_meta($post->ID, '_verso_owner_telephone', true),
                'address' => get_post_meta($post->ID, '_verso_owner_address', true)
            ),
            'animal' => array(
                'nom' => get_post_meta($post->ID, '_verso_animal_nom', true),
                'espece' => get_post_meta($post->ID, '_verso_animal_espece', true),
                'race' => get_post_meta($post->ID, '_verso_animal_race', true)
            ),
            'consultation' => array(
                'motif' => get_post_meta($post->ID, '_verso_motif', true)
            ),
            'veterinaire' => array(
                'nom' => get_post_meta($post->ID, '_verso_vet_nom', true),
                'prenom' => get_post_meta($post->ID, '_verso_vet_prenom', true),
                'clinique' => get_post_meta($post->ID, '_verso_vet_clinique', true),
                'email' => get_post_meta($post->ID, '_verso_vet_email', true),
                'telephone' => get_post_meta($post->ID, '_verso_vet_telephone', true)
            ),
            'files' => get_post_meta($post->ID, '_verso_file_urls', true) ?: array()
        );

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $consultation_data
            ),
            200
        );
    }

    /**
     * Obtient les fichiers d'une consultation
     */
    public function get_consultation_files($request) {
        $id = intval($request->get_param('id'));

        $post = get_post($id);
        if (!$post || $post->post_type !== 'verso_consultation') {
            return new WP_REST_Response(
                array('error' => 'Consultation non trouvée'),
                404
            );
        }

        $file_urls = get_post_meta($post->ID, '_verso_file_urls', true) ?: array();
        $uuid = get_post_meta($post->ID, '_verso_uuid', true);

        $files = array();
        foreach ($file_urls as $url) {
            $filename = basename($url);
            $files[] = array(
                'name' => $filename,
                'url' => $url,
                'delete_url' => get_rest_url(null, 'verso/v1/delete/' . $uuid . '/' . $filename)
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $files,
                'count' => count($files)
            ),
            200
        );
    }

    /**
     * Met à jour le statut d'une consultation
     */
    public function update_consultation_status($request) {
        $id = intval($request->get_param('id'));
        $params = $request->get_json_params();
        $status = sanitize_text_field($params['status'] ?? '');

        if (!$status) {
            return new WP_REST_Response(
                array('error' => 'Statut requis'),
                400
            );
        }

        $valid_statuses = array('new', 'reviewed', 'processed', 'archived');
        if (!in_array($status, $valid_statuses, true)) {
            return new WP_REST_Response(
                array('error' => 'Statut invalide. Utilisez: ' . implode(', ', $valid_statuses)),
                400
            );
        }

        $post = get_post($id);
        if (!$post || $post->post_type !== 'verso_consultation') {
            return new WP_REST_Response(
                array('error' => 'Consultation non trouvée'),
                404
            );
        }

        update_post_meta($post->ID, '_verso_status', $status);

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => 'Statut mis à jour',
                'id' => $post->ID,
                'status' => $status
            ),
            200
        );
    }
}

// Initialiser le gestionnaire de fichiers
new Verso_File_Handler();
?>
