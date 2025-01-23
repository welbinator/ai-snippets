<?php
/**
 * Plugin Name: AI Snippets
 * Description: A WordPress plugin to create and manage code snippets using AI.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0+
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

define('AI_SNIPPETS_VERSION', '1.0.0');
define('AI_SNIPPETS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_SNIPPETS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload dependencies (if needed in the future).
if (file_exists(AI_SNIPPETS_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once AI_SNIPPETS_PLUGIN_DIR . 'vendor/autoload.php';
}

// Include core files.
require_once AI_SNIPPETS_PLUGIN_DIR . 'includes/class-ai-snippets.php';
require_once AI_SNIPPETS_PLUGIN_DIR . 'includes/class-ai-snippets-api.php';
require_once AI_SNIPPETS_PLUGIN_DIR . 'includes/class-ai-snippets-settings.php';

// Create the custom table on plugin activation.
register_activation_hook(__FILE__, 'ai_snippets_create_table');

function ai_snippets_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'ai_snippets'; // Unique table name
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        code LONGTEXT NOT NULL,
        type VARCHAR(50) NOT NULL,
        active TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Initialize the plugin.
function ai_snippets_init() {
    // Load the main plugin class.
    $plugin = new AI_Snippets();

    // Run the plugin.
    $plugin->run();
}
add_action('plugins_loaded', 'ai_snippets_init');
