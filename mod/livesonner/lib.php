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
 * Library of interface functions and constants for LiveSonner module
 *
 * @package    mod_livesonner
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the Moodle features supported by the module.
 *
 * @param string $feature feature constant
 * @return mixed
 */
function livesonner_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Adds a LiveSonner instance
 *
 * @param stdClass $data form data
 * @param mod_livesonner_mod_form|null $mform form
 * @return int new instance ID
 */
function livesonner_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->isfinished = !empty($data->isfinished) ? 1 : 0;
    $data->teacherid = $data->teacherid ?? 0;

    $normalizedurl = livesonner_get_normalized_recording_url($data->recordingurl ?? '');
    $data->recordingurl = $normalizedurl === null ? '' : $normalizedurl;

    $data->id = $DB->insert_record('livesonner', $data);

    return $data->id;
}

/**
 * Updates a LiveSonner instance
 *
 * @param stdClass $data form data
 * @param mod_livesonner_mod_form|null $mform form
 * @return bool success
 */
function livesonner_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->isfinished = empty($data->isfinished) ? 0 : 1;
    $data->teacherid = $data->teacherid ?? 0;

    $normalizedurl = livesonner_get_normalized_recording_url($data->recordingurl ?? '');
    $data->recordingurl = $normalizedurl === null ? '' : $normalizedurl;

    $result = $DB->update_record('livesonner', $data);

    return $result;
}

/**
 * Deletes a LiveSonner instance
 *
 * @param int $id instance id
 * @return bool success
 */
function livesonner_delete_instance($id) {
    global $DB;

    if (!$livesonner = $DB->get_record('livesonner', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('livesonner_attendance', ['livesonnerid' => $id]);
    $DB->delete_records('livesonner', ['id' => $id]);

    return true;
}

/**
 * Determines if the current user can manage the session (finalise it, edit recording, view attendance).
 *
 * @param stdClass $livesonner LiveSonner record
 * @param context_module $context activity context
 * @return bool
 */
function livesonner_user_can_manage_session(stdClass $livesonner, context_module $context): bool {
    global $USER;

    if (has_capability('mod/livesonner:manage', $context)) {
        return true;
    }

    return !empty($livesonner->teacherid) && (int)$livesonner->teacherid === (int)$USER->id;
}

/**
 * Normalise a YouTube recording URL.
 *
 * Returns an empty string when no URL is provided, the canonical watch URL when the
 * value is recognised as a valid YouTube link, or null when the URL is invalid.
 *
 * @param string|null $url raw URL provided by the user
 * @return string|null
 */
function livesonner_get_normalized_recording_url(?string $url): ?string {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }

    $videoid = livesonner_extract_youtube_id($url);
    if ($videoid === null) {
        return null;
    }

    return 'https://www.youtube.com/watch?v=' . $videoid;
}

/**
 * Extract the YouTube video identifier from a URL when possible.
 *
 * @param string $url URL to analyse
 * @return string|null
 */
function livesonner_extract_youtube_id(string $url): ?string {
    $parts = @parse_url(trim($url));
    if (empty($parts['host'])) {
        return null;
    }

    $host = core_text::strtolower($parts['host']);
    $path = isset($parts['path']) ? trim($parts['path'], '/') : '';

    if ($host === 'youtu.be') {
        $candidate = $path !== '' ? explode('/', $path)[0] : '';
    } else if (preg_match('/(^|\.)youtube\.com$/', $host)) {
        if ($path === 'watch') {
            $query = [];
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $query);
            }
            $candidate = $query['v'] ?? '';
        } else if (strpos($path, 'embed/') === 0) {
            $candidate = substr($path, strlen('embed/'));
        } else if (strpos($path, 'shorts/') === 0) {
            $candidate = substr($path, strlen('shorts/'));
        } else if (strpos($path, 'live/') === 0) {
            $candidate = substr($path, strlen('live/'));
        } else {
            $candidate = '';
        }
    } else {
        return null;
    }

    $candidate = trim($candidate);
    if ($candidate === '') {
        return null;
    }

    // Remove any trailing parameters from shortened URLs.
    if (strpos($candidate, '?') !== false) {
        $candidate = substr($candidate, 0, strpos($candidate, '?'));
    }

    if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate)) {
        return null;
    }

    return $candidate;
}

/**
 * Retrieve the catalog of LiveSonner sessions for the Painel de aulas ao vivo integration.
 *
 * @param int $userid The user requesting the catalog
 * @return array[] Array of session data formatted for the panel
 */
