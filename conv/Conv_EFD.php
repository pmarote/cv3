<?php

function leitura_efd($arquivo_efd) {
  // não dei a indentação ... atenção...

  global $pr, $options;

  // Regras para o nome do arquivo db3 e também dos arquivos xls gerados na conversão
  // Se a opção "um arquivo excel para cada arquivo em fontes" estiver setada, nome = efd (efd.db3, efd.xls)
  // Caso contrário, nome = efd_{$arquivo_efd}
  $nomarqaux = explode("/", $arquivo_efd);
  if ($options['arqs_sep']) $nomarq = "efd_" . substr($nomarqaux[count($nomarqaux)-1], 0, -4); else $nomarq = "efd";

  if (!file_exists(PR_TMP . "/{$nomarq}.db3")) { 

	if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
	} else {
	  werro('Falha ao criar Banco de Dados efd.db3');
	  exit;
	}  

	$db->query('PRAGMA encoding = "UTF-8";');

	cria_tabela_cfopd($db);

	// Usado em Resumo de EFD_Dados
	$db->query('CREATE TABLE conta_reg (
	  arq, aaaamm, reg, qtd int)
	');
  
	// Código do Registro, proc = Processa Sim ou Não, Nível, Descrição
	$createtable = "
CREATE TABLE descri_reg (reg text, proc text, nivel int, descri text);
CREATE INDEX descri_reg_reg ON descri_reg (reg ASC);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/EFD_Reg_Descri.txt', 'descri_reg');	
  
	// Tabela 4.1.1 - Tipos de Documentos Fiscais - Ver arquivo Tab4.1.1.txt em PR_RES . '/tabelas'
	$createtable = "
CREATE TABLE tab4_1_1 (cod text, descri text, mod text);
CREATE INDEX tab4_1_1_cod ON tab4_1_1 (cod ASC);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/Tab4.1.1.txt', 'tab4_1_1');	

	// Tabela tab_munic
	$createtable = "
CREATE TABLE tab_munic (cod int primary key, uf text, munic text);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/Tabela_Municípios.txt', 'tab_munic');	
  
  
	$db->query('CREATE TABLE o000 (
	  ord int primary key,
	  cod_ver int,
	  cod_fin int,
	  dt_ini text,
	  dt_fin text,
	  nome text,
	  cnpj int,
	  cpf int,
	  uf text,
	  ie text,
	  cod_mun text,
	  im text,
	  suframa text,
	  ind_perfil text,
	  int_ativ int )
	');

	// Dados complementares de entidade
	$db->query('CREATE TABLE o005 (
	  ord int primary key,
	  fantasia text,
	  cep int,
	  end text,
	  num text,
	  compl text,
	  bairro text,
	  fone text,
	  fax text,
	  email text )
	');

	// DADOS DO CONTRIBUINTE SUBSTITUTO OU RESPONSÁVEL PELO ICMS DESTINO
	$db->query('CREATE TABLE o015 (
	  ord int primary key, uf_st text, ie_st text )
	');

	// Dados do Contabilista
	$db->query('CREATE TABLE o100 (
	  ord int primary key,
	  nome text,
	  cpf int,
	  crc text,
	  cnpj int,
	  cep int,
	  end text,
	  num text,
	  compl text,
	  bairro text,
	  fone text,
	  fax text,
	  email text,
	  cod_mun int)
	');

	$db->query('CREATE TABLE o150 (
	  ord int primary key,
	  cod_part text,
	  nome text,
	  cod_pais int,
	  cnpj int,
	  cpf int,
	  ie text,
	  cod_mun text,
	  suframa text,
	  end text,
	  num text,
	  compl text,
	  bairro text )
	');
	$db->query('CREATE INDEX "o150_reg_prim" ON o150 (cod_part ASC)');

  // 0175 - Alteração da Tabela de Cadastro de Participante 
	$db->query('CREATE TABLE o175 (
	  ord int primary key, dt_alt, nr_campo, cont_ant )
	');

  // 0190 - Unidades de Medida
	$db->query('CREATE TABLE o190 (
	  ord int primary key, unid text, descr text )
	');

  // 0200 - Tabela de Identificação do Item (Produtos e Serviços)
	$db->query('CREATE TABLE o200 (
	  ord int primary key,
	  cod_item text, descr_item text, cod_barra text, cod_ant_item text, unid_inv,
	  tipo_item int, cod_ncm text, ex_ipi text, cod_gen int, cod_lst int, aliq_icms real, cest int)
	');
	$db->query('CREATE INDEX "o200_reg_prim" ON o200 (cod_item ASC)');
  
  // 0205 - Alteração do Item
	$db->query('CREATE TABLE o205 (
	  ord int primary key, ordo200 int,
	  descr_ant_item, dt_ini, dt_fim, cod_ant_item)
	');

  // 0206: CÓDIGO DE PRODUTO CONFORME TABELA PUBLICADA PELA ANP
	$db->query('CREATE TABLE o206 (
	  ord int primary key,
	  cod_comb text)
	');

  // 0220 - Fatores de Conversão de Unidades
	$db->query('CREATE TABLE o220 (
	  ord int primary key, ordo200 int,
	  unid_conv, fat_conv)
	');

  // 0300 - Bens do Ativo Imobilizado
	$db->query('CREATE TABLE o300 (
	  ord int primary key, 
	  cod_ind_bem, ident_merc, descr_item, cod_prnc, cod_cta, nr_parc int)
	');

  // 0305 - Informações Sobre a Utilização do Bem
	$db->query('CREATE TABLE o305 (
	  ord int primary key, ordo300 int,
	  cod_ccus, func, vida_util int)
	');
	
  // 0400 - Tabela de Natureza de Operação / Prestação
	$db->query('CREATE TABLE o400 (
	  ord int primary key,
	  cod_nat, descr_nat)
	');

  // 0450 - Tabela de Informação Complementar do Documento Fiscal
	$db->query('CREATE TABLE o450 (
	  ord int primary key,
	  cod_inf, txt)
	');

  // 0460 - Tabela de Informação Complementar do Documento Fiscal
	$db->query('CREATE TABLE o460 (
	  ord int primary key,
	  cod_obs, txt)
	');

  // 0500 - Plano de Contas Contábeis
	$db->query('CREATE TABLE o500 (
	  ord int primary key,
	  dt_alt, cod_nat_cc, ind_cta, nível INT, cod_cta, nome_cta)
	');

  // 0600 - Centro de Custos
	$db->query('CREATE TABLE o600 (
	  ord int primary key,
	  dt_alt, cod_ccus, ccus)
	');

  // 1010 - OBRIGATORIEDADE DE REGISTROS DO BLOCO 1
	$db->query('CREATE TABLE l010 (
	  ord int primary key,
	  ind_exp text, ind_ccrf text, ind_comb text, ind_usina text, ind_va text, ind_ee text, ind_cart text, ind_form text, ind_aer text)
	');

  // 1100 - REGISTRO DE INFORMAÇÕES SOBRE EXPORTAÇÃO.
	$db->query('CREATE TABLE l100 (
	  ord int primary key,
	  ind_doc int, nro_de text, dt_de text, nat_exp int, nro_re int, dt_re text, chc_emb text, dt_chc text, dt_avb text, tp_chc int, pais int)
	');

  // 1105 - DOCUMENTOS FISCAIS DE EXPORTAÇÃO
	$db->query('CREATE TABLE l105 (
	  ord int primary key, ordl100 int,
	  cod_mod text, serie text, num_doc int, chv_nfe text, dt_doc text, cod_item text)
	');

	// 1200 - CONTROLE DE CRÉDITOS FISCAIS - ICMS
	$db->query('CREATE TABLE l200 (
	  ord int primary key,
	  cod_aj_apur text, sld_cred real, cred_apr real, cred_receb real, cred_util real, sld_cred_fim real)
	');

  // 1210 - UTILIZAÇÃO DE CRÉDITOS FISCAIS – ICMS
	$db->query('CREATE TABLE l210 (
	  ord int primary key,
	  tipo_util text, nr_doc text, vl_cred_util real, chv_doce text)
	');

  // 1300 - MOVIMENTAÇÃO DIÁRIA DE COMBUSTÍVEIS
	$db->query('CREATE TABLE l300 (
	  ord int primary key,
	  cod_item text, dt_fech text, estq_abert real, vol_entr real, vol_disp real, vol_saidas real,
	  estq_escr real, val_aj_perda real, val_aj_ganho real, fech_fisico real)
	');

  // 1310 - MOVIMENTAÇÃO DIÁRIA DE COMBUSTÍVEIS POR TANQUE
	$db->query('CREATE TABLE l310 (
	  ord int primary key, ord1300 int,
	  num_tanque text, estq_abert real, vol_entr real, vol_disp real, vol_saidas real,
	  estq_escr real, val_aj_perda real, val_aj_ganho real, fech_fisico real)
	');

  // 1320 - VOLUME DE VENDAS
	$db->query('CREATE TABLE l320 (
	  ord int primary key, ord1310 int,
	  num_bico int, nr_interv int, mot_interv text, nom_interv text, cnpj_interv int, cpf_interv int,
	  val_fecha real, val_abert real, vol_aferi real, vol_vendas real)
	');

  // 1350 - BOMBAS
	$db->query('CREATE TABLE l350 (
	  ord int primary key,
	  serie text, fabricante text, modelo text, tipo_medicao text)
	');

  // 1360 - LACRES DA BOMBA
	$db->query('CREATE TABLE l360 (
	  ord int primary key, ord1350 int,
	  num_lacre text, dt_aplicacao text)
	');

  // 1370 - BICOS DA BOMBA
	$db->query('CREATE TABLE l370 (
	  ord int primary key, ord1350 int,
	  NUM_BICO text, COD_ITEM text, NUM_TANQUE text)
	');

	// C100 - 
	$db->query('CREATE TABLE C100 (
	  ord int primary key,
	  ind_oper text, ind_emit text, cod_part text, cod_mod text, cod_sit int,
	  ser text, num_doc int, chv_nfe text, dt_doc text, dt_e_s text, vl_doc real,
	  ind_pgto text, vl_desc real, vl_abat_nt real, vl_merc real,
	  ind_frt text, vl_frt real, vl_seg real, vl_out_da real,
	  vl_bc_icms real, vl_icms real, vl_bc_icms_st real, vl_icms_st real, vl_ipi real,
	  vl_pis real, vl_cofins real, vl_pis_st real, vl_cofins_st real )
	');

	// C101 = INFORMAÇÃO COMPLEMENTAR DOS DOCUMENTOS FISCAIS QUANDO DAS OPERAÇÕES INTERESTADUAIS DESTINADAS A CONSUMIDOR FINAL NÃO CONTRIBUINTE EC 87/15 (CÓDIGO 55) 
	$db->query('CREATE TABLE C101 (
	  ord int primary key, ordC100 int, 
	  vl_fcp_uf_dest real, vl_icms_uf_dest real, vl_icms_uf_rem real)
	');

	// C110 = informação complementar da nota fiscal 
	$db->query('CREATE TABLE C110 (
	  ord int primary key, ordC100 int, 
	  cod_inf, txt_compl )
	');

	// C113 = Complemento de Documento - Documento Fiscal Referenciado
	$db->query('CREATE TABLE C113 (
	  ord int primary key, ordC110 int, 
	  ind_oper, ind_emit, cod_part, cod_mod, ser, sub, num_doc, dt_doc )
	');

	// C114 = Complemento de Documento - Cupom Fiscal Referenciado
	$db->query('CREATE TABLE C114 (
	  ord int primary key, ordC110 int, 
	  cod_mod text, ecf_fab text, ecf_cx int, num_doc int, dt_doc text)
	');

	// C120 - COMPLEMENTO DE DOCUMENTO - OPERAÇÕES DE IMPORTAÇÃO (CÓDIGOS 01 e 55)
	$db->query('CREATE TABLE C120 (
	  ord int primary key, ordC100 int, 
	  cod_doc_imp text, num_doc__imp text, pis_imp real, cofins_imp real, num_acdraw text)
	');

	// C140 = Fatura
	$db->query('CREATE TABLE C140 (
	  ord int primary key,
	  ordC100 int, ind_emit text, ind_tit text, desc_tit text, num_tit text, qtd_parc int, vl_tit real )
	');

	// C141 = Vencimento da Fatura
	$db->query('CREATE TABLE C141 (
	  ord int primary key,
	  ordC140 int, num_parc int, dt_vcto text, vl_parc real )
	');

	//  item da NF (C170)
	$db->query('CREATE TABLE C170 (
	  ord int primary key, ordC100 int,
	  num_item int, cod_item text, descr_compl text, qtd real, unid text,
	  vl_item real, vl_desc real, ind_mov text, cst_icms int, cfop int, cod_nat text,
	  vl_bc_icms real, aliq_icms real, vl_icms real, vl_bc_icms_st real, aliq_st real, vl_icms_st real,
	  ind_apur text, cst_ipi int, cod_enq text,
	  vl_bc_ipi real, aliq_ipi real, vl_ipi real, cst_pis int, vl_bc_pis real, aliq_pis real, quant_bc_pis real,
	  aliq_pis_r real, vl_pis real, cst_cofins int, vl_bc_cofins real, aliq_cofins real, quant_bc_cofins real,
	  aliq_cofins_r real, vl_cofins real, cod_cta text )
	');

	//  C171: armazenamento de combustiveis - LMC
	$db->query('CREATE TABLE C171 (
	  ord int primary key, ordC170 int,
	  num_tanque text, qtde real)
	');

	//  C173: operações com medicamentos
	$db->query('CREATE TABLE C173 (
	  ord int primary key, ordC170 int,
	  lote_med text, qtd_item real, dt_fab text, dt_val text, ind_med text, tp_prod text, vl_tab_max real)
	');

	//  C174: operações com armas de fogo
	$db->query('CREATE TABLE C174 (
	  ord int primary key, ordC170 int,
	  ind_arm text, num_arm text, descr_compl text )
	');

	//  C175: operações com veículos novos
	$db->query('CREATE TABLE C175 (
	  ord int primary key, ordC170 int,
	  ind_veic_oper text, cnpj int, uf text, chassi_veic text)
	');

	//  C176: ressarcimento de icms em operações com substituição tributária
	$db->query('CREATE TABLE C176 (
	  ord int primary key, ordC170 int,
	  cod_mod_ult_e text, num_doc_ult_e int, ser_ult_e text, dt_ult_e text, cod_part_ult_e text,
	  quant_ult_e real, vl_unit_ult_e real, vl_unit_bc_st real, 
	  chave_nfe_ult_e text, num_item_ult_e int, 
	  vl_unit_bc_icms_ult_e real, aliq_icms_ult_e real, vl_unit_limite_bc_icms_ult_e real,
	  vl_unit_icms_ult_e real, aliq_st_ult_e real, vl_unit_res real, 
	  cod_resp_ret int, cod_mot_res int, chave_nfe_ret text, cod_part_nfe_ret text, ser_nfe_ret text, num_nfe_ret int, item_nfe_ret int, 
	  cod_da text, num_da text )
	');

	//  registro analítico do documento (código 01, 1b, 04 e 55) (C190)
	$db->query('CREATE TABLE C190 (
	  ord int primary key, ordC100 int,
	  cst_icms int, cfop int, aliq_icms real, vl_opr real, vl_bc_icms real, vl_icms real, 
	  vl_bc_icms_st real, vl_icms_st real, vl_red_bc real, vl_ipi real, cod_obs )
	');

	//  observaçoes do lançamento fiscal (código 01, 1B E 55) (C195)
	$db->query('CREATE TABLE C195 (
	  ord int primary key, ordC190 int,
	  cod_obs, txt_compl )
	');

	//  OUTRAS OBRIGAÇÕES TRIBUTÁRIAS, AJUSTES E INFORMAÇÕES DE VALORES PROVENIENTES DE DOCUMENTO FISCAL (C197)
	$db->query('CREATE TABLE C197 (
	  ord int primary key, ordC190 int, ordC195 int,
	  cod_aj text, descr_compl_aj text, cod_item int, vl_bc_icms real, aliq_icms real, vl_icms real, vl_outros real )
	');

	
	// C400 - 
	$db->query('CREATE TABLE C400 (
	  ord int primary key,
	  cod_mod text, ecf_mod text, ecf_fab text, ecf_cx int )
	');

	// C405 = Redução Z 
	$db->query('CREATE TABLE C405 (
	  ord int primary key, ordC400 int, 
	  dt_doc text, cro int, crz int, num_coo_fin int, gt_fin real, vl_brt real)
	');

	// C410 = PIS E COFINS TOTALIZADOS NO DIA (CÓDIGO 02 e 2D)
	$db->query('CREATE TABLE C410 (
	  ord int primary key, ordC405 int, 
	  vl_pis real, vl_cofins real)
	');

	// C420 = REGISTRO DOS TOTALIZADORES PARCIAIS DA REDUÇÃO Z (COD 02, 2D e 60)
	$db->query('CREATE TABLE C420 (
	  ord int primary key, ordC405 int, 
	  cod_tot_par text, vlr_acum_tot real, nr_tot int, descr_nr_tot text)
	');

	// C460 = DOCUMENTO FISCAL EMITIDO POR ECF (CÓDIGO 02, 2D e 60)
	$db->query('CREATE TABLE C460 (
	  ord int primary key, ordC405 int, 
	  cod_mod text, cod_sit int, num_doc int, dt_doc text, vl_doc real, vl_pis real, vl_cofins real, cpf_cnpj int, nom_adq text)
	');

	// C470 =  ITENS DO DOCUMENTO FISCAL EMITIDO POR ECF (CÓDIGO 02 e 2D)
	$db->query('CREATE TABLE C470 (
	  ord int primary key, ordC460 int, 
	  cod_item text, qtd real, qtd_canc real, unid text, vl_item real, cst_icms int, cfop int, aliq_icms real, vl_pis real, vl_cofins real)
	');

	//  C490 -  REGISTRO ANALÍTICO DO MOVIMENTO DIÁRIO (CÓDIGO 02, 2D e 60)
	$db->query('CREATE TABLE C490 (
	  ord int primary key, ordC405 int,
	  cst_icms int, cfop int, aliq_icms real, vl_opr real, vl_bc_icms real, vl_icms real, cod_obs text)
	');

	
	// nota fiscal/conta de energia elétrica (código 06), nota fiscal/conta de fornecimento d'água canalizada (código 29) e
	// nota fiscal consumo fornecimento de gás (código 28).
	$db->query('CREATE TABLE C500 (
	  ord int primary key,
	  ind_oper text, ind_emit text, cod_part text, cod_mod text, cod_sit int,
	  ser text, sub int, cod_cons text, num_doc int, dt_doc text, dt_e_s text, vl_doc real,
	  vl_desc real, vl_forn real, vl_serv_nt real, vl_terc real, vl_da real, 
	  vl_bc_icms real, vl_icms real, vl_bc_icms_st real, vl_icms_st real, cod_inf text,
	  vl_pis real, vl_cofins real, tp_ligacao int, cod_grupo_tensao text )
	');

	//  C590 - registro analítico do documento - NF Energia Elétrica, Água Canalizada e Fornecimento de Gás
	$db->query('CREATE TABLE C590 (
	  ord int primary key, ordC500 int,
	  cst_icms int, cfop int, aliq_icms real, vl_opr real, vl_bc_icms real, vl_icms real, 
	  vl_bc_icms_st real, vl_icms_st real, vl_red_bc real, cod_obs )
	');

	//  C800 - CUPOM FISCAL ELETRÔNICO – SAT (CF-E-SAT) (CÓDIGO 59)
	$db->query('CREATE TABLE C800 (
	  ord int primary key, 
	  cod_mod text, cod_sit int, num_cfe int, dt_doc text, 
	  vl_cfe real, vl_pis real, vl_cofins real, 
	  cnpj_cpf int, nr_sat text, chv_cfe text, 
	  vl_desc real, vl_merc real, vl_out_da real, vl_icms real, vl_pis_st real, vl_cofins_st real )
	');

	//  C850: REGISTRO ANALÍTICO DO CF-E-SAT (CODIGO 59)
	$db->query('CREATE TABLE C850 (
	  ord int primary key, ordC800 int,
	  cst_icms int, cfop int, aliq_icms real, 
	  vl_opr real, vl_bc_icms real, vl_icms real, cod_obs text )
	');

	//  C860: IDENTIFICAÇÃO DO EQUIPAMENTO SAT-CF-E 
	$db->query('CREATE TABLE C860 (
	  ord int primary key, 
	  cod_mod text, nr_sat int, dt_doc text, doc_ini int, doc_fim int )
	');

	// C890: RESUMO DIÁRIO DO CF-E-SAT (CÓDIGO 59) POR EQUIPAMENTO SAT-CF-E
	$db->query('CREATE TABLE C890 (
	  ord int primary key, ordC860 int,
	  cst_icms int, cfop int, aliq_icms real, vl_opr real, vl_bc_icms real, vl_icms real, cod_obs text)
	');

	// Conhecimento de Transporte
	$db->query('CREATE TABLE D100 (
	  ord int primary key,
	  ind_oper text, ind_emit text, cod_part text, cod_mod text, cod_sit int,
	  ser text, sub text, num_doc int, chv_cte text, dt_doc text, dt_a_p text, tp_cte int, chv_cte_ref text,
	  vl_doc real, vl_desc real, ind_frt text, vl_serv real, vl_bc_icms real, vl_icms real, vl_nt real,
	  cod_inf text, cod_cta text )
	');

	//  item do CT (D100)
	$db->query('CREATE TABLE D110 (
	  ord int primary key, ordD100 int,
	  num_item int, cod_item text, vl_serv real, vl_out real )
	');

	//  Complemento do CT(D100) - Municípios Origem e Destino
	$db->query('CREATE TABLE D120 (
	  ord int primary key, ordD100 int,
	  cod_mun_orig int, cod_mun_dest int, veic_id text, uf_id text )
	');

	//  Registro Analítico do CT (D100)
	$db->query('CREATE TABLE D190 (
	  ord int primary key, ordD100 int,
	  cst_icms int, cfop int, aliq_icms real, vl_opr real, vl_bc_icms real, vl_icms real, vl_red_bc real, cod_obs text)
	');

	//  D195: OBSERVAÇÕES DO LANÇAMENTO FISCAL (CÓDIGO 07, 08, 8B, 09, 10, 11, 26, 27, 57, 63 e 67)
	$db->query('CREATE TABLE D195 (
	  ord int primary key, ordD190 int,
	  cod_obs, txt_compl )
	');

	//  D197: OUTRAS OBRIGAÇÕES TRIBUTÁRIAS, AJUSTES E INFORMAÇÕES DE VALORES PROVENIENTES DE DOCUMENTO FISCAL
	$db->query('CREATE TABLE D197 (
	  ord int primary key, ordD190 int, ordD195 int,
	  cod_aj text, descr_compl_aj text, cod_item int, vl_bc_icms real, aliq_icms real, vl_icms real, vl_outros real )
	');

	// D500 - nota fiscal de Serviço de Comunicação (Código 21) e Telecomunicação (Código 22)
	$db->query('CREATE TABLE D500 (
	  ord int primary key,
	  ind_oper text, ind_emit text, cod_part text, cod_mod text, cod_sit int,
	  ser text, sub int, num_doc int, dt_doc text, dt_a_p text, vl_doc real,
	  vl_desc real, vl_serv real, vl_serv_nt real, vl_terc real, vl_da real, 
	  vl_bc_icms real, vl_icms real, cod_inf text,
	  vl_pis real, vl_cofins real, cod_cta text, tp_assinante )
	');

	//  D590 - registro analítico do documento - nota fiscal de Serviço de Comunicação (Código 21) e Telecomunicação (Código 22)
	$db->query('CREATE TABLE D590 (
	  ord int primary key, ordD500 int,
	  cst_icms int, cfop int, aliq_icms real, vl_opr real, vl_bc_icms real, 
	  vl_icms real, vl_bc_icms_uf real, vl_icms_uf real, vl_red_bc real, cod_obs )
	');

	// E100 = Período de Apuração do ICMS
	$db->query('CREATE TABLE E100 (
	  ord int primary key,
	  dt_ini text, dt_fin text )
	');

	// E110 = Apuração do ICMS - Operações Próprias
	$db->query('CREATE TABLE E110 (
	  ord int primary key, ordE100 int,
	  vl_tot_debitos real, vl_aj_debitos real, vl_tot_aj_debitos real, vl_estornos_cred real,
	  vl_tot_creditos real, vl_aj_creditos real, vl_tot_aj_creditos real, cl_estornos_deb real,
	  vl_sld_credor_ant real, vl_sld_apurado real, vl_tot_ded real, vl_icms_recolher real,
	  vl_sld_credor_transportar real, deb_esp real)
	');
  
	// E111 = ajuste/benefício/incentivo da apuração do icms  
	$db->query('CREATE TABLE E111 (
	  ord int primary key, ordE110 int,
	  cod_aj_apur, descr_compl_aj, vl_aj_apur real)
	');
  
	// E112 - INFORMAÇÕES ADICIONAIS DOS AJUSTES DA APURAÇÃO DO ICMS
	$db->query('CREATE TABLE E112 (
	  ord int primary key, ordE111 int,
	  num_da text, num_proc text, ind_proc text, proc text, txt_compl text)
	');
  
	// E113 - INFORMAÇÕES ADICIONAIS DOS AJUSTES DA APURAÇÃO DO ICMS – IDENTIFICAÇÃO DOS DOCUMENTOS FISCAIS
	$db->query('CREATE TABLE E113 (
	  ord int primary key, ordE111 int,
	  cod_part text, cod_mod text, ser text, sub int, num_doc int, dt_doc text, cod_item text, vl_aj_item real, chv_doce text)
	');
  
	// E116 = obrigações do icms recolhido ou a recolher - operações próprias  
	$db->query('CREATE TABLE E116 (
	  ord int primary key, ordE110 int,
	  cod_or, vl_or real, dt_vcto, cod_rec, num_proc, ind_proc, proc, txt_compl, mes_ref)
	');
  
	// E200 = Período de Apuração do ICMS - Substituição Tributária
	$db->query('CREATE TABLE E200 (
	  ord int primary key,
	  uf text, dt_ini text, dt_fin text )
	');

	// E210 = Apuração do ICMS - Substituição Tributária
	$db->query('CREATE TABLE E210 (
	  ord int primary key, ordE200 int,
	  ind_mov_st, vl_sld_cred_ant_st real, vl_devol_st real, vl_ressarc_st real, vl_out_cred_st real, 
	  vl_aj_creditos_st real, vl_retençao_st real, vl_out_deb_st real, vl_aj_debitos_st real, 
	  vl_sld_dev_ant_st real, vl_deduções_st real, vl_icms_recol_st real, 
	  vl_sld_cred_st_transportar real, deb_esp_st real)
	');

	// E250 = obrigações do icms recolhido ou a recolher - Substituição Tributária  
	$db->query('CREATE TABLE E250 (
	  ord int primary key, ordE210 int,
	  cod_or, vl_or real, dt_vcto, cod_rec, num_proc, ind_proc, proc, txt_compl, mes_ref)
	');
  
	// E300 = Período De Apuração Do Fundo De Combate À Pobreza E Do Icms Diferencial De Alíquota – Uf Origem/Destino Ec 87/15
	$db->query('CREATE TABLE E300 (
	  ord int primary key,
	  uf text, dt_ini text, dt_fin text )
	');

	// E310 = Apuração do fundo de combate à pobreza e do icms - Diferencial de alíquota – uf origem/destino ec 87/15
	$db->query('CREATE TABLE E310 (
	  ord int primary key, ordE300 int,
	  ind_mov_fcp_difal, vl_sld_cred_ant_dif real, vl_tot_debitos_difal real, vl_out_deb_difal real, vl_tot_creditos_difal real, vl_out_cred_difal real,
	  vl_sld_dev_ant_difal real, vl_deduções_difal real, vl_recol_difal real, vl_sld_cred_transportar_difal real,
	  deb_esp_difal real, vl_sld_cred_ant_fcp real, vl_tot_deb_fcp real, vl_out_deb_fcp real, vl_tot_cred_fcp real,
	  vl_out_cred_fcp real, vl_sld_dev_ant_fcp real, vl_deduções_fcp real, vl_recol_fcp real, vl_sld_cred_transportar_fcp real, deb_esp_fcp real )
	');

	// E311 = Ajuste/benefício/incentivo da apuração do fundo de Combate à pobreza e do icms diferencial de alíquota uf Origem/destino ec 87/15  
	$db->query('CREATE TABLE E311 (
	  ord int primary key, ordE310 int,
	  cod_aj_apur, descr_compl_aj, vl_aj_apur real)
	');
  
	// E312 = Informações adicionais dos ajustes da apuração do Fundo de combate à pobreza e do icms diferencial de alíquota uf Origem/destino ec 87/15  
	$db->query('CREATE TABLE E312 (
	  ord int primary key, ordE311 int,
	  num_da, num_proc, ind_proc int, proc, txt_compl)
	');
  
	// E313 = Informações adicionais dos ajustes da apuração do Fundo de combate à pobreza e do icms diferencial de alíquota uf Origem/destino ec 87/15 - identificação dos documentos fiscais 
	$db->query('CREATE TABLE E313 (
	  ord int primary key, ordE311 int,
	  cod_part text, cod_mod text, ser text, sub int, num_doc int, chv_doce text, dt_doc text, cod_item text, vl_aj_item real)
	');
  
	// E316 = Ajuste/benefício/incentivo da apuração do fundo de Combate à pobreza e do icms diferencial de alíquota uf Origem/destino ec 87/15  
	$db->query('CREATE TABLE E316 (
	  ord int primary key, ordE310 int,
	  cod_or, vl_or real, dt_vcto text, cod_rec text, num_proc text, ind_proc int, proc text, txt_compl text, mes_ref text)
	');
  
	// G110 - ativo permanente – CIAP
	$db->query('CREATE TABLE G110 (
	  ord int primary key, 
	  dt_ini, dt_fin, saldo_in_icms REAL, som_parc REAL, vl_trib_exp REAL, vl_total REAL, ind_per_sai REAL, icms_aprop REAL, som_icms_oc REAL)
	');
  
	// G125 - movimentação de bem ou componente do ativo imobilizado
	$db->query('CREATE TABLE G125 (
	  ord int primary key, ordG110 int,
	  cod_ind_bem, dt_mov, tipo_mov, vl_imob_icms REAL, vl_imob_icms_st REAL, vl_imob_icms_frt REAL, vl_imob_icms_dif REAL, num_parc INT, vl_parc_pass REAL)
	');
  
	// G126 - Outros Créditos CIAP
	$db->query('CREATE TABLE G126 (
	  ord int primary key, ordG125 int,
	  dt_ini, dt_fim, num_parc INT, vl_parc_pass REAL, vl_trib_oc REAL, vl_total REAL, ind_per_sai REAL, vl_parc_aprop REAL)
	');
  
	// G130 - identificação do documento fiscal
	$db->query('CREATE TABLE G130 (
	  ord int primary key, ordG125 int,
	  ind_emit, cod_part, cod_mod, serie, num_doc INT, chv_nfe_cte, dt_doc)
	');
  
	// G140 - identificação do item do documento fiscal
	$db->query('CREATE TABLE G140 (
	  ord int primary key, ordG130 int,
	  num_item INT, cod_item)
	');

	// H005
	$db->query('CREATE TABLE H005 (
	  ord int primary key, 
	  dt_inv, vl_inv real, mot_inv)
	');
  
	// H010 = Inventário
	$db->query('CREATE TABLE H010 (
	  ord int primary key, ordH005 int,
	  cod_item, unid, qtd real, vl_unit real, vl_item real, ind_prop, cod_part, txt_compl, cod_cta, vl_item_ir)
	');
  
	// K100
	$db->query('CREATE TABLE K100 (
	  ord int primary key, 
	  dt_inv, dt_fin)
	');
  
	// K200
	$db->query('CREATE TABLE K200 (
	  ord int primary key, ordK100 int,
	  dt_est, cod_item, qtd real, ind_est, cod_part)
	');
  
  } else {
	if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
	} else {
	  werro('Falha ao abrir Banco de Dados efd.db3');
	  exit;
	}  
  }
  
    $ilidos = 1;		// $ilidos e $iord... é o seguinte...
						// o primeiro campo das tabelas (ord) tem o seguinte formato {anoAA}{mes_inicialMM}{nro_da_linha0000000}
						// exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011
	$ianomes = 0601;	// O ano-mes correto será Gravado quando ler o registro '0000'
    if (!$handle = fopen("{$arquivo_efd}", 'r')) {
     werro("Nao foi possivel a leitura do arquivo {$arquivo_efd} - possivelmente foi deletado durante o processamento");
     exit;
    }
	$a_conta_reg = array();		// para gravar no final a quantidade de cada registro no arquivo
	$dt_lcto = '';
	$ordo200 = 0;
	$ordo300 = 0;
	$ord1100 = 0;
	$ord1300 = 0;
	$ord1310 = 0;
	$ord1350 = 0;
	$ordC100 = 0;
	$ordC140 = 0;
	$ordC170 = 0;
	$ordC190 = 0;
	$ordC195 = 0;
	$ordC110 = 0;
	$ordC400 = 0;
	$ordC405 = 0;
	$ordC460 = 0;
	$ordC500 = 0;
	$ordC800 = 0;
	$ordC860 = 0;
	$ordD100 = 0;
	$ordD190 = 0;
	$ordD195 = 0;
	$ordE100 = 0;
	$ordE110 = 0;
	$ordE111 = 0;
	$ordE200 = 0;
	$ordE210 = 0;
	$ordE300 = 0;
	$ordE310 = 0;
	$ordE311 = 0;
	$ordG110 = 0;
	$ordG125 = 0;
	$ordG130 = 0;
	$ordH005 = 0;
	$ordK100 = 0;
	$aaaamm = '';
	$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

	
	while(!feof($handle)) {
	  $linha = fgets($handle); 
	  $campos = explode('|', $linha);

	  if ($campos[1] == '0000') $ianomes = substr($campos[4], 6, 2) . substr($campos[4], 2, 2);
	  $iord = $ilidos + $ianomes * 10000000;  

	  if (strlen($campos[1]) == 4) {
		if (isset($a_conta_reg["{$campos[1]}"])) $a_conta_reg["{$campos[1]}"]++;
		else $a_conta_reg["{$campos[1]}"] = 1;
	  }
	  
	  if ($pr->options['edutf'])
		foreach($campos  as $indice => $valor) $campos[$indice] = $db->escapeString($valor);
	  else
		foreach($campos  as $indice => $valor) $campos[$indice] = $db->escapeString(utf8_encode($valor));
	  
	  if ($campos[1] == '0000') {
	    $campos[4] = dtaSPED($campos[4]);
	    $campos[5] = dtaSPED($campos[5]);
		$aaaamm = substr($campos[4], 0, 7);
		$insert_query = <<<EOD
INSERT INTO o000 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}', '{$campos[13]}', '{$campos[14]}', '{$campos[15]}'
 )
