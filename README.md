# BPoint Payment Processor for CiviCRM

A CiviCRM payment processor extension for [BPoint](https://www.bpoint.com.au) - the Commonwealth Bank's payment gateway.

## Overview

This extension integrates BPoint's V5 REST API with CiviCRM using the **2 Party - AuthKey** integration method. This provides a secure, PCI-compliant payment processing solution.

**Key Features:**
- Secure AuthKey-based transaction processing
- Redirect-based payment form (2 Party method)
- Webhook/IPN support for transaction confirmation
- Transaction verification and fallback mechanisms
- Support for both test and live modes
- Minimal PCI scope (card data not processed on your server)

## Requirements

- CiviCRM 6.0+
- PHP 7.4+
- BPoint Checkout or Enterprise facility with:
  - API credentials (username, password, merchant number)
  - Webhook URL configured in BPoint merchant portal

## Installation

1. Download the extension to your CiviCRM extensions directory:
   ```bash
   cd /path/to/civicrm/extensions
   git clone https://github.com/fuzionnz/nz.co.fuzion.bpointprocessor.git
   ```

2. Go to **Administer > System Settings > Manage Extensions**
3. Enable the "BPoint Payment Processor" extension
4. Configure the payment processor (see Configuration section)

## Configuration

### Step 1: Get Your BPoint Credentials

1. Log in to your BPoint merchant portal
2. Go to **Settings > API Users**
3. Create or note down your API user credentials:
   - API Username
   - API Password
   - Merchant Number

### Step 2: Configure in CiviCRM

1. Navigate to **Administer > System Settings > Payment Processors**
2. Click **+ Add Payment Processor**
3. Fill in the following:
   - **Payment Processor Type:** BPoint
   - **Name:** BPoint (or your preferred name)
   - **Description:** BPoint Payment Gateway
   - **API Username:** Your BPoint API username
   - **API Password:** Your BPoint API password
   - **Merchant Number:** Your BPoint merchant number
   - **Test Mode:** Yes (for testing) / No (for live)

### Step 3: Configure Webhook URL (IPN)

BPoint needs to send notifications back to CiviCRM about transaction results:

1. From the Payment Processor URL, note the processor ID (e.g., if URL is `.../id=5` then ID is 5)
2. Your webhook URL will be:
   ```
   https://your-domain.com/civicrm/payment/ipn/5
   ```
   (Replace `5` with your processor ID)

3. Log into BPoint merchant portal
4. Go to **Settings > Webhooks**
5. Add a new webhook with:
   - **URL:** Your webhook URL from above
   - **Event Type:** Transaction.Processed
   - **Format:** JSON

## How It Works

### Payment Flow

1. **User submits contribution form** on your CiviCRM site
2. **CiviCRM calls doPayment()** 
3. **Extension requests AuthKey** from BPoint API
4. **User is redirected** to BPoint's hosted payment form
5. **User enters card details** (on BPoint's secure server)
6. **BPoint processes payment** and sends user back to your site
7. **Webhook/IPN notifies** CiviCRM of result
8. **Contribution is marked** Complete (if successful) or Failed (if declined)

### Transaction Verification

If the webhook doesn't arrive (e.g., user doesn't complete redirect), the extension can retrieve transaction status using the transaction number stored in CiviCRM.

## API Endpoints Used

The extension uses the following BPoint V5 API endpoints:

- **Create AuthKey:** `POST /webapi/v2/txns/processtxnauthkey`
- **Process Transaction:** Redirect to `https://www.bpoint.com.au/pay/{MerchantShortName}?in_pay_token={authKey}`
- **Get Transaction Result:** `GET /webapi/v2/txns/search` (for verification)
- **Webhook:** Receives callbacks when transactions complete

## Authentication

All API requests use Base64-encoded HTTP Authorization header:

```
Authorization: Base64(username|merchantnumber:password)
```

The extension handles this encoding automatically.

## Testing

### Test Credentials

BPoint provides test credentials. Use these to test your integration:

1. Configure a second payment processor with test credentials
2. Set **Test Mode** to "Yes"
3. All transactions will route to BPoint's sandbox

### Test Cards

Use these test cards in the BPoint hosted payment form:

| Card Number | CVN | Expiry | Result |
|------------|-----|--------|--------|
| 4111111111111111 | 123 | 12/25 | Approved |
| 5555555555554444 | 123 | 12/25 | Approved |
| 6011111111111117 | 123 | 12/25 | Approved |
| 378282246310005 | 1234 | 12/25 | Approved |

## Troubleshooting

### Transaction status shows "Pending"

This occurs when CiviCRM doesn't receive the webhook confirmation. To resolve:

1. Check that webhook URL is configured correctly in BPoint merchant portal
2. Verify CiviCRM can receive inbound HTTP requests
3. Check CiviCRM system logs for errors (Reports > System Logs)
4. Manually verify in BPoint merchant portal that transaction was processed

### "Invalid AuthKey" Error

- Check that API credentials are correct
- Verify merchant number format (no spaces)
- Ensure test/live mode matches your credentials

### Webhooks not triggering

- Confirm webhook URL is publicly accessible
- Check BPoint merchant portal webhook logs
- Verify your firewall isn't blocking POST requests from BPoint

## Support

For issues related to this extension, please:

1. Check the GitHub issues: https://github.com/fuzionnz/nz.co.fuzion.bpointprocessor/issues
2. Consult BPoint API documentation: https://www.bpoint.com.au/developers/v5/
3. Contact your CiviCRM support provider

## License

AGPLv3 - See LICENSE file for details

## Credits

Developed for secure, PCI-compliant payment processing with CiviCRM using BPoint's V5 API.
