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
  $pr->abre_excel_sql("s340", "DADOS DA EXPORTAÇÃO INDIRETA COMPROVADA- FICHA 5H", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM s335;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5335",
	'Ords325' => "Número da Linha do Registro 5325",
	'num_decl_exp' => "Número da Declaração para Despacho de Exportação ou Declaração Simplificada de Exportação",
	'comp_oper' => "Comprovação da Operação – preencher com:
0 – Sim
1 – Não"
);
  $pr->abre_excel_sql("s335", "5335 - OPERAÇÕES GERADORAS APURADAS NA FICHA 6C OU 6D", $sql, $col_format, $cabec, $form_final);

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
  $pr->abre_excel_sql("s330", "5330 - OPERAÇÕES GERADORAS APURADAS NAS FICHAS 6A OU 6B", $sql, $col_format, $cabec, $form_final);

  
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
	"G:I" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5315",
	'dt_emissao' => "Data da emissão do documento fiscal.",
	'tip_doc' => "Tipo do documento conforme a coluna Código Chave da tabela 4.1.",
	'ser' => "Série do documento.",
	'num_doc' => "Número do documento",
	'cod_part' => "Código do participante conforme registro 0150.",
	'valor_sai' => "Valor de Saída",
	'PERC_CRDOUT' => "Percentual de Crédito Outorgado relativo ao item",
	'VALOR_CRDOUT' => "Valor do Crédito Outorgado relativo ao item"
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
	"E:E" => "0",
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