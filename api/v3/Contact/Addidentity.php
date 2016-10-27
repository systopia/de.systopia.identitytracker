<?php
/*-------------------------------------------------------+
| Contact ID Tracker                                     |
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

/**
 * Find contacts based on the contact history
 */
function civicrm_api3_contact_addidentity($params) {

  // set used_since to now if not given
  if (empty($params['used_since'])) $params['used_since'] = date("YmdHis");

  // TODO: check if identifier_type exists?

  // create id-entry
  $query = CRM_Core_DAO::executeQuery(CRM_Identitytracker_Configuration::getInsertSQL(), array(
    1 => array($params['contact_id'], 'Integer'),
    2 => array($params['identifier_type'], 'String'),
    3 => array($params['identifier'], 'String'),
    4 => array($params['used_since'], 'String'),
  ));

  return civicrm_api3_create_success($params);
}

/**
 * API3 action specs
 */
function _civicrm_api3_contact_addidentity_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['identifier']['api.required'] = 1;
  $params['identifier_type']['api.required'] = 1;
  $params['used_since']['api.required'] = 0;
}
