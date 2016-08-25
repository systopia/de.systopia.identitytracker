<?php
/*-------------------------------------------------------+
| Contact ID History                                     |
| Copyright (C) 2016 SYSTOPIA                            |
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


require_once 'contactidhistory.civix.php';

/**
 * implement this hook to make sure we capture all ID changes
 */
function contactidhistory_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if ($op == 'edit' || $op == 'create' || $op == 'update') {
    if ($objectName == 'Individual' || $objectName == 'Organisation' || $objectName == 'Household') {
      if (!empty($objectRef->external_identifier)) {
        $exists = CRM_Core_DAO::singleValueQuery(CRM_Contactidhistory_Configuration::getLookupSQL(), array(
          1 => array($objectId, 'Integer'),
          2 => array(CRM_Contactidhistory_Configuration::TYPE_EXTERNAL, 'String'),
          3 => array($objectRef->external_identifier, 'String'),
          ));
        if (!$exists) {
          CRM_Core_DAO::executeQuery(CRM_Contactidhistory_Configuration::getInsertSQL(), array(
            1 => array($objectId, 'Integer'),
            2 => array(CRM_Contactidhistory_Configuration::TYPE_EXTERNAL, 'String'),
            3 => array($objectRef->external_identifier, 'String'),
            4 => array(date('YmdHis'), 'String'),
          ));
        }
      }

      if ($op == 'create') {
        // copy contact's CiviCRM ID once upon creation
        CRM_Core_DAO::executeQuery(CRM_Contactidhistory_Configuration::getInsertSQL(), array(
          1 => array($objectId, 'Integer'),
          2 => array(CRM_Contactidhistory_Configuration::TYPE_INTERNAL, 'String'),
          3 => array($objectId, 'String'),
          4 => array(date('YmdHis'), 'String'),
        ));

      }
    }
  }
}


/**
 * Whenever this extension is enabled, we'll make sure that our custom fields
 *  are there.
 */
function contactidhistory_civicrm_enable() {
  _contactidhistory_civix_civicrm_enable();

  // make sure the fields are there
  CRM_Contactidhistory_Configuration::instance()->createFieldsIfMissing();

  // then see if we need to migrate old data
  error_log("Starting internal contact ID migration...");
  CRM_Contactidhistory_Migration::migrateInternal();
  error_log("Starting external contact ID migration...");
  CRM_Contactidhistory_Migration::migrateExternal();
  error_log("Migration completed.");
}



/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function contactidhistory_civicrm_config(&$config) {
  _contactidhistory_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function contactidhistory_civicrm_xmlMenu(&$files) {
  _contactidhistory_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function contactidhistory_civicrm_install() {
  _contactidhistory_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function contactidhistory_civicrm_uninstall() {
  _contactidhistory_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function contactidhistory_civicrm_disable() {
  _contactidhistory_civix_civicrm_disable();
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
function contactidhistory_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _contactidhistory_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function contactidhistory_civicrm_managed(&$entities) {
  _contactidhistory_civix_civicrm_managed($entities);
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
function contactidhistory_civicrm_caseTypes(&$caseTypes) {
  _contactidhistory_civix_civicrm_caseTypes($caseTypes);
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
function contactidhistory_civicrm_angularModules(&$angularModules) {
_contactidhistory_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function contactidhistory_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _contactidhistory_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
