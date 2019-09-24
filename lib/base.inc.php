<?php

require __DIR__ . '/../cv3.config.php';

// início
set_time_limit(0); // Desabilita o limite padrao de 60 segundos para processamento

function versao() {
	return "3.1.5 - 09/2019";
// 3.1.5 - 09/2019 - Cada vez menores ajustes
// 3.1.4 - 06/2019 - Ajustes menores e início de juntada com novo cv4
// 3.1.3 - 05/2019 - Várias melhorias diversas, especialmente LASIMCA
// 3.1.2 - 04/2019 - Leitura de XML e ECD
// 3.1.1 - 03/2019 - Leitura de LASIMCA
// 3.1.0 - 03/2019 - Combinei com todos meus softwares em /pn, habilitando para GitHub
//                   db3 agora ficam em tmp... a parta db3 agora é perene, não se apaga
// 3.0.8 - 03/2019 - Importação de Arquivos .csv (ainda muito lento)
// 3.0.7 - 02/2019 - Importação de CAT-42
// 3.0.6 - 02/2019 - Mais aperfeiçoamentos e controle de Importação e Exportação (1100/1105/C120). Importação de E113 (Inf.Adicionais RAICMS - Identificação docs fiscais)
// 3.0.5 - 01/2019 - Vários pequenos aperfeiçoamentos e importação de .txt aperfeiçoada. Início do processamento de LADCA
// 3.0.4 - 12/2018 - Módulo espelhos de NFe funcional
// 3.0.3 - 12/2018 - Aperfeiçoamentos (especialmente em audit) e bugs corrigidos
// 3.0.2 - 12/2018 - Muitos bugs corrigidos, versão estabilizada, com modelo e conciliação, além de consulta web em produção
// 3.0.1 - 11/2018 - Reescrito do zero, com php-gtk e demais softwares atualizados, agora também tudo em UTF-8
}

// Error_Reporting baseado em http://www.php.net/manual/en/errorfunc.examples.php
// we will do our own error handling
// user defined error handling function
function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars) 
{
    // timestamp for the error entry
    $dt = date("Y-m-d H:i:s (T)");

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    // E_USER_WARNING and E_USER_NOTICE
    $errortype = array (
                E_ERROR              => 'Error',
                E_WARNING            => 'Warning',
                E_PARSE              => 'Parsing Error',
                E_NOTICE             => 'Notice',
                E_CORE_ERROR         => 'Core Error',
                E_CORE_WARNING       => 'Core Warning',
                E_COMPILE_ERROR      => 'Compile Error',
                E_COMPILE_WARNING    => 'Compile Warning',
                E_USER_ERROR         => 'User Error',
                E_USER_WARNING       => 'User Warning',
                E_USER_NOTICE        => 'User Notice',
                E_STRICT             => 'Runtime Notice',
                E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
                );
    // set of errors will be saved
    $list_errors = array(E_ERROR, E_PARSE, E_WARNING, E_COMPILE_ERROR, E_USER_ERROR, E_USER_WARNING);
    $warn_errors = array(E_WARNING, E_USER_WARNING);

    if (in_array($errno, $list_errors)) {
		$err = "<errorentry>";
		$err .= "\t<datetime>" . $dt . "</datetime>";
		$err .= "\t<errornum>" . $errno . "</errornum>";
		$err .= "\t<errortype>" . $errortype[$errno] . "</errortype>";
		$err .= "\t<errormsg>" . $errmsg . "</errormsg>";
		$err .= "\t<scriptname>" . $filename . "</scriptname>";
		$err .= "\t<scriptlinenum>" . $linenum . "</scriptlinenum>";
		$err .= "</errorentry>\r\n";
    
		// for testing
		//echo $err;

		// save to the error log
		if (in_array($errno, $warn_errors)) {
		  if($GLOBALS['number_of_errors']++ < 100) error_log($err, 3, $GLOBALS['warning_log_file']); // para evitar lentidão
		}  else {
		  if($GLOBALS['number_of_warnings']++ < 100) error_log($err, 3, $GLOBALS['error_log_file']); // para evitar lentidão
		}
    }
}

