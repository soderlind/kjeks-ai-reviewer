<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

/**
 * Optional, opt-in weekly background suggestion pass.
 *
 * Off by default. When enabled, one weekly event generates suggestions for the
 * pending batch (respecting the batch cap) so administrators return to a queue
 * of advisory suggestions rather than having to trigger them by hand.
 */
final class Cron {

	public const HOOK    = 'kjeks_ai_weekly_suggest';
	private const OPTION = 'kjeks_ai_settings';

	public function register(): void {
		add_action( self::HOOK, array( $this, 'run' ) );
		add_action( 'init', array( $this, 'sync_schedule' ) );
	}

	/**
	 * Ensures the scheduled event matches the current opt-in setting.
	 */
	public function sync_schedule(): void {
		$enabled   = $this->is_enabled();
		$scheduled = (bool) wp_next_scheduled( self::HOOK );

		if ( $enabled && ! $scheduled ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::HOOK );
		} elseif ( ! $enabled && $scheduled ) {
			$this->clear();
		}
	}

	public function run(): void {
		( new AiReviewer() )->suggest( false );
	}

	public function clear(): void {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
	}

	private function is_enabled(): bool {
		$settings = get_site_option( self::OPTION, array() );

		return is_array( $settings ) && ! empty( $settings['cron_enabled'] );
	}
}
