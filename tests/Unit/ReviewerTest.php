<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

use Soderlind\Kjeks\Inventory\Tracker;
use Soderlind\KjeksAiReviewer\Grounding\OpenCookieDatabase;
use Soderlind\KjeksAiReviewer\Reviewer;
use Soderlind\KjeksAiReviewer\Suggestion;

function reviewer(): Reviewer {
	$db = new OpenCookieDatabase( dirname( __DIR__, 2 ) . '/data/open-cookie-database.json' );

	return new Reviewer( $db );
}

it( 'produces a grounded suggestion with no AI call for a known cookie', function (): void {
	$tracker = new Tracker( id: 'ga', name: '_ga', domain: 'example.com' );

	$result = reviewer()->review( $tracker );

	expect( $result )->toBeInstanceOf( Suggestion::class );
	expect( $result->category )->toBe( 'analytics' );
	expect( $result->model )->toBe( 'grounding' );
	expect( $result->grounded_by )->toBe( 'open-cookie-database' );
	expect( $result->confidence )->toBeGreaterThan( 0.8 );
} );

it( 'maps a functional grounding entry to necessary', function (): void {
	$tracker = new Tracker( id: 'sess', name: 'PHPSESSID' );

	$result = reviewer()->review( $tracker );

	expect( $result )->toBeInstanceOf( Suggestion::class );
	expect( $result->category )->toBe( 'necessary' );
} );

it( 'fails closed when a cookie is unknown and AI is unavailable', function (): void {
	$tracker = new Tracker( id: 'x', name: 'totally_unknown_cookie_xyz' );

	$result = reviewer()->review( $tracker );

	expect( $result )->toBeInstanceOf( WP_Error::class );
	expect( $result->get_error_code() )->toBe( 'kjeks_ai_unsupported' );
} );
