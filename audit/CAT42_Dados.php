<?php

$pr->aud_registra(new PrMenu("cat42_dados", "CAT_42", "Dados da CAT42", "cat42"));

function cat42_dados() {

  global $pr;

/*  
  $dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 200);

  $lbl_obs1 	= new GtkLabel("Este modulo exporta os registros dos LADCAs");
  $lbl_obs2 	= new GtkLabel("(Crédito Acumulado - Custeio) carregados");

  $dialog->vbox->pack_start($lbl_obs1, false, false, 2);
  $dialog->vbox->pack_start($lbl_obs2, false, false, 2);

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
*/
  
  $pr->inicia_excel('CAT42_Dados_do_CAT42');

  $form_final = '
	$this->excel_zoom_visualizacao(75);
	$this->excel_orientacao(2);		// paisagem
';

  $sql = "
SELECT * FROM l200;
";
  $col_format = array(
	"A:B" => "0",
	"F:F" => "0",
	"L:L" => "#.##0,000",
	"M:N" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 1200",
		'COD_PART' => "Código de identificação do participante no arquivo conforme Registro 0150",
		'COD_MOD' => "Código do modelo do documento fiscal, conforme a Tabela 4.1.1 do SPED",
		'ECF_FAB' => "Número de série de fabricação do equipamento ECF.",
		'SER' => "Série do documento fiscal",
		'NUM_DOC' => "Número do documento fiscal",
		'NUM_ITEM' => "Número sequencial do item no documento fiscal",
		'IND_OPER' => "Indicador do tipo de operação:
0- Entrada;
1- Saída",
		'DATA' => "Data da entrada da mercadoria ou da saída",
		'CFOP' => "Código Fiscal de Operação e Prestação",
		'COD_ITEM' => "Código do item conforme Registro 0200",
		'QTD' => "Quantidade do Item",
		'ICMS_TOT' => "Valor total do ICMS suportado pelo contribuinte nas operações de entrada (v. observação feita para o Registro 1050)",
		'VL_CONFR' => "Valor de confronto nas operações de saída",
		'COD_LEGAL' => "Código de Enquadramento Legal da hipótese de Ressarcimento ou Complemento de ICMS ST"
);
  $pr->abre_excel_sql("l200", "1200 - REGISTRO DE DOCUMENTO FISCAL NÃO-ELETRÔNICO PARA FINS DE RESSARCIMENTO DE SUBSTITUIÇÂO TRIBUTÁRIA – SP", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM l100;
";
  $col_format = array(
	"A:B" => "0",
	"F:F" => "0",
	"H:H" => "#.##0,000",
	"I:J" => "#.##0,00");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 1100",
		'CHV_DOC' => "Chave do Documento Fiscal Eletrônico",
		'DATA' => "Data da entrada da mercadoria ou da saída",
		'NUM_ITEM' => "Número sequencial do item no Documento Fiscal Eletrônico",
		'IND_OPER' => "Indicador do tipo de operação:
0- Entrada;
1- Saída",
		'COD_ITEM' => "Código do item conforme Registro 0200",
		'CFOP' => "Código Fiscal de Operação e Prestação",
		'QTD' => "Quantidade do Item",
		'ICMS_TOT' => "Valor total do ICMS suportado pelo contribuinte nas operações de entrada (v. observação feita para o Registro 1050)",
		'VL_CONFR' => "Valor de confronto nas operações de saída",
		'COD_LEGAL' => "Código de Enquadramento Legal da hipótese de Ressarcimento ou Complemento de ICMS ST"
);
  $pr->abre_excel_sql("l100", "1100 - REGISTRO DE DOCUMENTO FISCAL ELETRÔNICO PARA FINS DE RESSARCIMENTO DE SUBSTITUIÇÂO TRIBUTÁRIA OU ANTECIPAÇÃO", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM l050;
";
  $col_format = array(
	"A:B" => "0",
	"C:C" => "#.##0,00",
	"D:D" => "#.##0,000",
	"E:E" => "#.##0,00",
	"F:F" => "#.##0,000");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 1050",
		'COD_ITEM' => "Código do item conforme Registro 0200",
		'QTD_INI' => "Quantidade inicial do item no início do primeiro dia do período.",
		'ICMS_TOT_INI' => "Valor inicial acumulado do total do ICMS suportado pelo contribuinte, relativamente ao item, no início do primeiro dia do período.",
		'QTD_FIM' => "Quantidade final do item no final do último dia do período.",
		'ICMS_TOT_FIM' => "Valor final acumulado do total do ICMS suportado pelo contribuinte, relativamente ao item, no início do primeiro dia do período."
);
  $pr->abre_excel_sql("l050", "1050 - REGISTRO DE SALDOS", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o205;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0205",
		'COD_ITEM' => "Código do item alterado no Registro 0200",
		'COD_ANT_ITEM' => "Código anterior do mesmo item",
		'DESCR_ANT_ITEM' => "Descrição anterior do mesmo item"
);
  $pr->abre_excel_sql("o205", "0205 - CÓDIGO ANTERIOR DO ITEM (Não obrigados ao SPED)", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o200;
";
  $col_format = array(
		"A:A" => "0",
		"B:B" => "0",
		"D:D" => "0",
		"F:F" => "0",
		"G:G" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0200",
	'COD_ITEM' => "Código do item",
	'DESCR_ITEM' => "Descrição do item",
	'COD_BARRA' => "Representação alfanumérica do código de barra do produto",
	'UNI_INV' => "Unidade de medida utilizada na quantificação de estoques.",
	'COD_NCM' => "Código da Nomenclatura Comum do MERCOSUL",
	'ALIQ_ICMS' => "Alíquota de ICMS aplicável ao item nas operações internas",
	'CEST' => "Código Especificador da Substituição Tributária"
);
  $pr->abre_excel_sql("o200", "0200 - TABELA DE IDENTIFICAÇÃO DO ITEM", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM o150;
";
  $col_format = array(
	"A:A" => "0",
	"D:H" => "0"
);
  $cabec = array(
	'OrdC150' => "Número da Linha do Registro C150",
	'cod_part' => "Código de identificação do participante no arquivo",
	'nome' => "Nome pessoal ou empresarial do participante",
	'cod_pais' => "Código do país do participante, conforme a tabela indicada no item 3.2.1 do Ato COTEPE/ICMS nº 09, de 18 de abril de 2008.",
	'cnpj' => "CNPJ do participante",
	'cpf' => "CPF do participante",
	'ie' => "Inscrição Estadual do participante",
	'cod_mun' => "Código do município, conforme a tabela IBGE"
);
  $pr->abre_excel_sql("o150", "TABELA DE CADASTRO DO PARTICIPANTE", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM o000;
";
  $col_format = array(
	"D:H" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0000",
	'periodo' => "Periodo das informações contidas no arquivo.",
	'nome' => "Nome empresarial da entidade",
	'cnpj' => "Número de inscrição no CNPJ do estabelecimento informante.",
	'ie' => "Inscrição Estadual do estabelecimento informante",
	'cod_mun' => "Código do município do domicílio fiscal da entidade, conforme a tabela IBGE",
	'cod_ver' => "Código da versão do leiaute conforme a Tabela de Versão do Leiaute",
	'cod_fin' => "Código da finalidade do arquivo conforme a Tabela Finalidade de Entrega do Arquivo"
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