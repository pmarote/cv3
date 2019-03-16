<?php

function abredb3_p17($arquivo_p17 = '') {
 global $tempo_inicio, $ldebug, $options;
 // Regras para o nome do arquivo db3 e também dos arquivos xls gerados na conversão
 // Se a opção "um arquivo excel para cada arquivo em fontes" estiver setada, nome = p17 (p17.db3, p17.xls)
 // Caso contrário, nome = p17_{$arquivo_p17}
 $nomarqaux = explode("/", $arquivo_p17);
 if ($options['arqs_sep']) $nomarq = "p17_" . substr($nomarqaux[count($nomarqaux)-1], 0, -4); else $nomarq = "p17";
  
 if (!file_exists(PR_TMP . "/{$nomarq}.db3")) { 

  if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
  } else {
	werro('Falha ao criar Banco de Dados p17.db3');
	exit;
  }  

  $db->query('PRAGMA encoding = "UTF-8";');

  cria_tabela_cfopd($db);

	  $db->query('create table R01(
		cnpj, ie, cnae, nomcon, munici, uf, dtaini, dtafin, lograd, lognro, logcpl, bairro, cep, contato, faxcon, telcon, email, site, arq )
	  ');
	  
	  $db->query('create table R02(
		cnpj, ie, uf, dtaemi, serie char, numero int, cfop int, codope, qtdpro real, vtbcstrt real, vtbcstef real, codpro, chassi, bcveic real, arq )
	  ');
	  $db->query('CREATE INDEX "R02chaprim" ON R02 (cnpj ASC, serie ASC, numero ASC)');
	  
	  $db->query('create table R03M(
		meanme, dtaemi, numord, numser, modelo, ndicoo, ndfcoo, nrocrz, valtgi real, valtgf real, arq)
	  ');
	  $db->query('CREATE INDEX "R03Mchaprim" ON R03M (dtaemi ASC, numord ASC)');
	  
	  $db->query('create table R03A(
		meanme, dtaemi, numord, staliq, valtot real, arq)
	  ');

	  $db->query('CREATE INDEX "R03Achaprim" ON R03A (dtaemi ASC, numord ASC, staliq ASC)');

	  $db->query('create table R03P(
		meanme, dtaemi, numord, qtddia real, vtbcstrt real, vtbcstef real, codpro, arq)
	  ');
	  $db->query('CREATE INDEX "R03Pchaprim" ON R03P (dtaemi ASC, numord ASC)');

	  
	  $db->query('create table R04(
		codpro, descri, unimed, arq )
	  ');
	  $db->query('CREATE INDEX "R04chaprim" ON R04 (arq ASC, codpro ASC)');
	  
	  $db->query('create table R05(
		codpro, dtaini, dtafin, alqicm real, arq)
	  ');
	  $db->query('CREATE INDEX "R05chaprim" ON R05 (arq ASC, codpro ASC)');
  
 } else {
  if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
  } else {
	werro('Falha ao abrir Banco de Dados p17.db3');
	exit;
  }  
 }
 return $db;
}

