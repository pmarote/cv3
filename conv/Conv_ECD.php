<?php

// As solução de processamento de Diário para Lancto estão no php abaixo... Serão utilizados por este e pelas soluções posteriores
require __DIR__ . '/Conv_ECD_Solucoes.php';

function leitura_ecd($arquivo_ecd) {
// não dei a indentação ... atenção...

  global $pr, $options;

  // Regras para o nome do arquivo db3 e também dos arquivos xls gerados na conversão
  // Se a opção "um arquivo excel para cada arquivo em fontes" estiver setada, nome = ecd (ecd.db3, ecd.xls)
  // Caso contrário, nome = ecd_{$arquivo_ecd}
  $nomarqaux = explode("/", $arquivo_ecd);
  if ($options['arqs_sep']) $nomarq = "ecd_" . substr($nomarqaux[count($nomarqaux)-1], 0, -4); else $nomarq = "ecd";
  
if (!file_exists(PR_TMP . "/{$nomarq}.db3")) { 

  if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
  } else {
	werro('Falha ao criar Banco de Dados ecd.db3');
	exit;
  }  

  $db->query('PRAGMA encoding = "UTF-8";');
 
	// Usado em Resumo de ECD_Dados
	$db->query('CREATE TABLE conta_reg (
	  arq, aaaamm, reg, qtd int)
	');

	// Código do Registro, proc = Processa Sim ou Não, Nível, Descrição
	$db->query('CREATE TABLE descri_reg (
	  reg, proc, nivel, descri)
	');
	$db->query('CREATE INDEX "descri_reg_prim" ON descri_reg (reg ASC)');

	$dados = file_get_contents(PR_RES . "/tabelas/ECD_Reg_Descri.txt");
	$linhas = explode("\n", $dados);
	foreach($linhas as $key => $value) {
	  $campos = explode("\t", substr($value, 0, -1));
	  $insert_query = "INSERT INTO descri_reg VALUES( ";
  	  foreach($campos as $keyc => $valuec) $insert_query .= "'" . $db->escapeString($valuec) . "', ";
	  $insert_query = substr($insert_query, 0, -2) . ')';
	  $db->query($insert_query);
	}
 
  $db->query('CREATE TABLE r0000 (
	ord int primary key,
	lecd, dt_ini, dt_fin, nome, cnpj, uf, ie, cod_mun, im, ind_sit_esp )
  ');

  $db->query('CREATE TABLE r0007 (
	ord int primary key,
	cod_ent_ref, cod_inscr )
  ');

	$db->query('CREATE TABLE r0150 (
	  ord int primary key,
	  cod_part text,
	  nome text,
	  cod_pais int,
	  cnpj text,
	  cpf text,
	  nit text,
	  uf text,
	  ie text,
	  ie_st text,
	  cod_mun text,
	  im text,
	  suframa text )
	');
	$db->query('CREATE INDEX "r0150_reg_prim" ON r0150 (cod_part ASC)');

	$db->query('CREATE TABLE r0180 (
	  ord int primary key,
	  ordr0150 int,
	  cod_rel text,
	  dt_ini_rel text,
	  dt_fin_rel )
	');

  $db->query('CREATE TABLE I010 (
	ord int primary key,
	ind_esc, cod_ver_lc )
  ');

  $db->query('CREATE TABLE I050 (
    ord int primary key,
    dt_alt,
    cod_nat text(2),
    ind_cta text(1),
    nivel int,
    cod_cta text,
    cod_cta_sup text,
    cta text,
	ordcta int,
	cod_cta_n1 text,
	cod_cta_n2 text,
	cod_cta_n3 text,
	cod_cta_n4 text,
	cod_cta_n5 text,
	cod_cta_n6 text,
	cod_cta_n7 text,
	cod_cta_n8 text)
  '); // Os seguintes campos não fazem parte da constituição original do I050:
  // ordcta é um campo criado para conseguir colocar o plano de contas em ordem correta..., na mesma em que aparece no arquivo SPED
  // cod_cta_n1 a n2 serve para guardar os níveis superiores e ler rapidamente
 

  $db->query('CREATE TABLE I051 (
    ord int primary key,
    cod_ent_ref text(2),
    cod_ccus VARCHAR(40),
    cod_cta_ref VARCHAR(40))
  ');
  
  $db->query('CREATE TABLE I052 (
    ord int primary key,
    cod_ccus,
    cod_agl)
  ');
 
  $db->query('CREATE TABLE I075 (
    ord int primary key,
    cod_hist,
    descr_hist)
  ');
  $db->query("CREATE INDEX I075_cod_hist on I075 (cod_hist ASC);");
 
  $db->query('CREATE TABLE I100 (
    ord int primary key,
    dt_alt,
    cod_ccus VARCHAR(40),
    ccus VARCHAR(80))
  ');
  $db->query("CREATE INDEX I100_cod_ccus on I100 (cod_ccus ASC);");

  $db->query('CREATE TABLE I150 (
    ord int primary key,
    dt_ini,
    dt_fin)
  ');

  $db->query('CREATE TABLE I155 (
    ord int primary key,
    cod_cta VARCHAR(40),
    cod_ccus VARCHAR(40),
    vl_sld_ini real,
    ind_dc_ini TEXT(1),
    vl_deb real,
    vl_cred real,
    vl_sld_fin real,
    ind_dc_fin TEXT(1))
  ');

  $db->query('CREATE TABLE I200 (
    ord int primary key,
    num_lcto,
    dt_lcto,
    vl_lcto real,
    ind_lcto)
  ');

  $db->query('CREATE TABLE I250 (
    ord int primary key,
	ord200 int,
    cod_cta,
    cod_ccus,
    vl_dc  real,
    ind_dc  TEXT(1),
    num_arq,
    cod_hist_pad,
    hist,
    cod_part)
  ');

  $db->query('CREATE TABLE I350 (
    ord int primary key,
    dt_res TEXT)
  ');

  $db->query('CREATE TABLE I355 (
    ord int primary key,
	ordi350 int,
    cod_cta VARCHAR(40),
    cod_ccus VARCHAR(40),
    vl_cta real,
    ind_dc TEXT(1))
  ');

  $db->query('CREATE TABLE J005 (
    ord int primary key,
    dt_ini, dt_fin, id_dem, cab_dem)
  ');

  $db->query('CREATE TABLE J100 (
    ord int primary key, dt_ini text, dt_fin text,
	cod_agl text, nivel_agl, ind_grp_bal, descr_cod_agl, vl_cta REAL, ind_dc_bal text)
  ');

  $db->query('CREATE TABLE J150 (
    ord int primary key, dt_ini, dt_fin,
	cod_agl, nivel_agl, descr_cod_agl, vl_cta REAL, ind_vl)
  ');

  $db->query('CREATE TABLE J900 (
	ord int primary key,
	dnrc_encer, num_ord, nat_livro, nome, qtd_lin, dt_ini_escr, dt_fin_escr )
  ');

  $db->query('CREATE TABLE J930 (
	ord int primary key,
	ident_nom, ident_cpf, ident_qualif, cod_assin, ind_crc )
  ');

 $db->query("CREATE TABLE contas(
	cod_cta text,
	dt_alt text,
	cod_nat text,
	ind_cta text,
	nivel int,
	cod_cta_sup text,
	cta text,
	cod_ccus text,
	cod_cta_ref TEXT,
	cod_agl,
	ordcta int,
	cod_cta_n1 text, cod_cta_n2 text, cod_cta_n3 text, cod_cta_n4 text,
	cod_cta_n5 text, cod_cta_n6 text, cod_cta_n7 text, cod_cta_n8 text
)");
  $db->query("CREATE INDEX contas_cod_cta on contas (cod_cta ASC)");

  $db->query("CREATE TABLE balanco (
    dt_ini text, dt_fin text, cod_agl text, 
	nivel_agl, ind_grp_bal, descr_cod_agl, vl_cta REAL, ind_dc_bal text,
	cod_agl_n1 text, cod_agl_n2 text, cod_agl_n3 text, cod_agl_n4 text,
	cod_agl_n5 text, cod_agl_n6 text, cod_agl_n7 text, cod_agl_n8 text)");
  $db->query("CREATE INDEX balanco_cod_agl on balanco (cod_agl ASC)");


  // Abaixo, tabela auxiliar para criar contas de compensação na "Não Solução" da questão N Débitos x M Créditos
  $db->query("CREATE TABLE contasNdebMCred(
	cod_cta text,
	cta text
)");
  $db->query("CREATE INDEX contasNdebMCred_cod_cta on contasNdebMCred (cod_cta ASC)");

  $db->query("CREATE TABLE saldos(
	ord INT,
	ord150,
	mes TEXT,
	cod_cta TEXT,
	cod_ccus TEXT,
	vl_sld_ini REAL,
	ind_dc_ini TEXT,
	vl_deb REAL,
	vl_cred REAL,
	vl_sld_fin REAL,
	ind_dc_fin TEXT)");
  $db->query("CREATE INDEX saldos_chapri on saldos (cod_cta ASC, mes ASC)");

  $db->query('CREATE TABLE diario (
    ord int primary key,
	ord200 int,
    num_lcto,
    dt_lcto,
    ind_lcto,
    cod_cta,
    vl_dc  real,
    ind_dc  TEXT(1),
    hist,
	padrao_s,
	padrao_m,
	padrao_nr,
	num_arq,
	cod_ccus)
  ');
 
  $db->query('CREATE TABLE lancto (
    num_lcto,
	nro_deb,
	nro_cred,
    dt_lcto,
    ind_lcto,
    cod_cta_d,
    cod_cta_c,
    valor  real,
    hist,
	padrao_s,
	padrao_m,
	padrao_nr,
	obs,
	ord)
  ');
  
  // RegSol Marca as Soluções Efetuadas
  $db->exec('CREATE TABLE regsol ( ord int, nro_sol int, num_lcto, num_lcto_novo);');
  $db->exec("CREATE INDEX regsol_chapri on regsol (ord ASC, nro_sol ASC);");

  // Sol1 	Marca totais dos valores de débitos e créditos antes e depois da solução 1
  //			Porque a solução 1, além de deletar lançamentos, pode mudar o total de débitos e crédito em relação ao diário
  //			se agregar partidas com débitos e créditos
  $db->exec('CREATE TABLE sol1 ( num_lcto, TotDeb real, TotCred real, TotDebFin real, TotCredFin real );');

  // Deve ser marcada também o numero do lancamento e ordem (item) do Diário do que passou por solucao 2, para solucao 3 não refazer
  //$db->exec('CREATE TABLE regsol2 ( num_lcto_ite Primary Key);');
 
  $db->query('CREATE TABLE plactaref (
     cod_cta_ref VARCHAR(40),
     cta VARCHAR(60),
     dt_ini_val,
     dt_fim_val,
     ind_cta TEXT(1))
  ');
  $db->query("CREATE INDEX plactaref_chapri on plactaref (cod_cta_ref ASC);");

  
	$arq = PR_RES . '/tabelas/PlanoCtasRefe_UTF8.txt';
    if (!$handle = fopen("$arq", 'r')) {
     werro("Nao foi possivel a leitura do arquivo $arq - possivelmente foi deletado durante o processamento");
     exit;
    }
	$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
	while(!feof($handle)) {
	  $linha = substr(fgets($handle), 0, -2); 
	  $campos = explode(';', $linha);
	  foreach($campos  as $indice => $valor) {
	    $campos[$indice] = $db->escapeString($valor);
	  }
	  $insert_query = <<<EOD
INSERT INTO plactaref VALUES(
'{$campos[0]}', '{$campos[1]}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}' )
EOD;
	  $db->query($insert_query);
	}
    fclose($handle);
	$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
  

} else {
  if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
  } else {
	werro('Falha ao abrir Banco de Dados ecd.db3');
	exit;
  }  
}
  
    $ilidos = 1;		// $ilidos e $iord... é o seguinte...
						// o primeiro campo das tabelas (ord) tem o seguinte formato {anoAA}{mes_inicial}{nro_da_linha0000000}
						// exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011
	$ianomes = 0601;	// O ano-mes correto será Gravado quando ler o registro '0000'
    if (!$handle = fopen("$arquivo_ecd", 'r')) {
     werro("Nao foi possivel a leitura do arquivo {$arquivo_ecd} - possivelmente foi deletado durante o processamento");
     exit;
    }
	$a_conta_reg = array();		// para gravar no final a quantidade de cada registro no arquivo
	$aaaamm = '';
	$dt_lcto = '';
	$i050_ordcta = 0;
	$ord150 = 0;
	$ordr0150 = 0;
	$mes150 = '';
	$ord200 = 0;
	$ordi350 = 0;
	$num_lcto = '#inicio#';
	$ind_lcto = '';
	$ai075 = array();
	$aconta = array();
	$actasup = array();
	$alancto = array('nro_deb' => 0, 'nro_cred' => 0);
	$cod_agl_n = array();	// para "captar" os diferentes níveis de código de aglutinação de balanço, até 8
	$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

	while(!feof($handle)) {
	  $linha = fgets($handle); 
	  $campos = explode('|', $linha);
	  
	  if ($campos[1] == '0000') $ianomes = substr($campos[3], 6, 2) . substr($campos[3], 2, 2);
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
	    $campos[3] = dtaSPED($campos[3]);
	    $campos[4] = dtaSPED($campos[4]);
		$aaaamm = substr($campos[3], 0, 7);
		$insert_query = <<<EOD
INSERT INTO r0000 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}' )
EOD;
		$db->query($insert_query);
	  }
	  
	  if ($campos[1] == '0007') {
		$insert_query = <<<EOD
INSERT INTO r0007 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}' )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0150') {
		$insert_query = <<<EOD
INSERT INTO r0150 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}'
 )
