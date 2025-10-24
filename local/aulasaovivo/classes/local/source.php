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

namespace local_aulasaovivo\local;

defined('MOODLE_INTERNAL') || die();

use moodle_exception;
use moodle_url;

/**
 * Data provider that bridges the dashboard with the mod plugin responsible for the classes.
 *
 * @package   local_aulasaovivo
 */
class source {
    /** @var array<int, array<int, bool>> fallback enrolments indexed by user/session. */
    protected static array $fallbackenrolments = [];

    /**
     * Returns the list of available sessions for the given user.
     *
     * @param int $userid
     * @return array{sessions: array<int, array>, usingfallback: bool}
     */
    public static function get_catalog(int $userid): array {
        [$sessions, $usingfallback] = self::call_provider('painelaulas_get_catalog', [$userid],
            self::get_fallback_sessions_with_state($userid));

        $sessions = array_map([self::class, 'normalise_session'], $sessions);

        return [
            'sessions' => array_values($sessions),
            'usingfallback' => $usingfallback,
        ];
    }

    /**
     * Returns the list of sessions in which the user is already enrolled.
     *
     * @param int $userid
     * @return array{sessions: array<int, array>, usingfallback: bool}
     */
    public static function get_enrolments(int $userid): array {
        [$sessions, $usingfallback] = self::call_provider('painelaulas_get_enrolments', [$userid],
            self::get_fallback_enrolments($userid));

        $sessions = array_map([self::class, 'normalise_session'], $sessions);

        return [
            'sessions' => array_values($sessions),
            'usingfallback' => $usingfallback,
        ];
    }

    /**
     * Enrols the user in the requested session.
     *
     * @param int $userid
     * @param int $sessionid
     * @return array{status: bool, message: string, usingfallback: bool}
     */
    public static function enrol_session(int $userid, int $sessionid): array {
        $component = self::get_provider_component();

        if ($component && component_callback_exists($component, 'painelaulas_enrol_session')) {
            $result = component_callback($component, 'painelaulas_enrol_session', [$userid, $sessionid], null);

            if (is_array($result)) {
                return [
                    'status' => !empty($result['status']),
                    'message' => (string)($result['message'] ?? ''),
                    'usingfallback' => !empty($result['usingfallback']),
                ];
            }

            if (!is_null($result)) {
                return [
                    'status' => (bool)$result,
                    'message' => '',
                    'usingfallback' => false,
                ];
            }
        }

        if (!self::is_fallback_enabled()) {
            throw new moodle_exception('integrationmissing', 'local_aulasaovivo');
        }

        $session = self::find_fallback_session($sessionid);
        if (!$session) {
            throw new moodle_exception('error:sessionnotfound', 'local_aulasaovivo');
        }

        self::$fallbackenrolments[$userid][$sessionid] = true;

        return [
            'status' => true,
            'message' => get_string('enrolsuccess', 'local_aulasaovivo'),
            'usingfallback' => true,
        ];
    }

    /**
     * Attempts to call the configured provider component. Falls back to local mock data when allowed.
     *
     * @param string $callback
     * @param array $params
     * @param array $fallback
     * @return array{0: array, 1: bool}
     */
    protected static function call_provider(string $callback, array $params, array $fallback): array {
        $component = self::get_provider_component();

        if ($component && component_callback_exists($component, $callback)) {
            $data = component_callback($component, $callback, $params, null);
            if (is_array($data)) {
                return [$data, false];
            }
        }

        if (!self::is_fallback_enabled()) {
            throw new moodle_exception('error:missingprovider', 'local_aulasaovivo');
        }

        return [$fallback, true];
    }

    /**
     * Normalises the session payload to the structure expected by the dashboard.
     *
     * @param array $session
     * @return array<string, mixed>
     */
    protected static function normalise_session(array $session): array {
        $starttime = self::coerce_time($session, ['starttime', 'start_time', 'startTime']);
        $endtime = self::coerce_time($session, ['endtime', 'end_time', 'endTime']);
        $duration = isset($session['duration']) ? (int)$session['duration'] : ($endtime && $starttime ? max(0, $endtime - $starttime) : 0);

        $imageurl = (string)($session['imageurl'] ?? $session['banner'] ?? $session['bannerurl'] ?? '');
        if (!$imageurl) {
            $imageurl = (new moodle_url('/local/aulasaovivo/pix/banner-placeholder.svg'))->out(false);
        }

        $instructor = $session['instructor'] ?? [];
        if (is_string($instructor)) {
            $instructor = ['name' => $instructor];
        } else if (is_array($instructor)) {
            if (!isset($instructor['name']) && isset($instructor['fullname'])) {
                $instructor['name'] = $instructor['fullname'];
            }
        } else {
            $instructor = [];
        }

        $tags = [];
        if (!empty($session['tags']) && is_array($session['tags'])) {
            $tags = array_values(array_map('strval', $session['tags']));
        } else if (!empty($session['track'])) {
            $tags = [(string)$session['track']];
        }

        $registrationtime = isset($session['registrationtime']) ? (int)$session['registrationtime'] : 0;

        $isenrolled = $registrationtime > 0;
        if (!$isenrolled) {
            $isenrolled = !empty($session['isenrolled'])
                || !empty($session['enrolled'])
                || !empty($session['is_enrolled']);
        }

        return [
            'id' => (int)($session['id'] ?? 0),
            'name' => format_string((string)($session['name'] ?? $session['title'] ?? '')),
            'summary' => format_text((string)($session['summary'] ?? $session['description'] ?? ''), FORMAT_HTML, ['filter' => false]),
            'starttime' => $starttime,
            'endtime' => $endtime,
            'timezone' => (string)($session['timezone'] ?? ''),
            'duration' => $duration,
            'launchurl' => (string)($session['launchurl'] ?? $session['launch_url'] ?? $session['joinurl'] ?? ''),
            'recordingurl' => (string)($session['recordingurl'] ?? $session['recording'] ?? ''),
            'imageurl' => $imageurl,
            'location' => format_string((string)($session['location'] ?? $session['room'] ?? '')),
            'tags' => $tags,
            'instructor' => [
                'name' => format_string((string)($instructor['name'] ?? '')),
                'avatar' => (string)($instructor['avatar'] ?? $instructor['image'] ?? ''),
            ],
            'isenrolled' => $isenrolled,
            'registrationtime' => $registrationtime,
            'status' => (string)($session['status'] ?? ''),
        ];
    }

