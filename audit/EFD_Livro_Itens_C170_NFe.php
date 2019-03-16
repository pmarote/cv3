<?php

$pr->aud_registra(new PrMenu("efd_livros_itensC170_NFe", "E_FD", "Livro Fiscal C170 _com NFe", "efd,dfe"));
$pr->aud_registra(new PrMenu("efd_livros_itensC170",     "E_FD", "Livro Fiscal C170 _sem NFe", "efd"));

function efd_livros_itensC170() {
  gera_efd_livros_itensC170('');
}

function efd_livros_itensC170_NFe() {
  gera_efd_livros_itensC170('NFe');
}

function gera_efd_livros_itensC170($tipo = '') {

  global $pr;

  $pr->inicia_excel('EFD_LivFisC170_0200_C190_C100_0150');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';

  $pr->aud_prepara("
-- Criação de c190_cfop, ou seja, sem separar por alíquotas e cst
-- Porque há varias inconsistências especialmente do código de origem (CST > 100). Exemplo: diferença NFe x c190 ou NFe x c170 e inclusive difer cst c170 x c190
DROP TABLE IF EXISTS c190_cst02_cfop;
CREATE TABLE c190_cfop (
	  ord int, ordC100 int,
	  cfop int, vl_opr real, vl_bc_icms real, vl_icms real, 
	  vl_bc_icms_st real, vl_icms_st real, vl_red_bc real, vl_ipi real, cod_obs );
INSERT INTO c190_cfop	  
  SELECT 'c190_cfop' AS ord, ordC100, cfop,
	sum(vl_opr) AS vl_opr, sum(vl_bc_icms) AS vl_bc_icms, sum(vl_icms) AS vl_icms, 
	sum(vl_bc_icms_st) AS vl_bc_icms_st, sum(vl_icms_st) AS vl_icms_st, sum(vl_red_bc) AS vl_red_bc, sum(vl_ipi) AS vl_ipi, 
	cod_obs
	FROM c190
	GROUP BY ordC100, cfop;
CREATE INDEX c190_cfop_ordC100 ON c190_cfop (ordC100 ASC);
");	
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
      WHERE nfe.cod_sit = 0;
");	
  $pr->aud_prepara("
DROP TABLE IF EXISTS c170_total; 
-- Preenchendo com os dados originais
CREATE TABLE c170_total AS SELECT * FROM c170;
");	
  if ($tipo == 'NFe') $pr->aud_prepara("
-- Inserindo dados das NFes que constam como registradas no C100
INSERT INTO c170_total
SELECT null AS ord, ord_c100 AS ordC100,
	  item AS num_item, codpro AS cod_item, 'NCM#' || codncm || '#' || descri AS descr_compl, qtdpro AS qtd, unimed AS unid,
	  valcon AS vl_item, 0 AS vl_desc, Null AS ind_mov, cst AS cst_icms, cfop, Null AS cod_nat,
	  bcicms AS vl_bc_icms, CASE WHEN bcicms > 0 THEN round(icms/bcicms*100,2) ELSE 0 END AS aliq_icms, icms AS vl_icms, 
	  bcicmsst AS vl_bc_icms_st, CASE WHEN bcicmsst > 0 THEN round(icmsst/bcicmsst*100,2) ELSE 0 END aliq_st, icmsst AS vl_icms_st,
	  Null AS ind_apur, Null AS cst_ipi, Null AS cod_enq,
	  Null AS vl_bc_ipi, Null AS aliq_ipi, valipi AS vl_ipi, Null AS cst_pis, Null AS vl_bc_pis, Null AS aliq_pis, Null AS quant_bc_pis,
	  Null AS aliq_pis_r, Null AS vl_pis, Null AS  cst_cofins, Null AS vl_bc_cofins, Null AS aliq_cofins, Null AS quant_bc_cofins,
	  Null AS aliq_cofins_r, Null AS vl_cofins, Null AS cod_cta
	  FROM c170_nfe_emit
	  WHERE ordC100 IS NOT NULL;
");	
  // Planilha RegEntSaidaC190_C170_0200_C100_0150 
  $sql = "
SELECT 
	  c170_total.*,  o200.*, c190_cfop.*,
	  c100.ord AS c100_ord, c100.ind_oper, c100.ind_emit, c100.cod_part, c100.cod_mod, c100.cod_sit,
	  c100.ser, c100.num_doc, '#' || c100.chv_nfe AS chv_nfe, c100.dt_doc, c100.dt_e_s, c100.vl_doc,
	  c100.ind_pgto, c100.vl_desc, c100.vl_abat_nt, c100.vl_merc,
	  c100.ind_frt, c100.vl_frt, c100.vl_seg, c100.vl_out_da,
	  c100.vl_bc_icms, c100.vl_icms, c100.vl_bc_icms_st, c100.vl_icms_st, c100.vl_ipi,
	  c100.vl_pis, c100.vl_cofins, c100.vl_pis_st, c100.vl_cofins_st, 
	  o150.*, 
	  round(c170_total.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(c170_total.ord / 10000000 + 0.5) * 10000000 AS ordmax
  FROM c170_total
  LEFT OUTER JOIN c100 ON c100.ord = c170_total.ordC100
  LEFT OUTER JOIN c190_cfop ON c190_cfop.ordC100 = c100.ord AND c190_cfop.cfop = c170_total.cfop
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
	"AZ:BA" => "0",
	"BC:BI" => "#.##0,00",
	"BK:BK" => "0",
	"BX:BZ" => "#.##0,00",
	"CB:CM" => "#.##0,00",
	"CN:CO" => "0",
	"CR:CT" => "0"
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
	'c190_agrup' => "Registro C190 desconsiderando cst e alíquota, agrupado apenas por CFOP
Houve essa necessidade porque há várias inconsistências no código da origem, inclusive entre C170 e C190",
	'OrdC100' => "Número da Linha do Registro C100",
	'cfop' => "Código Fiscal de Operação e Prestação do agrupamento de itens", 
	'vl_opr' => "Valor da operação na combinação de CST_ICMS, CFOP e alíquota do ICMS, correspondente ao somatório do valor das mercadorias, despesas acessórias (frete, seguros e outras despesas acessórias), ICMS_ST  e IPI",
	'vl_bc_icms' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms' => "Parcela correspondente ao 'Valor do ICMS' referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_bc_icms_st' => "Parcela correspondente ao 'Valor da base de cálculo do ICMS' da substituição tributária referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_icms_st' => "Parcela correspondente ao valor creditado/debitado do ICMS da substituição tributária, referente à combinação de CST_ICMS, CFOP, e alíquota do ICMS.",
	'vl_red_bc' => "Valor não tributado em função da redução da base de cálculo do ICMS, referente à combinação de CST_ICMS, CFOP e alíquota do ICMS.",
	'vl_ipi' => "Parcela correspondente ao 'Valor do IPI' referente à combinação CST_ICMS, CFOP e alíquota do ICMS.",
	'cod_obs' => "Código da observação do lançamento fiscal (campo 02 do Registro 0460)",
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
  $pr->abre_excel_sql('LivFiscC170_0200_C190_C100_0150', 'LivFiscC170_0200_C190_C100_0150', $sql, $col_format, $cabec, $form_final);
  
  $pr->finaliza_excel();
}


?>