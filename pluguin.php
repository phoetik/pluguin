<?php

/**
 * Pluguin
 *
 * @package     Ravand Admin Panel
 * @author      sina-radmanesh
 * @copyright   2022 RavandSoft
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Ravand Admin Panel
 * Plugin URI: https://ravandsoft.com/admin-panel
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

if (defined("PLUGUIN") || class_exists(\Pluguin\Pluguin::class)) {
    return;
}

require __DIR__ . "/vendor/autoload.php";

define("PLUGUIN", true);

use \Pluguin\Pluguin;

add_action("plugins_loaded", function () {

    $pluguin = new Pluguin;

    do_action("pluguin", $pluguin);

});
