<?php

function leitura_ladca($arquivo_ladca) {

	global $pr, $options;

	// Regras para o nome do arquivo db3 e também dos arquivos xls gerados na conversão
	// Se a opção "um arquivo excel para cada arquivo em fontes" estiver setada, nome = ladca (ladca.db3, ladca.xls)
	// Caso contrário, nome = ladca_{$arquivo_ladca}
	$nomarqaux = explode("/", $arquivo_ladca);
	if ($options['arqs_sep']) $nomarq = "ladca_" . substr($nomarqaux[count($nomarqaux)-1], 0, -4); else $nomarq = "ladca";

	if (!file_exists(PR_TMP . "/{$nomarq}.db3")) { 

	if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
	} else {
		werro_die('Falha ao criar Banco de Dados ladca.db3');
	}  

	$db->query('PRAGMA encoding = "UTF-8";');

	cria_tabela_cfopd($db);

	// Usado em Resumo de LADCA_Dados
	$db->query('CREATE TABLE conta_reg (
	  arq, aaaamm, reg, qtd int)
	');
  
	// Código do Registro, proc = Processa Sim ou Não, Nível, Descrição, Obrigatoriedade, Ocorrência
	$createtable = "
CREATE TABLE descri_reg (reg text, proc text, nivel int, descri text, obrig text, ocorr text);
CREATE INDEX descri_reg_reg ON descri_reg (reg ASC);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/LADCA_Reg_Descri.txt', 'descri_reg');	
  
	// Tabela 4.1.1 - Tipos de Documentos Fiscais - Ver arquivo Tab4.1.1.txt em PR_RES . '/tabelas'
	$createtable = "
CREATE TABLE tab4_1_1 (cod text, descri text, mod text);
CREATE INDEX tab4_1_1_cod ON tab4_1_1 (cod ASC);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/Tab4.1.1.txt', 'tab4_1_1');	

	// Tabela 4.1 - GÊNERO DO ITEM E DA OPERAÇÃO - Ver arquivo LADCA_Tab4.1.txt PR_RES . '/tabelas'
	$createtable = "
CREATE TABLE tab4_1 (cod text, descri text);
CREATE INDEX tab4_1_cod ON tab4_1 (cod ASC);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/LADCA_Tab4.1.txt', 'tab4_1');	

	// Tabela 6.1 - TABELA DE CODIFICAÇÃO DOS LANÇAMENTOS - Ver arquivo LADCA_Tab6.1.txt em PR_RES . '/tabelas'
	$createtable = "
CREATE TABLE tab6_1 (cod text, ori_des text, descri text);
CREATE INDEX tab6_1_cod ON tab6_1 (cod ASC);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/LADCA_Tab6.1.txt', 'tab6_1');	

	// Tabela tab_munic
	$createtable = "
CREATE TABLE tab_munic (cod int primary key, uf text, munic text);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/Tabela_Municípios.txt', 'tab_munic');	
  
  
	$db->query('CREATE TABLE o000 (
	  ord int primary key,
	  ladca text,
	  cod_ver int,
	  cod_fin int,
	  periodo text,
	  nome text,
	  cnpj int,
	  ie text,
	  cnae int,
	  cod_mun text,
	  op_crd_out int,
	  ie_intima )
	');

	$db->query('CREATE TABLE o150 (
	  ord int primary key,
	  cod_part text,
	  nome text,
	  cod_pais int,
	  cnpj int,
	  ie text,
	  uf text,
	  cep text,
	  end text,
	  num text,
	  compl text,
	  bairro text,
	  cod_mun text,
	  fone text)
	');
	$db->query('CREATE INDEX "o150_reg_prim" ON o150 (cod_part ASC)');

  // 0151 - Tabela de ie de contribuinte substituto
	$db->query('CREATE TABLE o151 (
	  ord int primary key, ordo150 int, ie_subs )
	');

  // 0200 - Tabela de Identificação do Item (Produtos e Serviços)
	$db->query('CREATE TABLE o200 (
	  ord int primary key,
	  cod_item text, descr_item text, uni text, cod_gen int)
	');
	$db->query('CREATE INDEX "o200_reg_prim" ON o200 (cod_item ASC)');
  
  // 0205 - Alteração do Item
	$db->query('CREATE TABLE o205 (
	  ord int primary key, ordo200 int,
	  cod_ant_item, descr_ant_item, pe_ini, pe_fim)
	');

  // 0300 - ENQUADRAMENTO LEGAL DA OPERAÇÃO/PRESTAÇÃO GERADORA DE CRÉDITO ACUMULADO DO ICMS
	$db->query('CREATE TABLE o300 (
	  ord int primary key, 
	  cod_legal int, desc int, anex, art, inc, alin, prg, itm, ltr, obs)
	');
	$db->query('CREATE INDEX "o300_reg_prim" ON o300 (cod_legal ASC)');

  // 0400 - TABELA DE IDENTIFICAÇÃO DE DOCUMENTO OU RELATÓRIO INTERNO
	$db->query('CREATE TABLE o400 (
	  ord int primary key,
	  cod_chv int, descr_doc_int, cod_ doc_int)
	');
	$db->query('CREATE INDEX "o400_reg_prim" ON o400 (cod_chv ASC)');

  // s010 - ABERTURA DE FICHA 1A
	$db->query('CREATE TABLE s010 (
	  ord int primary key,
	  cod_item, quant_ini real, cus_ini real, icms_ini real, quant_fim real, cus_fim real, icms_fim real)
	');

  // s015 - MOVIMENTAÇÃO DE ITENS
	$db->query('CREATE TABLE s015 (
	  ord int primary key, ords010 int,
	  num_lanc int, dt_mov, hist, tip_doc int, ser, num_doc int, cfop int,
	  num_di, cod_part text, cod_lanc int, ind int, cod_item_outra_tab, 
	  quan real, cust_merc real, vl_icms real)
	');

  // s020 - IPI E OUTROS TRIBUTOS NA ENTRADA
	$db->query('CREATE TABLE s020 (
	  ord int primary key, ords015 int,
	  val_ipi real, val_trib real)
	');

  // s150 - ABERTURA DE FICHA 2A
	$db->query('CREATE TABLE s150 (
	  ord int primary key,
	  cod_item, cus_ini real, icms_ini real, cus_fim real, icms_fim real,
	  quant_per real, cust_unit real, icms_unit real)
	');

  // s155 - APURAÇÃO DO CUSTO - FICHA TÉCNICA 5A
	$db->query('CREATE TABLE s155 (
	  ord int primary key, ords150 int,
	  cod_ins, 
	  quant_ins real, cust_unit real, icms_unit real, perd_norm real, ganho_norm real)
	');

  // s160 - MOVIMENTAÇÃO DE ITENS DA FICHA 2A
	$db->query('CREATE TABLE s160 (
	  ord int primary key, ords150 int,
	  num_lanc int, dt_mov, hist, tip_doc int,
	  ser, num_doc, cod_lanc int, ind int, cod_item, quan real, cust_item real, vl_icms real)
	');

  // s310 - ABERTURA DE FICHA 3A
	$db->query('CREATE TABLE s310 (
	  ord int primary key,
	  cod_item, quant_ini real, cus_ini real, icms_ini real, 
	  quant_fim real, cus_fim real, icms_fim real)
	');

  // s315 - MOVIMENTAÇÃO DE ITENS
	$db->query('CREATE TABLE s315 (
	  ord int primary key, ords310 int,
	  num_lanc int, dt_mov, hist, tip_doc int, ser, num_doc int, cfop int,
	  cod_part text, cod_lanc int, ind int, 
	  quan real, cust_merc real, vl_icms real,
	  perc_crdout real, valor_crdout real, valor_desp real)
	');

  // s325 - OPERAÇÕES GERADORAS DE CRÉDITO ACUMULADO
	$db->query('CREATE TABLE s325 (
	  ord int primary key, ords315 int,
	  cod_legal int, valor_op_item real, icms_gera_item real)
	');

	// s330 - OPERAÇÕES GERADORAS APURADAS NAS FICHAS 6A OU 6B
	$db->query('CREATE TABLE s330 (
	  ord int primary key, ords325 int,
	  valor_bc_item real, aliq_item real, icms_deb_item real)
	');

	// s350 - OPERAÇÕES NÃO GERADORAS DE CRÉDITO ACUMULADO – FICHA 6F
	$db->query('CREATE TABLE s350 (
	  ord int primary key, ords315 int,
	  valor_op_item real, valor_bc_item real, aliq_item real,
	  icms_deb_item real, icms_oper_item real, icms_oper_item_cred real)
	');


	// s360 - ABERTURA DE FICHA 3A
	$db->query('CREATE TABLE s360 (
	  ord int primary key,
	  cod_item, quant_ini real, cus_ini real, icms_ini real, 
	  quant_fim real, cus_fim real, icms_fim real)
	');

	// s365 - MOVIMENTAÇÃO DE ITENS
	$db->query('CREATE TABLE s365 (
	  ord int primary key, ords360 int,
	  num_lanc int, dt_mov, hist, tip_doc int, ser, num_doc int, cfop int,
	  num_di, cod_part text, cod_lanc int, ind int, 
	  quan real, cust_merc real, vl_icms real,
	  perc_crdout real, valor_crdout real, valor_desp real)
	');

    // 5370 - IPI E OUTROS TRIBUTOS NA ENTRADA
	$db->query('CREATE TABLE s370 (
	  ord int primary key, ords365 int,
	  val_ipi real, val_trib real)
	');

	// s380 - OPERAÇÕES GERADORAS DE CRÉDITO ACUMULADO
	$db->query('CREATE TABLE s380 (
	  ord int primary key, ords365 int,
	  cod_legal int, valor_op_item real, icms_gera_item real)
	');

	// s385 - OPERAÇÕES GERADORAS APURADAS NAS FICHAS 6A OU 6B
	$db->query('CREATE TABLE s385 (
	  ord int primary key, ords380 int,
	  valor_bc_item real, aliq_item real, icms_deb_item real)
	');

	// s400 - OPERAÇÕES NÃO GERADORAS DE CRÉDITO ACUMULADO – FICHA 6F
	$db->query('CREATE TABLE s400 (
	  ord int primary key, ords365 int,
	  valor_op_item real, valor_bc_item real, aliq_item real,
	  icms_deb_item real, icms_oper_item real, icms_oper_item_cred real)
	');

	// s590 - ABERTURA DE FICHA 5G
	$db->query('CREATE TABLE s590 (
	  ord int primary key, 
	  cod_prod_elab)
	');

	// s595 - INVENTÁRIO POR MATERIAL COMPONENTE - FICHA 5G
	$db->query('CREATE TABLE s595 (
	  ord int primary key, ords590 int,
	  cod_ins, quant_ins real, cust_ins real, icms_ins real)
	');


	
  } else {
	if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
	} else {
	  werro('Falha ao abrir Banco de Dados ladca.db3');
	  exit;
	}  
  }
  
    $ilidos = 1;		// $ilidos e $iord... é o seguinte...
						// o primeiro campo das tabelas (ord) tem o seguinte formato {anoAA}{mes_inicialMM}{nro_da_linha0000000}
						// exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011
	$ianomes = 0601;	// O ano-mes correto será Gravado quando ler o registro '0000'
    if (!$handle = fopen("{$arquivo_ladca}", 'r')) {
     werro_die("Nao foi possivel a leitura do arquivo {$arquivo_ladca} - possivelmente foi deletado durante o processamento");
    }
	$a_conta_reg = array();		// para gravar no final a quantidade de cada registro no arquivo
	$ordo150 = 0;
	$ordo200 = 0;
	$ords010 = 0;
	$ords015 = 0;
	$ords150 = 0;
	$ords310 = 0;
	$ords315 = 0;
	$ords325 = 0;
	$ords360 = 0;
	$ords365 = 0;
	$ords380 = 0;
	$ords590 = 0;
	$aaaamm = '';
	$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

	
	while(!feof($handle)) {
	  $linha = '|' . trim(fgets($handle)); // o SPED sempre começa com pipe, '|'... o LADCA não, então coloco aqui
	  $campos = explode('|', $linha);

	  if ($campos[1] == '0000') $ianomes = substr($campos[5], -2) . substr($campos[5], 0, 2);
	  $iord = $ilidos + $ianomes * 10000000;  

	  if (strlen($campos[1]) == 4) {
		if (isset($a_conta_reg["{$campos[1]}"])) $a_conta_reg["{$campos[1]}"]++;
		else $a_conta_reg["{$campos[1]}"] = 1;
	  }
	  
	  if ($pr->options['edutf'])
		foreach($campos  as $indice => $valor) $campos[$indice] = $db->escapeString($valor);
	  else
		foreach($campos  as $indice => $valor) $campos[$indice] = $db->escapeString(utf8_encode($valor));
	  
	  if ($campos[1] == '0000') {
		$aaaamm = substr($campos[5], -4) . substr($campos[5], 0, 2);
		$insert_query = <<<EOD
INSERT INTO o000 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0150') {
		$insert_query = <<<EOD
INSERT INTO o150 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}'
 )
