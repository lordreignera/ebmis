# MTN Mobile Money USSD Fix

## Problem
MTN mobile money payments were **failing immediately** with no USSD prompt sent to the phone.

## Root Cause
**Request ID was TOO LONG** for Stanbic FlexiPay API:
- Generated: `EbP1763208899923820685` (**22 characters**)
- Stanbic API limit: **~16-20 characters max**
- Error: `"Invalid length"` with HTTP 422

## Solution
Fixed `generateUniqueRequestId()` in `StanbicFlexiPayService.php`:

**Before:**
```php
return $this->config['request_prefix'] .  // EbP (3 chars)
       time() .                            // 1763208899 (10 chars)
       substr(microtime(false), 2, 6) .    // 923820 (6 chars)
       mt_rand(100, 999);                  // 685 (3 chars)
// Total: 22 characters ❌
```

**After:**
```php
$timestamp = substr((string)time(), -6);   // 208899 (6 chars)
$microsec = substr(microtime(false), 2, 3); // 660 (3 chars)
$random = str_pad(mt_rand(0, 99), 2, '0', STR_PAD_LEFT); // 32 (2 chars)

return $this->config['request_prefix'] . $timestamp . $microsec . $random;
// Example: EbP20894066032 (14 characters) ✅
```

## Result
✅ Request ID is now **14 characters** (within Stanbic limit)  
✅ API accepts the request  
✅ USSD prompt **SUCCESSFULLY SENT** to phone  
✅ Transaction status: `"Your transaction has been received and is being processed"`  
✅ FlexiPay Reference: `300059360430`

## Testing
```bash
php artisan stanbic:test
```

Output:
```
✅ Transaction initiated successfully!
Request ID: EbP20894066032
Status: Initiated
📱 Check the phone for USSD prompt to complete payment
```

## How to Pay Fees Now
1. Go to http://localhost:84/admin/members/619
2. Click "Pay Fees"
3. Enter phone: **256782743720**
4. Select network: **MTN**
5. Enter amount and submit
6. ✅ **USSD prompt will be sent to the phone!**
7. Customer approves on their phone
8. Payment completes automatically

## Status Codes
- **00** = Transaction initiated (USSD sent)
- **01** = Successful (money received)
- **57** = Failed (customer rejected or timeout)

## Files Modified
- `app/Services/StanbicFlexiPayService.php` (Line 481-488)
  - Changed request ID generation from 22 chars to 14 chars

---

**Status:** ✅ **FIXED - MTN USSD Now Working!**