EOD;
		$db->query($insert_query);
		if (is_null($pr->cnpj_master)) {
			$pr->cnpj_master = $campos[7] + 0;	// CNPJ é sempre int...
			wecho("\n\n#Definida propriedade pr->cnpj_master com o seguinte CNPJ: {$pr->cnpj_master} \n");	
		}
	  }

	  	  if ($campos[1] == '0005') {
		$insert_query = <<<EOD
INSERT INTO o005 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  	  if ($campos[1] == '0015') {
		$insert_query = <<<EOD
INSERT INTO o015 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  	  if ($campos[1] == '0100') {
		$insert_query = <<<EOD
INSERT INTO o100 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}', '{$campos[13]}', '{$campos[14]}'
 )
EOD;
		$db->query($insert_query);
	  }
	  
	  if ($campos[1] == '0150') {
		$insert_query = <<<EOD
INSERT INTO o150 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0175') {
	    $campos[2] = dtaSPED($campos[2]);
		$insert_query = <<<EOD
INSERT INTO o175 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0190') {
		$insert_query = <<<EOD
INSERT INTO o190 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0200') {
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		if (!isset($campos[13])) $campos[13] = ''; // CEST é somente a partir de 01/01/2017
		$insert_query = <<<EOD
INSERT INTO o200 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}', '{$campos[13]}'
 )
