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

        // Register AJAX handler for saving snippets.
        add_action('wp_ajax_save_snippet', [AI_Snippets_API::class, 'save_snippet']);
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
        $snippets = AI_Snippets_API::get_snippets();
        ?>
        <div class="wrap">
            <h1>AI Snippets</h1>
            <button id="add-new-snippet" class="button button-primary">Add New Snippet</button>

            <div id="snippet-editor" style="display: none; margin-top: 20px;">
                <h2>New Snippet</h2>
                <form id="snippet-form">
                    <input type="hidden" id="snippet-id" name="snippet_id">
                    
                    <input type="text" id="snippet-name" name="snippet_name" class="regular-text" placeholder="Snippet Name" required>
                    
                    <label for="snippet-type">Type:</label>
                    <select id="snippet-type" name="snippet_type">
                        <option value="php">PHP</option>
                        <option value="js">JavaScript</option>
                        <option value="css">CSS</option>
                        <option value="html">HTML</option>
                        <option value="shortcode">Shortcode</option>
                    </select>
                    
                    <label for="ai-prompt">Describe the snippet you want, and AI will generate it for you:</label>
                    <textarea id="ai-prompt" name="ai_prompt" rows="4" style="width: 100%;" placeholder="E.g., Create a shortcode to display the latest 5 posts."></textarea>
                    
                    <button id="submit-ai-prompt" type="button" class="button button-primary">Generate Snippet</button>
                    <br />
                    
                    <!-- Optional description field -->
                    <textarea id="snippet-description" name="snippet_description" placeholder="Snippet Description (optional)" rows="4" style="width: 100%;"></textarea>
                    
                    <label for="snippet-code" style="margin-top: 20px; display: block;">Generated Snippet:</label>
                    <textarea id="snippet-code" name="snippet_code" rows="10" style="width: 100%;" placeholder="AI-generated snippet will appear here..." required></textarea>
                    
                    <button id="save-snippet" type="button" class="button button-primary">Save Snippet</button>
                </form>
            </div>

            <!-- Snippet List -->
            <table class="widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($snippets)) : ?>
                        <?php foreach ($snippets as $snippet) : ?>
                            <tr>
                                <td><?php echo esc_html($snippet['name']); ?></td>
                                <td><?php echo esc_html($snippet['type']); ?></td>
                                <td><?php echo $snippet['active'] ? 'Active' : 'Inactive'; ?></td>
                                <td>
                                    <button class="button edit-snippet" data-id="<?php echo $snippet['id']; ?>">Edit</button>
                                    <button class="button delete-snippet" data-id="<?php echo $snippet['id']; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4">No snippets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
