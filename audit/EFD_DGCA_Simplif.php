<?php

$pr->aud_registra(new PrMenu("efd_dgca_simplif", "E_FD", "DGCAs Simplificados", "efd"));

function efd_dgca_simplif() {

  global $pr;

  $pr->inicia_excel('EFD_DGCA_Simplif');

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
-- Criação da tabela que junta todos os registros c190 e similares
DROP TABLE IF EXISTS junt_90s;	
CREATE TABLE junt_90s AS 
SELECT 
    'c190' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, vl_red_bc, vl_ipi
    FROM c190
UNION ALL
SELECT 
    'c490' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, 0 AS vl_red_bc, 0 AS vl_ipi
    FROM c490
UNION ALL
SELECT 
    'c590' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, vl_red_bc, 0 AS vl_ipi
    FROM c590
UNION ALL
SELECT 
    'c850' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, 0 AS vl_red_bc, 0 AS vl_ipi
    FROM c850
UNION ALL
SELECT 
    'c890' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, 0 AS vl_red_bc, 0 AS vl_ipi
    FROM c890
UNION ALL
SELECT 
    'd190' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, vl_red_bc, 0 AS vl_ipi
    FROM d190
UNION ALL
SELECT 
    'd590' AS origem, ord, round(ord / 10000000 - 0.49) AS aamm, cst_icms, cfop, aliq_icms, vl_opr, vl_bc_icms, vl_icms, vl_red_bc, 0 AS vl_ipi
    FROM d590;
-- dgca analítico
DROP TABLE IF EXISTS dgca_analit;	
CREATE TABLE dgca_analit AS
SELECT 
   junt_90s.*, dgca_simplif.*,
   CASE WHEN valcon_s = '-' THEN -vl_opr ELSE 
        CASE WHEN valcon_s = '+' THEN vl_opr ELSE 0 END
   END AS saidas,
   CASE WHEN valcon_e = '-' THEN -vl_opr ELSE 
        CASE WHEN valcon_e = '+' THEN vl_opr ELSE 0 END
   END AS entradas_valcon,
   CASE WHEN bcicms_e = '-' THEN -vl_bc_icms ELSE 
        CASE WHEN bcicms_e = '+' THEN vl_bc_icms ELSE 0 END
   END AS entradas_bcicms,
   CASE WHEN icms_pmc = '-' THEN -vl_icms ELSE 
        CASE WHEN icms_pmc = '+' THEN vl_icms ELSE 0 END
   END AS pmc_den
   FROM junt_90s
   LEFT OUTER JOIN dgca_simplif ON dgca_simplif.cfop = junt_90s.cfop;
");

	// Planilha Resumo Geral
	$sql = "
SELECT '##NT##Resumo Geral de DGCAs - Registros Analíticos    Total de Linhas: ', count(*) FROM dgca_analit;
SELECT DISTINCT '##NI##EFD:0000 Empresa: CNPJ ' || cnpj ||  ' IE ' || ie || ' NOME ' || nome FROM o000;
SELECT DISTINCT '##NI##EFD:0005 Fantasia: ' || fantasia ||  ' Endereço: ' || end || '  ' || num || '  ' || compl || '  ' || bairro || ' Fone: ' || fone || ' email: ' || email FROM o005;
SELECT DISTINCT '##NI##EFD:0100 Contabilista: ' || nome ||  ' cpf: ' || cpf || ' crc: ' || crc || ' cnpj: ' || cnpj || ' Endereço: ' || end || '  ' || num || '  ' || compl || '  ' || bairro || ' Fone: ' || fone || ' email: ' || email FROM o100;
SELECT '';
SELECT '##NI##      Arquivos SPED de: ' || min(dt_ini) || ' a ' || max(dt_fin) || ', qtd arquivos =', count(*) FROM o000;
SELECT '';
SELECT 'Indices:', '##NI##VVD-CA', '##NI##VCP-CA', '##NI##VVD-CA - VCP-CA', '##NI##IVA-CA', '##NI##PMC-CA';
SELECT '', vvd_ca, vcp_ca, vvd_ca - vcp_ca AS resultado, (vvd_ca - vcp_ca) / vcp_ca * 100 AS iva_ca, pcm_den / vcp_ca * 100 AS pcm_ca FROM 
    (SELECT sum(saidas) AS vvd_ca, sum(entradas_valcon) + sum(entradas_bcicms) AS vcp_ca, sum(pmc_den) AS pcm_den FROM  dgca_analit);
";
	$col_format = array(
		"A:A" => "0",
		"B:B" => "#.##0",
		"C:F" => "#.##0,00_ ;[Vermelho]-#.##0,00 ");

	$cabec = array(
	'Descrição' => 'Descrição',
	'Qtd' => 'Quantidade',
	'Saídas-CA' => 'Saídas conforme CFOPs de Crédito Acumulado (Portaria Cat 207/2009 Anexo III)',
	'Entradas-ValCon' => 'Entradas, valores contábeis, conforme CFOPs de Crédito Acumulado (Portaria Cat 207/2009 Anexo III)',
	'Entradas-BCICMS' => 'Entradas, bases de cálculos de ICMS, conforme CFOPs de Crédito Acumulado (Portaria Cat 207/2009 Anexo III)',
	'PMC-Den' => 'Denominador do PMC, ou seja, valor total de créditos, conforme CFOPs de Crédito Acumulado (Portaria Cat 207/2009 Anexo III)');
	$pr->abre_excel_sql('Resumo', 'Resumo Geral DGCAs.db3', $sql, $col_format, $cabec);

	// Planilha dgca_sintetica
	$sql = "
SELECT 
   CASE WHEN saidas = 0 THEN 'Nihil' ELSE
        CASE WHEN vl_red_bc > 0 THEN '2.x' ELSE
             CASE WHEN cfop > 7000 THEN '3.1' ELSE
	         CASE WHEN aliq_icms = 0 THEN '3.x' ELSE '1.x' END
             END
	END
   END AS art71, * FROM
(SELECT 
    aamm, cst_icms, aliq_icms, cfop, 
    sum(saidas) AS saidas, sum(entradas_valcon + entradas_bcicms) AS entradas,
    sum(entradas_valcon) AS entradas_valcon, sum(entradas_bcicms) AS entradas_bcicms, 
    sum(pmc_den) AS pmc_den, 
    sum(vl_opr) AS vl_opr, sum(vl_red_bc) AS vl_red_bc, sum(vl_bc_icms) AS vl_bc_icms, sum(vl_icms) AS vl_icms, sum(vl_ipi) AS vl_ipi
    FROM dgca_analit
    GROUP BY aamm, cst_icms, aliq_icms, cfop) AS sel_tmp;
";
	$col_format = array(
	"E:E" => "#.##0_ ;[Vermelho]-#.##0 ",
	"F:O" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = array(
	'art71' => "",
	'aamm' => "",
	'cst_icms' => "",
	'aliq_icms' => "",
	'cfop' => "",
	'saidas' => "",
	'entradas' => "",
	'entradas_valcon' => "",
	'entradas_bcicms' => "",
	'pmc_den' => "",
	'vl_opr' => "",
	'vl_red_bc' => "",
	'vl_bc_icms' => "",
	'vl_icms' => "",
	'vl_ipi' => ""
);
	$pr->abre_excel_sql('dgca_sintet', 'DGCa_sintética - Revise a primeira coluna e monte os DGCAs', $sql, $col_format, $cabec, $form_final);

	// Planilha dgca_analítica
	$tabela = 'dgca_analit';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"B:B" => "0",
	"E:E" => "#.##0_ ;[Vermelho]-#.##0 ",
	"G:K" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"L:L" => "#.##0_ ;[Vermelho]-#.##0 ",
	"Q:T" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = $pr->auto_cabec($tabela);
	$pr->abre_excel_sql('dgca_analit', 'dgca_analit', $sql, $col_format, $cabec, $form_final);
  
  $pr->finaliza_excel();
}


?>