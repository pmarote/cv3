<?php

$pr->aud_registra(new PrMenu("ecd_razao", "E_CD", "Livro Razão", "ecd"));

function ecd_razao() {

  global $pr;

  $dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 500);

  $lbl_obs1 	= new GtkLabel("Este modulo gera o equivalente ao Livro Diário em arquivos Excel");
  $lbl_obs2 	= new GtkLabel("Se não forem especificadas as contas abaixo, será gerado para todas as contas analíticas, com um");
  $lbl_obs3 	= new GtkLabel("arquivo Excel para cada Natureza de Conta. Gerar para todas as contas pode ser uma tarefa demorada");
  $lbl_obs4 	= new GtkLabel("Assim, preferencialmente, insira no quadro abaixo as contas de diário desejadas.");

  $textBuffer = new GtkTextBuffer();
  $scrolledwindow = new GtkScrolledWindow();
  $scrolledwindow->viewer = new GtkTextView();
  $scrolledwindow->set_policy(Gtk::POLICY_NEVER,Gtk::POLICY_ALWAYS); 
  $scrolledwindow->viewer->set_wrap_mode(Gtk::WRAP_WORD_CHAR);
  $textBuffer->set_text("");
  $scrolledwindow->viewer->set_buffer($textBuffer);
  $scrolledwindow->add($scrolledwindow->viewer);  
  
  $dialog->vbox->pack_start($lbl_obs1, false, false, 3);
  $dialog->vbox->pack_start($lbl_obs2, false, false, 3);
  $dialog->vbox->pack_start($lbl_obs3, false, false, 3);
  $dialog->vbox->pack_start($lbl_obs4, false, false, 3);
  $dialog->vbox->pack_start(new GtkHSeparator(), false, false, 3);
  $dialog->vbox->pack_start($scrolledwindow);

  $dialog->add_button(Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL);
  $dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);

  $dialog->set_has_separator(True);
  $dialog->show_all();
  $response_id = $dialog->run();
  $dialog->destroy();

  if ($response_id != Gtk::RESPONSE_OK) return;

  $contas_sel = explode("\n", $textBuffer->get_text($textBuffer->get_start_iter(), $textBuffer->get_end_iter()));
  foreach($contas_sel as $indice => $valor) {
     $contas_sel[$indice] = trim($valor);
	 if ($contas_sel[$indice] == '') unset($contas_sel[$indice]);
  }
  
  // Planilhas Razão
  $col_format = array(
	"F:G" => "0",
	"H:H" => "#.##0,00",
	"N:N" => "#.##0,00");
  $cabec = array(
	'Num_Lcto' => "Número do Lançamento", 
	'Qtd Déb' => "Quantidade de Débitos no Lançamento", 
	'Qtd Créd' => "Quantidade de Créditos no Lançamento", 
	'Data' => "Data do Lançamento", 
	'Ind_Lcto' => "Indicador do Lançamento:
N - Normal
E - Lançamento de Encerramento de Ctas de Resultado", 
	'Cta Débito' => "Código da Conta de Débito",
	'Cta Crédito' => "Código da Conta de Crédito",
	'Valor' => "Valor do Lançamento (Débito ou Crédito)",
	'Histórico' => "Descrição do Lançamento",
	'Padrão_S' => "Padrão Soundex",
	'Padrão_M' => "Padrão Metaphone",
	'Pad.Nr' => "Número retirado do Histórico",
	'Desc. Contrapartida' => "Descrição da Conta de Contrapartida",
	'Saldo' => "Saldo",
	'D/C' => "Indicador de Débito ou Crédito");

  if (count($contas_sel) == 0) {
	// Sem contas selecionadas, serão abertos vários Excels, um para cada natureza. Exemplo: ECD_Razao_1 -> Ativo; ECD_Razao_2 -> Passivo; etc...
	$arqs_excel = $pr->aud_sql2array("
SELECT DISTINCT cod_nat AS grupo_conta FROM saldos
    LEFT OUTER JOIN contas ON contas.cod_cta = saldos.cod_cta;");
  } else {
	// Se houver contas selecionadas, um excel para cada conta, caso exista em Saldos
	$list_in = '(';
	foreach($contas_sel as $indice => $valor) $list_in .= "'{$valor}', ";
	$list_in = substr($list_in, 0, -2) . ')';
	$arqs_excel = $pr->aud_sql2array("
SELECT DISTINCT cod_cta AS grupo_conta FROM saldos
   WHERE cod_cta IN {$list_in}
   ORDER BY cod_cta DESC;");
  }

  foreach ($arqs_excel as $ind_exc => $val_exc) {
//    $nome_excel = str_replace($val_exc['grupo_conta'], '.\\/:\*?<>|', '_');  // Dá uma limpada no nome do arquivo, caso necessário, por problemas do Windows
    $nome_excel = str_replace('<', '_', str_replace('>', '_', str_replace('.', '_', $val_exc['grupo_conta'])));  // Dá uma limpada no nome do arquivo, caso necessário, por problemas do Windows
	echo "\n{$val_exc['grupo_conta']}##$nome_excel##\n";
	$pr->inicia_excel("ECD_Razao_{$nome_excel}");
	// Se não houver seleção, serão feitas várias planilhas por arquivo Excel.
	if (count($contas_sel) == 0) $parametros = $pr->aud_sql2array("
SELECT codigo_conta, cta FROM
       (SELECT DISTINCT cod_cta AS codigo_conta FROM saldos) AS sel1
      LEFT OUTER JOIN contas ON contas.cod_cta = sel1.codigo_conta
      WHERE nivel = {$pr->sql_params['ecd']['max_nivel']} AND cod_nat = '{$val_exc['grupo_conta']}'
      ORDER BY codigo_conta DESC;");
	// Se houver seleção, será feita apenas uma planilha por arquivo Excel.
	if (count($contas_sel) != 0) {
	  $parametros = array();
	  $sql_desc_cta = $pr->aud_sql2array("SELECT cta FROM contas WHERE cod_cta = '{$val_exc['grupo_conta']}'");
	  $parametros[0]['codigo_conta'] = $val_exc['grupo_conta'];
	  $parametros[0]['cta'] = $sql_desc_cta[0]['cta'];
	}
	if (count($parametros > 0)) {
	  foreach($parametros as $indice => $valor) {
		$sql = "
SELECT '' AS num_lcto, '' AS nro_deb, '' AS nro_cred, '' AS dt_lcto, '' AS ind_lcto,
   '' AS cod_cta_d, '' AS cod_cta_c, '' AS valtot, '##znc##Seção 1 - Fluxos - Resumo das Contrapartidas' AS hist,
   '' AS padrao_s, '' AS padrao_m, '' As padrao_nr, '' AS contra_partida, '' AS saldo, '' AS dc;
SELECT '' AS num_lcto, '' AS nro_deb, '' AS nro_cred, '' AS dt_lcto, '' AS ind_lcto, cod_cta_d, cod_cta_c, valtot,
   '' AS hist, '' AS padrao_s, '' AS padrao_m, '' As padrao_nr, contra_partida, '' AS saldo, '' AS dc FROM
  (SELECT cod_cta_d || cod_cta_c AS grupo, cod_cta_d, cod_cta_c, sum(valor) AS valtot,
  contra_partida
  FROM razoes_aux
  WHERE cod_cta_busca = '{$valor['codigo_conta']}' AND hist <> 'ZeraAcum' AND num_lcto <> '|' AND num_lcto <> ''
  GROUP BY grupo
  ORDER BY valtot DESC);
--  AND num_lcto <> '|'   Exclui Saldo Final
--  AND num_lcto <> ''    Exclui Saldo Inicial
SELECT '' AS num_lcto, '' AS nro_deb, '' AS nro_cred, '' AS dt_lcto, '' AS ind_lcto,
   '' AS cod_cta_d, '' AS cod_cta_c, '' AS valtot, '##znc##Sação 2 - Razão' AS hist,
   '' AS padrao_s, '' AS padrao_m, '' As padrao_nr, '' AS contra_partida, '' AS saldo, '' AS dc;
SELECT num_lcto, nro_deb, nro_cred, dt_lcto, ind_lcto, cod_cta_d, cod_cta_c, valor, hist,
  padrao_s, padrao_m, padrao_nr, contra_partida,
  abs(saldo),
  CASE WHEN saldo < 0 THEN 'D' ELSE 
    CASE WHEN saldo > 0 THEN 'C' ELSE '' END
  END AS saldo_dc
  FROM razoes_aux
  WHERE cod_cta_busca = '{$valor['codigo_conta']}' AND hist <> 'ZeraAcum' AND num_lcto <> '|';
";
		$form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_largura_coluna("B:C", 4);
	$this->excel_largura_coluna("E:E", 4);
	$this->excel_largura_coluna("I:I", 50);
	$this->excel_largura_coluna("J:K", 6);
	$this->excel_largura_coluna("M:M", 35);
	$this->excel_zoom_visualizacao(75);
';
		$pr->abre_excel_sql("Razao_{$valor['codigo_conta']}", "Fluxos e Razão da Conta {$valor['codigo_conta']} - {$valor['cta']}", $sql, $col_format, $cabec, $form_final);
	  }
	}
	$pr->finaliza_excel();
  }
  


}


?>