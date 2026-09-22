# MEDIQ — Production Deployment & Disaster Recovery Architecture

## 1. Production Deployment Topology

```
                         Internet (HTTPS / Port 443)
                                      │
                                      ▼
                        ┌───────────────────────────┐
                        │      Nginx Web Server     │
                        │  - SSL Termination        │
                        │  - Security Headers       │
                        │  - Static Asset Caching   │
                        │  - Rate Limiting Zones    │
                        └─────────────┬─────────────┘
                                      │
                    ┌─────────────────┼─────────────────┐
                    │                 │                 │
                    ▼                 ▼                 ▼
          ┌──────────────────┐┌───────────────┐┌────────────────┐
          │  PHP-FPM 8.2+    ││  Doctor Web   ││   Admin Web    │
          │  REST API Pool   ││  Static/PHP   ││   Static/PHP   │
          └─────────┬────────┘└───────┬───────┘└────────┬───────┘
                    │                 │                 │
                    └─────────────────┼─────────────────┘
                                      │
                                      ▼
                        ┌───────────────────────────┐
                        │       MySQL 8.0+          │
                        │  - InnoDB Buffer Pool 4GB │
                        │  - utf8mb4 / strict mode  │
                        │  - Binary Logging Enabled │
                        └─────────────┬─────────────┘
                                      │
                                      ▼
                        ┌───────────────────────────┐
                        │   Private File Storage    │
                        │  - Doctor Credentials     │
                        │  - Prescriptions & QR     │
                        └───────────────────────────┘
```

---

## 2. Server Specifications & OS Prerequisites
- **Recommended OS**: Ubuntu 22.04 LTS or Debian 12.
- **PHP Version**: PHP 8.2+ with extensions: `php8.2-fpm`, `php8.2-mysql`, `php8.2-mbstring`, `php8.2-xml`, `php8.2-curl`, `php8.2-fileinfo`, `php8.2-gd`, `php8.2-zip`, `php8.2-opcache`.
- **Database**: MySQL 8.0+ or MariaDB 10.4+ configured with:
  ```ini
  innodb_buffer_pool_size = 4G
  innodb_flush_log_at_trx_commit = 1
  innodb_file_per_table = 1
  sql_mode = "STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"
  default_time_zone = "+06:00"
  ```
- **Timezone Setting**: Ensure operating system is configured to `Asia/Dhaka`:
  ```bash
  timedatectl set-timezone Asia/Dhaka
  ```

---

## 3. Scheduled Tasks & Crontab Configuration

Add the following crontab entries under the application runner user (`crontab -e -u www-data`):
```cron
# 1. Expire stale waitlist slot offers every minute
* * * * * php /var/www/mediq/backend/app/Jobs/WaitlistExpirerJob.php >> /var/log/mediq/cron.log 2>&1

# 2. Reset and initialize daily chamber availability at 00:05 AM (Asia/Dhaka)
5 0 * * * php /var/www/mediq/backend/app/Jobs/DailyQueueInitializerJob.php >> /var/log/mediq/cron.log 2>&1

# 3. Clean up temporary uploaded files older than 24 hours
0 3 * * * find /var/www/mediq/backend/storage/tmp -type f -mtime +1 -delete
```

---

## 4. Automated Backup & Disaster Recovery Runbook

### 4.1 Daily Automated Backup Script (`/usr/local/bin/mediq_backup.sh`)
```bash
#!/bin/bash
set -eo pipefail

BACKUP_DIR="/var/backups/mediq"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DB_NAME="mediq_prod"
DB_USER="mediq_backup"
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

# 1. Consistent MySQL dump with single-transaction to prevent table locking
mysqldump --single-transaction --quick --routines --triggers --events \
  -u "$DB_USER" "$DB_NAME" | gzip > "$BACKUP_DIR/db_${TIMESTAMP}.sql.gz"

# 2. Archive private document attachments
tar -czf "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz" -C /var/www/mediq/backend/storage documents prescriptions

# 3. Encrypt backup archives with GPG
gpg --batch --yes --passphrase-file /root/.backup_pass -c "$BACKUP_DIR/db_${TIMESTAMP}.sql.gz"
gpg --batch --yes --passphrase-file /root/.backup_pass -c "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz"
rm "$BACKUP_DIR/db_${TIMESTAMP}.sql.gz" "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz"

# 4. Remove backups older than retention policy
find "$BACKUP_DIR" -type f -mtime +$RETENTION_DAYS -delete

echo "[$TIMESTAMP] Backup completed successfully." >> /var/log/mediq/backup.log
```

### 4.2 Disaster Recovery & Database Restoration Runbook
In the event of hardware failure, database corruption, or server migration:
1. **Provision New Host**: Install PHP 8.2-FPM, MySQL 8.0, and Nginx matching server specifications.
2. **Retrieve Encrypted Archive**: Copy latest backup files `db_YYYYMMDD_HHMMSS.sql.gz.gpg` and `storage_YYYYMMDD_HHMMSS.tar.gz.gpg` to the recovery server.
3. **Decrypt Archives**:
   ```bash
   gpg --batch --yes --passphrase-file /root/.backup_pass -d db_20260904_020000.sql.gz.gpg > db_restore.sql.gz
   gpg --batch --yes --passphrase-file /root/.backup_pass -d storage_20260904_020000.tar.gz.gpg > storage_restore.tar.gz
   gunzip db_restore.sql.gz
   ```
4. **Restore Database**:
   ```bash
   mysql -u root -p -e "CREATE DATABASE mediq_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p mediq_prod < db_restore.sql
   ```
5. **Restore Private File Storage**:
   ```bash
   tar -xzf storage_restore.tar.gz -C /var/www/mediq/backend/storage/
   chown -R www-data:www-data /var/www/mediq/backend/storage
   chmod -R 750 /var/www/mediq/backend/storage
   ```
6. **Verify Data Integrity**:
   ```bash
   php /var/www/mediq/backend/tests/verify_db_integrity.php
   ```
   Check that doctor tables, active appointments, and user accounts match pre-disaster record counts.
