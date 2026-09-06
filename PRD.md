# Product Requirement Document (PRD)

## Project Name: ColoredCow Technical Candidate Evaluator

**Subtitle:** AI-Powered Multi-Dimensional Technical Evaluation for Talent Acquisition

**Status:** In Development

**Author:** Puspal Paul

**Target Stack:** PHP 8.3+, Laravel 13, Gemini / OpenAI API, Blade / Tailwind CSS / Alpine.js, SQLite

---

## 1. Problem Statement & Context

### 1.1 Background

ColoredCow is a 25-person custom software development company that builds complex, long-lived systems for social sector, healthcare, education, and agency clients. They operate from Uttarakhand and Gurgaon, India, with 11 years of delivery history.

Their hiring philosophy: **"We hire for judgment, not credentials. One or two students out of every 30-35 pass our filter."**

Current technical hiring relies on manual code review, inconsistent interview processes, and subjective assessments. This creates:
- Inconsistent evaluation criteria across candidates
- Time-intensive manual review of GitHub portfolios
- Difficulty comparing candidates objectively
- Risk of missing strong candidates who don't interview well
- Bias in evaluation when criteria aren't structured

### 1.2 Core Problem Definition

* **Who has it:** HR/talent acquisition team at ColoredCow evaluating technical candidates
* **What they actually need:** A system that takes a candidate's GitHub profile and repositories, evaluates them across multiple dimensions aligned with ColoredCow's values and technical practices, and produces structured, comparable evaluation reports
* **Why it matters:** Manual code review takes 2-4 hours per candidate, introduces subjective bias, and lacks consistency. AI can analyze code quality, patterns, and practices in minutes — but existing tools only score code, not the full picture of technical judgment, communication, and cultural alignment

### 1.3 What AI Hasn't Solved

Existing AI code review tools (CodeRabbit, Codacy, SonarQube) focus narrowly on code quality metrics. They miss:
- **Context-aware evaluation** — Does this candidate's approach match how ColoredCow builds systems?
- **Multi-dimensional assessment** — Code is one signal; judgment, communication, learning trajectory, and values alignment matter equally
- **Bias reduction** — Structured rubrics with consistent criteria across all candidates
- **Company-specific context** — A candidate who writes clean code but doesn't take ownership or communicate clearly isn't a ColoredCow fit

---

## 2. Target User Persona

| Attribute | Details |
| --- | --- |
| **Persona Name** | HR / Talent Acquisition at ColoredCow |
| **Role Example** | Technical hiring coordinator, engineering manager reviewing candidates |
| **Technical Literacy** | Comfortable with web interfaces, GitHub browsing, basic code reading |
| **Primary Frustration** | "I spend hours reviewing GitHub profiles manually, and I'm never sure if I'm comparing candidates fairly." |
| **Core Goal** | Add a candidate's GitHub info, get a comprehensive evaluation report, make informed shortlist/reject decisions quickly. |

---

## 3. Product Vision & Value Proposition

### 3.1 Vision

To give ColoredCow's hiring team an AI-powered evaluation engine that analyzes candidates the way their best engineers would — with depth, consistency, and alignment to company values — in minutes instead of hours.

### 3.2 "Context-Aware Multi-Dimensional" Architecture

Unlike generic code review tools that produce a single quality score, this system:

1. **Ingests** — Candidate submits GitHub profile + repositories (or HR adds them)
2. **Fetches** — System pulls repo metadata, file trees, commit history, README, issues via GitHub API
3. **Analyzes** — Per-repo analysis: language breakdown, test presence, CI config, documentation, commit patterns
4. **Evaluates** — AI evaluates across 7 dimensions using ColoredCow-specific context (values, tech stack, hiring philosophy)
5. **Reports** — Produces scored rubric + narrative summary + strengths/concerns/interview focus areas
6. **Decides** — HR reviews report, adds comments, shortlists or rejects

---

## 4. Key Functional Requirements

### 4.1 Candidate Intake

* **UR-1.1:** HR shall be able to add a candidate manually (name, email, GitHub username, repo URLs)
* **UR-1.2:** Candidates shall be able to self-submit via a public `/apply` form (name, email, GitHub repos)
* **UR-1.3:** System shall support two submission types: `hr_initiated` and `candidate_self_service`

### 4.2 GitHub Integration

* **UR-2.1:** System shall fetch repository metadata via GitHub API (stars, forks, language, description, topics)
* **UR-2.2:** System shall detect forked repositories and note parent repo
* **UR-2.3:** System shall handle private/unavailable repos gracefully with error status

### 4.3 Repository Analysis

* **UR-3.1:** System shall analyze each repo: file count, line count, primary languages
* **UR-3.2:** System shall detect presence of README, tests, CI configuration, documentation
* **UR-3.3:** System shall analyze commit frequency and message quality patterns
* **UR-3.4:** System shall detect architectural patterns (monolith, microservices, MVC, etc.)

