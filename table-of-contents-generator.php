<?php
/**
 * Plugin Name:       Table of Contents Generator
 * Plugin URI:        https://github.com/jdyach/table-of-contents-generator
 * Description:       Automatically parses H2 and H3 headings in post content and injects a linked Table of Contents. Configurable heading levels, minimum headings threshold, and title text.
 * Version:           1.0.1
 * Author:            Jacqui Dyach
 * Author URI:        https://jacquid.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       table-of-contents-generator
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'TOCG_VERSION',    '1.0.1' );
define( 'TOCG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TOCG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TOCG_OPTIONS_KEY', 'tocg_settings' );

require_once TOCG_PLUGIN_DIR . 'includes/class-tocg-parser.php';
require_once TOCG_PLUGIN_DIR . 'includes/class-tocg-output.php';
require_once TOCG_PLUGIN_DIR . 'includes/class-tocg-settings.php';

function tocg_init(): void {
    TOCG_Settings::init();
    TOCG_Output::init();
}
add_action( 'plugins_loaded', 'tocg_init' );

register_activation_hook( __FILE__, function (): void {
    if ( ! get_option( TOCG_OPTIONS_KEY ) ) {
        add_option( TOCG_OPTIONS_KEY, [
            'title'         => 'Table of Contents',
            'min_headings'  => 3,
            'include_h3'    => true,
            'post_types'    => [ 'post', 'page' ],
        ] );
    }
} );
