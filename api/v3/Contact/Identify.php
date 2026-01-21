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
 * Find ONE contact based on the contact history
 */
function civicrm_api3_contact_identify($params) {
  $result = civicrm_api3('Contact', 'findbyidentity', $params);
  if (empty($result['values'])) {
    throw new Exception('No contacts found.', 1);
  }
  elseif (count($result['values']) == 1) {
    return $result;
  }
  else {
    throw new \RuntimeException('More than one contact found.', 1);
  }
}

/**
 * API3 action specs
 */
function _civicrm_api3_contact_identify_spec(&$params) {
  $params['identifier']['api.required'] = 1;
  $params['identifier_type']['api.required'] = 1;
  $params['context']['api.required'] = 0;
}
