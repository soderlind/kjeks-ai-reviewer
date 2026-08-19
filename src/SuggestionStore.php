<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

/**
 * Stores advisory suggestions separately from the Kjeks registry.
 *
 * Suggestions live in their own network option keyed by tracker id, so that
 * accepting or removing a tracker's review can prune them without ever
 * mutating the registry as a side effect of generating a suggestion.
 */
final class SuggestionStore {

	private const OPTION = 'kjeks_ai_suggestions';

	/**
	 * All suggestions, keyed by tracker id.
	 *
	 * @return array<string, Suggestion>
	 */
	public function all(): array {
		$raw = get_site_option( self::OPTION, array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out = array();
		foreach ( $raw as $item ) {
			if ( is_array( $item ) && ! empty( $item['id'] ) ) {
				$suggestion             = Suggestion::from_array( $item );
				$out[ $suggestion->id ] = $suggestion;
			}
		}

		return $out;
	}

	public function get( string $id ): ?Suggestion {
		$all = $this->all();

		return $all[ $id ] ?? null;
	}

	public function has( string $id ): bool {
		return null !== $this->get( $id );
	}

	public function put( Suggestion $suggestion ): void {
		$all                    = $this->all();
		$all[ $suggestion->id ] = $suggestion;
		$this->save( $all );
	}

	public function remove( string $id ): void {
		$all = $this->all();
		if ( isset( $all[ $id ] ) ) {
			unset( $all[ $id ] );
			$this->save( $all );
		}
	}

	/**
	 * @param array<string, Suggestion> $suggestions Suggestions keyed by id.
	 */
	private function save( array $suggestions ): void {
		$raw = array();
		foreach ( $suggestions as $suggestion ) {
			$raw[ $suggestion->id ] = $suggestion->to_array();
		}

		update_site_option( self::OPTION, $raw );
	}
}
