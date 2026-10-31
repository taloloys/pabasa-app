# Multi-Church Reading-Intention System
## Development Specification
**Version:** 1.0  
**Baseline:** Multi-Church Reading-Intention System — System Requirements and Design Blueprint v1.0  
**Target stack:** Laravel, Inertia.js, React, MySQL, Laravel Cloud  
**Status:** Implementation specification; business and legal decisions explicitly marked as pending

---

## 1. Purpose and implementation principles

This document translates the approved planning blueprint into an implementation plan for database migrations, authorization, workflows, Inertia actions, background processing, and phase-by-phase testing.

### 1.1 Binding principles

1. **Server-side authorization is mandatory.** Hiding controls in React is not authorization.
2. **Church isolation is enforced at query and policy layers.** A Church Admin or lector must not access another church's private data by changing an ID in a URL or request.
3. **Payment, order, reading, cancellation/refund, and settlement states are separate.** One workflow must not implicitly complete another.
4. **Financial evidence and decisions are append-only/auditable.** Never silently overwrite or delete a financial decision.
5. **Money uses decimal/integer-safe arithmetic.** Do not use floating-point values for money.
6. **Important multi-step changes run in database transactions.** Use row locks where concurrent requests could double-verify, reorder, finalize, refund, or settle a record.
7. **Pricing and revenue-sharing remain configurable and inactive until formally approved.** Do not seed assumed rates or percentages.
8. **User-submitted intention text is preserved verbatim.** Interface localization must not translate saved names or intention wording.
9. **Protected files are served through authorized controllers or temporary private URLs.** Never expose payment evidence or private reading lists through public storage URLs.
10. **Every sensitive decision records the actor, time, reason, target, and relevant before/after metadata.**

### 1.2 Decisions intentionally not hard-coded

The following remain configurable or require business/legal approval:
- Intention prices, service fees, tax treatment, revenue-sharing percentages, rounding rules, and church payout formula.
- Whether/how fees are absorbed, passed through, or deducted.
- Exact payment submission deadline and the treatment of each late-payment scenario.
- Refund eligibility, approval thresholds, and refund deadlines.
- Settlement week boundaries, timezone policy, review sign-off requirements, and payout timetable.
- Legal retention periods, privacy wording, and formal response-time commitments.
- Whether an authorized GCash integration or transaction feed is available.
- Quantitative performance, uptime, and recovery objectives.

Until approved, the system may store a manually entered order amount and verified payment amount, but it must not calculate an authoritative church share using invented rules. Settlement calculation must return a clear “configuration required” result if no approved pricing/revenue configuration applies.

---

## 2. Application structure

Recommended Laravel organization:

```text
app/
  Actions/
    Churches/
    Masses/
    Orders/
    Payments/
    ReadingLists/
    Requests/
    Settlements/
  Enums/
  Events/
  Http/Controllers/
    Admin/
    Church/
    Lector/
    Public/
    User/
    Guest/
  Http/Requests/
  Jobs/
  Mail/
  Models/
  Notifications/
  Policies/
  Services/
    Audit/
    Booking/
    Payments/
    Pricing/
    ReadingLists/
    Settlements/
    GuestAccess/
  Support/
database/
  migrations/
  seeders/
resources/js/
  Pages/
  Components/
  Layouts/
  hooks/
routes/
  web.php
  console.php
tests/
  Feature/
  Unit/
```

Use Inertia pages for normal browser workflows. The endpoints below are route/controller actions, not a requirement to build a separate public REST API. If a future mobile/native client is introduced, expose a versioned API through the same domain actions and policies rather than duplicating business logic.

Use backed PHP enums for fixed system states. Store flexible church-defined categories as database records, not PHP enums.

---

## 3. Data model and migration plan

### 3.1 General conventions

- Primary keys: `id` as unsigned big integer unless UUID/ULID is adopted consistently before initial migration. Public-facing order references and guest credentials must not be based on sequential IDs.
- Timestamps: `created_at`, `updated_at`; add `deleted_at` only for records where soft deletion is appropriate.
- Time: store timestamps consistently in UTC; render using the church's configured timezone. Store the timezone identifier on each church. Confirm this policy before production.
- Money: `DECIMAL(12,2)` in PHP/Laravel decimal strings, never `float`. If the business chooses a currency other than PHP in future, add explicit currency codes and migration review.
- JSON: use only for supplementary metadata/snapshots, not as a replacement for relational columns needed for filtering, authorization, or financial reports.
- Foreign keys: use restrictive deletion for financial/audit history; use cascading deletion only for disposable child data that has no independent retention requirement.
- Church-owned records should carry `church_id` where it materially improves isolation and reporting. Where a record also references a parent, validate that both church IDs match in application services and, where feasible, composite database constraints.
- Index every common filter/sort field and foreign key.
- Do not use public sequential IDs in guest links or file paths.
- Use explicit status columns; do not infer payment status from nullable timestamps.

### 3.2 Migration sequence

Create migrations in this order to satisfy foreign keys:

1. `users`
2. `churches`
3. `church_memberships`
4. `church_branding`
5. `church_categories`
6. `mass_series`
7. `mass_schedules`
8. `mass_assignments`
9. `system_settings`
10. `pricing_configurations`
11. `orders`
12. `intentions`
13. `payments`
14. `payment_evidence`
15. `payment_exceptions`
16. `refunds`
17. `reading_lists`
18. `reading_list_entries`
19. `reading_list_entry_issues`
20. `correction_requests`
21. `cancellation_requests`
22. `rescheduling_requests`
23. `mass_change_cases` (administrative Mass cancellation/rescheduling cases)
24. `settlements`
25. `settlement_lines`
26. `payouts`
27. `support_tickets`
28. `support_ticket_events`
29. `notifications` / Laravel notifications table plus `notification_deliveries`
30. `invitations`
31. `guest_order_access`
32. `order_account_links` (optional explicit audit/link record)
33. `privacy_requests`
34. `audit_logs`
35. `security_incidents`
36. `reading_list_versions` or equivalent immutable snapshots
37. `failed_jobs`, `jobs`, `job_batches`, `sessions` as required by deployment configuration

### 3.3 Core tables and columns

The following is the recommended schema contract. Laravel migration syntax can be generated from it. Field lengths and legal retention rules must be reviewed before launch.

#### `users`
- `id`
- `name` string
- `email` string, unique
- `email_verified_at` nullable timestamp
- `password` string
- `status` enum/string: `active`, `disabled`, `pending_deletion`
- `preferred_locale` string default `en` (allow `en`, `fil`, `ceb`)
- `last_login_at` nullable timestamp
- remember token and timestamps
- Index: `status`; `preferred_locale`
- Do not use a global role as the sole authority for church-specific staff access. Central Admin is a global privilege; Church Admin and Lector are church memberships.

#### `churches`
- `id`
- `name`, `slug` unique
- `status`: `draft`, `pending_verification`, `verified`, `published`, `suspended`, `deactivated`
- public contact fields, address, city/municipality, province, optional coordinates
- `timezone` default approved system timezone; validate IANA timezone
- `verified_at`, `verified_by` nullable FK to users
- `published_at`, `suspended_at`, `suspended_by`, `suspension_reason`
- `deactivated_at`, `deactivated_by`, `deactivation_reason`
- timestamps
- Index: `status`, location filters, `name`
- Public directory queries must include only eligible published churches.

#### `church_memberships`
- `id`, `church_id`, `user_id`
- `role`: `church_admin`, `lector`
- `status`: `invited`, `active`, `suspended`, `revoked`
- `invited_by`, `activated_at`, `revoked_at`, `revoked_by`, `reason`
- timestamps
- Unique (`church_id`, `user_id`, `role`) or an equivalent rule that prevents duplicate active membership. If role changes are supported, record the change in audit history.
- Index (`user_id`, `status`), (`church_id`, `role`, `status`)
- A person may have memberships at multiple churches, but each request must resolve the relevant membership explicitly.

#### `church_branding`
- `id`, `church_id` unique
- approved logo/banner storage keys, theme values, public description, contact display options
- `approved_at`, `approved_by`
- timestamps
- Store assets privately until approved; sanitize uploads and validate file type/size.

#### `church_categories`
- `id`, `church_id` nullable for system-wide categories
- `type`: `intention` or `mass`
- `key`, `label`, optional description, `is_active`, `is_system`
- `sort_order`, timestamps
- Avoid deleting categories referenced by historical intentions. Deactivate instead.
- Unique key within the appropriate scope; implement carefully for MySQL nullable unique semantics.

#### `mass_series`
- `id`, `church_id`, recurrence definition/rule, start/end dates, timezone, series status
- `created_by`, timestamps
- Recurrence representation must be validated; do not silently edit existing occurrences when the series changes.

