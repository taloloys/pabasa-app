# Pabasa System — Performance Optimization Roadmap

**Project:** Multi-Church Reading System (Pabasa)  
**Backend:** Laravel  
**Frontend:** React 19 + Inertia.js 2.x  
**Database:** MySQL 8.4  
**Development Environment:** Docker on Windows  
**Local Application URL:** `http://localhost:8000`  
**Development Frontend:** Vite  
**Document Purpose:** Central optimization plan and progress tracker for AI-assisted development.

---

# 1. Optimization Objectives

The primary objective is to make the application responsive, reliable, maintainable, and efficient in both local development and production environments.

The optimization effort must address:

1. Backend response time.
2. Database query performance.
3. Frontend rendering and navigation.
4. JavaScript and CSS loading.
5. Docker and local development performance.
6. Asset and font loading.
7. Application caching.
8. Code maintainability and development simplicity.
9. Performance regression prevention.

Performance improvements must not compromise existing business logic, security, multi-church data isolation, or system functionality.

## Architectural Direction

The current architecture will remain:

- Laravel backend.
- React 19 frontend.
- Inertia.js 2.x for communication between Laravel and React.
- MySQL 8.4 database.
- Vite for frontend asset compilation.
- Docker for local development.

**Do not migrate the entire frontend to Blade as part of the initial optimization work.**

Blade may be considered for selected content-oriented public pages in a later architectural evaluation. Inertia SSR should also be evaluated before making that decision.

Any architectural migration must be supported by actual performance measurements and a clear maintenance benefit.

---

# 2. Current Performance Findings

The following findings were reported during previous investigations. They must be treated as reported findings until independently verified.

## 2.1 PHP and Docker Optimization

**Reported status:** Implemented.

Changes previously reported:

- Enabled PHP OPcache for CLI.
- Added an OPcache configuration file.
- Configured PHP realpath cache.
- Mounted the OPcache configuration into the Docker application container.
- Rebuilt the application container.

Reported configuration:

```ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=0
opcache.save_comments=1
realpath_cache_size=16M
realpath_cache_ttl=600
```

Reported benchmark results:

| Endpoint | Before | After |
|---|---:|---:|
| GET / | 3.336s | 2.153s |
| GET /churches | 3.465s | 2.150s |

These measurements need to be repeated using a consistent benchmarking methodology.

## 2.2 Frontend Rendering

**Reported status:** Investigated; optimization pending.

The initial HTML response reportedly contains an empty React mount point:

```html
<div id="app" data-page="..."></div>
```

Consequently, the browser must load and execute JavaScript before displaying the actual React-rendered page content.

The following were also reported:

- React client-side rendering contributes to initial page display time.
- Vite development mode serves unbundled JavaScript modules.
- Bunny Fonts is loaded through an external stylesheet.
- Font requests begin after the React-rendered content appears.

## 2.3 Backend Response Time

**Reported status:** Needs further investigation.

Previous measurements reported:

| Request type | Reported TTFB |
|---|---:|
| Cold document request | 4.667s |
| Warm document request | 3.381s |
| Inertia navigation request | 2.876s |

These measurements differ from the earlier benchmark results. They must not be directly compared without checking the measurement conditions.

The high response times across different pages suggest that shared backend components may be contributing to the slowdown.

## 2.4 Database Incident

**Status:** Requires immediate verification.

During previous testing, the development database reportedly became empty.

The investigation identified a possible PHPUnit configuration issue involving the `testing` database environment and Laravel's cached configuration.

The following facts must be considered:

- The development database previously contained one church and seeded users.
- No database backup was available.
- A previous Phase 1 execution log mentioned reseeding the development database.
- A later Phase 2A report stated that no seeders were executed during that phase.
- The exact sequence of database operations has not yet been conclusively reconciled.

**Important:** Do not run migrations, seeders, database resets, or destructive commands until the database state and recovery procedure have been reviewed and approved.

---

# 3. Optimization Phases

## Phase 0 — Project Safety and Baseline Preparation

**Priority:** Critical  
**Status:** COMPLETED (Verified on 2026-10-31)

A safe and strictly isolated development environment has been established.

### Tasks

