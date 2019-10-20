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
      1 => "Gera arquivo .txt a partir de lasimca.db3",
      2 => "CNAE do registro 0000 7 dígitos (Exemplo: 2229301):[GtkEntry]",
      3 => "IVA utilizado 4 casas dec (Exemplo: 0,8400):[GtkEntry]",
      4 => "PMC utilizado 4 casas dec (Exemplo: 12,5594):[GtkEntry]",
      5 => "Código de Finalidade reg 0000 (1 ou 3):[GtkEntry]",
  );
  $val_entry[2] = "0000000";
  $val_entry[3] = "0,8400";
  $val_entry[4] = "12,5594";
  $val_entry[5] = "1";

  //debug_log(print_r($lista_opcoes, True));

    foreach ($lista_opcoes as $indice => $valor) {
        if (mb_strpos($valor, '[GtkEntry]') === False) {
            if ($indice == 0) $dialog->vbox->pack_start(new GtkHSeparator(), false, false, 3);
            $chkbuttons[$indice] = new GtkCheckButton(str_replace('_', '__', $valor));
            $chkbuttons[$indice]->set_active(False);
            $dialog->vbox->pack_start($chkbuttons[$indice], false, false, 3);
        } else {
            $hboxes[$indice] = new GtkHBox();
            $labels[$indice] = new GtkLabel(str_replace('[GtkEntry]', '', $valor));
            $entrys[$indice] = new GtkEntry($val_entry[$indice]);
            $hboxes[$indice]->pack_start($labels[$indice], false, false, 0);
            $hboxes[$indice]->pack_start($entrys[$indice], false, false, 0);
            $dialog->vbox->pack_start($hboxes[$indice], false, false, 3);
        }
    }
  $dialog->add_button("Inverter Seleção", 100);
  $dialog->add_button(Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL);
  $dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);
  $dialog->set_has_separator(True);
  $dialog->show_all();
  do {
    $response_id = $dialog->run();
    if ($response_id == 100) {
      for($indice=0; $indice<=1; $indice++) {
        $chkbuttons[$indice]->set_active(!$chkbuttons[$indice]->get_active());
      }
    }
  } while ($response_id == 100);
  if ($response_id != Gtk::RESPONSE_OK) {
    $dialog->destroy();
    return;
  }
  $val_entry[2] = $entrys[2]->get_text();
  $val_entry[3] = $entrys[3]->get_text();
  $val_entry[4] = $entrys[4]->get_text();
  $val_entry[5] = $entrys[5]->get_text();
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
             CASE WHEN cfop > 7000 OR cfop BETWEEN 3201 AND 3299 OR cfop IN (5501, 5502, 6501, 6502) THEN '3.1' ELSE
           CASE WHEN aliq_icms = 0 THEN '3.x' ELSE '1.x' END
             END
  END
   END AS art71, * FROM
(SELECT 
    aamm, cst_icms, aliq_icms, cfop, 
    sum(saidas) AS saidas, sum(entradas_valcon + entradas_bcicms) AS entradas,
    sum(entradas_valcon) AS entradas_valcon, sum(entradas_bcicms) AS entradas_bcicms, 
    sum(pmc_den) AS pmc_den, 
    sum(CASE WHEN cfop < 5000 THEN -vl_opr ELSE vl_opr END) AS vl_opr, sum(CASE WHEN cfop < 5000 THEN -vl_red_bc ELSE vl_red_bc END) AS vl_red_bc, 
    sum(CASE WHEN cfop < 5000 THEN -vl_bc_icms ELSE vl_bc_icms END) AS vl_bc_icms, sum(CASE WHEN cfop < 5000 THEN -vl_icms ELSE vl_icms END) AS vl_icms, sum(CASE WHEN cfop < 5000 THEN -vl_ipi ELSE vl_ipi END) AS vl_ipi
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
	$pr->abre_excel_sql('dgca_sintet', 'DGCa_sintética (até coluna K, resumo DGCa_analítico) - Revise a primeira coluna e monte os DGCAs - Colunas K em diante, CFOP < 5000, valores negativos', $sql, $col_format, $cabec, $form_final);

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
    lasimca_do_efd($val_entry); // ver ao final deste arquivo php
  }

  if ($chkbuttons[1]->get_active()) {
    lasimca2txt(); // ver ao final deste arquivo php
  }

}