EOD;
		$db->query($insert_query);
		$ordo200 = $iord;
	  }

	  if ($campos[1] == '0205') {
	    $campos[3] = dtaSPED($campos[3]);
	    $campos[4] = dtaSPED($campos[4]);
		$insert_query = <<<EOD
INSERT INTO o205 VALUES(
'{$iord}', '{$ordo200}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0206') {
		$insert_query = <<<EOD
INSERT INTO o206 VALUES(
'{$iord}', '{$campos[2]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0220') {
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$insert_query = <<<EOD
INSERT INTO o220 VALUES(
'{$iord}', '{$ordo200}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0300') {
		$insert_query = <<<EOD
INSERT INTO o300 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}'
 )
EOD;
		$db->query($insert_query);
		$ordo300 = $iord;
	  }

	  if ($campos[1] == '0305') {
		$insert_query = <<<EOD
INSERT INTO o305 VALUES(
'{$iord}', '{$ordo300}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0400') {
		$insert_query = <<<EOD
INSERT INTO o400 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0450') {
		$insert_query = <<<EOD
INSERT INTO o450 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0460') {
		$insert_query = <<<EOD
INSERT INTO o460 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0500') {
	    $campos[2] = dtaSPED($campos[2]);
		$insert_query = <<<EOD
INSERT INTO o500 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '0600') {
	    $campos[2] = dtaSPED($campos[2]);
		$insert_query = <<<EOD
INSERT INTO o600 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '1010') {
		$insert_query = <<<EOD
INSERT INTO l010 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '1100') {
	    $campos[4] = dtaSPED($campos[4]);
	    $campos[7] = dtaSPED($campos[7]);
	    $campos[9] = dtaSPED($campos[9]);
	    $campos[10] = dtaSPED($campos[10]);
		$insert_query = <<<EOD
INSERT INTO l100 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}', 
'{$campos[11]}', '{$campos[12]}'
 )
EOD;
		$db->query($insert_query);
		$ord1100 = $iord;
	  }
	  
	  if ($campos[1] == '1105') {
	    $campos[6] = dtaSPED($campos[6]);
		$insert_query = <<<EOD
INSERT INTO l105 VALUES(
'{$iord}', '{$ord1100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}'
 )
EOD;
		$db->query($insert_query);
	  }
	  
	  if ($campos[1] == '1200') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$insert_query = <<<EOD
