<?php

$pr->aud_registra(new PrMenu("efd_livros", "E_FD", "LivrosFiscais", "efd"));

function efd_livros() {

  global $pr;

  $pr->inicia_excel('EFD_LivrosFiscais');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';


  // Planilha Conferência de Registros
  $sql = "
SELECT round(c190.ord / 10000000 - 0.49) AS mes, 'c190' AS tipo, 
   sum(CASE WHEN c190.cfop < 5000 THEN  c190.vl_icms ELSE 0 END) AS creditos,
   sum(CASE WHEN c190.cfop > 5000 THEN  c190.vl_icms ELSE 0 END) AS debitos
   FROM c190
   GROUP BY mes
UNION ALL
SELECT round(c490.ord / 10000000 - 0.49) AS mes, 'c490' AS tipo, 
   0 AS creditos,
   sum(c490.vl_icms) AS debitos
   FROM c490
   GROUP BY mes
UNION ALL
SELECT round(c590.ord / 10000000 - 0.49) AS mes, 'c590' AS tipo, 
   sum(CASE WHEN c590.cfop < 5000 THEN  c590.vl_icms ELSE 0 END) AS creditos,
   sum(CASE WHEN c590.cfop > 5000 THEN  c590.vl_icms ELSE 0 END) AS debitos
   FROM c590
   GROUP BY mes
UNION ALL
SELECT round(c850.ord / 10000000 - 0.49) AS mes, 'c850' AS tipo, 
   sum(CASE WHEN c850.cfop < 5000 THEN  c850.vl_icms ELSE 0 END) AS creditos,
   sum(CASE WHEN c850.cfop > 5000 THEN  c850.vl_icms ELSE 0 END) AS debitos
   FROM c850
   GROUP BY mes
UNION ALL
SELECT round(c890.ord / 10000000 - 0.49) AS mes, 'c890' AS tipo, 
   sum(CASE WHEN c890.cfop < 5000 THEN  c890.vl_icms ELSE 0 END) AS creditos,
   sum(CASE WHEN c890.cfop > 5000 THEN  c890.vl_icms ELSE 0 END) AS debitos
   FROM c890
   GROUP BY mes
UNION ALL
SELECT round(d190.ord / 10000000 - 0.49) AS mes, 'd190' AS tipo, 
   sum(CASE WHEN d190.cfop < 5000 THEN  d190.vl_icms ELSE 0 END) AS creditos,
   sum(CASE WHEN d190.cfop > 5000 THEN  d190.vl_icms ELSE 0 END) AS debitos
   FROM d190
   GROUP BY mes
UNION ALL
SELECT round(d590.ord / 10000000 - 0.49) AS mes, 'd590' AS tipo, 
   sum(CASE WHEN d590.cfop < 5000 THEN  d590.vl_icms ELSE 0 END) AS creditos,
   sum(CASE WHEN d590.cfop > 5000 THEN  d590.vl_icms ELSE 0 END) AS debitos
   FROM d590
   GROUP BY mes
UNION ALL
SELECT round(e110.ord / 10000000 - 0.49) AS mes, 'e110' AS tipo, 
   -vl_tot_creditos AS creditos,
   -vl_tot_debitos AS debitos
   FROM e110
   GROUP BY mes
;
";
  $col_format = array(
	"C:D" => "#.##0,00"
);
  $cabec = array(
	'Mes' => "Mês",
	'Reg' => "Tipo de Registro",
	'Créditos' => "Créditos",
	'Débitos' => "Débitos"

);
  $pr->abre_excel_sql('Conf', 'Conferência De Registros (Somar para Conferir)', $sql, $col_format, $cabec, $form_final);

  // Planilha Comunic_D590_D500
  $sql = "
SELECT  d590.*, d500.*, o150.*,
   round(d590.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(d590.ord / 10000000 + 0.5) * 10000000 AS ordmax
   FROM d590
   LEFT OUTER JOIN d500 ON d500.ord = d590.ordD500
   LEFT OUTER JOIN o150 ON o150.cod_part = d500.cod_part AND o150.ord > ordmin AND o150.ord < ordmax;
";
  $col_format = array(
	"A:B" => "0",
	"F:K" => "#.##0,00",
	"M:M" => "0",
	"X:AE" => "#.##0,00",
	"AG:AH" => "#.##0,00",
	"AK:AK" => "0",
	"AO:AQ" => "0",
	"AX:AY" => "0"
);
  $cabec = array(
	'OrdD590' => "Número da Linha do Registro D590",
	'OrdD500_D590' => "Número da Linha do Registro D500",
	'cst_icms' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1", 
	'cfop' => "Código Fiscal de Operação e Prestação do agrupamento de itens", 
	'aliq_icms' => "Alíquota do ICMS",
	'vl_opr' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_bc_icms' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms' => "Parcela correspondente ao 'Valor do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_bc_icms_uf' => "Parcela correspondente ao valor da base de cálculo do ICMS de outras UFs, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms_uf' => "Parcela correspondente ao valor do ICMS de outras UFs, referente à combinação de CST_ICMS, CFOP, e alíquota do ICMS.",
	'vl_red_bc' => "Valor não tributado em função da redução da base de cálculo do ICMS, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)",
	'OrdD500' => "Número da Linha do Registro D500",
	'ind_oper' => "Indicador do Tipo de Operação 0-Entrada 1-Saída",
	'ind_emit' => "Indicador do Emitente do Doc.Fiscal 0-Emissão Própria  1-Terceiros",
	'cod_part' => "Código do participante (campo 02 do Registro 0150): - do emitente do documento ou do remetente das mercadorias, no caso de entradas; - do adquirente, no caso de saídas",
	'cod_mod' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1",
	'cod_sit' => "Código da situação do documento fiscal, conforme a Tabela 4.1.2
00 - Documento Regular
01 - Documento Regular Extemporâneo
02 - Documento Cancelado
03 - Documento Cancelado Extemporâneo
04 - NF-e ou CT-e denegado
05 - NF-e ou CT-e - Numeração Inutilizada
06 - Documento Fiscal Complementar
07 - Documento Fiscal Complementar Extemporâneo
08 - Documentos Fiscal emitido com base em Regime Especial ou Norma Específica",
	'ser' => "Série do documento fiscal",
	'sub ser' => "Subsérie do documento fiscal",
	'num_doc' => "Número do documento fiscal",
	'dt_doc' => "Data da emissão do documento fiscal",
	'dt_a_p' => "Data da entrada (aquisição) ou da saída (prestação do serviço)",
	'vl_doc' => "Valor total do documento fiscal",
	'vl_desc' => "Valor total do desconto",
	'vl_serv' => "Valor da prestação de serviços",
	'vl_serv_nt' => "Valor total dos serviços não-tributados pelo ICMS",
	'vl_terc' => "Valor total cobrado em nome de terceiros",
	'vl_da' => "Valor total de despesas acessórias indicadas no documento fiscal",
	'vl_bc_icms_D500' => "Valor da base de cálculo do ICMS",
	'vl_icms_D500' => "Valor do ICMS",
	'cod_inf' => "Código da informação complementar do documento fiscal (campo 02 do Registro 0450)",
	'vl_pis' => "Valor total do PIS",
	'vl_cofins' => "Valor total da COFINS",
	'cod_cta' => "Código da conta analítica contábil debitada/creditada",
	'tp_assinante' => "Código do Tipo de Assinante:
1 - Comercial/Industrial
2 - Poder Público
3 - Residencial/Pessoa física
4 - Público
5 - Semi-Público
6 - Outros",
	'Ord0150' => "Número da Linha do Registro 0150",
	'cod_part_0150' => "Código de identificação do participante no arquivo",
	'nome' => "Nome pessoal ou empresarial do participante",
	'cod_pais' => "Código do país do participante, conforme a tabela do item 3.2.1",
	'cnpj' => "CNPJ do participante",
	'cpf' => "CPF do participante",
	'ie' => "Inscrição Estadual do participante",
	'cod_mun' => "Código do município, conforme a tabela IBGE",
	'suframa' => "Número de inscrição do participante na SUFRAMA",
	'end' => "Logradouro e endereço do imóvel",
	'num' => "Número do imóvel",
	'compl' => "Dados complementares do endereço",
	'bairro' => "Bairro em que o imóvel está situado",
	'ordmin' => "Uso interno do conversor, para fins de relacionamento entre tabelas",
	'ordmax' => "Uso interno do conversor, para fins de relacionamento entre tabelas"
);
  $pr->abre_excel_sql('Comunic_D590_D500', 'Serviços de Comunicação D590_D500', $sql, $col_format, $cabec, $form_final);
  


  
  // Planilha Transp_d190_d100_d120
  $sql = "
SELECT  d190.*, d100.*, d120.*, 
  tab_munic.uf, replace(tab_munic.munic, '\t', ''), tab_munic_dest.uf, replace(tab_munic_dest.munic, '\t', ''), o150.*,
   round(d190.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(d190.ord / 10000000 + 0.5) * 10000000 AS ordmax
   FROM d190
   LEFT OUTER JOIN d100 ON d100.ord = d190.ordD100
   LEFT OUTER JOIN d120 ON d120.ordD100 = d190.ordD100
   LEFT OUTER JOIN o150 ON o150.cod_part = d100.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
   LEFT OUTER JOIN tab_munic ON tab_munic.cod = d120.cod_mun_orig
   LEFT OUTER JOIN tab_munic AS tab_munic_dest ON tab_munic_dest.cod = d120.cod_mun_dest;
";
  $col_format = array(
	"A:B" => "0",
	"F:I" => "#.##0,00",
	"K:K" => "0",
	"Y:AE" => "#.##0,00",
	"AH:AI" => "0",
	"AR:AR" => "0",
	"AV:AX" => "0",
	"BE:BF" => "0"
);
  $cabec = array(
	'OrdD190' => "Número da Linha do Registro D190",
	'OrdD100_D190' => "Número da Linha do Registro D100",
	'cst_icms' => "Código da Situação Tributária, conforme a tabela indicada no item 4.3.1",
	'cfop' => "Código Fiscal de Operação e Prestação, conforme a tabela indicada no item 4.2.2",
	'aliq_icms' => "Alíquota do ICMS",
	'vl_opr' => "Valor da operação correspondente à combinação de CST_ICMS, CFOP, e alíquota do ICMS.",
	'vl_bc_icms' => "Parcela correspondente ao Valor da base de cálculo do ICMS referente à combinação CST_ICMS, CFOP, e alíquota do ICMS",
	'vl_icms' => "Parcela correspondente ao Valor do ICMS referente à combinação CST_ICMS, CFOP e alíquota do ICMS",
	'vl_red_bc' => "Valor não tributado em função da redução da base de cálculo do ICMS, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)",
	'OrdD100' => "Número da Linha do Registro D100",
	'ind_oper' => "Indicador do tipo de operação: 0- Aquisição; 1- Prestação",
	'ind_emit' => "Indicador do emitente do documento fiscal: 0- Emissão própria; 1- Terceiros",
	'cod_part' => "Código do participante (campo 02 do Registro 0150): - do prestador de serviço, no caso de aquisição de serviço; - do tomador do serviço, no caso de prestação de serviços.",
	'cod_mod' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1",
	'cod_sit' => "Código da situação do documento fiscal, conforme a Tabela 4.1.2",
	'ser' => "Série do documento fiscal",
	'sub' => "Subsérie do documento fiscal",
	'num_doc' => "Número do documento fiscal",
	'chv_cte' => "Chave do Conhecimento de Transporte Eletrônico",
	'dt_doc' => "Data da emissão do documento fiscal",
	'dt_a_p' => "Data da aquisição ou da prestação do serviço",
	'tp_ct-e' => "Tipo de Conhecimento de Transporte Eletrônico conforme definido no Manual de Integração do CT-e",
	'chv_cte_ref' => "Chave do CT-e de referência cujos valores foram complementados (opção “1” do campo anterior) ou cujo débito foi anulado(opção “2” do campo anterior).",
	'vl_doc' => "Valor total do documento fiscal",
	'vl_desc' => "Valor total do desconto",
	'ind_frt' => "Indicador do tipo do frete: 0- Por conta de terceiros; 1- Por conta do emitente; 2- Por conta do destinatário; 9- Sem cobrança de frete.",
	'vl_serv' => "Valor total da prestação de serviço",
	'vl_bc_icms_D100' => "Valor da base de cálculo do ICMS",
	'vl_icms_D100' => "Valor do ICMS",
	'vl_nt' => "Valor não-tributado",
	'cod_inf' => "Código da informação complementar do documento fiscal (campo 02 do Registro 0450)",
	'cod_cta' => "Código da conta analítica contábil debitada/creditada",		
	'OrdD120' => "Número da Linha do Registro D120",
	'OrdD100_D120' => "Número da Linha do Registro D100",
	'cod_mun_orig' => "Código do município de origem do serviço, conforme a tabela IBGE(Preencher com 9999999, se Exterior)",
	'cod_mun_dest' => "Código do município de destino, conforme a tabela IBGE(Preencher com 9999999, se Exterior)",
	'veic_id' => "Placa de identificação do veículo",
	'uf_id' => "Sigla da UF da placa do veículo.",
	'uf_orig' => "Unidade da Federação",
	'munic_orig' => "Município",
	'uf_dest' => "Unidade da Federação",
	'munic_dest' => "Município",
	'Ord0150' => "Número da Linha do Registro 0150",
	'cod_part_0150' => "Código de identificação do participante no arquivo",
	'nome' => "Nome pessoal ou empresarial do participante",
	'cod_pais' => "Código do país do participante, conforme a tabela do item 3.2.1",
	'cnpj' => "CNPJ do participante",
	'cpf' => "CPF do participante",
	'ie' => "Inscrição Estadual do participante",
	'cod_mun' => "Código do município, conforme a tabela IBGE",
	'suframa' => "Número de inscrição do participante na SUFRAMA",
	'end' => "Logradouro e endereço do imóvel",
	'num' => "Número do imóvel",
	'compl' => "Dados complementares do endereço",
	'bairro' => "Bairro em que o imóvel está situado",
	'ordmin' => "Uso interno do conversor, para fins de relacionamento entre tabelas",
	'ordmax' => "Uso interno do conversor, para fins de relacionamento entre tabelas"
);
  $pr->abre_excel_sql('Transp_D190_D100_D120', 'Serviços de Transportes D190_D100_D120', $sql, $col_format, $cabec, $form_final);
  

  
  // Planilha RegSaidaC850_C800
  $sql = "
SELECT  c850.*, c800.ord, 
	  c800.cod_mod, c800.cod_sit, c800.num_cfe, c800.dt_doc, 
	  c800.vl_cfe, c800.vl_pis, c800.vl_cofins, 
	  c800.cnpj_cpf, c800.nr_sat, '#' || c800.chv_cfe AS chv_cfe, 
	  c800.vl_desc, c800.vl_merc, c800.vl_out_da, c800.vl_icms, c800.vl_pis_st, c800.vl_cofins_st 
   FROM c850
   LEFT OUTER JOIN c800 ON c800.ord = c850.ordC800;
";
  $col_format = array(
	"A:B" => "0",
	"F:H" => "#.##0,00",
	"J:J" => "0",
	"K:K" => "0",
	"L:L" => "0",
	"O:Q" => "#.##0,00",
	"R:S" => "0",
	"U:ZQ" => "#.##0,00"
);
  $cabec = array(
	'OrdC850' => "Número da Linha do Registro C850",
	'OrdC800_C850' => "Número da Linha do Registro C800",
	'cst_icms' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1", 
	'cfop' => "Código Fiscal de Operação e Prestação do agrupamento de itens", 
	'aliq_icms' => "Alíquota do ICMS",
	'vl_opr' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_bc_icms' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms' => "Parcela correspondente ao 'Valor do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)",
	'OrdC800' => "Número da Linha do Registro C800",	
	'cod_mod' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1", 
	'cod_sit' => "Código da situação do documento fiscal, conforme a Tabela 4.1.2", 
	'num_cfe' => "Número do Cupom Fiscal Eletrônico",
	'dt_doc' => "Data da emissão do Cupom Fiscal Eletrônico",
	'vl_cfe' => "Valor total do Cupom Fiscal Eletrônico",
	'vl_pis' => "Valor total do PIS",
	'vl_cofins' => "Valor total da COFINS",
	'cnpj_cpf' => "CNPJ ou CPF do destinatário",
	'nr_sat' => "Número de Série do equipamento",
	'chv_cfe' => "Chave do Cupom Fiscal Eletrônico",
	'vl_desc' => "Valor total de descontos",
	'vl_merc' => "Valor total das mercadorias e serviços",
	'vl_out_da' => "Valor total de outras despesas acessórias e acréscimos",
	'vl_icms_C800' => "Valor do ICMS",
	'vl_pis_st' => "Valor total do PIS retido por subst. trib.",
	'vl_cofins_st' => "Valor total da COFINS retido por subst. trib."
);
  $pr->abre_excel_sql('RegSaidaC850_C800', 'RegSaidaC850_C800', $sql, $col_format, $cabec, $form_final);
  

  
  // Planilha RegSaidaC590_C500_0150
  $sql = "
SELECT  c590.*, c500.*, o150.*,
   round(c590.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(c590.ord / 10000000 + 0.5) * 10000000 AS ordmax
   FROM c590
   LEFT OUTER JOIN c500 ON c500.ord = c590.ordC500
   LEFT OUTER JOIN o150 ON o150.cod_part = c500.cod_part AND o150.ord > ordmin AND o150.ord < ordmax;
";
  $col_format = array(
	"A:B" => "0",
	"E:K" => "#.##0,00",
	"M:M" => "0",
	"Y:AK" => "#.##0,00",
	"AN:AN" => "0",
	"AR:AS" => "0",
	"BA:BB" => "0"
);
  $cabec = array(
	'OrdC590' => "Número da Linha do Registro C590",
	'OrdC500_C590' => "Número da Linha do Registro C500",
	'cst_icms' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1", 
	'cfop' => "Código Fiscal de Operação e Prestação do agrupamento de itens", 
	'aliq_icms' => "Alíquota do ICMS",
	'vl_opr' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_bc_icms' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms' => "Parcela correspondente ao 'Valor do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_bc_icms_st' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' da substituição tributária referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms_st' => "Parcela correspondente ao valor creditado/debitado do ICMS da substituição tributária, referente à combinação de CST_ICMS, CFOP, e alíquota do ICMS.",
	'vl_red_bc' => "Valor não tributado em função da redução da base de cálculo do ICMS, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)",
	'OrdC500' => "Número da Linha do Registro C500",
	'ind_oper' => "Indicador do Tipo de Operação 0-Entrada 1-Saída",
	'ind_emit' => "Indicador do Emitente do Doc.Fiscal 0-Emissão Própria  1-Terceiros",
	'cod_part' => "Código do participante (campo 02 do Registro 0150): - do emitente do documento ou do remetente das mercadorias, no caso de entradas; - do adquirente, no caso de saídas",
	'cod_mod' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1",
	'cod_sit' => "Código da situação do documento fiscal, conforme a Tabela 4.1.2
00 - Documento Regular
01 - Documento Regular Extemporâneo
02 - Documento Cancelado
03 - Documento Cancelado Extemporâneo
04 - NF-e ou CT-e denegado
05 - NF-e ou CT-e - Numeração Inutilizada
06 - Documento Fiscal Complementar
07 - Documento Fiscal Complementar Extemporâneo
08 - Documentos Fiscal emitido com base em Regime Especial ou Norma Específica",
	'ser' => "Série do documento fiscal",
	'sub ser' => "Subsérie do documento fiscal",
	'cod_cons' => "- Código de classe de consumo de energia elétrica ou gás:
01 - Comercial
02 - Consumo Próprio
03 - Iluminação Pública
04 - Industrial
05 - Poder Público
06 - Residencial
07 - Rural
08 -Serviço Público.
- Código de classe de consumo de Fornecimento
D´água – Tabela 4.4.2.",
	'num_doc' => "Número do documento fiscal",
	'dt_doc' => "Data da emissão do documento fiscal",
	'dt_e_s' => "Data da entrada ou da saída",
	'vl_doc' => "Valor total do documento fiscal",
	'vl_desc' => "Valor total do desconto",
	'vl_forn' => "Valor total fornecido/consumido",
	'vl_serv_nt' => "Valor total dos serviços não-tributados pelo ICMS",
	'vl_terc' => "Valor total cobrado em nome de terceiros",
	'vl_da' => "Valor total de despesas acessórias indicadas no documento fiscal",
	'vl_bc_icms_C500' => "Valor da base de cálculo do ICMS",
	'vl_icms_C500' => "Valor do ICMS",
	'vl_bc_icms_st_C500' => "Valor da base de cálculo do ICMS substituição tributária",
	'vl_icms_st_C500' => "Valor do ICMS retido por substituição tributária",
	'cod_inf' => "Código da informação complementar do documento fiscal (campo 02 do Registro 0450)",
	'vl_pis' => "Valor total do PIS",
	'vl_cofins' => "Valor total da COFINS",
	'tp_ligacao' => "Código de tipo de Ligação
1 - Monofásico
2 - Bifásico
3 - Trifásico",
	'cod_grupo_tensao' => "Código de grupo de tensão:
01 - A1 - Alta Tensão (230kV ou mais)
02 - A2 - Alta Tensão (88 a 138kV)
03 - A3 - Alta Tensão (69kV)
04 - A3a - Alta Tensão (30kV a 44kV)
05 - A4 - Alta Tensão (2,3kV a 25kV)
06 - AS - Alta Tensão Subterrâneo 06
07 - B1 - Residencial 07
08 - B1 - Residencial Baixa Renda 08
09 - B2 - Rural 09
10 - B2 - Cooperativa de Eletrificação Rural
11 - B2 - Serviço Público de Irrigação
12 - B3 - Demais Classes
13 - B4a - Iluminação Pública - rede de
distribuição
14 - B4b - Iluminação Pública - bulbo de
lâmpada",
	'Ord0150' => "Número da Linha do Registro 0150",
	'cod_part_0150' => "Código de identificação do participante no arquivo",
	'nome' => "Nome pessoal ou empresarial do participante",
	'cod_pais' => "Código do país do participante, conforme a tabela do item 3.2.1",
	'cnpj' => "CNPJ do participante",
	'cpf' => "CPF do participante",
	'ie' => "Inscrição Estadual do participante",
	'cod_mun' => "Código do município, conforme a tabela IBGE",
	'suframa' => "Número de inscrição do participante na SUFRAMA",
	'end' => "Logradouro e endereço do imóvel",
	'num' => "Número do imóvel",
	'compl' => "Dados complementares do endereço",
	'bairro' => "Bairro em que o imóvel está situado",
	'ordmin' => "Uso interno do conversor, para fins de relacionamento entre tabelas",
	'ordmax' => "Uso interno do conversor, para fins de relacionamento entre tabelas"
);
  $pr->abre_excel_sql('RegSaidaC590_C500_0150', 'RegSaidaC590_C500_0150', $sql, $col_format, $cabec, $form_final);
  

  // Planilha RegSaidaC490_C405_C400
  $sql = "
SELECT 
	 c490.* ,
	 c405.* ,
     c400.* 
  FROM c490
  LEFT OUTER JOIN c405 ON c405.ord = c490.ordC405
  LEFT OUTER JOIN c400 ON c400.ord = c405.ordC400;
";
  $col_format = array(
	"A:B" => "0",
	"E:H" => "#.##0,00",
	"J:K" => "0",
	"P:Q" => "#.##0,00",
	"R:R" => "0"
);
  $cabec = array(
	'OrdC490' => "Número da Linha do Registro C490",
	'OrdC405_C490' => "Número da Linha do Registro C405",
	'CST_ICMS' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1", 
	'CFOP' => "Código Fiscal de Operação e Prestação", 
	'ALIQ_ICMS' => "Alíquota do ICMS", 
	'VL_OPR' => "Valor da operação correspondente à combinação de CST_ICMS, CFOP, e alíquota do ICMS, incluídas as despesas acessórias e acréscimos", 
	'VL_BC_ICMS' => "Valor acumulado da base de cálculo do ICMS, referente à combinação de CST_ICMS, CFOP, e alíquota do ICMS", 
	'VL_ICMS' => "Valor acumulado do ICMS, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS", 
	'COD_OBS' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)",
	'OrdC405' => "Número da Linha do Registro C405",
	'OrdC400_C405' => "Número da Linha do Registro C400",
	'DT_DOC' => "Data do movimento a que se refere a Redução Z", 
	'CRO' => "Posição do Contador de Reinício de Operação", 
	'CRZ' => "Posição do Contador de Redução Z",
	'NUM_COO_FIN' => "Número do Contador de Ordem de Operação do último documento emitido no dia. (Número do COO na Redução Z)",
	'GT_FIN' => "Valor do Grande Total final",
	'VL_BRT' => "Valor da venda bruta",
	'Ord_C400' => "Número da Linha do Registro C400",
	'COD_MOD' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1", 
	'ECF_MOD' => "Modelo do equipamento", 
	'ECF_FAB' => "Número de série de fabricação do ECF",
	'ECF_CX' => "Número do caixa atribuído ao ECF"	
);
  $pr->abre_excel_sql('RegSaidaC490_C405_C400', 'RegSaidaC490_C405_C400', $sql, $col_format, $cabec, $form_final);
  
	$pr->aud_prepara("
-- índice para acelerar
CREATE INDEX IF NOT EXISTS c101_ordC100 ON c101 (ordC100 ASC);
");


  // Planilha RegEntSaidaC190_C100_C101_0150
  $sql = "
SELECT 
	  c190.* ,
	  c100.ind_oper, c100.ind_emit, c100.cod_part, c100.cod_mod, c100.cod_sit,
	  c100.ser, c100.num_doc, '#' || c100.chv_nfe AS chv_nfe, c100.dt_doc, c100.dt_e_s, c100.vl_doc,
	  c100.ind_pgto, c100.vl_desc, c100.vl_abat_nt, c100.vl_merc,
	  c100.ind_frt, c100.vl_frt, c100.vl_seg, c100.vl_out_da,
	  c100.vl_bc_icms, c100.vl_icms, c100.vl_bc_icms_st, c100.vl_icms_st, c100.vl_ipi,
	  c100.vl_pis, c100.vl_cofins, c100.vl_pis_st, c100.vl_cofins_st, 
	  c101.*,
	  o150.*, 
	  round(c190.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(c190.ord / 10000000 + 0.5) * 10000000 AS ordmax
  FROM c190
  LEFT OUTER JOIN c100 ON c100.ord = c190.ordC100
  LEFT OUTER JOIN c101 ON c101.ordC100 = c100.ord
  LEFT OUTER JOIN o150 ON o150.cod_part = c100.cod_part AND o150.ord > ordmin AND o150.ord < ordmax;
";
  $col_format = array(
	"A:B" => "0",
	"E:L" => "#.##0,00",
	"P:P" => "0",
	"X:X" => "#.##0,00",
	"Z:AA" => "#.##0,00",
	"AC:AO" => "#.##0,00",
	"AP:AQ" => "0",
	"AR:AT" => "#.##0,00",
	"AU:AV" => "0",
	"AY:BA" => "0",
	"BH:BI" => "0"
);
  $cabec = array(
	'OrdC190' => "Número da Linha do Registro C190",
	'OrdC100' => "Número da Linha do Registro C100",
	'cst_icms' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1", 
	'cfop' => "Código Fiscal de Operação e Prestação do agrupamento de itens", 
	'aliq_icms' => "Alíquota do ICMS",
	'vl_opr' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_bc_icms' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms' => "Parcela correspondente ao 'Valor do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_bc_icms_st' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' da substituição tributária referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms_st' => "Parcela correspondente ao valor creditado/debitado do ICMS da substituição tributária, referente à combinação de CST_ICMS, CFOP, e alíquota do ICMS.",
	'vl_red_bc' => "Valor não tributado em função da redução da base de cálculo do ICMS, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_ipi' => "Parcela correspondente ao 'Valor do IPI' referente à combinação CST_ICMS, CFOP e alíquota do ICMS.",
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)",
	'ind_oper' => "Indicador do Tipo de Operação 0-Entrada 1-Saída",
	'ind_emit' => "Indicador do Emitente do Doc.Fiscal 0-Emissão Própria  1-Terceiros",
	'cod_part' => "Código do participante (campo 02 do Registro 0150): - do emitente do documento ou do remetente das mercadorias, no caso de entradas; - do adquirente, no caso de saídas",
	'cod_mod' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1",
	'cod_sit' => "Código da situação do documento fiscal, conforme a Tabela 4.1.2
00 - Documento Regular
01 - Documento Regular Extemporâneo
02 - Documento Cancelado
03 - Documento Cancelado Extemporâneo
04 - NF-e ou CT-e denegado
05 - NF-e ou CT-e - Numeração Inutilizada
06 - Documento Fiscal Complementar
07 - Documento Fiscal Complementar Extemporâneo
08 - Documentos Fiscal emitido com base em Regime Especial ou Norma Específica",
	'ser' => "Série do documento fiscal",
	'num_doc' => "Número do documento fiscal",
	'chv_nfe' => "Chave da Nota Fiscal Eletrônica",
	'dt_doc' => "Data da emissão do documento fiscal",
	'dt_e_s' => "Data da entrada ou da saída",
	'vl_doc' => "Valor total do documento fiscal",
	'ind_pgto' => "Indicador do tipo de pagamento:
0- À vista;
1- A prazo;
9- Sem pagamento.
Obs.: A partir de 01/07/2012 passará a ser:
Indicador do tipo de pagamento:
0- À vista;
1- A prazo;
2 - Outros",
	'vl_desc' => "Valor total do desconto",
	'vl_abat_nt' => "Abatimento não tributado e não comercial Ex. desconto ICMS nas remessas para ZFM.",
	'vl_merc' => "Valor total das mercadorias e serviços",
	'ind_frt' => "Indicador do tipo do frete:
0- Por conta de terceiros; 1- Por conta do emitente; 2- Por conta do destinatário; 9- Sem cobrança de frete.
Obs.: A partir de 01/01/2012 passará a ser:
Indicador do tipo do frete:
0- Por conta do emitente;
1- Por conta do destinatário/remetente;
2- Por conta de terceiros;
9- Sem cobrança de frete.",
	'vl_frt' => "Valor do frete indicado no documento fiscal",
	'vl_seg' => "Valor do seguro indicado no documento fiscal",
	'vl_out_da' => "Valor de outras despesas acessórias",
	'vl_bc_icms_c100' => "Valor da base de cálculo do ICMS",
	'vl_icms_c100' => "Valor do ICMS",
	'vl_bc_icms_st_c100' => "Valor da base de cálculo do ICMS substituição tributária",
	'vl_icms_st_c100' => "Valor do ICMS retido por substituição tributária",
	'vl_ipi_c100' => "Valor total do IPI",
	'vl_pis' => "Valor total do PIS",
	'vl_cofins' => "Valor total da COFINS",
	'vl_pis_st' => "Valor total do PIS retido por substituição tributária",
	'vl_cofins_st' => "Valor total da COFINS retido por substituição tributária",
	'OrdC101' => "Número da Linha do Registro C101",
	'OrdC100_C101' => "Número da Linha do Registro C100 relacionado ao C101",
	'vl_fcp_uf_dest' => "valor total relativo ao fundo de combate à pobreza (fcp) da uf de destino",
	'vl_icms_uf_dest' => "valor total do icms interestadual para a uf de destino",
	'vl_icms_uf_rem' => "valor total do icms interestadual para a uf do remetente",
	'Ord0150' => "Número da Linha do Registro 0150",
	'cod_part_0150' => "Código de identificação do participante no arquivo",
	'nome' => "Nome pessoal ou empresarial do participante",
	'cod_pais' => "Código do país do participante, conforme a tabela do item 3.2.1",
	'cnpj' => "CNPJ do participante",
	'cpf' => "CPF do participante",
	'ie' => "Inscrição Estadual do participante",
	'cod_mun' => "Código do município, conforme a tabela IBGE",
	'suframa' => "Número de inscrição do participante na SUFRAMA",
	'end' => "Logradouro e endereço do imóvel",
	'num' => "Número do imóvel",
	'compl' => "Dados complementares do endereço",
	'bairro' => "Bairro em que o imóvel está situado",
	'ordmin' => "Uso interno do conversor, para fins de relacionamento entre tabelas",
	'ordmax' => "Uso interno do conversor, para fins de relacionamento entre tabelas"
);
  $pr->abre_excel_sql('RegEntSaidaC190_C100_C101_0150', 'LivroEntradasSaidasBaseC190_C100_C101_0150', $sql, $col_format, $cabec, $form_final);
  
  $pr->finaliza_excel();
}


?>