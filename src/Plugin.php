<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation;

use RichardMedina\CrmAutomation\Admin\AdminBoot;
use RichardMedina\CrmAutomation\Pipeline\Worker;
use RichardMedina\CrmAutomation\Sources\GravityForms\Source as GravitySource;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public const CRON_HOOK = 'rm_ca_run_worker';

	private static ?Plugin $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		add_action( 'init', static function (): void {
			load_plugin_textdomain( 'richardmedina-crm-automation', false, dirname( plugin_basename( RM_CA_FILE ) ) . '/languages' );
		} );

		( new AdminBoot() )->register();

		if ( ! $this->dependencies_satisfied() ) {
			return;
		}

		( new GravitySource() )->register();

		add_action( self::CRON_HOOK, [ Worker::class, 'run' ] );

		// Make sure the recurring queue worker is scheduled (every minute).
		add_action( 'init', static function (): void {
			if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( time() + 30, 'rm_ca_minute', self::CRON_HOOK );
			}
		} );

		add_filter( 'cron_schedules', static function ( array $schedules ): array {
			if ( ! isset( $schedules['rm_ca_minute'] ) ) {
				$schedules['rm_ca_minute'] = [
					'interval' => 60,
					'display'  => __( 'Every minute (RM CRM Automation)', 'richardmedina-crm-automation' ),
				];
			}
			return $schedules;
		} );
	}

	public function dependencies_satisfied(): bool {
		return class_exists( 'GFAPI' );
	}

	private function __construct() {}
	private function __clone() {}
}
