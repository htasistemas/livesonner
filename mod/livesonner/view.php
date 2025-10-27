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
 * Main view page for LiveSonner module
 *
 * @package    mod_livesonner
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('livesonner', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$livesonner = $DB->get_record('livesonner', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$canmanage = livesonner_user_can_manage_session($livesonner, $context);
$livesonner->recordingurl = (string)($livesonner->recordingurl ?? '');

$PAGE->set_url('/mod/livesonner/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($livesonner->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('mod-livesonner');
$PAGE->activityheader->disable();

if ($action === 'join') {
    require_sesskey();

    if (!empty($livesonner->isfinished)) {
        redirect(new moodle_url('/mod/livesonner/view.php', ['id' => $cm->id]), get_string('classfinished', 'mod_livesonner'), null,
            \core\output\notification::NOTIFY_INFO);
    }

    if (time() < $livesonner->timestart) {
        redirect(new moodle_url('/mod/livesonner/view.php', ['id' => $cm->id]), get_string('classnotstarted', 'mod_livesonner'), null,
            \core\output\notification::NOTIFY_WARNING);
    }

    if (!$DB->record_exists('livesonner_attendance', ['livesonnerid' => $livesonner->id, 'userid' => $USER->id])) {
        $attendance = (object) [
            'livesonnerid' => $livesonner->id,
            'userid' => $USER->id,
            'timeclicked' => time(),
        ];
        $DB->insert_record('livesonner_attendance', $attendance);
    }

    $url = $livesonner->meeturl;
    if (!preg_match('#^https?://#', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }
    redirect($url, get_string('joinredirectnotice', 'mod_livesonner'), 0, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'finalize') {
    require_sesskey();
    if (!$canmanage) {
        throw new required_capability_exception($context, 'mod/livesonner:manage', 'nopermissions', 'finalize');
    }

    if (empty($livesonner->isfinished)) {
        $livesonner->isfinished = 1;
        $livesonner->timemodified = time();
        $DB->update_record('livesonner', $livesonner);

        $completion = new completion_info($course);
        if ($completion->is_enabled($cm)) {
            $users = get_enrolled_users($context, 'mod/livesonner:view');
            foreach ($users as $user) {
                $completion->update_state($cm, COMPLETION_COMPLETE, $user->id);
            }
        }
        $message = get_string('finishsuccess', 'mod_livesonner');
    } else {
        $message = get_string('finishalready', 'mod_livesonner');
    }

    redirect(new moodle_url('/mod/livesonner/view.php', ['id' => $cm->id]), $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'saverecording') {
    require_sesskey();

    if (!$canmanage) {
        throw new required_capability_exception($context, 'mod/livesonner:manage', 'nopermissions', 'saverecording');
    }

    $recordingurl = optional_param('recordingurl', '', PARAM_RAW_TRIMMED);
    $normalized = livesonner_get_normalized_recording_url($recordingurl);

    if ($normalized === null) {
        redirect(new moodle_url('/mod/livesonner/view.php', ['id' => $cm->id]),
            get_string('invalidrecordingurl', 'mod_livesonner'), null, \core\output\notification::NOTIFY_ERROR);
    }

    $livesonner->recordingurl = $normalized;
    $livesonner->timemodified = time();
    $DB->update_record('livesonner', $livesonner);

    redirect(new moodle_url('/mod/livesonner/view.php', ['id' => $cm->id]),
        get_string('recordingsaved', 'mod_livesonner'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$event = \mod_livesonner\event\course_module_viewed::create([
    'objectid' => $livesonner->id,
    'context' => $context,
]);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$now = time();
$remaining = $livesonner->timestart - $now;
$hasstarted = $remaining <= 0;

$buttonurl = new moodle_url('/mod/livesonner/view.php', ['id' => $cm->id, 'action' => 'join', 'sesskey' => sesskey()]);
$buttonclass = 'btn btn-lg btn-primary btn-block';
$buttondisabled = false;
$buttonlabel = get_string('joinclass', 'mod_livesonner');
$statusmessage = '';

if (!empty($livesonner->isfinished)) {
    $buttondisabled = true;
    $buttonclass = 'btn btn-lg btn-secondary btn-block disabled';
    $buttonlabel = get_string('classfinished', 'mod_livesonner');
    $statusmessage = get_string('classfinished', 'mod_livesonner');
} else if (!$hasstarted) {
    $buttondisabled = true;
    $buttonclass = 'btn btn-lg btn-secondary btn-block disabled';
    $statusmessage = get_string('countdownmessage', 'mod_livesonner', livesonner_format_interval($remaining));
}

$videohtml = livesonner_render_recording($livesonner->recordingurl);

$PAGE->requires->strings_for_js(['countdownmessage'], 'mod_livesonner');

if (empty($livesonner->isfinished) && !$hasstarted) {
    $PAGE->requires->js_call_amd('mod_livesonner/countdown', 'init', [
        $livesonner->timestart,
        '#livesonner-countdown',
    ]);
}

$PAGE->set_secondary_navigation(false);

echo $OUTPUT->header();

echo html_writer::start_tag('div', ['class' => 'container-fluid my-5 px-3 px-md-4 px-lg-5 livesonner-container']);
    echo html_writer::start_tag('div', ['class' => 'card shadow-sm border-0 livesonner-card']);
        echo html_writer::start_tag('div', ['class' => 'card-body p-4 p-lg-5 livesonner-card-body']);
            echo html_writer::tag('h1', format_string($livesonner->name), ['class' => 'display-5 mb-3 text-primary']);
            echo html_writer::div(format_module_intro('livesonner', $livesonner, $cm->id), 'lead');

            echo html_writer::start_tag('div', ['class' => 'd-flex flex-column flex-md-row gap-3 my-4 align-items-start']);
                echo html_writer::div(html_writer::tag('span', get_string('starttimelabel', 'mod_livesonner', userdate($livesonner->timestart)), ['class' => 'badge bg-info text-dark fs-6 p-3']));
                echo html_writer::div(html_writer::tag('span', get_string('durationlabel', 'mod_livesonner', $livesonner->duration), ['class' => 'badge bg-warning text-dark fs-6 p-3']));
                if (!empty($livesonner->teacherid)) {
                    $teacher = core_user::get_user($livesonner->teacherid);
                    if ($teacher) {
                        $teacherprofile = new moodle_url('/user/view.php', ['id' => $teacher->id, 'course' => $course->id]);
                        $teachername = html_writer::link($teacherprofile, fullname($teacher));
                        echo html_writer::div(html_writer::tag('span', get_string('assignedteacher', 'mod_livesonner', $teachername), ['class' => 'badge bg-success text-white fs-6 p-3']));
                    }
                }
            echo html_writer::end_tag('div');

            if ($statusmessage) {
                echo html_writer::div($statusmessage, 'alert alert-info fs-5', ['id' => 'livesonner-countdown']);
            } else {
                echo html_writer::div('', 'alert alert-info fs-5 d-none', ['id' => 'livesonner-countdown']);
            }

            $buttonattrs = ['class' => $buttonclass, 'role' => 'button'];
            if ($buttondisabled) {
                $buttonattrs['aria-disabled'] = 'true';
            } else {
                $buttonattrs['href'] = $buttonurl;
                $buttonattrs['target'] = '_blank';
                $buttonattrs['rel'] = 'noopener noreferrer';
            }

            echo html_writer::tag('a', $buttonlabel, $buttonattrs);

            if ($canmanage && empty($livesonner->isfinished)) {
                $finishurl = new moodle_url('/mod/livesonner/view.php', ['id' => $cm->id, 'action' => 'finalize', 'sesskey' => sesskey()]);
                echo html_writer::tag('a', get_string('finalizeclass', 'mod_livesonner'), ['href' => $finishurl, 'class' => 'btn btn-outline-danger mt-3']);
            }
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', ['class' => 'card-footer bg-light p-3 p-lg-4 livesonner-card-footer']);
            if (!empty($livesonner->isfinished)) {
                echo html_writer::tag('h3', get_string('videosectiontitle', 'mod_livesonner'), ['class' => 'h4 mb-3']);
                echo $videohtml;

                if ($canmanage) {
                    echo html_writer::tag('h4', get_string('recordingformtitle', 'mod_livesonner'), ['class' => 'h5 mt-4']);
                    echo html_writer::div(get_string('recordingformdescription', 'mod_livesonner'), 'text-muted mb-3');

                    $formurl = new moodle_url('/mod/livesonner/view.php', ['id' => $cm->id, 'action' => 'saverecording', 'sesskey' => sesskey()]);
                    $form = html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl]);
                    $form .= html_writer::empty_tag('input', ['type' => 'text', 'name' => 'recordingurl', 'value' => s($livesonner->recordingurl), 'class' => 'form-control mb-2', 'placeholder' => get_string('recordingurlplaceholder', 'mod_livesonner')]);
                    $form .= html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('saverecording', 'mod_livesonner')]);
                    $form .= html_writer::end_tag('form');
                    echo $form;
                }
            } else if ($canmanage) {
                echo html_writer::div(get_string('videoavailableafterfinish', 'mod_livesonner'), 'text-muted');
            }
        echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

echo html_writer::end_tag('div');

if ($canmanage) {
    $registrations = $DB->get_records('livesonner_enrolments', ['livesonnerid' => $livesonner->id], 'timecreated ASC');
    $attendances = $DB->get_records('livesonner_attendance', ['livesonnerid' => $livesonner->id], 'timeclicked ASC');

    $registrationusers = array_map(static function($registration) {
        return $registration->userid;
    }, $registrations ?: []);
    $attendanceusers = array_map(static function($attendance) {
        return $attendance->userid;
    }, $attendances ?: []);

    $userids = array_unique(array_merge($registrationusers, $attendanceusers));
    $users = $userids ? user_get_users_by_id($userids) : [];

    echo html_writer::start_tag('div', ['class' => 'container-fluid my-4 px-3 px-md-4 px-lg-5 livesonner-container']);
        echo html_writer::tag('h3', get_string('registrationsheading', 'mod_livesonner'), ['class' => 'h4']);
        if ($registrations) {
            echo html_writer::div(get_string('registrationscount', 'mod_livesonner', count($registrations)), 'text-muted mb-3');
            echo html_writer::start_tag('div', ['class' => 'table-responsive']);
                echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover']);
                    echo html_writer::start_tag('thead');
                        echo html_writer::start_tag('tr');
                            echo html_writer::tag('th', get_string('registrationuser', 'mod_livesonner'), ['scope' => 'col']);
                            echo html_writer::tag('th', get_string('registrationtime', 'mod_livesonner'), ['scope' => 'col']);
                        echo html_writer::end_tag('tr');
                    echo html_writer::end_tag('thead');
                    echo html_writer::start_tag('tbody');
                        foreach ($registrations as $registration) {
                            if (!isset($users[$registration->userid])) {
                                $users[$registration->userid] = core_user::get_user($registration->userid);
                            }
                            if (!$users[$registration->userid]) {
                                continue;
                            }
                            $user = $users[$registration->userid];
                            $profileurl = new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]);
                            echo html_writer::start_tag('tr');
                                echo html_writer::tag('td', html_writer::link($profileurl, fullname($user)), ['class' => 'align-middle']);
                                echo html_writer::tag('td', userdate($registration->timecreated, get_string('strftimedatetimeshort', 'core_langconfig')), ['class' => 'align-middle']);
                            echo html_writer::end_tag('tr');
                        }
                    echo html_writer::end_tag('tbody');
                echo html_writer::end_tag('table');
            echo html_writer::end_tag('div');
        } else {
            echo html_writer::div(get_string('registrationsempty', 'mod_livesonner'), 'text-muted');
        }
    echo html_writer::end_tag('div');

    echo html_writer::start_tag('div', ['class' => 'container-fluid my-4 px-3 px-md-4 px-lg-5 livesonner-container']);
        echo html_writer::tag('h3', get_string('attendanceheading', 'mod_livesonner'), ['class' => 'h4']);
        if ($attendances) {
            echo html_writer::div(get_string('attendancecount', 'mod_livesonner', count($attendances)), 'text-muted mb-3');
            echo html_writer::start_tag('div', ['class' => 'table-responsive']);
                echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover']);
                    echo html_writer::start_tag('thead');
                        echo html_writer::start_tag('tr');
                            echo html_writer::tag('th', get_string('attendanceuser', 'mod_livesonner'), ['scope' => 'col']);
                            echo html_writer::tag('th', get_string('timeclicked', 'mod_livesonner'), ['scope' => 'col']);
                        echo html_writer::end_tag('tr');
                    echo html_writer::end_tag('thead');
                    echo html_writer::start_tag('tbody');
                        foreach ($attendances as $attendance) {
                            if (!isset($users[$attendance->userid])) {
                                $users[$attendance->userid] = core_user::get_user($attendance->userid);
                            }
                            if (!$users[$attendance->userid]) {
                                continue;
                            }
                            $user = $users[$attendance->userid];
                            $profileurl = new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]);
                            echo html_writer::start_tag('tr');
                                echo html_writer::tag('td', html_writer::link($profileurl, fullname($user)), ['class' => 'align-middle']);
                                echo html_writer::tag('td', userdate($attendance->timeclicked, get_string('strftimedatetimeshort', 'core_langconfig')), ['class' => 'align-middle']);
                            echo html_writer::end_tag('tr');
                        }
                    echo html_writer::end_tag('tbody');
                echo html_writer::end_tag('table');
            echo html_writer::end_tag('div');
        } else {
            echo html_writer::div(get_string('attendanceempty', 'mod_livesonner'), 'text-muted');
        }
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();

/**
 * Format a duration interval for display.
 *
 * @param int $seconds seconds remaining
 * @return string
 */
function livesonner_format_interval(int $seconds): string {
    return format_time(max(0, $seconds));
}

/**
 * Render the recorded video area.
 *
 * @param context_module $context context
 * @return string
 */
function livesonner_render_recording(string $recordingurl): string {
    if (empty($recordingurl)) {
        return html_writer::div(get_string('novideoavailable', 'mod_livesonner'), 'text-muted');
    }

    $videoid = livesonner_extract_youtube_id($recordingurl);
    if ($videoid === null) {
        $link = html_writer::link($recordingurl, $recordingurl, ['target' => '_blank', 'rel' => 'noopener noreferrer']);
        return html_writer::div($link, 'text-break');
    }

    $embedurl = new moodle_url('https://www.youtube.com/embed/' . $videoid, ['rel' => 0, 'modestbranding' => 1]);

    $iframe = html_writer::tag('iframe', '', [
        'src' => $embedurl,
        'title' => get_string('videosectiontitle', 'mod_livesonner'),
        'allow' => 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
        'allowfullscreen' => 'allowfullscreen',
        'class' => 'recording-iframe w-100 border-0 rounded',
    ]);

    return html_writer::div($iframe, 'recording-wrapper');
}
