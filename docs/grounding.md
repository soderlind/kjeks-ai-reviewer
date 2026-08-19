# Refreshing the Open Cookie Database snapshot

The reviewer grounds cookies against a **pinned snapshot** bundled at
[`data/open-cookie-database.json`](../data/open-cookie-database.json). Pinning
keeps grounding deterministic and reproducible across sites and CI runs.

The bundled file is a curated subset (`snapshot: curated-sample-v1`) of the
[Open Cookie Database](https://github.com/jkwakman/Open-Cookie-Database)
(GPL-3.0). To refresh it:

1. Download the upstream `open-cookie-database.csv`.
2. Convert the rows you want to the bundled JSON shape. Each entry uses:

   | Field               | Notes                                             |
   | ------------------- | ------------------------------------------------- |
   | `name`              | Cookie/storage key name.                          |
   | `domain`            | Owning domain (optional; improves match scoring). |
   | `pattern`           | A regex matched against the cookie name.          |
   | `provider`          | Vendor / product name.                            |
   | `category`          | Upstream category label (mapped to Kjeks).        |
   | `purpose`           | One-sentence description.                         |
   | `retention`         | Human phrase, e.g. `1 year`, `session`.           |
   | `party`             | `first` or `third`.                               |
   | `documentation_url` | Real vendor URL, or empty.                        |

3. Bump the `_meta.snapshot` value (e.g. `curated-sample-v2`).
4. Run `composer test` — [`tests/Unit/OpenCookieDatabaseTest.php`](../tests/Unit/OpenCookieDatabaseTest.php)
   exercises name and pattern matching.

## Matching rules

`OpenCookieDatabase::match()` scores each entry:

- **+2** when the `pattern` regex matches the cookie name (or the `name` matches exactly, case-insensitive).
- **+1** when the entry `domain` is a substring of the observed domain.

The highest-scoring entry wins; a score of `0` is no match. An entry is
considered *complete* (usable without an AI call) when it has a `category`,
`provider`, and `purpose`.
