<?php
/**
 * Skyroom wordpress plugin.
 *
 * @wordpress-plugin
 * Plugin Name:       Skyroom
 * Plugin URI:        https://skyroom.online/pages/wordpress-integration
 * Description:       A plugin to integrate skyroom with your wordpress site
 * Version:           1.0.0
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
