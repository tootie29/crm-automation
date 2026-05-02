<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Destinations\Registry as DestinationRegistry;
use RichardMedina\CrmAutomation\Rules\Repository as RuleRepository;
use RichardMedina\CrmAutomation\Sources\GravityForms\Source as GravitySource;

defined( 'ABSPATH' ) || exit;

final class RulesPage {

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$action = isset( $_GET['action'] ) ? sanitize_key( (string) $_GET['action'] ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		View::open_wrap();
		View::header();
		View::nav( AdminBoot::PAGE_SLUG );

		if ( $action === 'edit' || $action === 'new' ) {
			RuleEditor::render();
		} else {
			self::render_list();
		}

		View::footer();
		View::close_wrap();
	}

	private static function render_list(): void {
		$rules        = RuleRepository::all();
		$source       = new GravitySource();
		$forms_by_id  = [];
		foreach ( $source->list() as $f ) {
			$forms_by_id[ $f['id'] ] = $f['label'];
		}

		$new_url = add_query_arg( [ 'page' => AdminBoot::PAGE_SLUG, 'action' => 'new' ], admin_url( 'admin.php' ) );
		?>
		<div class="rm-ca-section">
			<div class="rm-ca-section__head">
				<h3 class="rm-ca-section__title"><?php esc_html_e( 'Rules', 'richardmedina-crm-automation' ); ?></h3>
				<p class="rm-ca-section__sub"><?php esc_html_e( 'Each rule maps a form to a destination with a field mapping.', 'richardmedina-crm-automation' ); ?></p>
			</div>
			<div class="rm-ca-section__body">
				<p>
					<a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add new rule', 'richardmedina-crm-automation' ); ?></a>
				</p>

				<?php if ( empty( $rules ) ) : ?>
					<p class="description"><?php esc_html_e( 'No rules yet. Click "Add new rule" to wire up your first form-to-CRM connection.', 'richardmedina-crm-automation' ); ?></p>
				<?php else : ?>
					<table class="widefat striped rm-ca-rules-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Name', 'richardmedina-crm-automation' ); ?></th>
								<th><?php esc_html_e( 'Source', 'richardmedina-crm-automation' ); ?></th>
								<th><?php esc_html_e( 'Destination', 'richardmedina-crm-automation' ); ?></th>
								<th><?php esc_html_e( 'Status', 'richardmedina-crm-automation' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'richardmedina-crm-automation' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rules as $rule ) :
								$edit_url   = add_query_arg( [ 'page' => AdminBoot::PAGE_SLUG, 'action' => 'edit', 'rule_id' => $rule->id ], admin_url( 'admin.php' ) );
								$toggle_url = wp_nonce_url( add_query_arg( [ 'page' => AdminBoot::PAGE_SLUG, 'rm_ca_action' => 'toggle', 'rule_id' => $rule->id ], admin_url( 'admin.php' ) ), 'rm_ca_toggle_' . $rule->id );
								$delete_url = wp_nonce_url( add_query_arg( [ 'page' => AdminBoot::PAGE_SLUG, 'rm_ca_action' => 'delete', 'rule_id' => $rule->id ], admin_url( 'admin.php' ) ), 'rm_ca_delete_' . $rule->id );
								$dest       = DestinationRegistry::get( $rule->destination_type );
								$dest_label = $dest ? $dest->label() : ( $rule->destination_type ?: '—' );
								$source_lbl = $rule->source_type === GravitySource::TYPE
									? ( 'Gravity Form #' . $rule->source_id . ( ! empty( $forms_by_id[ $rule->source_id ] ) ? ' — ' . $forms_by_id[ $rule->source_id ] : '' ) )
									: ( $rule->source_type . ' #' . $rule->source_id );
								?>
								<tr>
									<td><a href="<?php echo esc_url( $edit_url ); ?>"><strong><?php echo esc_html( $rule->name ?: '(unnamed)' ); ?></strong></a></td>
									<td><?php echo esc_html( $source_lbl ); ?></td>
									<td><?php echo esc_html( $dest_label ); ?></td>
									<td>
										<?php if ( $rule->enabled ) : ?>
											<span class="rm-ca-pill rm-ca-pill--success"><?php esc_html_e( 'Enabled', 'richardmedina-crm-automation' ); ?></span>
										<?php else : ?>
											<span class="rm-ca-pill rm-ca-pill--neutral"><?php esc_html_e( 'Disabled', 'richardmedina-crm-automation' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'richardmedina-crm-automation' ); ?></a>
										&nbsp;|&nbsp;
										<a href="<?php echo esc_url( $toggle_url ); ?>"><?php echo $rule->enabled ? esc_html__( 'Disable', 'richardmedina-crm-automation' ) : esc_html__( 'Enable', 'richardmedina-crm-automation' ); ?></a>
										&nbsp;|&nbsp;
										<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this rule? This cannot be undone.', 'richardmedina-crm-automation' ) ); ?>');" style="color:#b32d2e;"><?php esc_html_e( 'Delete', 'richardmedina-crm-automation' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
