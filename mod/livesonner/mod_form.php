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
require_once($CFG->dirroot . '/lib/enrollib.php');

/**
 * Class mod_livesonner_mod_form
 */
class mod_livesonner_mod_form extends moodleform_mod {
    /**
     * Defines the form elements.
     */
    public function definition() {
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

        $teacheroptions = $this->get_teacher_options();
        $mform->addElement('autocomplete', 'teacherid', get_string('teacher', 'mod_livesonner'), $teacheroptions, [
            'noselectionstring' => get_string('chooseateacher', 'mod_livesonner'),
        ]);
        $mform->setType('teacherid', PARAM_INT);
        $mform->addRule('teacherid', null, 'required', null, 'client');

        $mform->addElement('url', 'recordingurl', get_string('recordingurl', 'mod_livesonner'), ['size' => 64]);
        $mform->setType('recordingurl', PARAM_URL);
        $mform->addHelpButton('recordingurl', 'recordingurl', 'mod_livesonner');

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Retrieve the list of users available to be selected as a teacher.
     *
     * @return array<int, string>
     */
    protected function get_teacher_options(): array {
        $courseid = $this->current->course ?? ($this->course->id ?? 0);
        if (!$courseid) {
            return [];
        }

        $context = context_course::instance($courseid);
        $users = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email');

        $options = [];
        foreach ($users as $user) {
            $label = fullname($user);
            if (!empty($user->email)) {
                $label .= ' (' . $user->email . ')';
            }
            $options[$user->id] = $label;
        }

        core_collator::asort($options);

        return $options;
    }
}
