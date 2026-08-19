<?php
/**
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

use Soderlind\KjeksAiReviewer\Admin\ReviewerTab;
use Soderlind\KjeksAiReviewer\Rest\AcceptController;
use Soderlind\KjeksAiReviewer\Rest\SuggestController;

/**
 * Wires the add-on together.
 *
 * Everything is gated on the Kjeks registry being present. The AI-specific
 * surface degrades on its own when the site does not support the AI client, so
 * the plugin stays inert rather than fatal on unsupported installs.
 */
final class Plugin {

	private static ?self $instance = null;

	private Dependency $dependency;

	private function __construct() {
		$this->dependency = new Dependency();
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		if ( ! $this->dependency->has_kjeks() ) {
			add_action( 'network_admin_notices', array( $this, 'kjeks_missing_notice' ) );

			return;
		}

		( new SuggestController( $this->dependency ) )->register();
		( new AcceptController() )->register();
		( new ReviewerTab( $this->dependency ) )->register();
		( new Cron() )->register();
	}

	public function kjeks_missing_notice(): void {
		echo '<div class="notice notice-warning"><p>'
			. esc_html__( 'Kjeks AI Reviewer requires the Kjeks plugin to be active.', 'kjeks-ai-reviewer' )
			. '</p></div>';
	}

	public function dependency(): Dependency {
		return $this->dependency;
	}
}
