<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer\Admin;

use Soderlind\KjeksAiReviewer\Dependency;

/**
 * Registers the reviewer as a tab inside the Kjeks network admin screen.
 *
 * Rather than owning a page, the add-on enqueues a bundle on the Kjeks network
 * page. That bundle registers a tab through the `kjeks.networkAdminTabs` JS
 * filter, so the reviewer appears alongside the existing Cookies tab.
 */
final class ReviewerTab {

	/**
	 * Matches the Kjeks network page slug (page hook suffix).
	 */
	private const KJEKS_HOOK = 'toplevel_page_kjeks-network';

	private Dependency $dependency;

	public function __construct( ?Dependency $dependency = null ) {
		$this->dependency = $dependency ?? new Dependency();
	}

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue( string $hook ): void {
		if ( self::KJEKS_HOOK !== $hook ) {
			return;
		}

		// Nothing to show if the AI client is unavailable; the tab hides itself.
		if ( ! $this->dependency->supports_ai() ) {
			return;
		}

		$asset_file = KJEKS_AI_DIR . 'build/index.asset.php';
		$asset      = is_readable( $asset_file )
			? include $asset_file
			: array(
				'dependencies' => array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-hooks', 'wp-i18n' ),
				'version'      => KJEKS_AI_VERSION,
			);

		// Depend on kjeks-network so this loads after it, sharing the same hook registry.
		$dependencies   = $asset['dependencies'];
		$dependencies[] = 'kjeks-network';

		wp_enqueue_script(
			'kjeks-ai-reviewer',
			KJEKS_AI_URL . 'build/index.js',
			$dependencies,
			$asset['version'],
			true
		);

		if ( is_readable( KJEKS_AI_DIR . 'build/index.css' ) ) {
			wp_enqueue_style( 'kjeks-ai-reviewer', KJEKS_AI_URL . 'build/index.css', array( 'wp-components' ), $asset['version'] );
		}

		wp_set_script_translations( 'kjeks-ai-reviewer', 'kjeks-ai-reviewer', KJEKS_AI_DIR . 'languages' );

		wp_localize_script(
			'kjeks-ai-reviewer',
			'kjeksAiReviewer',
			array(
				'restBase' => esc_url_raw( rest_url( 'kjeks-ai/v1' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