#### `mass_schedules`
- `id`, `church_id`, nullable `mass_series_id`
- `starts_at`, `ends_at` nullable, `timezone`
- `category_id` nullable FK to Mass category; also store a category label snapshot where historical readability is needed
- `booking_status`: `open`, `closed`, `cancelled`
- `booking_opens_at`, `booking_cutoff_at`
- `finalization_deadline` nullable
- `location_label` nullable (chapel/venue)
- `resource_key` nullable for conflict checking where shared resources matter
- `status` such as `scheduled`, `cancelled`, `completed`
- `created_by`, `updated_by`, timestamps
- Index (`church_id`, `starts_at`), (`church_id`, `booking_status`, `starts_at`), (`mass_series_id`)
- Conflict checking must use actual shared resources and assigned staff, not blanket overlap rejection.

#### `mass_assignments`
- `id`, `church_id`, `mass_schedule_id`, `user_id`
- `assignment_role` (first-release role `lector`; allow extensibility)
- `status`: `active`, `removed`
- `assigned_by`, `assigned_at`, `removed_at`, `removed_by`
- Unique active assignment per Mass/user/role.
- Index (`user_id`, `status`), (`mass_schedule_id`, `status`)

#### `system_settings`
- `id`, `key` unique, `value` JSON, `scope` (`global` or `church`), nullable `church_id`
- `updated_by`, timestamps
- Restrict editing to Central Admin with explicit permission; validate setting-specific schemas. Never store secrets in ordinary settings.

#### `pricing_configurations`
- `id`, `scope` (`global` or `church`), nullable `church_id`
- `name`, `currency` default `PHP`, `status`: `draft`, `approved`, `retired`
- `effective_from`, `effective_until` nullable
- `pricing_rules` JSON for approved item pricing only; `revenue_share_rules` JSON for approved allocation formula only
- `approved_by`, `approved_at`, `change_reason`
- `version` integer
- timestamps
- Index (`scope`, `church_id`, `status`, `effective_from`)
- No default prices or revenue-share percentages. Seed no active approved configuration. Changes create a new version; do not mutate historical configurations used by existing orders/settlements.

#### `orders`
- `id`, `public_id` ULID/UUID unique; `order_number` unique human-readable random reference
- nullable `user_id` FK; `submitter_type`: `registered_user`, `guest`, `staff_assisted`
- `church_id`, `mass_schedule_id`
- `status`: `awaiting_payment`, `payment_under_review`, `confirmed`, `action_required`, `cancelled`, `completed`
- `currency`, `expected_amount DECIMAL(12,2)` nullable only if pricing configuration is not yet approved; if a price is required to submit, do not permit order creation until configured
- `pricing_configuration_id` nullable FK; `pricing_snapshot` JSON nullable
- `submitter_name`, `submitter_email` snapshots where needed for guest workflow
- `privacy_notice_version`, `privacy_acknowledged_at`
- `submitted_at`, `confirmed_at`, `cancelled_at`, `completed_at`
- timestamps
- Index (`church_id`, `created_at`), (`church_id`, `mass_schedule_id`, `status`), (`user_id`, `created_at`), (`status`, `created_at`)
- Enforce one church and one Mass per order in service validation and relationship constraints.

#### `intentions`
- `id`, `order_id`, `church_id`, `mass_schedule_id`
- `category_id` nullable FK plus `category_label_snapshot`
- `person_or_family_name`, nullable `pronunciation_hint` (separate staff-authored guidance), `wording` text
- `status`: `awaiting_payment`, `confirmed`, `correction_pending`, `cancelled`, `completed`
- `original_name_snapshot`, `original_wording_snapshot` set at creation or on first approved correction
- `sort_order` for order display if needed
- `created_at`, `updated_at`
- Index (`church_id`, `mass_schedule_id`, `status`), (`order_id`)
- Validate the order, church, and Mass IDs agree. A category rename must not change the snapshot.

#### `payments`
- `id`, `public_id` ULID/UUID unique, `order_id`, `church_id`
- `expected_amount DECIMAL(12,2)`, `submitted_amount DECIMAL(12,2)` nullable, `verified_amount DECIMAL(12,2)` nullable
- `currency`
- `status`: `pending_payment`, `pending_verification`, `needs_review`, `rejected`, `resolution_required`, `verified`, `refund_pending`, `partially_refunded`, `refunded`
- `provider` default `gcash`; do not imply official API integration
- `submitted_reference` nullable; normalized reference hash/lookup column as appropriate
- `verified_by`, `verified_at`, `rejected_by`, `rejected_at`, `rejection_reason`
- `resolution_code`, `resolution_notes` private
- timestamps
- Index (`church_id`, `status`, `created_at`), (`order_id`), (`submitted_reference`)
- Enforce duplicate-reference handling transactionally. Add a unique constraint only after confirming provider reference semantics; otherwise use a normalized reference table/claim mechanism that flags collisions without preventing evidence submission.

#### `payment_evidence`
- `id`, `payment_id`, `church_id`
- `storage_disk`, `storage_key`, `original_filename`, `mime_type`, `size_bytes`, optional checksum
- `uploaded_by_user_id` nullable, `uploaded_by_guest` boolean
- `evidence_status`: `active`, `superseded`, `quarantined`, `deleted_after_retention`
- timestamps
- Index (`payment_id`), (`church_id`, `created_at`)
- Never expose `storage_key` or public URL to users.

#### `payment_exceptions`
- `id`, `payment_id`, `church_id`
- `type`: `unmatched`, `underpayment`, `overpayment`, `duplicate_reference`, `late_payment`, `unclear_evidence`, `other`
- `status`: `open`, `investigating`, `resolved`, `closed`
- `priority`, `description`, `resolution_code`, `resolution_reason`
- `opened_by`, `assigned_to`, `resolved_by`, `resolved_at`
- timestamps
- Index (`church_id`, `status`, `priority`), (`payment_id`, `type`)

#### `refunds`
- `id`, `payment_id`, `order_id`, `church_id`
- `status`: `requested`, `under_review`, `approved`, `rejected`, `transfer_pending`, `completed`, `failed`, `cancelled`
- `requested_by_user_id` nullable, `request_source` (`user`, `staff`, `mass_change`)
- `requested_amount DECIMAL(12,2)`, `approved_amount DECIMAL(12,2)` nullable
- `reason`, `decision_reason`, `requested_at`, `decided_by`, `decided_at`
- `transfer_reference` nullable, `transfer_date` nullable, `completed_by` nullable, `completed_at` nullable
- evidence storage key nullable
- timestamps
- `completed` is permitted only when actual transfer reference/date and responsible staff confirmation are recorded. Do not automatically complete from a cancellation.

#### `reading_lists`
- `id`, `church_id`, `mass_schedule_id` unique
- `status`: `preparing`, `finalized`, `reopened`, `completed`
- `version` integer default 1
- `finalized_at`, `finalized_by`, `reopened_at`, `reopened_by`, `reopen_reason`
- `completed_at`, `completed_by`
- timestamps
- Index (`church_id`, `status`), (`mass_schedule_id`)
- The list is created when needed; only confirmed/eligible intentions become entries.

#### `reading_list_entries`
- `id`, `reading_list_id`, `church_id`, `mass_schedule_id`, `intention_id`
- `position` unsigned integer
- `status`: `pending`, `completed`, `issue_reported`, `excluded`
- `completed_at`, `completed_by`
- timestamps
- Unique (`reading_list_id`, `intention_id`); unique (`reading_list_id`, `position`) where supported and appropriate.
- Index (`church_id`, `mass_schedule_id`, `position`), (`status`)
- Reordering must run in a transaction and avoid temporary position collisions (e.g. use two-step temporary offsets or a robust ordered-update strategy).
- Include only intentions backed by a verified payment. Recheck this condition before showing names, finalizing, marking read, and generating a non-snapshot PDF.
- An open issue is private to authorized staff; the intention owner sees only a generic “under review” status.

#### `reading_list_entry_issues`
- `id`, `reading_list_entry_id`, `church_id`, `reported_by`, nullable `resolved_by`
- `status`: `open`, `resolved`
- `private_note` text, nullable `resolution` (`pending` or `excluded`), `reported_at`, nullable `resolved_at`
- timestamps
- Preserve resolved reports and their private notes as history. Never expose the private note to the intention owner.

#### `reading_list_versions`
- `id`, `reading_list_id`, `version`, `snapshot` JSON, `created_by`, `reason`, timestamps
- Unique (`reading_list_id`, `version`)
- Immutable snapshot of ordering/content at finalization or authorized change.

#### `correction_requests`
- `id`, `order_id`, `intention_id`, `church_id`
- `requested_by_user_id` nullable, `request_source`
- `old_values` JSON, `requested_values` JSON
- `status`: `submitted`, `under_review`, `approved`, `rejected`, `withdrawn`
- `reviewed_by`, `reviewed_at`, `decision_reason`
- timestamps
- Store a snapshot of the original and requested content. Apply only after authorization and review.

#### `cancellation_requests`
- `id`, `order_id`, nullable `intention_id`, `church_id`
- `requested_by_user_id` nullable, `request_source`, `reason`
- `status`: `submitted`, `under_review`, `approved`, `rejected`, `withdrawn`
- `reviewed_by`, `reviewed_at`, `decision_reason`
- `refund_required` nullable until decided; do not treat as a completed refund
- timestamps

