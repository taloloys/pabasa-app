# Pabasa — Development Phase Tracker

> **Project:** Multi-Church Reading-Intention System
> **Spec:** [`multi_church_reading_system_development_specification.md`](multi_church_reading_system_development_specification.md)
> **Last updated:** 2026-10-29

---

## Implementation Phases

```mermaid
flowchart TD
    P0["Phase 0: Stack Setup"]
    P1["Phase 1: Foundation & Security"]
    P2["Phase 2: Directory & Scheduling"]
    P3["Phase 3: Orders & Payments"]
    P4["Phase 4: Reading Operations"]
    P5["Phase 5: Finance & Support"]
    P6["Phase 6: Validation & Pilot"]

    P0 --> P1 --> P2 --> P3 --> P4 --> P5 --> P6
```

---

### Phase 0 — Stack Setup

**Status:** ✅ Completed

> Pre-requisite before any feature work. Not in the spec but required because the project is a fresh Laravel skeleton.

**Deliverables:**

- [x] Install Inertia.js (server adapter + client)
- [x] Install React 19 + @inertiajs/react
- [x] Install Laravel Breeze with Inertia/React variant (auth scaffolding)
- [x] Switch default DB to MySQL 8.4 in `.env.example` and `config/database.php`
- [x] Install Ziggy for named route access in React
- [x] Configure 3 locales: `en`, `fil`, `ceb`
- [x] Set up directory structure per spec §2
- [x] Verify Docker Compose boots cleanly with MySQL 8.4 + Redis 7

**Acceptance:** Project runs with Inertia/React, auth works, MySQL is default.

---

### Phase 1 — Foundation & Security

**Status:** ⬜ Not started
**Spec section:** §11 Phase 1

**Deliverables:**

- [ ] Migrations: `users`, `churches`, `church_memberships`, `invitations`, `system_settings`, `audit_logs`
- [ ] Backed PHP enums: `UserStatus`, `ChurchStatus`, `MembershipRole`, `MembershipStatus`
- [ ] Policies: `ChurchPolicy`, `MembershipPolicy`, `AuditLogPolicy`
- [ ] Auth: registration, login/logout, email verification, session management
- [ ] Invitation acceptance, revocation, staff deactivation + session invalidation
- [ ] Audit service (`app/Services/Audit/`)
- [ ] Baseline rate limits (login, invite acceptance, recovery)
- [ ] Factories: Central Admin, Church Admin, Lector, registered user, two churches

**Test cases (16):**

- [ ] 1. Unauthenticated visitor cannot access staff dashboards
- [ ] 2. Registered user can register/login/logout; logout invalidates session
- [ ] 3. Unverified user cannot access secure order tracking
- [ ] 4. Expired invitation cannot be accepted
- [ ] 5. Revoked invitation cannot be accepted
- [ ] 6. Invitation token is stored hashed and is one-time use
- [ ] 7. Church Admin can access own church membership list but not another church's
- [ ] 8. Lector membership does not grant Church Admin permissions
- [ ] 9. Revoked/deactivated membership immediately blocks subsequent requests
- [ ] 10. Central Admin privilege is checked server-side
- [ ] 11. Sensitive role/membership changes create audit entries with actor, target, reason, timestamp
- [ ] 12. Passwords, raw invitation tokens, and secrets never appear in audit logs
- [ ] 13. Rate limiting applies to login, invite acceptance, and recovery endpoints
- [ ] 14. Locale switching does not mutate stored user-entered text
- [ ] 15. Foreign keys and indexes migrate cleanly on MySQL 8.4
- [ ] 16. Backup restoration procedure can restore the Phase 1 database into an isolated environment

**Release gate:** Automated authorization tests pass; no cross-church access path remains untested for created resources.

---

### Phase 2 — Church Directory & Mass Scheduling

**Status:** ⬜ Not started
**Spec section:** §11 Phase 2

**Deliverables:**