EOD;
		$db->query($insert_query);
		$ordo150 = $iord;
	  }

	  if ($campos[1] == '0151') {
		$insert_query = <<<EOD
INSERT INTO o151 VALUES(
'{$iord}', '{$ordo150}', '{$campos[2]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0200') {
		$insert_query = <<<EOD
INSERT INTO o200 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
		$ordo200 = $iord;
	  }

	  if ($campos[1] == '0205') {
		$insert_query = <<<EOD
INSERT INTO o205 VALUES(
'{$iord}', '{$ordo200}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0300') {
		$insert_query = <<<EOD
INSERT INTO o300 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}', '{$campos[11]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0400') {
		$insert_query = <<<EOD
INSERT INTO o400 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5010') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$insert_query = <<<EOD
INSERT INTO s010 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
		$ords010 = $iord;
	  }

	  if ($campos[1] == '5015') {
	    $campos[3] = dtaSPED($campos[3]);
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$campos[16] = str_replace(',','.',str_replace('.','',$campos[16]));
		$insert_query = <<<EOD
INSERT INTO s015 VALUES(
'{$iord}',  '{$ords010}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}',
'{$campos[11]}', '{$campos[12]}', '{$campos[13]}', '{$campos[14]}', '{$campos[15]}',
'{$campos[16]}'
 )
EOD;
		$db->query($insert_query);
		$ords015 = $iord;
	  }

	  if ($campos[1] == '5020') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$insert_query = <<<EOD
INSERT INTO s020 VALUES(
'{$iord}', '{$ords015}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5150') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$insert_query = <<<EOD
INSERT INTO s150 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}'
 )
