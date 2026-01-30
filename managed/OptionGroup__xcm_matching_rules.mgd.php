<?php
/*
 * Copyright (C) 2026 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use CRM_Identitytracker_ExtensionUtil as E;

return [
  [
    'name' => 'OptionValue__xcm_matching_rules__CRM_Xcm_Matcher_IdTrackerInternalMatcher',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'xcm_matching_rules',
        'label' => E::ts('Identity Tracker: CiviCRM ID (internal)'),
        'value' => 'CRM_Xcm_Matcher_IdTrackerInternalMatcher',
        'name' => 'CRM_Xcm_Matcher_IdTrackerInternalMatcher',
        'is_reserved' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionValue__xcm_matching_rules__CRM_Xcm_Matcher_IdTrackerExternalMatcher',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'xcm_matching_rules',
        'label' => E::ts('Identity Tracker: External Identifier'),
        'value' => 'CRM_Xcm_Matcher_IdTrackerExternalMatcher',
        'name' => 'CRM_Xcm_Matcher_IdTrackerExternalMatcher',
        'is_reserved' => TRUE,
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
];
