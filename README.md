# TessituraMailChimpPane
Adds a preference pane and several sync tools in Tessitura to manage MailChimp subscriptions

## Install
1. Edit config.sample.  Set your MailChimp API key, your MailChimp default list ID, your Tessitura RAMP ID and your Tessiutra login. When you are finished rename to config.inc
2. Setup the following custom data fields in your list in MailChimp
  - CID
  - ZIPCODE
  - FNAME
  - LNAME
  - LETTER_SAL
  - ENV_SAL
  - PHONE
  - MEMB_LEVEL
  - MEMB_EXPR
  - NEXT_PERF
3. Currently the install has been modified for Mount Vernon's usage but you may want to edit sync.php and modify what fields you want to sync over.  You can also alter the field names above in sync.php.
4. In your TR_DATASERVICE_TABLES system table create a new entry.  This exposes additional data through the REST API regarding last ticket purchase.
  - name = tickets
  - table = T_TICKET_HISTORY
  - schema = dbo
  - singular = ticket

## Sync Tools
There are several PHP command line tools that you can use to syncronize data from Tessitura to MailChimp.

php sync.php [tessitura_list_id] [mailchimp_segment_id] (optional)
- syncronizes a Tessitura list to the list in MailChimp and optionally appends all contacts to a segment.  Unique key is email address.  The system will also loop through all email addresses on a record and add all of them to MailChimp.

php wipe-segment.php [mailchimp_segment_id]
- If you don't want to append to a segment when you sync you first need to run the wipe segment command to remove all of the subscribers from the segment

php load.php [your_list.txt] [mailchimp_segment_id]
- Allows you to load a local file of contacts to a MailChimp segment.  The file should be one email address per line.

php list.php
- Prints out subscribers to a MailChimp list.  This is useful to verify that your data has been stored as MailChimp's UI is cached.

php list-segment.php [mailchimp_segment_id]
- Prints out subscribers to a MailChimp list that reside in a segment.  This is useful to verify that your data has been stored as MailChimp's UI is cached.