function clean_prError() {
  // Deixa em prError.log apenas as últimas 10 linhas
  if (file_exists(PR_LOG . '/prError.log') && (filesize(PR_LOG . '/prError.log') > 1000000)) {
	$tmp = file_get_contents(PR_LOG . '/prError.log');
	$linhas = explode("\n", $tmp);
	$linha_inicial = count($linhas) - 10;
	if ($linha_inicial < 0) $linha_inicial = 0;
	$tmpout = '';
	for ($i = $linha_inicial; $i < count($linhas); $i++) {
	  $tmpout .= $linhas[$i] . "\n";
	}
	unlink(PR_LOG . '/prError.log');
	file_put_contents(PR_LOG . '/prError.log', $tmpout);
  }
}


function error_warning_logs($error_log, $warning_log) {

  if (file_exists($error_log)) unlink($error_log);
  if (file_exists($warning_log)) unlink($warning_log);
  $GLOBALS['error_log_file'] = $error_log;
  $GLOBALS['warning_log_file'] = $warning_log;
  $GLOBALS['number_of_errors'] = 0;
  $GLOBALS['number_of_warnings'] = 0;

  $old_error_handler = set_error_handler("userErrorHandler");

}

// Delete arquivos recursivamente
function recursiveDelete($str){
    if(is_file($str)){
        return @unlink($str);
    }
    elseif(is_dir($str)){
        $scan = glob(rtrim($str,'/').'/*');
        foreach($scan as $index=>$path){
            recursiveDelete($path);
        }
        return @rmdir($str);
    }
}

function wecho($str) {

	// é um echo normal, mas jogando o resultado na janela de texto
	global $textBuffer, $scrolledwindow, $pr;

	// A linha abaixo pode diminuir um pouco o desempenho do sistema... testar com calma no futuro, com e sem
	// abaixo foi substituído \n por \r\n para os arquivos .log serem lidos corretamente no Notepad
	file_put_contents($pr->arq_log, str_replace("\n", "\r\n", $str), FILE_APPEND);

	set_time_limit(3600);	// aqui é o seguinte... o PHP estava estourando o limite de tempo em 1800 segundos, mas não deveria parar
						// nunca, porque PHP-CLI é infinito (zero). Coloquei um max_execution_time = 3600 (segundos) em php-cli.ini
						// e, conforme manual, set_time_limit recomeça o contador do zero... então, esta linha é uma tentativa de
						// não dar erro...


	// Liberar a linha abaixo é interessante quando está dando pau e a janela fechando - assim, é possível ler o que está em wecho na janela cmd
	//if ($pr->options['ldebug']) echo "<wecho>" . $str;

	// Agora há também os Threads, em modo texto. Ou seja... se não existir $textBuffer, gera um echo normal
	if (isset($textBuffer)) {
	  $textBuffer->place_cursor($textBuffer->get_end_iter()); 
	  $textBuffer->insert_at_cursor($str);
	  $scrolledwindow->viewer->scroll_to_iter($textBuffer->get_end_iter(),0); 
	  while (Gtk::events_pending()) {  // redraw de toda a janela
		Gtk::main_iteration();
	  }
	} else echo "<wecho>" . $str;
}

function werro($errors) {
  // gera uma janela de erro com o texto $errors
  // após werro, coloque exit; para sair fora do php
  global $wnd;
  $dialog = new GtkMessageDialog($wnd, Gtk::DIALOG_MODAL,
            Gtk::MESSAGE_ERROR, Gtk::BUTTONS_OK, $errors);
  $dialog->set_markup(
            "O seguinte erro ocorreu:\r\n"
            . "<span foreground='red'>" . $errors . "</span>"
        );
  $dialog->run();
  $dialog->destroy();
}

function werro_die($errors) {
  // gera uma janela de erro com o texto $errors
  // após werro, para sair fora do php com exit;
  global $wnd, $obfw;;
  werro($errors);
  //$obfw->end();
  exit;
}

