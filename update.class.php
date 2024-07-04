<?php
class GitHubUpdateChecker {
    private $strApi_url = 'https://api.github.com/repos/AndriyBalakalchuk/wp-map-block-view-plugin/releases/latest'; // Заміни 'username/repository' на свій репозиторій
    private $strPlugin_file;
    private $strPlugin_dir;
    private $strPlugin_dir_name;
    private $strPlugin_name;
    private $strPlugin_version;

    public function __construct($strPlugin_file) {
        $this->strPlugin_file = $strPlugin_file;
        $this->strPlugin_dir = plugin_dir_path($strPlugin_file);
        $this->strPlugin_dir_name = trim(dirname(plugin_basename($strPlugin_file)), '/');
        $this->strPlugin_name = $this->get_current_plugin_info("Name");
        $this->strPlugin_version = $this->get_current_plugin_info("Version");
        add_action('admin_init', array($this, 'check_for_update'));
    }

    public function get_current_plugin_info($strWhat="Version") {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $objPlugin_data = get_plugin_data($this->strPlugin_file);
        return $objPlugin_data[$strWhat]===NULL ? "Unknown ".$strWhat : $objPlugin_data[$strWhat];
    }

    public function check_for_update() {
        $objResponse = wp_remote_get($this->strApi_url);
        if (is_wp_error($objResponse)) {
            error_log($objResponse->get_error_message());
            return;
        }

        $strBody = wp_remote_retrieve_body($objResponse);
        $objData = json_decode($strBody);
        
        if (isset($objData->tag_name) && version_compare($objData->tag_name, $this->strPlugin_version, '>')) {
            if (isset($_GET['update_bvstudio_plugin']) && $_GET['update_bvstudio_plugin'] == 'true') {
                $this->update_plugin($objData->zipball_url);
            }else{
                add_action('admin_notices', array($this, 'show_update_notice'));
            }
        }
    }

    public function show_update_notice() {
        echo '<div class="notice notice-warning"><p>New version of '.$this->strPlugin_name.' is available. <a href="' . admin_url('index.php?update_bvstudio_plugin=true') . '">Update now</a></p></div>';
    }

    public function update_plugin($url) {
        //скачать архів нової версії
        $zip_file = download_url($url);
        if (is_wp_error($zip_file)) {
            error_log($zip_file->get_error_message());
            return;
        }

        //скачано, створити темп директорії
        $strTemp_dir = WP_PLUGIN_DIR . '/temp_bvstudio_plugin_update';
        if (!is_dir($strTemp_dir)) {
            mkdir($strTemp_dir, 0755, true);
        }

        //розпакувати архів нової версії
        $objResult = $this->unzip_file($zip_file, $strTemp_dir);
        if (is_wp_error($objResult)) {
            error_log($objResult->get_error_message());
            return;
        }

        //з архіву розпаковано папку, знаходимо назву папки в нашій темп директорії
        //отримати список фсіх папкок в темп директорії
        $arrFiles = scandir($strTemp_dir);
        foreach ($arrFiles as $strItem) {
            if ($strItem == '.' || $strItem == '..') {continue;} 
            if (is_dir($strTemp_dir . '/' . $strItem)) {
                rename($strTemp_dir . '/' . $strItem, $strTemp_dir."/".$this->strPlugin_dir_name);
                break;
            }
        }

        $this->delete_old_version();

        $this->move_files($strTemp_dir, WP_PLUGIN_DIR);

        $this->delete_directory($strTemp_dir);

        unlink($zip_file);

        echo '<div class="notice notice-success"><p>Plugin '.$this->strPlugin_name.' updated successfully!</p></div>';
    }

    private function delete_old_version() {
        $this->delete_directory($this->strPlugin_dir);
    }

    private function delete_directory($strDir) {
        if (!is_dir($strDir)) {
            return;
        }

        $arrItems = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($strDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($arrItems as $strItem) {
            $strItem->isDir() ? rmdir($strItem) : unlink($strItem);
        }

        rmdir($strDir);
    }

    private function unzip_file($file, $strDestination) {
        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === TRUE) {
            $zip->extractTo($strDestination);
            $zip->close();
            return true;
        } else {
            return new WP_Error('unzip_error', __('Error unzipping file.'));
        }
    }

    private function move_files($strSource, $strDestination) {
        $arrItems = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($strSource, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($arrItems as $strItem) {
            $strDest_path = $strDestination . DIRECTORY_SEPARATOR . $arrItems->getSubPathName();
            if ($strItem->isDir()) {
                mkdir($strDest_path, 0755, true);
            } else {
                rename($strItem, $strDest_path);
            }
        }
    }
}
?>
