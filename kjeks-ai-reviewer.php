<?php
/**
 * Plugin Name:       Kjeks AI Reviewer
 * Plugin URI:        https://github.com/soderlind/kjeks-ai-reviewer
 * Description:       On WordPress 7+, uses the core AI client to suggest classifications and enriched metadata for unreviewed cookies in the Kjeks registry. Suggestions are advisory; an administrator confirms every one.
 * Version:           0.3.0
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Requires Plugins:  kjeks
 * Author:            Per Søderlind
 * Author URI:        https://soderlind.no
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kjeks-ai-reviewer
 * Domain Path:       /languages
 * Network:           true
 *
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\KjeksAiReviewer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KJEKS_AI_VERSION', '0.3.0' );
define( 'KJEKS_AI_FILE', __FILE__ );
define( 'KJEKS_AI_DIR', plugin_dir_path( __FILE__ ) );
define( 'KJEKS_AI_URL', plugin_dir_url( __FILE__ ) );

$kjeks_ai_autoload = KJEKS_AI_DIR . 'vendor/autoload.php';
if ( is_readable( $kjeks_ai_autoload ) ) {
	require $kjeks_ai_autoload;
}

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain( 'kjeks-ai-reviewer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		Plugin::instance()->boot();
	}
);
