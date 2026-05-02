<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Support\Encryption;
use RichardMedina\CrmAutomation\Support\Settings;

defined( 'ABSPATH' ) || exit;

final class DestinationsPage {

	public const NONCE = 'rm_ca_save_destinations';

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$ghl       = Settings::destination( 'gohighlevel' );
		$has_token = ! empty( $ghl['token_enc'] );
		$mask      = $has_token ? Encryption::mask( Encryption::decrypt( (string) $ghl['token_enc'] ) ) : '';

		View::open_wrap();
		View::header();
		View::nav( AdminBoot::PAGE_SLUG . '-destinations' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( self::NONCE ); ?>
			<input type="hidden" name="action" value="rm_ca_save_destinations" />

			<div class="rm-ca-section">
				<div class="rm-ca-section__head">
					<h3 class="rm-ca-section__title"><?php esc_html_e( 'GoHighLevel', 'richardmedina-crm-automation' ); ?></h3>
					<p class="rm-ca-section__sub"><?php esc_html_e( 'Connect a sub-account using a Private Integration Token (recommended for agency setups).', 'richardmedina-crm-automation' ); ?></p>
				</div>
				<div class="rm-ca-section__body">
					<div class="rm-ca-field">
						<label class="rm-ca-field__label" for="rm_ca_ghl_location"><?php esc_html_e( 'Location ID', 'richardmedina-crm-automation' ); ?></label>
						<input type="text" id="rm_ca_ghl_location" name="ghl[location_id]" value="<?php echo esc_attr( (string) ( $ghl['location_id'] ?? '' ) ); ?>" />
						<p class="rm-ca-field__desc"><?php esc_html_e( 'Found in GHL under Settings → Company → Location ID, or in the URL of any sub-account view.', 'richardmedina-crm-automation' ); ?></p>
					</div>
					<div class="rm-ca-field">
						<label class="rm-ca-field__label" for="rm_ca_ghl_token"><?php esc_html_e( 'Private Integration Token', 'richardmedina-crm-automation' ); ?></label>
						<input type="password" id="rm_ca_ghl_token" name="ghl[token]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $has_token ? sprintf( __( 'Token saved (%s) — leave blank to keep, or paste a new one to replace', 'richardmedina-crm-automation' ), $mask ) : __( 'pit-...', 'richardmedina-crm-automation' ) ); ?>" />
						<p class="rm-ca-field__desc">
							<?php esc_html_e( 'In GHL: Settings → Private Integrations → Create new integration → grant Contacts Write scope. Stored encrypted in the database.', 'richardmedina-crm-automation' ); ?>
						</p>
					</div>
				</div>
			</div>

			<div class="rm-ca-section">
				<div class="rm-ca-section__head">
					<h3 class="rm-ca-section__title"><?php esc_html_e( 'Generic webhook', 'richardmedina-crm-automation' ); ?></h3>
				</div>
				<div class="rm-ca-section__body">
					<p class="rm-ca-field__desc">
						<?php esc_html_e( 'No global credentials. Webhook URL + optional shared secret are configured per rule on the rule editor screen.', 'richardmedina-crm-automation' ); ?>
					</p>
				</div>
			</div>

			<div class="rm-ca-form-actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save destinations', 'richardmedina-crm-automation' ); ?></button>
			</div>
		</form>
		<?php
		View::footer();
		View::close_wrap();
	}
}
