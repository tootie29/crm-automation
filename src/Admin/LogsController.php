<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Pipeline\Worker;

defined( 'ABSPATH' ) || exit;

final class LogsController {

	public function register(): void {
		add_action( 'admin_post_rm_ca_retry_job', [ $this, 'retry_job' ] );
	}

	public function retry_job(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'richardmedina-crm-automation' ), '', [ 'response' => 403 ] );
		}
		$queue_id = (int) ( $_POST['queue_id'] ?? $_GET['queue_id'] ?? 0 );
		check_admin_referer( 'rm_ca_retry_' . $queue_id );

		Worker::retry_now( $queue_id );

		wp_safe_redirect( admin_url( 'admin.php?page=' . AdminBoot::PAGE_SLUG . '-logs&retried=1' ) );
		exit;
	}
}
