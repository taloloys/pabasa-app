# Pabasa — Multi-Church Reading-Intention System

## Project Overview

A multi-tenant church reading-intention management platform. Churches publish Mass schedules, parishioners (registered or guest) submit prayer intentions with GCash screenshot payment, Central Admin verifies payments, lectors manage reading lists during Mass, and settlements/payouts are tracked with full audit trails.

**Canonical specification:** [`multi_church_reading_system_development_specification.md`](multi_church_reading_system_development_specification.md)
— this file is the **source of truth** for all business rules, data models, state transitions, authorization, and workflows. When in doubt, read the spec.

---

## Stack

| Layer | Technology | Version |
|---|---|---|
| Backend | Laravel | 13.x |
| PHP | PHP | 8.5 |
| Frontend | React + Inertia.js | React 19, Inertia 2.x |
| CSS | Tailwind CSS | 4.x |
| Build | Vite | 8.x |
| Database | MySQL | 8.4 |
| Cache/Queue | Redis | 7.x |
| Deployment | Laravel Cloud | — |
| Testing | PHPUnit | 12.x |
| Formatting | Pint | — |

---

## Development Environment

### Docker (recommended)

```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate
```

### Local

```bash
composer install
npm install
php artisan migrate
composer dev
```

### Environment

- Copy `.env.example` to `.env` and set `APP_KEY`
- **Database must be MySQL 8.4** — do NOT use SQLite for development or testing
- Docker Compose exposes MySQL on `127.0.0.1:3307`, Redis on `127.0.0.1:6379`

---

## Commands

```bash
# Run all tests (MUST use MySQL, never SQLite)
php artisan test

# Run specific suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Code formatting
./vendor/bin/pint
./vendor/bin/pint --test   # check only

# Dev server
composer dev

# Fresh migration
php artisan migrate:fresh

# Queue worker
php artisan queue:work

# Scheduler
php artisan schedule:run
```

---

## Architecture

### Directory Structure

```
app/
  Actions/                    # Single-responsibility domain action classes
    Churches/
    Masses/
    Orders/
    Payments/
    ReadingLists/
    Requests/
    Settlements/
  Enums/                      # Backed PHP enums for all fixed states
  Events/                     # Domain events dispatched AFTER db commit
  Http/
    Controllers/
      Admin/                  # Central Admin controllers
      Church/                 # Church Admin controllers
      Lector/                 # Lector controllers
      Public/                 # Public/unauthenticated pages
      User/                   # Registered user controllers
      Guest/                  # Guest access controllers
    Requests/                 # Form request validation
  Jobs/                       # Queued background jobs
  Mail/
  Models/                     # Eloquent models
  Notifications/
  Policies/                   # Laravel Gate/Policy per resource
  Services/                   # Cross-cutting domain services
    Audit/
    Booking/
    Payments/
    Pricing/
    ReadingLists/
    Settlements/
    GuestAccess/
  Support/                    # Helpers, value objects
database/
  migrations/                 # Ordered by FK dependency (see spec §3.2)
  seeders/
resources/js/
  Pages/                      # Inertia page components
  Components/                 # Shared React components
  Layouts/                    # Layout components
  hooks/                      # Custom React hooks
routes/
  web.php
  console.php
tests/
  Feature/
  Unit/
```

### Key Patterns

1. **Domain Actions** — all business logic lives in `app/Actions/`. Controllers are thin: validate, call action, return Inertia response.
2. **Backed PHP Enums** — every status/state column uses a backed string enum in `app/Enums/`. Store flexible church-defined categories as database records, NOT enums.
3. **Policies** — Laravel Gates/Policies enforce all authorization. Never authorize from client-supplied `church_id`, role name, or hidden React controls alone.
4. **Domain Events** — dispatched AFTER successful database commit, never inside transactions.
5. **Inertia Pages** — standard browser workflows use Inertia. These are NOT a public REST API.

