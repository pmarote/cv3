<?php

$pr->aud_registra(new PrMenu("dfe_audit", "DF_e", "Auditorias de DFEs", "dfe"));

function dfe_audit() {

  global $pr;


  $pr->inicia_excel('DFe_Audit');
  
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';

	// Planilha índices
	$sql = "
SELECT substr(dhEmi, 1, 4) || substr(dhEmi, 6, 2) AS aaaamm, UFIni, UFFim, pICMS, sum(vTPrest) AS vTPrest, sum(vBC) AS vBC, sum(vICMS) AS vICMS FROM cte
   GROUP BY aaaamm, UFIni, UFFim, pICMS;
";
	$col_format = array(
	"D:G" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = array(
	'aaaamm' => 'Ano Mês',
	'UFIni' => 'UF início do transporte',
	'UFFim' => 'UF fim do transporte',
	'pICMS' => 'Alíquota do ICMS',
	'vTPrest' => 'Valor total da Prestação',
	'vBC' => 'Base de Cálculo do ICMS',
	'vICMS' => 'Valor do ICMS');
	$pr->abre_excel_sql('cte_ini_fim_aliq', 'CTEs - Verificar: UFIni -> ICMS em SP  UFFim -> Alíquotas   vTPrest = vBC', $sql, $col_format, $cabec, $form_final);
  

	
  $pr->finaliza_excel();
}

?>