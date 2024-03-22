# Contact Identity Tracker

CiviCRM contacts are identified by their CiviCRM ID and, optionally, an external
identifier, which is a single-calue filed on *Contact* entities.

This extension provides means for keeping track of multiple identifiers of
different configurable types for *Contact* entities by adding a multi-value
custom field group and an API for easily identifying contacts by those ID
records.

> [!WARNING]
> **Incompatible with CiviCRM Core `5.71` prior to version `1.4`**
>
> The extension is incompatible with CiviCRM Core starting from version `5.71.0`
> due to misuse of the API internally (which actually worked by accident).
> *Identity Tracker* version `1.4` fixes this incompatibility.
