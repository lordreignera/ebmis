# Old System Mobile Money Integration Analysis

## Summary
The old bimsadmin system successfully integrates with mobile money using FlexiPay API **WITHOUT sending merchant credentials**. This suggests the integration works via IP whitelisting or is in test/demo mode.

---

## API Endpoints Used

### 1. Collection (Receiving Money from Customer)
**Endpoint**: `https://emuria.net/flexipay/marchanFromMobileProd.php`

**Method**: POST (application/x-www-form-urlencoded)

**Parameters**:
```
phone=256772123456
network=MTN          # or AIRTEL
amount=50000
```

**No Authentication Required**:
- No merchant_code
- No secret_key
- No API key

**Response Format**:
```json
{
    "statusCode": "00",
    "statusDescription": "Initiated successfully", 
    "transactionReferenceNumber": "EbP1706301336",
    "flexipayReferenceNumber": "300019198928"
}
```

**Code Location**: `bimsadmin/app/Models/Admin_model.php` line 3712
```php
function payment_gateway_in($operation,$amount,$id,$added_by, $medium = null){
    // Network detection
    if(substr($phone,0,5) == "25677" || substr($phone,0,5) == "25678" ||substr($phone,0,5) == "25676"){
        $type = "MTN";
    }else if(substr($phone,0,5) == "25670" || substr($phone,0,5) == "25675" || substr($phone,0,5) == "25674"){
        $type = "AIRTEL";
    }
    
    $f = 'phone='.$phone.'&network='.$type.'&amount='.$amount.'';
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://emuria.net/flexipay/marchanFromMobileProd.php',
      CURLOPT_POSTFIELDS => $f,
      // NO MERCHANT CREDENTIALS SENT
    ));
}
```

---

### 2. MTN Disbursement (Sending Money to Customer)
**Endpoint**: `https://emuria.net/flexipay/marchanToMobilePayprod.php`

**Method**: POST

**Parameters**:
```
name=Name
phone=256772123456
network=MTN
amount=50000
```

**Code Location**: `bimsadmin/app/Models/Admin_model.php` line 3215
```php
function mtn_disburse_personal($phone, $amount, $loan_id,$type) {
    $networkParam = 'MTN'; // or 'AIRTEL'
    $f = 'name=Name&phone='.urlencode($recipients).'&network='.urlencode($networkParam).'&amount='.urlencode($amount);
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://emuria.net/flexipay/marchanToMobilePayprod.php',
      CURLOPT_POSTFIELDS => $f,
      // NO MERCHANT CREDENTIALS SENT
    ));
}
```

---

### 3. Airtel Disbursement (Different Provider!)
**Endpoint**: `https://springpesa.com/developer/api/airtel/`

**Method**: POST

**Parameters**:
```
amount=50000
phone=256701234567
action=wallet2airtel
apikey=baafd5c9a207dd4c3f3c5eae63b242c8ddddcb7b51675495359f07d36af25a74
```

**Code Location**: `bimsadmin/app/Models/Admin_model.php` line 3322
```php
function airtel_disburse_personal($phone, $amount, $loan_id) {
    $postFields = 'amount='.urlencode($amount).'&phone='.urlencode($recipients).'&action=wallet2airtel&apikey=baafd5c9a207dd4c3f3c5eae63b242c8ddddcb7b51675495359f07d36af25a74';
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://springpesa.com/developer/api/airtel/',
      CURLOPT_POSTFIELDS => $postFields,
    ));
}
```

**Note**: Uses SpringPesa, not FlexiPay!

---

## Status Checking & Webhooks

### Polling Script
**File**: `bimsadmin/check_tran.php`

Runs as cron job to check payment status:
```php
// Query pending transactions
$sql = "SELECT id, trans_id FROM raw_payments WHERE status IN ('00','01') AND (pay_status IS NULL OR pay_message = 'Pending')";

// Check status with FlexiPay
$post = 'reference=' . urlencode($ref);
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://emuria.net/flexipay/checkFromMMStatusProd.php',
    CURLOPT_POSTFIELDS => $post,
]);
```

### Webhook Endpoint
**URL**: `http://localhost/bimsadmin/admin/flexipay_callback`

**Controller**: `bimsadmin/Admin.php` line 3731
```php
function flexipay_callback() {
    log_message('info', 'FlexiPay Callback Received: ' . json_encode($_POST));
    
    $result = $this->admin_model->process_payment_callback($_POST);
    
    echo json_encode($result);
}
```

