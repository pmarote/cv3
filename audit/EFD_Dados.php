<?php

$pr->aud_registra(new PrMenu("efd_dados", "E_FD", "Dados da EFD", "efd"));

function efd_dados() {

  global $pr;

  $a_qtd_c100 = $pr->aud_sql2array("
SELECT count(*) AS contagem FROM c100;
");
  $a_qtd_c100[0]['contagem'] = number_format($a_qtd_c100[0]['contagem'], 0, ',', '.');

  $dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 200);

  $lbl_obs1 	= new GtkLabel("Este modulo exporta os registros dos EFDs carregados");
  $lbl_obs2 	= new GtkLabel("no Banco de Dados. Para evitar o risco de travamento no Excel,");
  $lbl_obs3 	= new GtkLabel("devido à grande quantidade de abas criadas,");
  $lbl_obs4 	= new GtkLabel("os dados serão separados em dois arquivos Excel, ");
  $lbl_obs5 	= new GtkLabel("um apenas para os Blocos C e D e outro para o restante.");
  $lbl_obs6 	= new GtkLabel("Total de Registros presentes no Registro C100: {$a_qtd_c100[0]['contagem']}");

  $dialog->vbox->pack_start($lbl_obs1, false, false, 2);
  $dialog->vbox->pack_start($lbl_obs2, false, false, 2);
  $dialog->vbox->pack_start($lbl_obs3, false, false, 2);
  $dialog->vbox->pack_start($lbl_obs4, false, false, 2);
  $dialog->vbox->pack_start($lbl_obs5, false, false, 2);
  $dialog->vbox->pack_start($lbl_obs6, false, false, 2);

  $dialog->add_button(Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL);
  $dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);

  $dialog->set_has_separator(True);
  $dialog->show_all();
  $response_id = $dialog->run();

  if ($response_id != Gtk::RESPONSE_OK) {
	$dialog->destroy();
	return;
  }

  $dialog->destroy();
  
  wecho("\nTarefa 1 de 2: Gerando EFD_Dados_do_EFD_Blocos_C_D");
  $pr->inicia_excel('EFD_Dados_do_EFD_Blocos_C_D');

  $form_final = '
	$this->excel_zoom_visualizacao(75);
	$this->excel_orientacao(2);		// paisagem
';


  $sql = "
SELECT * FROM D590;
";
  $col_format = array(
	"A:B" => "0",
	"F:K" => "#.##0,00",
);
  $cabec = array(
	'OrdD590' => "Número da Linha do Registro D590",
	'OrdD500' => "Número da Linha do Registro D500",
	'cst_icms' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1", 
	'cfop' => "Código Fiscal de Operação e Prestação do agrupamento de itens", 
	'aliq_icms' => "Alíquota do ICMS",
	'vl_opr' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_bc_icms' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms' => "Parcela correspondente ao 'Valor do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_bc_icms_uf' => "Parcela correspondente ao valor da base de cálculo do ICMS de outras UFs, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms_uf' => "Parcela correspondente ao valor do ICMS de outras UFs, referente à combinação de CST_ICMS, CFOP, e alíquota do ICMS.",
	'vl_red_bc' => "Valor não tributado em função da redução da base de cálculo do ICMS, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)"
);
  $pr->abre_excel_sql("D590", "D590 - Registro Analítico do documento - nota fiscal de Serviço de Comunicação (Código 21) e Telecomunicação (Código 22)", $sql, $col_format, $cabec, $form_final);



  
  $sql = "
SELECT *
	  FROM D500;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "0",
	"D:D" => "0",
	"L:V" => "#.##0,00",
	"W:W" => "0");
  $cabec = array(
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
	'vl_bc_icms' => "Valor da base de cálculo do ICMS",
	'vl_icms' => "Valor do ICMS",
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
6 - Outros"
);
  $pr->abre_excel_sql("D500", "D500 - nota fiscal de Serviço de Comunicação (Código 21) e Telecomunicação (Código 22)", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM D197;
";
  $col_format = array(
	"A:C" => "0",
	"G:J" => "#.##0,00"
);
  $cabec = array(
	'OrdD197' => "Número da Linha do Registro D197",
	'OrdD195' => "Número da Linha do Registro D195",
	'OrdD190' => "Número da Linha do Registro D190",
	'COD_AJ' => "Código do ajustes/benefício/incentivo, conforme tabela indicada no item 5.3",
	'DESCR_COMPL_AJ' => "Descrição complementar do ajuste do documento fiscal",
	'COD_ITEM' => "Código do item (campo 02 do Registro 0200)",
	'VL_BC_ICMS' => "Base de cálculo do ICMS ou do ICMS ST ",
	'ALIQ_ICMS' => "Alíquota do ICMS",
	'VL_ICMS' => "Valor do ICMS ou do ICMS ST",
	'VL_OUTROS' => "Outros valores"
);
  $pr->abre_excel_sql("D197", "D197 - OUTRAS OBRIGAÇÕES TRIBUTÁRIAS, AJUSTES E INFORMAÇÕES DE VALORES PROVENIENTES DE DOCUMENTO FISCAL", $sql, $col_format, $cabec, $form_final);
  

  $sql = "
SELECT * FROM D195;
";
  $col_format = array(
	"A:B" => "0"
);
  $cabec = array(
	'OrdD195' => "Número da Linha do Registro D195",
	'OrdD190' => "Número da Linha do Registro D190",
	'COD_OBS' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)",
	'TXT_COMPL' => "Descrição complementar do código de observação."
);
  $pr->abre_excel_sql("D195", "D195 - OBS DO LANÇTO FISCAL (CÓD 07, 08, 8B, 09, 10, 11, 26, 27, 57, 63 e 67)", $sql, $col_format, $cabec, $form_final);  

  $sql = "
SELECT * FROM D190;
";
  $col_format = array(
	"A:B" => "0",
	"E:I" => "#.##0,00");
  $cabec = array(
		'OrdD190' => "Número da Linha do Registro D190",
		'OrdD100' => "Número da Linha do Registro D100",
		'cst_icms' => "Código da Situação Tributária, conforme a tabela indicada no item 4.3.1",
		'cfop' => "Código Fiscal de Operação e Prestação, conforme a tabela indicada no item 4.2.2",
		'aliq_icms' => "Alíquota do ICMS",
		'vl_opr' => "Valor da operação correspondente à combinação de CST_ICMS, CFOP, e alíquota do ICMS.",
		'vl_bc_icms' => "Parcela correspondente ao Valor da base de cálculo do ICMS referente à combinação CST_ICMS, CFOP, e alíquota do ICMS",
		'vl_icms' => "Parcela correspondente ao Valor do ICMS referente à combinação CST_ICMS, CFOP e alíquota do ICMS",
		'vl_red_bc' => "Valor não tributado em função da redução da base de cálculo do ICMS, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
		'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)"
);
  $pr->abre_excel_sql("D190", "D190 - REGISTRO ANALÍTICO DOS DOCUMENTOS (CÓDIGO 07, 08, 8B, 09, 10, 11, 26, 27 e 57", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM D120;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "@");
  $cabec = array(
		'OrdD120' => "Número da Linha do Registro D120",
		'OrdD100' => "Número da Linha do Registro D100",
		'cod_mun_orig' => "Código do município de origem do serviço, conforme a tabela IBGE(Preencher com 9999999, se Exterior)",
		'cod_mun_dest' => "Código do município de destino, conforme a tabela IBGE(Preencher com 9999999, se Exterior)",
		'veic_id' => "Placa de identificação do veículo",
		'uf_id' => "Sigla da UF da placa do veículo."
);
  $pr->abre_excel_sql("D120", "D120 - COMPLEMENTO DA NOTA FISCAL DE SERVIÇOS DE TRANSPORTE (CÓDIGO 07)", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM D110;
";
  $col_format = array(
	"A:A" => "0",
	"E:F" => "#.##0,00");
  $cabec = array(
		'OrdD110' => "Número da Linha do Registro D110",
		'OrdD100' => "Número da Linha do Registro D100",
		'num_item' => "Número sequencial do item no documento fiscal",
		'cod_item' => "Código do item (campo 02 do Registro 0200)",
		'vl_serv' => "Valor do serviço",
		'vl_out' => "Outros valores"
);
  $pr->abre_excel_sql("D110", "D110 - ITENS DO DOCUMENTO - NOTA FISCAL DE SERVIÇOS DE TRANSPORTE (CÓDIGO 07)", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT 
	  ord,
	  ind_oper, ind_emit, cod_part, cod_mod, cod_sit ,
	  ser, sub, num_doc, '#' || chv_cte AS chv_cte, dt_doc, dt_a_p, tp_cte, chv_cte_ref,
	  vl_doc, vl_desc, ind_frt, vl_serv, vl_bc_icms, vl_icms, vl_nt,
	  cod_inf, cod_cta
  FROM D100;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "0",
	"D:D" => "0",
	"O:U" => "#.##0,00",
	"W:W" => "0");
  $cabec = array(
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
	'vl_bc_icms' => "Valor da base de cálculo do ICMS",
	'vl_icms' => "Valor do ICMS",
	'vl_nt' => "Valor não-tributado",
	'cod_inf' => "Código da informação complementar do documento fiscal (campo 02 do Registro 0450)",
	'cod_cta' => "Código da conta analítica contábil debitada/creditada"
);
  $pr->abre_excel_sql("D100", "D100 - NOTA FISCAL DE SERVIÇO DE TRANSPORTE (CÓDIGO 07) E CONHECIMENTOS DE TRANSPORTE RODOVIÁRIO DE CARGAS (CÓDIGO 08), CONHECIMENTOS DE TRANSPORTE DE CARGAS AVULSO (CÓDIGO 8B), AQUAVIÁRIO DE CARGAS (CÓDIGO 09), AÉREO (CÓDIGO 10), FERROVIÁRIO DE CARGAS (CÓDIGO 11) E MULTIMODAL DE CARGAS (CÓDIGO 26), NOTA FISCAL DE TRANSPORTE FERROVIÁRIO DE CARGA ( CÓDIGO 27) E CONHECIMENTO DE TRANSPORTE ELETRÔNICO – CT-e (CÓDIGO 57).", $sql, $col_format, $cabec, $form_final);



  $sql = "
SELECT * FROM C890;
";
  $col_format = array(
	"A:B" => "0",
	"F:H" => "#.##0,00",
);
  $cabec = array(
	'OrdC890' => "Número da Linha do Registro C890",
	'OrdC860' => "Número da Linha do Registro C860",
	'cst_icms' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1", 
	'cfop' => "Código Fiscal de Operação e Prestação do agrupamento de itens", 
	'aliq_icms' => "Alíquota do ICMS",
	'vl_opr' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_bc_icms' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms' => "Parcela correspondente ao 'Valor do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)"
);
  $pr->abre_excel_sql("C890", "C890 - resumo diário do cf-e-sat (código 59) por equipamento sat-cf-e", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT *
	  FROM C860;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "0",
	"C:C" => "0"
);
  $cabec = array(
	'OrdC860' => "Número da Linha do Registro C860",	
	'cod_mod' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1", 
	'nr_sat' => "Número de Série do equipamento SAT",
	'dt_doc' => "Data de emissão dos documentos fiscais", 
	'doc_ini' => "Número do documento inicial",
	'doc_fim' => "Número do documento final"
);
  $pr->abre_excel_sql("C860", "C860 - identificação do equipamento sat-cf-e", $sql, $col_format, $cabec, $form_final);
 

  $sql = "
SELECT * FROM C850;
";
  $col_format = array(
	"A:B" => "0",
	"F:H" => "#.##0,00"
);
  $cabec = array(
	'OrdC850' => "Número da Linha do Registro C850",
	'OrdC800' => "Número da Linha do Registro C800",
	'cst_icms' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1", 
	'cfop' => "Código Fiscal de Operação e Prestação do agrupamento de itens", 
	'aliq_icms' => "Alíquota do ICMS",
	'vl_opr' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_bc_icms' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms' => "Parcela correspondente ao 'Valor do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)"
);
  $pr->abre_excel_sql("C850", "C850 - registro analítico do cf-e-sat (codigo 59)", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT 
	  ord, 
	  cod_mod, cod_sit, num_cfe, dt_doc, 
	  vl_cfe, vl_pis, vl_cofins, 
	  cnpj_cpf, nr_sat, '#' || chv_cfe AS chv_cfe, 
	  vl_desc, vl_merc, vl_out_da, vl_icms, vl_pis_st, vl_cofins_st 
  FROM C800;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "0",
	"C:C" => "0",
	"F:H" => "#.##0,00",
	"I:J" => "0",
	"L:Q" => "#.##0,00"
);
  $cabec = array(
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
	'vl_icms' => "Valor do ICMS",
	'vl_pis_st' => "Valor total do PIS retido por subst. trib.",
	'vl_cofins_st' => "Valor total da COFINS retido por subst. trib."
);
  $pr->abre_excel_sql("C800", "C800 - cupom fiscal eletrônico – sat (cf-e-sat)", $sql, $col_format, $cabec, $form_final);
 


  $sql = "
SELECT * FROM C590;
";
  $col_format = array(
	"A:B" => "0",
	"F:K" => "#.##0,00",
);
  $cabec = array(
	'OrdC590' => "Número da Linha do Registro C590",
	'OrdC500' => "Número da Linha do Registro C500",
	'cst_icms' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1", 
	'cfop' => "Código Fiscal de Operação e Prestação do agrupamento de itens", 
	'aliq_icms' => "Alíquota do ICMS",
	'vl_opr' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_bc_icms' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms' => "Parcela correspondente ao 'Valor do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_bc_icms_st' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' da substituição tributária referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms_st' => "Parcela correspondente ao valor creditado/debitado do ICMS da substituição tributária, referente à combinação de CST_ICMS, CFOP, e alíquota do ICMS.",
	'vl_red_bc' => "Valor não tributado em função da redução da base de cálculo do ICMS, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)"
);
  $pr->abre_excel_sql("C590", "C590 - NF Energia Elétrica, Água Canalizada e Fornecimento de Gás", $sql, $col_format, $cabec, $form_final);


  
  $sql = "
SELECT *
	  FROM C500;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "0",
	"D:D" => "0",
	"M:Y" => "#.##0,00");
  $cabec = array(
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
	'vl_bc_icms' => "Valor da base de cálculo do ICMS",
	'vl_icms' => "Valor do ICMS",
	'vl_bc_icms_st' => "Valor da base de cálculo do ICMS substituição tributária",
	'vl_icms_st' => "Valor do ICMS retido por substituição tributária",
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
lâmpada"
);
  $pr->abre_excel_sql("C500", "C500 - Nota Fiscal Energia Elétrica, Água Canalizada e Gás", $sql, $col_format, $cabec, $form_final);
  
  
  $sql = "
