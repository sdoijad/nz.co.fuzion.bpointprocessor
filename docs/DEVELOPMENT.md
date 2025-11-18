# BPoint Extension - Development Guide

## Architecture Overview

The BPoint extension follows CiviCRM's payment processor architecture:

```
CiviCRM Contribution Form
         ↓
CRM_Core_Payment_BPoint::doPayment()
         ↓
BPoint V5 API (/txns/processtxnauthkey)
         ↓
Generate AuthKey
         ↓
Redirect User to BPoint Hosted Form
         ↓
User Enters Card Details (on BPoint's server)
         ↓
BPoint Processes Payment
         ↓
User Redirected Back + Webhook Notification
         ↓
CRM_Bpoint_IPN::handlePaymentNotification()
         ↓
Contribution Updated with Transaction Status
```

## File Structure

```
nz.co.fuzion.bpointprocessor/
├── info.xml                          # Extension metadata
├── composer.json                     # Composer package definition
├── bpoint.php                        # Main extension file with hooks
├── bpoint.civix.php                  # Auto-generated Civix scaffolding
├── README.md                         # User documentation
├── LICENSE                           # AGPLv3 license
├── CRM/
│   ├── Core/Payment/
│   │   └── BPoint.php               # Payment processor class (main logic)
│   └── Bpoint/
│       └── IPN.php                  # Webhook/IPN handler
├── managed/
│   └── PaymentProcessorType.mgd.php # Payment processor type definition
├── docs/
│   ├── INSTALLATION.md              # Installation guide
│   └── DEVELOPMENT.md               # This file
└── templates/                        # (Optional) Smarty templates
```

## Key Classes

### CRM_Core_Payment_BPoint

Main payment processor class extending `CRM_Core_Payment`.

**Key Methods:**

- `__construct($mode, &$paymentProcessor)` - Initialize processor
- `doPayment(&$params)` - Process payment (redirect to BPoint)
- `handlePaymentNotification()` - Handle webhook callback
- `prepareAuthKeyRequest($params)` - Prepare API request
- `buildPaymentUrl($authKey, $merchantShortName)` - Build redirect URL
- `callBPointAPI($endpoint, $data, $useGet)` - Make API calls
- `getTransactionDetails($txnNumber)` - Verify transaction status

### CRM_Bpoint_IPN

IPN/Webhook handler for processing BPoint callbacks.

**Key Methods:**

- `main()` - Main entry point for webhook processing
- `updateContribution($contributionId, $txnId, $status)` - Update CiviCRM

## API Reference

### BPoint Endpoints Used

#### 1. Create AuthKey (2 Party - AuthKey)

**Endpoint**: `POST /webapi/v2/txns/processtxnauthkey`

**Request**:
```php
{
  "ProcessTxnData": {
    "Action": "payment",
    "Amount": 5000,           // In cents
    "Currency": "AUD",
    "MerchantReference": "123",  // Contribution ID
    "Customer": {
      "Email": "user@example.com",
      "FirstName": "John",
      "LastName": "Doe",
      "Address": {
        "AddressLine1": "123 Street",
        "City": "Melbourne",
        "State": "VIC",
        "PostCode": "3000",
        "CountryCode": "AUS"
      }
    }
  },
  "HppParameters": {
    "ReturnBarUrl": "https://your-site.com/civicrm/payment/ipn?processor_id=5"
  }
}
```

**Response**:
```json
{
  "APIResponse": {
    "ResponseCode": 0,
    "ResponseText": "Success"
  },
  "AuthKey": "abc123xyz789",
  "MerchantShortName": "mymerchant"
}
```

#### 2. Redirect URL

After receiving AuthKey, redirect browser to:

```
https://www.bpoint.com.au/pay/{MerchantShortName}?in_pay_token={AuthKey}
```

User enters card details on this hosted page (reduces PCI scope).

#### 3. Webhook Callback

BPoint sends this to your webhook URL after transaction completes:

**POST Body**:
```json
{
  "TxnNumber": "1234567890",
  "AuthKey": "abc123xyz789",
  "MerchantReference": "123",
  "Amount": 5000,
  "ResultCode": 0,
  "ResponseText": "Success",
  "CardDetails": {
    "MaskedCardNumber": "512345***346",
    "CardType": "MC"
  }
}
```

#### 4. Search/Retrieve Transaction (Fallback)

**Endpoint**: `GET /webapi/v2/txns/search`

**Query Parameters**:
- `TxnNumber`: BPoint transaction number

**Response**: Transaction details matching webhook

## Authentication

All API requests use HTTP Authorization header with Base64 encoding:

```
Authorization: Base64(username|merchantnumber:password)

Example:
Authorization: dXNlcm5hbWV8NTM1MzEwOTAwMDAwMDAwMDpQYXNzdzByZA==
```

The extension handles this automatically in `callBPointAPI()`.

## Payment Flow Details

### 1. User Submits Contribution Form

CiviCRM collects:
- Billing details (name, address, email)
- Contribution amount
- Payment processor selection

### 2. doPayment() Called

```php
$result = $paymentProcessor->doPayment($params);
// Returns: ['redirect_url' => 'https://bpoint...', 'trxn_id' => $authKey]
```

CiviCRM redirects to `redirect_url`.

### 3. User on BPoint Hosted Form

- User enters card number, CVN, expiry
- BPoint validates card with processor
- Payment is authorized or declined

### 4. User Redirected Back

After payment processing, BPoint:
- Redirects user back to your return URL
- Sends webhook notification to callback URL

### 5. handlePaymentNotification() Processes Webhook

Webhook handler:
1. Receives POST from BPoint
2. Extracts transaction status
3. Updates CiviCRM contribution
4. Returns success/error response