INSERT INTO l200 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '1210') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$insert_query = <<<EOD
INSERT INTO l210 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '1300') {
	    $campos[3] = dtaSPED($campos[3]);
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$campos[11] = str_replace(',','.',str_replace('.','',$campos[11]));
		$insert_query = <<<EOD
INSERT INTO l300 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}',
'{$campos[8]}', '{$campos[9]}', '{$campos[10]}', '{$campos[11]}'
 )
EOD;
		$db->query($insert_query);
		$ord1300 = $iord;
	  }

	  if ($campos[1] == '1310') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$insert_query = <<<EOD
INSERT INTO l310 VALUES(
'{$iord}', '{$ord1300}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}',
'{$campos[8]}', '{$campos[9]}', '{$campos[10]}'
 )
EOD;
		$db->query($insert_query);
		$ord1310 = $iord;
	  }

	  if ($campos[1] == '1320') {
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$campos[11] = str_replace(',','.',str_replace('.','',$campos[11]));
		$insert_query = <<<EOD
INSERT INTO l320 VALUES(
'{$iord}', '{$ord1310}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}',
'{$campos[8]}', '{$campos[9]}', '{$campos[10]}', '{$campos[11]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '1350') {
		$insert_query = <<<EOD
INSERT INTO l350 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
		$ord1350 = $iord;
	  }

	  if ($campos[1] == '1360') {
	    $campos[3] = dtaSPED($campos[3]);
		$insert_query = <<<EOD
INSERT INTO l360 VALUES(
'{$iord}', '{$ord1350}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == '1370') {
		$insert_query = <<<EOD
INSERT INTO l370 VALUES(
'{$iord}', '{$ord1350}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }


	  if ($campos[1] == 'C100') {
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$campos[16] = str_replace(',','.',str_replace('.','',$campos[16]));
		$campos[18] = str_replace(',','.',str_replace('.','',$campos[18]));
		$campos[19] = str_replace(',','.',str_replace('.','',$campos[19]));
		$campos[20] = str_replace(',','.',str_replace('.','',$campos[20]));
		$campos[21] = str_replace(',','.',str_replace('.','',$campos[21]));
		$campos[22] = str_replace(',','.',str_replace('.','',$campos[22]));
		$campos[23] = str_replace(',','.',str_replace('.','',$campos[23]));
		$campos[24] = str_replace(',','.',str_replace('.','',$campos[24]));
		$campos[25] = str_replace(',','.',str_replace('.','',$campos[25]));
		$campos[26] = str_replace(',','.',str_replace('.','',$campos[26]));
		$campos[27] = str_replace(',','.',str_replace('.','',$campos[27]));
		$campos[28] = str_replace(',','.',str_replace('.','',$campos[28]));
		$campos[29] = str_replace(',','.',str_replace('.','',$campos[29]));
	    $campos[10] = dtaSPED($campos[10]);
	    $campos[11] = dtaSPED($campos[11]);
		$insert_query = <<<EOD
INSERT INTO C100 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}', '{$campos[15]}', '{$campos[16]}',
'{$campos[17]}', '{$campos[18]}', '{$campos[19]}', '{$campos[20]}',
'{$campos[21]}', '{$campos[22]}', '{$campos[23]}', '{$campos[24]}',
'{$campos[25]}', '{$campos[26]}', '{$campos[27]}', '{$campos[28]}', '{$campos[29]}'
 )
EOD;
		$db->query($insert_query);
		$ordC100 = $iord;
	  }

	  if ($campos[1] == 'C101') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$insert_query = <<<EOD
INSERT INTO C101 VALUES(
'{$iord}', '{$ordC100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'C110') {
		$insert_query = <<<EOD
INSERT INTO C110 VALUES(
'{$iord}', '{$ordC100}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
		$ordC110 = $iord;
	  }

	  if ($campos[1] == 'C113') {
	    $campos[9] = dtaSPED($campos[9]);
		$insert_query = <<<EOD
INSERT INTO C113 VALUES(
'{$iord}', '{$ordC110}', '{$campos[2]}', '{$campos[3]}',
'{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}'
 )
EOD;
		$db->query($insert_query);
	  }


	  if ($campos[1] == 'C114') {
	    $campos[6] = dtaSPED($campos[6]);
		$insert_query = <<<EOD
INSERT INTO C114 VALUES(
'{$iord}', '{$ordC110}', '{$campos[2]}', '{$campos[3]}',
'{$campos[4]}', '{$campos[5]}', '{$campos[6]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  
	  if ($campos[1] == 'C120') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$insert_query = <<<EOD
INSERT INTO C120 VALUES(
'{$iord}', '{$ordC100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}'
 )
EOD;
		$db->query($insert_query);
	  }


	  if ($campos[1] == 'C140') {
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$insert_query = <<<EOD
INSERT INTO C140 VALUES(
'{$iord}', '{$ordC100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}'
 )
EOD;
		$db->query($insert_query);
		$ordC140 = $iord;
	  }

	  if ($campos[1] == 'C141') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
	    $campos[3] = dtaSPED($campos[3]);
		$insert_query = <<<EOD
INSERT INTO C141 VALUES(
'{$iord}', '{$ordC140}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'C170') {
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$campos[16] = str_replace(',','.',str_replace('.','',$campos[16]));
		$campos[17] = str_replace(',','.',str_replace('.','',$campos[17]));
		$campos[18] = str_replace(',','.',str_replace('.','',$campos[18]));
		$campos[22] = str_replace(',','.',str_replace('.','',$campos[22]));
		$campos[23] = str_replace(',','.',str_replace('.','',$campos[23]));
		$campos[24] = str_replace(',','.',str_replace('.','',$campos[24]));
		$campos[26] = str_replace(',','.',str_replace('.','',$campos[26]));
		$campos[27] = str_replace(',','.',str_replace('.','',$campos[27]));
		$campos[28] = str_replace(',','.',str_replace('.','',$campos[28]));
		$campos[29] = str_replace(',','.',str_replace('.','',$campos[29]));
		$campos[30] = str_replace(',','.',str_replace('.','',$campos[30]));
		$campos[32] = str_replace(',','.',str_replace('.','',$campos[32]));
		$campos[33] = str_replace(',','.',str_replace('.','',$campos[33]));
		$campos[34] = str_replace(',','.',str_replace('.','',$campos[34]));
		$campos[35] = str_replace(',','.',str_replace('.','',$campos[35]));
		$campos[36] = str_replace(',','.',str_replace('.','',$campos[36]));
		$insert_query = <<<EOD
INSERT INTO C170 VALUES(
'{$iord}', '{$ordC100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}', '{$campos[15]}', '{$campos[16]}',
'{$campos[17]}', '{$campos[18]}', '{$campos[19]}', '{$campos[20]}',
'{$campos[21]}', '{$campos[22]}', '{$campos[23]}', '{$campos[24]}',
'{$campos[25]}', '{$campos[26]}', '{$campos[27]}', '{$campos[28]}', '{$campos[29]}',
'{$campos[30]}', '{$campos[31]}', '{$campos[32]}', '{$campos[33]}', '{$campos[34]}',
'{$campos[35]}', '{$campos[36]}', '{$campos[37]}'
 )
EOD;
		$db->query($insert_query);
		$ordC170 = $iord;
	  }

	  if ($campos[1] == 'C171') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
	    //$campos[3] = dtaSPED($campos[3]);
		$insert_query = <<<EOD
INSERT INTO C171 VALUES(
'{$iord}', '{$ordC170}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'C173') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = dtaSPED($campos[4]);
		$campos[5] = dtaSPED($campos[5]);
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$insert_query = <<<EOD
INSERT INTO C173 VALUES(
'{$iord}', '{$ordC170}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'C174') {
		$insert_query = <<<EOD
INSERT INTO C174 VALUES(
'{$iord}', '{$ordC170}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'C175') {
		$insert_query = <<<EOD
INSERT INTO C175 VALUES(
'{$iord}', '{$ordC170}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'C176') {
		$campos[5] = dtaSPED($campos[5]);
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		if (!isset($campos[10])) $campos[10] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[11])) $campos[11] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[12])) $campos[12] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[13])) $campos[13] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[14])) $campos[14] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[15])) $campos[15] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[16])) $campos[16] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[17])) $campos[17] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[18])) $campos[18] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[19])) $campos[19] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[20])) $campos[20] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[21])) $campos[21] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[22])) $campos[22] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[23])) $campos[23] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[24])) $campos[24] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[25])) $campos[25] = ''; // Somente a partir de 01/01/2017
		if (!isset($campos[26])) $campos[26] = ''; // Somente a partir de 01/01/2017
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$campos[16] = str_replace(',','.',str_replace('.','',$campos[16]));
		$campos[17] = str_replace(',','.',str_replace('.','',$campos[17]));
		$insert_query = <<<EOD
