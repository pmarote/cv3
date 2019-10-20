<?php

$pr->aud_registra(new PrMenu("audit_conc", "_Audit", "Conciliações", "audit"));

function audit_conc() {

	global $pr;

	$pr->inicia_excel('Audit_Conc');

  	$form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';


	// Planilha Conciliação 4
	$sql = "
SELECT * FROM 
    (SELECT concil, dfe_cod_sit AS cod_sit, dfe_tp_oper AS tp_oper, abs(dfe_valcon) AS valcon, abs(dfe_icms) AS icms, abs(dfe_icmsst) AS icmsst, dfe_origem AS origem, 
         dfe_dtaentsai AS dtaentsai, dfe_chav_ace AS chav_ace, dfe_cnpj_uf_razsoc AS cnpj_uf_razsoc, dfe_dtaina AS dtaina, dfe_descina AS descina, 
         DFe_cod_sits AS cod_sits, DFe_cfop_nfs AS cfop_nfs, DFe_cfops AS cfops, 
         cfopd.dfi, cfopd.st, cfopd.g1, cfopd.c3, cfopd.g2, cfopd.g3, cfopd.descri_simplif
         FROM conc_dfe_res
         LEFT OUTER JOIN chav_ace_class ON chav_ace_class.chav_ace = dfe_chav_ace
         LEFT OUTER JOIN cfopd ON cfopd.cfop = substr(DFe_cfops, 1, 4) + 0
         WHERE concil = 'DFe_sem_RES' AND dfe_cod_sit = 0 AND (dfe_tp_oper = 'E' OR dfe_tp_oper = 'D')
    UNION ALL
    SELECT concil, res_cod_sit AS cod_sit, res_tp_oper AS tp_oper, abs(res_valcon) AS valcon, abs(res_icms) AS icms, abs(res_icmsst) AS icmsst, res_origem AS origem, 
         res_dtaentsai AS dtaentsai, res_chav_ace AS chav_ace, res_cnpj_uf_razsoc AS cnpj_uf_razsoc, res_dtaina AS dtaina, res_descina AS descina, 
         RES_cod_sits AS cod_sits, Null AS cfop_nfs, RES_cfops AS cfops, 
         cfopd.dfi, cfopd.st, cfopd.g1, cfopd.c3, cfopd.g2, cfopd.g3, cfopd.descri_simplif
         FROM conc_dfe_res
         LEFT OUTER JOIN chav_ace_class ON chav_ace_class.chav_ace = res_chav_ace
         LEFT OUTER JOIN cfopd ON cfopd.cfop = substr(RES_cfops, 1, 4) + 0
         WHERE concil = 'EFD_C190_sem_DFe' AND res_cod_sit IN (0, 1, 6, 7, 8) AND res_tp_oper = 'E')
ORDER BY valcon DESC;
";
	$col_format = array(
	"D:F" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = array(
	'concil' => "",
	'cod_sit' => "",
	'tp_oper' => "",
	'valcon' => "",
	'icms' => "",
	'icmsst' => "",
	'origem' => "",
	'dtaentsai' => "",
	'chav_ace' => "",
	'cnpj_uf_razsoc' => "",
	'dtaina' => "",
	'descina' => "",
	'cod_sits' => "",
	'cfop_nfs' => "",
	'cfops' => "",
	'dfi' => "",
	'st' => "",
	'g1' => "",
	'c3' => "",
	'g2' => "",
	'g3' => "",
	'descri_simplif' => ""
);
	$pr->abre_excel_sql('Conc4', 'Conciliação 4: NFes entradas NÃO REGISTRADAS E Livros de Entradas (C190) sem NFes', $sql, $col_format, $cabec, $form_final);



	// Planilha Conciliação 3
	$sql = "
SELECT concil, dfe_cod_sit, dfe_tp_oper, res_icms - dfe_icms AS dif_icms, dfe_origem, res_origem, dfe_dtaentsai, res_dtaentsai, dfe_chav_ace, dfe_cnpj_uf_razsoc, dfe_dtaina, dfe_descina, 
     dfe_valcon, res_valcon, dfe_icms, res_icms, dfe_icmsst, res_icmsst, 
     RES_cod_sits, RES_tp_opers, DFE_cfop_nfs, RES_cfops, 
     cfopd.dfi, cfopd.st, cfopd.g1, cfopd.c3, cfopd.g2, cfopd.g3, cfopd.descri_simplif
     FROM conc_dfe_res
     LEFT OUTER JOIN chav_ace_class ON chav_ace_class.chav_ace = dfe_chav_ace
     LEFT OUTER JOIN cfopd ON cfopd.cfop = substr(RES_cfops, 1, 4) + 0
     WHERE concil = 'DFe-RES' AND dfe_cod_sit = 2 AND dfe_tp_oper = 'E';
";
	$col_format = array(
	"D:D" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"M:R" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = array(
	'concil' => "",
	'dfe_cod_sit' => "",
	'dfe_tp_oper' => "",
	'dif_icms' => "",
	'dfe_origem' => "",
	'res_origem' => "",
	'dfe_dtaentsai' => "",
	'res_dtaentsai' => "",
	'dfe_chav_ace' => "",
	'dfe_cnpj_uf_razsoc' => "",
	'dfe_dtaina' => "",
	'dfe_descina' => "",
	'dfe_valcon' => "",
	'res_valcon' => "",
	'dfe_icms' => "",
	'res_icms' => "",
	'dfe_icmsst' => "",
	'res_icmsst' => "",
	'RES_cod_sits' => "",
	'RES_tp_opers' => "",
	'DFE_cfop_nfs' => "",
	'RES_cfops' => "",
	'dfi' => "",
	'st' => "",
	'g1' => "",
	'c3' => "",
	'g2' => "",
	'g3' => "",
	'descri_simplif' => ""
);
	$pr->abre_excel_sql('Conc3', 'Conciliação 3: NFes entradas CANCELADAS REGISTRADAS', $sql, $col_format, $cabec, $form_final);



	// Planilha Conciliação 2
	$sql = "
SELECT concil, dfe_cod_sit, dfe_tp_oper, res_icms - dfe_icms AS dif_icms, dfe_origem, res_origem, dfe_dtaentsai, res_dtaentsai, dfe_chav_ace, dfe_cnpj_uf_razsoc, dfe_dtaina, dfe_descina, 
     dfe_valcon, res_valcon, dfe_icms, res_icms, dfe_icmsst, res_icmsst, 
     RES_cod_sits, RES_tp_opers, DFE_cfop_nfs, RES_cfops, 
     cfopd.dfi, cfopd.st, cfopd.g1, cfopd.c3, cfopd.g2, cfopd.g3, cfopd.descri_simplif
     FROM conc_dfe_res
     LEFT OUTER JOIN chav_ace_class ON chav_ace_class.chav_ace = dfe_chav_ace
     LEFT OUTER JOIN cfopd ON cfopd.cfop = substr(RES_cfops, 1, 4) + 0
     WHERE concil = 'DFe-RES' AND dfe_cod_sit = 0 AND dfe_tp_oper = 'E'
         AND (res_icms - dfe_icms) < -1;
";
	$col_format = array(
	"D:D" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"M:R" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = array(
	'concil' => "",
	'dfe_cod_sit' => "",
	'dfe_tp_oper' => "",
	'dif_icms' => "",
	'dfe_origem' => "",
	'res_origem' => "",
	'dfe_dtaentsai' => "",
	'res_dtaentsai' => "",
	'dfe_chav_ace' => "",
	'dfe_cnpj_uf_razsoc' => "",
	'dfe_dtaina' => "",
	'dfe_descina' => "",
	'dfe_valcon' => "",
	'res_valcon' => "",
	'dfe_icms' => "",
	'res_icms' => "",
	'dfe_icmsst' => "",
	'res_icmsst' => "",
	'RES_cod_sits' => "",
	'RES_tp_opers' => "",
	'DFE_cfop_nfs' => "",
	'RES_cfops' => "",
	'dfi' => "",
	'st' => "",
	'g1' => "",
	'c3' => "",
	'g2' => "",
	'g3' => "",
	'descri_simplif' => ""
);
	$pr->abre_excel_sql('Conc2', 'Conciliação 2: NFes entradas válidas REGISTRADAS com excesso de icms', $sql, $col_format, $cabec, $form_final);



	// Planilha Conciliação 1
	$sql = "
SELECT concil, dfe_cod_sit, dfe_tp_oper, dfe_icms - res_icms AS dif_icms, dfe_icmsst - res_icmsst AS dif_icmsst, dfe_origem, res_origem, dfe_dtaentsai, res_dtaentsai, dfe_chav_ace, dfe_cnpj_uf_razsoc, dfe_dtaina, dfe_descina, 
     dfe_valcon, res_valcon, dfe_icms, res_icms, dfe_icmsst, res_icmsst, 
     RES_cod_sits, RES_tp_opers, DFE_cfop_nfs, RES_cfops, 
     cfopd.dfi, cfopd.st, cfopd.g1, cfopd.c3, cfopd.g2, cfopd.g3, cfopd.descri_simplif
     FROM conc_dfe_res
     LEFT OUTER JOIN chav_ace_class ON chav_ace_class.chav_ace = dfe_chav_ace
     LEFT OUTER JOIN cfopd ON cfopd.cfop = substr(RES_cfops, 1, 4) + 0
     WHERE concil = 'DFe-RES' AND dfe_cod_sit = 0 AND dfe_tp_oper = 'S'
         AND (abs(dfe_icms - res_icms) > 1 OR abs(dfe_icmsst - res_icmsst) > 1);
";
	$col_format = array(
	"D:E" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"N:S" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = array(
	'concil' => "",
	'dfe_cod_sit' => "",
	'dfe_tp_oper' => "",
	'dif_icms' => "",
	'dif_icmsst' => "",
	'dfe_origem' => "",
	'res_origem' => "",
	'dfe_dtaentsai' => "",
	'res_dtaentsai' => "",
	'dfe_chav_ace' => "",
	'dfe_cnpj_uf_razsoc' => "",
	'dfe_dtaina' => "",
	'dfe_descina' => "",
	'dfe_valcon' => "",
	'res_valcon' => "",
	'dfe_icms' => "",
	'res_icms' => "",
	'dfe_icmsst' => "",
	'res_icmsst' => "",
	'RES_cod_sits' => "",
	'RES_tp_opers' => "",
	'DFE_cfop_nfs' => "",
	'RES_cfops' => "",
	'dfi' => "",
	'st' => "",
	'g1' => "",
	'c3' => "",
	'g2' => "",
	'g3' => "",
	'descri_simplif' => ""
);
	$pr->abre_excel_sql('Conc1', 'Conciliação 1: NFes saídas válidas REGISTRADAS com diferença de icms (ou icms st)', $sql, $col_format, $cabec, $form_final);

	// Planilha Resumo Conciliações
	$sql = "
SELECT '##NT##group by', 'dfe_cod_sit', 'dfe_tp_oper', '', '', '', '', '', '', ''
UNION ALL
SELECT concil, dfe_cod_sit, dfe_tp_oper, 
     sum(dfe_valcon) AS dfe_valcon, sum(res_valcon) AS res_valcon, 
     sum(dfe_icms) AS dfe_icms, sum(res_icms) AS res_icms, 
     sum(dfe_icmsst) AS dfe_icmsst, sum(res_icmsst) AS res_icmsst,
     CASE WHEN concil = 'DFe-RES' AND dfe_cod_sit = 0 AND dfe_tp_oper = 'S' THEN 'Conc1' ELSE 
          CASE WHEN concil = 'DFe-RES' AND dfe_cod_sit = 0 AND dfe_tp_oper = 'E' THEN 'Conc2' ELSE
              CASE WHEN concil = 'DFe-RES' AND dfe_cod_sit = 2 AND dfe_tp_oper = 'E' THEN 'Conc3' ELSE
                  CASE WHEN concil = 'DFe_sem_RES' AND dfe_cod_sit = 0 AND (dfe_tp_oper = 'E' OR dfe_tp_oper = 'D') THEN 'Conc4' ELSE
                      CASE WHEN concil = 'EFD_C190_sem_DFe' AND res_cod_sit IN (0, 1, 6, 7, 8) AND res_tp_oper = 'E' THEN 'Conc4' ELSE '' END
                  END
              END
          END
     END AS obs
     FROM conc_dfe_res
     GROUP BY concil, dfe_cod_sit, dfe_tp_oper
UNION ALL
SELECT '', '', '', '', '', '', '', '', '', ''
UNION ALL
SELECT '##NT##group by', 'res_cod_sit', 'res_tp_oper', '', '', '', '', '', '', ''
UNION ALL
SELECT concil, res_cod_sit, res_tp_oper, 
     sum(dfe_valcon) AS dfe_valcon, sum(res_valcon) AS res_valcon, 
     sum(dfe_icms) AS dfe_icms, sum(res_icms) AS res_icms, 
     sum(dfe_icmsst) AS dfe_icmsst, sum(res_icmsst) AS res_icmsst,
     CASE WHEN concil = 'DFe-RES' AND dfe_cod_sit = 0 AND dfe_tp_oper = 'S' THEN 'Conc1' ELSE 
          CASE WHEN concil = 'DFe-RES' AND dfe_cod_sit = 0 AND dfe_tp_oper = 'E' THEN 'Conc2' ELSE
              CASE WHEN concil = 'DFe-RES' AND dfe_cod_sit = 2 AND dfe_tp_oper = 'E' THEN 'Conc3' ELSE
                  CASE WHEN concil = 'DFe_sem_RES' AND dfe_cod_sit = 0 AND (dfe_tp_oper = 'E' OR dfe_tp_oper = 'D') THEN 'Conc4' ELSE
                      CASE WHEN concil = 'EFD_C190_sem_DFe' AND res_cod_sit <> 2 AND res_tp_oper = 'E' THEN 'Conc4' ELSE '' END
                  END
              END
          END
     END AS obs
     FROM conc_dfe_res
     GROUP BY concil, res_cod_sit, res_tp_oper;
";
	$col_format = array(
	"D:I" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = array(
	'concil' => "",
	'cod_sit' => "",
	'tp_oper' => "",
	'dfe_valcon' => "",
	'res_valcon' => "",
	'dfe_icms' => "",
	'res_icms' => "",
	'dfe_icmsst' => "",
	'res_icmsst' => "",
	'conc' => "Planilha de Conciliação Analítica"
);
	$pr->abre_excel_sql('Resumo', 'Resumo das Conciliações', $sql, $col_format, $cabec, $form_final);

  $pr->finaliza_excel();

}


?>