- [x] Inspect the current Docker Compose configuration.
- [x] Verify the active application container and PHP configuration.
- [x] Inspect the current MySQL database and table contents.
- [x] Verify the database volume and persistence configuration.
- [x] Inspect the migration status.
- [x] Review DatabaseSeeder and all nested seeders.
- [x] Determine whether the original church and user records can be recreated safely.
- [x] Create a database backup before making further changes (`storage/app/backups/pabasa_dev_backup.sql`).
- [x] Document the current database recovery procedure (`docs/backup_restore_procedure.md`).
- [x] Review Git status and preserve existing uncommitted changes.
- [x] Establish a dedicated testing database (`testing`).
- [x] Establish a dedicated MySQL testing user (`pabasa_test`) with permissions limited strictly to `testing.*`.
- [x] Verify that automated tests cannot modify the development database (permission isolation + `TestCase::assertTestingEnvironmentSafe` guard).

### Testing Database Requirements

The testing environment does not depend solely on `.env.testing` or PHPUnit environment variables for safety:
1. **Database-level privilege isolation:** MySQL user `pabasa_test` has privileges on `testing.*` only. Attempts to access `pabasa` fail with MySQL `ERROR 1044 (42000): Access denied`.
2. **Runtime guard in `tests/TestCase.php`:** Halts test execution immediately if configuration is cached (`$app->configurationIsCached()`) or if the database is not `testing`.
3. **Automated Docker provisioning:** Initialized via `docker/mysql/init/01-testing-db.sql` for all teammate environments.

### Completion Criteria

- [x] Development database state is understood.
- [x] A backup exists and can be verified (`storage/app/backups/pabasa_dev_backup.sql`).
- [x] Testing and development databases are isolated at both MySQL user level and Laravel framework level.
- [x] Existing seeded data can be restored through a reviewed procedure.
- [x] No destructive database operations are performed without explicit approval.

---

## Phase 1 — PHP Runtime and Docker Optimization

**Priority:** High  
**Status:** Previously implemented; verification pending

### Tasks

- [x] Add PHP OPcache configuration.
- [x] Enable OPcache for CLI.
- [x] Configure realpath caching.
- [x] Rebuild the application container.
- [ ] Verify that the current container uses the intended configuration.
- [ ] Confirm OPcache is active.
- [ ] Verify that PHP configuration changes are applied correctly.
- [ ] Measure performance after a fresh container restart.
- [ ] Investigate Docker bind-mount overhead on Windows.
- [ ] Evaluate alternative development file-sharing configurations only if measurements justify them.
- [ ] Verify that optimization does not interfere with automatic code changes during development.

### Important Considerations

Do not disable timestamp validation blindly in the development environment.

Development must continue to recognize code changes without requiring unnecessary container rebuilds.

### Completion Criteria

- PHP configuration is verified.
- Docker runtime configuration is documented.
- Performance measurements are reproducible.
- Application code changes continue to work normally.

---

## Phase 2 — Reproducible Performance Benchmarking

**Priority:** Critical  
**Status:** Pending

Establish a consistent performance measurement process before modifying application logic.

### Tasks

- [ ] Establish a baseline for the homepage.
- [ ] Establish a baseline for the church directory.
- [ ] Establish a baseline for church detail pages.
- [ ] Establish a baseline for reading/order submission pages.
- [ ] Establish a baseline for customer tracking pages.
- [ ] Establish a baseline for administrative dashboards.
- [ ] Establish a baseline for church management dashboards.
- [ ] Measure full-document navigation separately from Inertia navigation.
- [ ] Record TTFB.
- [ ] Record total response time.
- [ ] Record downloaded JavaScript and CSS size.
- [ ] Record LCP and CLS for full-document navigations.
- [ ] Record relevant frontend rendering and navigation timings.
- [ ] Perform cold-cache and warm-cache measurements.
- [ ] Use the same environment, endpoints, and methodology for before-and-after comparisons.

### Benchmarking Rules

Separate these environments:

1. Local development using Vite.
2. Local production-like environment using a compiled Vite build.
3. Production deployment, when available.

Do not use development-server measurements as the sole indicator of production performance.

Do not compare curl TTFB with browser LCP as if they measure the same thing.