- [ ] Migrations: `church_branding`, `church_categories`, `mass_series`, `mass_schedules`, `mass_assignments`
- [ ] Backed PHP enums: `BookingStatus`, `MassStatus`
- [ ] Policy: `MassSchedulePolicy`
- [ ] Church verification / publication / suspension workflow
- [ ] Public directory (published churches only)
- [ ] Categories management (church-defined, deactivate not delete)
- [ ] Mass schedules: recurring series/occurrences, booking windows, cutoffs
- [ ] Lector assignments
- [ ] Conflict detection (shared resources/staff, not blanket overlap rejection)

**Test cases (18):**

- [ ] 1. Draft/unverified/suspended/deactivated churches do not accept public submissions
- [ ] 2. Only published churches appear in the public directory
- [ ] 3. Central Admin can verify and publish a church; action is audited
- [ ] 4. Church Admin cannot edit another church's profile or branding
- [ ] 5. Church Admin can create/edit own church Masses
- [ ] 6. Editing one occurrence does not silently alter other recurring occurrences
- [ ] 7. Invalid or past booking windows are rejected
- [ ] 8. Closed Mass remains visible but cannot be selected for submission
- [ ] 9. New order creation is rejected after cutoff
- [ ] 10. Assigned lector can close submissions early
- [ ] 11. Lector cannot extend the cutoff beyond authorized limits
- [ ] 12. Conflict detection considers configured shared resources/staff and allows unrelated chapel overlaps
- [ ] 13. Lector cannot view unassigned Mass details by changing the URL ID
- [ ] 14. Suspending a church blocks new orders but preserves existing orders/payments
- [ ] 15. Mass cancellation creates a case and preserves original schedule data
- [ ] 16. Schedule updates and staff assignments create audit events
- [ ] 17. Church timezone is used consistently in displayed cutoff and schedule times
- [ ] 18. Concurrent requests around cutoff cannot both bypass the cutoff rule

**Release gate:** Booking rules pass boundary-time and concurrent-request tests.

---

### Phase 3 — Orders, Payments, Guest Tracking & Requests

**Status:** ⬜ Not started
**Spec section:** §11 Phase 3

**Deliverables:**

- [ ] Migrations: `pricing_configurations`, `orders`, `intentions`, `payments`, `payment_evidence`, `payment_exceptions`, `refunds`, `correction_requests`, `cancellation_requests`, `rescheduling_requests`, `guest_order_access`, `order_account_links`
- [ ] Backed PHP enums: `OrderStatus`, `IntentionStatus`, `PaymentStatus`, `RefundStatus`, `CancellationRequestStatus`, `ReschedulingRequestStatus`, `CorrectionRequestStatus`
- [ ] Policies: `OrderPolicy`, `PaymentPolicy`, `RefundPolicy`
- [ ] Domain action: `CreateOrder` (spec §8.1)
- [ ] Domain action: `SubmitPaymentEvidence` (spec §8.2)
- [ ] Domain action: `VerifyPayment` — Central Admin only (spec §8.3)
- [ ] Pricing configuration guardrails and order snapshots
- [ ] Protected evidence uploads (private disk, randomized keys)
- [ ] Guest access: secure tracking, email verification, recovery, credential revocation
- [ ] Correction, cancellation, rescheduling request records

**Test cases (30):**

