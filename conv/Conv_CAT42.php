<?php

function leitura_cat42($arquivo_cat42) {

	global $pr, $options;

	// Regras para o nome do arquivo db3 e também dos arquivos xls gerados na conversão
	// Se a opção "um arquivo excel para cada arquivo em fontes" estiver setada, nome = ladca (ladca.db3, ladca.xls)
	// Caso contrário, nome = ladca_{$arquivo_cat42}
	$nomarqaux = explode("/", $arquivo_cat42);
	if ($options['arqs_sep']) $nomarq = "cat42_" . substr($nomarqaux[count($nomarqaux)-1], 0, -4); else $nomarq = "cat42";

	if (!file_exists(PR_TMP . "/{$nomarq}.db3")) { 

	if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
	} else {
		werro_die('Falha ao criar Banco de Dados ladca.db3');
	}  

	$db->query('PRAGMA encoding = "UTF-8";');

	cria_tabela_cfopd($db);

	// Usado em Resumo de CAT42_Dados
	$db->query('CREATE TABLE conta_reg (
	  arq, aaaamm, reg, qtd int)
	');
  
	// Código do Registro, proc = Processa Sim ou Não, Nível, Descrição, Obrigatoriedade, Ocorrência
	$createtable = "
CREATE TABLE descri_reg (reg text, proc text, nivel int, descri text, obrig text, ocorr text);
CREATE INDEX descri_reg_reg ON descri_reg (reg ASC);
";
	create_table_from_txt($db, $createtable, 'res\tabelas\CAT42_Reg_Descri.txt', 'descri_reg');	

	// Tabela 4.1.1 - Tipos de Documentos Fiscais - Ver arquivo Tab4.1.1.txt em _sistema/res/tabelas
	$createtable = "
CREATE TABLE tab4_1_1 (cod text, descri text, mod text);
CREATE INDEX tab4_1_1_cod ON tab4_1_1 (cod ASC);
";
	create_table_from_txt($db, $createtable, 'res\tabelas\Tab4.1.1.txt', 'tab4_1_1');	
	
	// Tabela tab_munic
	$createtable = "
CREATE TABLE tab_munic (cod int primary key, uf text, munic text);
";
	create_table_from_txt($db, $createtable, 'res/tabelas/Tabela_Municípios.txt', 'tab_munic');	
  
  
	$db->query('CREATE TABLE o000 (
	  ord int primary key,
	  periodo text,
	  nome text,
	  cnpj int,
	  ie text,
	  cod_mun text,
	  cod_ver int,
	  cod_fin int)
	');

	$db->query('CREATE TABLE o150 (
	  ord int primary key,
	  cod_part text,
	  nome text,
	  cod_pais int,
	  cnpj int,
	  cpf int,
	  ie text,
	  cod_mun text)
	');
	$db->query('CREATE INDEX "o150_reg_prim" ON o150 (cod_part ASC)');

  // 0200 - Tabela de Identificação do Item (Produtos e Serviços)
	$db->query('CREATE TABLE o200 (
	  ord int primary key,
	  cod_item text, descr_item text, cod_barra text, unid_inv text, cod_ncm text, aliq_icms real, cest int)
	');
	$db->query('CREATE INDEX "o200_reg_prim" ON o200 (cod_item ASC)');
  
  // 0205 - Alteração do Item
	$db->query('CREATE TABLE o205 (
	  ord int primary key,
	  cod_item text, cod_ant_item, descr_ant_item)
	');

    // REGISTRO 1050 – REGISTRO DE SALDOS
  	$db->query('CREATE TABLE l050 (
	  ord int primary key,
	  cod_item text, qtd_ini real, icms_tot_ini real, qtd_fim real, icms_tot_fim reak)
	');

	// REGISTRO 1100 – REGISTRO DE DOCUMENTO FISCAL ELETRÔNICO PARA FINS DE RESSARCIMENTO DE SUBSTITUIÇÂO TRIBUTÁRIA OU ANTECIPAÇÃO.
  	$db->query('CREATE TABLE l100 (
	  ord int primary key,
	  chv_doc text, data text, num_item int, ind_oper int, cod_item text, 
	  cfop int, qtd real, icms_tot real, vl_confr real, cod_legal int)
	');

  // REGISTRO 1200 – REGISTRO DE DOCUMENTO FISCAL NÃO-ELETRÔNICO PARA FINS DE RESSARCIMENTO DE SUBSTITUIÇÂO TRIBUTÁRIA – SP
  	$db->query('CREATE TABLE l200 (
	  ord int primary key,
	  cod_part text, cod_mod text, ecf_fab text, ser text, num_doc int, num_item int, ind_oper int, 
	  data text, cfop int, cod_item text, qtd real, icms_tot real, vl_confr real, cod_legal int)
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
    if (!$handle = fopen("{$arquivo_cat42}", 'r')) {
     werro_die("Nao foi possivel a leitura do arquivo {$arquivo_cat42} - possivelmente foi deletado durante o processamento");
    }
	$a_conta_reg = array();		// para gravar no final a quantidade de cada registro no arquivo
	$aaaamm = '';
	$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

	
	while(!feof($handle)) {
	  $linha = '|' . trim(fgets($handle)); // o SPED sempre começa com pipe, '|'... o LADCA e CAT42 não, então coloco aqui
	  $campos = explode('|', $linha);

	  if ($campos[1] == '0000') $ianomes = substr($campos[2], -2) . substr($campos[2], 0, 2);
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
		$aaaamm = substr($campos[2], -4) . substr($campos[2], 0, 2);
		$insert_query = <<<EOD
INSERT INTO o000 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0150') {
		$insert_query = <<<EOD
INSERT INTO o150 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0200') {
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$insert_query = <<<EOD
INSERT INTO o200 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0205') {
		$insert_query = <<<EOD
INSERT INTO o205 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '1050') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$insert_query = <<<EOD
INSERT INTO l050 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '1100') {
	    $campos[3] = dtaSPED($campos[3]);
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$insert_query = <<<EOD
INSERT INTO l100 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '1200') {
	    $campos[9] = dtaSPED($campos[9]);
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$insert_query = <<<EOD
INSERT INTO l200 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}', '{$campos[15]}'
 )
EOD;
		$db->query($insert_query);
	  }


	  
 	  if (++$ilidos % 50000 == 0) {
		if ($pr->ldebug) {
		  wecho("\nLidas {$ilidos} linhas do arquivo {$arquivo_cat42} em ");
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
		$db->query("INSERT INTO conta_reg VALUES ('{$arquivo_cat42}', '{$aaaamm}', '{$indice}', {$valor});");
	}

	if ($pr->ldebug) {
	  wecho("\nParte 1 - Leitura finalizada: {$ilidos} linhas do arquivo {$arquivo_cat42} em ");
	  wecho($pr->tempo() . " segundos\n\n");
	} else wecho("*");
	flush();
	$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
	
   // Leitura de Arquivo Finalizada
}
  
?>