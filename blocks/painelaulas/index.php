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

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('block/painelaulas:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/painelaulas/index.php'));
$PAGE->set_title(get_string('pageheading', 'block_painelaulas'));
$PAGE->set_heading(get_string('pageheading', 'block_painelaulas'));
$PAGE->set_pagelayout('mydashboard');

$output = $PAGE->get_renderer('core');
$dashboard = new \block_painelaulas\output\dashboard();

$PAGE->requires->css(new moodle_url('/blocks/painelaulas/styles.css'));
$PAGE->requires->js_call_amd('block_painelaulas/dashboard', 'init', [$dashboard->get_js_config()]);

echo $output->header();
echo $output->render_from_template('block_painelaulas/dashboard', $dashboard->export_for_template($output));
echo $output->footer();
