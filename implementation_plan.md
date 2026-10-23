<!-- /autoplan restore point: "C:\\Users\\palpu\\.gstack\\projects\\evaluator\\main-autoplan-restore-20261020-141356.md" -->
## Implementation plan
# Clean Design System Overhaul — UI Philosophy Upgrade

The current UI is functional but inconsistent. It uses raw Tailwind utility classes with duplicated color mappings, no unified spacing rhythm, and a very sparse, grayscale-heavy aesthetic that feels utilitarian rather than polished. This plan introduces a cohesive design system rooted in clean, modern principles — consistent tokens, refined typography, purposeful color, and subtle motion — while preserving all existing functionality.

## Design Philosophy

**Guiding principle:** *"Information-dense, visually calm, structurally clear."*

- **Neutral canvas, purposeful color** — Gray-900/white as primary pair; color reserved for status, verdict, and interaction feedback only
- **Spatial consistency** — 4px base grid, 8/12/16/24/32/48px rhythm for all spacing
- **Typography scale** — 3 weights of Inter (400/500/600), 5 sizes (xs/sm/base/lg/xl), consistent line-heights
- **Surface hierarchy** — Background → Card → Elevated card, using border + subtle shadow instead of flat borders
- **Micro-interactions** — 150ms transitions on all interactive elements, focus-visible rings, smooth hover states

---

## Proposed Changes

### Design System Foundation

#### [MODIFY] [app.blade.php](file:///d:/website/operationsflow-hr/resources/views/layouts/app.blade.php)

Upgrade the master layout with:
- **Tailwind CDN config** — Define a custom theme with design tokens (color palette, spacing scale, font config, box-shadows) injected via `tailwind.config`
- **CSS custom properties** — Add a `<style>` block with CSS variables for reusable tokens across all views
- **Improved header** — Taller nav bar (h-14), proper icon-style nav items with subtle active indicators (bottom border accent instead of bg-gray-900 pill), breathing room between items
- **Better flash messages** — Left-border accent style with icons, auto-dismiss via Alpine.js
- **Footer** — Minimal footer with version/branding
- **Enhanced font loading** — Add Inter weight 300 (light) for large display numbers, and include `font-display: swap`

Key design tokens to define:
```
--surface-primary: white
--surface-secondary: #f9fafb (gray-50)
--surface-elevated: white + shadow-sm
--border-default: #f3f4f6 (gray-100)
--border-subtle: #e5e7eb (gray-200)
--text-primary: #111827 (gray-900)
--text-secondary: #6b7280 (gray-500)
--text-tertiary: #9ca3af (gray-400)
--accent: #2563eb (blue-600)
--radius-card: 12px
--radius-badge: 6px
--radius-button: 8px
```

---

### Dashboard

#### [MODIFY] [dashboard.blade.php](file:///d:/website/operationsflow-hr/resources/views/dashboard.blade.php)

- **Stat cards** — Elevated surface with subtle shadow, icon-left layout, trend indicator placeholder, larger number with unit separated
- **Pipeline visualization** — Thicker bars (h-2), rounded pill shape, animated width on load via Alpine `x-init`
- **Verdicts panel** — Horizontal dot-label layout instead of stacked, better empty state with illustration hint
- **Recent candidates table** — Alternating subtle row backgrounds, avatar placeholder circle with initials, better hover state with translateY micro-lift
- **Page header** — Add a greeting line + date stamp for context

---

### Candidates

#### [MODIFY] [index.blade.php](file:///d:/website/operationsflow-hr/resources/views/candidates/index.blade.php)

- **Filter bar** — Grouped filters in a collapsible panel with clear visual grouping, "Clear filters" link, pill-style active filter indicators
- **Table** — Consistent cell padding, right-aligned numeric columns, monospace scores, sortable column header styling (chevron icons), checkbox column for bulk actions prep
- **Status badges** — Unified badge component with dot-prefix pattern (colored dot + text), consistent sizing
- **Empty state** — Centered illustration placeholder with CTA button, more welcoming copy

#### [MODIFY] [create.blade.php](file:///d:/website/operationsflow-hr/resources/views/candidates/create.blade.php)

- **Form sections** — Card-based grouping with section icon + title bar, clearer required field markers
- **Input styling** — Taller inputs (py-2.5), visible placeholder text, focus ring using accent color
- **Repository inputs** — Numbered list with drag handle hint, better add/remove button styling
- **Submit area** — Full-width primary button with loading state prep, cancel as text link with proper weight

#### [MODIFY] [show.blade.php](file:///d:/website/operationsflow-hr/resources/views/candidates/show.blade.php)

- **Header area** — Candidate name as xl heading, status badge, action buttons as outlined variants with icon
- **Score card** — Large centered score in a circular progress ring (CSS-only), verdict below as prominent badge
- **Dimensions grid** — Taller bars (h-2), score number right-aligned, color-coded backgrounds
- **Radar chart** — Increase canvas size, themed grid lines, custom tooltip styling
- **Strengths/Concerns** — Icon-prefixed list with green checkmark / amber warning, card surface with subtle left border accent
- **Eliminator recommendation** — Cleaner layout with prominent status icon, separated action badge
- **Justifications** — Accordion-style collapsible sections per dimension
- **Comments** — Chat-bubble styling with avatar circle, timestamp aligned right
- **Repositories** — Card grid instead of stacked list, language color dot, star/fork inline

---

### Comparisons

#### [MODIFY] [index.blade.php](file:///d:/website/operationsflow-hr/resources/views/comparisons/index.blade.php)

- **Cards** — Larger card with mini radar chart preview (canvas), candidate count as badge
- **Delete button** — Icon-only with tooltip, positioned top-right

#### [MODIFY] [show.blade.php](file:///d:/website/operationsflow-hr/resources/views/comparisons/show.blade.php)

- **Table** — Highlighted best-in-row scores (bold + background), styled gap column
- **Candidate summary cards** — Consistent card height, score prominently displayed, truncated summary with "Read more" link

---

### Batches

#### [MODIFY] [index.blade.php](file:///d:/website/operationsflow-hr/resources/views/batches/index.blade.php)

- **Table styling** — Consistent with candidates index table
- **Progress column** — Mini progress bar inline instead of raw fraction text

#### [MODIFY] [create.blade.php](file:///d:/website/operationsflow-hr/resources/views/batches/create.blade.php)

- **Form styling** — Consistent with candidates create form
- **CSV format box** — Better code styling with syntax highlight hint, copy button

#### [MODIFY] [show.blade.php](file:///d:/website/operationsflow-hr/resources/views/batches/show.blade.php)

- **Progress bar** — Same style as dashboard pipeline bars
- **Candidate table** — Consistent with main candidates table

---

### Public Apply

#### [MODIFY] [show.blade.php](file:///d:/website/operationsflow-hr/resources/views/apply/show.blade.php)

- **Standalone layout** — Centered card with subtle shadow, branded header, cleaner spacing
- **Form** — Match internal form styling tokens but with a friendlier, applicant-facing tone
- **Submit button** — Full-width with gradient or solid accent color

#### [MODIFY] [success.blade.php](file:///d:/website/operationsflow-hr/resources/views/apply/success.blade.php)

- **Success state** — Larger checkmark icon in a green gradient circle, confetti-style subtle animation, clearer next-steps copy

---

## What Does NOT Change

- No controller, model, or service logic changes
- No route changes
- No database changes
- All Alpine.js and Chart.js behavior preserved
- All existing Blade `@yield`, `@section`, `@push` structure preserved
- CDN-based Tailwind approach preserved (no migration to Vite-built Tailwind)

## Verification Plan

### Manual Verification
- Run `composer dev` and visually verify every page:
  - Dashboard (with and without data)
  - Candidates index (with filters, empty state, pagination)
  - Candidate create form (validation errors)
  - Candidate show (full evaluation, analyzing state, no evaluation)
  - Comparisons index and show
  - Batches index, create, and show (processing state)
  - Public apply form and success page
- Verify responsive behavior at mobile/tablet/desktop breakpoints
- Verify all interactive elements (hover, focus, active states)
- Verify Chart.js radar charts render correctly with new theme

<!-- autoplan-accepted:ceo -->
- Extract 4 Blade partials (badge, card, form-input, table) to eliminate token duplication across 17 views
- Export design tokens as JSON (Style Dictionary format) for Figma designer handoff
- Add accessibility requirements (WCAG AA contrast, focus-visible, ARIA) to verification plan
- Add flash dismiss try-catch error handling in app.blade.php
- Add radar chart ResizeObserver for canvas resize resilience
- Document token naming convention mapping (CSS kebab-case ↔ Tailwind camelCase)
- Defer visual regression tests, dark mode, Vite migration to TODOS.md
<!-- /autoplan-accepted:ceo -->

---

### Phase 2: Design Review (UI scope detected)

#### Step 0: Design Scope Assessment
**0A. Initial Design Rating:** **6/10** — The plan specifies concrete tokens, spacing scale, typography, and component patterns. Gaps: no DESIGN.md exists, interaction states partially specified, responsive behavior described but not detailed, empty states mentioned but not fully designed, AI slop risk present (card grids, centered hero patterns).

**What 10/10 looks like:** Complete DESIGN.md with token YAML, all 5 interaction states per feature specified, responsive layouts per breakpoint, empty states with warmth/action/context, mockups approved for key screens, accessibility requirements as acceptance criteria.

**0B. DESIGN.md Status:** No DESIGN.md exists. Proceeding with universal design principles. Recommend `/design-consultation` after this review to formalize the design system.

**0C. Existing Design Leverage:**
- `app.blade.php`: Tailwind config object + CSS custom properties (partial token system)
- `dashboard.blade.php`: Pipeline bars, stat cards, verdicts panel — established patterns
- `candidates/index.blade.php`: Filter bar, table, status badges, empty state
- `candidates/show.blade.php`: Score card, radar chart, dimensions grid, accordion justifications
- Alpine.js: Auto-dismiss flash messages, x-init animations, x-transition
- Chart.js: Radar chart with custom styling

**0D. Focus Areas:** All 7 passes (plan has broad UI scope across 17 views).

#### Step 0.5: Visual Mockups
**DESIGN_NOT_AVAILABLE** — gstack designer binary not found. Proceeding with text-based review.

#### Pass 1: Information Architecture — Rating: 7/10
**Gap 1:** Plan defines token hierarchy but not screen-level content hierarchy. What does the user see first/second/third on each screen?
- Dashboard: Pipeline bars compete with stat cards for attention
- Candidate show: Score card, radar chart, and dimensions grid all vie for primary focus
- Comparison table: Gap column styling not prioritized vs score columns

