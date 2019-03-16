<?php

$pr->aud_registra(new PrMenu("p17_Mod1Mod3", "P_17", "Modelo 1 e Modelo 3", "p17"));

function p17_Mod1Mod3() {

  global $pr;

  $pr->inicia_excel('P17_Mod1Mod3');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(100);
';

  $sql = "
SELECT * FROM mod3;";
  $col_format = array(
		"H:H" => "0", 
		"I:I" => "#.##0,000",
		"J:J" => "#.##0,00",
		"M:M" => "#.##0,000",
		"N:U" => "#.##0,00",
		"V:V" => "#.##0,000",
		"W:W" => "#.##0,0000",
		"X:AC" => "#.##0,00"
);
  $cabec = array(
		'CodPro' => "Código do Produto",
		'Descri' => "Descrição do Produto",
		'Unid' => "Unidade de Medida", 
		'Alq_ICMS' => "Alíquota do ICMS",
		'Dta de emissão' => "Data de emissão na saída ou de recebimento na entrada",
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
		'Ent_Sér' => "Série da Nota Fiscal de Entrada",
		'Ent_Número' => "Número da Nota Fiscal de Entrada",
		'Ent_QtdPro' => "Quantidade da Mercadoria de Entrada",
		'Ent_VTBCSTRet' => "VALOR TOTAL DA BASE DE CALCULO DO ICMS DE RETENÇÃO NA SUBSTITUIÇÃO TRIBUTARIA de Entrada",
		'Sai_Sér' => "Série da Nota Fiscal de Saída",
		'Sai_Número' => "Número da Nota Fiscal de Saída",
		'Sai_QtdPro' => "Quantidade da Mercadoria de Saída",
		'Sai_VlUnit' => "Valor Unitário DA BASE DE CALCULO DO ICMS DE RETENÇÃO NA SUBSTITUIÇÃO TRIBUTARIA de Saída",
		'Sai_Cons_Usu_Final' => "Saída a Consumidor ou Usuário Final",
		'Sai_Ft_Ger_Nao_Real' => "Saída Fato Gerador Não Realizado",
		'Sai_Isen_NInc' => "Saída com Isenção ou Não Incidência",
		'Sai_Out_UF' => "Saída para Outras UFs DA BASE DE CALCULO DO ICMS DE RETENÇÃO NA SUBSTITUIÇÃO TRIBUTARIA de Saída",
		'Sai_Com_Sub' => "Saída para Comercialização Subsequente DA BASE DE CALCULO DO ICMS DE RETENÇÃO NA SUBSTITUIÇÃO TRIBUTARIA de Saída",
		'ValConf_BCEfSaída' => "Valor de Confronto: Base de Cálculo Efetiva na Saída - Consumidor Final",
		'ValConf_BCEfEntrDH' => "Valor de Confronto: Base de Cálculo Efetiva de Entrada - Demais Hipóteses",
		'Saldo_Qtd' => "Saldo - Quantidade",
		'Saldo_Unitário' => "Valor Unitário da Base de Cálculo de Retenção",
		'Saldo_Total' => "Valor Total da Base de Cálculo de Retenção",
		'Sai_Calc_Cons_Usu_Final' => "Saída Calculada a Consumidor ou Usuário Final",
		'Sai_Calc_Ft_Ger_Nao_Real' => "Saída Calculada Fato Gerador Não Realizado",
		'Sai_Calc_Isen_NInc' => "Saída Calculada com Isenção ou Não Incidência",
		'Sai_Calc_Out_UF' => "Saída Calculada para Outras UFs DA BASE DE CALCULO DO ICMS DE RETENÇÃO NA SUBSTITUIÇÃO TRIBUTARIA de Saída",
		'Sai_Calc_Com_Sub' => "Saída Calculada para Comercialização Subsequente DA BASE DE CALCULO DO ICMS DE RETENÇÃO NA SUBSTITUIÇÃO TRIBUTARIA de Saída"
);
  $pr->abre_excel_sql('Mod_3', 'Modelo 3', $sql, $col_format, $cabec, $form_final);
  
  $sql = "
SELECT * FROM mod1;";
  $col_format = array(
		"H:H" => "0", 
		"F:Y" => "#.##0,00"
);
  $cabec = array(
		'CodPro' => "Código do Produto",
		'Descri' => "Descrição do Produto",
		'Unid' => "Unidade de Medida", 
		'Alq_ICMS' => "Alíquota do ICMS",
		'Mês' => "Mês",
		'Sai_Cons_Usu_Final' => "Saída a Consumidor ou Usuário Final",
		'Sai_Ft_Ger_Nao_Real' => "Saída Fato Gerador Não Realizado",
		'Sai_Isen_NInc' => "Saída com Isenção ou Não Incidência",
		'Sai_Out_UF' => "Saída para Outras UFs DA BASE DE CALCULO DO ICMS DE RETENÇÃO NA SUBSTITUIÇÃO TRIBUTARIA de Saída",
		'ValConf_BCEfSaída' => "Valor de Confronto: Base de Cálculo Efetiva na Saída - Consumidor Final",
		'ValConf_BCEfEntrDH' => "Valor de Confronto: Base de Cálculo Efetiva de Entrada - Demais Hipóteses",
		'BCCompl' => "Diferença de Base de Cálculo do ICMS a ser Complementada",
		'ICMSCompl' => "Diferença de ICMS a ser Complementada",
		'BCRessar' => "Diferença de Base de Cálculo do ICMS a ser Ressarcida",
		'ICMSRessar' => "Diferença de ICMS a ser Ressarcida",
		'Sai_Calc_Cons_Usu_Final' => "Saída Calculada a Consumidor ou Usuário Final",
		'Sai_Calc_Ft_Ger_Nao_Real' => "Saída Calculada Fato Gerador Não Realizado",
		'Sai_Calc_Isen_NInc' => "Saída Calculada com Isenção ou Não Incidência",
		'Sai_Calc_Out_UF' => "Saída Calculada para Outras UFs DA BASE DE CALCULO DO ICMS DE RETENÇÃO NA SUBSTITUIÇÃO TRIBUTARIA de Saída",
		'BC_CalcCompl' => "Diferença Calculada de Base de Cálculo do ICMS a ser Complementada",
		'ICMS_CalcCompl' => "Diferença Calculada de ICMS a ser Complementada",
		'BC_CalcRessar' => "Diferença Calculada de Base de Cálculo do ICMS a ser Ressarcida",
		'ICMS_CalcRessar' => "Diferença Calculada de ICMS a ser Ressarcida",
		'ICMS_Resultado' => "Resultado do ICMS",
		'ICMS_Calc_Resultado' => "Resultado Calculado do ICMS"
);
  $pr->abre_excel_sql('Mod_1', 'Modelo 1', $sql, $col_format, $cabec, $form_final);


  $pr->finaliza_excel();
}


?>