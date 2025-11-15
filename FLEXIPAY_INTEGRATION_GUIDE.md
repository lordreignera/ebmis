# FlexiPay Integration Guide - MTN Mobile Money Setup

## Important Discovery

Your old system uses **Stanbic Bank FlexiPay API** (gateway.apps.platform.stanbicbank.co.ug)  
Your new Laravel system uses **Emuria FlexiPay API** (emuria.net/flexipay/)

These are **DIFFERENT services** with different credentials.

---

## Option 1: Continue with Emuria FlexiPay (Current Approach)

### Status
❌ **Not Working** - MTN payments don't send USSD prompts  
✅ **Airtel Works** - Airtel payments successfully send prompts

### Required Credentials from Emuria
Contact Emuria support at https://emuria.net to get:
- `FLEXIPAY_MERCHANT_CODE`
- `FLEXIPAY_SECRET_KEY`

### Configuration Steps
1. Get credentials from Emuria support
2. Update `.env` file:
   ```env
   FLEXIPAY_API_URL=https://emuria.net/flexipay/marchanFromMobileProd.php
   FLEXIPAY_MERCHANT_CODE=your_merchant_code
   FLEXIPAY_SECRET_KEY=your_secret_key
   FLEXIPAY_ENABLED=true
   MOBILE_MONEY_TEST_MODE=false
   ```
3. Clear cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

---

## Option 2: Switch to Stanbic Bank FlexiPay (Proven Working)

### Status
✅ **Credentials Available** - Found in old system  
✅ **MTN & Airtel Supported** - Stanbic supports both networks

### Available Credentials (From Old System)
```
Client ID (API Key):  d9b777335bde2e6d25db4dd0412de846
Client Secret:        3ee79ec68493ecc0dca4d07a87ea71f0
Merchant Code:        243575
Client ID Name:       EBIMSPRD
Password:             I4de39GwV739/lqXBXoXzJmZ1nKwvp0oIXZYa8UsPnJQoFlAKwHZNISdx6L3f/Ga
```

### Stanbic API Endpoints
- **OAuth Token:** `https://gateway.apps.platform.stanbicbank.co.ug/ug/oauth2/token`
- **Collection (Receive Money):** `https://gateway.apps.platform.stanbicbank.co.ug/fp/v1.1/merchantpayment`
- **Disbursement (Send Money):** `https://gateway.apps.platform.stanbicbank.co.ug/fp-domestic/v1.0/mobiletransfer`
- **Status Check:** `https://gateway.apps.platform.stanbicbank.co.ug/fp/v1.1/merchantpaymentstatus`

### Implementation Required
You would need to create a new service class for Stanbic integration:

1. **Authentication Flow:**
   - Get OAuth token using client credentials
   - Token expires after certain time
   - Use token in subsequent API calls

2. **Request Signing:**
   - Sign each request with RSA private key (found in old files)
   - Include signature in `x-signature` header

3. **Network Parameter:**
   - MTN network code: `MTN`
   - Airtel network code: `AIRTEL`

---

## Recommendation

### ⭐ Recommended: Option 2 (Stanbic Bank FlexiPay)

**Why?**
1. ✅ You already have working production credentials
2. ✅ The old system proves it works for both MTN and Airtel
3. ✅ No need to wait for new vendor approval
4. ✅ Established relationship with Stanbic
5. ✅ More secure with signature-based authentication

**Effort Required:**
- Create new `StanbicFlexiPayService` class
- Implement OAuth token management
- Implement request signing with RSA
- Update MobileMoneyService to use Stanbic API
- Test thoroughly with both MTN and Airtel

**Estimated Time:** 4-6 hours of development + testing

---

## Option Comparison

| Feature | Emuria FlexiPay | Stanbic FlexiPay |
|---------|-----------------|------------------|
| MTN Support | ❌ Not working | ✅ Working |
| Airtel Support | ✅ Working | ✅ Working |
| Credentials | ❌ Missing | ✅ Available |
| Setup Time | Unknown (waiting for vendor) | Immediate |
| Security | Basic | ✅ OAuth + RSA Signing |
| Documentation | Limited | Available in old code |
| Support | Emuria.net | Stanbic Bank |
| Cost | Unknown | Already setup |

---

## Next Steps

### If Choosing Option 1 (Emuria):
1. Contact Emuria support immediately
2. Request MTN merchant activation
3. Get production credentials
4. Test with small amounts
5. Document and monitor

### If Choosing Option 2 (Stanbic): ⭐ Recommended
1. I'll create a complete Stanbic FlexiPay service integration
2. Migrate old RSA private key securely
3. Implement OAuth token caching
4. Test with both MTN and Airtel
5. Update all payment flows to use Stanbic API

---

## Security Notes

### Stanbic Private Key
The RSA private key found in the old system should be:
- ✅ Stored securely (not in git)
- ✅ Moved to `.env` or secure key management
- ✅ Access restricted to application only
- ✅ Rotated periodically

### Credentials Storage
All API credentials should be stored in `.env` file:
```env
# Stanbic FlexiPay
STANBIC_CLIENT_ID=d9b777335bde2e6d25db4dd0412de846
STANBIC_CLIENT_SECRET=3ee79ec68493ecc0dca4d07a87ea71f0
STANBIC_MERCHANT_CODE=243575
STANBIC_CLIENT_NAME=EBIMSPRD
STANBIC_PASSWORD=I4de39GwV739/lqXBXoXzJmZ1nKwvp0oIXZYa8UsPnJQoFlAKwHZNISdx6L3f/Ga
STANBIC_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----\nMIIEowIBAA..."
```

---

## Testing Checklist

After implementation, test:
- [ ] MTN collection (receive money from MTN user)
- [ ] MTN disbursement (send money to MTN user)
- [ ] Airtel collection (receive money from Airtel user)
- [ ] Airtel disbursement (send money to Airtel user)
- [ ] Transaction status checking
- [ ] Error handling for failed transactions
- [ ] Callback/webhook handling (if applicable)
- [ ] Amount limits validation
- [ ] Phone number format validation

---

## Support Contacts

**Stanbic Bank FlexiPay Support:**
- Website: https://www.stanbicbank.co.ug
- Business Banking: +256 414 230 012
- Digital Services: api.support@stanbicbank.co.ug (check their official docs)

**Emuria FlexiPay Support:**
- Website: https://emuria.net
- Look for contact details on their website

---

**Decision Required:** Which option do you want to proceed with?

I recommend **Option 2 (Stanbic)** because you have all credentials and it's proven to work.
