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

    $data->id = $DB->insert_record('livesonner', $data);

    livesonner_save_video_files($data);

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

    $result = $DB->update_record('livesonner', $data);

    livesonner_save_video_files($data);

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

    if ($cm = get_coursemodule_from_instance('livesonner', $id, $livesonner->course, false, IGNORE_MISSING)) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_livesonner', 'video');
    }

    $DB->delete_records('livesonner_attendance', ['livesonnerid' => $id]);
    $DB->delete_records('livesonner', ['id' => $id]);

    return true;
}

/**
 * Saves the uploaded video file
 *
 * @param stdClass $data form data
 * @return void
 */
function livesonner_save_video_files(stdClass $data): void {
    global $CFG;

    $cmid = $data->coursemodule ?? null;
    if (!$cmid && !empty($data->id)) {
        if ($cm = get_coursemodule_from_instance('livesonner', $data->id, $data->course ?? 0, false, IGNORE_MISSING)) {
            $cmid = $cm->id;
        }
    }

    if (!$cmid) {
        return;
    }

    $context = context_module::instance($cmid);
    $options = ['subdirs' => 0, 'maxfiles' => 1, 'maxbytes' => $CFG->maxbytes, 'accepted_types' => ['video']];
    $draftitemid = $data->video_filemanager ?? 0;
    file_save_draft_area_files($draftitemid, $context->id, 'mod_livesonner', 'video', 0, $options);
}

/**
 * Serves files from the LiveSonner file areas
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param context_module $context context
 * @param string $filearea file area name
 * @param array $args arguments
 * @param bool $forcedownload if forced download
 * @param array $options additional options
 * @return void
 */
function livesonner_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, false, $cm);

    if ($filearea !== 'video') {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $itemid = 0;
    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';
    if ($filepath === '//') {
        $filepath = '/';
    }

    if (!$file = $fs->get_file($context->id, 'mod_livesonner', 'video', $itemid, $filepath, $filename)) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
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
    return [
        'video' => get_string('recordedvideo', 'mod_livesonner'),
    ];
}