INSERT INTO C176 VALUES(
'{$iord}', '{$ordC170}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
 '{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', 
 '{$campos[10]}', '{$campos[11]}', '{$campos[12]}', '{$campos[13]}', '{$campos[14]}',
 '{$campos[15]}', '{$campos[16]}', '{$campos[17]}', '{$campos[18]}', '{$campos[19]}',
 '{$campos[20]}', '{$campos[21]}', '{$campos[22]}', '{$campos[23]}', '{$campos[24]}',
 '{$campos[25]}', '{$campos[26]}'
 )
EOD;
		$db->query($insert_query);
	  }
  
	  if ($campos[1] == 'C190') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$campos[11] = str_replace(',','.',str_replace('.','',$campos[11]));
		$insert_query = <<<EOD
INSERT INTO C190 VALUES(
'{$iord}', '{$ordC100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}'
 )
EOD;
		$db->query($insert_query);
		$ordC190 = $iord;
	  }

	  if ($campos[1] == 'C195') {
		$insert_query = <<<EOD
INSERT INTO C195 VALUES(
'{$iord}', '{$ordC190}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
		$ordC195 = $iord;
	  }

	  if ($campos[1] == 'C197') {
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$insert_query = <<<EOD
INSERT INTO C197 VALUES(
'{$iord}', '{$ordC190}', '{$ordC195}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  
	  if ($campos[1] == 'C400') {
		$insert_query = <<<EOD
INSERT INTO C400 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
		$ordC400 = $iord;
	  }

	  if ($campos[1] == 'C405') {
	    $campos[2] = dtaSPED($campos[2]);
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$insert_query = <<<EOD
INSERT INTO C405 VALUES(
'{$iord}', '{$ordC400}','{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}'
 )
EOD;
		$db->query($insert_query);
		$ordC405 = $iord;
	  }


	  if ($campos[1] == 'C410') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$insert_query = <<<EOD
INSERT INTO C410 VALUES(
'{$iord}', '{$ordC405}','{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'C420') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$insert_query = <<<EOD
INSERT INTO C420 VALUES(
'{$iord}', '{$ordC405}','{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'C460') {
	    $campos[5] = dtaSPED($campos[5]);
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$insert_query = <<<EOD
INSERT INTO C460 VALUES(
'{$iord}', '{$ordC405}','{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}' )
EOD;
		$db->query($insert_query);
		$ordC460 = $iord;
	  }

	  if ($campos[1] == 'C470') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$campos[11] = str_replace(',','.',str_replace('.','',$campos[11]));
		$insert_query = <<<EOD
INSERT INTO C470 VALUES(
'{$iord}', '{$ordC460}','{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}', '{$campos[11]}' )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'C490') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$insert_query = <<<EOD
INSERT INTO C490 VALUES(
'{$iord}', '{$ordC405}','{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}',
'{$campos[6]}', '{$campos[7]}', '{$campos[8]}' )
EOD;
		$db->query($insert_query);
	  }


	  if ($campos[1] == 'C500') {
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$campos[16] = str_replace(',','.',str_replace('.','',$campos[16]));
		$campos[17] = str_replace(',','.',str_replace('.','',$campos[17]));
		$campos[18] = str_replace(',','.',str_replace('.','',$campos[18]));
		$campos[19] = str_replace(',','.',str_replace('.','',$campos[19]));
		$campos[20] = str_replace(',','.',str_replace('.','',$campos[20]));
		$campos[21] = str_replace(',','.',str_replace('.','',$campos[21]));
		$campos[22] = str_replace(',','.',str_replace('.','',$campos[22]));
		$campos[24] = str_replace(',','.',str_replace('.','',$campos[24]));
		$campos[25] = str_replace(',','.',str_replace('.','',$campos[25]));
	    $campos[11] = dtaSPED($campos[11]);
	    $campos[12] = dtaSPED($campos[12]);
		$insert_query = <<<EOD
INSERT INTO C500 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}', '{$campos[15]}', '{$campos[16]}',
'{$campos[17]}', '{$campos[18]}', '{$campos[19]}', '{$campos[20]}',
'{$campos[21]}', '{$campos[22]}', '{$campos[23]}', '{$campos[24]}',
'{$campos[25]}', '{$campos[26]}', '{$campos[27]}'
 )
EOD;
		$db->query($insert_query);
		$ordC500 = $iord;
	  }

	  if ($campos[1] == 'C590') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$insert_query = <<<EOD