#### `rescheduling_requests`
- `id`, `order_id`, nullable `intention_id`, `church_id`
- `requested_by_user_id` nullable, `request_source`
- `requested_mass_schedule_id`
- `reason`, `status`: `submitted`, `under_review`, `approved`, `rejected`, `withdrawn`
- `reviewed_by`, `reviewed_at`, `decision_reason`
- `payment_implication_status`: `not_assessed`, `no_change`, `recalculation_required`, `resolution_required`
- timestamps
- Validate target Mass belongs to the same church and is open/eligible at decision time.

#### `mass_change_cases`
- `id`, `church_id`, `mass_schedule_id`
- `change_type`: `cancel_mass`, `reschedule_mass`
- `status`: `opened`, `orders_flagged`, `users_notified`, `awaiting_resolution`, `resolved`, `closed`
- `original_schedule_snapshot`, nullable `replacement_mass_schedule_id`
- `reason`, `opened_by`, `resolved_by`, `resolved_at`
- timestamps
- Per-order outcomes should be recorded in an associated `mass_change_case_orders` table with original state, decision, target Mass, refund action, actor, reason, and timestamps.

#### `settlements`
- `id`, `public_id` ULID/UUID unique, `church_id`
- `period_start`, `period_end`, `currency`
- `status`: `draft`, `configuration_required`, `calculated`, `under_review`, `ready_for_payout`, `partially_paid`, `paid`, `disputed`, `voided`
- `pricing_configuration_id` nullable
- `gross_verified_amount DECIMAL(12,2)`, `fees_amount DECIMAL(12,2)` nullable, `church_amount_due DECIMAL(12,2)` nullable
- `calculation_snapshot` JSON nullable; snapshot includes formula version and source payment IDs
- `calculated_at`, `reviewed_by`, `reviewed_at`, `approved_by`, `approved_at`
- `voided_by`, `void_reason`
- timestamps
- Unique (`church_id`, `period_start`, `period_end`, `currency`) unless approved business rules require multiple versions; if recalculation is required, version the settlement explicitly rather than silently replacing it.

#### `settlement_lines`
- `id`, `settlement_id`, `payment_id`, `order_id`, `gross_amount DECIMAL(12,2)`, optional `fee_amount`, optional `church_amount`
- `calculation_snapshot` JSON nullable
- Unique (`settlement_id`, `payment_id`)
- Index (`payment_id`), (`order_id`)
- Only verified collected payments qualify. Prevent the same payment from being included twice in active settlements.

#### `payouts`
- `id`, `settlement_id`, `church_id`
- `status`: `draft`, `ready`, `transfer_pending`, `completed`, `failed`, `cancelled`
- `amount DECIMAL(12,2)`, `currency`, `transfer_reference` nullable
- `transfer_date` nullable, evidence storage key nullable
- `recorded_by`, `completed_by`, `completed_at`, `failure_reason`
- timestamps
- `completed` requires evidence of actual transfer and a responsible staff record. Payouts are not executed automatically by this system in the first release.

#### `support_tickets`
- `id`, `public_id` ULID/UUID unique, nullable `user_id`, nullable `order_id`, nullable `church_id`
- `requester_name`, `requester_email`
- `category`: `church`, `payment`, `refund`, `technical`, `security`, `urgent`, `other`
- `priority`: `urgent`, `high`, `normal`, `low`
- `status`: `new`, `triaged`, `assigned`, `in_progress`, `waiting_for_user`, `escalated`, `resolved`, `closed`
- `assigned_team`, nullable `assigned_to`, `created_by`
- `subject`, `description`
- `resolved_by`, `resolved_at`, `resolution_summary`
- timestamps
- Index (`status`, `priority`, `created_at`), (`church_id`, `status`), (`assigned_to`, `status`)

#### `support_ticket_events`
- `id`, `support_ticket_id`, `actor_user_id` nullable, `event_type`, `from_value` JSON nullable, `to_value` JSON nullable, `internal_note` text nullable, `public_message` text nullable, timestamps
- Internal notes and public messages must be separate fields and separately authorized.

#### `notification_deliveries`
- `id`, Laravel notification identifier, nullable `user_id`, nullable `order_id`, nullable `church_id`
- `channel`, `event_key`, `status`: `queued`, `sent`, `failed`, `retrying`, `abandoned`
- `attempt_count`, `last_attempt_at`, `sent_at`, `last_error_code` (avoid sensitive payloads), `next_attempt_at`
- timestamps
- Index (`status`, `next_attempt_at`), (`order_id`, `event_key`)

#### `invitations`
- `id`, `church_id`, `email`, `role`, `token_hash`, `invited_by`, `expires_at`, `accepted_at`, `revoked_at`, `revoked_by`
- timestamps
- Store only a hash of the invitation token; enforce expiry, one-time acceptance, and revocation.

#### `guest_order_access`
- `id`, `order_id`, `token_hash`, `email`, `email_verified_at`, `expires_at`, `revoked_at`, `last_used_at`
- timestamps
- Store token hashes, not raw access tokens. Use high-entropy random tokens and rate-limit all recovery/verification routes.
- A guest tracking link is a credential. Avoid putting sensitive data in URL query parameters beyond the unguessable token, and avoid logging it.

#### `privacy_requests`
- `id`, nullable `user_id`, `requester_email`, `request_type` (`access`, `correction`, `deletion`, `other`)
- `status`: `submitted`, `identity_verification`, `under_review`, `fulfilled`, `rejected`, `closed`
- `identity_verified_at`, `assigned_to`, `decision_reason`, `fulfilled_at`
- timestamps

#### `audit_logs`
- `id`, nullable `actor_user_id`, `actor_type`, `action`, `target_type`, `target_id`
- nullable `church_id`, `request_id`/correlation ID, `reason`
- `before_data` JSON nullable, `after_data` JSON nullable, `ip_hash` nullable, `user_agent_summary` nullable
- `created_at` (append-only; no normal `updated_at`)
- Index (`church_id`, `created_at`), (`actor_user_id`, `created_at`), (`target_type`, `target_id`), (`action`, `created_at`)
- Do not log passwords, raw guest tokens, complete payment screenshots, or unnecessary intention text. Audit records must not be editable through normal application actions.

#### `security_incidents`
- `id`, `incident_type`, `severity`, `status`, `reported_by`, `assigned_to`, `summary`, protected evidence references, `opened_at`, `contained_at`, `resolved_at`, `resolution_summary`
- timestamps
- Access restricted to specifically authorized Central Admin/security personnel.

### 3.4 Integrity rules and indexes

Application validation plus database constraints must enforce:
- Every order references exactly one church and one Mass belonging to that church.
- Every intention belongs to its order's church and Mass.
- Every payment belongs to the same church as its order.
- Every reading-list entry links to an intention eligible for that Mass and church.
- Every assignment links a lector membership active for that church.
- A Church Admin may only manage the church identified by the active membership used for the request.
- A settlement includes only verified payments for that church and reporting period, and no payment is duplicated in another active settlement.
- A refund's completed state cannot be set without actual transfer details and confirmation.
- A payout's completed state cannot be set without actual transfer details and confirmation.
- A finalization/reopening/correction event must be audited.
- Financial records and audit history are not hard-deleted as a dispute-resolution shortcut.

For MySQL, where composite foreign keys are practical, consider unique keys such as `(id, church_id)` on parent tables and composite foreign keys from child `(parent_id, church_id)` pairs. Where Laravel's schema tooling or existing relationships make this impractical, enforce matching-tenant invariants inside domain actions and test them explicitly. Do not assume a global Eloquent scope alone is a security boundary.

### 3.5 Deletion policy

- Church: suspend/deactivate, not hard-delete, after orders exist.
- User/staff: disable membership/session access; anonymize eligible fields only after privacy/legal review.
- Categories: deactivate rather than delete when referenced.
- Orders, payments, refunds, settlements, payouts, audit logs: retain according to approved legal policy; never cascade-delete.
- Payment evidence: protected retention/deletion job after an approved retention schedule is adopted.
- Guest credentials: revoke/expire independently from order history.
- Files: delete through a controlled retention process and record the action.

---

## 4. Roles, policies, and authorization

### 4.1 Role model

Use Laravel Gates/Policies and explicit membership checks:
- **Central Admin:** global role/permission, all churches within operational authority.
- **Church Admin:** active `church_memberships` record with role `church_admin`.
- **Lector:** active membership with role `lector`, plus an active assignment for the specific Mass when accessing Mass reading operations.
- **Registered User:** authenticated owner of the order, unless a separate explicit permission applies.
- **Guest:** possession of a valid, unexpired, non-revoked guest credential and successful email verification for private tracking.
- **Technical support:** if staffed by an account, use a narrowly scoped permission/role rather than broad Central Admin access. This is an operational permission, not a fifth general business role unless formally approved.

Never authorize from a client-supplied `church_id`, role name, email match, or hidden React control alone.

