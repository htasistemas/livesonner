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

namespace block_painelaulas\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use renderable;
use renderer_base;
use templatable;

/**
 * Renderable that exposes the dashboard template and JS configuration.
 *
 * @package   block_painelaulas
 */
class dashboard implements renderable, templatable {
    /** @var string */
    protected string $rootid;

    /** @var array<string, string> */
    protected array $strings;

    /** @var array */
    protected array $jsconfig;

    /**
     * dashboard constructor.
     */
    public function __construct() {
        global $USER;

        $this->rootid = html_writer::random_id('block-painelaulas');

        $this->strings = [
            'catalogtitle' => get_string('catalogtitle', 'block_painelaulas'),
            'catalogsubtitle' => get_string('catalogsubtitle', 'block_painelaulas'),
            'enrolledtitle' => get_string('enrolledtitle', 'block_painelaulas'),
            'enrolledsubtitle' => get_string('enrolledsubtitle', 'block_painelaulas'),
            'refresh' => get_string('refresh', 'block_painelaulas'),
            'previous' => get_string('previous', 'block_painelaulas'),
            'next' => get_string('next', 'block_painelaulas'),
            'fallbacknotice' => get_string('fallbacknotice', 'block_painelaulas'),
            'agendatitlecatalog' => get_string('agendatitlecatalog', 'block_painelaulas'),
            'agendatitleenrolled' => get_string('agendatitleenrolled', 'block_painelaulas'),
            'agendapast' => get_string('agendapast', 'block_painelaulas'),
            'agendalive' => get_string('agendalive', 'block_painelaulas'),
            'agendaunconfirmed' => get_string('agendaunconfirmed', 'block_painelaulas'),
            'startslabel' => get_string('startslabel', 'block_painelaulas'),
            'endslabel' => get_string('endslabel', 'block_painelaulas'),
            'locationlabel' => get_string('locationlabel', 'block_painelaulas'),
            'instructorlabel' => get_string('instructorlabel', 'block_painelaulas'),
            'taglabel' => get_string('taglabel', 'block_painelaulas'),
        ];

        $this->jsconfig = [
            'rootid' => $this->rootid,
            'services' => [
                'catalog' => 'block_painelaulas_get_catalog',
                'enrolled' => 'block_painelaulas_get_enrolments',
                'enrol' => 'block_painelaulas_enrol_session',
            ],
            'strings' => [
                'emptycatalog' => get_string('emptycatalog', 'block_painelaulas'),
                'emptyenrolled' => get_string('emptyenrolled', 'block_painelaulas'),
                'fallbacknotice' => $this->strings['fallbacknotice'],
                'enrolsuccess' => get_string('enrolsuccess', 'block_painelaulas'),
                'enrolfailure' => get_string('enrolfailure', 'block_painelaulas'),
                'integrationmissing' => get_string('integrationmissing', 'block_painelaulas'),
                'processing' => get_string('processing', 'block_painelaulas'),
                'countdownlabel' => get_string('countdownlabel', 'block_painelaulas'),
                'countdownlive' => get_string('countdownlive', 'block_painelaulas'),
                'countdownfinished' => get_string('countdownfinished', 'block_painelaulas'),
                'accesssession' => get_string('accesssession', 'block_painelaulas'),
                'enrolsession' => get_string('enrolsession', 'block_painelaulas'),
                'sessionclosed' => get_string('sessionclosed', 'block_painelaulas'),
                'seemore' => get_string('seemore', 'block_painelaulas'),
                'enrolledbadge' => get_string('enrolledbadge', 'block_painelaulas'),
                'confirmedbadge' => get_string('confirmedbadge', 'block_painelaulas'),
                'agendapast' => $this->strings['agendapast'],
                'agendalive' => $this->strings['agendalive'],
                'agendaunconfirmed' => $this->strings['agendaunconfirmed'],
                'toastdefault' => get_string('toastdefault', 'block_painelaulas'),
            ],
            'user' => [
                'id' => $USER->id,
            ],
            'locale' => current_language(),
            'timezone' => \core_date::get_user_timezone(),
        ];
    }

    /**
     * Export data for the dashboard.mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'rootid' => $this->rootid,
            'strings' => $this->strings,
        ];
    }

    /**
     * Returns configuration to bootstrap the AMD module.
     *
     * @return array
     */
    public function get_js_config(): array {
        return $this->jsconfig;
    }
}
