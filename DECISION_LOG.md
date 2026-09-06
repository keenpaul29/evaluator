# ColoredCow Technical Candidate Evaluator — Architectural & Decision Log

## 1. Executive Summary & Boundaries

This document records architectural choices, AI evaluation methodology, confidence scoring thresholds, and Human-in-the-Loop (HITL) verification boundaries established during the development of **ColoredCow Technical Candidate Evaluator**.

---

## 2. Decision Matrix & Boundaries

| Category | Trusted to AI | Human Review / Operator Override Required |
| --- | --- | --- |
| **GitHub Data Fetching** | API responses (commit history, file trees, metadata) | Handling private repos or API rate limit failures. |
| **Static Analysis** | Identifying presence of tests, CI configs, file line counts | Edge cases where custom framework structures are not detected properly. |
| **Evaluation Scoring** | Scoring along 7 technical dimensions and writing summary | Final shortlisting or rejection decision based on the generated report. |

---

## 3. Evaluation & Edge Cases Observed

### Case 1: High-Confidence Structured Repository (Auto-Evaluated)
- **Raw Input:** GitHub repository with clean commit history, README, and structured backend.
- **Result:** Successfully analyzed and evaluated. Overall score 8.5/10. Ready for HR review and shortlist decision.

### Case 2: Incomplete Data or Missing Context (HITL Flagged)
- **Raw Input:** Candidate provides only one empty repository or a heavily forked repository with no original commits.
- **Result:** Low evaluation confidence. Flagged reasons: `"Insufficient original code to evaluate"`. Sent to dashboard for manual HR override/rejection.

---

## 4. Verification & Testing Log
- **Automated Seeding Verification:** Verified `php artisan db:seed` runs cleanly and populates auto-approved and HITL review queue entries.
- **CSV Export Verification:** Verified `/export/csv` generates structured spreadsheets with column headers and total amounts.
