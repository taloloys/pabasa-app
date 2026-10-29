# Pabasa Database Backup & Restoration Procedure (MySQL 8.4)

## Overview

This document specifies the authoritative procedure for backing up and restoring the Pabasa multi-church database on MySQL 8.4.

---

## 1. Backup Procedure (Logical Dump)

To perform an atomic, consistent backup of the database:

```bash
# In Docker environment
docker compose exec mysql mysqldump \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --set-gtid-purged=OFF \
  -u pabasa -plocal_dev_password pabasa > storage/app/backups/pabasa_backup_$(date +%Y%m%d_%H%M%S).sql
```

### Key Flags:
- `--single-transaction`: Ensures snapshot consistency across InnoDB tables without table locks.
- `--quick`: Streams rows directly rather than buffering the entire table in memory.
- `--routines` & `--triggers`: Backs up stored routines and triggers.

---

## 2. Restoration Procedure into Isolated Environment

To restore the backup into a clean or isolated environment (e.g., staging or disaster recovery):

```bash
# 1. Create isolated database
docker compose exec mysql mysql -u pabasa -plocal_dev_password -e "DROP DATABASE IF EXISTS pabasa_isolated; CREATE DATABASE pabasa_isolated CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Restore schema and data from dump
docker compose exec -T mysql mysql -u pabasa -plocal_dev_password pabasa_isolated < storage/app/backups/pabasa_backup.sql

# 3. Verify integrity
docker compose exec mysql mysql -u pabasa -plocal_dev_password -e "USE pabasa_isolated; SHOW TABLES; SELECT count(*) FROM users; SELECT count(*) FROM churches;"
```

---

## 3. Verification Checklist

1. All foreign keys and unique constraints are restored without syntax errors.
2. Tables match the Phase 1 schema (`users`, `churches`, `church_memberships`, `invitations`, `system_settings`, `audit_logs`).
3. App can connect to the restored database and run migrations with `Nothing to migrate`.
