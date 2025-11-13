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

namespace mod_livesonner\local;

defined('MOODLE_INTERNAL') || die();

use context_course;
use context_module;
use context_user;
use core_date;
use core_user;
use moodle_exception;
use moodle_url;
use stored_file;

/**
 * Helper responsible for issuing and listing certificates for live classes.
 *
 * @package    mod_livesonner
 */
class certificate_manager {
    /** File area that stores issued certificates. */
    public const FILEAREA = 'certificates';

    /** @var string|null */
    protected static ?string $templatecache = null;

    /** @var bool */
    protected static bool $templatechecked = false;

    /**
     * Issues certificates for all recorded participants of a session.
     *
     * @param \stdClass $session LiveSonner record
     * @param \stdClass $course Course record
     * @param context_module $context Module context where files will be stored
     * @return int Number of certificates issued
     * @throws moodle_exception When the template cannot be loaded
     */
    public static function issue_for_session(\stdClass $session, \stdClass $course, context_module $context): int {
        global $CFG, $DB, $SITE;

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->libdir . '/pdflib.php');

        if (!extension_loaded('imagick')) {
            throw new moodle_exception('error:imagemagickmissing', 'mod_livesonner');
        }

        $attendances = $DB->get_records('livesonner_attendance', ['livesonnerid' => $session->id], '', 'id, userid');
        if (!$attendances) {
            return 0;
        }

        $template = self::get_template_contents();
        if ($template === null) {
            throw new moodle_exception('error:certificatetemplate', 'mod_livesonner');
        }

        $userids = array_map('intval', array_column(array_values($attendances), 'userid'));
        if (empty($userids)) {
            return 0;
        }

        $users = user_get_users_by_id($userids);
        if (!$users) {
            return 0;
        }

        $teachername = '';
        if (!empty($session->teacherid)) {
            $teacher = core_user::get_user($session->teacherid);
            if ($teacher) {
                $teachername = fullname($teacher);
            }
        }
        if ($teachername === '') {
            $teachername = format_string($SITE->fullname);
        }

        $coursecontext = context_course::instance($course->id);
        $fs = get_file_storage();
        $issued = 0;
        $now = time();

        foreach ($users as $userid => $user) {
            if (!$user || !array_key_exists($userid, $attendances)) {
                continue;
            }

            $usercontext = context_user::instance($userid);
            $username = format_string(fullname($user), true, ['context' => $usercontext]);
            $sessionname = format_string($session->name, true, ['context' => $context]);
            $issuedate = userdate($now, get_string('strftimedatefullshort', 'core_langconfig'),
                core_date::get_user_timezone($user));
            $instructor = format_string($teachername, true, ['context' => $coursecontext]);

            $html = str_replace([
                '{{username}}',
                '{{coursename}}',
                '{{issuedate}}',
                '{{instructorname}}',
            ], [
                $username,
                $sessionname,
                $issuedate,
                $instructor,
            ], $template);

            $content = self::generate_certificate_png($html, $sessionname);

            $filename = clean_filename('certificado-' . $session->id . '-' . $userid . '.png');

            $record = $DB->get_record('livesonner_certificates', [
                'livesonnerid' => $session->id,
                'userid' => $userid,
            ]);

            if ($record) {
                $record->filename = $filename;
                $record->timemodified = $now;
                $DB->update_record('livesonner_certificates', $record);
                $itemid = (int)$record->id;
            } else {
                $record = (object) [
                    'livesonnerid' => $session->id,
                    'userid' => $userid,
                    'filename' => $filename,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ];
                $itemid = $DB->insert_record('livesonner_certificates', $record);
            }

            $fs->delete_area_files($context->id, 'mod_livesonner', self::FILEAREA, $itemid);
            $fs->create_file_from_string([
                'contextid' => $context->id,
                'component' => 'mod_livesonner',
                'filearea' => self::FILEAREA,
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => $filename,
            ], $content);

            $issued++;
        }

