<?php
class AI_Snippets_API {
    private static $api_key;

    public static function init() {
        self::$api_key = get_option('ai_snippets_api_key', '');

        add_action('wp_ajax_check_openai_connection', [__CLASS__, 'check_openai_connection']);
    }

    public static function check_openai_connection() {
        check_ajax_referer('ai_snippets_nonce', 'security');
    
        if (empty(self::$api_key)) {
            $response = ['success' => false, 'message' => 'API Key is missing.'];
            error_log('Response: ' . json_encode($response)); // Log response for debugging
            wp_send_json_error($response);
        }
    
        $url = 'https://api.openai.com/v1/engines';
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . self::$api_key,
            ],
            'timeout' => 15,
        ]);
    
        if (is_wp_error($response)) {
            $error_response = ['success' => false, 'message' => 'Connection failed: ' . $response->get_error_message()];
            error_log('Response: ' . json_encode($error_response)); // Log response for debugging
            wp_send_json_error($error_response);
        }
    
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            $success_response = ['success' => true, 'message' => 'Connection successful. API Key is valid.'];
            error_log('Response: ' . json_encode($success_response)); // Log response for debugging
            wp_send_json_success($success_response);
        } else {
            $failure_response = ['success' => false, 'message' => 'Connection failed. HTTP Status Code: ' . $status_code];
            error_log('Response: ' . json_encode($failure_response)); // Log response for debugging
            wp_send_json_error($failure_response);
        }
    }
    
}
