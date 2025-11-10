<?php

/**
 * Payment Processor Type definition for BPoint
 *
 * This file defines BPoint as a payment processor type in CiviCRM
 * Managed entity - will be created/updated automatically on extension enable
 */

return [
  [
    'name' => 'BPoint',
    'entity' => 'payment_processor_type',
    'params' => [
      'version' => 3,
      'title' => 'BPoint',
      'name' => 'BPoint',
      'description' => 'BPoint Payment Processor for CiviCRM - Commonwealth Bank Payment Gateway',

      // Fields displayed in Payment Processor configuration form
      'user_name_label' => 'API Username',
      'password_label' => 'API Password',
      'signature_label' => 'Merchant Number',

      // Payment class that handles the integration
      'class_name' => 'Payment_BPoint',

      // Default URLs (can be overridden in processor config)
      'url_site_default' => 'https://www.bpoint.com.au',
      'url_api_default' => 'https://www.bpoint.com.au/webapi/v5',

      // Billing mode 4 = redirect offsite (user sent to BPoint hosted form)
      'billing_mode' => 4,

      // Payment type 1 = credit card
      'payment_type' => 1,
    ],
  ],
];
