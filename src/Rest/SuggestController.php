<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer\Rest;

use Soderlind\KjeksAiReviewer\AiReviewer;
use Soderlind\KjeksAiReviewer\Dependency;
use Soderlind\KjeksAiReviewer\PendingSource;
use Soderlind\KjeksAiReviewer\SuggestionStore;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Read-and-generate endpoints for the reviewer UI.
 *
 * GET  /kjeks-ai/v1/state    — current pending trackers with any suggestion.
 * POST /kjeks-ai/v1/suggest  — generate suggestions for the pending batch.
 * POST /kjeks-ai/v1/settings — toggle the opt-in weekly cron.
 *
 * All routes require `manage_network` on multisite, `manage_options` on single-site.
 */
final class SuggestController {

	private const NS = 'kjeks-ai/v1';

	private Dependency $dependency;

	public function __construct( ?Dependency $dependency = null ) {
		$this->dependency = $dependency ?? new Dependency();
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes(): void {
		register_rest_route(
			self::NS,
			'/state',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'state' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			self::NS,
			'/suggest',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'suggest' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'force' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'settings' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'cron_enabled' => array(
						'type'     => 'boolean',
						'required' => true,
					),
				),
			)
		);
	}

	public function can_manage(): bool {
		return is_multisite() ? current_user_can( 'manage_network' ) : current_user_can( 'manage_options' );
	}

	public function state(): WP_REST_Response {
		return new WP_REST_Response( $this->build_state() );
	}

	public function suggest( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->dependency->supports_ai() ) {
			return new WP_REST_Response(
				array( 'error' => __( 'This site does not support the AI client.', 'kjeks-ai-reviewer' ) ),
				409
			);
		}

		$reviewer = new AiReviewer();
		$summary  = $reviewer->suggest( (bool) $request->get_param( 'force' ) );

		$state           = $this->build_state();
		$state['result'] = $summary;

		return new WP_REST_Response( $state );
	}

	public function settings( WP_REST_Request $request ): WP_REST_Response {
		$settings                 = get_site_option( 'kjeks_ai_settings', array() );
		$settings                 = is_array( $settings ) ? $settings : array();
		$settings['cron_enabled'] = (bool) $request->get_param( 'cron_enabled' );
		update_site_option( 'kjeks_ai_settings', $settings );

		return new WP_REST_Response( $this->build_state() );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function build_state(): array {
		$store    = new SuggestionStore();
		$pending  = new PendingSource();
		$settings = get_site_option( 'kjeks_ai_settings', array() );

		$items = array();
		foreach ( $pending->pending( $store, true ) as $tracker ) {
			$suggestion = $store->get( $tracker->id );
			$items[]    = array(
				'id'           => $tracker->id,
				'name'         => $tracker->name,
				'domain'       => $tracker->domain,
				'storage_type' => $tracker->storage_type,
				'party'        => $tracker->party,
				'suggestion'   => null !== $suggestion ? $suggestion->to_array() : null,
			);
		}

		return array(
			'supported'    => $this->dependency->supports_ai(),
			'pending'      => $items,
			'pendingCount' => $pending->pending_count(),
			'cronEnabled'  => is_array( $settings ) && ! empty( $settings['cron_enabled'] ),
		);
	}
}
