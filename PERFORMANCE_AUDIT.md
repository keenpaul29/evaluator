# Performance Audit — ColoredCow Technical Candidate Evaluator

> **Audit Date:** October 2026  
> **Application:** ColoredCow Technical Candidate Evaluator  
> **Stack:** Laravel 13 · PHP 8.3 · SQLite · Blade / Tailwind / Alpine.js · Vite 8  
> **Full Document:** See [docs/PERFORMANCE_AUDIT.md](file:///d:/website/operationsflow-hr/docs/PERFORMANCE_AUDIT.md)

---

## Executive Summary

The ColoredCow Technical Candidate Evaluator is an AI-powered multi-dimensional assessment platform. While functionally rich with GitHub ingestion, multi-provider LLM evaluations, citation verification, and SSE progress tracking, the codebase currently suffers from severe architectural bottlenecks that prevent reliable multi-candidate or batch evaluations.

### Key Metrics & Findings Overview

| Category | Severity | Primary Root Cause | Real-World Impact |
|:---|:---:|:---|:---|
| **GitHub Ingestion** | 🔴 **CRITICAL** | Up to 100 sequential HTTP calls per repo in `estimateLines()`; uncapped repo sync | 30–120s latency per repo; API rate limit exhaustion; job timeouts |
| **Batch Processing** | 🔴 **CRITICAL** | Synchronous GitHub API calls executed inside the CSV upload web request loop | HTTP 504 Gateway Timeouts on batch uploads with >5 candidates |
| **SSE Streaming** | 🔴 **CRITICAL** | Long-polling SSE loop with `sleep(2)` without `connection_aborted()` checking | Complete PHP-FPM / web worker pool exhaustion under concurrent viewers |
| **Database & Locking** | 🔴 **CRITICAL** | SQLite file locking with session, queue, cache, and app data all sharing one SQLite file | `Database is locked` crashes under queue concurrency; table scans from missing indexes |
| **AI Dual-Pass** | 🟠 **HIGH** | Synchronous `CitationValidator::supportCheck` doubles LLM latency and costs | 2× API costs; adds 15–45s latency per evaluation |
| **Frontend Delivery** | 🟠 **HIGH** | In-browser Tailwind JIT CDN (~340 KB) loaded on every page; unbundled global Chart.js | 200–400ms First Paint delay; high layout shifts and CPU overhead |

---

## Severity Classification

- 🔴 **CRITICAL**: Causes application failures, process timeouts, worker starvation, or database lock crashes under normal concurrent operations.
- 🟠 **HIGH**: Substantial latency or throughput penalty, unnecessary API costs, or heavy memory consumption.
- 🟡 **MEDIUM**: Inefficient query patterns, redundant computations, or sub-optimal caching.
- 🟢 **LOW**: Code cleanliness, minor memory optimizations, and future-proofing.

---

## 1. GitHub API & Repository Ingestion (The 100+ Request Bottleneck)

### 🔴 1.1 `estimateLines()` Fires Up to 100 Sequential HTTP Requests Per Repo
- **Location:** [`app/Services/RepositoryAnalyzer.php:92–113`](file:///d:/website/operationsflow-hr/app/Services/RepositoryAnalyzer.php#L92-L113)
- **Problem:** To estimate lines of code, `estimateLines()` loops through up to 50 code files and invokes `GithubService::getFileContent()` for each file. Internally, `getFileContent()` issues **two** serial HTTP requests (Contents API + download_url). This results in up to **100 serial HTTP round-trips** per repository purely for line-counting.
- **Latency Impact:** At an average GitHub API round-trip of 250ms, this single method consumes **25 to 35 seconds per repository**. For a candidate with 3 repositories, line estimation alone takes over 1.5 minutes and burns 300 GitHub API requests.
- **Remediation:**
  1. Do not download full files just to estimate line count. Use the blob `size` attribute already available in the Git Tree response (`lines ≈ size / 35`).
  2. If exact lines are required for key files, sample at most 3–5 representative files or execute parallel fetches using `Http::pool()`.

---

### 🔴 1.2 Uncapped Repository Syncing & Analysis
- **Location:** [`app/Services/GithubService.php:286–300`](file:///d:/website/operationsflow-hr/app/Services/GithubService.php#L286-L300) & [`app/Services/EvaluationOrchestrator.php:93–117`](file:///d:/website/operationsflow-hr/app/Services/EvaluationOrchestrator.php#L93-L117)
- **Problem:** When syncing a candidate's GitHub profile, `syncCandidateRepos()` requests up to **100 repositories** (`perPage = 100`). It creates all non-fork repositories in the database. Subsequently, `EvaluationOrchestrator::analyzeRepos()` loops through **every single repository** linked to the candidate:
  ```php
  foreach ($repositories as $repo) {
      $analysis = $this->analyzer->analyze($repo);
  }
  ```
  If an applicant has 15 personal repositories on GitHub, the orchestrator analyzes all 15 serially. At 50–100 calls per repo, this executes **750 to 1,500 GitHub API calls** for a single candidate, exceeding the candidate timeout (600s) and exhausting the GitHub token rate limit (5,000/hr).
- **Remediation:**
  - Cap analyzed repositories to the top 3–5 most relevant repos (ordered by stargazers count, recency of push, and primary language relevance).
  - Allow HR reviewers or candidates to select the primary repositories evaluated.

---

### 🟠 1.3 `getFileContent()` Double-Fetches Base64 Content
- **Location:** [`app/Services/GithubService.php:180–205`](file:///d:/website/operationsflow-hr/app/Services/GithubService.php#L180-L205)
- **Problem:** GitHub's Contents API returns the file content as base64-encoded text for files under 1 MB (`$response['content']`). Currently, `getFileContent()` ignores the base64 content and issues a second request to `download_url`.
- **Latency Impact:** Doubles the HTTP round-trip latency for every single file retrieved across the entire system (`estimateLines`, `ArtifactContentFetcher`, etc.).
- **Remediation:** Decode `$contents['content']` directly using `base64_decode()`; only fall back to `download_url` for files without inline base64 content.

---

### 🟠 1.4 Redundant Artifact Excerpt Fetching
- **Location:** [`app/Services/Evaluation/ArtifactContentFetcher.php:17–39`](file:///d:/website/operationsflow-hr/app/Services/Evaluation/ArtifactContentFetcher.php#L17-L39)
- **Problem:** `RepositoryAnalyzer` runs `estimateLines()` across 50 files. Later in the same pipeline, `ArtifactContentFetcher` fetches 10 artifact files via `GithubService::getFileContent()`. Files like `README.md`, `composer.json`, and core controllers are often fetched twice within the same job.
- **Remediation:** Introduce an in-memory repository file cache in `GithubService` scoped to the current execution.

---

### 🟡 1.5 Rate-Limit Sleep Blocks Queue Worker Threads
- **Location:** [`app/Services/GithubService.php:54–63`](file:///d:/website/operationsflow-hr/app/Services/GithubService.php#L54-L63)
- **Problem:** When a 429 or rate-limit status is returned by GitHub, `handleRateLimit()` executes `sleep($waitSeconds)`. If rate-limited for 60 seconds, the queue worker process halts completely in synchronous sleep, blocking all other queued jobs on that worker.
- **Remediation:** Throw a `RateLimitedException` and utilize Laravel queue's `release($waitSeconds)`.

---

## 2. Real-Time SSE & Web Server Starvation

### 🔴 2.1 Web Worker Starvation via Long-Lived Synchronous SSE Loops
- **Location:** [`app/Http/Controllers/Api/EvaluationProgressController.php:46–93`](file:///d:/website/operationsflow-hr/app/Http/Controllers/Api/EvaluationProgressController.php#L46-L93)
- **Problem:** The SSE stream uses an internal `while ($iteration < $maxIterations)` loop with `sleep(2)` spanning up to **600 iterations (20 minutes)**. Under PHP-FPM or `php artisan serve`, each connection locks a worker process. 10 open SSE tabs exhaust the entire worker pool, causing all other incoming HTTP requests to time out.
- **Remediation:**
  1. Lower `maxIterations` to 120 (4 minutes) and increase polling interval to 3–4 seconds.
  2. Long term: Replace SSE polling with event broadcasting using **Laravel Reverb**, Pusher, or Redis Pub/Sub.

---

### 🟠 2.2 Unchecked Client Disconnects (Zombie Loop Execution)
- **Location:** [`app/Http/Controllers/Api/EvaluationProgressController.php:49–93`](file:///d:/website/operationsflow-hr/app/Http/Controllers/Api/EvaluationProgressController.php#L49-L93)
- **Problem:** Lacks `connection_aborted()` checks. If a user closes the browser tab, the server continues polling every 2 seconds for up to 20 minutes.
- **Remediation:** Check `if (connection_aborted()) break;` at the start of each iteration.

---

### 🟠 2.3 SSE Polling Query Density & DB Overload
- **Location:** [`app/Http/Controllers/Api/EvaluationProgressController.php:50–51`](file:///d:/website/operationsflow-hr/app/Http/Controllers/Api/EvaluationProgressController.php#L50-L51)
- **Problem:** Each iteration executes `$candidate->refresh()` and `$candidate->latestProgress`, firing 2 full model queries every 2 seconds per connected client.
- **Remediation:** Query only needed columns directly from `evaluation_progress` table using DB query builder.

---

## 3. Database Layer & SQLite Concurrency Architecture

### 🔴 3.1 SQLite Concurrency & File Lock Contention
- **Location:** [`config/database.php:35–45`](file:///d:/website/operationsflow-hr/config/database.php#L35-L45)
- **Problem:** With SQLite, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, and `CACHE_STORE=database` all write to `database.sqlite` simultaneously. Default `journal_mode=null` causes full database file locks on write, triggering `database is locked` exceptions under concurrent queue and web traffic.
- **Remediation:** Set `'journal_mode' => 'WAL'` and `'synchronous' => 'NORMAL'` in `config/database.php`.

---

### 🟠 3.2 Missing Indexes on Foreign Keys & Hot Query Columns
- `evaluation_progress.candidate_id`: Polled by SSE and `Candidate::latestProgress`.
- `candidates.batch_id`: Scanned by `$batch->candidates` on batch show pages.
- `interview_questions.evaluation_id`: Scanned when displaying candidate interview questions.
- `candidate_comparison_candidate.candidate_id`: Scanned for reverse candidate comparison lookups.
- **Remediation:** Add migration creating indexes on these columns.

---

### 🟠 3.3 Accessor Query Pitfalls & N+1 Latent Traps
- `Candidate::getLatestProgressAttribute`: Fires a query every time it's accessed.
- `Evaluation::getDimensionScore()`: Queries `->dimensions()->where(...)` instead of filtering the loaded collection.
- `CandidateController::export()`: Missing eager load on `evaluation`, causing N+1 in `CsvExporter`.
- `BatchController::store()`: `HrUser::first()` executed inside row loop.

---

## 4. Batch Ingestion & Queue Architecture

### 🔴 4.1 Synchronous GitHub API Execution in CSV Upload Web Request
- **Location:** [`app/Http/Controllers/BatchController.php:60–94`](file:///d:/website/operationsflow-hr/app/Http/Controllers/BatchController.php#L60-L94)
- **Problem:** During CSV batch upload, `syncCandidateRepoUrls()` calls `GithubService::getRepo()` synchronously for every candidate repository inside the web request loop. A batch of 30 candidates with 2 repos triggers 60 synchronous external HTTP requests, resulting in HTTP 504 timeouts.
- **Remediation:** Create candidate records with raw repo URLs and dispatch background jobs to perform repository synchronization asynchronously.

---

## 5. AI Evaluation Pipeline & Cost Multipliers

### 🟠 5.1 Dual-Pass Citation Support Check Doubles Latency & Cost
- **Location:** [`app/Services/Evaluation/CitationValidator.php:123–204`](file:///d:/website/operationsflow-hr/app/Services/Evaluation/CitationValidator.php#L123-L204)
- **Problem:** Executes a second synchronous LLM call (`supportCheck`) after initial evaluation parsing. Doubles API costs and adds 15–45s latency.
- **Remediation:** Merge claim verification criteria into primary prompt, or run support check as an async post-processing job.

---

### 🟡 5.2 Quadratic String Concatenation in Artifact Prompt Capping
- **Location:** [`app/Services/Evaluation/EvaluationPromptBuilder.php:173–202`](file:///d:/website/operationsflow-hr/app/Services/Evaluation/EvaluationPromptBuilder.php#L173-L202)
- **Problem:** `strlen(implode('', $kept))` re-allocates and joins array on every loop iteration ($O(N^2)$).
- **Remediation:** Use a running integer counter `$currentBytes`.

---

## 6. Frontend Delivery & Asset Pipeline

### 🟠 6.1 In-Browser Tailwind JIT CDN Compiler
- **Location:** [`resources/views/layouts/app.blade.php:7–41`](file:///d:/website/operationsflow-hr/resources/views/layouts/app.blade.php#L7-L41)
- **Problem:** Loads `<script src="https://cdn.tailwindcss.com"></script>` (~340 KB JS) which compiles CSS on the fly in the browser. Blocks rendering and adds 200–400ms to First Paint.
- **Remediation:** Remove CDN script and switch to Vite-compiled CSS bundle via `@vite(['resources/css/app.css', 'resources/js/app.js'])`.

---

## 7. Quick Wins Matrix (< 2 Hours Total Effort)

| Item | File | Estimated Effort | Impact |
|:---|:---|:---:|:---:|
| **1. Blob Size Heuristic** | `app/Services/RepositoryAnalyzer.php` | 15 mins | Eliminates up to 100 HTTP calls per repo |
| **2. Base64 Direct Decode** | `app/Services/GithubService.php` | 15 mins | Halves GitHub API round-trips across all file reads |
| **3. SQLite WAL & Pragmas** | `config/database.php` | 5 mins | Drastically reduces `Database is locked` concurrency crashes |
| **4. Foreign Key Indexes** | Migration | 20 mins | Eliminates full table scans in SSE and batch views |
| **5. Eager-load Evaluation in CSV** | `app/Http/Controllers/CandidateController.php` | 5 mins | Fixes N+1 query loop on CSV export |
| **6. SSE Disconnect Guard** | `app/Http/Controllers/Api/EvaluationProgressController.php` | 10 mins | Prevents orphan worker loops on closed tabs |
| **7. Static Context Cache** | `app/Services/ColoredCowContext.php` | 10 mins | Eliminates repeated 15 KB context array reconstructions |

*For complete implementation details, benchmarks, and architectural flowcharts, see [docs/PERFORMANCE_AUDIT.md](file:///d:/website/operationsflow-hr/docs/PERFORMANCE_AUDIT.md).*
