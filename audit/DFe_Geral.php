<?php

$pr->aud_registra(new PrMenu("dfe_tabelas", "DF_e", "Todas as Tabelas", "dfe"));

function dfe_tabelas() {

  global $pr;


  $dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 100);

  $lbl_obs1 	= new GtkLabel("Este módulo exporta para Excel as seguintes tabelas que estão dentro de dfe.db3:");
  $dialog->vbox->pack_start($lbl_obs1, false, false, 3);

  $chkbuttons = array();
  
  $lista_tabelas = db_lista_tabelas($pr->db);
  //debug_log(print_r($tabelas, True));

	foreach ($lista_tabelas as $indice => $valor) {
		$chkbuttons[$indice] = new GtkCheckButton(str_replace('_', '__', $valor));
		$chkbuttons[$indice]->set_active(True);
		$dialog->vbox->pack_start($chkbuttons[$indice], false, false, 3);
	}
	$dialog->add_button("Inverter Seleção", 100);
	$dialog->add_button(Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL);
	$dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);
	$dialog->set_has_separator(True);
	$dialog->show_all();
	do {
		$response_id = $dialog->run();
		if ($response_id == 100) {
			foreach ($lista_tabelas as $indice => $valor) {
				$chkbuttons[$indice]->set_active(!$chkbuttons[$indice]->get_active());
			}
		}
	} while ($response_id == 100);
	if ($response_id != Gtk::RESPONSE_OK) {
		$dialog->destroy();
		return;
	}
	$dialog->destroy();


  $pr->inicia_excel('DFe_TodasTabelas');
  
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';

	foreach ($lista_tabelas as $indice => $tabela) {
		if ($chkbuttons[$indice]->get_active()) {
			$sql = "SELECT * FROM {$tabela};";
			$col_format = array(
);
			$cabec = $pr->auto_cabec($tabela);
			$pr->abre_excel_sql(substr($tabela, 0, 15), $tabela, $sql, $col_format, $cabec, $form_final);
		}
	}

  $pr->finaliza_excel();
}

?>