function lasimca2txt() {
    $filername = lasimca2txt_pt1();
    // em seguida, faz as correções finais, que são
    $pr2 = new Pr;  // classe principal, global
    $pr2->aud_abre_db_e_attach('lasimca');
    if (!$handler = fopen($filername, 'r')) {
        werro_die("Nao foi possivel a leitura do arquivo {$filername}...");
    } 

    $filewname = mb_substr($filername, 0, -4) . "_final.txt";

    if (!$handlew = fopen($filewname, 'w')) {
        werro_die("Nao foi possivel a gravacao do arquivo {$filewname} - Feche o programa ou janela que está o usando");
    } 

    while(!feof($handler)) {
        $linha = fgets($handler);
        // correção de linhas 0150 exportação que exigem dados vazios
        if (mb_substr($linha, 0, 5) == '0150|') $linha = str_replace("|00000000000000||  |00000000|||||0000000|", "||||||||||", $linha);
        fputs($handlew, $linha);

        if (mb_substr($linha, 0, 5) == '5001|') {
            // se acabei de gravar o 5001, então o que vem depois são os registros 5315, 5325, 5330, 5350, etc... 
            // então, para gravar, vamos primeiro colocar em ordem!
            // parte 1: leitura e gravação no sqlite
            $pr2->db->exec('DROP TABLE IF EXISTS ord_grupo5;
  ');
            $pr2->db->exec('CREATE TABLE ord_grupo5 (
    ord int, dados txt);
  ');
            $pr2->db->exec('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
            do {
                $linha = fgets($handler);
                if (mb_substr($linha, 0, 5) == '5990|') {
                    $grupo5_continua= False;
                } else {
                    $grupo5_continua= True;
                    $iord = mb_substr($linha, 0, 11) + 0;
                    $dados = $pr2->db->escapeString(mb_substr($linha, 13));
                    $insert_query = <<<EOD
INSERT INTO ord_grupo5 VALUES(
{$iord}, '{$dados}'
 )
EOD;
                    $pr2->db->exec($insert_query);
                }
            } while ($grupo5_continua);
            $pr2->db->exec('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
            //agora indexa e salva o grupo 5 em ordem
            $pr2->db->exec('CREATE INDEX "ord_grupo5_prim" ON ord_grupo5 (ord ASC);');

            $result = $pr2->db->query('SELECT dados FROM ord_grupo5 ORDER BY ord;');
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                fputs($handlew, $row['dados']);
            }

            fputs($handlew, $linha);  // a linha 5990
        }
    }

    fclose($handlew);   
    fclose($handler);   
    wecho("\nFinalizada parte 2 ");

}


function lasimca2txt_pt1() {

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
SELECT ord, '5335' AS reg, 
     num_decl_exp, comp_oper
    FROM s335;
";
    fputs($handlew, sql2txtefd($pr2, $pr2->aud_sql2array($sql)));

    $sql = "
SELECT ord, '5340' AS reg, 
     data_doc_ind, num_doc_ind, ser_doc_ind, num_decl_exp_ind
    FROM s340;
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
  wecho("\nFinalizada parte 1: ");
  wecho((time() - $tempo_inicio) . " segundos\r\n");

  return PR_RESULTADOS . "/{$filewname}";
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
      if ($tipo == 'N' && !is_numeric($valor)) {
        // há a necessidade de converter para 100% numerico. Um caso clássico é o campo 
        // abaixo há um problema... o SPED aceita cod_part não numérico. Mas lasimca só quer cod_part numerico. Há uma correçao mais ou menos na função sql2txtefd
        $valnum = '';
        for($i=0; $i<mb_strlen($valor); $i++) {
          $letra = mb_substr($valor, $i, 1);
          $valnum .= (is_numeric($letra) ? $letra : ord($letra));
        }
        $valor = $valnum;
      }
      // abaixo mudei porque em números muito grandes estava vindo notação científica
      //if ($tipo == 'N') $valor = floor($valor);
      if ($tipo == 'N') {
        $posvirg = mb_strpos($valor, '.');
        if ($posvirg  !== False) $valor = mb_substr($valor, 0, $posvirg);
      }
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

function lasimca_do_efd($val_entry) {
  
  $pr2 = new Pr;  // classe principal, global

  wecho("\n\nDeletando e preenchendo novamente lasimca.db3 com dados de efd.db3:\nValores Fornecidos:\nCNAE: {$val_entry[2]}\nIVA: {$val_entry[3]}\nPMC: {$val_entry[4]}\ncod_fin: {$val_entry[5]}\n");
  $tempo_inicio = time();

  $pr2->aud_abre_db_e_attach('efd,lasimca');

  $o000_cnae = str_replace('-', '', str_replace('/', '', trim($val_entry[2])));
  $s325_iva_utilizado = str_replace(',', '.', trim($val_entry[3])) + 0;
  $s325_per_med_icms = str_replace(',', '.', trim($val_entry[4])) + 0;
  $cod_fin = trim($val_entry[5]) + 0;

  $limitador_red_bc = "dgca_analit.aliq_icms = 7";

  $pr2->aud_prepara("
-- Criação das tabelas do lasimca
-- Primeiro será criada lasimca.dgca_semifinal, com todas as hipóteses possíveis
-- As hipóteses automáticas (campo cod_legal) atualmente são:
--    cod_legal = 0 ? Não geradora porque campo saidas = 0
--    cod_legal = Null ? campo saidas <> 0 mas não sei classificar, ou alíquota de 18%
--    Hipótese 0300 desc = 1, Operações Interestaduais com alíquota 7%
--    Hipótese 0300 desc = 2, Operações Interestaduais com alíquota 12%
--    Hipótese 0300 desc = 3, Operações Internas com alíquota 7%
--    Hipótese 0300 desc = 4, Operações Internas (aqui processa tudo que é diferente de 7%)
--    Hipótese 0300 desc = 5, Outras (processado aqui em operações interestaduais com alíquotas diferentes de 7% e 12%)
--    Hipótese 0300 desc = 6, Qualquer hipótese de Redução de Base de Cálculo, limitado por $limitador_red_bc
--    Hipótese 0300 desc = 7, Saídas sem pagamento de Imposto - Exportação
--    Hipótese 0300 desc = 8, Saídas sem pagamento de Imposto - Exportação Indireta (Desativado por enquanto, joguei pra 7)
--    Hipótese 0300 desc= 11, Saídas sem pagamento de Imposto - Isenção
DROP TABLE IF EXISTS lasimca.dgca_semifinal;
CREATE TABLE lasimca.dgca_semifinal AS
SELECT ord, dt_emissao, tip_doc, ser, num_doc, cod_part,
    sum(valor_sai) AS valor_sai, sum(valor_bc) AS valor_bc, avg(aliq) AS aliq, sum(icms_deb) AS icms_deb,
    avg(perc_crd_out) AS perc_crd_out, sum(valor_crd_out) AS valor_crd_out,
    cod_legal, avg(iva_utilizado) AS iva_utilizado, avg(per_med_icms) AS per_med_icms, sum(cred_est_icms) AS cred_est_icms
    FROM 
      (SELECT dgca_analit.ord AS ord, c100.dt_doc AS dt_emissao, 31 AS tip_doc, c100.ser AS ser, c100.num_doc AS num_doc,  c100.cod_part AS cod_part, 
    dgca_analit.vl_opr AS valor_sai, dgca_analit.vl_bc_icms AS valor_bc, dgca_analit.aliq_icms AS aliq, dgca_analit.vl_icms AS icms_deb, 
    0 AS perc_crd_out, 0 AS valor_crd_out,
     CASE WHEN saidas = 0 THEN 0 ELSE
        CASE WHEN dgca_analit.vl_red_bc > 0 THEN 
           CASE WHEN {$limitador_red_bc} THEN 6 ELSE Null END
        ELSE
            CASE WHEN dgca_analit.cfop > 7000 OR dgca_analit.cfop BETWEEN 3201 AND 3299 THEN 7 ELSE
                CASE WHEN dgca_analit.cfop IN (5501, 5502, 6501, 6502) THEN 7 ELSE
                    CASE WHEN dgca_analit.aliq_icms = 0 AND dgca_analit.vl_icms = 0 THEN 11 ELSE 
                        CASE WHEN (dgca_analit.cfop BETWEEN 6001 AND 6999) AND dgca_analit.aliq_icms = 7 THEN 1 ELSE 
                            CASE WHEN (dgca_analit.cfop BETWEEN 6001 AND 6999) AND dgca_analit.aliq_icms = 12 THEN 2 ELSE 
                              CASE WHEN (dgca_analit.cfop BETWEEN 5001 AND 5999) AND dgca_analit.aliq_icms = 7 THEN 3 ELSE 
                                CASE WHEN (dgca_analit.cfop BETWEEN 5001 AND 5999) THEN 4 ELSE 
                                  CASE WHEN (dgca_analit.cfop BETWEEN 6001 AND 6999) THEN 5 ELSE Null
                                  END
                                END
                              END
                            END
                        END
                    END
                END
            END
        END
    END AS cod_legal, 
    {$s325_iva_utilizado} AS iva_utilizado, {$s325_per_med_icms} AS per_med_icms,
           round(dgca_analit.vl_opr / (1 + {$s325_iva_utilizado}) * {$s325_per_med_icms} / 100, 2) AS cred_est_icms
          FROM dgca_analit
          LEFT OUTER JOIN  c190 ON c190.ord = dgca_analit.ord
          LEFT OUTER JOIN c100 ON c100.ord = c190.ordC100
          WHERE c190.cfop > 5000
          ORDER BY dt_emissao)
GROUP BY ord, cod_legal;
-- Agora é necessário descobrir, afinal, quais as hipóteses cod_legal que dão crédito Acumulado, ou seja,
-- aqueles em que na somatória de (cred_est_icms - icms_deb) são positivos
-- Tudo o que não der, será transformado para não gerador (cod_legal = 0)
DROP TABLE IF EXISTS lasimca.hip_cred_acum;
CREATE TABLE lasimca.hip_cred_acum AS
SELECT cod_legal FROM 
    (SELECT cod_legal, sum(icms_deb), sum(cred_est_icms),  sum(cred_acum) AS cred_acum FROM 
        (SELECT cod_legal, icms_deb, cred_est_icms, cred_est_icms - icms_deb AS cred_acum
            FROM dgca_semifinal WHERE cod_legal <> 0)
    GROUP BY cod_legal
    HAVING cred_acum > 0);
-- o dgca_final é o dgca_semifinal agrupado por Data da Emissão do Documento Fiscal, Tipo, Série, Número do Documento e Hipótese de Geração, porque não podem estar duplicados no arquivo
-- aqui cod_legal = 0 será para qualquer hipótese não geradora de crédito acumulado!
-- também será cod_legal = 0 quando, mesmo em hipótese geradora de crédito acumulado, cred_est_icms <= icms_deb!
DROP TABLE IF EXISTS lasimca.dgca_final;
CREATE TABLE lasimca.dgca_final AS
SELECT ord, dt_emissao, tip_doc, ser, num_doc, cod_part, sum(valor_sai) AS valor_sai, sum(valor_bc) AS valor_bc, aliq, sum(icms_deb) AS icms_deb, 
    perc_crd_out, sum(valor_crd_out) AS valor_crd_out, 
    CASE WHEN cred_est_icms <= icms_deb THEN 0 ELSE cod_legal END AS cod_legal, 
    iva_utilizado, per_med_icms, sum(cred_est_icms) AS cred_est_icms
    FROM 
    (SELECT  ord, dt_emissao, tip_doc, ser, num_doc, cod_part, valor_sai, valor_bc, aliq, icms_deb, perc_crd_out, valor_crd_out, 
        CASE WHEN hip_cred_acum.cod_legal IS Null THEN 0 ELSE hip_cred_acum.cod_legal END AS cod_legal, iva_utilizado, per_med_icms, cred_est_icms 
        FROM dgca_semifinal
        LEFT OUTER JOIN hip_cred_acum ON hip_cred_acum.cod_legal = dgca_semifinal.cod_legal)
GROUP BY dt_emissao, tip_doc, ser, num_doc, cod_legal;
DELETE FROM lasimca.o000;
INSERT INTO lasimca.o000
SELECT ord, 'LASIMCA' AS lasimca, 1 AS cod_ver, {$cod_fin} AS cod_fin, substr(dt_ini, 6, 2) || substr(dt_ini, 1, 4) AS periodo,
    nome AS nome, cnpj AS cnpj, ie AS ie, {$o000_cnae} AS cnae, cod_mun AS cod_mun, ie AS ie_intima
    FROM main.o000;
DELETE FROM lasimca.o001;
INSERT INTO lasimca.o001
SELECT ord, 0 FROM main.o005;
DROP TABLE IF EXISTS lasimca.dcga_final_cod_part_cnpj;
CREATE TABLE lasimca.dcga_final_cod_part_cnpj AS
SELECT cod_part, cnpj FROM
    (SELECT DISTINCT dgca_final.cod_part AS cod_part,
        main.o150.cnpj AS cnpj,
        round(dgca_final.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(dgca_final.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM dgca_final
        LEFT OUTER JOIN main.o150 ON o150.cod_part = dgca_final.cod_part AND main.o150.ord > ordmin AND main.o150.ord < ordmax);
-- lasimca.o150 é baseado no efd.o150 da seguinte forma:
--   a1) - tem que haver uma linha de 0150 com o cnpj do emissor do arquivo (em 0000) - se não houver, cria
--   a2) Seleciona somente os cod_part necessários, ou seja, o que estao em dgca_final
--   b) Atenção: em lasimca.o150 o campo cnpj é na verdade cnpj_cpf - o cpf estará com 14 dígitos, ou seja, três zeros 000 à esquerda
--   c) Se o país for Brasil (cod_pais = 1058), não pode haver duplicidades de cod_part com mesmas combinações cnpj_cpf e ie
--   d) Se o país não for Brasil (cod_pais <> 1058), não se preocupe com nada, porque todos os campos exceto cod_part, nome e cod_pais DEVEM ESTAR VAZIOS
-- entao, não se esquecer, ao final, de converter a campo cod_part de 5315 com o código correto de lasimca.o150
DROP TABLE IF EXISTS lasimca.o150tmp_a_b;
-- selecao dos itens a2 e b acima
-- o item a1 acima será inserido no final deste trecho, diretamente INTO lasimca.o150
CREATE TABLE lasimca.o150tmp_a_b AS
SELECT cod_part, cod_pais, 
    CASE WHEN cpf <> '' AND cpf > 0 THEN cpf ELSE cnpj END AS cnpj_cpf, ie
    FROM main.o150
    WHERE main.o150.cod_part  IN (SELECT cod_part FROM lasimca.dcga_final_cod_part_cnpj);
-- selecao do item c acima
DROP TABLE IF EXISTS lasimca.o150tmp_c;
CREATE TABLE lasimca.o150tmp_c AS
SELECT cod_part, cod_pais, cnpj_cpf, ie
    FROM lasimca.o150tmp_a_b
    WHERE cod_pais = 1058
    GROUP BY cnpj_cpf, ie;
-- a tabela abaixo é a junçao de tmp_a_b com tmp_c correlacionando cod_part_efd com o cod_part_lasimca
DROP TABLE IF EXISTS lasimca.o150efd_lasimca;
CREATE TABLE lasimca.o150efd_lasimca AS
SELECT lasimca.o150tmp_a_b.cod_part AS cod_part_efd, lasimca.o150tmp_c.cod_part AS cod_part_lasimca, 
    lasimca.o150tmp_a_b.cod_pais AS cod_pais, o150tmp_a_b.cnpj_cpf AS cnpj_cpf, o150tmp_a_b.ie AS ie
    FROM lasimca.o150tmp_a_b
    LEFT OUTER JOIN lasimca.o150tmp_c ON lasimca.o150tmp_c.cnpj_cpf = lasimca.o150tmp_a_b.cnpj_cpf AND lasimca.o150tmp_c.ie = lasimca.o150tmp_a_b.ie
    WHERE lasimca.o150tmp_a_b.cod_pais = 1058
UNION ALL
SELECT lasimca.o150tmp_a_b.cod_part AS cod_part_efd, lasimca.o150tmp_a_b.cod_part AS cod_part_lasimca, 
    lasimca.o150tmp_a_b.cod_pais AS cod_pais, o150tmp_a_b.cnpj_cpf AS cnpj_cpf, o150tmp_a_b.ie AS ie
    FROM lasimca.o150tmp_a_b
    WHERE lasimca.o150tmp_a_b.cod_pais <> 1058;
-- abaixo há um problema... o SPED aceita cod_part não numérico. Mas lasimca só quer cod_part numerico. Há uma correçao mais ou menos na função sql2txtefd
DELETE FROM lasimca.o150;
-- inserção do item a1 mais acima
INSERT INTO lasimca.o150
SELECT main.o000.ord AS ord, main.o000.cnpj AS cod_part, main.o000.nome AS nome, 1058 AS cod_pais, main.o000.cnpj AS cnpj, main.o000.ie AS ie,
    main.o000.uf AS uf, '00000000' AS cep, Null AS end, Null AS num, Null AS compl, Null AS bairro, main.o000.cod_mun AS cod_mun, Null AS fone
    FROM main.o000
    WHERE main.o000.cnpj NOT IN (SELECT cod_part FROM lasimca.dcga_final_cod_part_cnpj);
-- inserção dos demais dados de o150
INSERT INTO lasimca.o150
SELECT ord, cod_part, nome, cod_pais, 
    CASE WHEN cod_pais = 1058 THEN cnpj ELSE '' END AS cnpj, 
    CASE WHEN cod_pais = 1058 THEN ie ELSE '' END AS  ie, 
    CASE WHEN cod_pais = 1058 THEN uf ELSE '' END AS uf, 
    CASE WHEN cod_pais = 1058 THEN cep ELSE '' END AS cep, 
    CASE WHEN cod_pais = 1058 THEN end ELSE '' END AS end, 
    CASE WHEN cod_pais = 1058 THEN num ELSE '' END AS num, 
    CASE WHEN cod_pais = 1058 THEN compl ELSE '' END AS compl, 
    CASE WHEN cod_pais = 1058 THEN bairro ELSE '' END AS bairro, 
    CASE WHEN cod_pais = 1058 THEN cod_mun ELSE '' END AS cod_mun, 
    CASE WHEN cod_pais = 1058 THEN fone ELSE '' END AS fone 
   FROM 
      (SELECT ord, cod_part, nome, cod_pais, CASE WHEN cpf <> '' AND cpf > 0 THEN cpf ELSE cnpj END AS cnpj, 
            ie, main.tab_munic.UF AS uf, '00000000' AS cep, end, num, compl, bairro, cod_mun, Null AS fone 
           FROM main.o150
           LEFT OUTER JOIN main.tab_munic ON main.tab_munic.cod = main.o150.cod_mun
           WHERE main.o150.cod_part  IN (SELECT DISTINCT cod_part_lasimca FROM lasimca.o150efd_lasimca));
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
SELECT dgca_final.ord, dgca_final.dt_emissao, dgca_final.tip_doc, dgca_final.ser, dgca_final.num_doc, 
    o150efd_lasimca.cod_part_lasimca AS cod_part, dgca_final.valor_sai, dgca_final.perc_crd_out, dgca_final.valor_crd_out 
    FROM dgca_final
    LEFT OUTER JOIN lasimca.o150efd_lasimca ON lasimca.o150efd_lasimca.cod_part_efd = dgca_final.cod_part;
DELETE FROM lasimca.s320;
--- INSERT INTO lasimca.s320 ... Não fazer devolução por enquanto
DELETE FROM lasimca.s325;
INSERT INTO lasimca.s325
SELECT ord, ord, cod_legal, iva_utilizado, per_med_icms, cred_est_icms, (cred_est_icms - icms_deb) AS icms_gera 
    FROM dgca_final
    WHERE cod_legal > 0;
DELETE FROM lasimca.s330;
INSERT INTO lasimca.s330
SELECT ord, ord, valor_bc, icms_deb
    FROM dgca_final
    WHERE cod_legal > 0 AND icms_deb > 0;
DELETE FROM lasimca.s335;
-- esse número 160001001001 abaixo é totalmente aleatório, é só porque há a necessidade de preenchimento de algum valor em num_decl_exp do registro 5335
INSERT INTO lasimca.s335
SELECT ord, ord, 160001001001, Null
    FROM dgca_final
    WHERE cod_legal IN (7, 9);
DELETE FROM lasimca.s340;
INSERT INTO lasimca.s340
SELECT ord, ord, Null, Null, Null, Null
    FROM dgca_final
    WHERE cod_legal = 8;
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