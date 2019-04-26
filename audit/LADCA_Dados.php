<?php

$pr->aud_registra(new PrMenu("ladca_dados", "LAD_CA", "Dados da LADCA", "ladca"));

function ladca_dados() {

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
  
  $pr->inicia_excel('LADCA_Dados_do_LADCA');

  $form_final = '
	$this->excel_zoom_visualizacao(75);
	$this->excel_orientacao(2);		// paisagem
';


  $sql = "
SELECT * FROM s595;
";
  $col_format = array(
	"A:C" => "0",
	"D:F" => "#.##0,000000");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5385",
	'Ords590' => "Número da Linha do Registro 5590",
	'COD_INS' => "Código do insumo conforme Registro 0200.",
	'QUANT_INS' => "Quantidade do insumo utilizado",
	'CUST_INS' => "Custo do insumo de entrada, excluídos os tributos e contribuições recuperáveis",
	'ICMS_INS' => "Valor do ICMS do insumo"
);
  $pr->abre_excel_sql("s595", "5595 - INVENTÁRIO POR MATERIAL COMPONENTE - FICHA 5G", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM s590;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5590",
	'COD_PROD_ELAB' => "Código do produto em elaboração conforme Registro 0200"
);
  $pr->abre_excel_sql("s590", "5590 - ABERTURA DE FICHA 5G", $sql, $col_format, $cabec, $form_final);


  
  $sql = "
