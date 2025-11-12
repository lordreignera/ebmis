# Mobile Money Payment Flow - Fee Payment

## Current Flow

### 1. User Initiates Payment (Frontend)
- User clicks "Add Fee Payment" on member details page
- Selects "Mobile Money" as payment method
- System shows member's phone number (256782743720)
- When form is submitted, AJAX request is sent to backend

### 2. Backend Processing (FeeController.php - Line 513)
```php
public function storeMobileMoneyPayment(Request $request)
```
**What it does:**
1. Validates the request data
2. Creates a Fee record with status = 0 (Pending)
3. Calls `MobileMoneyService->collectMoney()` method
4. Returns transaction reference for status polling

### 3. Mobile Money API Call (MobileMoneyService.php - Line 238)
```php
public function collectMoney(string $payerName, string $phone, float $amount, ?string $description = null)
```
**What it does:**
1. Formats phone number: 256782743720
2. Detects network: MTN (from prefix 78)
3. Makes POST request to: `https://emuria.net/flexipay/marchanFromMobileProd.php`
4. Sends data:
   ```php
   [
       'name' => 'lord reign',  // Member name
       'phone' => '256782743720',
       'network' => 'MTN',
       'amount' => 500
   ]
   ```

### 4. FlexiPay API Response
**Expected Response:**
- FlexiPay sends MTN prompt to 256782743720
- User authorizes payment on their phone
- FlexiPay returns transaction reference

**What's Happening:**
- API request is sent successfully
- BUT: **Prompt not reaching user's phone**

## Why Prompt Is Not Reaching User

### Possible Causes:

1. **FlexiPay Account Not Configured**
   - The endpoint `https://emuria.net/flexipay/` requires merchant account setup
   - Without proper merchant credentials, API accepts request but doesn't send prompt

2. **Phone Number Issue**
   - Number format: 256782743720 ✅ (Correct)
   - Network detected: MTN ✅ (Correct from prefix 78)
   - Possible issue: Wrong network operator? (Should verify actual sim card network)

3. **FlexiPay Service Issue**
   - Service might be down
   - Merchant account suspended
   - API endpoint changed

4. **No Merchant Configuration**
   - `config/flexipay.php` has these empty:
     ```php
     'merchant_code' => env('FLEXIPAY_MERCHANT_CODE', ''),
     'secret_key' => env('FLEXIPAY_SECRET_KEY', ''),
     ```
   - But the `MobileMoneyService.php` doesn't use these!
   - Service is hardcoded to use endpoint without auth

## How to Debug

### Step 1: Check Laravel Logs
```bash
tail -f storage/logs/laravel.log
```
Look for:
- "Mobile Money Collection Request"
- "FlexiPay Collection Response"
- HTTP response code
- Response body from FlexiPay

### Step 2: Test FlexiPay API Directly
Create test script `test_flexipay_collection.php`:
```php
<?php

$url = 'https://emuria.net/flexipay/marchanFromMobileProd.php';

$data = [
    'name' => 'Test User',
    'phone' => '256782743720',  // Your test number
    'network' => 'MTN',
    'amount' => 500
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
```

Run: `php test_flexipay_collection.php`

### Step 3: Check Transaction Status
The system polls for status using:
```
https://emuria.net/flexipay/checkFromMMStatusProd.php
```

### Step 4: Verify Phone Number
- Confirm 256782743720 is actually MTN
- Try with different MTN number
- Ensure phone has enough balance for test amount

## Solution Options

### Option 1: Contact FlexiPay Support
- Get merchant account setup
- Get API credentials (merchant_code, secret_key)
- Confirm correct endpoints to use

### Option 2: Use Different Payment Gateway
- MTN MoMo API directly
- Airtel Money API
- Flutterwave
- Payway

### Option 3: Manual Testing Mode
Enable test mode to simulate successful payments without real API:
```php
// In .env file:
MOBILE_MONEY_TEST_MODE=true
```

Then modify MobileMoneyService to return mock success when test_mode is true.

## Quick Fix for Testing

Add this to `.env`:
```env
FLEXIPAY_ENABLED=true
MOBILE_MONEY_TEST_MODE=false
FLEXIPAY_MERCHANT_CODE=your_merchant_code_here
FLEXIPAY_SECRET_KEY=your_secret_key_here
```

Then check Laravel logs to see actual API response from FlexiPay.

## API Endpoint Documentation

FlexiPay endpoints being used:
1. **Collection** (Prompt user to pay): `marchanFromMobileProd.php`
2. **Disbursement** (Send money): `marchanToMobilePayprod.php`
3. **Status Check**: `checkFromMMStatusProd.php`

All require proper merchant setup with FlexiPay/Emuria.
