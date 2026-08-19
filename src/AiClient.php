<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

/**
 * Thin adapter over the core WordPress AI client.
 *
 * Isolates the one call to core so the rest of the plugin depends on a small,
 * mockable surface and so a future move to a registered WP Ability only touches
 * this class.
 */
final class AiClient {

	/**
	 * Model identifier reported on suggestions produced with AI.
	 */
	public const MODEL = 'wp-ai-client';

	/**
	 * Generates text for a prompt, or returns a WP_Error.
	 *
	 * @param string $prompt Fully-formed prompt string.
	 * @return string|\WP_Error Raw model output or an error.
	 */
	public function generate( string $prompt ) {
		if ( ! function_exists( 'wp_supports_ai' ) || ! \wp_supports_ai() ) {
			return new \WP_Error( 'kjeks_ai_unsupported', __( 'This site does not support the AI client.', 'kjeks-ai-reviewer' ) );
		}

		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return new \WP_Error( 'kjeks_ai_unavailable', __( 'The AI client is not available.', 'kjeks-ai-reviewer' ) );
		}

		try {
			$result = \wp_ai_client_prompt( $prompt )
				->using_max_tokens( 400 )
				->generate_text();
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'kjeks_ai_exception', $e->getMessage() );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return (string) $result;
	}
}
