<?php

$pr->aud_registra(new PrMenu("efd_ressarcimento", "E_FD", "EFD Ressarcimento", "efd"));

function efd_ressarcimento() {

  global $pr;

  $pr->inicia_excel('EFD_Ressarcimento');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';

  
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


  // Planilha RessarcimentoC197_C195_0460_C100_0150
  $sql = "
SELECT c197.*,
    c195.*,
    o460.*,
    c100.*,
    o150.*, o200.*,
    round(c197.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(c197.ord / 10000000 + 0.5) * 10000000 AS ordmax
FROM c197
LEFT OUTER JOIN c195 ON c195.ord = c197.ordC195
LEFT OUTER JOIN o460 ON o460.cod_obs = c195.cod_obs  AND o460.ord > ordmin AND o460.ord < ordmax
LEFT OUTER JOIN c100 ON c100.ord = c195.ordC190
LEFT OUTER JOIN o150 ON o150.cod_part = c100.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
LEFT OUTER JOIN o200 ON o200.cod_item = c197.cod_item AND o200.ord > ordmin AND o200.ord < ordmax;
";
  $col_format = array(
	"A:C" => "0",
	"G:J" => "#.##0,00",
	"K:L" => "0",
	"O:O" => "0",
	"R:R" => "0",
	"U:U" => "0",
	"AC:AT" => "#.##0,00",
	"AU:AV" => "0",
	"AY:BA" => "0",
	"BH:BH" => "0",
	"BK:BK" => "0",
	"BU:BV" => "0"
);
  $cabec = array(
	'OrdC197' => "Número da Linha do Registro C197",
	'OrdC100_C197' => "Número da Linha do Registro C100",
	'OrdC195_C197' => "Número da Linha do Registro C195",
	'COD_AJ' => "Código do ajustes/benefício/incentivo, conforme tabela indicada no item 5.3",
	'DESCR_COMPL_AJ' => "Descrição complementar do ajuste do documento fiscal",
	'COD_ITEM' => "Código do item (campo 02 do Registro 0200)",
	'VL_BC_ICMS' => "Base de cálculo do ICMS ou do ICMS ST ",
	'ALIQ_ICMS' => "Alíquota do ICMS",
	'VL_ICMS' => "Valor do ICMS ou do ICMS ST",
	'VL_OUTROS' => "Outros valores",
	'OrdC195' => "Número da Linha do Registro C195",
	'OrdC100_C195' => "Número da Linha do Registro C100",
	'COD_OBS' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)",
	'TXT_COMPL' => "Descrição complementar do código de observação.	",
	'Ord_0460' => "Número da Linha do Registro 0450",
	'cod_obs' => "Código da Observação do lançamento fiscal.",
	'txt' => "TDescrição da observação vinculada ao lançamento  fiscal ",
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
	'vl_desc_C100' => "Valor total do desconto",
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
	'vl_bc_icms_C100' => "Valor da base de cálculo do ICMS",
	'vl_icms_C100' => "Valor do ICMS",
	'vl_bc_icms_st_C100' => "Valor da base de cálculo do ICMS substituição tributária",
	'vl_icms_st_C100' => "Valor do ICMS retido por substituição tributária",
	'vl_ipi_C100' => "Valor total do IPI",
	'vl_pis_C100' => "Valor total do PIS",
	'vl_cofins_C100' => "Valor total da COFINS",
	'vl_pis_st' => "Valor total do PIS retido por substituição tributária",
	'vl_cofins_st' => "Valor total da COFINS retido por substituição tributária",
	'OrdC150' => "Número da Linha do Registro C150",
	'cod_part_C150' => "Código de identificação do participante no arquivo",
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
	'Ord_0200' => "Número da Linha do Registro 0200",
	'COD_ITEM_200' => "Código do item",
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
	'ALIQ_ICMS_0200' => "Alíquota de ICMS aplicável ao item nas operações internas",
	'CEST' => "Código Especificador da Substituição Tributária",
	'ordmin' => "Uso interno do conversor, para fins de relacionamento entre tabelas",
	'ordmax' => "Uso interno do conversor, para fins de relacionamento entre tabelas"
);
  $pr->abre_excel_sql('RessarcimentoObs', 'RessarcimentoC197_C195_0460_C100_0150', $sql, $col_format, $cabec, $form_final);
    


  // Planilha RessarcimentoC176_C170_C100_0150_0200
  $sql = "
SELECT c176.*,
    c170.*,
    c100.*,
    o150.*, o200.*, 
    round(c176.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(c176.ord / 10000000 + 0.5) * 10000000 AS ordmax
FROM c176
LEFT OUTER JOIN c170 ON c170.ord = c176.ordC170
LEFT OUTER JOIN c100 ON c100.ord = c170.ordC100
LEFT OUTER JOIN o150 ON o150.cod_part = c100.cod_part AND o150.ord > ordmin AND o150.ord < ordmax
LEFT OUTER JOIN o200 ON o200.cod_item = c170.cod_item AND o200.ord > ordmin AND o200.ord < ordmax;
";
  $col_format = array(
	"A:B" => "0",
	"G:G" => "0",
	"H:J" => "#.##0,00",
	"M:R" => "#.##0,00",
	"AB:AC" => "0",
	"AG:AG" => "#.##0,00",
	"AI:AJ" => "#.##0,00",
	"AO:AT" => "#.##0,00",
	"AX:AZ" => "#.##0,00",
	"BB:BF" => "#.##0,00",
	"BH:BL" => "#.##0,00",
	"BN:BN" => "0",
	"BQ:BQ" => "0",
	"BY:BY" => "#.##0,00",
	"CB:CC" => "#.##0,00",
	"CE:CP" => "#.##0,00",
	"CQ:CR" => "0",
	"CU:CY" => "0",
	"DD:DD" => "0",
	"DG:DG" => "0",
	"DO:DO" => "#.##0,00",
	"DQ:DR" => "0"
);
  $cabec = array(
		'OrdC176' => "Número da Linha do Registro C176",
		'OrdC170_C176' => "Número da Linha do Registro C170",
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
		'NUM_DA' => "Número do documento de arrecadação estadual,se houver",
	'OrdC170' => "Número da Linha do Registro C170",
	'OrdC100_C170' => "Número da Linha do Registro C100",
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
	'cod_cta' => "Código da conta analítica contábil debitada/creditada",
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
	'vl_desc_C100' => "Valor total do desconto",
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
	'vl_bc_icms_C100' => "Valor da base de cálculo do ICMS",
	'vl_icms_C100' => "Valor do ICMS",
	'vl_bc_icms_st_C100' => "Valor da base de cálculo do ICMS substituição tributária",
	'vl_icms_st_C100' => "Valor do ICMS retido por substituição tributária",
	'vl_ipi_C100' => "Valor total do IPI",
	'vl_pis_C100' => "Valor total do PIS",
	'vl_cofins_C100' => "Valor total da COFINS",
	'vl_pis_st' => "Valor total do PIS retido por substituição tributária",
	'vl_cofins_st' => "Valor total da COFINS retido por substituição tributária",
	'OrdC150' => "Número da Linha do Registro C150",
	'cod_part_C150' => "Código de identificação do participante no arquivo",
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
	'Ord_0200' => "Número da Linha do Registro 0200",
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
	'CEST' => "Código Especificador da Substituição Tributária",
	'ordmin' => "Uso interno do conversor, para fins de relacionamento entre tabelas",
	'ordmax' => "Uso interno do conversor, para fins de relacionamento entre tabelas"
);
  $pr->abre_excel_sql('Ressarcimento', 'RessarcimentoC176_C170_C100_0150_0200', $sql, $col_format, $cabec, $form_final);

    
  $pr->finaliza_excel();
}

?>