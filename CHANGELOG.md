# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - Unreleased

### Added

- Initial release.
- AI-assisted, advisory classification suggestions for unreviewed Kjeks cookies via the WordPress 7 core AI client (`wp_ai_client_prompt()`), gated by `wp_supports_ai()`.
- Deterministic grounding against a pinned Open Cookie Database snapshot (ground before generate).
- Strict JSON schema validation with fail-closed behaviour (low confidence, malformed output, and fabricated URLs are rejected).
- Category mapping onto the four Kjeks categories; unmapped labels fall back to marketing, never necessary.
- Suggestions stored separately in the `kjeks_ai_suggestions` network option and pruned on accept/reject.
- REST API (`kjeks-ai/v1`): `GET /state`, `POST /suggest`, `POST /accept`, `POST /reject`, `POST /settings` — all requiring `manage_network`.
- "AI Reviewer" tab injected into the Kjeks network admin screen through the `kjeks.networkAdminTabs` filter.
- Single-item and high-confidence bulk accept (bulk excludes `necessary`).
- Optional, opt-in weekly background suggestion pass (off by default).