### 4.2 Policy matrix

| Resource/action | Central Admin | Church Admin | Assigned Lector | Registered owner | Verified guest |
|---|---|---|---|---|---|
| Create/verify/publish church | Yes | No | No | No | No |
| Edit church profile/branding | All churches | Own church | No | No | No |
| Manage church memberships | All churches | Own church; invite lectors according to policy | No | No | No |
| Manage Mass schedules | Oversight/all | Own church | Assigned Mass: close early only | Read open schedules | Read open schedules |
| Submit order | Only if explicit staff-assisted action is authorized and audited | No by default | No | Own submission | Guest submission |
| View order/intention details | Operational need, audited for sensitive access | Own church only as required | Assigned Mass confirmed entries only | Own orders | Own order via credential |
| Verify/reject payment | Yes only | No | No | No | No |
| View payment evidence | Authorized financial access | No routine access | No | Own submitted evidence/status, not internal evidence | Same as owner-safe view |
| Manage reading list | Oversight | Own church per permission | Assigned Mass only | No | No |
| Finalize/reopen reading list | Authorized Central Admin | Own church with permission | Assigned Mass; finalize under rules, no unrestricted reopening | No | No |
| Mark completion | Oversight | Authorized own-church action | Assigned Mass | No | No |
| Review correction/cancellation/reschedule | Central oversight | Own church if policy permits | Report issue; no customer request adjudication by default | Submit/withdraw own request | Submit/withdraw own request |
| Financial reports/settlements | All | Own church summary only | No | Own order payment status only | Own order payment status only |
| Create/complete payout | Authorized Central Admin | No | No | No | No |
| Support | All/escalated | Relevant own-church cases | Report issue | Submit/view own case | Submit/view verified case |
| Audit/security incident data | Narrowly authorized | Own permitted events only, if exposed | No | No | No |

Blueprint-specific restriction: Church Admins cannot approve, reject, or modify payment verification decisions. Lectors cannot access unassigned Masses. Technical support must not receive routine broad access to private intentions or financial data.

### 4.3 Required policies

Implement at least:
- `ChurchPolicy`: `view`, `create`, `update`, `verify`, `publish`, `suspend`, `deactivate`, `reactivate`.
- `MembershipPolicy`: `viewAny`, `invite`, `revoke`, `changeRole`.
- `MassSchedulePolicy`: `view`, `create`, `update`, `closeEarly`, `open`, `cancel`, `reschedule`, `assignLector`.
- `OrderPolicy`: `view`, `create`, `updateBeforeVerification`, `submitPaymentEvidence`, `requestCancellation`, `requestReschedule`, `requestCorrection`, `linkToAccount`.
- `PaymentPolicy`: `view`, `review`, `verify`, `reject`, `resolveException`.
- `RefundPolicy`: `request`, `review`, `approve`, `reject`, `recordTransfer`.
- `ReadingListPolicy`: `view`, `reorder`, `finalize`, `reopen`, `markEntryComplete`, `correctCompletion`.
- `SupportTicketPolicy`: `view`, `assign`, `replyPublic`, `addInternalNote`, `escalate`, `resolve`, `close`.
- `SettlementPolicy`: `view`, `calculate`, `review`, `approve`, `void`.
- `PayoutPolicy`: `view`, `recordTransfer`, `complete`.
- `PrivacyRequestPolicy`: `view`, `verifyIdentity`, `decide`, `fulfill`.
- `AuditLogPolicy` and `SecurityIncidentPolicy`: restricted to authorized personnel.

Each policy should call a shared helper such as `user->isCentralAdmin()` or `user->hasActiveMembership($churchId, $role)`. For lector operations, additionally check an active `mass_assignments` record for the exact Mass.

### 4.4 Tenant-safe query rules

- Derive church scope from the authorized model/membership, not arbitrary request input.
- Use route model binding plus policy checks; do not assume binding itself authorizes access.
- For church dashboards, start from `Church::query()` after `authorize()` and scope every relation to that church.
- For lector dashboards, query Masses through active assignments for the authenticated user.
- For user history, query orders through `where('user_id', auth()->id())`.
- For guests, resolve the order only after validating the credential hash, expiry, revocation, and email verification.
- Return a generic 404 for unauthorized cross-tenant resource IDs where appropriate to avoid confirming existence.
- Test IDOR attempts for every major resource, including PDFs, exports, evidence files, and support cases.

---

## 5. Workflow and state-transition rules

### 5.1 Order state

Recommended states:
- `awaiting_payment`
- `payment_under_review`
- `confirmed`
- `action_required`
- `cancelled`
- `completed`

Allowed transitions:
- `awaiting_payment` → `payment_under_review` when valid evidence is submitted.
- `awaiting_payment` → `cancelled` only through an authorized cancellation outcome or an approved timeout policy; do not invent an automatic timeout before configured.
- `payment_under_review` → `confirmed` only after payment verification and order eligibility checks succeed.
- `payment_under_review` → `action_required` when user/staff action is required.
- `action_required` → `payment_under_review` after corrected evidence/resolution is submitted.
- Any eligible active state → `cancelled` only through an approved cancellation decision, Mass change resolution, or authorized administrative action.
- `confirmed` → `completed` when the relevant reading workflow is complete and order completion rules are satisfied.
- `cancelled` is terminal for normal user actions; any exceptional reversal requires an authorized audited administrative workflow.

Do not automatically mark an order `confirmed` because a screenshot was uploaded. Do not mark it `completed` solely because a Mass time passed.

### 5.2 Payment state

States: `pending_payment`, `pending_verification`, `needs_review`, `rejected`, `resolution_required`, `verified`, `refund_pending`, `partially_refunded`, `refunded`.

Transitions:
- `pending_payment` → `pending_verification` when required reference and evidence are submitted.
- `pending_verification` → `verified` only by Central Admin after explicit human verification and amount/reference checks.
- `pending_verification` → `needs_review` for unclear/unmatched/duplicate/late/underpayment/overpayment scenarios.
- `pending_verification` → `rejected` only with a reason and notification event.
- `needs_review` → `verified`, `rejected`, or `resolution_required` after recorded investigation.
- `rejected` → `pending_verification` only when resubmission is explicitly allowed and a new evidence submission is recorded; preserve the prior decision.
- `verified` → `refund_pending` only when an approved refund process begins.
- `refund_pending` → `partially_refunded` or `refunded` only after actual transfer amount/details are recorded and confirmed.
- `verified` remains historically verified even if the order is later cancelled. Do not rewrite it to `rejected`.
- A duplicate reference is flagged and investigated; never count one transaction twice.

All payment decision actions must lock the payment row in a transaction, check current state again, write an audit record, and dispatch notifications after commit.

### 5.3 Intention state

- `awaiting_payment` → `confirmed` only when payment is verified and the order remains eligible.
- `confirmed` → `correction_pending` when a correction request is submitted; the saved approved wording does not change yet.
- `correction_pending` → `confirmed` after approval and an auditable correction; or back to `confirmed` after rejection/withdrawal.
- `confirmed` → `cancelled` only after an authorized cancellation decision.
- `confirmed` → `completed` when the corresponding reading-list entry is marked complete.
- Completed content corrections require the explicit correction workflow and must preserve previous values.
- **Project decision (2026-10-31):** Central Admin alone approves/rejects intention corrections. Apply an approval only while the reading list is preparing or reopened; a finalized list must first be reopened by its assigned lector with a reason. Do not approve corrections after the reading list is completed; a separate completed-list correction workflow requires approval before implementation.
- **Project decision (2026-10-31):** An assigned lector may correct an erroneously completed entry only while its reading list remains `finalized`. Require a reason, return the entry and intention to pending/confirmed, restore the order to confirmed if needed, and append an audit event without deleting the original completion event. Do not allow this correction after the reading list itself is completed.

### 5.4 Reading-list state

- `preparing` → `finalized` by an authorized actor or configured finalization job.
- `finalized` → `reopened` only with a reason, actor, audit record, and a new version/change history.
- `reopened` → `finalized` after corrections and validation.
- `finalized` → `completed` only after every required reading-list entry is explicitly marked `completed`; elapsed Mass time is not sufficient.
- An assigned lector may report a private issue against a pending paid entry. The owner sees only a generic “under review” status. The assigned lector or same-church Church Admin may resolve it to pending or excluded; exclusion changes only this reading list and never cancels the order, changes verified payment, or creates a refund.
- A report may be filed against a finalized list, but resolving it or changing pronunciation guidance requires the assigned lector to reopen the list with a reason first. Completed lists reject these operations. Open reports block finalization and PDF download.
- Church Admins author pronunciation hints as separate metadata; assigned lectors may view them in the workbench and protected PDF. Hints never alter the submitted name or wording and can be changed only while the list is preparing or reopened. Finalized versions snapshot the hint.
- A completed list may require a controlled correction/reopen workflow; never silently edit a finalized snapshot.
- Finalization freezes normal reordering/preparation changes.
- Unresolved orders are not silently discarded from operational tracking; they remain separately visible to authorized staff.

