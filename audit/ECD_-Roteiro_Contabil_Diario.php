<?php

$pr->aud_registra(new PrMenu("ecd_rot_contabil_diario", "E_CD", "Roteiro Contábil - Diário", "ecd"));

function ecd_rot_contabil_diario() {

  global $pr;

  // Linhas abaixo para gerar as Datas Default
  $dia_ini_fin = $pr->aud_sql2array("
	SELECT min(dt_lcto) AS dtaini, max(dt_lcto) AS dtafin FROM diario;
");
  $dia_ini = explode("-", $dia_ini_fin[0]['dtaini']);
  $dia_fin = explode("-", $dia_ini_fin[0]['dtafin']);
  
  // Contagem do Número total de Lançamentos
  $contlanc = $pr->aud_sql2array("
	SELECT count(*) AS contagem FROM diario;
");
  $contlanc[0]['contagem'] = number_format($contlanc[0]['contagem'], 0, ',', '.');
  
  $dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 500);

  $lbl_obs1 	= new GtkLabel("Este modulo exporta os registros 'puros' de diário, sem a decodificação de créditos e débitos");
  $lbl_obs2 	= new GtkLabel("No caso de grande quantidade de registros, sugerimos que sejam colocadas no quadro abaixo");
  $lbl_obs3 	= new GtkLabel("apenas o intervalo de Datas desejado, no formato dd/mm/aaaa-dd/mm/aaaa. Exemplo:");
  $lbl_obs4 	= new GtkLabel("10/03/2009-20/03/2009");
  $lbl_obs5 	= new GtkLabel("01/06/2009-30/09/2009");
  $lbl_obs6 	= new GtkLabel("Total de Registros presentes no Banco de Dados: {$contlanc[0]['contagem']}");

  $textBuffer = new GtkTextBuffer();
  $scrolledwindow = new GtkScrolledWindow();
  $scrolledwindow->viewer = new GtkTextView();
  $scrolledwindow->set_policy(Gtk::POLICY_NEVER,Gtk::POLICY_ALWAYS); 
  $scrolledwindow->viewer->set_wrap_mode(Gtk::WRAP_WORD_CHAR);
  $textBuffer->set_text("{$dia_ini[2]}/{$dia_ini[1]}/{$dia_ini[0]}-{$dia_fin[2]}/{$dia_fin[1]}/{$dia_fin[0]}");
  $scrolledwindow->viewer->set_buffer($textBuffer);
  $scrolledwindow->add($scrolledwindow->viewer);  
  
  $dialog->vbox->pack_start($lbl_obs1, false, false, 3);
  $dialog->vbox->pack_start($lbl_obs2, false, false, 3);
  $dialog->vbox->pack_start($lbl_obs3, false, false, 3);
  $dialog->vbox->pack_start($lbl_obs4, false, false, 3);
  $dialog->vbox->pack_start($lbl_obs5, false, false, 3);
  $dialog->vbox->pack_start($lbl_obs6, false, false, 3);
  $dialog->vbox->pack_start(new GtkHSeparator(), false, false, 3);
  $dialog->vbox->pack_start($scrolledwindow);

  $dialog->add_button(Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL);
  $dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);

  $dialog->set_has_separator(True);
  $dialog->show_all();
  $response_id = $dialog->run();

  if ($response_id != Gtk::RESPONSE_OK) {
	$dialog->destroy();
	return;
  }

  $adatas = array();
  do {
	$bcontinua = False;

	$datas_sel = explode("\n", $textBuffer->get_text($textBuffer->get_start_iter(), $textBuffer->get_end_iter()));
	$icontador = 0;
	$ilinha = 0;
	foreach($datas_sel as $indice => $valor) {
	  $ilinha++;
	  $dia_ini_fin = explode("-", $valor);
	  if (count($dia_ini_fin) == 2) {  // ou seja, há data inicial e final
		$dia_ini = explode("/", $dia_ini_fin[0]);
		$dia_fin = explode("/", $dia_ini_fin[1]);
		if (count($dia_ini) == 3 && count($dia_fin) == 3 && checkdate($dia_ini[1],$dia_ini[0],$dia_ini[2]) && checkdate($dia_fin[1],$dia_fin[0],$dia_fin[2])) {
		  // Datas válidas... agora é só saber se a inicial é menor ou igual à final
		  if (mktime(0, 0, 0, $dia_ini[1],$dia_ini[0],$dia_ini[2]) <= mktime(0, 0, 0, $dia_fin[1],$dia_fin[0],$dia_fin[2])) {
			$adatas[$icontador]['dtaini'] = substr('20' . $dia_ini[2], -4) . '-' . substr('0' . $dia_ini[1], -2) . '-' . substr('0' . $dia_ini[0], -2);
			$adatas[$icontador]['dtafin'] = substr('20' . $dia_fin[2], -4) . '-' . substr('0' . $dia_fin[1], -2) . '-' . substr('0' . $dia_fin[0], -2);
			$icontador++;
		  }
		} else { 
		  werro("Erro... Data inválida na linha {$ilinha}"); 
		  $bcontinua = True;
		}
	  } else { 
		werro("Erro... Formato de Data inválido na linha {$ilinha}"); 
		$bcontinua = True;
	  }
	}
	if (count($datas_sel) == 0) {
	  werro("Erro... É necessária a indicação de data inicial e final..."); 
	  $bcontinua = True;
	}
    if ($bcontinua) {
	  $response_id = $dialog->run();
	  if ($response_id != Gtk::RESPONSE_OK) {
		$dialog->destroy();
		return;
	  }
	}
  } while ($bcontinua);

  $dialog->destroy();

  foreach($adatas as $adatas_indice => $adatas_valor) {

	$pr->inicia_excel('ECD_Roteiro_Contabil_Diario' . $adatas_valor['dtaini'] . '_' . $adatas_valor['dtafin']);

	// Planilha Diário
	$sql = "
SELECT num_lcto, 
   dt_lcto, ind_lcto, cod_cta, cod_nat, vl_dc, ind_dc, hist, 
   lanctoaux.cta, lanctoaux.cod_cta_ref, plactaref.cta, padrao_s, padrao_m, padrao_nr,
   cta_n1, cta_n2, cta_n3, cta_n4, cta_n5, num_arq, lanctoaux.cod_ccus, i100.ccus
   FROM
   (SELECT diario.*, contas.cta AS cta, contas.cod_nat AS cod_nat, contas.cod_cta_ref AS cod_cta_ref,
   contas.cod_cta_n1 AS cta_n1, contas.cod_cta_n2 AS cta_n2, contas.cod_cta_n3 AS cta_n3, 
   contas.cod_cta_n4 AS cta_n4, contas.cod_cta_n5 AS cta_n5, num_arq
   FROM diario
   LEFT OUTER JOIN contas ON diario.cod_cta = contas.cod_cta WHERE dt_lcto >= '{$adatas_valor['dtaini']}' AND dt_lcto <= '{$adatas_valor['dtafin']}') as lanctoaux
   LEFT OUTER JOIN plactaref ON plactaref.cod_cta_ref = lanctoaux.cod_cta_ref
   LEFT OUTER JOIN i100 ON i100.cod_ccus = lanctoaux.cod_ccus;
";
	$col_format = array(
	"A:A" => "#.##0",
	"D:D" => "0",
	"E:E" => "00",
	"F:F" => "#.##0,00",
	"J:J" => "0",
	"O:U" => "0");
	$cabec = array(
	'Num Lcto' => "Número do Lançamento", 
	'Data' => "Data do Lançamento", 
	'Ind Lcto' => "Indicador do Lançamento:
N - Normal
E - Lançamento de Encerramento de Ctas de Resultado", 
	'Conta' => "Código da Conta",
	"Nat Cta" => "Código da Natureza da Conta, podendo ser:
01  Contas de ativo  
02  Contas de passivo  
03  Patrimônio líquido  
04  Contas de resultado  
05  Contas de compensação
09  Outras",
	'Valor' => "Valor do Lançamento (Débito ou Crédito)",
	'D/C' => "Indicador de Débito ou Crédito", 
	'Histórico' => "Descrição do Lançamento",
	'Descrição da Conta' => "Descrição da Conta",
	"Cód Cta Ref" => "Código da Conta do Plano de Contas Referencial",
	"Nome Cta Ref"	=> "Nome da Conta do Plano de Contas Referencial",
	'Padrão_S' => "Padrão Soundex",
	'Padrão_M' => "Padrão Metaphone",
	'Pad.Nr' => "Número retirado do Histórico",
	'Cta N1' => "Código da Conta de Nível 1",
	'Cta N2' => "Código da Conta de Nível 2",
	'Cta N3' => "Código da Conta de Nível 3",
	'Cta N4' => "Código da Conta de Nível 4",
	'Cta N5' => "Código da Conta de Nível 5",
	'Num_Arq' => "Número, Código ou caminho de localização dos documentos arquivados.",
	'Cod_CCus' => "Código do centro de custos.",
	'CCus' => "Nome do centro de custos.");

	$form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_largura_coluna("C:C", 4);
	$this->excel_largura_coluna("E:E", 4);
	$this->excel_largura_coluna("G:G", 4);
	$this->excel_largura_coluna("H:H", 50);
	$this->excel_largura_coluna("N:N", 12);
	$this->excel_largura_coluna("O:S", 6);
	$this->excel_zoom_visualizacao(75);
';
	$pr->abre_excel_sql('Diario', 'Diario ' . $adatas_valor['dtaini'] . ' a ' . $adatas_valor['dtafin'] . ' (Registros I200 e I250)', $sql, $col_format, $cabec, $form_final);
  
	$pr->finaliza_excel();
  }
}


?>