---

## Roles and Authorization

### Role Model

| Role | Scope | Source |
|---|---|---|
| Central Admin | Global | Global role/permission on user |
| Church Admin | Per-church | Active `church_memberships` record, role=`church_admin` |
| Lector | Per-church + per-Mass | Active membership role=`lector` + active `mass_assignments` for specific Mass |
| Registered User | Own resources | Authenticated, owns the order |
| Guest | Single order | Valid, unexpired, non-revoked guest credential + email verification |

### Authorization Rules

- **Church Admin cannot verify/reject payments** — this is Central Admin only
- **Lector cannot access unassigned Masses** — must have active assignment
- A person may have memberships at multiple churches; each request resolves the relevant membership explicitly
- Derive church scope from authenticated membership, NOT from request input
- Return 404 (not 403) for cross-tenant resource access to avoid confirming existence

### Required Policies (13)

`ChurchPolicy`, `MembershipPolicy`, `MassSchedulePolicy`, `OrderPolicy`, `PaymentPolicy`, `RefundPolicy`, `ReadingListPolicy`, `SupportTicketPolicy`, `SettlementPolicy`, `PayoutPolicy`, `PrivacyRequestPolicy`, `AuditLogPolicy`, `SecurityIncidentPolicy`

---

## Database Conventions

### General

- **Primary keys:** unsigned bigint auto-increment for internal; ULID/UUID for public-facing references (`public_id`)
- **Money:** `DECIMAL(12,2)` — **NEVER use `float`** for monetary values. Use `bcmath` in PHP.
- **Timestamps:** store in UTC; render using the church's configured timezone
- **JSON columns:** supplementary metadata only, never for columns needed in filters, authorization, or financial reports
- **Foreign keys:** restrictive deletion for financial/audit; cascade only for disposable child data
- **Status columns:** use explicit status enums, never infer from nullable timestamps
- **Indexes:** every FK, every common filter/sort field
- **Soft deletes:** add `deleted_at` only where soft deletion is appropriate; financial records are NEVER hard-deleted

### Church Isolation

- Every church-owned table carries `church_id`
- Validate parent and child `church_id` match in domain actions
- Use composite FK `(parent_id, church_id)` where MySQL tooling allows
- Do NOT rely on global Eloquent scopes as a security boundary

### Migration Order

Migrations must be created in FK dependency order as specified in spec §3.2 (36 tables from `users` through `security_incidents`).

---

## State Machines

All state transitions are documented in spec §5. Key rules:

- **Order:** `awaiting_payment` → `payment_under_review` → `confirmed` → `completed` (or `cancelled`/`action_required`)
- **Payment:** `pending_payment` → `pending_verification` → `verified` (only by Central Admin after explicit human verification)
- **Intention:** follows order payment state; corrections use a separate workflow
- **Reading List:** `preparing` → `finalized` → `completed` (reopen requires audit trail)
- **Settlement:** `draft` → `calculated` → `under_review` → `ready_for_payout` → `paid`
- **Refund/Payout:** completion requires actual transfer reference/date and staff confirmation — NEVER auto-complete

### Critical State Rules

1. Do NOT automatically mark an order `confirmed` because a screenshot was uploaded
2. Do NOT mark an order `completed` solely because Mass time passed
3. Do NOT auto-complete refunds or payouts from a timer
4. Do NOT count one GCash transaction/reference twice
5. All payment decision actions must `lockForUpdate()` in a transaction
6. `verified` payment remains historically verified even if order is later cancelled

---

## Financial Rules

### Non-Negotiable

1. **No invented pricing** — do NOT seed default prices, fees, or revenue-share percentages
2. **No assumed business rules** — if pricing config is not approved, return `configuration_required`
3. **Append-only financial records** — never silently overwrite or delete a financial decision
4. **Decimal-safe arithmetic** — `DECIMAL(12,2)` in DB, `bcmath` in PHP, never `float`
5. **Settlement includes only verified payments** — pending/rejected/review-required excluded
6. **No duplicate payment inclusion** — enforce with DB constraints + transactional checks
7. **Refund/payout completed only with actual transfer evidence** — reference, date, confirming staff