- [ ] 1. Registered user creates an order with multiple intentions for one Mass
- [ ] 2. Guest can submit without an account and receives a secure tracking flow
- [ ] 3. An order containing intentions for different churches or Masses is rejected
- [ ] 4. Inactive category is rejected for new submissions; historical label snapshot unchanged
- [ ] 5. Names and wording preserve exact submitted Unicode text
- [ ] 6. Duplicate-warning logic flags likely duplicates but does not block a legitimate repeat
- [ ] 7. No approved pricing config → `configuration_required`; no price invented
- [ ] 8. Order and all intention rows roll back if any child creation fails
- [ ] 9. Mass closing between page load and submit → server-side rejection
- [ ] 10. Payment evidence accepts only allowed, validated file types; rejects oversized/malicious
- [ ] 11. Evidence storage is private; cannot be fetched without authorization
- [ ] 12. Evidence submission moves payment to pending_verification but NEVER verified
- [ ] 13. Church Admin cannot verify/reject payment by direct POST
- [ ] 14. Lector cannot access payment evidence
- [ ] 15. Central Admin can verify only after explicit action with verified amount/reference
- [ ] 16. Duplicate reference creates a review flag; same transaction cannot be counted twice
- [ ] 17. Underpayment, overpayment, unclear evidence, unmatched ref, late payment → review/resolution flow
- [ ] 18. Rejection requires a reason and produces user-facing notice
- [ ] 19. Retried verification request does not duplicate reading-list entries
- [ ] 20. Guest tracking rejects order number alone
- [ ] 21. Guest tracking requires valid credential + verification + unexpired access
- [ ] 22. Recovery response does not disclose whether an email exists
- [ ] 23. Replacing a guest credential invalidates the old credential
- [ ] 24. Matching email alone cannot link a guest order to a registered account
- [ ] 25. Owner can edit eligible intentions before payment verification; blocked after
- [ ] 26. Cancellation request does not automatically cancel/refund the order
- [ ] 27. Rescheduling request does not automatically move an order
- [ ] 28. Every sensitive payment/order decision creates an audit record
- [ ] 29. Concurrent Central Admin verification → one consistent decision
- [ ] 30. Payment after cutoff held for review rather than silently discarded

**Release gate:** All payment exception tests pass; evidence is private; no payment can be verified by a non-authorized role.

---

### Phase 4 — Reading Operations

**Status:** ⬜ Not started
**Spec section:** §11 Phase 4

**Deliverables:**

- [ ] Migrations: `reading_lists`, `reading_list_entries`, `reading_list_versions`
- [ ] Backed PHP enums: `ReadingListStatus`, `ReadingEntryStatus`
- [ ] Policy: `ReadingListPolicy`
- [ ] Lector dashboard (assigned Masses only)
- [ ] Entry ordering, finalization (immutable version snapshot), reopening, completion
- [ ] Domain action: `ReorderReadingList` (spec §8.4)
- [ ] Domain action: `ApplyCorrection` (spec §8.5)
- [ ] Correction and issue workflows
- [ ] Protected PDF generation
- [ ] Read-only offline/PWA support

**Test cases (22):**

- [ ] 1. Reading list contains only eligible confirmed intentions
- [ ] 2. Unverified order never occupies a confirmed reading-list slot
- [ ] 3. Lector sees only assigned Masses and only authorized fields
- [ ] 4. Reordering persists the exact requested order
- [ ] 5. Duplicate, missing, or foreign entry IDs rejected during reorder
- [ ] 6. Reordering a finalized list is rejected
- [ ] 7. Finalization creates an immutable snapshot/version and audit event
- [ ] 8. Reopening requires permission and reason; it creates history
- [ ] 9. Re-finalization creates a new version without deleting the prior snapshot
- [ ] 10. Correction request does not change wording before approval
- [ ] 11. Approved correction preserves original wording and records reviewer/reason
- [ ] 12. Correction to a finalized list requires reopen/correct/re-finalize path
- [ ] 13. Completion records actor and timestamp
- [ ] 14. Completion correction requires a reason and preserves original event history
- [ ] 15. One entry can be completed independently of other entries
- [ ] 16. Whole-list completion blocked while required conditions unmet
- [ ] 17. Protected PDF denied to unassigned lector and unauthorized user
- [ ] 18. Cached list displays last sync time and stale/offline indicator
- [ ] 19. Offline mode disables reordering, completion, and schedule changes
- [ ] 20. Revoked access clears/invalidates cached private data
- [ ] 21. Scheduled finalization retry does not create duplicate versions or corrupt state
- [ ] 22. Issue reports expose user-safe status but not internal staff notes

**Release gate:** List finalization, reopening, correction, and offline stale-data warnings pass end-to-end tests.

---

### Phase 5 — Finance, Support, Reporting & Privacy

**Status:** ⬜ Not started
**Spec section:** §11 Phase 5

**Deliverables:**

