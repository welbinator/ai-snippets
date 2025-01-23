<?php
class AI_Snippets {
    public function run() {
        // Initialize settings and API.
        AI_Snippets_Settings::init();
        AI_Snippets_API::init();

        // Register admin menu.
        add_action('admin_menu', [$this, 'register_admin_menu']);

        // Enqueue scripts and localize nonce.
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts($hook) {
        // Only enqueue on the AI Snippets admin pages.
        if (!in_array($hook, ['toplevel_page_ai-snippets', 'ai-snippets_page_ai-snippets-api-key'])) {
            return;
        }
    
        wp_enqueue_script('ai-snippets-script', AI_SNIPPETS_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], AI_SNIPPETS_VERSION, true);
    
        // Localize the script with nonce and ajaxurl.
        wp_localize_script('ai-snippets-script', 'aiSnippetsData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_snippets_nonce'),
        ]);
    }

    public function register_admin_menu() {
        add_menu_page(
            'AI Snippets',          // Page title
            'AI Snippets',          // Menu title
            'manage_options',       // Capability
            'ai-snippets',          // Menu slug
            [$this, 'render_snippets_page'], // Callback function
            'dashicons-admin-tools', // Icon
            25                      // Position
        );

        add_submenu_page(
            'ai-snippets',          // Parent slug
            'API Key',              // Page title
            'API Key',              // Submenu title
            'manage_options',       // Capability
            'ai-snippets-api-key',  // Menu slug
            [AI_Snippets_Settings::class, 'render_settings_page'] // Callback function
        );
    }

    public function render_snippets_page() {
        ?>
        <div class="wrap">
            <h1>AI Snippets</h1>
            <button id="add-new-snippet" class="button button-primary">Add New Snippet</button>
            <div id="snippet-editor" style="display: none; margin-top: 20px;">
                <h2>New Snippet</h2>
                <form id="snippet-form">
                    <label for="snippet-name">Name:</label>
                    <input type="text" id="snippet-name" name="snippet_name" class="regular-text" required>
                    
                    <label for="snippet-type">Type:</label>
                    <select id="snippet-type" name="snippet_type">
                        <option value="php">PHP</option>
                        <option value="js">JavaScript</option>
                        <option value="css">CSS</option>
                        <option value="html">HTML</option>
                        <option value="shortcode">Shortcode</option>
                    </select>
    
                    <button id="generate-snippet" type="button" class="button">Use AI to Generate Snippet</button>
                    <div id="ai-generator" style="display: none; margin-top: 10px;">
                        <textarea id="ai-prompt" rows="4" style="width: 100%;" placeholder="Describe the snippet you want..."></textarea>
                        <button id="submit-ai-prompt" type="button" class="button button-primary">Submit</button>
                    </div>
    
                    <label for="snippet-code">Snippet:</label>
                    <textarea id="snippet-code" name="snippet_code" rows="10" style="width: 100%;"></textarea>
    
                    <button id="save-snippet" type="button" class="button button-primary">Save Snippet</button>
                </form>
            </div>
        </div>
        
        <?php
    }
    
}
