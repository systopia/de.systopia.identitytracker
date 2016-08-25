<?php
/*-------------------------------------------------------+
| Contact ID History                                     |
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

/*
 * Configuration wrapper
 */
class CRM_Contactidhistory_Configuration {

  const GROUP_NAME          = 'contact_id_history';
  const GROUP_LABEL         = 'ID History';
  const GROUP_TABLE         = 'civicrm_value_contact_id_history';
  const TYPE_FIELD_NAME     = 'id_history_entry_type';
  const TYPE_FIELD_LABEL    = 'ID Type';
  const TYPE_FIELD_COLUMN   = 'identifier_type';
  const ID_FIELD_NAME       = 'id_history_entry';
  const ID_FIELD_LABEL      = 'Identifier';
  const ID_FIELD_COLUMN     = 'identifier';
  const DATE_FIELD_NAME     = 'id_history_date';
  const DATE_FIELD_LABEL    = 'Used since';
  const DATE_FIELD_COLUMN   = 'used_since';

  const TYPE_GROUP_NAME     = 'contact_id_history_type';
  const TYPE_GROUP_LABEL    = 'Contact ID History - entry types';
  const TYPE_INTERNAL       = 'internal';
  const TYPE_INTERNAL_LABEL = 'CiviCRM ID';
  const TYPE_EXTERNAL       = 'external';
  const TYPE_EXTERNAL_LABEL = 'External Identifier';
  // const GROUP_LABEL     = ts('Contact ID History', array('domain' => 'de.systopia.contactidhistory'));


  protected $contact_id_history_group  = NULL;
  protected $contact_id_history_fields = NULL;
  protected $contact_id_option_group   = NULL;

  protected static $singleton = NULL;
  protected function __construct() {}

  public static function instance() {
    if (self::$singleton === NULL) {
      self::$singleton = new CRM_Contactidhistory_Configuration();
    }
    return self::$singleton;
  }


  /**
   * Get the ID of the specified custom field
   */
  public function getContactIdHistoryFieldID($field_name) {
    $fields = $this->getContactIdHistoryFields();
    if (empty($fields[$field_name]['id'])) {
      return NULL;
    } else {
      return $fields[$field_name]['id'];
    }
  }


  /**
   * get the custom group entity used for the contact history
   */
  public function getContactIdHistoryGroup() {
    if ($this->contact_id_history_group === NULL) {
      try {
        $this->contact_id_history_group = civicrm_api3('CustomGroup', 'getsingle', array('name' => self::GROUP_NAME));
      } 
      catch (Exception $e) {
        // that should simply mean that there is no such group
        $this->contact_id_history_group = array();
      }
    }

    if (empty($this->contact_id_history_group)) {
      return NULL;
    } else {
      return $this->contact_id_history_group;
    }
  }


  /**
   * Get the ID type option group ID, if it exists
   */
  public function getOptionGroupID() {
    if ($this->contact_id_option_group === NULL) {
      try {
        $this->contact_id_option_group = civicrm_api3('OptionGroup', 'getsingle', array('name' => self::TYPE_GROUP_NAME));
      } catch (Exception $e) {
        // group doesn't exist
        $this->contact_id_option_group = array();
      }
    }

    if (empty($this->contact_id_option_group['id'])) {
      return NULL;
    } else {
      return $this->contact_id_option_group['id'];
    }
  }


  /**
   * get the list of the custom fields used
   */
  protected function getContactIdHistoryFields() {
    if ($this->contact_id_history_fields === NULL) {
      $group = $this->getContactIdHistoryGroup();
      if ($group) {
        $reply = civicrm_api3('CustomField', 'get', array(
          'custom_group_id' => $group['id'],
          'name'            => array('IN', array(self::ID_FIELD_NAME, self::TYPE_FIELD_NAME, self::DATE_FIELD_NAME)),
          )
        );
        $this->contact_id_history_fields = array();
        foreach ($reply['values'] as $field) {
          $this->contact_id_history_fields[$field['name']] = $field;
        }
      }
    }
    return $this->contact_id_history_fields;
  }




  /*****************************************
   **       CUSTOM DATA CREATION          **
   ****************************************/

