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
