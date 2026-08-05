# Form-Forge — AI-Powered Form Builder

Laravel 8 · Livewire 2 · MySQL 8 · Google Gemini (free tier)

## ⚠️ Scope note (read first)

This was built in a single day against a multi-part brief. Following the brief's own
"prioritise A → B → C → D, ship what's complete, state what's unfinished" rule:

| Part | Status |
|---|---|
| **A — Core Form Builder** | ✅ Built. Manual canvas + two-way JSON editor, 12 field types, per-field validation config, public fill URL with server-side validation, submissions list with pagination/search, CSV export. Drag-and-drop reordering was **simplified to up/down buttons** to fit the time budget. |
| **B — AI Generation** | ✅ Built. Prompt → queued job → Gemini → validate/repair/retry → editable form. AI editing of existing forms also works ("add a section", "make phone required", etc). Model/tokens/latency logged per call. |
| **C — Word/Excel Import** | ❌ **Not built today.** Biggest known gap — see "What's Next" below for the intended approach. |
| **D — Own Ideas** | 🟡 Partial. Two implemented for real (basic rate limiting/spam protection, one-step form versioning/rollback). A third (AI multi-language forms) works for free via the existing AI-edit endpoint but hasn't been separately tested. |

## 🔗 Live Demo

- **URL:** `[FILL IN once deployed]`
- **Sample form (seeded):** `/f/internship-application-demo`
- No login is required for this build — form management is unauthenticated for demo purposes (see Known Limitations).

## 🧱 Tech Stack

- **Backend:** PHP 8, Laravel 8.75
- **Frontend:** Livewire 2, Blade, Bootstrap 5 (via CDN), vanilla JS
- **Database:** MySQL 8
- **Queue:** Laravel queues (`database` driver by default) — `GenerateFormJob` and `EditFormWithAiJob`
- **AI Provider:** Google Gemini (`gemini-1.5-flash`), called directly from Laravel via `Http` facade — no separate FastAPI service, given the timeframe
- **New packages added:** `livewire/livewire`, `phpoffice/phpword` and `phpoffice/phpspreadsheet` (Part C import), `maatwebsite/excel` (kept as a dependency of the Excel stack)

## ⚙️ Setup Instructions

```bash
git clone https://github.com/Anushka-P-Webdeveloper/Form-Forge.git
cd Form-Forge

composer install
npm install && npm run dev

cp .env.example .env
php artisan key:generate

# set DB_* and GEMINI_API_KEY in .env — see below

php artisan migrate --seed
php artisan storage:link

# required: AI generation/edit only completes if a queue worker is running
php artisan queue:work

php artisan serve
```

Visit `http://localhost:8000/forms` to manage forms, or `http://localhost:8000/f/internship-application-demo`
for the seeded public form.

### Required Environment Variables

| Variable | Description |
|---|---|
| `DB_*` | Standard Laravel MySQL connection vars |
| `GEMINI_API_KEY` | Get a free key at https://aistudio.google.com/app/apikey |
| `GEMINI_MODEL` | Defaults to `gemini-1.5-flash` |
| `QUEUE_CONNECTION` | `database` by default — run `php artisan queue:table && php artisan migrate` if the jobs table isn't present |

