<?php
class AI_Snippets_Settings {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_settings_page() {
        add_options_page(
            'AI Snippets Settings',
            'AI Snippets',
            'manage_options',
            'ai-snippets',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings() {
        // Register the option in the database.
        register_setting('ai_snippets_group', 'ai_snippets_api_key');
    
        // Add a section to the settings page.
        add_settings_section(
            'ai_snippets_section',      // Section ID.
            'API Configuration',        // Title of the section.
            null,                       // Callback for rendering the section description.
            'ai-snippets'               // Page to which the section is added.
        );
    
        // Add the API key field to the section.
        add_settings_field(
            'ai_snippets_api_key',      // Field ID.
            'OpenAI API Key',           // Field title.
            function() {
                // Render the input field.
                $value = get_option('ai_snippets_api_key', '');
                echo '<input type="text" name="ai_snippets_api_key" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'ai-snippets',              // Page to which the field is added.
            'ai_snippets_section'       // Section ID where the field is displayed.
        );
    }
    

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Snippets API Settings</h1>
            <form method="post" action="options.php">
                <?php
                // Output nonce, action, and option_page fields for the settings group.
                settings_fields('ai_snippets_group');
    
                // Output the settings section and its fields.
                do_settings_sections('ai-snippets');
    
                // Display the submit button.
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    
}
