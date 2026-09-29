# AGENTS.md

## Project

Laravel 13 + PHP 8.3 full-stack app. ColoredCow Technical Candidate Evaluator - AI-powered multi-dimensional technical evaluation for talent acquisition. SQLite database, Blade/Tailwind/Alpine.js frontend, Vite 8 build.

## Commands

```bash
# Full setup (installs deps, creates .env, generates key, migrates, builds frontend)
composer setup

# Dev server (runs artisan serve + queue worker + pail logs + vite concurrently)
composer dev

# Run all tests (clears config cache first, then phpunit)
composer test

# Run a single test file
php artisan test --filter=CandidateEvaluationTest

# Run a single test method
php artisan test --filter=CandidateEvaluationTest::test_candidate_can_be_created

# Code style fixer (Pint)
./vendor/bin/pint

# Fresh database with seed data
php artisan migrate:fresh --seed

# Live-LLM eval harness (tests/evals/, skipped by default). Requires AI API keys + EVALS_ENABLED=true
EVALS_ENABLED=true php artisan test tests/evals
```

## Architecture

- **Services:** Core business logic split into focused classes:
  - `EvaluationOrchestrator` coordinates the pipeline with progress checkpoints
  - `Evaluation\EvaluationPromptBuilder` builds AI prompts from analysis data, now feeding an ARTIFACTS section (top-N file excerpts + capped commit subjects, ~100KB deterministic cap via `MAX_PROMPT_BYTES`) with citation-requirements instructions
  - `Evaluation\AiProviderClient` handles Gemini/OpenAI API calls with fallback
  - `Evaluation\EvaluationStorage` stores results with validation (score 0-10, 7 required dimensions, verdict allowlist incl. `insufficient_data`)
  - `Evaluation\CitationValidator` (citation layer, post-generation): normalizes each dimension's `evidence` to citation objects `{file_path, commit_sha, url}` or the `{"insufficient": true}` marker, checks existence against the in-memory artifact registry, then runs a SEPARATE support-check AI pass (claim-vs-artifact over the narrowed artifact list); on support-check failure it flags affected dimensions insufficient rather than rejecting the evaluation
  - `Evaluation\VerdictCalculator` (deterministic, post-validation): excludes flagged dimensions from the verdict, requires ≥2 evidence-backed dims else `insufficient_data` (score 0.0), weighs ColValues 2× and gates strong verdicts behind it; iterates `VERDICT_SCORE_MAP` ascending
  - `Evaluation\ArtifactSelector` selects top-N artifact file paths; `Evaluation\ArtifactContentFetcher` fetches excerpts (reuses `getFileContent`)
  - `RepositoryAnalyzer` persists `artifact_file_paths` + `commit_samples` + `artifact_excerpts` onto `RepositoryAnalysis`
  - `GithubService` manages GitHub API calls with rate limit tracking and fork filtering
  - `InterviewQuestionService` generates AI-powered interview questions
  - `AiEvaluationService` is a thin orchestrator: builds prompt → provider fallback call → CitationValidator → VerdictCalculator → storage
- **Controllers** are thin — delegate to services. Entry points: `CandidateController` (HR), `PublicApplyController` (self-service), `BatchController` (CSV upload), `ComparisonController` (side-by-side), `DashboardController` (cached stats).
- **Models**: `Candidate` → has many `Repository` → has one `RepositoryAnalysis`. `Candidate` → has one `Evaluation` → has many `EvaluationDimension` + `EvaluationComment` + `InterviewQuestion`. `Candidate` → has one `EvaluationProgress`. `Candidate` → belongs to `BatchJob`. `CandidateComparison` ↔ `Candidate` (pivot).
- **Enums**: `CandidateStatus` backed enum with `label()` and `color()` methods. Blade views must use `->value` for array keys, `->label()` for display.
- **Status pipeline**: `submitted` → `analyzing` → `evaluated` → `shortlisted`/`rejected`
- **SSE streaming**: `EvaluationProgressController` streams progress updates via `text/event-stream` with `Last-Event-ID` resumption support.
- **Middleware**: `CheckHrUser` (`hr.user`) gates HR-only routes. Registered in `bootstrap/app.php`.

## Key Gotchas

