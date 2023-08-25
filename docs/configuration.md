# Configuration

## Identity Types

Identifier types are *OptionValue*s and can be managed using the CiviCRM Core
administration UI for option groups at *Administration* » *System Settings* »
*Option Groups*. The option group is called *Contact Identity Types*
(`contact_id_history_type`).

Each identity type consists of a (human-readable) name, a value (which will be
the machine-readable name), an optional description, and an optional icon.

Once you set up an identity type, you will be able to add identifiers to
contacts with that type. The contact overview will have a new tab *Contact
Identities*, which is basically a table view of a multi-value *CustomGroup* that
this extension creates and utilizes. This table will contain the *CiviCRM ID* of
the contact and also the *External Identifier* value if it is set.

## Identitfier Sources

If you keep track of identifiers in custom fields on your contacts (or maybe an
API synchronizes them into them), you might want to add them to *Identity
Tracker* by watching changes of values in those fields and automatically have
identifier records created. Head to *Administration* » *System Settings* »
*Identity Tracker Settings* for assigning custom fields to identity types. Once
you apply those settings, all currently existing values in those fields will be
copied into the contact identities table, as well as all subsquent changes in
those fields.