### Pricing Configuration

- Versioned, append-only `pricing_configurations` table
- Draft → Approved lifecycle; approved configs are immutable
- Orders snapshot the applicable pricing version at creation time
- Settlements snapshot formula version + source payment IDs

---

## Security Requirements

1. CSRF protection on all state-changing routes
2. Rate limiting: auth, guest recovery, evidence submission, sensitive staff actions
3. Payment evidence stored on private disk with randomized keys — NEVER expose storage URLs
4. Guest tokens: high-entropy, hashed storage only, expiring, revocable — NEVER log raw tokens
5. Server-side MIME validation for file uploads
6. Audit records for all financial/staff/schedule/list decisions
7. Session invalidation when staff access is revoked
8. Secrets in deployment config, not database or source control
9. No passwords, raw tokens, or unnecessary PII in logs

---

## Testing Rules

1. **Always use MySQL** — spec explicitly forbids SQLite-only testing (§12)
2. Use factories for: Central Admin, Church Admin, Lector, registered user, two churches, assigned/unassigned Masses, all entity states, guest credentials
3. For every state-changing action test:
   - Permitted actor + valid state succeeds
   - Forbidden actor receives 403/404
   - Invalid transition is rejected
   - Stale/concurrent request cannot overwrite newer state
   - Audit event is created
   - Notification queued only after commit
   - Failure rolls back related DB changes
4. Cross-church IDOR tests for every major resource
5. Concurrency tests for cutoff, verification, duplicate references

---

## Localization

- Supported locales: `en`, `fil`, `ceb`
- UI strings are translatable; user-submitted intention text is preserved verbatim
- Locale switching must NEVER translate/mutate saved names or intention wording
- Interface localization is separate from data storage

---

## Implementation Phases

| Phase | Focus | Spec Section |
|---|---|---|
| 0 | Stack setup (Inertia, React, Breeze, MySQL) | Pre-requisite |
| 1 | Foundation & Security — auth, churches, memberships, policies, audit | §11 Phase 1 |
| 2 | Church Directory & Mass Scheduling — verification, schedules, assignments | §11 Phase 2 |
| 3 | Orders, Payments, Guest Tracking — order creation, GCash evidence, verification | §11 Phase 3 |
| 4 | Reading Operations — lector lists, finalization, corrections, PDF | §11 Phase 4 |
| 5 | Finance, Support, Reports — settlements, payouts, exports, support tickets | §11 Phase 5 |
| 6 | Validation & Pilot — regression, security review, deployment, pilot | §11 Phase 6 |

---

## Pending Business Decisions (DO NOT hard-code)

These items are explicitly marked in the spec as requiring business/legal approval:

- Intention prices, service fees, tax treatment
- Revenue-sharing percentages, rounding rules, church payout formula
- Payment submission deadline and late-payment treatment
- Refund eligibility, approval thresholds, deadlines
- Settlement week boundaries, timezone policy, review sign-off
- Legal retention periods, privacy wording
- GCash integration method (manual screenshot only for now)

---

## Definition of Done

A feature is NOT done merely because its screen exists. It is complete when:

1. Migrations, constraints, and indexes are implemented
2. Server-side policies and tenant isolation are tested
3. Valid and invalid state transitions are covered
4. Audit history is written for sensitive actions
5. Notifications dispatched safely after commit
6. File access is private and authorized
7. Loading, empty, error, and permission-denied states handled in React
8. Relevant en/fil/ceb UI strings available
9. User-entered intention content preserved verbatim
10. Tests pass on MySQL (not SQLite)
11. Operational documentation updated
12. Undecided business rules are explicitly blocked/configurable, not assumed
