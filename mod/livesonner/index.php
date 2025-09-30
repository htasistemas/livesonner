<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * List of LiveSonner instances in a course
 *
 * @package    mod_livesonner
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$PAGE->set_url('/mod/livesonner/index.php', ['id' => $id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('modulenameplural', 'mod_livesonner'));
$PAGE->set_heading($course->fullname);

$instances = get_all_instances_in_course('livesonner', $course);

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('modulenameplural', 'mod_livesonner'));

if (!$instances) {
    echo $OUTPUT->notification(get_string('nodetails', 'mod_livesonner'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [get_string('name'), get_string('timestart', 'mod_livesonner'), get_string('duration', 'mod_livesonner')];
$table->data = [];

foreach ($instances as $instance) {
    $cm = get_coursemodule_from_instance('livesonner', $instance->id, $course->id, false, MUST_EXIST);
    $link = html_writer::link(new moodle_url('/mod/livesonner/view.php', ['id' => $cm->id]), format_string($instance->name));
    $table->data[] = [
        $link,
        userdate($instance->timestart),
        get_string('durationlabel', 'mod_livesonner', $instance->duration),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
