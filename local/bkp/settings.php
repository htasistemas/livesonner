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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('localsettingaulasaovivo', get_string('pluginname', 'local_aulasaovivo'));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext(
            'local_aulasaovivo/providercomponent',
            get_string('settings_providercomponent', 'local_aulasaovivo'),
            get_string('settings_providercomponent_desc', 'local_aulasaovivo'),
            'mod_livesonner',
            PARAM_COMPONENT
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_aulasaovivo/enablefallback',
            get_string('settings_enablefallback', 'local_aulasaovivo'),
            get_string('settings_enablefallback_desc', 'local_aulasaovivo'),
            1
        ));
    }
}
