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
  public function __construct($config_name) {
    parent::__construct($config_name);

    // read config, set defaults
    $config = $this->_plugin_config;
    if (!isset($config->fields)) {
      $config->fields = [
        'contact_id' => [
      // default is: same field (in-place)
          'target'           => 'contact_id',
      // or 'external', or any other
          'identity_type'    => 'internal',
      // overwrite target: only run if target is empty. Default is true
          'overwrite_target' => TRUE,
      // see https://github.com/systopia/de.systopia.identitytracker/issues/8
          'if_not_found'     => 'reset',
        ],
      ];
    }
  }

  /**
   * Analyse given fields
   */
  public function analyse(CRM_Banking_BAO_BankTransaction $btx, CRM_Banking_Matcher_Context $context) {
    $config = $this->_plugin_config;
    if (empty($config->fields) || !is_object($config->fields)) {
      $this->logMessage('Fields incorrectly configured.', 'warn');
      return;
    }

    // iterate through the specs
    $data_parsed = $btx->getDataParsed();
    foreach ($config->fields as $field => $specs) {
      // get the parameters for this field
      $specs = (array) $specs;
      $target           = empty($specs['target']) ? $field : $specs['target'];
      $overwrite_target = isset($specs['overwrite_target']) ? $specs['overwrite_target'] : TRUE;
      $identity_type    = $specs['identity_type'];
      $if_not_found     = empty($specs['if_not_found']) ? 'reset' : $specs['if_not_found'];
      $status_field     = empty($specs['status_field']) ? '' : $specs['status_field'];

      // check if identity_type is present
      if (empty($identity_type)) {
        $this->logMessage("No identity_type set for field '{$field}'.", 'warn');
        continue;
      }

      // check if there is a value
      if (empty($data_parsed[$field])) {
        $this->logMessage("Field '{$field}' is empty. Skipped.", 'debug');
        continue;
      }

      // if overwrite is off, check if field is empty
      if (!$overwrite_target && !empty($data_parsed[$target])) {
        $this->logMessage("Field '{$field}'s target '{$target}' is not empty. Skipped.", 'debug');
        continue;
      }

      // here's a value: investigate!
      try {
        $result = civicrm_api3('Contact', 'findbyidentity', [
          'identifier_type' => $identity_type,
          'identifier'      => $data_parsed[$field],
        ]);
        if ($result['count'] == 1) {
          # FOUND IT
          if ($data_parsed[$target] != $result['id']) {
            $this->logMessage("{$identity_type} ID '{$data_parsed[$field]}' ({$field}) resolved to '{$result['id']}'.", 'debug');
            $data_parsed[$target] = $result['id'];
          }
          else {
            $this->logMessage("{$identity_type} ID '{$data_parsed[$field]}' ({$field}) confirmed.", 'debug');
          }

          // mark status
          if ($status_field) {
            $data_parsed[$status_field] = 'IDENTIFIED';
          }

        }
        else {
          # NO (unique) MATCH
          $this->logMessage("{$identity_type} ID '{$data_parsed[$field]}' ({$field}) not identified.", 'debug');

          $if_not_found_instructions = explode(',', $if_not_found);
          foreach ($if_not_found_instructions as $if_not_found_instruction) {
            $if_not_found_instruction = strtolower(trim($if_not_found_instruction));
            switch ($if_not_found_instruction) {
              case 'delete':
                unset($data_parsed[$field]);
                break;

              case 'delete_target':
                unset($data_parsed[$target]);
                break;

              case 'reset':
                $data_parsed[$field] = '';
                break;

              case 'reset_target':
                $data_parsed[$target] = '';
                break;

              default:
                # nothing to do
                break;
            }
          }

          if ($status_field) {
            if ($result['count'] > 1) {
              $data_parsed[$status_field] = 'AMBIGUOUS';
            }
            else {
              $data_parsed[$status_field] = 'UNKNOWN';
            }
          }
        }

      }
      catch (Exception $ex) {
        $this->logMessage('Lookup error: ' . $ex->getMessage(), 'error');
      }
    }

    // SAVE changes and that's it
    $btx->setDataParsed($data_parsed);
  }

}
