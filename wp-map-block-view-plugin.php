<?php
/*
Plugin Name: GitHub Update Checker
Description: Simple plugin to check for updates from a public GitHub repository.
Version: 0.08
Author: Your Name
*/

//запит на підключення класу оновленнь плагіна з GitHub репозиторії за релізом
require_once('update.class.php');
// Заміни 'AndriyBalakalchuk/wp-map-block-view-plugin' на свій репозиторій
// Перевіряємо, чи ми на сторінці плагінів, перед ініціалізацією класу
new GitHubUpdateChecker(__FILE__,'https://api.github.com/repos/AndriyBalakalchuk/wp-map-block-view-plugin/releases/latest');
?>
