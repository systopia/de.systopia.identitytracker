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

/**
 *
 * Configuration wrapper
 *
 */
class CRM_Identitytracker_Configuration {

  public const GROUP_NAME          = 'contact_id_history';
  public const GROUP_TABLE         = 'civicrm_value_contact_id_history';
  public const TYPE_FIELD_NAME     = 'id_history_entry_type';
  public const TYPE_FIELD_COLUMN   = 'identifier_type';
  public const ID_FIELD_NAME       = 'id_history_entry';
  public const ID_FIELD_COLUMN     = 'identifier';
  public const DATE_FIELD_NAME     = 'id_history_date';
  public const DATE_FIELD_COLUMN   = 'used_since';
  public const CONTEXT_FIELD_NAME = 'id_history_context';
  public const CONTEXT_FIELD_COLUMN = 'context';

  // built-in identities
  public const TYPE_GROUP_NAME     = 'contact_id_history_type';
  public const TYPE_INTERNAL       = 'internal';
  public const TYPE_EXTERNAL       = 'external';

  protected ?array $contact_id_history_group  = NULL;
  protected ?array $contact_id_history_fields = NULL;
  protected ?array $contact_id_option_group   = NULL;

  protected static ?CRM_Identitytracker_Configuration $singleton = NULL;

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
    return "SELECT DISTINCT(`entity_id`)
            FROM `{$group_table}`
            LEFT JOIN civicrm_contact ON civicrm_contact.id = entity_id
            WHERE `{$type_column}` = %1
              AND `{$id_column}` = %2
              AND civicrm_contact.is_deleted = 0;";
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
    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
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
    }
    else {
      return $fields[$field_name]['id'];
    }
  }

  /**
   * get the custom group entity used for the contact history
   */
  public function getIdentitytrackerGroup() {
    if ($this->contact_id_history_group === NULL) {
      try {
        $this->contact_id_history_group = civicrm_api3('CustomGroup', 'getsingle', ['name' => self::GROUP_NAME]);
      }
      catch (Exception $e) {
        // that should simply mean that there is no such group
        $this->contact_id_history_group = [];
        // @ignoreException
      }
    }

    if (empty($this->contact_id_history_group)) {
      return NULL;
    }
    else {
      return $this->contact_id_history_group;
    }
  }

  /**
   * Get the ID type option group ID, if it exists
   */
  public function getOptionGroupID() {
    if ($this->contact_id_option_group === NULL) {
      try {
        $this->contact_id_option_group = civicrm_api3('OptionGroup', 'getsingle', ['name' => self::TYPE_GROUP_NAME]);
      }
      catch (Exception $e) {
        // group doesn't exist
        $this->contact_id_option_group = [];
        // @ignoreException
      }
    }

    if (empty($this->contact_id_option_group['id'])) {
      return NULL;
    }
    else {
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
        $reply = civicrm_api3('CustomField', 'get', [
          'custom_group_id' => $group['id'],
          'name'            => ['IN' => [self::ID_FIELD_NAME, self::TYPE_FIELD_NAME, self::DATE_FIELD_NAME]],
        ]
        );
        $this->contact_id_history_fields = [];
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
    $mapping = CRM_Core_BAO_Setting::getItem('de.systopia.identitytracker', 'identitytracker_mapping');
    if (is_array($mapping)) {
      return $mapping;
    }
    else {
      return [];
    }
  }

  /**
   * set the configured mapping, set ::getCustomFieldMapping
   *
   * @param $mapping array
   */
  public function setCustomFieldMapping($mapping) {
    Civi::settings()->set('identitytracker_mapping', $mapping);
  }

  /**
   * Checks it an $option_value already exists, and if not create it
   * @param $option_type
   *
   * @throws \CRM_Core_Exception
   */
  public static function add_identity_type($option_type, $option_label) {
    $result = civicrm_api3('OptionValue', 'get', [
      'sequential'      => 1,
      'option_group_id' => 'contact_id_history_type',
      'value'           => $option_type,
    ]);
    if ($result['count'] == '0') {
      civicrm_api3('OptionValue', 'create', [
        'option_group_id' => 'contact_id_history_type',
        'value'           => $option_type,
        'label'           => $option_label,
      ]);
    }
  }

  /**
   * Register the IdentityAnalyser if CiviBanking is present
   */
  public static function registerIdentityAnalyser() {
    $option_groups = civicrm_api3('OptionGroup', 'get', ['name' => 'civicrm_banking.plugin_types']);
    if (!empty($option_groups['id'])) {
      // the option group exists, CiviBanking seems to be there
      $entries = civicrm_api3('OptionValue', 'get', [
        'name'            => 'analyser_identity',
        'option_group_id' => $option_groups['id'],
      ]);
      if ($entries['count'] == 0) {
        civicrm_api3('OptionValue', 'create', [
          'name'            => 'analyser_identity',
          'label'           => 'Identity Analyser',
          'value'           => 'CRM_Banking_PluginImpl_Matcher_IdentityAnalyser',
          'is_default'      => 0,
          'description'     => 'Uses the ID Tracker Data to look up Contact IDs',
          'option_group_id' => $option_groups['id'],
        ]);
      }
    }
  }

}