- **No auth implemented yet.** `Auth::id()` returns null. `submitted_by` and `hr_user_id` on comments will be null. Don't rely on auth guards.
- **Evaluation runs via queue.** `EvaluateCandidateJob` dispatches to the `evaluations` queue (database driver). Job has `$tries=3` and `$timeout=600` (raised for the citation ladder: 2 providers × 120s `Http::timeout` × 2 passes ≈ 480s worst case). Controller dispatches the job and redirects immediately.
- **AI provider fallback is wired.** `AiEvaluationService::callProviderWithFallback()` tries the primary provider first, then falls back to the secondary. Both providers must fail for evaluation to fail.
- **Citation evidence is structural.** `EvaluationDimension.evidence` is an array of `{file_path, commit_sha, url}` objects or exactly `[{"insufficient": true}]`. A dimension flagged insufficient records its LLM score for display but is EXCLUDED from the verdict (see `VerdictCalculator`); the show page renders it de-emphasized and labels the verdict "provisional" when any flag is present. Don't write string evidence back into `evidence` — the validator and renderer expect citation objects.
- **Support-check needs a distinguishable prompt.** `CitationValidator::supportCheck` fires a SEPARATE AI call whose prompt contains "You verify whether cited repository artifacts". Http-faked tests covering the full ladder must return a `{"supported": [...]}` payload for that prompt and the evaluation payload otherwise (a single shared fake returns the eval JSON for the support-check call → `supported` key missing → all dims flagged insufficient).
- **Tests use SQLite in-memory** with `RefreshDatabase`. External API calls (GitHub, Gemini) are NOT mocked in existing tests — they hit real APIs. Keep this in mind when writing new tests. Tests that need deterministic verdicts must emit citation objects whose `file_path`/`commit_sha` exist in the fixture analysis's `artifact_file_paths`/`commit_samples`.
- **Live-LLM evals live in `tests/evals/`** (`LiveLlmTestCase` base, `EVALS_ENABLED=true` gate). Excluded from phpunit.xml, so default runs skip them; both tests auto-skip without the env flag. `LadderLiveEvalTest` exercises the full real ladder; `ArtifactVsLiveDivergenceTest` compares artifact-fed vs artifact-free verdicts (asserts no >1-tier divergence).
- **Tailwind loaded via CDN** in `app.blade.php` (`<script src="cdn.tailwindcss.com">`). The Vite-built CSS (`resources/css/app.css`) also imports Tailwind. Both paths exist.
- **SQLite busy_timeout** set to 10000ms in `config/database.php` for concurrent writes.
- **Dashboard stats are cached** for 60 seconds. Cache invalidates automatically when candidates are created/updated via `Candidate::boot()`.

## Database

SQLite file at `database/database.sqlite`. Tests use `:memory:`. Migrations are in `database/migrations/`. Seed data created via `DatabaseSeeder` — includes 2 HR users (admin + reviewer) and 3 sample candidates.

## Code Style

- 4-space indentation, LF line endings, UTF-8 (`.editorconfig`)
- Laravel Pint for PHP formatting
- No comments unless asked
- Blade templates in `resources/views/`, layouts in `resources/views/layouts/`
- Alpine.js for frontend interactivity (CDN-loaded), Chart.js for radar charts

## Env

Required env vars (see `.env.example`):
- `GITHUB_API_TOKEN` — GitHub API calls (60 req/hr unauthenticated, 5000 authenticated)
- `GEMINI_API_KEY` — Primary AI provider
- `OPENAI_API_KEY` — Fallback AI provider (auto-failover wired)
- `AI_PROVIDER` — `gemini` or `openai`
- `EVALS_ENABLED` — set `true` to run `tests/evals/` live-LLM harness (default `false`)

## Skill routing

When the user's request matches an available skill, invoke it via the Skill tool. When in doubt, invoke the skill.

Key routing rules:
- Product ideas/brainstorming → invoke /office-hours
- Strategy/scope → invoke /plan-ceo-review
- Architecture → invoke /plan-eng-review
- Design system/plan review → invoke /design-consultation or /plan-design-review
- Full review pipeline → invoke /autoplan
- Bugs/errors → invoke /investigate
- QA/testing site behavior → invoke /qa or /qa-only
- Code review/diff check → invoke /review
- Visual polish → invoke /design-review
- Ship/deploy/PR → invoke /ship or /land-and-deploy
- Save progress → invoke /context-save
- Resume context → invoke /context-restore
- Author a backlog-ready spec/issue → invoke /spec
