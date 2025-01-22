<?php
class AI_Snippets {
    public function run() {
        // Initialize settings and API.
        AI_Snippets_Settings::init();
        AI_Snippets_API::init();

        // Register admin menu.
        add_action('admin_menu', [$this, 'register_admin_menu']);
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
            <p>Manage and create AI-generated code snippets here.</p>
            <button id="check-connection" class="button button-primary">Check OpenAI Connection</button>
            <div id="connection-result" style="margin-top: 15px;"></div>
        </div>
        <script>
            document.getElementById('check-connection').addEventListener('click', function () {
    console.log('Checking connection...');
    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'check_openai_connection',
            security: '<?php echo wp_create_nonce('ai_snippets_nonce'); ?>'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Full Response:', data); // Log the response for debugging
        const resultDiv = document.getElementById('connection-result');

        // Correctly access the nested message key
        if (data && data.success && data.data && data.data.message) {
            resultDiv.innerHTML = `<p style="color: green;">${data.data.message}</p>`;
        } else if (data && data.data && data.data.message) {
            resultDiv.innerHTML = `<p style="color: red;">${data.data.message}</p>`;
        } else {
            resultDiv.innerHTML = `<p style="color: red;">Response format is valid but missing keys.</p>`;
        }
    })
    .catch(error => {
        console.error('Error:', error); // Log any unexpected errors
        document.getElementById('connection-result').innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
    });
});


        </script>
        <?php
    }
}
