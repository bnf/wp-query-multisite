<?php
/**
 * WP Query Multisite
 *
 * @package     TimJensen\WPQueryMultisite
 * @author      Tim Jensen <tim@timjensen.us>
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 *
 * Plugin Name: WP Query Multisite
 * Plugin URI:  https://github.com/timothyjensen/wp-query-multisite
 * Description: Allows WP_Query to query across the entire multisite network.
 * Version:     1.0.0
 * Author:      Tim Jensen
 * Author URI:  https://www.timjensen.us
 * Text Domain: wp-query-multisite
 * License:     GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace TimJensen\WPQueryMultisite;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( WPQueryMultisite::class ) ) {
	require_once __DIR__ . '/src/WPQueryMultisite.php';
}

WPQueryMultisite::make()->init();