SELECT * FROM C490;
";
  $col_format = array(
	"A:B" => "0",
	"E:H" => "#.##0,00",
);
  $cabec = array(
	'Ord' => "Número da Linha do Registro C490",
	'OrdC405' => "Número da Linha do Registro C405",
	'CST_ICMS' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1", 
	'CFOP' => "Código Fiscal de Operação e Prestação", 
	'ALIQ_ICMS' => "Alíquota do ICMS", 
	'VL_OPR' => "Valor da operação correspondente à combinação de CST_ICMS, CFOP, e alíquota do ICMS, incluídas as despesas acessórias e acréscimos", 
	'VL_BC_ICMS' => "Valor acumulado da base de cálculo do ICMS, referente à combinação de CST_ICMS, CFOP, e alíquota do ICMS", 
	'VL_ICMS' => "Valor acumulado do ICMS, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS", 
	'COD_OBS' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)"
);
  $pr->abre_excel_sql("C490", "C490 - REGISTRO ANALÍTICO DO MOVIMENTO DIÁRIO (CÓDIGO 02, 2D e 60)", $sql, $col_format, $cabec, $form_final);
  
 
  
  $sql = "
SELECT * FROM C470;
";
  $col_format = array(
	"A:B" => "0",
	"D:E" => "#.##0,00",
	"G:G" => "#.##0,00",
	"J:L" => "#.##0,00",
);
  $cabec = array(
	'Ord' => "Número da Linha do Registro C470",
	'OrdC460' => "Número da Linha do Registro C460",
	'COD_ITEM' => "Código do item (campo 02 do Registro 0200)", 
	'QTD' => "Quantidade do item", 
	'QTD_CANC' => "Quantidade cancelada, no caso de cancelamento parcial de item", 
	'UNID' => "Unidade do item (Campo 02 do registro 0190)", 
	'VL_ITEM' => "Valor total do item", 
	'CST_ICMS' => "Código da Situação Tributária, conforme a Tabela indicada no item 4.3.1.", 
	'CFOP' => "Código Fiscal de Operação e Prestação", 
	'ALIQ_ICMS' => "Alíquota do ICMS – Carga tributária efetiva em percentual", 
	'VL_PIS' => "Valor do PIS", 
	'VL_COFINS' => "Valor da COFINS"
);
  $pr->abre_excel_sql("C470", "C470 - ITENS DO DOCUMENTO FISCAL EMITIDO POR ECF (CÓDIGO 02 e 2D)", $sql, $col_format, $cabec, $form_final);
  
  
  $sql = "
SELECT * FROM C460;
";
  $col_format = array(
	"A:B" => "0",
	"J:J" => "0",
	"G:I" => "#.##0,00",
);
  $cabec = array(
	'Ord' => "Número da Linha do Registro C460",
	'OrdC405' => "Número da Linha do Registro C405",
	'COD_MOD' => "Código do modelo do documento fiscal, apresentar conforme a Tabela 4.1.1", 
	'COD_SIT' => "Código da situação do documento fiscal, conforme a Tabela 4.1.2", 
	'NUM_DOC' => "Número do documento fiscal (COO)", 
	'DT_DOC' => "Data da emissão do documento fiscal", 
	'VL_DOC' => "Valor total do documento fiscal", 
	'VL_PIS' => "Valor do PIS", 
	'VL_COFINS' => "Valor da COFINS", 
	'CPF_CNPJ' => "CPF ou CNPJ do adquirente", 
	'NOM_ADQ' => "Nome do adquirente"
);
  $pr->abre_excel_sql("C460", "C460 - DOCUMENTO FISCAL EMITIDO POR ECF (CÓDIGO 02, 2D e 60)", $sql, $col_format, $cabec, $form_final);
  
  
  $sql = "
SELECT * FROM C420;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "#.##0,00",
);
  $cabec = array(
	'Ord' => "Número da Linha do Registro C420",
	'OrdC405' => "Número da Linha do Registro C405",
	'COD_TOT_PAR' => "Valor acumulado no totalizador, relativo à respectiva Redução Z", 
	'VLR_ACUM_TOT' => "Valor acumulado no totalizador, relativo à respectiva Redução Z", 
	'NR_TOT' => "Número do totalizador quando ocorrer mais de uma situação com a mesma carga tributária efetiva", 
	'DESCR_NR_TOT' => "Descrição da situação tributária relativa ao totalizador parcial, quando houver mais de um com a mesma carga tributária efetiva"
);
  $pr->abre_excel_sql("C420", "C420 - REGISTRO DOS TOTALIZADORES PARCIAIS DA REDUÇÃO Z (COD 02, 2D e 60)", $sql, $col_format, $cabec, $form_final);
  
  
  $sql = "
SELECT * FROM C410;
";
  $col_format = array(
	"A:B" => "0",
	"C:D" => "#.##0,00",
);
  $cabec = array(
	'Ord' => "Número da Linha do Registro C410",
	'OrdC405' => "Número da Linha do Registro C405",
	'VL_PIS' => "Valor total do PIS", 
	'VL_COFINS' => "Valor total da COFINS"
);
  $pr->abre_excel_sql("C410", "C410 - PIS E COFINS TOTALIZADOS NO DIA (CÓDIGO 02 e 2D)", $sql, $col_format, $cabec, $form_final);
  
  
  $sql = "
SELECT * FROM C405;
";
  $col_format = array(
	"A:B" => "0",
	"G:H" => "#.##0,00",
);
  $cabec = array(
	'Ord' => "Número da Linha do Registro C405",
	'OrdC400' => "Número da Linha do Registro C400",
	'DT_DOC' => "Data do movimento a que se refere a Redução Z", 
	'CRO' => "Posição do Contador de Reinício de Operação", 
	'CRZ' => "Posição do Contador de Redução Z",
	'NUM_COO_FIN' => "Número do Contador de Ordem de Operação do último documento emitido no dia. (Número do COO na Redução Z)",
	'GT_FIN' => "Valor do Grande Total final",
	'VL_BRT' => "Valor da venda bruta"
);
  $pr->abre_excel_sql("C405", "C405 - REDUÇÃO Z (CÓDIGO 02, 2D e 60)", $sql, $col_format, $cabec, $form_final);
  

  $sql = "
