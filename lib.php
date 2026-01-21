<?php
defined('MOODLE_INTERNAL') || die();

function local_koitimetable_extend_navigation(global_navigation $navigation) {
    if (!isloggedin() || isguestuser()) {
        return;
    }
    // global $CFG;

    if ($home = $navigation->find('home', global_navigation::TYPE_SETTING)) {
        $home->remove();
    }

    $url = new moodle_url('/local/koitimetable/index.php');

    $navigation->add(
        get_string('timetable', 'local_koitimetable'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_koitimetable'
    );
}
