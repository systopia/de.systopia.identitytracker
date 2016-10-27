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

/*
 * Configuration wrapper
 */
class CRM_Identitytracker_Configuration {

  const GROUP_NAME          = 'contact_id_history';
  const GROUP_LABEL         = 'Contact Identities';
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

  // built-in identities
  const TYPE_GROUP_NAME     = 'contact_id_history_type';
  const TYPE_GROUP_LABEL    = 'Contact Identity Types';
  const TYPE_INTERNAL       = 'internal';
  const TYPE_INTERNAL_LABEL = 'CiviCRM ID';
  const TYPE_EXTERNAL       = 'external';
  const TYPE_EXTERNAL_LABEL = 'External Identifier';
  // const GROUP_LABEL     = ts('Contact ID History', array('domain' => 'de.systopia.identitytracker'));


  protected $contact_id_history_group  = NULL;
  protected $contact_id_history_fields = NULL;
  protected $contact_id_option_group   = NULL;

  protected static $singleton = NULL;
  protected function __construct() {}

  public static function instance() {
    if (self::$singleton === NULL) {
      self::$singleton = new CRM_Identitytracker_Configuration();
    }
    return self::$singleton;
  }



  public static function getSearchSQL() {
    $group_table  = self::GROUP_TABLE;
    $type_column  = self::TYPE_FIELD_COLUMN;
    $id_column    = self::ID_FIELD_COLUMN;
    return "SELECT DISTINCT(`entity_id`) FROM `{$group_table}` WHERE `{$type_column}` = %1 AND `{$id_column}` = %2;";
  }

  public static function getLookupSQL() {
    $group_table  = self::GROUP_TABLE;
    $type_column  = self::TYPE_FIELD_COLUMN;
    $id_column    = self::ID_FIELD_COLUMN;
    return "SELECT COUNT(id) FROM `{$group_table}` WHERE `entity_id` = %1 AND `{$type_column}` = %2 AND `{$id_column}` = %3;";
  }

  public static function getInsertSQL() {
    $group_table  = self::GROUP_TABLE;
    $type_column  = self::TYPE_FIELD_COLUMN;
    $id_column    = self::ID_FIELD_COLUMN;
    $date_column  = self::DATE_FIELD_COLUMN;
    // return "INSERT INTO `$group_table` (`entity_id`, `{$type_column}`, `{$id_column}`, `{$date_column}`) VALUES (%1, %2, %3, %4);";
    
    // This statement automatically checks for existing entries.
    return "INSERT INTO `$group_table` (`entity_id`, `{$type_column}`, `{$id_column}`, `{$date_column}`) 
            SELECT * FROM (SELECT %1 AS entity_id, %2 AS type, %3 AS indentifier, %4 AS used_since) AS tmp
            WHERE NOT EXISTS (
              SELECT `id` FROM `$group_table` 
              WHERE `entity_id` = %1 AND `{$type_column}` = %2 AND `{$id_column}` = %3
              LIMIT 1
            )";
  }

  /**
   * Get the ID of the specified custom field
   */
  public function getIdentitytrackerFieldID($field_name) {
    $fields = $this->getIdentitytrackerFields();
    if (empty($fields[$field_name]['id'])) {
      return NULL;
    } else {
      return $fields[$field_name]['id'];
    }
  }


  /**
   * get the custom group entity used for the contact history
   */
  public function getIdentitytrackerGroup() {
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
  public function getIdentitytrackerFields() {
    if ($this->contact_id_history_fields === NULL) {
      $group = $this->getIdentitytrackerGroup();
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


  /**
   * get the configured mapping
   *   array(<custom_field_id> => <option_value_id>)
   * of custom fields that should be treated as contact identity
   *
   * @return array
   */
  public function getCustomFieldMapping() {
    return civicrm_api3('Setting', 'getvalue', array('name' => 'identitytracker_mapping'));
  }

  /**
   * set the configured mapping, set ::getCustomFieldMapping
   *
   * @param $mapping array
   */
  public function setCustomFieldMapping($mapping) {
    civicrm_api3('Setting', 'create', array('identitytracker_mapping' => $mapping));
  }
  


  /*****************************************
   **       CUSTOM DATA CREATION          **
   ****************************************/

  public function createFieldsIfMissing() {
    // first: create the option group
    $this->createOptionGroupIfMissing();

    // NOW: create the group if missing
    $group = $this->getIdentitytrackerGroup();
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
      $group = $this->getIdentitytrackerGroup();
    }

    // then create type field if missing
    $type_field_id = $this->getIdentitytrackerFieldID(self::TYPE_FIELD_NAME);
    $id_field_id   = $this->getIdentitytrackerFieldID(self::ID_FIELD_NAME);
    $date_field_id = $this->getIdentitytrackerFieldID(self::DATE_FIELD_NAME);

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
