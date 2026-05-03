<?php
/**
 * Plugin Name:       RichardMedina CRM Automation
 * Plugin URI:        https://richardmedina.com.au/plugins/richardmedina-crm-automation
 * Description:       Pipes form submissions (Gravity Forms in v0.1) into external CRMs (GoHighLevel + generic webhook in v0.1) with field mapping, async queue, retries, and an audit log.
 * Version:           0.2.0-beta
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Richard Medina
 * Author URI:        https://richardmedina.com.au
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       richardmedina-crm-automation
 * Domain Path:       /languages
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'RM_CA_VERSION', '0.2.0-beta' );
define( 'RM_CA_FILE', __FILE__ );
define( 'RM_CA_DIR', plugin_dir_path( __FILE__ ) );
define( 'RM_CA_URL', plugin_dir_url( __FILE__ ) );
define( 'RM_CA_SLUG', 'richardmedina-crm-automation' );

if ( is_multisite() ) {
	add_action( 'admin_notices', static function (): void {
		echo '<div class="notice notice-error"><p><strong>RichardMedina CRM Automation</strong> does not support multisite in v0.1.</p></div>';
	} );
	return;
}

require_once RM_CA_DIR . 'src/Autoloader.php';
\RichardMedina\CrmAutomation\Autoloader::register();

register_activation_hook( __FILE__, [ \RichardMedina\CrmAutomation\Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \RichardMedina\CrmAutomation\Deactivator::class, 'deactivate' ] );

// Boot at priority 20 so most other plugins (Gravity Forms in particular) have already loaded.
add_action( 'plugins_loaded', static function (): void {
	\RichardMedina\CrmAutomation\Plugin::instance()->boot();
}, 20 );
