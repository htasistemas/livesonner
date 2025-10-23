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

/**
 * Adds the Aulas ao vivo tab to the primary navigation.
 *
 * @param primary_navigation $navigation
 */
function local_aulasaovivo_extend_primary_navigation($navigation): void {
    global $PAGE;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $context = context_system::instance();
    if (!has_capability('local/aulasaovivo:view', $context)) {
        return;
    }

    $url = new moodle_url('/local/aulasaovivo/index.php');
    $label = get_string('pluginname', 'local_aulasaovivo');

    if ($navigation->find('local_aulasaovivo', navigation_node::TYPE_CUSTOM)) {
        return;
    }

    $node = navigation_node::create(
        $label,
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_aulasaovivo',
        new pix_icon('i/calendar', $label)
    );

    if ($PAGE->url && $PAGE->url->compare($url, URL_MATCH_BASE)) {
        $node->forceopen = true;
        $node->isactive = true;
    }

    $navigation->add_node($node);
}
