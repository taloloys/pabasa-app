# Pabasa — Multi-Church Reading-Intention System

> A multi-tenant church reading-intention management platform for Catholic parishes and dioceses.

Churches publish Mass schedules, parishioners (registered or guest) submit prayer intentions with GCash screenshot payment, Central Admin verifies payments, lectors manage reading lists during Mass, and settlements/payouts are tracked with full financial audit trails.

---

## Documentation

- **Canonical Specification:** [`multi_church_reading_system_development_specification.md`](multi_church_reading_system_development_specification.md) — The single source of truth for business rules, data models, state machines, and workflows.
- **Development Phase Tracker:** [`DEV_PHASE.md`](DEV_PHASE.md) — Step-by-step implementation roadmap and deliverables from Phase 0 to Phase 6.

---

## Tech Stack

| Layer | Technology | Version |
|---|---|---|
| **Backend** | Laravel | 13.x |
| **Runtime** | PHP | 8.5 |
| **Frontend** | React + Inertia.js | React 19, Inertia 2.x |
| **Styling** | Tailwind CSS | 4.x |
| **Build Tool** | Vite | 8.x |
| **Database** | MySQL | 8.4 |
| **Cache & Queue** | Redis | 7.x |
| **Testing** | PHPUnit | 12.x |
| **Code Style** | Laravel Pint | Latest |

---

## Core Roles & Capabilities

- **Central Admin (Global):** Validates and verifies GCash payment screenshots, handles refunds and disputes, manages church verification, and oversees platform financial settlements.
- **Church Admin (Per-Church):** Configures church profile and branding, custom intention categories, Mass schedules, cutoff times, and assigns lectors to specific Masses.
- **Lector (Per-Church & Per-Mass):** Manages the live prayer intention list during assigned Masses, reorders items, logs pronunciation corrections, and exports PDF reading lists.
- **Registered User (Own Resources):** Submits prayer intentions, tracks order progress, views payment status history, and manages profile settings.
- **Guest (Single Order):** Submits intentions without account registration, tracks order status using secure expiring tokens, and recovers access via email challenges.

---

## Getting Started

### Prerequisites

- [Docker Desktop](https://www.docker.com/) (recommended)
- Alternatively: PHP 8.5, Composer, Node.js 22+, MySQL 8.4, and Redis 7 installed locally.

### Development with Docker (Recommended)

1. **Clone the repository:**
   ```bash
   git clone https://github.com/taloloys/pabasa-app.git
   cd pabasa-app
   ```

2. **Set up environment:**
   ```bash
   cp .env.example .env
   ```

3. **Start Docker containers:**
   ```bash
   docker compose up -d
   ```
   *Services exposed:*
   - Application: `http://localhost:8000`
   - Vite Dev Server (HMR): `http://localhost:5173`
   - MySQL: `127.0.0.1:3307`
   - Redis: `127.0.0.1:6379`

4. **Initialize application:**
   ```bash
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   ```

5. **Build frontend assets (or run Vite dev):**
   ```bash
   npm install
   npm run build
   # Or run Vite dev server:
   npm run dev
   ```

---

## Development Commands

```bash
# Run tests (always against MySQL, never SQLite)
docker compose exec app php artisan test

# Run specific test suites
docker compose exec app php artisan test --testsuite=Feature
docker compose exec app php artisan test --testsuite=Unit

# Check code formatting (Pint)
docker compose exec app ./vendor/bin/pint --test

# Fix code formatting
docker compose exec app ./vendor/bin/pint

# Run queue worker
docker compose exec app php artisan queue:work

# Run Laravel scheduler
docker compose exec app php artisan schedule:run
```

---

## Key Architectural Principles

1. **Strict Tenant Isolation:** Every church-owned record is scoped by `church_id`. Cross-tenant requests return `404 Not Found` (rather than `403 Forbidden`) to prevent resource enumeration.
2. **Explicit Verification Gate:** Orders are never automatically confirmed upon screenshot upload; explicit Central Admin verification is required with transactional row locking (`lockForUpdate()`).
3. **Decimal-Safe Financials:** All monetary amounts use `DECIMAL(12,2)` in the database and `bcmath` in PHP arithmetic. Floating-point math is strictly forbidden.
4. **Append-Only Auditing:** Sensitive state transitions (payments, schedule changes, reading list adjustments, payouts) record immutable audit entries with actor, action, timestamp, and justification.
5. **Localization:** Fully translatable interface supporting English (`en`), Filipino (`fil`), and Cebuano (`ceb`). User-submitted intention names and requests are preserved verbatim.

---

## License

This project is proprietary software developed for the Pabasa multi-church reading-intention network.
