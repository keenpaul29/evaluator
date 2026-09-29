# TODOS

## Evaluation pipeline

### TODO-005: Redesign ArtifactVsLiveDivergenceTest — the assertion is unsatisfiable
**Priority:** P1
**What:** `tests/evals/ArtifactVsLiveDivergenceTest.php` asserts that an artifact-fed evaluation and a zero-artifact evaluation land within one verdict tier of each other. That comparison is structurally invalid and the test cannot pass as written.
**Why:** The two arms run under different rules. `CitationValidator::validate` returns dimensions unchanged when the artifact registry is empty (CitationValidator.php:36-38), so the stripped arm keeps whatever markers the LLM emitted, while the artifact-fed arm goes through full existence + support verification. `VerdictCalculator` additionally requires >=2 evidence-backed dimensions, which a no-artifact run can only reach by LLM luck. Observed across two consecutive runs of unchanged product code: run 1 `artifact=insufficient_data / live=hire`; run 2 `artifact=hire / live=insufficient_data`. The direction inverts, which isolates the remaining variance to LLM nondeterminism in the stripped arm rather than a product defect.
**Pros:** Redesigning to compare two artifact-fed runs measures the thing that actually matters — run-to-run LLM variance on the same input — and gives the citation layer a real regression signal.
**Cons:** Costs real API calls per run; loses the (unachievable) cross-configuration comparison the test name implies.
**Context:** Found 2026-09-25 while running the live-LLM harness on the take-home assignment branch. The same session found and fixed a genuine bug it surfaced: `CitationValidator::decodeJson` discarded a valid support-check response whenever the model emitted concatenated JSON objects, which flagged every dimension insufficient and collapsed real `hire` verdicts to `insufficient_data`. That fix landed with 4 regression tests. This TODO is only about the remaining test-design flaw.
**Depends on:** Nothing. It is excluded from phpunit.xml and is not a merge gate.

## Deferred from /plan-eng-review (2026-09-16)

### TODO-001: Score Normalization Across AI Models
**What:** Normalize scores between Gemini and OpenAI so comparison feature produces meaningful results.
**Why:** The comparison feature (T13) assumes scores are comparable, but different AI models produce different score distributions. Comparing a Gemini-evaluated candidate to an OpenAI-evaluated candidate is apples-to-oranges.
**Pros:** Makes comparison feature accurate; prevents misleading hiring decisions.
**Cons:** Requires calibration data (run same candidates through both models), adds complexity to evaluation pipeline.
**Context:** When comparison feature ships, HR users will compare candidates evaluated by different models. Without normalization, the side-by-side scores are meaningless. The fix: run a calibration set of 10 candidates through both models, compute offset, apply correction factor.
**Depends on:** T13 (comparison feature) — must ship first to surface the problem.

### TODO-002: Candidate Portal for Public Applicants
**What:** Add a status page where public form candidates can check their evaluation result.
**Why:** PublicApplyController::store() dispatches evaluation but gives candidates zero feedback. The success page just says "applied" — no way to check results.
**Pros:** Completes the candidate experience; reduces HR overhead for status inquiries.
**Cons:** Requires auth for candidates (email-based magic link?), adds a new public-facing route.
**Context:** This is the most obvious missing feature flagged by the outside voice. Candidates who apply through the public form have no visibility into whether they were evaluated, shortlisted, or rejected. A simple status page with email verification would close this loop.
**Depends on:** Auth system (currently not implemented) — needs at least email-based verification.

## Deferred from /plan-eng-review (2026-09-23)

### TODO-003: Per-File Recency Data Collection
**What:** Add per-file last-touch date collection to `GithubService` via `GET /repos/.../commits?path=<file>` (N extra calls per repo, ~10/repo) so file ranking by "most-recent-commit date" becomes computable.
**Why:** Review decision E2 replaced the planned recency ranking with a computable file-tree-order rule, because `getRepoCommits` (GithubService.php:214-228) returns repo-level commits with zero per-file attribution. Recency ranking is the preferred signal once the data exists.
**Pros:** Enables the design's original recency-ranking intent; better artifact selection for citation grounding.
**Cons:** Costs GitHub API budget (unauth 60 req/hr, auth'd 5000); adds pipeline time; signal value unproven until Phase-1.5.
**Context:** Deferred so the Phase-1.5 citation layer ships first (the recency signal only matters once citations exist). Then collection can be added and ranked artifacts A/B'd.
**Depends on:** Phase-1.5 citation layer (top-N selection landing first).

### TODO-004: Culture-Fingerprint Productization
**What:** Package ColValues Alignment dimensions as a standalone product (per-role fingerprint comparison: candidate signals vs org/position values fingerprint) once the values-fit pipeline proves out.
**Why:** Approach B framing — the practices-formerly-known-as-artifacts become a culture-fit product, not just a review dimension. Highest-revenue follow-on if Phase-1.5 citations hold up.
**Pros:** Standalone revenue story; differentiates from score-based competitors (Exiqus trend).
**Cons:** Requires Phase-1.5 evidence build to be trustworthy first; premature productization risks over-extrapolation from n=1 evidence.
**Context:** Gated behind the pilot derate report (E3A) and Phase-1.5 evidence quality.
**Depends on:** Phase-1.5 citation/validator build + pilot derate report.
