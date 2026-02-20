<?php

namespace local_koitimetable\task;

defined('MOODLE_INTERNAL') || die();

class import_classes extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task_import_timetable', 'local_koitimetable');
    }

    public function execute() {
        global $DB, $CFG;

        require_once($CFG->libdir . '/filelib.php');

        mtrace('Starting nightly timetable import...');

        $curl = new \curl([
            'timeout' => 60,
            'ssl_verifypeer' => true
        ]);

        /* === Step 1: OAuth token === */
        $oauthurl = $CFG->t1_api_base . '/oauth2/access_token';

        $oauthresponse = $curl->post($oauthurl, [
            'grant_type'    => 'client_credentials',
            'client_id'     => $CFG->t1_client_id,
            'client_secret' => $CFG->t1_client_secret
        ]);

        $oauthdata = json_decode($oauthresponse, true);

        if (empty($oauthdata['access_token'])) {
            mtrace('Failed to obtain OAuth token', 'notifyerror');
            exit;
        }

        /* === Step 2: Fetch classtimes === */
        $endpoint = $CFG->t1_api_base . '/Api/RaaS/v2/classtimes?pageSize=1000000&page=1';

        $curl->setHeader([
            'Authorization: Bearer ' . $oauthdata['access_token'],
            'Content-Type: application/json'
        ]);

        $response = $curl->get($endpoint);
        $json = json_decode($response, true);

        if (empty($json['DataSet']) || !is_array($json['DataSet'])) {
            mtrace('Invalid API response', 'notifyerror');
            exit;
        }

        /* === Step 3: Import === */
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_koitimetable');

        $requiredkeys = [
            'ACTIVITY',
            'STARTDATE',
            'ENDDATE',
            'STARTTIME',
            'ENDTIME',
            'BUILDINGID',
            'ROOMID',
            'COMMENT'
        ];

        $inserted = 0;
        $skipped  = 0;

        foreach ($json['DataSet'] as $row) {

            foreach ($requiredkeys as $key) {
                if (!array_key_exists($key, $row)) {
                    $skipped++;
                    continue 2;
                }
            }

            $startdate = strtotime($row['STARTDATE']);
            $enddate   = strtotime($row['ENDDATE']);

            if (!$startdate || !$enddate || $enddate < $startdate || empty($row['COMMENT'])) {
                $skipped++;
                continue;
            }

            $record = new \stdClass();
            $record->groupname = $row['COMMENT'];
            $record->startdate = $startdate;
            $record->enddate   = $enddate;
            $record->timestart = (int)$row['STARTTIME'];
            $record->timeend   = (int)$row['ENDTIME'];
            $record->building  = $row['BUILDINGID'];
            $record->room      = $row['ROOMID'];
            $record->activity  = (int)$row['ACTIVITY'];

            try {
                $DB->insert_record('local_koitimetable', $record);
                $inserted++;
            } catch (Exception $e) {
                $skipped++;
            }
        }

        $transaction->allow_commit();

        mtrace('Timetable import finished.');
    }
}