-- Database Performance Optimization for EBIMS
-- Run this SQL on your production database to add indexes
-- This will significantly speed up queries

USE ebims1; -- Change to your database name

-- ==========================================
-- CORE TABLES INDEXES
-- ==========================================

-- Members table (most queried)
ALTER TABLE members ADD INDEX IF NOT EXISTS idx_member_type (member_type);
ALTER TABLE members ADD INDEX IF NOT EXISTS idx_group_id (group_id);
ALTER TABLE members ADD INDEX IF NOT EXISTS idx_verified (verified);
ALTER TABLE members ADD INDEX IF NOT EXISTS idx_soft_delete (soft_delete);
ALTER TABLE members ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);
ALTER TABLE members ADD INDEX IF NOT EXISTS idx_country_id (country_id);
ALTER TABLE members ADD INDEX IF NOT EXISTS idx_added_by (added_by);
ALTER TABLE members ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE members ADD INDEX IF NOT EXISTS idx_contact (contact);

-- Personal loans table (high frequency)
ALTER TABLE personal_loans ADD INDEX IF NOT EXISTS idx_user_id (user_id);
ALTER TABLE personal_loans ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE personal_loans ADD INDEX IF NOT EXISTS idx_verified (verified);
ALTER TABLE personal_loans ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);
ALTER TABLE personal_loans ADD INDEX IF NOT EXISTS idx_disbursement_date (disbursement_date);
ALTER TABLE personal_loans ADD INDEX IF NOT EXISTS idx_created_at (created_at);
ALTER TABLE personal_loans ADD INDEX IF NOT EXISTS idx_product_id (product_id);
ALTER TABLE personal_loans ADD INDEX IF NOT EXISTS idx_assigned_to (assigned_to);

-- Groups table
ALTER TABLE groups ADD INDEX IF NOT EXISTS idx_verified (verified);
ALTER TABLE groups ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);
ALTER TABLE groups ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE groups ADD INDEX IF NOT EXISTS idx_leader_id (leader_id);

-- Group loans table
ALTER TABLE group_loans ADD INDEX IF NOT EXISTS idx_group_id (group_id);
ALTER TABLE group_loans ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE group_loans ADD INDEX IF NOT EXISTS idx_verified (verified);
ALTER TABLE group_loans ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);
ALTER TABLE group_loans ADD INDEX IF NOT EXISTS idx_disbursement_date (disbursement_date);
ALTER TABLE group_loans ADD INDEX IF NOT EXISTS idx_product_id (product_id);

-- School loans table
ALTER TABLE school_loans ADD INDEX IF NOT EXISTS idx_school_id (school_id);
ALTER TABLE school_loans ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE school_loans ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);
ALTER TABLE school_loans ADD INDEX IF NOT EXISTS idx_disbursement_date (disbursement_date);

-- Student loans table
ALTER TABLE student_loans ADD INDEX IF NOT EXISTS idx_student_id (student_id);
ALTER TABLE student_loans ADD INDEX IF NOT EXISTS idx_school_id (school_id);
ALTER TABLE student_loans ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE student_loans ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);

-- Staff loans table
ALTER TABLE staff_loans ADD INDEX IF NOT EXISTS idx_staff_id (staff_id);
ALTER TABLE staff_loans ADD INDEX IF NOT EXISTS idx_school_id (school_id);
ALTER TABLE staff_loans ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE staff_loans ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);

-- ==========================================
-- SCHEDULES & REPAYMENTS
-- ==========================================

-- Loan schedules (heavily queried)
ALTER TABLE loan_schedules ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE loan_schedules ADD INDEX IF NOT EXISTS idx_due_date (due_date);
ALTER TABLE loan_schedules ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE loan_schedules ADD INDEX IF NOT EXISTS idx_paid (paid);

-- Group loan schedules
ALTER TABLE group_loan_schedules ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE group_loan_schedules ADD INDEX IF NOT EXISTS idx_due_date (due_date);
ALTER TABLE group_loan_schedules ADD INDEX IF NOT EXISTS idx_status (status);

