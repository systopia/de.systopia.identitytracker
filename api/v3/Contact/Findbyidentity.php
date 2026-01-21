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

use Civi\Api4\Contact;

/**
 * Find contacts based on the contact history
 */
function civicrm_api3_contact_findbyidentity($params) {
  $query = Contact::get(FALSE)
    ->addSelect('id')
    ->addJoin(
      'Custom_' . CRM_Identitytracker_Configuration::GROUP_NAME . ' AS custom_contact_id_history',
      'INNER',
      ['custom_contact_id_history.entity_id', '=', 'id']
    )
    ->addWhere('custom_contact_id_history.' . CRM_Identitytracker_Configuration::TYPE_FIELD_NAME, '=', $params['identifier_type'])
    ->addWhere('custom_contact_id_history.' . CRM_Identitytracker_Configuration::ID_FIELD_NAME, '=', $params['identifier'])
    ->addWhere('is_deleted', '=', FALSE)
    ->addGroupBy('id');

  if (isset($params['context'])) {
    $query->addWhere('custom_contact_id_history.' . CRM_Identitytracker_Configuration::CONTEXT_FIELD_NAME, '=', $params['context']);
  }

  $results = $query
    ->execute()
    ->indexBy('id')
    ->getArrayCopy();

  return civicrm_api3_create_success($results);
}

/**
 * API3 action specs
 */
function _civicrm_api3_contact_findbyidentity_spec(&$params) {
  $params['identifier']['api.required'] = 1;
  $params['identifier_type']['api.required'] = 1;
  $params['context']['api.required'] = 0;
}
