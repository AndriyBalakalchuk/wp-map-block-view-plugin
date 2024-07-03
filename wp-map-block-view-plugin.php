<?php
/*
Plugin Name: GitHub Update Checker
Description: Simple plugin to check for updates from a public GitHub repository.
Version: 1.0.0
Author: Your Name
*/

class GitHubUpdateChecker {
    private $api_url = 'https://api.github.com/repos/AndriyBalakalchuk/wp-map-block-view-plugin/releases/latest'; // Заміни 'username/repository' на свій репозиторій
    private $plugin_file;

    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        add_action('admin_init', array($this, 'check_for_update'));
    }

    public function check_for_update() {
        $response = wp_remote_get($this->api_url);
        if (is_wp_error($response)) {
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (isset($data->tag_name) && version_compare($data->tag_name, $this->get_current_version(), '>')) {
            add_action('admin_notices', array($this, 'show_update_notice'));
            if (isset($_GET['update_plugin']) && $_GET['update_plugin'] == 'true') {
                $this->update_plugin($data->zipball_url);
            }
        }
    }

    public function get_current_version() {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data($this->plugin_file);
        return $plugin_data['Version'];
    }

    public function show_update_notice() {
        echo '<div class="notice notice-warning"><p>New version of GitHub Update Checker is available. <a href="' . admin_url('admin.php?update_plugin=true') . '">Update now</a></p></div>';
    }

    public function update_plugin($url) {
        $zip_file = download_url($url);

        if (is_wp_error($zip_file)) {
            return;
        }

        $result = unzip_file($zip_file, WP_PLUGIN_DIR);

        if (is_wp_error($result)) {
            return;
        }

        unlink($zip_file);

        echo '<div class="notice notice-success"><p>Plugin updated successfully!</p></div>';
    }
}

// Додаємо дію для ініціалізації шорткоду
add_action( 'init', 'map_block_view_register_shortcode' );
// Реєстрація шорткоду
function map_block_view_register_shortcode() {
    add_shortcode( 'map_block_view_manufacturers', 'map_block_view_manufacturers_shortcode' );
}
// Функція для обробки шорткоду
function map_block_view_manufacturers_shortcode() {
    return "Hello, World!";
}

new GitHubUpdateChecker(__FILE__);
?>