**FIX TO 10:** Add information hierarchy spec per screen:
```
Dashboard: 1) Pipeline health (primary) 2) Stat cards (secondary) 3) Recent candidates (tertiary)
Candidate Show: 1) Score + verdict (primary) 2) Dimensions grid (secondary) 3) Radar chart (tertiary) 4) Strengths/Concerns (detail)
Comparison: 1) Score comparison (primary) 2) Gap analysis (secondary) 3) Candidate summaries (tertiary)
```

**Principle:** Hierarchy as service (Cognitive Pattern #3). Every decision answers "what should the user see first?"

**Auto-decision (P1 completeness, P5 explicit):** **ACCEPTED** — Add hierarchy specs to plan.

**Gap 2:** Navigation flow not specified for mobile. Desktop nav preserved but mobile hamburger behavior undefined.

**FIX TO 10:** Specify mobile nav: hamburger → slide-out drawer with same items, active indicator preserved.

**Auto-decision (P1 completeness):** **ACCEPTED** — Add to plan.

#### Pass 2: Interaction State Coverage — Rating: 6/10
**Current State Table (from plan):**

| Feature | Loading | Empty | Error | Success | Partial |
|---------|---------|-------|-------|---------|---------|
| Dashboard stats | — | ✅ (hint) | — | ✅ | — |
| Pipeline bars | ✅ (animated) | ✅ | — | ✅ | — |
| Candidate table | — | ✅ (illustration) | — | ✅ | ✅ (pagination) |
| Candidate show | ✅ (analyzing) | ✅ | ✅ | ✅ | — |
| Comparison table | — | — | — | ✅ | — |
| Batch progress | ✅ (bar) | — | ✅ | ✅ | ✅ |
| Apply form | ✅ (submitting) | — | ✅ | ✅ | — |

**Gaps (Auto-fixed per P1 completeness):**
1. **Dashboard stats loading** — Skeleton cards while data loads → **ADD**
2. **Dashboard stats error** — Inline error with retry → **ADD**
3. **Candidate table loading** — Skeleton rows → **ADD**
4. **Candidate table error** — Inline error with retry → **ADD**
5. **Comparison table loading** — Skeleton → **ADD**
6. **Comparison table empty** — Illustration + CTA → **ADD**
7. **Comparison table error** — Inline error → **ADD**
8. **Batch progress empty** — Empty state with CTA → **ADD**
9. **Apply form empty** — N/A (form starts empty) → **N/A**
10. **Apply form partial** — Auto-save draft indicator → **ADD**

**Principle:** Empty states are features (Design Principle #1). Every empty state needs warmth, primary action, context.

**Auto-decision:** **ACCEPTED** — Add all missing states to plan with specific descriptions.

#### Pass 3: User Journey & Emotional Arc — Rating: 6/10
**Current Journey:** HR lands on dashboard → scans pipeline → drills into candidate → evaluates evidence → shortlists/rejects.

**Gaps:**
1. **First-time user onboarding** — No guidance for new HR user. Dashboard empty state has "illustration hint" but no onboarding flow.
2. **Error recovery** — When evaluation fails (SSE timeout), user sees generic error. No guided recovery.
3. **Success celebration** — Shortlist action has no confirmation moment. Just redirects.
4. **Comparison flow** — How does user get to comparison? From dashboard? Candidate show? Not mapped.

**FIX TO 10:** Add storyboard with emotional arc:
```
STEP | USER DOES              | USER FEELS        | PLAN SPECIFIES?
-----|------------------------|-------------------|----------------
1    | Opens dashboard        | Oriented          | Pipeline + stats visible
2    | Sees candidate in pipe | Curious           | Click → candidate show
3    | Reviews evidence       | Confident/Skeptical| Score + dims + radar
4    | Clicks shortlist       | Decisive          | ✅ Success toast + redirect
5    | (If error)             | Frustrated        | ✅ Inline error + retry
6    | Opens comparison       | Analytical        | Side-by-side scores
7    | Reviews gaps           | Informed          | Gap column highlighted
```

**Principle:** Time-horizon design (Cognitive Pattern #10) — 5-sec visceral, 5-min behavioral, 5-year reflective.

**Auto-decision (P1 completeness):** **ACCEPTED** — Add storyboard to plan.

#### Pass 4: AI Slop Risk — Rating: 8/10
**Hard Rejection Check:** None triggered. Plan avoids generic SaaS patterns (no hero, no 3-column feature grid, no purple gradients).

**Litmus Checks:**
1. Brand unmistakable? **YES** — ColoredCow logo in header, accent color #2563eb
2. Strong visual anchor? **YES** — Pipeline bars on dashboard, score card on candidate show
3. Scannable by headlines? **YES** — Clear section headings, token-driven hierarchy
4. Each section one job? **YES** — Dashboard=overview, Candidate=detail, Compare=decision
5. Cards necessary? **YES** — Stat cards, candidate cards, batch cards all have distinct interactions
6. Motion improves hierarchy? **PARTIAL** — Pipeline animation on load, flash dismiss transition. Missing: accordion expand, table sort, card hover lift.
7. Premium without shadows? **YES** — Border + subtle shadow system, not decoration

**AI Slop Blacklist:** Plan avoids all 11 patterns. Token system prevents "cream palette," "uniform bubbly radius," "centered everything."

**Gap:** Motion moments not fully specified. Only pipeline bars and flash dismiss have animation.

**FIX TO 10:** Specify one authored motion moment per screen:
- Dashboard: Pipeline bars animate on load (specified)
- Candidate show: Score ring draws on load, accordion expand with easing
- Comparison: Gap column highlight on hover, row highlight on focus
- Apply form: Submit button loading spinner, success confetti

**Auto-decision (P5 explicit, P1 completeness):** **ACCEPTED** — Add motion specs to plan.

#### Pass 5: Design System Alignment — Rating: 7/10
**No DESIGN.md exists.** Plan defines tokens explicitly (score 7). Gap: tokens not formalized as shared vocabulary.

**Gaps:**
1. Token naming: CSS vars use kebab-case (`--surface-primary`), Tailwind config uses camelCase (`surfacePrimary`). Mapping documented (CEO expansion #6) but not enforced.
2. No component token references. Plan says "unified badge component" but doesn't cite token paths.
3. Radius tokens defined but not applied systematically (card vs badge vs button).

**FIX TO 10:** Formalize token-component mapping:
```
Badge: colors.surfacePrimary, colors.accent, rounded.badge, spacing.sm
Card: colors.surfaceElevated, border.default, rounded.card, shadow.card
FormInput: colors.textPrimary, colors.borderSubtle, colors.accent (focus), rounded.button
Table: colors.borderSubtle, colors.surfaceSecondary (alt rows), spacing.md
```

**Auto-decision (P4 DRY, P5 explicit):** **ACCEPTED** — Add token-component mapping table to plan.

#### Pass 6: Responsive & Accessibility — Rating: 7/10
**Responsive:** Verification plan mentions "mobile/tablet/desktop breakpoints" but no per-breakpoint specs.

**Gaps:**
1. Mobile nav: Hamburger behavior undefined (Pass 1 gap)
2. Table horizontal scroll on mobile not specified
3. Radar chart canvas resize behavior (CEO expansion #5 accepted ResizeObserver)
4. Form input stacking on mobile not specified
5. Comparison table: gap column visibility on mobile

**Accessibility:** WCAG AA contrast mentioned, focus-visible on interactive, ARIA on accordions/chat bubbles. Gaps:
1. Touch target sizes (44px min) not verified for all interactive elements
2. ARIA landmarks (main, nav, region) not specified
3. Visited link distinction not preserved (Universal Rule)
4. Heading hierarchy (h1→h2→h3) not audited across views

**FIX TO 10:** Add responsive specs per viewport + a11y checklist:
```
Mobile (375px): Nav→drawer, Tables→horizontal scroll, Cards→stacked, Charts→full-width
Tablet (768px): Nav→condensed, Tables→fit, Charts→side-by-side
Desktop (1024px+): Full layout per plan

A11y Checklist:
- [ ] 44px touch targets on all buttons/links
- [ ] ARIA landmarks: header[role=banner], nav[role=navigation], main[role=main]
- [ ] Visited links distinct color (Universal Rule)
- [ ] Heading hierarchy audit (h1→h2→h3)
- [ ] Focus-visible on all interactive (already in plan)
- [ ] Color contrast ≥4.5:1 body, ≥3:1 large text (WCAG AA)
```

**Auto-decision (P1 completeness, P5 explicit, P7 trust):** **ACCEPTED** — Add responsive + a11y specs to plan.

#### Pass 7: Unresolved Design Decisions
| Decision Needed | If Deferred, What Happens |
|-----------------|---------------------------|
| Empty state illustrations (SVG/PNG) | Engineer ships generic "No items found" |
| Mobile nav drawer animation timing | Defaults to browser default (jarring) |
| Accordion easing function | Linear (feels cheap) |
| Confetti animation library/implementation | Not implemented |
| Token JSON format validation | Manual process, drift risk |

**Auto-decision (P6 bias to action):** **DEFERRED to TODOS.md** — These are implementation details that can be resolved during build. Document as TODOs with context.

---

### Design Litmus Scorecard (Outside Voices)

| Check | opencode (in-host) | Codex | Consensus |
|-------|-------------------|-------|-----------|
| 1. Brand unmistakable? | ✅ YES | N/A | N/A |
| 2. Strong visual anchor? | ✅ YES | N/A | N/A |
| 3. Scannable by headlines? | ✅ YES | N/A | N/A |
| 4. Each section one job? | ✅ YES | N/A | N/A |
| 5. Cards necessary? | ✅ YES | N/A | N/A |
| 6. Motion improves hierarchy? | ⚠️ PARTIAL | N/A | N/A |
| 7. Premium without shadows? | ✅ YES | N/A | N/A |
| Hard rejections | 0 | N/A | N/A |

Codex: unavailable (ChatGPT account model compatibility). opencode (in-host): completed.

---

### NOT in Scope (Design)
- Vite-built Tailwind migration (separate epic)
- Dark mode (deferred to TODOS.md)
- Visual regression test infrastructure (deferred to TODOS.md)
- Animation library selection (implementation detail)
- Illustration asset creation (designer deliverable)

### What Already Exists (Design)
- `app.blade.php`: Tailwind config + CSS custom properties (foundation)
- Pipeline bars, stat cards, verdicts panel (dashboard patterns)
- Filter bar, status badges, table patterns (candidates index)
- Score card, radar chart, accordion (candidate show)
- Flash message Alpine.js pattern
- Chart.js radar chart with custom tooltip

### TODOS.md Updates (Design)
- **DESIGN-001:** Create DESIGN.md from plan tokens (Style Dictionary format)
- **DESIGN-002:** Design empty state illustrations (SVG) for all 6 empty states
- **DESIGN-003:** Specify mobile nav drawer animation (ease-out, 200ms)
- **DESIGN-004:** Define accordion easing (exponential ease-out)
- **DESIGN-005:** Select confetti animation approach (CSS-only vs canvas)
- **DESIGN-006:** Add token JSON validation to CI pipeline
- **DESIGN-007:** Audit heading hierarchy across all 17 views
- **DESIGN-008:** Verify visited link distinction per Universal Rule

---

### Implementation Tasks (Design Review)
- [ ] **DESIGN-001 (P1, human: ~2h / CC: ~15min) — Information Hierarchy Specs** — Add per-screen hierarchy to plan (Dashboard, Candidate Show, Comparison)
  - Surfaced by: Pass 1 (Info Arch) — Gap 1
  - Files: implementation_plan.md
  - Verify: Plan includes hierarchy for all 3 primary screens
- [ ] **DESIGN-002 (P1, human: ~1h / CC: ~10min) — Mobile Nav Spec** — Add hamburger→drawer behavior to plan
  - Surfaced by: Pass 1 (Info Arch) — Gap 2
  - Files: implementation_plan.md, app.blade.php
  - Verify: Mobile nav described in plan
- [ ] **DESIGN-003 (P1, human: ~2h / CC: ~20min) — Complete Interaction States** — Add all 10 missing states to plan table
  - Surfaced by: Pass 2 (States)
  - Files: implementation_plan.md
  - Verify: State table has loading/empty/error for all 7 features
- [ ] **DESIGN-004 (P1, human: ~1h / CC: ~15min) — User Journey Storyboard** — Add 7-step storyboard with emotional arc
  - Surfaced by: Pass 3 (Journey)
  - Files: implementation_plan.md
  - Verify: Storyboard table in plan
- [ ] **DESIGN-005 (P1, human: ~1h / CC: ~10min) — Motion Moments** — Add one authored motion per screen
  - Surfaced by: Pass 4 (AI Slop)
  - Files: implementation_plan.md
  - Verify: Motion specs for all 4 primary screens
- [ ] **DESIGN-006 (P1, human: ~1h / CC: ~15min) — Token-Component Mapping** — Add token→component mapping table
  - Surfaced by: Pass 5 (Design Sys)
  - Files: implementation_plan.md
  - Verify: Mapping table in plan
- [ ] **DESIGN-007 (P1, human: ~2h / CC: ~20min) — Responsive + A11y Specs** — Add per-breakpoint layout + a11y checklist
  - Surfaced by: Pass 6 (Responsive/A11y)
  - Files: implementation_plan.md
  - Verify: Responsive table + a11y checklist in plan

---

### Design Decisions Made: 7 (all auto-approved via 6 principles)
### Design Decisions Deferred: 5 (to TODOS.md)

### Overall Design Score: 6/10 → 9/10 after fixes

---

### Design Outside Voices Consensus Table

| Dimension | opencode (in-host) | Codex | Consensus |
|-----------|-------------------|-------|-----------|
| 1. Brand unmistakable? | ✅ YES | N/A | N/A |
| 2. Strong visual anchor? | ✅ YES | N/A | N/A |
| 3. Scannable by headlines? | ✅ YES | N/A | N/A |
| 4. Each section one job? | ✅ YES | N/A | N/A |
| 5. Cards necessary? | ✅ YES | N/A | N/A |
| 6. Motion improves hierarchy? | ⚠️ PARTIAL | N/A | N/A |
| 7. Premium without shadows? | ✅ YES | N/A | N/A |

Codex: unavailable (ChatGPT account model compatibility). opencode (in-host): completed.

---

### Cross-Phase Themes
- **Token system as foundation:** Both CEO and Design reviews identify the token system in `app.blade.php` as the critical foundation. CEO extracted partials; Design formalized token-component mapping.
- **Performance vs Scope trade-off:** CDN Tailwind (340KB) acknowledged in both reviews as known performance cost, explicitly deferred. Consistent decision.
- **Completeness over cleverness:** Both reviews favor explicit, complete specifications over minimal/abstract ones (P1, P5).

---

### Deferred to TODOS.md (Cross-Phase)
- Visual regression test suite (Playwright/Percy)
- Dark mode support
- Vite-built Tailwind migration (separate epic)
- Build-time token validation script
- Empty state illustrations
- Mobile nav drawer animation timing
- Accordion easing function
- Confetti animation implementation
- Token JSON format validation

---

### Implementation Tasks (aggregated across CEO + Design)
- [ ] **CEO-001 (P1, human: ~2 days / CC: ~30 min) — Extract Blade Partials**
- [ ] **CEO-002 (P1, human: ~1 hour / CC: ~15 min) — Design Token JSON Export**
- [ ] **CEO-003 (P1, human: ~2 hours / CC: ~20 min) — Accessibility Requirements**
- [ ] **CEO-004 (P2, human: ~30 min / CC: ~10 min) — Flash Dismiss Error Handling**
- [ ] **CEO-005 (P2, human: ~30 min / CC: ~10 min) — Radar Chart ResizeObserver**
- [ ] **CEO-006 (P2, human: ~1 hour / CC: ~15 min) — Token Naming Convention Doc**
- [ ] **DESIGN-001 (P1, human: ~2h / CC: ~15min) — Information Hierarchy Specs**
- [ ] **DESIGN-002 (P1, human: ~1h / CC: ~10min) — Mobile Nav Spec**
- [ ] **DESIGN-003 (P1, human: ~2h / CC: ~20min) — Complete Interaction States**
- [ ] **DESIGN-004 (P1, human: ~1h / CC: ~15min) — User Journey Storyboard**
- [ ] **DESIGN-005 (P1, human: ~1h / CC: ~10min) — Motion Moments**
- [ ] **DESIGN-006 (P1, human: ~1h / CC: ~15min) — Token-Component Mapping**
- [ ] **DESIGN-007 (P1, human: ~2h / CC: ~20min) — Responsive + A11y Specs**


<!-- autoplan-accepted:design -->
- Add information hierarchy specs per screen (Dashboard, Candidate Show, Comparison)
- Add mobile nav hamburger to drawer specification
- Complete interaction state table (10 missing states across 7 features)
- Add user journey storyboard with 7-step emotional arc
- Specify one authored motion moment per primary screen
- Add token-component mapping table (badge, card, form-input, table)
- Add responsive specs per breakpoint + a11y checklist (touch targets, ARIA landmarks, visited links, heading hierarchy)
- Defer empty state illustrations, mobile nav animation, accordion easing, confetti implementation, token JSON validation to TODOS.md
<!-- /autoplan-accepted:design -->

---

### Phase 2.5: DX Review (Developer-facing scope detected)

#### Step 0: DX Scope Assessment
**0A. Initial DX Rating:** **5/10** — The plan is a design system overhaul for internal HR tool UI. It affects developers who maintain Blade templates, CSS, and Alpine.js interactions. No public API/CLI/SDK changes.

**What 10/10 looks like:** Component library with Storybook docs, design token package (npm), copy-paste component examples, migration guide for existing views, automated visual regression in CI, clear contribution guidelines.

**0B. Product Type:** **Internal Tool / Design System** — Developers are internal team maintaining the codebase. Primary DX concerns: component discoverability, token consistency, migration effort, documentation.

**0C. Developer Journey Map:**
```
STAGE           | CURRENT STATE                    | TARGET STATE
----------------|----------------------------------|-----------------------------
Discovery       | Search Blade files for patterns  | Component library index page
Onboarding      | Read existing views, copy-paste  | Storybook + component docs
First Use       | Trial & error with tokens        | Guided example + token ref
Daily Work      | Inconsistent patterns across views | Unified partials + token ref
Debugging       | Browser DevTools for CSS vars    | Token inspector + design tokens
Upgrading       | Manual find/replace              | Automated codemod / token sync
```

**0D. TTHW Assessment:** Not applicable (no external developer onboarding). Internal "Time To First Component" ≈ 30 min (find pattern, copy, adapt). Target: < 5 min with component library.

#### Pass 1: Getting Started Experience — Rating: 4/10
**Gaps:**
1. **No component documentation** — Developers must read existing Blade files to understand patterns
2. **No token reference** — CSS custom properties defined inline in `app.blade.php`, no centralized reference
3. **No migration guide** — 17 views to update, no guidance on order or patterns
4. **No Storybook/preview** — Can't preview components in isolation

**FIX TO 10:** Add to plan:
- Component index page (`resources/views/components/index.blade.php`) with live examples
- Design token reference page (`/design-tokens` route)
- Migration guide with view-by-view checklist
- Storybook setup (deferred to TODOS.md)

**Auto-decision (P1 completeness, P5 explicit):** **ACCEPTED** — Add component index + token reference to plan. Storybook deferred.

#### Pass 2: API/CLI Ergonomics — Rating: N/A (No public API)
**Not applicable** — This is a view-layer refactor. No API, CLI, or SDK changes.

#### Pass 3: Error Handling — Rating: 6/10
**Current:** Flash message error handling (CEO expansion #4), Chart.js error boundary (CEO expansion #5), radar chart ResizeObserver (CEO expansion #5).

**Gaps:**
1. **Blade compilation errors** — No graceful degradation if partial missing
2. **Token resolution errors** — Silent failure if CSS var undefined
3. **Alpine.js errors** — Flash dismiss try-catch added, but other Alpine components unguarded

**FIX TO 10:** Add to plan:
- Blade `@isset` checks for partial inclusion
- CSS `@supports` fallback for custom properties
- Global Alpine.js error handler in `app.blade.php`

**Auto-decision (P1 completeness):** **ACCEPTED** — Add error handling specs to plan.

#### Pass 4: Documentation — Rating: 5/10
**Current:** Verification plan lists manual checks. No component docs, no token docs, no migration guide.

**Gaps:**
1. **Component API docs** — Props, slots, variants not documented
2. **Token usage guide** — When to use CSS var vs Tailwind class
3. **Alpine.js patterns** — Documented patterns for flash, accordion, chart
4. **Responsive behavior** — Breakpoint specs added (Design review) but not in component docs

**FIX TO 10:** Add to plan:
- Component README per partial (`badge.md`, `card.md`, etc.)
- Token usage decision tree (CSS var for theming, Tailwind for utility)
- Alpine.js pattern library in `docs/alpine-patterns.md`
- Responsive specs per component

**Auto-decision (P1 completeness, P3 pragmatic):** **ACCEPTED** — Add component docs + token guide to plan. Full pattern library deferred.

#### Pass 5: Escape Hatches — Rating: 7/10
**Current:** Plan allows inline `style` attribute overrides (Design review 0C). Token escape hatch documented.

**Gaps:**
1. **No opt-out for design system** — All views must use tokens
2. **No legacy support** — Old patterns must be migrated in same PR

**FIX TO 10:** Add to plan:
- Legacy mode: `.legacy` CSS scope for views not yet migrated
- Gradual migration: per-view opt-in via `@php $useDesignSystem = true @endphp`

**Auto-decision (P3 pragmatic, P5 explicit):** **ACCEPTED** — Add gradual migration strategy to plan.

#### Pass 6: Upgrade Path — Rating: 6/10
**Current:** No schema migrations, no breaking changes. Reversible via `git revert`.

**Gaps:**
1. **Token versioning** — No versioning for design tokens
2. **Breaking change process** — How to handle token renames/removals
3. **Deprecation strategy** — Old token names preserved with warnings

**FIX TO 10:** Add to plan:
- Token version in `design-tokens.json` (`$version`)
- Deprecation warnings in CSS (`@warn` via build tool)
- 2-version support policy

**Auto-decision (P1 completeness, P6 bias to action):** **ACCEPTED** — Add token versioning + deprecation policy to plan.

#### Pass 7: Dev Environment Friction — Rating: 5/10
**Current:** `composer dev` runs artisan serve + queue + vite + pail. No component preview.

**Gaps:**
1. **No component preview** — Must navigate full app to see component states
2. **No visual regression in CI** — Manual verification only
3. **Token changes require full reload** — No HMR for CSS custom properties

**FIX TO 10:** Add to plan:
- Component preview page at `/components` (all states)
- Visual regression in CI (deferred to TODOS.md)
- Vite HMR for CSS vars (requires Vite migration, deferred)

**Auto-decision (P1 completeness, P3 pragmatic):** **ACCEPTED** — Add component preview page to plan. Visual regression deferred.

#### Pass 8: Competitive Benchmarking — Rating: N/A
**Not applicable** — Internal tool, no external competition for DX.

---

### DX Litmus Scorecard (Outside Voices)

| Check | opencode (in-host) | Codex | Consensus |
|-------|-------------------|-------|-----------|
| 1. Getting started < 5 min? | N/A (internal) | N/A | N/A |
| 2. API/CLI naming guessable? | N/A | N/A | N/A |
| 3. Error messages actionable? | ⚠️ PARTIAL | N/A | N/A |
| 4. Docs findable & complete? | ❌ NO | N/A | N/A |
| 5. Upgrade path safe? | ⚠️ PARTIAL | N/A | N/A |
| 6. Dev environment friction-free? | ⚠️ PARTIAL | N/A | N/A |

Codex: unavailable. opencode (in-host): completed.

---

### NOT in Scope (DX)
- Public API/CLI/SDK design
- External developer onboarding
- Storybook setup (deferred to TODOS.md)
- Visual regression CI (deferred to TODOS.md)
- Vite HMR for CSS vars (requires Vite migration)

### What Already Exists (DX)
- `composer dev` — Unified dev command (serve + queue + vite + logs)
- `tailwind.config` in `app.blade.php` — Token introspection via browser DevTools
- Alpine.js patterns — Flash dismiss, accordion, x-init animations
- Chart.js radar chart — Reusable chart component pattern
- Blade `@yield`/`@section` — Consistent layout structure

### TODOS.md Updates (DX)
- **DX-001:** Set up Storybook for component library
- **DX-002:** Add visual regression testing (Playwright/Percy) to CI
- **DX-003:** Create component preview page (`/components`)
- **DX-004:** Document Alpine.js pattern library
- **DX-005:** Migrate to Vite-built Tailwind for HMR + performance
- **DX-006:** Add design token versioning to `design-tokens.json`
- **DX-007:** Create codemod for legacy pattern migration

---

### Implementation Tasks (DX Review)
- [ ] **DX-001 (P1, human: ~2h / CC: ~15min) — Component Index Page** — Create `resources/views/components/index.blade.php` with live examples of all partials
  - Surfaced by: Pass 1 (Getting Started)
  - Files: `resources/views/components/index.blade.php`, `web.php` (route)
  - Verify: `/components` shows all partials in all states
- [ ] **DX-002 (P1, human: ~1h / CC: ~10min) — Design Token Reference Page** — Add `/design-tokens` route displaying all tokens with values
  - Surfaced by: Pass 1 (Getting Started)
  - Files: `routes/web.php`, `DesignTokenController.php`, view
  - Verify: Page shows all CSS vars + Tailwind mappings
- [ ] **DX-003 (P1, human: ~1h / CC: ~15min) — Migration Guide** — Add view-by-view migration checklist to plan
  - Surfaced by: Pass 1 (Getting Started)
  - Files: `implementation_plan.md`
  - Verify: Checklist covers all 17 views
- [ ] **DX-004 (P1, human: ~1h / CC: ~10min) — Error Handling Specs** — Add Blade partial guards, CSS fallbacks, Alpine global handler
  - Surfaced by: Pass 3 (Error Handling)
  - Files: `implementation_plan.md`
  - Verify: Specs added to plan
- [ ] **DX-005 (P1, human: ~2h / CC: ~20min) — Component Documentation** — Add README per partial + token usage guide
  - Surfaced by: Pass 4 (Documentation)
  - Files: `resources/views/components/*.md`, `docs/design-tokens.md`
  - Verify: Each partial has props/slots/variants documented
- [ ] **DX-006 (P1, human: ~1h / CC: ~10min) — Gradual Migration Strategy** — Add legacy mode + per-view opt-in
  - Surfaced by: Pass 5 (Escape Hatches)
  - Files: `implementation_plan.md`, `app.blade.php`
  - Verify: Legacy CSS scope + opt-in variable documented
- [ ] **DX-007 (P1, human: ~1h / CC: ~10min) — Token Versioning** — Add `$version` to design-tokens.json + deprecation policy
  - Surfaced by: Pass 6 (Upgrade Path)
  - Files: `implementation_plan.md`
  - Verify: Version field + deprecation policy in plan
- [ ] **DX-008 (P1, human: ~1h / CC: ~15min) — Component Preview Page** — Add `/components` route with all states
  - Surfaced by: Pass 7 (Dev Environment)
  - Files: `routes/web.php`, `ComponentPreviewController.php`, view
  - Verify: Page renders all partials in loading/empty/error/success states

---

### DX Decisions Made: 8 (all auto-approved via 6 principles)
### DX Decisions Deferred: 7 (to TODOS.md)

### Overall DX Score: 5/10 → 8/10 after fixes

---

### DX Outside Voices Consensus Table

| Dimension | opencode (in-host) | Codex | Consensus |
|-------|-------------------|-------|-----------|
| 1. Getting started < 5 min? | N/A (internal) | N/A | N/A |
| 2. API/CLI naming guessable? | N/A | N/A | N/A |
| 3. Error messages actionable? | ⚠️ PARTIAL | N/A | N/A |
| 4. Docs findable & complete? | ❌ NO | N/A | N/A |
| 5. Upgrade path safe? | ⚠️ PARTIAL | N/A | N/A |
| 6. Dev environment friction-free? | ⚠️ PARTIAL | N/A | N/A |

Codex: unavailable. opencode (in-host): completed.

---

### Cross-Phase Themes
- **Token system as foundation:** CEO, Design, and DX all identify the token system in `app.blade.php` as the critical foundation. CEO extracted partials; Design formalized token-component mapping; DX adds component docs + token reference.
- **Performance vs Scope trade-off:** CDN Tailwind (340KB) acknowledged in all three reviews as known performance cost, explicitly deferred. Consistent decision.
- **Completeness over cleverness:** All three reviews favor explicit, complete specifications over minimal/abstract ones (P1, P5).
- **Gradual migration:** DX adds gradual migration strategy; CEO extracted partials; Design formalized token-component mapping. All aligned on incremental approach.

---

### Deferred to TODOS.md (Cross-Phase, updated)
- Visual regression test suite (Playwright/Percy)
- Dark mode support
- Vite-built Tailwind migration (separate epic)
- Build-time token validation script
- Empty state illustrations
- Mobile nav drawer animation timing
- Accordion easing function
- Confetti animation implementation
- Token JSON format validation
- Storybook setup
- Component preview page
- Visual regression CI
- Alpine.js pattern library
- Design token versioning
- Legacy pattern codemod

---

### Implementation Tasks (aggregated across CEO + Design + DX)
- [ ] **CEO-001 (P1, human: ~2 days / CC: ~30 min) — Extract Blade Partials**
- [ ] **CEO-002 (P1, human: ~1 hour / CC: ~15 min) — Design Token JSON Export**
- [ ] **CEO-003 (P1, human: ~2 hours / CC: ~20 min) — Accessibility Requirements**
- [ ] **CEO-004 (P2, human: ~30 min / CC: ~10 min) — Flash Dismiss Error Handling**
- [ ] **CEO-005 (P2, human: ~30 min / CC: ~10 min) — Radar Chart ResizeObserver**
- [ ] **CEO-006 (P2, human: ~1 hour / CC: ~15 min) — Token Naming Convention Doc**
- [ ] **DESIGN-001 (P1, human: ~2h / CC: ~15min) — Information Hierarchy Specs**
- [ ] **DESIGN-002 (P1, human: ~1h / CC: ~10min) — Mobile Nav Spec**
- [ ] **DESIGN-003 (P1, human: ~2h / CC: ~20min) — Complete Interaction States**
- [ ] **DESIGN-004 (P1, human: ~1h / CC: ~15min) — User Journey Storyboard**
- [ ] **DESIGN-005 (P1, human: ~1h / CC: ~10min) — Motion Moments**
- [ ] **DESIGN-006 (P1, human: ~1h / CC: ~15min) — Token-Component Mapping**
- [ ] **DESIGN-007 (P1, human: ~2h / CC: ~20min) — Responsive + A11y Specs**
- [ ] **DX-001 (P1, human: ~2h / CC: ~15min) — Component Index Page**
- [ ] **DX-002 (P1, human: ~1h / CC: ~10min) — Design Token Reference Page**
- [ ] **DX-003 (P1, human: ~1h / CC: ~15min) — Migration Guide**
- [ ] **DX-004 (P1, human: ~1h / CC: ~10min) — Error Handling Specs**
- [ ] **DX-005 (P1, human: ~2h / CC: ~20min) — Component Documentation**
- [ ] **DX-006 (P1, human: ~1h / CC: ~10min) — Gradual Migration Strategy**
- [ ] **DX-007 (P1, human: ~1h / CC: ~10min) — Token Versioning**
- [ ] **DX-008 (P1, human: ~1h / CC: ~15min) — Component Preview Page**


<!-- autoplan-accepted:dx -->
- Add component index page (`/components`) with live examples of all partials
- Add design token reference page (`/design-tokens`) displaying all tokens
- Add view-by-view migration checklist to plan
- Add Blade partial guards, CSS fallbacks, Alpine global error handler
- Add component README per partial + token usage decision tree
- Add gradual migration strategy (legacy mode + per-view opt-in)
- Add token versioning to design-tokens.json + deprecation policy
- Add component preview page with all interaction states
- Defer Storybook, visual regression CI, Vite HMR, Alpine pattern library, codemod to TODOS.md
<!-- /autoplan-accepted:dx -->

---

### Phase 3: Eng Review (Required Gate — Reviews Final Amended Plan)

#### Step 0: Scope Challenge
**Existing Code Leverage:**
| Sub-problem | Existing Code | Leverage Strategy |
|-------------|---------------|-------------------|
| Token system | `app.blade.php` tailwind.config + CSS vars | Extend, don't replace — all reviews build on this |
| Blade partials | 4 new partials (badge, card, form-input, table) | Follow Laravel Blade conventions; no new framework |
| Alpine.js patterns | Flash dismiss, accordion, x-init | Extend existing patterns; add error boundary |
| Chart.js radar | `candidates/show.blade.php` chart | Reuse component; add ResizeObserver |
| Design tokens | Defined in plan, exported as JSON | Single source of truth in `app.blade.php` |

**Complexity Check:** Plan touches 17 views + 4 new partials + 2 new routes + 2 new controllers = 25 files. Exceeds 8-file threshold but all changes are confined to view layer with identical token-application pattern. No new services, models, or database changes. **Verdict: Acceptable — breadth not depth.**

**Minimum Set for Core Goal:** All 17 views must use tokens for visual consistency. 4 partials eliminate duplication. 2 routes/controllers enable DX features (component index, token reference). All in scope.

**Search Check (Layer 1 — Tried & True):**
- Laravel Blade partials → Built-in pattern ✅
- CSS custom properties → Native platform feature ✅
- Alpine.js error boundaries → Community pattern (`@alpinejs/csp`) ✅
- Chart.js ResizeObserver → Standard integration ✅
- Design token JSON → Style Dictionary format (standard) ✅

No custom solutions where built-ins exist.

**TODOS Cross-Reference:** No blocking deferred items. Plan creates new TODOS (visual regression, dark mode, Vite migration) but doesn't depend on them.

**Completeness Check:** Plan specifies complete token system, all 17 views, all 5 interaction states, responsive specs, accessibility checklist, component docs, migration guide, error handling, gradual migration, token versioning, component preview. **Complete version — no shortcuts.**

#### Section 1: Architecture Review
**Component Structure:**
```
app.blade.php (Layout + Token Definitions)
├── components/
│   ├── badge.blade.php      (NEW)
│   ├── card.blade.php       (NEW)
│   ├── form-input.blade.php (NEW)
│   └── table.blade.php      (NEW)
├── views/ (17 MODIFIED)
│   ├── dashboard.blade.php
│   ├── candidates/index.blade.php
│   ├── candidates/create.blade.php
│   ├── candidates/show.blade.php
│   ├── comparisons/index.blade.php
│   ├── comparisons/show.blade.php
│   ├── batches/index.blade.php
│   ├── batches/create.blade.php
│   ├── batches/show.blade.php
│   ├── apply/show.blade.php
│   └── apply/success.blade.php
├── routes/
│   ├── web.php              (2 NEW routes: /components, /design-tokens)
└── controllers/
    ├── ComponentController.php   (NEW)
    └── DesignTokenController.php (NEW)
```

**Dependency Graph:**
```
app.blade.php
    │
    ├──► components/* (depend on token definitions)
    │
    ├──► views/* (depend on tokens + components)
    │
    ├──► routes/web.php (registers new routes)
    │
    └──► controllers/* (thin, delegate to views)
```

**Coupling:** Views now depend on centralized tokens in `app.blade.php` and partials in `components/`. Justified — single source of truth. No circular dependencies. Controllers are thin.

**Scaling:** Static assets only. No database/API scaling concerns. Blade view caching handles render performance.

**Single Points of Failure:** `app.blade.php` token definitions — if broken, all views affected. Mitigation: Token changes require visual verification; partial extraction limits blast radius.

**Security Architecture:** No new attack surface. View-only changes. No new endpoints accepting user input (DX routes are read-only). Alpine.js error handler prevents error leakage.

**Production Failure Scenarios:**
| Scenario | Impact | Mitigation |
|----------|--------|------------|
| Token typo in app.blade.php | Visual regression across all views | Visual verification required; git revert < 5 min |
| Partial missing | Laravel 500 on affected views | `@isset` guards added (DX expansion) |
| Alpine.js error | Flash messages persist | Global error handler added (DX expansion) |
| Chart.js canvas error | Blank radar chart | ResizeObserver + error boundary (CEO/Design) |

**Rollback:** `git revert` on modified files. No migrations. < 5 min.

**ASCII Architecture Diagram:**
```
┌─────────────────────────────────────────────────────────────┐
│                    app.blade.php (Layout)                   │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐   │
│  │ Tailwind    │  │ CSS Custom   │  │ Design Tokens    │   │
│  │ Config      │  │ Properties   │  │ (JS + CSS vars)  │   │
│  └─────────────┘  └──────────────┘  └──────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
              ┌─────────────┼─────────────┐
              ▼             ▼             ▼
       ┌──────────┐  ┌──────────┐  ┌──────────┐
       │  badge   │  │  card    │  │form-input│  ← NEW partials
       └──────────┘  └──────────┘  └──────────┘
              │             │             │
    ┌─────────┼─────────────┼─────────────┼─────────┐
    ▼         ▼             ▼             ▼         ▼
┌───────┐ ┌───────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│Dashboard│ │Candidates│ │Comparisons│ │Batches  │ │Apply    │
│(5 views)│ │(3 views) │ │(2 views) │ │(3 views)│ │(2 views)│
└───────┘ └───────┘ └──────────┘ └──────────┘ └──────────┘
              │
              ▼
       ┌──────────────┐
       │ /components  │  ← NEW DX routes
       │ /design-tokens│
       └──────────────┘
```

#### Section 2: Code Quality Review
**Organization:** Tokens centralized in `app.blade.php`. Partials in `resources/views/components/`. Follows Laravel conventions.

**DRY:** 4 partials eliminate token duplication across 17 views. Token-component mapping table formalizes usage.

**Naming:** CSS vars use kebab-case (`--surface-primary`). Tailwind config uses camelCase (`surfacePrimary`). Mapping documented.

**Error Handling:** 
- Flash dismiss try-catch (CEO)
- Chart.js error boundary (CEO)
- Radar chart ResizeObserver (CEO)
- Blade `@isset` guards for partials (DX)
- CSS `@supports` fallback (DX)
- Global Alpine error handler (DX)

**Over-engineering:** None. Token system is explicit. Partials are simple Blade includes. No abstraction layers.

**Under-engineering:** Silent token typo risk acknowledged. Token validation script deferred to TODOS.md.

**Cyclomatic Complexity:** No new logic branches. All changes declarative (HTML/CSS/Alpine).

#### Section 3: Test Review
**Test Diagram:**
```
NEW UX FLOWS: None (visual changes only)
NEW DATA FLOWS: None
NEW CODEPATHS: 
  - Partial inclusion (4 new partials)
  - Token variable resolution in Blade
  - 2 new DX routes + controllers
  - Alpine.js global error handler
  - Blade @isset guards
  - CSS @supports fallback
NEW BACKGROUND JOBS: None
NEW INTEGRATIONS: None
NEW ERROR/RESCUE PATHS:
  - Flash dismiss try-catch
  - Chart.js error boundary
  - Radar chart ResizeObserver
  - Partial missing → graceful degradation
  - Token undefined → CSS fallback
```

**Test Specs:**
- Unit: Partial rendering with token props (4 tests)
- Unit: Controller actions for `/components`, `/design-tokens` (2 tests)
- Integration: Each view renders with tokens applied (17 tests)
- Integration: DX routes return correct data (2 tests)
- E2E: Visual regression on 6 page groups — **DEFERRED** (TODOS.md)
- Accessibility: axe-core audit on all views — **ACCEPTED** (verification plan)

**LLM/Prompt Changes:** None.

**Test Ambition:** "What would make you confident shipping at 2am Friday?" → Visual verification of all 17 views + axe-core audit + token reference page loads.

#### Section 4: Performance Review
**N+1 Queries:** N/A (view-only). **Memory:** Token objects in Blade — negligible. **Indexes:** N/A. **Caching:** Blade views cached by Laravel view cache. **Background Jobs:** None.

**Slow Paths:** 
1. Tailwind CDN (340KB) blocks First Paint — **KNOWN, DEFERRED** (Vite migration separate epic)
2. Token resolution in Blade — O(1) per view, negligible

**Connection Pool:** N/A.

---

### Eng Litmus Scorecard (Outside Voices)

| Check | opencode (in-host) | Codex | Consensus |
|-------|-------------------|-------|-----------|
| 1. Architecture sound? | ✅ YES | N/A | N/A |
| 2. Test coverage sufficient? | ✅ YES (unit + integration) | N/A | N/A |
| 3. Performance risks addressed? | ⚠️ CDN Tailwind deferred | N/A | N/A |
| 4. Security threats covered? | ✅ YES | N/A | N/A |
| 5. Error paths handled? | ✅ YES (6 error paths) | N/A | N/A |
| 6. Deployment risk manageable? | ✅ YES (< 5 min rollback) | N/A | N/A |

Codex: unavailable (ChatGPT account model compatibility). opencode (in-host): completed.

---

### NOT in Scope (Eng)
- Vite-built Tailwind migration (separate epic)
- Dark mode (deferred to TODOS.md)
- Visual regression test infrastructure (deferred to TODOS.md)
- Storybook setup (deferred to TODOS.md)
- Component preview page (added to plan, not separate epic)
- Alpine.js pattern library (deferred to TODOS.md)
- Design token versioning (added to plan)
- Legacy pattern codemod (deferred to TODOS.md)

### What Already Exists (Eng)
- `app.blade.php`: Tailwind config + CSS custom properties
- Pipeline bars, stat cards, verdicts panel (dashboard patterns)
- Filter bar, status badges, table patterns (candidates index)
- Score card, radar chart, accordion (candidate show)
- Flash message Alpine.js pattern
- Chart.js radar chart with custom tooltip
- `composer dev` — Unified dev command
- Laravel Blade partial system

### TODOS.md Updates (Eng)
- **ENG-001:** Set up visual regression testing (Playwright/Percy) in CI
- **ENG-002:** Add design token validation to CI pipeline
- **ENG-003:** Create codemod for legacy pattern migration
- **ENG-004:** Audit heading hierarchy across all 17 views
- **ENG-005:** Verify visited link distinction per Universal Rule

---

### Implementation Tasks (Eng Review)
- [ ] **ENG-001 (P1, human: ~2h / CC: ~15min) — Architecture Diagram** — Add ASCII architecture diagram to plan
  - Surfaced by: Section 1 (Architecture)
  - Files: implementation_plan.md
  - Verify: Architecture diagram in plan
- [ ] **ENG-002 (P1, human: ~1h / CC: ~10min) — Test Specs** — Add unit/integration test specifications to plan
  - Surfaced by: Section 3 (Test Review)
  - Files: implementation_plan.md
  - Verify: Test specs for 4 partials + 2 controllers + 17 views
- [ ] **ENG-003 (P1, human: ~1h / CC: ~10min) — Error Path Documentation** — Document all 6 error paths with user-visible messages
  - Surfaced by: Section 2 (Code Quality) + Section 4 (Performance)
  - Files: implementation_plan.md
  - Verify: Error path table in plan
- [ ] **ENG-004 (P1, human: ~1h / CC: ~10min) — Rollback Procedure** — Add explicit rollback steps to plan
  - Surfaced by: Section 1 (Architecture)
  - Files: implementation_plan.md
  - Verify: Rollback steps documented
- [ ] **ENG-005 (P2, human: ~30min / CC: ~5min) — TODOS.md Sync** — Add Eng-specific TODOS to TODOS.md
  - Surfaced by: TODOS cross-reference
  - Files: TODOS.md
  - Verify: ENG-001 through ENG-005 in TODOS.md

---

### Eng Decisions Made: 5 (all auto-approved via 6 principles)
### Eng Decisions Deferred: 5 (to TODOS.md)

### Overall Eng Score: 9/10 (high — view-layer only, well-scoped, complete error handling)

---

### Eng Outside Voices Consensus Table

| Dimension | opencode (in-host) | Codex | Consensus |
|-----------|-------------------|-------|-----------|
| 1. Architecture sound? | ✅ YES | N/A | N/A |
| 2. Test coverage sufficient? | ✅ YES | N/A | N/A |
| 3. Performance risks addressed? | ⚠️ CDN Tailwind deferred | N/A | N/A |
| 4. Security threats covered? | ✅ YES | N/A | N/A |
| 5. Error paths handled? | ✅ YES | N/A | N/A |
| 6. Deployment risk manageable? | ✅ YES (< 5 min rollback) | N/A | N/A |

Codex: unavailable (ChatGPT account model compatibility). opencode (in-host): completed.

---

### Cross-Phase Themes (All 4 Phases)
- **Token system as foundation:** CEO, Design, DX, Eng all identify the token system in `app.blade.php` as the critical foundation. CEO extracted partials; Design formalized token-component mapping; DX adds component docs + token reference; Eng validates architecture.
- **Performance vs Scope trade-off:** CDN Tailwind (340KB) acknowledged in all four reviews as known performance cost, explicitly deferred. Consistent decision.
- **Completeness over cleverness:** All four reviews favor explicit, complete specifications over minimal/abstract ones (P1, P5).
- **Gradual migration:** DX adds gradual migration strategy; CEO extracted partials; Design formalized token-component mapping; Eng validates architecture. All aligned on incremental approach.
- **Error handling completeness:** CEO added flash/chart errors; DX added partial/CSS/Alpine guards; Eng validated all 6 error paths covered.

---

### Deferred to TODOS.md (Cross-Phase, Complete)
- Visual regression test suite (Playwright/Percy)
- Dark mode support
- Vite-built Tailwind migration (separate epic)
- Build-time token validation script
- Empty state illustrations
- Mobile nav drawer animation timing
- Accordion easing function
- Confetti animation implementation
- Token JSON format validation
- Storybook setup
- Component preview page (added to plan)
- Visual regression CI
- Alpine.js pattern library
- Design token versioning (added to plan)
- Legacy pattern codemod

---

### Implementation Tasks (Aggregated Across All 4 Phases)
- [ ] **CEO-001 (P1, human: ~2 days / CC: ~30 min) — Extract Blade Partials**
- [ ] **CEO-002 (P1, human: ~1 hour / CC: ~15 min) — Design Token JSON Export**
- [ ] **CEO-003 (P1, human: ~2 hours / CC: ~20 min) — Accessibility Requirements**
- [ ] **CEO-004 (P2, human: ~30 min / CC: ~10 min) — Flash Dismiss Error Handling**
- [ ] **CEO-005 (P2, human: ~30 min / CC: ~10 min) — Radar Chart ResizeObserver**
- [ ] **CEO-006 (P2, human: ~1 hour / CC: ~15 min) — Token Naming Convention Doc**
- [ ] **DESIGN-001 (P1, human: ~2h / CC: ~15min) — Information Hierarchy Specs**
- [ ] **DESIGN-002 (P1, human: ~1h / CC: ~10min) — Mobile Nav Spec**
- [ ] **DESIGN-003 (P1, human: ~2h / CC: ~20min) — Complete Interaction States**
- [ ] **DESIGN-004 (P1, human: ~1h / CC: ~15min) — User Journey Storyboard**
- [ ] **DESIGN-005 (P1, human: ~1h / CC: ~10min) — Motion Moments**
- [ ] **DESIGN-006 (P1, human: ~1h / CC: ~15min) — Token-Component Mapping**
- [ ] **DESIGN-007 (P1, human: ~2h / CC: ~20min) — Responsive + A11y Specs**
- [ ] **DX-001 (P1, human: ~2h / CC: ~15min) — Component Index Page**
- [ ] **DX-002 (P1, human: ~1h / CC: ~10min) — Design Token Reference Page**
- [ ] **DX-003 (P1, human: ~1h / CC: ~15min) — Migration Guide**
- [ ] **DX-004 (P1, human: ~1h / CC: ~10min) — Error Handling Specs**
- [ ] **DX-005 (P1, human: ~2h / CC: ~20min) — Component Documentation**
- [ ] **DX-006 (P1, human: ~1h / CC: ~10min) — Gradual Migration Strategy**
- [ ] **DX-007 (P1, human: ~1h / CC: ~10min) — Token Versioning**
- [ ] **DX-008 (P1, human: ~1h / CC: ~15min) — Component Preview Page**
- [ ] **ENG-001 (P1, human: ~2h / CC: ~15min) — Architecture Diagram**
- [ ] **ENG-002 (P1, human: ~1h / CC: ~10min) — Test Specs**
- [ ] **ENG-003 (P1, human: ~1h / CC: ~10min) — Error Path Documentation**
- [ ] **ENG-004 (P1, human: ~1h / CC: ~10min) — Rollback Procedure**
- [ ] **ENG-005 (P2, human: ~30min / CC: ~5min) — TODOS.md Sync**


<!-- autoplan-accepted:eng -->
- Add ASCII architecture diagram to plan
- Add unit/integration test specifications (4 partials + 2 controllers + 17 views)
- Document all 6 error paths with user-visible messages
- Add explicit rollback procedure (< 5 min via git revert)
- Sync Eng-specific TODOS to TODOS.md (ENG-001 through ENG-005)
- Defer visual regression CI, codemod, Vite migration, Storybook, Alpine patterns to TODOS.md
<!-- /autoplan-accepted:eng -->
## Review record

### Phase 1: CEO Review (SELECTIVE EXPANSION mode)

#### 0A. Premise Challenge
**Premise 1:** "The current UI is functional but inconsistent" — **VALIDATED**. The codebase uses raw Tailwind utilities with duplicated color mappings in `app.blade.php:7-41` (inline `tailwind.config` object) and CSS custom properties (`:root` variables at lines 47-61) that partially overlap. No design token abstraction exists.

**Premise 2:** "A cohesive design system will improve maintainability" — **VALIDATED**. 17 Blade views reference colors/spacings directly. Centralizing tokens reduces drift risk.

**Premise 3:** "CDN-based Tailwind approach should be preserved" — **QUESTIONABLE**. The PERFORMANCE_AUDIT.md identifies the Tailwind CDN (~340 KB JS) as HIGH severity: blocks rendering, adds 200-400ms to First Paint. Vite-built CSS would eliminate this. However, the plan explicitly states "CDN-based Tailwind approach preserved (no migration to Vite-built Tailwind)" as a non-goal. This is a strategic choice to limit scope.

**Premise 4:** "No controller/model/service changes needed" — **VALIDATED**. This is a pure view-layer refactor. All 17 modifications target Blade templates only.

**Premise 5:** "Manual verification is sufficient" — **RISKY**. No automated visual regression tests specified. With 17 views across 6 page groups, manual verification is error-prone. Recommend adding Percy/Playwright visual tests (deferred to TODOS.md per SELECTIVE EXPANSION).

**What would happen if we did nothing?** The UI remains inconsistent but functional. No user-facing breakage. Technical debt accumulates in view layer.

#### 0B. Existing Code Leverage
| Sub-problem | Existing Code | Leverage Strategy |
|-------------|---------------|-------------------|
| Color tokens | `app.blade.php:12-28` (tailwind.config), `:root` vars (lines 47-61) | Unify into single source of truth in `app.blade.php` tailwind.config |
| Spacing rhythm | Ad-hoc `p-4`, `px-6`, `py-3` across views | Define 4px base grid tokens; apply consistently |
| Surface hierarchy | `bg-white`, `bg-gray-50`, `shadow-sm` used inconsistently | Define 3 elevation levels as utilities |
| Badge component | Status badges in `candidates/index.blade.php`, `dashboard.blade.php` | Extract unified `badge` component with dot-prefix pattern |
| Card component | Implicit cards via `bg-white rounded-lg shadow` | Extract explicit card component with elevation variants |
| Form styling | Repeated input classes across `create.blade.php` files | Extract form input component with focus ring |
| Table styling | Repeated table classes in index views | Extract table component with sortable headers |
| Flash messages | `session()` checks in `app.blade.php:111-139` | Enhance existing Alpine.js auto-dismiss pattern |

**Rebuilding vs refactoring:** The plan modifies 17 Blade files directly rather than extracting components. This is appropriate for Blade (no component framework), but creates duplication risk. Recommend extracting partials for badge, card, form-input, table (deferred).

#### 0C. Dream State Mapping
```
CURRENT STATE                          THIS PLAN                          12-MONTH IDEAL
Raw Tailwind utils + duplicated config  Unified tokens in app.blade.php     Design token package (npm) shared across projects
Ad-hoc spacing (4/6/8/12/16px)          4px base grid rhythm                Spacing scale enforced by lint rule
2 elevation levels (flat/shadow)        3 elevations (bg/card/elevated)     Elevation system with semantic names
Inline styles in Blade                  CSS custom properties               CSS-in-JS or design token JSON for Figma sync
Manual visual verification              Manual verification plan            Automated visual regression (Percy)
CDN Tailwind (340KB, blocks render)     CDN Tailwind preserved              Vite-built Tailwind (production)
```

#### 0C-bis. Implementation Alternatives
**APPROACH A: Token-First Refactor (Plan as Written)**
- Summary: Define tokens in `app.blade.php`, apply to all 17 views directly
- Effort: M (~17 files, ~400 lines changed)
- Risk: Low
- Pros: Complete in one pass; single source of truth; minimal architectural change
- Cons: Duplicates token application across views; no component extraction; CDN Tailwind remains
- Reuses: Existing Blade structure, Alpine.js patterns

**APPROACH B: Component Extraction + Tokens**
- Summary: Extract Blade partials for badge, card, form-input, table; define tokens; apply via partials
- Effort: L (~17 views + 4-6 partials, ~600 lines)
- Risk: Medium (more files, integration risk)
- Pros: DRY; easier future changes; testable partials; foundation for design system package
- Cons: More upfront work; Blade partials have limitations vs Vue/React components
- Reuses: Same tokens; existing Alpine.js

**APPROACH C: Vite Migration + Component Extraction**
- Summary: Migrate to Vite-built Tailwind, extract components, define tokens
- Effort: XL (build config + 17 views + partials + Vite migration)
- Risk: High (build pipeline changes, cache busting, CI updates)
- Pros: Eliminates 340KB CDN block; production-ready; enables tree-shaking; best long-term
- Cons: Significant scope creep; blocks this plan's delivery
- Reuses: None new; replaces CDN approach

**RECOMMENDATION:** Choose **Approach A** because it delivers the complete design system in one pass with minimal risk, matching the plan's stated scope. Approach B's component extraction is a valid SELECTIVE EXPANSION candidate (deferred to TODOS.md). Approach C is correctly excluded as "NOT in scope" per plan.

Completeness: A=7/10 (covers all views but duplicates application), B=9/10 (DRY + tokens), C=10/10 (production build + tokens + DRY) — but C's effort is 3-4x A.

#### 0F. Mode Selection
**SELECTIVE EXPANSION** (autoplan default for feature enhancement on existing system). Baseline scope held; expansions surfaced for cherry-pick.

#### 0D. SELECTIVE EXPANSION Analysis

**Complexity Check:** Plan touches 17 files (exceeds 8-file smell threshold). However, all changes are confined to view layer with identical pattern (token application). No new classes/services. The complexity is breadth, not depth. **Verdict: Acceptable.**

**Minimum Set for Core Goal:** All 17 views must be updated for design system consistency. No single view can be deferred without breaking visual coherence. **Verdict: All 17 in scope.**

**Expansion Scan (Cherry-Pick Candidates):**

1. **Extract Blade Partials (Badge, Card, FormInput, Table)** — Effort: M, Risk: Medium. Creates reusable partials in `resources/views/components/`. Reduces duplication across 17 views. Enables future design system package extraction.
2. **Add Visual Regression Tests (Playwright + Percy)** — Effort: M, Risk: Low. Automates the manual verification plan. Catches drift on every PR.
3. **Migrate to Vite-built Tailwind** — Effort: XL, Risk: High. Eliminates 340KB CDN block. Performance win. Requires build pipeline changes.
4. **Design Token JSON Export for Figma Sync** — Effort: S, Risk: Low. Export tokens as JSON for designer handoff. Bridges code-design gap.
5. **Dark Mode Support** — Effort: M, Risk: Medium. Add dark variants to all tokens. Requires user preference detection.
6. **Accessibility Audit & Fixes** — Effort: M, Risk: Low. Ensure WCAG AA contrast, focus-visible, ARIA on new patterns.

**Cherry-Pick Ceremony (Auto-decided per 6 principles):**

| # | Proposal | Effort | Decision | Reasoning (Principles) |
|---|----------|--------|----------|------------------------|
| 1 | Extract Blade Partials | M | **ACCEPTED** (P1 completeness, P2 boil lakes) | In blast radius (all 17 views use same patterns); <1 day CC; eliminates duplication |
| 2 | Visual Regression Tests | M | **DEFERRED** to TODOS.md (P3 pragmatic) | Valuable but separate concern; can be added post-launch without blocking design system |
| 3 | Vite Migration | XL | **SKIP** (P3 pragmatic, P4 DRY - separate epic) | Out of blast radius; separate infrastructure epic |
| 4 | Token JSON Export | S | **ACCEPTED** (P1 completeness, P5 explicit) | Trivial effort; enables designer handoff; no code change risk |
| 5 | Dark Mode | M | **DEFERRED** to TODOS.md (P6 bias to action) | Nice-to-have; requires user preference infra not in scope |
| 6 | Accessibility Audit | M | **ACCEPTED** (P1 completeness, P5 explicit) | Required for production quality; in blast radius of token changes |

**Accepted Scope Additions:**
- Extract 4 Blade partials: `badge.blade.php`, `card.blade.php`, `form-input.blade.php`, `table.blade.php` in `resources/views/components/`
- Export design tokens as JSON (`public/design-tokens.json`) for Figma sync
- Add accessibility requirements to verification plan (WCAG AA contrast, focus-visible, ARIA)

**Deferred to TODOS.md:**
- Visual regression test suite (Playwright/Percy)
- Dark mode support
- Vite-built Tailwind migration (separate epic)

#### 0E. Temporal Interrogation
| Hour | Decision Needed | Resolution |
|------|-----------------|------------|
| 1 (Foundations) | Token naming convention (kebab-case vs camelCase) | Use kebab-case for CSS vars (`--surface-primary`); Tailwind config uses camelCase (`surfacePrimary`) — document mapping |
| 2-3 (Core Logic) | How to handle views with custom one-off styles? | Allow inline overrides via `style` attribute; document as escape hatch |
| 4-5 (Integration) | Partial extraction order (which first?) | Badge → Card → FormInput → Table (dependency order: badge used in card, form-input in forms, table in indexes) |
| 6+ (Polish) | Token JSON format for Figma? | Use Style Dictionary format (standard); include `$themes` for future dark mode |

#### Section 1: Architecture Review
**System Design:** Pure view-layer refactor. No new components, services, or data flows. Architecture unchanged.

**Dependency Graph:**
```
app.blade.php (token definitions)
    ├── components/badge.blade.php (NEW)
    ├── components/card.blade.php (NEW)
    ├── components/form-input.blade.php (NEW)
    ├── components/table.blade.php (NEW)
    └── 17 view files (MODIFIED)
```

**Data Flow:** None (view-only). **State Machines:** None. **Coupling:** Views now depend on token definitions in `app.blade.php` and new partials. Justified — single source of truth.

**Scaling:** No scaling concerns (static assets). **Single Points of Failure:** `app.blade.php` token definitions — if broken, all views affected. Mitigation: Token changes require visual verification.

**Security:** No new endpoints, params, or data mutations. **Production Failure:** Token typo → visual regression. Rollback: `git revert` on `app.blade.php` + partials (< 5 min).

**ASCII Architecture Diagram:**
```
┌─────────────────────────────────────────────────────────────┐
│                    app.blade.php (Layout)                   │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐   │
│  │ Tailwind    │  │ CSS Custom   │  │ Design Tokens    │   │
│  │ Config      │  │ Properties   │  │ (JS + CSS vars)  │   │
│  └─────────────┘  └──────────────┘  └──────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
              ┌─────────────┼─────────────┐
              ▼             ▼             ▼
       ┌──────────┐  ┌──────────┐  ┌──────────┐
       │  badge   │  │  card    │  │form-input│  ← NEW partials
       └──────────┘  └──────────┘  └──────────┘
              │             │             │
    ┌─────────┼─────────────┼─────────────┼─────────┐
    ▼         ▼             ▼             ▼         ▼
┌───────┐ ┌───────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│Dashboard│ │Candidates│ │Comparisons│ │Batches  │ │Apply    │
│(5 views)│ │(3 views) │ │(2 views) │ │(3 views)│ │(2 views)│
└───────┘ └───────┘ └──────────┘ └──────────┘ └──────────┘
```

#### Section 2: Error & Rescue Map
| Method/Codepath | What Can Go Wrong | Exception Class | Rescued? | Rescue Action | User Sees |
|-----------------|-------------------|-----------------|----------|---------------|-----------|
| Token application in Blade | Typo in CSS var name | None (silent) | N ← GAP | — | Broken layout (wrong color/spacing) |
| Partial inclusion | Partial file missing | ViewNotFoundException | Y | Laravel 500 handler | 500 error page |
| Alpine.js auto-dismiss | JS error in flash message | JS Error | N ← GAP | — | Flash message persists forever |
| Chart.js radar render | Canvas context lost | CanvasError | N ← GAP | — | Blank chart area |

**GAPS (Auto-fixed per P1/P5):**
1. **Token typo silent failure** → Add build-time token validation script (S effort) — **DEFERRED** to TODOS.md (separate tooling)
2. **Alpine.js error unhandled** → Wrap flash dismiss in try-catch — **ACCEPTED** (S effort, in blast radius)
3. **Chart.js error unhandled** → Add error boundary in radar chart partial — **ACCEPTED** (S effort, in blast radius)

#### Section 3: Security & Threat Model
**Attack Surface:** No expansion. View-only changes. No new inputs, endpoints, or file paths.
**Input Validation:** N/A. **Authorization:** N/A. **Secrets:** N/A. **Dependencies:** No new packages. **Data Classification:** N/A. **Injection:** N/A (no user input in tokens). **Audit Logging:** N/A.
**Verdict:** No security findings.

#### Section 4: Data Flow & Interaction Edge Cases
**Data Flows:** None new (view-only).
**Interaction Edge Cases:**

| Interaction | Edge Case | Handled? | How? |
|-------------|-----------|----------|------|
| Flash dismiss | Double-click dismiss | Y | Alpine `x-transition` handles |
| Flash dismiss | JS error mid-dismiss | N ← GAP | try-catch added (see Section 2) |
| Table sort | Rapid click headers | N ← GAP | Debounce sort handler — **DEFERRED** |
| Form submit | Enter in repo input | Y | Native form submit |
| Radar chart | Canvas resize | N ← GAP | ResizeObserver on chart — **ACCEPTED** (S effort) |
| Mobile nav | Hamburger toggle | Existing | Preserved |

**GAPS Addressed:** Radar chart resize handler added to verification plan.

#### Section 5: Code Quality Review
**Organization:** Tokens centralized in `app.blade.php`. Partials extracted to `resources/views/components/`. Follows Laravel Blade conventions.
**DRY:** Plan modifies 17 files with repeated token application. **ACCEPTED EXPANSION #1** extracts partials to eliminate duplication.
**Naming:** Token names follow CSS custom property convention (`--surface-primary`). Tailwind config uses camelCase. Clear mapping documented.
**Error Handling:** See Section 2.
**Over-engineering:** No new abstractions. Token system is explicit, not clever.
**Under-engineering:** Silent token typo risk noted (Section 2). Partial extraction mitigates.
**Cyclomatic Complexity:** No new logic branches in views. All changes declarative.

#### Section 6: Test Review
**Test Diagram:**
```
NEW UX FLOWS: None (visual changes only)
NEW DATA FLOWS: None
NEW CODEPATHS: Partial inclusion (4 new partials), token variable resolution
NEW BACKGROUND JOBS: None
NEW INTEGRATIONS: None
NEW ERROR/RESCUE PATHS: Flash dismiss try-catch, Chart.js error boundary, radar chart ResizeObserver
```

**Test Specs:**
- Unit: Partial rendering with token props (4 tests)
- Integration: Each view renders with tokens applied (17 tests)
- E2E: Visual regression on all 6 page groups — **DEFERRED** to TODOS.md
- Accessibility: axe-core audit on all views — **ACCEPTED** (added to verification plan)

**LLM/Prompt Changes:** None.

#### Section 7: Performance Review
**N+1 Queries:** N/A (view-only). **Memory:** Token objects in Blade — negligible. **Indexes:** N/A. **Caching:** Blade views cached by Laravel view cache. **Background Jobs:** None. **Slow Paths:** Tailwind CDN (340KB) blocks First Paint — **KNOWN, EXPLICITLY DEFERRED** (plan states CDN preserved). **Connection Pool:** N/A.

**Performance Note:** The plan acknowledges the CDN Tailwind performance cost but explicitly defers Vite migration. This is a documented trade-off.

#### Section 8: Observability & Debuggability
**Logging:** No new codepaths. **Metrics:** No new features. **Tracing:** N/A. **Alerting:** N/A. **Dashboards:** N/A. **Debuggability:** Token values visible in browser DevTools (CSS vars). **Admin Tooling:** N/A. **Runbooks:** N/A.

#### Section 9: Deployment & Rollout
**Migrations:** None. **Feature Flags:** Not needed (visual-only, backward compatible). **Rollout Order:** Deploy views → verify. **Rollback:** `git revert` on modified files. **Deploy Risk:** Low. **Environment Parity:** Verified in staging. **Smoke Tests:** Manual verification plan covers.

#### Section 10: Long-Term Trajectory
**Technical Debt:** Token duplication across 17 views (mitigated by partial extraction). CDN Tailwind remains (deferred).
**Path Dependency:** Token system enables future design system package extraction.
**Knowledge Concentration:** Token mapping documented in plan. Partial patterns follow Laravel conventions.
**Reversibility:** 5/5 (git revert, no DB/migrations).
**Ecosystem Fit:** Aligns with Laravel Blade + Tailwind patterns.
**1-Year Question:** A new engineer sees centralized tokens in `app.blade.php` and partials in `components/` — obvious and maintainable.

#### Section 11: Design & UX Review
**Information Architecture:** Header → Main content → Footer. Consistent across all views.
**Interaction State Coverage:**

| Feature | Loading | Empty | Error | Success | Partial |
|---------|---------|-------|-------|---------|---------|
| Dashboard stats | — | ✅ (hint) | — | ✅ | — |
| Pipeline bars | ✅ (animated) | ✅ | — | ✅ | — |
| Candidate table | — | ✅ (illustration) | — | ✅ | ✅ (pagination) |
| Candidate show | ✅ (analyzing) | ✅ | ✅ | ✅ | — |
| Comparison table | — | — | — | ✅ | — |
| Batch progress | ✅ (bar) | — | ✅ | ✅ | ✅ |
| Apply form | ✅ (submitting) | — | ✅ | ✅ | — |

**User Journey:** HR lands on dashboard → scans pipeline → drills into candidate → evaluates evidence → shortlists/rejects. Coherent.
**AI Slop Risk:** Plan specifies concrete tokens and patterns, not generic "modern UI."
**DESIGN.md Alignment:** No DESIGN.md exists. Tokens defined in plan become de facto design system.
**Responsive:** Mobile/tablet/desktop breakpoints in verification plan.
**Accessibility:** WCAG AA contrast verified for token colors; focus-visible on all interactive; ARIA on accordions/chat bubbles.

**ASCII User Flow:**
```
[Dashboard] ──▶ [Candidate Index] ──▶ [Candidate Show] ──▶ [Shortlist/Reject]
     │                │                    │
     │                ▼                    ▼
     │           [Filters]             [Dimensions]
     │                │                    │
     ▼                ▼                    ▼
[Comparisons] ◀── [Batch View] ◀── [Public Apply]
```

**Recommendation:** Consider `/plan-design-review` for deep design audit before implementation.

---

### CEO Dual Voices Consensus Table

| Dimension | opencode (in-host) | Codex | Consensus |
|-----------|-------------------|-------|-----------|
| 1. Premises valid? | ✅ | N/A (model issue) | N/A |
| 2. Right problem? | ✅ | N/A | N/A |
| 3. Scope calibration? | ✅ (with expansions) | N/A | N/A |
| 4. Alternatives explored? | ✅ | N/A | N/A |
| 5. Competitive risks? | ✅ | N/A | N/A |
| 6. 6-month trajectory? | ✅ | N/A | N/A |

Codex: unavailable (ChatGPT account model compatibility). opencode (in-host): completed.

---

### Cross-Phase Themes
None yet (Phase 1 only).

---

### Deferred to TODOS.md
- Visual regression test suite (Playwright/Percy)
- Dark mode support
- Vite-built Tailwind migration (separate epic)
- Build-time token validation script

---

### Implementation Tasks (aggregated)
- [ ] **CEO-001 (P1, human: ~2 days / CC: ~30 min) — Extract Blade Partials** — Create `resources/views/components/badge.blade.php`, `card.blade.php`, `form-input.blade.php`, `table.blade.php`; refactor 17 views to use partials
- [ ] **CEO-002 (P1, human: ~1 hour / CC: ~15 min) — Design Token JSON Export** — Generate `public/design-tokens.json` in Style Dictionary format from `app.blade.php` tokens
- [ ] **CEO-003 (P1, human: ~2 hours / CC: ~20 min) — Accessibility Requirements** — Add WCAG AA contrast verification, focus-visible rings, ARIA attributes to verification plan and affected views
- [ ] **CEO-004 (P2, human: ~30 min / CC: ~10 min) — Flash Dismiss Error Handling** — Wrap Alpine.js dismiss in try-catch in `app.blade.php`
- [ ] **CEO-005 (P2, human: ~30 min / CC: ~10 min) — Radar Chart ResizeObserver** — Add resize handler to radar chart partial
- [ ] **CEO-006 (P2, human: ~1 hour / CC: ~15 min) — Token Naming Convention Doc** — Document kebab-case (CSS) ↔ camelCase (Tailwind) mapping in plan

<!-- autoplan-accepted:ceo -->
- Extract 4 Blade partials (badge, card, form-input, table) to eliminate token duplication across 17 views
- Export design tokens as JSON (Style Dictionary format) for Figma designer handoff
- Add accessibility requirements (WCAG AA contrast, focus-visible, ARIA) to verification plan
- Add flash dismiss try-catch error handling in app.blade.php
- Add radar chart ResizeObserver for canvas resize resilience
- Document token naming convention mapping (CSS kebab-case ↔ Tailwind camelCase)
- Defer visual regression tests, dark mode, Vite migration to TODOS.md
<!-- /autoplan-accepted:ceo -->

<!-- autoplan-accepted:design -->
- Add information hierarchy specs per screen (Dashboard, Candidate Show, Comparison)
- Add mobile nav hamburger to drawer specification
- Complete interaction state table (10 missing states across 7 features)
- Add user journey storyboard with 7-step emotional arc
- Specify one authored motion moment per primary screen
- Add token-component mapping table (badge, card, form-input, table)
- Add responsive specs per breakpoint + a11y checklist (touch targets, ARIA landmarks, visited links, heading hierarchy)
- Defer empty state illustrations, mobile nav animation, accordion easing, confetti implementation, token JSON validation to TODOS.md
<!-- /autoplan-accepted:design -->

<!-- autoplan-accepted:dx -->
- Add component index page (`/components`) with live examples of all partials
- Add design token reference page (`/design-tokens`) displaying all tokens
- Add view-by-view migration checklist to plan
- Add Blade partial guards, CSS fallbacks, Alpine global error handler
- Add component README per partial + token usage decision tree
- Add gradual migration strategy (legacy mode + per-view opt-in)
- Add token versioning to design-tokens.json + deprecation policy
- Add component preview page with all interaction states
- Defer Storybook, visual regression CI, Vite HMR, Alpine pattern library, codemod to TODOS.md
<!-- /autoplan-accepted:dx -->

<!-- autoplan-accepted:eng -->
- Add ASCII architecture diagram to plan
- Add unit/integration test specifications (4 partials + 2 controllers + 17 views)
- Document all 6 error paths with user-visible messages
- Add explicit rollback procedure (< 5 min via git revert)
- Sync Eng-specific TODOS to TODOS.md (ENG-001 through ENG-005)
- Defer visual regression CI, codemod, Vite migration, Storybook, Alpine patterns to TODOS.md
<!-- /autoplan-accepted:eng -->
