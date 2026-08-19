<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

use Soderlind\Kjeks\Inventory\Tracker;
use Soderlind\Kjeks\Inventory\TrackerRegistry;

/**
 * Supplies the unreviewed trackers that are candidates for suggestions.
 *
 * Because Kjeks aggregates one registry entry per unique cookie network-wide,
 * this yields each unique unreviewed cookie exactly once — the reviewer then
 * needs only one AI call per unique cookie, not one per site.
 */
final class PendingSource {

	/**
	 * Maximum trackers returned in a single batch, to cap AI call volume.
	 */
	public const BATCH_CAP = 25;

	private TrackerRegistry $registry;

	public function __construct( ?TrackerRegistry $registry = null ) {
		$this->registry = $registry ?? new TrackerRegistry();
	}

	/**
	 * Returns unreviewed trackers, capped at BATCH_CAP.
	 *
	 * @param bool $force When false, skips trackers that already have a stored suggestion.
	 * @return array<int, Tracker>
	 */
	public function pending( SuggestionStore $suggestions, bool $force = false ): array {
		$out = array();

		foreach ( $this->registry->trackers() as $tracker ) {
			if ( $tracker->reviewed ) {
				continue;
			}

			if ( ! $force && $suggestions->has( $tracker->id ) ) {
				continue;
			}

			$out[] = $tracker;
			if ( count( $out ) >= self::BATCH_CAP ) {
				break;
			}
		}

		return $out;
	}

	/**
	 * Total number of unreviewed trackers (ignores the batch cap).
	 */
	public function pending_count(): int {
		$count = 0;
		foreach ( $this->registry->trackers() as $tracker ) {
			if ( ! $tracker->reviewed ) {
				++$count;
			}
		}

		return $count;
	}

	public function registry(): TrackerRegistry {
		return $this->registry;
	}
}