A scheduled finalization job must use the same domain action and policy/business validation as a manual finalization. It should be idempotent.

### 5.5 Cancellation request state

- `submitted` → `under_review` → `approved` or `rejected`.
- Requester may withdraw only while not decided.
- Approval updates order/intention state only according to the decision scope.
- Refund eligibility is a separate decision. A cancellation approval does not mean refund approved or refunded.
- Refund completion requires actual transfer evidence.

### 5.6 Rescheduling request state

- `submitted` → `under_review` → `approved` or `rejected`.
- On approval, lock both source and target Mass records and revalidate church match, target availability, cutoff, and reading-list finalization.
- Record original and target Mass IDs, decision reason, payment implication, and any resulting adjustment.
- Never silently move a verified payment or intention to another church.
- If pricing implications are unknown because pricing rules are unapproved, set `resolution_required` rather than guessing.

### 5.7 Mass cancellation/rescheduling case

1. Authorized staff opens a case and snapshots original Mass details.
2. Flag affected orders and prevent new submissions to a cancelled Mass.
3. Notify affected users after the database transaction commits.
4. Central Admin or authorized staff records a per-order outcome: retain if valid, move to another Mass, cancel, or initiate refund review.
5. Preserve the original schedule and order history.
6. Close the case only when every affected order has a recorded outcome or an explicitly documented unresolved exception.

### 5.8 Settlement and payout state

Settlement:
- `draft` → `configuration_required` if no applicable approved pricing/revenue rules exist.
- `draft` → `calculated` only when all required configuration is approved and eligible verified transactions are included.
- `calculated` → `under_review` → `ready_for_payout` after authorized review/approval.
- `ready_for_payout` → `partially_paid` or `paid` based on recorded actual payouts.
- `disputed` pauses payout completion pending an audited resolution.
- `voided` requires reason and must not erase financial history.

Payout:
- `draft` → `ready` → `transfer_pending` → `completed`.
- `transfer_pending` → `failed` if transfer failed, with reason.
- `completed` requires actual transfer reference/date and confirming staff member.
- Never mark a payout complete when merely prepared or initiated externally.

Only verified payments are included as collected revenue. Prevent double inclusion using database constraints/transactional checks.

### 5.9 Support ticket state

- `new` → `triaged` → `assigned` → `in_progress`.
- `in_progress` may transition to `waiting_for_user`, `escalated`, or `resolved`.
- `resolved` → `closed`; reopening requires reason and audit.
- Payment/refund cases route to Central Admin; church-specific cases route to the relevant Church Admin; technical cases route to designated technical support; urgent/unclassified cases route to Central Admin triage.
- Public responses and internal notes are distinct. Users never see internal notes.

---

## 6. Pricing and revenue-sharing configuration

### 6.1 Configuration lifecycle

Use versioned `pricing_configurations`:
- Draft configurations can be edited by specifically authorized Central Admin personnel.
- Approval requires a separate explicit action and audit record.
- Approved configurations have effective dates and cannot be silently mutated.
- Changes create a new version with a reason and effective date.
- Each order snapshots the applicable approved pricing version and calculation inputs.
- Each settlement snapshots the formula version, input transactions, rounding rules, and output values.
- If no valid approved configuration applies, do not fabricate totals. Mark the calculation `configuration_required` and block readiness for payout.

### 6.2 Calculation service contract

Implement a `PricingService`/`SettlementCalculator` that accepts:
- church ID
- reporting period
- currency
- eligible verified payments
- approved configuration/version
- explicit calculation timestamp

It returns:
- `configuration_status`
- gross verified amount
- fees, only if configured
- church amount due, only if configured
- calculation line items
- configuration version and source payment IDs
- validation errors

The service must be deterministic and unit-tested. It must not read mutable UI values or trust a client-supplied total.

### 6.3 No assumed business rules

Do not seed values such as fixed per-intention prices, service fees, church percentages, minimum payout amounts, or refund percentages. Keep the configuration interface available to authorized personnel but prevent approval until required rule fields are formally supplied. Document unresolved decisions in the admin interface.

---

## 7. Routes and Inertia actions

These are recommended route contracts. Route names can be adjusted to the team's naming conventions, but authorization and behavior must remain equivalent. Use `POST` for state-changing actions, `PATCH` for editable resources, and `DELETE` only for revocable/disposable resources. State-changing actions must use CSRF protection and validation.

### 7.1 Public and authentication

| Method | Route | Action | Authorization / notes |
|---|---|---|---|
| GET | `/` | Public home | Published data only |
| GET | `/churches` | Directory | Published churches; filters validated |
| GET | `/churches/{church:slug}` | Church page | Published church only |
| GET | `/churches/{church}/masses` | Available Masses | Only public schedule fields |
| GET | `/masses/{mass}/intentions/create` | Submission form | Mass open and within booking window |
| POST | `/register` | Register user | Rate limited; verification email |
| POST | `/login` | Login | Rate limited; session regeneration |
| POST | `/logout` | Logout | Authenticated |
| GET | `/email/verify` | Verification notice | Authenticated |
| POST | `/email/verification-notification` | Resend verification | Rate limited |
| GET | `/privacy` | Privacy notice | Public; versioned notice |

### 7.2 Orders and guest access

| Method | Route | Action | Authorization / notes |
|---|---|---|---|
| POST | `/orders` | Create order with intentions | Guest or authenticated user; validate open Mass/cutoff |
| GET | `/orders/{order:public_id}` | Owner order view | Registered owner only |
| PATCH | `/orders/{order:public_id}/intentions` | Edit pre-verification intentions | Owner; payment not verified; eligibility rechecked |
| POST | `/orders/{order:public_id}/payment-evidence` | Submit GCash reference/evidence | Owner or valid guest session; protected upload |
| POST | `/orders/{order:public_id}/cancellation-requests` | Request cancellation | Owner/verified guest |
| POST | `/orders/{order:public_id}/rescheduling-requests` | Request rescheduling | Owner/verified guest |
| POST | `/orders/{order:public_id}/correction-requests` | Request correction | Owner/verified guest |
| POST | `/orders/{order:public_id}/support-tickets` | Create support case | Owner/verified guest |
| POST | `/guest-access/request` | Request tracking access/recovery | Generic response to prevent email enumeration |
| POST | `/guest-access/verify` | Verify email challenge | Rate limited; one-time challenge |
| GET | `/guest/orders/{order:public_id}` | Guest tracking | Valid credential + verified email |
| POST | `/guest/orders/{order:public_id}/link-account` | Link order to account | Must verify guest order and account; email match alone insufficient |
| POST | `/guest-access/revoke` | Revoke guest credential | Verified owner/support workflow |

Do not render private order information on a page until guest access verification succeeds. Guest token raw value must never be stored or logged.

### 7.3 Central Admin

| Method | Route | Action |
|---|---|---|
| GET | `/admin/dashboard` | Operational dashboard |
| GET/POST | `/admin/churches` | List/create churches |
| GET/PATCH | `/admin/churches/{church}` | View/update church |
| POST | `/admin/churches/{church}/verify` | Verify church |
| POST | `/admin/churches/{church}/publish` | Publish after checks |
| POST | `/admin/churches/{church}/suspend` | Suspend with reason |
| POST | `/admin/churches/{church}/reactivate` | Reactivate with reason |
| POST | `/admin/churches/{church}/deactivate` | Deactivate with reason |
| POST | `/admin/churches/{church}/invitations` | Invite first Church Admin |
| GET | `/admin/payments` | Queue of payment reviews |
| GET | `/admin/payments/{payment}` | Payment detail and protected evidence |
| POST | `/admin/payments/{payment}/verify` | Verify payment with amount/reference |
| POST | `/admin/payments/{payment}/reject` | Reject with reason |
| POST | `/admin/payments/{payment}/needs-review` | Flag for investigation |
| POST | `/admin/payments/{payment}/resolve` | Resolve exception |
| POST | `/admin/refunds/{refund}/approve` | Approve refund request |
| POST | `/admin/refunds/{refund}/reject` | Reject refund request |
| POST | `/admin/refunds/{refund}/record-transfer` | Record actual transfer |
| GET | `/admin/settlements` | Settlement list |
| POST | `/admin/settlements/calculate` | Calculate a period |
| GET | `/admin/settlements/{settlement}` | Settlement detail |
| POST | `/admin/settlements/{settlement}/submit-review` | Submit for review |
| POST | `/admin/settlements/{settlement}/approve` | Approve payout readiness |
| POST | `/admin/settlements/{settlement}/void` | Void with reason |
| POST | `/admin/settlements/{settlement}/payouts` | Create payout record |
| POST | `/admin/payouts/{payout}/record-transfer` | Record actual payout transfer |
| POST | `/admin/pricing-configurations` | Create draft config |
| PATCH | `/admin/pricing-configurations/{config}` | Edit draft config only |
| POST | `/admin/pricing-configurations/{config}/approve` | Approve version with audit |
| GET | `/admin/audit-logs` | Authorized audit search |
| GET | `/admin/reports/{report}` | Authorized report |
| POST | `/admin/reports/{report}/export` | Queue PDF/CSV/XLSX export |

