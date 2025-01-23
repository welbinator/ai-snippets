<?php
class AI_Snippets_API {
    private static $api_key;

    public static function init() {
        self::$api_key = get_option('ai_snippets_api_key', '');

        add_action('wp_ajax_check_openai_connection', [__CLASS__, 'check_openai_connection']);
        add_action('wp_ajax_generate_snippet', [__CLASS__, 'generate_snippet']);

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
            'timeout' => 60,
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

    // Generate snippet with AI
    public static function generate_snippet() {
        check_ajax_referer('ai_snippets_nonce', 'security'); // Validate nonce
    
        $prompt = isset($_POST['prompt']) ? sanitize_text_field($_POST['prompt']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    
        if (empty($prompt)) {
            wp_send_json_error(['message' => 'Prompt is required.']);
        }
    
        $api_key = get_option('ai_snippets_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API Key is missing.']);
        }
    
        $retry_count = 0;
        $max_retries = 3;
        $response = null;
    
        $start_time = microtime(true); // Start timing the request
    
        while ($retry_count < $max_retries) {
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'model' => 'gpt-4',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a coding assistant. When responding to the user, ONLY provide the code snippet in the requested programming language or format, with no additional explanation, comments, or text. Respond with valid code only.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => 1000,
                ]),
                'timeout' => 20, // Increased timeout
            ]);
    
            if (!is_wp_error($response)) {
                break; // Exit retry loop if request succeeds
            }
    
            $retry_count++;
            error_log('Retrying OpenAI API request, attempt: ' . ($retry_count + 1));
        }
    
        $end_time = microtime(true); // End timing the request
        error_log('OpenAI request duration: ' . ($end_time - $start_time) . ' seconds');
    
        if (is_wp_error($response)) {
            error_log('API Error: ' . $response->get_error_message());
            wp_send_json_error(['message' => 'Failed to connect to OpenAI after retries.']);
        }
    
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        error_log('OpenAI Full Response: ' . $body); // Log the full response for debugging
        $body = json_decode($body, true);
    
        if ($status_code !== 200) {
            $error_message = $body['error']['message'] ?? 'Unknown error';
            error_log('OpenAI API Error: ' . $error_message);
            wp_send_json_error(['message' => 'OpenAI API request failed: ' . $error_message]);
        }
    
        $choices = $body['choices'] ?? null;
    
        if ($choices && isset($choices[0]['message']['content'])) {
            $snippet = $choices[0]['message']['content'];
    
            // Remove surrounding backticks if present
            $snippet = preg_replace('/^```[a-z]*\n|\n```$/', '', $snippet);
    
            wp_send_json_success(['snippet' => $snippet]);
        } else {
            error_log('Unexpected OpenAI Response Structure: ' . print_r($body, true));
            wp_send_json_error(['message' => 'Failed to generate snippet. Unexpected response structure.']);
        }
    }
    
    
// Save snippet
public static function save_snippet() {
    check_ajax_referer('ai_snippets_nonce', 'security');

    $name = sanitize_text_field($_POST['name'] ?? '');
    $type = sanitize_text_field($_POST['type'] ?? '');
    $code = sanitize_textarea_field($_POST['code'] ?? '');

    if (empty($name) || empty($code)) {
        wp_send_json_error(['message' => 'Name and code are required.']);
    }

    // Save snippet logic here, e.g., save to a custom post type or custom table
    wp_send_json_success(['message' => 'Snippet saved successfully.']);
}

    
}
