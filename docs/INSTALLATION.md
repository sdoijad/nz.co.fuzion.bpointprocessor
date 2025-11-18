# BPoint Payment Processor - Installation Guide

## Prerequisites

Before installing this extension, ensure you have:

1. **CiviCRM 6.0 or later** (tested up to 6.4)
2. **PHP 7.4 or later**
3. **BPoint Merchant Account** - Checkout or Enterprise facility
4. **SSL Certificate** - Your site must use HTTPS
5. **cURL Extension** - PHP must have cURL enabled
6. **BPoint API Credentials**:
   - API Username
   - API Password
   - Merchant Number

## Step 1: Get BPoint Credentials

### Via BPoint Merchant Portal

1. Log in to your BPoint merchant portal at https://www.bpoint.com.au
2. Go to **Settings > API Users**
3. Click **Create API User** (or note down existing credentials)
4. You will receive:
   - **API Username** (e.g., "user123")
   - **API Password** (secure string)
   - **Merchant Number** (e.g., "5353109000000000")

### Verify Your Facility Type

- This extension requires **BPoint Checkout or Enterprise** facility
- Direct API access is supported in these facility types
- Check with your bank if you're unsure

## Step 2: Prepare CiviCRM

Ensure your CiviCRM installation is:
- Running on HTTPS/SSL
- Publicly accessible (BPoint needs to send webhooks)
- Has outbound internet access (to reach BPoint API)

### Check Resource URLs

1. Go to **Administer > System Settings > Resource URLs**
2. Verify that **Extension Resource URL** is set correctly
   - Should be relative to your web root
   - Example: `/sites/all/civicrm_extensions/`

## Step 3: Install the Extension

### Option A: Using GitHub (Recommended)

```bash
cd /path/to/civicrm/extensions

# Clone the extension
git clone https://github.com/fuzionnz/nz.co.fuzion.bpointprocessor.git

# Navigate to extension directory
cd nz.co.fuzion.bpointprocessor

# If using Composer (optional, but recommended)
composer install
```

### Option B: Manual Download

1. Download the extension: https://github.com/fuzionnz/nz.co.fuzion.bpointprocessor/archive/refs/heads/main.zip
2. Extract to your extensions directory: `/path/to/civicrm/extensions/nz.co.fuzion.bpointprocessor/`

### Option C: Using CiviCRM Extension Manager (if available)

1. Go to **Administer > System Settings > Manage Extensions**
2. Find "BPoint Payment Processor"
3. Click **Install**

## Step 4: Enable the Extension

1. Go to **Administer > System Settings > Manage Extensions**
2. Search for "BPoint"
3. Click **Enable**
4. Confirm the extension is now marked as "Enabled"

## Step 5: Create Payment Processor

1. Go to **Administer > System Settings > Payment Processors**
2. Click **+ Add Payment Processor**
3. Fill in the form:

   | Field | Value |
   |-------|-------|
   | **Payment Processor Type** | BPoint |
   | **Name** | BPoint (or your preferred name) |
   | **Description** | BPoint Payment Gateway |
   | **API Username** | *Your BPoint API username* |
   | **API Password** | *Your BPoint API password* |
   | **Merchant Number** | *Your BPoint merchant number* |
   | **Test Mode** | Yes (for testing), No (for live) |

4. Click **Save**

## Step 6: Configure Webhook (Critical!)

BPoint must be able to notify CiviCRM when payments complete:

### Get Your Webhook URL

1. Find your processor in **Payment Processors** list
2. Note the processor **ID** from the URL (e.g., `.../id=5` means ID is 5)
3. Your webhook URL is:
   ```
   https://your-domain.com/civicrm/payment/ipn?processor_name=BPoint&processor_id=5
   ```

### Configure in BPoint

1. Log in to BPoint merchant portal
2. Go to **Settings > Webhooks** or **Integration > Webhooks**
3. Click **Add Webhook**
4. Fill in:
   - **URL**: Your webhook URL from above
   - **Event Types**: `Transaction.Processed` or equivalent
   - **Format**: JSON
   - **Active**: Yes

