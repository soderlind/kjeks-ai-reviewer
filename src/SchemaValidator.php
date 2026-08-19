<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

/**
 * Validates and normalises the raw JSON object a model returns for one tracker.
 *
 * Fails closed: an invalid or low-confidence payload becomes a WP_Error rather
 * than a low-quality suggestion, so the tracker stays in manual review.
 */
final class SchemaValidator {

	/**
	 * Minimum confidence below which a suggestion is rejected outright.
	 */
	public const MIN_CONFIDENCE = 0.3;

	/**
	 * Parses a raw model string into a validated field array.
	 *
	 * @param string $raw Raw model output (expected to be a JSON object).
	 * @return array<string, mixed>|\WP_Error Normalised fields or an error.
	 */
	public function parse( string $raw ) {
		$json = $this->extract_json( $raw );
		if ( null === $json ) {
			return new \WP_Error( 'kjeks_ai_invalid_json', __( 'The model did not return valid JSON.', 'kjeks-ai-reviewer' ) );
		}

		return $this->validate( $json );
	}

	/**
	 * @param array<string, mixed> $data Decoded model object.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function validate( array $data ) {
		$category = CategoryMap::to_kjeks( (string) ( $data['category'] ?? '' ) );

		$confidence = isset( $data['confidence'] ) ? (float) $data['confidence'] : 0.0;
		$confidence = max( 0.0, min( 1.0, $confidence ) );
		if ( $confidence < self::MIN_CONFIDENCE ) {
			return new \WP_Error( 'kjeks_ai_low_confidence', __( 'The suggestion confidence was too low; leaving for manual review.', 'kjeks-ai-reviewer' ) );
		}

		$party = 'first' === ( $data['party'] ?? '' ) ? 'first' : 'third';

		$documentation_url = isset( $data['documentation_url'] ) ? trim( (string) $data['documentation_url'] ) : '';
		if ( '' !== $documentation_url && ! wp_http_validate_url( $documentation_url ) ) {
			// Never trust a fabricated URL.
			$documentation_url = '';
		}

		return array(
			'category'          => $category,
			'provider'          => sanitize_text_field( (string) ( $data['provider'] ?? '' ) ),
			'purpose'           => sanitize_text_field( (string) ( $data['purpose'] ?? '' ) ),
			'party'             => $party,
			'retention'         => sanitize_text_field( (string) ( $data['retention'] ?? '' ) ),
			'documentation_url' => '' === $documentation_url ? '' : esc_url_raw( $documentation_url ),
			'confidence'        => $confidence,
			'rationale'         => sanitize_text_field( (string) ( $data['rationale'] ?? '' ) ),
		);
	}

	/**
	 * Extracts the first JSON object from a model response.
	 *
	 * Models sometimes wrap JSON in prose or code fences; this grabs the object.
	 *
	 * @return array<string, mixed>|null
	 */
	private function extract_json( string $raw ) {
		$raw = trim( $raw );

		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}

		$start = strpos( $raw, '{' );
		$end   = strrpos( $raw, '}' );
		if ( false === $start || false === $end || $end <= $start ) {
			return null;
		}

		$decoded = json_decode( substr( $raw, $start, $end - $start + 1 ), true );

		return is_array( $decoded ) ? $decoded : null;
	}
}