Payment evidence routes must authorize access before streaming/download. Avoid exposing direct storage URLs.

### 7.4 Church Admin

| Method | Route | Action |
|---|---|---|
| GET | `/church-admin/{church}/dashboard` | Own church dashboard |
| PATCH | `/church-admin/{church}/profile` | Update own church profile |
| PATCH | `/church-admin/{church}/branding` | Update branding pending approval if applicable |
| GET/POST | `/church-admin/{church}/categories` | Manage own church categories |
| GET/POST | `/church-admin/{church}/mass-schedules` | List/create Mass schedules |
| PATCH | `/church-admin/{church}/mass-schedules/{mass}` | Update schedule |
| POST | `/church-admin/{church}/mass-schedules/{mass}/close` | Close submissions |
| POST | `/church-admin/{church}/mass-schedules/{mass}/cancel` | Open Mass change case |
| POST | `/church-admin/{church}/mass-schedules/{mass}/reschedule` | Open reschedule case |
| POST | `/church-admin/{church}/mass-schedules/{mass}/assignments` | Assign lector |
| DELETE | `/church-admin/{church}/mass-assignments/{assignment}` | Remove assignment/revoke access to future operations |
| POST | `/church-admin/{church}/invitations` | Invite lector |
| GET | `/church-admin/{church}/orders` | Permitted own-church operational orders; exclude payment verification controls |
| GET | `/church-admin/{church}/reading-lists` | Own-church lists |
| GET | `/church-admin/{church}/reports` | Own-church permitted reports |
| GET | `/church-admin/{church}/settlements` | Own-church settlement summaries only |
| GET/POST | `/church-admin/{church}/support-tickets` | Relevant church cases |

Church Admin must receive `403` or generic `404` for payment verification actions regardless of UI.

### 7.5 Lector

| Method | Route | Action |
|---|---|---|
| GET | `/lector/dashboard` | Assigned Masses only |
| GET | `/lector/masses/{mass}` | Assigned Mass details |
| GET | `/lector/masses/{mass}/reading-list` | Assigned list |
| PATCH | `/lector/masses/{mass}/reading-list/order` | Reorder entries transactionally |
| POST | `/lector/masses/{mass}/reading-list/finalize` | Finalize list if permitted |
| POST | `/lector/masses/{mass}/reading-list/reopen` | Reopen only if role/policy allows, reason required |
| POST | `/lector/reading-list-entries/{entry}/complete` | Mark entry completed |
| POST | `/churches/{church}/lector/masses/{mass}/reading-list/entries/{entry}/report-issue` | Assigned lector reports a private issue |
| POST | `/churches/{church}/lector/masses/{mass}/reading-list/entries/{entry}/issues/{issue}/resolve` | Assigned lector resolves to pending or excluded |
| GET | `/churches/{church}/masses/{mass}/reading-list` | Same-church Church Admin reviews issues and authors pronunciation hints |
| PATCH | `/churches/{church}/masses/{mass}/reading-list/entries/{entry}/pronunciation` | Church Admin updates a separate pronunciation hint |
| POST | `/churches/{church}/masses/{mass}/reading-list/entries/{entry}/issues/{issue}/resolve` | Same-church Church Admin resolves an issue |
| POST | `/lector/masses/{mass}/complete` | Complete list when completion criteria pass |
| GET | `/lector/masses/{mass}/reading-list.pdf` | Protected PDF |
| GET | `/lector/masses/{mass}/sync` | Latest list/version for online sync |

A lector cannot access another Mass by guessing the Mass ID. Reordering/finalization/completion must recheck assignment and list state in the domain action.

### 7.6 Requests, support, privacy, and exports

Use dedicated authorized actions for:
- Review/approve/reject correction requests.
- Review/approve/reject cancellation requests.
- Review/approve/reject rescheduling requests.
- Apply an approved correction with before/after audit snapshots.
- Resolve per-order Mass change outcomes.
- Assign/escalate/respond to support tickets.
- Separate public support responses from internal notes.
- Submit and process privacy requests.
- Download exports through expiring, authorized file delivery.

All request-decision routes require policy authorization, validation, current-state recheck, transaction, audit event, and notification after commit.

---

## 8. Validation and domain-action requirements

### 8.1 Create order

`CreateOrder` must:
1. Validate privacy notice acknowledgement and submitter fields.
2. Load the Mass and church from the database; do not trust a posted church ID independently.
3. Verify church is published/active and Mass is open, within booking window, and before cutoff.
4. Validate every intention category is active and allowed for that church.
5. Ensure all intentions belong to the same church and Mass.
6. Check approved pricing configuration. If the business requires price before submission and no approved configuration exists, block submission with a clear administrative configuration error. Do not invent a price.
7. Create order, intention records, pricing snapshot if available, and guest-access workflow as applicable in one transaction.
8. Recheck availability/cutoff inside the transaction immediately before commit.
9. Return an order reference and next-step payment instructions only after successful creation.
10. Apply rate limits and duplicate-warning logic without blocking legitimate repeated intentions.

### 8.2 Submit payment evidence

- Validate file MIME using server-side content inspection, allowed file types, maximum size, and image decoding where appropriate.
- Store file privately, preferably with a randomized key.
- Normalize reference for duplicate checking while retaining the original user-entered value as needed.
- Create a payment evidence record and transition payment/order to review states in one transaction.
- Do not verify payment automatically.
- If the payment is late, flag for review; do not silently discard it.
- Never accept a client-supplied “verified” status or verified amount.

### 8.3 Verify payment

- Central Admin only.
- Lock payment and order rows with `lockForUpdate()`.
- Ensure current payment state permits verification.
- Compare expected amount, submitted amount, reference, and trusted records available to staff.
- If unmatched, duplicate, underpaid, overpaid, unclear, or late, require a review/resolution path rather than forced normal verification.
- Record verifier, timestamp, verified amount, decision notes, and audit.
- In the same transaction, transition eligible intentions and order, and add confirmed intentions to the reading list idempotently.
- Dispatch user notifications only after commit.
- Repeated verify requests must not create duplicate reading-list entries or duplicate financial inclusion.

### 8.4 Reorder/finalize reading list

- Require active assignment and appropriate policy.
- Lock list and entries.
- Reject reorder if finalized/completed.
- Validate submitted entry IDs exactly match the eligible set; reject unknown, duplicated, or omitted IDs unless the action explicitly supports a partial reorder.
- Update order atomically.
- On finalization, create immutable version snapshot, actor, timestamp, and audit.
- Scheduled finalization must call the same service and tolerate repeat execution.

### 8.5 Apply correction

- Require an approved request and authorized reviewer.
- Lock request, intention, and reading list.
- Preserve original values and record approved new values.
- If the list is finalized, require authorized reopen, reason, version snapshot, and re-finalization as needed.
- Update the relevant reading-list entry without changing payment history.
- Notify the requester after commit.

### 8.6 Refund and payout transfer recording

- Approval and transfer completion are different actions.
- Approval sets an approved amount and transfer-pending status only.
- Actual transfer action requires transfer reference, date, confirming staff identity, and evidence where the operating policy requires it.
- Validate amount does not exceed eligible refundable/settlement balance unless an authorized exception is recorded.
- Audit before/after state and notify relevant parties after commit.
- Never infer transfer success from an external payment/cancellation status.

---

## 9. Events, notifications, jobs, and scheduling

### 9.1 Domain events

Use explicit domain events, dispatched after successful database commit:
- `ChurchVerified`, `ChurchPublished`, `ChurchSuspended`
- `InvitationCreated`, `InvitationAccepted`, `MembershipRevoked`
- `OrderSubmitted`, `PaymentEvidenceSubmitted`
- `PaymentVerified`, `PaymentRejected`, `PaymentNeedsReview`
- `CorrectionRequested`, `CorrectionDecided`
- `CancellationRequested`, `CancellationDecided`
- `ReschedulingRequested`, `ReschedulingDecided`
- `MassChangeOpened`, `MassChangeResolved`
- `ReadingListFinalized`, `ReadingListReopened`, `ReadingEntryCompleted`
- `RefundApproved`, `RefundTransferRecorded`
- `SettlementCalculated`, `SettlementApproved`, `PayoutTransferRecorded`
- `SupportTicketAssigned`, `SupportTicketEscalated`, `SupportTicketResolved`
- `PrivacyRequestSubmitted`, `SecurityIncidentOpened`

### 9.2 Notification rules

- Email and in-app status update for registered users on significant order events.
- Guests use secure tracking and email notices; minimize intention text and sensitive data in email.
- Failed email delivery must not reverse a payment/order/reading state.
- Use queue retries and a delivery log with bounded retry policy configurable by operations.
- Staff can resend important notifications, with audit.
- Use idempotency keys/event identifiers to prevent duplicate notices after job retries.
- Do not log raw access tokens or full payment evidence.