-- Repayments table
ALTER TABLE repayments ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE repayments ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE repayments ADD INDEX IF NOT EXISTS idx_payment_date (payment_date);
ALTER TABLE repayments ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);

-- Group repayments
ALTER TABLE group_repayments ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE group_repayments ADD INDEX IF NOT EXISTS idx_payment_date (payment_date);
ALTER TABLE group_repayments ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);

-- ==========================================
-- SAVINGS & FEES
-- ==========================================

-- Savings table
ALTER TABLE savings ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE savings ADD INDEX IF NOT EXISTS idx_date (date);
ALTER TABLE savings ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);
ALTER TABLE savings ADD INDEX IF NOT EXISTS idx_product_id (product_id);

-- Savings withdrawals
ALTER TABLE savings_withdraw ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE savings_withdraw ADD INDEX IF NOT EXISTS idx_date (date);
ALTER TABLE savings_withdraw ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);

-- Fees table
ALTER TABLE fees ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE fees ADD INDEX IF NOT EXISTS idx_fee_type_id (fee_type_id);
ALTER TABLE fees ADD INDEX IF NOT EXISTS idx_date (date);
ALTER TABLE fees ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);
ALTER TABLE fees ADD INDEX IF NOT EXISTS idx_status (status);

-- ==========================================
-- DISBURSEMENTS & PAYMENTS
-- ==========================================

-- Disbursements
ALTER TABLE disbursements ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE disbursements ADD INDEX IF NOT EXISTS idx_disbursement_date (disbursement_date);
ALTER TABLE disbursements ADD INDEX IF NOT EXISTS idx_investment_id (investment_id);
ALTER TABLE disbursements ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);

-- Disbursement transactions
ALTER TABLE disbursement_txn ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE disbursement_txn ADD INDEX IF NOT EXISTS idx_transaction_date (transaction_date);
ALTER TABLE disbursement_txn ADD INDEX IF NOT EXISTS idx_status (status);

-- Group disbursements
ALTER TABLE group_disbursement ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE group_disbursement ADD INDEX IF NOT EXISTS idx_disbursement_date (disbursement_date);

-- Raw payments (mobile money callbacks)
ALTER TABLE raw_payments ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE raw_payments ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE raw_payments ADD INDEX IF NOT EXISTS idx_transaction_date (transaction_date);
ALTER TABLE raw_payments ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE raw_payments ADD INDEX IF NOT EXISTS idx_external_reference (ExternalReference);

-- Payments table
ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payment_date (payment_date);
ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);

-- ==========================================
-- REFERENCE & LOOKUP TABLES
-- ==========================================

-- Branches
ALTER TABLE branches ADD INDEX IF NOT EXISTS idx_is_active (is_active);
ALTER TABLE branches ADD INDEX IF NOT EXISTS idx_region_id (region_id);
ALTER TABLE branches ADD INDEX IF NOT EXISTS idx_manager_id (manager_id);

-- Products
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_product_type (product_type);

-- Member types
ALTER TABLE member_types ADD INDEX IF NOT EXISTS idx_status (status);

-- Countries
ALTER TABLE countries ADD INDEX IF NOT EXISTS idx_status (status);

-- Users
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_email (email);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_is_active (is_active);

-- ==========================================
-- SCHOOLS MODULE
-- ==========================================

-- Schools table
ALTER TABLE schools ADD INDEX IF NOT EXISTS idx_assessment_status (assessment_status);
ALTER TABLE schools ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);
ALTER TABLE schools ADD INDEX IF NOT EXISTS idx_district_id (district_id);

-- Students
ALTER TABLE students ADD INDEX IF NOT EXISTS idx_school_id (school_id);
ALTER TABLE students ADD INDEX IF NOT EXISTS idx_class_id (class_id);
ALTER TABLE students ADD INDEX IF NOT EXISTS idx_status (status);