- [ ] Migrations: `settlements`, `settlement_lines`, `payouts`, `support_tickets`, `support_ticket_events`, `notification_deliveries`, `privacy_requests`, `security_incidents`
- [ ] Backed PHP enums: `SettlementStatus`, `PayoutStatus`, `SupportTicketStatus`, `SupportTicketPriority`, `SupportTicketCategory`
- [ ] Policies: `SettlementPolicy`, `PayoutPolicy`, `SupportTicketPolicy`, `PrivacyRequestPolicy`, `SecurityIncidentPolicy`
- [ ] Settlement calculator service (spec §6.2): `PricingService` / `SettlementCalculator`
- [ ] Domain action: `RecordRefundTransfer` + `RecordPayoutTransfer` (spec §8.6)
- [ ] PDF/CSV/XLSX exports with formula injection mitigation
- [ ] Support ticket routing (payment→Central Admin, church→Church Admin, technical→tech support)
- [ ] Privacy request workflow
- [ ] Notification delivery monitoring

**Test cases (28):**

- [ ] 1. Settlement includes only verified payments
- [ ] 2. Pending, rejected, and review-required payments excluded
- [ ] 3. A payment cannot be included twice in active settlements
- [ ] 4. Missing approved revenue-sharing config → `configuration_required`
- [ ] 5. No fixed price, fee, or percentage is seeded or assumed
- [ ] 6. Calculation uses versioned approved config and stores reproducible snapshot
- [ ] 7. Changing a config does not alter past order or settlement snapshots
- [ ] 8. Settlement approval blocked until review prerequisites met
- [ ] 9. Church Admin sees only their church settlement summary
- [ ] 10. Lector cannot access financial reports
- [ ] 11. Creating a refund request does not mark it approved or completed
- [ ] 12. Refund approval does not mark money returned
- [ ] 13. Refund completion requires actual transfer details and authorized confirmation
- [ ] 14. Payout preparation does not mark payout completed
- [ ] 15. Payout completion requires actual transfer details and authorized confirmation
- [ ] 16. Support ticket routing: payment/refund → Central Admin
- [ ] 17. Church-specific tickets → relevant Church Admin
- [ ] 18. Technical tickets → designated technical support
- [ ] 19. Urgent/unclassified → Central Admin triage
- [ ] 20. Internal notes never appear in user-facing ticket views or exports
- [ ] 21. Notification failure does not reverse a payment or reading state
- [ ] 22. Notification retries do not create duplicate state transitions
- [ ] 23. Export is scoped to the requesting user's permissions
- [ ] 24. CSV/XLSX export mitigates formula injection
- [ ] 25. Private export files require authorization and expire per config
- [ ] 26. Privacy request cannot be fulfilled before identity verification
- [ ] 27. Retention/deletion job disabled until approved retention settings exist
- [ ] 28. Sensitive exports and financial actions are audited

**Release gate:** Settlement cannot be approved with unconfigured business rules; refund/payout completion requires actual transfer evidence.

---

### Phase 6 — System Validation, Deployment & Pilot

**Status:** ⬜ Not started
**Spec section:** §11 Phase 6

**Deliverables:**

- [ ] Full regression suite on production-compatible MySQL
- [ ] Security review and accessibility/usability checks
- [ ] Laravel Cloud deployment validation
- [ ] Queue/storage/email/backup checks
- [ ] Restore drill, monitoring, operational runbooks
- [ ] Pilot rollout with limited verified churches

**Test cases (18):**

- [ ] 1. Run full automated suite against production-compatible MySQL version
- [ ] 2. Cross-church IDOR tests: orders, intentions, schedules, assignments, evidence, PDFs, support, reports, settlements, exports
- [ ] 3. Simultaneous order submissions at cutoff
- [ ] 4. Concurrent payment verification and duplicate references
- [ ] 5. Payment upload interruption and safe retry behavior
- [ ] 6. Transaction rollback for order creation, verification, reorder, settlement, refund/payout
- [ ] 7. Queue retries, failed jobs, and notification recovery
- [ ] 8. Database backup restore and order/payment relationship reconciliation
- [ ] 9. Private storage objects remain private after deployment
- [ ] 10. Session invalidation after staff deactivation and password/security recovery
- [ ] 11. Rate limits and generic recovery responses
- [ ] 12. Keyboard navigation, focus, labels, validation errors, status announcements, contrast, mobile layouts
- [ ] 13. en/fil/ceb UI switching without translating saved intention content
- [ ] 14. PWA installability and offline stale-list handling
- [ ] 15. Logs do not contain passwords, raw guest tokens, or unnecessary private data
- [ ] 16. No real payment/refund/payout represented as completed without explicit human recording
- [ ] 17. Pilot with limited verified churches and staff
- [ ] 18. Documented support routing, payment reconciliation, Mass cancellation, settlement procedures confirmed