EOD;
		$db->query($insert_query);
		$ordr0150 = $iord;
	  }

	  if ($campos[1] == '0180') {
		$insert_query = <<<EOD
INSERT INTO r0180 VALUES(
'{$iord}', '{$ordr0150}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }


	  if ($campos[1] == 'I010') {
		$insert_query = <<<EOD
INSERT INTO I010 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}' )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'I050') {
	    $actasup[$campos[6]] = $campos[7];
		$nivel = $campos[5] + 0;
		$cod_cta_n = array();
		$cod_cta_n[$nivel] = $campos[6];
		$i050_ordcta++;
		$ctasup = $campos[7];
		for($iniv = $nivel-1; $iniv >= 1; $iniv--) {
		  $cod_cta_n[$iniv] = $ctasup;
		  $ctasup = $actasup[$ctasup];
		}
	    $campos[2] = dtaSPED($campos[2]);
		$insert_query = <<<EOD
INSERT INTO I050 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}', {$i050_ordcta},
'{$cod_cta_n[1]}', '{$cod_cta_n[2]}', '{$cod_cta_n[3]}', '{$cod_cta_n[4]}', 
'{$cod_cta_n[5]}', '{$cod_cta_n[6]}', '{$cod_cta_n[7]}', '{$cod_cta_n[8]}')
EOD;
		$db->query($insert_query);
		if (count($aconta) > 0) {
		  salvaconta($db, $aconta);	// Se já preencheu uma conta anteriormente, salva
		  unset($aconta);
		  $aconta = array();
		}
		$aconta['cod_cta'] = $campos[6];
		$aconta['dt_alt'] = $campos[2];
		$aconta['cod_nat'] = $campos[3];
		$aconta['ind_cta'] = $campos[4];
		$aconta['nivel'] = $campos[5];
		$aconta['cod_cta_sup'] = $campos[7];
		$aconta['cta'] = $campos[8];
		$aconta['ordcta'] = $i050_ordcta;
		$aconta['cod_cta_n1'] = $cod_cta_n[1];
		$aconta['cod_cta_n2'] = $cod_cta_n[2];
		$aconta['cod_cta_n3'] = $cod_cta_n[3];
		$aconta['cod_cta_n4'] = $cod_cta_n[4];
		$aconta['cod_cta_n5'] = $cod_cta_n[5];
		$aconta['cod_cta_n6'] = $cod_cta_n[6];
		$aconta['cod_cta_n7'] = $cod_cta_n[7];
		$aconta['cod_cta_n8'] = $cod_cta_n[8];
	  }

	  if ($campos[1] == 'I051') {
		$insert_query = <<<EOD
INSERT INTO I051 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}' )
EOD;
		$db->query($insert_query);
		// Para a elaboração da tabela contas, considera apenas um único I051 por I050
		$aconta['cod_ccus'] = $campos[3];
		$aconta['cod_cta_ref'] = $campos[4];
	  }

	  if ($campos[1] == 'I052') {
		$insert_query = <<<EOD
INSERT INTO I052 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}' )
EOD;
		$db->query($insert_query);
		// Para a elaboração da tabela contas, considera apenas um único I052 por I050
		$aconta['cod_agl'] = $campos[3];
	  }
	  
	  if ($campos[1] == 'I075') {
		$insert_query = <<<EOD
INSERT INTO I075 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}' )
EOD;
		$db->query($insert_query);
		$ai075[$campos[2]] = $campos[3];
	  }
	  
	  if ($campos[1] == 'I100') {
	    $campos[2] = dtaSPED($campos[2]);
		$insert_query = <<<EOD
INSERT INTO I100 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}' )
EOD;
		$db->query($insert_query);
	  }
	  
	  if ($campos[1] == 'I150') {
	    $campos[2] = dtaSPED($campos[2]);
	    $campos[3] = dtaSPED($campos[3]);
		$insert_query = <<<EOD
INSERT INTO I150 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}' )
EOD;
		$db->query($insert_query);
		$ord150 = $iord;
		$mes150 = substr($campos[2], 0, 7);
	  }
	  
	  if ($campos[1] == 'I155') {
		$insert_query = "
INSERT INTO I155 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', " . 
 str_replace(',','.',str_replace('.','',$campos[4])) . 
 ", '{$campos[5]}', " . 
 str_replace(',','.',str_replace('.','',$campos[6])) . 
 ", " . 
 str_replace(',','.',str_replace('.','',$campos[7])) .
 ", " . 
 str_replace(',','.',str_replace('.','',$campos[8])) . 
 ", '{$campos[9]}' )";
		$db->query($insert_query);
		$insert_query = "
INSERT INTO saldos VALUES(
'{$iord}', '{$ord150}', '{$mes150}', '{$campos[2]}', '{$campos[3]}', " . 
 str_replace(',','.',str_replace('.','',$campos[4])) . 
 ", '{$campos[5]}', " . 
 str_replace(',','.',str_replace('.','',$campos[6])) . 
 ", " . 
 str_replace(',','.',str_replace('.','',$campos[7])) .
 ", " . 
 str_replace(',','.',str_replace('.','',$campos[8])) . 
 ", '{$campos[9]}' )";
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'I200') {
	    if ($num_lcto <> $campos[2] && $num_lcto <> '#inicio#') {
		  grava_lancto($db, $alancto);
		  unset($alancto);
		  $alancto = array('nro_deb' => 0, 'nro_cred' => 0);
		}
	    $campos[3] = dtaSPED($campos[3]);
	    $num_lcto = $campos[2];
	    $dt_lcto = $campos[3];
	    $ind_lcto = $campos[5];
		$ord200 = $iord;
		$insert_query = "
INSERT INTO I200 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', " . str_replace(',','.',str_replace('.','',$campos[4])) . ", 
'{$campos[5]}' )";
		$db->query($insert_query);
	  }
	  
	  if ($campos[1] == 'I250') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$insert_query = <<<EOD
