<?php

/**
 * BPoint Payment Processor for CiviCRM
 *
 * @package CRM
 * @subpackage Core_Payment
 */

class CRM_Core_Payment_BPoint extends CRM_Core_Payment {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * API endpoints
   */
  const BPOINT_API_URL_LIVE = 'https://www.bpoint.com.au/webapi/v5';
  const BPOINT_API_URL_TEST = 'https://www.bpoint.com.au/webapi/v5'; // BPoint uses same URL, credentials differ

  /**
   * Constructor
   *
   * @param string $mode
   *   the mode of operation: live or test
   *
   * @param $paymentProcessor
   */
  public function __construct($mode, &$paymentProcessor) {
    parent::__construct($mode, $paymentProcessor);
    $this->_mode = $mode;
  }

  /**
   * Singleton function used to manage this class
   *
   * @param string $mode
   *   the mode of operation: live or test
   *
   * @param object $paymentProcessor
   *   the payment processor object
   *
   * @return object
   *   The BPoint payment processor object
   */
  public static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (!isset(self::$_singleton[$processorName]) || self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_BPoint($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * Submit a payment using BPoint
   *
   * @param array $params
   *   assoc array of input parameters for this transaction
   *
   * @return array
   *   the result in a nice formatted array for calling functions to process
   *   it will throw exception if any thing seriously wrong
   *
   * @throws Exception
   */
  public function doPayment(&$params, $component = 'contribute') {
    if ($this->_mode == 'test') {
      $this->logTransaction($params, 'TEST MODE');
    }

    // Convert PropertyBag to array if needed (CiviCRM 5.51+)
    if (is_object($params)) {
      $params = $params->getValues();
    }
    
    try {
      // Prepare the request for AuthKey
      $authKeyData = $this->prepareAuthKeyRequest($params);

      // Call BPoint API to get AuthKey
      $response = $this->callBPointAPI('txns/processtxnauthkey', $authKeyData);

      // Check if we got a valid response
      if (empty($response) || !is_array($response)) {
        throw new Exception('Empty response from BPoint API');
      }

      // Check for API errors
      if (!empty($response['APIResponse']['ResponseCode']) && $response['APIResponse']['ResponseCode'] !== 0) {
        $errorMsg = !empty($response['APIResponse']['ResponseText']) ? $response['APIResponse']['ResponseText'] : 'Unknown error';
        throw new Exception('BPoint API Error: ' . $errorMsg);
      }

      // Get the AuthKey
      if (empty($response['AuthKey'])) {
        throw new Exception('BPoint API did not return AuthKey');
      }

      $authKey = $response['AuthKey'];
      $merchantShortName = !empty($response['MerchantShortName']) ? $response['MerchantShortName'] : '';

      // Build the payment URL for redirect
      $redirectUrl = $this->buildPaymentUrl($authKey, $merchantShortName);

      return [
        'redirect_url' => $redirectUrl,
        'is_error' => 0,
        'trxn_id' => $authKey,
      ];

    } catch (Exception $e) {
      \Civi::log()->error('BPoint doPayment Error: ' . $e->getMessage());
      throw new \CRM_Core_Payment_ProcessorException('BPoint payment processing failed: ' . $e->getMessage());
    }
  }

  /**
   * Prepare the AuthKey request for BPoint
   *
   * @param array $params
   *   The payment parameters
   *
   * @return string
   *   JSON encoded request data
   *
   * @throws Exception
   */
  protected function prepareAuthKeyRequest($params) {
    // Validate required fields
    if (empty($params['amount']) || $params['amount'] <= 0) {
      throw new Exception('Invalid or missing amount');
    }

    // Amount must be in cents
    $amount = (int) round($params['amount'] * 100);

    // Get customer details
    $firstName = !empty($params['billing_first_name']) ? $params['billing_first_name'] : '';
    $lastName = !empty($params['billing_last_name']) ? $params['billing_last_name'] : '';
    $email = !empty($params['email']) ? $params['email'] : '';

    // Validate required customer fields
    if (empty($email)) {
      throw new Exception('Email address is required');
    }

    // Get merchant reference (contribution ID)
    $merchantReference = !empty($params['invoiceID']) ? $params['invoiceID'] : '';
    if (empty($merchantReference) && !empty($params['contributionID'])) {
      $merchantReference = $params['contributionID'];
    }

    if (empty($merchantReference)) {
      throw new Exception('Invoice ID or Contribution ID is required');
    }

    // Get billing address
    $address1 = !empty($params['billing_street_address']) ? $params['billing_street_address'] : '';
    $city = !empty($params['billing_city']) ? $params['billing_city'] : '';
    $state = !empty($params['billing_state_province']) ? $params['billing_state_province'] : '';
    $postcode = !empty($params['billing_postal_code']) ? $params['billing_postal_code'] : '';
    $country = !empty($params['billing_country']) ? $params['billing_country'] : 'AUS';

    // Get currency
    $currency = !empty($params['currencyID']) ? $params['currencyID'] : 'AUD';

    // Build the request
    $request = [
      'ProcessTxnData' => [
        'Action' => 'payment',
        'Amount' => $amount,
        'Currency' => $currency,
        'MerchantReference' => $merchantReference,
        'Type' => 'internet',
        'Customer' => [
          'FirstName' => $firstName,
          'LastName' => $lastName,
          'Email' => $email,
          'Address' => [
            'AddressLine1' => $address1,
            'City' => $city,
            'State' => $state,
            'PostCode' => $postcode,
            'CountryCode' => $country,
          ],
        ],
      ],
      'HppParameters' => [
        'ReturnUrl' => $this->getReturnUrl($params),
        'CallbackUrl' => $this->getNotifyUrl(),
      ],
    ];

    return json_encode($request);
  }

  /**
   * Call BPoint API
   *
   * @param string $endpoint
   *   The API endpoint (relative to base URL)
   *
   * @param string $data
   *   JSON encoded request data
   *
   * @return array
   *   The decoded JSON response
   *
   * @throws Exception
   */
  protected function callBPointAPI($endpoint, $data) {
    $apiUrl = ($this->_mode == 'test') ? self::BPOINT_API_URL_TEST : self::BPOINT_API_URL_LIVE;
    $url = $apiUrl . '/' . $endpoint;

    // Create authentication header
    $username = $this->_paymentProcessor['user_name'] ?? '';
    $password = $this->_paymentProcessor['password'] ?? '';
    $merchantNumber = $this->_paymentProcessor['signature'] ?? '';

    if (empty($username) || empty($password) || empty($merchantNumber)) {
      throw new Exception('BPoint credentials not configured');
    }

    // Create Base64 auth string
    $authString = "{$username}|{$merchantNumber}";
    $authHeader = 'Authorization: Basic ' . base64_encode($authString . ':' . $password);

    // Prepare cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      $authHeader,
      'Content-Type: application/json',
    ]);

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Check for cURL errors
    if ($curlError) {
      throw new Exception('cURL Error: ' . $curlError);
    }

    // Check HTTP response code
    if ($httpCode < 200 || $httpCode >= 300) {
      throw new Exception("BPoint API returned HTTP {$httpCode}");
    }

    // Decode response
    $decodedResponse = json_decode($response, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception('Invalid JSON response from BPoint API: ' . json_last_error_msg());
    }

    return $decodedResponse;
  }

