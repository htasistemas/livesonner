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

namespace mod_livesonner\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a LiveSonner module is viewed
 *
 * @package    mod_livesonner
 */
class course_module_viewed extends \core\event\course_module_viewed {
    /**
     * Init method.
     */
    protected function init() {
        parent::init();
        $this->data['objecttable'] = 'livesonner';
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('modulename', 'mod_livesonner');
    }

    /**
     * Returns non-localised event description with id's for admins.
     *
     * @return string
     */
    public function get_description() {
        return "O usuário com id '{$this->userid}' visualizou a atividade LiveSonner com id '{$this->objectid}'.";
    }
}
