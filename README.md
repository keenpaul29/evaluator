# ColoredCow Technical Candidate Evaluator

![License](https://img.shields.io/badge/License-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-777BB4.svg)
![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20.svg)

An AI-powered, multi-dimensional technical evaluation application designed to streamline the talent acquisition process for engineering roles. This application leverages artificial intelligence (Gemini/OpenAI) to statically analyze a candidate's GitHub repositories and provide a comprehensive evaluation across multiple technical dimensions.

## Features

### Core Evaluation
- **Automated GitHub Analysis**: Fetches candidate repositories and performs static code analysis with fork filtering (excludes forked repos).
- **AI-Powered Evaluation**: Analyzes code quality, architecture, problem-solving skills, and more using LLMs.
- **Multi-Dimensional Scoring**: Candidates are scored across 7 dimensions: Code Quality, Technical Judgment, Values Alignment, Communication, Problem Complexity, Learning Trajectory, and Technical Breadth.
- **AI Output Validation**: Ensures evaluations contain valid scores (0-10), correct verdicts, and all 7 required dimensions before storage.
- **AI Provider Fallback**: Automatic failover from primary to secondary AI provider (Gemini/OpenAI) if the first provider fails.

### Candidate Management
- **HR-Initiated Candidates**: Internal HR users can create candidates via `/candidates` (requires `hr.user` middleware).
- **Self-Service Application**: Candidates can apply publicly via the `/apply` endpoint.
- **Batch CSV Upload**: Upload multiple candidates at once via CSV (max 50 per batch) at `/batches/create`.
- **Status Pipeline**: `submitted` → `analyzing` → `evaluated` → `shortlisted`/`rejected` using typed `CandidateStatus` enum with `label()` and `color()` methods.

### Real-Time Progress
- **SSE Streaming**: `/api/evaluations/{candidate}/progress` streams real-time progress updates via Server-Sent Events with `Last-Event-ID` resumption support.
- **Progress Checkpoints**: Evaluation pipeline tracks progress through queued → analyzing → scoring → complete/failed stages.

### Interview Preparation
- **AI-Generated Questions**: Automatically generates dimension-specific interview questions with file references for each low-scoring area.
- **File References**: Questions link to specific code files for targeted discussion during interviews.

### Candidate Comparison
- **Side-by-Side Comparison**: Compare up to 5 candidates with radar chart visualization at `/comparisons`.
- **Persistent Comparisons**: Save and manage comparison sets for future reference.

### Dashboard & Notifications
- **Cached Dashboard Stats**: 60-second cache on dashboard statistics with automatic invalidation on data changes.
- **Email Notifications**: Sends evaluation completion emails to candidates who provided email addresses.

### Technical Features
- **GitHub Rate Limit Tracking**: Monitors API rate limits and automatically retries on 429 responses.
- **SQLite Busy Timeout**: 10-second busy timeout for concurrent write operations.
- **CheckHrUser Middleware**: Gates HR-only routes with `hr.user` middleware alias.

---

## Tech Stack

- **Backend**: Laravel 13, PHP 8.3
- **Database**: SQLite with WAL mode
- **Frontend**: Blade, Tailwind CSS, Alpine.js (via CDN/Vite)
- **Charts**: Chart.js for radar charts (candidate dimensions and comparisons)
- **AI Integration**: Google Gemini and OpenAI with automatic provider fallback
- **Build Tools**: Vite 8, Composer

---

## Prerequisites

Before you begin, ensure you have met the following requirements:
- **PHP** >= 8.3
- **Composer** (latest)
- **Node.js** & **NPM**
- **SQLite**

---

## Installation & Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd operationsflow-hr
   ```

2. **Environment Configuration**
   Copy the example environment file:
   ```bash
   cp .env.example .env
   ```

3. **Configure API Keys**
   Update your `.env` file with the necessary credentials:
   ```env
   # GitHub API Token for repository fetching (prevents rate limiting)
   GITHUB_API_TOKEN=your_github_token
   
   # AI Provider Configuration
   AI_PROVIDER=gemini # primary provider; use 'openai' to prefer OpenAI first
   GEMINI_API_KEY=your_gemini_key
   OPENAI_API_KEY=your_openai_key
   GEMINI_MODEL=gemini-1.5-flash
   OPENAI_MODEL=gpt-4o-mini
   ```

4. **Run Full Setup**
   The project includes a composer script to automate the setup process (installs dependencies, generates key, creates SQLite DB, runs migrations, and builds frontend assets):
   ```bash
   composer setup
   ```

---

## Development

Start the local development server. This custom command runs `artisan serve`, the queue worker, logs watcher, and Vite server concurrently:

```bash
composer dev
```

### Database Seeding
To get started quickly with sample data (creates HR users and sample candidates):
```bash
php artisan migrate:fresh --seed
```

---

## Testing

The test suite uses an in-memory SQLite database (`:memory:`). GitHub and AI HTTP calls are faked in feature tests so the suite is deterministic and safe to run without real API credentials.

**Run the full test suite:**
```bash
composer test
```

**Run a specific test file:**
```bash
php artisan test --filter=CandidateEvaluationTest
```

**Run a specific test method:**
```bash
php artisan test --filter=CandidateEvaluationTest::test_candidate_can_be_created
```

**Code Formatting:**
Ensure code standards are met using Laravel Pint:
```bash
./vendor/bin/pint
```

---

## Architecture & Core Concepts

### Services Pipeline
The evaluation process is orchestrated in `app/Services/`:
1. **`EvaluationOrchestrator`**: The main coordinator with progress checkpoints.
2. **`Evaluation\EvaluationPromptBuilder`**: Builds AI prompts from analysis data with company context.
3. **`Evaluation\AiProviderClient`**: Handles Gemini/OpenAI API calls with automatic failover.
4. **`Evaluation\EvaluationStorage`**: Stores results with validation (score 0-10, 7 required dimensions, verdict normalization).
5. **`GithubService`**: Fetches repositories and files via GitHub API with rate limit tracking and fork filtering.
6. **`RepositoryAnalyzer`**: Performs basic static analysis locally.
7. **`InterviewQuestionService`**: Generates AI-powered interview questions with file references.
8. **`AiEvaluationService`**: Thin orchestrator that delegates to the Evaluation sub-services.

### Controllers
- **`CandidateController`**: Handles internal HR operations (create, update, view candidates). Creates progress tracking before dispatching evaluation job.
- **`PublicApplyController`**: Handles the public self-service application form with progress tracking.
- **`BatchController`**: Manages CSV uploads and batch processing of multiple candidates.
- **`ComparisonController`**: Creates and manages candidate comparison sets with radar chart visualization.
- **`DashboardController`**: Cached overview statistics for HR users.
- **`EvaluationProgressController`**: SSE endpoint streaming real-time evaluation progress.
- **`EvaluationStatusController`**: JSON API for checking candidate evaluation status.

### Data Models
- **`Candidate`**: Core entity with typed `CandidateStatus` enum. Relationships: has many `Repository`, has one `Evaluation`, has one `EvaluationProgress`, belongs to `BatchJob`.
- **`Repository` & `RepositoryAnalysis`**: Tracks GitHub data and static analysis results.
- **`Evaluation`**: The overall AI evaluation result with `interviewQuestions()` relationship.
- **`EvaluationDimension`**: Specific scores across 7 dimensions (e.g., Code Quality: 8/10) with evidence and weights.
- **`EvaluationComment`**: Notes from human reviewers.
- **`EvaluationProgress`**: Tracks evaluation pipeline progress with `updateProgress()`, `markComplete()`, `markFailed()` methods.
- **`InterviewQuestion`**: AI-generated interview questions with dimension, file references, and reasoning.
- **`BatchJob`**: Tracks CSV upload batches with progress counting and automatic completion detection.
- **`CandidateComparison`**: Manages comparison sets with sortable candidate pivot.

### Enums
- **`CandidateStatus`**: Backed enum with `label()` and `color()` methods. Blade views must use `->value` for array keys, `->label()` for display.

### Middleware
- **`CheckHrUser`** (`hr.user`): Gates HR-only routes. Registered in `bootstrap/app.php`.

### Routes
| Route | Method | Description |
|-------|--------|-------------|
| `/` | GET | Dashboard with cached stats |
| `/candidates` | GET | Candidate list |
| `/candidates/create` | GET | HR candidate creation form |
| `/candidates` | POST | Create candidate (HR) |
| `/candidates/{id}` | GET | Candidate detail with evaluation |
| `/candidates/{id}/shortlist` | POST | Shortlist candidate |
| `/candidates/{id}/reject` | POST | Reject candidate |
| `/apply` | GET | Public application form |
| `/apply` | POST | Submit application |
| `/batches` | GET | Batch list |
| `/batches/create` | GET | CSV upload form |
| `/batches/{id}` | GET | Batch detail with candidates |
| `/comparisons` | GET | Comparison list |
| `/comparisons` | POST | Create comparison |
| `/comparisons/{id}` | GET | Comparison detail with radar chart |
| `/api/evaluation-status/{id}` | GET | Evaluation status JSON |
| `/api/evaluations/{id}/progress` | GET | SSE progress stream |

---

## Known Limitations & Gotchas

- **Authentication**: User authentication is not yet implemented. `Auth::id()` returns `null`. `submitted_by` and `hr_user_id` on comments will be null. Don't rely on auth guards.
- **Asynchronous Processing**: The AI evaluation runs asynchronously via `EvaluateCandidateJob` and Laravel Queues. Ensure the queue worker is running (`php artisan queue:work`), otherwise candidate evaluation will remain stuck in the 'analyzing' state.
- **AI Failover**: Set `AI_PROVIDER` to the preferred provider. If that provider fails and the other provider has an API key configured, the evaluation automatically retries with the fallback model and records the model actually used.
- **Dashboard Caching**: Dashboard stats are cached for 60 seconds. Cache invalidates automatically when candidates are created/updated via `Candidate::boot()`.
- **SQLite Concurrent Writes**: SQLite busy timeout set to 10 seconds to handle concurrent writes from queue workers.
- **Batch Upload Limit**: Maximum 50 candidates per CSV upload to prevent timeout issues.
- **GitHub Rate Limits**: Unauthenticated: 60 requests/hour. Authenticated: 5,000 requests/hour. Always configure `GITHUB_API_TOKEN`.

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