  /**
   * Build the payment redirect URL
   *
   * @param string $authKey
   *   The authentication key from BPoint
   *
   * @param string $merchantShortName
   *   The merchant short name
   *
   * @return string
   *   The redirect URL
   */
  protected function buildPaymentUrl($authKey, $merchantShortName) {
    return "https://www.bpoint.com.au/pay/{$merchantShortName}?in_pay_token={$authKey}";
  }

  /**
   * Get the return URL after payment (where user is redirected)
   *
   * @param array $params
   *   Payment parameters
   *
   * @return string
   *   The return URL
   */
  protected function getReturnUrl($params) {
    $qfKey = $params['qfKey'] ?? '';
    return CRM_Utils_System::url('civicrm/contribute/transact', "_qf_ThankYou_display=true&qfKey={$qfKey}", TRUE, NULL, FALSE);
  }

  /**
   * Get the notification/callback URL for server-to-server notifications
   *
   * @return string
   *   The notification URL
   */
  protected function getNotifyUrl() {
    $processorId = $this->_paymentProcessor['id'] ?? '';
    $processorName = $this->_paymentProcessor['name'] ?? 'BPoint';
    return CRM_Utils_System::url('civicrm/payment/ipn', "processor_id={$processorId}&processor_name={$processorName}", TRUE, NULL, FALSE);
  }

