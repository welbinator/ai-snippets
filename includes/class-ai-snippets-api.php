<?php
class AI_Snippets_API {
    private static $api_key;

    public static function init() {
        self::$api_key = get_option('ai_snippets_api_key', '');

        add_action('wp_ajax_check_openai_connection', [__CLASS__, 'check_openai_connection']);
        add_action('wp_ajax_generate_snippet', [__CLASS__, 'generate_snippet']);
        add_action('init', [__CLASS__, 'execute_active_snippets']); // Hook to run active snippets
    }

    public static function check_openai_connection() {
        check_ajax_referer('ai_snippets_nonce', 'security');
        if (empty(self::$api_key)) {
            wp_send_json_error(['message' => 'API Key is missing.']);
        }

        $url = 'https://api.openai.com/v1/engines';
        $response = wp_remote_get($url, [
            'headers' => ['Authorization' => 'Bearer ' . self::$api_key],
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            wp_send_json_success(['message' => 'Connection successful.']);
        } else {
            wp_send_json_error(['message' => 'HTTP Status Code: ' . $status_code]);
        }
    }

    public static function generate_snippet() {
        check_ajax_referer('ai_snippets_nonce', 'security');
        $prompt = sanitize_text_field($_POST['prompt'] ?? '');
        if (empty($prompt)) {
            wp_send_json_error(['message' => 'Prompt is required.']);
        }

        $api_key = get_option('ai_snippets_api_key', '');
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'Provide only valid code in response.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1000,
            ]),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'API error: ' . $response->get_error_message()]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['choices'][0]['message']['content'])) {
            $snippet = preg_replace('/^```[a-z]*\n|\n```$/', '', $body['choices'][0]['message']['content']);
            wp_send_json_success(['snippet' => $snippet]);
        }

        wp_send_json_error(['message' => 'Failed to generate snippet.']);
    }

    public static function save_snippet() {
        check_ajax_referer('ai_snippets_nonce', 'security');
        global $wpdb;

        $table_name = $wpdb->prefix . 'ai_snippets';
        $name = sanitize_text_field($_POST['name'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $code = wp_unslash($_POST['code'] ?? '');

        if (empty($name) || empty($code)) {
            wp_send_json_error(['message' => 'Name and code are required.']);
        }

        $data = [
            'name'        => $name,
            'description' => $description,
            'code'        => $code,
            'type'        => $type,
            'active'      => 0,
            'updated_at'  => current_time('mysql'),
        ];

        if (!empty($_POST['id'])) {
            $id = intval($_POST['id']);
            $wpdb->update($table_name, $data, ['id' => $id]);
            wp_send_json_success(['message' => 'Snippet updated successfully.']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table_name, $data);
            wp_send_json_success(['message' => 'Snippet saved successfully.']);
        }
    }

    public static function execute_active_snippets() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_snippets';

        $snippets = $wpdb->get_results("SELECT code FROM $table_name WHERE active = 1", ARRAY_A);

        foreach ($snippets as $snippet) {
            eval($snippet['code']); // Execute the PHP code
        }
    }

    public static function get_snippets() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_snippets';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);
    }
}