### 9.3 Scheduled jobs

- Finalize eligible reading lists at configured deadlines through the same finalization action.
- Retry failed notifications.
- Expire/revoke unused invitations and guest credentials.
- Generate settlement draft candidates for the approved weekly period; do not automatically approve or pay them.
- Run approved retention/deletion jobs only after retention policy is finalized.
- Check backup/queue/notification health through available hosting monitoring.
- Never auto-complete refunds or payouts from a timer.

---

## 10. Reporting and exports

Reports must be policy-scoped before query execution:
- Central: all authorized church/operational/financial reports.
- Church Admin: only own church and approved columns.
- Lector: assigned Mass operational data only; no financial data.
- User/guest: own order status and confirmation document only.

Formats: PDF and CSV/XLSX as selected in the blueprint. Queue large exports. Store generated files privately, expire them according to policy, and reauthorize before download. Spreadsheet exports must mitigate formula injection for user-controlled strings beginning with `=`, `+`, `-`, or `@`. Report metadata should record requester, filters, time, format, and scope. Do not include unnecessary personal data.

---

## 11. Phase-by-phase implementation and test plan

Tests below are acceptance tests, not a claim that tests have already been executed. Use Laravel PHPUnit or Pest, factories, fake mail/notifications/queues/storage, and MySQL integration tests for database-specific constraints. Use a staging environment for end-to-end browser and recovery tests.

### Phase 1 — Foundation and security

**Deliverables**
- Laravel/Inertia/React baseline, authentication, email verification, locales.
- Initial users/church/membership/invitation/settings/audit migrations.
- Role and resource policies.
- Invitation acceptance, revocation, staff deactivation/session invalidation.
- Audit service and baseline rate limits.

**Test cases**
1. Unauthenticated visitor cannot access staff dashboards.
2. Registered user can register/login/logout; logout invalidates the session.
3. Unverified user cannot access secure order tracking.
4. Expired invitation cannot be accepted.
5. Revoked invitation cannot be accepted.
6. Invitation token is stored hashed and is one-time use.
7. Church Admin can access own church membership list but cannot access another church's list.
8. Lector membership does not grant Church Admin permissions.
9. Revoked/deactivated membership immediately blocks subsequent requests.
10. Central Admin privilege is checked server-side.
11. Sensitive role/membership changes create audit entries with actor, target, reason, and timestamp.
12. Passwords, raw invitation tokens, and secrets never appear in audit logs.
13. Rate limiting applies to login, invite acceptance, and recovery endpoints.
14. Locale switching does not mutate stored user-entered text.
15. Foreign keys and indexes migrate cleanly on the selected MySQL version.
16. Backup restoration procedure can restore the Phase 1 database into an isolated environment.

**Release gate:** automated authorization tests pass; no cross-church access path remains untested for created resources.

### Phase 2 — Church directory and Mass scheduling

**Deliverables**
- Church verification/publication/suspension.
- Public directory and branding.
- Categories, Mass schedules, recurring series/occurrences, assignments.
- Booking windows/cutoffs and conflict checks.
- Staff schedule actions and audit.

**Test cases**
1. Draft/unverified/suspended/deactivated churches do not accept public submissions.
2. Only published churches appear in the public directory.
3. Central Admin can verify and publish a church; action is audited.
4. Church Admin cannot edit another church's profile or branding.
5. Church Admin can create/edit own church Masses.
6. Editing one occurrence does not silently alter other recurring occurrences.
7. Invalid or past booking windows are rejected.
8. Closed Mass remains visible but cannot be selected for submission.
9. New order creation is rejected after cutoff.
10. Assigned lector can close submissions early.
11. Lector cannot extend the cutoff beyond authorized limits.
12. Conflict detection considers configured shared resources/staff and allows unrelated chapel overlaps.
13. Lector cannot view unassigned Mass details by changing the URL ID.
14. Suspending a church blocks new orders but preserves existing orders/payments.
15. Mass cancellation creates a case and preserves original schedule data.
16. Schedule updates and staff assignments create audit events.
17. Church timezone is used consistently in displayed cutoff and schedule times.
18. Concurrent requests around cutoff cannot both bypass the cutoff rule.

**Release gate:** booking rules pass boundary-time and concurrent-request tests.

### Phase 3 — Orders, GCash, guest tracking, and requests

**Deliverables**
- Guest and registered order submission.
- Multiple intentions per order for one church/Mass.
- Pricing configuration guardrails and order snapshots.
- Protected evidence uploads.
- Human payment verification and exceptions.
- Secure tracking/recovery/account linking.
- Correction, cancellation, rescheduling request records.

**Test cases**
1. Registered user creates an order with multiple intentions for one Mass.
2. Guest can submit without an account and receives a secure tracking flow.
3. An order containing intentions for different churches or Masses is rejected.
4. Inactive category is rejected for new submissions; historical label snapshot remains unchanged.
5. Names and wording preserve exact submitted Unicode text.
6. Duplicate-warning logic flags likely duplicates but does not block a legitimate repeat.
7. No approved pricing configuration produces the defined configuration-required behavior; no price is invented.
8. Order and all intention rows roll back if any child creation fails.
9. Mass closing between page load and submit causes server-side rejection.
10. Payment evidence accepts only allowed, validated file types and rejects oversized/malicious files.
11. Evidence storage is private and cannot be fetched without authorization.
12. Evidence submission moves payment to pending verification but never verified.
13. Church Admin cannot verify/reject payment by direct POST.
14. Lector cannot access payment evidence.
15. Central Admin can verify only after explicit action and recording the verified amount/reference.
16. Duplicate reference creates a review flag and the same transaction cannot be counted twice.
17. Underpayment, overpayment, unclear evidence, unmatched reference, and late payment enter review/resolution flow.
18. Rejection requires a reason and produces an appropriate user-facing notice.
19. Retried verification request does not duplicate reading-list entries.
20. Guest tracking rejects order number alone.
21. Guest tracking requires valid credential, verification, and unexpired access.
22. Recovery response does not disclose whether an email exists.
23. Replacing a guest credential invalidates the old credential.
24. Matching email addresses alone cannot link a guest order to a registered account.
25. Owner can edit eligible intentions before payment verification; edit is blocked after verification.
26. Cancellation request does not automatically cancel/refund the order.
27. Rescheduling request does not automatically move an order.
28. Every sensitive payment/order decision creates an audit record.
29. Concurrent Central Admin verification attempts result in one consistent decision.
30. A payment sent after cutoff is held for review rather than silently discarded.

**Release gate:** all payment exception tests pass; evidence is private; no payment can be verified by a non-authorized role.

### Phase 4 — Reading operations

**Deliverables**
- Lector dashboard and assigned-Mass reading lists.
- Entry ordering, finalization/version snapshots, reopening, completion.
- Correction and issue workflows.
- Protected PDF and read-only offline/PWA support.

**Test cases**
1. Reading list contains only eligible confirmed intentions.
2. Unverified order never occupies a confirmed reading-list slot.
3. Lector sees only assigned Masses and only authorized fields.
4. Reordering persists the exact requested order.
5. Duplicate, missing, or foreign entry IDs are rejected during reorder.
6. Reordering a finalized list is rejected.
7. Finalization creates an immutable snapshot/version and audit event.
8. Reopening requires permission and reason; it creates history.
9. Re-finalization creates a new version without deleting the prior snapshot.
10. Correction request does not change wording before approval.
11. Approved correction preserves original wording and records reviewer/reason.
12. Correction to a finalized list requires the reopen/correct/re-finalize path.
13. Completion records actor and timestamp.
14. An assigned lector can correct an entry completion with a reason only before the whole list is completed; the original completion event remains in audit history.
15. One entry can be completed independently of other entries.
16. Whole-list completion is blocked while required completion conditions are unmet.
17. Protected PDF is denied to an unassigned lector and unauthorized user.
18. The already-loaded list displays last sync time and a stale/offline indicator; reconnect triggers an authorized refresh.
19. Offline mode disables reordering, completion, and schedule changes.
20. Offline handling uses only the already-loaded in-memory page: do not persist private reading-list data in browser storage or a service-worker cache; keep it read-only while disconnected and refresh through normal authorization on reconnect. Access revocation cannot be observed until connectivity returns.
21. A scheduled finalization retry does not create duplicate versions or corrupt state.
22. Issue reports expose user-safe status but not internal staff notes.
23. An unverified intention never appears in the lector workbench, even if stale intention status says confirmed.
24. Issue resolution is limited to the assigned lector or same-church Church Admin, preserves the private report, and exclusion does not alter payment/order state.
25. Church Admin pronunciation hints remain separate from submitted wording, are visible only to authorized staff, and are included in the immutable finalized snapshot/PDF.

**Release gate:** list finalization, reopening, correction, and offline stale-data warnings pass end-to-end tests.

### Phase 5 — Finance, support, reporting, privacy

