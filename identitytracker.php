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

declare(strict_types = 1);

require_once 'identitytracker.civix.php';
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Implements hook_civicrm_container().
 *
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function identitytracker_civicrm_container(ContainerBuilder $container) {
  $container->addCompilerPass(new Civi\Identitytracker\CompilerPass());
}

/**
 * implement this hook to make sure we capture all ID changes
 */
function identitytracker_civicrm_post($op, $objectName, $objectId, &$objectRef): void {
  if ($op === 'edit' || $op === 'create' || $op === 'update') {
    if (in_array($objectName, ['Individual', 'Organization', 'Household'], TRUE)) {
      if (!empty($objectRef->external_identifier) && ($objectRef->external_identifier !== 'null')) {
        $exists = CRM_Core_DAO::singleValueQuery(CRM_Identitytracker_Configuration::getLookupSQL(), [
          1 => [$objectId, 'Integer'],
          2 => [CRM_Identitytracker_Configuration::TYPE_EXTERNAL, 'String'],
          3 => [$objectRef->external_identifier, 'String'],
        ]);
        if ($exists === NULL || $exists === '') {
          CRM_Core_DAO::executeQuery(CRM_Identitytracker_Configuration::getInsertSQL(), [
            1 => [$objectId, 'Integer'],
            2 => [CRM_Identitytracker_Configuration::TYPE_EXTERNAL, 'String'],
            3 => [$objectRef->external_identifier, 'String'],
            4 => [date('YmdHis'), 'String'],
          ]);
        }
      }

      if ($op === 'create') {
        // copy contact's CiviCRM ID once upon creation
        CRM_Core_DAO::executeQuery(CRM_Identitytracker_Configuration::getInsertSQL(), [
          1 => [$objectId, 'Integer'],
          2 => [CRM_Identitytracker_Configuration::TYPE_INTERNAL, 'String'],
          3 => [$objectId, 'String'],
          4 => [date('YmdHis'), 'String'],
        ]);

      }
    }
  }
}

/**
 * if custom fields that are identities are written,
 *  make sure we copy the value into the identity table
 */
function identitytracker_civicrm_custom($op, $groupID, $entityID, &$params) {
  if ($op !== 'create' && $op !== 'edit') {
    return;
  }

  /** @var CRM_Identitytracker_Configuration $configuration */
  $configuration = CRM_Identitytracker_Configuration::instance();
  $mapping = $configuration->getCustomFieldMapping();
  foreach ($params as $write) {
    if (!empty($write['custom_field_id']) && isset($mapping[$write['custom_field_id']])) {
      // HIT! This is an identity field!
      $identity_type = $mapping[$write['custom_field_id']];
      if (!empty($write['value'])) {
        civicrm_api3('Contact', 'addidentity', [
          'contact_id' => $entityID,
          'identifier_type' => $identity_type,
          'identifier' => $write['value'],
        ]);
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
}

/**
 * Set permission to the API calls
 */
function identitytracker_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  $permissions['contact']['findbyhistory'] = ['view all contacts'];
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function identitytracker_civicrm_navigationMenu(&$menu) {
  _identitytracker_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label' => ts('Identity Tracker Settings'),
    'name' => 'Identity Tracker Settings',
    'url' => 'civicrm/admin/setting/idtracker',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);
  _identitytracker_civix_navigationMenu($menu);
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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function identitytracker_civicrm_install() {
  _identitytracker_civix_civicrm_install();
}
