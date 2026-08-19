# Kjeks AI Reviewer

An add-on for [Kjeks](https://github.com/soderlind/kjeks) that uses the WordPress 7 core AI client to **suggest** classifications and enriched metadata for unreviewed cookies in the Kjeks registry.

Every suggestion is **advisory**. The reviewer never sets a cookie's *reviewed* flag and never applies a classification on its own — an administrator confirms each one, which then runs the normal Kjeks review.

## Requirements

- Kjeks (active, network-wide)
- WordPress 7.0+ with a working AI provider (the plugin hides its UI when `wp_supports_ai()` is false)
- PHP 8.3+

## How it works

1. **Ground before generate.** Each unreviewed cookie is first matched against a pinned snapshot of the [Open Cookie Database](https://github.com/jkwakman/Open-Cookie-Database). A confident local match becomes a suggestion with **no AI call**. A partial match seeds the prompt so the model only fills gaps.
2. **Minimal prompt.** Only `name`, `domain`, `storage_type`, and `party` are sent to the model. The prompt is conservative: `necessary` is chosen only when a cookie is clearly essential, and unmapped categories fall back to `marketing` — never `necessary`.
3. **Strict validation.** The model must return a single JSON object. Low-confidence, malformed, or fabricated-URL responses are rejected and the cookie stays in manual review (fail closed).
4. **Separate storage.** Suggestions live in their own network option (`kjeks_ai_suggestions`), keyed by tracker id. Accepting or removing a cookie prunes its suggestion without ever mutating the registry as a side effect.

## Using it

The reviewer appears as an **AI Reviewer** tab on the Kjeks network admin screen (Network Admin → Kjeks).

- **Generate suggestions** — runs a batch (capped at 25 unique cookies) over the pending set.
- **Accept** — applies the suggested (or overridden) category and enriches empty fields, then records the normal Kjeks review.
- **Reject** — discards a suggestion without touching the registry.
- **Accept high-confidence** — bulk-accepts suggestions at ≥ 80% confidence, excluding `necessary` (which is single-accept only).
- **Weekly automation** — optional, opt-in background pass (off by default).

## Advisory only

This tool assists classification; it is **not** a compliance guarantee. Always review suggestions against your own knowledge of each cookie before accepting.

## Development

```bash
composer install
npm install
npm run build

composer test      # Pest
composer analyze   # PHPStan level 8
composer lint      # PHPCS (WordPress)
```

See [docs/grounding.md](docs/grounding.md) for how to refresh the bundled Open Cookie Database snapshot, and the [ADRs](docs/adr/) for the key design decisions.

## License

GPL-2.0-or-later. The bundled Open Cookie Database snapshot is licensed under GPL-3.0 by its authors.
