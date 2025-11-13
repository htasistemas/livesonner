<?php
// Este arquivo faz parte do Moodle - http://moodle.org/
//
// O Moodle é um software livre; você pode redistribuí-lo e/ou
// modificá-lo sob os termos da Licença Pública Geral GNU, conforme
// publicada pela Free Software Foundation, na versão 3 da Licença ou
// (a seu critério) qualquer versão posterior.
//
// O Moodle é distribuído na expectativa de que seja útil,
// mas SEM QUALQUER GARANTIA; nem mesmo a garantia implícita de
// COMERCIABILIDADE OU ADEQUAÇÃO A UMA FINALIDADE ESPECÍFICA. Consulte a
// Licença Pública Geral GNU para mais detalhes.
//
// Você deve ter recebido uma cópia da Licença Pública Geral GNU
// juntamente com o Moodle. Caso contrário, consulte <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Aulas ao vivo';
$string['pagetitle'] = 'Aulas ao vivo';
$string['aulasaovivo:view'] = 'Visualizar a página Aulas ao vivo';
$string['catalogtitle'] = 'Catálogo de aulas ao vivo';
$string['catalogsubtitle'] = 'Encontre novas transmissões e reserve sua vaga em poucos cliques.';
$string['enrolledtitle'] = 'Minhas aulas';
$string['enrolledsubtitle'] = 'Veja o status das aulas nas quais você já está inscrito.';
$string['certificatestitle'] = 'Ver meus certificados';
$string['certificatessubtitle'] = 'Baixe os certificados das aulas ao vivo que você já concluiu.';
$string['tabcatalog'] = 'Aulas disponíveis';
$string['tabenrolled'] = 'Minhas aulas';
$string['tabcertificates'] = 'Meus certificados';
$string['tablistlabel'] = 'Seções do painel de aulas ao vivo';
$string['refresh'] = 'Atualizar';
$string['previous'] = 'Anterior';
$string['next'] = 'Próximo';
$string['emptycatalog'] = 'Nenhuma aula disponível no momento. Volte em breve.';
$string['emptyenrolled'] = 'Você ainda não está inscrito em nenhuma aula.';
$string['emptycertificates'] = 'Você ainda não possui certificados disponíveis.';
$string['fallbacknotice'] = 'Exibindo dados de demonstração. Configure a integração com o plugin de aulas para usar dados reais.';
$string['enrolsuccess'] = 'Inscrição realizada com sucesso!';
$string['enrolfailure'] = 'Não foi possível concluir sua inscrição nesta aula.';
$string['integrationmissing'] = 'Configure o plugin de aulas para permitir inscrições reais.';
$string['processing'] = 'Processando...';
$string['countdownlabel'] = 'Faltam';
$string['countdownlive'] = 'Ao vivo';
$string['countdownfinished'] = 'Encerrada';
$string['accesssession'] = 'Acessar aula';
$string['enrolsession'] = 'Me inscrever';
$string['sessionclosed'] = 'Aula encerrada';
$string['seemore'] = 'Ver detalhes';
$string['enrolledbadge'] = 'Aula inscrita';
$string['confirmedbadge'] = 'Aula confirmada';
$string['startslabel'] = 'Data e hora';
$string['endslabel'] = 'Fim';
$string['locationlabel'] = 'Local';
$string['instructorlabel'] = 'Instrutor';
$string['taglabel'] = 'Trilha';
$string['certificateissuedon'] = 'Emitido em';
$string['certificatedownload'] = 'Baixar certificado';
$string['settings_providercomponent'] = 'Componente do plugin de aulas';
$string['settings_providercomponent_desc'] = 'Informe o componente (por exemplo, mod_livesonner) responsável por fornecer as aulas disponíveis e inscrições.';
$string['settings_enablefallback'] = 'Ativar dados de demonstração';
$string['settings_enablefallback_desc'] = 'Quando a integração não estiver disponível, a página exibirá dados fictícios para validação visual.';
$string['privacy:metadata'] = 'O plugin local Aulas ao vivo não armazena dados pessoais além do necessário para exibir inscrições ativas.';
$string['error:missingprovider'] = 'Não foi possível contactar o componente configurado para fornecer as aulas.';
$string['error:sessionnotfound'] = 'Aula não encontrada.';
$string['toastdefault'] = 'Atualização concluída.';
$string['agendatitlecatalog'] = 'Agenda completa';
$string['agendatitleenrolled'] = 'Próximas aulas confirmadas';
$string['agendapast'] = 'Encerrada';
$string['agendalive'] = 'Ao vivo';
$string['agendaunconfirmed'] = 'Próximas datas';
$string['manualcertificatebutton'] = 'Conceder certificado manualmente';
$string['manualcertificatetitle'] = 'Conceder certificado manualmente';
$string['manualcertificatesuccess'] = 'Certificado emitido com sucesso.';
$string['manualcertificateerror'] = 'Não foi possível emitir o certificado.';
$string['manualcertificate:session'] = 'Aula ao vivo';
$string['manualcertificate:sessionplaceholder'] = 'Selecione a aula ao vivo';
$string['manualcertificate:user'] = 'Usuário';
$string['manualcertificate:userplaceholder'] = 'Busque pelo usuário';
$string['manualcertificate:name'] = 'Nome do certificado';
$string['manualcertificate:file'] = 'Certificado em PDF';
$string['manualcertificate:submit'] = 'Salvar certificado';
$string['manualcertificate:success'] = 'O certificado da aula "{$a}" foi salvo.';
$string['manualcertificate:invaliduser'] = 'Não foi possível localizar o usuário selecionado.';
$string['manualcertificate:missingfile'] = 'Envie o arquivo PDF do certificado antes de salvar.';
$string['manualcertificate:invalidfiletype'] = 'Apenas arquivos PDF são permitidos para certificados.';