> No real API keys are committed. `.env.example` was added to the repo (it didn't previously exist) with placeholder values only.

## 🏗️ Architecture Overview

```
Canvas (Livewire FormBuilder) ⇄ Raw JSON editor ⇄ FormSchemaService::validate()
                                        ↓
                            forms.schema (JSON, source of truth)

Prompt → AiFormGenerator (Livewire) → creates Form(status=generating)
       → GenerateFormJob (queued) → GeminiService → FormSchemaService::validate()/repair()
       → up to 3 attempts → Form updated, status=draft (or failed → blank valid form)

Edit instruction → FormBuilder::aiEdit() → EditFormWithAiJob (queued)
       → GeminiService::editForm() → validate/repair → Form::updateSchema()
       → previous_schema kept for one-step rollback

Public fill (/f/{slug}) → PublicFormFill (Livewire)
       → FormSchemaService::buildValidationRules() → Laravel Validator (server-side, not browser)
       → Submission stored → CSV export via streamed response
```

`FormSchemaService` is the single place that defines what a valid schema looks like —
the canvas, the JSON editor, the AI output, and (eventually) the import pipeline are
all meant to go through it, so there's exactly one definition of "valid form" in the
codebase.

## 🗄️ Database Schema

| Table | Purpose | Key Indexes |
|---|---|---|
| `forms` | `title`, unique `slug`, `schema` (json), `previous_schema` (json, for rollback), `status` (draft/published/generating/failed), `ai_generated` | unique index on `slug`; index on `status`; composite `(status, created_at)` |
| `submissions` | `form_id`, `data` (json), `ip_address` | index on `form_id`; composite `(form_id, created_at)` for the paginated/sorted list |
| `ai_generation_logs` | `form_id`, `type` (generate/edit), `prompt`, `model`, `prompt_tokens`, `completion_tokens`, `latency_ms`, `attempt`, `status`, `error` | index on `form_id` |

Migrations: `database/migrations/2024_01_01_0000*`. No separate `form_fields` table —
fields live inside `forms.schema` per the brief's "JSON schema is the single source
of truth" requirement, which avoids keeping two representations in sync.

No SQL dump is included yet — run `php artisan migrate --seed` to get an identical
schema plus the sample form.

## 🔌 Routes

| Method | Route | Purpose |
|---|---|---|
| `GET` | `/forms` | List forms + AI generator |
| `POST` | `/forms` | Create a blank draft form |
| `GET` | `/forms/{form}/edit` | Manual builder + JSON editor + AI edit |
| `GET` | `/forms/{form}/submissions` | Paginated, searchable submissions list |
| `GET` | `/forms/{form}/submissions/export` | Streamed CSV export |
| `GET` | `/f/{slug}` | Public fill page (throttled `30,1`) |

Livewire component actions (`save`, `submit`, `aiEdit`, etc.) run over Livewire's own
`/livewire/message/*` endpoint, not these routes directly — see Known Limitations re: rate limiting.

## 🤖 AI Prompt Strategy

- **System prompt** (`GeminiService::systemPrompt()`): instructs the model to return
  *only* raw JSON matching a fixed shape (`title`, `fields[]` with `key`, `type`,
  `label`, `validation`, `section`, etc.), restricted to the 12 known field types,
  with no markdown fences or commentary.
- **Output contract:** enforced by requesting `responseMimeType: application/json`
  from the Gemini API itself, plus defensive extraction in `GeminiService::extractJson()`
  that strips stray code fences and salvages the largest `{...}` block if the model
  wraps the JSON in prose anyway.
- **Validation → repair → retry:** `GenerateFormJob` runs up to 3 attempts. Each
  candidate schema goes through `FormSchemaService::validate()`; if invalid, a
  deterministic `repair()` pass runs first (maps hallucinated types like `"select"` →
  `dropdown` or `"string"` → `text`, fills missing labels from the key, adds default
  options to empty choice fields) before falling back to another AI call. A broken
  schema is **never persisted** — on total failure the form is saved with an empty,
  schema-valid field list and `status=failed` so the user always has something they
  can open and build manually.
- **Hallucinated field types:** handled entirely by the `repair()` type map rather
  than re-prompting, since it's cheaper and deterministic.
- **AI editing of existing forms:** `editForm()` sends the current schema plus the
  instruction and asks for the full updated schema back (not a diff) — simpler to
  validate, at the cost of higher token usage per edit.
- **Logging:** every attempt (success, repaired, or failed) writes an `ai_generation_logs`
  row with model, prompt/completion tokens, and latency in ms.

## 📄 Word/Excel Import — Not Implemented

This is the most significant gap in today's build. `maatwebsite/excel` and
`phpoffice/phpword` were added to `composer.json` in anticipation, but no
controller, job, or preview/mapping UI was written. See "What's Next" for the
intended approach.

## ✨ Part D — Additional Features

Full writeups with problem/implementation/trade-offs are in `DECISIONS.md`. Summary:

1. **Rate limiting & spam protection** — `throttle:30,1` on the public fill route,
   plus a CSS-hidden honeypot field on the submission form.
2. **Minimal form versioning (rollback)** — every schema save keeps the previous
   version in `previous_schema`; one-click rollback in the editor.
3. **AI multi-language forms** — not a separate feature, reuses the AI-edit pipeline
   (e.g. "translate labels to Hindi"); flagged as untested rather than claimed as done.

## 📥 Part C — Word/Excel Import

`/forms/import` → drag/drop or pick a `.docx`/`.xlsx` file → uploaded to `storage/app/imports`
→ `ParseFormImportJob` (queued, same pattern as Part B's `GenerateFormJob`) parses it →
preview/mapping screen at `/imports/{id}/review` → **Create Form from Import** persists a normal,
fully editable `Form` row through the same `FormSchemaService::validate()` path as manual save and
AI generation.

**Hybrid parsing, as asked for in the brief:**
- **Deterministic first** (`app/Services/ImportParserService.php`):
  - **Word** (`phpoffice/phpword`): `Title` elements / bold short lines → sections; lines ending in
    `?`, `:`, or containing `____` → fields; `ListItem` bullets right after a question → its options
    (auto-upgrades the field to `dropdown`); tables are intentionally *not* guessed at — reported as
    a warning instead ("report unparseable blocks clearly").
  - **Excel** (`phpoffice/phpspreadsheet`): two layouts, auto-detected from the header row —
    a **structured** `Label | Type | Required | Options | Help Text` sheet (explicit, no guessing
    needed), and a plain **header-row** sheet where each column header becomes a field label.
  - A keyword table (`email`, `phone`, `dob`/`date`, `upload`/`resume` → `file`, `rating`, etc.)
    infers the type from the label text — no AI call for the common case.
- **AI only for what's genuinely ambiguous**: labels the keyword table can't classify are batched
  into **one** `GeminiService::classifyFieldTypes()` call for the whole document (not one call per
  field). If the AI layer is unavailable or errors, the field just keeps its deterministic `text`
  fallback — an import never fails because of the AI layer.
- **Preview & mapping screen** (`app/Http/Livewire/ImportMapper.php`): every field shows a
  **Detected / AI-inferred / Guessed — check me** badge, and label, type, options and the required
  flag are all editable before the user commits. Nothing is written to the `forms` table until they
  click "Create Form from Import".
- **Never persists a broken schema**: the job re-validates (and repairs, via the same
  `FormSchemaService::repair()` used for AI generation) before ever showing the mapping screen; if
  zero fields are detected the import is marked `failed` with the warnings attached instead of
  showing an empty mapping screen.

Sample files used to build and test this (committed under `/samples`):
- `samples/internship-application.docx` — sections, required markers, free-text and choice
  questions, plus a deliberately unstructured table to exercise the warnings path.
- `samples/header-row-employee-survey.xlsx` — plain header-row layout.
- `samples/structured-job-application.xlsx` — structured `Label | Type | Required | Options` layout,
  covering every field type including `dropdown`, `radio`, `checkbox` and `file`.

**Known gaps:** table extraction from `.docx` is intentionally out of scope (flagged as a warning,
not guessed at); very large files are read into memory by PhpSpreadsheet/PhpWord rather than
streamed — fine at the sizes this brief targets, would need a streaming reader for very large sheets.

## ⚠️ Known Limitations

- Drag-and-drop field reordering was replaced with up/down buttons.
- No authentication/multi-tenancy — anyone with the `/forms` URL can manage all forms. Fine for a demo, not for production.
- Livewire's own AJAX endpoint isn't covered by the `throttle:30,1` route middleware (that only guards the initial page load), so real spam protection on submission itself currently relies on the honeypot alone.
- Submission search uses a `LIKE` scan over the JSON column — fine at demo scale, would need a generated/indexed column or full-text search at real scale.
- No automated test coverage yet.
- No Docker/CI setup yet.
- `previous_schema` gives one level of undo only, not full version history.

## 🧭 What's Next (with more time)

1. `.docx` table extraction for the import pipeline (currently reported as a warning, not parsed).
2. Auth + multi-tenant scoping on forms.
3. Move Livewire actions behind the same throttling as the page routes (custom middleware or Livewire's `component` rate limiting).
4. Pest test suite for `FormSchemaService` and `ImportParserService` (the two highest-value targets — everything else depends on them) and the submission validation path.
5. Full version history instead of single-step rollback.
6. Docker Compose + a basic CI workflow.

## 🎥 Walkthrough Recording

`[FILL IN if recorded]`

---

See `DECISIONS.md` for the assumptions made, Part D rationale, and trade-offs accepted.
