<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

/**
 * Maps a free-form category label (from grounding data or the model) onto one
 * of the four Kjeks categories.
 *
 * Anything that cannot be mapped falls back to marketing — never necessary — so
 * that an unrecognised label can never silently be treated as essential.
 */
final class CategoryMap {

	public const NECESSARY   = 'necessary';
	public const PREFERENCES = 'preferences';
	public const ANALYTICS   = 'analytics';
	public const MARKETING   = 'marketing';

	/**
	 * @var array<string, string>
	 */
	private const MAP = array(
		'functional'      => self::NECESSARY,
		'security'        => self::NECESSARY,
		'essential'       => self::NECESSARY,
		'necessary'       => self::NECESSARY,
		'preferences'     => self::PREFERENCES,
		'personalization' => self::PREFERENCES,
		'personalisation' => self::PREFERENCES,
		'analytics'       => self::ANALYTICS,
		'statistics'      => self::ANALYTICS,
		'performance'     => self::ANALYTICS,
		'measurement'     => self::ANALYTICS,
		'marketing'       => self::MARKETING,
		'advertising'     => self::MARKETING,
		'targeting'       => self::MARKETING,
		'social'          => self::MARKETING,
	);

	/**
	 * Returns the Kjeks category for a raw label, defaulting to marketing.
	 */
	public static function to_kjeks( string $raw ): string {
		$key = strtolower( trim( $raw ) );

		return self::MAP[ $key ] ?? self::MARKETING;
	}

	/**
	 * Whether the mapped category is one of the four valid Kjeks categories.
	 */
	public static function is_valid( string $category ): bool {
		return in_array(
			$category,
			array( self::NECESSARY, self::PREFERENCES, self::ANALYTICS, self::MARKETING ),
			true
		);
	}
}
