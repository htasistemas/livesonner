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

namespace mod_livesonner\privacy;

defined('MOODLE_INTERNAL') || die();

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider implementation for LiveSonner.
 *
 * @package    mod_livesonner
 */
class provider implements
    \core_privacy\local\metadata\provider,
    plugin_provider,
    \core_privacy\local\request\userlist_provider {

    /**
     * Returns metadata about stored data.
     *
     * @param collection $collection metadata collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('livesonner', [
            'course' => 'privacy:metadata:livesonner:course',
            'name' => 'privacy:metadata:livesonner:name',
            'timestart' => 'privacy:metadata:livesonner:timestart',
            'duration' => 'privacy:metadata:livesonner:duration',
            'meeturl' => 'privacy:metadata:livesonner:meeturl',
            'teacherid' => 'privacy:metadata:livesonner:teacherid',
            'recordingurl' => 'privacy:metadata:livesonner:recordingurl',
        ], 'privacy:metadata:livesonner');

        $collection->add_database_table('livesonner_attendance', [
            'livesonnerid' => 'privacy:metadata:livesonner_attendance:livesonnerid',
            'userid' => 'privacy:metadata:livesonner_attendance:userid',
            'timeclicked' => 'privacy:metadata:livesonner_attendance:timeclicked',
        ], 'privacy:metadata:livesonner_attendance');

        return $collection;
    }

    /**
     * Return list of contexts that contain user data for the given user.
     *
     * @param int $userid user id
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :modulelevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {livesonner} l ON l.id = cm.instance
                  JOIN {livesonner_attendance} la ON la.livesonnerid = l.id
                 WHERE la.userid = :userid";

        $params = [
            'modulelevel' => CONTEXT_MODULE,
            'modname' => 'livesonner',
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export user data for approved contexts.
     *
     * @param approved_contextlist $contextlist list of approved contexts
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->get_contextids())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('livesonner', $context->instanceid, 0, false, MUST_EXIST);
            $livesonner = $DB->get_record('livesonner', ['id' => $cm->instance], '*', MUST_EXIST);

            $record = $DB->get_record('livesonner_attendance', [
                'livesonnerid' => $livesonner->id,
                'userid' => $userid,
            ]);

            if ($record) {
                $data = (object) [
                    'name' => format_string($livesonner->name, true),
                    'timeclicked' => userdate($record->timeclicked),
                    'meeturl' => $livesonner->meeturl,
                ];

                writer::with_context($context)->export_data(['attendance'], $data);
            }
        }
    }

    /**
     * Delete all user data for the given context.
     *
     * @param context $context context
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('livesonner', $context->instanceid, 0, false, MUST_EXIST);
        $DB->delete_records('livesonner_attendance', ['livesonnerid' => $cm->instance]);
    }

    /**
     * Delete user data for the supplied contexts.
     *
     * @param approved_contextlist $contextlist context list
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->get_contextids())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('livesonner', $context->instanceid, 0, false, MUST_EXIST);
            $DB->delete_records('livesonner_attendance', ['livesonnerid' => $cm->instance, 'userid' => $userid]);
        }
    }

    /**
     * Get user list for a context.
     *
     * @param userlist $userlist user list
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT la.userid
                  FROM {livesonner_attendance} la
                  JOIN {course_modules} cm ON cm.instance = la.livesonnerid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";

        $params = [
            'modname' => 'livesonner',
            'cmid' => $context->instanceid,
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist approved user list
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        $cm = get_coursemodule_from_id('livesonner', $context->instanceid, 0, false, MUST_EXIST);
        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['livesonnerid'] = $cm->instance;

        $DB->delete_records_select('livesonner_attendance', "livesonnerid = :livesonnerid AND userid $insql", $params);
    }
}