SELECT * FROM s400;
";
  $col_format = array(
	"A:B" => "0",
	"C:H" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5400",
	'Ords365' => "Número da Linha do Registro 5365",
	'VALOR_OP_ITEM' => "Valor Total da operação de saída relativo ao item",
	'VALOR_BC_ITEM' => "Base de Cálculo da operação de saída relativa ao item",
	'ALIQ_ITEM' => "Alíquota do ICMS da operação de saída relativa ao item N",
	'ICMS_DEB_ITEM' => "ICMS debitado da operação de saída do item N",
	'ICMS_OPER_ITEM' => "ICMS devido na operação de saída relativo ao item N",
	'ICMS_OPER_ITEM_CRED' => "Crédito de ICMS na operação de saída relativo ao item"
);
  $pr->abre_excel_sql("s400", "5400 - OPERAÇÕES NÃO GERADORAS DE CRÉDITO ACUMULADO – FICHA 6F", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM s385;
";
  $col_format = array(
	"A:C" => "0",
	"C:E" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5385",
	'Ords380' => "Número da Linha do Registro 5380",
	'VALOR_BC_ITEM' => "Base de Cálculo da operação de saída relativa ao item.",
	'ALIQ_ITEM' => "Alíquota do ICMS da operação de saída relativa ao item.",
	'ICMS_DEB_ITEM' => "ICMS debitado na operação de saída do item."
);
  $pr->abre_excel_sql("s385", "5385 - OPERAÇÕES GERADORAS APURADAS NAS FICHAS 6A OU 6B", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM s380;
";
  $col_format = array(
	"A:C" => "0",
	"D:E" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5380",
	'Ords365' => "Número da Linha do Registro 5365",
	'COD_LEGAL' => "Código do Enquadramento Legal conforme registro 0300.",
	'VALOR_OP_ITEM' => "Valor Total da Operação relativo ao item",
	'ICMS_GERA_ITEM' => "Crédito Acumulado Gerado na Operação com o item"
);
  $pr->abre_excel_sql("s380", "5380 - OPERAÇÕES GERADORAS DE CRÉDITO ACUMULADO", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM s370;
";
  $col_format = array(
	"A:B" => "0",
	"C:D" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro s370",
	'Ords365_1:1' => "Número da Linha do Registro s365 (1:1)",
	'VAL_IPI' => "Valor do IPI, quando recuperável.",
	'VAL_TRIB' => "Valor de outros tributos e contribuições não-cumulativos."
);
  $pr->abre_excel_sql("s370", "5370 - IPI E OUTROS TRIBUTOS NA ENTRADA", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM s365;
";
  $col_format = array(
	"A:C" => "0",
	"N:P" => "#.##0,000000",
	"Q:S" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5365",
	'Ords360' => "Número da Linha do Registro 5360",
	'NUM_LANC' => "Número do Lançamento",
	'DT_MOV' => "Data da movimentação",
	'HIST' => "Histórico",
	'TIP_DOC' => "Tipo do documento conforme a coluna Código Chave da tabela 4.2 ou Campo 02 do Registro 0400.",
	'SER' => "Série do documento.",
	'NUM_DOC' => "Número do documento",
	'CFOP' => "CFOP da Operação",
	'NUM_DI' => "Número da DI ou DSI",
	'COD_PART' => "Código do participante conforme registro 0150.",
	'COD_LANC' => "Código do lançamento, utilizar a tabela 6.1.
Dígito Descrição
1º Número do Módulo de Origem
2º Letra da Ficha de Origem - Convertido em Número
3º Número do Módulo de Destino
4º Letra da Ficha de Destino - Convertido em Número
5º Identificação do Lançamento
6º Identificação do Lançamento",
	'IND' => "Indicador do Movimento - preencher com:
0 – Entrada
1 - Saída.",
	'QUAN' => "Quantidade do item.",
	'CUST_ITEM' => "Custo do item de entrada, excluídos os tributos e contribuições recuperáveis",
	'VL_ICMS' => "Valor do ICMS do item.",
	'PERC_CRDOUT' => "Percentual de Crédito Outorgado relativo ao item",
	'VALOR_CRDOUT' => "Valor do Crédito Outorgado relativo ao item",
	'VALOR_DESP' => "Valor do Crédito – Despesas Operacionais"
);
  $pr->abre_excel_sql("s365", "5365 - MOVIMENTAÇÃO DE ITENS", $sql, $col_format, $cabec, $form_final);
  


  $sql = "
SELECT * FROM s360;
";
  $col_format = array(
	"A:A" => "0",
	"C:C" => "#.##0,000000",
	"D:E" => "#.##0,00",
	"F:F" => "#.##0,000000",
	"G:H" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5360",
	'COD_ITEM' => "Código do item conforme Registro 0200.",
	'QUANT_INI' => "Saldo inicial de quantidade do item",
	'CUS_INI' => "Saldo inicial do valor de custo do item",
	'ICMS_INI' => "Saldo inicial do valor do ICMS do item",
	'QUANT_FIM' => "Saldo final de quantidade do item",
	'CUS_FIM' => "Saldo final do valor de custo do item",
	'ICMS_FIM' =>  "Saldo final do valor do ICMS do item"
);
  $pr->abre_excel_sql("s360", "5360 - ABERTURA DE FICHA 3B", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM s350;
";
  $col_format = array(
	"A:B" => "0",
	"C:H" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5330",
	'Ords315' => "Número da Linha do Registro 5315",
	'VALOR_OP_ITEM' => "Valor Total da operação de saída relativo ao item",
	'VALOR_BC_ITEM' => "Base de Cálculo da operação de saída relativa ao item",
	'ALIQ_ITEM' => "Alíquota do ICMS da operação de saída relativa ao item N",
	'ICMS_DEB_ITEM' => "ICMS debitado da operação de saída do item N",
	'ICMS_OPER_ITEM' => "ICMS devido na operação de saída relativo ao item N",
	'ICMS_OPER_ITEM_CRED' => "Crédito de ICMS na operação de saída relativo ao item"
);
  $pr->abre_excel_sql("s350", "5350 - OPERAÇÕES NÃO GERADORAS DE CRÉDITO ACUMULADO – FICHA 6F", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM s330;
";
  $col_format = array(
	"A:C" => "0",
	"C:E" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5330",
	'Ords325' => "Número da Linha do Registro 5325",
	'VALOR_BC_ITEM' => "Base de Cálculo da operação de saída relativa ao item.",
	'ALIQ_ITEM' => "Alíquota do ICMS da operação de saída relativa ao item.",
	'ICMS_DEB_ITEM' => "ICMS debitado na operação de saída do item."
);
  $pr->abre_excel_sql("s330", "5330 - OPERAÇÕES GERADORAS APURADAS NAS FICHAS 6A OU 6B", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM s325;
";
  $col_format = array(
	"A:C" => "0",
	"D:E" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5325",
	'Ords315' => "Número da Linha do Registro 5315",
	'COD_LEGAL' => "Código do Enquadramento Legal conforme registro 0300.",
	'VALOR_OP_ITEM' => "Valor Total da Operação relativo ao item",
	'ICMS_GERA_ITEM' => "Crédito Acumulado Gerado na Operação com o item"
);
  $pr->abre_excel_sql("s325", "5325 - OPERAÇÕES GERADORAS DE CRÉDITO ACUMULADO", $sql, $col_format, $cabec, $form_final);
  
  $sql = "
SELECT * FROM s315;
";
  $col_format = array(
	"A:C" => "0",
	"M:O" => "#.##0,000000",
	"P:R" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5315",
	'Ords310' => "Número da Linha do Registro 5310",
	'NUM_LANC' => "Número do Lançamento",
	'DT_MOV' => "Data da movimentação",
	'HIST' => "Histórico",
	'TIP_DOC' => "Tipo do documento conforme a coluna Código Chave da tabela 4.2 ou Campo 02 do Registro 0400.",
	'SER' => "Série do documento.",
	'NUM_DOC' => "Número do documento",
	'CFOP' => "CFOP da Operação",
	'COD_PART' => "Código do participante conforme registro 0150.",
	'COD_LANC' => "Código do lançamento, utilizar a tabela 6.1.
Dígito Descrição
1º Número do Módulo de Origem
2º Letra da Ficha de Origem - Convertido em Número
3º Número do Módulo de Destino
4º Letra da Ficha de Destino - Convertido em Número
5º Identificação do Lançamento
6º Identificação do Lançamento",
	'IND' => "Indicador do Movimento - preencher com:
0 – Entrada
1 - Saída.",
	'QUAN' => "Quantidade do item.",
	'CUST_ITEM' => "Custo do item de entrada, excluídos os tributos e contribuições recuperáveis",
	'VL_ICMS' => "Valor do ICMS do item.",
	'PERC_CRDOUT' => "Percentual de Crédito Outorgado relativo ao item",
	'VALOR_CRDOUT' => "Valor do Crédito Outorgado relativo ao item",
	'VALOR_DESP' => "Valor do Crédito – Despesas Operacionais"
);
  $pr->abre_excel_sql("s315", "5315 - MOVIMENTAÇÃO DE ITENS", $sql, $col_format, $cabec, $form_final);
  


  $sql = "
SELECT * FROM s310;
";
  $col_format = array(
	"A:A" => "0",
	"C:C" => "#.##0,000000",
	"D:E" => "#.##0,00",
	"F:F" => "#.##0,000000",
	"G:H" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 5310",
	'COD_ITEM' => "Código do item conforme Registro 0200.",
	'QUANT_INI' => "Saldo inicial de quantidade do item",
	'CUS_INI' => "Saldo inicial do valor de custo do item",
	'ICMS_INI' => "Saldo inicial do valor do ICMS do item",
	'QUANT_FIM' => "Saldo final de quantidade do item",
	'CUS_FIM' => "Saldo final do valor de custo do item",
	'ICMS_FIM' =>  "Saldo final do valor do ICMS do item"
);
  $pr->abre_excel_sql("s310", "5310 - ABERTURA DE FICHA 3A", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM s160;
";
  $col_format = array(
	"A:C" => "0",
	"L:N" => "#.##0,000000");
  $cabec = array(
	'Ord' => "Número da Linha do Registro s160",
	'Ords150' => "Número da Linha do Registro s150",
	'NUM_LANC' => "Número do Lançamento",
	'DT_MOV' => "Data da movimentação",
	'HIST' => "Histórico",
	'TIP_DOC' => "Tipo do documento conforme a coluna Código Chave da tabela 4.2 ou Campo 02 do Registro 0400.",
	'SER' => "Série do documento.",
	'NUM_DOC' => "Número do documento",
	'COD_LANC' => "Código do lançamento, utilizar a tabela 6.1.
Dígito Descrição
1º Número do Módulo de Origem
2º Letra da Ficha de Origem - Convertido em Número
3º Número do Módulo de Destino
4º Letra da Ficha de Destino - Convertido em Número
5º Identificação do Lançamento
6º Identificação do Lançamento",
	'IND' => "Indicador do Movimento - preencher com:
0 – Entrada
1 - Saída.",
	'COD_ITEM' => "Código do item movimentado conforme Registro 0200.",
	'QUAN' => "Quantidade do item.",
	'CUST_ITEM' => "Custo do item de entrada, excluídos os tributos e contribuições recuperáveis",
	'VL_ICMS' => "Valor do ICMS do item."
);
  $pr->abre_excel_sql("s160", "5160 - MOVIMENTAÇÃO DE ITENS DA FICHA 2A", $sql, $col_format, $cabec, $form_final);
  

  $sql = "
SELECT * FROM s155;
";
  $col_format = array(
	"A:B" => "0",
	"D:H" => "#.##0,000000");
  $cabec = array(
	'Ord' => "Número da Linha do Registro s155",
	'Ords150' => "Número da Linha do Registro s150",
	'COD_INS' => "Código do insumo, conforme Registro 0200.",
	'QUANT_INS' => "Quantidade de insumo utilizada",
	'CUST_UNIT' => "Custo Unitário do Insumo por unidade de produto de entrada, excluídos os tributos e contribuições recuperáveis",
	'ICMS_UNIT' => "Valor Unitário do ICMS do Insumo por unidade de produto",
	'PERD_NORM' => "Quantidade de perda normal no processo produtivo",
	'GANHO_NORM' => "Quantidade de ganho normal no processo produtivo"
);
  $pr->abre_excel_sql("s155", "5155 - APURAÇÃO DO CUSTO - FICHA TÉCNICA 5A", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM s150;
";
  $col_format = array(
	"A:A" => "0",
	"C:F" => "#.##0,00",
	"G:I" => "#.##0,000000");
  $cabec = array(
	'Ord' => "Número da Linha do Registro s010",
	'COD_ITEM' => "Código do produto, conforme Registro 0200.",
	'CUS_INI' => "Saldo inicial do Valor de custo.",
	'ICMS_INI' => "Saldo inicial do Valor ICMS.",
	'CUS_FIM' => "Saldo final do Valor de custo.",
	'ICMS_FIM' =>  "Saldo final do Valor ICMS.",
	'QUANT_PER' => "Quantidade de produto concluído e transferido no período.",
	'CUST_UNIT' => "Custo Unitário do produto concluído e transferido no período.",
	'ICMS_UNIT' => "Valor Unitário de ICMS do produto concluído e transferido no período."
);
  $pr->abre_excel_sql("s150", "5150 - ABERTURA DE FICHA 2A", $sql, $col_format, $cabec, $form_final);
  

$sql = "
SELECT * FROM s085;
";
  $col_format = array(
	"A:C" => "0",
	"J:J" => "0",
	"N:Q" => "#.##0,000000");
  $cabec = array(
	'Ord' => "Número da Linha do Registro s085",
	'Ords080' => "Número da Linha do Registro s080",
	'NUM_LANC' => "Número do Lançamento",
	'DT_MOV' => "Data da movimentação",
	'HIST' => "Histórico",
	'TIP_DOC' => "Tipo do documento conforme a coluna Código Chave da tabela 4.2 ou Campo 02 do Registro 0400",
	'SER' => "Série do documento",
	'NUM_DOC' => "Número do documento",
	'CFOP' => "CFOP da Operação",
	'COD_PART' => "Código do participante conforme registro 0150.",
	'COD_LANC' => "Código do lançamento, utilizar a tabela 6.1.
Dígito Descrição
1º Número do Módulo de Origem
2º Letra da Ficha de Origem - Convertido em Número
3º Número do Módulo de Destino
4º Letra da Ficha de Destino - Convertido em Número
5º Identificação do Lançamento
6º Identificação do Lançamento",
	'IND' => "Indicador do Movimento – preencher com:
0 – Entrada
1 - Saída.",
	'COD_ITEM_OUTRA_TAB' => "Código do item controlado na ficha de origem ou destino conforme Registro 0200.",
	'QUAN' => "Quantidade do item.",
	'CUST_ENER' => "Custo do item",
	'VL_ICMS' => "Valor do ICMS do item.",
	'PERC_RATEIO' => "Percentual de rateio da Ficha 4A",
);
  $pr->abre_excel_sql("s085", "5085 - MOVIMENTAÇÃO DE ITENS DA FICHA 1C", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM s080;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro s010",
	'COD_ENERGIA' => "Código da Energia Elétrica conforme Registro 0200"
);
  $pr->abre_excel_sql("s080", "5080 - ABERTURA DE FICHA 1C", $sql, $col_format, $cabec, $form_final);
  

  $sql = "
SELECT * FROM s020;
";
  $col_format = array(
	"A:B" => "0",
	"C:D" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro s020",
	'Ords015_1:1' => "Número da Linha do Registro s015 (1:1)",
	'VAL_IPI' => "Valor do IPI, quando recuperável.",
	'VAL_TRIB' => "Valor de outros tributos e contribuições não-cumulativos."
);
  $pr->abre_excel_sql("s020", "5020 - IPI E OUTROS TRIBUTOS NA ENTRADA", $sql, $col_format, $cabec, $form_final);
  

  $sql = "
SELECT * FROM s015;
";
  $col_format = array(
	"A:C" => "0",
	"J:K" => "0",
	"O:Q" => "#.##0,000000");
  $cabec = array(
	'Ord' => "Número da Linha do Registro s015",
	'Ords010' => "Número da Linha do Registro s010",
	'NUM_LANC' => "Número do Lançamento",
	'DT_MOV' => "Data da movimentação",
	'HIST' => "Histórico",
	'TIP_DOC' => "Tipo do documento conforme a coluna Código Chave da tabela 4.2 ou Campo 02 do Registro 0400",
	'SER' => "Série do documento",
	'NUM_DOC' => "Número do documento",
	'CFOP' => "CFOP da Operação",
	'NUM_DI' => "Número da DI ou DSI",
	'COD_PART' => "Código do participante conforme registro 0150.",
	'COD_LANC' => "Código do lançamento, utilizar a tabela 6.1.
Dígito Descrição
1º Número do Módulo de Origem
2º Letra da Ficha de Origem - Convertido em Número
3º Número do Módulo de Destino
4º Letra da Ficha de Destino - Convertido em Número
5º Identificação do Lançamento
6º Identificação do Lançamento",
	'IND' => "Indicador do Movimento – preencher com:
0 – Entrada
1 - Saída.",
	'COD_ITEM_OUTRA_TAB' => "Código do item controlado na ficha de origem ou destino conforme Registro 0200.",
	'QUAN' => "Quantidade do item.",
	'CUST_MERC' => "Custo do item de entrada, excluídos os tributos e contribuições recuperáveis",
	'VL_ICMS' => "Valor do ICMS do item."
);
  $pr->abre_excel_sql("s015", "5015 - MOVIMENTAÇÃO DE ITENS", $sql, $col_format, $cabec, $form_final);

  
  $sql = "
SELECT * FROM s010;
";
  $col_format = array(
	"A:A" => "0",
	"C:C" => "#.##0,000000",
	"D:E" => "#.##0,00",
	"F:F" => "#.##0,000000",
	"G:H" => "#.##0,00");
  $cabec = array(
	'Ord' => "Número da Linha do Registro s010",
	'COD_ITEM' => "Código do item conforme Registro",
	'QUANT_INI' => "Saldo inicial de quantidade do item",
	'CUS_INI' => "Saldo inicial do valor de custo do item",
	'ICMS_INI' => "Saldo inicial do valor do ICMS do item",
	'QUANT_FIM' => "Saldo final de quantidade do item",
	'CUS_FIM' => "Saldo final do valor de custo do item",
	'ICMS_FIM' =>  "Saldo final do valor do ICMS do item"
);
  $pr->abre_excel_sql("s010", "5010 - ABERTURA DE FICHA 1A", $sql, $col_format, $cabec, $form_final);
  
  
  $sql = "
SELECT * FROM o400;
";
  $col_format = array(
	"A:A" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0400",
		'COD_CHV' => "Código chave e único de identificação do documento ou relatório de uso interno, gerado pelo contribuinte, para fins do sistema de apuração.
Obs: Será preenchido com o código chave e único para cada registro iniciando no número 100.",
		'DESCR_DOC_INT' => "Descrição do documento ou relatório de uso interno",
		'COD_ DOC_INT' => "Código do documento ou relatório de uso interno utilizado pelo contribuinte, se existir.
Obs: Será preenchido com o código do documento ou relatório utilizado pela empresa. Caso não exista este código, não preencha o campo"
);
  $pr->abre_excel_sql("o400", "0400 - TABELA DE IDENTIFICAÇÃO DE DOCUMENTO OU RELATÓRIO INTERNO", $sql, $col_format, $cabec, $form_final);
  

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
SELECT * FROM o205;
";
  $col_format = array(
	"A:B" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0205",
		'Ord0200' => "Número da Linha do Registro 0200",
		'COD_ANT_ITEM' => "Código anterior do mesmo item",
		'DESCR_ANT_ITEM' => "Descrição anterior do mesmo item",
		'DT_INI' => "Período inicial de utilização do código anterior do item",
		'DT_FIM' => "Período final de utilização do código anterior do item",
);
  $pr->abre_excel_sql("o205", "0205 - CÓDIGO ANTERIOR DO ITEM", $sql, $col_format, $cabec, $form_final);


  $sql = "
SELECT * FROM o200;
";
  $col_format = array(
		"A:A" => "0",
		"B:B" => "@");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0200",
	'COD_ITEM' => "Código do item",
	'DESCR_ITEM' => "Descrição do item",
	'UNI' => "Unidade de medida do Item.",
	'COD_GEN' => "Código do gênero do item, conforme a Tabela 4.1."
);
  $pr->abre_excel_sql("o200", "0200 - TABELA DE IDENTIFICAÇÃO DO ITEM", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM o151;
";
  $col_format = array(
	"A:C" => "0");
  $cabec = array(
		'Ord' => "Número da Linha do Registro 0151",
		'Ord150' => "Número da Linha do Registro 0150",
		'IE_SUBS' => "Inscrição Estadual em São Paulo do Participante de outra unidade da federação na condição de substituto tributário no Estado de São Paulo."
);
  $pr->abre_excel_sql("o151", "0151 - TABELA DE IE DE CONTRIBUINTE SUBSTITUTO", $sql, $col_format, $cabec, $form_final);

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
SELECT * FROM o000;
";
  $col_format = array(
	"G:L" => "0");
  $cabec = array(
	'Ord' => "Número da Linha do Registro 0000",
	'LADCA' => "Texto fixo contendo 'LADCA'",
	'cod_ver' => "Código da versão do leiaute conforme a Tabela 3.1",
	'cod_fin' => "Código da finalidade do arquivo conforme a Tabela 3.2",
	'periodo' => "Periodo das informações contidas no arquivo.",
	'nome' => "Nome empresarial do estabelecimento informante.",
	'cnpj' => "Número de inscrição no CNPJ do estabelecimento informante.",
	'ie' => "Inscrição Estadual do estabelecimento informante",
	'cnae' => "CNAE do contribuinte informante.",
	'cod_mun' => "Código do município do domicílio fiscal da entidade, conforme a tabela IBGE",
	'op_crd_out' => "Opção de crédito outorgado conforme artigo 11 do Anexo III do RICMS/00 por Prestador de Serviço de Transporte Rodoviário – preencher com:
0 – Não optante do crédito outorgado.
1- Optante do crédito outorgado.",
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
SELECT * FROM tab6_1;
";
  $col_format = array(
	"B:B" => "@");
  $cabec = array(
		'cod' => "Código da Tabela 6.1",
		'ori_des' => "Helper para Ficha de Origem ou Destino",
		'descri' => "Histórico"
);
  $pr->abre_excel_sql("Tab_6_1", "Tabela 6.1 - TABELA DE CODIFICAÇÃO DOS LANÇAMENTOS", $sql, $col_format, $cabec, $form_final);

  $sql = "
SELECT * FROM tab4_1;
";
  $col_format = array(
	"B:B" => "@");
  $cabec = array(
		'cod' => "Código da Tabela 4.1",
		'descri' => "Descrição do Gênero do Item de Mercadoria/Serviço
A tabela 'Gênero do Item de Mercadoria/Serviço' corresponde à tabela de 'Capítulos da NCM' acrescida do código '00
- Serviço'."
);
  $pr->abre_excel_sql("Tab_4_1", "Tabela 4.1 - GÊNERO DO ITEM E DA OPERAÇÃO", $sql, $col_format, $cabec, $form_final);


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