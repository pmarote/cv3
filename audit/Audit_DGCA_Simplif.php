<?php

$pr->aud_registra(new PrMenu("audit_dgca_simplif", "_Audit", "DGCAs Simplificados(GIAs e EFDs)", "audit"));

function audit_dgca_simplif() {

  global $pr;

  $pr->inicia_excel('Audit_DGCA_Simplif');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';

	// Tabela dgca_simplif - fórmulas para cálculos dos DGCAs simplificados
	$createtable = "
DROP TABLE IF EXISTS dgca_simplif;
CREATE TABLE dgca_simplif (cfop int, valcon_s text, valcon_e text, bcicms_e text, icms_pmc text);
CREATE INDEX dgca_simplif_chapri ON dgca_simplif (cfop ASC);
";
	create_table_from_txt($pr->db, $createtable, 'res\tabelas\dgca_simplif.txt', 'dgca_simplif');	

	$pr->aud_prepara("
-- dgca analítico
DROP TABLE IF EXISTS dgca_analit;	
CREATE TABLE dgca_analit AS
  SELECT a_c_t.*, dgca_simplif.*,
    CASE WHEN valcon_s = '-' THEN -valcon ELSE 
         CASE WHEN valcon_s = '+' THEN valcon ELSE 0 END
    END AS saidas,
    CASE WHEN valcon_e = '-' THEN -valcon ELSE 
         CASE WHEN valcon_e = '+' THEN valcon ELSE 0 END
    END AS entradas_valcon,
    CASE WHEN bcicms_e = '-' THEN -bcicms ELSE 
         CASE WHEN bcicms_e = '+' THEN bcicms ELSE 0 END
    END AS entradas_bcicms,
    CASE WHEN icms_pmc = '-' THEN -icms ELSE 
         CASE WHEN icms_pmc = '+' THEN icms ELSE 0 END
    END AS pmc_num
    FROM
    (SELECT  modelo.aaaamm, modelo.cfop, modelo.tp_origem, sum(valcon) AS valcon, sum(bcicms) AS bcicms, sum(icms) AS icms
        FROM modelo
        WHERE modelo.tp_origem IN ('GIA','RES')
        GROUP BY aaaamm, cfop, tp_origem) AS a_c_t
    LEFT OUTER JOIN dgca_simplif ON dgca_simplif.cfop = a_c_t.cfop;
DROP TABLE IF EXISTS dgca_analit_indices;
CREATE TABLE dgca_analit_indices AS
SELECT ano, aaaamm, tp_origem, VVD_CA, VCP_CA, VVD_CA - VCP_CA AS Resultado, (VVD_CA - VCP_CA) / VCP_CA * 100 AS IVA_CA,
    PMC_CA_NUM, PMC_CA_NUM / VCP_CA * 100 AS PMC
    FROM
    (SELECT substr(aaaamm, 1, 4) AS ano, aaaamm, tp_origem, sum(saidas) AS VVD_CA, sum(entradas_valcon) +  sum(entradas_bcicms) AS VCP_CA, sum(pmc_num) AS PMC_CA_NUM
        FROM dgca_analit
        GROUP BY aaaamm, tp_origem);
");

	// Planilha índices
	$tabela = 'dgca_analit_indices';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"A:B" => "0",
	"D:F" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"G:G" => "#.##0,0000_ ;[Vermelho]-#.##0,0000 ",
	"H:H" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"I:I" => "#.##0,0000_ ;[Vermelho]-#.##0,0000 "
);
	$cabec = $pr->auto_cabec($tabela);
	$pr->abre_excel_sql('dgca_analit_indices', 'dgca_analit_indices', $sql, $col_format, $cabec, $form_final);
  

	// Planilha dgca_analítica
	$tabela = 'dgca_analit';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"B:B" => "0",
	"D:F" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"G:G" => "0",
	"L:O" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = $pr->auto_cabec($tabela);
	$pr->abre_excel_sql('dgca_analit', 'dgca_analit', $sql, $col_format, $cabec, $form_final);
  
  $pr->finaliza_excel();
}


?>