function mod_livesonner_painelaulas_get_catalog(int $userid): array {
    return array_values(mod_livesonner_painelaulas_collect_sessions($userid));
}

/**
 * Retrieve the user's enrolments for the Painel de aulas ao vivo integration.
 *
 * @param int $userid The user requesting the enrolments
 * @return array[] Array of session data formatted for the panel
 */
function mod_livesonner_painelaulas_get_enrolments(int $userid): array {
    $sessions = mod_livesonner_painelaulas_collect_sessions($userid);

    return array_values(array_filter($sessions, static function(array $session): bool {
        return !empty($session['isenrolled']);
    }));
}

/**
 * Try to enrol the given user into the course associated with the session.
 *
 * @param int $userid The user that should be enrolled
 * @param int $sessionid LiveSonner session identifier
 * @return array{status:bool,message:string} Result information for the panel
 */
function mod_livesonner_painelaulas_enrol_session(int $userid, int $sessionid): array {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot . '/enrol/lib.php');

    $session = $DB->get_record('livesonner', ['id' => $sessionid]);
    if (!$session) {
        return [
            'status' => false,
            'message' => get_string('painelaulasinvalidsession', 'mod_livesonner'),
        ];
    }

    $coursecontext = context_course::instance($session->course);

    if ($USER->id !== $userid && !has_capability('mod/livesonner:manage', $coursecontext)) {
        return [
            'status' => false,
            'message' => get_string('painelaulaspermissiondenied', 'mod_livesonner'),
        ];
    }

    if (is_enrolled($coursecontext, $userid, '', true)) {
        return [
            'status' => true,
            'message' => get_string('painelaulasalreadyenrolled', 'mod_livesonner'),
        ];
    }

    $instances = enrol_get_instances($session->course, true);
    $enrolled = false;

    foreach ($instances as $instance) {
        if ((int)$instance->status !== ENROL_INSTANCE_ENABLED) {
            continue;
        }

        $plugin = enrol_get_plugin($instance->enrol);
        if (!$plugin || !method_exists($plugin, 'enrol_user')) {
            continue;
        }

        // Respect enrolment windows when defined.
        $now = time();
        if (!empty($instance->enrolstartdate) && (int)$instance->enrolstartdate > $now) {
            continue;
        }
        if (!empty($instance->enrolenddate) && (int)$instance->enrolenddate !== 0 && (int)$instance->enrolenddate < $now) {
            continue;
        }

        try {
            $plugin->enrol_user($instance, $userid, $instance->roleid ?? null);
            $enrolled = true;
            break;
        } catch (moodle_exception $exception) {
            debugging($exception->getMessage(), DEBUG_DEVELOPER);
        } catch (Exception $exception) {
            debugging($exception->getMessage(), DEBUG_DEVELOPER);
        }
    }

    if (!$enrolled && is_enrolled($coursecontext, $userid, '', true)) {
        $enrolled = true;
    }

    if ($enrolled) {
        return [
            'status' => true,
            'message' => get_string('painelaulasenrolmentsuccess', 'mod_livesonner'),
        ];
    }

    return [
        'status' => false,
        'message' => get_string('painelaulasenrolmentfailure', 'mod_livesonner'),
    ];
}

/**
 * Internal helper that gathers the sessions the user can see from the database.
 *
 * @param int $userid The target user
 * @return array[] Array of formatted session data keyed by session id
 */
