<?php

$pr->aud_registra(new PrMenu("efd_livros_itensC170_NFe", "E_FD", "Livro Fiscal C170 _com NFe", "efd,dfe"));
$pr->aud_registra(new PrMenu("efd_livros_itensC170",     "E_FD", "Livro Fiscal C170 _sem NFe", "efd"));
$pr->aud_registra(new PrMenu("efd_base_especifico", "E_FD", "Base para _Específico", "efd,dfe"));

function efd_livros_itensC170() {
  gera_efd_livros_itensC170('');
}

function efd_livros_itensC170_NFe() {
  gera_efd_livros_itensC170('NFe');
}

function efd_base_especifico() {
  gera_efd_livros_itensC170('NFe', 'base');
}

function gera_efd_livros_itensC170($tipo = '', $base = False) {

  global $pr;

  if (!$base) $pr->inicia_excel('EFD_LivFisC170_0200_C100_0150');
  if ($base)  $pr->inicia_excel('EFD_Base_Especifico');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';

  $pr->aud_prepara("
-- Criação de c170_NFe_Emit, parecido com DFe em modelo, para ficar mais fácil o resto
-- especialmente quanto à criação de chaves de acesso em casos de documentos não digitais
CREATE INDEX IF NOT EXISTS c100_chv_nfe ON c100 (chv_nfe ASC);
DROP TABLE IF EXISTS c170_NFe_Emit;
CREATE TABLE c170_NFe_Emit(
  origem, tp_origem, cnpj_origem, ie_origem, aaaamm, cod_sit INT, tp_oper, cst int, cfop INT, 
  valcon REAL, bcicms REAL, icms REAL, bcicmsst REAL, icmsst REAL, valipi REAL, valii REAL,
  emit, cnpj, ie, uf, razsoc, codncm, codpro, descri, qtdpro, unimed, dtaentsai_c100, dtaemi, 
  modelo, serie, numero INT, item INT, chav_ace, ord_c100 INT
);
");	
  if ($tipo == 'NFe') $pr->aud_prepara("
-- Inserindo agora as NFes de Emissão Própria (saída ou entrada), a partir de DFe, ##relacionando com C100##
INSERT INTO c170_NFe_Emit
  SELECT 
      'NFeEmisProp-C170NDisp' AS origem, 'RES' AS tp_origem, cnpj_origem, ie_origem,
      aaaamm, nfe.cod_sit, 
      tp_oper, cst, cfop,
      valcon, bcicms, icms, bcicmsst, icmsst, valipi, valii,
      'P' AS emit, cnpj, ie, uf, razsoc,
      codncm, codpro, descri, qtdpro, unimed,
      c100.dt_e_s AS dtaentsai_c100, dtaemi, 
      modelo, serie, nNF AS numero, nItem AS item, chav_ace, c100.ord AS ord_c100
      FROM nfe
      LEFT OUTER JOIN c100 ON c100.chv_nfe = chav_ace
      WHERE nfe.cod_sit = 0 AND nfe.origem = 'NFe_Emit';
");	
  $pr->aud_prepara("
DROP TABLE IF EXISTS c170_total; 
-- Preenchendo com os dados originais
CREATE TABLE c170_total AS SELECT * FROM c170;
");	
/*
Veja o manual do CV3 no tópico: emissão de NFe e escrituração na importação, lá está a explicação do porque está sendo deduzido o II aqui abaixo
Como a ideia é chegar no 'vl_item' do C170, que é "Valor total do item (mercadorias ou serviços)",
conforme conciliação C190xC170, temos que o total do C190 é C170.vl_item - C170.vl_desc + C170.vl_ipi + C170.vl_icms_st, considerando também que não há valor de desconto vindo da NFe, descontarei o ipi e icms_st abaixo
*/
  if ($tipo == 'NFe') $pr->aud_prepara("
-- Inserindo dados das NFes que constam como registradas no C100
INSERT INTO c170_total
SELECT origem AS ord, ord_c100 AS ordC100,
	  item AS num_item, codpro AS cod_item, 'NCM#' || codncm || '#' || descri AS descr_compl, qtdpro AS qtd, unimed AS unid,
	  valcon - valii - valipi - icmsst AS vl_item, 0 AS vl_desc, Null AS ind_mov, cst AS cst_icms, cfop, Null AS cod_nat,
	  bcicms AS vl_bc_icms, CASE WHEN bcicms > 0 THEN round(icms/bcicms*100,2) ELSE 0 END AS aliq_icms, icms AS vl_icms, 
	  bcicmsst AS vl_bc_icms_st, CASE WHEN bcicmsst > 0 THEN round(icmsst/bcicmsst*100,2) ELSE 0 END aliq_st, icmsst AS vl_icms_st,
	  Null AS ind_apur, Null AS cst_ipi, Null AS cod_enq,
	  Null AS vl_bc_ipi, Null AS aliq_ipi, valipi AS vl_ipi, Null AS cst_pis, Null AS vl_bc_pis, Null AS aliq_pis, Null AS quant_bc_pis,
	  Null AS aliq_pis_r, Null AS vl_pis, Null AS  cst_cofins, Null AS vl_bc_cofins, Null AS aliq_cofins, Null AS quant_bc_cofins,
	  Null AS aliq_cofins_r, Null AS vl_cofins, Null AS cod_cta
	  FROM c170_nfe_emit
	  WHERE ordC100 IS NOT NULL;
");	

  if (!$base) {

    // Planilha RegEntSaidaC170_0200_C100_0150 
    $sql = "
SELECT 
	  c170_total.*,  o200.*, 
	  c100.ord AS c100_ord, c100.ind_oper, c100.ind_emit, c100.cod_part, c100.cod_mod, c100.cod_sit,
	  c100.ser, c100.num_doc, '#' || c100.chv_nfe AS chv_nfe, c100.dt_doc, c100.dt_e_s, c100.vl_doc,
	  c100.ind_pgto, c100.vl_desc, c100.vl_abat_nt, c100.vl_merc,
	  c100.ind_frt, c100.vl_frt, c100.vl_seg, c100.vl_out_da,
	  c100.vl_bc_icms, c100.vl_icms, c100.vl_bc_icms_st, c100.vl_icms_st, c100.vl_ipi,
	  c100.vl_pis, c100.vl_cofins, c100.vl_pis_st, c100.vl_cofins_st, 
	  o150.*, 
	  round(c170_total.ordC100 / 10000000 - 0.49) * 10000000 AS ordmin, round(c170_total.ordC100 / 10000000 + 0.5) * 10000000 AS ordmax
  FROM c170_total
  LEFT OUTER JOIN c100 ON c100.ord = c170_total.ordC100
  LEFT OUTER JOIN o200 ON o200.cod_item = c170_total.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
  LEFT OUTER JOIN o150 ON o150.cod_part = c100.cod_part       AND o150.ord > ordmin AND o150.ord < ordmax;
";
    $col_format = array(
	"A:B" => "0",
	"D:D" => "0",
	"F:F" => "#.##0,000",
	"H:I" => "#.##0,00",
	"N:S" => "#.##0,00",
	"W:Y" => "#.##0,00",
	"AA:AE" => "#.##0,00",
	"AG:AK" => "#.##0,00",
	"AM:AN" => "0",
	"AT:AT" => "0",
	"AX:AX" => "#.##0,00",
	"AZ:AZ" => "0",
	"BC:BC" => "0",
	"BM:BO" => "#.##0,00",
	"BQ:CB" => "#.##0,00",
	"CC:CD" => "0",
	"CG:CI" => "0",
	"CP:CQ" => "0"
);
    $cabec = array(
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
	'cst_icms_C170' => "Código da Situação Tributária referente ao ICMS, conforme a Tabela indicada no item 4.3.1",
	'CFOP' => "Código Fiscal de Operação e Prestação",
	'cod_nat' => "Código da natureza da operação (campo 02 do Registro 0400)",
	'vl_bc_icms_c170' => "Valor da base de cálculo do ICMS",
	'aliq_icms_c170' => "Alíquota do ICMS",
	'vl_icms_c170' => "Valor do ICMS creditado/debitado",
	'vl_bc_icms_st_c170' => "Valor da base de cálculo referente à substituição tributária",
	'aliq_st_c170' => "Alíquota do ICMS da substituição tributária na unidade da federação de destino",
	'vl_icms_st_c170' => "Valor do ICMS referente à substituição tributária",
	'ind_apur' => "Indicador de período de apuração do IPI: 0 - Mensal; 1 - Decendial",
	'cst_ipi' => "Código da Situação Tributária referente ao IPI, conforme a Tabela indicada no item 4.3.2.",
	'cod_enq' => "Código de enquadramento legal do IPI, conforme tabela indicada no item 4.5.3.",
	'vl_bc_ipi_c170' => "Valor da base de cálculo do IPI",
	'aliq_ipi_c170' => "Alíquota do IPI",
	'vl_ipi_c170' => "Valor do IPI creditado/debitado",
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
	'c100_ord' => "Número da Linha do Registro C100",
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
	'vl_desc_c100' => "Valor total do desconto",
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
	'vl_pis_c100' => "Valor total do PIS",
	'vl_cofins_c100' => "Valor total da COFINS",
	'vl_pis_st_c100' => "Valor total do PIS retido por substituição tributária",
	'vl_cofins_st_c100' => "Valor total da COFINS retido por substituição tributária",
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
    $pr->abre_excel_sql('LivFiscC170_0200_C100_0150', 'LivFiscC170_0200_C100_0150', $sql, $col_format, $cabec, $form_final);

  }

  if ($base) {



    // Planilha Base para especifico C470
    $sql = "
-- Conforme manual do SPED, o CFOP de c470 sempre inicia por 5 !
-- vl_item do c470 é sempre o valor líquido, então não há o que se falar de desconto, etc
-- se o cupom for cancelado (c460.cod_sit = 2) o c470 não é informado
SELECT 
	  c470.ord AS ord, c460.ordC405 AS ordC405, '#'  AS chv_nfe, c470.ord - c470.ordC460 AS num_item, 
	  substr(o200.cod_ncm, 1, 2) AS cod_ncm2, 
	  substr(o200.cod_ncm, 1, 4) AS cod_ncm4, 
	  o200.cod_ncm cod_ncm, c470.cod_item AS cod_item, 
	  o200.descr_item AS descr_item, 
	  NULL AS descr_compl,
	  c470.qtd - (CASE WHEN qtd_canc > 0 THEN qtd_canc ELSE 0 END) AS qtd, c470.unid AS unid, 
	  c470.vl_item AS vl_oper, 
	  c470.vl_item AS vl_item, 0 AS vl_desc, 
	  0 AS ind_mov, c470.cst_icms AS cst_icms, 
	  c470.cfop AS cfop, cfopd.g1 AS g1, cfopd.g2 AS g2, cfopd.classe || ' ' || cfopd.descri_simplif AS cfop_descri,
	  Null AS cod_nat, 
	  c470.vl_item AS vl_bc_icms, c470.aliq_icms AS aliq_icms, 
	  round(c470.vl_item * c470.aliq_icms / 100, 2) AS vl_icms, 
	  0 AS vl_bc_icms_st, Null AS aliq_st, 
	  0 vl_icms_st, Null AS cod_cta,
	  c405.ord AS c405_ord, 1 AS ind_oper, 0 AS ind_emit, Null AS cod_part, 
	  c460.cod_mod AS cod_mod, c460.cod_sit AS cod_sit, Null AS ser, c460.num_doc AS num_doc, c460.dt_doc AS dt_doc, c460.dt_doc AS dt_e_s, Null AS ind_pagto, 
	  Null AS cod_part_o150, c460.cpf_cnpj AS cnpj_cpf,  Null AS ie, Null AS uf, c460.nom_adq AS nome, 
	  round(c470.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(c470.ord / 10000000 + 0.5) * 10000000 AS ordmax    
  FROM c470
  LEFT OUTER JOIN c460 ON c460.ord = c470.ordC460
  LEFT OUTER JOIN c405 ON c405.ord = c460.ordC405
  LEFT OUTER JOIN o200 ON o200.cod_item = c470.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
  LEFT OUTER JOIN cfopd ON cfopd.cfop = c470.cfop;
";
    $col_format = array(
	"A:B" => "0",
	"H:H" => "0",
	"K:K" => "#.##0,000_ ;[Vermelho]-#.##0,000 ",
	"M:M" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"N:O" => "#.##0,00",
	"Q:Q" => "000",
	"W:AB" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"AD:AD" => "0",
	"AG:AG" => "0",
	"AO:AQ" => "0",
	"AT:AU" => "0",
);
    $cabec = array(
	'OrdC470' => "Número da Linha do Registro C470",
	'OrdC405_C470' => "Número da Linha do Registro C405",
	'chv_nfe' => "Chave da Nota Fiscal Eletrônica",
	'num_item' => "Número Sequencial do Item no Documento Fiscal",
	'COD_NCM2' => "Primeiros 2 dígitos do Código da Nomenclatura Comum do Mercosul",
	'COD_NCM4' => "Primeiros 4 dígitos do Código da Nomenclatura Comum do Mercosul",
	'COD_NCM' => "Código da Nomenclatura Comum do Mercosul",
	'cod_item' => "Código do item (campo 02 do Registro 0200)",
	'descr_item' => "Descrição do item",
	'descr_compl' => "Descrição complementar do item como adotado no documento fiscal",
	'qtd_liq' => "Quantidade do item, negativo para entrada, positivo para saída",
	'unid' => "Unidade do item (Campo 02 do registro 0190)",
	'vl_oper' => "Negativo para entrada, total na mesma base do C190, ou seja, vl_item - vl_desc + vl_ipi + vl_icms_st",
	'vl_item' => "Valor total do item (mercadorias ou serviços)",
	'vl_desc' => "Valor do desconto comercial",
	'ind_mov' => "Movimentação física do ITEM/PRODUTO: 0. SIM 1. NÃO",
	'cst_icms_C470' => "Código da Situação Tributária referente ao ICMS, conforme a Tabela indicada no item 4.3.1",
	'cfop' => "Código Fiscal de Operação e Prestação",
	'g1' => "Agrupamento 1 de cfop",
	'g2' => "Agrupamento 2 de cfop",
	'cfop_descri' => "Classe de cfop e descrição simplificada",
	'cod_nat' => "Código da natureza da operação (campo 02 do Registro 0400)",
	'vl_bc_icms_c470' => "Valor da base de cálculo do ICMS",
	'aliq_icms_c470' => "Alíquota do ICMS",
	'vl_icms_c470' => "Valor do ICMS creditado/debitado",
	'vl_bc_icms_st_c470' => "Valor da base de cálculo referente à substituição tributária",
	'aliq_st_c470' => "Alíquota do ICMS da substituição tributária na unidade da federação de destino",
	'vl_icms_st_c470' => "Valor do ICMS referente à substituição tributária",
	'cod_cta' => "Código da conta analítica contábil debitada/creditada",
	'OrdC405' => "Número da Linha do Registro C405",
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
	'dt_doc' => "Data da emissão do documento fiscal",
	'dt_e_s' => "Data da entrada ou da saída",
	'ind_pgto' => "Indicador do tipo de pagamento:
0- À vista;
1- A prazo;
9- Sem pagamento.
Obs.: A partir de 01/07/2012 passará a ser:
Indicador do tipo de pagamento:
0- À vista;
1- A prazo;
2 - Outros",
	'cod_part_0150' => "Código de identificação do participante no arquivo",
	'cnpj_cpf' => "CNPJ do participante appended CPF do participante",
	'ie' => "Inscrição Estadual do participante",
	'uf' => "UF do participante",
	'nome' => "Nome pessoal ou empresarial do participante",
	'ordmin' => "Uso interno do conversor, para fins de relacionamento entre tabelas",
	'ordmax' => "Uso interno do conversor, para fins de relacionamento entre tabelas"
);
    $pr->abre_excel_sql('Base_EspecC470', 'Base para Especifico C470', $sql, $col_format, $cabec, $form_final);


    // Planilha Base para especifico C170
    $sql = "
SELECT 
	  c170_total.ord AS ord, c170_total.ordC100 AS ordC100, '#' || c100.chv_nfe AS chv_nfe, c170_total.num_item AS num_item, 
	  substr(CASE WHEN c170_total.ord = 'NFeEmisProp-C170NDisp' THEN substr(c170_total.descr_compl, 5, 8) ELSE o200.cod_ncm END, 1, 2) AS cod_ncm2, 
	  substr(CASE WHEN c170_total.ord = 'NFeEmisProp-C170NDisp' THEN substr(c170_total.descr_compl, 5, 8) ELSE o200.cod_ncm END, 1, 4) AS cod_ncm4, 
	  CASE WHEN c170_total.ord = 'NFeEmisProp-C170NDisp' THEN substr(c170_total.descr_compl, 5, 8) ELSE o200.cod_ncm END AS cod_ncm, c170_total.cod_item AS cod_item, 
	  CASE WHEN c170_total.ord = 'NFeEmisProp-C170NDisp' THEN substr(c170_total.descr_compl, 14, 255) ELSE o200.descr_item END AS descr_item, 
	  CASE WHEN c170_total.ord != 'NFeEmisProp-C170NDisp' THEN c170_total.descr_compl ELSE NULL END AS descr_compl,
	  CASE WHEN c170_total.cfop < 5000 THEN -c170_total.qtd ELSE c170_total.qtd END AS qtd, c170_total.unid AS unid, 
	  CASE WHEN c170_total.cfop < 5000 THEN -(c170_total.vl_item - c170_total.vl_desc + c170_total.vl_icms_st + c170_total.vl_ipi) 
	     ELSE c170_total.vl_item - c170_total.vl_desc + c170_total.vl_icms_st + c170_total.vl_ipi END AS vl_oper, 
	  c170_total.vl_item AS vl_item, c170_total.vl_desc AS vl_desc, 
	  c170_total.ind_mov AS ind_mov, c170_total.cst_icms AS cst_icms, 
	  c170_total.CFOP AS cfop, cfopd.g1 AS g1, cfopd.g2 AS g2, cfopd.classe || ' ' || cfopd.descri_simplif AS cfop_descri,
	  c170_total.cod_nat AS cod_nat, 
	  CASE WHEN c170_total.cfop < 5000 THEN -c170_total.vl_bc_icms ELSE c170_total.vl_bc_icms END AS vl_bc_icms, c170_total.aliq_icms AS aliq_icms, 
	  CASE WHEN c170_total.cfop < 5000 THEN -c170_total.vl_icms ELSE c170_total.vl_icms END AS vl_icms, 
	  CASE WHEN c170_total.cfop < 5000 THEN -c170_total.vl_bc_icms_st ELSE c170_total.vl_bc_icms_st END AS vl_bc_icms_st, c170_total.aliq_st AS aliq_st, 
	  CASE WHEN c170_total.cfop < 5000 THEN -c170_total.vl_icms_st ELSE c170_total.vl_icms_st END AS vl_icms_st, c170_total.cod_cta,
	  c100.ord AS c100_ord, c100.ind_oper AS ind_oper, c100.ind_emit AS ind_emit, c100.cod_part AS cod_part, 
	  c100.cod_mod AS cod_mod, c100.cod_sit AS cod_sit, c100.ser AS ser, c100.num_doc AS num_doc, c100.dt_doc AS dt_doc, c100.dt_e_s AS dt_e_s, c100.ind_pgto AS ind_pagto, 
	  o150.cod_part AS cod_part_o150, o150.cnpj || o150.cpf AS cnpj_cpf,  o150.ie AS ie, tab_munic.uf AS uf, o150.nome AS nome, 
	  round(c170_total.ordC100 / 10000000 - 0.49) * 10000000 AS ordmin, round(c170_total.ordC100 / 10000000 + 0.5) * 10000000 AS ordmax
  FROM c170_total
  LEFT OUTER JOIN c100 ON c100.ord = c170_total.ordC100
  LEFT OUTER JOIN o200 ON o200.cod_item = c170_total.cod_item AND o200.ord > ordmin AND o200.ord < ordmax
  LEFT OUTER JOIN o150 ON o150.cod_part = c100.cod_part       AND o150.ord > ordmin AND o150.ord < ordmax
  LEFT OUTER JOIN tab_munic ON tab_munic.cod = o150.cod_mun
  LEFT OUTER JOIN cfopd ON cfopd.cfop = c170_total.cfop;
";
    $col_format = array(
	"A:B" => "0",
	"H:H" => "0",
	"K:K" => "#.##0,000_ ;[Vermelho]-#.##0,000 ",
	"M:M" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"N:O" => "#.##0,00",
	"Q:Q" => "000",
	"W:AB" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"AD:AD" => "0",
	"AG:AG" => "0",
	"AO:AQ" => "0",
	"AT:AU" => "0",
);
    $cabec = array(
	'OrdC170' => "Número da Linha do Registro C170",
	'OrdC100_C170' => "Número da Linha do Registro C100",
	'chv_nfe' => "Chave da Nota Fiscal Eletrônica",
	'num_item' => "Número Sequencial do Item no Documento Fiscal",
	'COD_NCM2' => "Primeiros 2 dígitos do Código da Nomenclatura Comum do Mercosul",
	'COD_NCM4' => "Primeiros 4 dígitos do Código da Nomenclatura Comum do Mercosul",
	'COD_NCM' => "Código da Nomenclatura Comum do Mercosul",
	'cod_item' => "Código do item (campo 02 do Registro 0200)",
	'descr_item' => "Descrição do item",
	'descr_compl' => "Descrição complementar do item como adotado no documento fiscal",
	'qtd_liq' => "Quantidade do item, negativo para entrada, positivo para saída",
	'unid' => "Unidade do item (Campo 02 do registro 0190)",
	'vl_oper' => "Negativo para entrada, total na mesma base do C190, ou seja, vl_item - vl_desc + vl_ipi + vl_icms_st",
	'vl_item' => "Valor total do item (mercadorias ou serviços)",
	'vl_desc' => "Valor do desconto comercial",
	'ind_mov' => "Movimentação física do ITEM/PRODUTO: 0. SIM 1. NÃO",
	'cst_icms_C170' => "Código da Situação Tributária referente ao ICMS, conforme a Tabela indicada no item 4.3.1",
	'cfop' => "Código Fiscal de Operação e Prestação",
	'g1' => "Agrupamento 1 de cfop",
	'g2' => "Agrupamento 2 de cfop",
	'cfop_descri' => "Classe de cfop e descrição simplificada",
	'cod_nat' => "Código da natureza da operação (campo 02 do Registro 0400)",
	'vl_bc_icms_c170' => "Valor da base de cálculo do ICMS",
	'aliq_icms_c170' => "Alíquota do ICMS",
	'vl_icms_c170' => "Valor do ICMS creditado/debitado",
	'vl_bc_icms_st_c170' => "Valor da base de cálculo referente à substituição tributária",
	'aliq_st_c170' => "Alíquota do ICMS da substituição tributária na unidade da federação de destino",
	'vl_icms_st_c170' => "Valor do ICMS referente à substituição tributária",
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
	'dt_doc' => "Data da emissão do documento fiscal",
	'dt_e_s' => "Data da entrada ou da saída",
	'ind_pgto' => "Indicador do tipo de pagamento:
0- À vista;
1- A prazo;
9- Sem pagamento.
Obs.: A partir de 01/07/2012 passará a ser:
Indicador do tipo de pagamento:
0- À vista;
1- A prazo;
2 - Outros",
	'cod_part_0150' => "Código de identificação do participante no arquivo",
	'cnpj_cpf' => "CNPJ do participante appended CPF do participante",
	'ie' => "Inscrição Estadual do participante",
	'uf' => "UF do participante",
	'nome' => "Nome pessoal ou empresarial do participante",
	'ordmin' => "Uso interno do conversor, para fins de relacionamento entre tabelas",
	'ordmax' => "Uso interno do conversor, para fins de relacionamento entre tabelas"
);
    $pr->abre_excel_sql('Base_EspecC170', 'Base para Especifico C170', $sql, $col_format, $cabec, $form_final);

  }

  // Conciliação C470xC490 
  $sql = "
SELECT ordC405, vl_oper_c490, vL_oper_c470, round(vl_oper_c470 - vL_oper_c490, 2) AS dif ,
  Null AS vl_frt, Null AS vl_seg, Null AS vl_out_da, 
  cfops_c490, cfops_c470, CASE WHEN cfops_c490 <> cfops_c470 THEN 'S' ELSE 'N' END AS c_dif,
  Null AS chv_nfe
  FROM
    (SELECT ordC405, 
      sum(CASE WHEN orig = 'c490' THEN vl_oper ELSE 0 END) AS vl_oper_c490,
      sum(CASE WHEN orig = 'c470' THEN vl_oper ELSE 0 END) AS vl_oper_c470,
      group_concat(CASE WHEN orig = 'c490' THEN cfops ELSE '' END,'') AS cfops_c490,
      group_concat(CASE WHEN orig = 'c470' THEN cfops ELSE '' END,'') AS cfops_c470
      FROM 
            (SELECT ordC405, 'c490' AS orig, group_concat(cfop) AS cfops, sum(vl_opr) AS vl_oper FROM 
	  (SELECT ordC405, 'c490' AS orig, cfop, sum(vl_opr) AS vl_opr FROM c490 GROUP BY ordC405, cfop)
	  GROUP BY ordC405
        UNION ALL
        SELECT ordC405, 'c470' AS orig, group_concat(cfop) AS cfops, sum(vl_oper) AS vl_oper FROM 
	  (SELECT c460.ordC405 AS ordC405, 'c470' AS orig, cfop, sum(vl_item) AS vl_oper FROM c470
              LEFT OUTER JOIN c460 ON c460.ord = c470.ordC460 GROUP BY ordC405, cfop)
	  GROUP BY ordC405)
    GROUP BY ordC405)
LEFT OUTER JOIN c405 ON c405.ord  = ordC405;
";
  $col_format = array(
	"A:A" => "0",
	"B:G" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
    $cabec = array(
	'OrdC405_C470' => "Número da Linha do Registro C405",
	'vl_oper_c490' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_oper_c470' => "Os seguintes campos do C470: vl_item",
	'dif' => "Diferença entre os dois campos à esquerda",
	'vl_frt_C405' => "Valor do Frete constante no C405",
	'vl_seg_C405' => "Valor do Frete constante no C405",
	'vl_out_da_C405' => "Valor do Frete constante no C405",
	'cfops_c490' => "Lista de CFOPs que compões este C405, conforme C490",
	'cfops_c470' => "Lista de CFOPs que compões este C405, conforme C470",
	'c_dif' => "S caso haja diferença entre cfops de c490 e c470",
	'chv_nfe' => "Chave de Acesso (nulo)"
);
  $pr->abre_excel_sql('ConcC490xC470', 'Conciliação C490 x C470', $sql, $col_format, $cabec, $form_final);

  // Conciliação C170xC190 
  $sql = "
SELECT ordC100, vl_oper_c190, vL_oper_c170, round(vl_oper_c170 - vL_oper_c190, 2) AS dif ,
  vl_frt, vl_seg, vl_out_da, 
  cfops_c190, cfops_c170, CASE WHEN cfops_c190 <> cfops_c170 THEN 'S' ELSE 'N' END AS c_dif,
  chv_nfe
  FROM
    (SELECT ordC100, 
      sum(CASE WHEN orig = 'c190' THEN vl_oper ELSE 0 END) AS vl_oper_c190,
      sum(CASE WHEN orig = 'c170' THEN vl_oper ELSE 0 END) AS vl_oper_c170,
      group_concat(CASE WHEN orig = 'c190' THEN cfops ELSE '' END,'') AS cfops_c190,
      group_concat(CASE WHEN orig = 'c170' THEN cfops ELSE '' END,'') AS cfops_c170
      FROM 
        (SELECT ordC100, 'c190' AS orig, group_concat(cfop) AS cfops, sum(vl_opr) AS vl_oper FROM 
	  (SELECT ordC100, 'c190' AS orig, cfop, sum(vl_opr) AS vl_opr FROM c190 GROUP BY ordC100, cfop)
	  GROUP BY ordC100
        UNION ALL
        SELECT ordC100, 'c170' AS orig, group_concat(cfop) AS cfops, sum(vl_oper) AS vl_oper FROM 
	  (SELECT ordC100, 'c170' AS orig, cfop, sum(vl_item - vl_desc + vl_icms_st + vl_ipi) AS vl_oper FROM c170_total GROUP BY ordC100, cfop)
	  GROUP BY ordC100)
    GROUP BY ordC100)
LEFT OUTER JOIN c100 ON c100.ord  = ordC100;
";
  $col_format = array(
	"A:A" => "0",
	"B:G" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
    $cabec = array(
	'OrdC100_C170' => "Número da Linha do Registro C100",
	'vl_oper_c190' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_oper_c170' => "Os seguintes campos do C170: vl_item - vl_desc + vl_ipi + vl_icms_st",
	'dif' => "Diferença entre os dois campos à esquerda",
	'vl_frt_C100' => "Valor do Frete constante no C100",
	'vl_seg_C100' => "Valor do Frete constante no C100",
	'vl_out_da_C100' => "Valor do Frete constante no C100",
	'cfops_c190' => "Lista de CFOPs que compões este C100, conforme C190",
	'cfops_c170' => "Lista de CFOPs que compões este C100, conforme C170",
	'c_dif' => "S caso haja diferença entre cfops de c190 e c170",
	'chv_nfe' => "Chave de Acesso"
);
  $pr->abre_excel_sql('ConcC190xC170', 'Conciliação C190 x C170', $sql, $col_format, $cabec, $form_final);

  $tabela = 'cfopd';
  $sql = "SELECT * FROM {$tabela};";
  $col_format = array(
);
  $cabec = $pr->auto_cabec($tabela);
  $pr->abre_excel_sql(substr($tabela, 0, 15), $tabela, $sql, $col_format, $cabec, $form_final);
  
  $pr->finaliza_excel();
}


?>