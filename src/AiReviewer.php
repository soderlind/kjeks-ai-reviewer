<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

use Soderlind\Kjeks\Inventory\TrackerRegistry;

/**
 * Orchestrates suggestion generation and acceptance across the pending set.
 *
 * Fails closed and per-item: a single provider error or invalid response is
 * recorded against that cookie and the batch continues. Nothing is written to
 * the Kjeks registry until an administrator accepts a suggestion.
 */
final class AiReviewer {

	private PendingSource $pending;
	private Reviewer $reviewer;
	private SuggestionStore $store;

	public function __construct(
		?PendingSource $pending = null,
		?Reviewer $reviewer = null,
		?SuggestionStore $store = null
	) {
		$this->pending  = $pending ?? new PendingSource();
		$this->reviewer = $reviewer ?? new Reviewer();
		$this->store    = $store ?? new SuggestionStore();
	}

	/**
	 * Generates suggestions for the pending batch and stores them.
	 *
	 * @param bool $force Re-suggest trackers that already have a suggestion.
	 * @return array{processed:int, suggested:int, errors:array<string,string>}
	 */
	public function suggest( bool $force = false ): array {
		$processed = 0;
		$suggested = 0;
		$errors    = array();

		foreach ( $this->pending->pending( $this->store, $force ) as $tracker ) {
			++$processed;

			$result = $this->reviewer->review( $tracker );
			if ( is_wp_error( $result ) ) {
				$errors[ $tracker->id ] = $result->get_error_message();
				continue;
			}

			$this->store->put( $result );
			++$suggested;
		}

		return array(
			'processed' => $processed,
			'suggested' => $suggested,
			'errors'    => $errors,
		);
	}

	/**
	 * Accepts a stored suggestion, running the normal Kjeks review.
	 *
	 * @param string      $id       Tracker id.
	 * @param string|null $category Override category; defaults to the suggestion's.
	 * @return true|\WP_Error
	 */
	public function accept( string $id, ?string $category = null ) {
		$suggestion = $this->store->get( $id );
		if ( null === $suggestion ) {
			return new \WP_Error( 'kjeks_ai_no_suggestion', __( 'No suggestion exists for this tracker.', 'kjeks-ai-reviewer' ) );
		}

		$category = $category ?? $suggestion->category;
		if ( ! CategoryMap::is_valid( $category ) ) {
			return new \WP_Error( 'kjeks_ai_bad_category', __( 'Invalid category.', 'kjeks-ai-reviewer' ) );
		}

		$registry = $this->pending->registry();
		$trackers = $registry->trackers();
		if ( ! isset( $trackers[ $id ] ) ) {
			return new \WP_Error( 'kjeks_ai_no_tracker', __( 'The tracker no longer exists.', 'kjeks-ai-reviewer' ) );
		}

		$trackers[ $id ] = $this->enrich( $trackers[ $id ], $suggestion, $category );
		$registry->save_trackers( $trackers );

		// Prune the suggestion now that the review is recorded.
		$this->store->remove( $id );

		return true;
	}

	/**
	 * Rejects (discards) a stored suggestion without touching the registry.
	 */
	public function reject( string $id ): void {
		$this->store->remove( $id );
	}

	/**
	 * Applies the accepted category plus any enrichment the tracker lacks.
	 *
	 * Existing tracker values win; the suggestion only fills empty fields.
	 *
	 * @param object $tracker A Soderlind\Kjeks\Inventory\Tracker.
	 */
	private function enrich( $tracker, Suggestion $suggestion, string $category ): object {
		$reviewed = $tracker->with_review( $category );
		$data     = $reviewed->to_array();

		foreach ( array( 'provider', 'purpose', 'retention', 'documentation_url' ) as $field ) {
			if ( '' === (string) ( $data[ $field ] ?? '' ) && '' !== $suggestion->{$field} ) {
				$data[ $field ] = $suggestion->{$field};
			}
		}

		if ( 'first' === $suggestion->party && 'third' === ( $data['party'] ?? '' ) ) {
			$data['party'] = 'first';
		}

		return $tracker::from_array( $data );
	}

	public function store(): SuggestionStore {
		return $this->store;
	}

	public function pending_source(): PendingSource {
		return $this->pending;
	}
}
