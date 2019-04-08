<?php

$pr->aud_registra(new PrMenu("ecd_dados", "E_CD", "Dados da ECD", "ecd"));

function ecd_dados() {

  global $pr;

  $pr->inicia_excel('ECD_Dados_do_ECD');

  $tem_centro_custos = $pr->aud_sql2array("
	SELECT count(*) AS contagem FROM i100;
");

  if ($tem_centro_custos[0]['contagem'] > 0) {

	// Se houver centro de custos, 
	// Planilha saldos das contas de resultado antes do encerramento agrupado por centro de custos
	$sql = "
SELECT ordi350, dt_res, cod_cta, abs(vl_cta_agr) AS vl_cta,
    CASE WHEN vl_cta_agr < 0 THEN 'D' ELSE 'C' END AS ind_dc
    FROM 
    (SELECT ordi350, dt_res, cod_cta, sum(vl_cta) AS vl_cta_agr FROM 
        (SELECT i355.ord AS ord, ordi350, dt_res, cod_cta, cod_ccus, 
            CASE WHEN ind_dc = 'D'  THEN -vl_cta ELSE vl_cta END AS vl_cta
             FROM i355
             LEFT OUTER JOIN i350 ON i355.ordi350 = i350.ord)
        GROUP BY dt_res, cod_cta);
";
	$col_format = array(
	"A:A" => "0", 
	"C:C" => "0", 
	"D:D" => "#.##0,00"
);
	$cabec = array(
	'Ord350' => "Número da Linha do Registro I350.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011",
	'dt_res' => "Data da apuração do resultado.",
	'cod_cta' => "Código da conta analítica de resultado.",
	'vl_cta' => "Valor do saldo final antes do lançamento de encerramento.",
	'ind_dc' => "Indicador da situação do saldo final:
D - Devedor;
C - Credor."
);
	$form_final = '
	$this->excel_orientacao(2);		// paisagem
';

	$pr->abre_excel_sql("Saldo_Ctas_Res_Agr_CCusto", "Saldos das Contas de Resultado Antes do Encerramento (Registros I350 e I355), agrupados por Centro de Custo", $sql, $col_format, $cabec, $form_final);

  }

  // Planilha saldos das contas de resultado antes do encerramento
  $sql = "
SELECT i355.ord AS ord, ordi350, dt_res, cod_cta, cod_ccus, vl_cta, ind_dc
     FROM i355
     LEFT OUTER JOIN i350 ON i355.ordi350 = i350.ord;
";
  $col_format = array(
	"A:B" => "0", 
	"D:E" => "0", 
	"F:F" => "#.##0,00"
);
  $cabec = array(
	'Ord355' => "Número da Linha do Registro I355.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011",
	'Ord350' => "Número da Linha do Registro I350.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011",
	'dt_res' => "Data da apuração do resultado.",
	'cod_cta' => "Código da conta analítica de resultado.",
	'cod_ccus' => "Código do centro de custos.",
	'vl_cta' => "Valor do saldo final antes do lançamento de encerramento.",
	'ind_dc' => "Indicador da situação do saldo final:
D - Devedor;
C - Credor."
);
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
';

  $pr->abre_excel_sql("Saldo_Ctas_Res", "Saldos das Contas de Resultado Antes do Encerramento (Registros I350 e I355)", $sql, $col_format, $cabec, $form_final);

  
  // Planilha Diário
  $sql = "
SELECT i250.ord, i250.ord200, num_lcto, dt_lcto, ind_lcto, cod_cta, cod_ccus, vl_dc, ind_dc, num_arq, cod_hist_pad, hist, cod_part
   FROM i250
   LEFT OUTER JOIN i200 ON i200.ord = i250.ord200;
";
  $col_format = array(
	"C:C" => "0", 
	"F:G" => "0", 
	"H:H" => "#.##0,00",
	"J:M" => "0", 
);
  $cabec = array(
	'Ord250' => "Número da Linha do Registro 250.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011",
	'Ord200' => "Número da Linha do Registro 200.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011",
	'Num_Lcto' => "Número do Lançamento", 
	'Data' => "Data do Lançamento", 
	'Ind_Lcto' => "Indicador do Lançamento:
N - Normal
E - Lançamento de Encerramento de Ctas de Resultado", 
	'Cod Conta' => "Código da Conta",
	'Cod C Custo' => "Código do Centro de Custo",
	'Valor' => "Valor do Lançamento (Débito ou Crédito)",
	'D/C' => "Indicador de Débito ou Crédito", 
	'Num Arq' => "Número, Código ou caminho de localização dos documentos arquivados.",
	'Cod_Hist_Pad' => "Código do histórico padronizado, conforme tabela I075.",
	'Histórico' => "Descrição do Lançamento",
	'Cod_Part' => "Código de identificação do participante na partida conforme tabela 0150 (preencher somente quando identificado o tipo de participação no registro 0180).");
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_largura_coluna("L:L", 50);
';

  $pr->abre_excel_sql("Diario", "Diário (Registros I200 e I250)", $sql, $col_format, $cabec, $form_final);

  if ($tem_centro_custos[0]['contagem'] > 0) {

	// Se houver centro de custos, 
	// Planilha Saldos agrupada por Centro de Custos
  $sql = "
SELECT ord150, mes, cod_cta, abs(vl_sld_ini_agr) AS vl_sld_ini,
     CASE WHEN vl_sld_ini_agr < 0 THEN 'D' ELSE 'C' END AS ind_dc_ini,
     vl_deb, vl_cred, abs(vl_sld_fin_agr) AS vl_sld_fin,
     CASE WHEN vl_sld_fin_agr < 0 THEN 'D' ELSE 'C' END AS ind_dc_fin
     FROM
    (SELECT ord150, mes, cod_cta, sum(vl_sld_ini) AS vl_sld_ini_agr, sum(vl_deb) AS vl_deb, sum(vl_cred) AS vl_cred, sum(vl_sld_fin) AS vl_sld_fin_agr FROM
        (SELECT ord, ord150, mes, cod_cta, cod_ccus, 
            CASE WHEN ind_dc_ini = 'D' THEN -vl_sld_ini ELSE vl_sld_ini END AS vl_sld_ini,
	    vl_deb, vl_cred, 
	    CASE WHEN ind_dc_fin = 'D' THEN -vl_sld_fin ELSE vl_sld_fin END AS vl_sld_fin
            FROM saldos
            WHERE cod_cta NOT LIKE 'NS________' AND cod_cta NOT LIKE 'NS__________')
    GROUP BY mes, cod_cta);
";
  $col_format = array(
	"C:C" => "0", 
	"D:D" => "#.##0,00", 
	"F:H" => "#.##0,00");
  $cabec = array(
	'Ord150' => "Número da Linha do Registro 150.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011", 
	'Mês' => "Mês", 
	'Conta' => "Código da Conta", 
	'Saldo Inicial' => "Valor do Saldo Inicial", 
	'D/C' => "Saldo Inicial - Débito/Crédito", 
	'Débitos' => "Débitos", 
	'Créditos' => "Créditos", 
	'Saldo Final' => "Valor do Saldo Final",
	'D/Cf' => "Saldo Final - Débito/Crédito");
	$pr->abre_excel_sql("Saldos_Agr_CCusto", "Saldos (Registros I150 e I155) agrupados por Centro de Custo", $sql, $col_format, $cabec);

  }

	// Planilha Saldos
  $sql = "
SELECT ord, ord150, mes, cod_cta, cod_ccus, vl_sld_ini, ind_dc_ini, vl_deb, vl_cred, vl_sld_fin, ind_dc_fin
    FROM saldos
    WHERE cod_cta NOT LIKE 'NS________' AND cod_cta NOT LIKE 'NS__________';
";
  $col_format = array(
	"D:D" => "0", 
	"F:F" => "#.##0,00", 
	"H:J" => "#.##0,00");
  $cabec = array(
	'Ord155' => "Número da Linha do Registro 155.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011",
	'Ord150' => "Número da Linha do Registro 150.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011", 
	'Mês' => "Mês", 
	'Conta' => "Código da Conta", 
	'C.Custo' => "Código do Centro de Custo", 
	'Saldo Inicial' => "Valor do Saldo Inicial", 
	'D/C' => "Saldo Inicial - Débito/Crédito", 
	'Débitos' => "Débitos", 
	'Créditos' => "Créditos", 
	'Saldo Final' => "Valor do Saldo Final",
	'D/Cf' => "Saldo Final - Débito/Crédito");
  $pr->abre_excel_sql("Saldos", "Saldos (Registros I150 e I155)", $sql, $col_format, $cabec);

  // Planilha Centro de Custos 
  $sql = "
	SELECT dt_alt, cod_ccus, ccus FROM i100;
";
  $col_format = array(
	"B:B" => "0");
  $cabec = array(
	"Dt_Alt" => "Data da inclusão/alteração.",
	"Cod_CCus" => "Código do centro de custos.",
	"CCus" => "Nome do centro de custos.");
  $pr->abre_excel_sql("CentCustos", "Centros de Custos (Registros I100)", $sql, $col_format, $cabec);

  // Planilha Contas
  $sql = "
	SELECT cod_cta, dt_alt, cod_nat, ind_cta, nivel, cod_cta_sup, cta, cod_ccus, cod_cta_ref, cod_agl FROM contas
	WHERE cod_cta NOT LIKE 'NS________' AND cod_cta NOT LIKE 'NS__________';
";
  $col_format = array(
	"A:F" => "0");
  $cabec = array(
	"Cod_Cta" => "Código da Conta",
	"Dt_Alt" => "Data da Inclusão ou Alteração",
	"Cod_Nat" => "Código da Natureza da conta, podendo ser:
01  Contas de ativo  
02  Contas de passivo  
03  Patrimônio líquido  
04  Contas de resultado  
05  Contas de compensação
09  Outras",
	"Ind_Cta" => "Indicador do tipo de conta:
S - Sintética (grupo de contas)
A - Analítica (conta).",
	"Nível" => "Nível da Conta
(Ativo, Passivo, etc.). Deve ser acrescido de 1 a cada mudança de nível. Exemplo:
Nível  Grupo/Conta:
1      Ativo
2      Ativo Circulante
3      Disponível
4      Caixa",
	"Cod_Cta_Sup" => "Código da Conta de Nível Superior",
	"Cta" => "Descrição da Conta",
	"Cod_Ccus" => "Código do Centro de Custo",
	"Cod_Cta_Ref" => "Código da Conta do Plano de Contas Referencial",
	"Cod_Agl" => "Código de Aglutinação");
  $pr->abre_excel_sql("Contas", "Contas - Registros I050, I051(opcional) e I052(opcional)", $sql, $col_format, $cabec);
  
  // Planilha Plano de Contas Referencial
  $sql = "
SELECT * FROM plactaref;
";
  $col_format = array(
	"A:A" => "@");
  $cabec = array(
	"Conta" => "Código da Conta do Plano Referencial",
	"Nome"	=> "Descrição da Contas",
	"Data Início" => "Data de Início de Validade da Conta",
	"Data Final" => "Data de Fim de Validade da Conta",
	"A/S" => "Conta Analítica ou Sintética");
  $pr->abre_excel_sql("PlanCtaRef", "Plano de Contas Referencial", $sql, $col_format, $cabec);

  // Planilha DRE
  $sql = "
SELECT dt_ini, dt_fin, cod_agl, nivel_agl, 
  descr_cod_agl, 
  CASE WHEN ind_vl IN ('R','P') THEN vl_cta ELSE -vl_cta END AS vl_cta,
  CASE WHEN ind_vl IN ('P','N') THEN 
    CASE WHEN nivel_agl + 0 = 1 THEN '##C##' || ind_vl ELSE '##T##' || ind_vl END
  ELSE ind_vl END AS ind_vl FROM j150;
";
  $col_format = array(
	"C:D" => "0",
	"F:F" => "#.##0,00");
  $cabec = array(
	'dt_ini' => 'Data Inicial do DRE',
	'dt_fin' => 'Data Final do DRE',
	'Cod_Agl' => 'Código de aglutinação das contas, atribuído pelo empresário ou sociedade empresária.',
	'Nível_Agl' => 'Nível do Código de aglutinação (mesmo conceito do plano de contas – Registro I050).',
	'Descr_Cod_Agl' => 'Descrição do Código de aglutinação.',
	'Vl_Cta' => 'Valor total do Código de aglutinação na Demonstração do Resultado do Exercício no período informado.',
	'Ind_Vl' => 'Indicador da situação do valor informado no campo anterior: 
D - Despesa ou valor que represente parcela redutora do lucro;
R - Receita ou valor que represente incremento do lucro;
P - Subtotal ou total positivo;
N – Subtotal ou total negativo.');
  $pr->abre_excel_sql('DRE', 'DRE (Registro J150)', $sql, $col_format, $cabec);


  // Planilha Balanco
  $sql = "SELECT dt_ini, dt_fin, cod_agl, 
  CASE WHEN nivel_agl + 0 = 1 THEN '##C##' || nivel_agl ELSE
    CASE WHEN nivel_agl + 0 = 2 THEN '##T##' || nivel_agl ELSE nivel_agl END
  END AS nivel_agl, ind_grp_bal, descr_cod_agl, vl_cta, ind_dc_bal FROM j100";
  $col_format = array(
	"C:E" => "0",
	"G:G" => "#.##0,00");
  $cabec = array(
	'dt_ini' => 'Data Inicial do Balanço',
	'dt_fin' => 'Data Final do Balanço',
	'Cod_Agl' => 'Código de aglutinação das contas, atribuído pelo empresário ou sociedade empresária.',
	'Nível_Agl' => 'Nível do Código de aglutinação (mesmo conceito do plano de contas – Registro I050).',
	'Ind_Grp_Bal' => 'Indicador de grupo do balanço:
1 – Ativo;
2 – Passivo e Patrimônio Líquido;',
	'Descr_Cod_Agl' => 'Descrição do Código de aglutinação.',
	'Vl_Cta' => 'Valor total do Código de aglutinação no Balanço Patrimonial no exercício informado, ou de período definido em norma específica.',
	'Ind_DC_Bal' => 'Indicador da situação do saldo informado no campo anterior:
D - Devedor;
C – Credor.');
  $pr->abre_excel_sql('Balanco', 'Balanço (Registro J100)', $sql, $col_format, $cabec);  



  // Planilha 0180
  $sql = "SELECT * FROM r0180";
  $col_format = array(
	"C:E" => "0",
	"G:G" => "#.##0,00");
  $cabec = array(
	'Ord0180' => "Número da Linha do Registro 0180.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011",
	'Ord0150' => "Número da Linha do Registro 0150.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011",
	'cod_rel' => "Código do relacionamento conforme tabela publicada pelo Sped
01 Matriz no exterior
02 Filial, inclusive agência ou dependência, no exterior
03 Coligada, inclusive equiparada
04 Controladora
05 Controlada (exceto subsidiária integral)
06 Subsidiária integral
07 Controlada em conjunto
08 Entidade de Propósito Específico (conforme definição da CVM)
09 Participante do conglomerado, conforme norma específica do órgão regulador, exceto as que se enquadrem nos tipos precedentes
10 Vinculadas (Art. 23 da Lei 9.430/96), exceto as que se enquadrem nos tipos precedentes
11 Localizada em país com tributação favorecida (Art. 24 da Lei 9.430/96), exceto as que se enquadrem nos tipos precedentes",
	'dt_ini_rel' => "Data do início do relacionamento",
	'dt_fin_rel' => "Data do término do relacionamento");
  $pr->abre_excel_sql('R_0180', '0180 - Identificação do Relacionamento com o Participante', $sql, $col_format, $cabec);  


  // Planilha 0150
  $sql = "SELECT * FROM r0150";
  $col_format = array(
	"C:E" => "0",
	"G:G" => "#.##0,00");
  $cabec = array(
	'Ord0150' => "Número da Linha do Registro 0150.
Para possibilitar a utilização de vários arquivos ECD, foi utilizado o seguinte formato:
{anoAA}{mes_inicial}{nro_da_linha0000000}
exemplo... 11010000234 -> Linha 234, mes inicial 01, ano 2011",
	'cod_part' => "Código de identificação do participante no arquivo.",
	'nome' => "Nome pessoal ou empresarial do participante.",
	'cod_pais' => "Código do país do participante, conforme a tabela do Banco Central do Brasil.",
	'cnpj' => "CNPJ do participante.",
	'cpf' => "CPF do participante.",
	'nit' => "Número de Identificação do Trabalhador, Pis, Pasep, SUS.",
	'uf' => "Sigla da unidade da federação do participante.",
	'ie' => "Inscrição Estadual do participante.",
	'ie_st' => "Inscrição Estadual do participante na unidade da federação do destinatário, na condição de contribuinte substituto.",
	'cod_mun' => "Código do município, conforme a tabela do IBGE.",
	'im' => "Inscrição Municipal do participante.",
	'suframa' => "Número de inscrição do participante na Suframa.");
  $pr->abre_excel_sql('R_0150', '0150 - Tabela de Cadastro do Participante', $sql, $col_format, $cabec);  



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
  $pr->abre_excel_sql("Resumo", "Resumo do(s) ECD(s)", $sql, $col_format, $cabec, $form_final);



  $pr->finaliza_excel();
  
}

?>