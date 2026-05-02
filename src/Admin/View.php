<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Pipeline\Queue;
use RichardMedina\CrmAutomation\Rules\Repository as RuleRepository;
use RichardMedina\CrmAutomation\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Shared view helpers — branded header strip, footer, pills, toggle markup.
 * Same design language as richardmedina-security-hardening.
 */
final class View {

	public static function header(): void {
		$enabled = ! empty( Settings::get( 'enabled', true ) );
		$rules   = RuleRepository::all();
		$active  = 0;
		foreach ( $rules as $r ) {
			if ( $r->enabled ) {
				$active++;
			}
		}
		$queue = Queue::counts_by_status();
		$pending = (int) ( $queue['pending'] ?? 0 );
		$dead    = (int) ( $queue['dead'] ?? 0 );

		[ $sclass, $slabel ] = $enabled
			? [ 'rm-ca-pill--success', __( 'Enabled', 'richardmedina-crm-automation' ) ]
			: [ 'rm-ca-pill--danger', __( 'Disabled', 'richardmedina-crm-automation' ) ];
		?>
		<div class="rm-ca-header">
			<div class="rm-ca-header__brand">
				<span class="rm-ca-header__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"/><path d="M12 3v18"/><circle cx="6" cy="6" r="2"/><circle cx="18" cy="18" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="18" r="2"/></svg>
				</span>
				<div>
					<h2 class="rm-ca-header__title"><?php esc_html_e( 'RichardMedina CRM Automation', 'richardmedina-crm-automation' ); ?></h2>
					<p class="rm-ca-header__sub"><?php esc_html_e( 'Form submissions → CRMs, with field mapping, retries, and a log.', 'richardmedina-crm-automation' ); ?></p>
				</div>
			</div>
			<div class="rm-ca-header__meta">
				<span class="rm-ca-pill rm-ca-pill--neutral">v<?php echo esc_html( RM_CA_VERSION ); ?></span>
				<span class="rm-ca-pill rm-ca-pill--info"><?php echo esc_html( sprintf( __( '%d active rule(s)', 'richardmedina-crm-automation' ), $active ) ); ?></span>
				<?php if ( $pending > 0 ) : ?>
					<span class="rm-ca-pill rm-ca-pill--warning"><?php echo esc_html( sprintf( __( 'Queue: %d pending', 'richardmedina-crm-automation' ), $pending ) ); ?></span>
				<?php endif; ?>
				<?php if ( $dead > 0 ) : ?>
					<span class="rm-ca-pill rm-ca-pill--danger"><?php echo esc_html( sprintf( __( '%d dead', 'richardmedina-crm-automation' ), $dead ) ); ?></span>
				<?php endif; ?>
				<span class="rm-ca-pill <?php echo esc_attr( $sclass ); ?>"><span class="rm-ca-pill__dot"></span><?php echo esc_html( $slabel ); ?></span>
			</div>
		</div>
		<?php
	}

	public static function footer(): void {
		?>
		<div class="rm-ca-footer">
			<span><?php echo esc_html( sprintf( __( 'RichardMedina CRM Automation v%s', 'richardmedina-crm-automation' ), RM_CA_VERSION ) ); ?></span>
			<span><a href="https://richardmedina.com.au" target="_blank" rel="noopener">richardmedina.com.au</a></span>
		</div>
		<?php
	}

	public static function nav( string $active ): void {
		$tabs = [
			AdminBoot::PAGE_SLUG                  => __( 'Rules', 'richardmedina-crm-automation' ),
			AdminBoot::PAGE_SLUG . '-destinations' => __( 'Destinations', 'richardmedina-crm-automation' ),
			AdminBoot::PAGE_SLUG . '-logs'         => __( 'Logs', 'richardmedina-crm-automation' ),
			AdminBoot::PAGE_SLUG . '-settings'     => __( 'Settings', 'richardmedina-crm-automation' ),
		];
		?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $tabs as $slug => $label ) :
				$url   = admin_url( 'admin.php?page=' . $slug );
				$class = 'nav-tab' . ( $active === $slug ? ' nav-tab-active' : '' );
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</h2>
		<?php
	}

	public static function open_wrap(): void {
		echo '<div class="wrap rm-ca-wrap">';
	}

	public static function close_wrap(): void {
		echo '</div>';
	}
}
