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

/**
 *
 * This will execute a matching process based on the configuration,
 * employing various matching rules
 *
 */
class CRM_Xcm_Matcher_IdTrackerMatcher extends CRM_Xcm_MatchingRule {

  /**
   * which identity_type are we using? */
  protected ?string $identity_type = NULL;

  // list of fields to look into
  protected array $fields = [];

  public function __construct($identity_type, $fields) {
    $this->identity_type = $identity_type;
    $this->fields = $fields;
  }

  /**
   * Straightforward:
   *  - look if there's a value in one of the fields
   *  - use Contact.findbyidentity to resolve
   */
  public function matchContact(&$contact_data, $params = NULL) {
    foreach ($this->fields as $field) {
      if (!empty($contact_data[$field])) {
        $contact_ids    = [];
        $contact_search = civicrm_api3('Contact', 'findbyidentity', [
          'identifier_type' => $this->identity_type,
          'identifier'      => $contact_data[$field],
        ]);
        foreach ($contact_search['values'] as $contact) {
          $contact_ids[] = $contact['id'];
        }

        switch (count($contact_ids)) {
          case 0:
            return $this->createResultUnmatched();

          case 1:
            return $this->createResultMatched(reset($contact_ids));

          default:
            $contact_id = $this->pickContact($contact_ids);
            if ($contact_id) {
              return $this->createResultMatched($contact_id);
            }
            else {
              return $this->createResultUnmatched();
            }
        }
      }
    }

    // if we get here, none of the values was present
    return $this->createResultUnmatched();
  }

}
