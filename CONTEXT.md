# Kjeks AI Reviewer

An add-on for the Kjeks cookie-consent plugin: on WordPress 7+, it uses the core AI client to **suggest** classifications and enriched metadata for unreviewed Trackers in the Kjeks registry. Suggestions are advisory only — an administrator confirms every one. It does not, and cannot, guarantee legal compliance.

## Language

**Suggestion**:
An AI-proposed, advisory, *unconfirmed* classification and metadata for an unreviewed Tracker. Never sets `reviewed`, never auto-applies `necessary`. An administrator accepts, edits, or rejects it.
*Avoid*: Decision, classification (those are the admin's), result

**Enrichment**:
Added descriptive metadata for a Tracker — provider, purpose, retention, party, documentation URL. The "more data per cookie" the reviewer produces.
*Avoid*: Metadata dump, details

**Grounding**:
The deterministic known-cookie lookup performed *before* asking the model, so well-known cookies are answered from a database rather than generated.
*Avoid*: Lookup (too generic), cache

**Confidence**:
The model's self-reported certainty for a Suggestion, shown to the administrator. Never a gate that auto-applies.
*Avoid*: Score, accuracy

**Pending**:
A Tracker in the Kjeks registry with `reviewed === false` — the reviewer's input set.
*Avoid*: Unreviewed (use in prose), new
