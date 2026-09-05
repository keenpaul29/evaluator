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

- **Services** (`app/Services/`): Core business logic. `EvaluationOrchestrator` coordinates the pipeline: `GithubService` (API calls) → `RepositoryAnalyzer` (static analysis) → `AiEvaluationService` (LLM call + storage). `ColoredCowContext` provides company values/tech stack for AI prompts.
- **Controllers** are thin — delegate to services. Two entry points for candidate creation: `CandidateController` (HR-initiated) and `PublicApplyController` (self-service).
- **Models**: `Candidate` → has many `Repository` → has one `RepositoryAnalysis`. `Candidate` → has one `Evaluation` → has many `EvaluationDimension` + `EvaluationComment`.
- **Status pipeline**: `submitted` → `analyzing` → `evaluated` → `shortlisted`/`rejected`

## Key Gotchas

- **No auth implemented yet.** `Auth::id()` returns null. `submitted_by` and `hr_user_id` on comments will be null. Don't rely on auth guards.
- **Evaluation runs synchronously** in the controller request, not via queue. The `app/Jobs/` directory is empty despite queue being configured. Requests may timeout for candidates with many repos.
- **AI provider fallback is not wired.** `config('services.ai.provider')` selects one provider. If it fails, evaluation fails — no automatic fallback to the other.
- **Tests use SQLite in-memory** with `RefreshDatabase`. External API calls (GitHub, Gemini) are NOT mocked in existing tests — they hit real APIs. Keep this in mind when writing new tests.
- **Tailwind loaded via CDN** in `app.blade.php` (`<script src="cdn.tailwindcss.com">`). The Vite-built CSS (`resources/css/app.css`) also imports Tailwind. Both paths exist.

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
- `OPENAI_API_KEY` — Fallback AI provider (not auto-failover yet)
- `AI_PROVIDER` — `gemini` or `openai`