function mod_livesonner_painelaulas_collect_sessions(int $userid): array {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/user/lib.php');

    $now = time();

    $sql = "SELECT l.*, cm.id AS cmid, cm.visible AS cmvisible, c.fullname AS coursename,\n                   c.shortname AS courseshortname, c.visible AS coursevisible\n              FROM {livesonner} l\n        INNER JOIN {course} c ON c.id = l.course\n        INNER JOIN {modules} m ON m.name = :modname\n        INNER JOIN {course_modules} cm ON cm.instance = l.id AND cm.module = m.id\n             WHERE cm.deletioninprogress = 0\n          ORDER BY l.timestart ASC";

    $records = $DB->get_records_sql($sql, ['modname' => 'livesonner']);

    $sessions = [];
    $teachercache = [];

    foreach ($records as $record) {
        $coursecontext = context_course::instance($record->course);
        $modulecontext = context_module::instance($record->cmid);

        if (!$record->coursevisible && !has_capability('moodle/course:viewhiddencourses', $coursecontext, $userid, false)) {
            continue;
        }

        if (!$record->cmvisible && !has_capability('moodle/course:viewhiddenactivities', $modulecontext, $userid, false)) {
            continue;
        }

        $starttime = (int)$record->timestart;
        $durationseconds = max(0, (int)$record->duration * MINSECS);
        $fallbackduration = $durationseconds ?: HOURSECS;
        $endtime = $starttime ? $starttime + $fallbackduration : 0;

        if (!empty($record->isfinished)) {
            continue;
        }

        if ($endtime && $endtime <= $now) {
            continue;
        }

        $isenrolled = is_enrolled($coursecontext, $userid, '', true);

        $summary = '';
        if (!empty($record->intro)) {
            $summary = format_text($record->intro, $record->introformat, ['context' => $modulecontext, 'para' => false]);
        }

        $tags = [];
        if (class_exists('core_tag_tag') && \core_tag_tag::is_enabled('mod_livesonner', 'livesonner')) {
            $tags = array_values(\core_tag_tag::get_item_tags_array('mod_livesonner', 'livesonner', $record->id));
        }

        $instructor = null;
        if (!empty($record->teacherid)) {
            if (!array_key_exists($record->teacherid, $teachercache)) {
                $teachercache[$record->teacherid] = core_user::get_user($record->teacherid);
            }
            $teacher = $teachercache[$record->teacherid];
            if ($teacher) {
                $instructor = [
                    'id' => (int)$teacher->id,
                    'fullname' => fullname($teacher),
                    'name' => fullname($teacher),
                    'profileurl' => (new moodle_url('/user/view.php', ['id' => $teacher->id, 'course' => $record->course]))->out(false),
                ];
            }
        }

        $sessions[$record->id] = [
            'id' => (int)$record->id,
            'cmid' => (int)$record->cmid,
            'courseid' => (int)$record->course,
            'coursename' => format_string($record->coursename, true, ['context' => $coursecontext]),
            'courseshortname' => format_string($record->courseshortname, true, ['context' => $coursecontext]),
            'name' => format_string($record->name, true, ['context' => $modulecontext]),
            'summary' => $summary,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'duration' => $durationseconds,
            'durationminutes' => (int)$record->duration,
            'location' => (string)$record->meeturl,
            'tags' => $tags,
            'instructor' => $instructor,
            'isenrolled' => $isenrolled,
            'isfinished' => !empty($record->isfinished),
            'launchurl' => (new moodle_url('/mod/livesonner/view.php', ['id' => $record->cmid]))->out(false),
            'meetingurl' => (string)$record->meeturl,
            'recordingurl' => (string)($record->recordingurl ?? ''),
        ];
    }

    return $sessions;
}

/**
 * Obtains the information needed to display the module in the course overview
 *
 * @param cm_info $coursemodule course module
 * @return cached_cm_info|null
 */
function livesonner_get_coursemodule_info($coursemodule) {
    global $DB;

    if (!$livesonner = $DB->get_record('livesonner', ['id' => $coursemodule->instance], 'id, name, timestart, duration')) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $livesonner->name;
    $info->content = get_string('summarycoursemodule', 'mod_livesonner', [
        'date' => userdate($livesonner->timestart),
        'duration' => $livesonner->duration,
    ]);

    return $info;
}

/**
 * Completion state
 *
 * @param stdClass $course course
 * @param cm_info $cm course module
 * @param int $userid user id
 * @param bool $type type
 * @return bool
 */
function livesonner_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    $livesonner = $DB->get_record('livesonner', ['id' => $cm->instance], 'id, isfinished', MUST_EXIST);

    return !empty($livesonner->isfinished);
}

/**
 * Add additional information to the course module navigation
 *
 * @param navigation_node $navigation navigation node
 * @param stdClass $course course
 * @param stdClass $module module
 * @param cm_info $cm course module
 */
function livesonner_extend_navigation(navigation_node $navigation, stdClass $course, stdClass $module, cm_info $cm) {
    if (has_capability('mod/livesonner:manage', $cm->context)) {
        $navigation->add(
            get_string('finalizeclass', 'mod_livesonner'),
            new moodle_url('/mod/livesonner/view.php', ['id' => $cm->id, 'action' => 'finalize']),
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/completion-manual-y', '')
        );
    }
}

/**
 * Describes file areas within the module.
 *
 * @param stdClass $course course
 * @param stdClass $cm course module
 * @param context_module $context context
 * @return array
 */
function livesonner_get_file_areas($course, $cm, $context) {
    return [];
}