5. Save and test the webhook

### Test Webhook Connectivity

1. In BPoint portal, click **Test** on your webhook
2. BPoint will send a test notification to your webhook URL
3. Check your CiviCRM system logs (**Reports > System Logs**) for confirmation

## Step 7: Use in Contributions

Your BPoint processor is now ready to use:

1. Create a **Contribution Page** (Events/Contributions)
2. In the payment settings, select **BPoint** as the processor
3. Users will now see BPoint as a payment option
4. When they click "Submit Payment", they'll be redirected to BPoint

## Testing

### Test Transactions

1. Set **Test Mode** to "Yes" in processor settings
2. Use BPoint test credentials
3. On the contribution form, test cards:
   - **4111111111111111** (Visa)
   - **5555555555554444** (Mastercard)
   - **6011111111111117** (Discover)
   - **378282246310005** (Amex)
4. Use any CVN (e.g., 123) and future expiry date
5. Complete the test contribution

### Verify Test Transaction

1. Go to **Contacts > Find Contributions**
2. Check for "Test" contributions
3. Look for your test contribution with status "Completed"
4. Verify transaction ID is populated

## Troubleshooting

### Extension Not Appearing

- Clear CiviCRM cache: **Administer > System Settings > Cleanup Caches**
- Verify info.xml is present in extension root
- Check extension directory permissions

### "Invalid AuthKey" Error

- Verify API Username, Password, and Merchant Number are correct
- Ensure no extra spaces in credentials
- Try regenerating API credentials in BPoint portal

### Webhooks Not Triggering

- Verify webhook URL is publicly accessible
- Check BPoint webhook logs in merchant portal
- Verify your firewall allows inbound POST requests
- Check CiviCRM logs for webhook processing errors

### Transaction Shows "Pending"

- Webhooks not configured correctly
- User didn't wait for redirect back to site
- BPoint API connection issue
- Check CiviCRM system logs for errors

### SSL/Certificate Errors

- Ensure your server has valid SSL certificate
- Update PHP certificate bundle: `pecl install ca-bundle`
- Test: `curl -I https://your-domain.com`

## Production Checklist

Before going live:

- [ ] Test Mode disabled (payment processor set to "No")
- [ ] Production BPoint credentials configured
- [ ] Webhook URL tested and working
- [ ] SSL certificate is valid
- [ ] CiviCRM logs monitored for errors
- [ ] Contribution page tested with real payment
- [ ] Transaction verified in BPoint merchant portal
- [ ] Team trained on transaction troubleshooting

## Next Steps

1. Create your first contribution page using BPoint
2. Configure contribution receipts and confirmations
3. Set up recurring donations (if supported in future)
4. Monitor transactions in BPoint merchant portal
5. Reconcile with your bank

## Support & Resources

- **CiviCRM Docs**: https://docs.civicrm.org
- **BPoint API**: https://www.bpoint.com.au/developers/v5/
- **Issue Tracker**: https://github.com/fuzionnz/nz.co.fuzion.bpointprocessor/issues
- **CiviCRM Community**: https://civicrm.org/community

## Common Issues & Solutions

### Issue: "cURL error 60: SSL certificate problem"

**Solution**: Update your CA bundle:
```bash
# For macOS
brew install ca-certificates

# For Linux/Debian
sudo apt-get install ca-certificates

# Verify cURL can access HTTPS
curl -I https://www.bpoint.com.au
```

### Issue: Processor shows in list but not selectable

**Solution**: 
1. Clear browser cache
2. Hard refresh browser (Ctrl+Shift+R)
3. Clear CiviCRM cache: **Administer > System Settings > Cleanup Caches**

### Issue: Payment redirects but doesn't return

**Solution**:
1. Check webhook is configured (even if not triggered, should still work)
2. Verify return URL in BPoint settings allows 0-second delay
3. User may have closed browser or lost connection
4. Transaction may have succeeded (check BPoint merchant portal)

---

**Last Updated**: 2025-01-01
**Version**: 1.0.0
