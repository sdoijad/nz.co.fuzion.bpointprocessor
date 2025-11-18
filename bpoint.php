<?php

/**
 * BPoint Payment Processor Extension for CiviCRM
 * 
 * This extension integrates BPoint (Commonwealth Bank Payment Gateway) with CiviCRM
 * using the V5 API and 2 Party - AuthKey integration method.
 * 
 * @package bpoint
 */

require_once 'bpoint.civix.php';
use CRM_Bpoint_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config()
 * 
 * Set up configuration when CiviCRM boots
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config
 */
function bpoint_civicrm_config(&$config) {
  _bpoint_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu()
 * 
 * Register custom menu items
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function bpoint_civicrm_xmlMenu(&$menu) {
  _bpoint_civix_civicrm_xmlMenu($menu);
}

/**
 * Implements hook_civicrm_install()
 * 
 * Execute installation tasks
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function bpoint_civicrm_install() {
  _bpoint_civix_civicrm_install();
  bpoint_setup_routes();
}

/**
 * Implements hook_civicrm_postInstall()
 * 
 * Perform tasks after extension installation
 */
function bpoint_civicrm_postInstall() {
  _bpoint_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall()
 * 
 * Execute uninstallation tasks
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function bpoint_civicrm_uninstall() {
  _bpoint_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable()
 * 
  * When extension is enabled
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function bpoint_civicrm_enable() {
  _bpoint_civix_civicrm_enable();
  bpoint_setup_routes();
}

/**
 * Implements hook_civicrm_disable()
 * 
 * When extension is disabled
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function bpoint_civicrm_disable() {
  _bpoint_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade()
 * 
 * Handle extension upgrades
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function bpoint_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _bpoint_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed()
 * 
 * Define managed entities (like payment processor type)
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function bpoint_civicrm_managed(&$entities) {
  _bpoint_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes()
 * 
 * Declare case types (not used in this extension)
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function bpoint_civicrm_caseTypes(&$caseTypes) {
  _bpoint_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules()
 * 
 * Declare Angular modules (not used in this extension)
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function bpoint_civicrm_angularModules(&$angularModules) {
  _bpoint_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders()
 * 
 * Declare folders where CiviCRM should look for settings definitions
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function bpoint_civicrm_alterSettingsFolders(&$metaFolders = NULL) {
  _bpoint_civix_civicrm_alterSettingsFolders($metaFolders);
}

/**
 * Implements hook_civicrm_entityTypes()
 * 
 * Declare entity types (not used in this extension)
 * 
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function bpoint_civicrm_entityTypes(&$entityTypes) {
  _bpoint_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_permission()
 *
 * Define custom permissions
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_permission
 */
function bpoint_civicrm_permission(&$permissions) {
  $permissions['administer BPoint'] = [
    'label' => ts('Administer BPoint Settings'),
    'description' => ts('Configure and manage BPoint payment processor settings'),
  ];
}

/**
 * Implements hook_civicrm_pageRun()
 * 
 * Handle page rendering (used for IPN processing)
 */
function bpoint_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');
  
  // Handle BPoint IPN callback
  if (strpos($_SERVER['REQUEST_URI'], '/civicrm/payment/ipn') !== FALSE) {
    if (isset($_GET['processor_name']) && $_GET['processor_name'] === 'BPoint') {
      CRM_Bpoint_IPN::main();
      CRM_Utils_System::civiExit();
    }
  }
}

/**
 * Setup custom routes for BPoint IPN
 * 
 * This allows BPoint to POST to /civicrm/payment/ipn and have it processed
 */
function bpoint_setup_routes() {
  // The route is defined in the core payment system
  // CiviCRM handles /civicrm/payment/ipn natively
}
