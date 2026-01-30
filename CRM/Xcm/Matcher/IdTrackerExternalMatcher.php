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
 * Will use the ID tracker to match the field 'external_identifier'
 *
 */
class CRM_Xcm_Matcher_IdTrackerExternalMatcher extends CRM_Xcm_Matcher_IdTrackerMatcher {

  public function __construct() {
    parent::__construct(CRM_Identitytracker_Configuration::TYPE_EXTERNAL, ['external_identifier']);
  }

}
