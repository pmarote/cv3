<?php

function limpa_solucoes_0_anteriores($db) {
  $db->query("
	DELETE FROM contasndebmcred;
	DELETE FROM contas WHERE cod_cta LIKE 'NS________';
	DELETE FROM contas WHERE cod_cta LIKE 'NS__________';
");
}

// Retorna um array com padrao_r (Soundex), padrao_m (Metaphone) e padrao_nr (numero)
function padrao($db, $hist) {

	$r = array();
	// cálculo dos padrões
	$result = '';
	$r['padrao_nr'] = '';
	for($i=0; $i<strlen($hist); $i++) {
	  $asc = ord(strtoupper(substr($hist, $i, 1)));
	  if ($asc >= 65 && $asc <= 90) $result .= chr($asc); else $result .= ' ';
	  if ($asc >= 48 && $asc <= 57) $r['padrao_nr'] .= chr($asc);
	    else if (substr($r['padrao_nr'], -1) <> ' ') $r['padrao_nr'] .= ' ';
	}
	$palavras = explode(' ', $result);
	$result_final = '';
	foreach ($palavras as $indice => $valor)
	  if (strlen($valor) >= 2) $result_final .= $valor;
	if ($r['padrao_nr'] == '' ) $r['padrao_nr'] = 'Null';
	$r['padrao_s'] = $db->escapeString(soundex($result_final));
	$r['padrao_m'] = $db->escapeString(metaphone($result_final));
	return $r;
}

function grava_lancto($db, $alancto) {
  // $alancto é um array com n elementos, n >= 2, que corresponde ao lançamento
  // $alancto tem também mais dois elementos: $alancto['nro_deb'] e $alancto['nro_cred'] que contém o número de débitos
  // e créditos do lançamento  
  // o lançamento pode ter 1 crédito - 1 débito, n créditos - 1 débito, 1 débito - n créditos, n débitos - n créditos
  // cada elemento de $alancto é outro array com os dados de diario, exceto o campo (ord200), ou seja,
  // num_lcto, dt_lcto, ind_lcto, cod_cta, vl_dc  real, ind_dc  TEXT(1), hist, param_s, param_m, param_nr, obs, ord
  // a tabela lancto é a tabela diário aberta em 1 crédito - 1 débito. Podemos chamar lancto de diário analítico. Os campos lancto são:
  // num_lcto, dt_lcto, ind_lcto, cod_cta_d, cod_cta_c, valor  real, hist, padrao_s, padrao_m, padrao_nr, obs, ord_d, ord_c
  // O campo ord_d e ord_c é o número do registro na tabela I250. É importante para poder refazer soluções, reconstruindo lançamentos a partir do diário

  // Observação: antes, solução 1 era automática... agora é manual, no menu Roteiro Contábil - Identificação de Contrapartidas da 4ª Fórmula (N Débitos x M Créditos)
  // Solução 1: "Agrupando Número, Data, Conta, Ind_DC e Históricos Iguais"
  // solucao1($db, $alancto);

  // Solução 0 -> Situação de "Não Solução": N Débitos para M Créditos: 
  // "Não Solução": São gerados N + M lançamentos, com contrapartidas em Contas de Compensação
  // "Não Solução" parte 1: Criar Contas de Compensação cujo código é a quantidade de N Débitos e M Créditos
  // "Não Solução" parte 2: Gerar N + M lancamentos com as contrapartidas nas Contas de Compensação criadas
  if ($alancto['nro_deb'] > 1 &&  $alancto['nro_cred'] > 1) solucao0($db, $alancto);

  // Situação Padrão: 1 débito para 1 crédito, 1 débito para n créditos ou n débitos para 1 crédito
  if ($alancto['nro_deb'] == 1 || $alancto['nro_cred'] == 1) {
    grava_lancto_1_n($db, $alancto, $alancto['nro_deb'], $alancto['nro_cred']);
  }
}

function grava_lancto_1_n($db, $alancto, $nro_deb, $nro_cred) {
  // Esta função serve apenas para os seguintes casos:
  // 1 crédito para 1 débito, 1 crédito para n débitos ou n créditos para 1 débito
  if ($nro_deb == 1) { 
	// Primeiro caso... apenas 1 débito... primeiro pega o código da conta -> $cod_cta_d e o histórico -> $historico_deb
	$obs = '';
	$ord = '';
	foreach($alancto  as $indice => $valor) {
	  if (isset($valor[5]) && $valor[5] == 'D') {
		$cod_cta_d = $valor[3];
		$historico_deb = $valor[6];
		$obs = $valor[10];
		$ord = $valor[11];
	  }
	}
	// Agora faz todos os lançamentos, pelos créditos
	foreach($alancto  as $indice => $valor) {
	  if (is_array($valor)) {
		if ($valor[5] == 'C') {
		  $historico_cred = $valor[6]; // criei $historico_cred e $historico_deb para controlar o caso, fora do padrão, de histórico de débito diferente de histórico de crédito
		  if ($historico_cred != $historico_deb) {
			$historico_cred .= ' - ' . $historico_deb;
			$padroes = padrao($db, $historico_cred);
			$valor[7] = $padroes['padrao_s'];
			$valor[8] = $padroes['padrao_m'];
			$valor[9] = $padroes['padrao_nr'];
		  }
		  if ($obs <> '' && $valor[10] <> '') $obsfinal = $obs . ' ' . $valor[10];
		    else $obsfinal = $obs . $valor[10];
		  $ord .= ($ord <> '' ? ', ' : '') . $valor[11];
		  
		  $db->query("INSERT INTO lancto VALUES(
'{$valor[0]}', {$alancto['nro_deb']}, {$alancto['nro_cred']}, '{$valor[1]}', '{$valor[2]}', '{$cod_cta_d}', '{$valor[3]}', 
'{$valor[4]}', '{$historico_cred}', '{$valor[7]}', '{$valor[8]}', '{$valor[9]}', '{$obsfinal}', '{$valor[11]}' )
");
		}
	  }
	}
  } else {
	// Segundo caso... apenas 1 crédito... primeiro pega o código da conta -> $cod_cta_c e o histórico -> $historico_cred
	$obs = '';
	foreach($alancto  as $indice => $valor) {
	  if (isset($valor[5]) && $valor[5] == 'C') {
		$cod_cta_c = $valor[3];
		$historico_cred = $valor[6];
		$obs = $valor[10];
		$ord = $valor[11];
	  }
	}
	// Agora faz todos os lançamentos, pelos débitos
	foreach($alancto  as $indice => $valor) {
	  if (is_array($valor)) {
		if ($valor[5] == 'D') {
		  $historico_deb = $valor[6];
		  if ($historico_deb != $historico_cred) {
			$historico_deb .= ' - ' . $historico_cred;
			$padroes = padrao($db, $historico_deb);
			$valor[7] = $padroes['padrao_s'];
			$valor[8] = $padroes['padrao_m'];
			$valor[9] = $padroes['padrao_nr'];
		  }
		  if ($obs <> '' && $valor[10] <> '') $obsfinal = $obs . ' ' . $valor[10];
		    else $obsfinal = $obs . $valor[10];
		  $ord .= ($ord <> '' ? ', ' : '') . $valor[11];

		$db->query("INSERT INTO lancto VALUES(
'{$valor[0]}', {$alancto['nro_deb']}, {$alancto['nro_cred']}, '{$valor[1]}', '{$valor[2]}', '{$valor[3]}', '{$cod_cta_c}', 
'{$valor[4]}', '{$historico_deb}', '{$valor[7]}', '{$valor[8]}', '{$valor[9]}', '{$obsfinal}', '{$valor[11]}' )
");
		}
	  }
	}
  }
}

function solucao0($db, &$alancto) {
  // Situação de "Não Solução": N Débitos para M Créditos: 
  // "Não Solução": São gerados N + M lançamentos, com contrapartidas em Contas de Compensação
  // "Não Solução" parte 1: Criar Contas de Compensação cujo código é a quantidade de N Débitos e M Créditos
  // "Não Solução" parte 2: Gerar N + M lancamentos com as contrapartidas nas Contas de Compensação criadas
  if ($alancto['nro_deb'] > 1 &&  $alancto['nro_cred'] > 1) {
    if ($alancto['nro_deb'] < 10000) $nro_deb_aux = $alancto['nro_deb'];  else $nro_deb_aux = 9999;
    if ($alancto['nro_cred'] < 10000) $nro_cred_aux = $alancto['nro_cred'];  else $nro_cred_aux = 9999;
	$cod_cta_comp = 'NS' . substr('000' . $nro_deb_aux, -4) . substr('000' . $nro_cred_aux, -4);
    $desc_cta_comp = "Conta de CompensaÃ§Ã£o para 'NÃ£o SoluÃ§Ã£o' de Casos de LanÃ§amentos com ";
	$desc_cta_comp .= $nro_deb_aux == 9999 ? "9999 ou mais DÃ©bitos para " : "{$nro_deb_aux} DÃ©bitos para ";
	$desc_cta_comp .= $nro_cred_aux == 9999 ? "9999 ou mais CrÃ©ditos" : "{$nro_cred_aux} CrÃ©ditos";
	foreach($alancto  as $indice => $valor) {
	  if (is_array($valor)) {
		if ($valor[5] == 'C') {
		  $cod_cta_d = $cod_cta_comp;
		  $cod_cta_c = $valor[3];
		} else {
		  $cod_cta_d = $valor[3];
		  $cod_cta_c = $cod_cta_comp;
		}
		$obs = ($valor[10] = '' ? '' : ($valor[10] . ' ') ). "#SoluÃ§Ã£o 0: (NÃ£o SoluÃ§Ã£o) Gerados N + M lanÃ§amentos, com contrapartidas em Contas de CompensaÃ§Ã£o";
		$db->query("INSERT INTO lancto VALUES(
'{$valor[0]}', {$alancto['nro_deb']}, {$alancto['nro_cred']}, '{$valor[1]}', '{$valor[2]}', '{$cod_cta_d}', '{$cod_cta_c}', 
'{$valor[4]}', '{$valor[6]}', '{$valor[7]}', '{$valor[8]}', '{$valor[9]}', '{$obs}', '{$valor[11]}' )
");
	  }
	}
	$cod_cta_comp = $db->escapeString($cod_cta_comp);
	$desc_cta_comp = $db->escapeString($desc_cta_comp);
	$db->query("INSERT INTO contasNdebMCred VALUES(
'{$cod_cta_comp}', '{$desc_cta_comp}' )");
  }
}

function solucao1($db, &$alancto, $ind_dc = False) {
  // Solução 1: "Agrupando Número, Data, Conta, Ind_DC e Históricos Iguais"
  // Para controle, é descrito no final do histórico a solução executada
  // Por que tem acontecido isso na prática ?	
  //	Porque internamente as empresas trabalham com mais níveis de contabilidade, ou com centros de custos
  //	Quando não se analisa os centros de custos ou quando níveis são excluídos, aparecem lançamentos iguais
  //
  // DEBUG - No sqlite, SELECT * FROM lancto WHERE num_lcto IN (select num_lcto FROM lancto WHERE obs LIKE '%#Solu%');

  // Para o cálculo do Valor total do Diário diminuído em virtude da Solução 1, ao agregar débitos e créditos no mesmo lançamento
  $avtdmsl1 = array('TotDeb' => 0, 'TotCred' => 0, 'TotDebFin' => 0, 'TotCredFin' => 0);

  $padrao_lancto = array(); // padrão para agrupamento, com 6 campos: num_lcto, dt_lcto, ind_lcto, cod_cta, ind_dc, hist
  if ($alancto['nro_deb'] > 1 || $alancto['nro_cred'] > 1) {
	foreach($alancto  as $indice => $valor) {
	  if (is_array($valor)) {
		if ($valor[5] == 'D') $avtdmsl1['TotDeb'] += $valor[4]; else $avtdmsl1['TotCred'] += $valor[4];
		if ($ind_dc) $padrao = "{$valor[0]}##{$valor[1]}##{$valor[2]}##{$valor[3]}##{$valor[5]}##{$valor[6]}";
			else     $padrao = "{$valor[0]}##{$valor[1]}##{$valor[2]}##{$valor[3]}##{$valor[6]}";
		if (! isset($padrao_lancto[$padrao])) {
		  $padrao_lancto[$padrao] = $indice;
		} else {
		  // Já existe o padrão ! Então soma o total no $alancto anterior, arruma histórico, ['nro_deb'] ou ['nro_cred'] e deleta o item no $alancto
		  // Cuidado abaixo... Os dados estão com . decimal; PHP soma com . decimal mas mostra o resultado com , ...e sqlite quer . decimal
		  $valor_anterior  = $alancto[$padrao_lancto[$padrao]][4];
		  $ind_dc_anterior = $alancto[$padrao_lancto[$padrao]][5];
		  $valor_atual  = $valor[4];
		  $ind_dc_atual = $valor[5];
		  $ord_atual = $valor[11];
		  if ($ind_dc) {
			$alancto[$padrao_lancto[$padrao]][4] = str_replace(',', '.', $valor_anterior + $valor_atual );
			// A explicação abaixo na observação ( obs ) é importante para entender o que aconteceu...
			// Não tem ? explica e coloca o primeiro valor agrupado... Tem ? Coloca o segundo, terceiro, etc... valor agrupado
			if ( strpos($alancto[$padrao_lancto[$padrao]][10], "#SoluÃ§Ã£o 1") === False) {
			  $alancto[$padrao_lancto[$padrao]][10] .= "#SoluÃ§Ã£o 1: Agrupando 2 " . ($valor[5] == 'D' ? "DÃ©bitos" : "CrÃ©ditos") . " Iguais ";
			  $alancto[$padrao_lancto[$padrao]][10] .= "com valores " . number_format($valor_anterior, 2, ',', '.');
			  $alancto[$padrao_lancto[$padrao]][10] .= ", " . number_format($valor_atual, 2, ',', '.');
			  // $db->exec("INSERT INTO regsol VALUES ( '{$alancto[$padrao_lancto[$padrao]][11]}', 1, '{$valor[0]}', 'Agregado {$valor[11]}' );");
			} else {
			  $posagr = strpos($alancto[$padrao_lancto[$padrao]][10], 'Agrupando');
			  $quanti = substr($alancto[$padrao_lancto[$padrao]][10], $posagr + 10, 1);
			  for ($iqt = 1; $iqt <= 3; $iqt++) { // aceita até 9999
				if (substr($alancto[$padrao_lancto[$padrao]][10], $posagr + 10 + $iqt, 1) <> ' ') 
				  $quanti .= substr($alancto[$padrao_lancto[$padrao]][10], $posagr + 10 + $iqt, 1);
				else break;
			  }
			  $alancto[$padrao_lancto[$padrao]][10] = 
				str_replace('Agrupando ' . $quanti, 'Agrupando ' . ($quanti + 1), $alancto[$padrao_lancto[$padrao]][10]);
			  $alancto[$padrao_lancto[$padrao]][10] .= ", " . number_format($valor_atual, 2, ',', '.');
			  $alancto[$padrao_lancto[$padrao]][11] .= ", {$ord_atual}";
			  //debug_log("posagr={$posagr}#quanti={$quanti}#lancto10={$alancto[$padrao_lancto[$padrao]][10]}\n");
			}
		  } else {
			$valor_calc = ($ind_dc_anterior == 'D' ? -1 : 1) * $valor_anterior + ($ind_dc_atual == 'D' ? -1 : 1) * $valor_atual;
			$alancto[$padrao_lancto[$padrao]][4] = str_replace(',', '.', abs($valor_calc) );
			if ($valor_calc <= 0) $alancto[$padrao_lancto[$padrao]][5] = 'D'; else $alancto[$padrao_lancto[$padrao]][5] = 'C';
			// A explicação abaixo na observação ( obs ) é importante para entender o que aconteceu...
			// Não tem ? explica e coloca o primeiro valor agrupado... Tem ? Coloca o segundo, terceiro, etc... valor agrupado
			if ( strpos($alancto[$padrao_lancto[$padrao]][10], "#SoluÃ§Ã£o 1") === False) {
			  $alancto[$padrao_lancto[$padrao]][10] .= "#SoluÃ§Ã£o 1: Agrupando 2 Partidas Iguais ";
			  $alancto[$padrao_lancto[$padrao]][10] .= "com valores " . number_format($valor_anterior, 2, ',', '.') . " ({$ind_dc_anterior})";
			  $alancto[$padrao_lancto[$padrao]][10] .= ", " . number_format($valor_atual, 2, ',', '.') . " ({$ind_dc_atual})";
			  // $db->exec("INSERT INTO regsol VALUES ( '{$alancto[$padrao_lancto[$padrao]][11]}', 1, '{$valor[0]}', 'Agregado {$valor[11]}' );");
			} else {
			  $posagr = strpos($alancto[$padrao_lancto[$padrao]][10], 'Agrupando');
			  $quanti = substr($alancto[$padrao_lancto[$padrao]][10], $posagr + 10, 1);
			  for ($iqt = 1; $iqt <= 3; $iqt++) { // aceita até 9999
				if (substr($alancto[$padrao_lancto[$padrao]][10], $posagr + 10 + $iqt, 1) <> ' ') 
				  $quanti .= substr($alancto[$padrao_lancto[$padrao]][10], $posagr + 10 + $iqt, 1);
				else break;
			  }
			  $alancto[$padrao_lancto[$padrao]][10] = 
				str_replace('Agrupando ' . $quanti, 'Agrupando ' . ($quanti + 1), $alancto[$padrao_lancto[$padrao]][10]);
			  $alancto[$padrao_lancto[$padrao]][10] .= ", " . number_format($valor_atual, 2, ',', '.') . " ({$ind_dc_atual})";
			  $alancto[$padrao_lancto[$padrao]][11] .= ", {$ord_atual}";
			  //debug_log("posagr={$posagr}#quanti={$quanti}#lancto10={$alancto[$padrao_lancto[$padrao]][10]}\n");
			}
		  }
		  // antes de apagar o item de $alancto, registra a solução
		  $db->exec("INSERT INTO regsol VALUES ( '{$valor[11]}', 1, '{$valor[0]}', 'Deletado' );");
		  unset($alancto[$indice]);
		}
	  }
	}
	unset($alancto['nro_deb']);
	unset($alancto['nro_cred']);
	$alancto = array_values($alancto);// dois ou mais $alancto foram excluídos (unset)... precisamos agora reindexar o array
	$alancto['nro_deb'] = 0;
	$alancto['nro_cred'] = 0;
	foreach($alancto  as $indice => $valor) {
	  if (is_array($valor)) {
		if ($valor[5] == 'D') $alancto['nro_deb']++ ; else $alancto['nro_cred']++ ;
		$db->exec("INSERT INTO regsol VALUES ( '{$valor[11]}', 1, '{$valor[0]}', '{$valor[10]}' );");
		if ($valor[5] == 'D') $avtdmsl1['TotDebFin'] += $valor[4]; else $avtdmsl1['TotCredFin'] += $valor[4];
	  }
	}
  }
  $db->exec("INSERT INTO sol1 VALUES ( '{$valor[0]}', " . str_replace(',', '.', $avtdmsl1['TotDeb']) . ", " . str_replace(',', '.', $avtdmsl1['TotCred']) . ", " . str_replace(',', '.', $avtdmsl1['TotDebFin']) . ", " . str_replace(',', '.', $avtdmsl1['TotCredFin']) . ");");
}


function solucao2($db, &$alancto) {
	// Solução 2 -> Verifica sequencias de 1 x N ou N x 1 e, localizados val débito = val crédito, gera novo alancto
	//				em seguida, apaga os itens do novo alancto e começa de novo
	// Exemplo:
	//		A						B					C
	// 1-	15.542,63	C	1-	15.542,63	C	1-	15.542,63	C
	// 2-	 2.797,67	C	2-	   100,00	C	2-	 1.181,24	C
	// 3-	 2.797,67	D	3-	   156,45	C	3-	 1.081,24	D
	// 4-	   100,00	C	4-	   256,45	D	4-	   100,00	D
	// 5-	   156,45	C	5-	 1.181,24	C	5-	15.542,63	D
	// 6-	   256,45	D	6-	 1.081,24	D
	// 7-	 1.181,24	C	7-	   100,00	D
	// 8-	 1.081,24	D	8-	15.542,63	D
	// 9-	   100,00	D
	// 10-	15.542,63	D
	// Coluna A =>  1 2 3 - Não.... 3 - 1 > 1 então... 2 3 - Sim e apaga 2 3 	4 vira 2, 5 vira 3 e assim por diante
	// Coluna B =>  1 2 3 4 - Não.... 4 - 1 > 1 então... 2 3 4 - Sim e apaga 2 3 4 	5 vira 2, 6 vira 3 e assim por diante
	// Coluna C =>  1 2 3 (para aqui porque 2 C e 1 D) - Não.... 2 3 4 - Sim e apaga 2 3 4
	// Coluna D =>  1 2
	// Resumindo...
	// inicia em zero...
	// para quando (acabou o lancamento) ou (mais de 1 [C ou D] e encontrei um [D ou C])  ou (1 [C ou D] fui até o final de [D ou C]) ou
	//				soma D + C = zero -> neste caso, achei, gero um novo alancto e apago os lancamentos usados e volta @inicio para zero
	// quando para sem soma D + C = zero, reinicia, a partir de $inicio + 1, até $inicio = $nro de itens - 1
	$num_lcto_seq = 1;

	// A solução 2 vai ser tentada de várias formas... A primeira, na ordenação que veio
	solucao2_ord($db, $alancto, $num_lcto_seq);
	
	for ($i_tentat = 1; $i_tentat <= 3; $i_tentat++) {
	  // debug_log("i_tentat={$i_tentat}#" . print_r($alancto, True), 10000);
	  if (count($alancto) > 2) {
		// Se não conseguiu refazer todos os lançamentos, vai tentando de novo, alterando a ordenação
		wecho_rcpnm("*");
		$nro_deb_back = $alancto['nro_deb'];
		$nro_cred_back = $alancto['nro_cred'];
		unset($alancto['nro_deb']);
		unset($alancto['nro_cred']);
		if ($i_tentat == 1) usort($alancto, solucao2_build_sorter('ord'));
		if ($i_tentat == 2) usort($alancto, solucao2_build_sorter('padrao_nr'));
		if ($i_tentat == 3) usort($alancto, solucao2_build_sorter('hist'));
		// if ($alancto[0][0] == '20100107008820001') debug_log("i_tentat_apos_usort={$i_tentat}#" . print_r($alancto, True), 10000);
		$alancto['nro_deb'] = $nro_deb_back;
		$alancto['nro_cred'] = $nro_cred_back;
		$db->exec('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
		solucao2_ord($db, $alancto, $num_lcto_seq);
		$db->exec('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
	  }
	}
}

function solucao2_build_sorter($key) {
  return function ($a, $b) use ($key) {
	if ($key == 'ord')			return strnatcmp($a[11], $b[11]);
	if ($key == 'padrao_nr')	return strnatcmp($a[9] . $a[6] . $a[5], $b[9] . $b[6] . $b[5]);
	if ($key == 'hist')			return strnatcmp($a[6] . $a[5], $b[6] . $b[5]);
  };
}

function solucao2_ord($db, &$alancto, &$num_lcto_seq) {
	$inicio = 0;
	$i = $inicio;
	$fim = False;
	while (! $fim) {
	  //if ($alancto[0][0] == '20100107008820001') 
	  //  debug_log("\nInicio-{$inicio}#I-{$i}#nro_deb=#{$alancto['nro_deb']}#nro_cred=#{$alancto['nro_cred']}#", 10000);
	  $seq_fim = sol2_achaseq($alancto, $i);
	  if ($seq_fim !== False) {
	    if (sol2_proclcto($db, $alancto, $i, $seq_fim, $num_lcto_seq)) {		// função $alancto por referencia
		  $inicio = 0;		// achou sequencia ? começa tudo de novo, do zero
		  $i = 0;
		} else {
		  $i++;
		  if ($i >= (count($alancto) - 3)) {
			$inicio++;
			$i = $inicio;
		  }
		}
	  } else {
		$inicio++;
		$i = $inicio;
	  }
	  if ($inicio >= (count($alancto) - 3)) $fim = True;
	}
}

function sol2_achaseq(&$alancto, $i) {
	// retorna o fim da sequencia 1 x N ou N x 1 ou False se não há fim de sequencia
	// exemplos, tendo como primeiro caso, $i = 0
	// DDCC -> 2   DDD -> Falso  D -> Falso DCCCC -> 4  DCCCD -> 3  e vice versa
	if ($alancto[$i][5] == 'D') {
	  if (!isset($alancto[$i+1][5])) return False;
	  if ($alancto[$i+1][5] == 'D') {	// N x 1 -> procura o primeiro C
	    for ($j = 2; ($i + $j) <= (count($alancto) - 3); $j++) { if ($alancto[$i+$j][5] == 'C') return ($i+$j); }
		return False;
	  } else {	// 1 X N -> procura o último C, ou, um a menos que o próximo D ou o fim
		if (!isset($alancto[$i+2][5])) return ($i+1);
	    for ($j = 2; ($i + $j) <= (count($alancto) - 3); $j++) { if ($alancto[$i+$j][5] == 'D') return ($i+$j-1); }
		return (count($alancto) - 3);
	  }
	} else {	// agora faz o inverso da parte de cima
	  if (!isset($alancto[$i+1][5])) return False;
	  if ($alancto[$i+1][5] == 'C') {	// N x 1 -> procura o primeiro D
	    for ($j = 2; ($i + $j) <= (count($alancto) - 3); $j++) { if ($alancto[$i+$j][5] == 'D') return ($i+$j); }
		return False;
	  } else {	// 1 X N -> procura o último D, ou, um a menos que o próximo C ou o fim
		if (!isset($alancto[$i+2][5])) return ($i+1);
	    for ($j = 2; ($i + $j) <= (count($alancto) - 3); $j++) { if ($alancto[$i+$j][5] == 'C') return ($i+$j-1); }
		return (count($alancto) - 3);
	  }
	}
}

function sol2_proclcto(&$db, &$alancto, $seq_ini, $seq_fim, &$num_lcto_seq) {
	// procura um lançamento (valor débitos = valor em créditos) dentro de $seq_ini e $seq_fim
	// achou ? gera um novo lançamento e retira de alancto as partidas localizadas
	$valor = 0;
	for ($i = $seq_ini; $i <= $seq_fim; $i++) {
	  $valor = ( $alancto[$i][5] == 'D' ? ($valor - $alancto[$i][4]) : ($valor + $alancto[$i][4]) );
	  if (abs($valor) < 0.01) {
		// achei ! de $seq_ini até $i... tomar as providências e sair...

		// Achou ? Constrói outro $alancto ($alancto_seq) com o número de lançamento appended '-1' , '-2', assim por diante ( $num_lcto_seq )
		$query_sol2 = '';
		$alancto_seq = array('nro_deb' => 0, 'nro_cred' => 0);
		for ($j = $seq_ini; $j <= $i; $j++) {
		  $alancto_seq[$j - $seq_ini] = $alancto[$j];
		  $alancto_seq[$j - $seq_ini][0] = $alancto[$j][0] . "_sl2_" . substr('000' . $num_lcto_seq, -4);
		  $alancto_seq[$j - $seq_ini][10] = $alancto[$j][10] . "#SoluÃ§Ã£o 2: LanÃ§amentos 1 x 1, 1 x N, N x 1 ou N x N consecutivos, cadastrados com o mesmo nÃºmero";
		  if ($alancto[$j][5] == 'D') $alancto_seq['nro_deb']++; else $alancto_seq['nro_cred']++; 
		  $db->exec("INSERT INTO regsol VALUES ( '{$alancto[$j][11]}', 2, '{$alancto[$j][0]}', '{$alancto[$j][0]}_sl2_" . 
				substr('000' . $num_lcto_seq, -4). "#{$j}' );");
		  if ($alancto[$j][5] == 'D') $alancto['nro_deb']--; else $alancto['nro_cred']--; 
		  unset($alancto[$j]);
		}
		grava_lancto($db, $alancto_seq);	// Em Conv_ECD_Solucoes.php
		$num_lcto_seq++;		// para pular para o próximo número de lançamento solucao
		$nro_deb_back = $alancto['nro_deb'];
		$nro_cred_back = $alancto['nro_cred'];
		unset($alancto['nro_deb']);
		unset($alancto['nro_cred']);
		$alancto = array_values($alancto);// dois ou mais $alancto foram excluídos (unset)... precisamos agora reindexar o array
		$alancto['nro_deb'] = $nro_deb_back;
		$alancto['nro_cred'] = $nro_cred_back;
		return True;
	  }
	}
	return False;
}


function solucao3($db, &$alancto) {
	$num_lcto_seq = 1;
	return;
	// Em implementação, mas, com o aperfeiçoamento da solução 2 com suas várias ordenações, talvez a solução 3 fique muito restrita
	// as rotinas básicas, teóricas, são as abaixo
	// agradecimentos ao colega Walter Bentivegna, que forneceu o código fonte
	echo date('H:i:s', time());
	$maxSoln = 0;
	$targetVal = 102029.39;
	//$inArr = array(7650, 4000, 41032.8, 241.9, 1402.75, 15600, 3168, 2400, 45369.59, 70);
	$inArr = array(41032.8, 19963.79, 60996.59, 41032.8);
	$rslt = array();
	$haveRandomNegatives = False;
	recursiveMatch($maxSoln, $targetVal, $inArr, $haveRandomNegatives, 0, 0, 0.001, Null, "", ", ");
	print_r($rslt);
	echo date('H:i:s', time());
}

// realEqual simplesmente substitui = .... é porque, em números reais, as vezes 9.9 - 9.9 pode dar 1,52E-12 ao invés de zero
function realEqual($a, $b, $epsilon = 0) {
  return (abs($a - $b) <= $epsilon);	// Retorna True ou False
}

// Retorna um número $newVal... se $currRslt <> '', retorna, por exemplo, '2, 3' ($currRslt = 2; $newVal = 3)
function extendRslt($currRslt, $newVal, $separator) {
  if ($currRslt === '') return $newVal;
  else return ($currRslt . $separator . $newVal);
}

// $maxSoln = 0 ? Infinitas tentativas
// $haveRandomNegatives vai ser sempre falso
// $rslt é um array que contém strings de uma ou mais soluções encontradas... exemplo: $rslt[0] = '3, 6, 9';  $rsl[1] = '7, 8, 10';
// $rslt2 não usa aqui porque tem a linha global $rslt -> alterar para &$rslt
// Esta função não faz comparativos 1 x 1, apenas 1 x N
//		Fonte: http://www.tushar-mehta.com/excel/templates/match_values/index.html#VBA_multiple_combinations
// Esta versão em PHP NÃO TRABALHA com números negativos - a original (acima), sim
// Esta versão tem uma otimização que diminui bastante o número de iterações - !($currTotal + $inArr[$i] > $targetVal + $epsilon)
// COLOQUE OS DADOS $inArr EM ORDER *DECRESCENTE* - assim, a otimização vai diminuir ainda mais as iterações
// Não utilize vírgula como separator - senão não vai conseguir separar depois os resultados
function recursiveMatch($maxSoln, $targetVal, $inArr, $haveRandomNegatives, $currIdx, $currTotal, $epsilon, $rslt2, $currRslt, $separator) {
  global $rslt;
  for ($i = $currIdx; $i <= (count($inArr) - 1); $i++) {
    echo "\n#currIdx={$currIdx}#currTotal={$currTotal}#inArr[i]={$inArr[$i]}#currRslt={$currRslt}#i={$i}";
    if (realEqual($currTotal + $inArr[$i], $targetVal, $epsilon)) {  // Achei !
	  //$rslt[count($rslt)] = ($currTotal + $inArr[$i]) . $separator . date('H:i:s', time()) . $separator . extendRslt($currRslt, $i, $separator);
	  $rslt[] = ($currTotal + $inArr[$i]) . $separator . date('H:i:s', time()) . $separator . extendRslt($currRslt, $i, $separator);
	  echo "achei..." . $rslt[count($rslt) - 1];
	  if ($maxSoln == 0) {
	    // prossegue até achar todas as soluções
		// if (count($rslt) % 100 == 0) echo "Rslt(" . count($rslt) . ")=" . $rslt[count($rslt)];  // Debug
	  } else {
	    if (count($rslt) >= $maxSoln) return;	// Atingiu o número máximo de soluções ? Sai fora
	  }
	} else {
	  if (!($currTotal + $inArr[$i] > $targetVal + $epsilon) && ($currIdx < (count($inArr) - 1))) {		// A primeira parte é a otimização
	    recursiveMatch($maxSoln, $targetVal, $inArr, $haveRandomNegatives, $i+1, $currTotal + $inArr[$i], 
			$epsilon, Null, extendRslt($currRslt, $i, $separator), $separator);
		if ($maxSoln <> 0 && count($rslt) >= $maxSoln) return;
	  } else {
	    // Verificamos a combinação e não achamos solução...
	  }
	}
  }
}


function solucao4($db, &$alancto) {
	// Situação de débitos >= 2 e créditos >= 2
	// Fazer novos arrays e chamar grava_lancto_1_n
	$num_lcto_seq = 1;

	if($alancto['nro_deb'] <= $alancto['nro_cred']) $caso = 1; else $caso = 2;

	// aqui o objetivo é reduzir para:
	// (CASO 1) se nro_deb <= nro_cred -> várias arrays, com $nro_deb = 1 e valor de cada crédito proporcional
	// ao que o débito representa sobre o total
	// (CASO 2) se nro_cred <= nro_deb -> várias arrays, com $nro_cred = 1 e valor de cada débito proporcional
	// ao que o crédito representa sobre o total

	$valor_total = 0; // total dos débitos (CASO 1) ou total dos créditos (CASO 2)

	// aqui vai conter vários ($alancto['nro_deb'])  arrays com $nro_deb  = 1 em cada(CASO 1)
	// aqui vai conter vários ($alancto['nro_cred']) arrays com $nro_cred = 1 em cada(CASO 2)
	// - primeiro preenche, depois acerta os valores proporcionais e ajusta a soma
	$alanctoaux = array();
	$ajus_cent = array(); // ver explicações no trecho Ajuste de Centavos pt 1, mais abaixo
	for ($i=0; $i<  ($caso == 1 ? $alancto['nro_deb'] : $alancto['nro_cred']); $i++) {
	  // o primeiro array de $alanctoaux[$i] é o único, o de débito (CASO 1) ou o de crédito (CASO 2) (VER **OBS1** abaixo)
	  $icontador = 0;
	  foreach($alancto  as $indice => $valor) {
		if (isset($valor[5]) && $valor[5] == ($caso == 1 ? 'D' : 'C')) {
		  if ($i == $icontador) {
			$alanctoaux[$i]['nro_deb']  = $alancto['nro_deb'];
			$alanctoaux[$i]['nro_cred'] = $alancto['nro_cred'];
			$alanctoaux[$i][] = $valor;
			$valor_total += $valor[4];
		  }
		  $icontador++;
		}
	  }
	  // os próximos arrays de $alanctoaux[$i] são os de créditos (CASO 1) ou débitos (CASO 2)
	  foreach($alancto  as $indice => $valor) {
		if (isset($valor[5]) && $valor[5] == ($caso == 1 ? 'C' : 'D')) {
		  $alanctoaux[$i][] = $valor;
		  if ($i == 0) $ajus_cent[] = $valor[4];
		}
	  }
	}

	// quase pronto... preenchido $alanctoaux[n][], onde n é o número de débitos (CASO 1) ou créditos (CASO 2)
	// agora coloca os valores proporcionais nos créditos (CASO 1) ou débitos (CASO 2)
	// Proporção: 	O valor total está em $valor_total
	//				O débito (CASO 1) ou crédito (CASO 2) está em $alanctoaux[$i][0][4] (VER **OBS1** acima)
	// aproveita e arruma também o número do lançamento, mostrando que ocorreu uma solução
	for ($i=0; $i<  ($caso == 1 ? $alancto['nro_deb'] : $alancto['nro_cred']); $i++) {
	  // fazendo proporção nos créditos (CASO 1) ou débitos (CASO 2) - o ajuste de centavos está mais a frente
	  for ($j=1; $j <= ($caso == 1 ? $alancto['nro_cred'] : $alancto['nro_deb']); $j++) {
		$alanctoaux[$i][$j][4] = str_replace(',', '.', 
		  round($alanctoaux[$i][$j][4] * $alanctoaux[$i][0][4] / $valor_total, 2));
	  }
	}

	// Ajuste de Centavos - Pt 1

	// Primeiro faz pela soma dos créditos (CASO 1) ou débitos (CASO 2)
	// foi lido cada um dos créditos (CASO 1) ou débitos (CASO 2) totais, que vem de $alancto, em $ajus_cent[]
	// $ajus_cent[0], por exemplo, é o primeiro crédito total (CASO 1) ou débito total(CASO 2)
	// Compara cada um deles com os créditos (CASO 1) ou débitos (CASO 2) proporcionais, que estão em $alanctoaux
	// A diferença de centavos vai ser adicionada / subtraída no primeiro $alanctoaux, ou seja, 
	//		$alanctoaux[0][1] até $alanctoaux[0][$alancto['nro_cred']] (CASO1) ou $alanctoaux[0][$alancto['nro_deb']]
	for ($j=1; $j <= ($caso == 1 ? $alancto['nro_cred'] : $alancto['nro_deb']); $j++) {
	  $ajuste = $ajus_cent[$j-1];
	  for ($i=0; $i < ($caso == 1 ? $alancto['nro_deb'] : $alancto['nro_cred']); $i++) {
	    $ajuste -= $alanctoaux[$i][$j][4];
	  }
	  $alanctoaux[0][$j][4] = str_replace(',', '.', 
		round($alanctoaux[0][$j][4] + $ajuste, 2));
	}

	// Ajuste de Centavos - Pt 2

	// Agora soma cada um dos créditos (CASO 1) ou débitos (CASO 2) proporcionais e compara com o débito (CASO 1) ou crédito (CASO 2)
	// A diferença de centavos vai ser adic./subtraída no primeiro crédito (CASO 1) ou débito (CASO 2) de $alanctoaux, ou seja, 
	//		$alanctoaux[$i][1]
	for ($i=0; $i < ($caso == 1 ? $alancto['nro_deb'] : $alancto['nro_cred']); $i++) {
	  $ajuste = $alanctoaux[$i][0][4];
	  for ($j=1; $j <= ($caso == 1 ? $alancto['nro_cred'] : $alancto['nro_deb']); $j++) {
	    $ajuste -= $alanctoaux[$i][$j][4];
	  }
	  $alanctoaux[$i][1][4] = str_replace(',', '.', 
		round($alanctoaux[$i][1][4] + $ajuste, 2));
	//  $alanctoaux[$i][1][9] = str_replace(',', '.', 
	//	round($alanctoaux[$i][1][4] + $ajuste, 2)) . "#" . round($ajuste, 2) . "#"; // Debug
	}

	// Ajusta o número do lançamento _sl4_ nro...
	foreach($alanctoaux as $indice => $alancto2) {
	  foreach($alancto2 as $ind2 => $alancto3) {
		if (is_array($alancto3)) {
		  $alanctoaux[$indice][$ind2][0] = $alanctoaux[$indice][$ind2][0] . "_sl4_" . substr('000' . $num_lcto_seq, -4);
		  $alanctoaux[$indice][$ind2][10] .= '#SoluÃ§Ã£o 4: SoluÃ§Ã£o "Cartesiana" - Geradas ';
		  $alanctoaux[$indice][$ind2][10] .= $alanctoaux[$indice]['nro_deb'] * $alanctoaux[$indice]['nro_cred'] * 2;
		  $alanctoaux[$indice][$ind2][10] .= ' (N x M x 2) partidas, proporcionais aos ';
		  $alanctoaux[$indice][$ind2][10] .= $alanctoaux[$indice]['nro_deb'] . ' DÃ©bitos por ';
		  $alanctoaux[$indice][$ind2][10] .= $alanctoaux[$indice]['nro_cred'] . ' CrÃ©ditos originais';
		  $db->exec("INSERT INTO regsol VALUES ( '{$alanctoaux[$indice][$ind2][11]}', 4, '{$alanctoaux[$indice][$ind2][0]}', '{$alanctoaux[$indice][$ind2][0]}_sl4_" . substr('000' . $num_lcto_seq, -4) . "#{$ind2}' );");
		}
	  }
	  $num_lcto_seq++;
	}

	// agora $alanctoaux está pronto !
	if ($caso == 1) {
	  foreach($alanctoaux as $indice => $alancto2) {
	    $alancto2['nro_deb'] = 1;
		grava_lancto_1_n($db, $alancto2, 1, $alancto['nro_cred']);
	  }	
	} else {
	  foreach($alanctoaux as $indice => $alancto2) {
	    $alancto2['nro_cred'] = 1;
		grava_lancto_1_n($db, $alancto2, $alancto['nro_deb'], 1);
	  }
	}

	// Não usar unset($alancto) porque $alancto vem por referência 
	foreach($alancto as $indice => $valor) unset($alancto[$indice]);
	$alancto['nro_deb'] = 0;
	$alancto['nro_cred'] = 0;

	$num_lcto_seq++;
}


?> 