For Inertia navigation, measure the XHR response time and client-side rendering separately.

### Completion Criteria

Produce a benchmark report containing:

- Endpoint.
- Test environment.
- Cold or warm request.
- Number of test repetitions.
- Median response time.
- Minimum and maximum response times.
- TTFB.
- LCP where applicable.
- CLS where applicable.
- Relevant network and rendering observations.

---

## Phase 3 — Laravel Backend Performance Investigation

**Priority:** Critical  
**Status:** Pending

Investigate why Laravel responses are taking several seconds even on localhost.

### Tasks

- [ ] Profile Laravel application boot time.
- [ ] Inspect service providers.
- [ ] Inspect global and route middleware.
- [ ] Inspect authentication and authorization middleware.
- [ ] Inspect session handling.
- [ ] Inspect CSRF processing.
- [ ] Inspect shared Inertia middleware and global shared props.
- [ ] Inspect service container bindings and expensive singleton initialization.
- [ ] Inspect synchronous external API calls.
- [ ] Inspect filesystem operations during requests.
- [ ] Inspect logging overhead.
- [ ] Inspect database connection establishment time.
- [ ] Inspect unnecessary queries executed on every request.
- [ ] Identify expensive controller operations.
- [ ] Identify repeated computation across requests.
- [ ] Measure the effect of Laravel configuration, route, and event caching.

### Optimization Opportunities

Where justified by profiling:

- Reduce unnecessary middleware work.
- Avoid repeated database lookups.
- Cache expensive, relatively stable data.
- Defer nonessential operations.
- Avoid unnecessary external service calls during page requests.
- Reduce expensive application initialization.
- Use appropriate Laravel caching mechanisms.

### Completion Criteria

- The main contributors to backend latency are identified.
- Each significant bottleneck has supporting measurements.
- Optimizations are implemented incrementally.
- Before-and-after results are recorded.
- Existing functionality remains intact.

---

## Phase 4 — Database Query and Schema Optimization

**Priority:** High  
**Status:** Pending

Investigate inefficient database operations across the application.

### Tasks

- [ ] Inspect queries generated by important controllers.
- [ ] Identify N+1 query problems.
- [ ] Review eager loading relationships.
- [ ] Identify unnecessary database queries.
- [ ] Inspect unpaginated records.
- [ ] Review pagination strategies.
- [ ] Inspect filtering and sorting queries.
- [ ] Review indexes for frequently queried columns.
- [ ] Inspect foreign-key relationships.
- [ ] Review multi-column indexes for common query combinations.
- [ ] Examine slow queries using query logging or MySQL profiling tools.
- [ ] Check whether large collections are unnecessarily loaded into memory.
- [ ] Review database transaction usage.
- [ ] Verify that indexes do not introduce unnecessary write overhead.

### Multi-Church Data Isolation

Database optimization must preserve tenant isolation.

Verify that:

- Church-specific queries are properly scoped.
- Users cannot retrieve records belonging to another church without authorization.
- Relationships and joins preserve the intended church ownership.
- Caching does not mix data between churches.
- Reports and exports use the correct church context.

### Completion Criteria

- Critical queries are documented.
- N+1 problems are addressed.
- Appropriate indexes are implemented.
- Large datasets use suitable pagination.
- Multi-church isolation tests pass.

---

## Phase 5 — Laravel and Inertia Data Transfer Optimization

**Priority:** High  
**Status:** Pending

Reduce unnecessary data transferred between Laravel and React.

### Tasks

- [ ] Review all controllers returning Inertia responses.
- [ ] Inspect global shared Inertia props.
- [ ] Identify unnecessarily large page props.
- [ ] Avoid sending data that a page does not use.
- [ ] Review nested relationships included in responses.
- [ ] Implement pagination where appropriate.
- [ ] Evaluate lazy and deferred props where supported and useful.
- [ ] Review frequently repeated data.
- [ ] Avoid recalculating identical data unnecessarily.
- [ ] Inspect JSON response sizes.
- [ ] Review the use of Laravel Collections in Inertia props.
- [ ] Verify serialization behavior before changing Collections to arrays.
- [ ] Check whether caching can reduce repeated computation safely.

### Important Considerations

Do not convert every Collection to an array automatically.

