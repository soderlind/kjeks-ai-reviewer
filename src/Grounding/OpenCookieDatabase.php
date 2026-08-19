<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer\Grounding;

/**
 * Deterministic grounding against a pinned Open Cookie Database snapshot.
 *
 * Grounding runs before any model call: a confident local match yields a
 * suggestion with no AI involved, and a partial match seeds the prompt so the
 * model only fills gaps. This keeps common cookies accurate and cheap.
 */
final class OpenCookieDatabase {

	/**
	 * @var array<int, array<string, string>>|null
	 */
	private ?array $entries = null;

	private string $path;

	public function __construct( ?string $path = null ) {
		$this->path = $path ?? KJEKS_AI_DIR . 'data/open-cookie-database.json';
	}

	/**
	 * Finds the best grounding match for a cookie by name, then domain.
	 *
	 * @param string $name   Cookie/storage key name.
	 * @param string $domain Domain the tracker was seen on (optional).
	 * @return array<string, string>|null Matched entry, or null.
	 */
	public function match( string $name, string $domain = '' ): ?array {
		$name = trim( $name );
		if ( '' === $name ) {
			return null;
		}

		$best       = null;
		$best_score = 0;

		foreach ( $this->load() as $entry ) {
			$score = $this->score( $entry, $name, $domain );
			if ( $score > $best_score ) {
				$best_score = $score;
				$best       = $entry;
			}
		}

		return $best;
	}

	/**
	 * Scores an entry against a lookup. Higher is a better match; 0 is no match.
	 *
	 * @param array<string, string> $entry  Database entry.
	 * @param string                $name   Cookie name.
	 * @param string                $domain Domain.
	 */
	private function score( array $entry, string $name, string $domain ): int {
		$pattern = (string) ( $entry['pattern'] ?? '' );
		$matched = false;

		if ( '' !== $pattern ) {
			$regex = '/' . str_replace( '/', '\/', $pattern ) . '/i';
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Patterns come from bundled data; a malformed one should not warn, just fail to match.
			$matched = 1 === @preg_match( $regex, $name );
		}

		if ( ! $matched && isset( $entry['name'] ) && strcasecmp( (string) $entry['name'], $name ) === 0 ) {
			$matched = true;
		}

		if ( ! $matched ) {
			return 0;
		}

		$score = 2;

		$entry_domain = (string) ( $entry['domain'] ?? '' );
		if ( '' !== $entry_domain && '' !== $domain && false !== stripos( $domain, $entry_domain ) ) {
			++$score;
		}

		return $score;
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private function load(): array {
		if ( null !== $this->entries ) {
			return $this->entries;
		}

		$this->entries = array();

		if ( ! is_readable( $this->path ) ) {
			return $this->entries;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a bundled local JSON file, not a remote URL.
		$decoded = json_decode( (string) file_get_contents( $this->path ), true );
		if ( is_array( $decoded ) && isset( $decoded['entries'] ) && is_array( $decoded['entries'] ) ) {
			foreach ( $decoded['entries'] as $entry ) {
				if ( is_array( $entry ) ) {
					$this->entries[] = array_map( 'strval', $entry );
				}
			}
		}

		return $this->entries;
	}
}
