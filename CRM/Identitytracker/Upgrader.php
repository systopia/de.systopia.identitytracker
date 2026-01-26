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

use CRM_Identitytracker_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Identitytracker_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Extension is enabled
   */
  public function enable(): void {
    // Add XCM matchers
    $customData = new CRM_Identitytracker_CustomData(E::LONG_NAME);
    $customData->syncOptionGroup(E::path('/resources/rules_option_group.json'));

    // register the IndentiyAnalyser if CiviBanking is installed
    CRM_Identitytracker_Configuration::registerIdentityAnalyser();
  }

  /**
   * Extension is disabled
   */
  public function disable(): void {
    // Remove XCM matchers
    $matchers = ['CRM_Xcm_Matcher_IdTrackerInternalMatcher', 'CRM_Xcm_Matcher_IdTrackerExternalMatcher'];
    foreach ($matchers as $matcher_name) {
      $entry = civicrm_api3('OptionValue', 'get', [
        'name'            => $matcher_name,
        'option_group_id' => 'xcm_matching_rules',
      ]);
      if (!empty($entry['id'])) {
        civicrm_api3('OptionValue', 'delete', ['id' => $entry['id']]);
      }
    }
  }

  public function postInstall(): void {
    Civi::log()->debug('de.systopia.identitytracker: Migrating internal contact IDs...');
    CRM_Identitytracker_Migration::migrateInternal();
    Civi::log()->debug('de.systopia.identitytracker: Migrating external contact IDs...');
    CRM_Identitytracker_Migration::migrateExternal();
    Civi::log()->debug('de.systopia.identitytracker: Migration completed.');
  }

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

  /**
   * Make sure the new XCM matchers are available
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1301() {
    $this->ctx->log->info('Installing XCM matchers.');
    $this->enable();
    return TRUE;
  }

}
