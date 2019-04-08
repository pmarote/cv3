<?php

$pr->aud_registra(new PrMenu("ecd_rot_contabil", "E_CD", "Roteiro Contábil - Contas, Saldos e Resumo do ECD", "ecd"));

function ecd_rot_contabil() {

  global $pr;

  $dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 100);

  $lbl_obs1 	= new GtkLabel("Este módulo lista as Contas, Saldos, DRE, Balanço e Resumo do ECD");
  $chkbutton1 	= new GtkCheckButton("Na planilha contas__saldos, ordena por ordem crescente de código da conta");

  $dialog->vbox->pack_start($lbl_obs1, false, false, 3);
  $dialog->vbox->pack_start($chkbutton1, false, false, 3);

  $dialog->add_button(Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL);
  $dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);

  $dialog->set_has_separator(True);
  $dialog->show_all();
  $response_id = $dialog->run();

  if ($response_id != Gtk::RESPONSE_OK) {
	$dialog->destroy();
	return;
  }
  $dialog->destroy();

  //-- Saldos - contém um registro para cada cod_cta e cod_ccus
  //-- 1 - Agrupar por Conta
  //-- 2 - Transpor, ou seja, ver qual o mês inicial, mês final e jogar os saldos, debitos e créditos em colunas
  $mes_ini_fin = $pr->aud_sql2array("
	SELECT min(mes) AS mesmin, max(mes) AS mesmax FROM saldos;
");

  $sel_part1 = '';
  $sel_part2 = '';
  
  $anoini = substr($mes_ini_fin[0]['mesmin'], 0, 4) + 0; 
  $mesini = substr($mes_ini_fin[0]['mesmin'], 5, 2) + 0; 
  $anofin = substr($mes_ini_fin[0]['mesmax'], 0, 4) + 0; 
  $mesfin = substr($mes_ini_fin[0]['mesmax'], 5, 2) + 0; 
  
  $cabec_final = '';
  $ano = $anoini;
  $mes = $mesini;
  do {
	$mes2dig = substr('0' . $mes, -2);
	$ano2dig = substr($ano, -2);
	$sel_part1 .= ",
    abs(sld_ini_{$ano}{$mes2dig}) AS sld_ini_{$ano}{$mes2dig}, CASE WHEN sld_ini_{$ano}{$mes2dig} <= 0 THEN 'D' ELSE 'C' END AS dc_ini_{$ano}{$mes2dig},
    deb_{$ano}{$mes2dig}, cred_{$ano}{$mes2dig},
    abs(sld_fin_{$ano}{$mes2dig}) AS sld_fin_{$ano}{$mes2dig}, CASE WHEN sld_fin_{$ano}{$mes2dig} <= 0 THEN 'D' ELSE 'C' END AS dc_fin_{$ano}{$mes2dig}";
	$sel_part2 .= ",
   sum(CASE WHEN mes = '{$ano}-{$mes2dig}' THEN sld_ini ELSE 0 END) AS sld_ini_{$ano}{$mes2dig},
   sum(CASE WHEN mes = '{$ano}-{$mes2dig}' THEN deb     ELSE 0 END) AS deb_{$ano}{$mes2dig},
   sum(CASE WHEN mes = '{$ano}-{$mes2dig}' THEN cred    ELSE 0 END) AS cred_{$ano}{$mes2dig},
   sum(CASE WHEN mes = '{$ano}-{$mes2dig}' THEN sld_fin ELSE 0 END) AS sld_fin_{$ano}{$mes2dig}
";
	$cabec_final .= ",
	'SdoIni {$mes2dig}/{$ano2dig}' => 'Valor do Saldo Inicial em {$mes2dig}/{$ano}', 
	'D/C {$mes2dig}/{$ano2dig}' => 'Saldo Inicial em {$mes2dig}/{$ano} - Débito/Crédito', 
	'Déb {$mes2dig}/{$ano2dig}' => 'Débitos em {$mes2dig}/{$ano}', 
	'Créd {$mes2dig}/{$ano2dig}' => 'Créditos em {$mes2dig}/{$ano}', 
	'SdoFin {$mes2dig}/{$ano2dig}' => 'Valor do Saldo Final em {$mes2dig}/{$ano}',
	'D/Cf {$mes2dig}/{$ano2dig}' => 'Saldo Final em {$mes2dig}/{$ano} - Débito/Crédito'
";
	$mes++;
	if ($mes > 12) {
	  $mes = 1;
	  $ano++;
	}
	$continua = True;
	if ($ano == $anofin && $mes > $mesfin) $continua = False;
	if ($ano > $anofin) $continua = False;
  } while($continua);
  
  $select_saldo_transp = "
DROP TABLE IF EXISTS saldos_transp;
CREATE TABLE saldos_transp AS
SELECT cod_cta_sld
" . $sel_part1 . "
    FROM
        (SELECT cod_cta AS cod_cta_sld
" . $sel_part2 . "
            FROM 
            (SELECT mes, cod_cta, 
             sum(CASE WHEN ind_dc_ini = 'D' THEN -vl_sld_ini ELSE vl_sld_ini END) AS sld_ini, 
             sum(vl_deb) AS deb, sum(vl_cred) AS cred, 
             sum(CASE WHEN ind_dc_fin = 'D' THEN -vl_sld_fin ELSE vl_sld_fin END) AS sld_fin
               FROM saldos
               GROUP BY cod_cta, mes)
         GROUP BY cod_cta);
";

  $pr->db->createFunction('sqlite_lancto_sem_sl', 'sqlite_lancto_sem_sl');

  $part_tipo_1x1 = $pr->aud_sql2array("
SELECT count(*) * 2 AS contagem FROM (SELECT nro_deb, nro_cred FROM  lancto WHERE nro_deb = 1 AND nro_cred = 1);
");
  if (!isset($part_tipo_1x1[0]['contagem'])) $part_tipo_1x1[0]['contagem'] = 0;
  $part_tipo_1x1[0]['contagem'] = str_replace(',', '.', $part_tipo_1x1[0]['contagem']);

  
  $part_adic_sol4 = $pr->aud_sql2array("
SELECT - (sum(c3 - (c1 + c2))) AS adicionais FROM 
      (SELECT sqlite_lancto_sem_sl(nro_n, '_sl4_') AS nr_lcto, count(nro_n) AS c1, CASE WHEN nro_deb = 1 THEN nro_cred ELSE nro_deb END AS c2, (count(nro_n) * (nro_deb + nro_cred)) AS c3 FROM 
            (SELECT DISTINCT num_lcto AS nro_n, nro_deb, nro_cred FROM  lancto WHERE num_lcto LIKE '%_sl4_%') 
            GROUP BY nr_lcto);
");
  if (!isset($part_adic_sol4[0]['adicionais'])) $part_adic_sol4[0]['adicionais'] = 0;
  $part_adic_sol4[0]['adicionais'] = str_replace(',', '.', $part_adic_sol4[0]['adicionais']);

  $totdim_sol1 = $pr->aud_sql2array("
SELECT sum((TotDeb - TotDebFin) + (TotCred - TotCredFin)) totdim_sol1 FROM sol1;
");
  if (!isset($totdim_sol1[0]['totdim_sol1'])) $totdim_sol1[0]['totdim_sol1'] = 0;
  $totdim_sol1[0]['totdim_sol1'] = str_replace(',', '.', $totdim_sol1[0]['totdim_sol1']);

  $pr->aud_prepara($select_saldo_transp);

  $pr->inicia_excel('ECD_Roteiro_Contabil_Contas_Resumo');

  // Planilha Resumo do ECD
  $sql = "
SELECT '##NT##Tipo de Escrituração: ' || ind_esc || '     Versão: ' || cod_ver_lc FROM i010;
SELECT '';
SELECT ' Registros I050 (Contas): ', count(*) FROM i050;
SELECT ' Registros I051 (RelaÃ§Ã£o das Contas com Plano de Contas Referencial): ', count(*) FROM i051;
SELECT ' Registros I052 (RelaÃ§Ã£o das Contas com Conta de Agrupamento para BalanÃ§o): ', count(*) FROM i052;
SELECT ' Registros I075 (Tabela de HistÃ³rico Padronizado): ', count(*) FROM i075;
SELECT ' Registros I100 (RelaÃ§Ã£o dos Centros de Custo): ', count(*) FROM i100;
SELECT ' Registros I150 (Datas Iniciais e Finais dos Saldos, i155): ', count(*) FROM i150;
SELECT ' Registros I155 (Saldos Iniciais e Finais): ', count(*) FROM i155;
SELECT ' Registros I200 (NÃºmero do LanÃ§amento e Data): ', count(*) FROM i200;
SELECT '##N## Registros I250 (Débitos e Créditos de Cada Lançamento): ', count(*) FROM i250;
SELECT ' Registros I350 (Saldos Das Contas De Resultado Antes Do Encerramento - IdentificaÃ§Ã£o Da Data): ', count(*) FROM i350;
SELECT ' Registros I355 (Detalhes Dos Saldos Das Contas De Resultado Antes Do Encerramento): ', count(*) FROM i355;
SELECT ' Registros J005 (DemonstraÃ§Ãµes ContÃ¡beis): ', count(*) FROM j005;
SELECT ' Registros J100 (BalanÃ§o Patrimonial): ', count(*) FROM j100;
SELECT ' Registros J150 (DemonstraÃ§Ã£o de Resultado do ExercÃ­cio): ', count(*) FROM j150;
SELECT 'Quantidade de Linhas Geradas do DiÃ¡rio Decodificado em DÃ©bito e CrÃ©dito (Contrapartidas Identificadas)', count(*) FROM lancto;
SELECT 'Valor Total dos DÃ©bitos em DiÃ¡rio: ', Null, sum(CASE WHEN ind_dc = 'D' THEN vl_dc ELSE 0 END) FROM diario;
SELECT 'Valor Total de CrÃ©ditos em DiÃ¡rio (igual a DÃ©bitos): ', Null, sum(CASE WHEN ind_dc = 'C' THEN vl_dc ELSE 0 END) FROM diario;
SELECT '##N##  `-> Valor Total (Débitos + Créditos)', Null, sum(vl_dc) FROM diario;
SELECT 'Valor Total das Partidas ContÃ¡beis do tipo 1 DÃ©bito para 1 CrÃ©dito (1Âª FÃ³rmula)', Null, sum(valor) * 2 FROM lancto WHERE nro_deb = 1 AND nro_cred = 1;
SELECT 'Valor Total das Partidas ContÃ¡beis do tipo 1 DÃ©bito para N CrÃ©ditos (2Âª FÃ³rmula)', Null, sum(valor) * 2 FROM lancto WHERE nro_deb = 1 AND nro_cred > 1;
SELECT 'Valor Total das Partidas ContÃ¡beis do tipo N DÃ©bitos para 1 CrÃ©dito (3Âª FÃ³rmula)', Null, sum(valor) * 2 FROM lancto WHERE nro_deb > 1 AND nro_cred = 1;
SELECT 'Valor Total das Partidas ContÃ¡beis do tipo N DÃ©bitos para M CrÃ©ditos (4Âª FÃ³rmula)', Null, sum(valor) FROM lancto WHERE nro_deb > 1 AND nro_cred > 1;
SELECT 'Valor total do DiÃ¡rio diminuÃ­do em virtude da SoluÃ§Ã£o 1, no caso de agregar DÃ©bitos e CrÃ©ditos em um mesmo lanÃ§amento', Null, {$totdim_sol1[0]['totdim_sol1']};
SELECT '##N##  `-> Valor Total das Partidas Contábeis (deve ser igual a Débitos + Créditos)', Null, round(tp123 + tp4, 2) + {$totdim_sol1[0]['totdim_sol1']} FROM 
   (SELECT sum(CASE WHEN nro_deb > 1 AND nro_cred > 1 THEN 0 ELSE valor END) * 2 AS tp123, 
       sum(CASE WHEN nro_deb > 1 AND nro_cred > 1 THEN valor ELSE 0 END) AS tp4 FROM lancto);
SELECT 'Qtde.de Partidas do tipo 1 DÃ©bito para 1 CrÃ©dito (1Âª FÃ³rmula)', {$part_tipo_1x1[0]['contagem']};
SELECT 'Qtde.de Partidas do tipo 1 DÃ©bito para N CrÃ©ditos (2Âª FÃ³rmula)', sum(nro_deb + nro_cred) FROM
   (SELECT DISTINCT num_lcto, nro_deb, nro_cred FROM  lancto WHERE nro_deb = 1 AND nro_cred > 1);
SELECT 'Qtde.de Partidas do tipo N DÃ©bitos para 1 CrÃ©dito (3Âª FÃ³rmula)', sum(nro_deb + nro_cred) FROM
   (SELECT DISTINCT num_lcto, nro_deb, nro_cred FROM  lancto WHERE nro_deb > 1 AND nro_cred = 1);
SELECT 'Qtde.de Partidas do tipo N DÃ©bitos para N CrÃ©ditos (4Âª FÃ³rmula)', sum(nro_deb + nro_cred) FROM
   (SELECT DISTINCT num_lcto, nro_deb, nro_cred FROM  lancto WHERE nro_deb > 1 AND nro_cred > 1);
SELECT 'Qtde. de Partidas do DiÃ¡rio Deletadas em virtude da SoluÃ§Ã£o 1', count(*) AS qtd, Null FROM regsol WHERE nro_sol = 1 AND num_lcto_novo = 'Deletado';
SELECT 'Qtde. de Partidas Adicionais criadas em virtude da SoluÃ§Ã£o 4', {$part_adic_sol4[0]['adicionais']};
SELECT '##N##  `-> Total de Partidas Decodificadas (deve ser igual à quantidade de Reg.I250)', sum(nro_deb + nro_cred) + qtdregsol1 + {$part_adic_sol4[0]['adicionais']} + {$part_tipo_1x1[0]['contagem']} FROM
   (SELECT DISTINCT num_lcto, nro_deb, nro_cred, qtdregsol1 FROM lancto, (SELECT count(*) AS qtdregsol1 FROM regsol
    WHERE nro_sol = 1 AND num_lcto_novo = 'Deletado') WHERE nro_deb >1 OR nro_cred > 1);
SELECT 'Qtde. de LanÃ§amentos do tipo 1 DÃ©bito para 1 CrÃ©dito (1Âª FÃ³rmula)', count(*) FROM
   (SELECT DISTINCT  num_lcto FROM lancto WHERE nro_deb = 1 AND nro_cred = 1);
SELECT 'Qtde. de LanÃ§amentos do tipo 1 DÃ©bito para n CrÃ©ditos (2Âª FÃ³rmula)', count(*) FROM
   (SELECT DISTINCT  num_lcto FROM lancto WHERE nro_deb = 1 AND nro_cred > 1);
SELECT 'Qtde. de LanÃ§amentos do tipo n DÃ©bitos para 1 CrÃ©dito (3Âª FÃ³rmula)', count(*) FROM
   (SELECT DISTINCT  num_lcto FROM lancto WHERE nro_deb > 1 AND nro_cred = 1);
SELECT 'Qtde. de LanÃ§amentos do tipo n DÃ©bitos para n CrÃ©ditos (4Âª FÃ³rmula)', count(*) FROM
   (SELECT DISTINCT  num_lcto FROM lancto WHERE nro_deb > 1 AND nro_cred > 1);
SELECT '';
SELECT '##NTC##Soluções N Débitos para M Créditos (4ª Fórmula)';
SELECT 'Qtde. e Valor Total de Linhas de LanÃ§amentos Decodificados com SoluÃ§Ã£o 0 (NÃ£o SoluÃ§Ã£o) Gerados lanÃ§amentos com contrapartidas em Contas de CompensaÃ§Ã£o', count(*) AS qtd, sum(valor) AS valor FROM lancto WHERE obs LIKE '%#SoluÃ§Ã£o 0%';
SELECT 'Qtde. e Valor Total de Linhas de LanÃ§amentos Decodificados com SoluÃ§Ã£o 1 - Agrupando DÃ©bitos (ou CrÃ©ditos) Iguais', count(*) AS qtd, sum(valor) AS valor FROM lancto WHERE obs LIKE '%#SoluÃ§Ã£o 1%';
SELECT 'Qtde. e Valor Total de Linhas de LanÃ§amentos Decodificados com SoluÃ§Ã£o 2 - LanÃ§amentos 1 x 1, 1 x N, N x 1 ou N x N consecutivos, cadastrados com o mesmo nÃºmero', count(*) AS qtd, sum(valor) AS valor FROM lancto WHERE obs LIKE '%#SoluÃ§Ã£o 2%';
SELECT 'Qtde. e Valor Total de Linhas de LanÃ§amentos Decodificados com SoluÃ§Ã£o 4 - SoluÃ§Ã£o Cartesiana - Geradas N x M x 2 partidas, proporcionais aos N dÃ©bitos por M crÃ©ditos originais', count(*) AS qtd, sum(valor) AS valor FROM lancto WHERE obs LIKE '%#SoluÃ§Ã£o 4%';
SELECT '';
SELECT '##NTC##Dados do Arquivo ECD';
SELECT 'Termo de Encerramento    Nro.Livro: ' || num_ord || '    Natureza: ' || nat_livro FROM j900;
SELECT '(Reg 0000)    Data Inicial: ' || substr(dt_ini, 9, 2) || '/' || substr(dt_ini, 6, 2) || '/' || substr(dt_ini, 1, 4) || '    Data Final: ' || substr(dt_fin, 9, 2) || '/' || substr(dt_fin, 6, 2) || '/' || substr(dt_fin, 1, 4) FROM r0000;
SELECT '(Reg J900)    Data de InÃ­cio de EscrituraÃ§Ã£o: ' || substr(dt_ini_escr, 9, 2) || '/' || substr(dt_ini_escr, 6, 2) || '/' || substr(dt_ini_escr, 1, 4) || '    Data de TÃ©rmino de EscrituraÃ§Ã£o: ' || substr(dt_fin_escr, 9, 2) || '/' || substr(dt_fin_escr, 6, 2) || '/' || substr(dt_fin_escr, 1, 4) FROM j900;
SELECT 'Contribuinte: ' || nome FROM r0000;
SELECT 'CNPJ: ' || cnpj || '    IE: ' || ie || '    UF: ' || UF FROM r0000;
SELECT 'Outras InscriÃ§Ãµes Estaduais: ' || cod_inscr FROM r0007;
SELECT 'SignatÃ¡rios da EscrituraÃ§Ã£o:';
SELECT ident_nom || '    CPF: ' || ident_cpf || '    Qualif: ' || ident_qualif || '    CRC: ' || ind_crc FROM j930;";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "#.##0",
	"C:C" => "#.##0,00");
  $cabec = array(
	'Descrição' => 'Descrição
Indicador da forma de escrituração contábil:
G - Livro Diário  (Completo sem escrituração auxiliar);
R - Livro Diário  com Escrituração Resumida (com escrituração auxiliar);
A - Livro Diário Auxiliar ao Diário com Escrituração Resumida;
B - Livro Balancetes Diários e Balanços;
Z – Razão Auxiliar (Livro Contábil Auxiliar conforme leiaute definido nos registros I500 a I555).
',
	'Qtd' => 'Quantidade',
	'Val.Tot.' => 'Valor Total');
  $pr->abre_excel_sql('Resumo', 'Resumo do ECD', $sql, $col_format, $cabec);



  // Planilha Balancetes
  $sql = "
SELECT select1.mes, select1.cod_cta, contas.cod_nat, contas.cta AS cta_debito,
	select1.situacao, select1.vl_sld_ini, select1.ind_dc_ini, select1.vl_deb, select1.vl_cred,
	select1.vl_sld_fin, select1.ind_dc_fin, debitos, creditos, n_debitos, n_creditos, e_debitos, e_creditos FROM 
(SELECT saldos.mes AS mes, saldos.cod_cta AS cod_cta, 
  CASE WHEN abs(vl_deb - (CASE WHEN debitos ISNULL THEN 0 ELSE debitos END)) <= 0.01 THEN
    CASE WHEN abs(vl_cred - (CASE WHEN creditos ISNULL THEN 0 ELSE creditos END)) <= 0.01 THEN
      CASE WHEN abs(CASE WHEN ind_dc_ini = 'D' THEN -vl_sld_ini ELSE vl_sld_ini END - vl_deb + vl_cred - CASE WHEN ind_dc_fin = 'D' THEN -vl_sld_fin ELSE vl_sld_fin END) <= 0.01 THEN
        'Ok'
      ELSE 'Erro Saldo Inicial + Debitos - Creditos <> Saldo Final' END
    ELSE 'Erro Creditos em Saldos <> Creditos no DiÃ¡rio' END
  ELSE 'Erro Debitos em Saldos <> Debitos no DiÃ¡rio' END AS situacao,
  saldos.vl_sld_ini AS vl_sld_ini, saldos.ind_dc_ini AS ind_dc_ini,
  saldos.vl_deb AS vl_deb, saldos.vl_cred AS vl_cred, 
  saldos.vl_sld_fin AS vl_sld_fin, saldos.ind_dc_fin AS ind_dc_fin,
  debitos, creditos, n_debitos, n_creditos, e_debitos, e_creditos
  FROM saldos
  LEFT OUTER JOIN movto ON movto.mes = saldos.mes AND movto.cod_cta = saldos.cod_cta) AS select1
  LEFT OUTER JOIN contas ON select1.cod_cta = contas.cod_cta
  WHERE contas.nivel = {$pr->sql_params['ecd']['max_nivel']} AND contas.cod_cta NOT LIKE 'NS________';;
";
  $col_format = array(
	"B:B" => "0",
	"C:C" => "00",
	"F:Q" => "#.##0,00");
  $cabec = array(
	'Mês' => "Mês", 
	'Conta' => "Código da Conta", 
	"Nat" => "Código da Natureza da conta, podendo ser:
01  Contas de ativo  
02  Contas de passivo  
03  Patrimônio líquido  
04  Contas de resultado  
05  Contas de compensação
09  Outras",
	'Desc_Conta' => "Descrição da Conta",
	'Sit' => "Verificações de Saldo Inicial mais Débitos menos Créditos com Saldo Final e comparação com Débitos e Créditos do Diário", 
	'Saldo Inicial' => "Valor do Saldo Inicial", 
	'I' => "Saldo Inicial - Débito/Crédito", 
	'Débitos' => "Débitos", 
	'Créditos' => "Créditos", 
	'Saldo Final' => "Valor do Saldo Final",
	'F' => "Saldo Final - Débito/Crédito", 
	'Diario_Déb' => "Débitos conforme Diário", 
	'Diário Créd' => "Créditos conforme Diário", 
	'N_Diario_Déb' => "Débitos conforme Diário - Lançamentos Normais", 
	'N_Diário Créd' => "Créditos conforme Diário - Lançamentos Normais", 
	'E_Diario_Déb' => "Débitos conforme Diário - Lançamentos de Encerramento", 
	'E_Diário Créd' => "Créditos conforme Diário - Lançamentos de Encerramento");
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_largura_coluna("C:C", 4);
	$this->excel_zoom_visualizacao(100);
';
  $pr->abre_excel_sql('Confr_Saldos_Diario', 'Confronto Sds. Iniciais + Débitos - Créditos = Sds. Finais (Reg.I155) e também com Débitos e Créditos do Diário (Reg.I250)', $sql, $col_format, $cabec, $form_final);
  
  
  // Planilha DRE
  $sql = "
SELECT dt_ini, dt_fin, cod_agl, nivel_agl, 
  CASE WHEN ind_vl IN ('P','N') THEN '' ELSE '     ' END || descr_cod_agl AS descr_cod_agl,
  CASE WHEN ind_vl IN ('R','P') THEN vl_cta ELSE -vl_cta END AS vl_cta,
  CASE WHEN ind_vl IN ('P','N') THEN 
    CASE WHEN nivel_agl + 0 = 1 THEN '##EN##' || ind_vl ELSE '##TN##' || ind_vl END
  ELSE ind_vl END AS ind_vl FROM j150;
";
  $col_format = array(
	"C:D" => "0",
	"F:F" => "#.##0,00");
  $cabec = array(
	'dt_ini' => 'Data Inicial do DRE',
	'dt_fin' => 'Data Final do DRE',
	'Cod_Agl' => 'Código de aglutinação das contas, atribuído pelo empresário ou sociedade empresária.',
	'Nível_Agl' => 'Nível do Código de aglutinação (mesmo conceito do plano de contas – Registro I050).',
	'Descr_Cod_Agl' => 'Descrição do Código de aglutinação.',
	'Vl_Cta' => 'Valor total do Código de aglutinação na Demonstração do Resultado do Exercício no período informado.',
	'Ind_Vl' => 'Indicador da situação do valor informado no campo anterior: 
D - Despesa ou valor que represente parcela redutora do lucro;
R - Receita ou valor que represente incremento do lucro;
P - Subtotal ou total positivo;
N – Subtotal ou total negativo.');
  $pr->abre_excel_sql('DRE', 'DRE (Registro J150)', $sql, $col_format, $cabec);


  // Planilha Balanco
  $sql = "SELECT dt_ini, dt_fin, cod_agl, 
  CASE WHEN nivel_agl + 0 = 1 THEN '##EN##' || nivel_agl ELSE
    CASE WHEN nivel_agl + 0 = 2 THEN '##TN##' || nivel_agl ELSE nivel_agl END
  END AS nivel_agl, ind_grp_bal, 
  CASE WHEN nivel_agl + 0 > 1 THEN '     ' ELSE '' END || 
     CASE WHEN nivel_agl + 0 > 2 THEN '     ' ELSE '' END || 
        CASE WHEN nivel_agl + 0 > 3 THEN '     ' ELSE '' END ||
            CASE WHEN nivel_agl + 0 > 4 THEN '     ' ELSE '' END || descr_cod_agl AS descr_cod_agl,
  vl_cta, ind_dc_bal FROM j100";
  $col_format = array(
	"C:E" => "0",
	"G:G" => "#.##0,00");
  $cabec = array(
	'dt_ini' => 'Data Inicial do Balanço',
	'dt_fin' => 'Data Final do Balanço',
	'Cod_Agl' => 'Código de aglutinação das contas, atribuído pelo empresário ou sociedade empresária.',
	'Nível_Agl' => 'Nível do Código de aglutinação (mesmo conceito do plano de contas – Registro I050).',
	'Ind_Grp_Bal' => 'Indicador de grupo do balanço:
1 – Ativo;
2 – Passivo e Patrimônio Líquido;',
	'Descr_Cod_Agl' => 'Descrição do Código de aglutinação.',
	'Vl_Cta' => 'Valor total do Código de aglutinação no Balanço Patrimonial no exercício informado, ou de período definido em norma específica.',
	'Ind_DC_Bal' => 'Indicador da situação do saldo informado no campo anterior:
D - Devedor;
C – Credor.');
  $pr->abre_excel_sql('Balanco', 'Balanço (Registro J100)', $sql, $col_format, $cabec);  


  // Planilha saldos das contas de resultado antes do encerramento
  $cont_i355 = $pr->aud_sql2array("
	SELECT count(*) AS cont_i355 FROM i355;
");
  if ($cont_i355[0]['cont_i355'] + 0 == 0) {
	$sql = "SELECT 'NÃ£o hÃ¡ registros I355 disponÃ­veis...';";
	$col_format = array(
		"A:A" => "0" 
);
	$cabec = array(
		"Mensagem" => "Mensagem"
);
  } else {
	$sql = "
SELECT 
       CASE WHEN nivel = 1 THEN '##E##' || cod_cta_n1 ELSE cod_cta_n1 END AS cod_cta_n1,
       CASE WHEN nivel = 2 THEN '##T##' || cod_cta_n2 ELSE cod_cta_n2 END AS cod_cta_n2,
       cod_cta_n3,
       cod_cta_n4,
       cod_cta_n5,
       CASE WHEN nivel >= 6 THEN contas.cod_cta ELSE Null END AS cod_cta_n6,
      saldos_ant_enc.dt_res AS dt_res, saldos_ant_enc.vl_cta AS vl_cta, saldos_ant_enc.ind_dc AS ind_dc,
      dt_alt, cod_nat, contas.ind_cta AS ind_cta, nivel, cod_cta_sup, contas.cta AS cta, contas.cod_ccus AS cod_ccus, cod_agl, 
      contas.cod_cta_ref AS cod_cta_ref, plactaref.cta AS desc_cta_ref
      FROM contas
      LEFT OUTER JOIN saldos_ant_enc ON saldos_ant_enc.cod_cta  = contas.cod_cta
      LEFT OUTER JOIN plactaref ON plactaref.cod_cta_ref = contas.cod_cta_ref
       WHERE cod_nat + 0 = 4
       ORDER BY ordcta;
";
	$col_format = array(
		"A:F" => "0", 
		"H:H" => "#.##0,00",
		"K:K" => "00", 
		"N:R" => "0" 
);
	$cabec = array(
		"Conta_N1" => "Código da Conta, quando de Nível 1",
		"Conta_N2" => "Código da Conta, quando de Nível 2",
		"Conta_N3" => "Código da Conta, quando de Nível 3",
		"Conta_N4" => "Código da Conta, quando de Nível 4",
		"Conta_N5" => "Código da Conta, quando de Nível 5",
		"Conta_N6+" => "Código da Conta, quando de Nível 6 ou superior",
		'dt_res' => "Data da apuração do resultado.",
		'vl_cta' => "Valor do saldo final antes do lançamento de encerramento.",
		'ind_dc' => "Indicador da situação do saldo final:
D - Devedor;
C - Credor.",
		"Data Inc/Alt" => "Data da Inclusão ou Alteração",
		"Natureza" => "Código da Natureza da conta, podendo ser:
01  Contas de ativo  
02  Contas de passivo  
03  Patrimônio líquido  
04  Contas de resultado  
05  Contas de compensação
09  Outras",
		"Ind_Cta" => "Indicador do tipo de conta:
S - Sintética (grupo de contas)
A - Analítica (conta).",
		"Nível" => "Nível da Conta
(Ativo, Passivo, etc.). Deve ser acrescido de 1 a cada mudança de nível. Exemplo:
Nível  Grupo/Conta:
1      Ativo
2      Ativo Circulante
3      Disponível
4      Caixa",
		"Conta Sup" => "Código da Conta de Nível Superior",
		"Nome" => "Descrição da Conta",
		"C.Custo" => "Código do Centro de Custo",
		"Cod_Agl" => "Código de aglutinação utilizado na Demonstração de Resultado do Exercício no Bloco J (somente para as contas analíticas).",
		"Cod_Conta Ref" => "Código da Conta do Plano de Contas Referencial",
		"Conta Ref" => "Descrição da Conta do Plano de Contas Referencial",
);
  }
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
';

  $pr->abre_excel_sql("Saldo_Ctas_Res", "Saldos das Contas de Resultado Antes do Encerramento (Registros I350 e I355)", $sql, $col_format, $cabec, $form_final);

  // Planilha Centro de Custos 
  $sql = "
	SELECT dt_alt, cod_ccus, ccus FROM i100;
";
  $col_format = array(
	"B:B" => "0");
  $cabec = array(
	"Dt_Alt" => "Data da inclusão/alteração.",
	"Cod_CCus" => "Código do centro de custos.",
	"CCus" => "Nome do centro de custos.");
  $pr->abre_excel_sql("CentCustos", "Centros de Custos (Registro I100)", $sql, $col_format, $cabec);


   // Planilha I075 - Tabela de Histórico Padronizado
  $sql = "
SELECT cod_hist, descr_hist FROM i075;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
	'cod_hist' => 'Código do Histórico Padronizado',
	'Descrição do Histórico' => 'Descrição do Histórico Padronizado');
  $pr->abre_excel_sql('I075', 'Tabela de Histórico Padronizado (Registro I075)', $sql, $col_format, $cabec);


  $order_ctasal = '';
  if ($chkbutton1->get_active()) $order_ctasal = ' ORDER BY cod_cta';
  // Planilha Contas e Saldos
  $sql = "
SELECT cod_cta_n1, cod_cta_n2, cod_cta_n3, cod_cta_n4, cod_cta_n5, cod_cta_n6, 
      dt_alt, cod_nat, ind_cta, nivel, cod_cta_sup, cta, cod_ccus, cod_agl, cod_cta_ref, desc_cta_ref, 
      medsaldo, medsaldo_dc, minsaldo, minsaldo_dc, maxsaldo, maxsaldo_dc,
      saldos_transp.*
     FROM
    (SELECT 
       CASE WHEN nivel = 1 THEN '##E##' || cod_cta_n1 ELSE cod_cta_n1 END AS cod_cta_n1,
       CASE WHEN nivel = 2 THEN '##T##' || cod_cta_n2 ELSE cod_cta_n2 END AS cod_cta_n2,
       cod_cta_n3,
       cod_cta_n4,
       cod_cta_n5,
       CASE WHEN nivel >= 6 THEN cod_cta ELSE Null END AS cod_cta_n6,
      cod_cta, dt_alt, cod_nat, contas.ind_cta AS ind_cta, nivel, cod_cta_sup, contas.cta AS cta, cod_ccus, cod_agl, 
      contas.cod_cta_ref AS cod_cta_ref, plactaref.cta AS desc_cta_ref
      FROM contas
      LEFT OUTER JOIN plactaref ON plactaref.cod_cta_ref = contas.cod_cta_ref
       ORDER BY ordcta)
    LEFT OUTER JOIN saldos_transp ON cod_cta_sld = cod_cta
    LEFT OUTER JOIN salmedminmax ON salmedminmax.cod_cta_busca = cod_cta{$order_ctasal};";
  $col_format = array(
	"A:F" => "0",
	"K:K" => "0",
	"Q:U" => "#.##0,00",
	"W:W" => "0",
	"X:FF" => "#.##0,00");
  $string_cabec = <<<EOD
cabec = array(
	"Conta_N1" => "Código da Conta, quando de Nível 1",
	"Conta_N2" => "Código da Conta, quando de Nível 2",
	"Conta_N3" => "Código da Conta, quando de Nível 3",
	"Conta_N4" => "Código da Conta, quando de Nível 4",
	"Conta_N5" => "Código da Conta, quando de Nível 5",
	"Conta_N6+" => "Código da Conta, quando de Nível 6 ou superior",
	"Data Inc/Alt" => "Data da Inclusão ou Alteração",
	"Natureza" => "Código da Natureza da conta, podendo ser:
01  Contas de ativo  
02  Contas de passivo  
03  Patrimônio líquido  
04  Contas de resultado  
05  Contas de compensação
09  Outras",
	"Ind_Cta" => "Indicador do tipo de conta:
S - Sintética (grupo de contas)
A - Analítica (conta).",
	"Nível" => "Nível da Conta
(Ativo, Passivo, etc.). Deve ser acrescido de 1 a cada mudança de nível. Exemplo:
Nível  Grupo/Conta:
1      Ativo
2      Ativo Circulante
3      Disponível
4      Caixa",
	"Conta Sup" => "Código da Conta de Nível Superior",
	"Nome" => "Descrição da Conta",
	"C.Custo" => "Código do Centro de Custo",
	"Cod_Agl" => "Código de aglutinação utilizado no Balanço Patrimonial e na Demonstração de Resultado do Exercício no Bloco J (somente para as contas analíticas).",
	"Cod_Conta Ref" => "Código da Conta do Plano de Contas Referencial",
	"Conta Ref" => "Descrição da Conta do Plano de Contas Referencial",
	"Saldo\nDiário Med" => "Saldo Médio dos Finais de Cada Dia",
	"D/C\nSMd" => "Saldo Médio dos Finais de Cada Dia - Indicador Débito / Crédito",
	"Saldo\nDiário Max Déb/Min Créd" => "Máximo valor a Débito dos Saldos dos Finais de Cada Dia ou,
caso não haja Débito, Mínimo valor a Crédito dos Saldos dos Finais de Cada Dia",
	"D/C\nMxDeb/MnCréd" => "Indicador Débito / Crédito do Máximo valor a Débito dos Saldos dos Finais de Cada Dia ou,
caso não haja Débito, Mínimo valor a Crédito dos Saldos dos Finais de Cada Dia",
	"Saldo\nDiário Max Créd/Min Déb" => "Máximo valor a Crédito dos Saldos dos Finais de Cada Dia ou,
caso não haja Crédito, Mínimo valor a Débito dos Saldos dos Finais de Cada Dia",
	"D/C\nMxCréd/MnDeb" => "Indicador Débito / Crédito do Máximo valor a Crédito dos Saldos dos Finais de Cada Dia ou,
caso não haja Crédito, Mínimo valor a Débito dos Saldos dos Finais de Cada Dia",
	"Cta em Saldo" => "Existe lançamento em Saldos (Registros I150 e I155) dessa conta ? Caso positivo, aparece o número da conta. Caso não exista, campo em branco."
	{$cabec_final}
);
EOD;
  eval('$'.$string_cabec);
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(75);
';
  $pr->abre_excel_sql("Contas_Saldos", "Contas e Saldos", $sql, $col_format, $cabec, $form_final);
  
  
  $pr->finaliza_excel();
}

function sqlite_lancto_sem_sl($valor, $tipo_sl) {
  return substr($valor, 0, strpos($valor, $tipo_sl));
}

?>