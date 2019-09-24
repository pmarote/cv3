<?php

$pr->aud_registra(new PrMenu("lasimca_dados", "LASIM_CA", "Dados da LASIMCA", "lasimca"));

function lasimca_dados() {

  global $pr;

  $pr->inicia_excel('LASIMCA_Dados_do_LASIMCA');

  $form_final = '
	$this->excel_zoom_visualizacao(75);
	$this->excel_orientacao(2);		// paisagem
';




  $sql = "
SELECT * FROM q999;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 9999",
		'qtd_lin_9' => "Quantidade total de linhas do arquivo digital"
);
  $pr->abre_excel_sql("q999", "9999 - ENCERRAMENTO DO ARQUIVO DIGITAL", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM q990;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 9990",
		'qtd_lin_9' => "Quantidade total de linhas do Bloco 9"
);
  $pr->abre_excel_sql("q990", "9990 - ENCERRAMENTO DO BLOCO 9", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM q900;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 9900",
		'reg_blc' => "Registro que será totalizado no próximo campo",
		'qtd_reg_blc' => "Total de registros do tipo informado no campo anterior"
);
  $pr->abre_excel_sql("q900", "9900 - REGISTROS DO ARQUIVO", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM q001;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 9001",
		'ind_mov' => "Indicador de movimento:
0- Bloco com dados informados"
);
  $pr->abre_excel_sql("q001", "9001 - ABERTURA DO BLOCO 9", $sql, $col_format, $cabec, $form_final);
 
  $sql = "