**Release gate:** Complete blueprint's minimum release gates, obtain legal/privacy review, confirm centralized GCash reconciliation and payout procedures, and obtain explicit sign-off for business rules that affect money.

---

## Risks & Edge Cases

| # | Risk | Severity | Mitigation | Status |
|---|---|---|---|---|
| 1 | **Church isolation bypass** — cross-tenant data leak via URL ID manipulation | 🔴 Critical | Composite FK where possible; all queries scoped from membership, not request input; IDOR test suite for every resource | ⬜ |
| 2 | **Double-counted payment** — same GCash transaction included in multiple settlements | 🔴 Critical | Unique constraints on `settlement_lines(settlement_id, payment_id)`; `lockForUpdate()` during verification; transactional checks | ⬜ |
| 3 | **Invented pricing** — system fabricates prices/fees/percentages | 🔴 Critical | No seeded values; `configuration_required` status blocks settlement calculation; documented in AGENTS.md | ⬜ |
| 4 | **Float money arithmetic** — rounding errors in financial calculations | 🔴 Critical | `DECIMAL(12,2)` in all migrations; `bcmath` for PHP math; linting/review enforcement | ⬜ |
| 5 | **Concurrent cutoff bypass** — two requests racing past Mass cutoff time | 🟠 High | Recheck cutoff inside transaction with row lock on `mass_schedules` | ⬜ |
| 6 | **Guest token leak** — raw tokens logged or exposed in URLs | 🟠 High | Hash-only storage; never log raw tokens; rate-limit recovery routes; minimize URL params | ⬜ |
| 7 | **Payment evidence exposure** — screenshots publicly accessible | 🟠 High | Private disk storage; randomized keys; authorized controller streaming only; never expose `storage_key` | ⬜ |
| 8 | **Finalized list corruption** — silent edits to finalized reading list | 🟠 High | Immutable version snapshots; reopen requires audit trail + reason; re-finalization creates new version | ⬜ |
| 9 | **SQLite in tests** — tests pass on SQLite but fail on MySQL production | 🟡 Medium | Spec explicitly forbids SQLite-only testing; enforce MySQL in CI/CD and local testing | ⬜ |
| 10 | **No Inertia/React installed** — stack gap blocks UI work | 🟡 Medium | Phase 0 prerequisite; must complete before any feature phase | ⬜ |
| 11 | **Concurrent verification race** — two admins verifying same payment simultaneously | 🟠 High | `lockForUpdate()` + state recheck inside transaction; second request fails gracefully | ⬜ |
| 12 | **Auto-complete anti-pattern** — timers or scheduled jobs completing refunds/payouts | 🔴 Critical | Spec explicitly forbids auto-completion; all completions require human confirmation + transfer evidence | ⬜ |

---

## Verification Strategy

| Layer | Tools / Approach | Responsible |
|---|---|---|
| **Unit tests** | PHPUnit — state machine transitions, pricing calculator, settlement calculator, duplicate detection, cutoff calculation, guest token verification | Dev |
| **Feature tests** | Laravel HTTP tests — routes, validation, policies, transactions, notifications, file authorization | Dev |
| **Integration tests** | MySQL-specific — row locking, composite constraints, queue behavior, private storage, concurrent requests | Dev |
| **Browser / E2E** | Playwright or similar — guest submission/tracking, staff payment review, reading-list finalization, correction and cancellation flows | QA |
| **Security** | IDOR sweep across all resources, CSRF validation, rate limit verification, evidence access control, token handling audit | Dev + QA |
| **Static analysis** | Pint (formatting), Larastan/PHPStan (type safety) | Dev |
| **Accessibility** | Keyboard navigation, focus management, labels, contrast, screen reader announcements | QA |
| **Localization** | en/fil/ceb switching; verify saved intention text is never mutated | QA |