EOD;
		$db->query($insert_query);
		$ords150 = $iord;
	  }

	  if ($campos[1] == '5155') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$insert_query = <<<EOD
INSERT INTO s155 VALUES(
'{$iord}',  '{$ords150}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5160') {
	    $campos[3] = dtaSPED($campos[3]);
		$campos[11] = str_replace(',','.',str_replace('.','',$campos[11]));
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$insert_query = <<<EOD
INSERT INTO s160 VALUES(
'{$iord}', '{$ords150}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}',
'{$campos[11]}', '{$campos[12]}', '{$campos[13]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5310') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$insert_query = <<<EOD
INSERT INTO s310 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
		$ords310 = $iord;
	  }

	  if ($campos[1] == '5315') {
	    $campos[3] = dtaSPED($campos[3]);
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$campos[16] = str_replace(',','.',str_replace('.','',$campos[16]));
		$campos[17] = str_replace(',','.',str_replace('.','',$campos[17]));
		$insert_query = <<<EOD
INSERT INTO s315 VALUES(
'{$iord}',  '{$ords310}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}',
'{$campos[11]}', '{$campos[12]}', '{$campos[13]}', '{$campos[14]}', '{$campos[15]}',
'{$campos[16]}', '{$campos[17]}'
 )
EOD;
		$db->query($insert_query);
		$ords315 = $iord;
	  }

	  if ($campos[1] == '5325') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$insert_query = <<<EOD