First identify an actual serialization problem or measurable performance benefit.

All Inertia data must remain compatible with the existing React components.

### Completion Criteria

- Unnecessary props are removed.
- Large responses are reduced where practical.
- Page-specific data loading is improved.
- Existing frontend functionality remains intact.

---

## Phase 6 — React and Frontend Rendering Optimization

**Priority:** High  
**Status:** Pending

Investigate frontend performance independently from backend response time.

### Tasks

- [ ] Inspect React component rendering behavior.
- [ ] Identify unnecessary component re-renders.
- [ ] Inspect large component trees.
- [ ] Identify expensive filtering and sorting operations.
- [ ] Review React state management.
- [ ] Review unnecessary effects.
- [ ] Optimize expensive calculations where justified.
- [ ] Review the use of memoization.
- [ ] Review large tables and lists.
- [ ] Implement pagination or virtualization where appropriate.
- [ ] Inspect duplicated component logic.
- [ ] Review page-level JavaScript dependencies.
- [ ] Ensure only necessary page components and assets are loaded.

### Important Considerations

Do not add `React.memo`, `useMemo`, or `useCallback` everywhere.

Apply these techniques only when profiling identifies unnecessary work or when they prevent a measurable performance problem.

### Completion Criteria

- Unnecessary rendering is reduced.
- Large lists remain responsive.
- Interactive controls work correctly.
- React optimization does not introduce stale state or functional regressions.

---

## Phase 7 — Vite, JavaScript, CSS, and Asset Optimization

**Priority:** High  
**Status:** Pending

Optimize frontend assets and distinguish development performance from production performance.

### Tasks

- [ ] Generate a production Vite build.
- [ ] Verify production asset loading.
- [ ] Inspect generated JavaScript bundles.
- [ ] Identify large dependencies.
- [ ] Review page-level code splitting.
- [ ] Remove unused dependencies where safe.
- [ ] Remove unused JavaScript and CSS.
- [ ] Review Tailwind CSS output.
- [ ] Optimize large images.
- [ ] Use appropriate image formats and dimensions.
- [ ] Configure asset caching for production.
- [ ] Verify that production pages do not depend on the Vite development server.
- [ ] Compare development and production-like performance.

### Completion Criteria

- Production build succeeds.
- Assets load correctly.
- Unnecessary JavaScript and CSS are reduced.
- Production performance is measured separately from development performance.

---

## Phase 8 — Font and Initial Page Rendering Optimization

**Priority:** Medium-High  
**Status:** Pending

Investigate the impact of external fonts and client-side rendering on initial page display.

### Tasks

- [ ] Inspect the current Bunny Fonts stylesheet.
- [ ] Identify fonts actually used by the application.
- [ ] Identify required font weights and styles.
- [ ] Evaluate self-hosting fonts.
- [ ] Verify font licensing before self-hosting.
- [ ] Ensure appropriate `font-display` behavior.
- [ ] Preload only critical fonts when justified.
- [ ] Avoid unnecessary font variants.
- [ ] Measure font loading impact on LCP.
- [ ] Inspect initial HTML rendering behavior.
- [ ] Evaluate Inertia SSR for selected pages.
- [ ] Compare SSR and client-side rendering performance.

### Important Considerations

Do not preload every font.

Do not use arbitrary nonblocking stylesheet techniques without verifying that they work correctly.

The initial HTML and font-loading strategy should be evaluated together.

### Completion Criteria

- Font loading is optimized.
- Initial rendering is measured.
- LCP improves where font loading was a verified bottleneck.
- No significant font flashing or layout instability is introduced.

---

## Phase 9 — Rendering Architecture Evaluation: React, Blade, and Inertia SSR

**Priority:** Medium  
**Status:** Pending architectural decision

Evaluate whether the current frontend architecture is appropriate for each system area.

### Current Direction

Keep React + Inertia for the existing application while optimization work is underway.

### Evaluate Blade for

- Public church directory.
- Public church information pages.
- Other mostly static content pages.

### Keep React + Inertia for

- Customer order submission.
- Customer order tracking.
- Administrative dashboards.
- Church management dashboards.
- Interactive forms.
- Dynamic tables and analytics.

