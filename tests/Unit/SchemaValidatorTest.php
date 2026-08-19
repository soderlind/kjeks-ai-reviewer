<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

use Soderlind\KjeksAiReviewer\SchemaValidator;

it( 'parses a clean JSON object', function (): void {
	$raw = '{"category":"analytics","provider":"Acme","purpose":"Counts visits","party":"third","retention":"1 year","documentation_url":"https://example.com/cookies","confidence":0.8,"rationale":"Analytics cookie."}';

	$fields = ( new SchemaValidator() )->parse( $raw );

	expect( $fields )->toBeArray();
	expect( $fields['category'] )->toBe( 'analytics' );
	expect( $fields['provider'] )->toBe( 'Acme' );
	expect( $fields['confidence'] )->toBe( 0.8 );
} );

it( 'extracts JSON wrapped in prose or code fences', function (): void {
	$raw = "Sure! Here is the result:\n```json\n{\"category\":\"marketing\",\"confidence\":0.7}\n```";

	$fields = ( new SchemaValidator() )->parse( $raw );

	expect( $fields )->toBeArray();
	expect( $fields['category'] )->toBe( 'marketing' );
} );

it( 'rejects invalid JSON', function (): void {
	$result = ( new SchemaValidator() )->parse( 'not json at all' );

	expect( $result )->toBeInstanceOf( WP_Error::class );
	expect( $result->get_error_code() )->toBe( 'kjeks_ai_invalid_json' );
} );

it( 'rejects low-confidence suggestions so they stay in manual review', function (): void {
	$result = ( new SchemaValidator() )->parse( '{"category":"analytics","confidence":0.1}' );

	expect( $result )->toBeInstanceOf( WP_Error::class );
	expect( $result->get_error_code() )->toBe( 'kjeks_ai_low_confidence' );
} );

it( 'drops fabricated documentation URLs', function (): void {
	$fields = ( new SchemaValidator() )->parse( '{"category":"analytics","confidence":0.9,"documentation_url":"not a url"}' );

	expect( $fields['documentation_url'] )->toBe( '' );
} );

it( 'maps unknown categories to marketing, never necessary', function (): void {
	$fields = ( new SchemaValidator() )->parse( '{"category":"mystery","confidence":0.9}' );

	expect( $fields['category'] )->toBe( 'marketing' );
} );
