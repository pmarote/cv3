<?php

$pr->aud_registra(new PrMenu("p17_NFe_confronto", "P_17", "Confronto com NFe", "p17,nfe"));
$pr->aud_registra(new PrMenu("p17_NFe_confronto", "_NFe", "Confronto com Port.17/99", "p17,nfe"));

function p17_NFe_confronto() {

  global $pr;

  $pr->inicia_excel('P17_NFe_confronto');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(75);
';
	// Ajuste em Itensnf na DUFRY:
	// UPDATE itensnf SET codpro = 'EMT027/' || codpro WHERE modelo = 55 AND substr(chav_ace, 8, 14) = '27197888007597' AND substr(codpro, 1, 4) <> 'CFOP';
	// UPDATE itensnf SET codpro = 'EMT027/' || substr(descri, 1, 7) WHERE modelo = 55 AND substr(chav_ace, 8, 14) = '27197888007597' AND substr(codpro, 1, 4) = 'CFOP';
  $pr->aud_prepara("
DROP TABLE IF EXISTS p17_nfe_confr;
CREATE TABLE p17_nfe_confr AS
SELECT r02.cnpj AS cnpj, r02.ie AS ie, r02.uf AS uf, r02.dtaemi AS dtaemi,
    r02.serie AS serie, r02.numero AS numero, r02.cfop AS cfop, r02.qtdpro AS qtdpro, r02.vtbcstrt AS vtbcstrt, r02.vtbcstef AS vtbcstef, r02.codpro AS codpro,
    itensnf.cfop AS nfe_cfop, itensnf.qtdpro AS nfe_qtdpro, itensnf.bcicmsst AS nfe_bcicmsst,
    CASE WHEN itensnf.cfop IS NULL THEN 'NFe Nao Encontrada ' ELSE
       CASE WHEN r02.qtdpro = itensnf.qtdpro THEN '' ELSE 'Qtd Erro ' END ||
       CASE WHEN substr(r02.cfop, 1, 1) <> substr(itensnf.cfop, 1, 1) THEN 'CFOP Erro ' ELSE '' END ||
       CASE WHEN r02.cfop < 5000 AND r02.vtbcstrt >  itensnf.bcicmsst THEN 'BC_ST_Ret Erro ' ELSE '' END ||
       ''
    END AS erro
    FROM r02
    LEFT OUTER JOIN itensnf ON r02.numero = itensnf.numero AND r02.cnpj = itensnf.cnpj AND r02.serie + 0 = itensnf.serie AND r02.codpro = itensnf.codpro
    WHERE r02.cfop > 1000;
DROP TABLE IF EXISTS p17_nfe_confr;
CREATE TABLE p17_nfe_confr AS
SELECT p17_itnf_confr.cnpj AS cnpj, p17_itnf_confr.ie AS ie, p17_itnf_confr.uf AS uf, p17_itnf_confr.dtaemi AS dtaemi,
    p17_itnf_confr.serie AS serie, p17_itnf_confr.numero AS numero, p17_itnf_confr.cfop AS cfop, p17_itnf_confr.qtdpro AS qtdpro, p17_itnf_confr.vtbcstrt AS vtbcstrt, p17_itnf_confr.vtbcstef AS vtbcstef, p17_itnf_confr.codpro AS codpro,
    itensnf.cfop AS nfe_cfop, itensnf.qtdpro AS nfe_qtdpro, itensnf.bcicmsst AS nfe_bcicmsst,
    CASE WHEN itensnf.cfop IS NULL THEN 'NFe Nao Encontrada ' ELSE
       CASE WHEN p17_itnf_confr.qtdpro = itensnf.qtdpro THEN '' ELSE 'Qtd Erro ' END ||
       CASE WHEN substr(p17_itnf_confr.cfop, 1, 1) <> substr(itensnf.cfop, 1, 1) THEN 'CFOP Erro ' ELSE '' END ||
       CASE WHEN p17_itnf_confr.cfop < 5000 AND p17_itnf_confr.vtbcstrt >  itensnf.bcicmsst THEN 'BC_ST_Ret Erro ' ELSE '' END ||
       ''
    END p17_itnf_confr erro
    FROM p17_itnf_confr
    LEFT OUTER JOIN itensnf ON r02.numero = itensnf.numero AND r02.cnpj = itensnf.cnpj AND r02.serie + 0 = itensnf.serie AND r02.codpro = itensnf.codpro
    WHERE r02.cfop > 1000;

SELECT p17_itnf_confr.cnpj AS cnpj, p17_itnf_confr.ie AS ie, p17_itnf_confr.uf AS uf, p17_itnf_confr.dtaemi AS dtaemi,
    p17_itnf_confr.serie AS serie, p17_itnf_confr.numero AS numero, p17_itnf_confr.cfop AS cfop,
    p17_itnf_confr.qtdpro AS qtdpro, p17_itnf_confr.vtbcstrt AS vtbcstrt, p17_itnf_confr.vtbcstef AS vtbcstef, p17_itnf_confr.codpro AS codpro,
    itensnf.cfop AS nfe_cfop, itensnf.qtdpro AS nfe_qtdpro, itensnf.bcicmsst AS nfe_bcicmsst,
    CASE WHEN itensnf.cfop IS NULL THEN 'NFe Nao Encontrada ' ELSE
       CASE WHEN p17_itnf_confr.qtdpro = itensnf.qtdpro THEN '' ELSE 'Qtd Erro ' END ||
       CASE WHEN substr(p17_itnf_confr.cfop, 1, 1) <> substr(itensnf.cfop, 1, 1) THEN 'CFOP Erro ' ELSE '' END ||
       CASE WHEN p17_itnf_confr.cfop < 5000 AND p17_itnf_confr.vtbcstrt >  itensnf.bcicmsst THEN 'BC_ST_Ret Erro ' ELSE '' END ||
       ''
    END AS erro
    FROM p17_itnf_confr
    LEFT OUTER JOIN itensnf ON p17_itnf_confr.numero = itensnf.numero AND p17_itnf_confr.item = itensnf.item
      AND p17_itnf_confr.dtaemi = itensnf.dtaemi
     WHERE p17_itnf_confr.cfop > 1000;    
");
  $sql = "
SELECT '', '', '', '', '', '', '', '##NTD##Parte 1', '##NT##Resumo', '', 'Total de Registros r02:', count(cnpj) AS espaco
    FROM r02;
SELECT '##D##Quantidade:' AS cnpj, count(cnpj) AS ie, 'Ano:' AS uf, substr(dtaemi, 1, 4) AS dtaemi,
    '' AS serie, '' AS numero, '' AS cfop, sum(qtdpro) AS qtdpro, sum(vtbcstrt) AS vtbcstrt, '' AS vtbcstef, '' AS codpro,
    '' AS nfe_cfop, sum(nfe_qtdpro) AS nfe_qtdpro, sum(nfe_bcicmsst) AS nfe_bcicmsst, 
    CASE WHEN erro = '' THEN 'Sem Erros - Confronto Ok' ELSE erro END AS erro
   FROM p17_nfe_confr
   GROUP BY erro, dtaemi;    
SELECT '##D##Quantidade:' AS cnpj, count(cnpj) AS ie, 'Ano:' AS uf, substr(dtaemi, 1, 4) AS dtaemi,
    '' AS serie, '' AS numero, '' AS cfop, sum(qtdpro) AS qtdpro, sum(vtbcstrt) AS vtbcstrt, '' AS vtbcstef, '' AS codpro,
    '' AS p32_cfop, '' AS p32_qtdpro, '' AS p32_bcicmsst, 
    CASE WHEN cfop = 2 THEN 'Regs. R02 de Saldo Inicial - Não Confrontados - Ok' ELSE 'Outros Regs. R02 de Controle - Não Confrontados - Ok' END AS erro
   FROM r02
   WHERE r02.cfop <= 1000
   GROUP BY erro, dtaemi;    
SELECT '', '', '', '', '', '', '', '##NTD##Parte 2', '##NT##Listagem', '##NT##dos', '##NT##Erros' AS espaco;
SELECT * FROM p17_nfe_confr
   WHERE erro <> '' AND NOT (erro LIKE '%NFe Nao Encontrada%')
   ORDER BY erro, dtaemi;   
";
  $col_format = array(
		"A:B" => "0", 
		"E:G" => "#.##0", 
		"H:H" => "#.##0,000",
		"I:J" => "#.##0,00",
		"L:L" => "#.##0", 
		"M:M" => "#.##0,000",
		"N:N" => "#.##0,00"
);
  $cabec = array(
		'CNPJ' => "CNPJ do remetente nas entradas e do destinatário nas saídas",
		'Inscrição Estadual' => "Inscrição Estadual do remetente nas entradas e do destinatário nas saídas",
		'UF' => "Sigla da unidade da Federação do remetente  nas entradas e do destinatário nas saídas",
		'Dta de emissão' => "Data de emissão na saída ou de recebimento na entrada",
		'Sér/SK' => "Série da Nota Fiscal ou Estoque Inicial (SK)",
		'Número' => "Número da Nota Fiscal",
		'CFOP' => "Código Fiscal de Operação e Prestação. Poderá ser:
1 - Lançamento de Não Realização de Fato Gerador Presumido (um por dia)
2 - Lançamento de Estoque Inicial
1.403 / 2.403 Compra para comercialização em operação com mercadoria sujeita ao regime de substituição tributária.
1.409 / 2.409 Transferência para comercialização em operação com mercadoria sujeita ao regime de substituição tributária.
1.411 / 2.411 Devolução de venda de mercadoria adquirida ou recebida de terceiros em operação com mercadoria sujeita ao regime de substituição tributária.
1.949 /2.949 Outra entrada de mercadoria ou prestação de serviço não especificada.
6.404 Venda de mercadoria sujeita ao regime de substituição tributária, cujo imposto já tenha sido retido anteriormente.
5.405 Venda de mercadoria adquirida ou recebida de terceiros em operação com mercadoria sujeita ao regime de substituição tributária, na condição de contribuinte substituído.
5.409 / 6.409 Transferência de mercadoria adquirida ou recebida de terceiros em operação com mercadoria sujeita ao regime de substituição tributária.
5.411 / 6.411 Devolução de compra para comercialização em operação com mercadoria sujeita ao regime de substituição tributária.
5.929 Lançamento efetuado em decorrência de emissão de documento fiscal relativo a operação ou prestação também registrada em equipamento Emissor de Cupom Fiscal - ECF.
5.949 / 6.949 Outra saída de mercadoria ou prestação de serviço não especificado.
Combustível e lubrificante, também 1.652 / 2.652, 1.659 / 2.659, 1.661 / 2.661, 1.662 / 2.662,
 5.655 / 6.655, 5.656 / 6.656, 5.659 / 6.659 , 5.661 / 6.661, 5.662 / 6.662",
		'QtdPro' => "Quantidade da Mercadoria",
		'VTBCSTRet' => "VALOR TOTAL DA BASE DE CALCULO DO ICMS DE RETENÇÃO NA SUBSTITUIÇÃO TRIBUTARIA
Corresponde ao valor resultante da multiplicação da quantidade pelo valor unitário da base de cálculo da retenção",
		'Valor de Confronto' => "Não utilizado nas Entradas. 
Base de Cálculo Efetiva para CONFRONTO nas Saídas Destinadas a Consumidor ou Usuário Final (coluna 15 do controle de estoque Mod.3) OU
Base de Cálculo Efetiva para CONFRONTO nas Entradas, nas demais hipóteses (coluna 16 do controle de estoque Mod.3). 
Não utilizado em Saídas para Comercialização Subsequente (lançar zero), porque estas não geram ressarcimento.

P.Cat 17, Art.4º, § 1º - O valor de confronto previsto na alínea e do inciso IV (colunas 15 e 16) será registrado, conforme o caso, como segue:
1 - na coluna Base de Cálculo Efetiva na Saída a Consumidor ou Usuário Final (15), o valor da correspondente operação de saída:
a) realizada com consumidor ou usuário final;
b) na hipótese em que a parcela do imposto retido a ser ressarcido corresponder à saída subseqüente amparada por isenção ou não incidência, nos termos da alínea b do item 2 do § 4º do artigo 248 do Regulamento do ICMS;
2 - na coluna Base de Cálculo Efetiva da Entrada nas Demais Hipóteses (16), o valor da base de cálculo da operação própria do sujeito passivo por substituição do qual a mercadoria tenha sido recebida diretamente ou o valor da base de cálculo que seria atribuída à operação própria do contribuinte substituído do qual a mercadoria tenha sido recebida, caso estivesse submetida ao regime comum de tributação, observado o disposto no § 5º.

§ 5º - O valor da base de cálculo efetiva da entrada (coluna 16), será obtido no documento fiscal correspondente à entrada ou, na impossibilidade de sua identificação, pela ordem:
1 - no controle comum de estoque, se mantido pelo contribuinte para identificação do custo da mercadoria saída;
2 - nos documentos fiscais relativos às entradas mais recentes, suficientes para comportar a quantidade envolvida.",
		'CodPro' => "Código do Produto",
		'NFe_CFOP' => "Código Fiscal de Operação e Prestação no Registro nfe",
		'NFe_QtdPro' => "Quantidade da Mercadoria no Registro nfe",
		'NFe_BCIcmsSt' => "BC do ICMS ST no Registro nfe"
);
  $pr->abre_excel_sql('P17_NFe', 'Confronto Port.Cat 17/99 com NFe', $sql, $col_format, $cabec, $form_final);

  $pr->finaliza_excel();

}


?>