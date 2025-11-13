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

namespace local_aulasaovivo\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use renderable;
use renderer_base;
use templatable;

/**
 * Renderable that exposes the dashboard template and JS configuration.
 *
 * @package   local_aulasaovivo
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

        $this->rootid = html_writer::random_id('local-aulasaovivo');

        $this->strings = [
            'catalogtitle' => get_string('catalogtitle', 'local_aulasaovivo'),
            'catalogsubtitle' => get_string('catalogsubtitle', 'local_aulasaovivo'),
            'enrolledtitle' => get_string('enrolledtitle', 'local_aulasaovivo'),
            'enrolledsubtitle' => get_string('enrolledsubtitle', 'local_aulasaovivo'),
            'certificatestitle' => get_string('certificatestitle', 'local_aulasaovivo'),
            'certificatessubtitle' => get_string('certificatessubtitle', 'local_aulasaovivo'),
            'tabcatalog' => get_string('tabcatalog', 'local_aulasaovivo'),
            'tabenrolled' => get_string('tabenrolled', 'local_aulasaovivo'),
            'tabcertificates' => get_string('tabcertificates', 'local_aulasaovivo'),
            'tablistlabel' => get_string('tablistlabel', 'local_aulasaovivo'),
            'refresh' => get_string('refresh', 'local_aulasaovivo'),
            'previous' => get_string('previous', 'local_aulasaovivo'),
            'next' => get_string('next', 'local_aulasaovivo'),
            'fallbacknotice' => get_string('fallbacknotice', 'local_aulasaovivo'),
            'agendatitlecatalog' => get_string('agendatitlecatalog', 'local_aulasaovivo'),
            'agendatitleenrolled' => get_string('agendatitleenrolled', 'local_aulasaovivo'),
            'agendapast' => get_string('agendapast', 'local_aulasaovivo'),
            'agendalive' => get_string('agendalive', 'local_aulasaovivo'),
            'agendaunconfirmed' => get_string('agendaunconfirmed', 'local_aulasaovivo'),
            'startslabel' => get_string('startslabel', 'local_aulasaovivo'),
            'endslabel' => get_string('endslabel', 'local_aulasaovivo'),
            'locationlabel' => get_string('locationlabel', 'local_aulasaovivo'),
            'instructorlabel' => get_string('instructorlabel', 'local_aulasaovivo'),
            'taglabel' => get_string('taglabel', 'local_aulasaovivo'),
        ];

        $this->jsconfig = [
            'rootid' => $this->rootid,
            'services' => [
                'catalog' => 'local_aulasaovivo_get_catalog',
                'enrolled' => 'local_aulasaovivo_get_enrolments',
                'enrol' => 'local_aulasaovivo_enrol_session',
                'certificates' => 'local_aulasaovivo_get_certificates',
            ],
            'strings' => [
                'emptycatalog' => get_string('emptycatalog', 'local_aulasaovivo'),
                'emptyenrolled' => get_string('emptyenrolled', 'local_aulasaovivo'),
                'emptycertificates' => get_string('emptycertificates', 'local_aulasaovivo'),
                'fallbacknotice' => $this->strings['fallbacknotice'],
                'enrolsuccess' => get_string('enrolsuccess', 'local_aulasaovivo'),
                'enrolfailure' => get_string('enrolfailure', 'local_aulasaovivo'),
                'integrationmissing' => get_string('integrationmissing', 'local_aulasaovivo'),
                'processing' => get_string('processing', 'local_aulasaovivo'),
                'countdownlabel' => get_string('countdownlabel', 'local_aulasaovivo'),
                'countdownlive' => get_string('countdownlive', 'local_aulasaovivo'),
                'countdownfinished' => get_string('countdownfinished', 'local_aulasaovivo'),
                'accesssession' => get_string('accesssession', 'local_aulasaovivo'),
                'enrolsession' => get_string('enrolsession', 'local_aulasaovivo'),
                'sessionclosed' => get_string('sessionclosed', 'local_aulasaovivo'),
                'seemore' => get_string('seemore', 'local_aulasaovivo'),
                'enrolledbadge' => get_string('enrolledbadge', 'local_aulasaovivo'),
                'confirmedbadge' => get_string('confirmedbadge', 'local_aulasaovivo'),
                'startslabel' => $this->strings['startslabel'],
                'locationlabel' => $this->strings['locationlabel'],
                'instructorlabel' => $this->strings['instructorlabel'],
                'agendapast' => $this->strings['agendapast'],
                'agendalive' => $this->strings['agendalive'],
                'agendaunconfirmed' => $this->strings['agendaunconfirmed'],
                'toastdefault' => get_string('toastdefault', 'local_aulasaovivo'),
                'certificateissuedon' => get_string('certificateissuedon', 'local_aulasaovivo'),
                'certificatedownload' => get_string('certificatedownload', 'local_aulasaovivo'),
            ],
            'user' => [
                'id' => $USER->id,
            ],
            'locale' => str_replace('_', '-', current_language()),
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
            'configjson' => $this->get_config_json(),
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

    /**
     * Returns the DOM configuration payload encoded as JSON.
     *
     * @return string
     */
    protected function get_config_json(): string {
        return json_encode($this->jsconfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    /**
     * Returns the root element identifier.
     *
     * @return string
     */
    public function get_rootid(): string {
        return $this->rootid;
    }
}
