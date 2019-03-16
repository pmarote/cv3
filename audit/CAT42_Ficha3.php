<?php

$pr->aud_registra(new PrMenu("cat42_ficha3", "CAT_42", "Ficha 3 - Cálculo da CAT42", "cat42"));

function cat42_ficha3() {

  global $pr;

  $pr->inicia_excel('Cat43_Ficha3');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(100);
';

  $tabela = "aux1";
  $sql = "
SELECT * FROM {$tabela};";
  $col_format = array(
		"H:H" => "0"
);
  $cabec = $pr->auto_cabec($tabela);
  $pr->abre_excel_sql('Ficha_3', 'Ficha 3', $sql, $col_format, $cabec, $form_final);
  
  $pr->finaliza_excel();
}


?>