function wpopup($mensagem) {
  // gera uma janela popup com o texto $mensagem
  global $wnd;
  $dialog = new GtkMessageDialog($wnd, Gtk::DIALOG_MODAL,
            Gtk::MESSAGE_INFO, Gtk::BUTTONS_OK, $mensagem);
  $dialog->set_markup($mensagem);
  $dialog->run();
  $dialog->destroy();
}

function debug_log($str, $limite = 100) {
	// debug_log está restrito, por default a 100 mensagens, para evitar lentidões
	static $contador = 0;
	if ($contador++ >= $limite) return;
	// abaixo foi substituído \n por \r\n para os arquivos .log serem lidos corretamente no Notepad
	// na primeira, apaga o arquivo caso exista
	if ($contador == 1) {
	  if (file_exists(PR_LOG . '/Debug.log')) unlink(PR_LOG . '/Debug.log');
	}
	file_put_contents(PR_LOG . '/Debug.log', str_replace("\n", "\r\n", $str), FILE_APPEND);
}

/*
compliance com SQL data types

INTEGER. The value is a signed integer, stored in 1, 2, 3, 4, 6, or 8 bytes depending on the magnitude of the value.
			Maximum is 2^63-1 = 9223372036854775807
REAL. The value is a floating point value, stored as an 8-byte IEEE floating point number.
TEXT. The value is a text string, stored using the database encoding (UTF-8, UTF-16BE or UTF-16LE).

*/


function qual_tipo_sqlite($valor) {
	// A ideia aqui é verificar se o valor, nos padrões brasileiros, pode ser inserido como INT ou REAL no Sqlite, senão usa TEXT mesmo
	// Valores retornados: T ,  R  ou  I
/*
99 é INT#" . qual_tipo_sqlite('99') . "#
99,99 é REAL#" . qual_tipo_sqlite('99,99') . "#
99999999999,99 é REAL#" . qual_tipo_sqlite('99999999999,99') . "#
99999999999,999.999 não é REAL#" . qual_tipo_sqlite('99999999999,999.999') . "#
99999999,999,99 não é REAL#" . qual_tipo_sqlite('99999999,999,99') . "#
99.999.999.999,99 é REAL#" . qual_tipo_sqlite('99.999.999.999,99') . "#
99999999999 é INT#" . qual_tipo_sqlite('99999999999') . "#
99.999.999.9999 não é INT#" . qual_tipo_sqlite('99.999.999.9999') . "#
99.9999.999.999 não é INT#" . qual_tipo_sqlite('99.9999.999.999') . "#
99.999.999.999 é INT#" . qual_tipo_sqlite('99.999.999.999') . "#
*/	
	$tipo = "T";
	$valor = trim($valor);
	// tirando todas as vírgulas e pontos, tem que ser numérico
	if (!is_numeric(str_replace(',','',str_replace('.','',$valor)))) return $tipo;
	// testa de novo... em alguns casos, como ', 1'  , php diz que é numérico. Ou seja... só pode ter números, ponto e vírgulas, mais nada
	if (str_replace('0','',str_replace('1','',str_replace('2','',str_replace('3','',str_replace('4','',str_replace('5','',str_replace('6','',str_replace('7','',str_replace('8','',str_replace('9','',str_replace(',','',str_replace('.','',$valor)))))))))))) <> '') return $tipo;
	// caso contrário, vai testando pra ver se pode ser REAL ou INT
	// Se houver vírgula, só pode ter uma vírgula!
	$pos_virgula = mb_strpos($valor, ',');
	if ($pos_virgula !== False) {
		// reconstrói o campo sem a vírgula encontrada
		$teste_campo = mb_substr($valor, 0, $pos_virgula) . mb_substr($valor, $pos_virgula - mb_strlen($campos[$i]) + 1);
			//debug_log("#t1#{$valor}#{$pos_virgula}#{$teste_campo}#\r\n");
		// se houver uma segunda vírgula, esquece...
		if (mb_strpos($teste_campo, ',') !== False) return $tipo;
	}
	// tá quase. Através de $pos_virgula já sabemos se não há vírgula, ou se há somente uma vírgula. Assim posso decidir se é INT ou REAL
	// agora é assim... se não houver pontos, pode sair... Se houver pontos (testar do fim pro começo), ver abaixo 
	$pos_ponto = mb_strrpos($valor, '.');
	if ($pos_ponto === False) {
		if ($pos_virgula !== False) {
			$tipo = "R";
			return $tipo;
		} else {
			$tipo = "I";
			return $tipo;
		}
	} else {
		// se houver pontos, não podem estar depois da vírgula
		if ($pos_virgula !== False && $pos_ponto > $pos_virgula) return $tipo;
		// beleza... já podemos trabalhar agora somente com o valor inteiro, descartando a vírgula e o valor fracionário
		if ($pos_virgula !== False) $valor_int = mb_substr($valor, 0, $pos_virgula);
		else $valor_int = $valor;
			//debug_log("#t2#{$valor}#{$pos_virgula}#{$valor_int}#\r\n");
		// nnn.nnn.nnn.nnn
		// 0123456789012345
		// se houver pontos, tem que testar se estão de 4 em 4 (milhares), do fim pro começo. Vamos lá
		$pos_ponto_ant = mb_strlen($valor_int);
		while (mb_strrpos($valor_int, '.') !== False) {
			  //debug_log("#t3#{$pos_virgula}#{$valor_int}#{$pos_ponto_ant}#" . mb_strrpos($valor_int, '.') . "#\r\n");
			if ($pos_ponto_ant - mb_strrpos($valor_int, '.') != 4) return $tipo;
			$pos_ponto_ant = $pos_ponto_ant - 4;
			$valor_int = mb_substr($valor, 0, $pos_ponto_ant);
			  //debug_log("#t4#{$valor_int}#{$pos_ponto_ant}#\r\n");
		}
		// ufa... acabou... os pontos estão corretamente colocados!
		if ($pos_virgula !== False) {
			$tipo = "R";
			return $tipo;
		} else {
			$tipo = "I";
			return $tipo;
		}
	}
}
	