function leitura_p17($arquivo_p17) {

 global $tempo_inicio, $ldebug, $options;
 
    $db = abredb3_p17($arquivo_p17);
   
	$l_arq     = trim(utf8_encode($db->escapeString($arquivo_p17)));

    $ilidos = 0;
    if (!$handle = fopen("$arquivo_p17", 'r')) {
     werro("Nao foi possivel a leitura do arquivo {$arquivo_p17} - possivelmente foi deletado durante o processamento");
     exit;
    }
	$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

	while(!feof($handle)) {
	  $linha = fgets($handle);

		if (substr($linha, 0, 2) == '01') {
	    $l_cnpj = $db->escapeString(substr($linha, 2, 14));
	    $l_ie = trim($db->escapeString(substr($linha, 16, 14)));
	    $l_cnae = trim($db->escapeString(substr($linha, 30, 7)));
	    $l_nomcon = trim(utf8_encode($db->escapeString(substr($linha, 37, 35))));
		$l_munici = trim(utf8_encode($db->escapeString(substr($linha, 72, 30))));
	    $l_uf = $db->escapeString(substr($linha, 102, 2));
	    $l_dtaini = $db->escapeString(dtap32(substr($linha, 104, 8)));
	    $l_dtafin = $db->escapeString(dtap32(substr($linha, 112, 8)));
		$l_lograd = trim(utf8_encode($db->escapeString(substr($linha, 120, 34))));
		$l_lognro = trim(utf8_encode($db->escapeString(substr($linha, 154, 5))));
		$l_logcpl = trim(utf8_encode($db->escapeString(substr($linha, 159, 22))));
		$l_bairro = trim(utf8_encode($db->escapeString(substr($linha, 181, 15))));
		$l_cep    = trim(utf8_encode($db->escapeString(substr($linha, 196, 8))));
		$l_contato = trim(utf8_encode($db->escapeString(substr($linha, 204, 28))));
		$l_faxcon = trim(utf8_encode($db->escapeString(substr($linha, 232, 10))));
		$l_telcon = trim(utf8_encode($db->escapeString(substr($linha, 242, 12))));
		$l_email   = trim(utf8_encode($db->escapeString(substr($linha, 254, 50))));
		$l_site    = trim(utf8_encode($db->escapeString(substr($linha, 304, 40))));
	  
	    $insert_query = <<<EOD
INSERT INTO R01 VALUES(
'$l_cnpj', '$l_ie', '$l_cnae', '$l_nomcon', '$l_munici', '$l_uf', '$l_dtaini', '$l_dtafin', '$l_lograd', '$l_lognro', '$l_logcpl', 
'$l_bairro', '$l_cep', '$l_contato', '$l_faxcon', '$l_telcon', '$l_email', '$l_site', '$l_arq' )
EOD;
        $db->query($insert_query);
	  }

	  if (substr($linha, 0, 2) == '02') {
	    $l_cnpj = $db->escapeString(substr($linha, 2, 14));
	    $l_ie = trim($db->escapeString(substr($linha, 16, 14)));
	    $l_uf = $db->escapeString(substr($linha, 30, 2));
	    $l_dtaemi = $db->escapeString(dtap32(substr($linha, 32, 8)));
	    $l_serie = trim($db->escapeString(substr($linha, 40, 2)));
	    $l_numero = $db->escapeString(substr($linha, 42, 6));
	    $l_cfop = $db->escapeString(substr($linha, 48, 4));
	    $l_codope = $db->escapeString(substr($linha, 52, 2));
	    $l_qtdpro = $db->escapeString(number_format(substr($linha, 54, 13) / 1000, 3, '.', ''));
	    $l_vtbcstrt = $db->escapeString(number_format(substr($linha, 67, 13) / 100, 2, '.', ''));
	    $l_vtbcstef = $db->escapeString(number_format(substr($linha, 80, 13) / 100, 2, '.', ''));
	    $l_codpro = trim($db->escapeString(substr($linha, 93, 14)));
	    $l_chassi = trim($db->escapeString(substr($linha, 107, 22)));
	    $l_bcveic = $db->escapeString(number_format(substr($linha, 129, 13) / 100, 2, '.', ''));
	  
	    $insert_query = <<<EOD
INSERT INTO R02 VALUES(
'$l_cnpj', '$l_ie', '$l_uf', '$l_dtaemi', '$l_serie', 
'$l_numero', '$l_cfop', '$l_codope', '$l_qtdpro', '$l_vtbcstrt', '$l_vtbcstef', '$l_codpro', '$l_chassi', '$l_bcveic', '$l_arq' )
EOD;
        $db->query($insert_query);
	  }

	  if (substr($linha, 0, 3) == '03M') {
	    $l_meanme = $db->escapeString(substr($linha, 2, 1));
	    $l_dtaemi = $db->escapeString(dtap32(substr($linha, 3, 8)));
	    $l_numord = $db->escapeString(substr($linha, 11, 3));
	    $l_numser = $db->escapeString(substr($linha, 14, 15));
	    $l_modelo = trim($db->escapeString(substr($linha, 29, 2)));
	    $l_ndicoo = $db->escapeString(substr($linha, 31, 6));
	    $l_ndfcoo = $db->escapeString(substr($linha, 37, 6));
	    $l_nrocrz = $db->escapeString(substr($linha, 43, 6));
	    $l_valtgi = $db->escapeString(number_format(substr($linha, 49, 16) / 100, 2, '.', ''));
	    $l_valtgf = $db->escapeString(number_format(substr($linha, 65, 16) / 100, 2, '.', ''));
	    $insert_query = <<<EOD
INSERT INTO R03M VALUES(
'$l_meanme', '$l_dtaemi', '$l_numord', '$l_numser', '$l_modelo', '$l_ndicoo', '$l_ndfcoo',
'$l_nrocrz', '$l_valtgi', '$l_valtgf', '$l_arq' )
EOD;
        $db->query($insert_query);
	  }

	  if (substr($linha, 0, 3) == '03A') {
	    $l_meanme = $db->escapeString(substr($linha, 2, 1));
	    $l_dtaemi = $db->escapeString(dtap32(substr($linha, 3, 8)));
	    $l_numord = $db->escapeString(substr($linha, 11, 3));
	    $l_staliq = $db->escapeString(substr($linha, 14, 4));
	    $l_valtot = $db->escapeString(number_format(substr($linha, 18, 12) / 100, 2, '.', ''));
	    $insert_query = <<<EOD
INSERT INTO R03A VALUES(
'$l_meanme', '$l_dtaemi', '$l_numord', '$l_staliq', '$l_valtot', '$l_arq')
EOD;
        $db->query($insert_query);
	  }

	  if (substr($linha, 0, 3) == '03P') {
	    $l_meanme = $db->escapeString(substr($linha, 2, 1));
	    $l_dtaemi = $db->escapeString(dtap32(substr($linha, 3, 8)));
	    $l_numord = $db->escapeString(substr($linha, 11, 3));
	    $l_qtdpro = $db->escapeString(number_format(substr($linha, 14, 13) / 1000, 3, '.', ''));
	    $l_vtbcstrt = $db->escapeString(number_format(substr($linha, 27, 13) / 100, 2, '.', ''));
	    $l_vtbcstef = $db->escapeString(number_format(substr($linha, 40, 13) / 100, 2, '.', ''));
	    $l_codpro = trim($db->escapeString(substr($linha, 53, 14)));
	    $insert_query = <<<EOD
INSERT INTO R03P VALUES(
'$l_meanme', '$l_dtaemi', '$l_numord', '$l_qtdpro', '$l_vtbcstrt', '$l_vtbcstef', '$l_codpro', '$l_arq')
EOD;
        $db->query($insert_query);
	  }

	  if (substr($linha, 0, 2) == '04') {
	    $l_codpro = trim($db->escapeString(substr($linha, 2, 14)));
	    $l_descri = trim(utf8_encode($db->escapeString(substr($linha, 16, 75))));
	    $l_unimed = trim(utf8_encode($db->escapeString(substr($linha, 91, 3))));
	    $insert_query = <<<EOD
INSERT INTO R04 VALUES(
'$l_codpro', '$l_descri', '$l_unimed', '$l_arq')
EOD;
        $db->query($insert_query);
	  }

	  if (substr($linha, 0, 2) == '05') {
	    $l_codpro = trim($db->escapeString(substr($linha, 2, 14)));
	    $l_dtaini = $db->escapeString(dtap32(substr($linha, 16, 8)));
	    $l_dtafin = $db->escapeString(dtap32(substr($linha, 24, 8)));
	    $l_alqicm = $db->escapeString(number_format(substr($linha, 32, 4) / 100, 2, '.', ''));	// 2 decimais
	    $insert_query = <<<EOD
INSERT INTO R05 VALUES(
'$l_codpro', '$l_dtaini', '$l_dtafin', '$l_alqicm', '$l_arq')
EOD;
        $db->query($insert_query);
	  }

	  if (++$ilidos % 50000 == 0) {
		if ($ldebug) {
		  wecho("\nLidas {$ilidos} linhas do arquivo {$arquivo_p17} em ");
		  wecho(time() - $tempo_inicio . " segundos");
		} else wecho("*");
		flush();
		$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
		$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
      }
	}
    fclose($handle);
	if ($ldebug) {
	  wecho("\nParte 1 - Leitura finalizada: {$ilidos} linhas do arquivo {$arquivo_p17} em ");
	  wecho(time() - $tempo_inicio . " segundos\n\n");
	} else wecho("*");
	flush();
	$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

}
  
?> 