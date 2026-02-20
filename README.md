# KOI_timetable_moodle
v0.1
This is a local moodle plugin for visualising student's timetable/weekly class schedule on moodle directly.
Written by Kevin Yu (IT Team)

# Installation Instructions
To install this plugin, you should place this repository's files in moodle/local/koitimetable

You will also have to setup TechOne webservice credentials in config.php. You can find this file at /server/moodle/config.php
You need to add the following fields:
```
$CFG->t1_client_id = 'koit1sms_it_ws';
$CFG->t1_client_secret = 'omitted';
$CFG->t1_api_base = 'https://T1-address.com/T1Default/CiAnywhere/Web/KOIT1SMS';
```