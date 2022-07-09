<?php

/**
 * Pluguin
 *
 * @package     Pluguin
 * @author      sina-radmanesh
 * @copyright   2022 RavandSoft
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Pluguin
 * Plugin URI: https://webbax.dev/pluguin
 * Description: ...
 * Version:     0.0.1
 * Requires PHP: 7.4
 * Author:      Sina Radmanesh
 * Author URI:  http://webbax.ir
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ravand
 * Domain Path: /resources/lang
 */

use \Pluguin\Pluguin;

if (defined("PLUGUIN") || class_exists(Pluguin::class)) {
    return;
}

add_action( 'admin_menu', 'wporg_options_page' );
function wporg_options_page() {
    add_menu_page(
        'WPOrg',
        'WPOrg Options',
        'manage_options',
        // plugin_dir_path(__FILE__) . 'admin/view.php',
        'post-new.php?post_type=acf-s-group',
        // function(){
        //     return "hi";
        // },
        null,
        plugin_dir_url(__FILE__) . 'images/icon_wporg.png',
        20
    );
}

return;
require __DIR__ . "/vendor/autoload.php";

define("PLUGUIN", true);

Pluguin::init();

function pluguin()
{
    return Pluguin::getInstance();
}


// pluguin()->database->migrator()->run([__DIR__."/migrations/CreateLosersTable.php"]);