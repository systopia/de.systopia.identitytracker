# Contact Identity Tracker

This extension lets you keep track of multiple identifiers for your contadts,
each with an identifier type and of different sources. This allows for more
things to identify contacts by other than the CiviCRM contact ID and the
*External Identifier* field.

You can add as many identifier types and record as many identifiers of those
types for your contacts as you like. You can also set custom fields as sources
for specific identifier types, causing any change to those fields be recorded
with their new value, while keeping track of old values. This allows for merged
contacts to see which identifiers they and their merged duplicates used to have.

You will also be able to see the date the identifier has been used the first
time (or was added to CiviCRM), allowing for a history of identifiers once any
of them change.

Each identifier may also get a *context*, which will be useful for extension
developers to make use of for distinguishing between identifiers of the same
type, e.&nbsp;g. for multiple API keys belonging to same web service. This field
will have to be enabled before using it, e.&nbsp;g. with a managed entity.
