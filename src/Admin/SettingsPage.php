<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Support\Settings;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$opts = Settings::all();

		View::open_wrap();
		View::header();
		View::nav( AdminBoot::PAGE_SLUG . '-settings' );
		?>
		<form action="options.php" method="post">
			<?php settings_fields( AdminBoot::SETTINGS_GROUP ); ?>

			<div class="rm-ca-section">
				<div class="rm-ca-section__head">
					<h3 class="rm-ca-section__title"><?php esc_html_e( 'Plugin status', 'richardmedina-crm-automation' ); ?></h3>
				</div>
				<div class="rm-ca-section__body">
					<div class="rm-ca-row">
						<div class="rm-ca-row__main">
							<label class="rm-ca-row__label" for="rm_ca_enabled"><?php esc_html_e( 'Master switch', 'richardmedina-crm-automation' ); ?></label>
							<p class="rm-ca-row__desc"><?php esc_html_e( 'When off, no submissions are dispatched. Existing queue items still process.', 'richardmedina-crm-automation' ); ?></p>
						</div>
						<div class="rm-ca-row__control">
							<label class="rm-ca-switch">
								<input type="checkbox" id="rm_ca_enabled" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[enabled]" value="1" <?php checked( ! empty( $opts['enabled'] ) ); ?> />
								<span class="rm-ca-switch__slider" aria-hidden="true"></span>
							</label>
						</div>
					</div>
					<div class="rm-ca-row">
						<div class="rm-ca-row__main">
							<label class="rm-ca-row__label" for="rm_ca_debug"><?php esc_html_e( 'Debug mode', 'richardmedina-crm-automation' ); ?></label>
							<p class="rm-ca-row__desc"><?php esc_html_e( 'Verbose info-level logging to wp-content/uploads/rm-ca-logs/. Errors and warnings always log regardless.', 'richardmedina-crm-automation' ); ?></p>
						</div>
						<div class="rm-ca-row__control">
							<label class="rm-ca-switch">
								<input type="checkbox" id="rm_ca_debug" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[debug_mode]" value="1" <?php checked( ! empty( $opts['debug_mode'] ) ); ?> />
								<span class="rm-ca-switch__slider" aria-hidden="true"></span>
							</label>
						</div>
					</div>
				</div>
			</div>

			<div class="rm-ca-section">
				<div class="rm-ca-section__head">
					<h3 class="rm-ca-section__title"><?php esc_html_e( 'Queue', 'richardmedina-crm-automation' ); ?></h3>
				</div>
				<div class="rm-ca-section__body">
					<div class="rm-ca-field">
						<label class="rm-ca-field__label" for="rm_ca_max_attempts"><?php esc_html_e( 'Max retry attempts', 'richardmedina-crm-automation' ); ?></label>
						<input type="number" min="1" max="20" id="rm_ca_max_attempts" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[max_attempts]" value="<?php echo esc_attr( (string) $opts['max_attempts'] ); ?>" />
						<p class="rm-ca-field__desc"><?php esc_html_e( 'Backoff schedule: 1m → 5m → 30m → 2h → 6h. After max attempts the job is marked dead.', 'richardmedina-crm-automation' ); ?></p>
					</div>
					<div class="rm-ca-field">
						<label class="rm-ca-field__label" for="rm_ca_log_retention"><?php esc_html_e( 'Log retention (days)', 'richardmedina-crm-automation' ); ?></label>
						<input type="number" min="1" max="365" id="rm_ca_log_retention" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[log_retention_days]" value="<?php echo esc_attr( (string) $opts['log_retention_days'] ); ?>" />
					</div>
				</div>
			</div>

			<div class="rm-ca-form-actions">
				<?php submit_button( __( 'Save changes', 'richardmedina-crm-automation' ), 'primary', 'submit', false ); ?>
			</div>
		</form>
		<?php
		View::footer();
		View::close_wrap();
	}
}
