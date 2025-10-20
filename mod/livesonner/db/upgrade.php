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
 * Upgrade steps for the LiveSonner module.
 *
 * @package    mod_livesonner
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute LiveSonner upgrade steps.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool
 */
function xmldb_livesonner_upgrade(int $oldversion): bool {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/lib/xmldb/xmldb_field.php');
    require_once($CFG->dirroot . '/lib/xmldb/xmldb_table.php');
    require_once($CFG->libdir . '/filestorage/file_storage.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2024052801) {
        $table = new xmldb_table('livesonner');

        if (!$dbman->field_exists($table, 'teacherid')) {
            $field = new xmldb_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'meeturl');
            $dbman->add_field($table, $field);
        }

        if (!$dbman->field_exists($table, 'recordingurl')) {
            $field = new xmldb_field('recordingurl', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'teacherid');
            $dbman->add_field($table, $field);
        }

        $fs = get_file_storage();
        $contexts = $DB->get_records_sql(
            "SELECT ctx.id
               FROM {context} ctx
               JOIN {course_modules} cm ON cm.id = ctx.instanceid
               JOIN {modules} m ON m.id = cm.module
              WHERE ctx.contextlevel = ? AND m.name = ?",
            [CONTEXT_MODULE, 'livesonner']
        );

        foreach ($contexts as $context) {
            $fs->delete_area_files($context->id, 'mod_livesonner', 'video');
        }

        upgrade_mod_savepoint(true, 2024052801, 'livesonner');
    }

    return true;
}
