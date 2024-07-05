<?php
/*
* Plugin Name:       Map Block View
* Plugin URI:        https://github.com/AndriyBalakalchuk/wp-map-block-view-plugin/
* Description:       A plugin for replacing the [map_block_view_manufacturers] shortcode with a block with a production map, which receives data from Google Tables.
* Version: 0.09
* Requires at least: 6.4.5
* Requires PHP:      7.0
* Author:            bvstud.io
* Author URI:        https://bvstud.io
* License:           GPL v2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html
* Update URI:        https://github.com/AndriyBalakalchuk/wp-map-block-view-plugin/releases
*/

// Перевірка безпосереднього доступу
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MBV_DB_NAME', 'map_block_view_db' );

define( 'MBV_VERSION', '0.09' ); //для стилів та скриптів

define( 'MBV_PLUGIN', __FILE__ );

define( 'MBV_PLUGIN_BASENAME', plugin_basename( MBV_PLUGIN ) );

define( 'MBV_PLUGIN_NAME', trim( dirname( MBV_PLUGIN_BASENAME ), '/' ));

define( 'MBV_PLUGIN_DIR', untrailingslashit( dirname( MBV_PLUGIN )));

define( 'MBV_PLUGIN_PUBLIC_DIR', MBV_PLUGIN_DIR . '/public' );

define( 'MBV_PLUGIN_URL', plugin_dir_url(__FILE__));

define( 'MBV_PLUGIN_PUBLIC_URL', MBV_PLUGIN_URL . 'public' );

// Перевіряємо, чи це сторінка плагінів - Викликаємо клас тільки на сторінці плагінів
global $pagenow;
if ($pagenow == 'plugins.php') {
    //запит на підключення класу оновленнь плагіна з GitHub репозиторії за релізом
    require_once('includes/update.class.php');
    // Заміни 'AndriyBalakalchuk/wp-map-block-view-plugin' на свій репозиторій
    new GitHubUpdateChecker(MBV_PLUGIN,'https://api.github.com/repos/AndriyBalakalchuk/wp-map-block-view-plugin/releases/latest');
}

// Функція для створення таблиці при активації плагіну
function map_block_view_create_table() {
    global $wpdb;
    $strCharset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS `".($wpdb->prefix.MBV_DB_NAME)."` (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        value text NOT NULL,
        PRIMARY KEY (id)
    ) $strCharset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Функція для видалення таблиці при деактивації плагіну
function map_block_view_delete_table() {
    global $wpdb;
    $sql = "DROP TABLE IF EXISTS ".($wpdb->prefix.MBV_DB_NAME).";";
    $wpdb->query($sql);
}

// Функція для обробки шорткоду
function map_block_view_manufacturers_shortcode() {
    // HTML код, який замінить шорткод
    $strManufacturersBlockPath = MBV_PLUGIN_PUBLIC_DIR . '/public.html';
    $strOutput = 'Manufacturers block public file not found';

    if (file_exists($strManufacturersBlockPath)) {
        $strOutput = file_get_contents($strManufacturersBlockPath);
    }

    return $strOutput;
}

function map_block_view_enqueue_shortcode_assets() {
    wp_enqueue_style('map_block_view_styles', MBV_PLUGIN_PUBLIC_URL . '/css/style.css', array(), MBV_VERSION);
    wp_enqueue_script('map_block_view_scripts', MBV_PLUGIN_PUBLIC_URL . '/js/js.js', array(), MBV_VERSION, true);
}

// Реєстрація шорткоду
function map_block_view_register_shortcode() {
    add_shortcode( 'map_block_view_manufacturers', 'map_block_view_manufacturers_shortcode' );
}

// Реєстрація хука активації плагіну
register_activation_hook(__FILE__, 'map_block_view_create_table');
// Реєстрація хука деактивації плагіну
register_deactivation_hook(__FILE__, 'map_block_view_delete_table');

// Додаємо дію для ініціалізації шорткоду
add_action( 'init', 'map_block_view_register_shortcode' );
// Додаємо дію для ініціалізації CSS та JavaScript
add_action('wp_enqueue_scripts', 'map_block_view_enqueue_shortcode_assets');

/** Always end your PHP files with this closing tag */
?>
