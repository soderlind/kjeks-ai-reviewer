=== Kjeks AI Reviewer ===
Contributors: soderlind
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.3
Requires Plugins: kjeks
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-assisted, advisory classification suggestions for unreviewed cookies in Kjeks, using the WordPress core AI client.

== Description ==

Kjeks AI Reviewer uses the WordPress 7 core AI client to suggest classifications
and enriched metadata for unreviewed cookies in the Kjeks registry.

Every suggestion is advisory. The reviewer never marks a cookie reviewed and
never applies a classification on its own — an administrator confirms each one,
which then runs the normal Kjeks review.

Cookies are first grounded against a pinned Open Cookie Database snapshot, so
common cookies are classified accurately without an AI call. Only minimal
metadata (name, domain, storage type, party) is sent to the model, and every
response is strictly validated: low-confidence, malformed, or fabricated-URL
answers are rejected and left for manual review.

This tool assists classification; it is not a compliance guarantee.

== Installation ==

1. Install and network-activate Kjeks.
2. Upload and network-activate Kjeks AI Reviewer.
3. Open Network Admin → Kjeks and select the "AI Reviewer" tab.

The tab is hidden if the site does not support the AI client.

== Changelog ==

= 0.1.0 =
* Initial release.
