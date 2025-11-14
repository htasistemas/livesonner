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
require_capability('local/aulasaovivo:view', $context);

$pagetitle = get_string('pagetitle', 'local_aulasaovivo');

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aulasaovivo/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$dashboard = new \local_aulasaovivo\output\dashboard();

$PAGE->requires->css(new moodle_url('/local/aulasaovivo/styles.css'));
$PAGE->requires->js_call_amd('local_aulasaovivo/dashboard', 'init', [$dashboard->get_rootid()]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_aulasaovivo/dashboard', $dashboard->export_for_template($OUTPUT));
echo $OUTPUT->footer();