INSERT INTO I250 VALUES(
'{$iord}', '{$ord200}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}' )
EOD;
		$db->query($insert_query);
		if ($campos[7] <> '') $hist = '[i075]-' . $ai075[$campos[7]] . '-' . $campos[8];
		    else $hist = $campos[8];

		// cálculo dos padrões
		$padroes = padrao($db, $hist);
		
		$insert_query = "INSERT INTO diario VALUES(
'{$iord}', '{$ord200}', '{$num_lcto}', '{$dt_lcto}', '{$ind_lcto}', '{$campos[2]}', '{$campos[4]}', 
'{$campos[5]}', '{$hist}', '{$padroes['padrao_s']}', '{$padroes['padrao_m']}', '{$padroes['padrao_nr']}', '{$campos[6]}', '{$campos[3]}' )";
		//echo $insert_query;
		$db->query($insert_query);

		$alancto[] = array($num_lcto, $dt_lcto, $ind_lcto, $campos[2], $campos[4], $campos[5], $hist,
		  $padroes['padrao_s'], $padroes['padrao_m'], $padroes['padrao_nr'], '', $iord);
		if ($campos[5] == 'D') $alancto['nro_deb']++; else $alancto['nro_cred']++; 

	  }

	  if ($campos[1] == 'I350') {
	    $campos[2] = dtaSPED($campos[2]);
		$insert_query = <<<EOD
INSERT INTO I350 VALUES(
'{$iord}', '{$campos[2]}' )
EOD;
		$db->query($insert_query);
		$ordi350 = $iord;
	  }
	  
	  if ($campos[1] == 'I355') {
		$insert_query = "
INSERT INTO I355 VALUES(
'{$iord}', '{$ordi350}', '{$campos[2]}', '{$campos[3]}', " . 
 str_replace(',','.',str_replace('.','',$campos[4])) . 
 ", '{$campos[5]}' )";
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'J005') {
	    $campos[2] = dtaSPED($campos[2]);
	    $campos[3] = dtaSPED($campos[3]);
		$J005dt_ini = $campos[2];
		$J005dt_fin = $campos[3];
		$insert_query = <<<EOD
INSERT INTO J005 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}' )
EOD;
		$db->query($insert_query);
	  }
	  
	  if ($campos[1] == 'J100') {
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$insert_query = <<<EOD
INSERT INTO J100 VALUES(
'{$iord}', '{$J005dt_ini}', '{$J005dt_fin}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}' )
EOD;
		$db->query($insert_query);

		$cod_agl_n[$campos[3] + 0] = $campos[2];
		for($iagl = $campos[3] + 1; $iagl <= 8; $iagl++) $cod_agl_n[$iagl] = '';
		$insert_query = <<<EOD
INSERT INTO balanco VALUES(
'{$J005dt_ini}', '{$J005dt_fin}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}',
'{$cod_agl_n[1]}', '{$cod_agl_n[2]}', '{$cod_agl_n[3]}', '{$cod_agl_n[4]}', 
'{$cod_agl_n[5]}', '{$cod_agl_n[6]}', '{$cod_agl_n[7]}', '{$cod_agl_n[8]}' )
EOD;
		$db->query($insert_query);


	  }
	  
	  if ($campos[1] == 'J150') {
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$insert_query = <<<EOD
INSERT INTO J150 VALUES(
'{$iord}', '{$J005dt_ini}', '{$J005dt_fin}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}' )
EOD;
		$db->query($insert_query);
	  }
	  

	  if ($campos[1] == 'J900') {
	    $campos[7] = dtaSPED($campos[7]);
	    $campos[8] = dtaSPED($campos[8]);
		$insert_query = <<<EOD
INSERT INTO J900 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}')
EOD;
		$db->query($insert_query);
	  }
	  

	  if ($campos[1] == 'J930') {
		$insert_query = <<<EOD
INSERT INTO J930 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}')
EOD;
		$db->query($insert_query);
	  }
	  

	  if (++$ilidos % 10000 == 0) {
		if ($pr->ldebug) {
		  wecho("\nLidas {$ilidos} linhas do arquivo {$arquivo_ecd} em ");
		  wecho($pr->tempo() . " segundos");
		} else wecho("*");
		flush();
		$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
		$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
      }
	}
    fclose($handle);
	grava_lancto($db, $alancto); // grava os últimos lançamentos
	if (count($aconta) > 0) salvaconta($db, $aconta);	// grava a última conta

	// Antes de finalizar, grava a contagem de registros para este arquivo
	foreach($a_conta_reg as $indice => $valor) {
		$db->query("INSERT INTO conta_reg VALUES ('{$arquivo_ecd}', '{$aaaamm}', '{$indice}', {$valor});");
	}

	if ($pr->ldebug) {
	  wecho("\nParte 1 - Leitura finalizada: {$ilidos} linhas do arquivo {$arquivo_ecd} em ");
	  wecho($pr->tempo() . " segundos\n\n");
	} else wecho("*");
	flush();
	$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