  public function createFieldsIfMissing() {
    // first: create the option group
    $this->createOptionGroupIfMissing();

    // NOW: create the group if missing
    $group = $this->getContactIdHistoryGroup();
    if (empty($group)) {
      CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `{self::GROUP_TABLE}`;");
      civicrm_api3('CustomGroup', 'create', array(
        'name'                 => self::GROUP_NAME,
        'title'                => self::GROUP_LABEL,
        'extends'              => 'Contact',
        'is_active'            => 1,
        'style'                => 'Tab with table',
        'help_pre'             => 'This is a list of all the IDs this contact ever had, even across merging duplicates.',
        'help_post'            => '',
        'table_name'           => self::GROUP_TABLE,
        'is_multiple'          => '1',
        'collapse_display'     => '0',
        'collapse_adv_display' => '0',
        'is_reserved'          => '0',
        'collapse_display'     => 1,
        'collapse_adv_display' => 1,
        ));
      $this->contact_id_history_group = NULL;
      $group = $this->getContactIdHistoryGroup();
    }

    // then create type field if missing
    $type_field_id = $this->getContactIdHistoryFieldID(self::TYPE_FIELD_NAME);
    $id_field_id   = $this->getContactIdHistoryFieldID(self::ID_FIELD_NAME);
    $date_field_id = $this->getContactIdHistoryFieldID(self::DATE_FIELD_NAME);

    if (empty($type_field_id)) {
      civicrm_api3('CustomField', 'create', array(
        'name'                 => self::TYPE_FIELD_NAME,
        'label'                => self::TYPE_FIELD_LABEL,
        'custom_group_id'      => $group['id'],
        'data_type'            => 'String',
        'html_type'            => 'Select',
        'is_required'          => '1',
        'is_searchable'        => '1',
        'is_search_range'      => '0',
        'is_active'            => '1',
        'is_view'              => '0',
        'column_name'          => self::TYPE_FIELD_COLUMN,
        'option_group_id'      => $this->getOptionGroupID(),
        'in_selector'          => '1'));
      $this->contact_id_history_fields = NULL;
    }

    if (empty($id_field_id)) {
      civicrm_api3('CustomField', 'create', array(
        'name'                 => self::ID_FIELD_NAME,
        'label'                => self::ID_FIELD_LABEL,
        'custom_group_id'      => $group['id'],
        'data_type'            => 'String',
        'html_type'            => 'Text',
        'is_required'          => '1',
        'is_searchable'        => '1',
        'is_search_range'      => '0',
        'is_active'            => '1',
        'is_view'              => '0',
        'column_name'          => self::ID_FIELD_COLUMN,
        'in_selector'          => '1'));
      $this->contact_id_history_fields = NULL;
    }

    if (empty($date_field_id)) {
      civicrm_api3('CustomField', 'create', array(
        'name'                 => self::DATE_FIELD_NAME,
        'label'                => self::DATE_FIELD_LABEL,
        'custom_group_id'      => $group['id'],
        'data_type'            => 'Date',
        'html_type'            => 'Select Date',
        'is_required'          => '1',
        'is_searchable'        => '1',
        'is_search_range'      => '1',
        'is_active'            => '1',
        'is_view'              => '0',
        'column_name'          => self::DATE_FIELD_COLUMN,
        'date_format'          => 'yy-mm-dd',
        'time_format'          => '2',
        'in_selector'          => '1'));
      $this->contact_id_history_fields = NULL;
    }
  }

  protected function createOptionGroupIfMissing() {
    $option_group_id = $this->getOptionGroupID();
    if (empty($option_group_id)) {
      $this->contact_id_option_group = NULL;

      $group = civicrm_api3('OptionGroup', 'create', array(
        'name'                 => self::TYPE_GROUP_NAME,
        'title'                => self::TYPE_GROUP_LABEL,
        'is_reserved'          => '1',
        'is_active'            => '1'));

      civicrm_api3('OptionValue', 'create', array(
        'name'                 => self::TYPE_INTERNAL,
        'label'                => self::TYPE_INTERNAL_LABEL,
        'value'                => self::TYPE_INTERNAL,
        'option_group_id'      => $group['id'],
        'is_default'           => '0',
        'is_reserved'          => '1',
        'is_active'            => '1'));

      civicrm_api3('OptionValue', 'create', array(
        'name'                 => self::TYPE_EXTERNAL,
        'label'                => self::TYPE_EXTERNAL_LABEL,
        'value'                => self::TYPE_EXTERNAL,
        'option_group_id'      => $group['id'],
        'is_default'           => '1',
        'is_reserved'          => '1',
        'is_active'            => '1'));
    }
  }
}