INSERT INTO C590 VALUES(
'{$iord}', '{$ordC500}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}'
 )
EOD;
		$db->query($insert_query);
	  }
	  
	  
	  if ($campos[1] == 'C800') {
	    $campos[5] = dtaSPED($campos[5]);
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$insert_query = <<<EOD
INSERT INTO C800 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}', '{$campos[15]}', '{$campos[16]}', '{$campos[17]}'
 )
EOD;
		$db->query($insert_query);
		$ordC800 = $iord;
	  }
	  
	  if ($campos[1] == 'C850') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$insert_query = <<<EOD
INSERT INTO C850 VALUES(
'{$iord}', '{$ordC800}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
	  }
	  
	  if ($campos[1] == 'C860') {
	    $campos[4] = dtaSPED($campos[4]);
		$insert_query = <<<EOD
INSERT INTO C860 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}'
 )
EOD;
		$db->query($insert_query);
		$ordC860 = $iord;
	  }
	  
	  if ($campos[1] == 'C890') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$insert_query = <<<EOD
INSERT INTO C890 VALUES(
'{$iord}', '{$ordC860}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  
	  if ($campos[1] == 'D100') {
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$campos[16] = str_replace(',','.',str_replace('.','',$campos[16]));
		$campos[18] = str_replace(',','.',str_replace('.','',$campos[18]));
		$campos[19] = str_replace(',','.',str_replace('.','',$campos[19]));
		$campos[20] = str_replace(',','.',str_replace('.','',$campos[20]));
		$campos[21] = str_replace(',','.',str_replace('.','',$campos[21]));
	    $campos[11] = dtaSPED($campos[11]);
	    $campos[12] = dtaSPED($campos[12]);
		$insert_query = <<<EOD
INSERT INTO D100 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}', '{$campos[15]}', '{$campos[16]}',
'{$campos[17]}', '{$campos[18]}', '{$campos[19]}', '{$campos[20]}',
'{$campos[21]}', '{$campos[22]}', '{$campos[23]}'
 )