### 4.4 AI Evaluation (7 Dimensions)

* **UR-4.1:** System shall evaluate across 7 dimensions with scores 1-10:
    1. **Code Quality** — Readability, structure, naming, DRY, error handling
    2. **Technical Judgment** — Architecture decisions, trade-off awareness, pragmatic choices
    3. **ColValues Alignment** — Evidence of ownership, learning, collaboration, craftsmanship (against ColoredCow's 6 values)
    4. **Communication** — README quality, commit message clarity, code comments, issue responses
    5. **Problem Complexity** — Sophistication of problems solved, system design thinking
    6. **Learning Trajectory** — Evidence of growth over time, responding to feedback
    7. **Technical Breadth** — Range across frontend/backend/DevOps/database
* **UR-4.2:** System shall produce an overall score (1-10) and verdict (strong_hire / hire / maybe / no_hire / strong_no_hire)
* **UR-4.3:** System shall generate narrative summary, strengths list, concerns list, and interview focus areas
* **UR-4.4:** System shall use ColoredCow-specific context in evaluation (values, tech stack, hiring philosophy)

### 4.5 Evaluation Pipeline

* **UR-5.1:** Evaluation shall be triggered asynchronously via Laravel Queue
* **UR-5.2:** Candidate status shall update through states: submitted → analyzing → evaluated → shortlisted/rejected
* **UR-5.3:** System shall support polling for evaluation status via API endpoint

### 4.6 Dashboard & HR Interface

* **UR-6.1:** Dashboard shall show pipeline funnel (submitted → analyzed → evaluated → shortlisted/rejected)
* **UR-6.2:** Candidate list shall be filterable by status, score range, verdict
* **UR-6.3:** Evaluation report shall display radar chart of 7-dimension scores
* **UR-6.4:** Evaluation report shall show per-dimension breakdown with justification and evidence
* **UR-6.5:** HR shall be able to add comments to evaluations
* **UR-6.6:** HR shall be able to shortlist or reject candidates from the report view

---

## 5. Technical Stack & Architectural Guidelines

| Component | Selected Technology | Rationale |
| --- | --- | --- |
| **Backend Framework** | PHP 8.3+ / Laravel 13 | Latest framework, strong queue management, Eloquent ORM |
| **Database** | SQLite | Lightweight, sufficient for single-team use |
| **Frontend UI** | Laravel Blade / Tailwind CSS v4 / Alpine.js | Server-rendered with lightweight interactivity |
| **AI Processing** | Google Gemini (primary) / OpenAI GPT-4o-mini (fallback) | Gemini for primary evaluation, OpenAI as backup |
| **GitHub Integration** | GitHub REST API v3 via Guzzle | Fetch repo metadata, files, commits, issues |
| **Queue & Async Jobs** | Laravel Queues (Database Driver) | Non-blocking evaluation pipeline |
| **Charts** | Chart.js via CDN | Radar charts for dimension visualization |

---

## 6. AI vs. Human Judgment Boundaries

| Workflow Step | Executed By | Rationale & Guardrails |
| --- | --- | --- |
| **GitHub Data Fetching** | System (automated) | API calls, no judgment needed |
| **Repository Analysis** | System (automated) | Pattern detection, metrics calculation |
| **Code Quality Scoring** | AI Engine | Structured rubric with evidence requirements |
| **ColValues Assessment** | AI Engine | Evaluates against ColoredCow's explicit values |
| **Final Verdict** | AI suggests, HR decides | AI provides recommendation; HR makes shortlist/reject decision |
| **Interview Focus Areas** | AI generates, HR reviews | AI identifies gaps; HR prioritizes interview topics |

---

## 7. ColoredCow Company Context

### 7.1 Core Values (Evaluate Candidates Against)

1. **Take Responsibility** — Ownership beyond assigned scope, proactive contribution
2. **Build Remarkable** — Attention to detail, craft in how and why things are built
3. **Always Learning** — Growth mindset, learning from failure, continuous improvement
4. **Respect & Make Each Other Successful** — Collaboration, helping others, team-first thinking
5. **Plentiful for Everyone** — Generosity with knowledge, not hoarding
6. **Freedom and Creativity** — Independent thinking, creative problem solving

### 7.2 Technical Stack

* **Backend:** PHP/Laravel, Python/Django, Elixir
* **Frontend:** React, Vue.js, JavaScript/TypeScript
* **Database:** PostgreSQL, MySQL, Redis
* **Cloud:** AWS (ECS Fargate, Lambda, ALB, CloudFront, WAF)
* **DevOps:** Docker, GitHub Actions, Terraform, Ansible
* **Monitoring:** Sentry
* **Testing:** Automated testing, integration and regression testing

### 7.3 System Types They Build

* Multi-tenant SaaS platforms
* Long-lived, evolving systems
* Open-source platforms
* Data & analytics systems
* Cloud-native systems

---

## 8. Success Metrics & KPIs

1. **Evaluation Time:** Reduce candidate evaluation from 2-4 hours (manual) to 5-10 minutes (AI + HR review)
2. **Evaluation Consistency:** Same candidate evaluated by AI produces consistent scores across runs
3. **HR Satisfaction:** HR team reports evaluations are actionable and comparable
4. **Decision Quality:** Shortlisted candidates have higher interview pass rate than previous manual process

---

## 9. Database Schema

### candidates
| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK | auto-increment |
| name | string | required |
| email | string | nullable, unique |
| phone | string | nullable |
| github_username | string | nullable |
| linkedin_url | string | nullable |
| portfolio_url | string | nullable |
| status | enum | submitted/analyzing/evaluated/shortlisted/rejected |
| submitted_by | bigint FK | nullable → hr_users.id |
| submission_type | enum | hr_initiated/candidate_self_service |
| notes | text | nullable |
| timestamps | | created_at, updated_at |

### repositories
| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK | auto-increment |
| candidate_id | bigint FK | → candidates.id |
| github_repo_id | bigint | unique per candidate |
| name | string | repo name |
| full_name | string | owner/repo |
| description | string | nullable |
| html_url | string | GitHub URL |
| default_branch | string | default: main |
| primary_language | string | nullable |
| stars_count | integer | default: 0 |
| forks_count | integer | default: 0 |
| open_issues_count | integer | default: 0 |
| created_at_github | timestamp | nullable |
| updated_at_github | timestamp | nullable |
| topics | json | array of strings |
| is_fork | boolean | default: false |
| fork_parent_name | string | nullable |
| analyzed_at | timestamp | nullable |
| timestamps | | |

### evaluations
| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK | auto-increment |
| candidate_id | bigint FK | unique → candidates.id |
| overall_score | decimal(3,1) | out of 10 |
| verdict | enum | strong_hire/hire/maybe/no_hire/strong_no_hire |
| narrative_summary | longText | AI-written report |
| strengths | json | array of strings |
| concerns | json | array of strings |
| interview_focus_areas | json | array of strings |
| ai_model_used | string | e.g., gemini-1.5-flash |
| evaluated_at | timestamp | |
| reviewed_by_hr | boolean | default: false |
| hr_notes | text | nullable |
| timestamps | | |

### evaluation_dimensions
| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK | auto-increment |
| evaluation_id | bigint FK | → evaluations.id |
| dimension | enum | code_quality/technical_judgment/colvalues_alignment/communication/problem_complexity/learning_trajectory/technical_breadth |
| score | decimal(3,1) | out of 10 |
| weight | decimal(3,2) | default: 1.00 |
| justification | text | AI explanation |
| evidence | json | array of specific examples |
| timestamps | | |

### repository_analyses
| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK | auto-increment |
| repository_id | bigint FK | unique → repositories.id |
| total_files_analyzed | integer | |
| total_lines_analyzed | integer | |
| primary_languages | json | language breakdown |
| has_readme | boolean | |
| has_tests | boolean | |
| has_ci_config | boolean | |
| has_documentation | boolean | |
| commit_frequency_score | decimal(3,1) | 1-10 |
| avg_commit_quality_score | decimal(3,1) | 1-10 |
| code_complexity_estimate | string | low/medium/high |
| architectural_patterns | json | array of detected patterns |
| dependencies_analysis | json | |
| analyzed_at | timestamp | |
| timestamps | | |

### hr_users
| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK | auto-increment |
| name | string | |
| email | string | unique |
| password | string | hashed |
| role | enum | admin/reviewer |
| timestamps | | |

### evaluation_comments
| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK | auto-increment |
| evaluation_id | bigint FK | → evaluations.id |
| hr_user_id | bigint FK | → hr_users.id |
| comment | text | |
| timestamps | | |

---

## 10. Milestone Execution Plan

* [x] **Phase 1: Discovery & Planning** — Research ColoredCow values/stack, design schema, write PRD
* [x] **Phase 2: Schema & Models** — Create migrations, Eloquent models, relationships
* [x] **Phase 3: Services Layer** — GitHub API, Repository Analyzer, AI Evaluation, Orchestrator
* [x] **Phase 4: Controllers & Routes** — Candidate CRUD, public apply, evaluation triggering
* [x] **Phase 5: UI & Dashboard** — Blade views, radar charts, evaluation reports, pipeline view
* [x] **Phase 6: Tests & Polish** — Feature tests, seed data, manual verification
* [ ] **Phase 7: Future Enhancements (Pending)** — Implement HR Authentication, and AI provider fallback mechanisms.
