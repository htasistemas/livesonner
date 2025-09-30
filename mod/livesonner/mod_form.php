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
 * Form for creating and editing LiveSonner activities
 *
 * @package    mod_livesonner
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Class mod_livesonner_mod_form
 */
class mod_livesonner_mod_form extends moodleform_mod {
    /**
     * Defines the form elements.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement('date_time_selector', 'timestart', get_string('timestart', 'mod_livesonner'));
        $mform->addRule('timestart', null, 'required', null, 'client');

        $mform->addElement('text', 'duration', get_string('duration', 'mod_livesonner'), ['size' => 10]);
        $mform->setType('duration', PARAM_INT);
        $mform->setDefault('duration', 60);
        $mform->addRule('duration', null, 'required', null, 'client');
        $mform->addRule('duration', get_string('positivevalue', 'mod_livesonner'), 'regex', '/^[1-9][0-9]*$/', 'client');

        $mform->addElement('url', 'meeturl', get_string('meeturl', 'mod_livesonner'), ['size' => 64]);
        $mform->setType('meeturl', PARAM_URL);
        $mform->addRule('meeturl', null, 'required', null, 'client');

        $videooptions = ['subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 1, 'accepted_types' => ['video']];
        $mform->addElement('filemanager', 'video_filemanager', get_string('recordedvideo', 'mod_livesonner'), null, $videooptions);
        $mform->addHelpButton('video_filemanager', 'recordedvideo', 'mod_livesonner');

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Preprocess form data before displaying
     *
     * @param array $defaultvalues default values
     */
    public function data_preprocessing(&$defaultvalues) {
        global $CFG;

        $videooptions = ['subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 1, 'accepted_types' => ['video']];
        $contextid = $this->context->id ?? 0;
        if (!empty($this->current->coursemodule)) {
            $contextid = context_module::instance($this->current->coursemodule)->id;
        }

        $draftitemid = file_get_submitted_draft_itemid('video_filemanager');
        file_prepare_draft_area($draftitemid, $contextid, 'mod_livesonner', 'video', 0, $videooptions);
        $defaultvalues['video_filemanager'] = $draftitemid;
    }
}
