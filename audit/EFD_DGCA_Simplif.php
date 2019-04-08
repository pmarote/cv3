<?php

$pr->aud_registra(new PrMenu("efd_dgca_simplif", "E_FD", "DGCAs Simplificados", "efd"));

function efd_dgca_simplif() {

  global $pr;

  $dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 100);

  $lbl_obs1   = new GtkLabel("Este módulo gera relatórios para desenvolvimento de DGCAs simplificadas;");
  $dialog->vbox->pack_start($lbl_obs1, false, false, 3);

  $chkbuttons = array();
  
  $lista_opcoes = array(
  0 => "Deleta e preenche novamente lasimca.db3 com dados de efd.db3",
  1 => "Gera arquivo .txt a partir de lasimca.txt"
);
  //debug_log(print_r($lista_opcoes, True));

  foreach ($lista_opcoes as $indice => $valor) {
    $chkbuttons[$indice] = new GtkCheckButton(str_replace('_', '__', $valor));
    $chkbuttons[$indice]->set_active(True);
    $dialog->vbox->pack_start($chkbuttons[$indice], false, false, 3);
  }
  $dialog->add_button("Inverter Seleção", 100);
  $dialog->add_button(Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL);
  $dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);
  $dialog->set_has_separator(True);
  $dialog->show_all();
  do {
    $response_id = $dialog->run();
    if ($response_id == 100) {
      foreach ($lista_opcoes as $indice => $valor) {
        $chkbuttons[$indice]->set_active(!$chkbuttons[$indice]->get_active());
      }
    }
  } while ($response_id == 100);
  if ($response_id != Gtk::RESPONSE_OK) {
    $dialog->destroy();
    return;
  }
  $dialog->destroy();

  //debug_log("Opção0:" . ($chkbuttons[0]->get_active() ? "Sim" : "Não") . "\r" );
  //debug_log("Opção1:" . ($chkbuttons[1]->get_active() ? "Sim" : "Não") . "\r" );

  $pr->inicia_excel('EFD_DGCA_Simplif');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';

	// Tabela dgca_simplif - fórmulas para cálculos dos DGCAs simplificados
	$createtable = "
