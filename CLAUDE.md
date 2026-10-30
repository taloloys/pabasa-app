# Pabasa — Multi-Church Reading-Intention System

## Project Overview

Multi-tenant church reading-intention management platform. Laravel 13 + Inertia.js + React + MySQL 8.4. Deployed to Laravel Cloud.

**Canonical specification:** `docs/multi_church_reading_system_development_specification.md` — the source of truth for all business rules, data models, state transitions, authorization, and workflows.  
**Development phases tracker:** `docs/DEV_PHASE.md`  
**Performance optimization roadmap:** `docs/performance/OPTIMIZATION_ROADMAP.md`

---

## Performance Optimization Rules

Read `docs/performance/OPTIMIZATION_ROADMAP.md` before doing any optimization work.

You are responsible for helping maintain and execute this optimization roadmap.

Follow these rules:

1. Identify the current phase and its status.
2. Review previous findings and implementation reports before repeating investigations.
3. Inspect the actual project files and current Docker/database environment rather than assuming previous reports are accurate.
4. Work only on the current approved phase.
5. Do not make unrelated changes.
6. Do not perform destructive database operations or modify development data without my explicit approval.
7. Do not mark a phase complete without meeting its acceptance criteria.
8. Record all modified files, commands, tests, measurements, and remaining issues.
9. Update the roadmap after every approved optimization session.
10. Preserve historical benchmark results.

Before implementing changes, provide:

- Current phase and status.
- Findings from the existing implementation.
- Problems identified.
- Proposed changes.
- Files expected to be modified.
- Risks and regression possibilities.
- Verification procedure.

Wait for approval before making implementation changes.

For performance work, always establish a baseline and compare results using the same environment and measurement methodology.

Do not recommend a complete React-to-Blade migration unless actual measurements demonstrate that the current rendering architecture is a significant bottleneck and the proposed alternative provides a meaningful performance and maintenance advantage.

Start by reviewing the roadmap and reporting the current state of Phase 0. Do not execute database modifications.

---

## Stack

- **Backend:** Laravel 13.x (PHP 8.5)
- **Frontend:** React 19 + Inertia.js 2.x + Tailwind CSS 4
- **Database:** MySQL 8.4 (NEVER use SQLite for dev or testing)
- **Cache/Queue:** Redis 7
- **Build:** Vite 8
- **Testing:** PHPUnit 12
- **Formatting:** Pint
- **Deployment:** Laravel Cloud

---

## Commands

```bash
php artisan test                    # Run all tests (MySQL only)
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
./vendor/bin/pint --test            # Check formatting
./vendor/bin/pint                   # Fix formatting
composer dev                        # Dev server
php artisan migrate                 # Run migrations
php artisan queue:work              # Queue worker
```

---

## Architecture

### Pattern: Domain Actions

- `app/Actions/{Domain}/` — single-responsibility action classes with all business logic
- `app/Http/Controllers/{Role}/` — thin controllers: validate → call action → return Inertia response
- `app/Enums/` — backed PHP string enums for all fixed states
- `app/Policies/` — Laravel Gates/Policies enforce authorization
- `app/Events/` — domain events dispatched AFTER successful db commit
- `app/Services/{Domain}/` — cross-cutting services (Audit, Pricing, Settlements, etc.)

### Controller Organization

Controllers are grouped by role: `Admin/`, `Church/`, `Lector/`, `Public/`, `User/`, `Guest/`

---

## Roles

| Role | Scope | Authorization Source |
|---|---|---|
| Central Admin | Global | Global permission on user |
| Church Admin | Per-church | Active `church_memberships` (role=church_admin) |
| Lector | Per-church, per-Mass | Active membership + active `mass_assignments` |
| Registered User | Own resources | Auth user owns the order |
| Guest | Single order | Valid/unexpired/unrevoked credential + email verification |

### Critical Rules

- Church Admin CANNOT verify/reject payments — Central Admin only
- Lector CANNOT access unassigned Masses
- Derive church scope from membership, NEVER from request input
- Return 404 (not 403) for cross-tenant access

---

## Database

- **Money:** `DECIMAL(12,2)` always. NEVER `float`. Use `bcmath` in PHP.
- **Timestamps:** UTC storage; render using church timezone
- **PKs:** auto-increment bigint internally; ULID/UUID for public-facing `public_id`
- **FKs:** restrictive deletion for financial/audit records
- **Church isolation:** every church-owned table has `church_id`; validate parent/child match in actions
- **Status columns:** explicit enum columns, never inferred from nullable timestamps
- Migration order follows spec §3.2 (36 tables, FK dependency order)

---

## State Machines

All transitions documented in spec §5. Critical rules:

1. NEVER auto-confirm order because screenshot uploaded
2. NEVER auto-complete order because Mass time passed
3. NEVER auto-complete refund/payout from a timer
4. NEVER count one GCash reference twice
5. All payment decisions: `lockForUpdate()` in transaction
6. Verified payment stays historically verified even if order cancelled

---

## Financial Rules

1. NO invented pricing — no seeded defaults for prices, fees, or revenue shares
2. If pricing not approved → return `configuration_required`, block settlement
3. Append-only financial records — never overwrite/delete financial decisions
4. Settlement includes ONLY verified payments
5. Refund/payout completed ONLY with actual transfer evidence

---

## Security

- CSRF on all state-changing routes
- Rate limiting on auth, guest recovery, evidence submission
- Payment evidence: private disk, randomized keys, authorized streaming only
- Guest tokens: hashed only, never logged raw
- Server-side MIME validation for uploads
- Audit all financial/staff/schedule decisions
- Session invalidation on access revocation
- No secrets in DB or source control

---

## Testing

- **ALWAYS MySQL** — spec forbids SQLite-only testing
- Test every state-changing action for: permitted actor succeeds, forbidden actor gets 403/404, invalid transition rejected, concurrent request safety, audit event created, notification after commit, failure rollback
- Cross-church IDOR tests for every resource
- Concurrency tests for cutoff, verification, duplicate refs

---

## Localization

- Locales: `en`, `fil`, `ceb`
- UI strings translatable; user-submitted intention text preserved verbatim
- Locale switching NEVER mutates saved data

---

## Implementation Phases

1. **Phase 0:** Stack setup — Inertia, React, Breeze, MySQL
2. **Phase 1:** Foundation — auth, churches, memberships, policies, audit
3. **Phase 2:** Directory — church verification, Mass schedules, assignments
4. **Phase 3:** Orders — creation, GCash evidence, payment verification, guest access
5. **Phase 4:** Reading — lector lists, finalization, corrections, PDF
6. **Phase 5:** Finance — settlements, payouts, reports, support
7. **Phase 6:** Validation — regression, security review, deployment, pilot

---

## Pending Business Decisions (DO NOT hard-code)

- Pricing, fees, tax, revenue-sharing percentages
- Payment deadlines, late-payment treatment
- Refund eligibility, thresholds, deadlines
- Settlement timing, review sign-off
- Legal retention periods
- GCash integration method (manual screenshot only for now)

---

## Definition of Done

A feature is complete when: migrations/constraints/indexes implemented, policies tested, state transitions covered, audit written, notifications after commit, file access private, React states handled (loading/empty/error/denied), en/fil/ceb strings available, intention text preserved, tests pass on MySQL, undecided business rules blocked (not assumed).
