<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

/**
 * Gate that answers whether the plugin can operate right now.
 *
 * The reviewer only functions when the Kjeks registry is available and the site
 * supports the core AI client. Everything degrades gracefully: when AI is not
 * supported the UI hides itself rather than erroring.
 */
final class Dependency {

	/**
	 * Whether the Kjeks plugin's registry class is loaded.
	 */
	public function has_kjeks(): bool {
		return class_exists( '\\Soderlind\\Kjeks\\Inventory\\TrackerRegistry' );
	}

	/**
	 * Whether the current WordPress install supports the AI client.
	 */
	public function supports_ai(): bool {
		return function_exists( 'wp_supports_ai' ) && function_exists( 'wp_ai_client_prompt' ) && \wp_supports_ai();
	}

	/**
	 * Whether both dependencies are satisfied.
	 */
	public function is_ready(): bool {
		return $this->has_kjeks() && $this->supports_ai();
	}
}
