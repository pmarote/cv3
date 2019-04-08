<?php

$pr->aud_registra(new PrMenu("ecd_rot_contabil_lanc", "E_CD", "Roteiro Contábil - Diário (Contrapartidas Identificadas)", "ecd"));

function ecd_rot_contabil_lanc() {

  global $pr;

  // Linhas abaixo para gerar as Datas Default
  $dia_ini_fin = $pr->aud_sql2array("
	SELECT min(dt_lcto) AS dtaini, max(dt_lcto) AS dtafin FROM lancto;
");
  $dia_ini = explode("-", $dia_ini_fin[0]['dtaini']);
  $dia_fin = explode("-", $dia_ini_fin[0]['dtafin']);
  
  // Contagem do Número total de Lançamentos
  $contlanc = $pr->aud_sql2array("
	SELECT count(*) AS contagem FROM lancto;
");
  $contlanc[0]['contagem'] = number_format($contlanc[0]['contagem'], 0, ',', '.');
  
  $dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 500);

  $lbl_obs1 	= new GtkLabel("Este modulo exporta o Livro Diário Decodificado, ou seja, com a identificação das Contrapartidas, listando, ");
  $lbl_obs2 	= new GtkLabel("em cada linha, os respectivos débitos e créditos. No caso de grande quantidade de linhas, sugerimos que sejam");
  $lbl_obs3 	= new GtkLabel("colocadas no quadro abaixo apenas o intervalo de Datas desejado, no formato dd/mm/aaaa-dd/mm/aaaa. Exemplo:");
  $lbl_obs4 	= new GtkLabel("10/03/2009-20/03/2009");
  $lbl_obs5 	= new GtkLabel("01/06/2009-30/09/2009");
  $lbl_obs6 	= new GtkLabel("Total de linhas decodificadas com contrapartidas, dos lançamentos presentes no Banco de Dados: {$contlanc[0]['contagem']}");

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

	$pr->inicia_excel('ECD_Roteiro_Contabil_Diario_Contrapartidas' . $adatas_valor['dtaini'] . '_' . $adatas_valor['dtafin']);

	// Planilha Lancamentos
	$sql = "
SELECT num_lcto, nro_deb, nro_cred,
   dt_lcto, ind_lcto, cod_cta_d, nat_debito, cod_cta_c, nat_credito, valor, hist, 
   padrao_s, padrao_m, padrao_nr, cta_debito, cta_credito,
   cod_cta_ref_d, cod_cta_ref_c, plactaref_d_cta, plactaref_c_cta, cta_d_n1, cta_d_n2, cta_d_n3, cta_d_n4, cta_d_n5,
   cta_c_n1, cta_c_n2, cta_c_n3, cta_c_n4, cta_c_n5, ord, num_arq ,obs
   FROM
   (SELECT lancto.*, contas_d.cta AS cta_debito, contas_d.cod_nat AS nat_debito, 
   contas_d.cod_cta_ref AS cod_cta_ref_d, plactaref_d.cta AS plactaref_d_cta, 
   contas_d.cod_cta_n1 AS cta_d_n1, contas_d.cod_cta_n2 AS cta_d_n2, contas_d.cod_cta_n3 AS cta_d_n3, 
   contas_d.cod_cta_n4 AS cta_d_n4, contas_d.cod_cta_n5 AS cta_d_n5,
   i250.num_arq AS num_arq, contas_c.cta AS cta_credito, contas_c.cod_nat AS nat_credito, 
   contas_c.cod_cta_ref AS cod_cta_ref_c, plactaref_c.cta AS plactaref_c_cta, 
   contas_c.cod_cta_n1 AS cta_c_n1, contas_c.cod_cta_n2 AS cta_c_n2, contas_c.cod_cta_n3 AS cta_c_n3, 
   contas_c.cod_cta_n4 AS cta_c_n4, contas_c.cod_cta_n5 AS cta_c_n5
   FROM lancto
   LEFT OUTER JOIN contas AS contas_d ON cod_cta_d = contas_d.cod_cta
   LEFT OUTER JOIN plactaref AS plactaref_d ON plactaref_d.cod_cta_ref = contas_d.cod_cta_ref
   LEFT OUTER JOIN contas AS contas_c ON cod_cta_c = contas_c.cod_cta
   LEFT OUTER JOIN plactaref AS plactaref_c ON plactaref_c.cod_cta_ref = contas_c.cod_cta_ref
   LEFT OUTER JOIN i250 ON i250.ord = lancto.ord
    WHERE dt_lcto >= '{$adatas_valor['dtaini']}' AND dt_lcto <= '{$adatas_valor['dtafin']}') as lanctoaux
   ORDER BY dt_lcto, num_lcto, ord;
";
	$col_format = array(
	"A:C" => "#.##0",
	"F:H" => "0",
	"G:G" => "00",
	"I:I" => "00",
	"J:J" => "#.##0,00",
	"Q:R" => "0",
	"U:AE" => "0");
	$cabec = array(
	'Num Lcto' => "Número do Lançamento", 
	'Qtd Déb' => "Quantidade de Débitos no Lançamento", 
	'Qtd Créd' => "Quantidade de Créditos no Lançamento", 
	'Data' => "Data do Lançamento", 
	'Ind Lcto' => "Indicador do Lançamento:
N - Normal
E - Lançamento de Encerramento de Ctas de Resultado", 
	'Cta Débito' => "Código da Conta de Débito",
	"Nat Deb" => "Código da Natureza da conta débito, podendo ser:
01  Contas de ativo  
02  Contas de passivo  
03  Patrimônio líquido  
04  Contas de resultado  
05  Contas de compensação
09  Outras",
	'Cta Crédito' => "Código da Conta de Crédito",
	"Nat Cred" => "Código da Natureza da conta crédito, podendo ser:
01  Contas de ativo  
02  Contas de passivo  
03  Patrimônio líquido  
04  Contas de resultado  
05  Contas de compensação
09  Outras",
	'Valor' => "Valor do Lançamento (Débito ou Crédito)",
	'Histórico' => "Descrição do Lançamento",
	'Padrão_S' => "Padrão Soundex",
	'Padrão_M' => "Padrão Metaphone",
	'Pad.Nr' => "Número retirado do Histórico",
	'Desc. Cta Débito' => "Descrição da Conta de Débito",
	'Desc. Cta Crédito' => "Descrição da Conta de Crédito",
	"Cód Cta Ref Deb" => "Código da Conta Débito do Plano de Contas Referencial",
	"Cód Cta Ref Créd" => "Código da Conta Crédito do Plano de Contas Referencial",
	"Nome Cta Ref Deb"	=> "Nome da Conta Débito do Plano de Contas Referencial",
	"Nome Cta Ref Créd"	=> "Nome da Conta Crédito do Plano de Contas Referencial",
	'Cta Déb N1' => "Código da Conta de Débito de Nível 1",
	'Cta Déb N2' => "Código da Conta de Débito de Nível 2",
	'Cta Déb N3' => "Código da Conta de Débito de Nível 3",
	'Cta Déb N4' => "Código da Conta de Débito de Nível 4",
	'Cta Déb N5' => "Código da Conta de Débito de Nível 5",
	'Cta Créd N1' => "Código da Conta de Crédito de Nível 1",
	'Cta Créd N2' => "Código da Conta de Crédito de Nível 2",
	'Cta Créd N3' => "Código da Conta de Crédito de Nível 3",
	'Cta Créd N4' => "Código da Conta de Crédito de Nível 4",
	'Cta Créd N5' => "Código da Conta de Crédito de Nível 5",
	'Ord' => "Número(s) da(s) Linha(s) no Arquivo ECD (Ordem) do Reg.I250",
	'Num_Arq' => "Número, Código ou caminho de localização dos documentos arquivados",
	'Obs' => "Observações do Lançamento, normalmente Soluções Aplicadas"
);

	$form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_largura_coluna("B:C", 4);
	$this->excel_largura_coluna("E:E", 4);
	$this->excel_largura_coluna("G:G", 4);
	$this->excel_largura_coluna("I:I", 4);
	$this->excel_largura_coluna("K:K", 50);
	$this->excel_largura_coluna("L:M", 6);
	$this->excel_largura_coluna("O:P", 35);
	$this->excel_zoom_visualizacao(75);
';
	$pr->abre_excel_sql('Diario_Contrapartidas', 'Livro Diário (Contrapartidas Identificadas) ' . $adatas_valor['dtaini'] . ' a ' . $adatas_valor['dtafin'], $sql, $col_format, $cabec, $form_final);
  
	$pr->finaliza_excel();
  }
}


?>