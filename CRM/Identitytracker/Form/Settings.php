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

declare(strict_types = 1);

require_once 'CRM/Core/Form.php';

/**
 * Settings form controller
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Identitytracker_Form_Settings extends CRM_Core_Form {

  public const CUSTOM_FIELD_COUNT = 5;

  private CRM_Identitytracker_Configuration $configuration;

  public function __construct($state = NULL, $action = CRM_Core_Action::NONE, $method = 'post', $name = NULL) {
    parent::__construct($state, $action, $method, $name);
    $configuration = CRM_Identitytracker_Configuration::instance();
    if ($configuration === NULL) {
      throw new RuntimeException('Configuration not found!');
    }
    $this->configuration = $configuration;
  }

  public function buildQuickForm() {

    // find all eligible custom fields
    $custom_fields = [0 => ts('-- select --', ['domain' => 'de.systopia.identitytracker'])];
    $custom_fields += $this->getEligibleCustomFields();

    if (count($custom_fields) <= 1) {
      CRM_Core_Session::setStatus(ts('No suitable custom fields found!', ['domain' => 'de.systopia.identitytracker']), ts('Warning', ['domain' => 'de.systopia.identitytracker']), 'warn');
    }

    // get identity types
    $identity_types = [0 => ts('-- select --', ['domain' => 'de.systopia.identitytracker'])];
    $identity_types += $this->getIdentityTypes();

    // add elements
    $config_rows = [];
    for ($i = 1; $i <= self::CUSTOM_FIELD_COUNT; $i++) {
      $this->addElement('select',
                        "custom_field_$i",
                        ts('Custom Field', ['domain' => 'de.systopia.identitytracker']),
                        $custom_fields,
                        ['class' => 'crm-select2']);

      $this->addElement('select',
                        "identity_type_$i",
                        ts('Identity Type', ['domain' => 'de.systopia.identitytracker']),
                        $identity_types,
                        ['class' => 'crm-select2']);

      $config_rows["custom_field_$i"] = "identity_type_$i";
    }
    $this->assign('config_rows', $config_rows);

    // add a link to the custom group
    $group_id = $this->configuration->getOptionGroupID();
    $option_group_url = CRM_Utils_System::url('civicrm/admin/options', "reset=1&gid={$group_id}");
    $this->assign('option_group_url', $option_group_url);

    // add the save button
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
    ]);

    parent::buildQuickForm();
  }

  public function setDefaultValues(): array {
    $defaults = parent::setDefaultValues();

    $mapping = $this->configuration->getCustomFieldMapping();

    if ($mapping) {
      $i = 0;
      foreach ($mapping as $custom_field => $identity_type) {
        $i++;
        $defaults["custom_field_$i"]  = $custom_field;
        $defaults["identity_type_$i"] = $identity_type;
      }
    }

    return $defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();

    // store
    $mapping = [];
    for ($i = 0; $i <= self::CUSTOM_FIELD_COUNT; $i++) {
      $custom_field  = $values["custom_field_$i"] ?? NULL;
      $identity_type = $values["identity_type_$i"] ?? NULL;
      if (!empty($custom_field) && !empty($identity_type)) {
        $mapping[$custom_field] = $identity_type;
      }
    }

    $this->configuration->setCustomFieldMapping($mapping);

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    // migrate all (TODO: only changes?)
    foreach ($mapping as $custom_field_id => $identity_type) {
      CRM_Identitytracker_Migration::migrateCustom($identity_type, $custom_field_id);
    }

    parent::postProcess();
  }

  /**
   * get the list of identity types
   */
  protected function getIdentityTypes(): array {
    $identity_types = [];
    $identity_types_query = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => 'contact_id_history_type',
      'return'          => 'value,label',
      'option.limit'    => 0,
    ]);
    foreach ($identity_types_query['values'] as $identity_type) {
      $identity_types[$identity_type['value']] = $identity_type['label'];
    }
    return $identity_types;
  }

  /**
   * find the custom fields eligible for ID tracking
   */
  protected function getEligibleCustomFields() {
    // FIRST: find all contact types:
    $contact_types = ['Contact'];
    $contact_types_query = civicrm_api3('ContactType', 'get', [
      'return'        => 'name',
      'option.limit'  => 0,
    ]);
    foreach ($contact_types_query['values'] as $contact_type) {
      $contact_types[] = $contact_type['name'];
    }

    // THEN: find all custom groups extending these
    $custom_groups_query = civicrm_api3('CustomGroup', 'get', [
      'return'        => 'id',
      'extends'       => ['IN' => $contact_types],
      'option.limit'  => 0,
    ]);
    $custom_groups = [];
    foreach ($custom_groups_query['values'] as $custom_group) {
      $custom_groups[] = $custom_group['id'];
    }

    // THEN: find all custom fields of these groups
    $custom_fields_query = civicrm_api3('CustomField', 'get', [
      'return'          => 'id,label',
      'custom_group_id' => ['IN' => $custom_groups],
      'option.limit'    => 0,
    ]);
    $custom_fields = [];
    foreach ($custom_fields_query['values'] as $custom_field) {
      $custom_fields[$custom_field['id']] = $custom_field['label'];
    }

    // remove our own fields
    $own_fields = $this->configuration->getIdentitytrackerFields();
    foreach ($own_fields as $own_field) {
      $own_field_id = $own_field['id'];
      if (isset($custom_fields[$own_field_id])) {
        unset($custom_fields[$own_field_id]);
      }
    }

    return $custom_fields;
  }

}
