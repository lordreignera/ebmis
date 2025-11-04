-- Script to drop all tables in correct order for Laravel Cloud database
-- Run this BEFORE importing your old database dump

SET FOREIGN_KEY_CHECKS = 0;

-- Drop all tables (in reverse dependency order)
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `failed_jobs`;

-- Drop school-related tables
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `school_classes`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `schools`;
DROP TABLE IF EXISTS `uganda_subcounties`;
DROP TABLE IF EXISTS `uganda_parishes`;
DROP TABLE IF EXISTS `uganda_villages`;
DROP TABLE IF EXISTS `uganda_districts`;
DROP TABLE IF EXISTS `regions`;

-- Drop loan and financial tables
DROP TABLE IF EXISTS `loan_schedules`;
DROP TABLE IF EXISTS `group_loan_schedules`;
DROP TABLE IF EXISTS `group_loan_members`;
DROP TABLE IF EXISTS `group_loan_charges`;
DROP TABLE IF EXISTS `loan_charges`;
DROP TABLE IF EXISTS `repayments`;
DROP TABLE IF EXISTS `group_repayments`;
DROP TABLE IF EXISTS `disbursement_txn`;
DROP TABLE IF EXISTS `group_disbursement_txn`;
DROP TABLE IF EXISTS `group_disbursement`;
DROP TABLE IF EXISTS `disbursements`;
DROP TABLE IF EXISTS `disbursement`;
DROP TABLE IF EXISTS `loans`;
DROP TABLE IF EXISTS `personal_loans`;
DROP TABLE IF EXISTS `group_loans`;
DROP TABLE IF EXISTS `guarantors`;
DROP TABLE IF EXISTS `attachments`;

-- Drop savings and payment tables
DROP TABLE IF EXISTS `savings_withdraw`;
DROP TABLE IF EXISTS `savings`;
DROP TABLE IF EXISTS `savings_products`;
DROP TABLE IF EXISTS `savings_delete`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `raw_payments`;
DROP TABLE IF EXISTS `raw_payments_checks`;
DROP TABLE IF EXISTS `app_repayments`;

-- Drop product and fee tables
DROP TABLE IF EXISTS `product_charges`;
DROP TABLE IF EXISTS `fees`;
DROP TABLE IF EXISTS `fees_types`;
DROP TABLE IF EXISTS `products`;

-- Drop investment tables
DROP TABLE IF EXISTS `investment`;
DROP TABLE IF EXISTS `investors`;
DROP TABLE IF EXISTS `paypal_orders`;

-- Drop member and group tables
DROP TABLE IF EXISTS `members`;
DROP TABLE IF EXISTS `groups`;
DROP TABLE IF EXISTS `g_closed`;

-- Drop business tables
DROP TABLE IF EXISTS `business_address`;
DROP TABLE IF EXISTS `business`;
DROP TABLE IF EXISTS `business_type`;
DROP TABLE IF EXISTS `areas_of_interest`;
DROP TABLE IF EXISTS `place_of_birth`;

-- Drop asset and liability tables
DROP TABLE IF EXISTS `assets`;
DROP TABLE IF EXISTS `asset_types`;
DROP TABLE IF EXISTS `liabilities`;
DROP TABLE IF EXISTS `liability_types`;

-- Drop account tables
DROP TABLE IF EXISTS `accounts_ledger`;
DROP TABLE IF EXISTS `system_accounts`;
DROP TABLE IF EXISTS `closing_bals`;
DROP TABLE IF EXISTS `opening_bals`;

-- Drop misc tables
DROP TABLE IF EXISTS `bulk_sms_users`;
DROP TABLE IF EXISTS `bulk_sms`;
DROP TABLE IF EXISTS `audit_trail`;
DROP TABLE IF EXISTS `geo_location`;
DROP TABLE IF EXISTS `api_raw`;
DROP TABLE IF EXISTS `agency`;
DROP TABLE IF EXISTS `designations`;
DROP TABLE IF EXISTS `id_types`;
DROP TABLE IF EXISTS `reset_pass`;
DROP TABLE IF EXISTS `user_access`;

-- Drop permission tables
DROP TABLE IF EXISTS `permission_sections`;
DROP TABLE IF EXISTS `model_has_permissions`;
DROP TABLE IF EXISTS `model_has_roles`;
DROP TABLE IF EXISTS `role_has_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;

-- Drop team tables
DROP TABLE IF EXISTS `team_invitations`;
DROP TABLE IF EXISTS `team_user`;
DROP TABLE IF EXISTS `teams`;

-- Drop user tables
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `user_legacy_mapping`;
DROP TABLE IF EXISTS `users`;

-- Drop base tables
DROP TABLE IF EXISTS `branches`;
DROP TABLE IF EXISTS `countries`;
DROP TABLE IF EXISTS `migrations`;

SET FOREIGN_KEY_CHECKS = 1;