-- Staff
ALTER TABLE staff ADD INDEX IF NOT EXISTS idx_school_id (school_id);
ALTER TABLE staff ADD INDEX IF NOT EXISTS idx_designation_id (designation_id);
ALTER TABLE staff ADD INDEX IF NOT EXISTS idx_status (status);

-- ==========================================
-- GUARANTORS & ATTACHMENTS
-- ==========================================

-- Guarantors
ALTER TABLE guarantors ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE guarantors ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE guarantors ADD INDEX IF NOT EXISTS idx_guarantor_member_id (guarantor_member_id);

-- Attachments
ALTER TABLE attachments ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE attachments ADD INDEX IF NOT EXISTS idx_loan_id (loan_id);
ALTER TABLE attachments ADD INDEX IF NOT EXISTS idx_attachment_type (attachment_type);

-- ==========================================
-- AUDIT & LOGGING
-- ==========================================

-- Audit trail
ALTER TABLE audit_trail ADD INDEX IF NOT EXISTS idx_user_id (user_id);
ALTER TABLE audit_trail ADD INDEX IF NOT EXISTS idx_action (action);
ALTER TABLE audit_trail ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- SMS logs
ALTER TABLE sms_logs ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE sms_logs ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE sms_logs ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- User access logs
ALTER TABLE user_access ADD INDEX IF NOT EXISTS idx_user_id (user_id);
ALTER TABLE user_access ADD INDEX IF NOT EXISTS idx_login_time (login_time);

-- ==========================================
-- FINANCIAL TABLES
-- ==========================================

-- Investments
ALTER TABLE investment ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);
ALTER TABLE investment ADD INDEX IF NOT EXISTS idx_status (status);

-- System accounts
ALTER TABLE system_accounts ADD INDEX IF NOT EXISTS idx_account_type (account_type);
ALTER TABLE system_accounts ADD INDEX IF NOT EXISTS idx_parent_account_id (parent_account_id);
ALTER TABLE system_accounts ADD INDEX IF NOT EXISTS idx_is_active (is_active);

-- Accounts ledger
ALTER TABLE accounts_ledger ADD INDEX IF NOT EXISTS idx_account_id (account_id);
ALTER TABLE accounts_ledger ADD INDEX IF NOT EXISTS idx_transaction_date (transaction_date);
ALTER TABLE accounts_ledger ADD INDEX IF NOT EXISTS idx_transaction_type (transaction_type);

-- Assets
ALTER TABLE assets ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE assets ADD INDEX IF NOT EXISTS idx_asset_type_id (asset_type_id);
ALTER TABLE assets ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);

-- Liabilities
ALTER TABLE liabilities ADD INDEX IF NOT EXISTS idx_member_id (member_id);
ALTER TABLE liabilities ADD INDEX IF NOT EXISTS idx_liability_type_id (liability_type_id);
ALTER TABLE liabilities ADD INDEX IF NOT EXISTS idx_branch_id (branch_id);

-- ==========================================
-- OPTIMIZE TABLES
-- ==========================================

-- Optimize most frequently accessed tables
OPTIMIZE TABLE members;
OPTIMIZE TABLE personal_loans;
OPTIMIZE TABLE groups;
OPTIMIZE TABLE group_loans;
OPTIMIZE TABLE loan_schedules;
OPTIMIZE TABLE repayments;
OPTIMIZE TABLE savings;
OPTIMIZE TABLE fees;
OPTIMIZE TABLE disbursements;
OPTIMIZE TABLE raw_payments;
OPTIMIZE TABLE payments;

-- ==========================================
-- SHOW RESULTS
-- ==========================================

SELECT 'Database indexes created successfully!' AS status;

-- Show all indexes on main tables
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN (
        'members', 'personal_loans', 'groups', 'group_loans',
        'loan_schedules', 'repayments', 'savings', 'fees',
        'disbursements', 'payments', 'raw_payments'
    )
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME, INDEX_NAME;
