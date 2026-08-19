<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

use Soderlind\Kjeks\Inventory\Tracker;
use Soderlind\KjeksAiReviewer\Grounding\OpenCookieDatabase;

/**
 * Produces one advisory Suggestion for one tracker.
 *
 * Order of operations (ground before generate):
 *   1. Look the cookie up in the pinned Open Cookie Database.
 *   2. A confident local match becomes a suggestion with no model call.
 *   3. Otherwise the match seeds a minimal, conservative prompt and the model
 *      fills the gaps; the response is strictly validated.
 *
 * A suggestion is always advisory — it never sets the reviewed flag.
 */
final class Reviewer {

	/**
	 * Confidence assigned to a fully-grounded (no-AI) suggestion.
	 */
	private const GROUNDED_CONFIDENCE = 0.9;

	private OpenCookieDatabase $grounding;
	private AiClient $ai;
	private SchemaValidator $validator;

	public function __construct(
		?OpenCookieDatabase $grounding = null,
		?AiClient $ai = null,
		?SchemaValidator $validator = null
	) {
		$this->grounding = $grounding ?? new OpenCookieDatabase();
		$this->ai        = $ai ?? new AiClient();
		$this->validator = $validator ?? new SchemaValidator();
	}

	/**
	 * @return Suggestion|\WP_Error
	 */
	public function review( Tracker $tracker ) {
		$grounded = $this->grounding->match( $tracker->name, $tracker->domain );

		if ( null !== $grounded && $this->is_complete( $grounded ) ) {
			return $this->from_grounding( $tracker, $grounded );
		}

		$raw = $this->ai->generate( $this->build_prompt( $tracker, $grounded ) );
		if ( is_wp_error( $raw ) ) {
			return $raw;
		}

		$fields = $this->validator->parse( $raw );
		if ( is_wp_error( $fields ) ) {
			return $fields;
		}

		return new Suggestion(
			id: $tracker->id,
			category: $fields['category'],
			provider: '' !== $fields['provider'] ? $fields['provider'] : (string) ( $grounded['provider'] ?? '' ),
			purpose: $fields['purpose'],
			party: $fields['party'],
			retention: $fields['retention'],
			documentation_url: $fields['documentation_url'],
			confidence: $fields['confidence'],
			rationale: $fields['rationale'],
			model: AiClient::MODEL,
			grounded_by: null !== $grounded ? 'open-cookie-database' : '',
			generated_at: time(),
		);
	}

	/**
	 * @param array<string, string> $entry Grounding entry.
	 */
	private function from_grounding( Tracker $tracker, array $entry ): Suggestion {
		return new Suggestion(
			id: $tracker->id,
			category: CategoryMap::to_kjeks( (string) ( $entry['category'] ?? '' ) ),
			provider: (string) ( $entry['provider'] ?? '' ),
			purpose: (string) ( $entry['purpose'] ?? '' ),
			party: 'first' === ( $entry['party'] ?? '' ) ? 'first' : 'third',
			retention: (string) ( $entry['retention'] ?? '' ),
			documentation_url: esc_url_raw( (string) ( $entry['documentation_url'] ?? '' ) ),
			confidence: self::GROUNDED_CONFIDENCE,
			rationale: __( 'Matched a known entry in the Open Cookie Database.', 'kjeks-ai-reviewer' ),
			model: 'grounding',
			grounded_by: 'open-cookie-database',
			generated_at: time(),
		);
	}

	/**
	 * A grounding entry is complete enough to skip the model when it has a
	 * category, provider, and purpose.
	 *
	 * @param array<string, string> $entry Grounding entry.
	 */
	private function is_complete( array $entry ): bool {
		return '' !== ( $entry['category'] ?? '' )
			&& '' !== ( $entry['provider'] ?? '' )
			&& '' !== ( $entry['purpose'] ?? '' );
	}

	/**
	 * Builds a conservative, JSON-only prompt sending minimal cookie metadata.
	 *
	 * @param array<string, string>|null $grounded Partial grounding hint, if any.
	 */
	private function build_prompt( Tracker $tracker, ?array $grounded ): string {
		$facts = array(
			'name'         => $tracker->name,
			'domain'       => $tracker->domain,
			'storage_type' => $tracker->storage_type,
			'party'        => $tracker->party,
		);

		$hint = '';
		if ( null !== $grounded ) {
			$hint = "\nA partial match from a public cookie database (may be incomplete): "
				. wp_json_encode(
					array(
						'provider' => $grounded['provider'] ?? '',
						'category' => $grounded['category'] ?? '',
					)
				) . "\n";
		}

		return "You classify a single browser storage item for a cookie-consent tool.\n"
			. "Return ONLY a JSON object, no prose, no code fences, with these keys:\n"
			. '{"category":"necessary|preferences|analytics|marketing","provider":"","purpose":"","party":"first|third","retention":"","documentation_url":"","confidence":0.0,"rationale":""}' . "\n\n"
			. "Rules:\n"
			. "- Be conservative. Choose \"necessary\" only if the item is clearly essential (session, auth, security, CSRF, load balancing).\n"
			. "- If unsure, prefer \"marketing\" over \"necessary\".\n"
			. "- retention is a human phrase like \"1 year\" or \"session\".\n"
			. "- documentation_url MUST be a real, well-known vendor URL or an empty string. Never invent one.\n"
			. "- confidence is 0.0-1.0. rationale is one short sentence.\n"
			. $hint
			. "\nItem: " . wp_json_encode( $facts ) . "\n";
	}
}
