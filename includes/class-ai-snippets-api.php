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
            'timeout' => 20,
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
            'timeout' => 20, // Increased timeout to 20 seconds
        ]);
        

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'API error: ' . $response->get_error_message()]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['choices'][0]['message']['content'])) {
            $content = $body['choices'][0]['message']['content'];

            // Extract only the content inside the first pair of backticks
            if (preg_match('/```[a-z]*\n(.*?)\n```/s', $content, $matches)) {
                $snippet = $matches[1]; // Extracted code
            } else {
                $snippet = $content; // Fallback to raw content if no backticks are found
            }

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

    public static function toggle_snippet_status() {
        check_ajax_referer('ai_snippets_nonce', 'security'); // Validate nonce
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_snippets';
    
        // Get the ID and active status from the request
        $id = intval($_POST['id'] ?? 0);
        $active = intval($_POST['active'] ?? 0);
    
        // Ensure we have a valid ID
        if (!$id) {
            wp_send_json_error(['message' => 'Snippet ID is required.']);
        }
    
        // Update the snippet's active status in the database
        $updated = $wpdb->update(
            $table_name,
            ['active' => $active],
            ['id' => $id],
            ['%d'], // Format for active status
            ['%d']  // Format for ID
        );
    
        if ($updated === false) {
            // Respond with an error if the update fails
            wp_send_json_error(['message' => 'Failed to update snippet status.']);
        }
    
        // Respond with success and the updated status
        $status = $active ? 'activated' : 'deactivated';
        wp_send_json_success(['message' => "Snippet successfully $status."]);
    }
    
    public static function delete_snippet() {
        check_ajax_referer('ai_snippets_nonce', 'security'); // Validate nonce for security
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_snippets';
    
        // Get the snippet ID from the request
        $id = intval($_POST['id'] ?? 0);
    
        // Ensure a valid ID is provided
        if (empty($id)) {
            wp_send_json_error(['message' => 'Invalid snippet ID.']);
        }
    
        // Attempt to delete the snippet from the database
        $deleted = $wpdb->delete($table_name, ['id' => $id], ['%d']);
    
        if ($deleted === false) {
            // Respond with an error if the deletion fails
            wp_send_json_error(['message' => 'Failed to delete snippet.']);
        }
    
        // Respond with success if deletion is successful
        wp_send_json_success(['message' => 'Snippet deleted successfully.']);
    }
    

    public static function get_snippets() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_snippets';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);
    }
}
