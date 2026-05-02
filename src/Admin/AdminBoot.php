<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Plugin;
use RichardMedina\CrmAutomation\Support\Settings;

defined( 'ABSPATH' ) || exit;

final class AdminBoot {

	public const PAGE_SLUG = 'rm-ca';
	public const SETTINGS_GROUP = 'rm_ca_settings_group';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( RM_CA_FILE ), [ $this, 'add_action_links' ] );

		( new RulesController() )->register();
		( new DestinationsController() )->register();
		( new LogsController() )->register();

		add_action( 'admin_notices', [ $this, 'maybe_dependency_notice' ] );
	}

	public function maybe_dependency_notice(): void {
		if ( Plugin::instance()->dependencies_satisfied() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$on_us  = $screen && isset( $screen->id ) && str_contains( (string) $screen->id, self::PAGE_SLUG );
		if ( ! $on_us ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>RichardMedina CRM Automation:</strong> ';
		echo esc_html__( 'Gravity Forms is required for v0.1. Install and activate Gravity Forms to enable form capture.', 'richardmedina-crm-automation' );
		echo '</p></div>';
	}

	public function add_action_links( array $links ): array {
		$url      = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$settings = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Rules', 'richardmedina-crm-automation' ) );
		array_unshift( $links, $settings );
		return $links;
	}

	public function enqueue( string $hook ): void {
		if ( ! str_contains( $hook, self::PAGE_SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'rm-ca-admin', RM_CA_URL . 'assets/admin/admin.css', [], RM_CA_VERSION );
		wp_enqueue_script( 'rm-ca-admin', RM_CA_URL . 'assets/admin/admin.js', [], RM_CA_VERSION, true );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'RichardMedina CRM Automation', 'richardmedina-crm-automation' ),
			__( 'RM Automation', 'richardmedina-crm-automation' ),
			'manage_options',
			self::PAGE_SLUG,
			[ RulesPage::class, 'render' ],
			'dashicons-randomize',
			58
		);
		add_submenu_page( self::PAGE_SLUG, __( 'Rules', 'richardmedina-crm-automation' ),       __( 'Rules', 'richardmedina-crm-automation' ),       'manage_options', self::PAGE_SLUG,                       [ RulesPage::class, 'render' ] );
		add_submenu_page( self::PAGE_SLUG, __( 'Destinations', 'richardmedina-crm-automation' ),__( 'Destinations', 'richardmedina-crm-automation' ),'manage_options', self::PAGE_SLUG . '-destinations',   [ DestinationsPage::class, 'render' ] );
		add_submenu_page( self::PAGE_SLUG, __( 'Logs', 'richardmedina-crm-automation' ),        __( 'Logs', 'richardmedina-crm-automation' ),        'manage_options', self::PAGE_SLUG . '-logs',           [ LogsPage::class, 'render' ] );
		add_submenu_page( self::PAGE_SLUG, __( 'Settings', 'richardmedina-crm-automation' ),    __( 'Settings', 'richardmedina-crm-automation' ),    'manage_options', self::PAGE_SLUG . '-settings',       [ SettingsPage::class, 'render' ] );
	}

	public function register_settings(): void {
		register_setting( self::SETTINGS_GROUP, Settings::OPTION_KEY, [
			'type'              => 'array',
			'sanitize_callback' => [ Settings::class, 'sanitize' ],
			'default'           => Settings::defaults(),
		] );
	}
}