// Criação de Arquivos Finalizada

}

function salvaconta($db, $aconta) {
  $db->query("INSERT INTO contas VALUES(
'{$aconta['cod_cta']}', '{$aconta['dt_alt']}', '{$aconta['cod_nat']}', '{$aconta['ind_cta']}', {$aconta['nivel']},
'{$aconta['cod_cta_sup']}', '{$aconta['cta']}', '{$aconta['cod_ccus']}', '{$aconta['cod_cta_ref']}', '{$aconta['cod_agl']}', '{$aconta['ordcta']}',
'{$aconta['cod_cta_n1']}', '{$aconta['cod_cta_n2']}', '{$aconta['cod_cta_n3']}', '{$aconta['cod_cta_n4']}',
'{$aconta['cod_cta_n5']}', '{$aconta['cod_cta_n6']}', '{$aconta['cod_cta_n7']}', '{$aconta['cod_cta_n8']}'
)");
}


/*
Parece que está tudo funcionando. Os SQLs de conferência são:
-- Conferência em nível de num_lcto
  SELECT num_lcto, round(ddebitos - dcreditos,2) AS dif1, round(ldebitos - lcreditos,2) AS dif2, round(ddebitos - ldebitos,2) AS dif3, round(dcreditos - lcreditos,2) AS dif4 FROM 
  (SELECT num_lcto, sum(ddebitos) AS ddebitos, sum(dcreditos) AS dcreditos, sum(ldebitos) AS ldebitos, sum(lcreditos) AS lcreditos FROM
  (SELECT num_lcto,
    sum(CASE WHEN ind_dc = 'D' THEN vl_dc ELSE 0 END) AS ddebitos, 
    sum(CASE WHEN ind_dc = 'C' THEN vl_dc ELSE 0 END) AS dcreditos, 0 AS ldebitos, 0 AS lcreditos
    FROM diario
    GROUP BY num_lcto
UNION ALL    
 SELECT num_lcto, 0 AS ddebitos, 0 AS dcreditos, sum(valor) AS ldebitos, sum(valor) AS lcreditos
   FROM lancto
   GROUP BY num_lcto)
   GROUP BY num_lcto)
   WHERE dif1 <> 0 OR dif2 <> 0 OR dif3 <> 0 OR dif4 <> 0;
   
-- Conferência em nível de num_lcto e cod_cta
SELECT num_lcto, round(ddebitos - ldebitos,2) AS dif1, round(dcreditos - lcreditos,2) AS dif2 FROM 
(SELECT num_lcto, cod_cta, sum(ddebitos) AS ddebitos, sum(dcreditos) AS dcreditos, sum(ldebitos) AS ldebitos, sum(lcreditos) AS lcreditos FROM
(SELECT num_lcto, cod_cta,
    sum(CASE WHEN ind_dc = 'D' THEN vl_dc ELSE 0 END) AS ddebitos, 
    sum(CASE WHEN ind_dc = 'C' THEN vl_dc ELSE 0 END) AS dcreditos, 0 AS ldebitos, 0 AS lcreditos
    FROM diario
    GROUP BY num_lcto, cod_cta
UNION ALL    
SELECT num_lcto, cod_cta, 0 AS ddebitos, 0 AS dcreditos, sum(ldebitos) AS ldebitos, sum(lcreditos) AS lcreditos FROM
 (SELECT num_lcto, cod_cta_d AS cod_cta, 0 AS ddebitos, 0 AS dcreditos, valor AS ldebitos, 0 AS lcreditos
   FROM lancto
UNION ALL
SELECT num_lcto, cod_cta_c AS cod_cta, 0 AS ddebitos, 0 AS dcreditos, 0 AS ldebitos, valor AS lcreditos
   FROM lancto)
   GROUP BY num_lcto, cod_cta)
   GROUP BY num_lcto, cod_cta)
   WHERE dif1 <> 0 OR dif2 <> 0;
*/
?> 