// Atenção !!! Para compatibilidade, usar sempre o formato AAAA-MM-DD no SQLite
// E, para nossa sorte, o Excel, ao abrir AAAA-MM-DD, reconhece como data e converte automaticamente para DD/MM/AAAA
function dtap32($data) {
  // Transforma data no formato Portaria Cat 32 ( AAAAMMDD )  para AAAA-MM-DD
  return substr($data, 0, 4) . '-' . substr($data, 4, 2) . '-' . substr($data, 6, 2);
}

function dtaSPED($data) {
  // Transforma data no formato SPED ( DDMMAAAA )  para AAAA-MM-DD
  return substr($data, 4, 4) . '-' . substr($data, 2, 2) . '-' . substr($data, 0, 2);
}

//function dtaAAAAMMDD2Barra($data) {
//  // Transforma data no formato AAAAMMDD para AAAA-MM-DD
//  dtap32($data);
//}

function dtaBarra2AAAAMMDD($data) {
  // Transforma data no formato DD/MM/AAAA para AAAA-MM-DD
  // antes, existia a opção de sair a data AAAAMMDD. Agora, por padrão, é sempre AAAA-MM-DD então esta função e a de baixo são exatamente iguais
  $valores = explode('/', $data);
  if (isset($valores[2])) {  // só converte se a data for legível (duas barras)
    $valores[0] = substr( '00' . $valores[0], -2);
    $valores[1] = substr( '00' . $valores[1], -2);
    $valores[2] = substr( '20' . $valores[2], -4);
    return $valores[2] . '-' . $valores[1] .  '-' . $valores[0];
  } else return $data;  // se a data não for legível (duas barras), retorna a mesma coisa que mandou
}

