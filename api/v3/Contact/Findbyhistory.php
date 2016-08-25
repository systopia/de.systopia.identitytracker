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

/**
 * Find contacts based on the contact history
 */
function civicrm_api3_contact_findbyhistory($params) {
  // TODO: check if fields are there?
  
  $query = CRM_Core_DAO::executeQuery(CRM_Contactidhistory_Configuration::getSearchSQL(), array(
    1 => array($params['identifier_type'], 'String'),
    2 => array($params['identifier'], 'String'),
  ));

  $results = array();
  while ($query->fetch()) {
    $results[$query->entity_id] = array('id' => $query->entity_id);
  }

  return civicrm_api3_create_success($results);
}

/**
 * API3 action specs
 */
function _civicrm_api3_contact_findbyhistory_spec(&$params) {
  $params['identifier']['api.required'] = 1;
  $params['identifier_type']['api.required'] = 1;
}
