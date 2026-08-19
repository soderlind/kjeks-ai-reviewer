<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

/**
 * An advisory classification suggestion for a single tracker.
 *
 * A suggestion never sets the reviewed flag and is never applied automatically.
 * An administrator accepts it, which runs the normal Kjeks review.
 */
final class Suggestion {

	/**
	 * @param string $id                Tracker id this suggestion is for.
	 * @param string $category          One of the four Kjeks categories.
	 * @param string $provider          Suggested provider/vendor name.
	 * @param string $purpose           Short description of what the tracker does.
	 * @param string $party             'first' or 'third'.
	 * @param string $retention         Human-readable retention (e.g. "1 year").
	 * @param string $documentation_url Vendor documentation URL, or empty.
	 * @param float  $confidence        0.0–1.0 model/grounding confidence.
	 * @param string $rationale         One-sentence justification.
	 * @param string $model             AI model identifier, or 'grounding'.
	 * @param string $grounded_by       Source that grounded the suggestion, or empty.
	 * @param int    $generated_at      Unix timestamp.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $category,
		public readonly string $provider = '',
		public readonly string $purpose = '',
		public readonly string $party = 'third',
		public readonly string $retention = '',
		public readonly string $documentation_url = '',
		public readonly float $confidence = 0.0,
		public readonly string $rationale = '',
		public readonly string $model = '',
		public readonly string $grounded_by = '',
		public readonly int $generated_at = 0,
	) {}

	/**
	 * @param array<string, mixed> $data Raw data.
	 */
	public static function from_array( array $data ): self {
		return new self(
			id: (string) ( $data['id'] ?? '' ),
			category: (string) ( $data['category'] ?? '' ),
			provider: (string) ( $data['provider'] ?? '' ),
			purpose: (string) ( $data['purpose'] ?? '' ),
			party: 'first' === ( $data['party'] ?? '' ) ? 'first' : 'third',
			retention: (string) ( $data['retention'] ?? '' ),
			documentation_url: (string) ( $data['documentation_url'] ?? '' ),
			confidence: (float) ( $data['confidence'] ?? 0.0 ),
			rationale: (string) ( $data['rationale'] ?? '' ),
			model: (string) ( $data['model'] ?? '' ),
			grounded_by: (string) ( $data['grounded_by'] ?? '' ),
			generated_at: (int) ( $data['generated_at'] ?? 0 ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'category'          => $this->category,
			'provider'          => $this->provider,
			'purpose'           => $this->purpose,
			'party'             => $this->party,
			'retention'         => $this->retention,
			'documentation_url' => $this->documentation_url,
			'confidence'        => $this->confidence,
			'rationale'         => $this->rationale,
			'model'             => $this->model,
			'grounded_by'       => $this->grounded_by,
			'generated_at'      => $this->generated_at,
		);
	}
}
