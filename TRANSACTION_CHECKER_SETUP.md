# Automatic Transaction Checking System

## Overview
This system automatically checks the status of pending mobile money transactions and processes successful repayments without manual intervention.

## How It Works

1. **Transaction Status Check**
   - Every 5 minutes, the system queries all pending transactions from `raw_payments` table
   - Checks status with FlexiPay provider using the transaction reference
   - Updates the payment status in the database

2. **Auto-Processing Successful Payments**
   - When a transaction is confirmed as successful (status code '00' or '01'):
     * Updates the `repayments` table (sets status=1, pay_status='SUCCESS')
     * Updates the `loan_schedules` table (deducts payment from balance)
     * If schedule balance ≤ 500 UGX, marks schedule as fully paid (status=1)
     * If all schedules for a loan are paid, closes the loan (status=3)
     * Logs the action in the `trail` table for audit

3. **Database Tables Updated**
   - `raw_payments` - Transaction status from provider
   - `repayments` - Repayment record status
   - `loan_schedules` - Schedule balance and payment status
   - `personal_loans` / `group_loans` - Loan completion status
   - `trail` - Audit log

## Running the Command

### Manual Run
```bash
php artisan transactions:check
```

### Check Specific Transaction
```bash
php artisan transactions:check --txn=TXN123456
```

### View Logs
```bash
type storage\logs\laravel.log
type storage\logs\check_transactions.log
```

## Setting Up Automatic Checking

### Option 1: Laravel Schedule (Requires Laravel Scheduler)
The schedule is already configured in `routes/console.php` to run every 5 minutes.

Run this command continuously:
```bash
php artisan schedule:work
```

### Option 2: Windows Task Scheduler (Recommended for Production)

1. Open **Task Scheduler**
2. Create a new **Basic Task**
3. Name: "EBIMS Transaction Checker"
4. Trigger: **Daily**
5. Repeat task every: **5 minutes**
6. For duration of: **Indefinitely**
7. Action: **Start a program**
8. Program: `C:\wamp64\www\ebims\check_transactions.bat`
9. Start in: `C:\wamp64\www\ebims`

### Option 3: Manual Periodic Runs
Just run the batch file manually whenever you want to check:
```
check_transactions.bat
```

## Transaction Status Codes

- **00** or **01** = Success (payment confirmed)
- **02** = Failed
- **Pending** = Still processing

## What Gets Logged

- Number of transactions processed
- Number of auto-approved repayments
- Any errors encountered
- Schedule updates and balance changes
- Loan closures

## Troubleshooting

### No transactions being processed
- Check if there are pending transactions: `SELECT * FROM raw_payments WHERE status IN ('00','01') AND (pay_status IS NULL OR pay_message='Pending')`
- Verify FlexiPay API is accessible
- Check logs in `storage/logs/laravel.log`

### Repayments not updating
- Ensure `repayments.txn_id` matches `raw_payments.trans_id`
- Check that schedule_id exists in `loan_schedules`
- Verify loan_id is correct

### Duplicate processing
- The system checks if repayment status=0 before processing
- Already processed repayments (status=1) are skipped

## Example Output

```
Starting transaction status check...
Found 3 pending transactions.
Checking transaction EbP1762817876...
  Status: 01 - Dear Customer your Transaction is Successful.
  Processing repayment ID 934
  Schedule balance: 5200 - 5200 = 0
  Schedule marked as cleared
  Unpaid schedules remaining: 1
Summary: Processed=3, Auto-approved=3
```

## Benefits

- **No manual intervention needed** - Payments process automatically
- **Real-time updates** - Checks every 5 minutes
- **Audit trail** - All actions logged
- **Loan auto-closure** - Loans close when fully paid
- **Safe processing** - Only processes confirmed successful transactions