EOD;
		$db->query($insert_query);
		$ordD100 = $iord;
	  }

	  if ($campos[1] == 'D110') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$insert_query = <<<EOD
INSERT INTO D110 VALUES(
'{$iord}', '{$ordD100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'D120') {
		$insert_query = <<<EOD
INSERT INTO D120 VALUES(
'{$iord}', '{$ordD100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'D190') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$insert_query = <<<EOD
INSERT INTO D190 VALUES(
'{$iord}', '{$ordD100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}'
 )
EOD;
		$db->query($insert_query);
		$ordD190 = $iord;
	  }

	  if ($campos[1] == 'D195') {
		$insert_query = <<<EOD
INSERT INTO D195 VALUES(
'{$iord}', '{$ordD190}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
		$ordD195 = $iord;
	  }

	  if ($campos[1] == 'D197') {
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$insert_query = <<<EOD
INSERT INTO D197 VALUES(
'{$iord}', '{$ordD190}', '{$ordD195}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
	  }



	  if ($campos[1] == 'D500') {
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$campos[16] = str_replace(',','.',str_replace('.','',$campos[16]));
		$campos[17] = str_replace(',','.',str_replace('.','',$campos[17]));
		$campos[18] = str_replace(',','.',str_replace('.','',$campos[18]));
		$campos[19] = str_replace(',','.',str_replace('.','',$campos[19]));
		$campos[21] = str_replace(',','.',str_replace('.','',$campos[21]));
		$campos[22] = str_replace(',','.',str_replace('.','',$campos[22]));
	    $campos[10] = dtaSPED($campos[10]);
	    $campos[11] = dtaSPED($campos[11]);
		$insert_query = <<<EOD
INSERT INTO D500 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}', '{$campos[15]}', '{$campos[16]}',
'{$campos[17]}', '{$campos[18]}', '{$campos[19]}', '{$campos[20]}',
'{$campos[21]}', '{$campos[22]}', '{$campos[23]}', '{$campos[24]}'
 )
EOD;
		$db->query($insert_query);
		$ordD500 = $iord;
	  }

	  if ($campos[1] == 'D590') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$insert_query = <<<EOD
INSERT INTO D590 VALUES(
'{$iord}', '{$ordD500}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'E100') {
	    $campos[2] = dtaSPED($campos[2]);
	    $campos[3] = dtaSPED($campos[3]);
		$insert_query = <<<EOD
INSERT INTO E100 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
		$ordE100 = $iord;
	  }

	  if ($campos[1] == 'E110') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$campos[11] = str_replace(',','.',str_replace('.','',$campos[11]));
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$insert_query = <<<EOD
INSERT INTO E110 VALUES(
'{$iord}', '{$ordE100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}', '{$campos[15]}'
 )
EOD;
		$db->query($insert_query);
		$ordE110 = $iord;
	  }
  
	  if ($campos[1] == 'E111') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$insert_query = <<<EOD
INSERT INTO E111 VALUES(
'{$iord}', '{$ordE110}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
		$ordE111 = $iord;
	  }
 
	  if ($campos[1] == 'E112') {
		$insert_query = <<<EOD
INSERT INTO E112 VALUES(
'{$iord}', '{$ordE111}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}'
 )
EOD;
		$db->query($insert_query);
	  }
 
	  if ($campos[1] == 'E113') {
	    $campos[7] = dtaSPED($campos[7]);
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$insert_query = <<<EOD
INSERT INTO E113 VALUES(
'{$iord}', '{$ordE111}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}'
 )
EOD;
		$db->query($insert_query);
	  }
 
	  if ($campos[1] == 'E116') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
	    $campos[4] = dtaSPED($campos[4]);
		if (trim($campos[10]) == '') $campos[10] = ''; // campo mes_ref não tem nos EFDs antigos e não vem setado, vem com mudança de linha 0d0a
		$insert_query = <<<EOD
