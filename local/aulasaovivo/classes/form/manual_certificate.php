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

namespace local_aulasaovivo\form;

defined('MOODLE_INTERNAL') || die();

use context_course;
use context_module;
use context_system;
use core_form\dynamic_form;
use mod_livesonner\local\certificate_manager;
use moodle_exception;
use moodle_url;
use required_capability_exception;
use stored_file;

/**
* Dynamic form that allows administrators to issue manual certificates.
*
* @package   local_aulasaovivo
*/
class manual_certificate extends dynamic_form {
/**
* Form definition.
*/
protected function definition(): void {
$mform = $this->_form;

$mform->addElement('autocomplete', 'sessionid', get_string('manualcertificate:session', 'local_aulasaovivo'),
$this->get_session_options(), [
'placeholder' => get_string('manualcertificate:sessionplaceholder', 'local_aulasaovivo'),
'noselectionstring' => get_string('manualcertificate:sessionplaceholder', 'local_aulasaovivo'),
]);
$mform->setType('sessionid', PARAM_INT);
$mform->addRule('sessionid', get_string('required'), 'required', null, 'client');

$mform->addElement('autocomplete', 'userid', get_string('manualcertificate:user', 'local_aulasaovivo'), [], [
'ajax' => 'core_user/form_user_selector',
@@ -55,50 +56,59 @@ class manual_certificate extends dynamic_form {
$mform->setType('userid', PARAM_INT);
$mform->addRule('userid', get_string('required'), 'required', null, 'client');

$mform->addElement('text', 'name', get_string('manualcertificate:name', 'local_aulasaovivo'));
$mform->setType('name', PARAM_TEXT);

$mform->addElement('filepicker', 'certificate', get_string('manualcertificate:file', 'local_aulasaovivo'), null, [
'accepted_types' => ['.png'],
'maxbytes' => 0,
'subdirs' => 0,
]);
$mform->addRule('certificate', get_string('required'), 'required', null, 'client');

$this->add_action_buttons(true, get_string('manualcertificate:submit', 'local_aulasaovivo'));
}

/**
* Returns the form submission context.
*
* @return context_system
*/
protected function get_context_for_dynamic_submission(): context_system {
return context_system::instance();
}

/**
* Returns the page URL associated with the form submissions.
*
* @return moodle_url
*/
protected function get_page_url_for_dynamic_submission(): moodle_url {
return new moodle_url('/local/aulasaovivo/index.php');
}

/**
* Validates access prior to processing the form.
*/
protected function check_access_for_dynamic_submission(): void {
$context = $this->get_context_for_dynamic_submission();
if (!has_capability('moodle/site:config', $context)) {
throw new required_capability_exception($context, 'moodle/site:config', 'nopermissions', 'manualcertificate');
}
}

/**
* Loads the form data when opened.
*/
protected function set_data_for_dynamic_submission(): void {
// Nothing to preload.
}

/**
* Processes the submitted form data.
*
* @return array<string, string>
*/
public function process_dynamic_submission(): array {
global $DB;
