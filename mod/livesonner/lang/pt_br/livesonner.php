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

/**
 * Language strings for LiveSonner module
 *
 * @package    mod_livesonner
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LiveSonner';
$string['modulename'] = 'LiveSonner';
$string['modulenameplural'] = 'Aulas ao vivo LiveSonner';
$string['pluginadministration'] = 'Administração do LiveSonner';
$string['modulename_help'] = 'Crie aulas ao vivo integradas ao Google Meet com registro de presença e disponibilização da gravação.';
$string['name'] = 'Título da aula';
$string['timestart'] = 'Data e hora de início';
$string['duration'] = 'Duração estimada (minutos)';
$string['meeturl'] = 'Link do Google Meet';
$string['teacher'] = 'Professor responsável pela aula';
$string['chooseateacher'] = 'Selecione o professor';
$string['recordingurl'] = 'Gravação da aula (link do YouTube)';
$string['recordingurl_help'] = 'Após finalizar a aula, cole o link do YouTube para disponibilizar a gravação aos alunos.';
$string['positivevalue'] = 'Informe um valor inteiro positivo.';
$string['settingsdescription'] = 'Configure a atividade LiveSonner para transmitir aulas ao vivo.';
$string['eventjoin'] = 'Participação na aula ao vivo';
$string['finalizeclass'] = 'Finalizar aula';
$string['livesonner:addinstance'] = 'Adicionar uma nova atividade LiveSonner ao curso';
$string['livesonner:manage'] = 'Gerenciar a aula ao vivo LiveSonner';
$string['livesonner:view'] = 'Visualizar a aula ao vivo LiveSonner';
$string['attendancealreadyrecorded'] = 'Sua presença já foi registrada.';
$string['classnotstarted'] = 'A aula ainda não começou.';
$string['classfinished'] = 'Aula concluída';
$string['joinclass'] = 'Participar da aula';
$string['countdownmessage'] = 'A aula começará em {$a}';
$string['durationlabel'] = '{$a} minutos';
$string['starttimelabel'] = 'Início: {$a}';
$string['videosectiontitle'] = 'Assista à gravação da aula';
$string['novideoavailable'] = 'Nenhuma gravação foi compartilhada ainda.';
$string['attendanceintro'] = 'Clique em participar para registrar sua presença e acessar a aula ao vivo.';
$string['attendanceuser'] = 'Participante';
$string['attendancecount'] = 'Total de presenças registradas: {$a}';
$string['attendanceempty'] = 'Nenhuma presença foi registrada até o momento.';
$string['timeclicked'] = 'Registrado em';
$string['registrationsheading'] = 'Inscrições confirmadas';
$string['registrationscount'] = 'Total de inscrições: {$a}';
$string['registrationsempty'] = 'Nenhuma inscrição foi registrada ainda.';
$string['registrationuser'] = 'Inscrito';
$string['registrationtime'] = 'Inscrição em';
$string['summarycoursemodule'] = '{$a->date} · {$a->duration} minutos';
$string['attendanceheading'] = 'Presenças registradas';
$string['completionrequirement'] = 'A atividade será marcada como concluída apenas quando a aula for finalizada pelo professor.';
$string['finishsuccess'] = 'A aula foi finalizada e a conclusão liberada.';
$string['finishsuccesswithcertificates'] = 'A aula foi finalizada e {$a} certificado(s) foi/foram emitido(s).';
$string['finishsuccessnocertificates'] = 'A aula foi finalizada, mas nenhum certificado pôde ser emitido.';
$string['finishalready'] = 'A aula já havia sido finalizada.';
$string['filearea_certificates'] = 'Certificados emitidos';
$string['error:certificatetemplate'] = 'Não foi possível carregar o modelo de certificado. Entre em contato com o administrador do site.';
$string['privacy:metadata:livesonner'] = 'Armazena detalhes da aula ao vivo.';
$string['privacy:metadata:livesonner:course'] = 'Curso ao qual a aula pertence.';
$string['privacy:metadata:livesonner:name'] = 'Nome da aula.';
$string['privacy:metadata:livesonner:timestart'] = 'Horário de início da aula.';
$string['privacy:metadata:livesonner:duration'] = 'Duração prevista da aula.';
$string['privacy:metadata:livesonner:meeturl'] = 'Link da reunião.';
$string['privacy:metadata:livesonner:teacherid'] = 'Professor responsável pela aula.';
$string['privacy:metadata:livesonner:recordingurl'] = 'Link do YouTube com a gravação da aula.';
$string['privacy:metadata:livesonner_attendance'] = 'Armazena registros de participação ao vivo.';
$string['privacy:metadata:livesonner_attendance:livesonnerid'] = 'Aula ao vivo acessada.';
$string['privacy:metadata:livesonner_attendance:userid'] = 'Usuário participante.';
$string['privacy:metadata:livesonner_attendance:timeclicked'] = 'Momento do clique para participar.';
$string['privacy:metadata:livesonner_enrolments'] = 'Armazena inscrições realizadas pelo painel de aulas ao vivo.';
$string['privacy:metadata:livesonner_enrolments:livesonnerid'] = 'Aula ao vivo inscrita.';
$string['privacy:metadata:livesonner_enrolments:userid'] = 'Usuário inscrito na aula.';
$string['privacy:metadata:livesonner_enrolments:timecreated'] = 'Momento em que a inscrição foi registrada.';
$string['privacy:metadata:livesonner_certificates'] = 'Armazena os certificados emitidos para aulas ao vivo concluídas.';
$string['privacy:metadata:livesonner_certificates:livesonnerid'] = 'Aula ao vivo para a qual o certificado foi emitido.';
$string['privacy:metadata:livesonner_certificates:userid'] = 'Usuário que recebeu o certificado.';
$string['privacy:metadata:livesonner_certificates:filename'] = 'Nome do arquivo de certificado gerado.';
$string['privacy:metadata:livesonner_certificates:timecreated'] = 'Momento da emissão do certificado.';
$string['privacy:metadata:livesonner_certificates:timemodified'] = 'Última atualização do registro do certificado.';
$string['privacy:metadata:reason'] = 'Esses dados são necessários para controlar presença e conclusão da atividade.';
$string['viewattendance'] = 'Visualizar presenças';
$string['joinredirectnotice'] = 'Você será redirecionado para a sala da aula.';
$string['videoavailableafterfinish'] = 'Finalize a aula para liberar o campo de inclusão do link da gravação no YouTube.';
$string['assignedteacher'] = 'Professor: {$a}';
$string['saverecording'] = 'Salvar link da gravação';
$string['recordingsaved'] = 'O link da gravação foi salvo com sucesso.';
$string['invalidrecordingurl'] = 'Informe um link válido do YouTube (por exemplo https://www.youtube.com/watch?v=XXXXXXXXXXX).';
$string['recordingformtitle'] = 'Compartilhar a gravação';
$string['recordingformdescription'] = 'Cole o link do YouTube com a gravação da aula. Deixe em branco para remover a gravação.';
$string['recordingurlplaceholder'] = 'https://www.youtube.com/watch?v=XXXXXXXXXXX';
$string['backtocourse'] = 'Voltar ao curso';
$string['nodetails'] = 'Nenhuma informação encontrada para esta aula.';
$string['painelaulasinvalidsession'] = 'Não foi possível localizar a sessão selecionada.';
$string['painelaulaspermissiondenied'] = 'Você não tem permissão para matricular este usuário na sessão.';
$string['painelaulasalreadyenrolled'] = 'Você já está matriculado no curso desta sessão.';
$string['painelaulasenrolmentsuccess'] = 'Matrícula realizada com sucesso. Agora você pode acessar a sessão.';
$string['painelaulasenrolmentfailure'] = 'Não foi possível realizar sua matrícula nesta sessão. Entre em contato com a administração do site.';