**Deliverables**
- Settlement candidates, configurable calculator, settlement lines, payout records.
- Payment/refund exception administration.
- PDF/CSV/XLSX exports.
- Support routing and event history.
- Privacy request workflow and notification delivery monitoring.

**Test cases**
1. Settlement includes only verified payments.
2. Pending, rejected, and review-required payments are excluded.
3. A payment cannot be included twice in active settlements.
4. Missing approved revenue-sharing configuration yields `configuration_required`.
5. No fixed price, fee, or percentage is seeded or assumed.
6. Calculation uses a versioned approved configuration and stores a reproducible snapshot.
7. Changing a configuration does not alter past order or settlement snapshots.
8. Settlement approval is blocked until review prerequisites are met.
9. Church Admin sees only their church settlement summary.
10. Lector cannot access financial reports.
11. Creating a refund request does not mark it approved or completed.
12. Refund approval does not mark money returned.
13. Refund completion requires actual transfer details and authorized confirmation.
14. Payout preparation does not mark payout completed.
15. Payout completion requires actual transfer details and authorized confirmation.
16. Support ticket routing sends payment/refund issues to Central Admin.
17. Church-specific tickets route to the relevant Church Admin.
18. Technical tickets route to designated technical support.
19. Urgent/unclassified tickets enter Central Admin triage.
20. Internal notes never appear in user-facing ticket views or exports.
21. Notification failure does not reverse a payment or reading state.
22. Notification retries do not create duplicate state transitions.
23. Export is scoped to the requesting user's permissions.
24. CSV/XLSX export mitigates formula injection.
25. Private export files require authorization and expire according to configuration.
26. Privacy request cannot be fulfilled before required identity verification.
27. Retention/deletion job is disabled until approved retention settings exist.
28. Sensitive exports and financial actions are audited.

**Release gate:** settlement cannot be approved with unconfigured business rules; refund/payout completion requires actual transfer evidence.

### Phase 6 — System validation, deployment, and pilot

**Deliverables**
- Full regression suite, security review, accessibility/usability checks.
- Laravel Cloud deployment validation.
- Queue/storage/email/backup checks.
- Restore drill, monitoring, operational runbooks, pilot rollout.

**Test cases**
1. Run the full automated suite against the production-compatible MySQL version.
2. Execute cross-church IDOR tests across orders, intentions, schedules, assignments, evidence, PDFs, support, reports, settlements, and exports.
3. Test simultaneous order submissions at cutoff.
4. Test concurrent payment verification and duplicate references.
5. Test payment upload interruption and safe retry behavior.
6. Test transaction rollback for order creation, verification, reorder, settlement calculation, and refund/payout recording.
7. Test queue retries, failed jobs, and notification recovery.
8. Restore a database backup to an isolated environment and reconcile order/payment relationships.
9. Confirm private storage objects remain private after deployment.
10. Verify session invalidation after staff deactivation and password/security recovery.
11. Test rate limits and generic recovery responses.
12. Test keyboard navigation, visible focus, labels, validation errors, status announcements, contrast, and mobile layouts.
13. Test English, Filipino, and Cebuano UI switching without translating saved intention content.
14. Test PWA installability where supported and offline stale-list handling.
15. Verify logs do not contain passwords, raw guest tokens, or unnecessary private intention/payment data.
16. Verify no real payment, refund, or payout is represented as completed without explicit human recording.
17. Conduct pilot with a limited number of verified churches and staff.
18. Confirm documented support routing, payment reconciliation, Mass cancellation, and settlement procedures before broad rollout.

**Release gate:** complete the blueprint's minimum release gates, obtain legal/privacy review, confirm centralized GCash reconciliation and payout procedures, and obtain explicit sign-off for business rules that affect money.

---

## 12. Automated test architecture

Recommended test layers:
- **Unit tests:** state-transition services, pricing calculator, settlement calculator, duplicate detection, cutoff calculation, guest token verification.
- **Feature tests:** routes, validation, policies, transactions, notifications, file authorization.
- **Integration tests:** MySQL constraints, row locking/concurrency, queue behavior, private storage, export generation.
- **Browser/end-to-end tests:** guest submission/tracking, staff payment review, reading-list finalization, correction and cancellation flows.
- **Operational tests:** backup restore, failed notification replay, retention job dry run, incident response tabletop.

Use factories for at least: Central Admin, Church Admin, Lector, registered user, two churches, assigned/unassigned Masses, order states, payment states, reading-list states, settlement states, and guest credentials.

For every state-changing action, test:
1. permitted actor + valid current state succeeds;
2. forbidden actor receives 403/404;
3. invalid transition is rejected;
4. stale/concurrent request cannot overwrite a newer state;
5. audit event is created;
6. notification is queued only after commit;
7. failure rolls back related database changes.

Do not use SQLite as the only test database for production-critical behavior. Run integration tests on the same MySQL major version and relevant SQL mode as deployment.

---

## 13. Security, privacy, and operational requirements

### 13.1 Required safeguards
- CSRF protection for browser state-changing requests.
- Rate limiting for authentication, guest recovery, evidence submission, and sensitive staff actions.
- Strong password hashing and secure session/cookie settings.
- Email verification before secure tracking access.
- High-entropy, hashed, expiring, revocable guest/invitation credentials.
- Private storage for evidence, PDFs, and exports.
- Server-side MIME/file validation, upload size limits, randomized storage keys, and safe file delivery.
- Audit records for financial decisions, staff changes, schedule overrides, list finalization/reopening, privacy actions, and security incidents.
- Least privilege for technical support.
- Secrets stored in deployment secret configuration, not database settings or source control.
- No raw access tokens or credentials in application logs.
- Session invalidation when staff access is revoked.
- Daily backups and restoration tests; point-in-time recovery only if confirmed by the chosen hosting configuration.
- Data retention and deletion jobs remain disabled until approved periods and legal requirements are documented.

### 13.2 Privacy/legal launch checklist
Before production, the responsible organization must validate:
- Privacy notice and consent/acknowledgement wording.
- Data-controller/processor responsibilities and vendor arrangements.
- Data access/correction/deletion request process.
- Legal retention requirements for financial and operational records.
- Payment evidence retention and secure deletion.
- Breach assessment and notification procedures under applicable Philippine law.
- Whether the generated payment confirmation has any tax/accounting implications.
- Appropriate access to religious/intention-related personal data and minimization controls.

This document is an engineering specification, not legal advice or a determination of compliance.

---

## 14. Deployment and environment checklist

- Separate local, staging, and production environments.
- Production secrets stored in Laravel Cloud environment configuration.
- HTTPS enabled; secure cookies and trusted proxy settings validated.
- Database migrations run through a controlled deployment process with backups and rollback planning.
- Queue workers and scheduler configured and monitored.
- Private object storage configured for evidence, PDFs, and exports.
- Public assets separated from private files.
- Email provider configured with sender authentication and delivery monitoring.
- Error reporting configured to redact sensitive fields.
- Database backups automated; restoration periodically tested.
- Health checks and basic operational alerts configured.
- PWA service worker excludes sensitive pages, payment forms, guest credentials, and private API responses from unsafe caching.
- **Project decision (2026-10-31):** Do not persist private reading-list data in browser storage or a service-worker cache. An already-loaded page may remain visible in memory, read-only, during connection loss; reload/reopen while offline is unsupported. On reconnect, refresh through normal server authorization. Access revocation cannot be observed until connectivity returns.
- Confirm the actual Laravel Cloud limits and behavior for database backups, storage, queues, scaling, and recovery; do not promise unverified uptime or recovery targets.

---

## 15. Definition of done

A feature is not done merely because its screen exists. It is complete only when:
- Migrations, constraints, and indexes are implemented.
- Server-side policies and tenant isolation are tested.
- Valid and invalid state transitions are covered.
- Audit history is written for sensitive actions.
- Notifications are dispatched safely after commit where required.
- File access is private and authorized.
- Loading, empty, error, and permission-denied states are handled in Inertia/React.
- Relevant English/Filipino/Cebuano UI strings are available.
- User-entered intention content is preserved.
- Unit/feature/integration tests pass on MySQL.
- Operational documentation is updated.
- Any undecided business rule is explicitly blocked/configurable, not silently assumed.

---

## 16. Implementation order and immediate next actions

1. Confirm whether the project is greenfield and which Laravel version/authentication starter kit is selected.
2. Finalize the enum/state vocabulary before generating migrations.
3. Build Phase 1 schema, policies, and authorization tests first.
4. Implement church/Mass scoping before order/payment screens.
5. Implement order creation and payment verification as domain actions, not controller-only logic.
6. Build reading lists only from confirmed intentions.
7. Build settlement infrastructure with no active default pricing/revenue-sharing values.
8. Complete exception, concurrency, and cross-church tests before pilot.
9. Obtain business/legal sign-off for pricing, revenue share, refund policy, retention, and operational deadlines before enabling those decisions in production.

**Non-negotiable acceptance principle:** the system must never invent pricing/revenue-sharing values, expose another church's private data, count a payment twice, or mark a refund/payout complete without recording the actual transfer.
