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
```

## Architecture

- **Services** (`app/Services/`): Core business logic split into focused classes:
  - `EvaluationOrchestrator` coordinates the pipeline with progress checkpoints
  - `Evaluation\EvaluationPromptBuilder` builds AI prompts from analysis data
  - `Evaluation\AiProviderClient` handles Gemini/OpenAI API calls with fallback
  - `Evaluation\EvaluationStorage` stores results with validation (score 0-10, 7 required dimensions, verdict normalization)
  - `GithubService` manages GitHub API calls with rate limit tracking and fork filtering
  - `InterviewQuestionService` generates AI-powered interview questions
  - `AiEvaluationService` is a thin orchestrator that delegates to the Evaluation sub-services
- **Controllers** are thin — delegate to services. Entry points: `CandidateController` (HR), `PublicApplyController` (self-service), `BatchController` (CSV upload), `ComparisonController` (side-by-side), `DashboardController` (cached stats).
- **Models**: `Candidate` → has many `Repository` → has one `RepositoryAnalysis`. `Candidate` → has one `Evaluation` → has many `EvaluationDimension` + `EvaluationComment` + `InterviewQuestion`. `Candidate` → has one `EvaluationProgress`. `Candidate` → belongs to `BatchJob`. `CandidateComparison` ↔ `Candidate` (pivot).
- **Enums**: `CandidateStatus` backed enum with `label()` and `color()` methods. Blade views must use `->value` for array keys, `->label()` for display.
- **Status pipeline**: `submitted` → `analyzing` → `evaluated` → `shortlisted`/`rejected`
- **SSE streaming**: `EvaluationProgressController` streams progress updates via `text/event-stream` with `Last-Event-ID` resumption support.
- **Middleware**: `CheckHrUser` (`hr.user`) gates HR-only routes. Registered in `bootstrap/app.php`.

## Key Gotchas

- **No auth implemented yet.** `Auth::id()` returns null. `submitted_by` and `hr_user_id` on comments will be null. Don't rely on auth guards.
- **Evaluation runs via queue.** `EvaluateCandidateJob` dispatches to the `evaluations` queue (database driver). Job has `$tries=3` and `$timeout=300`. Controller dispatches the job and redirects immediately.
- **AI provider fallback is wired.** `AiEvaluationService::callProviderWithFallback()` tries the primary provider first, then falls back to the secondary. Both providers must fail for evaluation to fail.
- **Tests use SQLite in-memory** with `RefreshDatabase`. External API calls (GitHub, Gemini) are NOT mocked in existing tests — they hit real APIs. Keep this in mind when writing new tests.
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
