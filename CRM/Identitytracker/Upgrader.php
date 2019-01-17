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


use CRM_Identitytracker_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Identitytracker_Upgrader extends CRM_Identitytracker_Upgrader_Base {

  /**
   * Register the IdentityAnalyser if CiviBanking is present
   *
   * @return TRUE on success
   */
  public function upgrade_1300() {
    $this->ctx->log->info('Registering IdentityAnalyser');
    CRM_Identitytracker_Configuration::registerIdentityAnalyser();
    return TRUE;
  }
}
