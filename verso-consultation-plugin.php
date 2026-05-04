<?php
/**
 * Plugin Name: Verso Consultation Form
 * Description: Consultation request form with file uploads and ERP integration
 * Version: 1.0.0
 * Author: Verso Vet
 * Text Domain: verso-consultation
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VERSO_CONSULTATION_DIR', plugin_dir_path(__FILE__));
define('VERSO_CONSULTATION_URL', plugin_dir_url(__FILE__));
define('VERSO_CONSULTATION_VERSION', '1.0.0');

// Include classes
require_once VERSO_CONSULTATION_DIR . 'includes/class-vault-client.php';
require_once VERSO_CONSULTATION_DIR . 'includes/class-form-handler.php';
require_once VERSO_CONSULTATION_DIR . 'includes/class-webhook-sender.php';

/**
 * Activation hook
 */
function verso_consultation_activate() {
    // Create uploads directory
    $upload_dir = wp_upload_dir();
    $consult_dir = $upload_dir['basedir'] . '/consultations';
    if (!is_dir($consult_dir)) {
        wp_mkdir_p($consult_dir);
    }

    // Set default Vault configuration if not set
    if (!get_option('verso_consultation_vault_url')) {
        update_option('verso_consultation_vault_url', 'http://10.0.0.44:8050');
    }
    if (!get_option('verso_consultation_vault_token')) {
        // Try to get from environment variable or use placeholder
        $token = getenv('ONYX_VAULT_TOKEN') ?: '';
        update_option('verso_consultation_vault_token', $token);
    }
    if (!get_option('verso_consultation_skill_url')) {
        update_option('verso_consultation_skill_url', 'http://10.0.0.44:8092');
    }

    // Create consultation page if it doesn't exist
    $page_title = 'Demande de consultation';
    $page_content = '[verso_consultation_form]';
    $page_slug = 'demande-consultation';

    $existing_page = get_page_by_path($page_slug);
    if (!$existing_page) {
        wp_insert_post([
            'post_title'   => $page_title,
            'post_content' => $page_content,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => $page_slug,
        ]);
    }

    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'verso_consultation_activate');

/**
 * Deactivation hook
 */
function verso_consultation_deactivate() {
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'verso_consultation_deactivate');

/**
 * Initialize plugin
 */
function verso_consultation_init() {
    // Register shortcode
    add_shortcode('verso_consultation_form', ['Verso_Form_Handler', 'render_form']);
}

add_action('init', 'verso_consultation_init');

/**
 * Register REST endpoints
 */
function verso_consultation_register_rest_routes() {
    Verso_Webhook_Sender::register_endpoint();
}

add_action('rest_api_init', 'verso_consultation_register_rest_routes');

/**
 * Enqueue scripts and styles
 */
function verso_consultation_enqueue_assets() {
    if (is_page_template('page-demande-consultation.php') ||
        (is_page() && strpos(get_the_content(), '[verso_consultation_form]') !== false)) {

        // Bootstrap CSS
        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );

        // Custom styles
        wp_enqueue_style(
            'verso-consultation-css',
            VERSO_CONSULTATION_URL . 'css/style.css',
            ['bootstrap-css'],
            VERSO_CONSULTATION_VERSION
        );

        // Bootstrap JS
        wp_enqueue_script(
            'bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            [],
            '5.3.0',
            true
        );

        // Custom scripts
        wp_enqueue_script(
            'verso-consultation-js',
            VERSO_CONSULTATION_URL . 'js/form.js',
            ['jquery', 'bootstrap-js'],
            VERSO_CONSULTATION_VERSION,
            true
        );

        // Localize script with AJAX data
        wp_localize_script('verso-consultation-js', 'versoConsultation', [
            'ajaxUrl' => rest_url('verso/v1/consultation'),
            'nonce'   => wp_create_nonce('verso_consultation'),
        ]);
    }
}

add_action('wp_enqueue_scripts', 'verso_consultation_enqueue_assets');

/**
 * Load plugin text domain
 */
function verso_consultation_load_textdomain() {
    load_plugin_textdomain(
        'verso-consultation',
        false,
        basename(VERSO_CONSULTATION_DIR) . '/languages'
    );
}

add_action('plugins_loaded', 'verso_consultation_load_textdomain');

// Admin notice if Vault not configured
function verso_consultation_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $vault_url = get_option('verso_consultation_vault_url');
    $vault_token = get_option('verso_consultation_vault_token');

    if (!$vault_url || !$vault_token) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e('Verso Consultation Form:', 'verso-consultation'); ?></strong>
                <?php esc_html_e('Vault configuration is missing. Please configure plugin settings.', 'verso-consultation'); ?>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=verso-consultation')); ?>">
                    <?php esc_html_e('Configure', 'verso-consultation'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}

add_action('admin_notices', 'verso_consultation_admin_notice');

// Add admin settings page
function verso_consultation_add_admin_page() {
    add_options_page(
        'Verso Consultation Settings',
        'Verso Consultation',
        'manage_options',
        'verso-consultation',
        'verso_consultation_render_settings'
    );
}

add_action('admin_menu', 'verso_consultation_add_admin_page');

function verso_consultation_render_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['verso_consultation_save'])) {
        check_admin_referer('verso_consultation_nonce');
        update_option('verso_consultation_vault_url', sanitize_text_field($_POST['vault_url']));
        update_option('verso_consultation_vault_token', sanitize_text_field($_POST['vault_token']));
        update_option('verso_consultation_skill_url', sanitize_text_field($_POST['skill_url']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }

    $vault_url = get_option('verso_consultation_vault_url', 'http://10.0.0.44:8050');
    $vault_token = get_option('verso_consultation_vault_token', '');
    $skill_url = get_option('verso_consultation_skill_url', 'http://10.0.0.44:8092');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Verso Consultation Settings', 'verso-consultation'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('verso_consultation_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vault_url"><?php esc_html_e('Vault URL', 'verso-consultation'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="vault_url" name="vault_url" value="<?php echo esc_attr($vault_url); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="vault_token"><?php esc_html_e('Vault Token', 'verso-consultation'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="vault_token" name="vault_token" value="<?php echo esc_attr($vault_token); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="skill_url"><?php esc_html_e('Consultation Skill URL', 'verso-consultation'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="skill_url" name="skill_url" value="<?php echo esc_attr($skill_url); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'verso_consultation_save'); ?>
        </form>
    </div>
    <?php
}