### Test Commands

```bash
# Unit + Feature tests (MySQL required)
php artisan test

# Specific suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# With coverage
php artisan test --coverage

# Formatting check
./vendor/bin/pint --test
```

---

## Open Questions & Pending Decisions

> ⚠️ These items are explicitly marked in the spec as requiring business/legal approval. **DO NOT hard-code any of these.**

| # | Decision | Impact | Owner | Status |
|---|---|---|---|---|
| 1 | **Intention prices** — per-intention pricing, tiered pricing, or donation model? | Orders, settlements, reporting | Business | ⬜ Pending |
| 2 | **Service fees** — platform fee amount, who pays (absorbed vs pass-through)? | Payments, settlements | Business | ⬜ Pending |
| 3 | **Tax treatment** — VAT/tax implications of intention fees? | Financial records, exports | Business + Legal | ⬜ Pending |
| 4 | **Revenue-sharing** — church percentage, rounding rules, payout formula? | Settlements, payouts | Business | ⬜ Pending |
| 5 | **Payment deadline** — how long after order creation before payment expires? | Orders, scheduled jobs | Business | ⬜ Pending |
| 6 | **Late payment treatment** — accept for review, reject, or hold? | Payment verification workflow | Business | ⬜ Pending |
| 7 | **Refund eligibility** — which scenarios qualify, time windows, approval thresholds? | Refunds, cancellations | Business + Legal | ⬜ Pending |
| 8 | **Settlement week boundaries** — calendar week, Mon-Sun, cut-off day/time? | Settlements, scheduled jobs | Business | ⬜ Pending |
| 9 | **Settlement timezone policy** — church timezone or system timezone for period boundaries? | Settlements | Business | ⬜ Pending |
| 10 | **Legal retention periods** — how long to retain financial records, evidence, audit logs? | Retention/deletion jobs | Legal | ⬜ Pending |
| 11 | **Privacy notice wording** — specific language for consent/acknowledgement? | Registration, order submission | Legal | ⬜ Pending |
| 12 | **GCash integration** — manual screenshot only or future API integration? | Payment flow, evidence handling | Business + Tech | ⬜ Manual only (v1) |

### Technical Decisions (Team)

| # | Decision | Recommendation | Status |
|---|---|---|---|
| 1 | **Primary key strategy** | ULID for public-facing `public_id`; auto-increment `bigint` for internal `id` | ⬜ Decide |
| 2 | **Auth starter kit** | Laravel Breeze with Inertia/React variant | ⬜ Decide |
| 3 | **Test framework** | PHPUnit (already installed) or switch to Pest | ⬜ Decide |
| 4 | **State machine library** | PHP backed enums + domain action validation (no external library) | ⬜ Decide |
| 5 | **PWA offline scope** | Reading lists only? All pages? Needs UX definition. | ⬜ Decide |
| 6 | **Static analysis** | Add Larastan/PHPStan to dev dependencies? | ⬜ Decide |

---

## Progress Summary

| Phase | Deliverables | Tests | Status |
|---|---|---|---|
| Phase 0 — Stack Setup | 8 items | — | ✅ Completed |
| Phase 1 — Foundation & Security | 8 items | 16 cases | ⬜ Not started |
| Phase 2 — Directory & Scheduling | 9 items | 18 cases | ⬜ Not started |
| Phase 3 — Orders & Payments | 10 items | 30 cases | ⬜ Not started |
| Phase 4 — Reading Operations | 10 items | 22 cases | ⬜ Not started |
| Phase 5 — Finance & Support | 9 items | 28 cases | ⬜ Not started |
| Phase 6 — Validation & Pilot | 6 items | 18 cases | ⬜ Not started |
| **Total** | **60 items** | **132 cases** | — |

