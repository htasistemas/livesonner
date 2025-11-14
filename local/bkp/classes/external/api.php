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

namespace local_aulasaovivo\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_system;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use local_aulasaovivo\local\source;

/**
 * External API for the Aulas ao vivo dashboard.
 *
 * @package   local_aulasaovivo
 */
class api extends external_api {
    /**
     * Parameters for get_catalog.
     *
     * @return external_function_parameters
     */
    public static function get_catalog_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Retrieves catalogue sessions for the current user.
     *
     * @return array
     */
    public static function get_catalog(): array {
        global $USER;

        self::validate_parameters(self::get_catalog_parameters(), []);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/aulasaovivo:view', $context);

        $result = source::get_catalog($USER->id);
        return self::format_sessions_response($result);
    }

    /**
     * Returns description of get_catalog result.
     *
     * @return external_single_structure
     */
    public static function get_catalog_returns(): external_single_structure {
        return self::sessions_response_structure();
    }

    /**
     * Parameters for get_enrolments.
     *
     * @return external_function_parameters
     */
    public static function get_enrolments_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Retrieves enrolled sessions for the current user.
     *
     * @return array
     */
    public static function get_enrolments(): array {
        global $USER;

        self::validate_parameters(self::get_enrolments_parameters(), []);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/aulasaovivo:view', $context);

        $result = source::get_enrolments($USER->id);
        return self::format_sessions_response($result);
    }

    /**
     * Returns description of get_enrolments result.
     *
     * @return external_single_structure
     */
    public static function get_enrolments_returns(): external_single_structure {
        return self::sessions_response_structure();
    }

    /**
     * Parameters for get_certificates.
     *
     * @return external_function_parameters
     */
    public static function get_certificates_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Retrieves issued certificates for the current user.
     *
     * @return array
     */
    public static function get_certificates(): array {
        global $USER;

        self::validate_parameters(self::get_certificates_parameters(), []);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/aulasaovivo:view', $context);

        $result = source::get_certificates($USER->id);

        return [
            'certificates' => array_map([self::class, 'prepare_certificate'], $result['certificates']),
            'usingfallback' => !empty($result['usingfallback']),
        ];
    }

    /**
     * Returns description of get_certificates result.
     *
     * @return external_single_structure
     */
    public static function get_certificates_returns(): external_single_structure {
        return new external_single_structure([
            'certificates' => new external_multiple_structure(self::certificate_structure(), 'Certificates list', VALUE_DEFAULT, []),
            'usingfallback' => new external_value(PARAM_BOOL, 'True when demo data is being used', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Parameters for enrol_session.
     *
     * @return external_function_parameters
     */
    public static function enrol_session_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_INT, 'Session identifier'),
        ]);
    }

    /**
     * Executes the enrolment for the current user.
     *
     * @param int $sessionid
     * @return array
     */
    public static function enrol_session(int $sessionid): array {
        global $USER;

        $params = self::validate_parameters(self::enrol_session_parameters(), ['sessionid' => $sessionid]);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/aulasaovivo:view', $context);

        $result = source::enrol_session($USER->id, $params['sessionid']);

        return [
            'status' => (bool)$result['status'],
            'message' => (string)($result['message'] ?? ''),
            'usingfallback' => !empty($result['usingfallback']),
        ];
    }