        return $issued;
    }

    /**
     * Returns the certificates issued to the requested user.
     *
     * @param int $userid
     * @return array<int, array<string, mixed>>
     */
    public static function get_user_certificates(int $userid): array {
        global $DB;

        $sql = "SELECT lc.id, lc.livesonnerid, lc.filename, lc.timecreated, lc.timemodified, lc.userid,
                       l.name AS sessionname, l.course AS courseid, cm.id AS cmid, c.fullname AS coursename
                  FROM {livesonner_certificates} lc
                  JOIN {livesonner} l ON l.id = lc.livesonnerid
                  JOIN {course} c ON c.id = l.course
                  JOIN {modules} m ON m.name = :modname
                  JOIN {course_modules} cm ON cm.instance = l.id AND cm.module = m.id
                 WHERE lc.userid = :userid
                   AND cm.deletioninprogress = 0
              ORDER BY lc.timecreated DESC";

        $records = $DB->get_records_sql($sql, [
            'modname' => 'livesonner',
            'userid' => $userid,
        ]);

        $certificates = [];
        foreach ($records as $record) {
            $coursecontext = context_course::instance($record->courseid);
            $modulecontext = context_module::instance($record->cmid);

            $fileurl = moodle_url::make_pluginfile_url(
                $modulecontext->id,
                'mod_livesonner',
                self::FILEAREA,
                $record->id,
                '/',
                $record->filename
            )->out(false);

            $previewurl = '';
            if (preg_match('/\.png$/i', (string)$record->filename)) {
                $previewurl = $fileurl;
            }

            $certificates[] = [
                'id' => (int)$record->id,
                'sessionid' => (int)$record->livesonnerid,
                'sessionname' => format_string($record->sessionname, true, ['context' => $modulecontext]),
                'coursename' => format_string($record->coursename, true, ['context' => $coursecontext]),
                'issuedate' => (int)$record->timecreated,
                'issuedatestring' => userdate($record->timecreated, get_string('strftimedatefullshort', 'core_langconfig')),
                'fileurl' => $fileurl,
                'filename' => $record->filename,
                'previewurl' => $previewurl,
            ];
        }

        return $certificates;
    }

    /**
     * Stores a manually uploaded certificate for a given user and session.
     *
     * @param int $sessionid
     * @param int $userid
     * @param stored_file $file
     * @param string $name
     * @return array<string, mixed>
     */
    public static function store_manual_certificate(int $sessionid, int $userid, stored_file $file, string $name = ''): array {
        global $DB;

        $session = $DB->get_record('livesonner', ['id' => $sessionid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $session->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('livesonner', $session->id, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $filename = trim($name) !== '' ? $name : $file->get_filename();
        $filename = clean_filename($filename);
        if ($filename === '') {
            $filename = 'certificado.png';
        }
        if (!preg_match('/\.png$/i', $filename)) {
            $filename .= '.png';
        }

        $now = time();
        $record = $DB->get_record('livesonner_certificates', [
            'livesonnerid' => $session->id,
            'userid' => $userid,
        ]);

        if ($record) {
            $record->filename = $filename;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $DB->update_record('livesonner_certificates', $record);
            $itemid = $record->id;
        } else {
            $record = (object) [
                'livesonnerid' => $session->id,
                'userid' => $userid,
                'filename' => $filename,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $itemid = $DB->insert_record('livesonner_certificates', $record);
            $record->id = $itemid;
        }

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_livesonner', self::FILEAREA, $itemid);

        $filerecord = [
            'contextid' => $context->id,
            'component' => 'mod_livesonner',
            'filearea' => self::FILEAREA,
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => $filename,
        ];
        $fs->create_file_from_storedfile($filerecord, $file);

        $coursecontext = context_course::instance($course->id);
        $modulecontext = context_module::instance($cm->id);

        $fileurl = moodle_url::make_pluginfile_url(
            $context->id,
            'mod_livesonner',
            self::FILEAREA,
            $record->id,
            '/',
            $filename
        )->out(false);

        $previewurl = '';
        if (preg_match('/\.png$/i', $filename)) {
            $previewurl = $fileurl;
        }

        return [
            'id' => $record->id,
            'sessionid' => $session->id,
            'sessionname' => format_string($session->name, true, ['context' => $modulecontext]),
            'coursename' => format_string($course->fullname, true, ['context' => $coursecontext]),
            'issuedate' => $now,
            'issuedatestring' => userdate($now, get_string('strftimedatefullshort', 'core_langconfig')),
            'fileurl' => $fileurl,
            'filename' => $filename,
            'previewurl' => $previewurl,
        ];
    }

    /**
     * Generates the PNG binary contents for a certificate based on the HTML template.
     *
     * @param string $html
     * @param string $sessionname
     * @return string
     * @throws moodle_exception When the PNG cannot be rendered
     */
    protected static function generate_certificate_png(string $html, string $sessionname): string {
        global $CFG;

        require_once($CFG->libdir . '/pdflib.php');

        if (!extension_loaded('imagick')) {
            throw new moodle_exception('error:imagemagickmissing', 'mod_livesonner');
        }

        $pdf = new \pdf();
        $pdf->SetTitle($sessionname);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0, true);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage('L', 'LETTER');
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();
        $pdfcontents = $pdf->Output('', 'S');
        unset($pdf);

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImageBlob($pdfcontents);
            $imagick->setIteratorIndex(0);
            $imagick->setImageBackgroundColor('white');
            if (method_exists($imagick, 'setImageAlphaChannel')) {
                $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
            }
            if (method_exists($imagick, 'mergeImageLayers')) {
                $flattened = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                if ($flattened instanceof \Imagick) {
                    $imagick = $flattened;
                }
            } else if (method_exists($imagick, 'flattenImages')) {
                $flattened = $imagick->flattenImages();
                if ($flattened instanceof \Imagick) {
                    $imagick = $flattened;
                }
            }
            $imagick->setImageFormat('png');
            $imagick->setImageCompressionQuality(90);
            $pngcontents = $imagick->getImageBlob();
        } catch (\ImagickException $exception) {
            throw new moodle_exception('error:imagemagickrender', 'mod_livesonner', '', null, $exception->getMessage());
        } finally {
            if (isset($imagick) && $imagick instanceof \Imagick) {
                $imagick->clear();
                $imagick->destroy();
            }
        }

        return $pngcontents;
    }

    /**
     * Loads the certificate HTML template from disk.
     *
     * @return string|null
     */
    protected static function get_template_contents(): ?string {
        global $CFG;

        if (self::$templatechecked) {
            return self::$templatecache;
        }
        self::$templatechecked = true;

        $candidates = [
            $CFG->dirroot . '/mod/livesonner/htmlcertificado.html',
        ];

        // Allow falling back to the legacy location at the Moodle root when present.
        $legacy = $CFG->dirroot . '/htmlcertificado.html';
        if (!in_array($legacy, $candidates, true)) {
            $candidates[] = $legacy;
        }

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                $contents = file_get_contents($path);
                if ($contents !== false) {
                    self::$templatecache = $contents;
                    break;
                }
            }
        }

        return self::$templatecache;
    }
}
