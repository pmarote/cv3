<?php

function abredb3_txt($arquivo_txt = '') {
 global $tempo_inicio, $ldebug, $options;
 // Regras para o nome do arquivo db3 e também dos arquivos xls gerados na conversão
 // Se a opção "um arquivo excel para cada arquivo em fontes" estiver setada, nome = txt (txt.db3, txt.xls)
 // Caso contrário, nome = txt_{$arquivo_txt}
 $nomarqaux = explode("/", $arquivo_txt);
 if ($options['arqs_sep']) $nomarq = "txt_" . substr($nomarqaux[count($nomarqaux)-1], 0, -4); else $nomarq = "txt";

	if (!file_exists(PR_TMP . "/{$nomarq}.db3")) { 

		if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
		} else {
			werro('Falha ao criar Banco de Dados p32.db3');
			exit;
		}  
		$db->query('PRAGMA encoding = "UTF-8";');

	} else {
	
		if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
		} else {
			werro('Falha ao abrir Banco de Dados p32.db3');
			exit;
		}  
	
	}


	
	return $db;
}

function conv_txt_arruma_nome_campo($campo) {
    $map = array(
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'ª' => 'a', 
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 
            'é' => 'e', 'è' => 'e', 'ê' => 'e', '&' => 'e', 
            'É' => 'E', 'È' => 'E', 'Ê' => 'E',  
            'í' => 'i', 'ì' => 'i',
            'Í' => 'I', 'Ì' => 'I',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'º' => 'o', '°' => 'o',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'ú' => 'u', 'ù' => 'u',                         'ü' => 'u',
            'Ú' => 'U', 'Ù' => 'U',                         'Ü' => 'U',
            'ç' => 'c', 'Ç' => 'C',
			' ' => '_', '"' => '´', "'" => '´', '`' => '´', 
            '[' => '',  ']' => '',  '<' => '',  '>' => '',  '{' => '',  '}' => '',  '(' => '',  ')' => '',  ':' => '',  ';' => '', 
            ',' => '',  '.' => '',  '!' => '',  '?' => '',  '+' => '',  '-' => '',  '*' => '',  '/' => '',  '%' => '',  '~' => '',
            '^' => '',  '¨' => '',  '#' => ''
    );
    return strtr($campo, $map);
}


function txt_explode($linha, $tipo_arq) {
	if ($tipo_arq == '.txt') {
		return explode("\t", $linha);
	} else {
		if ($tipo_arq == '.csv') {
			return str_getcsv($linha);	
			// 86 segundos com o comando acima
			// 94 segundos com o código abaixo	- abaixo ainda falta processar escape (padrão é barra invertida \ )
/*
			// .csv é o seguinte... É separado por vírgulas (aqui transforma em TABs)
			// mas se o campo começar com um aspas, ele só acaba quando achar a próxima aspas (pode ter vírgula entre as aspas, que não é separação)
			$linha_r = "";
			$abriu_aspas = False;
			$iniciou_campo = False;
			for($i=0; $i<mb_strlen($linha); $i++) {
				$schar = mb_substr($linha, $i, 1);
				if (!$iniciou_campo && !$abriu_aspas && $schar == '"') {
					$abriu_aspas = True;
					$iniciou_campo = True;
					continue;
				}
				if (!$abriu_aspas) {
					if ($schar == ',') {
						$linha_r .= "\t";	// Insire TAB como separador
						$iniciou_campo = False;
						continue;
					} else {
						$linha_r .= $schar;
						continue;
					}
				}
				if ($abriu_aspas) {
					if ($schar == '"') {
						$abriu_aspas = False;
						continue;
					} else {
						$linha_r .= $schar;
						continue;
					}
				}
			}
			//debug_log("LinhaOriginal:{$linha}\r\nLinhaTab:{$linha_r}\r\n");
			return explode("\t", $linha_r);
*/			
		}  else return "tipo_arquivo_inválido({$tipo_arq})";
		
	}	
}

