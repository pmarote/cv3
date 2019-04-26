<?php

$pr->aud_registra(new PrMenu("ladca_fichas", "LAD_CA", "LADCA - Fichas", "ladca"));

function ladca_fichas() {

  global $pr;

  $pr->inicia_excel('LADCA_Fichas');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';


  // Planilha Ficha 6F
  $sql = "
SELECT tipo, substr(ord, 1, 4) AS per, origem || ord AS orig, num_lanc, cod_item, descr_item, dt_mov, tip_doc, ser, num_doc,
    cfop, cnpj, uf, cod_legal,
    quan, uni, valor_op_item, valor_bc_item, aliq_item, icms_deb_item,
    cust_merc, vl_icms, perc_crdout, valor_crdout, valor_desp, 
    vl_icms + valor_crdout + valor_desp AS val_tot_icms, vl_icms  + valor_crdout + valor_desp - icms_deb_item AS cred_acum_ger,
    round((valor_op_item - cust_merc) * 100 / cust_merc, 2) AS IVA,
    round(vl_icms * 100 / cust_merc, 2) AS PMC FROM 
    (SELECT '6F' AS tipo, s350.ord AS ord, s315.num_lanc AS num_lanc, 'Rg5350_5315_5310_-Ficha3A-CPV' AS origem, s315.dt_mov AS dt_mov, s315.tip_doc AS tip_doc, s315.ser AS ser, s315.num_doc AS num_doc,
         s310.cod_item AS cod_item, o200.descr_item AS descr_item, s315.cfop AS cfop, o150.cnpj AS cnpj, o150.uf AS uf, Null AS cod_legal,
         s315.quan AS quan, o200.uni AS uni, s350.valor_op_item AS valor_op_item, s350.valor_bc_item AS valor_bc_item, s350.aliq_item AS aliq_item, s350.icms_deb_item AS icms_deb_item,
         round(s315.cust_merc, 2) AS cust_merc,  round(s315.vl_icms, 2) AS vl_icms,  s315.perc_crdout AS perc_crdout, s315.valor_crdout AS valor_crdout, s315.valor_desp AS valor_desp, 
        round(s350.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s350.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s350
        LEFT OUTER JOIN s315 ON s315.ord = s350.ords315
        LEFT OUTER JOIN s310 ON s310.ord = s315.ords310
        LEFT OUTER JOIN o150 ON o150.cod_part = s315.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
        LEFT OUTER JOIN o200 ON o200.cod_item = s310.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
      UNION ALL
      SELECT '6F' AS tipo, s400.ord AS ord, s365.num_lanc AS num_lanc, 'Rg5400_5365_5360_-Ficha3A-CMV' AS origem, s365.dt_mov AS dt_mov, s365.tip_doc AS tip_doc, s365.ser AS ser, s365.num_doc AS num_doc,
         s360.cod_item AS cod_item, o200.descr_item AS descr_item, s365.cfop AS cfop, o150.cnpj AS cnpj, o150.uf AS uf, Null AS cod_legal,
         s365.quan AS quan, o200.uni AS uni, s400.valor_op_item AS valor_op_item, s400.valor_bc_item AS valor_bc_item, s400.aliq_item AS aliq_item, s400.icms_deb_item AS icms_deb_item,
         round(s365.cust_merc, 2) AS cust_merc,  round(s365.vl_icms, 2) AS vl_icms,  s365.perc_crdout AS perc_crdout, s365.valor_crdout AS valor_crdout, s365.valor_desp AS valor_desp, 
        round(s400.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s400.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s400
        LEFT OUTER JOIN s365 ON s365.ord = s400.ords365
        LEFT OUTER JOIN s360 ON s360.ord = s365.ords360
        LEFT OUTER JOIN o150 ON o150.cod_part = s365.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
        LEFT OUTER JOIN o200 ON o200.cod_item = s360.cod_item AND o200.ord > ordmin AND o200.ord < ordmax) AS f6a_aux
ORDER BY per, orig;
";
  $col_format = array(
	"D:E" => "0",
	"J:J" => "0",
	"L:L" => "0",
	"Q:Z" => "#.##0,00",
	"AA:AC" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
  $cabec = array(
  	'tipo' => "Tipo (6F)",
  	'per' => "Período",
	'orig' => "Origem - Registros e Tipo de Cálculo",
	'num_lanc' => "Número do Lançamento",
	'cod_item' => "Código da mercadoria",
	'descr_item' => "Descrição da mercadoria",
	'dt_mov' => "Data",
	'tip_doc' => "Tipo Doc",
	'ser' => "Série",
	'num_doc' => "Nro Doc",
	'cfop' => "CFOP",
	'cnpj' => "Destinatário",
	'uf' => "UF Destinatário",
	'cod_legal' => "cod_legal",
	'quan' => "Quantidade do item.",
	'uni' => "Unidade de medida do Item. (Reg 0200)",
	'valor_op_item' => "Valor da Operação",
	'valor_bc_item' => "Base de Cálculo",
	'aliq_item' => "Alíquota",
	'icms_deb_item' => "ICMS Debitado",
	'cust_merc' => "Valor do Custo de entrada, excluídos os tributos e contribuições recuperáveis",
	'vl_icms' => "Valor do ICMS",
	'perc_crdout' => "Porc Créd Outorgado",
	'valor_crdout' => "Val Créd Outorgado",
	'valor_desp' => "Val Créd Desp Operacionais",
	'val_tot_icms' => "Valor Total ICMS",
	'cred_acum_ger_TEORIA' => "Créd Acumulado Gerado, em teoria",
	'IVA' => "IVA: (valor_op_item - cust_merc) / cust_merc * 100",
	'PMC' => "PMC: vl_icms / cust_merc * 100"
);
  $pr->abre_excel_sql('Ficha6F', 'Ficha 6F', $sql, $col_format, $cabec, $form_final);



  // Planilha Ficha 6A/6B/6E
  $sql = "
SELECT tipo, substr(ord, 1, 4) AS per, origem || ord AS orig, num_lanc, cod_item, descr_item, dt_mov, tip_doc, ser, num_doc,
    cfop, cnpj, uf, cod_legal,
    quan, uni, valor_op_item, valor_bc_item, aliq_item, icms_deb_item,
    cust_merc, vl_icms, perc_crdout, valor_crdout, valor_desp, 
    vl_icms + valor_crdout + valor_desp AS val_tot_icms, vl_icms  + valor_crdout + valor_desp - icms_deb_item AS cred_acum_ger,
    round((valor_op_item - cust_merc) * 100 / cust_merc, 2) AS IVA,
    round(vl_icms * 100 / cust_merc, 2) AS PMC FROM 
    (SELECT '6E' AS tipo, s325.ord AS ord, s315.num_lanc AS num_lanc, 'Rg5330NULL_5325_5315_5310_-Ficha3A-CPV' AS origem, s315.dt_mov AS dt_mov, s315.tip_doc AS tip_doc, s315.ser AS ser, s315.num_doc AS num_doc,
         s310.cod_item AS cod_item, o200.descr_item AS descr_item, s315.cfop AS cfop, o150.cnpj AS cnpj, o150.uf AS uf, s325.cod_legal AS cod_legal,
         s315.quan AS quan, o200.uni AS uni, s325.valor_op_item AS valor_op_item, s330.valor_bc_item AS valor_bc_item, s330.aliq_item AS aliq_item, 0 AS icms_deb_item,
         round(s315.cust_merc, 2) AS cust_merc,  round(s315.vl_icms, 2) AS vl_icms,  s315.perc_crdout AS perc_crdout, s315.valor_crdout AS valor_crdout, s315.valor_desp AS valor_desp, 
        round(s325.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s325.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s325
        LEFT OUTER JOIN s330 ON s330.ords325 = s325.ord
        LEFT OUTER JOIN s315 ON s315.ord = s325.ords315
        LEFT OUTER JOIN s310 ON s310.ord = s315.ords310
        LEFT OUTER JOIN o150 ON o150.cod_part = s315.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
        LEFT OUTER JOIN o200 ON o200.cod_item = s310.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
	WHERE s330.ord IS NULL
      UNION ALL
       SELECT '6A/6B' AS tipo, s330.ord AS ord, s315.num_lanc AS num_lanc, 'Rg5330_5325_5315_5310_-Ficha3A-CPV' AS origem, s315.dt_mov AS dt_mov, s315.tip_doc AS tip_doc, s315.ser AS ser, s315.num_doc AS num_doc,
         s310.cod_item AS cod_item, o200.descr_item AS descr_item, s315.cfop AS cfop, o150.cnpj AS cnpj, o150.uf AS uf, s325.cod_legal AS cod_legal,
         s315.quan AS quan, o200.uni AS uni, s325.valor_op_item AS valor_op_item, s330.valor_bc_item AS valor_bc_item, s330.aliq_item AS aliq_item, s330.icms_deb_item AS icms_deb_item,
         round(s315.cust_merc, 2) AS cust_merc,  round(s315.vl_icms, 2) AS vl_icms,  s315.perc_crdout AS perc_crdout, s315.valor_crdout AS valor_crdout, s315.valor_desp AS valor_desp, 
        round(s330.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s330.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s330
        LEFT OUTER JOIN s325 ON s325.ord = s330.ords325
        LEFT OUTER JOIN s315 ON s315.ord = s325.ords315
        LEFT OUTER JOIN s310 ON s310.ord = s315.ords310
        LEFT OUTER JOIN o150 ON o150.cod_part = s315.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
        LEFT OUTER JOIN o200 ON o200.cod_item = s310.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
      UNION ALL
      SELECT '6E' AS tipo, s385.ord AS ord, s365.num_lanc AS num_lanc, 'Rg5385NULL_5380_5365_5360_-Ficha3A-CMV' AS origem, s365.dt_mov AS dt_mov, s365.tip_doc AS tip_doc, s365.ser AS ser, s365.num_doc AS num_doc,
         s360.cod_item AS cod_item, o200.descr_item AS descr_item, s365.cfop AS cfop, o150.cnpj AS cnpj, o150.uf AS uf, s380.cod_legal AS cod_legal,
         s365.quan AS quan, o200.uni AS uni, s380.valor_op_item AS valor_op_item, s385.valor_bc_item AS valor_bc_item, s385.aliq_item AS aliq_item, 0 AS icms_deb_item,
         round(s365.cust_merc, 2) AS cust_merc,  round(s365.vl_icms, 2) AS vl_icms,  s365.perc_crdout AS perc_crdout, s365.valor_crdout AS valor_crdout, s365.valor_desp AS valor_desp, 
        round(s380.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s380.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s380
        LEFT OUTER JOIN s385 ON s385.ords380 = s380.ord
        LEFT OUTER JOIN s365 ON s365.ord = s380.ords365
        LEFT OUTER JOIN s360 ON s360.ord = s365.ords360
        LEFT OUTER JOIN o150 ON o150.cod_part = s365.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
        LEFT OUTER JOIN o200 ON o200.cod_item = s360.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
	WHERE s385.ord IS NULL
	UNION ALL
      SELECT '6A/6B' AS tipo, s385.ord AS ord, s365.num_lanc AS num_lanc, 'Rg5385_5380_5365_5360_-Ficha3A-CMV' AS origem, s365.dt_mov AS dt_mov, s365.tip_doc AS tip_doc, s365.ser AS ser, s365.num_doc AS num_doc,
         s360.cod_item AS cod_item, o200.descr_item AS descr_item, s365.cfop AS cfop, o150.cnpj AS cnpj, o150.uf AS uf, s380.cod_legal AS cod_legal,
         s365.quan AS quan, o200.uni AS uni, s380.valor_op_item AS valor_op_item, s385.valor_bc_item AS valor_bc_item, s385.aliq_item AS aliq_item, s385.icms_deb_item AS icms_deb_item,
         round(s365.cust_merc, 2) AS cust_merc,  round(s365.vl_icms, 2) AS vl_icms,  s365.perc_crdout AS perc_crdout, s365.valor_crdout AS valor_crdout, s365.valor_desp AS valor_desp, 
        round(s385.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s385.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s385
        LEFT OUTER JOIN s380 ON s380.ord = s385.ords380
        LEFT OUTER JOIN s365 ON s365.ord = s380.ords365
        LEFT OUTER JOIN s360 ON s360.ord = s365.ords360
        LEFT OUTER JOIN o150 ON o150.cod_part = s365.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
        LEFT OUTER JOIN o200 ON o200.cod_item = s360.cod_item AND o200.ord > ordmin AND o200.ord < ordmax) AS f6a_aux
ORDER BY aliq_item, dt_mov, num_doc;
";
  $col_format = array(
	"D:E" => "0",
	"J:J" => "0",
	"L:L" => "0",
	"Q:Z" => "#.##0,00",
	"AA:AC" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
  $cabec = array(
  	'tipo' => "Tipo (6A/6B/6E)",
  	'per' => "Período",
	'orig' => "Origem - Registros e Tipo de Cálculo",
	'num_lanc' => "Número do Lançamento",
	'cod_item' => "Código da mercadoria",
	'descr_item' => "Descrição da mercadoria",
	'dt_mov' => "Data",
	'tip_doc' => "Tipo Doc",
	'ser' => "Série",
	'num_doc' => "Nro Doc",
	'cfop' => "CFOP",
	'cnpj' => "Destinatário",
	'uf' => "UF Destinatário",
	'cod_legal' => "cod_legal",
	'quan' => "Quantidade do item.",
	'uni' => "Unidade de medida do Item. (Reg 0200)",
	'valor_op_item' => "Valor da Operação",
	'valor_bc_item' => "Base de Cálculo",
	'aliq_item' => "Alíquota",
	'icms_deb_item' => "ICMS Debitado",
	'cust_merc' => "Valor do Custo de entrada, excluídos os tributos e contribuições recuperáveis",
	'vl_icms' => "Valor do ICMS",
	'perc_crdout' => "Porc Créd Outorgado",
	'valor_crdout' => "Val Créd Outorgado",
	'valor_desp' => "Val Créd Desp Operacionais",
	'val_tot_icms' => "Valor Total ICMS",
	'cred_acum_ger' => "Créd Acumulado Gerado",
	'IVA' => "IVA: (valor_op_item - cust_merc) / cust_merc * 100",
	'PMC' => "PMC: vl_icms / cust_merc * 100"
);
  $pr->abre_excel_sql('Ficha6A_6B_6E', 'Ficha 6A ou 6B ou 6E', $sql, $col_format, $cabec, $form_final);


  // Planilha Ficha 5A
  $sql = "
SELECT tipo, per, orig, cod_item, descr_item, uni_item, cus_ini, icms_ini, cus_fim, icms_fim, quant_per, cust_unit, icms_unit,
   cod_ins, descr_ins, uni_ins, quant_ins, cust_unit2, icms_unit2, perd_norm, ganho_norm FROM
	(SELECT '5A' AS tipo, substr(s155.ord, 1, 4) AS per, 'Rg5155_5150_' || s155.ord AS orig, 
                s150.cod_item AS cod_item, o200.descr_item AS descr_item, o200.uni AS uni_item, s150.cus_ini AS cus_ini, s150.icms_ini AS icms_ini, s150.cus_fim AS cus_fim, s150.icms_fim AS icms_fim, s150.quant_per AS quant_per, s150.cust_unit AS cust_unit, s150.icms_unit AS icms_unit, 
        	s155.cod_ins AS cod_ins, o200ins.descr_item AS descr_ins, o200ins.uni AS uni_ins, s155.quant_ins AS quant_ins, s155.cust_unit AS cust_unit2, s155.icms_unit As icms_unit2, s155.perd_norm AS perd_norm, s155.ganho_norm AS ganho_norm,
        	round(s155.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s155.ord / 10000000 + 0.5) * 10000000 AS ordmax
                FROM s155
        	LEFT OUTER JOIN s150 ON s150.ord = s155.ords150
        	LEFT OUTER JOIN o200 AS o200ins ON o200ins.cod_item = s155.cod_ins AND o200ins.ord > ordmin AND o200ins.ord < ordmax
        	LEFT OUTER JOIN o200 ON o200.cod_item = s150.cod_item AND o200.ord > ordmin AND o200.ord < ordmax);
";
  $col_format = array(
	"D:D" => "0",
	"G:J" => "#.##0,00",
	"K:K" => "#.##0,0000",
	"L:M" => "#.##0,000000",
	"N:N" => "0",
	"Q:Q" => "#.##0,0000",
	"R:S" => "#.##0,000000",
	"T:U" => "#.##0,0000"
);
  $cabec = array(
  	'tipo' => "Tipo (5A)",
  	'per' => "Período",
	'orig' => "Origem - Registros e Tipo de Cálculo",
	'cod_item' => "Código do produto, conforme Registro 0200.",
	'descr_item' => "Descrição da mercadoria. (Reg 0200)",
	'uni_it' => "Unidade de medida do Item. (Reg 0200)",
	'cus_ini' => "Saldo inicial do Valor de custo.",
	'icms_ini' => "Saldo inicial do Valor ICMS.",
	'cus_fim' => "Saldo final do Valor de custo.",
	'icms_fim' =>  "Saldo final do Valor ICMS.",
	'quant_per' => "Quantidade de produto concluído e transferido no período.",
	'cust_unit' => "Custo Unitário do produto concluído e transferido no período.",
	'icms_unit' => "Valor Unitário de ICMS do produto concluído e transferido no período.",
	'cod_ins' => "Código do insumo, conforme Registro 0200.",
	'descr_ins' => "Descrição do insumo. (Reg 0200)",
	'uni_ins' => "Unidade de medida do insumo. (Reg 0200)",
	'quant_ins' => "Quantidade de insumo utilizada",
	'cust_unit_2' => "Custo Unitário do Insumo por unidade de produto de entrada, excluídos os tributos e contribuições recuperáveis",
	'icms_unit_2' => "Valor Unitário do ICMS do Insumo por unidade de produto",
	'perd_norm' => "Quantidade de perda normal no processo produtivo",
	'ganho_norm' => "Quantidade de ganho normal no processo produtivo"
);
  $pr->abre_excel_sql('Ficha5A', 'Ficha 5A - Ficha Técnica Unitária da Produção do Período', $sql, $col_format, $cabec, $form_final);


  // Planilha Ficha 3B
  $sql = "
SELECT substr(ord, 1, 4) AS per, origem || '-' || ord AS orig, cod_item, descr_item,
   num_lanc, dt_mov, hist, cfop,
   tip_doc, ser, num_doc, num_di, cnpj, uf, cod_lanc, fic_orig_dest, 
   ent_quan, ent_cust_merc, ent_vl_icms, ent_ipi, ent_out_imp_contrib, sai_quan, sai_cust_merc, sai_vl_icms,
   perc_crdout, valor_crdout, valor_desp
     FROM
      (SELECT s360.ord AS ord, 'Rg5360' AS origem, s360.cod_item AS cod_item, o200.descr_item AS descr_item, 
         Null AS num_lanc, '20' || substr(s360.ord, 1, 2) || '-' || substr(s360.ord, 3, 2) || '-01' AS dt_mov, '5360-Saldos Iniciais' AS hist, Null AS cfop, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS num_di,
	 Null AS cnpj, Null AS uf, Null AS cod_lanc, 
	 Null AS fic_orig_dest, 
	 quant_ini AS ent_quan,
	 cus_ini AS ent_cust_merc,
	 icms_ini AS ent_vl_icms,
	 'R5370' AS ent_ipi, 'r5370' AS ent_out_imp_contrib,
	 0 AS sai_quan,
	 0 AS sai_cust_merc,
	 0 AS sai_vl_icms,
         0 AS perc_crdout, 0 AS valor_crdout, 0 AS valor_desp, 
        round(s360.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s360.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s360
        LEFT OUTER JOIN o200 ON o200.cod_item = s360.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
      UNION ALL
      SELECT s365.ord AS ord, 'Rg5365_5360' AS origem,  s360.cod_item AS cod_item, o200.descr_item AS descr_item, 
         s365.num_lanc AS num_lanc, s365.dt_mov AS dt_mov, s365.hist AS hist, s365.cfop AS cfop, 
         s365.tip_doc AS tip_doc, s365.ser AS ser, s365.num_doc AS num_doc, s365.num_di AS num_di,
	 o150.cnpj AS cnpj, o150.uf AS uf, s365.cod_lanc AS cod_lanc, 
	 tab6_1.descri AS fic_orig_dest, 
	 CASE WHEN ind = 0 THEN s365.quan ELSE 0 END AS ent_quan,
	 CASE WHEN ind = 0 THEN round(s365.cust_merc, 2) ELSE 0 END AS ent_cust_merc,
	 CASE WHEN ind = 0 THEN round(s365.vl_icms, 2) ELSE 0 END AS ent_vl_icms,
	 'R5370' AS ent_ipi, 'r5370' AS ent_out_imp_contrib,
	 CASE WHEN ind = 1 THEN s365.quan ELSE 0 END AS sai_quan,
	 CASE WHEN ind = 1 THEN round(s365.cust_merc, 2) ELSE 0 END AS sai_cust_merc,
	 CASE WHEN ind = 1 THEN round(s365.vl_icms, 2) ELSE 0 END AS sai_vl_icms,
        s365.perc_crdout AS perc_crdout, s365.valor_crdout AS valor_crdout, s365.valor_desp AS valor_desp, 
        round(s365.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s365.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s365
        LEFT OUTER JOIN s360 ON s360.ord = s365.ords360
	    LEFT OUTER JOIN tab6_1 ON tab6_1.cod = s365.cod_lanc
        LEFT OUTER JOIN o150 ON o150.cod_part = s365.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
        LEFT OUTER JOIN o200 ON o200.cod_item = s360.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
        UNION ALL
         SELECT s360.ord AS ord, 'Rg5360' AS origem, s360.cod_item AS cod_item, o200.descr_item AS descr_item, 
         Null AS num_lanc, '20' || substr(s360.ord, 1, 2) || '-' || substr(s360.ord, 3, 2) || '-32' AS dt_mov, '5360-Saldos Finais' AS hist, Null AS cfop, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS num_di,
	 Null AS cnpj, Null AS uf, Null AS cod_lanc, 
	 Null AS fic_orig_dest, 
	 0 AS ent_quan,
	 0 AS ent_cust_merc,
	 0 AS ent_vl_icms,
	 'R5370' AS ent_ipi, 'r5370' AS ent_out_imp_contrib,
	 quant_fim AS sai_quan,
	 cus_fim AS sai_cust_merc,
	 icms_fim AS sai_vl_icms,
         0 AS perc_crdout, 0 AS valor_crdout, 0 AS valor_desp, 
        round(s360.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s360.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s360
        LEFT OUTER JOIN o200 ON o200.cod_item = s360.cod_item AND o200.ord > ordmin AND o200.ord < ordmax) AS f6a_aux
ORDER BY per, cod_item, dt_mov;
";
  $col_format = array(
	"E:E" => "0",
	"M:M" => "0",
	"Q:AA" => "#.##0,00"
);
  $cabec = array(
  	'per' => "Período",
	'orig' => "Origem - Registros e Tipo de Cálculo",
	'cod_item' => "Código da mercadoria",
	'descr_item' => "Descrição da mercadoria",
	'num_lanc' => "Número do Lançamento",
	'dt_mov' => "Data",
	'hist' => "Histórico",
	'cfop' => "CFOP",
	'tip_doc' => "Tipo Doc",
	'ser' => "Série",
	'num_doc' => "Nro Doc",
	'num_di' => "Número da DI ou DSI",
	'cnpj' => "Destinatário",
	'uf' => "UF Destinatário",
	'cod_lanc' => "Preencher com um dos seguintes códigos da Tabela de Codificação dos Lançamentos: 703211, 703212, 703216, 327017, 327018, 703219, 113241, 321141, 133241, 253245, 327661, 327662, 327664, 327771, 327772, 327773, 327774, 327775, 327776, 773278.
Dígito Descrição
1º Número do Módulo de Origem
2º Letra da Ficha de Origem - Convertido em Número
3º Número do Módulo de Destino
4º Letra da Ficha de Destino - Convertido em Número
5º Identificação do Lançamento
6º Identificação do Lançamento",
	'fic_orig_dest' => "Código da ficha de origem, valores permitidos: 1A, 1C e 2E e código da ficha de destino; valor permitido: 1A.",
	'ent_quan' => "Entrada - Quantidade",
	'ent_cust_merc' => "Entrada - Valor do Custo de entrada, excluídos os tributos e contribuições recuperáveis",
	'ent_vl_icms' => "Entrada - Valor do ICMS",
	'ent_ipi' => "Entrada - Valor do IPI",
	'ent_out_imp_contrib' => "Entrada - Valor de Outros Impostos e Contribuições",
	'sai_quan' => "Saída - Quantidade",
	'sai_cust_merc' => "Saída - Valor do Custo",
	'sai_vl_icms' => "Saída - Valor do ICMS",
	'perc_crdout' => "Porc Créd Outorgado",
	'valor_crdout' => "Val Créd Outorgado",
	'valor_desp' => "Val Créd Desp Operacionais"
);
  $pr->abre_excel_sql('Ficha3B', 'Ficha 3B', $sql, $col_format, $cabec, $form_final);



  // Planilha Ficha 3A
  $sql = "
SELECT substr(ord, 1, 4) AS per, origem || '-' || ord AS orig, cod_item, descr_item,
   num_lanc, dt_mov, hist, cfop,
   tip_doc, ser, num_doc, num_di, cnpj, uf, cod_lanc, fic_orig_dest, 
   ent_quan, ent_cust_merc, ent_vl_icms, ent_ipi, ent_out_imp_contrib, sai_quan, sai_cust_merc, sai_vl_icms,
   perc_crdout, valor_crdout, valor_desp
     FROM
      (SELECT s310.ord AS ord, 'Rg5310' AS origem, s310.cod_item AS cod_item, o200.descr_item AS descr_item, 
         Null AS num_lanc, '20' || substr(s310.ord, 1, 2) || '-' || substr(s310.ord, 3, 2) || '-01' AS dt_mov, '5310-Saldos Iniciais' AS hist, Null AS cfop, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS num_di,
	 Null AS cnpj, Null AS uf, Null AS cod_lanc, 
	 Null AS fic_orig_dest, 
	 quant_ini AS ent_quan,
	 cus_ini AS ent_cust_merc,
	 icms_ini AS ent_vl_icms,
	 'R5370' AS ent_ipi, 'r5370' AS ent_out_imp_contrib,
	 0 AS sai_quan,
	 0 AS sai_cust_merc,
	 0 AS sai_vl_icms,
         0 AS perc_crdout, 0 AS valor_crdout, 0 AS valor_desp, 
        round(s310.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s310.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s310
        LEFT OUTER JOIN o200 ON o200.cod_item = s310.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
      UNION ALL
      SELECT s315.ord AS ord, 'Rg5315_5310' AS origem,  s310.cod_item AS cod_item, o200.descr_item AS descr_item, 
         s315.num_lanc AS num_lanc, s315.dt_mov AS dt_mov, s315.hist AS hist, s315.cfop AS cfop, 
         s315.tip_doc AS tip_doc, s315.ser AS ser, s315.num_doc AS num_doc, 'Nihil' AS num_di,
	 o150.cnpj AS cnpj, o150.uf AS uf, s315.cod_lanc AS cod_lanc, 
	 tab6_1.descri AS fic_orig_dest, 
	 CASE WHEN ind = 0 THEN s315.quan ELSE 0 END AS ent_quan,
	 CASE WHEN ind = 0 THEN round(s315.cust_merc, 2) ELSE 0 END AS ent_cust_merc,
	 CASE WHEN ind = 0 THEN round(s315.vl_icms, 2) ELSE 0 END AS ent_vl_icms,
	 'Nihil' AS ent_ipi, 'Nihil' AS ent_out_imp_contrib,
	 CASE WHEN ind = 1 THEN s315.quan ELSE 0 END AS sai_quan,
	 CASE WHEN ind = 1 THEN round(s315.cust_merc, 2) ELSE 0 END AS sai_cust_merc,
	 CASE WHEN ind = 1 THEN round(s315.vl_icms, 2) ELSE 0 END AS sai_vl_icms,
        s315.perc_crdout AS perc_crdout, s315.valor_crdout AS valor_crdout, s315.valor_desp AS valor_desp, 
        round(s315.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s315.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s315
        LEFT OUTER JOIN s310 ON s310.ord = s315.ords310
	    LEFT OUTER JOIN tab6_1 ON tab6_1.cod = s315.cod_lanc
        LEFT OUTER JOIN o150 ON o150.cod_part = s315.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
        LEFT OUTER JOIN o200 ON o200.cod_item = s310.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
        UNION ALL
         SELECT s310.ord AS ord, 'Rg5310' AS origem, s310.cod_item AS cod_item, o200.descr_item AS descr_item, 
         Null AS num_lanc, '20' || substr(s310.ord, 1, 2) || '-' || substr(s310.ord, 3, 2) || '-32' AS dt_mov, '5310-Saldos Finais' AS hist, Null AS cfop, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS num_di,
	 Null AS cnpj, Null AS uf, Null AS cod_lanc, 
	 Null AS fic_orig_dest, 
	 0 AS ent_quan,
	 0 AS ent_cust_merc,
	 0 AS ent_vl_icms,
	 'R5370' AS ent_ipi, 'r5370' AS ent_out_imp_contrib,
	 quant_fim AS sai_quan,
	 cus_fim AS sai_cust_merc,
	 icms_fim AS sai_vl_icms,
         0 AS perc_crdout, 0 AS valor_crdout, 0 AS valor_desp, 
        round(s310.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s310.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s310
        LEFT OUTER JOIN o200 ON o200.cod_item = s310.cod_item AND o200.ord > ordmin AND o200.ord < ordmax) AS f6a_aux
ORDER BY per, cod_item, dt_mov;
";
  $col_format = array(
	"E:E" => "0",
	"M:M" => "0",
	"Q:AA" => "#.##0,00"
);
  $cabec = array(
  	'per' => "Período",
	'orig' => "Origem - Registros e Tipo de Cálculo",
	'cod_item' => "Código da mercadoria",
	'descr_item' => "Descrição da mercadoria",
	'num_lanc' => "Número do Lançamento",
	'dt_mov' => "Data",
	'hist' => "Histórico",
	'cfop' => "CFOP",
	'tip_doc' => "Tipo Doc",
	'ser' => "Série",
	'num_doc' => "Nro Doc",
	'num_di' => "Número da DI ou DSI",
	'cnpj' => "Destinatário",
	'uf' => "UF Destinatário",
	'cod_lanc' => "Preencher com um dos seguintes códigos da Tabela de Codificação dos Lançamentos: 703211, 703212, 703216, 327017, 327018, 703219, 113241, 321141, 133241, 253245, 327661, 327662, 327664, 327771, 327772, 327773, 327774, 327775, 327776, 773278.
Dígito Descrição
1º Número do Módulo de Origem
2º Letra da Ficha de Origem - Convertido em Número
3º Número do Módulo de Destino
4º Letra da Ficha de Destino - Convertido em Número
5º Identificação do Lançamento
6º Identificação do Lançamento",
	'fic_orig_dest' => "Código da ficha de origem, valores permitidos: 1A, 1C e 2E e código da ficha de destino; valor permitido: 1A.",
	'ent_quan' => "Entrada - Quantidade",
	'ent_cust_merc' => "Entrada - Valor do Custo de entrada, excluídos os tributos e contribuições recuperáveis",
	'ent_vl_icms' => "Entrada - Valor do ICMS",
	'ent_ipi' => "Entrada - Valor do IPI",
	'ent_out_imp_contrib' => "Entrada - Valor de Outros Impostos e Contribuições",
	'sai_quan' => "Saída - Quantidade",
	'sai_cust_merc' => "Saída - Valor do Custo",
	'sai_vl_icms' => "Saída - Valor do ICMS",
	'perc_crdout' => "Porc Créd Outorgado",
	'valor_crdout' => "Val Créd Outorgado",
	'valor_desp' => "Val Créd Desp Operacionais"
);
  $pr->abre_excel_sql('Ficha3A', 'Ficha 3A', $sql, $col_format, $cabec, $form_final);
  



  // Planilha Ficha 2A
  $sql = "
SELECT substr(ord, 1, 4) AS per, origem || '-' || ord AS orig, cod_item, descr_item, uni_item, 
   num_lanc, dt_mov, hist, cfop,
   tip_doc, ser, num_doc, num_di, cnpj, uf, cod_lanc, fic_orig_dest, cod_item_outra_tab, descr_outra, uni_outra, 
   ent_quan, ent_cust_merc, ent_vl_icms, ent_ipi, ent_out_imp_contrib, sai_quan, sai_cust_merc, sai_vl_icms,
   perc_crdout, valor_crdout, valor_desp
     FROM
      (SELECT s150.ord AS ord, 'Rg5150' AS origem, s150.cod_item AS cod_item, o200.descr_item AS descr_item,  o200.uni AS uni_item,
         Null AS num_lanc, '20' || substr(s150.ord, 1, 2) || '-' || substr(s150.ord, 3, 2) || '-01' AS dt_mov, '5150-Saldos Iniciais' AS hist, Null AS cfop, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS num_di,
	 Null AS cnpj, Null AS uf, Null AS cod_lanc, 
	 Null AS fic_orig_dest, 
	 Null AS cod_item_outra_tab, Null AS descr_outra, Null AS uni_outra, 
	 'Nihil' AS ent_quan,
	 cus_ini AS ent_cust_merc,
	 icms_ini AS ent_vl_icms,
	 'R5370' AS ent_ipi, 'r5370' AS ent_out_imp_contrib,
	 0 AS sai_quan,
	 0 AS sai_cust_merc,
	 0 AS sai_vl_icms,
         0 AS perc_crdout, 0 AS valor_crdout, 0 AS valor_desp, 
        round(s150.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s150.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s150
        LEFT OUTER JOIN o200 ON o200.cod_item = s150.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
      UNION ALL
      SELECT s160.ord AS ord, 'Rg5150_5160' AS origem,  s150.cod_item AS cod_item, o200.descr_item AS descr_item, o200.uni AS uni_item,
         s160.num_lanc AS num_lanc, s160.dt_mov AS dt_mov, s160.hist AS hist, 'Nihil' AS cfop, 
         s160.tip_doc AS tip_doc, s160.ser AS ser, s160.num_doc AS num_doc, 'Nihil' AS num_di,
	 'Nihil' AS cnpj, 'Nihil' AS uf, s160.cod_lanc AS cod_lanc, 
	 tab6_1.descri AS fic_orig_dest, 
	 s160.cod_item AS cod_item_outra_tab, o200out.descr_item AS descr_outra, o200out.uni AS uni_outra, 
	 CASE WHEN ind = 0 THEN s160.quan ELSE 0 END AS ent_quan,
	 CASE WHEN ind = 0 THEN round(s160.cust_item, 2) ELSE 0 END AS ent_cust_merc,
	 CASE WHEN ind = 0 THEN round(s160.vl_icms, 2) ELSE 0 END AS ent_vl_icms,
	 'R5020' AS ent_ipi, 'r5020' AS ent_out_imp_contrib,
	 CASE WHEN ind = 1 THEN s160.quan ELSE 0 END AS sai_quan,
	 CASE WHEN ind = 1 THEN round(s160.cust_item, 2) ELSE 0 END AS sai_cust_merc,
	 CASE WHEN ind = 1 THEN round(s160.vl_icms, 2) ELSE 0 END AS sai_vl_icms,
        'Nihil' AS perc_crdout, 'Nihil' AS valor_crdout, 'Nihil' AS valor_desp, 
        round(s160.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s160.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s160
        LEFT OUTER JOIN s150 ON s150.ord = s160.ords150
	    LEFT OUTER JOIN tab6_1 ON tab6_1.cod = s160.cod_lanc
        LEFT OUTER JOIN o200 ON o200.cod_item = s150.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
        LEFT OUTER JOIN o200 AS o200out ON o200out.cod_item = s160.cod_item AND o200out.ord > ordmin AND o200out.ord < ordmax
        UNION ALL
         SELECT s150.ord AS ord, 'Rg5150' AS origem, s150.cod_item AS cod_item, o200.descr_item AS descr_item,  o200.uni AS uni_item,
         Null AS num_lanc, '20' || substr(s150.ord, 1, 2) || '-' || substr(s150.ord, 3, 2) || '-32' AS dt_mov, '5150-Saldos Finais' AS hist, Null AS cfop, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS num_di,
	 Null AS cnpj, Null AS uf, Null AS cod_lanc, 
	 Null AS fic_orig_dest, 
	 Null AS cod_item_outra_tab, Null AS descr_outra, Null AS uni_outra, 
	 0 AS ent_quan,
	 0 AS ent_cust_merc,
	 0 AS ent_vl_icms,
	 'Nihil' AS ent_ipi, 'Nihil' AS ent_out_imp_contrib,
	 'Nihil' AS sai_quan,
	 cus_fim AS sai_cust_merc,
	 icms_fim AS sai_vl_icms,
         0 AS perc_crdout, 0 AS valor_crdout, 0 AS valor_desp, 
        round(s150.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s150.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s150
        LEFT OUTER JOIN o200 ON o200.cod_item = s150.cod_item AND o200.ord > ordmin AND o200.ord < ordmax) AS f6a_aux
ORDER BY per, cod_item, dt_mov;
";
  $col_format = array(
	"E:E" => "0",
	"N:N" => "0",
	"U:AE" => "#.##0,00"
);
  $cabec = array(
  	'per' => "Período",
	'orig' => "Origem - Registros e Tipo de Cálculo",
	'cod_item' => "Código da mercadoria",
	'descr_item' => "Descrição da mercadoria",
	'uni_it' => "Unidade de medida do Item. (Reg 0200)",
	'num_lanc' => "Número do Lançamento",
	'dt_mov' => "Data",
	'hist' => "Histórico",
	'cfop' => "CFOP",
	'tip_doc' => "Tipo Doc",
	'ser' => "Série",
	'num_doc' => "Nro Doc",
	'num_di' => "Número da DI ou DSI",
	'cnpj' => "Destinatário",
	'uf' => "UF Destinatário",
	'cod_lanc' => "Preencher com um dos seguintes códigos da Tabela de Codificação dos Lançamentos: 703211, 703212, 703216, 327017, 327018, 703219, 113241, 321141, 133241, 253245, 327661, 327662, 327664, 327771, 327772, 327773, 327774, 327775, 327776, 773278.",
	'fic_orig_dest' => "Código da ficha de origem, valores permitidos: 1A, 1C e 2E e código da ficha de destino; valor permitido: 1A.",
	'cod_out' => "Código do item controlado na ficha de origem ou destino conforme Registro 0200.",
	'descr_out' => "Descrição do item controlado na ficha de origem ou destino conforme Registro 0200",
	'uni_out' => "Unidade do item controlado na ficha de origem ou destino conforme Registro 0200",
	'ent_quan' => "Entrada - Quantidade",
	'ent_cust_merc' => "Entrada - Valor do Custo de entrada, excluídos os tributos e contribuições recuperáveis",
	'ent_vl_icms' => "Entrada - Valor do ICMS",
	'ent_ipi' => "Entrada - Valor do IPI",
	'ent_out_imp_contrib' => "Entrada - Valor de Outros Impostos e Contribuições",
	'sai_quan' => "Saída - Quantidade",
	'sai_cust_merc' => "Saída - Valor do Custo",
	'sai_vl_icms' => "Saída - Valor do ICMS",
	'perc_crdout' => "Porc Créd Outorgado",
	'valor_crdout' => "Val Créd Outorgado",
	'valor_desp' => "Val Créd Desp Operacionais"
);
  $pr->abre_excel_sql('Ficha2A', 'Ficha 2A', $sql, $col_format, $cabec, $form_final);


  // Planilha Ficha 1C
  $sql = "
SELECT substr(ord, 1, 4) AS per, origem || '-' || ord AS orig, cod_item, descr_item,
   num_lanc, dt_mov, hist, cfop,
   tip_doc, ser, num_doc, cnpj, uf, cod_lanc, fic_orig_dest, cod_item_outra_tab, descr_outra, uni_outra, 
   ent_quan, ent_cust_merc, ent_vl_icms, ent_out_imp_contrib, sai_quan, sai_cust_merc, sai_vl_icms
     FROM
      (SELECT s085.ord AS ord, 'Rg5085_5080' AS origem,  s080.cod_energia AS cod_item, o200.descr_item AS descr_item, 
         s085.num_lanc AS num_lanc, s085.dt_mov AS dt_mov, s085.hist AS hist, s085.cfop AS cfop, 
         s085.tip_doc AS tip_doc, s085.ser AS ser, s085.num_doc AS num_doc, 
	 o150.cnpj AS cnpj, o150.uf AS uf, s085.cod_lanc AS cod_lanc, 
	 tab6_1.descri AS fic_orig_dest, 
	 s085.cod_item AS cod_item_outra_tab, o200out.descr_item AS descr_outra, o200out.uni AS uni_outra, 
	 CASE WHEN ind = 0 THEN s085.quan ELSE 0 END AS ent_quan,
	 CASE WHEN ind = 0 THEN round(s085.cust_ener, 2) ELSE 0 END AS ent_cust_merc,
	 CASE WHEN ind = 0 THEN round(s085.vl_icms, 2) ELSE 0 END AS ent_vl_icms,
	 Null AS ent_out_imp_contrib,
	 CASE WHEN ind = 1 THEN s085.quan ELSE 0 END AS sai_quan,
	 CASE WHEN ind = 1 THEN round(s085.cust_ener, 2) ELSE 0 END AS sai_cust_merc,
	 CASE WHEN ind = 1 THEN round(s085.vl_icms, 2) ELSE 0 END AS sai_vl_icms,
        round(s085.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s085.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s085
        LEFT OUTER JOIN s080 ON s080.ord = s085.ords080
	LEFT OUTER JOIN tab6_1 ON tab6_1.cod = s085.cod_lanc
        LEFT OUTER JOIN o150 ON o150.cod_part = s085.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
        LEFT OUTER JOIN o200 ON o200.cod_item = s080.cod_energia AND o200.ord > ordmin AND o200.ord < ordmax
        LEFT OUTER JOIN o200 AS o200out ON o200out.cod_item = s085.cod_item AND o200out.ord > ordmin AND o200out.ord < ordmax) AS f6a_aux
ORDER BY per, orig;
";
  $col_format = array(
	"E:E" => "0",
	"L:L" => "0",
	"S:Y" => "#.##0,00"
);
  $cabec = array(
  	'per' => "Período",
	'orig' => "Origem - Registros e Tipo de Cálculo",
	'cod_item' => "Código da mercadoria",
	'descr_item' => "Descrição da mercadoria",
	'num_lanc' => "Número do Lançamento",
	'dt_mov' => "Data",
	'hist' => "Histórico",
	'cfop' => "CFOP",
	'tip_doc' => "Tipo Doc",
	'ser' => "Série",
	'num_doc' => "Nro Doc",
	'cnpj' => "Destinatário",
	'uf' => "UF Destinatário",
	'cod_lanc' => "Preencher com um dos seguintes códigos da Tabela de Codificação dos Lançamentos: 701311, 701316, 137017, 137018, 701319, 132121, 132221, 132321, 132421, 132521, 132721, 133241, 211343, 221343, 271343, 137663, 137665, 131185 e 111388.",
	'fic_orig_dest' => "Código da ficha de origem - valores permitidos: 1A, 2A, 2B e 2G ou código da ficha de destino - valores permitidos: 1A, 2A, 2B, 2C, 2D, 2E e 2G. e 3B.",
	'cod_out' => "Código do item controlado na ficha de origem ou destino conforme Registro 0200.",
	'descr_out' => "Descrição do item controlado na ficha de origem ou destino conforme Registro 0200",
	'uni_out' => "Unidade do item controlado na ficha de origem ou destino conforme Registro 0200",
	'ent_quan' => "Entrada - Quantidade",
	'ent_cust_merc' => "Entrada - Valor do Custo de entrada, excluídos os tributos e contribuições recuperáveis",
	'ent_vl_icms' => "Entrada - Valor do ICMS",
	'ent_out_imp_contrib' => "Entrada - Valor de Outros Impostos e Contribuições",
	'sai_quan' => "Saída - Quantidade",
	'sai_cust_merc' => "Saída - Valor do Custo",
	'sai_vl_icms' => "Saída - Valor do ICMS"
);
  $pr->abre_excel_sql('Ficha1C', 'Ficha 1C', $sql, $col_format, $cabec, $form_final);



  // Planilha Ficha 1A
  $sql = "
SELECT substr(ord, 1, 4) AS per, origem || '-' || ord AS orig, cod_item, descr_item, uni_item, 
   num_lanc, dt_mov, hist, cfop,
   tip_doc, ser, num_doc, num_di, cnpj, uf, cod_lanc, fic_orig_dest, cod_item_outra_tab, descr_outra, uni_outra, 
   ent_quan, ent_cust_merc, ent_vl_icms, ent_ipi, ent_out_imp_contrib, sai_quan, sai_cust_merc, sai_vl_icms,
   perc_crdout, valor_crdout, valor_desp
     FROM
      (SELECT s010.ord AS ord, 'Rg5010' AS origem, s010.cod_item AS cod_item, o200.descr_item AS descr_item, o200.uni AS uni_item, 
         Null AS num_lanc, '20' || substr(s010.ord, 1, 2) || '-' || substr(s010.ord, 3, 2) || '-01' AS dt_mov, '5010-Saldos Iniciais' AS hist, Null AS cfop, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS num_di,
	 Null AS cnpj, Null AS uf, Null AS cod_lanc, 
	 Null AS fic_orig_dest, 
	 Null AS cod_item_outra_tab, Null AS descr_outra, Null AS uni_outra, 
	 quant_ini AS ent_quan,
	 cus_ini AS ent_cust_merc,
	 icms_ini AS ent_vl_icms,
	 'R5370' AS ent_ipi, 'r5370' AS ent_out_imp_contrib,
	 0 AS sai_quan,
	 0 AS sai_cust_merc,
	 0 AS sai_vl_icms,
         0 AS perc_crdout, 0 AS valor_crdout, 0 AS valor_desp, 
        round(s010.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s010.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s010
        LEFT OUTER JOIN o200 ON o200.cod_item = s010.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
      UNION ALL
      SELECT s015.ord AS ord, 'Rg5015_5010' AS origem,  s010.cod_item AS cod_item, o200.descr_item AS descr_item, o200.uni AS uni_item,  
         s015.num_lanc AS num_lanc, s015.dt_mov AS dt_mov, s015.hist AS hist, s015.cfop AS cfop, 
         s015.tip_doc AS tip_doc, s015.ser AS ser, s015.num_doc AS num_doc, s015.num_di AS num_di,
	 o150.cnpj AS cnpj, o150.uf AS uf, s015.cod_lanc AS cod_lanc, 
	 tab6_1.descri AS fic_orig_dest, 
	 s015.cod_item_outra_tab AS cod_item_outra_tab, o200out.descr_item AS descr_outra, o200out.uni AS uni_outra, 
	 CASE WHEN ind = 0 THEN s015.quan ELSE 0 END AS ent_quan,
	 CASE WHEN ind = 0 THEN round(s015.cust_merc, 2) ELSE 0 END AS ent_cust_merc,
	 CASE WHEN ind = 0 THEN round(s015.vl_icms, 2) ELSE 0 END AS ent_vl_icms,
	 'R5020' AS ent_ipi, 'r5020' AS ent_out_imp_contrib,
	 CASE WHEN ind = 1 THEN s015.quan ELSE 0 END AS sai_quan,
	 CASE WHEN ind = 1 THEN round(s015.cust_merc, 2) ELSE 0 END AS sai_cust_merc,
	 CASE WHEN ind = 1 THEN round(s015.vl_icms, 2) ELSE 0 END AS sai_vl_icms,
        'Nihil' AS perc_crdout, 'Nihil' AS valor_crdout, 'Nihil' AS valor_desp, 
        round(s015.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s015.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s015
        LEFT OUTER JOIN s010 ON s010.ord = s015.ords010
	LEFT OUTER JOIN tab6_1 ON tab6_1.cod = s015.cod_lanc
        LEFT OUTER JOIN o150 ON o150.cod_part = s015.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
        LEFT OUTER JOIN o200 ON o200.cod_item = s010.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
	LEFT OUTER JOIN o200 AS o200out ON o200out.cod_item = s015.cod_item_outra_tab AND o200out.ord > ordmin AND o200out.ord < ordmax
        UNION ALL
         SELECT s010.ord AS ord, 'Rg5010' AS origem, s010.cod_item AS cod_item, o200.descr_item AS descr_item, o200.uni AS uni_item, 
         Null AS num_lanc, '20' || substr(s010.ord, 1, 2) || '-' || substr(s010.ord, 3, 2) || '-32' AS dt_mov, '5010-Saldos Finais' AS hist, Null AS cfop, 
         Null AS tip_doc, Null AS ser, Null AS num_doc, Null AS num_di,
	 Null AS cnpj, Null AS uf, Null AS cod_lanc, 
	 Null AS fic_orig_dest, 
	 Null AS cod_item_outra_tab, Null AS descr_outra, Null AS uni_outra, 
	 0 AS ent_quan,
	 0 AS ent_cust_merc,
	 0 AS ent_vl_icms,
	 'R5370' AS ent_ipi, 'r5370' AS ent_out_imp_contrib,
	 quant_fim AS sai_quan,
	 cus_fim AS sai_cust_merc,
	 icms_fim AS sai_vl_icms,
         0 AS perc_crdout, 0 AS valor_crdout, 0 AS valor_desp, 
        round(s010.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s010.ord / 10000000 + 0.5) * 10000000 AS ordmax
        FROM s010
        LEFT OUTER JOIN o200 ON o200.cod_item = s010.cod_item AND o200.ord > ordmin AND o200.ord < ordmax) AS f6a_aux
ORDER BY per, cod_item, dt_mov;
";
  $col_format = array(
	"E:E" => "0",
	"N:N" => "0",
	"U:AE" => "#.##0,00"
);
  $cabec = array(
  	'per' => "Período",
	'orig' => "Origem - Registros e Tipo de Cálculo",
	'cod_item' => "Código da mercadoria. (Reg 0200)",
	'descr_item' => "Descrição da mercadoria. (Reg 0200)",
	'uni_it' => "Unidade de medida do Item. (Reg 0200)",
	'num_lanc' => "Número do Lançamento",
	'dt_mov' => "Data",
	'hist' => "Histórico",
	'cfop' => "CFOP",
	'tip_doc' => "Tipo Doc",
	'ser' => "Série",
	'num_doc' => "Nro Doc",
	'num_di' => "Número da DI ou DSI",
	'cnpj' => "Destinatário",
	'uf' => "UF Destinatário",
	'cod_lanc' => "Preencher com um dos seguintes códigos da Tabela de Codificação dos Lançamentos: 703211, 703212, 703216, 327017, 327018, 703219, 113241, 321141, 133241, 253245, 327661, 327662, 327664, 327771, 327772, 327773, 327774, 327775, 327776, 773278.",
	'fic_orig_dest' => "Código da ficha de origem, valores permitidos: 1A, 1C e 2E e código da ficha de destino; valor permitido: 1A.",
	'cod_out' => "Código do item controlado na ficha de origem ou destino conforme Registro 0200.",
	'descr_out' => "Descrição do item controlado na ficha de origem ou destino conforme Registro 0200",
	'uni_out' => "Unidade do item controlado na ficha de origem ou destino conforme Registro 0200",
	'ent_quan' => "Entrada - Quantidade",
	'ent_cust_merc' => "Entrada - Valor do Custo de entrada, excluídos os tributos e contribuições recuperáveis",
	'ent_vl_icms' => "Entrada - Valor do ICMS",
	'ent_ipi' => "Entrada - Valor do IPI",
	'ent_out_imp_contrib' => "Entrada - Valor de Outros Impostos e Contribuições",
	'sai_quan' => "Saída - Quantidade",
	'sai_cust_merc' => "Saída - Valor do Custo",
	'sai_vl_icms' => "Saída - Valor do ICMS",
	'perc_crdout' => "Porc Créd Outorgado",
	'valor_crdout' => "Val Créd Outorgado",
	'valor_desp' => "Val Créd Desp Operacionais"
);
  $pr->abre_excel_sql('Ficha1A', 'Ficha 1A', $sql, $col_format, $cabec, $form_final);

  
  $pr->finaliza_excel();
}


?>