SELECT * FROM C400;
";
  $col_format = array(
	"A:A" => "0",
);
  $cabec = array(
	'Ord' => "Número da Linha do Registro C400",
	'COD_MOD' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1", 
	'ECF_MOD' => "Modelo do equipamento", 
	'ECF_FAB' => "Número de série de fabricação do ECF",
	'ECF_CX' => "Número do caixa atribuído ao ECF"
);
  $pr->abre_excel_sql("C400", "EQUIPAMENTO ECF (CÓDIGO 02, 2D e 60)", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM C197;
";
  $col_format = array(
	"A:C" => "0",
	"G:J" => "#.##0,00"
);
  $cabec = array(
	'OrdC197' => "Número da Linha do Registro C197",
	'OrdC195' => "Número da Linha do Registro C195",
	'OrdC190' => "Número da Linha do Registro C190",
	'COD_AJ' => "Código do ajustes/benefício/incentivo, conforme tabela indicada no item 5.3",
	'DESCR_COMPL_AJ' => "Descrição complementar do ajuste do documento fiscal",
	'COD_ITEM' => "Código do item (campo 02 do Registro 0200)",
	'VL_BC_ICMS' => "Base de cálculo do ICMS ou do ICMS ST ",
	'ALIQ_ICMS' => "Alíquota do ICMS",
	'VL_ICMS' => "Valor do ICMS ou do ICMS ST",
	'VL_OUTROS' => "Outros valores"
);
  $pr->abre_excel_sql("C197", "C197 - OUTRAS OBRIGAÇÕES TRIBUTÁRIAS, AJUSTES E INFORMAÇÕES DE VALORES PROVENIENTES DE DOCUMENTO FISCAL", $sql, $col_format, $cabec, $form_final);
  

  $sql = "
SELECT * FROM C195;
";
  $col_format = array(
	"A:B" => "0"
);
  $cabec = array(
	'OrdC195' => "Número da Linha do Registro C195",
	'OrdC190' => "Número da Linha do Registro C190",
	'COD_OBS' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)",
	'TXT_COMPL' => "Descrição complementar do código de observação."
);
  $pr->abre_excel_sql("C195", "C195 - OBSERVAÇOES DO LANÇAMENTO FISCAL (CÓDIGO 01, 1B E 55)", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM C190;
";
  $col_format = array(
	"A:B" => "0",
	"F:L" => "#.##0,00",
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
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)"
);
  $pr->abre_excel_sql("C190", "C190 - Registro Analítico do Documento (código 01, 1B, 04 e 55)", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT ord, ordC170, cod_mod_ult_e, num_doc_ult_e, ser_ult_e, dt_ult_e, cod_part_ult_e, quant_ult_e, vl_unit_ult_e, vl_unit_bc_st, 
  '#' || chave_nfe_ult_e, num_item_ult_e, 
  vl_unit_bc_icms_ult_e, aliq_icms_ult_e, vl_unit_limite_bc_icms_ult_e,
  vl_unit_icms_ult_e, aliq_st_ult_e, vl_unit_res, 
  cod_resp_ret, cod_mot_res, chave_nfe_ret, cod_part_nfe_ret, ser_nfe_ret, num_nfe_ret, item_nfe_ret, 
  cod_da, num_da FROM C176;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "0",
	"G:G" => "0",
	"H:J" => "#.##0,000",
	"M:O" => "#.##0,00",
	"P:P" => "#.##0,000",
	"Q:Q" => "#.##0,00",
	"R:R" => "#.##0,000"
	);
  $cabec = array(
		'Ord' => "Número da Linha do Registro C176",
		'OrdC170' => "Número da Linha do Registro C170",
		'cod_mod_ult_e' => "Código do modelo do documento fiscal relativa a última entrada",
		'num_doc_ult_e' => "Número do documento fiscal relativa a última entrada",
		'ser_ult_e' => "Série do documento fiscal relativa a última entrada",
		'dt_ult_e' => "Data relativa a última entrada da mercadoria",
		'cod_part_ult_e' => "Código do participante (do emitente do documento relativa a última entrada)",
		'quant_ult_e' => "Quantidade do item relativa a última entrada",
		'vl_unit_ult_e' => "Valor unitário da mercadoria constante na NF relativa a última entrada inclusive despesas acessórias.",
		'vl_unit_bc_st' => "Valor unitário da base de cálculo do imposto pago por substituição.",
		'CHAVE_NFE_ULT_E' => "Número completo da chave da NFe relativo à última entrada",
		'NUM_ITEM_ULT_E' => "Número sequencial do item na NF entrada que corresponde à mercadoria objeto de pedido de ressarcimento",
		'VL_UNIT_BC_ICMS_ULT_E' => "Valor unitário da base de cálculo da operação própria do remetente sob o regime comum de tributação", 
		'ALIQ_ICMS_ULT_E' => "Alíquota do ICMS aplicável à última entrada da mercadoria", 
		'VL_UNIT_LIMITE_BC_ICMS_ULT_E' => "Valor unitário da base de cálculo do ICMS relativo à última entrada da mercadoria, limitado ao valor da BC da retenção (corresponde ao menor valor entre os campos `VL_UNIT_BC_ST´ e `VL_UNIT_BC_ICMS_ULT_E´", 
		'VL_UNIT_ICMS_ULT_E' => "Valor unitário do crédito de ICMS sobre operações próprias do remetente, relativo à última entrada da mercadoria, decorrente da quebra da ST – equivalente a multiplicação entre os campos `ALIQ_ICMS_ULT_E´ e `VL_UNIT_LIMITE_BC_ICMS_ULT_E´",
		'ALIQ_ST_ULT_E' => "Alíquota do ICMS ST relativa à última entrada da mercadoria", 
		'VL_UNIT_RES' => "Valor unitário do ressarcimento (parcial ou completo) de ICMS decorrente da quebra da STN", 
		'COD_RESP_RET' => "Código que indica o responsável pela retenção do ICMS-ST: 
1-Remetente Direto
2-Remetente Indireto
3-Próprio declarante",
		'COD_MOT_RES' => "Código do motivo do ressarcimento
1 – Venda para outra UF;
2 – Saída amparada por isenção ou não incidência;
3 – Perda ou deterioração;
4 – Furto ou roubo
9 - Outros", 
		'CHAVE_NFE_RET' => "Número completo da chave da NF-e emitida pelo substituto, na qual consta o valor do ICMS-ST retido",
		'COD_PART_NFE_RET' => "Código do participante do emitente da NF-e em que houve a retenção do ICMS-ST – campo 02 do registro 0150",
		'SER_NFE_RET' => "Série da NF-e em que houve a retenção do ICMSST",
		'NUM_NFE_RET' => "Número da NF-e em que houve a retenção do ICMS-ST",
		'ITEM_NFE_RET' => "Número sequencial do item na NF-e em que houve a retenção do ICMS-ST, que corresponde à mercadoria objeto de pedido de ressarcimento",
		'COD_DA' => "Código do modelo do documento de arrecadação :
0 - documento estadual de arrecadação
1 – GNREC", 
		'NUM_DA' => "Número do documento de arrecadação estadual,se houver"
);
  $pr->abre_excel_sql("C176", "C176: RESSARCIMENTO DE ICMS EM OPERAÇÕES COM SUBSTITUIÇÃO TRIBUTÁRIA (CÓDIGO 01, 55)", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT ord, ordC170, ind_veic_oper, cnpj, uf, chassi_veic FROM C175;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "0",
	"F:F" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro C175",
		'OrdC170' => "Número da Linha do Registro C170",
		'ind_veic_oper' => "Indicador do tipo de operação com veículo:
0- Venda para concessionária; 
1- Faturamento direto;
2- Venda direta;
3- Venda da concessionária;
9- Outros",
		'CNPJ' => "CNPJ da Concessionária",
		'UF' => "Sigla da unidade da federação da Concessionária",
		'chassi_veic' => "Chassi do veículo"
);
  $pr->abre_excel_sql("C175", "C175: OPERAÇÕES COM VEÍCULOS NOVOS (CÓDIGO 01 e 55)", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT ord, ordC170, ind_arm, num_arm, descr_compl FROM C174;
";
  $col_format = array(
	"A:B" => "0",
	"C:C" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro C174",
		'OrdC170' => "Número da Linha do Registro C170",
		'num_arm' => "Numeração de série de fabricação da arma",
		'descr_compl' => "Descrição da arma, compreendendo: número do cano, calibre, marca, capacidade de cartuchos, tipo de funcionamento, quantidade de canos, comprimento, tipo de alma, quantidade e sentido das raias e demais elementos que permitam sua perfeita identificação"
);
  $pr->abre_excel_sql("C174", "C174: OPERAÇÕES COM ARMAS DE FOGO (CÓDIGO 01)", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT ord, ordC170, lote_med, qtd_item, dt_fab, dt_val, ind_med, tp_prod, vl_tab_max FROM C173;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "#.##0,000",
	"I:I" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro C173",
		'OrdC170' => "Número da Linha do Registro C170",
		'lote_med' => "Número do lote de fabricação do medicamento",
		'qtd_item' => "Quantidade de item por lote", 
		'dt_fab' => "Data de fabricação do medicamento",
		'dt_val' => "Data de expiração da validade do medicamento",
		'ind_med' => "Indicador de tipo de referência da base de cálculo do ICMS (ST) do produto farmacêutico:
0- Base de cálculo referente ao preço tabelado ou preço máximo sugerido; 
1- Base cálculo – Margem de valor agregado; 
2- Base de cálculo referente à Lista Negativa; 
3- Base de cálculo referente à Lista Positiva; 
4- Base de cálculo referente à Lista Neutra",
		'tp_prod' => "Tipo de produto:
0- Similar;
1- Genérico;
2- Ético ou de marca",
		'vl_tab_max' => "Valor do preço tabelado ou valor do preço máximo"
);
  $pr->abre_excel_sql("C173", "C173: OPERAÇÕES COM MEDICAMENTOS (CÓDIGO 01 e 55)", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT ord, ordC170, num_tanque, qtde FROM C171;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "#.##0,000");
  $cabec = array(
		'Ord' => "Número da Linha do Registro C171",
		'OrdC170' => "Número da Linha do Registro C170",
		'num_tanque' => "Tanque onde foi armazenado o combustível",
		'qtde' => "Quantidade ou volume armazenado"
);
  $pr->abre_excel_sql("C171", "C171: LMC - ARMAZENAMENTO DE COMBUSTÍVEIS (código 01, 55)", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT 
   	ord, ordC100,
	num_item, cod_item, descr_compl, qtd, unid,
	vl_item, vl_desc, ind_mov, cst_icms, cfop, cod_nat,
	vl_bc_icms, aliq_icms, vl_icms, vl_bc_icms_st, aliq_st, vl_icms_st,
	ind_apur, cst_ipi, cod_enq,
	vl_bc_ipi, aliq_ipi, vl_ipi, cst_pis, vl_bc_pis, aliq_pis, quant_bc_pis,
	aliq_pis_r, vl_pis, cst_cofins, vl_bc_cofins, aliq_cofins, quant_bc_cofins,
	aliq_cofins_r, vl_cofins, cod_cta
   FROM C170;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "@",
	"F:F" => "#.##0,000",
	"H:I" => "#.##0,00",
	"N:S" => "#.##0,00",
	"W:Y" => "#.##0,00",
	"AA:AE" => "#.##0,00",
	"AG:AK" => "#.##0,00"
);
  $cabec = array(
	'OrdC170' => "Número da Linha do Registro C170",
	'OrdC100' => "Número da Linha do Registro C100",
	'num_item' => "Número Sequencial do Item no Documento Fiscal",
	'cod_item' => "Código do item (campo 02 do Registro 0200)",
	'descr_compl' => "Descrição complementar do item como adotado no documento fiscal",
	'qtd' => "Quantidade do item",
	'unid' => "Unidade do item (Campo 02 do registro 0190)",
	'vl_item' => "Valor total do item (mercadorias ou serviços)",
	'vl_desc' => "Valor do desconto comercial",
	'ind_mov' => "Movimentação física do ITEM/PRODUTO: 0. SIM 1. NÃO",
	'cst_icms' => "Código da Situação Tributária referente ao ICMS, conforme a Tabela indicada no item 4.3.1",
	'CFOP' => "Código Fiscal de Operação e Prestação",
	'cod_nat' => "Código da natureza da operação (campo 02 do Registro 0400)",
	'vl_bc_icms' => "Valor da base de cálculo do ICMS",
	'aliq_icms' => "Alíquota do ICMS",
	'vl_icms' => "Valor do ICMS creditado/debitado",
	'vl_bc_icms_st' => "Valor da base de cálculo referente à substituição tributária",
	'aliq_st' => "Alíquota do ICMS da substituição tributária na unidade da federação de destino",
	'vl_icms_st' => "Valor do ICMS referente à substituição tributária",
	'ind_apur' => "Indicador de período de apuração do IPI: 0 - Mensal; 1 - Decendial",
	'cst_ipi' => "Código da Situação Tributária referente ao IPI, conforme a Tabela indicada no item 4.3.2.",
	'cod_enq' => "Código de enquadramento legal do IPI, conforme tabela indicada no item 4.5.3.",
	'vl_bc_ipi' => "Valor da base de cálculo do IPI",
	'aliq_ipi' => "Alíquota do IPI",
	'vl_ipi' => "Valor do IPI creditado/debitado",
	'cst_pis' => "Código da Situação Tributária referente ao PIS.",
	'vl_bc_pis' => "Valor da base de cálculo do PIS",
	'aliq_pis' => "Alíquota do PIS (em percentual)",
	'quant_bc_pis' => "Quantidade – Base de cálculo PIS",
	'aliq_pis_r' => "Alíquota do PIS (em reais)",
	'vl_pis' => "Valor do PIS",
	'cst_cofins' => "Código da Situação Tributária referente ao COFINS.",
	'vl_bc_cofins' => "Valor da base de cálculo da COFINS",
	'aliq_cofins' => "Alíquota do COFINS (em percentual)",
	'quant_bc_cofins' => "Quantidade – Base de cálculo COFINS",
	'aliq_cofins_r' => "Alíquota da COFINS (em reais)",
	'vl_cofins' => "Valor da COFINS",
	'cod_cta' => "Código da conta analítica contábil debitada/creditada"
);
  $pr->abre_excel_sql("C170", "C170 - ITENS DO DOCUMENTO", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM C141;
";
  $col_format = array(
	"A:B" => "0",
	"E:E" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro C141",
		'OrdC140' => "Número da Linha do Registro C140",
		'num_parc' => "Número da parcela a receber/pagar",
		'dt_vcto' => "Data de vencimento da parcela",
		'vl_parc' => "Valor da parcela a receber/pagar"
);
  $pr->abre_excel_sql("C141", "C141 - VENCIMENTO DA FATURA (CÓDIGO 01)", $sql, $col_format, $cabec, $form_final);
  
  
  $sql = "
SELECT * FROM C140;
";
  $col_format = array(
	"A:B" => "0",
	"H:H" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro C140",
	'OrdC100' => "Número da Linha do Registro C100",
	'ind_emit' => "Indicador do emitente do título:
0- Emissão própria;
1- Terceiros",
	'IND_TIT' => "Indicador do tipo de título de crédito:
00- Duplicata;
01- Cheque;
02- Promissória;
03- Recibo;
99- Outros (descrever)",
	'desc_tit' => "Descrição complementar do título de crédito",
	'num_tit' => "Número ou código identificador do título de crédito",
	'qtd_parc' => "Quantidade de parcelas a receber/pagar",
	'vl_tit' => "Valor total dos títulos de créditos"
);
  $pr->abre_excel_sql("C140", "C140 - FATURA (CÓDIGO 01)", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM C120;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "0",
	"E:F" => "#.##0,00",
	"G:G" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro C120",
		'OrdC100' => "Número da Linha do Registro C100",
		'COD_DOC_IMP' => "Documento de importação:
0 - Declaração de Importação;
1 - Declaração Simplificada de Importação.",
		'NUM_DOC__IMP' => "Número do documento de Importação.",
		'PIS_IMP' => "Valor pago de PIS na importação",
		'COFINS IMP' => "Valor pago de COFINS na importação",
		'NUM_ACDRAW' => "Número do Ato Concessório do regime Drawback"
);
  $pr->abre_excel_sql("C120", "C120 - COMPLEMENTO DE DOCUMENTO - OPERAÇÕES DE IMPORTAÇÃO (CÓDIGOS 01 e 55)", $sql, $col_format, $cabec, $form_final);
  


  
  $sql = "
SELECT * FROM C114;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro C141",
		'OrdC110' => "Número da Linha do Registro C110",
		'COD_MOD' => "Código do modelo do documento fiscal, conforme a tabela indicada no item 4.1.1",
		'ECF_FAB' => "Número de série de fabricação do ECF",
		'ECF_CX' => "Número do caixa atribuído ao ECF",
		'num_doc' => "Número do documento fiscal",
		'dt_doc' => "Data da emissão do documento fiscal."
);
  $pr->abre_excel_sql("C114", "C114 - Cupom Fiscal Referenciado", $sql, $col_format, $cabec, $form_final);
  

  
  $sql = "
SELECT * FROM C113;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro C141",
		'OrdC110' => "Número da Linha do Registro C110",
		'ind_oper' => "Indicador do tipo de operação:
0- Entrada/aquisição;
1- Saída/prestação",
		'ind_emit' => "Indicador do emitente do título:
0- Emissão própria;
1- Terceiros",
		'cod_part' => "Código do participante emitente (campo 02 do Registro 0150)  do documento referenciado.",
		'cod_mod' => "Código do documento fiscal, conforme a Tabela 4.1.1",
		'ser' => "Série do documento fiscal",
		'sub' => "Subsérie do documento fiscal",
		'num_doc' => "Número do documento fiscal",
		'dt_doc' => "Data da emissão do documento fiscal."
);
  $pr->abre_excel_sql("C113", "C113 - Complemento de Documento - Documento Fiscal Referenciado", $sql, $col_format, $cabec, $form_final);
  

  $sql = "
