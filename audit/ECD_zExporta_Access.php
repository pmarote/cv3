<?php

$pr->aud_registra(new PrMenu("ecd_exporta_mdb", "E_CD", "Exportação para Access", "ecd"));

function ecd_exporta_mdb() {

  global $pr;

  $dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 150);

  $lbl_obs1 	= new GtkLabel("Este modulo exporta os registros dos ECDs carregados para o Banco de Dados ECD.mdb, em formato ACCESS. ");
  $lbl_obs2 	= new GtkLabel("Há duas opções para este procedimento: Automatizado ou geração de Banco de Dados sem carregar o Diário (Diario_I200_I250)");
  $lbl_obs3 	= new GtkLabel("e exportação do mesmo em formato .txt, para posterior importação no ACCESS. Este segundo método é cerca de 20 vezes");
  $lbl_obs4 	= new GtkLabel("mais rápido em ECDs muito grandes (acima de 500.000 registros). O arquivo .txt será gerado no padrão Delimitado por Tabulações.");
  $lbl_obs5 	= new GtkLabel("Velocidade Aproximada de Exportação no Método Automatizado: 500 registros por segundo");
  $chkbtn1	= new GtkCheckButton("Usar Método 2: Geração de Banco de Dados sem carregar o Diário e Exportação em formato .txt para posterior importação no ACCESS");

  $dialog->vbox->pack_start($lbl_obs1, false, false, 2);
  $dialog->vbox->pack_start($lbl_obs2, false, false, 2);
  $dialog->vbox->pack_start($lbl_obs3, false, false, 2);
  $dialog->vbox->pack_start($lbl_obs4, false, false, 2);
  $dialog->vbox->pack_start($lbl_obs5, false, false, 2);
  $dialog->vbox->pack_start(new GtkHSeparator(), false, false, 2);
  $dialog->vbox->pack_start($chkbtn1, false, false, 2);

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

  $nroarq = 1;
  while(file_exists(PR_RESULTADOS . "/ECD" . ($nroarq == 1 ? "" : "{$nroarq}") . ".mdb")) $nroarq++;
  
  $nomarq = PR_RESULTADOS . "/ECD" . ($nroarq == 1 ? "" : "{$nroarq}") . ".mdb";
  copy(PR_RES . "/ECD.mdb", $nomarq);
  
  if ($chkbtn1->get_active()) {
    $nomdir = $nomarq . "_Arquivos_TXT";
    mkdir($nomdir);
	wecho("\n\nGerando " . substr($nomarq, 14) . ", sem Diário (Diario_I200_I250), na pasta Resultados e gerando, arquivo .txt do mesmo na pasta {$nomdir}");
  }
  
  if (! $db_conn = new COM("ADODB.Connection") ) {
	werro("Erro ! Não foi possível efetuar conexão à biblioteca .mdb");
	return;
  }
  if (! $connstr = "DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=". realpath($nomarq).";" ) {
	werro("Erro ! Não foi possível conectar o Banco de Dados Access inicial");
	return;
  }
  $db_conn->open($connstr);

  wecho("\n\nGerando " . substr($nomarq, 14) . " na pasta Resultados");

  wecho("\nPreenchendo a Tabela Contas_I050_I051_I052 ");
  $sqlite = ("
	SELECT cod_cta, dt_alt, cod_nat, ind_cta, nivel, cod_cta_sup, cta, cod_ccus, cod_cta_ref, cod_agl FROM contas
	WHERE cod_cta NOT LIKE 'NS________' AND cod_cta NOT LIKE 'NS__________';
");
  $result = $pr->query_log($sqlite);
  while ($linha = $result->fetchArray(SQLITE3_ASSOC)) {
	$sql = "INSERT INTO Contas_I050_I051_I052 VALUES (";
	foreach ($linha as $ind2 => $val2) $sql .= "'" . $pr->db->escapeString($linha[$ind2]) . "', ";
	$sql = substr($sql, 0, -2) . ");";
	$db_conn->execute($sql);
	if (++$i_lidos % 5000 == 0) wecho("*");
  }
  
  wecho("\nPreenchendo a Tabela Saldos_I150_I155 ");
  $sqlite = ("
SELECT mes, cod_cta, cod_ccus, vl_sld_ini, ind_dc_ini, vl_deb, vl_cred, vl_sld_fin, ind_dc_fin FROM saldos
    WHERE cod_cta NOT LIKE 'NS________' AND cod_cta NOT LIKE 'NS__________';
");
  $result = $pr->query_log($sqlite);
  while ($linha = $result->fetchArray(SQLITE3_ASSOC)) {
	$sql = "INSERT INTO Saldos_I150_I155 VALUES (";
	foreach ($linha as $ind2 => $val2) $sql .= "'" . $pr->db->escapeString($linha[$ind2]) . "', ";
	$sql = substr($sql, 0, -2) . ");";
	$db_conn->execute($sql);
	if (++$i_lidos % 5000 == 0) wecho("*");
  }
  
  wecho("\nPreenchendo a Tabela Diario_I200_I250 ");
  $sqlite = ("
SELECT num_lcto, dt_lcto, ind_lcto, cod_cta, cod_ccus, vl_dc, ind_dc, num_arq, cod_hist_pad, hist, cod_part
   FROM i250
   LEFT OUTER JOIN i200 ON i200.ord = i250.ord200;
");
  $i_lidos = 1;
  $result = $pr->query_log($sqlite);
  if ($chkbtn1->get_active()) {
	$nroarq = 1;
	while(file_exists("{$nomdir}/Diario_I200_I250" . ($nroarq == 1 ? "" : "_{$nroarq}") . ".txt")) $nroarq++;
	if (!$handle = fopen("{$nomdir}/Diario_I200_I250" . ($nroarq == 1 ? "" : "_{$nroarq}") . ".txt", 'w')) {
	  werro_die("Nao foi possivel a gravacao do arquivo {$nomdir}/Diario_I200_I250" . ($nroarq == 1 ? "" : "_{$nroarq}") . ".txt - Abandonando<br><br>");
	}
  }
  while ($linha = $result->fetchArray(SQLITE3_ASSOC)) {
	if ($chkbtn1->get_active()) {
	  $slinha = '';
	  $prim_campo = True;
	  foreach($linha as $ind2 => $val2) {
		if (! $prim_campo) $slinha .= "\t";
		$prim_campo = False;
		$slinha .= $val2;
	  }
	  $slinha .= "\r\n";
	  fputs($handle, $slinha);
	} else {
	  $sql = "INSERT INTO Diario_I200_I250 VALUES (";
	  foreach ($linha as $ind2 => $val2) $sql .= "'" . $pr->db->escapeString($linha[$ind2]) . "', ";
	  $sql = substr($sql, 0, -2) . ");";
	  $db_conn->execute($sql);
	}
	if (++$i_lidos % 5000 == 0) wecho("*");
  }
  if ($chkbtn1->get_active()) fclose($handle);

  wecho("\nPreenchendo a Tabela Saldos_Ant_Enc_I350_I355 ");
  $sqlite = ("
SELECT dt_res, cod_cta, cod_ccus, vl_cta, ind_dc
     FROM i355
     LEFT OUTER JOIN i350 ON i355.ordi350 = i350.ord;
");
  $result = $pr->query_log($sqlite);
  while ($linha = $result->fetchArray(SQLITE3_ASSOC)) {
	$sql = "INSERT INTO Saldos_Ant_Enc_I350_I355 VALUES (";
	foreach ($linha as $ind2 => $val2) $sql .= "'" . $pr->db->escapeString($linha[$ind2]) . "', ";
	$sql = substr($sql, 0, -2) . ");";
	$db_conn->execute($sql);
	if (++$i_lidos % 5000 == 0) wecho("*");
  }
    
  wecho("\nPreenchendo a Tabela Balanco_J100 ");
  $sqlite = ("
SELECT dt_ini, dt_fin, cod_agl, nivel_agl, 
  ind_grp_bal, descr_cod_agl, vl_cta, ind_dc_bal FROM j100
");
  $result = $pr->query_log($sqlite);
  while ($linha = $result->fetchArray(SQLITE3_ASSOC)) {
	$sql = "INSERT INTO Balanco_J100 VALUES (";
	foreach ($linha as $ind2 => $val2) $sql .= "'" . $pr->db->escapeString($linha[$ind2]) . "', ";
	$sql = substr($sql, 0, -2) . ");";
	$db_conn->execute($sql);
	if (++$i_lidos % 5000 == 0) wecho("*");
  }
  
  wecho("\nPreenchendo a Tabela DRE_J150 ");
  $sqlite = ("
SELECT dt_ini, dt_fin, cod_agl, nivel_agl, 
  descr_cod_agl, 
  CASE WHEN ind_vl IN ('R','P') THEN vl_cta ELSE -vl_cta END AS vl_cta, ind_vl
  FROM j150;
");
  $result = $pr->query_log($sqlite);
  while ($linha = $result->fetchArray(SQLITE3_ASSOC)) {
	$sql = "INSERT INTO DRE_j150 VALUES (";
	foreach ($linha as $ind2 => $val2) $sql .= "'" . $pr->db->escapeString($linha[$ind2]) . "', ";
	$sql = substr($sql, 0, -2) . ");";
	$db_conn->execute($sql);
	if (++$i_lidos % 5000 == 0) wecho("*");
  }
  
  if ($chkbtn1->get_active()) {
	wecho("\n\nFinalizado ! Copiado " . substr($nomarq, 14) . ", vazio, na pasta Resultados e gerados os arquivos .txt na pasta {$nomdir}");
  }
  $db_conn->Close();
  unset($db_conn);
  wecho("\n\nArquivo " . substr($nomarq, 14) . " gerado com sucesso na pasta Resultados !");

}

?>