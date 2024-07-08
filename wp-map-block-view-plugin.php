<?php
/*
* Plugin Name:       Map Block View
* Plugin URI:        https://github.com/AndriyBalakalchuk/wp-map-block-view-plugin/
* Description:       A plugin for replacing the [map_block_view_manufacturers] shortcode with a block with a production map, which receives data from Google Tables.
* Version: 0.10
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

//№	Name	Logo Link	Address	Lat/Long	Description EN	Description DE	Act	Publ


    $sql = "CREATE TABLE IF NOT EXISTS `".($wpdb->prefix.MBV_DB_NAME)."` (
        id int(15) NOT NULL AUTO_INCREMENT,
        destination text NOT NULL,
        name text NOT NULL,
        cover_link text NOT NULL,
        address text NOT NULL,
        lat_and_long text NOT NULL,
        description_en text NOT NULL,
        description_de text NOT NULL,
        status text NOT NULL,
        published text NOT NULL,
        area int(11) NOT NULL,
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
        // получить сирий HTML-файл
        $strOutput = file_get_contents($strManufacturersBlockPath);
        // получить дані з таблиці бази даних
        $arrMapData = map_block_view_get_filtered_data('destination', 'manufacturers');
        // echo "<pre>";var_dump($arrMapData);echo "</pre>";exit;
        // замінити дані в HTML-файлі
        $strOutput = str_replace('<!--{{data-arrAllMapData}}-->',  "<script>window.arrAllMapData = ".JSON_encode($arrMapData).";</script>", $strOutput);
    }

    return $strOutput;
}

// Додаємо REST API маршрут при активації плагіну
add_action('rest_api_init', function () {
    register_rest_route(MBV_PLUGIN_NAME.'/v1', '/new-input', array(
        'methods' => 'POST',
        'callback' => 'map_block_view_handle_update_table',
        'permission_callback' => '__return_true'
    ));
});

// тест доступ за посиланням localhost:8888/modulmatic-website/wp-json/wp-map-block-view-plugin/v1/new-input
// тест обʼєкт - {"destination": ["manufacturers"],"name":["Solid Modulbau"],"cover_link":["https://i.ibb.co/Xkm3VTm/Solid-Modulbau-Logo.jpg"],"address":["Produktion & Firmensitz Otto-Hahn-Straße 1-2 48683 Ahaus"],"lat_and_long":["52.08339146970849,7.019180719708498"],"description_en":["Building with Solid.Modulbau is faster, cheaper, and more sustainable! This is our promise to investors and users who are forward-thinking. Otherwise, we will not be able to create the necessary and, above all, affordable living space for our society. We also see our work as a contribution to social peace in Germany and, prospectively, in Europe and the world. At the same time, construction must become climate-neutral, as the construction industry is one of the main contributors to CO2 emissions. If we want to halt climate change, something must be done in our industry – and it must be done today."],"description_de":["Bauen wird mit Solid.Modulbau schneller, günstiger und nachhaltiger! Das ist unser Versprechen an Investoren und Nutzer, die zukunftsorientiert sind. Anders schaffen wir nicht den benötigten und vor allem bezahlbaren Wohnraum für unsere Gesellschaft. Unsere Tätigkeit sehen wir daher auch als Beitrag zum sozialen Frieden in Deutschland und – perspektivisch – in Europa und der Welt. Gleichzeitig muss das Bauen klimaneutral werden, denn die Baubranche ist einer der Hauptverursacher von CO2. Wollen wir den Klimawandel aufhalten, muss sich also vor allem in unserer Branche etwas tun – und zwar heute."],"status":["false"],"published":["true"],"area":[0]}
function map_block_view_handle_update_table(WP_REST_Request $request) {
    global $wpdb;

    // Отримуємо дані з запиту
    $objData = $request->get_json_params();

    // Визначаємо таблицю
    $strTableName = $wpdb->prefix.MBV_DB_NAME;

    // Перевіряємо чи дані у правильному форматі
    if( !isset($objData["destination"]) ||
        !isset($objData["name"]) ||
        !isset($objData["cover_link"]) ||
        !isset($objData["address"]) ||
        !isset($objData["lat_and_long"]) ||
        !isset($objData["description_en"]) ||
        !isset($objData["description_de"]) ||
        !isset($objData["status"]) ||
        !isset($objData["published"]) ||
        !isset($objData["area"]) ||
        !is_array($objData["destination"]) ||
        !is_array($objData["name"]) ||
        !is_array($objData["cover_link"]) ||
        !is_array($objData["address"]) ||
        !is_array($objData["lat_and_long"]) ||
        !is_array($objData["description_en"]) ||
        !is_array($objData["description_de"]) ||
        !is_array($objData["status"]) ||
        !is_array($objData["published"]) ||
        !is_array($objData["area"])) {
        return new WP_REST_Response(array('message'=>'Wrong data','code'=>'wrong_data'), 400);
    }

    //визначення для якого з блоків прийшли дані
    $strDestination = $objData["destination"][0];
    if($strDestination != "manufacturers" && $strDestination != "projects") {
        return new WP_REST_Response(array('message'=>'Unknown destination','code'=>'unknown_destination'), 400);
    }

    //видаляємо в таблиці всі стрічки де destination = $strDestination
    $sql = "DELETE FROM $strTableName WHERE destination = '$strDestination'";
    $wpdb->query($sql);

    // Вставляємо нові дані
    for ($i = 0; $i < count($objData["name"]); $i++) {
        $wpdb->insert($strTableName, array(
            'destination' => $strDestination,
            'name' => $objData["name"][$i],
            'cover_link' => $objData["cover_link"][$i],
            'address' => $objData["address"][$i],
            'lat_and_long' => $objData["lat_and_long"][$i],
            'description_en' => $objData["description_en"][$i],
            'description_de' => $objData["description_de"][$i],
            'status' => $objData["status"][$i],
            'published' => $objData["published"][$i],
            'area' => $objData["area"][$i],
        ));
    }

    return new WP_REST_Response(array( 'message' => 'All data was inserted to your site' ), 200);
}

function map_block_view_get_filtered_data($strFilterColumn, $strFilterValue) {
    global $wpdb;

    // Захист від SQL ін'єкцій
    $strTableName = $wpdb->prefix.MBV_DB_NAME;
    $strFilterColumn = esc_sql($strFilterColumn);
    $strFilterValue = esc_sql($strFilterValue);

    // Запит до бази даних
    $objQuery = $wpdb->prepare("SELECT * FROM $strTableName WHERE $strFilterColumn = %s", $strFilterValue);
    $arrResults = $wpdb->get_results($objQuery, ARRAY_A);

    return $arrResults;
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