SELECT * FROM C110;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro C110",
		'OrdC100' => "Número da Linha do Registro C100",
		'cod_inf' => "Código da informação complementar do documento fiscal (campo 02 do Registro 0450)", 
		'txt_compl' => "Descrição complementar do código de referência."
);
  $pr->abre_excel_sql("C110", "C110 - Complemento de Documento - Informação Complementar da Nota Fiscal (código 01, 1B, 55)", $sql, $col_format, $cabec, $form_final);
  
  $sql = "
SELECT * FROM C101;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro C101",
		'OrdC100' => "Número da Linha do Registro C100",
		'vl_fcp_uf_dest' => "valor total relativo ao fundo de combate à pobreza (fcp) da uf de destino",
		'vl_icms_uf_dest' => "valor total do icms interestadual para a uf de destino",
		'vl_icms_uf_rem' => "valor total do icms interestadual para a uf do remetente"
);
  $pr->abre_excel_sql("C101", "C101 - INF.COMPL.DOS DOCS FISCAIS QD DAS OPER INTERESTADUAIS DEST A CONS FINAL NÃO CONTRIBUINTE EC 87/15)", $sql, $col_format, $cabec, $form_final);
  

  $sql = "
SELECT 
  	  ord,
	  ind_oper, ind_emit, cod_part, cod_mod, cod_sit,
	  ser, num_doc, '#' || chv_nfe AS chv_nfe, dt_doc, dt_e_s, vl_doc,
	  ind_pgto, vl_desc, vl_abat_nt, vl_merc,
	  ind_frt, vl_frt, vl_seg, vl_out_da,
	  vl_bc_icms, vl_icms, vl_bc_icms_st, vl_icms_st, vl_ipi,
	  vl_pis, vl_cofins, vl_pis_st, vl_cofins_st
	  FROM C100;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "0",
	"D:D" => "0",
	"L:L" => "#.##0,00",
	"O:P" => "#.##0,00",
	"R:AC" => "#.##0,00");
  $cabec = array(
	'OrdC100' => "Número da Linha do Registro C100",
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
	'vl_bc_icms' => "Valor da base de cálculo do ICMS",
	'vl_icms' => "Valor do ICMS",
	'vl_bc_icms_st' => "Valor da base de cálculo do ICMS substituição tributária",
	'vl_icms_st' => "Valor do ICMS retido por substituição tributária",
	'vl_ipi' => "Valor total do IPI",
	'vl_pis' => "Valor total do PIS",
	'vl_cofins' => "Valor total da COFINS",
	'vl_pis_st' => "Valor total do PIS retido por substituição tributária",
	'vl_cofins_st' => "Valor total da COFINS retido por substituição tributária"
);
  $pr->abre_excel_sql("C100", "C100 - NOTA FISCAL", $sql, $col_format, $cabec, $form_final);
  

  $pr->finaliza_excel();

  
  
  wecho("\n\nTarefa 2 de 2: Gerando EFD_Dados_do_EFD_Blocos_0_E_G_H");
  $pr->inicia_excel('EFD_Dados_do_EFD_Blocos_0_E_G_H');

  $sql = "
