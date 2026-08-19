<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

use Soderlind\KjeksAiReviewer\CategoryMap;

it( 'maps known labels onto Kjeks categories', function (): void {
	expect( CategoryMap::to_kjeks( 'functional' ) )->toBe( CategoryMap::NECESSARY );
	expect( CategoryMap::to_kjeks( 'security' ) )->toBe( CategoryMap::NECESSARY );
	expect( CategoryMap::to_kjeks( 'personalization' ) )->toBe( CategoryMap::PREFERENCES );
	expect( CategoryMap::to_kjeks( 'statistics' ) )->toBe( CategoryMap::ANALYTICS );
	expect( CategoryMap::to_kjeks( 'advertising' ) )->toBe( CategoryMap::MARKETING );
} );

it( 'is case-insensitive and trims', function (): void {
	expect( CategoryMap::to_kjeks( '  ANALYTICS  ' ) )->toBe( CategoryMap::ANALYTICS );
} );

it( 'falls back to marketing for unknown labels, never necessary', function (): void {
	expect( CategoryMap::to_kjeks( 'something-weird' ) )->toBe( CategoryMap::MARKETING );
	expect( CategoryMap::to_kjeks( '' ) )->toBe( CategoryMap::MARKETING );
} );

it( 'validates the four Kjeks categories', function (): void {
	expect( CategoryMap::is_valid( 'necessary' ) )->toBeTrue();
	expect( CategoryMap::is_valid( 'marketing' ) )->toBeTrue();
	expect( CategoryMap::is_valid( 'nonsense' ) )->toBeFalse();
} );
