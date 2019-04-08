<?php

$pr->aud_registra(new PrMenu("ecd_dre_dinamico", "E_CD", "Saldos Mensais e Diários das Contas de Resultado e Confronto com DRE", "ecd"));

function ecd_dre_dinamico() {

  global $pr;

  $dialog = new GtkDialog('Opções', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 100);

  $lbl_obs1 	= new GtkLabel("Este módulo mostra os Saldos Mensais e Diários das Contas de Resultado, efetuando também o confronto com DRE.");
  $lbl_obs2 	= new GtkLabel("Neste processamento, são excluídos os Lançamentos de Apuração de Resultado (i200.ind__lcto = E)");
  $lbl_obs3 	= new GtkLabel("Nos saldos diários, são mostrados apenas os dias em que há lançamentos envolvendo contas de resultado.");

  $dialog->vbox->pack_start($lbl_obs1, false, false, 3);
  $dialog->vbox->pack_start($lbl_obs2, false, false, 3);
  $dialog->vbox->pack_start($lbl_obs3, false, false, 3);

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
  wecho('*');

  $pr->db->exec("
DROP TABLE IF EXISTS saldia;
CREATE TABLE saldia (cod_cta TEXT, data TEXT, valor REAL);
CREATE INDEX saldia_chapri ON saldia (cod_cta ASC);
BEGIN;
");


  // A criação dos Saldos Diários é feita no Array $saldia e depois gravado na tabela saldia
  // O saldo inicial é feito no dia zero. Ou seja... se o primeiro dia for 2010-01-01, o saldo inicial estará em 2010-01-00
  $mes_inicial = $pr->aud_sql2array("
  SELECT min(mes) || '-00' AS dtaini FROM saldos;
");
  // Primeiro, verifica todas as contas de resultado que tem movimento ou saldo inicial
  $contas_saldos = $pr->aud_sql2array("
SELECT DISTINCT sel1.cod_cta FROM 
      (SELECT DISTINCT cod_cta
         FROM saldos WHERE mes IN (SELECT min(mes) FROM saldos) AND cod_cta NOT LIKE 'NS________' AND cod_cta NOT LIKE 'NS__________'
      UNION ALL
      SELECT DISTINCT cod_cta
         FROM diario) AS sel1
    LEFT OUTER JOIN contas ON contas.cod_cta = sel1.cod_cta
    WHERE contas.cod_nat + 0 = 4;
");
  // em seguida, verifica todos os dias que tem movimento de conta de resultado, exceto apuração de resultado
  // Vai ter um índice de array para cada dia, para cada conta
  //debug_log("contas_saldos=" . print_r($contas_saldos, true));	//debug
  $dias = $pr->aud_sql2array("
SELECT  DISTINCT dt_lcto FROM diario
   LEFT OUTER JOIN contas ON contas.cod_cta = diario.cod_cta
   WHERE contas.cod_nat + 0 = 4 AND diario.ind_lcto <> 'E'
   ORDER BY dt_lcto;
");
  //debug_log("dias=" . print_r($dias, true));	// debug
  // monta o array do dia inicial e dos dias com movimentos, ou seja, todos os campos possíveis, com valor igual a zero
  $saldia = array();
  foreach($contas_saldos AS $indice => $valor) {
    $saldia[$valor['cod_cta']] = 0;
  }
  unset($contas_saldos);	// liberando memória
  //debug_log("saldia=" . print_r($saldia, true));	//debug
  
  // saldos iniciais das contas de resultado (o query abaixo retorna também os saldos iniciais das contas sintéticas)
  $saldos_iniciais = $pr->aud_sql2array("
      SELECT saldos.cod_cta AS cod_cta, mes || '-00' AS dt_lcto, 
         CASE WHEN contas.cod_nat + 0 = 1 THEN
	    CASE WHEN ind_dc_ini = 'D' THEN vl_sld_ini ELSE -vl_sld_ini END
	 ELSE
	    CASE WHEN ind_dc_ini = 'D' THEN -vl_sld_ini ELSE vl_sld_ini END
	 END AS saldo
         FROM saldos 
	 LEFT OUTER JOIN contas ON contas.cod_cta = saldos.cod_cta
	 WHERE contas.cod_nat + 0 = 4 AND 
	       mes IN (SELECT min(mes) FROM saldos) AND saldos.cod_cta NOT LIKE 'NS________' AND saldos.cod_cta NOT LIKE 'NS__________';
");
  wecho('*');

  // Saldos Iniciais
  foreach($saldos_iniciais AS $indice => $valor) {
	$saldia[$valor['cod_cta']] = $valor['saldo'] + 0;
  }
  //debug_log("saldia_pt2=" . print_r($saldia, true));	//debug
  foreach($saldia AS $indice => $valor) {
    $valor_ponto = str_replace(',', '.', $valor);
	$pr->db->exec("
INSERT INTO saldia VALUES ('{$indice}', '{$mes_inicial[0]['dtaini']}', {$valor_ponto});
");
  }
  unset($saldos_iniciais);	// liberando memória
  
  // Saldos a cada dia de movimentos, sem lançamentos de encerramento
  $saldia_transp_sqlaux = '';	// para complementar o sql saldia_transp mais abaixo
  $cabec_final = '';	// para complementar o cabeçalho no excel, com cada uma das datas
  $sql_dre_din = '';	// para complementar o SQL do DRE dinâmico
  foreach($dias AS $ind_dias => $val_dias) {
	$saldia_transp_sqlaux .= " ,
      sum(CASE WHEN data = '{$val_dias['dt_lcto']}' THEN valor ELSE 0 END) AS d" . str_replace('-', '_', $val_dias['dt_lcto']);
	$data_brasil = substr($val_dias['dt_lcto'], 8, 2) . '-' . substr($val_dias['dt_lcto'], 5, 2) . '-' . substr($val_dias['dt_lcto'], 0, 4);
	$cabec_final .= " ,
	'd{$data_brasil}' => 'Valor do Saldo em {$data_brasil}'";
	$sql_dre_din .= ",
         sum(saldia_transp.d" . str_replace('-', '_', $val_dias['dt_lcto']) . ") AS d" . str_replace('-', '_', $val_dias['dt_lcto']);
	$mov_dia = $pr->aud_sql2array("
SELECT 
   CASE WHEN contas.cod_nat + 0 = 1 THEN
      CASE WHEN ind_dc = 'D' THEN vl_dc ELSE -vl_dc END
   ELSE
      CASE WHEN ind_dc = 'D' THEN -vl_dc ELSE vl_dc END
   END AS saldo,
   cod_cta_n1, cod_cta_n2, cod_cta_n3, cod_cta_n4, 
   cod_cta_n5, cod_cta_n6, cod_cta_n7, cod_cta_n8
   FROM diario
   LEFT OUTER JOIN contas ON contas.cod_cta = diario.cod_cta
   WHERE contas.cod_nat + 0 = 4 AND dt_lcto = '{$val_dias['dt_lcto']}'
   AND num_lcto NOT IN (SELECT DISTINCT num_lcto FROM diario WHERE diario.ind_lcto = 'E');
");
	//debug_log(print_r($mov_dia, True));	//debug
	foreach($mov_dia AS $indice => $valor) {
	  if ($valor['cod_cta_n1'] <> '') $saldia[$valor['cod_cta_n1']] += $valor['saldo'];
	  if ($valor['cod_cta_n2'] <> '') $saldia[$valor['cod_cta_n2']] += $valor['saldo'];
	  if ($valor['cod_cta_n3'] <> '') $saldia[$valor['cod_cta_n3']] += $valor['saldo'];
	  if ($valor['cod_cta_n4'] <> '') $saldia[$valor['cod_cta_n4']] += $valor['saldo'];
	  if ($valor['cod_cta_n5'] <> '') $saldia[$valor['cod_cta_n5']] += $valor['saldo'];
	  if ($valor['cod_cta_n6'] <> '') $saldia[$valor['cod_cta_n6']] += $valor['saldo'];
	  if ($valor['cod_cta_n7'] <> '') $saldia[$valor['cod_cta_n7']] += $valor['saldo'];
	  if ($valor['cod_cta_n8'] <> '') $saldia[$valor['cod_cta_n8']] += $valor['saldo'];
	}
	unset($mov_dia);
	//debug_log(print_r($sal_dia, True));	//debug
	foreach($saldia AS $indice => $valor) {
	  $valor_ponto = str_replace(',', '.', $valor);
	  $pr->db->exec("
INSERT INTO saldia VALUES ('{$indice}', '{$val_dias['dt_lcto']}', {$valor_ponto});
");
	}
	wecho('*');
  }

  // Saldos mensais: o processamento é o mesmo, a diferença é no SQL
  //	No SQL de saldos diário, onde está saldia_transp.* vira saldia_transp.d2011_01_31, etc...
  $sql_salmes = '';		// vai ser completado conforme fórmula acima
  $cabec_final_mes = '';	// para complementar o cabeçalho no excel, com cada uma das datas
  $sql_dre_din_mes = '';	// para complementar o SQL do DRE dinâmico
  $dia = $mes_inicial[0]['dtaini'];
  $mes = substr($dia, 5, 2);
  $ano = substr($dia, 0, 4);
  // Cada mudança de ano ou mês, pega o último dia...
  foreach($dias AS $ind_dias => $val_dias) {
    // debug_log("dia={$dia} mes={$mes} ano={$ano} valdias={$val_dias['dt_lcto']}");
	if($mes <> substr($val_dias['dt_lcto'], 5, 2) || $ano <> substr($val_dias['dt_lcto'], 0, 4)) {
	  // mudou o mês ou ano ? Pega o dia anterior, que vai ser o último dia do mês...
	  $mes = substr($val_dias['dt_lcto'], 5, 2);
	  $ano = substr($val_dias['dt_lcto'], 0, 4);
	  $sql_salmes .= " ,
      saldia_transp.d" . str_replace('-', '_', $dia);

	  $mes_brasil = substr($dia, 5, 2) . '-' . substr($dia, 0, 4);
	  $cabec_final_mes .= " ,
	  'm{$mes_brasil}' => 'Valor do Saldo Final no Mês {$mes_brasil}'";

	  $sql_dre_din_mes .= ",
         sum(saldia_transp.d" . str_replace('-', '_', $dia) . ") AS d" . str_replace('-', '_', $dia);

	}
	$dia = $val_dias['dt_lcto'];
  }
  // por fim, pega também o última dia do ano
  $sql_salmes .= " ,
      saldia_transp.d" . str_replace('-', '_', $dia);
  // debug_log("sql_salmes=" . $sql_salmes);
  $mes_brasil = substr($dia, 5, 2) . '-' . substr($dia, 0, 4);
  $cabec_final_mes .= " ,
  'm{$mes_brasil}' => 'Valor do Saldo Final no Mês {$mes_brasil}'";

  $sql_dre_din_mes .= ",
         sum(saldia_transp.d" . str_replace('-', '_', $dia) . ") AS d" . str_replace('-', '_', $dia);



  $pr->db->exec("
DROP TABLE IF EXISTS saldia_transp;
CREATE TABLE saldia_transp AS
SELECT cod_cta,
      sum(CASE WHEN data = '{$mes_inicial[0]['dtaini']}' THEN valor ELSE 0 END) AS sld_ini{$saldia_transp_sqlaux}
      FROM saldia
      GROUP BY cod_cta;
CREATE INDEX saldia_transp_chapri ON saldia_transp (cod_cta ASC);
");
  
  $pr->db->exec('COMMIT;'); 

  $pr->aud_prepara("");

  $pr->inicia_excel('ECD_Saldos_Mens_Dia_DRE');


  // Saldos Mensais
  $sql = "
SELECT cod_cta_n1, cod_cta_n2, cod_cta_n3, cod_cta_n4, cod_cta_n5, cod_cta_n6, 
      dt_alt, cod_nat, ind_cta, nivel, cod_cta_sup, cta, cod_ccus, cod_agl, cod_cta_ref, desc_cta_ref, saldia_transp.cod_cta, saldia_transp.sld_ini
	  {$sql_salmes}
     FROM
    (SELECT 
       CASE WHEN nivel = 1 THEN '##E##' || cod_cta_n1 ELSE cod_cta_n1 END AS cod_cta_n1,
       CASE WHEN nivel = 2 THEN '##T##' || cod_cta_n2 ELSE cod_cta_n2 END AS cod_cta_n2,
       cod_cta_n3,
       cod_cta_n4,
       cod_cta_n5,
       CASE WHEN nivel >= 6 THEN contas.cod_cta ELSE Null END AS cod_cta_n6,
      contas.cod_cta AS cod_cta, dt_alt, cod_nat, contas.ind_cta AS ind_cta, nivel, cod_cta_sup, contas.cta AS cta, cod_ccus, cod_agl, 
      contas.cod_cta_ref AS cod_cta_ref, plactaref.cta AS desc_cta_ref
      FROM contas
      LEFT OUTER JOIN plactaref ON plactaref.cod_cta_ref = contas.cod_cta_ref
	  WHERE contas.cod_nat + 0 = 4 AND contas.cod_cta NOT LIKE 'NS________' AND contas.cod_cta NOT LIKE 'NS__________'
       ORDER BY ordcta) AS sqlaux
      LEFT OUTER JOIN saldia_transp ON saldia_transp.cod_cta = sqlaux.cod_cta;";
  $col_format = array(
	"A:F" => "0",
	"K:K" => "0",
	"M:Q" => "0",
	"R:NQ" => "#.##0,00");
  $string_cabec = <<<EOD
cabec = array(
	"Conta_N1" => "Código da Conta, quando de Nível 1",
	"Conta_N2" => "Código da Conta, quando de Nível 2",
	"Conta_N3" => "Código da Conta, quando de Nível 3",
	"Conta_N4" => "Código da Conta, quando de Nível 4",
	"Conta_N5" => "Código da Conta, quando de Nível 5",
	"Conta_N6+" => "Código da Conta, quando de Nível 6 ou superior",
	"Data Inc/Alt" => "Data da Inclusão ou Alteração",
	"Natureza" => "Código da Natureza da conta, podendo ser:
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
	"Conta Sup" => "Código da Conta de Nível Superior",
	"Nome" => "Descrição da Conta",
	"C.Custo" => "Código do Centro de Custo",
	"Cod_Agl" => "Código de aglutinação utilizado no Balanço Patrimonial e na Demonstração de Resultado do Exercício no Bloco J (somente para as contas analíticas).",
	"Cod_Conta Ref" => "Código da Conta do Plano de Contas Referencial",
	"Conta Ref" => "Descrição da Conta do Plano de Contas Referencial",
	"Cod_Cta" => "Código da Conta",
	"Sld_Ini" => "Saldo Inicial"
	{$cabec_final_mes}
);
EOD;
  //
  eval('$'.$string_cabec);
  //debug_log("cabec_final=" . print_r($cabec_final, true));	//debug  
  //debug_log("string_cabec=" . print_r($string_cabec, true));	//debug
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(75);
';


  $pr->abre_excel_sql("Saldos_Mensais", "Saldos Mensais, no seguinte Padrão: Receitas: Positivo; Despesas: Negativo", $sql, $col_format, $cabec, $form_final);


  
  

  // Saldos Diários
  $sql = "
SELECT cod_cta_n1, cod_cta_n2, cod_cta_n3, cod_cta_n4, cod_cta_n5, cod_cta_n6, 
      dt_alt, cod_nat, ind_cta, nivel, cod_cta_sup, cta, cod_ccus, cod_agl, cod_cta_ref, desc_cta_ref, saldia_transp.*
     FROM
    (SELECT 
       CASE WHEN nivel = 1 THEN '##E##' || cod_cta_n1 ELSE cod_cta_n1 END AS cod_cta_n1,
       CASE WHEN nivel = 2 THEN '##T##' || cod_cta_n2 ELSE cod_cta_n2 END AS cod_cta_n2,
       cod_cta_n3,
       cod_cta_n4,
       cod_cta_n5,
       CASE WHEN nivel >= 6 THEN contas.cod_cta ELSE Null END AS cod_cta_n6,
      contas.cod_cta AS cod_cta, dt_alt, cod_nat, contas.ind_cta AS ind_cta, nivel, cod_cta_sup, contas.cta AS cta, cod_ccus, cod_agl, 
      contas.cod_cta_ref AS cod_cta_ref, plactaref.cta AS desc_cta_ref
      FROM contas
      LEFT OUTER JOIN plactaref ON plactaref.cod_cta_ref = contas.cod_cta_ref
	  WHERE contas.cod_nat + 0 = 4 AND contas.cod_cta NOT LIKE 'NS________' AND contas.cod_cta NOT LIKE 'NS__________'
       ORDER BY ordcta) AS sqlaux
      LEFT OUTER JOIN saldia_transp ON saldia_transp.cod_cta = sqlaux.cod_cta;";
  $col_format = array(
	"A:F" => "0",
	"K:K" => "0",
	"M:Q" => "0",
	"R:NQ" => "#.##0,00");
  $string_cabec = <<<EOD
cabec = array(
	"Conta_N1" => "Código da Conta, quando de Nível 1",
	"Conta_N2" => "Código da Conta, quando de Nível 2",
	"Conta_N3" => "Código da Conta, quando de Nível 3",
	"Conta_N4" => "Código da Conta, quando de Nível 4",
	"Conta_N5" => "Código da Conta, quando de Nível 5",
	"Conta_N6+" => "Código da Conta, quando de Nível 6 ou superior",
	"Data Inc/Alt" => "Data da Inclusão ou Alteração",
	"Natureza" => "Código da Natureza da conta, podendo ser:
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
	"Conta Sup" => "Código da Conta de Nível Superior",
	"Nome" => "Descrição da Conta",
	"C.Custo" => "Código do Centro de Custo",
	"Cod_Agl" => "Código de aglutinação utilizado no Balanço Patrimonial e na Demonstração de Resultado do Exercício no Bloco J (somente para as contas analíticas).",
	"Cod_Conta Ref" => "Código da Conta do Plano de Contas Referencial",
	"Conta Ref" => "Descrição da Conta do Plano de Contas Referencial",
	"Cod_Cta" => "Código da Conta",
	"Sld_Ini" => "Saldo Inicial"
	{$cabec_final}
);
EOD;
  //
  eval('$'.$string_cabec);
  //debug_log("cabec_final=" . print_r($cabec_final, true));	//debug  
  //debug_log("string_cabec=" . print_r($string_cabec, true));	//debug
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(75);
';


//  $pr->abre_excel_sql("Saldos_Diários", "Saldos Diários, no seguinte Padrão: Contas de Ativo: Positivo = Débito; Negativo = Crédito  --  Contas //de Passivo, Patrimônio Líquido, Resultado, Compensação ou Outras: Positivo = Crédito; Negativo = Débito", $sql, $col_format, $cabec, $form_final);
  $pr->abre_excel_sql("Saldos_Diários", "Saldos Diários, no seguinte Padrão: Receitas: Positivo; Despesas: Negativo", $sql, $col_format, $cabec, $form_final);



  // DRE Dinâmico Mensal
  $sql = "
SELECT 
  dt_ini, dt_fin, nivel_agl, 
  CASE WHEN ind_vl IN ('P','N') THEN '' ELSE '     ' END || descr_cod_agl AS descr_cod_agl,
  sel1.*,
  CASE WHEN ind_vl IN ('R','P') THEN vl_cta ELSE -vl_cta END AS vl_cta_dre,
  CASE WHEN ind_vl IN ('P','N') THEN 
    CASE WHEN nivel_agl + 0 = 1 THEN '##EN##' || ind_vl ELSE '##TN##' || ind_vl END
  ELSE ind_vl END AS ind_vl
  FROM
      (SELECT contas.cod_agl AS cod_agl, 
         sum(saldia_transp.sld_ini) AS sld_ini
		 {$sql_dre_din_mes}
         FROM contas, saldia_transp
         WHERE contas.cod_cta = saldia_transp.cod_cta AND cod_agl <> ''
         GROUP BY cod_agl) AS sel1
   LEFT OUTER JOIN j150 ON j150.cod_agl = sel1.cod_agl
   ORDER BY j150.nivel_agl DESC; 
";
  $col_format = array(
	"C:E" => "0",
	"F:NQ" => "#.##0,00");
  $string_cabec = <<<EOD
cabec = array(
	'dt_ini' => 'Data Inicial do DRE',
	'dt_fin' => 'Data Final do DRE',
	'Nível_Agl' => 'Nível do Código de aglutinação (mesmo conceito do plano de contas – Registro I050).',
	'Descr_Cod_Agl' => 'Descrição do Código de aglutinação.',
	'Cod_Agl' => 'Código de aglutinação das contas, atribuído pelo empresário ou sociedade empresária.',
	"Sld_Ini" => "Saldo Inicial"
	{$cabec_final_mes},
	'Vl_Cta_DRE_J150' => 'Valor total do Código de aglutinação na Demonstração do Resultado do Exercício no período informado, no registro J150.',
	'Ind_Vl_J150' => "Indicador da situação do valor informado no campo anterior, no registro J150: 
D - Despesa ou valor que represente parcela redutora do lucro;
R - Receita ou valor que represente incremento do lucro;
P - Subtotal ou total positivo;
N – Subtotal ou total negativo."
);
EOD;
  eval('$'.$string_cabec);
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(75);
';

  $pr->abre_excel_sql("DRE_Dinamico_Mes", "Saldos Mensais agrupados conforme DRE, no seguinte Padrão: Receitas: Positivo; Despesas: Negativo", $sql, $col_format, $cabec, $form_final);




  // DRE Dinâmico Diário
  $sql = "
SELECT 
  dt_ini, dt_fin, nivel_agl, 
  CASE WHEN ind_vl IN ('P','N') THEN '' ELSE '     ' END || descr_cod_agl AS descr_cod_agl,
  sel1.*,
  CASE WHEN ind_vl IN ('R','P') THEN vl_cta ELSE -vl_cta END AS vl_cta_dre,
  CASE WHEN ind_vl IN ('P','N') THEN 
    CASE WHEN nivel_agl + 0 = 1 THEN '##EN##' || ind_vl ELSE '##TN##' || ind_vl END
  ELSE ind_vl END AS ind_vl
  FROM
      (SELECT contas.cod_agl AS cod_agl, 
         sum(saldia_transp.sld_ini) AS sld_ini
		 {$sql_dre_din}
         FROM contas, saldia_transp
         WHERE contas.cod_cta = saldia_transp.cod_cta AND cod_agl <> ''
         GROUP BY cod_agl) AS sel1
   LEFT OUTER JOIN j150 ON j150.cod_agl = sel1.cod_agl
   ORDER BY j150.nivel_agl DESC; 
";
  $col_format = array(
	"C:E" => "0",
	"F:NQ" => "#.##0,00");
  $string_cabec = <<<EOD
cabec = array(
	'dt_ini' => 'Data Inicial do DRE',
	'dt_fin' => 'Data Final do DRE',
	'Nível_Agl' => 'Nível do Código de aglutinação (mesmo conceito do plano de contas – Registro I050).',
	'Descr_Cod_Agl' => 'Descrição do Código de aglutinação.',
	'Cod_Agl' => 'Código de aglutinação das contas, atribuído pelo empresário ou sociedade empresária.',
	"Sld_Ini" => "Saldo Inicial"
	{$cabec_final},
	'Vl_Cta_DRE_J150' => 'Valor total do Código de aglutinação na Demonstração do Resultado do Exercício no período informado, no registro J150.',
	'Ind_Vl_J150' => "Indicador da situação do valor informado no campo anterior, no registro J150: 
D - Despesa ou valor que represente parcela redutora do lucro;
R - Receita ou valor que represente incremento do lucro;
P - Subtotal ou total positivo;
N – Subtotal ou total negativo."
);
EOD;
  eval('$'.$string_cabec);
  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(75);
';

  $pr->abre_excel_sql("DRE_Dinamico_Dia", "Saldos Diários agrupados conforme DRE, no seguinte Padrão: Receitas: Positivo; Despesas: Negativo", $sql, $col_format, $cabec, $form_final);


  

  
  $pr->finaliza_excel();
}

/*
SELECT CASE WHEN j100.descr_cod_agl ISNULL THEN '' ELSE j100.descr_cod_agl END || 
  CASE WHEN j150.descr_cod_agl ISNULL THEN '' ELSE j150.descr_cod_agl END, 
  sel1.*,
  CASE WHEN j100.descr_cod_agl ISNULL THEN '' ELSE 
        CASE WHEN j100.ind_grp_bal + 0 = 1 THEN
              CASE WHEN ind_dc_bal = "D" THEN j100.vl_cta ELSE -j100.vl_cta END
        ELSE
              CASE WHEN ind_dc_bal = "C" THEN j100.vl_cta ELSE -j100.vl_cta END
        END 
  END
  +
  CASE WHEN j150.descr_cod_agl ISNULL THEN '' ELSE 
        CASE WHEN j150.ind_vl IN ('R', 'P') THEN j150.vl_cta ELSE -j150.vl_cta END
  END	
  AS val_bal_dre
  FROM
      (SELECT contas.cod_agl AS cod_agl, 
         sum(saldia_transp.sld_ini),
         sum(saldia_transp.d2011_01_31),
         sum(saldia_transp.d2011_12_31)
         FROM contas, saldia_transp
         WHERE contas.cod_cta = saldia_transp.cod_cta AND cod_agl <> ''
         GROUP BY cod_agl) AS sel1
   LEFT OUTER JOIN j100 ON j100.cod_agl = sel1.cod_agl
   LEFT OUTER JOIN j150 ON j150.cod_agl = sel1.cod_agl;
*/

?>