INSERT INTO s325 VALUES(
'{$iord}', '{$ords315}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
		$ords325 = $iord;
	  }

	  if ($campos[1] == '5330') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$insert_query = <<<EOD
INSERT INTO s330 VALUES(
'{$iord}', '{$ords325}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5350') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$insert_query = <<<EOD
INSERT INTO s350 VALUES(
'{$iord}', '{$ords315}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}'
 )
EOD;
		$db->query($insert_query);
	  }


	  if ($campos[1] == '5360') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$insert_query = <<<EOD
INSERT INTO s360 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
		$ords360 = $iord;
	  }

	  if ($campos[1] == '5365') {
	    $campos[3] = dtaSPED($campos[3]);
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$campos[16] = str_replace(',','.',str_replace('.','',$campos[16]));
		$campos[17] = str_replace(',','.',str_replace('.','',$campos[17]));
		$campos[18] = str_replace(',','.',str_replace('.','',$campos[18]));
		$insert_query = <<<EOD
INSERT INTO s365 VALUES(
'{$iord}',  '{$ords360}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}',
'{$campos[11]}', '{$campos[12]}', '{$campos[13]}', '{$campos[14]}', '{$campos[15]}',
'{$campos[16]}', '{$campos[17]}', '{$campos[18]}'
 )
EOD;
		$db->query($insert_query);
		$ords365 = $iord;
	  }

	  if ($campos[1] == '5370') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$insert_query = <<<EOD
