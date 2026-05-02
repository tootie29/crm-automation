<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Destinations\Registry as DestinationRegistry;
use RichardMedina\CrmAutomation\Rules\Repository as RuleRepository;
use RichardMedina\CrmAutomation\Rules\Rule;
use RichardMedina\CrmAutomation\Sources\GravityForms\Source as GravitySource;

defined( 'ABSPATH' ) || exit;

final class RuleEditor {

	public const NONCE = 'rm_ca_save_rule';

	public static function render(): void {
		$rule_id = isset( $_GET['rule_id'] ) ? (int) $_GET['rule_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rule    = $rule_id > 0 ? RuleRepository::find( $rule_id ) : null;

		$source       = new GravitySource();
		$forms        = $source->list();
		$destinations = DestinationRegistry::all();

		$selected_form = $rule ? $rule->source_id : '';
		$selected_dest = $rule ? $rule->destination_type : 'gohighlevel';
		$mapping       = $rule ? $rule->mapping : [];
		$options       = $rule ? $rule->options : [];

		$source_fields = $selected_form !== '' ? $source->describe_fields( $selected_form ) : [];
		$dest_object   = $destinations[ $selected_dest ] ?? null;
		$dest_fields   = $dest_object ? $dest_object->target_fields() : [];

		$action_url = admin_url( 'admin-post.php' );
		$cancel_url = admin_url( 'admin.php?page=' . AdminBoot::PAGE_SLUG );
		?>
		<form method="post" action="<?php echo esc_url( $action_url ); ?>">
			<?php wp_nonce_field( self::NONCE ); ?>
			<input type="hidden" name="action" value="rm_ca_save_rule" />
			<input type="hidden" name="rule_id" value="<?php echo esc_attr( (string) ( $rule->id ?? 0 ) ); ?>" />

			<div class="rm-ca-section">
				<div class="rm-ca-section__head">
					<h3 class="rm-ca-section__title"><?php echo $rule ? esc_html__( 'Edit rule', 'richardmedina-crm-automation' ) : esc_html__( 'New rule', 'richardmedina-crm-automation' ); ?></h3>
					<p class="rm-ca-section__sub"><?php esc_html_e( 'Pick a form, pick a destination, then map fields.', 'richardmedina-crm-automation' ); ?></p>
				</div>
				<div class="rm-ca-section__body">

					<div class="rm-ca-field">
						<label class="rm-ca-field__label" for="rm_ca_rule_name"><?php esc_html_e( 'Rule name', 'richardmedina-crm-automation' ); ?></label>
						<input type="text" id="rm_ca_rule_name" name="name" value="<?php echo esc_attr( $rule->name ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Contact form → GHL leads', 'richardmedina-crm-automation' ); ?>" required />
					</div>

					<div class="rm-ca-field">
						<label class="rm-ca-field__label"><?php esc_html_e( 'Enabled', 'richardmedina-crm-automation' ); ?></label>
						<label class="rm-ca-switch">
							<input type="checkbox" name="enabled" value="1" <?php checked( $rule ? $rule->enabled : true ); ?> />
							<span class="rm-ca-switch__slider" aria-hidden="true"></span>
						</label>
					</div>

					<div class="rm-ca-field">
						<label class="rm-ca-field__label" for="rm_ca_form_id"><?php esc_html_e( 'Source: Gravity Form', 'richardmedina-crm-automation' ); ?></label>
						<select id="rm_ca_form_id" name="source_id" required>
							<option value=""><?php esc_html_e( '— Select a form —', 'richardmedina-crm-automation' ); ?></option>
							<?php foreach ( $forms as $f ) : ?>
								<option value="<?php echo esc_attr( $f['id'] ); ?>" <?php selected( $selected_form, $f['id'] ); ?>>
									<?php echo esc_html( $f['label'] . ' (#' . $f['id'] . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<input type="hidden" name="source_type" value="<?php echo esc_attr( GravitySource::TYPE ); ?>" />
						<p class="rm-ca-field__desc"><?php esc_html_e( 'Save the rule to refresh available form fields below.', 'richardmedina-crm-automation' ); ?></p>
					</div>

					<div class="rm-ca-field">
						<label class="rm-ca-field__label" for="rm_ca_dest_type"><?php esc_html_e( 'Destination', 'richardmedina-crm-automation' ); ?></label>
						<select id="rm_ca_dest_type" name="destination_type" required>
							<?php foreach ( $destinations as $type => $d ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $selected_dest, $type ); ?>>
									<?php echo esc_html( $d->label() ); ?>
									<?php echo $d->configured() ? '' : esc_html__( ' — not configured', 'richardmedina-crm-automation' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>

			<?php if ( $selected_dest === 'webhook' ) : ?>
				<div class="rm-ca-section">
					<div class="rm-ca-section__head">
						<h3 class="rm-ca-section__title"><?php esc_html_e( 'Webhook options', 'richardmedina-crm-automation' ); ?></h3>
					</div>
					<div class="rm-ca-section__body">
						<div class="rm-ca-field">
							<label class="rm-ca-field__label" for="rm_ca_webhook_url"><?php esc_html_e( 'Webhook URL', 'richardmedina-crm-automation' ); ?></label>
							<input type="url" id="rm_ca_webhook_url" name="options[webhook_url]" value="<?php echo esc_attr( (string) ( $options['webhook_url'] ?? '' ) ); ?>" placeholder="https://hooks.example.com/incoming/abc123" />
							<p class="rm-ca-field__desc"><?php esc_html_e( 'Where to POST a JSON payload. Compatible with Zapier, Make, n8n, custom servers.', 'richardmedina-crm-automation' ); ?></p>
						</div>
						<div class="rm-ca-field">
							<label class="rm-ca-field__label" for="rm_ca_webhook_secret"><?php esc_html_e( 'Shared secret (optional)', 'richardmedina-crm-automation' ); ?></label>
							<input type="text" id="rm_ca_webhook_secret" name="options[webhook_secret]" value="<?php echo esc_attr( (string) ( $options['webhook_secret'] ?? '' ) ); ?>" />
							<p class="rm-ca-field__desc"><?php esc_html_e( 'When set, payload is HMAC-signed in the X-RM-CA-Signature header (sha256=…).', 'richardmedina-crm-automation' ); ?></p>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $selected_dest === 'gohighlevel' ) : ?>
				<div class="rm-ca-section">
					<div class="rm-ca-section__head">
						<h3 class="rm-ca-section__title"><?php esc_html_e( 'GoHighLevel options', 'richardmedina-crm-automation' ); ?></h3>
					</div>
					<div class="rm-ca-section__body">
						<div class="rm-ca-field">
							<label class="rm-ca-field__label" for="rm_ca_ghl_source"><?php esc_html_e( 'Lead source (overrides mapping if set)', 'richardmedina-crm-automation' ); ?></label>
							<input type="text" id="rm_ca_ghl_source" name="options[source]" value="<?php echo esc_attr( (string) ( $options['source'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Contact form, Free quote', 'richardmedina-crm-automation' ); ?>" />
						</div>
						<div class="rm-ca-field">
							<label class="rm-ca-field__label" for="rm_ca_ghl_tags"><?php esc_html_e( 'Tags (comma-separated, applied to every contact)', 'richardmedina-crm-automation' ); ?></label>
							<input type="text" id="rm_ca_ghl_tags" name="options[tags]" value="<?php echo esc_attr( (string) ( $options['tags'] ?? '' ) ); ?>" placeholder="lead,wp-form,contact" />
						</div>
						<div class="rm-ca-field">
							<label class="rm-ca-field__label" for="rm_ca_ghl_tag_prefix"><?php esc_html_e( 'Tag prefix (optional, namespaces this rule\'s tags)', 'richardmedina-crm-automation' ); ?></label>
							<input type="text" id="rm_ca_ghl_tag_prefix" name="options[tag_prefix]" value="<?php echo esc_attr( (string) ( $options['tag_prefix'] ?? '' ) ); ?>" placeholder="enquiry:" />
							<p class="rm-ca-field__desc">
								<?php esc_html_e( 'Prepended verbatim to every tag this rule sends (both the static tags above and any tags mapped from form fields). Example: with prefix "enquiry:" and a mapped tag value "booking", GHL receives "enquiry:booking". Useful when multiple sources tag contacts and you want to filter by origin.', 'richardmedina-crm-automation' ); ?>
							</p>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="rm-ca-section">
				<div class="rm-ca-section__head">
					<h3 class="rm-ca-section__title"><?php esc_html_e( 'Field mapping', 'richardmedina-crm-automation' ); ?></h3>
					<p class="rm-ca-section__sub"><?php esc_html_e( 'Map each destination field to a form field, or to a static value.', 'richardmedina-crm-automation' ); ?></p>
				</div>
				<div class="rm-ca-section__body">
					<?php if ( empty( $source_fields ) ) : ?>
						<p class="description"><?php esc_html_e( 'Select a form and save the rule to see source fields here.', 'richardmedina-crm-automation' ); ?></p>
					<?php else : ?>
						<table class="widefat rm-ca-mapping">
							<thead>
								<tr>
									<th style="width:30%;"><?php esc_html_e( 'Destination field', 'richardmedina-crm-automation' ); ?></th>
									<th style="width:18%;"><?php esc_html_e( 'Mode', 'richardmedina-crm-automation' ); ?></th>
									<th><?php esc_html_e( 'Source field / static value', 'richardmedina-crm-automation' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $dest_fields as $df ) :
									$key  = $df['key'];
									$spec = $mapping[ $key ] ?? [ 'mode' => 'field', 'value' => '' ];
									?>
									<tr>
										<td>
											<strong><?php echo esc_html( $df['label'] ); ?></strong>
											<?php if ( ! empty( $df['required'] ) ) : ?>
												<span style="color:#b32d2e;"> *</span>
											<?php endif; ?>
											<br>
											<code><?php echo esc_html( $key ); ?></code>
											<?php if ( ! empty( $df['description'] ) ) : ?>
												<p class="description"><?php echo esc_html( $df['description'] ); ?></p>
											<?php endif; ?>
										</td>
										<td>
											<select name="mapping[<?php echo esc_attr( $key ); ?>][mode]">
												<option value="field"  <?php selected( $spec['mode'], 'field' ); ?>><?php esc_html_e( 'Form field', 'richardmedina-crm-automation' ); ?></option>
												<option value="static" <?php selected( $spec['mode'], 'static' ); ?>><?php esc_html_e( 'Static value', 'richardmedina-crm-automation' ); ?></option>
											</select>
										</td>
										<td>
											<select name="mapping[<?php echo esc_attr( $key ); ?>][value]" data-mode-target="<?php echo esc_attr( $key ); ?>" class="rm-ca-mapping-field">
												<option value=""><?php esc_html_e( '— No mapping —', 'richardmedina-crm-automation' ); ?></option>
												<?php foreach ( $source_fields as $sf ) : ?>
													<option value="<?php echo esc_attr( $sf['key'] ); ?>" <?php selected( $spec['mode'] === 'field' ? $spec['value'] : '', $sf['key'] ); ?>>
														<?php echo esc_html( $sf['label'] . ' [' . $sf['key'] . ']' ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<input type="text" name="mapping[<?php echo esc_attr( $key ); ?>][static_value]" class="rm-ca-mapping-static" value="<?php echo esc_attr( $spec['mode'] === 'static' ? $spec['value'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Static value', 'richardmedina-crm-automation' ); ?>" />
										</td>
									</tr>
								<?php endforeach; ?>

								<?php
								// Custom field rows (cf:foo) the user has saved before.
								$custom_keys = [];
								foreach ( $mapping as $key => $spec ) {
									if ( is_string( $key ) && str_starts_with( $key, 'cf:' ) ) {
										$custom_keys[ $key ] = $spec;
									}
								}
								if ( ! empty( $custom_keys ) ) {
									foreach ( $custom_keys as $key => $spec ) :
										?>
										<tr>
											<td>
												<strong><?php echo esc_html( __( 'Custom field', 'richardmedina-crm-automation' ) ); ?></strong>
												<br>
												<code><?php echo esc_html( $key ); ?></code>
											</td>
											<td>
												<select name="mapping[<?php echo esc_attr( $key ); ?>][mode]">
													<option value="field"  <?php selected( $spec['mode'], 'field' ); ?>>Form field</option>
													<option value="static" <?php selected( $spec['mode'], 'static' ); ?>>Static value</option>
												</select>
											</td>
											<td>
												<select name="mapping[<?php echo esc_attr( $key ); ?>][value]" class="rm-ca-mapping-field">
													<option value="">— No mapping —</option>
													<?php foreach ( $source_fields as $sf ) : ?>
														<option value="<?php echo esc_attr( $sf['key'] ); ?>" <?php selected( $spec['mode'] === 'field' ? $spec['value'] : '', $sf['key'] ); ?>>
															<?php echo esc_html( $sf['label'] . ' [' . $sf['key'] . ']' ); ?>
														</option>
													<?php endforeach; ?>
												</select>
												<input type="text" name="mapping[<?php echo esc_attr( $key ); ?>][static_value]" class="rm-ca-mapping-static" value="<?php echo esc_attr( $spec['mode'] === 'static' ? $spec['value'] : '' ); ?>" placeholder="Static value" />
											</td>
										</tr>
										<?php
									endforeach;
								}
								?>
							</tbody>
						</table>

						<p class="rm-ca-field__desc">
							<?php esc_html_e( 'Need a custom field? Add a row by typing its GHL custom-field key (without "cf:") and a source field id below — supports per-location custom fields.', 'richardmedina-crm-automation' ); ?>
						</p>
						<div class="rm-ca-custom-add">
							<input type="text" name="custom_key" placeholder="<?php esc_attr_e( 'GHL custom field key (e.g. account_type)', 'richardmedina-crm-automation' ); ?>" />
							<select name="custom_source">
								<option value="">— Form field —</option>
								<?php foreach ( $source_fields as $sf ) : ?>
									<option value="<?php echo esc_attr( $sf['key'] ); ?>"><?php echo esc_html( $sf['label'] . ' [' . $sf['key'] . ']' ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="rm-ca-form-actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save rule', 'richardmedina-crm-automation' ); ?></button>
				<a href="<?php echo esc_url( $cancel_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'richardmedina-crm-automation' ); ?></a>
			</div>
		</form>
		<?php
	}
}