function dtaBarra2AAAA_MM_DD($data) {
  // Transforma data no formato DD/MM/AAAA para AAAA-MM-DD
  // Agora ficou igual à função de cima
  return dtaBarra2AAAAMMDD($data);
  //$data2 = dtaBarra2AAAAMMDD($data);
  //if ($data2 <> $data) return substr($data2, 0, 4) . '-' . substr($data2, 4, 2) . '-' . substr($data2, 6, 2);
  //else return $data; // ver comentário acima sobre data legível ou não
}

function listdir($start_dir='.', $exclui_xml = False) {  // usado para ler arquivos recursivamente

  $files = array();
  if (is_dir($start_dir)) {
    $fh = opendir($start_dir);
    while (($file = readdir($fh)) !== false) {
      # loop through the files, skipping . and .., and recursing if necessary
      if (strcmp($file, '.')==0 || strcmp($file, '..')==0) continue;
      $filepath = $start_dir . '/' . $file;
      if ( is_dir($filepath) )
        $files = array_merge($files, listdir($filepath, $exclui_xml));
      else {
      	if ($exclui_xml) {
      	  //debug_log(mb_strtolower(mb_substr($filepath, -4)) . "\r");
      	  if (mb_strtolower(mb_substr($filepath, -4)) <> '.xml') array_push($files, $filepath);
      	} else array_push($files, $filepath);
      }
    }
    closedir($fh);
  } else {
    # false if the function was called with an invalid non-directory argument
    $files = false;
  }
  return $files;
}

function db_lista_campos($db, $tabela) {
	$valor = array();
	$result = $db->query("PRAGMA table_info(`{$tabela}`);");
	while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
		$valor[] = $row['name'];
	}
	$db->close;
	return $valor;
}

function db_lista_tabelas($db) {
	$valor = array();
	$result = $db->query("SELECT name FROM sqlite_master WHERE type='table';");
	while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
		$valor[] = $row['name'];
	}
	$db->close;
	return $valor;
}

function db_lista_dbs($db) {
	$valor = array();
	$result = $db->query("PRAGMA database_list;");
	while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
		$valor[] = $row;
	}
	$db->close;
	return $valor;
}


function db_esta_aberto($db, $nome) {
	$valor = False;
	$result = $db->query("PRAGMA database_list;");
	while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	  if ($row['name'] == $nome) $valor = True;
	}
	$db->close;
	return $valor;
}

function attach_common($db) {
  if (!file_exists(PR_DB3 . '/common.db3')) return False;
  else {
	if (!db_esta_aberto($db, 'common')) $db->exec("ATTACH '" . PR_DB3 . "/common.db3' AS common");
	return True;
  }
}
  