### 6. Contribution Updated

If successful:
- Status: "Completed"
- Transaction ID: BPoint transaction number
- Contribution recorded in contact record

## Error Handling

### API Errors

Caught in `callBPointAPI()`:

```php
// Connection error
throw new Exception("BPoint API connection error: {$curlError}");

// HTTP error
throw new Exception("BPoint API returned HTTP {$httpCode}: {$response}");

// JSON parsing error
throw new Exception("Invalid JSON response from BPoint API");

// API error response
throw new Exception("BPoint API error: {$errorMsg}");
```

### Payment Errors

If transaction fails:
- Contribution status: "Failed"
- Error message: BPoint error code and text
- User can retry contribution

### Webhook Errors

If webhook doesn't arrive:
- Contribution stays "Pending"
- Admin can manually verify in BPoint portal
- Can call `getTransactionDetails()` to verify status

## Configuration

### Payment Processor Fields

Stored in `civicrm_payment_processor`:

- `user_name`: API Username
- `password`: API Password
- `signature`: Merchant Number
- `is_test`: 1 for test mode, 0 for live

### Settings (Optional)

Can add custom settings:

```php
// Enable debug logging
Civi::settings()->set('bpoint_debug_mode', TRUE);
```

## Testing

### Unit Test Example

```php
<?php

class CRM_Bpoint_PaymentTest extends CiviUnitTestCase {

  public function testAuthKeyRequest() {
    $processor = $this->createPaymentProcessor();
    $params = [
      'amount' => 100,
      'currency' => 'AUD',
      'billing_first_name' => 'John',
      'billing_last_name' => 'Doe',
      'email' => 'john@example.com',
      'contribution_id' => 123,
    ];

    $payment = CRM_Core_Payment::singleton('test', $processor);
    $result = $payment->doPayment($params);

    $this->assertArrayHasKey('redirect_url', $result);
    $this->assertStringContainsString('bpoint.com.au/pay/', $result['redirect_url']);
  }

}
```

### Manual Testing

1. **Test Mode Setup**
   - Create payment processor with test credentials
   - Set Test Mode to "Yes"

2. **Test Card**
   - Use 4111111111111111 (Visa)
   - CVN: 123
   - Expiry: 12/25

3. **Create Test Contribution**
   - Use test payment processor
   - Submit $10 test contribution
   - Verify redirect to BPoint

4. **Complete Payment**
   - Enter test card details
   - Complete payment on BPoint

5. **Verify Result**
   - Check contribution status (should be Completed)
   - Verify transaction ID populated
   - Check BPoint merchant portal

## Extending the Extension

### Add Recurring Payment Support

Would require:

1. Implement `supportsRecurring()` return TRUE
2. Add token/reference transaction support
3. Implement `createRecurringPayment()` method
4. Handle recurring payment updates
5. Implement cron job for recurring billing

### Add 3 Party Methods

Could add support for:

- 3 Party - JavaScript Simple
- 3 Party - JavaScript Advanced
- 3 Party - Iframe Fields

Each would have:
- Different `doPayment()` implementation
- Client-side JavaScript handling
- Separate tokenization flow

## Security Considerations

### PCI Compliance

✅ **Compliant** - This extension:
- Never handles card data directly
- Uses hosted payment form on BPoint
- Communicates only with BPoint API
- Reduces PCI scope to "Service Provider" level

### API Credential Security

- Credentials stored in CiviCRM database (encrypted)
- Never logged or displayed
- Only transmitted in Authorization header to BPoint
- Use strong passwords

### HTTPS/SSL

- All API calls use HTTPS
- Verify SSL certificate in `callBPointAPI()`
- Users redirected to HTTPS-only BPoint form

### CSRF Protection

- CiviCRM handles CSRF tokens
- IPN endpoint validates processor ID
- Additional validation in webhook handler

## Performance

### Optimization Tips

1. **Caching**
   ```php
   // Cache merchant details
   \Civi::cache('default')->set('bpoint_merchant_info', $data, 3600);
   ```

2. **Async Processing** (Future)
   - Use CiviCRM queuing for slow operations
   - Process webhooks asynchronously

3. **Error Retry**
   ```php
   // Retry failed API calls
   $maxRetries = 3;
   for ($i = 0; $i < $maxRetries; $i++) {
     try {
       return $this->callBPointAPI($endpoint, $data);
     } catch (Exception $e) {
       if ($i === $maxRetries - 1) throw $e;
       sleep(2 ** $i); // Exponential backoff
     }
   }
   ```

## Troubleshooting Development

### Debug Logging

Enable PHP error logging:

```php
// In bpoint.php
error_log('BPoint: ' . json_encode($data));
```

### API Response Debugging

Check raw API responses:

```php
// In callBPointAPI()
Civi::log()->debug('BPoint API Response: ' . $response);
```

### Webhook Testing

Test webhook locally:

```bash
# Send test webhook
curl -X POST http://localhost/civicrm/payment/ipn?processor_id=5 \
  -H "Content-Type: application/json" \
  -d '{
    "TxnNumber": "123456",
    "MerchantReference": "100",
    "Amount": 5000,
    "ResultCode": 0,
    "ResponseText": "Success"
  }'
```

## Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature/your-feature`
3. Make changes and test
4. Submit pull request with description
5. Code review and merge

## Resources

- **CiviCRM Payment Processors**: https://docs.civicrm.org/dev/en/latest/extensions/payment-processors/
- **BPoint API V5**: https://www.bpoint.com.au/developers/v5/
- **CiviCRM Hooks**: https://docs.civicrm.org/dev/en/latest/hooks/
- **PSR Standards**: https://www.php-fig.org/

---

**Last Updated**: 2025-01-01
**Version**: 1.0.0
