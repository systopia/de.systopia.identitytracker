<?php
/*-------------------------------------------------------+
| Contact ID Tracker                                     |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


require_once 'identitytracker.civix.php';

/**
 * implement this hook to make sure we capture all ID changes
 */
function identitytracker_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if ($op == 'edit' || $op == 'create' || $op == 'update') {
    if ($objectName == 'Individual' || $objectName == 'Organization' || $objectName == 'Household') {
      if (!empty($objectRef->external_identifier) && ($objectRef->external_identifier != 'null')) {
        $exists = CRM_Core_DAO::singleValueQuery(CRM_Identitytracker_Configuration::getLookupSQL(), array(
          1 => array($objectId, 'Integer'),
          2 => array(CRM_Identitytracker_Configuration::TYPE_EXTERNAL, 'String'),
          3 => array($objectRef->external_identifier, 'String'),
          ));
        if (!$exists) {
          CRM_Core_DAO::executeQuery(CRM_Identitytracker_Configuration::getInsertSQL(), array(
            1 => array($objectId, 'Integer'),
            2 => array(CRM_Identitytracker_Configuration::TYPE_EXTERNAL, 'String'),
            3 => array($objectRef->external_identifier, 'String'),
            4 => array(date('YmdHis'), 'String'),
          ));
        }
      }

      if ($op == 'create') {
        // copy contact's CiviCRM ID once upon creation
        CRM_Core_DAO::executeQuery(CRM_Identitytracker_Configuration::getInsertSQL(), array(
          1 => array($objectId, 'Integer'),
          2 => array(CRM_Identitytracker_Configuration::TYPE_INTERNAL, 'String'),
          3 => array($objectId, 'String'),
          4 => array(date('YmdHis'), 'String'),
        ));

      }
    }
  }
}

/**
 * if custom fields that are identities are written, 
 *  make sure we copy the value into the identity table
 */
function identitytracker_civicrm_custom($op, $groupID, $entityID, &$params) {
  if ( $op != 'create' && $op != 'edit' ) {
    return;
  }

  $configuration = CRM_Identitytracker_Configuration::instance();
  $mapping = $configuration->getCustomFieldMapping();
  foreach ($params as $write) {
    if (!empty($write['custom_field_id']) && isset($mapping[$write['custom_field_id']])) {
      // HIT! This is an identity field!
      $identity_type = $mapping[$write['custom_field_id']];
      if (!empty($write['value'])) {
        civicrm_api3('Contact', 'addidentity', array(
          'contact_id' => $entityID,
          'identifier_type' => $identity_type,
          'identifier' => $write['value'],
          ));
      }
    }
  }
}

/**
 * Whenever this extension is enabled, we'll make sure that our custom fields
 *  are there.
 */
function identitytracker_civicrm_enable() {
  _identitytracker_civix_civicrm_enable();

  // register the IndentiyAnalyser if CiviBanking is installed
  CRM_Identitytracker_Configuration::registerIdentityAnalyser();

  // make sure the fields are there
  CRM_Identitytracker_Configuration::instance()->createFieldsIfMissing();

  // then see if we need to migrate old data
  CRM_Core_Error::debug_log_message("de.systopia.identitytracker: Migrating internal contact IDs...");
  CRM_Identitytracker_Migration::migrateInternal();
  CRM_Core_Error::debug_log_message("de.systopia.identitytracker: Migrating external contact IDs...");
  CRM_Identitytracker_Migration::migrateExternal();
  CRM_Core_Error::debug_log_message("de.systopia.identitytracker: Migration completed.");
}

/**
 * Set permission to the API calls
 */
function identitytracker_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  $permissions['contact']['findbyhistory'] = array('view all contacts');
}



/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function identitytracker_civicrm_config(&$config) {
  _identitytracker_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function identitytracker_civicrm_xmlMenu(&$files) {
  _identitytracker_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function identitytracker_civicrm_install() {
  _identitytracker_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function identitytracker_civicrm_uninstall() {
  _identitytracker_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function identitytracker_civicrm_disable() {
  _identitytracker_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function identitytracker_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _identitytracker_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function identitytracker_civicrm_managed(&$entities) {
  _identitytracker_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function identitytracker_civicrm_caseTypes(&$caseTypes) {
  _identitytracker_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function identitytracker_civicrm_angularModules(&$angularModules) {
_identitytracker_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function identitytracker_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _identitytracker_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
