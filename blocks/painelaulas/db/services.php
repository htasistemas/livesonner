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
    'block_painelaulas_get_catalog' => [
        'classname'   => 'block_painelaulas\external\api',
        'methodname'  => 'get_catalog',
        'classpath'   => '',
        'description' => 'Retorna a lista de aulas disponíveis para o usuário logado.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'block/painelaulas:view',
    ],
    'block_painelaulas_get_enrolments' => [
        'classname'   => 'block_painelaulas\external\api',
        'methodname'  => 'get_enrolments',
        'classpath'   => '',
        'description' => 'Retorna a lista de aulas nas quais o usuário está inscrito.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'block/painelaulas:view',
    ],
    'block_painelaulas_enrol_session' => [
        'classname'   => 'block_painelaulas\external\api',
        'methodname'  => 'enrol_session',
        'classpath'   => '',
        'description' => 'Realiza a inscrição do usuário na aula informada.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'block/painelaulas:view',
    ],
];