**Model**: `bimsadmin/app/Models/Admin_model.php` line 5766
```php
function process_payment_callback($data) {
    $txn_id = $data['transactionReferenceNumber'];
    $status_code = $data['statusCode'];
    $status_desc = $data['statusDescription'];
    
    // Find repayment
    $getRepay = $this->db->query("SELECT * FROM repayments WHERE txn_id = '".$txn_id."' AND status='0'");
    
    // If successful (00 or 01)
    if($status_code == '00' || $status_code == '01') {
        return $this->approve_repayment($repayment->id, $status_code, $status_desc);
    }
}
```

---

## Network Detection Logic

Both systems use same phone prefix detection:

```php
// MTN prefixes
if(substr($phone,0,5) == "25677" || 
   substr($phone,0,5) == "25678" ||
   substr($phone,0,5) == "25676") {
    $network = "MTN";
}

// Airtel prefixes
else if(substr($phone,0,5) == "25670" || 
        substr($phone,0,5) == "25675" || 
        substr($phone,0,5) == "25674") {
    $network = "AIRTEL";
}
```

---

## Why Old System Works Without Credentials

### Possible Reasons:

1. **IP Whitelisting**
   - FlexiPay whitelisted the server IP address
   - No credentials needed if request comes from approved IP
   - This is common for test/sandbox environments

2. **Demo/Test Mode**
   - Account might be in test mode
   - Test mode doesn't require authentication
   - But transactions may not be real

3. **Default Account**
   - FlexiPay might have a default merchant account
   - Requests without credentials go to default account
   - Explains why Airtel works (same default account)

4. **Legacy Integration**
   - Older FlexiPay API version didn't require credentials
   - Modern API requires merchant_code and secret_key
   - Old system using legacy endpoints

---

## Differences: Old vs New System

| Feature | Old (bimsadmin) | New (Laravel) |
|---------|----------------|---------------|
| **FlexiPay Auth** | None | merchant_code + secret_key |
| **Airtel Disbursement** | SpringPesa API | FlexiPay API |
| **Collection** | FlexiPay (both networks) | FlexiPay (both networks) |
| **Status Check** | Cron polling + webhook | Real-time polling |
| **Credentials** | ❌ Not sent | ✅ Should be sent |

---

## Action Items

### 1. Check FlexiPay Account Settings
Contact FlexiPay support and ask:
- "Is our account configured with IP whitelisting?"
- "Do we need merchant credentials for production?"
- "Are we currently in test/sandbox mode?"
- "What's the difference between the old and new API?"

### 2. Test Without Credentials in New System
Try removing credentials from new system temporarily:
```php
// In MobileMoneyService.php, comment out:
// if (!empty($merchantCode)) {
//     $requestData['merchant_code'] = $merchantCode;
// }
// if (!empty($secretKey)) {
//     $requestData['secret_key'] = $secretKey;
// }
```

If it works, then IP whitelisting is active.

### 3. Keep SpringPesa for Airtel Disbursements
The old system uses SpringPesa for Airtel disbursements (not FlexiPay):
- API Key: `baafd5c9a207dd4c3f3c5eae63b242c8ddddcb7b51675495359f07d36af25a74`
- Endpoint: `https://springpesa.com/developer/api/airtel/`
- This might be why Airtel works better in old system

### 4. Database Tables Used
Old system uses these tables:
- `raw_payments` - Stores all mobile money transactions
- `repayments` - Loan repayments linked to schedules
- `disbursement_txn` - Disbursement transaction logs
- `loan_schedules` - Payment schedules
- `trail` - Audit trail for auto-reconciliation

---

## Recommendation

**Try the old system's approach in the new Laravel system**:

1. **Remove merchant credentials** temporarily
2. **Test collection with both MTN and Airtel**
3. **If it works**, confirm IP whitelisting with FlexiPay
4. **If it fails**, get proper production credentials

The fact that the old system works perfectly WITHOUT credentials strongly suggests **IP whitelisting is enabled** on your FlexiPay account.

---

## Code Comparison

### Old System (Works)
```php
// Collection - NO CREDENTIALS
$f = 'phone='.$phone.'&network='.$type.'&amount='.$amount;
curl_setopt(CURLOPT_POSTFIELDS, $f);
```

### New System (MTN Fails)
```php
// Collection - WITH CREDENTIALS
$requestData = [
    'name' => $sanitizedName,
    'phone' => $formattedPhone,
    'network' => $network,
    'amount' => $amount,
    'merchant_code' => $merchantCode,  // ← Added
    'secret_key' => $secretKey         // ← Added
];
```

**The difference**: Old system sends ONLY phone, network, amount. New system adds merchant credentials.

**Test**: Remove credentials from new system and see if it works like the old one.
