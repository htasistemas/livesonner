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

use context_system;
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

    /** @var bool */
    protected bool $canmanualcertificates;

    /**
     * dashboard constructor.
     */
    public function __construct() {
        global $USER;

        $this->rootid = html_writer::random_id('local-aulasaovivo');

        $systemcontext = context_system::instance();
        $this->canmanualcertificates = has_capability('moodle/site:config', $systemcontext);

        $this->strings = $this->resolve_strings([
            'catalogtitle',
            'catalogsubtitle',
            'enrolledtitle',
            'enrolledsubtitle',
            'certificatestitle',
            'certificatessubtitle',
            'tabcatalog',
            'tabenrolled',
            'tabcertificates',
            'tablistlabel',
            'refresh',
            'previous',
            'next',
            'fallbacknotice',
            'agendatitlecatalog',
            'agendatitleenrolled',
            'agendapast',
            'agendalive',
            'agendaunconfirmed',
            'startslabel',
            'endslabel',
            'locationlabel',
            'instructorlabel',
            'taglabel',
            'manualcertificatebutton',
        ]);

        $jsstrings = $this->resolve_strings([
            'emptycatalog',
            'emptyenrolled',
            'emptycertificates',
            'enrolsuccess',
            'enrolfailure',
            'integrationmissing',
            'processing',
            'countdownlabel',
            'countdownlive',
            'countdownfinished',
            'accesssession',
            'enrolsession',
            'sessionclosed',
            'seemore',
            'enrolledbadge',
            'confirmedbadge',
            'toastdefault',
            'certificateissuedon',
            'certificatedownload',
            'manualcertificatetitle',
            'manualcertificatesuccess',
            'manualcertificateerror',
        ]);

        $jsstrings['fallbacknotice'] = $this->strings['fallbacknotice'];
        $jsstrings['startslabel'] = $this->strings['startslabel'];
        $jsstrings['locationlabel'] = $this->strings['locationlabel'];
        $jsstrings['instructorlabel'] = $this->strings['instructorlabel'];
        $jsstrings['agendapast'] = $this->strings['agendapast'];
        $jsstrings['agendalive'] = $this->strings['agendalive'];
        $jsstrings['agendaunconfirmed'] = $this->strings['agendaunconfirmed'];

        $this->jsconfig = [
            'rootid' => $this->rootid,
            'services' => [
                'catalog' => 'local_aulasaovivo_get_catalog',
                'enrolled' => 'local_aulasaovivo_get_enrolments',
                'enrol' => 'local_aulasaovivo_enrol_session',
                'certificates' => 'local_aulasaovivo_get_certificates',
            ],
            'strings' => $jsstrings,
            'user' => [
                'id' => $USER->id,
            ],
            'locale' => str_replace('_', '-', current_language()),
            'timezone' => \core_date::get_user_timezone(),
            'canmanualcertificates' => $this->canmanualcertificates,
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
            'canmanualcertificates' => $this->canmanualcertificates,
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

    /**
     * Returns an associative array of resolved language strings.
     *
     * @param array $identifiers
     * @return array<string, string>
     */
    protected function resolve_strings(array $identifiers): array {
        $strings = [];
        foreach ($identifiers as $key => $identifier) {
            if (is_int($key)) {
                $key = $identifier;
            }
            $strings[$key] = $this->resolve_string($identifier);
        }

        return $strings;
    }

    /**
     * Safely retrieves a language string, falling back to English when needed.
     *
     * @param string $identifier
     * @return string
     */
    protected function resolve_string(string $identifier): string {
        $value = get_string($identifier, 'local_aulasaovivo');
        if ($this->is_missing_string($value)) {
            $value = get_string($identifier, 'local_aulasaovivo', null, 'en');
        }

        if ($this->is_missing_string($value)) {
            return $identifier;
        }

        return $value;
    }

    /**
     * Detects missing string placeholders.
     *
     * @param string $value
     * @return bool
     */
    protected function is_missing_string(string $value): bool {
        return preg_match('/^\[\[[^\]]+\]\]$/', $value) === 1;
    }
}
