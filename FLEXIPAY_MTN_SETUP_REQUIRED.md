# FlexiPay MTN Integration Setup - URGENT

## Problem Identified
✅ **Airtel payments work** - USSD prompts are sent successfully  
❌ **MTN payments fail** - No prompts sent to user's phone  

## Root Cause
The `.env` file shows **empty merchant credentials**:
```env
FLEXIPAY_MERCHANT_CODE=
FLEXIPAY_SECRET_KEY=
```

## Why This Matters
FlexiPay requires **proper merchant account credentials** to:
1. Authenticate API requests
2. Send payment prompts to MTN users
3. Process actual transactions (not just test mode)

Without these credentials, MTN rejects the payment request before sending any USSD prompt.

## Critical Bug Fixed
Found and fixed a separate bug where payments were being marked as "Paid" when they actually failed:
- FlexiPay returns `statusCode: "01"` with `statusDescription: "FAILED"`
- Our code was checking only the status code, treating "01" as success
- **Fix applied**: Now checking `statusDescription` text instead of just the code

### Corrected Payments
- Fee #285 (Member 619): 400 UGX - marked as Paid but actually Failed
- Fee #211 (Member 409): 30,000 UGX - marked as Paid but actually Failed

## What You Need to Do

### Step 1: Contact FlexiPay Support
Contact **Emuria/FlexiPay** at `https://emuria.net` to:

1. **Request MTN Merchant Account Activation**
   - Provide your business details
   - Request production API credentials for MTN Mobile Money
   - Ask for separate credentials if MTN and Airtel require different merchant IDs

2. **Get Your Credentials**
   You need:
   - `FLEXIPAY_MERCHANT_CODE` - Your unique merchant identifier
   - `FLEXIPAY_SECRET_KEY` - API authentication key
   - Confirmation that your account is activated for **MTN Uganda** network

### Step 2: Update .env File
Once you receive credentials from FlexiPay, update your `.env` file:

```env
FLEXIPAY_MERCHANT_CODE=your_actual_merchant_code_here
FLEXIPAY_SECRET_KEY=your_actual_secret_key_here
```

### Step 3: Test MTN Integration
After adding credentials:

1. Clear Laravel cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. Test with a small MTN payment (500 UGX minimum)

3. Verify you receive the USSD prompt on your MTN phone

## Technical Details

### Current API Endpoints
```php
Collection (receive money): https://emuria.net/flexipay/marchanFromMobileProd.php
Disbursement (send money): https://emuria.net/flexipay/marchanToMobilePayprod.php
Status Check: https://emuria.net/flexipay/checkFromMMStatusProd.php
```

### Request Format
```php
[
    'name' => 'Member Name',
    'phone' => '256772123456', // Uganda format
    'network' => 'MTN', // or 'AIRTEL'
    'amount' => 500
]
```

### Network Detection
- **MTN**: Prefixes 77, 78, 76
- **Airtel**: Prefixes 70, 75, 74

## Why Airtel Works But MTN Doesn't

Possible reasons:
1. **Demo/Test Mode**: FlexiPay might allow Airtel in test mode without full credentials
2. **Partial Registration**: Your account might be registered only for Airtel network
3. **Different Aggregators**: FlexiPay might use different backend providers for each network
4. **Missing MTN Agreement**: MTN requires separate merchant agreement/activation

## Questions to Ask FlexiPay Support

1. "Is my merchant account activated for **MTN Mobile Money Uganda**?"
2. "Do I need separate merchant codes for MTN and Airtel?"
3. "Why do Airtel transactions work but MTN transactions don't send prompts?"
4. "What are my production API credentials for MTN?"
5. "Is there a different endpoint for MTN vs Airtel?"

## Testing After Setup

### Test Checklist
- [ ] MTN payment sends USSD prompt to phone
- [ ] User can enter PIN on MTN USSD menu
- [ ] Payment completes successfully
- [ ] Status changes from Pending → Paid (not Failed)
- [ ] Transaction reference is saved correctly

### Test Phone Numbers
Use real MTN numbers (077x, 078x, 076x) with active mobile money accounts.

## Support Contacts

**FlexiPay/Emuria Support**
- Website: https://emuria.net
- Check their website for:
  - Support email
  - Phone number
  - WhatsApp contact
  - Documentation portal

## Next Steps After Credential Setup

1. Update production `.env` on Digital Ocean
2. Clear cache on production server
3. Test with small amounts first
4. Monitor Laravel logs for API responses
5. Document successful test transactions

---

**Status**: 🔴 **BLOCKING** - MTN payments cannot work without proper merchant credentials  
**Priority**: 🔥 **URGENT** - Contact FlexiPay immediately  
**ETA**: Depends on FlexiPay activation time (usually 1-3 business days)
