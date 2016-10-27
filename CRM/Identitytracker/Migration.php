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
 * This class can migrate existing contacts's IDs into the newly created tables
 */
class CRM_Identitytracker_Migration {

  public static function migrateInternal() {
    self::migrate(CRM_Identitytracker_Configuration::TYPE_INTERNAL, 'id');
  }

  public static function migrateExternal() {
    self::migrate(CRM_Identitytracker_Configuration::TYPE_EXTERNAL, 'external_identifier');
  }

  /**
   * will migrate civicrm_contact base data
   */
  protected static function migrate($type, $contact_field) {
    $install_date = CRM_Core_DAO::singleValueQuery("SELECT MIN(`created_date`) FROM `civicrm_contact`;");
    $group_table  = CRM_Identitytracker_Configuration::GROUP_TABLE;
    $type_column  = CRM_Identitytracker_Configuration::TYPE_FIELD_COLUMN;
    $id_column    = CRM_Identitytracker_Configuration::ID_FIELD_COLUMN;
    $date_column  = CRM_Identitytracker_Configuration::DATE_FIELD_COLUMN;

    CRM_Core_DAO::executeQuery("
      INSERT INTO `$group_table` (`entity_id`, `{$type_column}`, `{$id_column}`, `{$date_column}`)
        (SELECT  id AS ch_entity_id,
                 %1 AS ch_type,
                 `{$contact_field}` AS ch_indentifier,
                 COALESCE (DATE(`created_date`), DATE(%2)) AS ch_date
          FROM   `civicrm_contact`
          WHERE  `{$contact_field}` IS NOT NULL
            AND  `{$contact_field}` != ''
            AND  NOT EXISTS ( SELECT `{$group_table}`.`id` FROM `{$group_table}` 
                               WHERE `entity_id` = `civicrm_contact`.`id`
                                 AND `{$type_column}` = %1
                                 AND `{$id_column}` = `civicrm_contact`.`{$contact_field}`)
        );", array(
            1 => array($type, 'String'),
            2 => array($install_date, 'String'),
        ));
  }

  /**
   * will migrate custom data
   */
  public static function migrateCustom($identity_type, $custom_field_id, $sqlDateTerm = 'NOW()') {
    $group_table  = CRM_Identitytracker_Configuration::GROUP_TABLE;
    $type_column  = CRM_Identitytracker_Configuration::TYPE_FIELD_COLUMN;
    $id_column    = CRM_Identitytracker_Configuration::ID_FIELD_COLUMN;
    $date_column  = CRM_Identitytracker_Configuration::DATE_FIELD_COLUMN;

    // get custom field data
    $custom_field = civicrm_api3('CustomField', 'getsingle', array('id' => $custom_field_id));
    $custom_group = civicrm_api3('CustomGroup', 'getsingle', array('id' => $custom_field['custom_group_id']));
    $custom_field_column = $custom_field['column_name'];
    $custom_group_table  = $custom_group['table_name'];

    CRM_Core_DAO::executeQuery("
      INSERT INTO `$group_table` (`entity_id`, `{$type_column}`, `{$id_column}`, `{$date_column}`)
        (SELECT  `entity_id` AS ch_entity_id,
                 %1 AS ch_type,
                 `{$custom_field_column}` AS ch_indentifier,
                 {$sqlDateTerm} AS ch_date
          FROM   `{$custom_group_table}`
          WHERE  `{$custom_field_column}` IS NOT NULL
            AND  `{$custom_field_column}` != ''
            AND  NOT EXISTS ( SELECT `{$group_table}`.`id` FROM `{$group_table}`
                               WHERE `{$group_table}`.`entity_id` = `{$custom_group_table}`.`entity_id`
                                 AND `{$type_column}` = %1
                                 AND `{$id_column}` = `{$custom_group_table}`.`{$custom_field_column}`)
        );", array(
            1 => array($identity_type, 'String'),
        ));
  }
}
