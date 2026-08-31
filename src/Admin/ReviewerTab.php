<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer\Admin;

use Soderlind\Kjeks\AddonKit\AbstractSettingsTab;
use Soderlind\KjeksAiReviewer\Dependency;

/**
 * Registers the reviewer as a tab inside the Kjeks "Cookie Consent" screen.
 *
 * Extends the shared {@see AbstractSettingsTab}: the add-on registers its tab
 * through the `kjeks_settings_tabs` filter and enqueues its own React bundle
 * through the `kjeks_settings_enqueue_scripts` action, mounting into the
 * container the base class renders.
 */
final class ReviewerTab extends AbstractSettingsTab {

	private Dependency $dependency;

	public function __construct( ?Dependency $dependency = null ) {
		$this->dependency = $dependency ?? new Dependency();
	}

	/**
	 * Wire the tab, but only when the core tab shell is available and the AI
	 * client is usable. Never falls back to a standalone menu.
	 */
	public function hooks(): void {
		if ( ! is_admin() || ! self::core_supports_tabs() || ! $this->dependency->supports_ai() ) {
			return;
		}

		add_filter( 'kjeks_settings_tabs', array( $this, 'register_tab' ) );
		add_action( 'kjeks_settings_enqueue_scripts', array( $this, 'enqueue_tab_scripts' ), 10, 2 );
	}

	/**
	 * Back-compat alias for the previous public entry point.
	 */
	public function register(): void {
		$this->hooks();
	}

	protected function get_tab_slug(): string {
		return 'ai-reviewer';
	}

	protected function get_tab_label(): string {
		return __( 'AI Reviewer', 'kjeks-ai-reviewer' );
	}

	protected function get_text_domain(): string {
		return 'kjeks-ai-reviewer';
	}

	protected function get_build_path(): string {
		return KJEKS_AI_DIR . 'build/';
	}

	protected function get_build_url(): string {
		return KJEKS_AI_URL . 'build/';
	}

	protected function get_languages_path(): string {
		return KJEKS_AI_DIR . 'languages';
	}

	protected function get_plugin_version(): string {
		return KJEKS_AI_VERSION;
	}

	protected function get_localized_name(): string {
		return 'kjeksAiReviewer';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_localized_data(): array {
		return array(
			'restBase' => esc_url_raw( rest_url( 'kjeks-ai/v1' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
		);
	}
}
