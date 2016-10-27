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

require_once 'CRM/Core/Form.php';


/**
 * Settings form controller
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Identitytracker_Form_Settings extends CRM_Core_Form {
  
  const CUSTOM_FIELD_COUNT = 5;


  public function buildQuickForm() {

    // find all eligible custom fields
    $custom_fields = array(0 => ts('-- select --', array('domain' => 'de.systopia.identitytracker')));
    $custom_fields += $this->getEligibleCustomFields();

    if (count($custom_fields) <= 1) {
      CRM_Core_Session::setStatus(ts("No suitable custom fields found!", array('domain' => 'de.systopia.identitytracker')), ts("Warning", array('domain' => 'de.systopia.identitytracker')), 'warn');
    }

    // get identity types
    $identity_types = array(0 => ts('-- select --', array('domain' => 'de.systopia.identitytracker')));
    $identity_types += $this->getIdentityTypes();

    // add elements
    $config_rows = array();
    for ($i=1; $i <= self::CUSTOM_FIELD_COUNT; $i++) { 
      $this->addElement('select',
                        "custom_field_$i",
                        ts('Custom Field', array('domain' => 'de.systopia.identitytracker')),
                        $custom_fields,
                        array('class' => 'crm-select2'));

      $this->addElement('select',
                        "identity_type_$i",
                        ts('Identity Type', array('domain' => 'de.systopia.identitytracker')),
                        $identity_types,
                        array('class' => 'crm-select2'));

      $config_rows["custom_field_$i"] = "identity_type_$i";
    }
    $this->assign('config_rows', $config_rows);

    // add a link to the custom group
    $configuration = CRM_Identitytracker_Configuration::instance();
    $group_id = $configuration->getOptionGroupID();
    $option_group_url = CRM_Utils_System::url('civicrm/admin/options', "reset=1&gid={$group_id}");
    $this->assign('option_group_url', $option_group_url);

    // add the save button
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      )
    ));

    parent::buildQuickForm();
  }


  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    $configuration = CRM_Identitytracker_Configuration::instance();
    $mapping = $configuration->getCustomFieldMapping();
    $i = 0;
    foreach ($mapping as $custom_field => $identity_type) {
      $i++;
      $defaults["custom_field_$i"]  = $custom_field;
      $defaults["identity_type_$i"] = $identity_type;
    }

    return $defaults;
  }


  public function postProcess() {
    $values = $this->exportValues();

    // store
    $mapping = array();
    for ($i=0; $i <= self::CUSTOM_FIELD_COUNT; $i++) { 
      $custom_field  = CRM_Utils_Array::value("custom_field_$i", $values, NULL);
      $identity_type = CRM_Utils_Array::value("identity_type_$i", $values, NULL);
      if (!empty($custom_field) && !empty($identity_type)) {
        $mapping[$custom_field] = $identity_type;
      }
    }

    $configuration = CRM_Identitytracker_Configuration::instance();
    $configuration->setCustomFieldMapping($mapping);

    // migrate all (TODO: only changes?)
    foreach ($mapping as $custom_field_id => $identity_type) {
      CRM_Identitytracker_Migration::migrateCustom($identity_type, $custom_field_id);
    }

    parent::postProcess();
  }


  /**
   * get the list of identity types
   */
  protected function getIdentityTypes() {
    $identity_types_query = civicrm_api3('OptionValue', 'get', array(
      'option_group_id' => 'contact_id_history_type',
      'return'          => 'value,label', 
      'options.limit'   => 0));
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
    $contact_types = array('Contact');
    $contact_types_query = civicrm_api3('ContactType', 'get', array(
      'return'        => 'name', 
      'options.limit' => 0));
    foreach ($contact_types_query['values'] as $contact_type) {
      $contact_types[] = $contact_type['name'];
    }

    // THEN: find all custom groups extending these
    $custom_groups_query = civicrm_api3('CustomGroup', 'get', array(
      'return'        => 'id',
      'extends'       => array('IN' => $contact_types),
      'options.limit' => 0));
    foreach ($custom_groups_query['values'] as $custom_group) {
      $custom_groups[] = $custom_group['id'];
    }

    // THEN: find all custom fields of these groups
    $custom_fields_query = civicrm_api3('CustomField', 'get', array(
      'return'          => 'id,label',
      'custom_group_id' => array('IN' => $custom_groups),
      'options.limit'   => 0));
    foreach ($custom_fields_query['values'] as $custom_field) {
      $custom_fields[$custom_field['id']] = $custom_field['label'];
    }

    // remove our own fields
    $configuration = CRM_Identitytracker_Configuration::instance();
    $own_fields = $configuration->getIdentitytrackerFields();
    foreach ($own_fields as $own_field) {
      $own_field_id = $own_field['id'];
      if (isset($custom_fields[$own_field_id])) {
        unset($custom_fields[$own_field_id]);
      }
    }

    return $custom_fields;
  }
}