  /**
   * Handle incoming payment notifications (webhook/IPN)
   *
   * @return array
   *   Result array with status and transaction info
   */
  public function handlePaymentNotification() {
    try {
      // Get the raw POST data
      $rawData = file_get_contents('php://input');
      $notification = json_decode($rawData, TRUE);

      if (empty($notification)) {
        \Civi::log()->error('BPoint IPN: Empty notification received');
        return [
          'is_error' => 1,
          'error_message' => 'Empty notification data',
        ];
      }

      // Extract transaction details
      $txnNumber = $notification['TxnNumber'] ?? '';
      $resultCode = $notification['ResultCode'] ?? '';
      $merchantReference = $notification['MerchantReference'] ?? '';
      $amount = isset($notification['Amount']) ? ($notification['Amount'] / 100) : 0; // Convert from cents

      if (empty($txnNumber) || empty($merchantReference)) {
        \Civi::log()->error('BPoint IPN: Missing transaction details');
        return [
          'is_error' => 1,
          'error_message' => 'Missing transaction details',
        ];
      }

      // Check if payment was successful
      $isSuccessful = ($resultCode === '0' || $resultCode === 0);

      // Find the contribution by merchant reference (invoice ID)
      try {
        $contribution = civicrm_api3('Contribution', 'getsingle', [
          'invoice_id' => $merchantReference,
        ]);
        $contributionId = $contribution['id'];
      } catch (Exception $e) {
        \Civi::log()->error("BPoint IPN: Could not find contribution with invoice_id={$merchantReference}");
        return [
          'is_error' => 1,
          'error_message' => 'Contribution not found',
        ];
      }

      // Log the transaction
      \Civi::log()->info("BPoint IPN: TxnNumber={$txnNumber}, ResultCode={$resultCode}, MerchantRef={$merchantReference}, ContributionID={$contributionId}");

      // Return result
      return [
        'is_error' => 0,
        'contribution_id' => $contributionId,
        'trxn_id' => $txnNumber,
        'amount' => $amount,
        'contribution_status_id' => $isSuccessful ? 'Completed' : 'Failed',
        'error_message' => $isSuccessful ? '' : "BPoint Result Code: {$resultCode}",
      ];

    } catch (Exception $e) {
      \Civi::log()->error('BPoint handlePaymentNotification Error: ' . $e->getMessage());
      return [
        'is_error' => 1,
        'error_message' => 'Error processing notification: ' . $e->getMessage(),
      ];
    }
  }

  /**
   * Check if processor is properly configured
   * This method is required by the parent CRM_Core_Payment class
   *
   * @return string|null
   *   An error message if the processor is not properly configured, otherwise NULL
   */
  public function checkConfig() {
    $config = [];

    // Check if credentials are configured
    if (empty($this->_paymentProcessor['user_name'])) {
      $config[] = ts('API Username is not configured for this payment processor.');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $config[] = ts('API Password is not configured for this payment processor.');
    }

    if (empty($this->_paymentProcessor['signature'])) {
      $config[] = ts('Merchant Number is not configured for this payment processor.');
    }

    if (!empty($config)) {
      return implode(' ', $config);
    }

    return NULL;
  }

  /**
   * Log transaction details for debugging
   *
   * @param array $params
   *   Transaction parameters
   *
   * @param string $message
   *   Message to log
   */
  protected function logTransaction($params, $message = '') {
    $logData = [
      'message' => $message,
      'amount' => $params['amount'] ?? '',
      'contribution_id' => $params['contributionID'] ?? '',
      'invoice_id' => $params['invoiceID'] ?? '',
    ];
    \Civi::log()->info('BPoint Transaction: ' . json_encode($logData));
  }

}
