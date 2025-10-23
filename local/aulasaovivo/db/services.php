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

$functions = [
    'local_aulasaovivo_get_catalog' => [
        'classname'   => 'local_aulasaovivo\\external\\api',
        'methodname'  => 'get_catalog',
        'classpath'   => '',
        'description' => 'Returns the catalogue of upcoming live classes for the current user.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/aulasaovivo:view',
    ],
    'local_aulasaovivo_get_enrolments' => [
        'classname'   => 'local_aulasaovivo\\external\\api',
        'methodname'  => 'get_enrolments',
        'classpath'   => '',
        'description' => 'Returns the live classes in which the current user is already enrolled.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/aulasaovivo:view',
    ],
    'local_aulasaovivo_enrol_session' => [
        'classname'   => 'local_aulasaovivo\\external\\api',
        'methodname'  => 'enrol_session',
        'classpath'   => '',
        'description' => 'Enrols the current user in the selected live class.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/aulasaovivo:view',
    ],
];
