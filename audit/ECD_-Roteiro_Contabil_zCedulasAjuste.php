<?php

$pr->aud_registra(new PrMenu("ecd_rot_cedulas_ajuste", "E_CD", "Roteiro Contábil - Cédulas de Ajuste", "ecd"));

function ecd_rot_cedulas_ajuste() {

  global $pr;

	$nomarq = "Cedulas_de_Ajuste_1.xlsx";
	$i = 2;
	while (file_exists(PR_RESULTADOS . "/{$nomarq}")) {
		$nomarq = substr($nomarq, 0, -6) . $i . '.xlsx';
		$i++;
	}

	$pr->nomarq = $nomarq;	// vou usar este nome na hora de finaliza_excel()...

	// primeiro o excel é gerado em ./tmp e, depois de pronto, movido para ./Resultados
	$pr->excel = new COM("excel.application") or werro_die("Erro... não foi possível abrir o Excel a partir do Conversor !"); 
	if ($pr->options['ldebug']) wecho("Abrindo excel, versao {$pr->excel->Version}\n"); else wecho("*");
	$pr->ver_excel = $pr->excel->Version;	// 2003, 11.0;	2007, 12.0	;2010, 14.0
	// Aumenta o limite máximo das Querys, definido em opções, usado em abre_excel, na exportação .txt para 1.000.000 no caso de Excel 2007 ou 2010
	if ($pr->ver_excel <> '11.0' && $pr->options['limit_sql'] == 300000)
			$pr->options['limit_sql'] = 1000000;	// Limite 
	//bring it to front 
	if ($pr->options['ldebug']) $pr->excel->Visible = 1; 
	//dont want alerts ... run silent 
	$pr->excel->DisplayAlerts = 0; 
	//open workbook "Cédula de Ajuste"
	$saux1 = str_replace('/', "\\", PR_RES . "/tabelas/Cedulas_de_Ajuste.xlsx");
	$wkb_final = $pr->excel->Workbooks->Open($saux1); 
   
	$xls_final = PR_TMP . '/' . $pr->nomarq;
	if (file_exists($xls_final)) unlink($xls_final);
	
    $wkb_final->SaveAs(str_replace('/', "\\", $xls_final)); 

	$a_sql = $pr->aud_sql2array("
SELECT cod_cta, cta, cod_agl, sf, dcf, Null as n1, 
   cod_agl_n1, cod_agl_n2, cod_agl_n3, cod_agl_n4, cod_agl_n5, cod_agl_n6 FROM contas
   LEFT OUTER JOIN 
      (SELECT cod_cta AS cc, vl_sld_fin AS sf, ind_dc_fin AS dcf FROM saldos WHERE mes IN (SELECT max(mes) FROM saldos)) AS salaux 
   ON cc = cod_cta
   LEFT OUTER JOIN
      (SELECT cod_agl AS cb, cod_agl_n1, cod_agl_n2, cod_agl_n3, cod_agl_n4, cod_agl_n5, cod_agl_n6 FROM balanco 
       WHERE dt_fin IN (SELECT max(dt_fin) FROM balanco)) AS balaux
   ON cb = cod_agl;
");
	$sheet = $pr->excel->Sheets("Contas_Saldos");
	foreach($a_sql as $ind => $val) {
	  $col = 1;
	  foreach($a_sql[$ind] as $ind2 => $val2) {
		$cell = $sheet->Cells($ind + 2, $col);
		$cell->value = utf8_decode($val2);
		$col++;
	  }
	  if ($ind % 100 == 0) wecho("*");
	}

	$a_sql = $pr->aud_sql2array("
SELECT contas.cod_cta AS cod_cta, dt_res,  vl_cta, ind_dc, cta, cod_agl FROM contas
   LEFT OUTER JOIN saldos_ant_enc ON saldos_ant_enc.cod_cta = contas.cod_cta
   WHERE cod_nat + 0 = 4;
");
	$sheet = $pr->excel->Sheets("Saldo_Ctas_Res");
	foreach($a_sql as $ind => $val) {
	  $col = 1;
	  foreach($a_sql[$ind] as $ind2 => $val2) {
		$cell = $sheet->Cells($ind + 2, $col);
		$cell->value = utf8_decode($val2);
		$col++;
	  }
	  if ($ind % 100 == 0) wecho("*");
	}

	$a_sql = $pr->aud_sql2array("
  SELECT dt_ini, dt_fin, cod_agl, nivel_agl, ind_grp_bal, descr_cod_agl, vl_cta, ind_dc_bal, Null as n1,
   cod_agl_n1, cod_agl_n2, cod_agl_n3, cod_agl_n4, cod_agl_n5, cod_agl_n6
   FROM balanco
   WHERE dt_fin IN (SELECT max(dt_fin) FROM balanco);
");
	$sheet = $pr->excel->Sheets("BalancoJ100");
	foreach($a_sql as $ind => $val) {
	  $col = 1;
	  foreach($a_sql[$ind] as $ind2 => $val2) {
		$cell = $sheet->Cells($ind + 2, $col);
		$cell->value = utf8_decode($val2);
		$col++;
	  }
	  if ($ind % 100 == 0) wecho("*");
	}

	$a_sql = $pr->aud_sql2array("
SELECT dt_ini, dt_fin, cod_agl, nivel_agl, descr_cod_agl, vl_cta, ind_vl FROM j150
     WHERE dt_fin IN (SELECT max(dt_fin) FROM j150);
");
	$sheet = $pr->excel->Sheets("DREJ150");
	foreach($a_sql as $ind => $val) {
	  $col = 1;
	  foreach($a_sql[$ind] as $ind2 => $val2) {
		$cell = $sheet->Cells($ind + 2, $col);
		$cell->value = utf8_decode($val2);
		$col++;
	  }
	  if ($ind % 100 == 0) wecho("*");
	}

	$sheet = $pr->excel->Sheets("Dados");
	$a_sql = $pr->aud_sql2array("SELECT count(*) AS c FROM contas;");
	$cell = $sheet->Cells(1, 2);
	if (isset($a_sql[0]['c'])) $cell->value = utf8_decode($a_sql[0]['c']);
	$a_sql = $pr->aud_sql2array("SELECT count(*) AS c FROM contas WHERE cod_nat + 0 = 4;");
	$cell = $sheet->Cells(2, 2);
	if (isset($a_sql[0]['c'])) $cell->value = utf8_decode($a_sql[0]['c']);
	$a_sql = $pr->aud_sql2array("SELECT count(*) AS c FROM J100 WHERE dt_fin IN (SELECT max(dt_fin) FROM j100);");
	$cell = $sheet->Cells(3, 2);
	if (isset($a_sql[0]['c'])) $cell->value = utf8_decode($a_sql[0]['c']);
	$a_sql = $pr->aud_sql2array("SELECT count(*) AS c FROM J150 WHERE dt_fin IN (SELECT max(dt_fin) FROM j150);");
	$cell = $sheet->Cells(4, 2);
	if (isset($a_sql[0]['c'])) $cell->value = utf8_decode($a_sql[0]['c']);
	$a_sql = $pr->aud_sql2array("
SELECT nome, 
   substr(ie, 1, 3) || '.' || substr(ie, 4, 3) || '.' || substr(ie, 7, 3) || '.' || substr(ie, 10, 3) AS ie, 
   substr(substr('000000000' || cnpj, -14), 1, 2) || '.' || substr(substr('000000000' || cnpj, -14), 3, 3) || '.' ||
   substr(substr('000000000' || cnpj, -14), 6, 3) || '/' || substr(substr('000000000' || cnpj, -14), 9, 4) || '-' || 
   substr(substr('000000000' || cnpj, -14), 13, 2) AS cnpj, 
   substr(dt_ini, 9, 2) || '/' || substr(dt_ini, 6, 2) || '/' || substr(dt_ini, 1, 4) || ' a ' || 
   substr(dt_fin, 9, 2) || '/' || substr(dt_fin, 6, 2) || '/' || substr(dt_fin, 1, 4) AS per_aju FROM r0000;
");
	$cell = $sheet->Cells(6, 2);
	if (isset($a_sql[0]['nome']))    $cell->value = utf8_decode($a_sql[0]['nome']);
	$cell = $sheet->Cells(7, 2);
	if (isset($a_sql[0]['ie']))      $cell->value = utf8_decode($a_sql[0]['ie']);
	$cell = $sheet->Cells(8, 2);
	if (isset($a_sql[0]['cnpj']))    $cell->value = utf8_decode($a_sql[0]['cnpj']);
	$cell = $sheet->Cells(11, 2);
	if (isset($a_sql[0]['per_aju'])) $cell->value = utf8_decode($a_sql[0]['per_aju']);

	// Não me pergunte o porquê... no arquivo Excel original está a área de impressão definida
	// mas por algum motivo, ela é perdida... então estou refazendo abaixo
	$sheet = $pr->excel->Sheets("FIA");
	$sheet->PageSetup->PrintArea = '$A$3:$W$57';
	$sheet = $pr->excel->Sheets("DISPONIVEL");
	$sheet->PageSetup->PrintArea = '$F$1:$AE$44';
	$sheet = $pr->excel->Sheets("DRE");
	$sheet->PageSetup->PrintArea = '$F$1:$AC$45';
	$sheet = $pr->excel->Sheets("PL");
	$sheet->PageSetup->PrintArea = '$F$1:$AE$44';
	$sheet = $pr->excel->Sheets("BTAI");
	$sheet->PageSetup->PrintArea = '$E$1:$Y$56';

	  $nomarq = PR_RESULTADOS . "/" . $pr->nomarq;
	  if (file_exists($nomarq)) unlink($nomarq);

	  $wkb_final = $pr->excel->ActiveWorkbook;
	  $wkb_final->SaveAs(str_replace('/', "\\", $nomarq)); 

	  //close the book 
	  $wkb_final->Close(false); 
	  $pr->excel->Workbooks->Close(); 
	  //closing excel 
	  $pr->excel->Quit(); 
	  //free the object 
	  $pr->excel = null; 
	
	  wecho("\n\nArquivo Excel {$pr->nomarq} gerado com Sucesso !\n\n.\n");

//	  if ($pr->options['aut_excel']) {
//		$shell = new COM('WScript.Shell');
//		$shell->Run('excel ..\\Resultados\\' . $pr->nomarq);
//		unset($shell);
//	  }


}


?>