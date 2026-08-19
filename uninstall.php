<?php
/**
 * Uninstall handler: removes the AI suggestions network option.
 *
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( is_multisite() ) {
	delete_site_option( 'kjeks_ai_suggestions' );
	delete_site_option( 'kjeks_ai_settings' );
} else {
	delete_option( 'kjeks_ai_suggestions' );
	delete_option( 'kjeks_ai_settings' );
}
