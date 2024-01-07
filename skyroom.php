<?php
/**
 * Skyroom wordpress plugin.
 *
 * @wordpress-plugin
 * Plugin Name:       Skyroom
 * Plugin URI:        https://skyroom.online/pages/wordpress-integration
 * Description:       A plugin to integrate skyroom with your wordpress site
 * Version:           1.6.3
 * Author:            Skyroom
 * Author URI:        https://skyroom.online/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       skyroom
 * Domain Path:       /languages
 */

// Prevent direct access
defined('WPINC') || die;

// Load composer autoloader
require_once __DIR__.'/vendor/autoload.php';

// Register activation hook
register_activation_hook(__FILE__, [Skyroom\Util\Activator::class, 'activate']);

// Boot plugin
add_action('plugins_loaded', [new Skyroom\Plugin(), 'boot']);

//load languages
function Skyroom_load_plugin_textdomain() {
    load_plugin_textdomain( 'skyroom', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'Skyroom_load_plugin_textdomain' );
