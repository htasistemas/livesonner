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

use block_painelaulas\output\dashboard;

/**
 * Block wrapper for the live classes dashboard.
 *
 * @package   block_painelaulas
 */
class block_painelaulas extends block_base {

    /**
     * Initialise the block.
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_painelaulas');
    }

    /**
     * Indicates that the block provides site-wide configuration settings.
     *
     * @return bool
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Define the pages where the block can be added.
     *
     * @return array
     */
    public function applicable_formats(): array {
        return [
            'site' => true,
            'my' => true,
        ];
    }

    /**
     * Ensure multiple instances are not added.
     *
     * @return bool
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Prepare the content rendered inside the block.
     *
     * @return stdClass
     */
    public function get_content(): stdClass {
        global $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $context = \context_system::instance();
        if (!has_capability('block/painelaulas:view', $context)) {
            return $this->content;
        }

        $dashboard = new dashboard();

        $PAGE->requires->css(new \moodle_url('/blocks/painelaulas/styles.css'));
        $PAGE->requires->js_call_amd('block_painelaulas/dashboard', 'init', [$dashboard->get_js_config()]);

        $this->content->text = $OUTPUT->render_from_template(
            'block_painelaulas/dashboard',
            $dashboard->export_for_template($OUTPUT)
        );

        return $this->content;
    }
}