DROP TABLE IF EXISTS dgca_simplif;
CREATE TABLE dgca_simplif (cfop int, valcon_s text, valcon_e text, bcicms_e text, icms_pmc text);
CREATE INDEX dgca_simplif_chapri ON dgca_simplif (cfop ASC);
";
	create_table_from_txt($pr->db, $createtable, PR_RES . '/tabelas/dgca_simplif.txt', 'dgca_simplif');	

	$pr->aud_prepara("
-- Criação da tabela que junta todos os registros c190 e similares
DROP TABLE IF EXISTS junt_90s;	
CREATE TABLE junt_90s AS 
SELECT 
    'c190' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, vl_red_bc, vl_ipi
    FROM c190
UNION ALL
SELECT 
    'c490' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, 0 AS vl_red_bc, 0 AS vl_ipi
    FROM c490
UNION ALL
SELECT 
    'c590' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, vl_red_bc, 0 AS vl_ipi
    FROM c590
UNION ALL
SELECT 
    'c850' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, 0 AS vl_red_bc, 0 AS vl_ipi
    FROM c850
UNION ALL
SELECT 
    'c890' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, 0 AS vl_red_bc, 0 AS vl_ipi
    FROM c890
UNION ALL
SELECT 
    'd190' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, vl_red_bc, 0 AS vl_ipi
    FROM d190
UNION ALL
SELECT 
    'd590' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, vl_red_bc, 0 AS vl_ipi
    FROM d590;
-- dgca analítico
DROP TABLE IF EXISTS dgca_analit;	
CREATE TABLE dgca_analit AS
SELECT 
   junt_90s.*, dgca_simplif.*,
   CASE WHEN valcon_s = '-' THEN -vl_opr ELSE 
        CASE WHEN valcon_s = '+' THEN vl_opr ELSE 0 END
   END AS saidas,
   CASE WHEN valcon_e = '-' THEN -vl_opr ELSE 
        CASE WHEN valcon_e = '+' THEN vl_opr ELSE 0 END
   END AS entradas_valcon,
   CASE WHEN bcicms_e = '-' THEN -vl_bc_icms ELSE 
        CASE WHEN bcicms_e = '+' THEN vl_bc_icms ELSE 0 END
   END AS entradas_bcicms,
   CASE WHEN icms_pmc = '-' THEN -vl_icms ELSE 
        CASE WHEN icms_pmc = '+' THEN vl_icms ELSE 0 END
   END AS pmc_den
   FROM junt_90s
   LEFT OUTER JOIN dgca_simplif ON dgca_simplif.cfop = junt_90s.cfop;
");


	// Planilha Resumo Geral
	$sql = "
SELECT '##NT##Resumo Geral de DGCAs - Registros Analíticos    Total de Linhas: ', count(*) FROM dgca_analit;
SELECT DISTINCT '##NI##EFD:0000 Empresa: CNPJ ' || cnpj ||  ' IE ' || ie || ' NOME ' || nome FROM o000;
SELECT DISTINCT '##NI##EFD:0005 Fantasia: ' || fantasia ||  ' Endereço: ' || end || '  ' || num || '  ' || compl || '  ' || bairro || ' Fone: ' || fone || ' email: ' || email FROM o005;
SELECT DISTINCT '##NI##EFD:0100 Contabilista: ' || nome ||  ' cpf: ' || cpf || ' crc: ' || crc || ' cnpj: ' || cnpj || ' Endereço: ' || end || '  ' || num || '  ' || compl || '  ' || bairro || ' Fone: ' || fone || ' email: ' || email FROM o100;
SELECT '';
SELECT '##NI##      Arquivos SPED de: ' || min(dt_ini) || ' a ' || max(dt_fin) || ', qtd arquivos =', count(*) FROM o000;
SELECT '';
SELECT 'Indices:', '##NI##VVD-CA', '##NI##VCP-CA', '##NI##VVD-CA - VCP-CA', '##NI##IVA-CA', '##NI##PMC-CA';
SELECT '', vvd_ca, vcp_ca, vvd_ca - vcp_ca AS resultado, (vvd_ca - vcp_ca) / vcp_ca * 100 AS iva_ca, pcm_den / vcp_ca * 100 AS pcm_ca FROM 
    (SELECT sum(saidas) AS vvd_ca, sum(entradas_valcon) + sum(entradas_bcicms) AS vcp_ca, sum(pmc_den) AS pcm_den FROM  dgca_analit);
";
	$col_format = array(
		"A:A" => "0",
		"B:B" => "#.##0",
		"C:F" => "#.##0,00_ ;[Vermelho]-#.##0,00 ");

	$cabec = array(
	'Descrição' => 'Descrição',
	'Qtd' => 'Quantidade',
	'Saídas-CA' => 'Saídas conforme CFOPs de Crédito Acumulado (Portaria Cat 207/2009 Anexo III)',
	'Entradas-ValCon' => 'Entradas, valores contábeis, conforme CFOPs de Crédito Acumulado (Portaria Cat 207/2009 Anexo III)',
	'Entradas-BCICMS' => 'Entradas, bases de cálculos de ICMS, conforme CFOPs de Crédito Acumulado (Portaria Cat 207/2009 Anexo III)',
	'PMC-Den' => 'Denominador do PMC, ou seja, valor total de créditos, conforme CFOPs de Crédito Acumulado (Portaria Cat 207/2009 Anexo III)');
	$pr->abre_excel_sql('Resumo', 'Resumo Geral DGCAs.db3', $sql, $col_format, $cabec);

	// Planilha dgca_sintetica
	$sql = "
SELECT 
   CASE WHEN saidas = 0 THEN 'Nihil' ELSE
        CASE WHEN vl_red_bc > 0 THEN '2.x' ELSE
             CASE WHEN cfop > 7000 THEN '3.1' ELSE
	         CASE WHEN aliq_icms = 0 THEN '3.x' ELSE '1.x' END
             END
	END
   END AS art71, * FROM
(SELECT 
    aamm, cst_icms, aliq_icms, cfop, 
    sum(saidas) AS saidas, sum(entradas_valcon + entradas_bcicms) AS entradas,
    sum(entradas_valcon) AS entradas_valcon, sum(entradas_bcicms) AS entradas_bcicms, 
    sum(pmc_den) AS pmc_den, 
    sum(vl_opr) AS vl_opr, sum(vl_red_bc) AS vl_red_bc, sum(vl_bc_icms) AS vl_bc_icms, sum(vl_icms) AS vl_icms, sum(vl_ipi) AS vl_ipi
    FROM dgca_analit
    GROUP BY aamm, cst_icms, aliq_icms, cfop) AS sel_tmp;
";
	$col_format = array(
	"E:E" => "#.##0_ ;[Vermelho]-#.##0 ",
	"F:O" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = array(
	'art71' => "",
	'aamm' => "",
	'cst_icms' => "",
	'aliq_icms' => "",
	'cfop' => "",
	'saidas' => "",
	'entradas' => "",
	'entradas_valcon' => "",
	'entradas_bcicms' => "",
	'pmc_den' => "",
	'vl_opr' => "",
	'vl_red_bc' => "",
	'vl_bc_icms' => "",
	'vl_icms' => "",
	'vl_ipi' => ""
);
	$pr->abre_excel_sql('dgca_sintet', 'DGCa_sintética - Revise a primeira coluna e monte os DGCAs', $sql, $col_format, $cabec, $form_final);

	// Planilha dgca_analítica
	$tabela = 'dgca_analit';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"B:B" => "0",
	"E:E" => "#.##0_ ;[Vermelho]-#.##0 ",
	"G:K" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"L:L" => "#.##0_ ;[Vermelho]-#.##0 ",
	"Q:T" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = $pr->auto_cabec($tabela);
	$pr->abre_excel_sql('dgca_analit', 'dgca_analit', $sql, $col_format, $cabec, $form_final);
  
  $pr->finaliza_excel();

  if ($chkbuttons[0]->get_active()) {
    lasimca_do_efd(); // ver ao final deste arquivo php
  }

  if ($chkbuttons[1]->get_active()) {
    lasimca2txt(); // ver ao final deste arquivo php
  }

}


function lasimca2txt() {

  $pr2 = new Pr;  // classe principal, global
  $pr2->carrega_dicdados();
  //debug_log(print_r($pr2->dicdados, True));

  wecho("\n\nCriando arquivo txt a partir da lasimca.db3\r\n");
  $tempo_inicio = time();

  $pr2->aud_abre_db_e_attach('lasimca');
  $a_temp = $pr2->aud_sql2array("SELECT periodo || '_' || nome || '.txt' AS fname FROM o000 LIMIT 1;");
  $filewname = isset($a_temp[0]['fname']) ? $a_temp[0]['fname'] : "#ERRO#";

  if ($filewname == "#ERRO#") {
    werro_die("Não consegui nem gerar o nome do arquivo .txt... lasimca.db3 está presente e está com o registro 0000 preenchido?");
  } 

  if (!$handlew = fopen(PR_RESULTADOS . "/{$filewname}", 'w')) {
    werro_die("Nao foi possivel a gravacao do arquivo " . PR_RESULTADOS . "/{$filewname} - Feche o programa ou janela que está o usando");
  } 

    $sql = "
SELECT '0000' AS reg, lasimca, cod_ver, cod_fin, periodo, nome, cnpj, ie, cnae, cod_mun, ie_intima FROM o000;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT '0001' AS reg, ind_mov FROM o001;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT '0150' AS reg, 
    cod_part, nome, cod_pais, cnpj, ie, uf, cep, end, num, compl, bairro, cod_mun, fone
    FROM o150;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT '0300' AS reg, 
    cod_legal, desc, anex, art, inc, alin, prg, itm, ltr, obs
    FROM o300;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT '0990' AS reg, qtd_lin_0 FROM o990;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT '5001' AS reg, ind_mov FROM s001;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    // vou colocar em ordem mais pra frente, por isso estou colocando a ord aqui...
    $sql = "
SELECT ord, '5315' AS reg, 
    dt_emissao, tip_doc, ser, num_doc,
    cod_part, valor_sai, perc_crdout, valor_crdout
    FROM s315;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT ord, '5325' AS reg, 
    cod_legal, iva_utilizado, per_med_icms, cred_est_icms, icms_gera
    FROM s325;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT ord, '5330' AS reg, 
     valor_bc, icms_deb
    FROM s330;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT ord, '5350' AS reg, 
     valor_bc, icms_deb, num_decl_exp_ind
    FROM s350;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT '5990' AS reg, qtd_lin_c FROM s990;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT '9001' AS reg, ind_mov FROM q001;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT '9900' AS reg, reg_blc, qtd_reg_blc FROM q900;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT '9990' AS reg, qtd_lin_9 FROM q990;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT '9999' AS reg, qtd_lin FROM q999;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

  fclose($handlew);   
  wecho("\nFinalizado: ");
  wecho((time() - $tempo_inicio) . " segundos\r\n");
}

function sql2txtefd($pr, $a_dados, $tipo = 'ca') {
  // ou seja, cria uma ou mais linhas texto no formato efd, separado por |
  // $tipo == 'ca' ? NÃO começa com |... $tipo == 'efd' ? Começa com |
   //debug_log(print_r($a_dados, True));
  $linha = ($tipo == 'ca' ? '' : '|');
  foreach ($a_dados as $indl => $colunas) {
    foreach ($colunas as $indc => $valor) {
      //debug_log("valor:{$valor}  coluna:lasimca, {$colunas['reg']}, {$indc}, " . $pr->dicdados['lasimca'][$colunas['reg']][$indc]['tipo'] . ", " . $pr->dicdados['lasimca'][$colunas['reg']][$indc]['tam'] . ", " . $pr->dicdados['lasimca'][$colunas['reg']][$indc]['dec'] . "#", 200);
      $valor = utf8_decode($valor);	// bota em ISO 8859-1
      // detecta se é data... se for, muda de AAAA-MM-DD para DDMMAAAA
      if (substr($valor, 0, 2) == '20' && substr($valor, 4, 1) == '-' && substr($valor, 7, 1) == '-' && substr($valor, 5, 2) + 0 <= 12  && substr($valor, 5, 2) + 0 >= 1)
      	$valor = substr($valor, 8, 2) . substr($valor, 5, 2) . substr($valor, 0, 4);
      $tipo = $pr->dicdados['lasimca'][$colunas['reg']][$indc]['tipo'];
      $tam = $pr->dicdados['lasimca'][$colunas['reg']][$indc]['tam'];
      $dec = $pr->dicdados['lasimca'][$colunas['reg']][$indc]['dec'];
      $decimais = '';
      if ($dec <> '-') {
        $decimais = round($valor - floor($valor), $dec);
        if (strpos($decimais, '.') === False) $decimais .= '.0';
        $decimais = ',' . substr($decimais . str_repeat('0', $dec), 2, $dec);
      }
      if ($tipo == 'N') $valor = floor($valor);
      if ($tam <> '-') {
        if ($tipo == 'C') {
          $valor = str_repeat(' ', $tam) . $valor;
          $valor = mb_substr($valor, -$tam);  // pegas os últimos $tam caracteres
        }
        if ($tipo == 'N') {
            $valor = str_repeat('0', $tam) . $valor;
            $valor = mb_substr($valor, -$tam);  // pegas os últimos $tam caracteres
        } 
      }
      $valor .= $decimais;
      $linha .= str_replace('|', '', $valor) . '|';
    }
    $linha = substr($linha, 0, -1);  // retira o | final - não fiz com mb_ porque já está ISO 8859-1
    $linha .= "\n";  // coloca um newline unix
  }
  return $linha;
}

function lasimca_do_efd() {
  
  $pr2 = new Pr;  // classe principal, global

  wecho("\n\nDeletando e preenchendo novamente lasimca.db3 com dados de efd.db3:\r\n");
  $tempo_inicio = time();

  $pr2->aud_abre_db_e_attach('efd,lasimca');

  $pr2->aud_prepara("
-- Criação das tabelas do lasimca
-- #########   FORNECER O CNAE para lasimca.o000
-- Hipótese 0300 desc = 1, ou seja, Operações Interestaduais com alíquota 7%
DROP TABLE IF EXISTS lasimca.dgca_final;
CREATE TABLE lasimca.dgca_final AS
SELECT ord, dt_emissao, tip_doc, ser, num_doc, cod_part,
    sum(valor_sai) AS valor_sai, sum(valor_bc) AS valor_bc, avg(aliq) AS aliq, sum(icms_deb) AS icms_deb,
    avg(perc_crd_out) AS perc_crd_out, sum(valor_crd_out) AS valor_crd_out,
    cod_legal, avg(iva_utilizado) AS iva_utilizado, avg(per_med_icms) AS per_med_icms, sum(cred_est_icms) AS cred_est_icms
    FROM 
      (SELECT c100.ord AS ord, c100.dt_doc AS dt_emissao, 31 AS tip_doc, c100.ser AS ser, c100.num_doc AS num_doc,  c100.cod_part AS cod_part, 
    dgca_analit.vl_opr AS valor_sai, dgca_analit.vl_bc_icms AS valor_bc, dgca_analit.aliq_icms AS aliq, dgca_analit.vl_icms AS icms_deb, 
    0 AS perc_crd_out, 0 AS valor_crd_out,
    CASE WHEN dgca_analit.valcon_s = '+' AND dgca_analit.aliq_icms = 7 THEN 1 ELSE 0 END AS cod_legal, 
    0.8400 AS iva_utilizado, 16.7440 AS per_med_icms,
           round(dgca_analit.vl_opr / (1 + 0.8400) * 16.7440 / 100, 2) AS cred_est_icms
          FROM dgca_analit
          LEFT OUTER JOIN  c190 ON c190.ord = dgca_analit.ord
          LEFT OUTER JOIN c100 ON c100.ord = c190.ordC100
          WHERE c190.cfop > 5000
          ORDER BY dt_emissao)
GROUP BY ord, cod_legal;
DELETE FROM lasimca.o000;
INSERT INTO lasimca.o000
SELECT ord, 'LASIMCA' AS lasimca, 1 AS cod_ver, 1 AS cod_fin, substr(dt_ini, 6, 2) || substr(dt_ini, 1, 4) AS periodo,
    nome AS nome, cnpj AS cnpj, ie AS ie, 'CNAE' AS cnae, cod_mun AS cod_mun, ie AS ie_intima
    FROM main.o000;
DELETE FROM lasimca.o001;
INSERT INTO lasimca.o001
SELECT ord, 0 FROM main.o005;
DELETE FROM lasimca.o150;
INSERT INTO lasimca.o150
SELECT ord, cod_part, nome, cod_pais, cnpj, ie, main.tab_munic.UF AS uf, '00000000' AS cep, end, num, compl, bairro, cod_mun, Null AS fone 
     FROM main.o150
     LEFT OUTER JOIN main.tab_munic ON main.tab_munic.cod = main.o150.cod_mun
     WHERE main.o150.cod_part  IN (SELECT DISTINCT cod_part FROM dgca_final);
DELETE FROM lasimca.o300;
INSERT INTO lasimca.o300 VALUES (Null, 1, 1, '', '71', 'I', '', '', '0', '', 'Operações Interestaduais com alíquota 7%');
INSERT INTO lasimca.o300 VALUES (Null, 2, 2, '', '71', 'I', '', '', '0', '', 'Operações Interestaduais com alíquota 12%');
INSERT INTO lasimca.o300 VALUES (Null, 3, 3, '', '71', 'I', '', '', '0', '', 'Operações Internas com alíquota 7%');
INSERT INTO lasimca.o300 VALUES (Null, 4, 4, '', '71', 'I', '', '', '0', '', 'Operações Internas');
INSERT INTO lasimca.o300 VALUES (Null, 5, 5, '', '71', 'I', '', '', '0', '', 'Outras');
INSERT INTO lasimca.o300 VALUES (Null, 6, 6, '', '71', 'II', '', '', '0', '', 'Redução de Base de Cálculo');
INSERT INTO lasimca.o300 VALUES (Null, 7, 7, '', '71', 'III', '', '', '0', '', 'Saídas sem pagamento de Imposto - Exportação');
INSERT INTO lasimca.o300 VALUES (Null, 8, 8, '', '71', 'III', '', '', '0', '', 'Saídas sem pagamento de Imposto - Exportação Indireta');
INSERT INTO lasimca.o300 VALUES (Null, 9, 9, '', '71', 'III', '', '', '0', '', 'Saídas sem pagamento de Imposto - ZF Manaus');
INSERT INTO lasimca.o300 VALUES (Null, 10, 10, '', '71', 'III', '', '', '0', '', 'Saídas sem pagamento de Imposto - Diferimento');
INSERT INTO lasimca.o300 VALUES (Null, 11, 11, '', '71', 'III', '', '', '0', '', 'Saídas sem pagamento de Imposto - Isenção');
INSERT INTO lasimca.o300 VALUES (Null, 12, 12, '', '71', 'III', '', '', '0', '', 'Saídas sem pagamento de Imposto - ST');
INSERT INTO lasimca.o300 VALUES (Null, 13, 13, '', '71', 'III', '', '', '0', '', 'Saídas sem pagamento de Imposto - Outras');
DELETE FROM lasimca.o990;
-- Será inserido o990 no final, junto com q900
DELETE FROM lasimca.s001;
INSERT INTO lasimca.s001
SELECT Null AS ord, 0;
DELETE FROM lasimca.s315;
INSERT INTO lasimca.s315
SELECT ord, dt_emissao, tip_doc, ser, num_doc, cod_part, valor_sai, perc_crd_out, valor_crd_out FROM dgca_final;
DELETE FROM lasimca.s320;
--- INSERT INTO lasimca.s320 ... Não fazer devolução por enquanto
DELETE FROM lasimca.s325;
INSERT INTO lasimca.s325
SELECT ord, ord, cod_legal, iva_utilizado, per_med_icms, cred_est_icms, (cred_est_icms - icms_deb) AS icms_gera 
    FROM dgca_final
    WHERE cod_legal > 0 AND (cred_est_icms - icms_deb) > 0;
DELETE FROM lasimca.s330;
INSERT INTO lasimca.s330
SELECT ord, ord, valor_bc, icms_deb
    FROM dgca_final
    WHERE cod_legal > 0 AND (cred_est_icms - icms_deb) > 0;
DELETE FROM lasimca.s350;
INSERT INTO lasimca.s350
SELECT ord, ord, valor_bc, icms_deb, Null AS num_decl_exp_ind
    FROM dgca_final
    WHERE cod_legal = 0;
DELETE FROM lasimca.s990;
-- Será inserido s990 no final, junto com q900
DELETE FROM lasimca.q001;
INSERT INTO lasimca.q001
SELECT Null AS ord, 0;
DROP TABLE IF EXISTS lasimca.q900aux;
CREATE TABLE lasimca.q900aux AS
SELECT Null AS ord, reg_blc, qtd AS qtd_lin_0 FROM 
    (
    SELECT '0000' AS reg_blc, count(*) AS qtd FROM lasimca.o000
    UNION ALL
    SELECT '0001' AS reg_blc, count(*) AS qtd FROM lasimca.o001
    UNION ALL
    SELECT '0150' AS reg_blc, count(*) AS qtd FROM lasimca.o150
    UNION ALL
    SELECT '0300' AS reg_blc, count(*) AS qtd FROM lasimca.o300
    UNION ALL
    SELECT '0990' AS reg_blc, 1 AS qtd
    UNION ALL
    SELECT '5001' AS reg_blc, count(*) AS qtd FROM lasimca.s001
    UNION ALL
    SELECT '5315' AS reg_blc, count(*) AS qtd FROM lasimca.s315
    UNION ALL
    SELECT '5320' AS reg_blc, count(*) AS qtd FROM lasimca.s320
    UNION ALL
    SELECT '5325' AS reg_blc, count(*) AS qtd FROM lasimca.s325
    UNION ALL
    SELECT '5330' AS reg_blc, count(*) AS qtd FROM lasimca.s330
    UNION ALL
    SELECT '5335' AS reg_blc, count(*) AS qtd FROM lasimca.s335
    UNION ALL
    SELECT '5340' AS reg_blc, count(*) AS qtd FROM lasimca.s340
    UNION ALL
    SELECT '5350' AS reg_blc, count(*) AS qtd FROM lasimca.s350
    UNION ALL
    SELECT '5990' AS reg_blc, 1 AS qtd
    UNION ALL
    SELECT '9001' AS reg_blc, count(*) AS qtd FROM lasimca.q001
    UNION ALL
    SELECT '9990' AS reg_blc, 1 AS qtd
    UNION ALL
    SELECT '9999' AS reg_blc, 1 AS qtd)
WHERE qtd > 0;
DELETE FROM lasimca.q900;
INSERT INTO lasimca.q900
SELECT * FROM 
    (SELECT * FROM q900aux
     UNION ALL
     SELECT Null AS ord, '9900' AS reg_blc, count(*) + 1 FROM q900aux)
ORDER BY reg_blc;
-- Já houve DELETE FROM lá acima
INSERT INTO lasimca.o990
SELECT Null AS ord, sum(qtd_lin_0) FROM q900aux WHERE q900aux.reg_blc < '5000';  
-- Já houve DELETE FROM lá acima
INSERT INTO lasimca.s990
SELECT Null AS ord, sum(qtd_lin_0) FROM q900aux WHERE q900aux.reg_blc >= '5000' AND q900aux.reg_blc < '9000';
DELETE FROM lasimca.q990;
INSERT INTO lasimca.q990
SELECT Null AS ord, count(*) + 1 + 3 FROM q900aux;
DELETE FROM lasimca.q999;
INSERT INTO lasimca.q999
SELECT Null AS ord, sum(qtd) AS qtd FROM
    (SELECT sum(qtd_lin_0) AS qtd FROM q900aux
    UNION ALL
    SELECT count(*) + 1 AS qtd FROM q900aux);
");

  wecho("\nFinalizado: ");
  wecho((time() - $tempo_inicio) . " segundos\r\n");

}



?>