function leitura_txt($arquivo_txt) {

	global $pr, $tempo_inicio, $ldebug, $options, $abredb3_txt_nome_tabela;
	
	$tipo_arq = mb_strtolower(substr($arquivo_txt, -4));	// .txt ou .csv
	
    $db = abredb3_txt($arquivo_txt);
	//debug_log("Quantidade de Campos: {$abredb3_txt_qtd_campos}\r\n");		// está chegando corretamente esta variável global?

	// abre duas vezes... Na primeira vez, a primeira linha é usada para os nomes dos campos e a segunda linha para detectar os tipos de campos
    if (!$handle = fopen("{$arquivo_txt}", 'r')) {
     werro_die("Nao foi possivel a leitura do arquivo {$arquivo_txt} - possivelmente foi deletado durante o processamento");
    }

	$linha = fgets($handle);	// cabecalho serão os campos da linha 1
	// a primeira linha pode ter o BOM do utf8...(exemplo: exportação txt do BO). Para evitar problemas, retirar o BOM do início...
	$bom = pack('H*','EFBBBF');
	//$campos = explode("\t", trim(preg_replace("/^$bom/", '', $linha), "\r\n"));
	$campos = txt_explode(trim(preg_replace("/^$bom/", '', $linha), "\r\n"), $tipo_arq);

	$linha2 = fgets($handle);	// para pegar os tipos de campos
	//$tp_campos = explode("\t", trim($linha2, "\r\n"));
	$tp_campos = txt_explode(trim($linha2, "\r\n"), $tipo_arq);

			//	// se de cara o número de campos for diferente, já desiste...
			//	if (count($campos) != count($tp_campos)) werro_die("No arquivo {$arquivo_txt}, o número de campos da primeira linha difere do número de campos do //cabeçalho!");
			//	acima, mudei tudo! Ver abaixo
/* ######################################################################################
	Quem define o número de campos é apenas a primeira linha
		'- Se quiser mudar no número de campos, edite a primeira linha e insira ou remova tabs... simples assim
	- Se, nas linhas seguintes:
		a) Houver mais campos: serão inseridas duas linhas, sendo que a primeira é uma linha de erro, no primeiro campo com a mensagem: 
		'#ERRO#Linha{$ilidos}_Abaixo_Com_". count($campos) . "_Campos
		b) Houver menos campos: o software tentará descobrir se os demais campos estão na linha a seguir ou nas seguintes. Isso pode acontecer quando um campo tem \r\n, como por exemplo campo observação
			b1- Se nas próximas linhas ele conseguir completar exatamente o número de campos correto, ele completa;
			b2- Se não conseguir, desiste (na última linha) e completa com espaços, no último campo avisando:
			'#ERRO#Linha{$ilidos}_Abaixo_Com_". count($campos) . "_Campos
*/

	// criação da tabela. Começa definindo o nome
	$abredb3_txt_nome_tabela = substr(substr($arquivo_txt, strrpos($arquivo_txt, '/') + 1), 0, -4);		// -4 para tirar o .txt
	if (substr($abredb3_txt_nome_tabela, 0, 1) <= '9' && substr($abredb3_txt_nome_tabela, 0, 1) >= '0') $abredb3_txt_nome_tabela = '_' . $abredb3_txt_nome_tabela;		// nome da tabela não pode começar com número
	$abredb3_txt_nome_tabela = conv_txt_arruma_nome_campo($abredb3_txt_nome_tabela);

	// criação da tabela. Definição completa dos campos, tentando inclusive descobrir se o campo é Texto, Real ou Int
	$sql_create = "create table {$abredb3_txt_nome_tabela} (";
	// os campos tem que ter o nome 'limpo' para fins de SQL. Além disso, não pode haver campo com nome repetido
	$a_campos_final = array();
	$abredb3_txt_qtd_campos = 0;
	foreach($campos as $indice => $valor) {
		$valor = strtolower(conv_txt_arruma_nome_campo($valor));
		while (in_array($valor, $a_campos_final)) $valor .= '_';		// enquanto houver campo repetido, vai inserindo '_'
		$a_campos_final[] = $valor;
		$abredb3_txt_qtd_campos++;
		if (mb_strlen($tp_campos[$indice]) >= 25) $tipo_campo = 'T';	// se houver 25 caracteres ou mais, deverá ser chave de acesso, etc... sem hipótese de número
		else $tipo_campo = qual_tipo_sqlite($tp_campos[$indice]);	// em base.inc.php
		$sql_create .= $db->escapeString(utf8_encode(trim($valor)));
		$sql_create .= ($tipo_campo == 'T' ? ' TEXT' : ($tipo_campo == 'R' ? ' REAL' : ' INT')) . ", ";
	}
	$sql_create = substr($sql_create, 0, -2) . ');';
	//debug_log("\r\n#{$sql_create}#\r\n");
	//debug_log("Quantidade de Campos: {$abredb3_txt_qtd_campos}\r\n");
	$db->exec($sql_create);
	

	// Leitura do Arquivo ! (linhas 2 e seguintes)
    $ilidos = 1;			// começa um acima porque pulou o cabeçalho
	$db->query('BEGIN;'); 	// Conforme faq do Sqlite, acelera Insert (questao 19)
	
	$txt_encoding = 'ANSI';		// a cada linha vai detectando o encoding... se for utf-8, muda esta variável...
	
	
	// a leitura acaba quando feof($handle) E !isset($linha2) !  Porque pode ter acabado a leitura mas ainda pode haver algo a ser lido, que estará guardado em $linha2!
	while( !(feof($handle) && !isset($linha2)) ) {
		// para evitar que a primeira linha de dados (linha2) seja pulada
		if (isset($linha2)) {
			$linha = $linha2;
			unset($linha2);
		} else {
			$linha = fgets($handle);
		}
	  if (mb_detect_encoding($linha, 'UTF-8', true)) $txt_encoding = 'UTF-8';
	  else $txt_encoding = 'ANSI';
	  $ilidos++;
	  if (! (trim($linha) == '')) {		// pula linhas em branco
		//$campos = explode("\t", trim($linha, "\r\n"));
		$campos = txt_explode(trim($linha, "\r\n"), $tipo_arq);

		// tem que controlar o número de campos... se vier errado, com mais campos, avisa com uma linha adicional antes
		if (count($campos) > 0 && count($campos) > $abredb3_txt_qtd_campos) {
			$insert_query = "INSERT INTO {$abredb3_txt_nome_tabela} VALUES( ";
			$insert_query .= "'#ERRO#Acima#Linha{$ilidos}_Com_". count($campos) . "_Campos', ";
			if ($abredb3_txt_qtd_campos > 1) for ($i=1; $i<$abredb3_txt_qtd_campos; $i++) $insert_query .= "'', ";
			$insert_query = substr($insert_query, 0, -2) . ' );';
			//debug_log("{$insert_query}\r\n");
		    $db->query(substr($insert_query, 0, -2) . ' );');
		}

		// tem que controlar o número de campos... se vier errado, com menos campos, tenta completar com linha de baixo, conforme explicado lá em cima
		if (count($campos) > 0 && count($campos) < $abredb3_txt_qtd_campos) {
			// $b_desiste = completa com espaços avisando erro no finalizada
			$b_desiste = False;
			if ( feof($handle) ) $b_desiste = True;
			if ( !$b_desiste ) {
				$linha2 = fgets($handle);
				//$campos2 = explode("\t", trim($linha2, "\r\n"));
				$campos2 = txt_explode(trim($linha2, "\r\n"), $tipo_arq);
				// o número de campos totais é sempre (count($campos) + count($campos2) - 1), lembre-se que 1(um) campo está em duas linhas
				
				// se ainda for menor, vai tentando completar e gera nova linha2 !
				while ( (count($campos) + count($campos2) - 1) < $abredb3_txt_qtd_campos) {
					// vai insistindo...
					if ( feof($handle) ) $b_desiste = True;
					if ( !$b_desiste ) {
						$linha = $linha . $linha2;
						unset($linha2);
						$linha2 = fgets($handle);
						//$campos2 = explode("\t", trim($linha2, "\r\n"));
						$campos2 = txt_explode(trim($linha2, "\r\n"), $tipo_arq);
					}
				}

				// se for igual é porque deu certo!!!
				if ( (count($campos) + count($campos2) - 1) == $abredb3_txt_qtd_campos) {
					$linha = $linha . $linha2;
					unset($linha2);
					// reprocessa
					if (mb_detect_encoding($linha, 'UTF-8', true)) $txt_encoding = 'UTF-8';
					else $txt_encoding = 'ANSI';
					//$campos = explode("\t", trim($linha, "\r\n"));
					$campos = txt_explode(trim($linha, "\r\n"), $tipo_arq);
				} else {
					// se for maior, desiste...
					$b_desiste = True;
				}
			}
			if ($b_desiste) {
				// Deu campo a menor! Avisar e completar com espaços
				$insert_query = "INSERT INTO {$abredb3_txt_nome_tabela} VALUES( ";
				$insert_query .= "'#ERRO#Abaixo#Linha{$ilidos}_Com_". count($campos) . "_Campos', ";
				if ($abredb3_txt_qtd_campos > 1) for ($i=1; $i<$abredb3_txt_qtd_campos; $i++) $insert_query .= "'', ";
				$insert_query = substr($insert_query, 0, -2) . ' );';
				$db->query(substr($insert_query, 0, -2) . ' );');
				// reprocessa
				if (mb_detect_encoding($linha, 'UTF-8', true)) $txt_encoding = 'UTF-8';
				else $txt_encoding = 'ANSI';
				//$campos = explode("\t", trim($linha, "\r\n"));
				$campos = txt_explode(trim($linha, "\r\n"), $tipo_arq);
			}
		}
		
		$insert_query = "INSERT INTO {$abredb3_txt_nome_tabela} VALUES( ";
		
		for ($i=0; $i<$abredb3_txt_qtd_campos; $i++) {	// sempre vai inserir o número certo de campos
			// se não existir o campo, insere Null
			if (!isset($campos[$i])) $insert_query .= "Null, ";
			else {
				$campos[$i] = $db->escapeString(trim($campos[$i]));
				// se for qualquer tipo de data, transforma para aaaa-mm-dd
				// possibilidades: d/m/aa até dd/mm/aaaa  (6 a 10 caracteres)
				if (strlen($campos[$i]) <= 10 && strlen($campos[$i]) >= 6) {
					$iaux = strpos($campos[$i], '/');
					if($iaux !== False) {
						$iaux2 = strpos($campos[$i], '/', $iaux + 1);
						if (strpos($campos[$i], '/', $iaux2 + 1) === False) {
							// só pode ter duas barras   3/3/2345
							//							 01234567
							$aux_dia = substr('0' . substr($campos[$i], 0, $iaux), -2);
							$aux_mes = substr('0' . substr($campos[$i], $iaux + 1, $iaux2 - $iaux - 1), -2);
							$aux_ano = substr('20' . substr($campos[$i], $iaux2 + 1), -4);
							$campos[$i] = "{$aux_ano}-{$aux_mes}-{$aux_dia}";
						}
					}
				}
				if ($txt_encoding == 'ANSI') $campos[$i] = utf8_encode($campos[$i]);
				if (mb_strlen($campos[$i]) >= 25) $tp_campo = 'T';	// se houver 25 caracteres ou mais, deverá ser chave de acesso, etc... sem hipótese de número
				else $tp_campo = qual_tipo_sqlite($campos[$i]);	// em base.inc.php
				if($tp_campo == 'I' || $tp_campo == 'R') $insert_query .= str_replace(',','.',str_replace('.', '', trim($campos[$i]))) . ", ";
				else $insert_query .= "'{$campos[$i]}', ";
					// debug_log("#{$tp_campo}#{$campos[$i]}#");
			}
		}
		$insert_query = substr($insert_query, 0, -2) . ' );';
		//debug_log("{$insert_query}\r\n", 10000);
	    $db->query($insert_query);

	    if ($ilidos % 20000 == 0) {
			if ($ldebug) {
				wecho("\nLidas {$ilidos} linhas em ");
				wecho(time() - $tempo_inicio . " segundos");
			} else wecho("*");
		}
	  }
	}
    fclose($handle);
	if ($ldebug) {
	  wecho("\nParte 1 - Leitura finalizada: {$ilidos} linhas do arquivo {$arquivo} em ");
	  wecho(time() - $tempo_inicio . " segundos\n\n");
	} else wecho("*");
	flush();
	$db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

}

?>