<?php

function leitura_lasimca($arquivo_lasimca) {

	global $pr, $options;

	// Regras para o nome do arquivo db3 e também dos arquivos xls gerados na conversão
	// Se a opção "um arquivo excel para cada arquivo em fontes" estiver setada, nome = lasimca (lasimca.db3, lasimca.xls)
	// Caso contrário, nome = lasimca_{$arquivo_lasimca}
	$nomarqaux = explode("/", $arquivo_lasimca);
	if ($options['arqs_sep']) $nomarq = "lasimca_" . substr($nomarqaux[count($nomarqaux)-1], 0, -4); else $nomarq = "lasimca";

	if (!file_exists(PR_TMP . "/{$nomarq}.db3")) { 

	if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
	} else {
		werro_die('Falha ao criar Banco de Dados lasimca.db3');
	}  

	$db->query('PRAGMA encoding = "UTF-8";');

	cria_tabela_cfopd($db);

	// Usado em Resumo de LASIMCA_Dados
	$db->query('CREATE TABLE conta_reg (
	  arq, aaaamm, reg, qtd int)
	');
  
	// Código do Registro, proc = Processa Sim ou Não, Nível, Descrição, Obrigatoriedade, Ocorrência
	$createtable = "
CREATE TABLE descri_reg (reg text, proc text, nivel int, descri text, obrig text, ocorr text);
CREATE INDEX descri_reg_reg ON descri_reg (reg ASC);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/LASIMCA_Reg_Descri.txt', 'descri_reg');	
  
	// Tabela 4.1 - Tabela Documentos Fiscais do ICMS - Ver arquivo LASIMCA_Tab4.1.txt em PR_RES . '/tabelas'
	$createtable = "
CREATE TABLE tab4_1 (cod_chv int, codigo text, descri text, mod text);
CREATE INDEX tab4_1_cod ON tab4_1 (cod_chv ASC);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/LASIMCA_Tab4.1.txt', 'tab4_1');	

	// Tabela tab_munic
	$createtable = "
CREATE TABLE tab_munic (cod int primary key, uf text, munic text);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/Tabela_Municípios.txt', 'tab_munic');	
  
  
	$db->query('CREATE TABLE o000 (
	  ord int primary key,
	  lasimca text,
	  cod_ver int,
	  cod_fin int,
	  periodo text,
	  nome text,
	  cnpj int,
	  ie text,
	  cnae int,
	  cod_mun text,
	  ie_intima )
	');

    // 0001 - ABERTURA DO BLOCO 0
	$db->query('CREATE TABLE o001 (
	  ord int primary key,
	  ind_mov int)
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

  // 0300 - ENQUADRAMENTO LEGAL DA OPERAÇÃO/PRESTAÇÃO GERADORA DE CRÉDITO ACUMULADO DO ICMS
	$db->query('CREATE TABLE o300 (
	  ord int primary key, 
	  cod_legal int, desc int, anex, art, inc, alin, prg, itm, ltr, obs)
	');
	$db->query('CREATE INDEX "o300_reg_prim" ON o300 (cod_legal ASC)');

  // 0990 - ENCERRAMENTO DO BLOCO 0
	$db->query('CREATE TABLE o990 (
	  ord int primary key,
	  qtd_lin_0 int)
	');

  // 5001 - ABERTURA DO BLOCO 5
	$db->query('CREATE TABLE s001 (
	  ord int primary key,
	  ind_mov int)
	');

  // s315 - OPERAÇÕES DE SAÍDA
	$db->query('CREATE TABLE s315 (
	  ord int primary key, 
	  dt_emissao, tip_doc int, ser, num_doc int,
	  cod_part text, valor_sai real, perc_crdout real, valor_crdout real)
	');

  // s320 - DEVOLUÇÃO DE SAÍDA
	$db->query('CREATE TABLE s320 (
	  ord int primary key, ords315 int,
	  dt_sai, tip_doc int, ser, num_doc int)
	');

  // s325 - OPERAÇÕES GERADORAS DE CRÉDITO ACUMULADO
	$db->query('CREATE TABLE s325 (
	  ord int primary key, ords315 int,
	  cod_legal int, iva_utilizado real, per_med_icms real,
	  cred_est_icms real, icms_gera real)
	');

	// s330 - OPERAÇÕES GERADORAS APURADAS NAS FICHAS 6A OU 6B
	$db->query('CREATE TABLE s330 (
	  ord int primary key, ords325 int,
	  valor_bc real, icms_deb real)
	');

	// s335 - OPERAÇÕES GERADORAS APURADAS NAS FICHAS 6C OU 6D
	$db->query('CREATE TABLE s335 (
	  ord int primary key, ords325 int,
	  num_decl_exp text, comp_oper int)
	');

	// s340 - DADOS DA EXPORTAÇÃO INDIRETA COMPROVADA- FICHA 5H
	$db->query('CREATE TABLE s340 (
	  ord int primary key, ords335 int,
	  data_doc_ind, num_doc_ind int, ser_doc_ind, num_decl_exp_ind text)
	');


	// s350 - OPERAÇÕES NÃO GERADORAS DE CRÉDITO ACUMULADO – FICHA 6F
	$db->query('CREATE TABLE s350 (
	  ord int primary key, ords315 int,
	  valor_bc real, icms_deb real, num_decl_exp_ind text)
	');

    // 5990 - ENCERRAMENTO DO BLOCO 5
	$db->query('CREATE TABLE s990 (
	  ord int primary key,
	  qtd_lin_c int)
	');

    // 9001 - ABERTURA DO BLOCO 9
	$db->query('CREATE TABLE q001 (
	  ord int primary key,
	  ind_mov int)
	');

    // 9900 - REGISTROS DO ARQUIVO
	$db->query('CREATE TABLE q900 (
	  ord int primary key,
	  reg_blc text, qtd_reg_blc int)
	');

    // 9990 - ENCERRAMENTO DO BLOCO 9
	$db->query('CREATE TABLE q990 (
	  ord int primary key,
	  qtd_lin_9 int)
	');

    // 9999 - ENCERRAMENTO DO ARQUIVO DIGITAL
	$db->query('CREATE TABLE q999 (
	  ord int primary key,
	  qtd_lin int)
	');




	
  } else {
	if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
	} else {
	  werro('Falha ao abrir Banco de Dados lasimca.db3');
	  exit;
	}  
  }
  
    $ilidos = 1;		// $ilidos e $iord... é o seguinte...
						// o primeiro campo das tabelas (ord) tem o seguinte formato {anoAA}{mes_inicialMM}{nro_da_linha0000000}
						// exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011
	$ianomes = 0601;	// O ano-mes correto será Gravado quando ler o registro '0000'
    if (!$handle = fopen("{$arquivo_lasimca}", 'r')) {
     werro_die("Nao foi possivel a leitura do arquivo {$arquivo_lasimca} - possivelmente foi deletado durante o processamento");
    }
	$a_conta_reg = array();		// para gravar no final a quantidade de cada registro no arquivo
	$ordo150 = 0;
	$ordo200 = 0;
	$ords315 = 0;
	$ords325 = 0;
	$ords335 = 0;
	$ords360 = 0;
	$ords365 = 0;
	$ords380 = 0;
	$ords590 = 0;
	$aaaamm = '';
	$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

	
	while(!feof($handle)) {
	  $linha = '|' . trim(fgets($handle)); // o SPED sempre começa com pipe, '|'... o LASIMCA não, então coloco aqui
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
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0001') {
		$insert_query = <<<EOD
INSERT INTO o001 VALUES(
'{$iord}', '{$campos[2]}'
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

	  if ($campos[1] == '0300') {
		$insert_query = <<<EOD
INSERT INTO o300 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}', '{$campos[11]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0990') {
		$insert_query = <<<EOD
INSERT INTO o990 VALUES(
'{$iord}', '{$campos[2]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5001') {
		$insert_query = <<<EOD
INSERT INTO s001 VALUES(
'{$iord}', '{$campos[2]}'
 )
EOD;
		$db->query($insert_query);
	  }


	  if ($campos[1] == '5315') {
	    $campos[2] = dtaSPED($campos[2]);
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$insert_query = <<<EOD
INSERT INTO s315 VALUES(
'{$iord}',  '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}'
 )
EOD;
		$db->query($insert_query);
		$ords315 = $iord;
	  }

	  if ($campos[1] == '5320') {
	    $campos[2] = dtaSPED($campos[2]);
		$insert_query = <<<EOD
INSERT INTO s320 VALUES(
'{$iord}', '{$ords315}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5325') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$insert_query = <<<EOD
INSERT INTO s325 VALUES(
'{$iord}', '{$ords315}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}'
 )
EOD;
		$db->query($insert_query);
		$ords325 = $iord;
	  }

	  if ($campos[1] == '5330') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$insert_query = <<<EOD
INSERT INTO s330 VALUES(
'{$iord}', '{$ords325}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5335') {
		$insert_query = <<<EOD
INSERT INTO s335 VALUES(
'{$iord}', '{$ords325}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
		$ords335 = $iord;
	  }

	  if ($campos[1] == '5340') {
		$insert_query = <<<EOD
INSERT INTO s340 VALUES(
'{$iord}', '{$ords335}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5350') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$insert_query = <<<EOD
INSERT INTO s350 VALUES(
'{$iord}', '{$ords315}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '5990') {
		$insert_query = <<<EOD
INSERT INTO s990 VALUES(
'{$iord}', '{$campos[2]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '9001') {
		$insert_query = <<<EOD
INSERT INTO q001 VALUES(
'{$iord}', '{$campos[2]}'
 )
EOD;
		$db->query($insert_query);
	  }


	  if ($campos[1] == '9900') {
		$insert_query = <<<EOD
INSERT INTO q900 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '9990') {
		$insert_query = <<<EOD
INSERT INTO q990 VALUES(
'{$iord}', '{$campos[2]}'
 )
EOD;
		$db->query($insert_query);
	  }


	  if ($campos[1] == '9999') {
		$insert_query = <<<EOD
INSERT INTO q999 VALUES(
'{$iord}', '{$campos[2]}'
 )
EOD;
		$db->query($insert_query);
	  }

  
 	  if (++$ilidos % 50000 == 0) {
		if ($pr->ldebug) {
		  wecho("\nLidas {$ilidos} linhas do arquivo {$arquivo_lasimca} em ");
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
		$db->query("INSERT INTO conta_reg VALUES ('{$arquivo_lasimca}', '{$aaaamm}', '{$indice}', {$valor});");
	}

	if ($pr->ldebug) {
	  wecho("\nParte 1 - Leitura finalizada: {$ilidos} linhas do arquivo {$arquivo_lasimca} em ");
	  wecho($pr->tempo() . " segundos\n\n");
	} else wecho("*");
	flush();
	$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
	
   // Leitura de Arquivo Finalizada
}
  
?>