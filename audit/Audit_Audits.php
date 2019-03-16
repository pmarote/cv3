<?php

$pr->aud_registra(new PrMenu("audit_audits", "_Audit", "Auditorias", "audit"));

function audit_audits() {

	global $pr;

	$lista_tabelas = array(
	0 => "Lista de cnpjs que constem na tabela modelo",
	1 => "aud_modelo (requisito para os demais abaixo)",
	2 => "Resumo Geral",
	3 => "Conciliações",
	4 => "Mapas",
	5 => "Espelhos NFes, estilo Danfe (acima R$5.000,00)"
);

	$dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
	$dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
	$dialog->set_default_size(400, 100);
	$lbl_obs1 	= new GtkLabel("Este módulo cria e exporta auditorias, gravadas em audit.db3:");
	$dialog->vbox->pack_start($lbl_obs1, false, false, 3);
	$lbl_obs2 	= new GtkLabel("Lembre-se! Cada opção a seguir depende que a superior seja gerada antes!");
	$dialog->vbox->pack_start($lbl_obs2, false, false, 3);
	$lbl_obs3 	= new GtkLabel("(a inferior depende da superior)");
	$dialog->vbox->pack_start($lbl_obs3, false, false, 3);

	$chkbuttons = array();
	foreach ($lista_tabelas as $indice => $valor) {
		$chkbuttons[$indice] = new GtkCheckButton(str_replace('_', '__', $valor));
		$chkbuttons[$indice]->set_active(True);
		$dialog->vbox->pack_start($chkbuttons[$indice], false, false, 3);
	}
	$dialog->add_button("Inverter Seleção", 100);
	$dialog->add_button(Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL);
	$dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);
	$dialog->set_has_separator(True);
	$dialog->show_all();
	do {
		$response_id = $dialog->run();
		if ($response_id == 100) {
			foreach ($lista_tabelas as $indice => $valor) {
				$chkbuttons[$indice]->set_active(!$chkbuttons[$indice]->get_active());
			}
		}
	} while ($response_id == 100);
	if ($response_id != Gtk::RESPONSE_OK) {
		$dialog->destroy();
		return;
	}
	$dialog->destroy();

	$restricao_data_modelo = "  ";
	// $restricao_data_modelo = " AND aaaamm BETWEEN '201004' AND '201006' ";

	if ($chkbuttons[0]->get_active() || $chkbuttons[1]->get_active() || 
		$chkbuttons[2]->get_active() || $chkbuttons[3]->get_active() || $chkbuttons[4]->get_active())
		$pr->inicia_excel('Audit_Auditorias');

	$form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(80);
