<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Sources\GravityForms;

use RichardMedina\CrmAutomation\Pipeline\Dispatcher;
use RichardMedina\CrmAutomation\Sources\SourceContract;
use RichardMedina\CrmAutomation\Sources\Submission;
use RichardMedina\CrmAutomation\Support\Logger;

defined( 'ABSPATH' ) || exit;

final class Source implements SourceContract {

	public const TYPE = 'gravityforms';

	public function type(): string {
		return self::TYPE;
	}

	public function label(): string {
		return 'Gravity Forms';
	}

	public function register(): void {
		// Fires after a Gravity Forms submission is fully saved.
		add_action( 'gform_after_submission', [ $this, 'on_submission' ], 10, 2 );
	}

	/**
	 * @param array<string,mixed> $entry
	 * @param array<string,mixed> $form
	 */
	public function on_submission( array $entry, array $form ): void {
		try {
			$fields = $this->collect_fields( $entry, $form );

			$submission = new Submission(
				self::TYPE,
				(string) ( $form['id'] ?? '' ),
				(string) ( $entry['id'] ?? '' ),
				$fields,
				[
					'form_title' => (string) ( $form['title'] ?? '' ),
					'date'       => (string) ( $entry['date_created'] ?? '' ),
					'ip'         => (string) ( $entry['ip'] ?? '' ),
				]
			);

			Dispatcher::dispatch( $submission );
		} catch ( \Throwable $e ) {
			Logger::error( 'gravityforms.dispatch_failed', [
				'source_type' => self::TYPE,
				'source_id'   => (string) ( $form['id'] ?? '' ),
				'error'       => $e->getMessage(),
			] );
		}
	}

	public function list(): array {
		if ( ! class_exists( 'GFAPI' ) ) {
			return [];
		}
		$forms = \GFAPI::get_forms( true ); // active only
		$out   = [];
		foreach ( (array) $forms as $form ) {
			$out[] = [
				'id'    => (string) ( $form['id'] ?? '' ),
				'label' => (string) ( $form['title'] ?? '' ),
			];
		}
		return $out;
	}

	public function describe_fields( string $source_id ): array {
		if ( ! class_exists( 'GFAPI' ) || $source_id === '' ) {
			return [];
		}
		$form = \GFAPI::get_form( (int) $source_id );
		if ( ! is_array( $form ) ) {
			return [];
		}
		$out = [];
		foreach ( (array) ( $form['fields'] ?? [] ) as $field ) {
			$id    = (string) ( $field->id ?? '' );
			$label = (string) ( $field->label ?? '' );
			$type  = (string) ( $field->type ?? '' );

			if ( $type === 'name' ) {
				// expose the sub-inputs (3.3 = first, 3.6 = last)
				$out[] = [ 'key' => $id . '.3', 'label' => $label . ' (first)', 'type' => 'text' ];
				$out[] = [ 'key' => $id . '.6', 'label' => $label . ' (last)',  'type' => 'text' ];
				$out[] = [ 'key' => $id,        'label' => $label . ' (full)',  'type' => 'text' ];
				continue;
			}
			if ( $type === 'address' ) {
				foreach ( [ '.1' => 'street', '.2' => 'street 2', '.3' => 'city', '.4' => 'state', '.5' => 'postal', '.6' => 'country' ] as $sub => $sublabel ) {
					$out[] = [ 'key' => $id . $sub, 'label' => $label . ' (' . $sublabel . ')', 'type' => 'text' ];
				}
				continue;
			}
			$out[] = [ 'key' => $id, 'label' => $label, 'type' => $type ];
		}
		return $out;
	}

	/**
	 * @param array<string,mixed> $entry
	 * @param array<string,mixed> $form
	 * @return array<int,array{key:string,label:string,value:string,type:string}>
	 */
	private function collect_fields( array $entry, array $form ): array {
		$labels = [];
		$types  = [];
		foreach ( (array) ( $form['fields'] ?? [] ) as $field ) {
			$id            = (string) ( $field->id ?? '' );
			$labels[ $id ] = (string) ( $field->label ?? '' );
			$types[ $id ]  = (string) ( $field->type ?? '' );
			foreach ( (array) ( $field->inputs ?? [] ) as $input ) {
				$sub_id          = (string) ( $input['id'] ?? '' );
				$sub_label       = (string) ( $input['label'] ?? '' );
				$labels[ $sub_id ] = trim( $labels[ $id ] . ' — ' . $sub_label, ' —' );
				$types[ $sub_id ]  = $types[ $id ];
			}
		}

		$out = [];
		foreach ( $entry as $key => $value ) {
			$key = (string) $key;
			if ( $key === '' || ! preg_match( '/^[0-9]+(\.[0-9]+)?$/', $key ) ) {
				continue; // skip meta keys (id, form_id, date_created, etc.)
			}
			$value = is_scalar( $value ) ? (string) $value : (string) wp_json_encode( $value );
			$out[] = [
				'key'   => $key,
				'label' => $labels[ $key ] ?? $key,
				'value' => $value,
				'type'  => $types[ $key ] ?? '',
			];
		}
		return $out;
	}
}
