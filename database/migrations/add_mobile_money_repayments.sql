-- Migration: Add Mobile Money Payment Tracking to Repayments Table
-- Date: January 2025
-- Purpose: Support mobile money payments with late fee calculation for loan repayments

-- Step 1: Add new columns to repayments table
ALTER TABLE repayments
ADD COLUMN payment_status VARCHAR(50) DEFAULT 'Pending' COMMENT 'Payment status: Pending, Completed, Failed' AFTER pay_message,
ADD COLUMN transaction_reference VARCHAR(255) UNIQUE COMMENT 'FlexiPay transaction reference for tracking' AFTER payment_status,
ADD COLUMN payment_phone VARCHAR(20) COMMENT 'Phone number used for payment (audit trail)' AFTER transaction_reference,
ADD COLUMN original_amount DECIMAL(10,2) COMMENT 'Original amount before retry (if changed)' AFTER payment_phone;

-- Step 2: Add indexes for performance
-- Note: If you get "Duplicate key name" error, the index already exists - you can skip this step
-- DROP INDEX IF EXISTS idx_transaction_reference ON repayments;
-- DROP INDEX IF EXISTS idx_payment_status ON repayments;

CREATE INDEX idx_transaction_reference ON repayments(transaction_reference);
CREATE INDEX idx_payment_status ON repayments(payment_status);

-- Step 3: Ensure products table has late fee configuration
-- Check if column exists first, then add it
SET @col_exists = (SELECT COUNT(*) 
                   FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'products' 
                   AND COLUMN_NAME = 'late_fee_per_day');

SET @query = IF(@col_exists = 0, 
                'ALTER TABLE products ADD COLUMN late_fee_per_day DECIMAL(10,2) DEFAULT 1000.00 COMMENT ''Late fee charged per day overdue'' AFTER interest_rate', 
                'SELECT ''Column late_fee_per_day already exists'' AS message');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 4: Ensure Late Fee type exists in fees_types table
INSERT INTO fees_types (name, category, status, date_created)
SELECT 'Late Fee', 'Late Fee', 1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM fees_types WHERE category = 'Late Fee'
);

-- Step 5: Add helpful indexes to related tables
-- Note: If you get "Duplicate key name" errors, those indexes already exist - skip those lines
-- Uncomment these lines if you need to drop indexes first:
-- DROP INDEX IF EXISTS idx_loan_id ON repayments;
-- DROP INDEX IF EXISTS idx_schedule_id ON repayments;
-- DROP INDEX IF EXISTS idx_personal_loan_id ON fees;
-- DROP INDEX IF EXISTS idx_fees_type_id ON fees;
-- DROP INDEX IF EXISTS idx_loan_id_schedule ON loan_schedules;

CREATE INDEX idx_loan_id ON repayments(loan_id);
CREATE INDEX idx_schedule_id ON repayments(schedule_id);
CREATE INDEX idx_personal_loan_id ON fees(loan_id);
CREATE INDEX idx_fees_type_id ON fees(fees_type_id);
CREATE INDEX idx_loan_id_schedule ON loan_schedules(loan_id);

-- Verification Queries (Run after migration)
-- Check new columns exist
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'repayments' 
    AND TABLE_SCHEMA = DATABASE()
    AND COLUMN_NAME IN ('payment_status', 'transaction_reference', 'payment_phone', 'original_amount');

-- Check indexes created
SHOW INDEX FROM repayments WHERE Key_name IN ('idx_transaction_reference', 'idx_payment_status');

-- Check Late Fee type exists
SELECT * FROM fees_types WHERE category = 'Late Fee';

-- Check products have late fee configuration
SELECT id, name, late_fee_per_day FROM products LIMIT 5;

-- ROLLBACK Script (Use only if needed to undo changes)
/*
-- Drop repayments columns and indexes
ALTER TABLE repayments
DROP INDEX IF EXISTS idx_transaction_reference,
DROP INDEX IF EXISTS idx_payment_status,
DROP COLUMN IF EXISTS payment_status,
DROP COLUMN IF EXISTS transaction_reference,
DROP COLUMN IF EXISTS payment_phone,
DROP COLUMN IF EXISTS original_amount;

-- Drop products column
ALTER TABLE products DROP COLUMN IF EXISTS late_fee_per_day;

-- Remove Late Fee type
DELETE FROM fees_types WHERE category = 'Late Fee';
*/
