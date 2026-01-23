<?php
require('../../config.php');
require_login();

$PAGE->set_url('/local/koitimetable/index.php');
$PAGE->set_title(get_string('timetable', 'local_koitimetable'));
$PAGE->set_heading(get_string('timetable', 'local_koitimetable'));

echo $OUTPUT->header();

global $DB, $USER;

if (has_capability('local/koitimetable:manage', context_system::instance())) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/koitimetable/import.php'),
        'Manual Timetable Import Page (Admin/Manager only)',
        'get'
    );
}

// 1. Get user's groups
$courses = enrol_get_users_courses($USER->id);
$groupnames = [];
foreach ($courses as $course) {
    $groups = groups_get_all_groups($course->id, $USER->id);
    $groupnames = array_merge($groupnames, array_map(fn($g) => $g->name, $groups));
    echo '<h2>Course ' . $course->shortname . ' Groups: ' . implode(', ', $groupnames) . '</h2>';
}
if (empty($groupnames)) {
    echo $OUTPUT->notification('You are not enrolled in any classes.', 'notifyinfo');
    echo $OUTPUT->footer();
    exit;
}   

// 2. Query timetable
list($sqlin, $params) = $DB->get_in_or_equal($groupnames, SQL_PARAMS_NAMED);

$sql = "
    SELECT *
    FROM {local_koitimetable}
    WHERE groupname $sqlin
    ORDER BY startdate, timestart
";

$records = $DB->get_records_sql($sql, $params);

// 3. Render
$table = new html_table();
$table->head = ['Group', 'Date', 'Time', 'Location'];

foreach ($records as $r) {
    $table->data[] = [
        s($r->groupname),
        userdate($r->startdate, '%d %b %Y'),
        substr($r->timestart, 0, 2) . ':' . substr($r->timestart, 2, 2)
        . ' – ' .
        substr($r->timeend, 0, 2) . ':' . substr($r->timeend, 2, 2),
        s($r->building . ' ' . $r->room)
    ];
}

echo html_writer::table($table);

// Draw timetable
$weekoffset = optional_param('week', 0, PARAM_INT); // +1, -1, etc.

$today = strtotime('today');
$monday = strtotime("monday this week", $today);
$monday = strtotime("{$weekoffset} week", $monday);
$sunday = strtotime("sunday this week", $monday);

$week = [];

foreach ($records as $r) {

    // Skip classes not running this week
    if ($r->enddate < $monday || $r->startdate > $sunday) {
        continue;
    }

    $weekday = (int)date('N', $r->startdate);

    $startminutes =
        intval(substr($r->timestart, 0, 2)) * 60 +
        intval(substr($r->timestart, 2, 2));

    $endminutes =
        intval(substr($r->timeend, 0, 2)) * 60 +
        intval(substr($r->timeend, 2, 2));

    $week[$weekday][] = (object)[
        'group' => $r->groupname,
        'start' => $startminutes,
        'end'   => $endminutes,
        'room'  => $r->building . ' ' . $r->room
    ];
}

echo '<div class="week-header">';

$prevurl = new moodle_url('/local/koitimetable/index.php', [
    'week' => $weekoffset - 1
]);

$nexturl = new moodle_url('/local/koitimetable/index.php', [
    'week' => $weekoffset + 1
]);

echo html_writer::link($prevurl, "← Previous week", ['class' => 'week-nav']);
echo '&nbsp;&nbsp;&nbsp;';
echo html_writer::link($nexturl, "Next week →", ['class' => 'week-nav']);
echo '<br>';
echo html_writer::span(
    userdate($monday, '%d %b') . ' to ' . userdate($sunday, '%d %b %Y'),
    'week-range'
);

echo '</div>';
$days = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday'
];

echo '<div class="timetable-horizontal">';

/* Time header */
echo '<div class="time-header">';
echo '<div class="day-label"></div>'; // empty corner

for ($t = 9; $t <= 20; $t++) {
    echo '<div class="time-slot">' . sprintf('%02d:00', $t) . '</div>';
}
echo '</div>';

/* Day rows */
foreach ($days as $daynum => $dayname) {

    echo '<div class="day-row">';
    echo '<div class="day-label">' . $dayname . '</div>';

    echo '<div class="day-track">';

    if (!empty($week[$daynum])) {
        foreach ($week[$daynum] as $c) {

            $left  = ($c->start - (9*60*1)) * 2;
            $width = ($c->end - $c->start) * 2; // double the scale, i.e. 1 min = 0.5px

            echo html_writer::div(
                '<strong>' . s(substr($c->group, 0, 6)) . '</strong><br>' . s($c->room),
                'class-block',
                [
                    'style' => "
                        left: {$left}px;
                        width: {$width}px;
                    "
                ]
            );
        }
    }

    echo '</div>'; // day-track
    echo '</div>'; // day-row
}

echo '</div>';


echo $OUTPUT->footer();