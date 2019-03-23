<?php

$pr->aud_registra(new PrMenu("ladca_efd_modelo", "LAD_CA", "Modelo - Cred Acumulado Custeio", "ladca,efd"));

function ladca_efd_modelo() {

  global $pr;

  $pr->inicia_excel('LADCA_Modelo');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';

  $pr->aud_prepara("
-- s020 (ipi na entrada) é 1:1
CREATE INDEX IF NOT EXISTS s020_s015 ON s020 (ords015 ASC);
-- s370 (ipi na entrada) é 1:1
CREATE INDEX IF NOT EXISTS s370_s365 ON s370 (ords365 ASC);
-- Índices para registros também 1:1
CREATE INDEX IF NOT EXISTS s325_s315 ON s325 (ords315 ASC);
CREATE INDEX IF NOT EXISTS s330_s325 ON s330 (ords325 ASC);
CREATE INDEX IF NOT EXISTS s380_s365 ON s380 (ords365 ASC);
CREATE INDEX IF NOT EXISTS s385_s380 ON s385 (ords380 ASC);
DROP TABLE IF EXISTS ca_modelo;
CREATE TABLE ca_modelo AS
    SELECT s360.ord AS ord, '3B' AS ficha, 'Rg5360' AS origem, s360.cod_item AS cod_item,
         '5360-SI' AS num_lanc, '20' || substr(s360.ord, 1, 2) || '-' || substr(s360.ord, 3, 2) || '-01' AS dt_mov, '5360-Saldos Iniciais' AS hist,  
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS cfop, Null AS num_di, Null AS cod_part,
	 Null AS cod_lanc, 
	 Null AS fic_orig_dest, Null AS cod_item_outra_tab, 
	 0 AS ind,
	 -quant_ini AS quan,
	 0 AS cust_unit, -cus_ini AS cust,
	 0 AS icms_unit, -icms_ini AS vl_icms,
	 0 AS ent_ipi, 0 AS ent_out_imp_contrib,
         0 AS valor_crdout, 0 AS valor_desp
        FROM s360
      UNION ALL
      SELECT s365.ord AS ord, '3B' AS ficha, 'Rg5365_5360' AS origem,  s360.cod_item AS cod_item, 
         s365.num_lanc AS num_lanc, s365.dt_mov AS dt_mov, s365.hist AS hist, 
         s365.tip_doc AS tip_doc, s365.ser AS ser, s365.num_doc AS num_doc, s365.cfop AS cfop, s365.num_di AS num_di, s365.cod_part AS cod_part,
	 s365.cod_lanc AS cod_lanc, 
	 tab6_1.ori_des AS fic_orig_dest, Null AS cod_item_outra_tab, 
	 s365.ind AS ind,
	 CASE WHEN ind = 0 THEN -s365.quan ELSE s365.quan END AS quan,
	 0 AS cust_unit, CASE WHEN ind = 0 THEN -round(s365.cust_merc, 2) ELSE -round(s365.cust_merc, 2) END AS cust,
	 0 AS icms_unit, CASE WHEN ind = 0 THEN -round(s365.vl_icms, 2) ELSE -round(s365.vl_icms, 2) END AS vl_icms,
	 -s370.val_ipi AS ent_ipi, -s370.val_trib AS ent_out_imp_contrib,
        s365.valor_crdout AS valor_crdout, s365.valor_desp AS valor_desp
        FROM s365
        LEFT OUTER JOIN s360 ON s360.ord = s365.ords360
        LEFT OUTER JOIN s370 ON s370.ords365 = s365.ord
	    LEFT OUTER JOIN tab6_1 ON tab6_1.cod = s365.cod_lanc
        UNION ALL
         SELECT s360.ord AS ord, '3B' AS ficha, 'Rg5360' AS origem, s360.cod_item AS cod_item, 
         '5360-SF' AS num_lanc, '20' || substr(s360.ord, 1, 2) || '-' || substr(s360.ord, 3, 2) || '-32' AS dt_mov, '5360-Saldos Finais' AS hist, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS cfop, Null AS num_di, Null AS cod_part,
	 Null AS cod_lanc, 
	 Null AS fic_orig_dest, Null AS cod_item_outra_tab, 
	 1 AS ind,
	 quant_fim AS quan,
	 0 AS cust_unit, -cus_fim AS cust,
	 0 AS icms_unit, -icms_fim AS vl_icms,
	 0 AS ent_ipi, 0 AS ent_out_imp_contrib,
         0 AS valor_crdout, 0 AS valor_desp
        FROM s360;
INSERT INTO ca_modelo
    SELECT s310.ord AS ord, '3A' AS ficha, 'Rg5310' AS origem, s310.cod_item AS cod_item,
         '5310-SI' AS num_lanc, '20' || substr(s310.ord, 1, 2) || '-' || substr(s310.ord, 3, 2) || '-01' AS dt_mov, '5310-Saldos Iniciais' AS hist,  
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS cfop, Null AS num_di, Null AS cod_part,
	 Null AS cod_lanc, 
	 Null AS fic_orig_dest, Null AS cod_item_outra_tab, 
	 0 AS ind,
	 -quant_ini AS quan,
	 0 AS cust_unit, -cus_ini AS cust,
	 0 AS icms_unit, -icms_ini AS vl_icms,
	 0 AS ent_ipi, 0 AS ent_out_imp_contrib,
         0 AS valor_crdout, 0 AS valor_desp
        FROM s310
      UNION ALL
      SELECT s315.ord AS ord, '3A' AS ficha, 'Rg5315_5310' AS origem,  s310.cod_item AS cod_item, 
         s315.num_lanc AS num_lanc, s315.dt_mov AS dt_mov, s315.hist AS hist, 
         s315.tip_doc AS tip_doc, s315.ser AS ser, s315.num_doc AS num_doc, s315.cfop AS cfop, Null AS num_di, s315.cod_part AS cod_part,
	 s315.cod_lanc AS cod_lanc, 
	 tab6_1.ori_des AS fic_orig_dest, Null AS cod_item_outra_tab, 
	 s315.ind AS ind,
	 CASE WHEN ind = 0 THEN -s315.quan ELSE s315.quan END AS quan,
	 0 AS cust_unit, CASE WHEN ind = 0 THEN -round(s315.cust_merc, 2) ELSE -round(s315.cust_merc, 2) END AS cust,
	 0 AS icms_unit, CASE WHEN ind = 0 THEN -round(s315.vl_icms, 2) ELSE -round(s315.vl_icms, 2) END AS vl_icms,
	 0 AS ent_ipi, 0 AS ent_out_imp_contrib,
        s315.valor_crdout AS valor_crdout, s315.valor_desp AS valor_desp
        FROM s315
        LEFT OUTER JOIN s310 ON s310.ord = s315.ords310
	    LEFT OUTER JOIN tab6_1 ON tab6_1.cod = s315.cod_lanc
        UNION ALL
         SELECT s310.ord AS ord, '3A' AS ficha, 'Rg5310' AS origem, s310.cod_item AS cod_item, 
         '5310-SF' AS num_lanc, '20' || substr(s310.ord, 1, 2) || '-' || substr(s310.ord, 3, 2) || '-32' AS dt_mov, '5310-Saldos Finais' AS hist, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS cfop, Null AS num_di, Null AS cod_part,
	 Null AS cod_lanc, 
	 Null AS fic_orig_dest, Null AS cod_item_outra_tab, 
	 1 AS ind,
	 quant_fim AS quan,
	 0 AS cust_unit, -cus_fim AS cust,
	 0 AS icms_unit, -icms_fim AS vl_icms,
	 0 AS ent_ipi, 0 AS ent_out_imp_contrib,
         0 AS valor_crdout, 0 AS valor_desp
        FROM s310;
INSERT INTO ca_modelo
    SELECT s150.ord AS ord, '2A' AS ficha, 'Rg5150' AS origem, s150.cod_item AS cod_item,
         '5150-SI' AS num_lanc, '20' || substr(s150.ord, 1, 2) || '-' || substr(s150.ord, 3, 2) || '-01' AS dt_mov, '5150-Saldos Iniciais' AS hist,  
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS cfop, Null AS num_di, Null AS cod_part,
	 Null AS cod_lanc, 
	 Null AS fic_orig_dest, Null AS cod_item_outra_tab, 
	 0 AS ind,
	 0 AS quan,
	 0 AS cust_unit, -cus_ini AS cust,
	 0 AS icms_unit, -icms_ini AS vl_icms,
	 0 AS ent_ipi, 0 AS ent_out_imp_contrib,
         0 AS valor_crdout, 0 AS valor_desp
        FROM s150
      UNION ALL
      SELECT s160.ord AS ord, '2A' AS ficha, 'Rg5160_5150' AS origem,  s150.cod_item AS cod_item, 
         s160.num_lanc AS num_lanc, s160.dt_mov AS dt_mov, s160.hist AS hist, 
         s160.tip_doc AS tip_doc, s160.ser AS ser, s160.num_doc AS num_doc, Null AS cfop, Null AS num_di, Null AS cod_part,
	 s160.cod_lanc AS cod_lanc, 
	 tab6_1.ori_des AS fic_orig_dest, Null AS cod_item_outra_tab, 
	 s160.ind AS ind,
	 CASE WHEN ind = 0 THEN -s160.quan ELSE s160.quan END AS quan,
	 -s150.cust_unit AS cust_unit, CASE WHEN ind = 0 THEN -round(s160.cust_item, 2) ELSE -round(s160.cust_item, 2) END AS cust,
	 -s150.icms_unit AS icms_unit, CASE WHEN ind = 0 THEN -round(s160.vl_icms, 2) ELSE -round(s160.vl_icms, 2) END AS vl_icms,
	 0 AS ent_ipi, 0 AS ent_out_imp_contrib,
         0 AS valor_crdout, 0 AS valor_desp
        FROM s160
        LEFT OUTER JOIN s150 ON s150.ord = s160.ords150
	    LEFT OUTER JOIN tab6_1 ON tab6_1.cod = s160.cod_lanc
        UNION ALL
         SELECT s150.ord AS ord, '2A' AS ficha, 'Rg5150' AS origem, s150.cod_item AS cod_item, 
         '5150-SF' AS num_lanc, '20' || substr(s150.ord, 1, 2) || '-' || substr(s150.ord, 3, 2) || '-32' AS dt_mov, '5150-Saldos Finais' AS hist, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS cfop, Null AS num_di, Null AS cod_part,
	 Null AS cod_lanc, 
	 Null AS fic_orig_dest, cod_item AS cod_item_outra_tab, 
	 1 AS ind,
	 0 AS quan,
	 0 AS cust_unit, -cus_fim AS cust,
	 0 AS icms_unit, -icms_fim AS vl_icms,
	 0 AS ent_ipi, 0 AS ent_out_imp_contrib,
         0 AS valor_crdout, 0 AS valor_desp
        FROM s150;
INSERT INTO ca_modelo
    SELECT s010.ord AS ord, '1A' AS ficha, 'Rg5310' AS origem, s010.cod_item AS cod_item,
         '5310-SI' AS num_lanc, '20' || substr(s010.ord, 1, 2) || '-' || substr(s010.ord, 3, 2) || '-01' AS dt_mov, '5310-Saldos Iniciais' AS hist,  
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS cfop, Null AS num_di, Null AS cod_part,
	 Null AS cod_lanc, 
	 Null AS fic_orig_dest, Null AS cod_item_outra_tab, 
	 0 AS ind,
	 -quant_ini AS quan,
	 0 AS cust_unit, -cus_ini AS cust,
	 0 AS icms_unit, -icms_ini AS vl_icms,
	 0 AS ent_ipi, 0 AS ent_out_imp_contrib,
         0 AS valor_crdout, 0 AS valor_desp
        FROM s010
      UNION ALL
      SELECT s015.ord AS ord, '1A' AS ficha, 'Rg5015_5010' AS origem,  s010.cod_item AS cod_item, 
         s015.num_lanc AS num_lanc, s015.dt_mov AS dt_mov, s015.hist AS hist, 
         s015.tip_doc AS tip_doc, s015.ser AS ser, s015.num_doc AS num_doc, s015.cfop AS cfop, s015.num_di AS num_di, s015.cod_part AS cod_part,
	 s015.cod_lanc AS cod_lanc, 
	 tab6_1.ori_des AS fic_orig_dest, cod_item_outra_tab AS cod_item_outra_tab, 
	 s015.ind AS ind,
	 CASE WHEN ind = 0 THEN -s015.quan ELSE s015.quan END AS quan,
	 0 AS cust_unit, CASE WHEN ind = 0 THEN -round(s015.cust_merc, 2) ELSE -round(s015.cust_merc, 2) END AS cust,
	 0 AS icms_unit, CASE WHEN ind = 0 THEN -round(s015.vl_icms, 2) ELSE -round(s015.vl_icms, 2) END AS vl_icms,
	 -s020.val_ipi AS ent_ipi, -s020.val_trib AS ent_out_imp_contrib,
         0 AS valor_crdout, 0 AS valor_desp
        FROM s015
        LEFT OUTER JOIN s010 ON s010.ord = s015.ords010
        LEFT OUTER JOIN s020 ON s020.ords015 = s015.ord
	    LEFT OUTER JOIN tab6_1 ON tab6_1.cod = s015.cod_lanc
        UNION ALL
         SELECT s010.ord AS ord, '1A' AS ficha, 'Rg5310' AS origem, s010.cod_item AS cod_item, 
         '5310-SF' AS num_lanc, '20' || substr(s010.ord, 1, 2) || '-' || substr(s010.ord, 3, 2) || '-32' AS dt_mov, '5310-Saldos Finais' AS hist, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS cfop, Null AS num_di, Null AS cod_part,
	 Null AS cod_lanc, 
	 Null AS fic_orig_dest, Null AS cod_item_outra_tab, 
	 1 AS ind,
	 quant_fim AS quan,
	 0 AS cust_unit, -cus_fim AS cust,
	 0 AS icms_unit, -icms_fim AS vl_icms,
	 0 AS ent_ipi, 0 AS ent_out_imp_contrib,
         0 AS valor_crdout, 0 AS valor_desp
        FROM s010;
");

  // Planilha Ca_Modelo
  $sql = "
SELECT substr(ca_modelo.ord, 1, 4) AS per, 
    CASE WHEN ficha = '1A' THEN cod_item_outra_tab ELSE cod_item END AS cod_item_alvo,
    ca_modelo.*,
    CASE WHEN s325.cod_legal IS NOT NULL THEN s325.cod_legal ELSE
        CASE WHEN s380.cod_legal IS NOT NULL THEN s380.cod_legal ELSE Null END
    END AS cod_legal,
    CASE WHEN s325.valor_op_item IS NOT NULL THEN s325.valor_op_item ELSE
        CASE WHEN s380.valor_op_item IS NOT NULL THEN s380.valor_op_item ELSE Null END
    END AS valor_op_item,
    CASE WHEN s330.valor_bc_item IS NOT NULL THEN s330.valor_bc_item ELSE
        CASE WHEN s385.valor_bc_item IS NOT NULL THEN s385.valor_bc_item ELSE Null END
    END AS valor_bc_item,
    CASE WHEN s330.aliq_item IS NOT NULL THEN s330.aliq_item ELSE
        CASE WHEN s385.aliq_item IS NOT NULL THEN s385.aliq_item ELSE Null END
    END AS aliq_item,
    CASE WHEN s330.icms_deb_item IS NOT NULL THEN s330.icms_deb_item ELSE
        CASE WHEN s385.icms_deb_item IS NOT NULL THEN s385.icms_deb_item ELSE Null END
    END AS icms_deb_item,
    CASE WHEN s325.icms_gera_item IS NOT NULL THEN -s325.icms_gera_item ELSE
        CASE WHEN s380.icms_gera_item IS NOT NULL THEN -s380.icms_gera_item ELSE Null END
    END AS icms_gera_item
    FROM ca_modelo
    LEFT OUTER JOIN s325 ON s325.ords315 = ca_modelo.ord
    LEFT OUTER JOIN s330 ON s330.ords325 = s325.ord
    LEFT OUTER JOIN s380 ON s380.ords365 = ca_modelo.ord
    LEFT OUTER JOIN s385 ON s385.ords380 = s380.ord;
";
  $col_format = array(
	"C:D" => "0",
);
  $cabec = $pr->auto_cabec('ca_modelo');
  $pr->abre_excel_sql('ca_modelo', 'Credito Acumulado - Custos - Port 83 - Modelo', $sql, $col_format, $cabec, $form_final);

  
  $pr->finaliza_excel();
}


?>