### Evaluate Inertia SSR for

- Public church pages.
- Initial page rendering.
- Pages where search engine indexing and initial HTML delivery are important.

### Comparison Requirements

Compare:

1. Initial page response.
2. LCP.
3. JavaScript transfer size.
4. Navigation performance.
5. Development complexity.
6. Maintainability.
7. Deployment complexity.
8. Existing component reuse.
9. Long-term maintenance cost.

### Completion Criteria

Produce an architecture recommendation supported by measurements.

Do not migrate the entire frontend solely because Blade is simpler or because React currently renders slowly.

If a hybrid architecture is recommended, document the boundaries between Blade and Inertia pages and ensure that authentication, routing, layouts, and navigation remain consistent.

---

## Phase 10 — Docker and Local Development Workflow Optimization

**Priority:** Medium  
**Status:** Pending

Improve the developer experience for the team.

### Tasks

- [ ] Review Docker Compose configuration.
- [ ] Review application container startup time.
- [ ] Inspect unnecessary running services.
- [ ] Review Docker bind mounts.
- [ ] Investigate Windows filesystem performance.
- [ ] Review MySQL container configuration.
- [ ] Review Vite host and networking configuration.
- [ ] Ensure teammates can start the project consistently.
- [ ] Document development commands.
- [ ] Document database initialization.
- [ ] Document backup and restore procedures.
- [ ] Document production build commands.
- [ ] Verify environment variable handling.
- [ ] Ensure sensitive credentials are not committed to Git.

### Completion Criteria

- The application starts reliably.
- Teammates can reproduce the environment.
- Docker configuration is documented.
- Local development remains convenient.
- No unnecessary services or processes are introduced.

---

## Phase 11 — Application Caching Strategy

**Priority:** Medium  
**Status:** Pending

Identify data and application operations that benefit from caching.

### Tasks

- [ ] Review Laravel configuration caching.
- [ ] Review route caching.
- [ ] Review event caching.
- [ ] Review application data caching.
- [ ] Review repeated public directory queries.
- [ ] Review frequently accessed church information.
- [ ] Review expensive dashboard calculations.
- [ ] Review cache invalidation.
- [ ] Verify cache isolation between churches.
- [ ] Review session and cache storage configuration.
- [ ] Establish appropriate production cache settings.

### Important Considerations

Do not blindly cache dynamic or user-specific information.

Cache keys must include the correct church context when caching church-specific data.

Development and testing environments must not rely on stale cached configuration.

### Completion Criteria

- Appropriate caching opportunities are identified.
- Cache invalidation is reliable.
- Multi-church data isolation is preserved.
- Cached configuration does not interfere with automated testing.

---

## Phase 12 — Regression Testing and Performance Validation

**Priority:** Critical  
**Status:** Pending

Verify that optimization does not break existing features.

### Tasks

- [ ] Run existing automated tests using the isolated testing database.
- [ ] Test authentication and authorization.
- [ ] Test customer order submission.
- [ ] Test order tracking.
- [ ] Test payment-related workflows.
- [ ] Test cancellation requests.
- [ ] Test rescheduling requests.
- [ ] Test administrative workflows.
- [ ] Test church management workflows.
- [ ] Test report generation and exports.
- [ ] Test multi-church access restrictions.
- [ ] Test frontend navigation.
- [ ] Test responsive layouts.
- [ ] Test production build.
- [ ] Repeat performance benchmarks.
- [ ] Compare results with the original baseline.

### Completion Criteria

- Critical functionality works.
- Automated tests pass.
- Multi-church isolation is verified.
- Production assets work.
- Performance improvements are supported by repeatable measurements.

---

# 4. Optimization Priority

The phases should be executed in the following order:

| Priority | Phase | Focus |
|---|---|---|
| Critical | Phase 0 | Database safety and environment |
| High | Phase 1 | PHP and Docker verification |
| Critical | Phase 2 | Performance baseline |
| Critical | Phase 3 | Backend latency |
| High | Phase 4 | Database optimization |
| High | Phase 5 | Inertia data transfer |
| High | Phase 6 | React rendering |
| High | Phase 7 | Vite and assets |
| Medium-High | Phase 8 | Fonts and initial rendering |
| Medium | Phase 9 | Blade vs React vs SSR evaluation |
| Medium | Phase 10 | Docker developer experience |
| Medium | Phase 11 | Caching strategy |
| Critical | Phase 12 | Regression and final validation |