    /**
     * Converts different field names into a unix timestamp.
     *
     * @param array $session
     * @param array $keys
     * @return int
     */
    protected static function coerce_time(array $session, array $keys): int {
        foreach ($keys as $key) {
            if (!empty($session[$key])) {
                if (is_numeric($session[$key])) {
                    return (int)$session[$key];
                }
                $timestamp = strtotime((string)$session[$key]);
                if ($timestamp) {
                    return $timestamp;
                }
            }
        }
        return 0;
    }

    /**
     * Returns fallback sessions including enrolment state for the current request.
     *
     * @param int $userid
     * @return array<int, array>
     */
    protected static function get_fallback_sessions_with_state(int $userid): array {
        $sessions = self::get_fallback_sessions();
        foreach ($sessions as &$session) {
            if (!empty(self::$fallbackenrolments[$userid][$session['id']])) {
                $session['isenrolled'] = true;
            }
        }
        return $sessions;
    }

    /**
     * Returns fallback enrolments for the given user.
     *
     * @param int $userid
     * @return array<int, array>
     */
    protected static function get_fallback_enrolments(int $userid): array {
        $sessions = self::get_fallback_sessions_with_state($userid);
        return array_values(array_filter($sessions, static function(array $session): bool {
            return !empty($session['isenrolled']);
        }));
    }

    /**
     * Locates a fallback session by id.
     *
     * @param int $sessionid
     * @return array|null
     */
    protected static function find_fallback_session(int $sessionid): ?array {
        foreach (self::get_fallback_sessions() as $session) {
            if ((int)$session['id'] === $sessionid) {
                return $session;
            }
        }
        return null;
    }

    /**
     * Returns the configured provider component.
     *
     * @return string
     */
    protected static function get_provider_component(): string {
        $component = (string)get_config('local_aulasaovivo', 'providercomponent');
        return $component ?: 'mod_livesonner';
    }

    /**
     * Checks whether the fallback catalogue can be used.
     *
     * @return bool
     */
    protected static function is_fallback_enabled(): bool {
        return (bool)get_config('local_aulasaovivo', 'enablefallback');
    }

    /**
     * Generates a static catalogue used when the integration is not yet configured.
     *
     * @return array<int, array>
     */
    protected static function get_fallback_sessions(): array {
        $now = time();
        $day = 86400;
        $image = (new moodle_url('/local/aulasaovivo/pix/banner-placeholder.svg'))->out(false);

        return [
            [
                'id' => 101,
                'name' => 'Aula 01: Introdução a Algoritmos',
                'summary' => 'Conheça os blocos fundamentais que sustentam qualquer algoritmo e como estruturá-los.',
                'starttime' => strtotime('next tuesday 19:00'),
                'endtime' => strtotime('next tuesday 20:30'),
                'location' => 'Sala virtual 1',
                'instructor' => ['name' => 'Ana Souza'],
                'tags' => ['Fundamentos'],
                'imageurl' => $image,
                'isenrolled' => false,
                'launchurl' => '',
            ],
            [
                'id' => 102,
                'name' => 'Aula 02: Estruturas de Repetição',
                'summary' => 'Desenvolva técnicas para dominar laços condicionais e contadores com exemplos práticos.',
                'starttime' => strtotime('next thursday 18:30'),
                'endtime' => strtotime('next thursday 20:00'),
                'location' => 'Sala virtual 2',
                'instructor' => ['name' => 'Carlos Lima'],
                'tags' => ['Fundamentos'],
                'imageurl' => $image,
                'isenrolled' => true,
                'launchurl' => '#',
            ],
            [
                'id' => 103,
                'name' => 'Aula 03: Estruturas de Dados',
                'summary' => 'Aprenda como listas, filas e pilhas resolvem problemas reais e otimizam algoritmos.',
                'starttime' => $now - ($day * 2),
                'endtime' => $now - ($day * 2) + 5400,
                'location' => 'Sala virtual 3',
                'instructor' => ['name' => 'Helena Prado'],
                'tags' => ['Prática'],
                'imageurl' => $image,
                'isenrolled' => false,
                'launchurl' => '#',
            ],
        ];
    }
}