INSERT INTO E116 VALUES(
'{$iord}', '{$ordE110}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'E200') {
	    $campos[3] = dtaSPED($campos[3]);
	    $campos[4] = dtaSPED($campos[4]);
		$insert_query = <<<EOD
INSERT INTO E200 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
		$ordE200 = $iord;
	  }

	  if ($campos[1] == 'E210') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$campos[11] = str_replace(',','.',str_replace('.','',$campos[11]));
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$insert_query = <<<EOD
INSERT INTO E210 VALUES(
'{$iord}', '{$ordE200}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}', '{$campos[15]}'
 )
EOD;
		$db->query($insert_query);
		$ordE210 = $iord;
	  }
  
	  if ($campos[1] == 'E250') {
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
	    $campos[4] = dtaSPED($campos[4]);
		if (trim($campos[10]) == '') $campos[10] = ''; // campo mes_ref não tem nos EFDs antigos e não vem setado, vem com mudança de linha 0d0a
		$insert_query = <<<EOD
INSERT INTO E250 VALUES(
'{$iord}', '{$ordE210}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  
	  if ($campos[1] == 'E300') {
	    $campos[3] = dtaSPED($campos[3]);
	    $campos[4] = dtaSPED($campos[4]);
		$insert_query = <<<EOD
INSERT INTO E300 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
		$ordE300 = $iord;
	  }

	  if ($campos[1] == 'E310') {
		$campos[2] = str_replace(',','.',str_replace('.','',$campos[2]));
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$campos[11] = str_replace(',','.',str_replace('.','',$campos[11]));
		$campos[12] = str_replace(',','.',str_replace('.','',$campos[12]));
		$campos[13] = str_replace(',','.',str_replace('.','',$campos[13]));
		$campos[14] = str_replace(',','.',str_replace('.','',$campos[14]));
		$campos[15] = str_replace(',','.',str_replace('.','',$campos[15]));
		$campos[16] = str_replace(',','.',str_replace('.','',$campos[16]));
		$campos[17] = str_replace(',','.',str_replace('.','',$campos[17]));
		$campos[18] = str_replace(',','.',str_replace('.','',$campos[18]));
		$campos[19] = str_replace(',','.',str_replace('.','',$campos[19]));
		$campos[20] = str_replace(',','.',str_replace('.','',$campos[20]));
		$campos[21] = str_replace(',','.',str_replace('.','',$campos[21]));
		$campos[22] = str_replace(',','.',str_replace('.','',$campos[22]));
		$insert_query = <<<EOD
INSERT INTO E310 VALUES(
'{$iord}', '{$ordE300}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', 
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}', '{$campos[12]}',
'{$campos[13]}', '{$campos[14]}', '{$campos[15]}', '{$campos[16]}', '{$campos[17]}', '{$campos[18]}',
'{$campos[19]}', '{$campos[20]}', '{$campos[21]}', '{$campos[22]}'
 )
EOD;
		$db->query($insert_query);
		$ordE310 = $iord;
	  }
  
	  if ($campos[1] == 'E311') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$insert_query = <<<EOD
INSERT INTO E311 VALUES(
'{$iord}', '{$ordE310}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
		$ordE311 = $iord;
	  }
  
	  if ($campos[1] == 'E312') {
		$insert_query = <<<EOD
INSERT INTO E312 VALUES(
'{$iord}', '{$ordE311}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'E313') {
	    $campos[8] = dtaSPED($campos[8]);
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$insert_query = <<<EOD
INSERT INTO E313 VALUES(
'{$iord}', '{$ordE311}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}',
'{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}'
 )
EOD;
		$db->query($insert_query);
	  }
	  
	  if ($campos[1] == 'E316') {
	    $campos[4] = dtaSPED($campos[4]);
		$insert_query = <<<EOD
INSERT INTO E316 VALUES(
'{$iord}', '{$ordE310}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}', '{$campos[5]}', '{$campos[6]}',
'{$campos[7]}', '{$campos[8]}', '{$campos[9]}', '{$campos[10]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  
	  if ($campos[1] == 'G110') {
	    $campos[2] = dtaSPED($campos[2]);
	    $campos[3] = dtaSPED($campos[3]);
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$insert_query = <<<EOD
INSERT INTO G110 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}'
 )
EOD;
		$db->query($insert_query);
		$ordG110 = $iord;
	  }

	  if ($campos[1] == 'G125') {
	    $campos[3] = dtaSPED($campos[3]);
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[10] = str_replace(',','.',str_replace('.','',$campos[10]));
		$insert_query = <<<EOD
INSERT INTO G125 VALUES(
'{$iord}', '{$ordG110}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}'
 )
EOD;
		$db->query($insert_query);
		$ordG125 = $iord;
	  }

	  if ($campos[1] == 'G126') {
	    $campos[2] = dtaSPED($campos[2]);
	    $campos[3] = dtaSPED($campos[3]);
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		$campos[7] = str_replace(',','.',str_replace('.','',$campos[7]));
		$campos[8] = str_replace(',','.',str_replace('.','',$campos[8]));
		$campos[9] = str_replace(',','.',str_replace('.','',$campos[9]));
		$insert_query = <<<EOD
INSERT INTO G126 VALUES(
'{$iord}', '{$ordG125}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'G130') {
	    $campos[8] = dtaSPED($campos[8]);
		$insert_query = <<<EOD
INSERT INTO G130 VALUES(
'{$iord}', '{$ordG125}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}'
 )
EOD;
		$db->query($insert_query);
		$ordG130 = $iord;
	  }

	  if ($campos[1] == 'G140') {
		$insert_query = <<<EOD
INSERT INTO G140 VALUES(
'{$iord}', '{$ordG130}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
	  }


	  if ($campos[1] == 'H005') {
	    $campos[2] = dtaSPED($campos[2]);
		$campos[3] = str_replace(',','.',str_replace('.','',$campos[3]));
		$insert_query = <<<EOD
INSERT INTO H005 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}'
 )
EOD;
		$db->query($insert_query);
		$ordH005 = $iord;
	  }

	  if ($campos[1] == 'H010') {
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$campos[5] = str_replace(',','.',str_replace('.','',$campos[5]));
		$campos[6] = str_replace(',','.',str_replace('.','',$campos[6]));
		if (!isset($campos[11])) $campos[11] = ''; // Somente a partir de 01/01/2015
		$campos[11] = str_replace(',','.',str_replace('.','',$campos[11]));
		$insert_query = <<<EOD
INSERT INTO H010 VALUES(
'{$iord}', '{$ordH005}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}', '{$campos[7]}', '{$campos[8]}',
'{$campos[9]}', '{$campos[10]}', '{$campos[11]}'
 )
EOD;
		$db->query($insert_query);
	  }

	  if ($campos[1] == 'K100') {
	    $campos[2] = dtaSPED($campos[2]);
	    $campos[3] = dtaSPED($campos[3]);
		$insert_query = <<<EOD
INSERT INTO K100 VALUES(
'{$iord}', '{$campos[2]}', '{$campos[3]}'
 )
EOD;
		$db->query($insert_query);
		$ordK100 = $iord;
	  }

	  if ($campos[1] == 'K200') {
	    $campos[2] = dtaSPED($campos[2]);
		$campos[4] = str_replace(',','.',str_replace('.','',$campos[4]));
		$insert_query = <<<EOD
INSERT INTO K200 VALUES(
'{$iord}', '{$ordK100}', '{$campos[2]}', '{$campos[3]}', '{$campos[4]}',
'{$campos[5]}', '{$campos[6]}'
 )
EOD;
		$db->query($insert_query);
	  }

 	  if (++$ilidos % 50000 == 0) {
		if ($pr->ldebug) {
		  wecho("\nLidas {$ilidos} linhas do arquivo {$arquivo_efd} em ");
		  wecho($pr->tempo() . " segundos");
		} else wecho("*");
		flush();
		$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
		$db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
      }
	}
    fclose($handle);

	// Antes de finalizar, grava a contagem de registros para este arquivo
	foreach($a_conta_reg as $indice => $valor) {
		$db->query("INSERT INTO conta_reg VALUES ('{$arquivo_efd}', '{$aaaamm}', '{$indice}', {$valor});");
	}

	if ($pr->ldebug) {
	  wecho("\nParte 1 - Leitura finalizada: {$ilidos} linhas do arquivo {$arquivo_efd} em ");
	  wecho($pr->tempo() . " segundos\n\n");
	} else wecho("*");
	flush();
	$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
	
   // Leitura de Arquivo Finalizada
}
  
?>