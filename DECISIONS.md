# DECISIONS.md

Built solo in a single day. This records the assumptions made where the brief was
silent, what got prioritized and why, the Part D choices, and what's next.

## 1. Assumptions

| Area | Assumption | Reasoning |
|---|---|---|
| Framework version | Kept Laravel 8 (brief asked for 10/11) | The repo already existed on Laravel 8; a mid-day framework upgrade was a bigger risk to a working demo than the version mismatch itself. Flagged explicitly in the README rather than left silent. |
| AI layer | Gemini called directly from Laravel (`GeminiService` via the `Http` facade), no separate FastAPI service | Positive signal in the brief, but one more moving part (separate service, separate deploy, inter-service auth) than a one-day budget could absorb reliably. |
| Auth / multi-tenancy | None — `/forms` is open | Brief didn't mandate auth for the mandatory parts; time went to A/B correctness instead. This is the first thing to add before this is anything but a demo. |
| Field types | Implemented all 12 from the brief's example list (text, textarea, number, email, phone, date, dropdown, radio, checkbox, file, heading, rating) | Covers the required "at least ten," plus `heading` for section breaks per the grouping requirement. |
| Drag & drop | Replaced with up/down reorder buttons | A real drag-and-drop implementation (Livewire + Sortable.js wiring, server sync) was the single highest-risk/lowest-priority item relative to time left — same end capability (reorder fields), lower risk of a broken UI on demo day. |
| Schema storage | Single `forms.schema` JSON column, no normalized `form_fields` table | The brief explicitly calls the JSON schema "the single source of truth" — normalizing it into rows would create two representations to keep in sync for no real benefit at this scale. |
| Word/Excel import | Not attempted today | Given a same-day deadline, A + B were prioritized to be complete and correct rather than spreading effort across A, B, and a rushed, buggy C. Explicit brief instruction: "ship what's complete... honest scoping is respected, silent gaps are not." |

## 2. Part D — Chosen Features & Why

### 2.1 Rate limiting & spam protection

- **User problem:** an unauthenticated public fill URL is an open target for scripted mass submissions.
- **Implementation:** `throttle:30,1` middleware on the `/f/{slug}` route, plus a CSS-hidden honeypot input (`website`) on the fill form — bots that fill every field trip it and get a silent no-op "success" instead of a rejected request that teaches them what to avoid.
- **Trade-offs accepted:** the route throttle only covers the initial page load, not the Livewire AJAX `submit()` call itself (Livewire's actions run through its own endpoint). Documented as a known limitation rather than glossed over — the honeypot is currently doing most of the real work.
- **With more time:** apply Livewire's per-component rate limiting (or a custom middleware keyed on IP + form ID) directly to the `submit` action, and add a simple submission-count-per-IP-per-hour cap stored in cache.

### 2.2 Minimal form versioning (rollback)

- **User problem:** an AI edit or a manual change can make a form worse, and there was no way to undo it.
- **Implementation:** `forms.previous_schema` stores the schema as it was immediately before the last save (manual or AI). `Form::updateSchema()` writes into it on every save; `Form::rollbackSchema()` swaps current ↔ previous. One button in the editor.
- **Trade-offs accepted:** this is one step of undo, not a version history. A second save after a rollback overwrites the ability to "redo" forward again.
- **With more time:** a proper `form_versions` table (form_id, schema, created_at, created_by, change_type) with a version list/diff view — the real "form versioning and rollback" feature from the brief's own list.

### 2.3 AI multi-language forms (via existing AI-edit pipeline)

- **User problem:** teams often need the same form in more than one language without rebuilding it by hand.
- **Implementation:** no new code — this rides entirely on `EditFormWithAiJob`/`GeminiService::editForm()`. An instruction like "translate labels to Hindi" is just another edit instruction; the model returns the full schema with translated labels, which goes through the same validate/repair path as any other edit.
- **Trade-offs accepted:** this is **not separately tested** today, so it's listed as a partial/likely-works item rather than a confirmed feature. Nothing stops the model from also translating `key` values (which would break lookups) — there's no explicit instruction in the system prompt telling it to leave `key` untouched, which is a real gap.
- **With more time:** add an explicit rule to the system prompt ("never modify `key` fields on edit, only human-facing text"), and a `locale` field on `forms` so a form can hold multiple language variants of the same schema rather than overwriting the original.

## 3. Other Trade-offs Accepted (Part A/B)

- **Submission search is a raw `LIKE` scan over the JSON `data` column.** Fine for a demo; would need a generated/virtual column with an index, or a move to full-text search, before this holds up at real submission volume.
- **CSV export streams via `chunk(200)` rather than loading all submissions into memory** — deliberate, so it doesn't fall over on a form with a large response count, even though nothing in today's demo data actually needs that.
- **AI generation retries up to 3 times against the model, with one deterministic repair pass in between**, rather than either retrying indefinitely or failing on the first bad response. Caps both latency and API cost per generation while still giving a hallucinated field type a real chance to self-correct before burning another paid call.
- **File uploads in the public form are stored via Laravel's local/public disk**, not queued or virus-scanned. Acceptable for a demo; not for production.

## 4. What's Unfinished / Known Gaps

- **Word/Excel import (Part C) — not built.** See README "What's Next" for the intended hybrid deterministic + AI approach.
- No automated tests (Pest/PHPUnit) were written today.
- No Docker or CI.
- No authentication — every form and every submission list is publicly reachable by anyone with the URL.
- Livewire actions aren't covered by the route-level throttle (see 2.1).
- `.env.example` didn't exist in the original repo and was added from scratch today — double-check it against your actual local `.env` before deploying.

## 5. What I'd Build Next (With Two More Weeks)

**Days 1–3**
- Word/Excel import: `phpoffice/phpword` for `.docx` (headings → sections, paragraph-style questions → fields, lists → options), `maatwebsite/excel` for a header-row `.xlsx` layout, with a preview/mapping screen before commit and AI used only to infer ambiguous field types — not to parse structure.
- Auth (Laravel Breeze or Fortify) + basic multi-tenant scoping on `forms`/`submissions`.

**Days 4–7**
- Full form version history (not just one-step rollback), with a diff view.
- Pest test suite, starting with `FormSchemaService` (validate/repair/rules) since everything else depends on its correctness, then the submission validation path.
- Fix Livewire action rate limiting properly.

**Days 8–14**
- Conditional logic / branching on fields.
- Redis-cached compiled validation rules per form (avoid rebuilding rules from JSON on every submission).
- Webhooks + a public read-only submissions API.
- Docker Compose for one-command local setup + a basic CI pipeline (lint + Pest on PR).

## 6. AI Coding Assistant Usage

AI assistance (Claude) was used throughout today's build — scaffolding migrations,
models, the Gemini integration, Livewire components, and these docs — with every
architectural choice (JSON-as-source-of-truth, validate/repair/retry loop, job
structure, what got cut for Part C) made and understood, and explainable line-by-line
in a live walkthrough per the brief's ground rules.
