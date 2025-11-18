<?php

/**
 * BPoint IPN Handler
 * 
 * Processes webhook callbacks from BPoint when transactions complete
 * This script receives POST data from BPoint and updates CiviCRM contribution status
 * 
 * @package bpoint
 */

class CRM_Bpoint_IPN {

  /**
   * Main entry point for webhook processing
   * 
   * @return bool
   *   TRUE if successful
   */
  public static function main() {
    try {
      // Get processor ID from request
      $processorId = $_GET['processor_id'] ?? NULL;
      
      if (!$processorId) {
        return self::error('Processor ID not provided');
      }

      // Get payment processor configuration
      try {
        $processor = civicrm_api3('PaymentProcessor', 'getsingle', [
          'id' => $processorId,
        ]);
      } catch (Exception $e) {
        return self::error('Payment processor not found');
      }

      // Verify it's a BPoint processor
      if (empty($processor['class_name']) || $processor['class_name'] !== 'Payment_BPoint') {
        return self::error('Invalid or non-BPoint payment processor');
      }

      // Get payment processor instance
      $paymentClass = CRM_Core_Payment::singleton('live', $processor);

      // Process the webhook
      $result = $paymentClass->handlePaymentNotification();

      // Extract data from result
      if (empty($result['is_error'])) {
        // Success - transaction processed
        $contributionId = $result['contribution_id'] ?? NULL;
        $txnId = $result['trxn_id'] ?? NULL;
        $amount = $result['amount'] ?? NULL;
        $status = $result['contribution_status_id'] ?? 'Completed';

        // Update contribution with transaction details
        if ($contributionId && $txnId) {
          self::updateContribution($contributionId, $txnId, $amount, $status);
        } else {
          return self::error('Missing contribution ID or transaction ID');
        }

        // Return success response
        return self::success('Payment processed successfully');
      } else {
        return self::error($result['error_message'] ?? 'Unknown error');
      }

    } catch (Exception $e) {
      Civi::log()->error('BPoint IPN Error: ' . $e->getMessage());
      return self::error('IPN processing failed: ' . $e->getMessage());
    }
  }

  /**
   * Update contribution in CiviCRM
   *
   * @param int $contributionId
   *   CiviCRM contribution ID
   * @param string $txnId
   *   BPoint transaction ID
   * @param float $amount
   *   Transaction amount
   * @param string $status
   *   Contribution status
   */
  private static function updateContribution($contributionId, $txnId, $amount, $status) {
    try {
      $params = [
        'id' => $contributionId,
        'trxn_id' => $txnId,
        'contribution_status_id' => $status,
      ];

      // Only set receive_date if payment was successful
      if ($status === 'Completed') {
        $params['receive_date'] = date('YmdHis');
      }

      // Optionally verify amount matches
      if ($amount > 0) {
        // Get the existing contribution to verify amount
        $contribution = civicrm_api3('Contribution', 'getsingle', ['id' => $contributionId]);
        if (abs($contribution['total_amount'] - $amount) > 0.01) {
          Civi::log()->warning("BPoint: Amount mismatch for contribution {$contributionId}. Expected: {$contribution['total_amount']}, Received: {$amount}");
        }
      }

      civicrm_api3('Contribution', 'create', $params);

      Civi::log()->info("BPoint: Updated contribution {$contributionId} with status {$status} and trxn_id {$txnId}");

    } catch (Exception $e) {
      Civi::log()->error("BPoint: Failed to update contribution {$contributionId}: " . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Send success response
   * 
   * @param string $message
   *   Success message
   * 
   * @return bool
   */
  private static function success($message) {
    http_response_code(200);
    echo json_encode(['success' => TRUE, 'message' => $message]);
    return TRUE;
  }

  /**
   * Send error response
   * 
   * @param string $message
   *   Error message
   * 
   * @return bool
   */
  private static function error($message) {
    http_response_code(400);
    echo json_encode(['success' => FALSE, 'message' => $message]);
    Civi::log()->error('BPoint IPN: ' . $message);
    return FALSE;
  }

}
