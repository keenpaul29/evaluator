# ColoredCow Technical Candidate Evaluator

![License](https://img.shields.io/badge/License-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-777BB4.svg)
![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20.svg)

An AI-powered, multi-dimensional technical evaluation application designed to streamline the talent acquisition process for engineering roles. This application leverages artificial intelligence (Gemini/OpenAI) to statically analyze a candidate's GitHub repositories and provide a comprehensive evaluation across multiple technical dimensions.

## 🚀 Features

- **Automated GitHub Analysis**: Fetches candidate repositories and performs static code analysis.
- **AI-Powered Evaluation**: Analyzes code quality, architecture, problem-solving skills, and more using LLMs.
- **Multi-Dimensional Scoring**: Candidates are scored on various dimensions like Code Quality, Testing, Documentation, etc.
- **Self-Service Application**: Candidates can apply publicly via the `/apply` endpoint.
- **HR Dashboard**: Internal view for HR and technical reviewers to manage and review candidates (`/dashboard`).
- **Visual Data**: Radar charts to visualize candidate performance across dimensions.

---

## 🛠 Tech Stack

- **Backend**: Laravel 13, PHP 8.3
- **Database**: SQLite
- **Frontend**: Blade, Tailwind CSS, Alpine.js (via CDN/Vite)
- **Charts**: Chart.js for data visualization
- **AI Integration**: Support for Google Gemini and OpenAI.

---

## 📋 Prerequisites

Before you begin, ensure you have met the following requirements:
- **PHP** >= 8.3
- **Composer** (latest)
- **Node.js** & **NPM**
- **SQLite**

---

## ⚙️ Installation & Setup

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
   AI_PROVIDER=gemini # or 'openai'
   GEMINI_API_KEY=your_gemini_key
   OPENAI_API_KEY=your_openai_key
   ```

4. **Run Full Setup**
   The project includes a composer script to automate the setup process (installs dependencies, generates key, creates SQLite DB, runs migrations, and builds frontend assets):
   ```bash
   composer setup
   ```

---

## 💻 Development

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

## 🧪 Testing

The test suite uses an in-memory SQLite database (`:memory:`). 
*Note: External API calls (GitHub, Gemini) are not mocked in existing tests and will hit real endpoints.*

**Run the full test suite:**
```bash
composer test
```

**Run a specific test file:**
```bash
php artisan test --filter=CandidateEvaluationTest
```

**Code Formatting:**
Ensure code standards are met using Laravel Pint:
```bash
./vendor/bin/pint
```

---

## 🏗 Architecture & Core Concepts

### Services Pipeline
The evaluation process is orchestrated in `app/Services/`:
1. **`EvaluationOrchestrator`**: The main coordinator.
2. **`GithubService`**: Fetches repositories and files via GitHub API.
3. **`RepositoryAnalyzer`**: Performs basic static analysis locally.
4. **`AiEvaluationService`**: Sends the aggregated data to the configured LLM for evaluation.
5. **`ColoredCowContext`**: Injects company-specific context (tech stack, values) into AI prompts.

### Controllers
- **`CandidateController`**: Handles internal HR operations (create, update, view candidates).
- **`PublicApplyController`**: Handles the public self-service application form.
- **`DashboardController`**: Overview for HR users.

### Data Models
- **`Candidate`**: Core entity (Status: `submitted` → `analyzing` → `evaluated` → `shortlisted`/`rejected`).
- **`Repository` & `RepositoryAnalysis`**: Tracks GitHub data and static analysis results.
- **`Evaluation`**: The overall AI evaluation result.
- **`EvaluationDimension`**: Specific scores (e.g., Code Quality: 8/10).
- **`EvaluationComment`**: Notes from human reviewers.

---

## ⚠️ Known Limitations & Gotchas

- **Authentication**: User authentication is not yet implemented. `Auth::id()` returns `null`. Comments and actions will currently not be tied to specific HR users.
- **Asynchronous Processing**: The AI evaluation runs asynchronously via `EvaluateCandidateJob` and Laravel Queues. Ensure the queue worker is running (`php artisan queue:work`), otherwise candidate evaluation will remain stuck in the 'analyzing' state.
- **AI Failover**: No automatic fallback exists. If `services.ai.provider` fails, the evaluation will fail. 

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