    /**
     * Returns description of enrol_session result.
     *
     * @return external_single_structure
     */
    public static function enrol_session_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Whether the enrolment completed successfully'),
            'message' => new external_value(PARAM_TEXT, 'Optional message for the user', VALUE_DEFAULT, ''),
            'usingfallback' => new external_value(PARAM_BOOL, 'True when the fallback data was used', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Helper that wraps the common sessions response.
     *
     * @param array $result
     * @return array
     */
    protected static function format_sessions_response(array $result): array {
        return [
            'sessions' => array_map([self::class, 'prepare_session'], $result['sessions']),
            'usingfallback' => !empty($result['usingfallback']),
        ];
    }

    /**
     * Returns the response structure shared by catalogue/enrolment endpoints.
     *
     * @return external_single_structure
     */
    protected static function sessions_response_structure(): external_single_structure {
        return new external_single_structure([
            'sessions' => new external_multiple_structure(self::session_structure(), 'Sessions list', VALUE_DEFAULT, []),
            'usingfallback' => new external_value(PARAM_BOOL, 'True when demo data is being used', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Shapes a session record to the external structure.
     *
     * @param array $session
     * @return array
     */
    protected static function prepare_session(array $session): array {
        return [
            'id' => (int)$session['id'],
            'name' => (string)$session['name'],
            'summary' => (string)$session['summary'],
            'starttime' => (int)$session['starttime'],
            'endtime' => (int)$session['endtime'],
            'timezone' => (string)($session['timezone'] ?? ''),
            'duration' => (int)($session['duration'] ?? 0),
            'launchurl' => (string)($session['launchurl'] ?? ''),
            'recordingurl' => (string)($session['recordingurl'] ?? ''),
            'imageurl' => (string)($session['imageurl'] ?? ''),
            'location' => (string)($session['location'] ?? ''),
            'tags' => array_values(array_map('strval', $session['tags'] ?? [])),
            'instructor' => [
                'name' => (string)($session['instructor']['name'] ?? ''),
                'avatar' => (string)($session['instructor']['avatar'] ?? ''),
            ],
            'isenrolled' => !empty($session['isenrolled']),
            'status' => (string)($session['status'] ?? ''),
        ];
    }

    /**
     * Shapes a certificate record to the external structure.
     *
     * @param array $certificate
     * @return array
     */
    protected static function prepare_certificate(array $certificate): array {
        return [
            'id' => (int)($certificate['id'] ?? 0),
            'sessionid' => (int)($certificate['sessionid'] ?? 0),
            'sessionname' => (string)($certificate['sessionname'] ?? ''),
            'coursename' => (string)($certificate['coursename'] ?? ''),
            'issuedate' => (int)($certificate['issuedate'] ?? 0),
            'issuedatestring' => (string)($certificate['issuedatestring'] ?? ''),
            'fileurl' => (string)($certificate['fileurl'] ?? ''),
            'filename' => (string)($certificate['filename'] ?? ''),
            'previewurl' => (string)($certificate['previewurl'] ?? ''),
        ];
    }

    /**
     * Session description structure.
     *
     * @return external_single_structure
     */
    protected static function session_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Session identifier'),
            'name' => new external_value(PARAM_TEXT, 'Session title'),
            'summary' => new external_value(PARAM_RAW, 'Summary/description', VALUE_DEFAULT, ''),
            'starttime' => new external_value(PARAM_INT, 'Start time (unix timestamp)'),
            'endtime' => new external_value(PARAM_INT, 'End time (unix timestamp)', VALUE_DEFAULT, 0),
            'timezone' => new external_value(PARAM_RAW_TRIMMED, 'Timezone identifier', VALUE_DEFAULT, ''),
            'duration' => new external_value(PARAM_INT, 'Duration in seconds', VALUE_DEFAULT, 0),
            'launchurl' => new external_value(PARAM_RAW_TRIMMED, 'URL to launch the live session', VALUE_DEFAULT, ''),
            'recordingurl' => new external_value(PARAM_RAW_TRIMMED, 'URL to the recording when available', VALUE_DEFAULT, ''),
            'imageurl' => new external_value(PARAM_RAW, 'Image URL for the card banner', VALUE_DEFAULT, ''),
            'location' => new external_value(PARAM_TEXT, 'Where the class happens', VALUE_DEFAULT, ''),
            'tags' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Tag label'),
                'Session tags / tracks',
                VALUE_DEFAULT,
                []
            ),
            'instructor' => new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Instructor full name', VALUE_DEFAULT, ''),
                'avatar' => new external_value(PARAM_RAW_TRIMMED, 'Avatar image URL', VALUE_DEFAULT, ''),
            ], 'Instructor information'),
            'isenrolled' => new external_value(PARAM_BOOL, 'Whether the current user is enrolled'),
            'status' => new external_value(PARAM_TEXT, 'Optional status provided by the source', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Certificate description structure.
     *
     * @return external_single_structure
     */
    protected static function certificate_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Certificate identifier'),
            'sessionid' => new external_value(PARAM_INT, 'Related session identifier', VALUE_DEFAULT, 0),
            'sessionname' => new external_value(PARAM_TEXT, 'Session name', VALUE_DEFAULT, ''),
            'coursename' => new external_value(PARAM_TEXT, 'Course name', VALUE_DEFAULT, ''),
            'issuedate' => new external_value(PARAM_INT, 'Issuance timestamp', VALUE_DEFAULT, 0),
            'issuedatestring' => new external_value(PARAM_TEXT, 'Formatted issuance date', VALUE_DEFAULT, ''),
            'fileurl' => new external_value(PARAM_URL, 'Download URL', VALUE_DEFAULT, ''),
            'filename' => new external_value(PARAM_FILE, 'Certificate filename', VALUE_DEFAULT, ''),
            'previewurl' => new external_value(PARAM_URL, 'Certificate preview URL', VALUE_DEFAULT, ''),
        ]);
    }
}
