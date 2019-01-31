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

/*
 * Will use the ID tracker to match the fields 'xcm_submitted_contact_id' (and the legacy: 'id')
 */
class CRM_Xcm_Matcher_IdTrackerInternalMatcher extends CRM_Xcm_Matcher_IdTrackerMatcher {
  function __construct() {
    parent::__construct(CRM_Identitytracker_Configuration::TYPE_INTERNAL, ['xcm_submitted_contact_id', 'id']);
  }
}