The priority indicates importance, not permission to skip dependencies. For example, database safety and a reproducible baseline must be established before risky database changes.

---

# 5. Rules for AI-Assisted Optimization

Any AI coding agent working on this project must follow these rules.

## Before Making Changes

1. Inspect the relevant existing implementation.
2. Read this roadmap.
3. Identify the current phase.
4. Review the Git working tree.
5. Identify affected files.
6. Explain the proposed changes.
7. Identify possible regressions.
8. Establish how the changes will be measured.

## During Implementation

1. Work on one optimization area at a time.
2. Avoid unrelated refactoring.
3. Preserve existing business logic.
4. Preserve existing API and Inertia contracts unless changes are explicitly approved.
5. Preserve multi-church data isolation.
6. Avoid introducing unnecessary dependencies.
7. Avoid premature optimization.
8. Do not delete existing functionality to improve performance.
9. Do not execute destructive database commands without explicit approval.

## Database Safety Rules

Never execute the following without explicit approval:

- `migrate:fresh`
- `db:wipe`
- `migrate:reset`
- `db:seed`
- `migrate --seed`
- SQL `DROP DATABASE`
- SQL `DROP TABLE`
- SQL `TRUNCATE`
- Docker volume deletion
- Any command that could reset or overwrite development data.

Before any approved database modification:

1. Confirm the target database.
2. Verify the active Laravel database configuration.
3. Verify that the configuration cache is not pointing to an unintended database.
4. Create and verify a backup.
5. Explain the expected changes.
6. Wait for approval.

## After Making Changes

Every optimization phase must produce:

- Summary of changes.
- Files modified.
- Reason for each modification.
- Commands executed.
- Tests performed.
- Test results.
- Before-and-after performance measurements where applicable.
- Remaining issues.
- Risks or regressions discovered.
- Updated roadmap status.

Do not mark a phase complete merely because code changes were made. Completion requires verification against that phase's criteria.

---

# 6. Progress Tracking

Maintain the following status labels:

- **NOT STARTED:** No investigation or implementation yet.
- **INVESTIGATING:** Collecting evidence and identifying bottlenecks.
- **IN PROGRESS:** Approved changes are being implemented.
- **IMPLEMENTED:** Changes are made but not fully verified.
- **VERIFICATION REQUIRED:** Implementation exists but needs additional testing.
- **COMPLETED:** Acceptance criteria have been met.
- **BLOCKED:** Progress depends on an unresolved issue or approval.

Every phase must have a recorded status.

Update this document after each completed optimization session.

Do not remove previous benchmark results. Keep historical results so improvements and regressions can be compared.

---

# 7. Current Starting Point for the Next Optimization Session

Based on the previous investigations:

1. Phase 1 PHP/OPcache changes were reportedly implemented.
2. Phase 2A browser performance diagnostics were reportedly completed.
3. The application still experiences noticeable lag across all pages.
4. Backend TTFB remains a major concern.
5. Vite development mode and client-side React rendering may contribute to initial rendering delays.
6. External font loading may contribute to LCP.
7. Database safety and test database isolation remain unresolved.
8. The existing frontend architecture has not been approved for migration.

**Next recommended action:**

Complete Phase 0 database and environment safety verification, then establish a reliable Phase 2 performance baseline.

Afterward, proceed to Phase 3 backend profiling before making broad frontend architectural changes.

---

# 8. Definition of Overall Success

The optimization project is considered successful when:

- All major pages respond consistently and feel responsive.
- Backend response times are substantially lower than the original baseline.
- Initial page rendering is improved.
- Inertia navigation is responsive.
- Production JavaScript and CSS are optimized.
- Database queries are efficient.
- Docker local development remains convenient.
- Development and testing databases are isolated.
- No business logic or multi-church security behavior is broken.
- Automated tests pass.
- Performance measurements are reproducible.
- The architecture remains understandable and maintainable for the development team.

**Final principle: Measure first, optimize the proven bottleneck, and verify the result.**

