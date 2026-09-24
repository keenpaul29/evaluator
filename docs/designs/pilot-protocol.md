# Pilot: Silent-Observation Session — ColoredCow HR Evaluates a Real Candidate

Source: `docs/designs/behavioral-values-fit-evaluator.md` (Success Criteria, The Assignment, review decision E3A).
Status: PLANNED — protocol frozen before the session; do not edit mid-session.

## Purpose (derated claim)

The pilot validates the current evaluated-candidate UI end-to-end and surfaces blockers only. It does **not** claim to prove values-fit under HR scrutiny — that claim is positioned behind the Phase-1.5 evidence build. The single session converts Q5's zero-observation gap ("only I've used it") into a real usage observation. Capture the HR user's shortlist decision in their own words.

## Session contract

- One real ColoredCow HR/Talent person evaluates one real candidate through the tool, in silence, for the full flow. Author sits with them and does not coach.
- ~30-60 minutes, in person or on a shared screen.
- Author records observations on paper/notes app, not on the tool's screen.

## Candidate threshold (must pass BEFORE the session)

Vet with `php artisan pilot:candidate-check <github_username>` (or manual GitHub review):

1. ≥2 submitted repos with authored commits — "authored" means the fetched commit log contains commits whose `author.login` or `commit.author.{name,email}` matches the candidate's GitHub profile.
2. At least one repo has tests + CI config + README present (exercises the evidence path, not the edge case).
3. Repos are mostly non-fork (forks with no original commits fail the authored check above anyway).

If the chosen candidate fails at evaluation time, the session switches to the pre-picked contingency candidate without restarting the protocol.

## Fixed debrief questions (post-session, verbatim capture)

1. What did you trust in the evaluation, and why?
2. What did you hesitate on or not trust?
3. Would you shortlist this candidate based on this report alone? Why or why not?
4. What was missing that you expected to see?
5. How long did it take you to feel confident in a decision?

Record the hr user's shortlist decision in their own words, quoted exactly.

## Author silent-observation template

During the session, tally under three columns (mark each occurrence, no coaching):

- HESITATIONS — where the user paused, re-read, or doubted the report.
- SURPRISES — what they reacted to that the author did not predict.
- TRUST POINTS — what they accepted immediately.

After the session, write 5-10 lines: what the author predicted vs what actually happened.

## Session runbook

1. Confirm candidate passed threshold pre-check; contingency candidate vetted.
2. Confirm env on the running deploy: `GEMINI_API_KEY` present; recommend `OPENAI_API_KEY` so fallback is wired; `GITHUB_API_TOKEN` present for repo sync.
3. Open the tool's candidate index. Hand the screen to the HR user.
4. Direct the user to the one candidate name. No further instruction ("evaluate this candidate").
5. Author stays silent. Tally hesitations/surprises/trust points.
6. The user reaches a shortlist/reject decision (or names a blocker). Stop.
7. Debrief with the fixed questions. Quote their decision.
8. Write the pilot report (see below) the same day.

## Pilot report structure

File: `docs/pilot/2026-XX-XX-<candidate>.md`

1. Candidate (handle, repos) + threshold check result.
2. Environment (model used, git sha of the app).
3. Session log — what the user did, in order, and the observation tallies.
4. Blockers (anything that stopped the user).
5. Debrief transcript (verbatim Q&A) + their shortlist decision in their own words.
6. Author reflection (silent observation outcomes).
7. Recommended next actions (committed to this file, not deferred).

## Post-session gating

- If no blockers: the phase-1.5 evidence build and report redesign proceed from pilot blockers — pattern is already shipped; report redesign scope comes from Q4 answers.
- If the user blocked: the blocker is a named finding for the founder gate, not a silent feature request.
- Report to Prateek factually: what the HR user did, decided, hesitated on — no scored claims.