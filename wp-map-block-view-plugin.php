<?php
/*
Plugin Name: GitHub Update Checker
Description: Simple plugin to check for updates from a public GitHub repository.
Version: 0.04
Author: Your Name
*/

class GitHubUpdateChecker {
    private $api_url = 'https://api.github.com/repos/AndriyBalakalchuk/wp-map-block-view-plugin/releases/latest'; // Заміни 'username/repository' на свій репозиторій
    private $plugin_file;
    private $plugin_dir;
    private $plugin_version;

    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_dir = plugin_dir_path($plugin_file);
        $this->plugin_version = get_current_version();
        add_action('admin_init', array($this, 'check_for_update'));
    }

    public function get_current_version() {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data($this->plugin_file);
        return $plugin_data['Version'];
    }

    public function check_for_update() {
        $response = wp_remote_get($this->api_url);
        if (is_wp_error($response)) {
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if (isset($data->tag_name) && version_compare($data->tag_name, $this->plugin_version, '>')) {
            add_action('admin_notices', array($this, 'show_update_notice'));
            if (isset($_GET['update_plugin']) && $_GET['update_plugin'] == 'true') {
                $this->update_plugin($data->zipball_url);
            }
        }
    }

    public function show_update_notice() {
        echo '<div class="notice notice-warning"><p>New version of GitHub Update Checker is available. <a href="' . admin_url('index.php?update_plugin=true') . '">Update now</a></p></div>';
    }

    public function update_plugin($url) {
        $zip_file = download_url($url);

        if (is_wp_error($zip_file)) {
            return;
        }

        $this->delete_old_version();

        $temp_dir = WP_PLUGIN_DIR . '/temp_plugin_update';

        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }

        $result = $this->unzip_file($zip_file, $temp_dir);

        if (is_wp_error($result)) {
            return;
        }

        $this->move_files($temp_dir, $this->plugin_dir);

        $this->delete_directory($temp_dir);
        unlink($zip_file);

        echo '<div class="notice notice-success"><p>Plugin updated successfully!</p></div>';
    }

    private function delete_old_version() {
        $this->delete_directory($this->plugin_dir);
    }

    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item) : unlink($item);
        }

        rmdir($dir);
    }

    private function unzip_file($file, $destination) {
        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === TRUE) {
            $zip->extractTo($destination);
            $zip->close();
            return true;
        } else {
            return new WP_Error('unzip_error', __('Error unzipping file.'));
        }
    }

    private function move_files($source, $destination) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $dest_path = $destination . DIRECTORY_SEPARATOR . $items->getSubPathName();
            if ($item->isDir()) {
                mkdir($dest_path, 0755, true);
            } else {
                rename($item, $dest_path);
            }
        }
    }
}

// // Додаємо дію для ініціалізації шорткоду
// add_action( 'init', 'map_block_view_register_shortcode' );
// // Реєстрація шорткоду
// function map_block_view_register_shortcode() {
//     add_shortcode( 'map_block_view_manufacturers', 'map_block_view_manufacturers_shortcode' );
// }
// // Функція для обробки шорткоду
// function map_block_view_manufacturers_shortcode() {
//     return "Hello, World!";
// }

new GitHubUpdateChecker(__FILE__);
?>