INSERT INTO s370 VALUES(
'{$iord}', '{$ords365}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5380') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$insert_query = <<<EOD
INSERT INTO s380 VALUES(
'{$iord}', '{$ords365}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
		$ords380 = $iord;
	  }

	  if ($campos[1] == '5385') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$insert_query = <<<EOD
INSERT INTO s385 VALUES(
'{$iord}', '{$ords380}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5400') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$insert_query = <<<EOD
INSERT INTO s400 VALUES(
'{$iord}', '{$ords365}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5590') {
		$insert_query = <<<EOD
INSERT INTO s590 VALUES(
'{$iord}', '{$campos[2]}'
 )
EOD;
		$db->query($insert_query);
		$ords590 = $iord;
	  }

	  if ($campos[1] == '5595') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$insert_query = <<<EOD
INSERT INTO s595 VALUES(
'{$iord}', '{$ords590}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
	  }




	  
 	  if (++$ilidos % 50000 == 0) {
		if ($pr->ldebug) {
		  wecho("\nLidas {$ilidos} linhas do arquivo {$arquivo_ladca} em ");
		  wecho($pr->tempo() . " segundos");
		} else wecho("*");
		flush();
		$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
		$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
      }
	}
    fclose($handle);

	// Antes de finalizar, grava a contagem de registros para este arquivo
	foreach($a_conta_reg as $indice => $valor) {
		$db->query("INSERT INTO conta_reg VALUES ('{$arquivo_ladca}', '{$aaaamm}', '{$indice}', {$valor});");
	}

	if ($pr->ldebug) {
	  wecho("\nParte 1 - Leitura finalizada: {$ilidos} linhas do arquivo {$arquivo_ladca} em ");
	  wecho($pr->tempo() . " segundos\n\n");
	} else wecho("*");
	flush();
	$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
	
   // Leitura de Arquivo Finalizada
}
  
?>