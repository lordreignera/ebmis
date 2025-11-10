# EBIMS Production Deployment Checklist

## Pre-Deployment Steps ✅

### 1. Database Backup
```bash
# Create a full database backup
mysqldump -u username -p ebims_database > ebims_backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Check Current Production State
```bash
# Upload verify_production_compatibility.php to server
# Run compatibility check
php artisan tinker --execute="require_once('database/verify_production_compatibility.php');"
```

### 3. Prepare Files for Upload
- Upload only the new migration file: `2025_11_09_000001_add_mobile_pin_to_members_table.php`
- Upload updated views if needed
- Upload updated controllers if needed

## Deployment Steps 🚀

### 4. Upload Code Changes
```bash
# Upload new files via FTP/Git
# Main files to update:
- database/migrations/2025_11_09_000001_add_mobile_pin_to_members_table.php
- resources/views/admin/members/create.blade.php (if modified)
- resources/views/admin/members/edit.blade.php (if modified)
- resources/views/admin/reports/disbursed_loans.blade.php
- app/Http/Controllers/Admin/MemberController.php (if modified)
- app/Http/Controllers/Admin/ReportsController.php
```

### 5. Run Migrations
```bash
# Check migration status first
php artisan migrate:status

# Run only new migrations
php artisan migrate

# Expected output: Should add mobile_pin column only
```

### 6. Clear Caches
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Post-Deployment Verification ✅

### 7. Test Critical Functionality
1. **Member Registration Form**
   - Access: `/admin/members/create`
   - Verify: Mobile PIN field is visible
   - Test: Create a new member with mobile PIN

2. **Member Edit Form**
   - Access: `/admin/members/{id}/edit`
   - Verify: Mobile PIN field shows existing value
   - Test: Update mobile PIN

3. **Reports Export**
   - Access: `/admin/reports/disbursed-loans`
   - Test: CSV export works
   - Test: Excel export works
   - Test: PDF export works

### 8. Database Verification
```bash
# Check if mobile_pin column was added
php artisan tinker --execute="
  use Illuminate\Support\Facades\Schema;
  var_dump(Schema::hasColumn('members', 'mobile_pin'));
  echo Schema::getColumnType('members', 'mobile_pin');
"
```

## Rollback Plan 🔙

### If Something Goes Wrong:
1. **Database Rollback:**
   ```bash
   php artisan migrate:rollback --step=1
   ```

2. **Full Database Restore:**
   ```bash
   mysql -u username -p ebims_database < ebims_backup_YYYYMMDD_HHMMSS.sql
   ```

3. **File Rollback:**
   - Restore previous version of modified files
   - Clear caches: `php artisan optimize:clear`

## Expected Changes Summary 📋

### Database Changes:
- ✅ `mobile_pin` column added to `members` table
- ✅ Column type: `varchar(10)`, nullable
- ✅ No data loss, existing records unaffected

### Functionality Changes:
- ✅ Member registration form includes mobile PIN field
- ✅ Member edit form includes mobile PIN field
- ✅ Export reports maintain existing functionality
- ✅ All existing members data preserved

## Risk Assessment 📊

### Low Risk ✅
- Adding nullable column to existing table
- No existing data modification
- Backwards compatible changes

### Medium Risk ⚠️
- Multiple file updates
- Cache clearing required

### High Risk ❌
- None (using safe incremental approach)

## Contact Information 📞
- Developer: [Your contact]
- Backup support: [Backup contact]
- Server admin: [Server admin contact]