SELECT * FROM K200;
";
  $col_format = array(
	"A:A" => "0",
	"C:C" => "0",
	"D:D" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro",
		'OrdK100' => "Número da Linha do Registro K100",
		'dt_est' => "Data do estoque final",
		'cod_item' => "Código do item (campo 02 do Registro 0200)",
		'qtd' => "Quantidade em estoque",
		'ind_est' => "Indicador do tipo de estoque:
0 = Estoque de propriedade do informante e em seu poder;
1 = Estoque de propriedade do informante e em posse de terceiros;
2 = Estoque de propriedade de terceiros e em posse do informante",
		'cod_part' => "Código do participante (campo 02 do Registro 0150):
- proprietário/possuidor que não seja o informante do arquivo"
);
  $pr->abre_excel_sql("K200", "K200 - ESTOQUE ESCRITURADO", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM K100;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro",
		'dt_ini' => "Data inicial a que a apuração se refere",
		'dt_fin' => "Data final a que a apuração se refere"
);
  $pr->abre_excel_sql("K100", "K100 - PERÍODO DE APURAÇÃO DO ICMS/IPI", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM H010;
";
  $col_format = array(
	"A:B" => "0",
	"E:G" => "#.##0,00",
	"K:K" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro",
		'OrdH005' => "Número da Linha do Registro H005",
		'cod_item' => "Código do item (campo 02 do Registro 0200)",
		'unid' => "Unidade do item",
		'qtd' => "Quantidade do item",
		'vl_unit' => "Valor unitário do item",
		'vl_item' => "Valor do item",
		'ind_prop' => "Indicador de propriedade/posse do item:
0- Item de propriedade do informante e em seu poder;
1- Item de propriedade do informante em posse de terceiros;
2- Item de propriedade de terceiros em posse do informante",
		'cod_part' => "Código do participante (campo 02 do Registro 0150):
- proprietário/possuidor que não seja o informante do arquivo",
		'txt_compl' => "Descrição complementar",
		'cod_cta' => "Código da conta analítica contábil debitada/creditada",
		'vl_item_ir' => "Valor do item para efeitos do Imposto de Renda"
);
  $pr->abre_excel_sql("H010", "H010 - Inventário", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM H005;
";
  $col_format = array(
	"A:A" => "0",
	"C:C" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro",
		'dt_inv' => "Data do Inventário",
		'vl_inv' => "Valor Total do Inventário",
		'mot_inv' => "Motivo do Inventário:
01 – No final no período;
02 – Na mudança de forma de tributação da mercadoria (ICMS);
03 – Na solicitação da baixa cadastral, paralisação temporária e outras situações;
04 – Na alteração de regime de pagamento – condição do contribuinte;
05 – Por determinação dos fiscos
"
);
  $pr->abre_excel_sql("H005", "H005 - Totais do Inventário", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM G140;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro",
	'OrdG130' => "Número da Linha do Registro G130",
	'num_item' => "Número sequencial do item no documento fiscal",
	'cod_item' => "Código correspondente do bem no documento fiscal (campo 02 do registro 0200)"
);
  $pr->abre_excel_sql("G140", "G140 - IDENTIFICAÇÃO DO ITEM DO DOCUMENTO FISCAL", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM G130;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro",
	'OrdG125' => "Número da Linha do Registro G125",
	'ind_emit' => "Indicador do emitente do documento fiscal:
0- Emissão própria;
1- Terceiros",
	'cod_part' => "Código do participante :
- do emitente do documento ou do remetente das mercadorias, no caso de entradas;
- do adquirente, no caso de saídas",
	'cod_mod' => "Código do modelo de documento fiscal, conforme tabela 4.1.1",
	'serie' => "Série do documento fiscal", 
	'num_doc' => "Número de documento fiscal", 
	'chv_nfe_cte' => "Chave do documento fiscal eletrônico",
	'dt_doc' => "Data da emissão do documento fiscal"
);
  $pr->abre_excel_sql("G130", "G130 - IDENTIFICAÇÃO DO DOCUMENTO FISCAL", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM G126;
";
  $col_format = array(
	"A:B" => "0",
	"F:J" => "#.##0,00",
	"I:I" => "#.##0,00000000");
  $cabec = array(
	'Ord' => "Número da Linha do Registro",
	'OrdG125' => "Número da Linha do Registro G125",
	'dt_ini' => "Data inicial do período de apuração", 
	'dt_fim' => "Data final do período de apuração",
	'num_parc' => "Número da parcela do ICMS",
	'vl_parc_pass' => "Valor da parcela de ICMS passível de apropriação - antes da aplicação da participação percentual do valor das saídas tributadas/exportação sobre as saídas totais", 
	'vl_trib_oc' => "Valor do somatório das saídas tributadas e saídas para exportação no período indicado neste registro", 
	'vl_total' => "Valor total de saídas no período indicado neste registro",
	'ind_per_sai' => "Índice de participação do valor do somatório das saídas tributadas e saídas para exportação no valor total de saídas (Campo 06 dividido pelo campo 07)",
	'vl_parc_aprop' => "Valor de outros créditos de ICMS a ser apropriado na apuração (campo 05 vezes o campo 08)"
);
  $pr->abre_excel_sql("G126", "G126 - OUTROS CRÉDITOS CIAP", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM G125;
";
  $col_format = array(
	"A:B" => "0",
	"F:I" => "#.##0,00",
	"K:K" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro",
	'OrdG110' => "Número da Linha do Registro G110",
	'cod_ind_bem' => "Código individualizado do bem ou componente adotado no controle patrimonial do estabelecimento informante",
	'dt_mov' => "Data da movimentação ou do saldo inicial", 
	'tipo_mov' => "Tipo de movimentação do bem ou componente:
SI = Saldo inicial de bens imobilizados;
IM = Imobilização de bem individual;
IA = Imobilização em Andamento - Componente;
CI = Conclusão de Imobilização em Andamento – Bem Resultante;
MC = Imobilização oriunda do Ativo Circulante;
BA = Baixa do bem - Fim do período de apropriação;
AT = Alienação ou Transferência;
PE = Perecimento, Extravio ou Deterioração;
OT = Outras Saídas do Imobilizado",
	'vl_imob_icms' => "Valor do ICMS da Operação Própria na entrada do bem ou componente",
	'vl_imob_icms_st' => "Valor do ICMS da Oper. por Sub. Tributária na entrada do bem ou componente",
	'vl_imob_icms_frt' => "Valor do ICMS sobre Frete do Conhecimento de Transporte na entrada do bem ou componente",
	'vl_imob_icms_dif' => "Valor do ICMS - Diferencial de Alíquota, conforme Doc. de Arrecadação, na entrada do bem ou componente",
	'num_parc' => "Número da parcela do ICMS",
	'vl_parc_pass' => "Valor da parcela de ICMS passível de apropriação (antes da aplicação da participação percentual do valor das saídas tributadas/exportação sobre as saídas totais)"
);
  $pr->abre_excel_sql("G125", "G125 - MOVIMENTAÇÃO DE BEM OU COMPONENTE DO ATIVO IMOBILIZADO", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM G110;
";
  $col_format = array(
	"A:A" => "0",
	"D:J" => "#.##0,00",
	"H:H" => "#.##0,00000000");
  $cabec = array(
	'Ord' => "Número da Linha do Registro",
	'dt_ini' => "Data inicial a que a apuração se refere",
	'dt_fin' => "Data final a que a apuração se refere",
	'saldo_in_icms' => "Saldo inicial de ICMS do CIAP, composto por ICMS de bens que entraram anteriormente ao período de apuração (somatório dos campos 05 a 08 dos registros G125)",
	'som_parc' => "Somatório das parcelas de ICMS passível de apropriação de cada bem (campo 10 do G125)",
	'vl_trib_exp' => "Valor do somatório das saídas tributadas e saídas para exportação",
	'vl_total' => "Valor total de saídas",
	'ind_per_sai' => "Índice de participação do valor do somatório das saídas tributadas e saídas para exportação no valor total de saídas (Campo 06 dividido pelo campo 07)",
	'icms_aprop' => "Valor de ICMS a ser apropriado na apuração do ICMS, correspondente á multiplicação do campo 05 pelo campo 08.",
	'som_icms_oc' => "Valor de outros créditos a ser apropriado na Apuração do ICMS, correspondente ao somatório do campo 09 do registro G126."
);
  $pr->abre_excel_sql("G110", "G110 - ATIVO PERMANENTE – CIAP", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM E316;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E316",
		'OrdE310' => "Número da Linha do Registro E310",
		'cod_or' => "Código da obrigação recolhida ou a recolher, conforme a Tabela 5.4",
		'vl_or' => "Valor da obrigação recolhida ou a recolher",
		'dt_vcto' => "Data de vencimento da obrigação",
		'cod_rec' => "Código de receita referente à obrigação, próprio da unidade da federação da origem/destino, conforme legislação estadual.",
		'num_proc' => "Número do processo ou auto de infração ao qual a obrigação está vinculada, se houver",
		'ind_proc' => "Indicador da origem do processo:
0- SEFAZ;
1- Justiça Federal;
2- Justiça Estadual;
9- Outros",
		'proc' => "Descrição resumida do processo que embasou o lançamento",
		'txt_compl' => "Descrição complementar das obrigações recolhidas ou a recolher",
		'mes_ref' => "Informe o mês de referência no formato mmaaaa"
);
  $pr->abre_excel_sql("E316", "E316 - Obrigações recolhidas ou a recolher - fundo de Combate à pobreza e icms diferencial de alíquota uf origem/destino Ec 87/15", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT *  FROM E313;
";
  $col_format = array(
	"A:C" => "0",
	"K:K" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E313",
		'OrdE311' => "Número da Linha do Registro E311",
		'cod_part' => "Código do participante (campo 02 do Registro 0150)",
		'cod_mod' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1",
		'ser' => "Série do documento fiscal",
		'sub' => "Subsérie do documento fiscal",
		'num_doc' => "Número do documento fiscal",
		'CHV_DOCe' => "Chave do Documento Eletrônico",
		'dt_doc' => "Data da emissão do documento fiscal",
		'cod_item' => "Código do item (campo 02 do Registro 0200)",
		'vl_aj_item' => "Valor do ajuste para a operação/item"
);
  $pr->abre_excel_sql("E313", "E313 - Informações adicionais dos ajustes da apuração do Fundo de combate à pobreza e do icms diferencial de alíquota uf Origem/destino ec 87/15 - identificação dos documentos fiscais", $sql, $col_format, $cabec, $form_final);
  
  $sql = "
SELECT * FROM E312;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E312",
		'OrdE311' => "Número da Linha do Registro E311",
		'num_da' => "Número do documento de arrecadação estadual, se houver",
		'num_proc' => "Número do processo ao qual o ajuste está vinculado, se houver",
		'ind_proc' => "Indicador da origem do processo:
0- Sefaz;
1- Justiça Federal;
2- Justiça Estadual;
9- Outros",
		'proc' => "Descrição resumida do processo que embasou o lançamento",
		'txt_compl' => "Descrição complementar"
);
  $pr->abre_excel_sql("E312", "E312 - Informações adicionais dos ajustes da apuração do Fundo de combate à pobreza e do icms diferencial de alíquota uf Origem/destino ec 87/15", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM E311;
";
  $col_format = array(
	"A:B" => "0",
	"E:E" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E311",
		'OrdE310' => "Número da Linha do Registro E310",
		'cod_aj_apur' => "Código do ajuste da apuração e dedução, conforme a Tabela indicada no item 5.1.1.",
		'descr_compl_aj' => "Descrição complementar do ajuste da apuração.",
		'vl_aj_apur' => "Valor do ajuste da apuração"
);
  $pr->abre_excel_sql("E311", "E311 - Ajuste/Benefício/Incentivo da apuração do fundo de combate à pobreza e do icms diferencial de alíquota uf origem/destino ec 87/15", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM E310;
";
  $col_format = array(
	"A:A" => "0",
	"C:W" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E310",
		'OrdE300' => "Número da Linha do Registro E300",
		'ind_mov_fcp_difal' => "Indicador de movimento:
0 – Sem operações
1 – Com operações",
		'vl_sld_cred_ant_dif' => "Valor do Saldo credor de período anterior – ICMS Diferencial de Alíquota da UF de Origem/Destino",
		'vl_tot_debitos_difal' => "Valor total dos débitos por Saídas e prestações com débito do ICMS referente ao diferencial de alíquota devido à UF de Origem/Destino",
		'vl_out_deb_difal' => "Valor total dos ajustes Outros débitos ICMS Diferencial de Alíquota da UF de Origem Destino e  Estorno de créditos ICMS Diferencial de Alíquota da UF de
Origem/Destino",
		'vl_tot_creditos_difal' => "Valor total dos créditos do ICMS referente ao diferencial de alíquota devido à UF de Origem/Destino",
		'vl_out_cred_difal' => "Valor total de Ajustes Outros créditos ICMS Diferencial de Alíquota da UF de Origem/Destino e Estorno de débitos ICMS Diferencial de Alíquota da UF de
Origem/Destino",
		'vl_sld_dev_ant_difal' => "Valor total de Saldo devedor ICMS Diferencial de Alíquota da UF de Origem/Destino antes das deduções",
		'vl_deduções_difal' => "Valor total dos ajustes Deduções ICMS Diferencial de Alíquota da UF de Origem/Destino",
		'vl_recol_difal' => "Valor recolhido ou a recolher referente ao ICMS Diferencial de Alíquota da UF de Origem/Destino (08-09)",
		'vl_sld_cred_transportar_difal' => "Saldo credor a transportar para o período seguinte referente ao ICMS Diferencial de Alíquota da UF de Origem/Destino",
		'deb_esp_difal' => "Valores recolhidos ou a recolher, extraapuração - ICMS Diferencial de Alíquota da UF de Origem/Destino.",
		'vl_sld_cred_ant_fcp' => "Valor do Saldo credor de período anterior – FCP",
		'vl_tot_deb_fcp' => "Valor total dos débitos FCP por Saídas e prestações",
		'vl_out_deb_fcp' => "Valor total dos ajustes Outros débitos FCP e Estorno de créditos FCP",
		'vl_tot_cred_fcp' => "Valor total dos créditos FCP por Entradas",
		'vl_out_cred_fcp' => "Valor total de Ajustes Outros créditos FCP e Estorno de débitos FCP",
		'vl_sld_dev_ant_fcp' => "Valor total de Saldo devedor FCP antes das deduções",
		'vl_deduções_fcp' => "Valor total das deduções FCP",
		'vl_recol_fcp' => "Valor recolhido ou a recolher referente ao FCP (18–19)",
		'vl_sld_cred_transportar_fcp' => "Saldo credor a transportar para o período seguinte referente ao FCP",
		'deb_esp_fcp' => "Valores recolhidos ou a recolher, extraapuração - FCP."		
);
  $pr->abre_excel_sql("E310", "E310 - Apuração do fundo de combate à pobreza e do icms - Diferencial de alíquota uf origem/destino ec 87/15", $sql, $col_format, $cabec, $form_final);
  

  $sql = "
SELECT * FROM E300;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E300",
		'uf' => "Sigla da unidade da federação a que se refere à apuração do FCP e do ICMS Diferencial de Alíquota da UF de Origem/Destino",
		'dt_ini' => "Data Inicial a que a apuração se refere",
		'dt_fin' => "Data Final a que a apuração se refere"
);
  $pr->abre_excel_sql("E300", "E300 - Período De Apuração Do Fundo De Combate À Pobreza E Do Icms Diferencial De Alíquota Uf Origem/Destino Ec 87/15", $sql, $col_format, $cabec, $form_final);
  
  
  
  $sql = "
SELECT * FROM E250;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E250",
		'OrdE210' => "Número da Linha do Registro E210",
		'cod_or' => "Código da obrigação a recolher, conforme a Tabela 5.4",
		'vl_or' => "Valor da obrigação  ICMS ST a recolher", 
		'dt_vcto' => "Data de vencimento da obrigação",
		'cod_rec' => "Código de receita referente à obrigação, próprio da unidade da federação, conforme legislação estadual.",
		'num_proc' => "Número do processo ou auto de infração ao qual a obrigação está vinculada, se houver.",
		'ind_proc' => "Indicador da origem do processo:
0- Sefaz;
1- Justiça Federal;
2- Justiça Estadual;
9- Outros",
		'proc' => "Descrição resumida do processo que embasou o lançamento",
		'txt_compl' => "Descrição complementar das obrigações a recolher.",
		'mes_ref' => "Informe o mês de referência no formato 'mmaaaa'
(Acrescido o campo 10 - MÊS_REF pelo Ato COTEPE/ICMS 47/09, efeitos a partir de 01.07.10)"
);
  $pr->abre_excel_sql("E250", "E250 - Obrigações do ICMS a Recolher - Substituição Tributária", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM E210;
";
  $col_format = array(
	"A:A" => "0",
	"C:P" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E210",
		'OrdE200' => "Número da Linha do Registro E200",
		'ind_mov_st' => "Indicador de movimento:
0 - Sem operações com ST
1 - Com operações de ST",
		'vl_sld_cred_ant_st' => "Valor do 'Saldo credor de período anterior - Substituição Tributária'",
		'vl_devol_st' => "Valor total do ICMS ST de devolução de mercadorias",
		'vl_ressarc_st' => "Valor total do ICMS ST de ressarcimentos",
		'vl_out_cred_st' => "Valor total de Ajustes 'Outros créditos ST' e 'Estorno de débitos ST'",
		'vl_aj_creditos_st' => "Valor total dos ajustes a crédito de ICMS ST, provenientes de ajustes do documento fiscal.",
		'vl_retençao_st' => "Valor Total do ICMS retido por Substituição Tributária",
		'vl_out_deb_st' => "Valor Total dos ajustes 'Outros débitos ST' e 'Estorno de créditos ST'",
		'vl_aj_debitos_st' => "Valor total dos ajustes a débito de ICMS ST, provenientes de ajustes do documento fiscal.",
		'vl_sld_dev_ant_st' => "Valor total de Saldo devedor antes das deduções",
		'vl_deduções_st' => "Valor total dos ajustes 'Deduções ST'",
		'vl_icms_recol_st' => "Imposto a recolher ST (11-12)",
		'vl_sld_cred_st_transportar' => "Saldo credor de ST a transportar para o período seguinte [(03+04+05+06+07)- (08+09+10)].",
		'deb_esp_st' => "Valores recolhidos ou a recolher, extra-apuração."
);
  $pr->abre_excel_sql("E210", "E210 - apuração do icms – Substituição Tributária", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM E200;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E200",
		'uf' => "UF",
		'dt_ini' => "Data Inicial a que a apuração se refere",
		'dt_fin' => "Data Final a que a apuração se refere"
);
  $pr->abre_excel_sql("E200", "E200 - período da apuração do icms - Substituição Tributária", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM E116;
";
  $col_format = array(
	"A:B" => "0",
	"D:D" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E116",
		'OrdE110' => "Número da Linha do Registro E110",
		'cod_or' => "Código da obrigação a recolher, conforme a Tabela 5.4",
		'vl_or' => "Valor da obrigação a recolher", 
		'dt_vcto' => "Data de vencimento da obrigação",
		'cod_rec' => "Código de receita referente à obrigação, próprio da unidade da federação, conforme legislação estadual.",
		'num_proc' => "Número do processo ou auto de infração ao qual a obrigação está vinculada, se houver.",
		'ind_proc' => "Indicador da origem do processo:
0- Sefaz;
1- Justiça Federal;
2- Justiça Estadual;
9- Outros",
		'proc' => "Descrição resumida do processo que embasou o lançamento",
		'txt_compl' => "Descrição complementar das obrigações a recolher.",
		'mes_ref' => "Informe o mês de referência no formato 'mmaaaa'
(Acrescido o campo 10 - MÊS_REF pelo Ato COTEPE/ICMS 47/09, efeitos a partir de 01.07.10)"
);
  $pr->abre_excel_sql("E116", "E116 - Obrigações do ICMS a Recolher - Obrigações Próprias", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM E113;
";
  $col_format = array(
	"A:B" => "0",
	"J:J" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E113",
		'OrdE111' => "Número da Linha do Registro E111",
		'COD_PART' => "Código do participante (campo 02 do Registro 0150):
- do emitente do documento ou do remetente das mercadorias, no caso de entradas;
- do adquirente, no caso de saídas",
		'COD_MOD' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1",
		'SER' => "Série do documento fiscal",
		'SUB' => "Subsérie do documento fiscal",
		'NUM_DOC' => "Número do documento fiscal",
		'DT_DOC' => "Data da emissão do documento fiscal",
		'COD_ITEM' => "Código do item (campo 02 do Registro 0200)",
		'VL_AJ_ITEM' => "Valor do ajuste para a operação/item",
		'CHV_DOCe' => "Chave do Documento Eletrônico"
);
  $pr->abre_excel_sql("E113", "E113 - INFORMAÇÕES ADICIONAIS DOS AJUSTES DA APURAÇÃO DO ICMS IDENTIFICAÇÃO DOS DOCUMENTOS FISCAIS", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM E112;
";
  $col_format = array(
	"A:F" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E112",
		'OrdE111' => "Número da Linha do Registro E111",
		'NUM_DA' => "Número do documento de arrecadação estadual, se houver", 
		'NUM_PROC' => "Número do processo ao qual o ajuste está vinculado, se houver", 
		'IND_PROC' => "Indicador da origem do processo:
0- Sefaz;
1- Justiça Federal;
2- Justiça Estadual;
9- Outros", 
		'PROC' => "Descrição resumida do processo que embasou o lançamento", 
		'TXT_COMPL' => "Descrição complementar"
);
  $pr->abre_excel_sql("E112", "E112 - INFORMAÇÕES ADICIONAIS DOS AJUSTES DA APURAÇÃO DO ICMS", $sql, $col_format, $cabec, $form_final);



  $sql = "
SELECT * FROM E111;
";
  $col_format = array(
	"A:B" => "0",
	"E:E" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E111",
		'OrdE110' => "Número da Linha do Registro E110",
		'cod_aj_apur' => "Código do ajuste da apuração e dedução, conforme a Tabela indicada no item 5.1.1.",
		'descr_compl_aj' => "Descrição complementar do ajuste da apuração.",
		'vl_aj_apur' => "Valor do ajuste da apuração"
);
  $pr->abre_excel_sql("E111", "E111 - Ajuste/Benefício/Incentivo da Apuração do ICMS", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM E110;
";
  $col_format = array(
	"A:B" => "0",
	"C:P" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E110",
		'OrdE100' => "Número da Linha do Registro E100",
		'tot_debitos' => "Valor total dos débitos por Saídas e prestações com débito do imposto",
		'vl_aj_debitos' => "Valor total dos ajustes a débito decorrentes do documento fiscal.",
		'vl_tot_aj_debitos' => "Valor total de Ajustes a débito",
		'vl_estornos_cred' => "Valor total de Ajustes Estornos de créditos",
		'vl_tot_creditos' => "Valor total dos créditos por Entradas e aquisições com crédito do imposto",
		'vl_aj_creditos' => "Valor total dos ajustes a crédito decorrentes do documento fiscal.",
		'vl_tot_aj_creditos' => "Valor total de Ajustes a crédito",
		'vl_estornos_deb' => "Valor total de Ajustes Estornos de Débitos",
		'vl_sld_credor_ant' => "Valor total de Saldo credor do período anterior",
		'vl_sld_apurado' => "Valor do saldo devedor apurado",
		'vl_tot_ded' => "Valor total de Deduções",
		'vl_icms_recolher' => "Valor total de ICMS a recolher (11-12)",
		'vl_sld_credor_transportar' => "Valor total de Saldo credor a transportar para o período seguinte",
		'deb_esp' => "Valores recolhidos ou a recolher, extra-apuração."
);
  $pr->abre_excel_sql("E110", "E110 - apuração do icms – operações próprias", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM E100;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro E100",
		'dt_ini' => "Data Inicial a que a apuração se refere",
		'dt_fin' => "Data Final a que a apuração se refere"
);
  $pr->abre_excel_sql("E100", "E100 - período da apuração do icms", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM l370;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1300",
	'Ord1350' => "Número da Linha do Registro 1350",
	'NUM_BICO' => "Número sequencial do bico ligado a bomba",
	'COD_ITEM' => "Código do Produto, constante do registro 0200",
	'NUM_TANQUE' => "Tanque que armazena o combustível."
);
  $pr->abre_excel_sql("l370", "1370 - Bicos das Bombas", $sql, $col_format, $cabec, $form_final);

    
  $sql = "
SELECT * FROM l360;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1300",
	'Ord1350' => "Número da Linha do Registro 1350",
	'NUM_LACRE' => "Número do Lacre associado na Bomba",
	'DT_APLICACAO' => "Data de aplicação do Lacre"
);
  $pr->abre_excel_sql("l360", "1360 - Lacres das Bombas", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM l350;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1300",
	'SERIE' => "Número de Série da Bomba",
	'FABRICANTE' => "Nome do Fabricante da Bomba",
	'MODELO' => "Modelo da Bomba",
	'TIPO_MEDICAO' => "Identificador de medição:
0 - analógico;
1 – digital"
);
  $pr->abre_excel_sql("l350", "1350 - Bombas", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM l320;
";
  $col_format = array(
	"A:B" => "0",
	"I:L" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1320",
	'Ord1310' => "Número da Linha do Registro 1310",
	'NUM_BICO' => "Bico Ligado à Bomba",
	'NR_INTERV' => "Número da intervenção",
	'MOT_INTERV' => "Motivo da Intervenção",
	'NOM_INTERV' => "Nome do Interventor",
	'CNPJ_INTERV' => "CNPJ da empresa responsável pela intervenção",
	'CPF_INTERV' => "CPF do técnico responsável pela intervenção",
	'VAL_FECHA' => "Valor da leitura final do contador, no fechamento do bico",
	'VAL_ABERT' => "Valor da leitura inicial do contador, na abertura do bico",
	'VOL_AFERI' => "Aferições da Bomba, em litros",
	'VOL_VENDAS' => "Vendas (08 – 09 - 10 ) do bico , em litros "
);
  $pr->abre_excel_sql("l320", "1320 - Volume de Vendas", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM l310;
";
  $col_format = array(
	"A:B" => "0",
	"D:K" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1310",
	'Ord1300' => "Número da Linha do Registro 1300",
	'NUM_TANQUE' => "Tanque que armazena o combustível.",
	'ESTQ_ABERT' => "Estoque no inicio do dia, em litros",
	'VOL_ENTR' => "Volume Recebido no dia (em litros)",
	'VOL_DISP' => "Volume Disponível (03 + 04), em litros",
	'VOL_SAIDAS' => "Volume Total das Saídas, em litros",
	'ESTQ_ESCR' => "Estoque Escritural (05 – 06), litros",
	'VAL_AJ_PERDA' => "Valor da Perda, em litros",
	'VAL_AJ_GANHO' => "Valor do ganho, em litros",
	'FECH_FISICO' => "Estoque de Fechamento, em litros"
);
  $pr->abre_excel_sql("l310", "1310 - Movimentação Diária de Combustíveis Por Tanque", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM l300;
";
  $col_format = array(
	"A:A" => "0",
	"D:K" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1300",
	'COD_ITEM' => "Código do Produto, constante do registro 0200",
	'DT_FECH' => "Data do fechamento da movimentação",
	'ESTQ_ABERT' => "Estoque no início do dia, em litros",
	'VOL_ENTR' => "Volume Recebido no dia (em litros)",
	'VOL_DISP' => "Volume Disponível (04 + 05), em litros",
	'VOL_SAIDAS' => "Volume Total das Saídas, em litros",
	'ESTQ_ESCR' => "Estoque Escritural (06 – 07), litros",
	'VAL_AJ_PERDA' => "Valor da Perda, em litros",
	'VAL_AJ_GANHO' => "Valor do ganho, em litros",
	'FECH_FISICO' => "Estoque de Fechamento, em litros"
);
  $pr->abre_excel_sql("l300", "1300 - Movimentação Diária de Combustíveis", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT ord, tipo_util, nr_doc, vl_cred_util, '#' || chv_doce FROM l210;
";
  $col_format = array(
	"A:A" => "0",
	"D:D" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1210",
	'TIPO_UTIL' => "Tipo de utilização do crédito, conforme tabela indicada no item 5.5, abaixo reproduzida:
Tabela 5.5 - Tabela de Tipos de Utilização dos Créditos Fiscais - Utilização obrigatória a partir de 01-01-2017.
Código - Descrição
SP01 - Compensação Escritural - conjuntamente com a apuração relativa às operações submetidas ao regime comum de tributação, mediante lançamento no livro Registro de Apuração do ICMS (inciso I do artigo 270 do RICMS/00).
SP02 - Nota Fiscal de Ressarcimento - quando a mercadoria tiver sido recebida diretamente do estabelecimento do sujeito passivo por substituição, mediante emissão de documento fiscal, que deverá ser previamente visado pela repartição fiscal, indicando como destinatário o referido estabelecimento e como valor da operação aquele a ser ressarcido (inciso II do artigo 270 do RICMS/00).
SP03 - Pedido de Ressarcimento - mediante requerimento à Secretaria da Fazenda (inciso III do artigo 270 do RICMS/00).
SP04 - Liquidação de débito fiscal do estabelecimento ou de outro do mesmo titular (§ 2º do artigo 270 do RICMS/00).",
	'NR_DOC' => "Número do documento utilizado na baixa de créditos",
	'VL_CRED_UTIL' => "Total de crédito utilizado",
	'CHV_DOCe' =>  "Chave do Documento Eletrônico"
);
  $pr->abre_excel_sql("l210", "1210 - Utilização de Créditos Fiscais - ICMS", $sql, $col_format, $cabec, $form_final);

 
  $sql = "
SELECT * FROM l200;
";
  $col_format = array(
	"A:A" => "0",
	"C:G" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1200",
	'COD_AJ_APUR' => "Código de ajuste, conforme informado na Tabela indicada no item 5.1.1
(Sempre será o código SP099719, conforme Art 6º, inc I, da Port Cat 158/2015)",
	'SLD_CRED' => "Saldo de créditos fiscais de períodos anteriores",
	'CRED_APR' => "Total de crédito apropriado no mês",
	'CRED_RECEB' => "Total de créditos recebidos por transferência",
	'CRED_UTIL' => "Total de créditos utilizados no período",
	'SLD_CRED_FIM' =>  "Saldo de crédito fiscal acumulado a transportar para o período seguinte"
);
  $pr->abre_excel_sql("l200", "1200 - Controle de Créditos Fiscais - ICMS", $sql, $col_format, $cabec, $form_final);
  
  $sql = "
SELECT * FROM l105;
";
  $col_format = array(
	"A:A" => "0",
	"H:H" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1105",
	'Ordl100' => "Número da Linha do Registro 1100",
	'COD_MOD' => "Código do modelo da NF, conforme tabela 4.1.1",
	'SERIE' => "Série da Nota Fiscal",
	'NUM_DOC' => "Número de Nota Fiscal de Exportação emitida pelo Exportador",
	'CHV_NFE' => "Chave da Nota Fiscal Eletrônica",
	'DT_DOC' => "Data da emissão da NF de exportação",
	'COD_ITEM' => "Código do item (campo 02 do Registro 0200)"
);
  $pr->abre_excel_sql("l105", "1105 - DOCUMENTOS FISCAIS DE EXPORTAÇÃO", $sql, $col_format, $cabec, $form_final);
  
  $sql = "
SELECT * FROM l100;
";
  $col_format = array(
	"A:A" => "0",
	"F:F" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1100",
	'IND_DOC' => "Informe o tipo de documento:
0 - Declaração de Exportação;
1 - Declaração Simplificada de Exportação;
2 - Declaração Única de Exportação.",
	'NRO_DE' => "Número da declaração",
	'DT_DE' => "Data da declaração",
	'NAT_EXP' => "Preencher com:
0 - Exportação Direta
1 - Exportação Indireta",
	'NRO_RE' => "Nº do registro de Exportação",
	'DT_RE' => "Data do Registro de Exportação",
	'CHC_EMB' => "Nº do conhecimento de embarque",
	'DT_CHC' => "Data do conhecimento de embarque",
	'DT_AVB' => "Data da averbação da Declaração de exportação",
	'TP_CHC' => "Informação do tipo de conhecimento de embarque:
01 AWB
02 MAWB
03 HAWB
04 COMAT
06 R. EXPRESSAS
07 ETIQ. REXPRESSAS
08 HR. EXPRESSAS
09 AV7
10 BL
11 MBL
12 HBL
13 CRT
14 DSIC
16 COMAT BL
17 RWB
18 HRWB
19 TIF/DTA
20 CP2
91 NÂO IATA
92 MNAO IATA
93 HNAO IATA
99 OUTROS",
	'PAIS' => "Código do país de destino da mercadoria (Preencher conforme tabela do SISCOMEX)"
);
  $pr->abre_excel_sql("l100", "1100 - REGISTRO DE INFORMAÇÕES SOBRE EXPORTAÇÃO", $sql, $col_format, $cabec, $form_final);
  
  
  $sql = "
SELECT * FROM l010;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 1010",
	'IND_EXP' => "Reg. 1100 - Ocorreu averbação (conclusão) de exportação no período:
S – Sim
N - Não",
	'IND_CCRF' => "Reg 1200 – Existem informações acerca de créditos de ICMS a serem controlados, definidos pela Sefaz:
S – Sim
N - Não",
	'IND_COMB' => "Reg. 1300 – É comercio varejista de combustíveis com movimentação e/ou estoque no período:
S – Sim
N - Não",
	'IND_USINA' => "Reg. 1390 – Usinas de açúcar e/álcool – O estabelecimento é produtor de açúcar e/ou álcool carburante com movimentação e/ou estoque no período:
S – Sim
N - Não",
	'IND_VA' => "Reg 1400 – Sendo o registro obrigatório em sua Unidade de Federação, existem informações a serem prestadas neste registro:
S – Sim;
N - Não",
	'IND_EE' => "Reg 1500 - A empresa é distribuidora de energia e ocorreu fornecimento de energia elétrica para consumidores de outra UF:
S – Sim;
N - Não",
	'IND_CART' => "Reg 1600 - Realizou vendas com Cartão de Crédito ou de débito:
S – Sim;
N - Não",
	'IND_FORM' => "Reg. 1700 – Foram emitidos documentos fiscais em papel no período em unidade da federação que exija o controle de utilização de documentos fiscais:
S – Sim
N - Não",
	'IND_AER' => "Reg 1800 – A empresa prestou serviços de transporte aéreo de cargas e de passageiros:
S – Sim
N - Não"
);
  $pr->abre_excel_sql("l010", "1010 - Obrigatoriedade de Registros do Bloco 1", $sql, $col_format, $cabec, $form_final);
  
  
  $sql = "
SELECT * FROM o600;
";
  $col_format = array(
	"A:A" => "0",
	"F:F" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0600",
	'dt_alt' => "Data da inclusão/alteração",
	'cod_ccus' => "Código do centro de custos",
	'ccus' =>  "Nome do centro de custos."
);
  $pr->abre_excel_sql("o600", "0600 - Centro de Custos", $sql, $col_format, $cabec, $form_final);
  
  
  $sql = "
SELECT * FROM o500;
";
  $col_format = array(
	"A:A" => "0",
	"F:F" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0500",
	'dt_alt' => "Data da inclusão/alteração",
	'cod_ nat_cc' => "Código da natureza da conta/grupo de contas:
01 - Contas de ativo;
02 - Contas de passivo;
03 - Patrimônio líquido;
04 - Contas de resultado;
05 - Contas de compensação;
09 - Outras.",
	'ind_cta' => "Indicador do tipo de conta:
S - Sintética (grupo de contas);
A - Analítica (conta).",
	'nível' => "Nível da conta analítica/grupo de contas.",
	'cod_cta' => "Código da conta analítica/grupo de contas.",
	'nome_cta' => "Nome da conta analítica/grupo de contas."
);
  $pr->abre_excel_sql("o500", "0500 - Plano de Contas Contábeis", $sql, $col_format, $cabec, $form_final);
  

 
  $sql = "
SELECT * FROM o460;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0460",
		'cod_obs' => "Código da Observação do lançamento fiscal.",
		'txt' => "Descrição da observação vinculada ao lançamento fiscal"
);
  $pr->abre_excel_sql("o460", "0460 - Tabela de Observações do documento fiscal", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM o450;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "@");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0450",
		'cod_inf' => "Código da informação complementar do documento fiscal.",
		'txt' => "Texto livre da informação complementar existente no documento fiscal, inclusive espécie de normas legais, poder normativo, número, capitulação, data e demais referências pertinentes com indicação referentes ao tributo"
);
  $pr->abre_excel_sql("o450", "0450 - Tabela de Informação Complementar do documento fiscal", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM o400;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "@");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0400",
		'cod_nat' => "Código da natureza da operação/prestação",
		'descr_nat' => "Descrição da natureza da operação/prestação"
);
  $pr->abre_excel_sql("o400", "0400 - Tabela de Natureza da Operação/ Prestação", $sql, $col_format, $cabec, $form_final);
  

  $sql = "
SELECT * FROM o305;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0305",
	'Ord300' => "Número da Linha do Registro 0300",
	'cod_ccus' => "Código do centro de custo onde o bem está sendo ou será utilizado (campo 03 do Registro 0600)",
	'func' => "Descrição sucinta da função do bem na atividade do estabelecimento",
	'vida_util' => "Vida útil estimada do bem, em número de meses"
);
  $pr->abre_excel_sql("o305", "0305 - CADASTRO DE BENS OU COMPONENTES DO ATIVO IMOBILIZADO", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o300;
";
  $col_format = array(
	"A:A" => "0",
	"F:F" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0300",
	'cod_ind_bem' => "Código individualizado do bem ou componente adotado no controle patrimonial do estabelecimento informante",
	'ident_merc' => "Identificação do tipo de mercadoria:
1 = bem;
2 = componente.",
	'descr_item' => "Descrição do bem ou componente (modelo, marca e outras características necessárias a sua individualização)",
	'cod_prnc' => "Código de cadastro do bem principal nos casos em que o bem ou componente ( campo 02) esteja vinculado a um bem principal.",
	'cod_cta' => "Código da conta analítica de contabilização do bem ou componente (campo 06 do Registro 0500)",
	'nr_parc' => "Número total de parcelas a serem apropriadas, segundo a legislação de cada unidade federada"
);
  $pr->abre_excel_sql("o300", "0300 - CADASTRO DE BENS OU COMPONENTES DO ATIVO IMOBILIZADO", $sql, $col_format, $cabec, $form_final);


  
  $sql = "
SELECT * FROM o220;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0220",
		'Ord200' => "Número da Linha do Registro 0200",
		'unid_conv' => "Unidade comercial a ser convertida na unidade de estoque, referida no registro 0200.",
		'fat_conv' => "Fator de conversão: fator utilizado para converter (multiplicar) a unidade a ser convertida na unidade adotada no inventário."
);
  $pr->abre_excel_sql("o220", "0220 - Fatores de Conversão de Unidades", $sql, $col_format, $cabec, $form_final);
  
  $sql = "
SELECT * FROM o206;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0206",
		'cod_comb' => "Código do produto, conforme tabela publicada pela ANP"
);
  $pr->abre_excel_sql("o206", "0206 - Código de produto conforme tabela publicada pela ANP", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM o205;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0220",
		'Ord200' => "Número da Linha do Registro 0200",
		'descr_ant_item' => "Descrição anterior do item",
		'dt_ini' => "Data inicial de utilização da descrição do item",
		'dt_fim' => "Data Final de utilização da descrição do item",
		'cod_ant_item' => "Código anterior do item com relação à última informação apresentada"
);
  $pr->abre_excel_sql("o205", "0205 - Alteração do Item", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o200;
";
  $col_format = array(
		"A:A" => "0",
		"B:B" => "@",
		"D:D" => "0",
		"H:H" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0200",
	'COD_ITEM' => "Código do item",
	'DESCR_ITEM' => "Descrição do item",
	'COD_BARRA' => "Representação alfanumérico do código de barra do produto, se houver",
	'COD_ANT_ITEM' => "Código anterior do item com relação à última informação apresentada.",
	'UNID_INV' => "Unidade de medida utilizada na quantificação de estoques.",
	'TIPO_ITEM' => "Tipo do item – Atividades Industriais, Comerciais e Serviços:
00 – Mercadoria para Revenda;
01 – Matéria-Prima;
02 – Embalagem;
03 – Produto em Processo;
04 – Produto Acabado;
05 – Subproduto;
06 – Produto Intermediário;
07 – Material de Uso e Consumo;
08 – Ativo Imobilizado;
09 – Serviços;
10 – Outros insumos;
99 – Outras",
	'COD_NCM' => "Código da Nomenclatura Comum do Mercosul",
	'EX_IPI' => "Código EX, conforme a TIPI",
	'COD_GEN' => "Código do gênero do item, conforme a Tabela 4.2.1",
	'COD_LST' => "Código do serviço conforme lista do Anexo I da Lei Complementar Federal nº 116/03.",
	'ALIQ_ICMS' => "Alíquota de ICMS aplicável ao item nas operações internas",
	'CEST' => "Código Especificador da Substituição Tributária"
);
  $pr->abre_excel_sql("o200", "0200 - TABELA DE IDENTIFICAÇÃO DO ITEM (PRODUTO E SERVIÇOS)", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o190;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "@");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0190",
		'unid' => "Código da Unidade de Medida",
		'descr' => "Descrição da Unidade de Medida"
);
  $pr->abre_excel_sql("o190", "0190 - IDENTIFICAÇÃO DAS UNIDADES DE MEDIDA", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM o175;
";
  $col_format = array(
	"A:A" => "0",
	"D:D" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0175",
		'dt_alt' => "Data de alteração do cadastro",
		'nr_campo' => "Número do campo alterado (Somente campos 03 a 13)",
		'Cont_Ant' => "Conteúdo anterior do campo"
);
  $pr->abre_excel_sql("o175", "0175 - Alteração da Tabela de Cadastro de Participante (Reg 0150)", $sql, $col_format, $cabec, $form_final);



  $sql = "
SELECT * FROM o150;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "0",
	"E:E" => "0",
	"G:G" => "0"
);
  $cabec = array(
	'OrdC150' => "Número da Linha do Registro C150",
	'cod_part' => "Código de identificação do participante no arquivo",
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
	'bairro' => "Bairro em que o imóvel está situado"
);
  $pr->abre_excel_sql("o150", "0150 - TABELA DE CADASTRO DO PARTICIPANTE", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o100;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0100",
	'nome' => "Nome do contabilista.",
	'cpf' => "Número de inscrição do contabilista no CPF.",
	'crc' => "Número de inscrição do contabilista no Conselho Regional de Contabilidade.",
	'cnpj' => "Número de inscrição do escritório de contabilidade no CNPJ, se houver.",
	'cep' => "Código de Endereçamento Postal.",
	'end' => "Logradouro e endereço do imóvel.",
	'num' => "Número do imóvel.",
	'compl' => "Dados complementares do endereço.",
	'bairro' => "Bairro em que o imóvel está situado.",
	'fone' => "Número do telefone (DDD+FONE).",
	'fax' => "Número do fax.",
	'email' => "Endereço do correio eletrônico.",
	'cod_mun' => "Código do município, conforme tabela IBGE."
);
  $pr->abre_excel_sql("o100", "0100 - DADOS DO CONTABILISTA", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o015;
";
  $col_format = array(
	"A:A" => "0",
	"C:C" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0015",
	'uf_st' => "Sigla da unidade da federação do contribuinte substituído ou unidade de federação do consumidor final não contribuinte - ICMS Destino EC 87/15.",
	'ie_st' => "Inscrição Estadual do contribuinte substituto na unidade da federação do contribuinte substituído ou unidade de federação do consumidor final não contribuinte - ICMS Destino EC 87/15."
);
  $pr->abre_excel_sql("o015", "0015 - DADOS DO CONTRIBUINTE SUBSTITUTO OU RESPONSÁVEL PELO ICMS DESTINO", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM o005;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0005",
	'fantasia' => "Nome de fantasia associado ao nome empresarial.",
	'cep' => "Código de Endereçamento Postal.",
	'end' => "Logradouro e endereço do imóvel.",
	'num' => "Número do imóvel.",
	'compl' => "Dados complementares do endereço.",
	'bairro' => "Bairro em que o imóvel está situado.",
	'fone' => "Número do telefone (DDD+FONE).",
	'fax' => "Número do fax.",
	'email' => "Endereço do correio eletrônico."
);
  $pr->abre_excel_sql("o005", "0005 - DADOS COMPLEMENTARES DA ENTIDADE", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o000;
";
  $col_format = array(
	"J:M" => "0",
	"G:H" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0000",
	'cod_ver' => "Código da versão do leiaute conforme a tabela indicada no Ato COTEPE",
	'cod_fin' => "Código da finalidade do arquivo:
0 - Remessa do arquivo original;
1 - Remessa do arquivo substituto.",
	'dt_ini' => "Data inicial das informações contidas no arquivo.",
	'dt_fin' => "Data final das informações contidas no arquivo.",
	'nome' => "Nome empresarial da entidade.",
	'cnpj' => "Número de inscrição da entidade no CNPJ.",
	'cpf' => "Número de inscrição da entidade no CPF.",
	'uf' => "Sigla da unidade da federação da entidade.",
	'ie' => "Inscrição Estadual da entidade.",
	'cod_mun' => "Código do município do domicílio fiscal da entidade, conforme a tabela IBGE",
	'im' => "Inscrição Municipal da entidade.",
	'suframa' => "Inscrição da entidade na SUFRAMA",
	'ind_perfil' => "Perfil de apresentação do arquivo fiscal, conforme definido pelo Fisco Estadual:
A – Perfil A;
B – Perfil B;
C – Perfil C.",
	'ind_ativ' => "Indicador de tipo de atividade:
0 – Industrial ou equiparado a industrial
1 – Outros."
);
  $pr->abre_excel_sql("o000", "0000 - ABERTURA DO ARQUIVO DIGITAL E IDENTIFICAÇÃO DA ENTIDADE", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM tab_munic;
";
  $col_format = array(
	"B:B" => "@");
  $cabec = array(
		'cod' => "Código do Município",
		'uf' => "Unidade da Federação",
		'munic' => "Município"
);
  $pr->abre_excel_sql("Tab_Munic", "Tabela de Municípios", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM tab4_1_1;
";
  $col_format = array(
	"B:B" => "@");
  $cabec = array(
		'cod' => "Código da Tabela 4.1.1",
		'descri' => "Descrição do Documento Fiscal",
		'mod' => "Modelo do Documento Fiscal"
);
  $pr->abre_excel_sql("Tab_4_1_1", "Tabela 4.1.1 - Tabela Documentos Fiscais do ICMS", $sql, $col_format, $cabec, $form_final);


  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(100);
	$this->excel_largura_coluna("A:A", 7);
	$this->excel_largura_coluna("E:E", 100);
';
  $sql = "
  SELECT '' AS aaaamm, '' AS reg, '' AS qtd, '' AS proc, '##NTZ##Parte 1 - Total Geral' AS descri;
  SELECT s1.aaaamm, s1.reg, s1.qtd, descri_reg.proc, descri_reg.descri
    FROM (SELECT '' AS aaaamm, reg, sum(qtd) AS qtd FROM conta_reg GROUP BY reg) AS s1
    LEFT OUTER JOIN descri_reg ON s1.reg = descri_reg.reg;
  SELECT '' AS aaaamm, '' AS reg, '' AS qtd, '' AS proc, '##NTZ##Parte 2 - Totais em cada Período' AS descri;
  SELECT s1.aaaamm, s1.reg, s1.qtd, descri_reg.proc, descri_reg.descri
    FROM (SELECT aaaamm, reg, qtd FROM conta_reg) AS s1
    LEFT OUTER JOIN descri_reg ON s1.reg = descri_reg.reg;
";
  $col_format = array(
	'B:B' => '0000'
);
  $cabec = array(
	'aaaamm' => 'Ano/Mês',
	'Reg' => 'Registro',
	'Qtd' => 'Quantidade de Registros',
	'Pr' => 'Processado pelo Conversor ?',
	'Descrição do Registro' => 'Descrição do Registro'
);
  $pr->abre_excel_sql("Resumo", "Resumo do(s) EFD(s)", $sql, $col_format, $cabec, $form_final);


  $pr->finaliza_excel();
  
}

?>