';

  if ($chkbuttons[0]->get_active()) {

	// Planilha CNPJs
	$sql = "
SELECT DISTINCT cnpj FROM modelo;
";
	$col_format = array(
	"A:A" => "0"
);
	$cabec = array(
	'cnpj' => "Lista dos Diferentes cnpjs que constam na tabela modelo, para buscar no BO"
);
	$pr->abre_excel_sql('cnpj', 'Lista de cnpjs que constem na tabela modelo', $sql, $col_format, $cabec, $form_final);

  }

  if ($chkbuttons[1]->get_active()) {

	$pr->aud_prepara("
-- primeiro vou criar um índice para acelerar o procedimento, que tem sido muito longo em algumas empresas grandes...
CREATE INDEX IF NOT EXISTS modelocnpj ON modelo (cnpj ASC);
-- O trecho abaixo para criar cnpj_uf_razsoc, com os dados mais atualizados possíveis, por isso criei o índice acima
DROP TABLE IF EXISTS cnpj_uf_razsoc;
CREATE TABLE cnpj_uf_razsoc AS
    SELECT cnpj, substr('00000000000000' || cnpj, -14)  || '_' || substr('  ' || uf, -2) || '_' ||  
    CASE WHEN razsoc IS NULL THEN '' ELSE substr(razsoc, 1, 30) END 
    AS cnpj_uf_razsoc, max(aaaamm) AS max_aaaamm FROM modelo WHERE cnpj IS NOT NULL GROUP BY cnpj;
CREATE INDEX IF NOT EXISTS cnpj_uf_razsocchapri ON cnpj_uf_razsoc (cnpj ASC);    
DROP TABLE IF EXISTS aud_modelo;
CREATE TABLE aud_modelo AS 
  SELECT 
    modelo.origem, tp_origem, dtaentsai, 
    CASE WHEN modelo.chav_ace = '' OR modelo.chav_ace IS Null 
       THEN 'Cv' || substr('00000000000000' || modelo.cnpj, -14) || 'm' || substr('00' || modelo, -2) || 's' || substr('000' || serie, -3) || 'n' || substr('000000000' || numero, -9)
	ELSE modelo.chav_ace
    END AS  chav_ace,
    modelo.cnpj_origem, aaaamm, substr(aaaamm, 1, 4) AS ano, cod_sit, 
    tp_oper, cst, modelo.cfop AS cfop, 
    CASE WHEN substr(modelo.cfop, 2, 1) = '4' THEN 'S' ELSE 'N' END AS st_cfop,
    CASE WHEN cst IN (10, 30, 60, 70) THEN 'S' ELSE
        CASE WHEN  cst IS NULL THEN Null ELSE 'N' END
    END AS st_cst,	
    CASE WHEN substr(modelo.cfop, 1, 1) IN ('1', '5') THEN 'D' ELSE
        CASE WHEN substr(modelo.cfop, 1, 1) IN ('2', '6') THEN 'F' ELSE 'I' END
    END AS d_f_i,
    cfop_nf, 
    CASE WHEN tp_oper = 'E' THEN -valcon ELSE valcon END AS valcon, 
    CASE WHEN tp_oper = 'E' THEN -bcicms ELSE bcicms END AS bcicms, 
    alicms,
    CASE WHEN tp_oper = 'E' THEN -icms ELSE icms END AS icms, 
    CASE WHEN tp_oper = 'E' THEN -outimp ELSE outimp END AS outimp, 
    CASE WHEN tp_oper = 'E' THEN -bcicmsst ELSE bcicmsst END AS bcicmsst, 
    alicmsst,
    CASE WHEN tp_oper = 'E' THEN -icmsst ELSE icmsst END AS icmsst, 
    modelo.cnpj AS cnpj, ie, uf, razsoc, cnpj_uf_razsoc, dtaina, descina, 
    dtaemi, modelo, serie, numero, 
    classe, g1, c3, g2, g3, descri_simplif,
    substr(g1, 1, 1) || substr('00' || g2, -2) || substr('00' || g3, -2) || ' ' || classe || ' ' || descri_simplif AS ordem,
    pod_creditar AS p_cred,
    CASE WHEN dtaentsai > dtaina AND dtaina > '1800-01-01' THEN valcon ELSE null END AS inat_valcon,
    CASE WHEN dtaentsai > dtaina AND dtaina > '1800-01-01' THEN icms ELSE null END AS inat_icms,
    CASE WHEN dtaentsai > dtaina AND dtaina > '1800-01-01' THEN icmsst ELSE null END AS inat_icmsst,
    CASE WHEN tp_oper = 'E' AND uf = 'SP' THEN round(bcicms * 18% - icms, 2) END AS a3_01_9_d,
    CASE WHEN tp_oper = 'E' AND uf IS NOT Null AND uf <> '' AND UF <> 'SP' AND uf <> 'EX' THEN round(bcicms * 12% - icms, 2) END AS a3_01_11_1,
    CASE WHEN tp_oper = 'E' AND substr(modelo.cfop, 2, 1) = '4' AND substr(modelo.cfop, 2, 3) <> '410' AND substr(modelo.cfop, 2, 3) <> '411' THEN icms END AS a3_01_12_d_f,
    CASE WHEN tp_oper = 'E' AND pod_creditar = 'N' THEN icms END AS a3_01_12_o,
    CASE WHEN tp_oper = 'S' AND uf = 'SP' THEN round(- bcicms * 18% + icms, 2) END AS a3_01_32_1,
    CASE WHEN tp_oper = 'S' AND uf = 'SP' THEN round(- valcon * 18% + icms, 2) END AS a3_01_32_1_vc,
    CASE WHEN tp_oper = 'S' AND uf NOT IN ('EX', 'SP') AND alicms < (CASE WHEN uf IN ('RS', 'SC', 'PR', 'RJ', 'MG') THEN 12 ELSE 7 END)
                  THEN round(-bcicms * (CASE WHEN uf IN ('RS', 'SC', 'PR', 'RJ', 'MG') THEN 12 ELSE 7 END) / 100 + icms, 2) END AS a3_01_32_2,
    CASE WHEN tp_oper = 'S' AND uf NOT IN ('EX', 'SP') AND (ie = '' OR trim(upper(ie)) = 'ISENTO') THEN round((-bcicms * 18 / 100) + icms, 2) END AS a3_01_33_1
    FROM modelo
    LEFT OUTER JOIN cfopd ON cfopd.cfop = modelo.cfop
    LEFT OUTER JOIN cnpj_uf_razsoc ON cnpj_uf_razsoc.cnpj = modelo.cnpj
    WHERE 1=1 {$restricao_data_modelo};
");
  
	// Planilha aud_modelo
	$tabela = 'aud_modelo';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"E:E" => "0",
	"X:Y" => "0",
	"P:W" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"AQ:AY" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = $pr->auto_cabec($tabela);
	
	$cabec = array(
	'origem' => "arquivo que deu a origem",
	'tp_origem' => "GIA, RES ou DFe",
	'dtaentsai' => "Data de Entrada ou Saída",
	'chav_ace' => "Chave de Acesso",
	'cnpj_origem' => "CNPJ da empresa sob análise",
	'aaaamm' => "Ano e Mês",
	'ano' => "Ano",
	'cod_sit' => "Código da Situação,valor inteiro conforme Tab 4.1.2 da EFD. 
Se não for informado, este código será preenchido automaticamente com zero.
Código Descrição
0 Documento regular
1 Documento regular extemporâneo
2 Documento cancelado
3 Documento cancelado extemporâneo
4 NF-e ou CT-e - denegado
5 NF-e ou CT-e - Numeração inutilizada
6 Documento Fiscal Complementar
7 Documento Fiscal Complementar extemporâneo.
8 Documento Fiscal emitido com base em Regime Especial ou Norma Específica
10 (Criado pelo Conversor) Notas Fiscais de Entrada emitidas por terceiros, que entram como saída em modelo (mas não são levadas para aud_modelo)",
	'tp_oper' => "Tipo de Operação, do ponto de vista da empresa fiscalizada: E - Entrada   S - Saída
Em DFe, pode ser também D - Devolução, que são as NFes emitidas por terceiros onde encontra a empresa fisc. como remetente",
	'cst' => "Código da Situação Tributária OCC O=Origem C=Código, podendo ser:
00 - Tributada integralmente
10 - Tributada e com cobrança do ICMS por ST
20 - Com redução de base de cálculo
30 - Isenta/Não tributada e com cobrança do ICMS por ST
40 - Isenta  
41 - Não Tributada
50 - Suspensão
51 - Diferimento
60 - ICMS   Cobrado Anteriormente por ST
70 - Com redução de base de cálculo cobrança do ICMS por ST
90 - Outras

0 - Nacional, exceto as indicadas nos códigos 3 a 5;
1 - Estrangeira - Importação direta, exceto a indicada no código 6;
2 - Estrangeira - Adquirida no mercado interno, exceto a indicada no código 7;
3 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a  40%;
4 - Nacional, cuja produção tenha sido feita em conformidade com os processos produtivos básicos;
5 - Nacional, mercadoria ou bem com Conteúdo de Importação inferior ou igual a 40%;
6 - Estrangeira - Importação direta, sem similar nacional;
7 - Estrangeira - Adquirida no mercado interno, sem similar nacional;",
	'cfop' => "Código Fiscal de Operação e Prestação.
 No caso de auditoria em NFe, se for de emissão de terceiros, este não será o CFOP constante na NFe, mas o CFOP conforme tabela de conversão em _sistema/tabelas/cfop_entsai.txt .",
	'st_cfop' => "",
	'st_cst' => "",
	'd_f_i' => "Dentro (SP), Fora (UFs) ou Internacional (Exportação ou Importação",
	'cfop_nf' => "No caso de auditoria em NFe, Código Fiscal de Operação e Prestação constante na NFe.
 No caso de auditoria em NFe, se for de emissão de terceiros, este será o CFOP constante na NFe e o campo anterior conterá o CFOP conforme tabela de conversão em _sistema/tabelas/cfop_entsai.txt .",
	'valcon' => "Valor Contábil, ou valor total do Produto nos casos da NFe",
	'bcicms' => "Base de Cálculo do ICMS",
	'alicms' => "Alíquota do ICMS",
	'icms' => "Valor do ICMS",
	'outimp' => "Outros Impostos, como IPI e II
No manual da GIA, a descrição é: Lançam-se aqui os valores de IPI ou outros para ajustar a soma da nota fiscal
Nas DFes, é a soma de valipi (vIPI na DFe) e valii (vII na DFe) de cada item
Nas RES-EFD vem apenas o valor do ipi, somente do C190 (VL_IPI do C190)",
	'bcicmsst' => "Base de Cálculo do ICMS-ST",
	'alicmsst' => "Alíquota do ICMS-ST",
	'icmsst' => "Valor do ICMS-ST",
	'cnpj' => "CNPJ do destinatário, quando saída ou emitente, quando entrada.",
	'ie' => "IE do destinatário, quando saída ou emitente, quando entrada.",
	'uf' => "UF do destinatário, quando saída ou emitente, quando entrada.",
	'razsoc' => "Razão Social do destinatário, quando saída ou emitente, quando entrada.",
	'cnpj_uf_razsoc' => "Os três campos anteriores juntos, com conteúdos sempre iguais (sem alteração de razão social - ele pega o primeiro), para fins utilização prática em Tabela Dinâmica",
	'dtaina' => "Data da Inatividade (se inativo) do destinatário, quando saída ou emitente, quando entrada.",
	'descina' => "Descrição da Inatividade (se inativo) do destinatário, quando saída ou emitente, quando entrada.",
	'dtaemi' => "Data de Emissão",
	'modelo' => "Modelo do Documento Fiscal",
	'serie' => "Série do Documento Fiscal",
	'numero' => "Número do Documento Fiscal",
	'classe' => "Classe da CFOP, normalmente utilizado em subclassificação de g1",
	'g1' => "Agrupamento 1 de CFOPs",
	'c3' => "Agrupamento c3 de CFOPs (usado em comércio)",
	'g2' => "Agrupamento 2 de CFOPs",
	'g3' => "Agrupamento 3 de CFOPs",
	'descri_simplif' => "Descrição simplificada do CFOP para não ocupar muito espaço",
	'ordem' => "Ordem da CFOP com descrição, normalmente utilizado em subclassificação de g1",
	'p_cred' => "Pode Creditar? Possibilidade de crédito, exclusivamente de acordo com o CFOP",
	'inat_valcon' => "CNPJ remetente/destinatário inativo na data da operação - Retorna o valor contábil.",
	'inat_icms' => "CNPJ remetente/destinatário inativo na data da operação - Retorna o valor do icms.",
	'inat_icmsst' => "CNPJ remetente/destinatário inativo na data da operação - Retorna o valor do icms st.",
	'3_01 9_d' => "Roteiro 3.01 - 9.+ - NFs de Entradas Internas - Valor do ICMS declarado menos Valor do ICMS calculado (BC * 18%).",
	'3_01 11_1' => "Roteiro 3.01 - 11.1 - NFs de Entradas Interestaduais - ICMS menos (BC ICMS * 12%).",
	'3_01 12_d_f' => "Roteiro 3.01 - 12.d(internas) e f(interestaduais) - NFs de Entradas - Aproveitamento de ICMS em entradas com ST.",
	'3_01 12_o' => "Roteiro 3.01 - NFs de Entradas - Aproveitamento de ICMS em entradas onde pod_cred = 'N'.",
	'3_01 32_1' => "Roteiro 3.01 - 32.1 - NFs de Saídas Internas - ICMS calculado (BC * 18%) menos ICMS Declarado",
	'3_01 32_1_vc' => "Roteiro 3.01 - 32.1 - NFs de Saídas Internas - ICMS calculado pelo Valor Contábil * 18% menos ICMS Declarado",
	'3_01 32_2' => "Roteiro 3.01 - 32.2 - NFs de Saídas Interestaduais - ICMS calculado (BC * 12% nas saídas para os estados do sul e sudeste;  7% nas saídas para os estados do norte, nordeste, cento-oeste e Espírito Santo.) menos ICMS declarado.",
	'3_01 33_1' => "Roteiro 3.01 - 33.1 - NFs de Saídas Interestaduais para destinatário ISENTO - ICMS calculado (BC * 18%) menos ICMS Declarado",



);

	
	$pr->abre_excel_sql('aud_modelo', 'aud_modelo - Lembre que os registros cancelados são os de cod_sit NOT IN (0, 1, 6, 7, 8)', $sql, $col_format, $cabec, $form_final);
  }

  if ($chkbuttons[2]->get_active()) {

	$pr->aud_prepara("
-- Base para calcular todos os IVAs, PMD e PMC
DROP TABLE IF EXISTS ind_aux;
CREATE TABLE ind_aux AS
SELECT 
  sum(qtd_gia1) AS qtd_gia1,  sum(qtd_gia2) AS qtd_gia2,
  sum(qtd_res1) AS qtd_res1,  sum(qtd_res2) AS qtd_res2,
  sum(qtd_dfe1) AS qtd_dfe1,  sum(qtd_dfe2) AS qtd_dfe2,
  sum(valcon_gia1) AS valcon_gia1,  sum(valcon_gia2) AS valcon_gia2,
  sum(valcon_res1) AS valcon_res1,  sum(valcon_res2) AS valcon_res2,
  sum(valcon_dfe1) AS valcon_dfe1,  sum(valcon_dfe2) AS valcon_dfe2,
  sum(icms_gia1) AS icms_gia1,  sum(icms_gia2) AS icms_gia2,
  sum(icms_res1) AS icms_res1,  sum(icms_res2) AS icms_res2,
  sum(icms_dfe1) AS icms_dfe1,  sum(icms_dfe2) AS icms_dfe2
  FROM
      (SELECT gr, tp_origem,
           CASE WHEN gr = '1-' AND tp_origem = 'GIA' THEN qtd ELSE 0 END AS qtd_gia1,
           CASE WHEN gr = '2-' AND tp_origem = 'GIA' THEN qtd ELSE 0 END AS qtd_gia2,
           CASE WHEN gr = '1-' AND tp_origem = 'RES' THEN qtd ELSE 0 END AS qtd_res1,
           CASE WHEN gr = '2-' AND tp_origem = 'RES' THEN qtd ELSE 0 END AS qtd_res2,
           CASE WHEN gr = '1-' AND tp_origem = 'DFe' THEN qtd ELSE 0 END AS qtd_dfe1,
           CASE WHEN gr = '2-' AND tp_origem = 'DFe' THEN qtd ELSE 0 END AS qtd_dfe2,
           CASE WHEN gr = '1-' AND tp_origem = 'GIA' THEN valcon ELSE 0 END AS valcon_gia1,
           CASE WHEN gr = '2-' AND tp_origem = 'GIA' THEN valcon ELSE 0 END AS valcon_gia2,
           CASE WHEN gr = '1-' AND tp_origem = 'RES' THEN valcon ELSE 0 END AS valcon_res1,
           CASE WHEN gr = '2-' AND tp_origem = 'RES' THEN valcon ELSE 0 END AS valcon_res2,
           CASE WHEN gr = '1-' AND tp_origem = 'DFe' THEN valcon ELSE 0 END AS valcon_dfe1,
           CASE WHEN gr = '2-' AND tp_origem = 'DFe' THEN valcon ELSE 0 END AS valcon_dfe2,
           CASE WHEN gr = '1-' AND tp_origem = 'GIA' THEN icms ELSE 0 END AS icms_gia1,
           CASE WHEN gr = '2-' AND tp_origem = 'GIA' THEN icms ELSE 0 END AS icms_gia2,
           CASE WHEN gr = '1-' AND tp_origem = 'RES' THEN icms ELSE 0 END AS icms_res1,
           CASE WHEN gr = '2-' AND tp_origem = 'RES' THEN icms ELSE 0 END AS icms_res2,
           CASE WHEN gr = '1-' AND tp_origem = 'DFe' THEN icms ELSE 0 END AS icms_dfe1,
           CASE WHEN gr = '2-' AND tp_origem = 'DFe' THEN icms ELSE 0 END AS icms_dfe2
          FROM
            (SELECT substr(g1, 1, 2) AS gr, tp_origem, count(g1) AS qtd, sum(valcon) AS valcon, sum(icms) AS icms
	       FROM aud_modelo 
	       WHERE cod_sit IN (0, 1, 6, 7, 8) AND substr(g1, 1, 2) IN ('1-','2-') AND tp_oper IN ('E', 'S')
	       GROUP BY g1, tp_origem));
");

  
	// Planilha Resumo Geral
	$sql = "
SELECT '##NT##Resumo Geral de aud_modelo - Registros Válidos, cod_sit IN (0, 1, 6, 7, 8)    Total de Linhas: ', count(*) FROM aud_modelo;
SELECT DISTINCT '##NI##Empresa: CNPJ ' || cnpj_origem ||  ' IE ' || ie_origem FROM modelo;
SELECT DISTINCT '##NI##EFD:0000 Empresa: CNPJ ' || cnpj ||  ' IE ' || ie FROM o000;
SELECT DISTINCT '##NI##EFD:0000 Empresa: NOME ' || nome FROM o000;
SELECT DISTINCT '##NI##EFD:0005 Fantasia: ' || fantasia || ' Fone: ' || fone || ' email: ' || email FROM o005;
SELECT DISTINCT '##NI##EFD:0005 Endereço: ' || end || '  ' || num || '  ' || compl || '  ' || bairro FROM o005;
SELECT '';
SELECT DISTINCT '##NI##EFD:0100 Contabilista: ' || nome ||  ' cpf: ' || cpf || ' crc: ' || crc FROM o100;
SELECT DISTINCT '##NI##EFD:0100 Contabilista: cnpj: ' || cnpj || ' Fone: ' || fone || ' email: ' || email FROM o100;
SELECT DISTINCT '##NI##EFD:0100 Contabilista: Endereço: ' || end || '  ' || num || '  ' || compl || '  ' || bairro FROM o100;
SELECT '';
SELECT '##NI##VERIFICAR: Histórico e O que os outros já fizeram?';
SELECT '##I##    Inclua empresas relacionadas';
SELECT '##I##    AIIMs anteriores';
SELECT '##I##    PGSFs anteriores (especialmente relatórios)';
SELECT '##I##    O que pede a OSF atual?';
SELECT '##I##    Faça o trabalho reverso';
SELECT '##I##    Negócio, produto, tipos de fornecedores e clientes';
SELECT '';
SELECT '##NI##VERIFICAR: todas os meses encontram-se entregues?';
SELECT ' tp_origem = ''GIA'' Quantidade de Meses: ' || count(*) FROM (SELECT aaaamm FROM modelo WHERE tp_origem = 'GIA' GROUP BY aaaamm);
SELECT ' tp_origem = ''GIA'' de: ' || min(aaaamm) || ' a ' || max(aaaamm) || ', qtd linhas =', count(*) FROM modelo WHERE tp_origem = 'GIA';
SELECT '  ' || group_concat(aaaamm) FROM (SELECT substr(aaaamm, 1, 4) AS ano, aaaamm FROM modelo WHERE tp_origem = 'GIA' GROUP BY aaaamm) GROUP BY ano;
SELECT '';
SELECT ' tp_origem = ''RES'' Quantidade de Meses: ' || count(*) FROM (SELECT aaaamm FROM modelo WHERE tp_origem = 'RES' GROUP BY aaaamm);
SELECT ' tp_origem = ''RES'' de: ' || min(aaaamm) || ' a ' || max(aaaamm) || ', qtd linhas =', count(*) FROM modelo WHERE tp_origem = 'RES';
SELECT '  ' || group_concat(aaaamm) FROM (SELECT substr(aaaamm, 1, 4) AS ano, aaaamm FROM modelo WHERE tp_origem = 'RES' GROUP BY aaaamm) GROUP BY ano;
SELECT '';
SELECT ' tp_origem = ''DFe'' Quantidade de Meses: ' || count(*) FROM (SELECT aaaamm FROM modelo WHERE tp_origem = 'DFe' GROUP BY aaaamm);
SELECT ' tp_origem = ''DFe'' de: ' || min(aaaamm) || ' a ' || max(aaaamm) || ', qtd linhas =', count(*) FROM modelo WHERE tp_origem = 'DFe';
SELECT '  ' || group_concat(aaaamm) FROM (SELECT substr(aaaamm, 1, 4) AS ano, aaaamm FROM modelo WHERE tp_origem = 'DFe' GROUP BY aaaamm) GROUP BY ano;
SELECT '';
SELECT '##NI##VERIFICAR: O que está sendo declarado nas GIAs está sendo pago ?';
SELECT 'Anote aqui o total de 065 das GIAs e quanto foi pago ->';
SELECT 'Anote aqui o total de 065 das GIAs ST e quanto foi pago ->';
SELECT 'Anote aqui os totais das demais GARES pagas, explicando ->';
SELECT '##I##Abaixo, totalização dos débitos e créditos das GIAs e RES, por ano', 'meses', 'icms_deb', 'icms_cred', 'deb - cred', 'icmsst deb - cred';
SELECT 'Tipo de Origem ' || tp_origem || ' Ano: ' || ano AS tp_origem_ano, count(*) AS qtd, sum(icms_deb) AS icms_deb, sum(icms_cred) AS icms_cred, sum(icms_deb) - sum(icms_cred) AS deb_cred,
  sum(icmsst_deb) - sum(icmsst_cred) AS icmsst_deb_cred FROM
    (SELECT tp_origem, ano, sum(icms_deb) AS icms_deb, sum(icms_cred) AS icms_cred, sum(icmsst_deb) AS icmsst_deb, sum(icmsst_cred) AS icmsst_cred FROM
        (SELECT tp_origem, substr(aaaamm, 1, 4) AS ano, aaaamm, 
            CASE WHEN tp_oper = 'S' THEN icms ELSE 0 END AS icms_deb,
            CASE WHEN tp_oper <> 'S' THEN icms ELSE 0 END AS icms_cred,
            CASE WHEN tp_oper = 'S' THEN icmsst ELSE 0 END AS icmsst_deb,
            CASE WHEN tp_oper <> 'S' THEN icmsst ELSE 0 END AS icmsst_cred
            FROM modelo WHERE tp_origem IN ('GIA', 'RES')) GROUP BY tp_origem, aaaamm)
  GROUP BY ano, tp_origem;
SELECT '##NI##VERIFICAR: Há diferenças quanto ao 065 da GIA ?';
SELECT '##NI##VERIFICAR: Há outros débitos ou outros créditos na GIA ?';
SELECT '##NI##VERIFICAR: Estão faltando débitos ou outros créditos na GIA ?';
SELECT '##I##     Exemplos: 426-A nos casos de comércio  e  diferencial de alíquota UF (consumo e ativo) ?';
SELECT '##NI##VERIFICAR: Há saldos credores na GIA ? Estão crescentes ? Qual a razão?';
SELECT '##NI##VERIFICAR: Há erros de transposição de saldo Credor ?';
SELECT '';
SELECT '##NI##VERIFICAR: Totais NFes=RES=GIA (entradas e saídas)? Fazer TD em aud_modelo';
SELECT '##I##    RótLinha: cod_sit tp_oper  RótColuna: tp_origem  Valores: valcon icms icmsst';
SELECT '';
SELECT '##NI##VERIFICAR: Concilie DFe com RES - utilizar conc_dfe_res. Separar a anotar abaixo:';
SELECT '##I##    DFe sem RES ->';
SELECT '##I##    RES sem DFe ->';
SELECT '##I## icms DFe < RES -> (entrada e saída)';
SELECT '##I## icms DFe > RES -> (entrada e saída)';
SELECT '##I## icmsst SP  DFe < RES -> (entrada e saída)';
SELECT '##I## icmsst SP  DFe > RES -> (entrada e saída)';
SELECT '';
SELECT '##NT##Totais por Valcon - Grupos (Somente tp_oper E ou S)';
SELECT g1, count(g1), sum(valcon) ,
  sum(CASE WHEN tp_origem = 'GIA' THEN valcon ELSE 0 END) AS soma_gia,
  sum(CASE WHEN tp_origem = 'RES' THEN valcon ELSE 0 END) AS soma_res,
  sum(CASE WHEN tp_origem = 'DFe' THEN valcon ELSE 0 END) AS soma_nfe
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_oper IN ('E', 'S') GROUP BY g1;
SELECT '##NI##VERIFICAR: Comente os IVAs abaixo ->';
SELECT 'IVA:', Null, Null, 
  (valcon_gia1 + valcon_gia2) * 100 / -valcon_gia2 AS IVA_gia,
  (valcon_res1 + valcon_res2) * 100 / -valcon_res2 AS IVA_res,
  (valcon_dfe1 + valcon_dfe2) * 100 / -valcon_dfe2 AS IVA_dfe
  FROM ind_aux;
SELECT '##NT##Totais por icms - Grupos (Somente tp_oper E ou S)';
SELECT g1, count(g1), sum(icms) ,
  sum(CASE WHEN tp_origem = 'GIA' THEN icms ELSE 0 END) AS soma_gia,
  sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS soma_res,
  sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS soma_nfe
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_oper IN ('E', 'S') GROUP BY g1;
SELECT '##NI##VERIFICAR: Calcule os IVAs abaixo! Comente!:';
SELECT 'IVA:', Null, Null, 
  (icms_gia1 + icms_gia2) * 100 / -icms_gia2 AS IVA_gia,
  (icms_res1 + icms_res2) * 100 / -icms_res2 AS IVA_res,
  (icms_dfe1 + icms_dfe2) * 100 / -icms_dfe2 AS IVA_dfe
  FROM ind_aux;
SELECT '##NI##VERIFICAR: Calcule os PMDs e PMCs abaixo! Comente!:';
SELECT 'PMD:', Null, Null, 
  icms_gia1 * 100 / valcon_gia1 AS IVA_gia,
  icms_res1 * 100 / valcon_res1 AS IVA_res,
  icms_dfe1 * 100 / valcon_dfe1 AS IVA_dfe
  FROM ind_aux;
SELECT 'PMC:', Null, Null, 
  icms_gia2 * 100 / valcon_gia2 AS IVA_gia,
  icms_res2 * 100 / valcon_res2 AS IVA_res,
  icms_dfe2 * 100 / valcon_dfe2 AS IVA_dfe
  FROM ind_aux;
SELECT '##NT##Totais por icmsst - Grupos (Somente tp_oper E ou S)';
SELECT g1, count(g1), sum(icmsst) ,
  sum(CASE WHEN tp_origem = 'GIA' THEN icmsst ELSE 0 END) AS soma_gia,
  sum(CASE WHEN tp_origem = 'RES' THEN icmsst ELSE 0 END) AS soma_res,
  sum(CASE WHEN tp_origem = 'DFe' THEN icmsst ELSE 0 END) AS soma_nfe
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_oper IN ('E', 'S') GROUP BY g1;
SELECT '##NI##VERIFICAR: Sendo o caso, calcule os PMDs e PMCs efetivos abaixo! Comente!:';
SELECT 'PMD:';
SELECT 'PMC:';
SELECT '';
SELECT '##NI##VERIFICAR:';
SELECT '';
SELECT '##NI##As verificações automatizadas de 3.01 em aud_modelo:';
SELECT '##I##          Inidôneos -> Comentários....';
SELECT '##I##          Últimas Colunas -> Comentários....';
SELECT '';
SELECT '##NI##Se NÃO é transportadora, foi analisado os transportes que ele TOMA e seus créditos?:';
SELECT '##I##          E os que ele TOMA e é responsável, especialmente transportadoras fora de SP?';
SELECT '##I##          -> Comentários....';
SELECT '';
SELECT '##NI##Quem são os clientes, fornecedores e seus produtos? Os créditos são realmente de insumos ?';
SELECT '##I##    planilhas clifor, for10g, cli10g, for10m, cli10m';
SELECT '##I##    as análises de produtos devem ser feitas diretamente nas planilhas DFe';
SELECT '##I##          Comentários....';
SELECT '';
SELECT '##NI##Como estão as Alíquotas Médias, para cada CFOP? E os desvios? TD em aud_modelo';
SELECT '##I##    RótLinha: cod_sit g1 d_f_i ordem  RótColuna: tp_origem  Valores: valcon icms icmsst';
SELECT '##I##          Comentários....';
SELECT '';
SELECT '##NI##Estatísticas opcionais', 'check';
SELECT '##I##    GIAs de Terceiros';
SELECT '##I##    Variações de Preços Unitários com CliFor';
SELECT '##I##    Repetições em DFe cancelados';
SELECT '##I##    Transportadora? Calcule o consumo de combustível padrão';
SELECT '';
SELECT '##NI##Abaixo, verificações obrigatórias incluindo planilha PT Seleção por IE.xls';
SELECT '';
SELECT '##NT##Análise dos Créditos';
SELECT g1 || ' - ' || g2  || ' - ' ||  ordem, count(g1), sum(icms) AS creds ,
  sum(CASE WHEN tp_origem = 'GIA' THEN icms ELSE 0 END) AS soma_gia,
  sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS soma_res,
  sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS soma_nfe
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND icms < 0 GROUP BY g1, g2, ordem ORDER BY creds;
SELECT '##NT##Analise dos Creditos-DFI- Ver Aliquotas e se tem diferencial de aliquota';
SELECT d_f_i || ' - ' || g1 || ' - ' || g2  || ' - ' ||  ordem, count(g1), sum(icms) AS creds ,
  sum(CASE WHEN tp_origem = 'GIA' THEN icms ELSE 0 END) AS soma_gia,
  sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS soma_res,
  sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS soma_nfe
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND icms < 0 GROUP BY g1, g2, ordem, d_f_i ORDER BY creds;
SELECT '##NT##Totais Saidas por UFs';
SELECT 
   CASE WHEN uf IS NULL THEN '##I##GIA?' ELSE 
        CASE WHEN uf = 'SP' THEN '##N##' || uf ELSE uf END
  END AS uf, count(g1), sum(valcon) AS geral,
  sum(CASE WHEN tp_origem = 'GIA' THEN valcon ELSE 0 END) AS soma_gia,
  sum(CASE WHEN tp_origem = 'RES' THEN valcon ELSE 0 END) AS soma_res,
  sum(CASE WHEN tp_origem = 'DFe' THEN valcon ELSE 0 END) AS soma_nfe
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_oper = 'S' GROUP BY uf ORDER BY geral DESC;
SELECT '##NT##Totais Entradas por UFs';
SELECT
  CASE WHEN uf IS NULL THEN '##I##GIA?' ELSE 
        CASE WHEN uf = 'SP' THEN '##N##' || uf ELSE uf END
  END AS uf, count(g1), sum(valcon) AS geral,
  sum(CASE WHEN tp_origem = 'GIA' THEN valcon ELSE 0 END) AS soma_gia,
  sum(CASE WHEN tp_origem = 'RES' THEN valcon ELSE 0 END) AS soma_res,
  sum(CASE WHEN tp_origem = 'DFe' THEN valcon ELSE 0 END) AS soma_nfe
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_oper = 'E' GROUP BY uf ORDER BY geral;
SELECT '##NT##ICMS Saidas por UFs';
SELECT
  CASE WHEN uf IS NULL THEN '##I##GIA?' ELSE 
        CASE WHEN uf = 'SP' THEN '##N##' || uf ELSE uf END
  END AS uf, count(g1), sum(icms) AS geral,
  sum(CASE WHEN tp_origem = 'GIA' THEN icms ELSE 0 END) AS soma_gia,
  sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS soma_res,
  sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS soma_nfe
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_oper = 'S' GROUP BY uf ORDER BY geral DESC;
SELECT '##NT##ICMS Entradas por UFs';
SELECT 
  CASE WHEN uf IS NULL THEN '##I##GIA?' ELSE 
        CASE WHEN uf = 'SP' THEN '##N##' || uf ELSE uf END
  END AS uf, count(g1), sum(icms) AS geral,
  sum(CASE WHEN tp_origem = 'GIA' THEN icms ELSE 0 END) AS soma_gia,
  sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS soma_res,
  sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS soma_nfe
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_oper = 'E' GROUP BY uf ORDER BY geral;
SELECT '##NT##X04-Créditos em tese Vedados e X05-E949';
SELECT d_f_i || ' - ' || g1 || ' - ' || g2  || ' - ' ||  ordem, count(g1), sum(icms) AS creds,
  sum(CASE WHEN tp_origem = 'GIA' THEN icms ELSE 0 END) AS soma_gia,
  sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS soma_res,
  sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS soma_nfe
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND icms < 0 AND p_cred <> 'S' GROUP BY g1, g2, ordem, d_f_i ORDER BY creds;
SELECT '##NT##Batimentos por Tipo de Operação E/S', 'Contagem', 'Valor Contl', 'bc icms', 'icms', 'bc icmsst', 'icmsst';
SELECT ' tp_oper=' || tp_oper || ' '   ||  tp_origem  AS classif, count(g1), 
  sum(valcon) AS valcon, sum(bcicms) AS bcicms, sum(icms) AS icms, sum(bcicmsst) AS bcicmsst, sum(icmsst) AS icmsst
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) GROUP BY tp_oper, tp_origem ORDER BY classif;
SELECT '';
SELECT aaaamm || ' tp_oper=' || tp_oper || ' ' || tp_origem  AS classif, count(g1), 
  sum(valcon) AS valcon, sum(bcicms) AS bcicms, sum(icms) AS icms, sum(bcicmsst) AS bcicmsst, sum(icmsst) AS icmsst
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) GROUP BY aaaamm, tp_oper, tp_origem ORDER BY classif;
SELECT '##NT##Exclusoes de Base de Cálculo S933 e S949', 'Contagem', 'Valor Contl', 'BC ICMS', 'icms', 'Dif VC-BC';
SELECT classif, contagem, valcon, bcicms, icms, valcon - bcicms AS dif_vc_bc FROM
    (SELECT tp_origem || ' - ' || d_f_i || ' - ' || g1 || ' - ' || g2  || ' - ' ||  ordem AS classif, count(g1) AS contagem, 
      sum(valcon) AS valcon, sum(bcicms) AS bcicms, sum(icms) AS icms
      FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND classe IN ('S933','S949') GROUP BY tp_origem, d_f_i, g1, g2, ordem ORDER BY classif);
SELECT '##NT##X11-Remessa e Retorno Dep Fechad Arm Geral S905 S934 E906 E907', 'Contagem', 'Valor Contl', 'BC ICMS', 'icms', 'Dif VC-BC';
SELECT tp_origem || ' - ' || d_f_i || ' - ' || g1 || ' - ' || g2  || ' - ' ||  ordem AS classif, count(g1), 
  sum(valcon) AS valcon, sum(bcicms) AS bcicms, sum(icms) AS icms, valcon - bcicms AS dif_vc_bc
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND classe IN ('S905','S934','E906','E907') GROUP BY tp_origem, g1, g2, ordem, d_f_i ORDER BY valcon DESC;
SELECT '##NT##Remessa e Retorno Industrialização', 'Relevancia', 'Valor Contl', 'BC ICMS', 'icms', 'Dif VC-BC';
SELECT tp_origem || ' - ' || d_f_i || ' - ' || g1 || ' - ' || g2  || ' - ' ||  ordem AS classif, sum(abs(valcon)) AS total, 
  sum(valcon) AS valcon, sum(bcicms) AS bcicms, sum(icms) AS icms, valcon - bcicms AS dif_vc_bc
  FROM aud_modelo WHERE cod_sit IN (0, 1, 6, 7, 8) AND g1 LIKE '5-%' AND c3 LIKE '%indust%' GROUP BY tp_origem, g1, g2, ordem, d_f_i ORDER BY total DESC;
";
	$col_format = array(
		"A:A" => "0",
		"B:B" => "#.##0",
		"C:F" => "#.##0,00_ ;[Vermelho]-#.##0,00 ");

	$cabec = array(
	'Descrição' => 'Descrição',
	'Qtd' => 'Quantidade',
	'Val.Tot.' => 'Valor Total',
	'GIA' => 'Valores conforme GIAs',
	'RES' => 'Valores conforme Livros',
	'DFe' => 'Valores conforme Documentos Fiscais Eletrônicos');
	$pr->abre_excel_sql('Resumo', 'Resumo Geral audit.db3', $sql, $col_format, $cabec);

  }

  if ($chkbuttons[3]->get_active()) {

	$pr->aud_prepara("
-- Os passos são, a partir de aud_modelo:
--    Cria o que está escriturado em resaux, um registro pra cada nota fiscal
--    Cria as notas fiscais em dfeaux, um registro pra cada nota fiscal
CREATE TABLE resaux AS
SELECT  origem, cnpj_origem, dtaentsai, chav_ace, aaaamm, cod_sit, tp_oper, dtaemi, modelo, serie, numero, cnpj, uf, cnpj_uf_razsoc, dtaina, descina, 
    sum(valcon) AS valcon, sum(bcicms) AS bcicms, sum(icms) AS icms, sum(outimp) AS outimp, sum(bcicmsst) AS bcicmsst, sum(icmsst) AS icmsst
    FROM aud_modelo
    WHERE tp_origem = 'RES'
    GROUP BY chav_ace;
CREATE INDEX IF NOT EXISTS resauxchav_ace ON resaux (chav_ace ASC);
CREATE TABLE dfeaux AS
SELECT  origem, cnpj_origem, dtaentsai, chav_ace, aaaamm, cod_sit, tp_oper, dtaemi, modelo, serie, numero, cnpj, uf, cnpj_uf_razsoc, dtaina, descina, 
    sum(valcon) AS valcon, sum(bcicms) AS bcicms, sum(icms) AS icms, sum(outimp) AS outimp, sum(bcicmsst) AS bcicmsst, sum(icmsst) AS icmsst
    FROM aud_modelo
    WHERE tp_origem = 'DFe'
    GROUP BY chav_ace;
CREATE INDEX IF NOT EXISTS dfeauxchav_ace ON dfeaux (chav_ace ASC);
CREATE TABLE conc_dfe_res AS
SELECT 
    CASE WHEN resaux.origem IS NULL THEN 'DFe_sem_RES' ELSE 'DFe-RES' END AS concil,
    dfeaux.origem AS dfe_origem, resaux.origem AS res_origem, dfeaux.cnpj_origem AS dfe_cnpj_origem, resaux.cnpj_origem AS res_cnpj_origem,   
    dfeaux.dtaentsai AS dfe_dtaentsai, resaux.dtaentsai AS res_dtaentsai, 
    dfeaux.chav_ace AS dfe_chav_ace, resaux.chav_ace AS res_chav_ace, 
    dfeaux.aaaamm AS dfe_aaaamm, resaux.aaaamm AS res_aaaamm , 
    dfeaux.cod_sit AS dfe_cod_sit, resaux.cod_sit AS res_cod_sit , 
    dfeaux.tp_oper AS dfe_tp_oper, resaux.tp_oper AS res_tp_oper , 
    dfeaux.dtaemi AS dfe_dtaemi, resaux.dtaemi AS res_dtaemi , 
    dfeaux.modelo AS dfe_modelo, resaux.modelo AS res_modelo , 
    dfeaux.serie AS dfe_serie, resaux.serie AS res_serie , 
    dfeaux.numero AS dfe_numero, resaux.numero AS res_numero , 
    dfeaux.cnpj AS dfe_cnpj, resaux.cnpj AS res_cnpj , 
    dfeaux.uf AS dfe_uf, resaux.uf AS res_uf, 
    dfeaux.cnpj_uf_razsoc AS dfe_cnpj_uf_razsoc, resaux.cnpj_uf_razsoc AS res_cnpj_uf_razsoc, 
    dfeaux.dtaina AS dfe_dtaina, resaux.dtaina AS res_dtaina, 
    dfeaux.descina AS dfe_descina, resaux.descina AS res_descina , 
    dfeaux.valcon AS dfe_valcon, resaux.valcon AS res_valcon, 
    dfeaux.bcicms AS dfe_bcicms, resaux.bcicms AS res_bcicms , 
    dfeaux.icms AS dfe_icms, resaux.icms AS res_icms , 
    dfeaux.outimp AS dfe_outimp, resaux.outimp AS res_outimp , 
    dfeaux.bcicmsst AS dfe_bcicmsst, resaux.bcicmsst AS res_bcicmsst , 
    dfeaux.icmsst AS dfe_icmsst, resaux.icmsst AS res_icmsst
    FROM dfeaux
    LEFT OUTER JOIN resaux ON resaux.chav_ace = dfeaux.chav_ace
UNION ALL
SELECT 
    resaux.origem || '_sem_DFe' AS concil,
    dfeaux.origem AS dfe_origem, resaux.origem AS res_origem, dfeaux.cnpj_origem AS dfe_cnpj_origem, resaux.cnpj_origem AS res_cnpj_origem,   
    dfeaux.dtaentsai AS dfe_dtaentsai, resaux.dtaentsai AS res_dtaentsai, 
    dfeaux.chav_ace AS dfe_chav_ace, resaux.chav_ace AS res_chav_ace, 
    dfeaux.aaaamm AS dfe_aaaamm, resaux.aaaamm AS res_aaaamm , 
    dfeaux.cod_sit AS dfe_cod_sit, resaux.cod_sit AS res_cod_sit , 
    dfeaux.tp_oper AS dfe_tp_oper, resaux.tp_oper AS res_tp_oper , 
    dfeaux.dtaemi AS dfe_dtaemi, resaux.dtaemi AS res_dtaemi , 
    dfeaux.modelo AS dfe_modelo, resaux.modelo AS res_modelo , 
    dfeaux.serie AS dfe_serie, resaux.serie AS res_serie , 
    dfeaux.numero AS dfe_numero, resaux.numero AS res_numero , 
    dfeaux.cnpj AS dfe_cnpj, resaux.cnpj AS res_cnpj , 
    dfeaux.uf AS dfe_uf, resaux.uf AS res_uf, 
    dfeaux.cnpj_uf_razsoc AS dfe_cnpj_uf_razsoc, resaux.cnpj_uf_razsoc AS res_cnpj_uf_razsoc, 
    dfeaux.dtaina AS dfe_dtaina, resaux.dtaina AS res_dtaina, 
    dfeaux.descina AS dfe_descina, resaux.descina AS res_descina , 
    dfeaux.valcon AS dfe_valcon, resaux.valcon AS res_valcon, 
    dfeaux.bcicms AS dfe_bcicms, resaux.bcicms AS res_bcicms , 
    dfeaux.icms AS dfe_icms, resaux.icms AS res_icms , 
    dfeaux.outimp AS dfe_outimp, resaux.outimp AS res_outimp , 
    dfeaux.bcicmsst AS dfe_bcicmsst, resaux.bcicmsst AS res_bcicmsst , 
    dfeaux.icmsst AS dfe_icmsst, resaux.icmsst AS res_icmsst
    FROM resaux
    LEFT OUTER JOIN dfeaux ON dfeaux.chav_ace = resaux.chav_ace
    WHERE dfe_origem IS NULL;
");
  
	// Planilha conc_dfe_res
	$tabela = 'conc_dfe_res';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"E:E" => "0",
	"X:Y" => "0",
	"P:W" => "#.##0,00_ ;[Vermelho]-#.##0,00 ",
	"AQ:AY" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = $pr->auto_cabec($tabela);
	$pr->abre_excel_sql('conc_dfe_res', 'conc_dfe_res - Conciliações DFes e Livros - Lembre que os registros cancelados são os de cod_sit NOT IN (0, 1, 6, 7, 8)', $sql, $col_format, $cabec, $form_final);
  }
 
 
  if ($chkbuttons[4]->get_active()) {

	$pr->aud_prepara("
DROP TABLE IF EXISTS clifor;
CREATE TABLE clifor AS
  SELECT cnpj AS dCNPJ, ie AS dIE, razsoc AS dxNome, uf AS dUF,
  dtaina, descina,
  sum(valcon) AS tot_geral,
  sum(CASE WHEN tp_origem = 'DFe' THEN valcon ELSE 0 END) AS dfe_valcon, 
  sum(CASE WHEN tp_origem = 'RES' THEN valcon ELSE 0 END) AS res_valcon,   
  sum(CASE WHEN tp_origem = 'DFe' THEN bcicms ELSE 0 END) AS dfe_bcicms, 
  sum(CASE WHEN tp_origem = 'RES' THEN bcicms ELSE 0 END) AS res_bcicms,   
  sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS dfe_icms, 
  sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS res_icms,   
  sum(CASE WHEN tp_origem = 'DFe' THEN outimp ELSE 0 END) AS dfe_outimp, 
  sum(CASE WHEN tp_origem = 'RES' THEN outimp ELSE 0 END) AS res_outimp,   
  sum(CASE WHEN tp_origem = 'DFe' THEN bcicmsst ELSE 0 END) AS dfe_bcicmsst, 
  sum(CASE WHEN tp_origem = 'RES' THEN bcicmsst ELSE 0 END) AS res_bcicmsst,   
  sum(CASE WHEN tp_origem = 'DFe' THEN icmsst ELSE 0 END) AS dfe_icmsst, 
  sum(CASE WHEN tp_origem = 'RES' THEN icmsst ELSE 0 END) AS res_icmsst,
  count(cnpj) AS qtdnfe
  FROM aud_modelo
  WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_origem IN ('DFe','RES')
  GROUP BY dCNPJ
  ORDER BY tot_geral DESC;
CREATE INDEX clifordCNPJ on clifor (dCNPJ ASC);
-- 25 maiores clientes por grupos
DROP TABLE IF EXISTS cli10g;
CREATE TABLE cli10g AS
SELECT clifor.rowid AS classif, etapa1.* FROM
     (SELECT cnpj AS dCNPJ, ie AS dIE, razsoc AS dxNome, uf AS dUF, g1, c3, ordem,
      dtaina, descina,
      sum(valcon) AS tot_geral,
      sum(CASE WHEN tp_origem = 'DFe' THEN valcon ELSE 0 END) AS dfe_valcon, 
      sum(CASE WHEN tp_origem = 'RES' THEN valcon ELSE 0 END) AS res_valcon,   
      sum(CASE WHEN tp_origem = 'DFe' THEN bcicms ELSE 0 END) AS dfe_bcicms, 
      sum(CASE WHEN tp_origem = 'RES' THEN bcicms ELSE 0 END) AS res_bcicms,   
      sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS dfe_icms, 
      sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS res_icms,   
      sum(CASE WHEN tp_origem = 'DFe' THEN outimp ELSE 0 END) AS dfe_outimp, 
      sum(CASE WHEN tp_origem = 'RES' THEN outimp ELSE 0 END) AS res_outimp,   
      sum(CASE WHEN tp_origem = 'DFe' THEN bcicmsst ELSE 0 END) AS dfe_bcicmsst, 
      sum(CASE WHEN tp_origem = 'RES' THEN bcicmsst ELSE 0 END) AS res_bcicmsst,   
      sum(CASE WHEN tp_origem = 'DFe' THEN icmsst ELSE 0 END) AS dfe_icmsst, 
      sum(CASE WHEN tp_origem = 'RES' THEN icmsst ELSE 0 END) AS res_icmsst,
      count(cnpj) AS qtdnfe
      FROM aud_modelo
      WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_origem IN ('DFe','RES') AND cnpj IN (SELECT dCNPJ FROM clifor WHERE tot_geral > 0 LIMIT 25)
      GROUP BY dCNPJ, g1, c3, ordem) AS etapa1
LEFT OUTER JOIN clifor ON clifor.dCNPJ = etapa1.dCNPJ
ORDER BY classif, g1, c3, ordem;
-- 25 maiores fornecedores por grupos
DROP TABLE IF EXISTS for10g;
CREATE TABLE for10g AS
SELECT clifor.rowid AS classif, etapa1.* FROM
     (SELECT cnpj AS dCNPJ, ie AS dIE, razsoc AS dxNome, uf AS dUF, g1, c3, ordem,
      dtaina, descina,
      sum(valcon) AS tot_geral,
      sum(CASE WHEN tp_origem = 'DFe' THEN valcon ELSE 0 END) AS dfe_valcon, 
      sum(CASE WHEN tp_origem = 'RES' THEN valcon ELSE 0 END) AS res_valcon,   
      sum(CASE WHEN tp_origem = 'DFe' THEN bcicms ELSE 0 END) AS dfe_bcicms, 
      sum(CASE WHEN tp_origem = 'RES' THEN bcicms ELSE 0 END) AS res_bcicms,   
      sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS dfe_icms, 
      sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS res_icms,   
      sum(CASE WHEN tp_origem = 'DFe' THEN outimp ELSE 0 END) AS dfe_outimp, 
      sum(CASE WHEN tp_origem = 'RES' THEN outimp ELSE 0 END) AS res_outimp,   
      sum(CASE WHEN tp_origem = 'DFe' THEN bcicmsst ELSE 0 END) AS dfe_bcicmsst, 
      sum(CASE WHEN tp_origem = 'RES' THEN bcicmsst ELSE 0 END) AS res_bcicmsst,   
      sum(CASE WHEN tp_origem = 'DFe' THEN icmsst ELSE 0 END) AS dfe_icmsst, 
      sum(CASE WHEN tp_origem = 'RES' THEN icmsst ELSE 0 END) AS res_icmsst,
      count(cnpj) AS qtdnfe
      FROM aud_modelo
      WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_origem IN ('DFe','RES') AND cnpj IN (SELECT dCNPJ FROM clifor WHERE tot_geral < 0 ORDER BY tot_geral LIMIT 25)
      GROUP BY dCNPJ, g1, c3, ordem) AS etapa1
LEFT OUTER JOIN clifor ON clifor.dCNPJ = etapa1.dCNPJ
ORDER BY classif DESC, g1, c3, ordem;
-- 25 maiores clientes por mês
DROP TABLE IF EXISTS cli10m;
CREATE TABLE cli10m AS
SELECT clifor.rowid AS classif, etapa1.* FROM
     (SELECT cnpj AS dCNPJ, ie AS dIE, razsoc AS dxNome, uf AS dUF, aaaamm,
      dtaina, descina,
      sum(valcon) AS tot_geral,
      sum(CASE WHEN tp_origem = 'DFe' THEN valcon ELSE 0 END) AS dfe_valcon, 
      sum(CASE WHEN tp_origem = 'RES' THEN valcon ELSE 0 END) AS res_valcon,   
      sum(CASE WHEN tp_origem = 'DFe' THEN bcicms ELSE 0 END) AS dfe_bcicms, 
      sum(CASE WHEN tp_origem = 'RES' THEN bcicms ELSE 0 END) AS res_bcicms,   
      sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS dfe_icms, 
      sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS res_icms,   
      sum(CASE WHEN tp_origem = 'DFe' THEN outimp ELSE 0 END) AS dfe_outimp, 
      sum(CASE WHEN tp_origem = 'RES' THEN outimp ELSE 0 END) AS res_outimp,   
      sum(CASE WHEN tp_origem = 'DFe' THEN bcicmsst ELSE 0 END) AS dfe_bcicmsst, 
      sum(CASE WHEN tp_origem = 'RES' THEN bcicmsst ELSE 0 END) AS res_bcicmsst,   
      sum(CASE WHEN tp_origem = 'DFe' THEN icmsst ELSE 0 END) AS dfe_icmsst, 
      sum(CASE WHEN tp_origem = 'RES' THEN icmsst ELSE 0 END) AS res_icmsst,
      count(cnpj) AS qtdnfe
      FROM aud_modelo
      WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_origem IN ('DFe','RES') AND cnpj IN (SELECT dCNPJ FROM clifor WHERE tot_geral > 0 LIMIT 25)
      GROUP BY dCNPJ, aaaamm) AS etapa1
LEFT OUTER JOIN clifor ON clifor.dCNPJ = etapa1.dCNPJ
ORDER BY classif, aaaamm;
-- 25 maiores fornecedores por mês
DROP TABLE IF EXISTS for10m;
CREATE TABLE for10m AS
SELECT clifor.rowid AS classif, etapa1.* FROM
     (SELECT cnpj AS dCNPJ, ie AS dIE, razsoc AS dxNome, uf AS dUF, aaaamm,
      dtaina, descina,
      sum(valcon) AS tot_geral,
      sum(CASE WHEN tp_origem = 'DFe' THEN valcon ELSE 0 END) AS dfe_valcon, 
      sum(CASE WHEN tp_origem = 'RES' THEN valcon ELSE 0 END) AS res_valcon,   
      sum(CASE WHEN tp_origem = 'DFe' THEN bcicms ELSE 0 END) AS dfe_bcicms, 
      sum(CASE WHEN tp_origem = 'RES' THEN bcicms ELSE 0 END) AS res_bcicms,   
      sum(CASE WHEN tp_origem = 'DFe' THEN icms ELSE 0 END) AS dfe_icms, 
      sum(CASE WHEN tp_origem = 'RES' THEN icms ELSE 0 END) AS res_icms,   
      sum(CASE WHEN tp_origem = 'DFe' THEN outimp ELSE 0 END) AS dfe_outimp, 
      sum(CASE WHEN tp_origem = 'RES' THEN outimp ELSE 0 END) AS res_outimp,   
      sum(CASE WHEN tp_origem = 'DFe' THEN bcicmsst ELSE 0 END) AS dfe_bcicmsst, 
      sum(CASE WHEN tp_origem = 'RES' THEN bcicmsst ELSE 0 END) AS res_bcicmsst,   
      sum(CASE WHEN tp_origem = 'DFe' THEN icmsst ELSE 0 END) AS dfe_icmsst, 
      sum(CASE WHEN tp_origem = 'RES' THEN icmsst ELSE 0 END) AS res_icmsst,
      count(cnpj) AS qtdnfe
      FROM aud_modelo
      WHERE cod_sit IN (0, 1, 6, 7, 8) AND tp_origem IN ('DFe','RES') AND cnpj IN (SELECT dCNPJ FROM clifor WHERE tot_geral < 0 ORDER BY tot_geral LIMIT 25)
      GROUP BY dCNPJ, aaaamm) AS etapa1
LEFT OUTER JOIN clifor ON clifor.dCNPJ = etapa1.dCNPJ
ORDER BY classif DESC, aaaamm;
");

	$tabela = 'clifor';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"A:B" => "0",
	"G:S" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = $pr->auto_cabec($tabela);
	$pr->abre_excel_sql('clifor', 'Maiores Clientes e Fornecedores (ao final) - Somente Registros Válidos', $sql, $col_format, $cabec, $form_final);

	$tabela = 'cli10g';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"B:C" => "0",
	"K:W" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = $pr->auto_cabec($tabela);
	$pr->abre_excel_sql('cli10g', 'Maiores Clientes por Grupos - Registros Válidos', $sql, $col_format, $cabec, $form_final);

	$tabela = 'for10g';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"B:C" => "0",
	"K:W" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = $pr->auto_cabec($tabela);
	$pr->abre_excel_sql('for10g', 'Maiores Fornecedores por Grupos - Registros Válidos', $sql, $col_format, $cabec, $form_final);

	$tabela = 'cli10m';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"B:C" => "0",
	"I:U" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = $pr->auto_cabec($tabela);
	$pr->abre_excel_sql('cli10m', 'Maiores Clientes por Mês (ver Sazonalidades) - Registros Válidos', $sql, $col_format, $cabec, $form_final);

	$tabela = 'for10m';
	$sql = "
SELECT * FROM {$tabela};
";
	$col_format = array(
	"B:C" => "0",
	"I:U" => "#.##0,00_ ;[Vermelho]-#.##0,00 "
);
	$cabec = $pr->auto_cabec($tabela);
	$pr->abre_excel_sql('for10m', 'Maiores Fornecedores por Mês (ver Sazonalidades) - Registros Válidos', $sql, $col_format, $cabec, $form_final);

  }

	if ($chkbuttons[0]->get_active() || $chkbuttons[1]->get_active() || 
		$chkbuttons[2]->get_active() || $chkbuttons[3]->get_active() || $chkbuttons[4]->get_active())  
			$pr->finaliza_excel();

  if ($chkbuttons[5]->get_active()) {
	  gera_espelhos_nfe(); // ver ao final deste arquivo php

  }

}



function gera_espelhos_nfe() {
	
	$pr2 = new Pr;	// classe principal, global

	wecho("\n\nGerando Espelhos de NFes a partir das seguintes tabelas:\r\n");
	wecho("  dfe/nfe, dfe/nfe_danfe (dados NFe), audit/conc_dfe_res (correlacao) e audit/aud_modelo (dados RES)\r\n");
	wecho("Obs: Velocidade Média nos notebooks Thinkpad da Sefaz: 100 por segundo.\n");
	wecho("Os arquivos serão em formato .html e estarão dentro de /Resultados/DANFes\n");
	$tempo_inicio = time();

	do {
		if (is_dir('../Resultados/DANFes')) 
			recursiveDelete('../Resultados/DANFes');
		if (is_dir('../Resultados/DANFes')) $b_pronto = False; else $b_pronto = True;
		if ($b_pronto) {
			mkdir('../Resultados/DANFes'); 
		} else {
			if ($tentativas++ < 4) { 
				sleep(2);
			} else {
				werro_die('Erro na criação da pasta Resultados/DANFes');
			}
		}
	} while (!$b_pronto);
	
//	if (is_dir('../Resultados/DANFes')) 
//		recursiveDelete('../Resultados/DANFes');
//	sleep(1);	// não sei o porquê, mas as vezes dá erro abaixo se for muito em seguida
//	if (!mkdir('../Resultados/DANFes')) 
//		werro_die('Erro na criação da pasta Resultados/DANFes');
		
	$pr2->aud_abre_db_e_attach('dfe,audit');

	$a_temp = $pr2->aud_sql2array("SELECT nome FROM o000 LIMIT 1;");
	$nome_emp = isset($a_temp[0]['nome']) ? $a_temp[0]['nome'] : "Nome da Empresa Fiscalizada não disponível (não há efd)";

	$pr2->aud_prepara("
CREATE INDEX IF NOT EXISTS audit.conc_dfe_res_dfe_chave_ace ON conc_dfe_res (dfe_chav_ace ASC);
CREATE INDEX IF NOT EXISTS nfe_chave_acenItem ON nfe (chav_ace ASC, nItem ASC);
");
	$sql = "
SELECT nfe.*, nfe_danfe.*, conc_dfe_res.*
    FROM nfe
    LEFT OUTER JOIN nfe_danfe ON nfe_danfe.chav_ace = nfe.chav_ace
    LEFT OUTER JOIN conc_dfe_res ON conc_dfe_res.dfe_chav_ace = nfe.chav_ace
    WHERE abs(conc_dfe_res.dfe_valcon) >= 5000
    ORDER BY nfe.chav_ace, nfe.nItem;
";
	$result = $pr2->query_log($sql);
	$linha = $result->fetchArray(SQLITE3_ASSOC);
	$i_qtd = 0;
	$chav_ace = -1;
	$nomarqhtml = "arquivo_com_erro_no_nome.html";
	while ($linha = $result->fetchArray(SQLITE3_ASSOC)) {
		if ($i_qtd++ > 10000) break;
		//debug_log("#{$linha['chav_ace']}#a");
		if ($linha['chav_ace'] != $chav_ace)	{
			//debug_log("b");
			// salva o anterior, se não for o primeiro
			if ( $chav_ace != -1) {
				//debug_log("c");
				$html .= gera_nfe_html_fim();
				if (!file_put_contents($nomarqhtml, $html)) werro_die("erro ao salvar a página {$linha['chav_ace']}.html ..");
			}
			// começa um novo
			$chav_ace = $linha['chav_ace'];
			$nomarqhtml = "../Resultados/DANFes/{$linha['chav_ace']}.html";
			$html = gera_nfe_html_inicio($linha, $nome_emp);
		}
		$html .= gera_nfe_html_item($linha);
		if ($i_qtd == 1000) wecho("...Processados {$i_qtd} Registros...");
	}
	// salva o anterior, se não for o primeiro
	if ( $chav_ace != -1) {
		$html .= gera_nfe_html_fim();
		if (!file_put_contents($nomarqhtml, $html)) werro_die("erro ao salvar a página {$linha['chav_ace']}.html ..");
	}
	
	wecho("\nFinalizado: Gerados {$i_qtd} DANFes em ");
	wecho((time() - $tempo_inicio) . " segundos\r\n");

}




function gera_nfe_html_inicio($linha, $nome_emp) {

	$html = <<<EOD
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Conversor 3</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />
  <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />

EOD;

	$html .= <<<EOD
  <STYLE type="text/css">
	SPAN.info    { color: green; text-align: center; font-style: italic }
	SPAN.warning { color: yellow }
	SPAN.error   { color: red }
	.CSS_Tabela_Externa {
		width:100%;
		border: 1px solid #c7a460;
		background-color: #f4edd5;
		margin: 3px 0px 3px 0px;
	}
	.TextoLegendaCampos {
		font-family: Arial, Verdana, Helvetica, sans-serif;
		font-size: 12px;
		color: #6f5e39;
		font-weight:normal;
	}
	.TextoCampos
	{
		border: 1px solid #d6c39e;
		background-color:#fbfbf5;
		font-family: Arial, Verdana, Helvetica, sans-serif;
		font-size: 11px;   
		color:#000000;
		display:block;
		background-position:center;
		padding: 3px;
		min-height: 11px;
	}
	.TextoCamposDireita
	{
		text-align: right;
		border: 1px solid #d6c39e;
		background-color:#fbfbf5;
		font-family: Arial, Verdana, Helvetica, sans-serif;
		font-size: 11px;   
		color:#000000;
		display:block;
		background-position:center;
		padding: 3px;
		min-height: 11px;
	}
	.TextoCamposAmarelo
	{
		border: 1px solid #d6c39e;
		background-color:#ffff00;
		font-family: Arial, Verdana, Helvetica, sans-serif;
		font-size: 11px;   
		color:#000000;
		display:block;
		background-position:center;
		padding: 3px;
		min-height: 11px;
	}
	.TextoCamposDireitaAmarelo
	{
		text-align: right;
		border: 1px solid #d6c39e;
		background-color:#ffff00;
		font-family: Arial, Verdana, Helvetica, sans-serif;
		font-size: 11px;   
		color:#000000;
		display:block;
		background-position:center;
		padding: 3px;
		min-height: 11px;
	}
	.TextoTitulo {
		text-align: left;
		border: none;
		font-size: 14px;
		font-weight: bold;
		width: 98%;
		display: block;
		padding: 0px 0px 3px 20px;
		margin: 0px 0px 0px 0px;
		height: 13px;
		vertical-align: middle;
		color: #b27235;
	}
	.Titulo-Aba {
		text-align: center !important;
		border-top: 1px solid #d6c39e !important;
		border-bottom: 1px solid #d6c39e !important;
		margin-bottom: 1px !important;
	}

  </STYLE>

EOD;

	$html .= <<<EOD
</head>
<body>
<span class="TextoTitulo"><span class="Titulo-Aba">Visualizando NFe de Chave de Acesso: {$linha['chav_ace']}</span></span>
EOD;


	$nfe_aliq_med_efet   = number_format($linha['dfe_icms'] / ($linha['dfe_valcon'] - $linha['dfe_icmsst']) * 100, 2, ',', '.');
	$livro_aliq_med_efet = number_format($linha['res_icms'] / ($linha['res_valcon'] - $linha['res_icmsst']) * 100, 2, ',', '.');

	$lvlamarelo = abs($linha['dfe_valcon'] - $linha['res_valcon']) < 0.03 ? "" : "Amarelo";
	$licamarelo = abs($linha['dfe_icms'] - $linha['res_icms']) < 0.03 ? "" : "Amarelo";
	$lstamarelo = abs($linha['dfe_icmsst'] - $linha['res_icmsst']) < 0.03 ? "" : "Amarelo";
	$lamamarelo = abs($livro_aliq_med_efet - $nfe_aliq_med_efet) < 0.03 ? "" : "Amarelo";

	$motamarelo = $linha['concil'] == 'DFe-RES' ? "" : "Amarelo";
	
	if ($linha['tp_oper'] == "E") {
		$lvlamarelo = $linha['res_valcon'] == 0 ? ""  : $lvlamarelo;
		$licamarelo = $linha['res_icms'] == 0 ? ""   : $licamarelo;
		$lstamarelo = $linha['res_icmsst'] == 0 ? "" : $lstamarelo;
		$lamamarelo = $livro_aliq_med_efet == 0 ? ""  : $lamamarelo;
	}
	$_nfe_numero  = number_format($linha['nNF']  , 0, ',', '.');
	$_nfe_serie = $linha['serie'];
	$_dtaentsai = $linha['dfe_dtaentsai'];
	$_livro_dtaentsai = $linha['res_dtaentsai'];
	$_dtaemi = $linha['dtaemi'];
	$_nfe_valor   = number_format(abs($linha['dfe_valcon'])   , 2, ',', '.');
	$_livro_valor = number_format(abs($linha['res_valcon']) , 2, ',', '.');
	$_nfe_icms   = number_format(abs($linha['dfe_icms'])    , 2, ',', '.');
	$_livro_icms  = number_format(abs($linha['res_icms'])    , 2, ',', '.');
	$_nfe_icmsst    = number_format(abs($linha['dfe_icmsst'])    , 2, ',', '.');
	$_livro_icmsst  = number_format(abs($linha['res_icmsst'])  , 2, ',', '.');
	if (trim($linha['tpesoL']) <> '') $_tpesoL        = number_format($linha['tpesoL']        , 3, ',', '.');
	     else $_tpesoL = '';
	if (trim($linha['tpesoB']) <> '') $_tpesoB        = number_format($linha['tpesoB']        , 3, ',', '.');
	     else $_tpesoB = '';
	$_vFrete = number_format($linha['vFrete'], 2, ',', '.');
	$_vSeg = number_format($linha['vSeg'] , 2, ',', '.');
	$_vDesc = number_format($linha['vDesc'] , 2, ',', '.');
	$_vII = number_format($linha['vII'] , 2, ',', '.');
	$_vIPI = number_format($linha['vIPI']    , 2, ',', '.');
	$_vOutro = number_format($linha['vOutro']    , 2, ',', '.');
	$_nDI = $linha['nDI'];

		// tp_oper == 'S' (NFe_Emit)	emit == 'P' 	_rcnpj = cnpj_origem	_dcnpj = cnpj			Remetente = Emitente
		// tp_oper == 'D' (!NFe_Emit)	emit == 'T'  	_rcnpj = cnpj_origem	_dcnpj = cnpj			Destinatário = Emitente
		// tp_oper == 'E' (!NFe_Emit)	emit == 'T'  	_rcnpj = cnpj			_dcnpj = cnpj_origem	Remetente = Emitente
		// tp_oper == 'E' (NFe_Emit)	emit == 'P' 	_rcnpj = cnpj			_dcnpj = cnpj_origem	Destinatário = Emitente
	if ( $linha['origem'] == "NFe_Emit"	)  {
		$_nfe_emit = "P";
	} else {
		$_nfe_emit = "T";
	}

	if ( ($_nfe_emit == "P" && $linha['tp_oper'] == "S") || ($_nfe_emit == "T" && $linha['tp_oper'] == "E") )  {
		// quando o remetente é o emitente
	    $emitente_remetente    = "(Emitente)";
	    $emitente_destinatario = "";
	} else {
		// quando o destinatário é o emitente
	    $emitente_remetente    = "";
	    $emitente_destinatario = "(Emitente)";	
	}
	
	if (
		($linha['origem'] == "NFe_Emit" && substr($linha['tp_oper'], 0, 1) == "S")	||
		($linha['origem'] != "NFe_Emit" && substr($linha['tp_oper'], 0, 1) == "D")
	)  {
	    $_rrazsoc = $nome_emp;
		$_drazsoc = $linha['razsoc'];
		$_rcnpj = $linha['cnpj_origem'];
		$_dcnpj = $linha['cnpj'];
		$emitente_destinatario .= ((trim($linha['descina']) <> '') || (trim($linha['dtaina']) <> '') ? 
			" (Situação Cadastral: {$linha['descina']} {$linha['dtaina']} )" : "");
		$_rie  = $linha['ie_origem'];
		$_die  = $linha['ie'];
		$_ruf   = "SP";
		$_duf   = $linha['uf'];
		
        $_rxLgr = $linha['exLgr'];
        $_rnro = $linha['enro'];
        $_rxCpl = $linha['exCpl'];
        $_rxBairro = $linha['exBairro'];
        $_rxMun = $linha['exMun'];
        $_rCep = $linha['eCep'];
        $_rxPais = $linha['exPais'];
        $_rTel = $linha['eTel'];

        $_dxLgr = $linha['dxLgr'];
        $_dnro = $linha['dnro'];
        $_dxCpl = $linha['dxCpl'];
        $_dxBairro = $linha['dxBairro'];
        $_dxMun = $linha['dxMun'];
        $_dCep = $linha['dCep'];
        $_dxPais = $linha['dxPais'];
        $_dTel = $linha['dTel'];

	} else {
	    $_drazsoc = $nome_emp;
		$_rrazsoc = $linha['razsoc'];
		$_dcnpj = $linha['cnpj_origem'];
		$_rcnpj = $linha['cnpj'];
		$emitente_remetente .= ((trim($linha['descina']) <> '') || (trim($linha['dtaina']) <> '') ? 
			" (Situação Cadastral: {$linha['descina']} {$linha['dtaina']} )" : "");
		$_die  = $linha['ie_origem'];
		$_rie  = $linha['ie'];
		$_duf   = "SP";
		$_ruf   = $linha['uf'];		
		
        $_dxLgr = $linha['exLgr'];
        $_dnro = $linha['enro'];
        $_dxCpl = $linha['exCpl'];
        $_dxBairro = $linha['exBairro'];
        $_dxMun = $linha['exMun'];
        $_dCep = $linha['eCep'];
        $_dxPais = $linha['exPais'];
        $_dTel = $linha['eTel'];

        $_rxLgr = $linha['dxLgr'];
        $_rnro = $linha['dnro'];
        $_rxCpl = $linha['dxCpl'];
        $_rxBairro = $linha['dxBairro'];
        $_rxMun = $linha['dxMun'];
        $_rCep = $linha['dCep'];
        $_rxPais = $linha['dxPais'];
        $_rTel = $linha['dTel'];
		
	}

	$html .= <<<EOD
        <table class="CSS_Tabela_Externa">
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Mod<br /></span>
                    <span class="TextoCamposDireita">55</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Sér<br /></span>
                    <span class="TextoCamposDireita">{$_nfe_serie}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Numero<br /></span>
                    <span class="TextoCamposDireita">{$_nfe_numero}</span>
                </td>
                <td valign="top" height="30" title = "Tipo de Operação, do ponto de vista do Contribuinte Fiscalizado: E = Entrada; S = Saída">
                    <span class="TextoLegendaCampos">tp_oper<br /></span>
                    <span class="TextoCampos">{$linha['tp_oper']}</span>
                </td>
                <td valign="top" height="30" title = "Emitente - P (Próprio) ou T (Terceiros)">
                    <span class="TextoLegendaCampos">emit<br /></span>
                    <span class="TextoCampos">{$_nfe_emit}</span>
                </td>
                <td valign="top" height="30" title = "Fonte de Dados da NFe">
                    <span class="TextoLegendaCampos">Origem<br /></span>
                    <span class="TextoCampos">{$linha['origem']}</span>
                </td>				
                <td valign="top" height="30" title = "Código da Situação,valor inteiro conforme Tab 4.1.2 da EFD. 
Se não for informado, este código será preenchido automaticamente com zero.
Código Descrição
0 Documento regular
1 Documento regular extemporâneo
2 Documento cancelado
3 Documento cancelado extemporâneo
4 NF-e ou CT-e - denegado
5 NF-e ou CT-e - Numeração inutilizada
6 Documento Fiscal Complementar
7 Documento Fiscal Complementar extemporâneo.
8 Documento Fiscal emitido com base em Regime Especial ou Norma Específica">
                    <span class="TextoLegendaCampos">cod_sit<br /></span>
                    <span class="TextoCamposDireita{$stnamarelo}">{$linha['cod_sit']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Data Emissão<br /></span>
                    <span class="TextoCamposDireita">{$_dtaemi}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">NFe - Data Saída/Entrada<br /></span>
                    <span class="TextoCamposDireita">{$_dtaentsai}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">RES - Data Saída/Entrada<br /></span>
                    <span class="TextoCamposDireita">{$_livro_dtaentsai}</span>
                </td>
                <td valign="top" height="30" title = "Número da DI, quando for NFe de Importação">
                    <span class="TextoLegendaCampos">DI<br /></span>
                    <span class="TextoCampos">{$linha['nDI']}</span>
                </td>
                <td valign="top" height="30" title = "UF de Desembaraço, quando for NFe de Importação">
                    <span class="TextoLegendaCampos">UF_Desemb<br /></span>
                    <span class="TextoCampos">{$linha['UFDesemb']}</span>
                </td>
            </tr>
        </table>
	<span class="TextoTitulo">Valores e Cálculo do Imposto</span>
	  <table width="100%"><td>
        <table class="CSS_Tabela_Externa">
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos"><br />NFe</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Valor Total<br /></span>
                    <span class="TextoCamposDireita">{$_nfe_valor}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">ICMS<br /></span>
                    <span class="TextoCamposDireita">{$_nfe_icms}</span>
                </td>
                <td valign="top" height="30" title = "Alíquota Média Efetiva: Valor Total ICMS / (Valor Total NFe - ICMSST)">
                    <span class="TextoLegendaCampos">Alíq.Méd.Efet.<br /></span>
                    <span class="TextoCamposDireita">{$nfe_aliq_med_efet}%</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">ICMSST<br /></span>
                    <span class="TextoCamposDireita">{$_nfe_icmsst}</span>
                </td>
            </tr>
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">RES</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita{$lvlamarelo}">{$_livro_valor}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita{$licamarelo}">{$_livro_icms}</span>
                </td>
                <td valign="top" height="30" title = "Alíquota Média Efetiva: Valor Total ICMS / Valor Total NFe">
                    <span class="TextoCamposDireita{$lamamarelo}">{$livro_aliq_med_efet}%</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita{$lstamarelo}">{$_livro_icmsst}</span>
                </td>
            </tr>
        </table>
	  </td><td>
        <table class="CSS_Tabela_Externa">
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">NFe - Natureza da Operação<br /></span>
                    <span class="TextoCampos">{$linha['natOp']}</span>
                </td>
            </tr>
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Forma de Correlação NFe <-> RES <br /></span>
                    <span class="TextoCampos{$motamarelo}">{$linha['concil']}</span>
                </td>
            </tr>
        </table>
	  </td><td>
        <table class="CSS_Tabela_Externa">
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Frete<br /></span>
                    <span class="TextoCamposDireita">{$_vFrete}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Seguro<br /></span>
                    <span class="TextoCamposDireita">{$_vSeg}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Desconto<br /></span>
                    <span class="TextoCamposDireita">{$_vDesc}</span>
                </td>
            </tr>
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">II<br /></span>
                    <span class="TextoCamposDireita">{$_vII}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">IPI<br /></span>
                    <span class="TextoCamposDireita">{$_vIPI}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Outras Despesas<br /></span>
                    <span class="TextoCamposDireita">{$_vOutro}</span>
                </td>
            </tr>
        </table>
	  </td></table>

	<span class="TextoTitulo">Remetente {$emitente_remetente}</span>

	    <table class="CSS_Tabela_Externa">
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">CNPJ<br /></span>
                    <span class="TextoCampos">{$_rcnpj}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">IE<br /></span>
                    <span class="TextoCampos">{$_rie}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Razão Social<br /></span>
                    <span class="TextoCampos">{$_rrazsoc}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">UF<br /></span>
                    <span class="TextoCampos">{$_ruf}</span>
                </td>
            </tr>
        </table>
	    <table class="CSS_Tabela_Externa">
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Logradouro<br /></span>
                    <span class="TextoCampos">{$_rxLgr}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Nro<br /></span>
                    <span class="TextoCampos">{$_rnro}</span>
                </td>
                <td valign="top" height="30" title="Complemento">
                    <span class="TextoLegendaCampos">Cpl<br /></span>
                    <span class="TextoCampos">{$_rxCpl}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Bairro<br /></span>
                    <span class="TextoCampos">{$_rxBairro}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Município<br /></span>
                    <span class="TextoCampos">{$_rxMun}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">CEP<br /></span>
                    <span class="TextoCampos">{$_rCep}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">País<br /></span>
                    <span class="TextoCampos">{$_rxPais}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Tel<br /></span>
                    <span class="TextoCampos">{$_rTel}</span>
                </td>
            </tr>
        </table>

	<span class="TextoTitulo">Destinatário {$emitente_destinatario}</span>

	    <table class="CSS_Tabela_Externa">
            <tr>
				<td valign="top" height="30">
                    <span class="TextoLegendaCampos">CNPJ<br /></span>
                    <span class="TextoCampos">{$_dcnpj}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">IE<br /></span>
                    <span class="TextoCampos">{$_die}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Razão Social<br /></span>
                    <span class="TextoCampos">{$_drazsoc}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">UF<br /></span>
                    <span class="TextoCampos">{$_duf}</span>
                </td>
            </tr>
        </table>
	    <table class="CSS_Tabela_Externa">
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Logradouro<br /></span>
                    <span class="TextoCampos">{$_dxLgr}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Nro<br /></span>
                    <span class="TextoCampos">{$_dnro}</span>
                </td>
                <td valign="top" height="30" title="Complemento">
                    <span class="TextoLegendaCampos">Cpl<br /></span>
                    <span class="TextoCampos">{$_dxCpl}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Bairro<br /></span>
                    <span class="TextoCampos">{$_dxBairro}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Município<br /></span>
                    <span class="TextoCampos">{$_dxMun}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">CEP<br /></span>
                    <span class="TextoCampos">{$_dCep}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">País<br /></span>
                    <span class="TextoCampos">{$_dxPais}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Tel<br /></span>
                    <span class="TextoCampos">{$_dTel}</span>
                </td>
            </tr>
        </table>
EOD;

	$html .= <<<EOD
            </tr>
        </table>
EOD;
 
	$html .= <<<EOD
	<span class="TextoTitulo">Transporte</span>

	    <table class="CSS_Tabela_Externa">
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">CNPJ Transportadora<br /></span>
                    <span class="TextoCampos">{$linha['tCNPJ']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">IE Transportadora<br /></span>
                    <span class="TextoCampos">{$linha['tIE']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Raz.Social Transportadora<br /></span>
                    <span class="TextoCampos">{$linha['txNome']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Ender.Transportadora<br /></span>
                    <span class="TextoCampos">{$linha['txEnder']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Munic.Transp.<br /></span>
                    <span class="TextoCampos">{$linha['txMun']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">UF Transp.<br /></span>
                    <span class="TextoCampos">{$linha['tUF']}</span>
                </td>
            </tr>
        </table>		

	    <table class="CSS_Tabela_Externa">
            <tr>
                <td valign="top" height="30" title="0 - CIF - Por Conta do Emitente
1 - FOB - Por Conta do Destinatário ou Remetente em Devolução
2 - Por conta de terceiros
9 - Sem frete. (V2.0)">
                    <span class="TextoLegendaCampos">Mod.Frete<br /></span>
                    <span class="TextoCampos">{$linha['modFrete']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Qtd.Volumes<br /></span>
                    <span class="TextoCamposDireita">{$linha['qVol']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Espécie<br /></span>
                    <span class="TextoCampos">{$linha['tesp']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Marca<br /></span>
                    <span class="TextoCampos">{$linha['tmarca']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Numeração<br /></span>
                    <span class="TextoCampos">{$linha['tnVol']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Peso Líq.<br /></span>
                    <span class="TextoCamposDireita">{$_tpesoL}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Peso Bruto<br /></span>
                    <span class="TextoCamposDireita">{$_tpesoB}</span>
                </td>
            </tr>
        </table>		
	<span class="TextoTitulo">Observações</span>
        <table class="CSS_Tabela_Externa">
            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Fisco<br /></span>
                    <span class="TextoCampos">{$linha['infAdFisco']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Contribuinte<br /></span>
                    <span class="TextoCampos">{$linha['infCpl']}</span>
                </td>
            </tr>
        </table>		
EOD;

	$html .= <<<EOD
	<span class="TextoTitulo">Itens</span>
	    <table class="CSS_Tabela_Externa">
EOD;

	return $html;
}


function gera_nfe_html_item($linha) {
	//debug_log(print_r($linha, True));
	
	$_cfop   = number_format(abs($linha['cfop']), 0, ',', '.');
	
    $_valcon   = number_format(abs($linha['valcon']), 2, ',', '.');
	
    $_bcicms   = number_format(abs($linha['bcicms']), 2, ',', '.');
	$alq_icms  = number_format(abs($linha['alicms']), 2, ',', '.');
	$alq_icms_efet = $linha['bcicms'] <> 0 ? number_format($linha['icms'] / $linha['valcon'] * 100, 2, ',', '.') : 0;
    $_icms     = number_format(abs($linha['icms']), 2, ',', '.');
	
	$iva_st = $linha['bcicmsst'] <> 0 ? number_format(100* ($linha['bcicmsst'] / ($linha['valcon']-$linha['icmsst']) - 1), 2, ',', '.') : "N/D";
    $_bcicmsst   = number_format(abs($linha['bcicmsst']), 2, ',', '.');
	$alq_icmsst  = number_format(abs($linha['alicmsst']), 2, ',', '.');
    $_icmsst     = number_format(abs($linha['icmsst']), 2, ',', '.');
	
    $_outimp     = number_format(abs($linha['outimp']), 2, ',', '.');
    $_valipi     = number_format(abs($linha['valipi']), 2, ',', '.');
    $_valii      = number_format(abs($linha['valii']), 2, ',', '.');
	
    $_qtdpro   = number_format(abs($linha['qtdpro']), 3, ',', '.');
		// colocar .000 atrapalha a visualização... caso ocorra isto, tira o .000
	if (substr($_qtdpro, -4) == ",000") $_qtdpro = substr($_qtdpro, 0, -4);

/*
		$tot_valcon_dfe += $row_modelo['valcon'];
		$tot_bcicms_dfe += $row_modelo['bcicms'];
		$tot_icms_dfe += $row_modelo['icms'];
		$tot_bcicmsst_dfe += $row_modelo['bcicmsst'];
		$tot_icmsst_dfe += $row_modelo['icmsst'];
		$tot_outimp_dfe += $row_modelo['outimp'];
*/

	// na primeira linha, coloca os títulos... nas demais, só coloca os valores
	if (($linha['nItem']+0) == 1) {
		$html = <<<EOD

            <tr>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">It<br /></span>
                    <span class="TextoCamposDireita" title="origem={$linha['dfe_origem']}">{$linha['nItem']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">CFOP<br /></span>
                    <span class="TextoCamposDireita">{$_cfop}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">CST<br /></span>
                    <span class="TextoCamposDireita">{$linha['cst']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Val.Líquido<br /></span>
                    <span class="TextoCamposDireita">{$_valcon}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">BC ICMS<br /></span>
                    <span class="TextoCamposDireita">{$_bcicms}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Alq<br /></span>
                    <span class="TextoCamposDireita">{$alq_icms}%</span>
                </td>
                 <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Alq Efet<br /></span>
                    <span class="TextoCamposDireita">{$alq_icms_efet}%</span>
                </td>
               <td valign="top" height="30">
                    <span class="TextoLegendaCampos">ICMS<br /></span>
                    <span class="TextoCamposDireita">{$_icms}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">IVA ST<br /></span>
                    <span class="TextoCamposDireita">{$iva_st}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">BC ICMS ST<br /></span>
                    <span class="TextoCamposDireita">{$_bcicmsst}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Alq ST<br /></span>
                    <span class="TextoCamposDireita">{$alq_icmsst}%</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">ICMS ST<br /></span>
                    <span class="TextoCamposDireita">{$_icmsst}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Out.Imp.<br /></span>
                    <span class="TextoCamposDireita">{$_outimp}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">IPI<br /></span>
                    <span class="TextoCamposDireita">{$_valipi}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">II<br /></span>
                    <span class="TextoCamposDireita">{$_valii}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">NCM<br /></span>
                    <span class="TextoCampos">{$linha['codncm']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Cod.Prod.<br /></span>
                    <span class="TextoCampos">{$linha['codpro']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Produto<br /></span>
                    <span class="TextoCampos">{$linha['descri']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Quant.<br /></span>
                    <span class="TextoCamposDireita">{$_qtdpro}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoLegendaCampos">Unid<br /></span>
                    <span class="TextoCampos">{$linha['unimed']}</span>
                </td>
            </tr>
EOD;
	}

	// na primeira linha, coloca os títulos... nas demais, só coloca os valores
	if (($linha['nItem']+0) != 1) {
		$html = <<<EOD

            <tr>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita" title="origem={$linha['dfe_origem']}">{$linha['nItem']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$_cfop}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$linha['cst']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$_valcon}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$_bcicms}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$alq_icms}%</span>
                </td>
                 <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$alq_icms_efet}%</span>
                </td>
               <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$_icms}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$iva_st}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$_bcicmsst}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$alq_icmsst}%</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$_icmsst}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$_outimp}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$_valipi}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$_valii}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCampos">{$linha['codncm']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCampos">{$linha['codpro']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCampos">{$linha['descri']}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCamposDireita">{$_qtdpro}</span>
                </td>
                <td valign="top" height="30">
                    <span class="TextoCampos">{$linha['unimed']}</span>
                </td>
            </tr>
EOD;
	}

	return $html;

}

function gera_nfe_html_fim() {

	$html = <<<EOD
        </table>
</body>
</html>
EOD;

	return $html;
}

?>