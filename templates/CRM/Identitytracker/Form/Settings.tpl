{*-------------------------------------------------------+
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
+-------------------------------------------------------*}

<div class="crm-block crm-form-block">
  <div>
    <h3>{ts domain="de.systopia.identitytracker"}Contact Identity Tracker{/ts}</h3>
    <div id="help">
      {ts domain="de.systopia.identitytracker"}This page allows you to map custom fields to identity types. These will then be automatically monitored by the system so changes to their values can be recorded.{/ts}
      {ts domain="de.systopia.identitytracker" 1=$option_group_url}You might want to add new identity types <a href="%1">here</a>.{/ts}
    </div>
    <table>
      <thead>
        <tr>
          <th>{$form.custom_field_1.label}</th>
          <th></th>
          <th>{$form.identity_type_1.label}</th>
        </tr>
      </thead>
      {foreach from=$config_rows key=custom_field item=identity_type}
      <tr>
        <td>{$form.$custom_field.html}</td>
        <td><code>==&gt;</code></td>         
        <td>{$form.$identity_type.html}</td>
      </tr>
      {/foreach}
    </table>
  </div>
</div>

<br/>

<div id="help">
  {ts domain="de.systopia.identitytracker"}Careful! When you click 'save', all currently existing values will be copied into the contact identities table.{/ts}
</div>

<div class="crm-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
