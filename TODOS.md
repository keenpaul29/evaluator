# TODOS

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