SELECT * FROM s990;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 5990",
		'qtd_lin_c' => "Quantidade total de linhas do Bloco 5"
);
  $pr->abre_excel_sql("s990", "5990 - ENCERRAMENTO DO BLOCO 5", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM s350;
";
  $col_format = array(
	"A:B" => "0",
	"C:D" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5330",
	'Ords315' => "Número da Linha do Registro 5315",
	'valor_bc' => "Base de Cálculo da operação de saída",
	'icms_deb' => "ICMS debitado da operação de saída",
	'num_decl_exp_ind' => "Número da Declaração para Despacho de Exportação ou Declaração Simplificada de Exportação do estabelecimento exportador"
);
  $pr->abre_excel_sql("s350", "5350 - OPERAÇÕES NÃO GERADORAS DE CRÉDITO ACUMULADO – FICHA 6F", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM s340;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5340",
	'Ords335' => "Número da Linha do Registro 5335",
	'data_doc_ind' => "Data de emissão do documento fiscal do exportador",
	'num_doc_ind' => "Número do documento fiscal do exportador",
	'ser_doc_ind' => "Série do documento fiscal do exportador",
	'num_decl_exp_ind' => "Número da Declaração para Despacho de Exportação ou Declaração Simplificada de Exportação do estabelecimento exportador"
);
  $pr->abre_excel_sql("s340", "5340 - DADOS DA EXPORTAÇÃO INDIRETA COMPROVADA- FICHA 5H", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM s335;
";
  $col_format = array(
	"A:C" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5335",
	'Ords325' => "Número da Linha do Registro 5325",
	'num_decl_exp' => "Número da Declaração para Despacho de Exportação ou Declaração Simplificada de Exportação",
	'comp_oper' => "Comprovação da Operação – preencher com:
0 – Sim
1 – Não"
);
  $pr->abre_excel_sql("s335", "5335 - OPERAÇÕES GERADORAS APURADAS NA FICHA 6C (Art71,III-Export) OU 6D (Art71,III-ZFM) ", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM s330;
";
  $col_format = array(
	"A:B" => "0",
	"C:D" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5330",
	'Ords325' => "Número da Linha do Registro 5325",
	'valor_bc' => "Base de Cálculo da operação de saída",
	'icms_deb' => "ICMS debitado na operação de saída"
);
  $pr->abre_excel_sql("s330", "5330 - OPERAÇÕES GERADORAS APURADAS NAS FICHAS 6A (Art71,I) OU 6B  (Art71,II)", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM s325;
";
  $col_format = array(
	"A:C" => "0",
	"D:E" => "#.##0,0000",
	"F:G" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5325",
	'Ords315' => "Número da Linha do Registro 5315",
	'cod_legal' => "Código do Enquadramento Legal conforme registro 0300.",
	'iva_utilizado' => "IVA - Índice de Valor Acrescido considerado no cálculo do custo estimado da operação ou prestação geradora conforme a legislação vigente.",
	'per_med_icms' => "Percentual Médio de Crédito do Imposto – é a alíquota média das entradas das mercadorias, insumos e serviços recebidos relacionados às saídas geradoras de crédito acumulado, obtida conforme legislação vigente, informar utilizando o formato percentual.",
	'cred_est_icms' => "Crédito estimado do ICMS",
	'icms_gera' => "Crédito Acumulado Gerado na operação"
);
  $pr->abre_excel_sql("s325", "5325 - OPERAÇÕES GERADORAS DE CRÉDITO ACUMULADO", $sql, $col_format, $cabec, $form_final);


  
  $sql = "
SELECT * FROM s320;
";
  $col_format = array(
	"A:C" => "0",
);
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5320",
	'Ords315' => "Número da Linha do Registro 5315",
	'dt_sai' => "Data da emissão do documento fiscal que acobertou a operação original.",
	'tip_doc' => "Tipo do documento que acobertou a operação original, utilizar a coluna Código Chave da tabela 4.1.",
	'ser' => "Série do documento que acobertou a operação original.",
	'num_doc' => "Número do documento que acobertou a operação original"
);
  $pr->abre_excel_sql("s320", "5320 - DEVOLUÇÃO DE SAÍDA", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM s315;
";
  $col_format = array(
	"A:A" => "0",
	"F:F" => "0",
	"G:I" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5315",
	'dt_emissao' => "Data da emissão do documento fiscal.",
	'tip_doc' => "Tipo do documento conforme a coluna Código Chave da tabela 4.1.",
	'ser' => "Série do documento.",
	'num_doc' => "Número do documento",
	'cod_part' => "Código do participante conforme registro 0150.",
	'valor_sai' => "Valor de Saída",
	'perc_crdout' => "Percentual de Crédito Outorgado relativo ao item",
	'valor_crdout' => "Valor do Crédito Outorgado relativo ao item"
);
  $pr->abre_excel_sql("s315", "5315 - OPERAÇÕES DE SAÍDA", $sql, $col_format, $cabec, $form_final);
    
  
  $sql = "
SELECT * FROM s001;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 5001",
		'ind_mov' => "Indicador de movimento:
0- Bloco com dados informados.
1- Bloco com dados informados"
);
  $pr->abre_excel_sql("s001", "5001 - ABERTURA DO BLOCO 5", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM o990;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0990",
		'qtd_lin_0' => "Quantidade total de linhas do Bloco 0"
);
  $pr->abre_excel_sql("o990", "0990 - ENCERRAMENTO DO BLOCO 0", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o300;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0300",
	'DESC' => "Informar um dos seguintes códigos, relativo à hipótese de geração, conforme o inciso do artigo 71 do RICMS/00 :
1 – 'Inciso I - Operações interestaduais com alíquota 7%'
2 – 'Inciso I - Operações interestaduais com alíquota 12%'
3 – 'Inciso I - Operações internas com alíquota 7%'
4 – 'Inciso I - Operações internas'
5 – 'Inciso I - Outras'
6 – 'Inciso II - Redução de Base de Cálculo'
7 – 'Inciso III - Saídas sem pagamento de Imposto – Exportação'
8 – 'Inciso III - Saídas sem pagamento de Imposto – Exportação Indireta'
9 – 'Inciso III - Saídas sem pagamento de Imposto – ZF Manaus'
10 – 'Inciso III - Saídas sem pagamento de Imposto – Diferimento'
11 – 'Inciso III - Saídas sem pagamento de Imposto – Isenção'
12 – 'Inciso III - Saídas sem pagamento de Imposto – ST'
13 – 'Inciso III - Saídas sem pagamento de Imposto – Outras'",
	'ANEX' => "Informar o Anexo do RICMS referente ao enquadramento legal da operação ou prestação geradora do crédito acumulado do ICMS, se existir.",
	'ART' => "Informar o Artigo do RICMS referente ao enquadramento legal da operação ou prestação geradora do crédito acumulado do ICMS.",
	'INC' => "Informar o Inciso do RICMS referente ao enquadramento legal da operação ou prestação geradora do crédito acumulado do ICMS, se existir.",
	'ALIN' => "Informar a Alínea do RICMS referente ao enquadramento legal da operação ou prestação geradora do crédito acumulado do ICMS, se existir.",
	'PRG' => "Informar o Parágrafo do RICMS referente ao enquadramento legal da operação ou prestação geradora do crédito acumulado do ICMS, se existir.",
	'ITM' => "Informar o Item do RICMS referente ao enquadramento legal da operação ou prestação geradora do crédito acumulado do ICMS, se existir.",
	'LTR' => "Informar a letra do RICMS referente ao enquadramento legal da operação ou prestação geradora do crédito acumulado do ICMS, se existir.",
	'OBS' => "Informação complementar referente ao enquadramento legal da operação ou prestação geradora do crédito acumulado do ICMS."
);
  $pr->abre_excel_sql("o300", "0300 - ENQUAD LEGAL DA OPERAÇÃO PRESTAÇÃO GERADORA DE CRÉD ACUMULADO DO ICMS", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o150;
";
  $col_format = array(
	"A:A" => "0",
	"B:B" => "0",
	"E:F" => "0",
	"H:H" => "00000000"
);
  $cabec = array(
	'OrdC150' => "Número da Linha do Registro C150",
	'cod_part' => "Código de identificação do participante no arquivo",
	'nome' => "Razão social ou nome do participante",
	'cod_pais' => "Código do país do participante, conforme a tabela indicada na Tabela de Países do Banco Central do Brasil: www.bcb.gov.br",
	'cnpj' => "CNPJ ou CPF do participante",
	'ie' => "Inscrição Estadual do participante",
	'uf' => "Sigla da unidade da federação do participante",
	'cep' => "Código de Endereçamento Postal",
	'end' => "Logradouro e endereço do imóvel",
	'num' => "Número do imóvel",
	'compl' => "Dados complementares do endereço",
	'bairro' => "Bairro em que o imóvel está situado",
	'cod_mun' => "Código do município, conforme a tabela IBGE",
	'fone' => "Número do telefone",
);
  $pr->abre_excel_sql("o150", "0150 - CADASTRO DE PARTICIPANTES DE OPERAÇÕES E PRESTAÇÕES", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM o001;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0001",
		'ind_mov' => "Indicador de movimento:
0- Bloco com dados informados.
1- Bloco com dados informados"
);
  $pr->abre_excel_sql("o001", "0001 - ABERTURA DO BLOCO 0", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o000;
";
  $col_format = array(
	"G:L" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0000",
	'lasimca' => "Texto fixo contendo 'LASIMCA'",
	'cod_ver' => "Código da versão do leiaute conforme a Tabela 3.1",
	'cod_fin' => "Código da finalidade do arquivo conforme a Tabela 3.2",
	'periodo' => "Periodo das informações contidas no arquivo.",
	'nome' => "Nome empresarial do estabelecimento informante.",
	'cnpj' => "Número de inscrição no CNPJ do estabelecimento informante.",
	'ie' => "Inscrição Estadual do estabelecimento informante",
	'cnae' => "CNAE do contribuinte informante.",
	'cod_mun' => "Código do município do domicílio fiscal da entidade, conforme a tabela IBGE",
	'ie_intima' => "Inscrição Estadual do Estabelecimento paulista gerador de crédito acumulado notificado, por intimação específica, a entregar arquivo."
);
  $pr->abre_excel_sql("o000", "0000 - ABERTURA DO ARQUIVO DIGITAL E IDENTIFICAÇÃO DO CONTRIBUINTE", $sql, $col_format, $cabec, $form_final);


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
SELECT '20' || substr(s350.ord, 1, 4) AS aaaamm, s315.ord AS ord, 
    s315.dt_emissao AS dt_emissao, tab4_1.codigo AS modelo, s315.ser AS ser, s315.num_doc AS num_doc, s315.cod_part AS cod_part, s315.valor_sai AS valor_sai, 
    s315.perc_crdout AS perc_crdout, s315.valor_crdout AS valor_crdout, 
    s350.valor_bc AS valor_bc, s350.icms_deb AS icms_deb, s350.num_decl_exp_ind AS num_decl_exp_ind,
    o150.nome AS nome, o150.cod_pais, o150.cnpj AS cnpj, o150.ie AS ie, o150.uf AS uf, o150.cep AS cep, o150.end AS end, o150.num AS num, o150.compl AS compl, o150.bairro AS bairro, o150.cod_mun AS cod_mun, o150.fone AS fone,
    round(s350.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s350.ord / 10000000 + 0.5) * 10000000 AS ordmax
    FROM s350
    LEFT OUTER JOIN s315 ON s315.ord = s350.Ords315
    LEFT OUTER JOIN o150 ON o150.cod_part = s315.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
    LEFT OUTER JOIN tab4_1 ON tab4_1.cod_chv = s315.tip_doc;
";
  $col_format = array(
	"B:B" => "0",
	"G:G" => "0",
	"H:L" => "#.##0,00",
	"P:Q" => "0",
	"Y:AA" => "0"
);
  $cabec = array(
	'aaaamm' => 'Ano/Mês',
	'Ord5315' => "Número da Linha do Registro 5315",
	'dt_emissao' => "Data da emissão do documento fiscal.",
	'modelo' => "Modelo do documento conforme a coluna Código da tabela 4.1.",
	'ser' => "Série do documento.",
	'num_doc' => "Número do documento",
	'cod_part' => "Código do participante conforme registro 0150.",
	'valor_sai' => "Valor de Saída",
	'perc_crdout' => "Percentual de Crédito Outorgado relativo ao item",
	'valor_crdout' => "Valor do Crédito Outorgado relativo ao item",
	'valor_bc' => "Base de Cálculo da operação de saída",
	'icms_deb' => "ICMS debitado na operação de saída",
	'num_decl_exp_ind' => "Número da Declaração para Despacho de Exportação ou Declaração Simplificada de Exportação do estabelecimento exportador",
	'nome' => "Razão social ou nome do participante",
	'cod_pais' => "Código do país do participante, conforme a tabela indicada na Tabela de Países do Banco Central do Brasil: www.bcb.gov.br",
	'cnpj' => "CNPJ ou CPF do participante",
	'ie' => "Inscrição Estadual do participante",
	'uf' => "Sigla da unidade da federação do participante",
	'cep' => "Código de Endereçamento Postal",
	'end' => "Logradouro e endereço do imóvel",
	'num' => "Número do imóvel",
	'compl' => "Dados complementares do endereço",
	'bairro' => "Bairro em que o imóvel está situado",
	'cod_mun' => "Código do município, conforme a tabela IBGE",
	'fone' => "Número do telefone",
	'ordmin' => "Valor auxiliar para controle interno",
	'ordmax' => "Valor auxiliar para controle interno"
);
  $pr->abre_excel_sql("OperNAOGerad", "Operações Não Geradoras de Crédito Acumulado 5350 combinado com 5315", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT '20' || substr(s325.ord, 1, 4) AS aaaamm, s315.ord AS ord, 
    s315.dt_emissao AS dt_emissao, tab4_1.codigo AS modelo, s315.ser AS ser, s315.num_doc AS num_doc, s315.cod_part AS cod_part, s315.valor_sai AS valor_sai, 
    s315.perc_crdout AS perc_crdout, s315.valor_crdout AS valor_crdout, 
    s330.valor_bc AS valor_bc, s330.icms_deb AS icms_deb, 
    s335.num_decl_exp AS num_decl_exp, s335.comp_oper AS comp_oper, 
    s340.data_doc_ind AS data_doc_ind, s340.num_doc_ind AS num_doc_ind, s340.ser_doc_ind AS ser_doc_ind, s340.num_decl_exp_ind AS num_decl_exp_ind, 
    substr('0' || s325.cod_legal, -2) || ' - ' || o300.art || ' - ' || o300.inc  || ' - ' || o300.anex || ' - ' || o300.obs AS cod_legal, iva_utilizado, per_med_icms, cred_est_icms, icms_gera,
    o150.nome AS nome, o150.cod_pais, o150.cnpj AS cnpj, o150.ie AS ie, o150.uf AS uf, o150.cep AS cep, o150.end AS end, o150.num AS num, o150.compl AS compl, o150.bairro AS bairro, o150.cod_mun AS cod_mun, o150.fone AS fone,
    round(s325.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s325.ord / 10000000 + 0.5) * 10000000 AS ordmax
    FROM s325
    LEFT OUTER JOIN s330 ON s330.Ords325 = s325.ord
    LEFT OUTER JOIN s335 ON s335.Ords325 = s325.ord
    LEFT OUTER JOIN s340 ON s340.Ords335 = s335.ord
    LEFT OUTER JOIN s315 ON s315.ord = s325.Ords315
    LEFT OUTER JOIN o150 ON o150.cod_part = s315.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
    LEFT OUTER JOIN o300 ON o300.cod_legal = s325.cod_legal AND o300.ord > ordmin AND o300.ord < ordmax
    LEFT OUTER JOIN tab4_1 ON tab4_1.cod_chv = s315.tip_doc;
";
  $col_format = array(
	"B:B" => "0",
	"G:G" => "0",
	"H:L" => "#.##0,00",
	"P:R" => "0",
	"T:W" => "#.##0,00",
	"Z:AA" => "0",
	"AI:AK" => "0"
);
  $cabec = array(
	'aaaamm' => 'Ano/Mês',
	'Ord5315' => "Número da Linha do Registro 5315",
	'dt_emissao' => "Data da emissão do documento fiscal.",
	'modelo' => "Modelo do documento conforme a coluna Código da tabela 4.1.",
	'ser' => "Série do documento.",
	'num_doc' => "Número do documento",
	'cod_part' => "Código do participante conforme registro 0150.",
	'valor_sai' => "Valor de Saída",
	'perc_crdout' => "Percentual de Crédito Outorgado relativo ao item",
	'valor_crdout' => "Valor do Crédito Outorgado relativo ao item",
	'valor_bc' => "Base de Cálculo da operação de saída",
	'icms_deb' => "ICMS debitado na operação de saída",
	'num_decl_exp' => "Número da Declaração para Despacho de Exportação ou Declaração Simplificada de Exportação",
	'comp_oper' => "Comprovação da Operação – preencher com:
0 – Sim
1 – Não",
	'data_doc_ind' => "Data de emissão do documento fiscal do exportador",
	'num_doc_ind' => "Número do documento fiscal do exportador",
	'ser_doc_ind' => "Série do documento fiscal do exportador",
	'num_decl_exp_ind' => "Número da Declaração para Despacho de Exportação ou Declaração Simplificada de Exportação do estabelecimento exportador", 
	'enq_leg_0300' => "Enquadramento Legal - Composição dos Dados do 0300",
	'iva_utilizado' => "IVA - Índice de Valor Acrescido considerado no cálculo do custo estimado da operação ou prestação geradora conforme a legislação vigente.",
	'per_med_icms' => "Percentual Médio de Crédito do Imposto – é a alíquota média das entradas das mercadorias, insumos e serviços recebidos relacionados às saídas geradoras de crédito acumulado, obtida conforme legislação vigente, informar utilizando o formato percentual.",
	'cred_est_icms' => "Crédito estimado do ICMS",
	'icms_gera' => "Crédito Acumulado Gerado na operação",
	'nome' => "Razão social ou nome do participante",
	'cod_pais' => "Código do país do participante, conforme a tabela indicada na Tabela de Países do Banco Central do Brasil: www.bcb.gov.br",
	'cnpj' => "CNPJ ou CPF do participante",
	'ie' => "Inscrição Estadual do participante",
	'uf' => "Sigla da unidade da federação do participante",
	'cep' => "Código de Endereçamento Postal",
	'end' => "Logradouro e endereço do imóvel",
	'num' => "Número do imóvel",
	'compl' => "Dados complementares do endereço",
	'bairro' => "Bairro em que o imóvel está situado",
	'cod_mun' => "Código do município, conforme a tabela IBGE",
	'fone' => "Número do telefone",
	'ordmin' => "Valor auxiliar para controle interno",
	'ordmax' => "Valor auxiliar para controle interno"
);
  $pr->abre_excel_sql("OperGeradoras", "Operações Geradoras de Crédito Acumulado 5325/5330/5335/5340 combinado com 5315", $sql, $col_format, $cabec, $form_final);



  $sql = "
SELECT * FROM tab4_1;
";
  $col_format = array(
	"B:B" => "@");
  $cabec = array(
		'cod_chv' => "Codigo Chave",
		'codigo' => "Código",
		'descri' => "Descrição do Documento Fiscal",
		'mod' => "Modelo do Documento Fiscal"
);
  $pr->abre_excel_sql("Tab_4_1", "Tabela 4.1 - Tabela Documentos Fiscais do ICMS", $sql, $col_format, $cabec, $form_final);


  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(100);
	$this->excel_largura_coluna("A:A", 7);
	$this->excel_largura_coluna("E:E", 100);
';
  $sql = "
SELECT '', '', '', '', '##NT##Resumo Geral de LASIMCA';
SELECT '20' || substr(ord, 1, 4), '0000', '', '', '##NI##Empresa: CNPJ ' || cnpj ||  ' IE ' || ie ||  ' IE  Intima ' || ie_intima ||  ' cnae ' || cnae FROM o000;
SELECT '20' || substr(ord, 1, 4), '0000', '', '', '##NI##Empresa: Periodo ' || periodo || '    NOME ' || nome FROM o000;
SELECT '';
SELECT aaaamm, '',  sum(qtd), '', '##N## Conferência: Qtd 5325 + Qtd 5350: (conferir, deve bater com total 5315)'  FROM 
        (SELECT '20' || substr(ord, 1, 4) AS aaaamm, count(*) AS qtd FROM s325
        UNION ALL
        SELECT '20' || substr(ord, 1, 4) AS aaaamm, count(*) AS qtd FROM s350)
GROUP BY aaaamm;

SELECT '';
SELECT '', '', '', '', '##N##Abaixo, erros que o e-cred gera ocorrências';
SELECT '', '', '', '', '##N##Verificar se o arquivo NÃO está em UTF-8';
SELECT '20' || substr(ord, 1, 4), '0000', 'Valor->', cod_fin, '##N##Verificar se o campo cod_fin de 0000 está correto! 1-Normal 3-Substit' FROM o000;
-- teste 0150 exportação que não estao com dados vazios
-- Exemplo de registro correto: 0150|213001|EMPRESA|02755||||||||||
SELECT '', '', '', '', '##I##Listagem, se houver,0150 exportação que não estao com dados NÃO vazios:';
SELECT aaaamm, '', count(cod_part) AS qtd, '', '##i##teste 0150 exportação que não estao com dados NÃO vazios  cod_parts-->', group_concat(cod_part, ', ') AS cod_parts FROM 
    (SELECT '20' || substr(ord, 1, 4) AS aaaamm, cod_part FROM o150 
        WHERE cod_pais <> 1058 AND 
        (cnpj <> '' OR ie <> '' OR uf  <> '' OR cep  <> '' OR end  <> '' OR  num <> '' OR  compl  <> '' OR  bairro  <> '' OR  cod_mun  <> '' OR fone  <> '') )
GROUP BY aaaamm;
-- teste 0150 nacional duplicidade de combinação cnpj e ie
SELECT '', '', '', '', '##I##Listagem, se houver, linhas de 0150 nacionais com duplicidade de combinação cnpj e ie:';
SELECT '20' || substr(ord, 1, 4) AS aaaamm, '0150', count(cod_part) AS qtd, 'cod_parts-->', group_concat(cod_part, ', ') FROM o150 
    WHERE cod_pais = 1058
    GROUP BY aaaamm, cnpj, ie
    HAVING qtd > 1;
-- teste 0150 com duplicidade no campo cod_part
SELECT '', '', '', '', '##I##Listagem, se houver, linhas de 0150 com duplicidade no campo cod_part:';
SELECT '20' || substr(ord, 1, 4) AS aaaamm, '0150', count(cod_part) AS qtd, 'cnpjs-->', group_concat(cnpj, ', '), cod_part FROM o150 
    GROUP BY cod_part
    HAVING qtd > 1;    
-- teste 0150 faltando o cnpj do proprio emitente do arquivo digital
SELECT '', '', CASE WHEN contagem = 1 THEN '' ELSE contagem END AS qtd, '', 
    CASE WHEN contagem = 0 THEN '##N##ERRO! Não há linha de 0150 com o cnpj do emissor do arquivo (em 0000)! ' ELSE 
       CASE WHEN contagem = 1 THEN '##I##Ok! Há exatamente uma linha de 0150 com o cnpj do emissor do arquivo (em 0000)'
       ELSE '##N##ERRO! Há mais de uma linha de 0150 com o cnpj do emissor do arquivo (em 0000)! Total de linhas: ' || contagem END
    END AS msg
    FROM 
    (SELECT count(*) AS contagem FROM 
        (SELECT o150.cnpj FROM o150 
        LEFT OUTER JOIN o000 ON o000.cnpj = o150.cnpj
        WHERE o000.cnpj IS Not Null));
-- teste 5335 com num_decl_exp não preenchido
SELECT '', '', '', '', '##I##Listagem, se houver, linhas de5335 com num_decl_exp não preenchido:';
SELECT '20' || substr(ord, 1, 4) AS aaaamm, '5335', '', '', '##i##Reg 5335 linha ' || (substr(ord, 5, 7) + 0) || ' campo num_decl_exp não preenchido' FROM s335 
   WHERE num_decl_exp IS Null OR num_decl_exp = 0 OR num_decl_exp = '';    
-- teste 5135 cod_part sem correspondente no 0150
SELECT aaaamm, '', count(cod_part) AS qtd, '', '##I##cod_parts presentes em linhas de 5315 sem correspondência com linha de 0150-->', group_concat(cod_part, ', ') AS cod_parts FROM 
    (SELECT '20' || substr(s315.ord, 1, 4) AS aaaamm, s315.cod_part AS cod_part
        FROM s315
        LEFT OUTER JOIN o150 ON o150.cod_part = s315.cod_part
        WHERE o150.cod_part IS NULL
	GROUP BY aaaamm, cod_part);
SELECT '', '', '', '', '##I##Listagem, se houver,dt_emissao, tip_doc, ser, num_doc e Hipótese de Geração duplicados:';
SELECT aaaamm, '5315', qtd, '', 'Duplicou em: ' || dt_emissao || ' - ' || tip_doc || ' - ' || ser || ' - ' || num_doc || ' - ' || cod_legal || ' Linhas: ' || ords FROM
    (SELECT aaaamm, group_concat(ord, ', ') AS ords, dt_emissao, tip_doc, ser, num_doc, cod_legal, count(num_doc) AS qtd
         FROM
         (SELECT '20' || substr(s315.ord, 1, 4) AS aaaamm, substr(s315.ord, 5, 7) + 0 AS ord, s315.dt_emissao, s315.tip_doc, s315.ser, s315.num_doc, 
              s325.cod_legal AS cod_legal
              FROM s325
              LEFT OUTER JOIN s330 ON s330.Ords325 = s325.ord
              LEFT OUTER JOIN s315 ON s315.ord = s325.Ords315
          UNION ALL
          SELECT  '20' || substr(s315.ord, 1, 4) AS aaaamm, substr(s315.ord, 5, 7) + 0 AS ord, s315.dt_emissao, s315.tip_doc, s315.ser, s315.num_doc, 0 AS cod_legal
    	  FROM s350
              LEFT OUTER JOIN s315 ON s315.ord = s350.Ords315)
        GROUP BY aaaamm, dt_emissao, tip_doc, ser, num_doc, cod_legal
        HAVING qtd > 1);
-- teste 5235 com icms gerado em valores negativos
SELECT '', '', '', '', '##I##Listagem, se houver,5325 com icms gerado em valores negativos:';
SELECT  '20' || substr(ord, 1, 4) AS aaaamm, '5325', 'Valor->', icms_gera, '##N##<-- ERRO! ICMS gerado com valor negativo na linha ' || ord FROM s325
        WHERE icms_gera < 0;
SELECT '';
SELECT '', '', '', '', '##I##Abaixo, listagem constante em 9900';
SELECT '20' || substr(ord, 1, 4), reg_blc, qtd_reg_blc, '', 'Quantidade constante em 9900' FROM q900;
SELECT '';
SELECT 'aaaamm', 'valor_sai', 'valor_bc', 'icms_deb', '##I##DGCAs extraídos dos registros 5325 5315 5330', 'cred_est_icms', 'icms_gera';
SELECT aaaamm, sum(valor_sai) AS valor_sai, sum(valor_bc) AS valor_bc, sum(icms_deb) AS icms_deb, 
     '##i##' || cod_legal AS cod_legal, 
     sum(cred_est_icms) AS cred_est_icms, sum(icms_gera) AS icms_gera
     FROM
     (SELECT '20' || substr(s325.ord, 1, 4) AS aaaamm, 
          substr('0' || s325.cod_legal, -2) || ' - ' || o300.art || ' - ' || o300.inc  || ' - ' || o300.anex || ' - ' || o300.obs AS cod_legal, cred_est_icms, icms_gera,
          s315.valor_sai AS valor_sai,
          s330.valor_bc AS valor_bc, s330.icms_deb AS icms_deb, 
	  round(s325.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s325.ord / 10000000 + 0.5) * 10000000 AS ordmax
          FROM s325
          LEFT OUTER JOIN s330 ON s330.Ords325 = s325.ord
          LEFT OUTER JOIN s315 ON s315.ord = s325.Ords315
	  LEFT OUTER JOIN o300 ON o300.cod_legal = s325.cod_legal AND o300.ord > ordmin AND o300.ord < ordmax
      UNION ALL
      SELECT '20' || substr(s350.ord, 1, 4) AS aaaamm, 'Oper Não Geradoras 5350-5315' AS cod_legal, Null AS cred_est_icms, Null AS icms_gera,
          s315.valor_sai AS valor_sai,
          s350.valor_bc AS valor_bc, s350.icms_deb AS icms_deb, 
	  round(s350.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(s350.ord / 10000000 + 0.5) * 10000000 AS ordmax
	  FROM s350
          LEFT OUTER JOIN s315 ON s315.ord = s350.Ords315)
    GROUP BY aaaamm, cod_legal;
SELECT '';
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
  $pr->abre_excel_sql("Resumo", "Resumo do(s) LASIMCA(s)", $sql, $col_format, $cabec, $form_final);


  $pr->finaliza_excel();
  
}

?>