<?php
// This file is part of Moodle - http://moodle.org/.
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

namespace local_aulasaovivo\form;

defined('MOODLE_INTERNAL') || die();

use context_course;
use context_module;
use context_system;
use context_user;
use core_form\dynamic_form;
use mod_livesonner\local\certificate_manager;
use moodle_exception;
use required_capability_exception;
use stored_file;
use moodle_url;

/**
 * Dynamic form that allows administrators to issue manual certificates.
 *
 * @package   local_aulasaovivo
 */
class manual_certificate extends dynamic_form {

    /**
     * Form definition.
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('autocomplete', 'sessionid',
            get_string('manualcertificate:session', 'local_aulasaovivo'),
            $this->get_session_options(), [
                'placeholder' => get_string('manualcertificate:sessionplaceholder', 'local_aulasaovivo'),
                'noselectionstring' => get_string('manualcertificate:sessionplaceholder', 'local_aulasaovivo'),
            ]);
        $mform->setType('sessionid', PARAM_INT);
        $mform->addRule('sessionid', get_string('required'), 'required', null, 'client');

        $mform->addElement('autocomplete', 'userid',
            get_string('manualcertificate:user', 'local_aulasaovivo'), [], [
                'ajax' => 'core_user/form_user_selector',
                'multiple' => false,
                'placeholder' => get_string('manualcertificate:userplaceholder', 'local_aulasaovivo'),
            ]);
        $mform->setType('userid', PARAM_INT);
        $mform->addRule('userid', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'name',
            get_string('manualcertificate:name', 'local_aulasaovivo'));
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('filepicker', 'certificate',
            get_string('manualcertificate:file', 'local_aulasaovivo'), null, [
                'accepted_types' => ['.png'],
                'maxbytes' => 0,
                'subdirs' => 0,
            ]);
        $mform->addRule('certificate', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(
            true,
            get_string('manualcertificate:submit', 'local_aulasaovivo')
        );
    }

    /**
     * Returns the form submission context.
     *
     * @return context_system
     */
    public function get_context_for_dynamic_submission(): context_system {
        return context_system::instance();
    }

    /**
     * Page URL used by the dynamic form infrastructure (autosave, etc.).
     *
     * @return moodle_url
     */
    public function get_page_url_for_dynamic_submission(): moodle_url {
        // Ajusta se a página "oficial" do dashboard for outra.
        return new moodle_url('/local/aulasaovivo/index.php');
    }

    /**
     * Validates access prior to processing the form.
     */
    public function check_access_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        if (!has_capability('moodle/site:config', $context)) {
            throw new required_capability_exception(
                $context,
                'moodle/site:config',
                'nopermissions',
                'manualcertificate'
            );
        }
    }

    /**
     * Loads the form data when opened.
     */
    public function set_data_for_dynamic_submission(): void {
        // Nothing to preload.
    }

    /**
     * Processes the submitted form data.
     *
     * @return array<string, string>
     */
    public function process_dynamic_submission() {
        global $DB, $USER;

        $data = (object)$this->get_data();
        $sessionid = (int)$data->sessionid;
        $userid = (int)$data->userid;
        $name = trim((string)($data->name ?? ''));
        $draftid = (int)$data->certificate;

        if (!$DB->record_exists('user', ['id' => $userid, 'deleted' => 0])) {
            throw new moodle_exception('manualcertificate:invaliduser', 'local_aulasaovivo');
        }

        // Obtém o arquivo enviado na área de rascunho do usuário.
        $draftfile = $this->get_draft_file($draftid);

        // Persiste o certificado usando o gerenciador do módulo.
        $certificate = certificate_manager::store_manual_certificate(
            $sessionid,
            $userid,
            $draftfile,
            $name
        );

        // Limpa a área de rascunho do usuário para evitar arquivos órfãos.
        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftid);

        return [
            'message' => get_string(
                'manualcertificate:success',
                'local_aulasaovivo',
                $certificate['sessionname']
            ),
        ];
    }

    /**
     * Retrieves the available sessions for the selector.
     *
     * @return array<int, string>
     */
    public function get_session_options(): array {
        global $DB;

        $records = $DB->get_records_sql("
            SELECT l.id,
                   l.name,
                   c.id AS courseid,
                   c.fullname AS coursename,
                   cm.id AS cmid
              FROM {livesonner} l
              JOIN {course} c ON c.id = l.course
              JOIN {modules} m ON m.name = :modname
              JOIN {course_modules} cm
                ON cm.instance = l.id
               AND cm.module = m.id
             WHERE cm.deletioninprogress = 0
          ORDER BY c.fullname, l.name
        ", ['modname' => 'livesonner']);

        $options = [];
        foreach ($records as $record) {
            $coursecontext = context_course::instance($record->courseid);
            $modulecontext = context_module::instance($record->cmid);

            $sessionname = format_string($record->name, true, ['context' => $modulecontext]);
            $coursename = format_string($record->coursename, true, ['context' => $coursecontext]);

            $options[$record->id] = $coursename . ' — ' . $sessionname;
        }

        return $options;
    }

    /**
     * Returns the single file uploaded to the user's draft area.
     *
     * @param int $draftid
     * @return stored_file
     */
    public function get_draft_file(int $draftid): stored_file {
        global $USER;

        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);

        $files = $fs->get_area_files(
            $usercontext->id,
            'user',
            'draft',
            $draftid,
            'id DESC',
            false
        );

        if (empty($files)) {
            throw new moodle_exception('manualcertificate:missingfile', 'local_aulasaovivo');
        }

        $file = reset($files);
        if (!in_array($file->get_mimetype(), ['image/png', 'image/x-png'], true)) {
            throw new moodle_exception('manualcertificate:invalidfiletype', 'local_aulasaovivo');
        }

        return $file;
    }
}