function create_table_from_txt($db, $createtable, $txt_file, $tablename, $cabec = True) {

	$db->exec($createtable);
	$dados = file_get_contents(utf8_decode($txt_file));
	$linhas = explode("\n", $dados);
	$db->exec('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
	foreach($linhas as $key => $value) {
		// Se $cabec = True é porque há linha de cabeçalho, que deve ser pulada!
		if ($cabec) {
			$cabec = False;
		} else {
			$campos = explode("\t", trim($value, " \n\r"));
			$insert_query = "INSERT INTO {$tablename} VALUES( ";
			foreach($campos as $keyc => $valuec) $insert_query .= "'" . $db->escapeString($valuec) . "', ";
			$insert_query = substr($insert_query, 0, -2) . ')';
			$db->exec($insert_query);
		}
	}
	$db->exec('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
}

function cria_tabela_cfopd($db) {

	$createtable = "
CREATE TABLE cfopd (cfop int, dfi text, st text, classe text, g1 text, c3 text, g2 text, g3 text, descri_simplif text, descri text, pod_creditar text);
CREATE INDEX cfopd_cfop ON cfopd (cfop ASC);
";
	create_table_from_txt($db, $createtable, PR_RES . '/tabelas/cfopd.txt', 'cfopd');

}

function nfe_cUF_xUF($cUF) {
	// Dado o código do estado (exemplo: 35), retorna a descrição do estado (exemplo: "SP")
	switch ($cUF + 0) {
		case 11: return ("RO");
		case 12: return ("AC");
		case 13: return ("AM");
		case 14: return ("RR");
		case 15: return ("PA");
		case 16: return ("AP");
		case 17: return ("TO");
		case 21: return ("MA");
		case 22: return ("PI");
		case 23: return ("CE");
		case 24: return ("RN");
		case 25: return ("PB");
		case 26: return ("PE");
		case 27: return ("AL");
		case 28: return ("SE");
		case 29: return ("BA");
		case 31: return ("MG");
		case 32: return ("ES");
		case 33: return ("RJ");
		case 35: return ("SP");
		case 41: return ("PR");
		case 42: return ("SC");
		case 43: return ("RS");
		case 50: return ("MS");
		case 51: return ("MT");
		case 52: return ("GO");
		case 53: return ("DF");
	}
}

function corrige_cfop_entsai($cfop) {
	// Pode não parecer, mas esta função é bem mais complexa do que parece...
	// Neste primeiro momento, vai ser feito uma tabela de conversão
	// Mais para frente, seria interessante uma análise dos casos em que podem haver resultados diferentes
	if (!isset($GLOBALS['cfop_entsai'])) {
	  $GLOBALS['cfop_entsai'] = array();
	  $dados = file_get_contents("tabelas\cfop_entsai.txt");
	  $linhas = explode("\n", $dados);
	  foreach($linhas as $key => $value) {
		$campos = explode("\t", substr($value, 0, -1));
		$GLOBALS['cfop_entsai'][$campos[0]+0] = $campos[1]+0;
		if (!isset($GLOBALS['cfop_entsai'][$campos[1]+0])) $GLOBALS['cfop_entsai'][$campos[1]+0] = $campos[0]+0;
	  }
	  // print_r($GLOBALS['cfop_entsai']); 	// debug
	}
	if ($cfop > 5000) {
	  $cfop_ent = 5000 + ($cfop - floor($cfop / 1000)*1000);
	  $cfop_sai = 1000 + $cfop_ent;  // se não estiver na tabela, somente diminui 4000
	} else {
	  $cfop_ent = 1000 + ($cfop - floor($cfop / 1000)*1000);
	  $cfop_sai = 5000 + $cfop_ent;  // se não estiver na tabela, somente aumenta 4000
	}
	if (isset($GLOBALS['cfop_entsai'][$cfop_ent])) $cfop_sai = $GLOBALS['cfop_entsai'][$cfop_ent]; // se estiver na tabela, substitui
	// echo "\nCFOP=" . $cfop . "ENT=" . $cfop_ent . "SAI=" . $cfop_sai . "RET=" . ($cfop_sai + ($cfop - $cfop_ent)); // Debug
	return $cfop_sai + ($cfop - $cfop_ent);
}

function fgetcsv_bo(&$handle) {

	//  a leitura csv sempre foi feita da seguinte forma:
	//		$campos = fgetcsv($handle, 5000, ',', '"');
	//
	//  No entanto, ocorreram alguns problemas nos arquivos csv vindos do BO, quando há um " dentro de um campo
	//  Por isso, resolvi reescrever esta função

	$linha = trim(fgets($handle));
	//  O csv que vem do BO, em regra
	//		- Nos cabeçalhos é separado por vírgulas e os campos não estão dentro de aspas
	//		- Nas linhas de dados é separado por vírgulas e os campos estão dentro de aspas
	if (substr($linha, 0, 1) == '"') $campos = explode('","', substr($linha, 1, strlen($linha) - 2));
	else							 $campos = explode(',', $linha);
	return $campos;

}
  
// Funções para uso em SQL (arquivos .sql, setor #EVAL, processados por sql.php)

// Funções customizadas para SQLITE
// registre a função com $db->createFunction
// Exemplo: $db->createFunction('sqlite_acum', 'sqlite_acum');

// Função sqlite_acum($valor) -> Acumula valores - utilizado, por exemplo, para saldos em contas correntes (saldo anterior + movimento = saldo final)
// Antes de usar a função, chame sqlite_zera_acum() para inicializar

function sqlite_zera_acum() {
  global $sqlite_acum, $sqlite_acum_primvez;
  
  $sqlite_acum_primvez = True;
  $sqlite_acum = 0;
  
  return(0);
}

function sqlite_acum($valor) {
  global $sqlite_acum, $sqlite_acum_primvez;
  
  if($sqlite_acum_primvez) {
    // por algum motivo que desconheço, a primeira linha do Select SQLite é chamada duas vezes... então essa flag tira a primeira vez
    $sqlite_acum_primvez = False;
  } else $sqlite_acum += $valor;
  //wecho("#{$sqlite_acum}-{$valor}#");
  return ($sqlite_acum);
}

// Função sqlite_customedio_qtd($qtd, $valor) -> Retorna conforme fórmula do custo médio
//		Acumula $qtd e joga em $sqlite_customedio_qtdsaldo
//		Acumula $valor e joga em $sqlite_customedio_valorsaldo
//		Retorna o Saldo da quantidade ($sqlite_customedio_qtdsaldo) e não o custo médio (custo médio é o método)
//		Para ver o Saldo do valor ($sqlite_customedio_valorsaldo) use sqlite_customedio_valor() - ver abaixo
function sqlite_customedio_qtd($qtd, $valor) {
  global $sqlite_customedio, $sqlite_customedio_valorsaldo, $sqlite_customedio_qtdsaldo, $sqlite_customedio_primvez;
  
    if ($qtd == 'z') {
	  // Zera acumuladores
	  $sqlite_customedio = 0;
	  $sqlite_customedio_qtdsaldo = 0;
	  $sqlite_customedio_valorsaldo = 0;
	} else {
	  $sqlite_customedio_qtdsaldo += $qtd;
	  // Entrada: qtd > 0
	  if ($qtd > 0) { 
		$sqlite_customedio_valorsaldo += $valor;
		$sqlite_customedio = $sqlite_customedio_qtdsaldo == 0 ? 0 : round($sqlite_customedio_valorsaldo / $sqlite_customedio_qtdsaldo, 4);
	  } else {
	  // Saída - Custo médio não se altera, repete o último
		$sqlite_customedio_valorsaldo = round($sqlite_customedio * $sqlite_customedio_qtdsaldo, 2);
	  }
	}
	return ($sqlite_customedio_qtdsaldo);
}

function sqlite_customedio_valor() {
  global $sqlite_customedio_valorsaldo;
  return ($sqlite_customedio_valorsaldo);
}

function sqlite_customedio_custo() {
  global $sqlite_customedio;
  return ($sqlite_customedio);
}


// Função sqlite_final_mes -> Retorna o último dia do mês
// Exemplo: sqlite_final_mes('2009-04-01') = '2009-04-30'
function sqlite_final_mes($valor) {
  $date = new DateTime($valor);
  $date->add(new DateInterval('P1M'));
  $date->sub(new DateInterval('P1D'));
  return ($date->format('Y-m-d'));
}

// Função sqlite_padrao -> Retorna o padrão - usado no campo histórico do ECD
function sqlite_padrao($valor) {
  $result = '';
  for($i=0; $i<strlen($valor); $i++) {
    $asc = ord(strtoupper(substr($valor, $i, 1)));
	if ($asc >= 65 && $asc <= 90) $result .= chr($asc); else $result .= ' ';
  }
  $palavras = explode(' ', $result);
  $result_final = '';
  foreach ($palavras as $indice => $valor)
    if (strlen($valor) >= 2) $result_final .= $valor;
  return (soundex($result_final));
}

// Função sqlite_padrao_nr -> Retorna o número que está no campo histórico do ECD
function sqlite_padrao_nr($valor) {
  $result = '';
  for($i=0; $i<strlen($valor); $i++) {
    $asc = ord(substr($valor, $i, 1));
	if ($asc >= 48 && $asc <= 57) $result .= chr($asc);
  }
  return ($result);
}

?>