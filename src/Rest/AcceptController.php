<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer\Rest;

use Soderlind\KjeksAiReviewer\AiReviewer;
use Soderlind\KjeksAiReviewer\CategoryMap;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Accept / reject endpoints.
 *
 * POST /kjeks-ai/v1/accept — apply a suggestion (runs the normal Kjeks review).
 * POST /kjeks-ai/v1/reject — discard a suggestion without touching the registry.
 *
 * Accepting `necessary` is single-item only; there is no bulk path for it, which
 * is enforced in the UI. Both routes require the manage_network capability.
 */
final class AcceptController {

	private const NS = 'kjeks-ai/v1';

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes(): void {
		register_rest_route(
			self::NS,
			'/accept',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'accept' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'id'       => array(
						'type'     => 'string',
						'required' => true,
					),
					'category' => array(
						'type'              => 'string',
						'required'          => false,
						'validate_callback' => static function ( $value ): bool {
							return '' === $value || CategoryMap::is_valid( (string) $value );
						},
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/reject',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'reject' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'id' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_network' );
	}

	public function accept( WP_REST_Request $request ): WP_REST_Response {
		$id       = sanitize_text_field( (string) $request->get_param( 'id' ) );
		$category = $request->get_param( 'category' );
		$category = ( null !== $category && '' !== $category ) ? sanitize_text_field( (string) $category ) : null;

		$result = ( new AiReviewer() )->accept( $id, $category );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 400 );
		}

		return new WP_REST_Response( array( 'accepted' => $id ) );
	}

	public function reject( WP_REST_Request $request ): WP_REST_Response {
		$id = sanitize_text_field( (string) $request->get_param( 'id' ) );
		( new AiReviewer() )->reject( $id );

		return new WP_REST_Response( array( 'rejected' => $id ) );
	}
}
