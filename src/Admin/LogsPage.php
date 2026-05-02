<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Database\Schema;
use RichardMedina\CrmAutomation\Pipeline\Queue;

defined( 'ABSPATH' ) || exit;

final class LogsPage {

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wpdb;
		$table  = Schema::logs_table();
		$queue  = Schema::queue_table();
		$counts = Queue::counts_by_status();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 100", ARRAY_A );
		// phpcs:enable

		View::open_wrap();
		View::header();
		View::nav( AdminBoot::PAGE_SLUG . '-logs' );
		?>
		<div class="rm-ca-grid">
			<div class="rm-ca-card">
				<p class="rm-ca-card__label"><?php esc_html_e( 'Pending', 'richardmedina-crm-automation' ); ?></p>
				<p class="rm-ca-card__value"><?php echo (int) ( $counts[ Queue::STATUS_PENDING ] ?? 0 ); ?></p>
			</div>
			<div class="rm-ca-card">
				<p class="rm-ca-card__label"><?php esc_html_e( 'Done', 'richardmedina-crm-automation' ); ?></p>
				<p class="rm-ca-card__value"><?php echo (int) ( $counts[ Queue::STATUS_DONE ] ?? 0 ); ?></p>
			</div>
			<div class="rm-ca-card">
				<p class="rm-ca-card__label"><?php esc_html_e( 'Dead', 'richardmedina-crm-automation' ); ?></p>
				<p class="rm-ca-card__value"><?php echo (int) ( $counts[ Queue::STATUS_DEAD ] ?? 0 ); ?></p>
			</div>
			<div class="rm-ca-card">
				<p class="rm-ca-card__label"><?php esc_html_e( 'Running', 'richardmedina-crm-automation' ); ?></p>
				<p class="rm-ca-card__value"><?php echo (int) ( $counts[ Queue::STATUS_RUNNING ] ?? 0 ); ?></p>
			</div>
		</div>

		<div class="rm-ca-section">
			<div class="rm-ca-section__head">
				<h3 class="rm-ca-section__title"><?php esc_html_e( 'Recent log entries (last 100)', 'richardmedina-crm-automation' ); ?></h3>
			</div>
			<div class="rm-ca-section__body">
				<?php if ( empty( $rows ) ) : ?>
					<p class="description"><?php esc_html_e( 'No log entries yet.', 'richardmedina-crm-automation' ); ?></p>
				<?php else : ?>
					<table class="widefat striped rm-ca-logs-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'When', 'richardmedina-crm-automation' ); ?></th>
								<th><?php esc_html_e( 'Level', 'richardmedina-crm-automation' ); ?></th>
								<th><?php esc_html_e( 'Source', 'richardmedina-crm-automation' ); ?></th>
								<th><?php esc_html_e( 'Destination', 'richardmedina-crm-automation' ); ?></th>
								<th><?php esc_html_e( 'Message', 'richardmedina-crm-automation' ); ?></th>
								<th><?php esc_html_e( 'Context', 'richardmedina-crm-automation' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rows as $row ) :
								$level = (string) $row['level'];
								$pill  = match ( $level ) {
									'error' => 'rm-ca-pill--danger',
									'warn'  => 'rm-ca-pill--warning',
									default => 'rm-ca-pill--neutral',
								};
								?>
								<tr>
									<td><code><?php echo esc_html( (string) $row['created_at'] ); ?></code></td>
									<td><span class="rm-ca-pill <?php echo esc_attr( $pill ); ?>"><?php echo esc_html( strtoupper( $level ) ); ?></span></td>
									<td><?php echo esc_html( (string) $row['source_type'] . ( $row['source_id'] ? ' #' . $row['source_id'] : '' ) ); ?></td>
									<td><?php echo esc_html( (string) $row['destination_type'] ); ?></td>
									<td><?php echo esc_html( (string) $row['message'] ); ?></td>
									<td><details><summary><?php esc_html_e( 'Show', 'richardmedina-crm-automation' ); ?></summary><pre style="white-space:pre-wrap;font-size:11px;"><?php echo esc_html( (string) $row['context_json'] ); ?></pre></details></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<?php
		View::footer();
		View::close_wrap();
	}
}
