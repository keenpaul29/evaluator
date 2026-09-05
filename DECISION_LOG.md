# ColoredCow Technical Candidate Evaluator — Architectural & Decision Log

## 1. Executive Summary & Boundaries

This document records architectural choices, AI evaluation methodology, confidence scoring thresholds, and Human-in-the-Loop (HITL) verification boundaries established during the development of **ColoredCow Technical Candidate Evaluator**.

---

## 2. Decision Matrix & Boundaries

| Category | Trusted to AI | Human Review / Operator Override Required |
| --- | --- | --- |
| **Ingestion Parsing** | Extracted JSON Schema (`title`, `client_name`, `amount`, `category`, `due_date`) | Extracted items with confidence score `< 85%` or flagged ambiguities. |
| **Category Tagging** | Autonomous classification (`Logistics`, `Billing`, `Appointment`, `Inventory`) | Overriding mistagged categories during HITL review. |
| **Financial Actions** | Extraction of dollar amounts from raw text/receipts | Final approval before database locking or external export. |

---

## 3. Evaluation & Edge Cases Observed

### Case 1: High-Confidence Structured Invoice (Auto-Approved)
- **Raw Input:** `"From: Marcus Vance <m.vance@vancelogistics.com> Subject: Freight invoice #8812 Total paid $3400.00 USD."`
- **Result:** Confidence score **92%**. Automatically ingested and auto-approved to the operational database.

### Case 2: Ambiguous Field Missing (HITL Flagged)
- **Raw Input:** `"Note from field agent: Customer mentioned needing appointment next week for annual audit. Contact name is Alex Taylor. No total dollar amount mentioned yet."`
- **Result:** Confidence score **57%**. Flagged reasons: `"Financial amount or dollar value missing in text"`. Automatically routed to `ReviewQueue` for one-click human verification.

---

## 4. Verification & Testing Log
- **Automated Seeding Verification:** Verified `php artisan db:seed` runs cleanly and populates auto-approved and HITL review queue entries.
- **CSV Export Verification:** Verified `/export/csv` generates structured spreadsheets with column headers and total amounts.
