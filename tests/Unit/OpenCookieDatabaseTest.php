<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

use Soderlind\KjeksAiReviewer\Grounding\OpenCookieDatabase;

function fixture_path(): string {
	return dirname( __DIR__, 2 ) . '/data/open-cookie-database.json';
}

it( 'matches a cookie by exact name', function (): void {
	$db    = new OpenCookieDatabase( fixture_path() );
	$entry = $db->match( '_ga' );

	expect( $entry )->toBeArray();
	expect( $entry['provider'] )->toBe( 'Google Analytics' );
	expect( $entry['category'] )->toBe( 'analytics' );
} );

it( 'matches by pattern for suffixed names', function (): void {
	$db    = new OpenCookieDatabase( fixture_path() );
	$entry = $db->match( '_ga_ABC123' );

	expect( $entry )->toBeArray();
	expect( $entry['provider'] )->toBe( 'Google Analytics' );
} );

it( 'returns null for an unknown cookie', function (): void {
	$db = new OpenCookieDatabase( fixture_path() );

	expect( $db->match( 'totally_unknown_cookie_xyz' ) )->toBeNull();
} );

it( 'returns null for an empty name', function (): void {
	$db = new OpenCookieDatabase( fixture_path() );

	expect( $db->match( '' ) )->toBeNull();
} );

it( 'prefers a domain-matching entry when scores tie', function (): void {
	$db    = new OpenCookieDatabase( fixture_path() );
	$entry = $db->match( '_fbp', 'connect.facebook.com' );

	expect( $entry['provider'] )->toBe( 'Meta Platforms' );
} );
