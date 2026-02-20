# KOI_timetable_moodle
v0.1
This is a local moodle plugin for visualising student's timetable/weekly class schedule on moodle directly.
Written by Kevin Yu (IT Team)

# Installation Instructions
To install this plugin, you should place this repository's files in moodle/local/koitimetable. **Be sure to name the directory as koitimetable**, otherwise the plugin will not function.

You will also have to setup TechOne webservice credentials in config.php. You can find this file at /server/moodle/config.php
You need to add the following fields:
```
$CFG->t1_client_id = 'clientid';
$CFG->t1_client_secret = 'omitted';
$CFG->t1_api_base = 'https://T1-address.com/T1Default/CiAnywhere/Web/KOIT1SMS';
```
You can find these fields in T1>Site settings>Oauth idp credentials.