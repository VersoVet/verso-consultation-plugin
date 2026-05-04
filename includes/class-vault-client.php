<?php
/**
 * Vault Client for retrieving secrets
 */

if (!defined('ABSPATH')) {
    exit;
}

class Verso_Vault_Client {
    /**
     * Get secret from Vault
     *
     * @param string $key Secret key
     * @return string|false Secret value or false if failed
     */
    public static function get_secret($key) {
        $vault_url = get_option('verso_consultation_vault_url', 'http://10.0.0.44:8050');
        $vault_token = get_option('verso_consultation_vault_token', '');

        if (!$vault_token) {
            return false;
        }

        $url = trailingslashit($vault_url) . 'vault/' . sanitize_text_field($key);

        $response = wp_remote_get(
            $url,
            [
                'headers'   => [
                    'X-Vault-Token' => $vault_token,
                    'Accept'        => 'application/json',
                ],
                'timeout'   => 10,
                'sslverify' => false,
            ]
        );

        if (is_wp_error($response)) {
            error_log('Verso Vault error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return isset($data['value']) ? $data['value'] : false;
    }

    /**
     * Get secret as JSON
     *
     * @param string $key Secret key
     * @return array|false Parsed JSON or false if failed
     */
    public static function get_secret_json($key) {
        $secret = self::get_secret($key);
        if (!$secret) {
            return false;
        }

        return json_decode($secret, true);
    }
}
