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

/**
 * This matcher will use the tracked identities to resolve id fields
 */
class CRM_Banking_PluginImpl_Matcher_IdentityAnalyser extends CRM_Banking_PluginModel_Analyser {

  /**
   * class constructor
   */
  function __construct($config_name) {
    parent::__construct($config_name);

    // read config, set defaults
    $config = $this->_plugin_config;
    if (!isset($config->fields))          $config->fields        = 'contact_id'; // can also be comma-separated or array
    if (!isset($config->identity_type))   $config->identity_type = 'internal';   // or 'external', or any other
    if (!isset($config->if_not_found))    $config->if_not_found  = 'reset';      // see https://github.com/systopia/de.systopia.identitytracker/issues/8
  }

  /**
   * Analyse given fields
   */
  public function analyse(CRM_Banking_BAO_BankTransaction $btx, CRM_Banking_Matcher_Context $context) {
    $config = $this->_plugin_config;

    // EXTRACT the list of fields to look into
    $fields = $config->fields;
    if (is_string($fields)) {
      $fields = explode(',', $fields);
    }
    if (!is_array($fields)) {
      $this->logMessage("Fields incorrectly configured.", 'warn');
      return;
    }
    if (empty($fields)) {
      $this->logMessage("No fields configured.", 'warn');
      return;
    }

    // LOOP: let's go!
    $data_parsed = $btx->getDataParsed();
    $data_parsed_changed = FALSE;
    foreach ($fields as $field) {
      if (!empty($data_parsed[$field])) {
        // here's a value: investigate!
        try {
          $result = civicrm_api3('Contact', 'findbyidentity', [
              'identifier_type' => $config->identity_type,
              'identifier'      => $data_parsed[$field]]);
          if ($result['count'] == 1) {
            # FOUND IT
            if ($data_parsed[$field] != $result['id']) {
              $this->logMessage("{$config->identity_type} ID '{$data_parsed[$field]}' ({$field}) resolved to '{$result['id']}'.", 'debug');
              $data_parsed[$field] = $result['id'];
              $data_parsed_changed = TRUE;
            } else {
              $this->logMessage("{$config->identity_type} ID '{$data_parsed[$field]}' ({$field}) confirmed.", 'debug');
            }

          } else {
            # NO (unique) MATCH
            $this->logMessage("{$config->identity_type} ID '{$data_parsed[$field]}' ({$field}) not confirmed.", 'debug');
            $data_parsed_changed = TRUE;
            switch (strtolower($config->if_not_found)) {
              case 'delete':
                unset($data_parsed[$field]);
                break;

              case 'status':
                if ($result['count'] > 1) {
                  $data_parsed[$field] = 'AMBIGUOUS';
                } else {
                  $data_parsed[$field] = 'UNKNOWN';
                }
                break;

              default:
                $data_parsed[$field] = '';
                break;
            }
          }

        } catch (Exception $ex) {
          $this->logMessage("Lookup error: " . $ex->getMessage(), 'error');
        }
      }
    }

    // SAVE changes and that's it
    if ($data_parsed_changed) {
      $btx->setDataParsed($data_parsed);
    }
  }
}
