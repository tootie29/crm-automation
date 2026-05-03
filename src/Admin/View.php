<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Pipeline\Queue;
use RichardMedina\CrmAutomation\Rules\Repository as RuleRepository;
use RichardMedina\CrmAutomation\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Shared view helpers — branded header, sidebar nav, shell, footer.
 * Same design language as richardmedina-security-hardening v0.2.0.
 *
 * Layout each page produces (zero per-page edits required for the redesign):
 *   .wrap.rm-ca-wrap
 *     .rm-ca-pageheader     (header())
 *     .rm-ca-shell          (nav() opens this)
 *       .rm-ca-nav          (sidebar)
 *       .rm-ca-main         (nav() opens this; footer() closes it)
 *     .rm-ca-footer         (footer())
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
		$queue   = Queue::counts_by_status();
		$pending = (int) ( $queue['pending'] ?? 0 );
		$dead    = (int) ( $queue['dead'] ?? 0 );

		[ $sclass, $slabel ] = $enabled
			? [ 'rm-ca-pill--success', __( 'Enabled', 'richardmedina-crm-automation' ) ]
			: [ 'rm-ca-pill--danger', __( 'Disabled', 'richardmedina-crm-automation' ) ];
		?>
		<header class="rm-ca-pageheader">
			<div class="rm-ca-pageheader__brand">
				<span class="rm-ca-pageheader__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"/><path d="M12 3v18"/><circle cx="6" cy="6" r="2"/><circle cx="18" cy="18" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="18" r="2"/></svg>
				</span>
				<div class="rm-ca-pageheader__text">
					<p class="rm-ca-pageheader__eyebrow"><?php esc_html_e( 'RichardMedina', 'richardmedina-crm-automation' ); ?></p>
					<h2 class="rm-ca-pageheader__title"><?php esc_html_e( 'CRM Automation', 'richardmedina-crm-automation' ); ?></h2>
					<p class="rm-ca-pageheader__sub"><?php esc_html_e( 'Form submissions → CRMs, with field mapping, retries, and a log.', 'richardmedina-crm-automation' ); ?></p>
				</div>
			</div>
			<div class="rm-ca-pageheader__meta">
				<span class="rm-ca-pill rm-ca-pill--neutral rm-ca-pill--ghost"><?php echo esc_html( 'v' . RM_CA_VERSION ); ?></span>
				<span class="rm-ca-pill rm-ca-pill--info" title="<?php esc_attr_e( 'Active mapping rules', 'richardmedina-crm-automation' ); ?>">
					<?php echo esc_html( sprintf( _n( '%d active rule', '%d active rules', $active, 'richardmedina-crm-automation' ), $active ) ); ?>
				</span>
				<?php if ( $pending > 0 ) : ?>
					<span class="rm-ca-pill rm-ca-pill--warning" title="<?php esc_attr_e( 'Queue depth', 'richardmedina-crm-automation' ); ?>"><?php echo esc_html( sprintf( __( '%d pending', 'richardmedina-crm-automation' ), $pending ) ); ?></span>
				<?php endif; ?>
				<?php if ( $dead > 0 ) : ?>
					<span class="rm-ca-pill rm-ca-pill--danger" title="<?php esc_attr_e( 'Permanently failed jobs', 'richardmedina-crm-automation' ); ?>"><?php echo esc_html( sprintf( __( '%d dead', 'richardmedina-crm-automation' ), $dead ) ); ?></span>
				<?php endif; ?>
				<span class="rm-ca-pill <?php echo esc_attr( $sclass ); ?>" title="<?php esc_attr_e( 'Plugin status', 'richardmedina-crm-automation' ); ?>"><span class="rm-ca-pill__dot"></span><?php echo esc_html( $slabel ); ?></span>
			</div>
		</header>
		<?php
	}

	/**
	 * Sidebar nav — opens the shell + main column.
	 * footer() must be called to close them.
	 */
	public static function nav( string $active ): void {
		$tabs = [
			AdminBoot::PAGE_SLUG                   => [
				'label' => __( 'Rules', 'richardmedina-crm-automation' ),
				'icon'  => '<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
			],
			AdminBoot::PAGE_SLUG . '-destinations' => [
				'label' => __( 'Destinations', 'richardmedina-crm-automation' ),
				'icon'  => '<circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/>',
			],
			AdminBoot::PAGE_SLUG . '-logs'         => [
				'label' => __( 'Logs', 'richardmedina-crm-automation' ),
				'icon'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/>',
			],
			AdminBoot::PAGE_SLUG . '-settings'     => [
				'label' => __( 'Settings', 'richardmedina-crm-automation' ),
				'icon'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.36.16.66.4.88.7l.04.06a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
			],
		];

		echo '<div class="rm-ca-shell">';
		?>
		<aside class="rm-ca-nav" aria-label="<?php esc_attr_e( 'CRM Automation sections', 'richardmedina-crm-automation' ); ?>">
			<ul class="rm-ca-nav__list">
				<?php foreach ( $tabs as $slug => $tab ) :
					$url       = admin_url( 'admin.php?page=' . $slug );
					$is_active = ( $active === $slug );
					?>
					<li class="rm-ca-nav__item">
						<a
							href="<?php echo esc_url( $url ); ?>"
							class="rm-ca-nav__link<?php echo $is_active ? ' is-active' : ''; ?>"
							<?php echo $is_active ? 'aria-current="page"' : ''; ?>
						>
							<span class="rm-ca-nav__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $tab['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></svg>
							</span>
							<span class="rm-ca-nav__label"><?php echo esc_html( $tab['label'] ); ?></span>
							<span class="rm-ca-nav__chevron" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="rm-ca-nav__footer">
				<p class="rm-ca-nav__footer-label"><?php esc_html_e( 'Need help?', 'richardmedina-crm-automation' ); ?></p>
				<a class="rm-ca-nav__footer-link" href="https://richardmedina.com.au/plugins/richardmedina-crm-automation" target="_blank" rel="noopener">
					<?php esc_html_e( 'Documentation', 'richardmedina-crm-automation' ); ?>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7"/><polyline points="9 7 17 7 17 15"/></svg>
				</a>
			</div>
		</aside>
		<main class="rm-ca-main">
		<?php
	}

	public static function footer(): void {
		// Close .rm-ca-main and .rm-ca-shell that nav() opened.
		echo '</main></div>';
		?>
		<div class="rm-ca-footer">
			<span><?php echo esc_html( sprintf( __( 'RichardMedina CRM Automation v%s', 'richardmedina-crm-automation' ), RM_CA_VERSION ) ); ?></span>
			<span><a href="https://richardmedina.com.au" target="_blank" rel="noopener">richardmedina.com.au</a></span>
		</div>
		<?php
	}

	public static function open_wrap(): void {
		echo '<div class="wrap rm-ca-wrap rm-ca-wrap--v2">';
	}

	public static function close_wrap(): void {
		echo '</div>';
	}
}
