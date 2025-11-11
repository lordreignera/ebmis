# School Loans Online Deployment Guide

## Problem
When running migrations online, you get this error:
```
SQLSTATE[HY000]: General error: 1824 Failed to open the referenced table 'schools'
```

**Root Cause:** The `schools`, `students`, `staff`, `users`, `products`, and `branches` tables are using the **MyISAM** storage engine, which doesn't support foreign key constraints. The school loan tables require **InnoDB** engine.

---

## Solution Options

### Option 1: Run Pre-Migration Script (Recommended)

**Step 1:** Upload the conversion migration file
```bash
# This file was created: database/migrations/2025_11_11_235959_convert_tables_to_innodb.php
```

**Step 2:** Run the migration
```bash
php artisan migrate
```

This will:
1. First convert all required tables to InnoDB (migration 2025_11_11_235959)
2. Then create the school_loans table (migration 2025_11_12_000001)
3. Then create the student_loans table (migration 2025_11_12_000002)
4. Then create the staff_loans table (migration 2025_11_12_000003)

---

### Option 2: Run Standalone PHP Script (If Option 1 Fails)

**Step 1:** Upload `convert_tables_to_innodb.php` to your server root

**Step 2:** Update database credentials in the script (if needed):
```php
$host = 'localhost';
$dbname = 'ebims1';  // Your database name
$username = 'root';   // Your database username
$password = '';       // Your database password
```

**Step 3:** Run via command line:
```bash
cd /path/to/your/project
php convert_tables_to_innodb.php
```

**OR** run via browser:
```
http://yourdomain.com/convert_tables_to_innodb.php
```

**Step 4:** After successful conversion, run migrations:
```bash
php artisan migrate
```

**Step 5:** Delete the script for security:
```bash
rm convert_tables_to_innodb.php
```

---

### Option 3: Manual MySQL Commands (If Both Options Fail)

**Step 1:** Connect to MySQL on your online server:
```bash
mysql -u root -p ebims1
```

**Step 2:** Run these commands one by one:
```sql
-- Convert tables to InnoDB
ALTER TABLE schools ENGINE=InnoDB;
ALTER TABLE students ENGINE=InnoDB;
ALTER TABLE staff ENGINE=InnoDB;
ALTER TABLE users ENGINE=InnoDB;
ALTER TABLE products ENGINE=InnoDB;
ALTER TABLE branches ENGINE=InnoDB;

-- Convert charset to utf8mb4 (optional but recommended)
ALTER TABLE schools CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE students CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE staff CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE products CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE branches CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Step 3:** Verify conversions:
```sql
SELECT TABLE_NAME, ENGINE, TABLE_COLLATION 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'ebims1' 
AND TABLE_NAME IN ('schools', 'students', 'staff', 'users', 'products', 'branches');
```

You should see all tables showing `InnoDB` as the engine.

**Step 4:** Exit MySQL and run migrations:
```bash
exit
php artisan migrate
```

---

## Verification Steps

After running any of the above options, verify success:

**1. Check Table Engines:**
```bash
php artisan tinker
```
```php
DB::select("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('schools', 'students', 'staff', 'users', 'products', 'branches')");
```

**2. Check Migration Status:**
```bash
php artisan migrate:status
```

You should see:
```
✓ 2025_11_11_235959_convert_tables_to_innodb
✓ 2025_11_12_000001_create_school_loans_table
✓ 2025_11_12_000002_create_student_loans_table
✓ 2025_11_12_000003_create_staff_loans_table
```

**3. Verify Tables Exist:**
```bash
php artisan tinker
```
```php
Schema::hasTable('school_loans');  // Should return true
Schema::hasTable('student_loans'); // Should return true
Schema::hasTable('staff_loans');   // Should return true
```

**4. Test Foreign Key Constraints:**
```bash
php artisan tinker
```
```php
// This should work if constraints are properly set
DB::table('school_loans')->insert([
    'school_id' => 1,  // Must exist in schools table
    'product_type' => 1,
    'branch_id' => 1,
    'added_by' => 1,
    'code' => 'TEST001',
    'interest' => '10',
    'period' => '12',
    'principal' => 100000,
    'installment' => 10000,
    'status' => 0
]);
```

---

## Common Issues & Solutions

### Issue 1: "Access denied" when converting tables
**Solution:** Ensure your database user has `ALTER` privilege:
```sql
GRANT ALTER ON ebims1.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

### Issue 2: "Table is full" error
**Solution:** Check disk space on server:
```bash
df -h
```
Free up space if needed.

### Issue 3: "Duplicate entry" errors during conversion
**Solution:** Check for duplicate primary keys:
```sql
SELECT id, COUNT(*) FROM schools GROUP BY id HAVING COUNT(*) > 1;
```

### Issue 4: Migration runs but foreign keys fail
**Solution:** Check if referenced IDs exist:
```sql
-- Ensure no orphaned records
SELECT sl.id, sl.school_id 
FROM school_loans sl 
LEFT JOIN schools s ON sl.school_id = s.id 
WHERE s.id IS NULL;
```

### Issue 5: "Unknown database charset" error
**Solution:** Update MySQL server configuration to support utf8mb4:
```ini
[mysqld]
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
```

---

## Rollback Plan

If something goes wrong, you can rollback:

**Option A: Rollback migrations**
```bash
php artisan migrate:rollback --step=4
```

**Option B: Drop tables manually**
```sql
DROP TABLE IF EXISTS staff_loans;
DROP TABLE IF EXISTS student_loans;
DROP TABLE IF EXISTS school_loans;
```

**Option C: Restore database backup**
```bash
mysql -u root -p ebims1 < backup_before_migration.sql
```

---

## Performance Considerations

**Converting large tables to InnoDB:**
- For tables with millions of rows, conversion can take time
- Expect 1-5 minutes per million rows
- The table will be locked during conversion (no writes possible)
- Schedule conversion during low-traffic periods

**Monitoring conversion progress:**
```sql
SHOW PROCESSLIST;
```

---

## Summary

**Quick Steps:**
1. Upload `2025_11_11_235959_convert_tables_to_innodb.php` to `database/migrations/`
2. Run `php artisan migrate`
3. Verify with `php artisan migrate:status`
4. Test school loan creation

**If that fails:**
1. Run `convert_tables_to_innodb.php` script
2. Then run `php artisan migrate`

**If both fail:**
1. Manually execute SQL commands to convert tables
2. Then run `php artisan migrate`

---

## Files Created

1. **database/migrations/2025_11_11_235959_convert_tables_to_innodb.php**
   - Laravel migration to convert tables to InnoDB
   - Runs automatically with `php artisan migrate`

2. **convert_tables_to_innodb.php**
   - Standalone PHP script (root directory)
   - Can run independently of Laravel
   - Use if migrations fail

3. **docs/SCHOOL_LOANS_ONLINE_DEPLOYMENT.md**
   - This comprehensive guide

---

## Contact & Support

If you encounter issues not covered here:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check MySQL error log: `/var/log/mysql/error.log`
3. Enable query logging: `DB::enableQueryLog()` then `DB::getQueryLog()`

---

**Last Updated:** November 11, 2025
**Version:** 1.0
**Tested On:** Laravel 11.x, MySQL 5.7+, MySQL 8.0+
