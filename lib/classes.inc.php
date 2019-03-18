<?php

class Pr {

  public $aud_params = array();		// array de objetos PrMenu
  public $asubmenu_item = array();	// array com os objetos de submenus de Auditorias - para habilitar ou desabilitar (sensitive) - Ver Leitura.php
  public $options = array();		// array de opções
  public $db;						// objeto Sqlite3
  public $arq_log;					// nome do arquivo de log (guarda todas as mensagens geradas por wecho)
  public $db_disponiveis = '';		// string contendo a lista dos dbs disponíveis
  public $mens_final_conf = '';		// mensagem a ser mostrada ao final da conversão

  // Propriedades - Seção Parâmetros (utilizados nos Sqls das Auditorias)
  public $sql_params = array();		// contém sempre dois índices: O primeiro, nome do db3 (nfe, p32, etc...)
									// o segundo, o nome do parâmetro
									// Por convenção, todos os parâmetros são preenchidos em TabAux.php

  // Propriedades - Seção Excel
  public $ver_excel;				// Versão do Excel (Espera-se versão 2003 (11.0) e 2010 (14.0))
									// Futuramente, trabalhar também 2007 (12.0)
  public $nomarq;					// Nome do Arquivo (sem o sufixo .xls)
  public $excel;					// Guarda um link com o Excel do sistema ( COM )
  public $form_espe;				// Formatações especiais para fazer no Excel
  
  // Propriedades - Geração de Html
  public $indice_html;				// Arquivo 
  
  // Propriedades - Geração de Access
  public $table_def;				// Definição dos campos da tabela

  function __construct() {
  	$this->arq_log = PR_LOG . '/ConversorWecho.log';
	$this->inicia_log();
    // ['label'] é o texto que vai aparecer quando o usuário clicar em opções
	// ['tipo'] pode ser 'CheckButton' (True ou False), 'Entry' (campo texto), 'EntryInt' (campo inteiro) ou 'ComboBox' (ComboBox drop down list)
	// se ['tipo'] = 'ComboBox', deverá haver um array com a listagem em ['alist'], contendo em cada linha, valor e descrição
	$this->options['ldebug'] = True;		// logical - O sistema está em modo Debug ?  
	$this->options['label']['ldebug'] = 'Mostra mensagens detalhadas (_modo Debug)';
	$this->options['tipo']['ldebug'] = 'CheckButton';
	$this->options['aut_excel'] = False;		// logical - 
	$this->options['label']['aut_excel'] = 'Abrir automaticamente o Excel após gerar auditoria.';
	$this->options['tipo']['aut_excel'] = 'CheckButton';
	$this->options['edutf'] = True;		// logical - Os arquivos ECD e EFD estão codificados em UTF-8 (caso desabilitado, ANSI)  
	$this->options['label']['edutf'] = 'Os arquivos ECD e EFD estão codificados em UTF-8 (caso desabilitado, ANSI, que é o padrão, conforme manual do SPED)';
	$this->options['tipo']['edutf'] = 'CheckButton';
	$this->options['limit_sql'] = 300000;	// Limite máximo das Querys, definido em opções, usado em abre_excel, na exportação .txt
	$this->options['label']['limit_sql'] = 'Limite de linhas nas consultas : ';
	$this->options['tipo']['limit_sql'] = 'EntryInt';
	$this->options['nivdetmes'] = 4;	// Nível de detalhe padrão quando da visualização de dados dentro do ano
	$this->options['label']['nivdetmes'] = 'Nível de detalhe padrão quando da visualização de dados dentro do ano : ';
	$this->options['tipo']['nivdetmes'] = 'ComboBox';
	$this->options['alist']['nivdetmes'] = array(
		1 => 'Mensal',
		2 => 'Bimestral',
		3 => 'Trimestral',
		4 => 'Quadrimestral',
		6 => 'Semestral'
);
	$this->options['tiparqaud'] = 1;	// Auditorias Geral Qual(quais) tipos de arquivos
	$this->options['label']['tiparqaud'] = 'As auditorias geram qual(quais) tipos de arquivos : ';
	$this->options['tipo']['tiparqaud'] = 'ComboBox';
	$this->options['alist']['tiparqaud'] = array(
		1 => 'Somente Excel',
		2 => 'Somente Texto',
		3 => 'Excel e Texto',
		4 => 'Html e Texto',
		5 => 'Excel, Html e Texto',
		6 => 'Access e Texto'
);
	$this->options['limit_html'] = 10000;	// ver label abaixo
	$this->options['label']['limit_html'] = 'Limite de linhas por arquivos Html : ';
	$this->options['tipo']['limit_html'] = 'EntryInt';
	$this->options['limit_arqs_html'] = 10;	// ver label abaixo
	$this->options['label']['limit_arqs_html'] = 'Limite máximo de número de arquivos .html gerados por auditoria : ';
	$this->options['tipo']['limit_arqs_html'] = 'EntryInt';
	$this->options['savopt'] = False;		// ver label abaixo
	$this->options['label']['savopt'] = "Salva automaticamente as opções para próximas aberturas deste software";
	$this->options['tipo']['savopt'] = 'CheckButton';

	$this->read_options();
}


  // ** Seção Options
  public function save_options() {
	// Salva somente as opções que devem ser carregadas em logins futuros
	// Ou seja, tudo o que não é array, mais precisamente, excluindo ['label'], ['tipo'] e ['alist']
	$asave = array();
	foreach ($this->options as $indice => $valor) {
	  if (! is_array($this->options[$indice])) $asave[$indice] = $valor;
	}
	if(! file_put_contents(PR_RES . '/options.conf', serialize($asave))) wecho("Alerta... Não foi possível gravar o arquivo de configurações de opções options.conf");
  }

  public function read_options() {
	// Lê opções somente se estiver disponível o arquivo res/options.conf
	if (file_exists(PR_RES . '/options.conf')) {
	  $aread = unserialize(file_get_contents(PR_RES . '/options.conf'));
	  //debug_log(print_r($aread, True));
	  foreach ($aread as $indice => $valor) $this->options[$indice] = $valor;
	}
  }

  
  public function inicia_log($arq_log = '') {
	if ($arq_log <> '') $this->arq_log = $arq_log;
	if (file_exists($this->arq_log)) unlink($this->arq_log);
	if (file_exists(PR_LOG . '/SQLs.log')) unlink(PR_LOG . '/SQLs.log');
}
  
  public function query_log($sql) {
	file_put_contents(PR_LOG . '/SQLs.log', str_replace("\n", "\r\n", $sql), FILE_APPEND);
	return $this->db->query($sql);
  }

  public function exec_log($sql) {
	file_put_contents(PR_LOG . '/SQLs.log', str_replace("\n", "\r\n", $sql), FILE_APPEND);
	return $this->db->exec($sql);
  }

  // ** Seção Parâmetros
  public function salva_sql_params($nome_db) {
	// Salva os parâmetros dentro do db de nome $nome_db, na tabela parametros
	if ($db = new SQLite3(PR_DB3 . "/{$nome_db}.db3")) {
	} else {
	  werro("Falha ao abrir Banco de Dados {$nome_db}");
	  break;
	}
	$db->exec("DROP TABLE IF EXISTS parametros;");
	$db->exec("CREATE TABLE parametros (dados TEXT);");
	$db->exec("INSERT INTO parametros VALUES ('" . serialize($this->sql_params[$nome_db]) . "');");
	$db->close;
  }

  public function le_sql_params($nome_db) {
	// Lê os parâmetros dentro do db de nome $nome_db, da tabela parametros, caso já não tenha lido
	
	if ($db = new SQLite3(PR_DB3 . "/{$nome_db}.db3")) {
	} else {
	  werro("Falha ao abrir Banco de Dados {$nome_db}");
	  break;
	}
	$result = $db->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'parametros';");
    if (($row = $result->fetchArray(SQLITE3_ASSOC))) {
	  $result_param = $db->query("SELECT * FROM parametros");
	  if ($row = $result_param->fetchArray(SQLITE3_ASSOC)) {
	    $a_parametros = unserialize($row['dados']);
		foreach ($a_parametros as $indice => $valor) {
		  if (!isset($this->sql_params[$nome_db][$indice])) $this->sql_params[$nome_db][$indice] = $valor;
		}
	  }
	}
	$db->close;
  }

  public function le_todos_sql_params() {
	$this->carrega_db_disponiveis();
	$aaux = explode('[', str_replace(']', '', $this->db_disponiveis));
	foreach($aaux as $indice => $valor) {
	  $valor = trim($valor);
	  if ($valor <> '') $this->le_sql_params($valor);
	}
  }

  public function carrega_db_disponiveis() {
	// preenche $this->db_disponiveis com os db3s que estão disponíveis em PR_TMP
	$this->db_disponiveis = '';
	if (($fh = @opendir(PR_TMP)) !== False) {
	  while(($file = readdir($fh)) !== false) {
		if (strtolower(substr($file, -4)) == ".db3") {
		  if (strtolower(substr($file, 0, 3)) == "dfe") $this->db_disponiveis .= strpos($lbltexto, "dfe") > 0 ? "" : "[dfe] ";
		  if (strtolower(substr($file, 0, 3)) == "p17") $this->db_disponiveis .= strpos($lbltexto, "p17") > 0 ? "" : "[p17] ";
		  if (strtolower(substr($file, 0, 3)) == "p32") $this->db_disponiveis .= strpos($lbltexto, "p32") > 0 ? "" : "[p32] ";
		  if (strtolower(substr($file, 0, 3)) == "efd") $this->db_disponiveis .= strpos($lbltexto, "efd") > 0 ? "" : "[efd] ";
		  if (strtolower(substr($file, 0, 3)) == "ecd") $this->db_disponiveis .= strpos($lbltexto, "ecd") > 0 ? "" : "[ecd] ";
		  if (strtolower(substr($file, 0, 3)) == "gia") $this->db_disponiveis .= strpos($lbltexto, "gia") > 0 ? "" : "[gia] ";
		  if (strtolower(substr($file, 0, 3)) == "txt") $this->db_disponiveis .= strpos($lbltexto, "txt") > 0 ? "" : "[txt] ";
		  if (strtolower(substr($file, 0, 5)) == "audit") $this->db_disponiveis .= strpos($lbltexto, "audit") > 0 ? "" : "[audit] ";
		  if (strtolower(substr($file, 0, 5)) == "ladca") $this->db_disponiveis .= strpos($lbltexto, "ladca") > 0 ? "" : "[ladca] ";
		  if (strtolower(substr($file, 0, 5)) == "cat42") $this->db_disponiveis .= strpos($lbltexto, "cat42") > 0 ? "" : "[cat42] ";
		}
	  }
	}
	return $this->db_disponiveis;
  }

  // ** Seção Contagem de Tempo
  protected $p_tempo;
  
  public function zera_tempo() {
    $this->p_tempo = time();
  }
  
  public function tempo() {
    return time() - $this->p_tempo;
  }
  
  
  // ** Seção Métodos para Auditoria

  public function aud_abre_db_e_attach($use) {
	// aud_abre_db_e_attach - Abre os dbs e faz os attachs necessários, conforme $use
	// se há mais do que um db em $use, deverá estar separado por vírgulas
	// abre o primeiro e se houver mais de um, attacha os outros
	$usedbs = explode(',', $use);
	$arqdb = PR_TMP . "/" . trim($usedbs[0]) . ".db3";
	if (trim($usedbs[0]) == 'common') $arqdb = PR_DB3 . '/common.db3';
	if (trim($usedbs[0]) == 'cweb') $arqdb = PR_DB3 . '/cweb.db3';
	if (trim($usedbs[0]) == 'ies') $arqdb = PR_DB3 . '/ies.db3';
	if (trim($usedbs[0]) == 'gia5156') $arqdb = PR_DB3 . '/gia5156.db3';
	if ($this->db = new SQLite3($arqdb)) {
	  if ( !(trim($usedbs[0]) == 'common' || trim($usedbs[0]) == 'cweb' || trim($usedbs[0]) == 'ies' || trim($usedbs[0]) == 'gia5156')) 
	  	$this->le_sql_params(trim($usedbs[0]));
	} else {
	  werro("Falha ao abrir Banco de Dados {$usedbs[0]}");
	  break;
	}
	$idb = 1;
		//debug_log("#use={$use}#usedbs=" . print_r($usedbs, True));
	while (isset($usedbs[$idb])) {
	  $arqdb = PR_TMP . "/" . trim($usedbs[$idb]) . ".db3";
	  if (trim($usedbs[$idb]) == 'common') $arqdb = PR_DB3 . '/common.db3';
	  if (trim($usedbs[$idb]) == 'cweb') $arqdb = PR_DB3 . '/cweb.db3';
	  if (trim($usedbs[$idb]) == 'ies') $arqdb = PR_DB3 . '/ies.db3';
	  if (trim($usedbs[$idb]) == 'gia5156') $arqdb = PR_DB3 . '/gia5156.db3';
	  if ($this->exec_log("ATTACH '$arqdb' AS " . trim($usedbs[$idb]))) {
		if ( !(trim($usedbs[$idb]) == 'common' || trim($usedbs[$idb]) == 'cweb' || trim($usedbs[$idb]) == 'ies' || trim($usedbs[$idb]) == 'gia5156')) $this->le_sql_params(trim($usedbs[$idb]));
	  } else {
		werro("Falha ao abrir Banco de Dados {$usedbs[$idb]}");
		break;
	  }
	  $idb++;
	}
  }
  
  public function aud_registra(PrMenu $aud_param) {
	// aud_registra - Registra a auditoria PrMenu
	$this->aud_params[] = $aud_param;
  }
  
  public function aud_executa(PrMenu $aud_param) {
	// aud_executa - Chamado a partir do click no menu, em leitura.php, em function on_menu_select($menu_item)
	// $aud_param é o objeto PrMenu contendo os parâmetros daquela opção
	if ($this->aud_disponivel($aud_param->use)) {
	  // Abre os arquivos conforme $aud_param['use'];
	  $this->aud_abre_db_e_attach($aud_param->use);
	  $callback = $aud_param->callback;
	  $callback();
	} else wecho("\nErro: Auditoria '{$aud_param->menu} - {$aud_param->submenu}' não disponível - Falta banco de dados\n");
  }

  public function aud_disponivel($use) {
	// aud_disponível - Retorna True se a auditoria estiver disponível, isto é, se todos os bancos
	// de dados de $use estiverem na pasta PR_TMP
	if ($this->db_disponiveis == '') $this->carrega_db_disponiveis();
	$dbsuse = explode(',', $use);
	$sensitive = True;
	foreach ($dbsuse as $key => $entry) {
	  $dblist .= "[" . trim($entry) . "] ";
	  if (!(strpos($this->db_disponiveis, trim($entry)) > 0)) $sensitive = False;
	}
	return($sensitive);
  }
  
  public function aud_prepara($sql) {
	// aud_prepara - simplesmente faz um $db->exec , mas vai mostrando o andamento com wecho
	$sql_prep = explode(';', $sql);
	foreach($sql_prep as $ind_sqlp => $val_sqlp) {
	  if ($this->options['ldebug']) {
		wecho("SQL Preparatório: " . trim(substr($val_sqlp, 0, 60)) . "...\n");
	  } else wecho("*");
	  $this->exec_log($val_sqlp . ';');
	}
  }

  public function aud_sql2array($sql) {
    // Retorna uma consulta sql em um array.
	// não se esqueça, após, de ver se count(array) > 0... cuidado para não processar o nada...

	$result_param = $this->query_log($sql);
	$a_parametros = array();
	while ($parametros = $result_param->fetchArray(SQLITE3_ASSOC)) {
	  $a_parametros[] = $parametros;
	}
	return $a_parametros;
  }
 
// *****************************
// * Seção: Métodos para Excel *
// *****************************

// Agora também gera outros tipos de arquivos: txt e html, conforme $this->options['tiparqaud'] (ver método _construct)
// ao gerar txt e/ou html, o nome do arquivo excel é o nome da pasta, e cada planilha é o nome do arquivo .txt e/ou .html

  public function inicia_excel($nomarq) {
	// antes de iniciar, coloca um flag , um arquivo, indicando que está sendo criado um excel
	// após finalizar ( finaliza_excel ), esse arquivo é deletado
	// assim, ao fazer o startup, se este arquivo existir é porque houve travamento.... e então é sugerido utilizar "Forçar encerramento das janelas Excel"
	file_put_contents(PR_LOG . '/gerando_excel.log', 'x', FILE_APPEND);

  /*** 
		* @see    Open Excel, Hit Alt+F11, thne Hit F2 -- this is your COM bible 
		***/
	// $nomarq é o nome do arquivo a ser gerado, sem o sufixo .xls ou .xlsx (Versão 2007 ou 2010)
	$this->nomarq = $nomarq;	// vou usar este nome na hora de finaliza_excel()...

	// Os arquivos .txt, usados no Excel, ou não, estarão sempre dentro do diretório com o nome do arquivo excel, ou seja, $this->nomarq
	recursiveDelete(PR_TMP . "/{$this->nomarq}");
	if ( is_dir(PR_TMP . "/{$this->nomarq}") ) werro_die("Erro... não foi possível excluir a pasta " . PR_TMP . "/{$this->nomarq}. Verifique se há arquivo(s) aberto(s) nela...");;
	mkdir(PR_TMP . "/{$this->nomarq}"); 
	if (! is_dir(PR_TMP . "/{$this->nomarq}") ) werro_die("Erro... não foi possível criar a pasta " . PR_TMP . "/{$this->nomarq}. Verifique se não há proteção contra gravação...");;

	if ($this->options['tiparqaud'] == 1 || $this->options['tiparqaud'] == 3 || $this->options['tiparqaud'] == 5) { 	// Gerando tipo de arquivo excel...
	  // primeiro o excel é gerado em PR_TMP e, depois de pronto, movido para ./Resultados
	  $this->excel = new COM("excel.application") or werro_die("Erro... não foi possível abrir o Excel a partir do Conversor !"); 
	  if ($this->options['ldebug']) wecho("Abrindo excel, versao {$this->excel->Version}\n"); else wecho("*");
	  $this->ver_excel = $this->excel->Version;	// 2003, 11.0;	2007, 12.0	;2010, 14.0
	  // Aumenta o limite máximo das Querys, definido em opções, usado em abre_excel, na exportação .txt para 1.000.000 no caso de Excel 2007 ou 2010
	  if ($this->ver_excel <> '11.0' && $this->options['limit_sql'] == 300000)
			$this->options['limit_sql'] = 1000000;	// Limite 
	  //bring it to front #Tirei, mesmo em modo debug, porque me enchia muito o saco#
	  //if ($this->options['ldebug']) $this->excel->Visible = 1; 
	  //dont want alerts ... run silent 
	  $this->excel->DisplayAlerts = 0; 
	  //create a new workbook 
	  $wkb_final = $this->excel->Workbooks->Add(); 
	  // as três linhas abaixo para evitar que alguém abra o arq.temporário e trave o unlink
	  $sheet = $this->excel->ActiveSheet;
	  $cell=$sheet->Cells(1,1); 
	  $cell->value = "Arquivo Temporário ! Se foi este foi aberto manualmente, por favor feche, para não causar erros !";
	  $cell=$sheet->Cells(2,1); 
	  $cell->value = "Se foi aberto automaticamente, por favor não altere, espere a finalização !";
	  //select the default sheet 
	  $sheet_final = $this->excel->ActiveSheet; 
   
	  if ($this->ver_excel == '11.0') $xls_final = PR_TMP . '/' . $this->nomarq . '.xls'; 
	  else $xls_final = PR_TMP . '/' . $this->nomarq . '.xlsx';
	  if (file_exists($xls_final)) unlink($xls_final);
	  $wkb_final->SaveAs( $xls_final); 
	}
  }

  public function finaliza_excel() {

	if ($this->options['tiparqaud'] == 1 || $this->options['tiparqaud'] == 3  || $this->options['tiparqaud'] == 5) { 	// Fechando tipo de arquivo excel...
	  $this->excel->Sheets("Plan1")->Select;
	  $this->excel->ActiveWindow->SelectedSheets->Delete();
	  $this->excel->Sheets("Plan2")->Select;
	  $this->excel->ActiveWindow->SelectedSheets->Delete();
	  $this->excel->Sheets("Plan3")->Select;
	  $this->excel->ActiveWindow->SelectedSheets->Delete();
   
	  if ($this->ver_excel == '11.0') $nomarq = PR_RESULTADOS . "\\" . $this->nomarq . '.xls';
		else $nomarq = PR_RESULTADOS . "\\" . $this->nomarq . '.xlsx';
	  if (file_exists($nomarq)) unlink($nomarq);
	  // caso não consiga apagar (ex: excel aberto), vai inserindo '_' até achar um nome livre
	  $s_underlines = '';
	  while (file_exists($nomarq)) {
		$s_underlines .= '_';
		if ($this->ver_excel == '11.0') $nomarq = str_replace('.xls', '', $nomarq) . '_.xls';
		  else $nomarq = str_replace('.xlsx', '', $nomarq) . '_.xlsx';
	  }

	  $wkb_final = $this->excel->ActiveWorkbook;
	  $wkb_final->SaveAs($nomarq); 

	  //close the book 
	  $wkb_final->Close(false); 
	  $this->excel->Workbooks->Close(); 
	  //closing excel 
	  $this->excel->Quit(); 
	  //free the object 
	  $this->excel = null; 
	
	  if ($this->ver_excel == '11.0') wecho("\n\nArquivo Excel {$this->nomarq}{$s_underlines}.xls gerado com Sucesso !\n\n.\n");
		else wecho("\n\nArquivo Excel {$this->nomarq}{$s_underlines}.xlsx gerado com Sucesso !\n\n.\n");

	  if ($this->options['aut_excel']) {
		$shell = new COM('WScript.Shell');
		if ($this->ver_excel == '11.0') $shell->Run('excel ' . str_replace("/", "\\", PR_RESULTADOS). '\\' . $this->nomarq . $s_underlines . ".xls");
		  else $shell->Run('excel ' . str_replace("/", "\\", PR_RESULTADOS). '\\' . $this->nomarq . $s_underlines . ".xlsx");
		unset($shell);
	  }


	}
	if ($this->options['tiparqaud'] <> 1) { 	// Fechando tipo de arquivo txt...
	  $nomdir = PR_RESULTADOS . "/{$this->nomarq}";
	  recursiveDelete($nomdir);
	  // caso não consiga apagar pasta (ex: excel aberto), vai inserindo '_' até achar um nome livre
	  while (is_dir($nomdir)) {
		wecho("Falha ao apagar a pasta {$nomdir}... Possivelmente há arquivos abertos nessa pasta...\n");
		$nomdir = $nomdir . "_";
	  }
	  // Simplesmente move o diretório para Resultados
	  if (! rename(PR_TMP . "/{$this->nomarq}", $nomdir))
		wecho("\n\nNão foi possível colocar os arquivos Txt e/ou Html solicitados em Resultados, na pasta {$nomdir} .\nMas eles estão em " . PR_TMP . "/{$this->nomarq}\n\n");
	  else {
		wecho("\n\nArquivos Txt solicitados estão em Resultados, na pasta {$nomdir}\n\n");
		if ($this->options['tiparqaud'] == 4 || $this->options['tiparqaud'] == 5) wecho("Arquivos Html solicitados estão em Resultados, na pasta {$nomdir}\n\n");
		if ($this->options['tiparqaud'] == 6) wecho("Banco de Dados .mdb solicitado está em Resultados, na pasta {$nomdir}\n\n");
	  }
	}

	// Apagando o log gerando_excel - ver explicação na função inicia_excel
	if (file_exists(PR_LOG . '/gerando_excel.log')) unlink(PR_LOG . '/gerando_excel.log');
  }

  public function abre_excel_sql($planilha, $titulo, $sql, $col_format, $cabec, $form_final = '') {
/*	Os parâmetros são:
	$planilha - Nome da Planilha
	$titulo - titulo da planilha - opcional
	$sql - select sql final (que vai gerar a planilha)
	$col_format - array com formatação de texto de colunas específicas
	$cabec - array com cabecalho, que é o nome e comentário de cada coluna
	$form_final - opcional - são comandos, dentro de uma string, que serão evaluados (eval()) ao final de cada planilha. Exemplo:
	  $form_final = '
		$this->excel_orientacao(2);		// paisagem
		$this->excel_largura_coluna("B:C", 4);
		$this->excel_largura_coluna("M:N", 35);
		$this->excel_zoom_visualizacao(75);
	  '; // ** ATENCAO ** $form_final está com haspas simples (single quote = '). Se colocar haspas duplas (double quote = ")
		 // o PHP vai ver o $this->excel... e vai tentar processar... vai dar mierda...
		Porque fiz assim com $form_final ? Porque um abre_excel_sql() pode gerar mais do que uma planilha, se tiver mais do que 65.000 linhas
		Então esta função também já automatiza as formatações para todas as planilhas geradas
	Agora qualquer planilha pode ter formatação especial
	export_tab verifica se tem formatação. Caso tenha, retorna um array com os dados em $this->form_espe 
	As formatações especiais são caracteres que vem no resultado do sql. 
	// qualquer campo pode começar com ##NTCD##, determinando a formatação da linha e/ou do campo
	// ##N## - Linha Negrita
	// ##I## - Linha Itálica
	// ##T## - Cor de Totalização (cinza claro)
	// ##E## - Cor de Totalização (cinza escuro)
	// ##A## - Cor de Totalizacao 2, de Classe (cinza escuro e uma linha em branco antes)
	// ##C## - Cor de Totalizacao 2, de Classe (cinza escuro e uma linha em branco a seguir)
	// ##D## - Força com que os Campos da linha sejam à direita
	// ##Z## - Força com que os Campos da linha sejam centralizados
	// ##F07## - Tamanho da Fonte, com dois dígitos sempre. Exemplos: ##F07## - Tamanho 7   ##F10## - Tamanho 10  ##F32## - Tamanho 32
	// se for em minúsculo, formata o campo apenas e não a linha - exemplo ##n## - Campo Negrito
	
*/
	// Início: Primeiro faz a Exportação do sql em arquivo texto separados por tabs. Aproveita e já preenche $this->form_espe
	$this->form_espe = array();

	// No caso de exportação Access, define automaticamente a tabela com base no conteúdo do arquivo .txt gerado
	if ($this->options['tiparqaud'] == 6) $this->table_def = array();
	
	// Aqui é feita a abertura inicial do arquivo .txt . No caso de Excel 2003, Se o resultado tiver mais do que 65.000 linhas, será aberto outras, com nome _1 , _2, _3 ...
	$arq_txt = 0;		// número do arquivo txt, se tiver mais do que 65.000 linhas
	if (file_exists(PR_TMP . "/{$this->nomarq}/{$planilha}.txt")) unlink(PR_TMP . "/{$this->nomarq}/{$planilha}.txt"); 
	if (!$handle = fopen(PR_TMP . "/{$this->nomarq}/{$planilha}.txt", 'w')) {
	  werro_die("Nao foi possivel a gravacao do arquivo " . PR_TMP . "/{$planilha}.txt - Feche o programa ou janela que está o usando<br><br>");
	}

	// Pode haver mais de um select concatenado. Cada um está separado por ponto e vírgula e será executado um a um
	$asqlexp = explode(';', trim($sql));
	$ilinha = 0;
	foreach($asqlexp as $ind_sql => $val_sql) {
	  if ($val_sql <> '') {
		if ($this->options['ldebug']) {
		  wecho("SQL Excel: " . $val_sql . ";\n");
		} else wecho("*");

		// insere LIMIT com base em $this->options['limit_sql']
		$query_lim = trim($val_sql);
		if (substr($query_lim, -1) == ';') $query_lim = substr($query_lim, 0, -1) . ' LIMIT ' . $this->options['limit_sql'] . ';';
		else $query_lim .= ' LIMIT ' . $this->options['limit_sql'] . ';';
		// echo($query_lim); // Debug
		//	Por que utf8_decode na linha de baixo? É uma coisa meio louca, mas se colocar: SELECT '##NT##Totais por Valcon (reg válidos)' só assim dará certo, porque o resultado vem em ANSI
		$result = $this->query_log(utf8_decode($query_lim));
		// Um detalhe na linha abaixo... Sempre foi feito assim, então cuidado com o que vem pela frente..
		// Quando é feito um fetchArray, no caso de SQLITE3_ASSOC, se dois campos do select tiverem o mesmo
		// nome (exemplo: r50.cnpj e r54.cnpj), somente o primeiro aparece... então vou jogar SQLITE3_NUM
		// while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
		while ($row = $result->fetchArray(SQLITE3_NUM)) {
		  $icoluna = 0;
		  foreach($row as $indice => $valor) {	// processamento campo a campo
			// como o \r ou \n fode o excel, jogando o resto da linha para baixo, elimina se houver isso
			// a mudança de linha é substituída por um espaço, para não colar palavras de linhas diferentes
			$row[$indice] = utf8_decode(str_replace("\r", '', str_replace("\n", ' ', $valor)));

			// Verifica agora se há formatação especial - caso haja, lança um item em $form_espe e continua
			$valores = explode('##', $valor);
			// $valores deve ter no mínimo 3 elementos... 0 -> antes de ##; 1 -> dentro de ## -> 2 -> depois de ##
			if (isset($valores[2])) {
			  // se o elemento 1 tiver mais do que 7 caracteres, não é formatação especial... então pula
			  if (strlen($valores[1]) <= 7) {
				$row[$indice] = $valores[0] . $valores[2]; // exclui os caracteres de formatação especial
				// se existir valores 3, 4, etc..., devem ser dados que não podem ser omitidos... então, vai completando 
				$i = 3;
				if (isset($valores[$i])) {
				  $row[$indice] .= '##' . $valores[$i];
				  $i++;
				}
				$this->form_espe[] = array($ilinha, $icoluna, $valores[1]);
			  }
			}
			// Excel não corta números acima de 15 dígitos... então coloca # antes para o Excel tratar como String
			// senão, verifica se é numérico e se tem ponto - caso positivo, muda para vírgula
			if (is_numeric($row[$indice]) && $row[$indice] > 999999999999999) $row[$indice] = "#" . $row[$indice];
			if (is_numeric($row[$indice]) && mb_strpos($row[$indice], '.') !== False ) $row[$indice] = str_replace('.', ',', $row[$indice]);
			$icoluna++;
		  }

		  if (is_array($row)) {
			$slinha = '';
			$icoluna = 0;
			foreach($row as $key => $value) { 	// processamento campo a campo
			  if ($icoluna > 0) $slinha .= "\t";
			  $slinha .= $value;
			  if ($this->options['tiparqaud'] == 6) {
				// No caso de exportação Access, define automaticamente a tabela com base no conteúdo do arquivo .txt gerado
				// A princípio, todos os campos são NADA
				// Se algum campo for numérico, passa a ser NUMERIC    (NADA -> NUMERIC)
				// Se algum campo não for numérico, passa a ser CHAR   (NADA -> CHAR ou DATA -> CHAR ou NUMERIC -> CHAR)
				// DATA é um campo super chato. Todas as linhas deverão ter datas compatíveis e serem testadas, senão pau... 
				//		DATA sempre parte do NADA  (NADA -> DATA). Se der qualquer problema, passa a ser CHAR
				// No momento de criar a tabela, os campos NADA serão transformados para CHAR(1)
				if (!isset($this->table_def[$icoluna]['tipo'])) $this->table_def[$icoluna]['tipo'] = 'NADA';
				if ($value <> '') {
				  if ($this->table_def[$icoluna]['tipo'] == 'NADA' && is_numeric(str_replace(',', '.', $value))) {
				    $this->table_def[$icoluna]['tipo'] = 'NUMERIC';
				  } 
				  if (($this->table_def[$icoluna]['tipo'] == 'NADA' || $this->table_def[$icoluna]['tipo'] == 'DATE') && 
						strlen($value) == 10 &&
						substr($value, 4, 1) == '-' &&
						substr($value, 7, 1) == '-' &&
						checkdate(substr($value, 5, 2),substr($value, 8, 2),substr($value, 1, 4)) ) {
					$this->table_def[$icoluna]['tipo'] = 'DATE';
				  } else {
				    if ($this->table_def[$icoluna]['tipo'] == 'DATE') $this->table_def[$icoluna]['tipo'] = 'CHAR';
				  }
				  if (($this->table_def[$icoluna]['tipo'] == 'NADA' || $this->table_def[$icoluna]['tipo'] == 'NUMERIC') && !is_numeric(str_replace(',', '.', $value))) {
				    $this->table_def[$icoluna]['tipo'] = 'CHAR';
				  }
				  if (($this->table_def[$icoluna]['tipo'] == 'CHAR')) {
				    if (!isset($this->table_def[$icoluna]['tam'])) $this->table_def[$icoluna]['tam'] = strlen($value);
				    else {
				      if ($this->table_def[$icoluna]['tam'] < strlen($value)) $this->table_def[$icoluna]['tam'] = strlen($value);
				    }
				  }
				}
			  }
			  $icoluna++;
			}
			$slinha .= "\r\n";
			fputs($handle, $slinha);
		  }
		  $ilinha++;
		  // Limite somente no Excel 2003-65.000 ou 1.000.000 nos outros 
		  if (($this->ver_excel == '11.0' && $ilinha == 65000) || ($this->ver_excel <> '11.0' && $ilinha == 1000000)) {	
			fclose($handle);
			$arq_txt++;			// número do arquivo txt, se tiver mais do que 65.000 linhas (2003) ou 1.000.000 linhas
			if (file_exists(PR_TMP . "/{$this->nomarq}/{$planilha}_{$arq_txt}.txt")) unlink(PR_TMP . "/{$this->nomarq}/{$planilha}_{$arq_txt}.txt"); 
			if (!$handle = fopen(PR_TMP . "/{$this->nomarq}/{$planilha}_{$arq_txt}.txt", 'w')) {
			  werro_die("Nao foi possivel a gravacao do arquivo " . PR_TMP . "/{$planilha}_{$arq_txt}.txt - Feche o programa ou janela que está o usando<br><br>");
			}
			$ilinha = 0;
		  }
		}
	  }
	}
	fclose($handle);

	// Exportação do Arquivo .txt finalizada... agora abre_excel... usada uma função auxiliar porque ela pode ser recursiva
	if ($this->options['tiparqaud'] == 1 || $this->options['tiparqaud'] == 3 || $this->options['tiparqaud'] == 5) { 	// Gerando tipo de arquivo excel...
	  for ($i=$arq_txt; $i>= 0; $i--) {
		if ($i == 0) $this->abre_excel_aux($planilha, $titulo, $sql, $col_format, $cabec);
		else $this->abre_excel_aux("{$planilha}_{$i}", $titulo, $sql, $col_format, $cabec);
		if ($form_final <> '') eval( $form_final );
	  }
	}

	// agora gera html, caso necessário
	if ($this->options['tiparqaud'] == 4 || $this->options['tiparqaud'] == 5) { 	
	  for ($i=$arq_txt; $i>= 0; $i--) {
		if ($i == 0) $this->abre_html_aux($planilha, $titulo, $sql, $col_format, $cabec);
		else $this->abre_html_aux("{$planilha}_{$i}", $titulo, $sql, $col_format, $cabec);
	  }
	}

	// agora gera access, caso necessário
	if ($this->options['tiparqaud'] == 6) { 	
	  for ($i=$arq_txt; $i>= 0; $i--) {
		if ($i == 0) $this->abre_access_aux($planilha, $titulo, $sql, $col_format, $cabec);
		else $this->abre_access_aux("{$planilha}_{$i}", $titulo, $sql, $col_format, $cabec);
	  }
	} 
}

  public function auto_cabec($tabela) {

	$retorno =  array(
	'erro' => "Erro no método auto_cabec"
);

	$result = $this->query_log("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = '{$tabela}';");
    if (($row = $result->fetchArray(SQLITE3_NUM))) {
		// CREATE TABLE xxxx ( campo1 tipo, ....)
		$campos_str = substr($row[0], strpos($row[0], '(' ) + 1, strpos($row[0], ')' ) - strpos($row[0], '(' ) - 1);
		$campos = explode(',', $campos_str);
		$retorno = array();
		foreach($campos as $indice => $valor) {
		  $campo = explode(' ', trim($valor));
		  $retorno[$campo[0]] = (isset($campo[1]) ? $campo[1] : '');
		}
//		debug_log(print_r($row, True));
//		debug_log("###{$campos_str}###");
//		debug_log(print_r($retorno, True));

	}
	return $retorno;

  }



  public function abre_access_aux($planilha, $titulo, $sql, $col_format = '', $cabec = '') {

	for ($i = 0; $i < count($this->table_def); $i++) {
	  $this->table_def[$i]['nomcampo'] = "c{$i}";
	}
	$i = 0;
	if (is_array($cabec)) {
	  $i = 0;
	  foreach($cabec as $indice => $valor) {
		// toma o cuidado em converter para caracteres sem acentuação e retirar caracteres indesejados, como '!.[] ou espaço
	    $this->table_def[$i]['nomcampo'] = iconv('ISO-8859-1','ASCII//TRANSLIT', 
		   strtr(trim($indice), "áàâãéêíóôõúÁÀÂÃÉÊÍÓÔÕÚ !\"#$%&'*+,./{|}~[\\]^_`-()<>\t\r\n", "aaaaeeiooouAAAAEEIOOOU_______________________________"));
	    $i++;
	  }
	}
	//debug_log(print_r($this->table_def, True));
	if (file_exists(PR_TMP . "/{$this->nomarq}/{$planilha}.mdb")) unlink(PR_TMP . "/{$this->nomarq}/{$planilha}.mdb"); 
    copy("./tabelas/base.mdb", PR_TMP . "/{$this->nomarq}/{$planilha}.mdb");

    if (! $db_conn = new COM("ADODB.Connection") ) {
	  werro("Erro ! Não foi possível efetuar conexão à biblioteca .mdb");
	  return;
    }
    if (! $connstr = "DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=". realpath(PR_TMP . "/{$this->nomarq}/{$planilha}.mdb").";" ) {
	  werro("Erro ! Não foi possível conectar o Banco de Dados Access inicial");
	  return;
    }
    $db_conn->open($connstr);

    wecho("\nGerando " . realpath(PR_TMP . "/{$this->nomarq}/{$planilha}.mdb"));

	// Para checar se uma tabela existe ou não dá um trabalhão no mdb... então é melhor partir sempre de um BD vazio
	//$db_conn->execute("DROP TABLE IF EXISTS Frente_GIAs;");

	// Evita caracteres indesejados também no nome da tabela
	$table_name = iconv('ISO-8859-1','ASCII//TRANSLIT', 
		   strtr(trim($planilha), "áàâãéêíóôõúÁÀÂÃÉÊÍÓÔÕÚ !\"#$%&'*+,./{|}~[\\]^_`-()<>\t\r\n", "aaaaeeiooouAAAAEEIOOOU_______________________________"));
	$sql = "CREATE TABLE {$table_name} (";
	//debug_log("table_def: " . print_r($this->table_def, True) . "\n");
	foreach($this->table_def as $indice => $valor) {
	  // Ao criar a tabela, os campos NADA são transformados para CHAR(1)
	  if ($this->table_def[$indice][tipo] == 'NADA') {
	    $this->table_def[$indice][tipo] = 'CHAR';
	    $this->table_def[$indice][tam] = 1;
	  }
	  $sql .= $this->table_def[$indice]['nomcampo'] . ' ';
	  if ($this->table_def[$indice][tipo] == 'CHAR') {
		// Varchar deve ser um valor entre 1 e 255
	    $sql .= "CHAR(" . ($this->table_def[$indice]['tam'] < 255 ? ($this->table_def[$indice]['tam'] == 0 ? 1 : $this->table_def[$indice]['tam'] ) : 255) . "), ";
	  }
	  else $sql .= "{$this->table_def[$indice][tipo]}, ";
	}
	$sql = substr($sql, 0, -2) . ");";
	//debug_log("CREATE TABLE SQL = {$sql}");
	$db_conn->execute($sql);

	if (!$handle = fopen(PR_TMP . "/{$this->nomarq}/{$planilha}.txt", 'r')) {
	  werro_die("Nao foi possivel a leitura do arquivo " . PR_TMP . "/{$planilha}.txt - Erro do sistema de arquivos ?<br><br>");
	}

	$ilidos = 0;
	$tempo_inicio = time();
	while(!feof($handle)) {
	  $campos = fgetcsv($handle, 1500, "\t");
	  // se houver linha vazia, ou seja, sem tab, nem processa
	  if (isset($campos[1])) {
	    $i = 0;
		$sql = "INSERT INTO {$table_name} VALUES (";
		foreach ($campos as $ind => $val) {
		  if ($val == '') $sql .= "Null, ";
		  else            $sql .= "'" . $this->db->escapeString($val) . "', ";
		  $i++;
		}
		// verifica se há a quantidade correta de campos... caso esteja faltando, insere Nulls
		for($j = $i; $j < count($this->table_def); $j++) $sql .= "Null, ";
		$sql = substr($sql, 0, -2) . ");";
		//debug_log("INSERT SQL: {$sql}\n");
		$db_conn->execute($sql);
	  }
	  if ($ilidos++ % 5000 == 0) {
		if ($ldebug) {
		  wecho("\nLidas {$ilidos} linhas em ");
		  wecho(time() - $tempo_inicio . " segundos");
		} else wecho("*");
	  }
    }
	
	$db_conn->Close();
    unset($db_conn);
    wecho("\nGerado " . realpath(PR_TMP . "/{$this->nomarq}/{$planilha}.mdb"));
	  
  }
  
  public function abre_excel_aux($planilha, $titulo, $sql, $col_format = '', $cabec = '') {

	$linhas_titulo = 0;
  
//	if ($this->options['ldebug']) {
	  wecho("Gerando planilha do excel a partir de " . PR_TMP . "/{$this->nomarq}/{$planilha}.txt\n");
	  //wecho(time() - $tempo_inicio . " segundos\n");
//	} else wecho("*");

	$empty = new Variant(null);  // não está sendo usado... mas, se precisar, aqui está
	$this->excel->Workbooks->OpenText(PR_TMP . "/{$this->nomarq}/{$planilha}.txt", 2, 1, 1, 2,0,1,0,0,0,0 );  
	// http://msdn.microsoft.com/en-us/library/aa195814(v=office.11).aspx
	// , 2 -> Origin 		xlWindows = 2 ou codepage
	// , 1 -> StartRow		default value = 1
	// , 1 -> DataType		xlDelimited = 1		xlFixedWidth = 2
	// , 2 -> TextQualifier	xlTextQualifierNone = -4142		xlTextQualifierSingleQuote = 2	xlTextQualifierDoubleQuote = 1
	// , 0 -> ConsecutiveDelimiter	True to have consecutive delimiters considered one delimiter. The default is False
	// , 1 -> Tab			1 = True
	// , 0 -> Semicolon		0 = False
	// , 0 -> Comma			0 = False
	// , 0 -> Space			0 = False
	// , 0 -> Other			0 = False
	$sheet = $this->excel->ActiveSheet;

	// opções de formatação padrão
	$this->excel->ActiveSheet->PageSetup->PaperSize = 9; // xlPaperA4
	$this->excel_imprime_linhas_grade(True);
	$this->excel_margens(0.5, 0.3, 0.5, 0.3);

	if (is_array($col_format)) {
	  foreach($col_format as $indice => $valor) {
		$r = $sheet->Range($indice)->Columns; 
		$r->Cells->NumberFormat = $valor;
	  }
	}
	if (is_array($cabec)) {
	  $r = $sheet->Range("1:1")->Rows; 
	  $r->Cells->Insert(1);
	  $r = $sheet->Range("1:1")->Rows; 
	  $r->Cells->HorizontalAlignment = -4108;	// xlCenter
	  $r->Cells->VerticalAlignment = -4108;		// xlCenter
	  $r->Cells->WrapText = True;				// Quebra Texto Automaticamente
	  $i = 1;
	  foreach($cabec as $indice => $valor) {
		$cell=$sheet->Cells(1,$i++); 
		$cell->value = utf8_decode($indice);
		$cell->AddComment;
		$cell->Comment->Text(utf8_decode($valor));
		//if(strlen($valor) > 60) {
		  if(strlen($valor) < 200) {
			$cell->Comment->Shape->ScaleWidth(1.5, 0, 0);		// Duplica a largura do comentário
			$cell->Comment->Shape->ScaleHeight(4, 0, 0);	// altura do comentário
		  } else {
			if(strlen($valor) < 800) {
			  $cell->Comment->Shape->ScaleWidth(2, 0, 0);		// Duplica a largura do comentário
			  $cell->Comment->Shape->ScaleHeight(7, 0, 0);	// Altura do comentário 
			} else {
			  $cell->Comment->Shape->ScaleWidth(3.5, 0, 0);
			  $cell->Comment->Shape->ScaleHeight(10, 0, 0);
			}
		  }
		//}
	  }
	}
	$r = $sheet->Range("A:Z")->Columns; 
	$r->Cells->EntireColumn->Autofit;
	// Somente o Autofit não funciona 100%, dando erro em campos numéricos ao alterar o zoom. Por isso as linhas abaixo...
	$this->excel->ActiveCell->SpecialCells(11)->Select; // vai até a última linha, última coluna
	for ($ilarg = 1; $ilarg <= $this->excel->ActiveCell->Column; $ilarg++) {
	  // Aumenta a largura somente em 2 mm, para que não dê erros em colunas numéricas ao mudar o Zoom
	  $lar_col = $this->excel->ActiveSheet->Columns($ilarg)->ColumnWidth;
	  // O Excel não permite largura de coluna acima de 254
	  if ($lar_col < 253) $this->excel->ActiveSheet->Columns($ilarg)->ColumnWidth = $lar_col + 2; 
	}
	$this->excel->Range("A1:A1")->Select; // volta para a primeira linha, primeira coluna

	if ($titulo <> '') {
	  // O título pode incluir uma ou mais linhas do relatório gerado. Elas serão retiradas do seu lugar e inseridas
	  //  logo acima do cabeçalho. Para isso, basta inserir o seguinte ##+1## para uma linha, ##+4##, 4 linhas e assim por diante
	  $linhas_titulo_adicionais = 0;
	  $valores = explode('##', $titulo);
	  // $valores deve ter no mínimo 3 elementos... 0 -> antes de ##; 1 -> dentro de ## -> 2 -> depois de ##
	  if (isset($valores[2])) {
		// se o elemento 1 tiver mais do que 4 caracteres, não é formatação especial... então pula
		if (strlen($valores[1]) <= 4) {
		  $linhas_titulo_adicionais = str_replace('+', '', $valores[1]) + 0; // mais zero para transformar para inteiro
		  $titulo = $valores[0] . $valores[2]; // exclui os caracteres de formatação especial;
		  // se existir valores 3, 4, etc..., devem ser dados que não podem ser omitidos... então, vai completando 
		  $i = 3;
		  if (isset($valores[$i])) {
			$titulo .= '##' . $valores[$i];
			  $i++;
		  }
		}
	  }

	  $linhas_titulo++;
	  $r = $sheet->Range("1:1")->Rows; 
	  $r->Cells->Insert(1);
	  $cell=$sheet->Cells(1,1); 
	  $cell->value = utf8_decode($titulo);
	  $r = $sheet->Range("1:1")->Rows; 
	  $r->Cells->Font->Bold = True;
	  $r->Cells->Font->Size = 14;
	  $r->Cells->Interior->ColorIndex = 15;

	  // Arrumando o título com as linhas adicionais
	  if ($linhas_titulo_adicionais > 0) {
		$linhas_titulo += $linhas_titulo_adicionais;
		$this->excel->Rows("3:" . ($linhas_titulo_adicionais + 2))->Select;
		$this->excel->Selection->Cut;
		$this->excel->Rows("2:2")->Select;
		$this->excel->Selection->Insert(-4121); // menos 4121 é o valor de xlShiftDown
	  }
	}

	$linhas_aux = $linhas_titulo + 2;
	$sheet->Range("{$linhas_aux}:{$linhas_aux}")->Rows->Select(); 
	$this->excel->ActiveWindow->FreezePanes = 1;
	$this->excel->ActiveSheet->PageSetup->PrintTitleRows = "$1:$" . ($linhas_titulo + 1);


	$sheet->Range("A1:A1")->Select(); 
	$this->excel->Windows($planilha . '.txt')->Activate;
	$this->excel->Sheets($planilha)->Select;
	if ($this->ver_excel == '11.0') $this->excel->Sheets($planilha)->Move($this->excel->Workbooks($this->nomarq . '.xls')->Sheets(1));
		else $this->excel->Sheets($planilha)->Move($this->excel->Workbooks($this->nomarq . '.xlsx')->Sheets(1));

	// formatação especial -> array $form_espe -> ver explicação em function abre_excel_sql
	// wecho(print_r($this->form_espe, True)); // debug
	$offset_linhas = 0;
	foreach($this->form_espe as $indice => $valor) {

	  $linha = $valor[0] + 1 + $offset_linhas;		// a linha no excel começa no 1... 
	  if ($titulo <> '') $linha++;		// se tiver título, pula mais um
	  if($valor[0] + 2 > $linhas_titulo) $linha++;	// tem o cabeçalho também
	  $r = $this->excel->ActiveSheet->Range("{$linha}:{$linha}")->Rows; 
	  if (!(strpos($valor[2], 'N') === False)) $r->Cells->Font->Bold = True;
	  if (!(strpos($valor[2], 'I') === False)) $r->Cells->Font->Italic = True;
	  if (!(strpos($valor[2], 'T') === False)) $r->Cells->Interior->ColorIndex = 15;
	  if (!(strpos($valor[2], 'E') === False)) $r->Cells->Interior->ColorIndex = 48;
	  if (!(strpos($valor[2], 'D') === False)) $r->Cells->HorizontalAlignment = -4152; // xlRight
	  if (!(strpos($valor[2], 'Z') === False)) $r->Cells->HorizontalAlignment = -4108; // xlCenter
	  if (!(strpos($valor[2], 'A') === False)) {
		$i = $linha;
		$r = $this->excel->ActiveSheet->Range("{$i}:{$i}")->Rows; 
		$r->Cells->Insert(1);
		$j = $i + 1;
		$r = $this->excel->ActiveSheet->Range("{$j}:{$j}")->Rows; 
		$r->Cells->Interior->ColorIndex = 48;
		$offset_linhas++;
	  }
	  if (!(strpos($valor[2], 'C') === False)) {
		$i = $linha + 1;
		$r = $this->excel->ActiveSheet->Range("{$i}:{$i}")->Rows; 
		$r->Cells->Insert(1);
		$j = $i - 1;
		$r = $this->excel->ActiveSheet->Range("{$j}:{$j}")->Rows; 
		$r->Cells->Interior->ColorIndex = 48;
		$offset_linhas++;
	  }
	  if (!(strpos($valor[2], 'F') === False)) $r->Cells->Font->Size = substr($valor[2], strpos($valor[2], 'F') + 1, 2) + 0; // mais zero para converter a inteiro
	  $cell=$this->excel->ActiveSheet->Cells($linha, $valor[1] + 1);
	  if (!(strpos($valor[2], 'n') === False)) $cell->Font->Bold = True;
	  if (!(strpos($valor[2], 't') === False)) $cell->Interior->ColorIndex = 15;
	  if (!(strpos($valor[2], 'e') === False)) $cell->Interior->ColorIndex = 48;
	  if (!(strpos($valor[2], 'c') === False)) $cell->Interior->ColorIndex = 48;
	  if (!(strpos($valor[2], 'a') === False)) $cell->Interior->ColorIndex = 48;
	  if (!(strpos($valor[2], 'd') === False)) $cell->HorizontalAlignment = -4152; // xlRight
	  if (!(strpos($valor[2], 'z') === False)) $cell->HorizontalAlignment = -4108; // xlCenter
	  if (!(strpos($valor[2], 'f') === False)) $cell->Font->Size = substr($valor[2], strpos($valor[2], 'f') + 1, 2) + 0; // mais zero para converter a inteiro

	}
  }


  public function abre_html_aux($planilha, $titulo, $sql, $col_format = '', $cabec = '') {

	$linhas_titulo = 0;
  
	if ($this->options['ldebug']) {
	  wecho("Gerando arquivo html a partir de " . PR_TMP . "/{$this->nomarq}/{$planilha}.txt \n");
	  //wecho(time() - $tempo_inicio . " segundos\n");
	} else wecho("*");

	if (file_exists(PR_TMP . "/{$this->nomarq}/{$planilha}.html")) unlink(PR_TMP . "/{$this->nomarq}/{$planilha}.html"); 
	if (!$handlew = fopen(PR_TMP . "/{$this->nomarq}/{$planilha}.html", 'w')) {
	  werro_die("Nao foi possivel a gravacao do arquivo " . PR_TMP . "/{$planilha}.html - Feche o programa ou janela que está o usando<br><br>");
	}

	if (!$handle = fopen(PR_TMP . "/{$this->nomarq}/{$planilha}.txt", 'r')) {
	  werro_die("Nao foi possivel a leitura do arquivo " . PR_TMP . "/{$planilha}.txt - Erro do sistema de arquivos ?<br><br>");
	}

	$htmlinicio = <<<EOD
<html>
<head>
 <meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
<title>Auditor NF-e - DRT/13 - Guarulhos {$titulo}</title>
<style>
TH		{FONT-FAMILY: Verdana,Helvetica; FONT-SIZE: 11px}
TD		{FONT-FAMILY: Verdana,Helvetica; FONT-SIZE: 11px}
</style>
</head>
<body bgcolor="#ffffff">
<font face=arial>
<center><b>{$titulo}</b></center><br>
<table border="0" align="center" >
EOD;

	if (is_array($cabec)) {
	  $htmlinicio .=  "\n<tr bgcolor='#dddddd'>";
	  foreach($cabec as $indice => $valor) {
		$indice = htmlentities($indice);
		$valor = htmlentities($valor);
	    $htmlinicio .= "<td title='{$valor}' align='center'>{$indice}</td>";
	  }
	  $htmlinicio .=  "</tr>\n";
	}

	fputs($handlew, $htmlinicio);

	// Transforma a estrutura de $this->form_espe para uma mais adequada à exportação .html, chamada aqui de $form_html
	$form_html = array();	// Este terá a estrutura [linha][coluna]
	foreach($this->form_espe as $indf => $valf) {
	  $form_html[$this->form_espe[$indf][0]][$this->form_espe[$indf][1]] = $this->form_espe[$indf][2];
	}
	// wecho("\n##\n" . print_r($form_html, True) . "\n##\n"); // debug
	$ilinhas_html = 0;
	$html_linha_final = '';
	while(!feof($handle)) {
	  $campos = fgetcsv($handle, 1500, "\t");
	  if (is_array($campos)) {
		$ilinhas_html++;
		//
		//  TODO
		//		Verificar as formatações por campo, em minúsculas
		//		Verificar saltos de linhas
		//		Formatar colunas ( array $col_format )
		//		Formatação de tamanho de fontes
		//
		// verifica se há formatações de linha inteira
		$line_style = '';
		if (isset($form_html[$ilinhas_html - 1])) {
		  foreach($form_html[$ilinhas_html - 1] as $indl => $vall) {
			if (!(strpos($vall, 'N') === False)) $line_style .= "font-weight:bold;";
			if (!(strpos($vall, 'T') === False)) $line_style .= "background-color:lightgrey;";
			if (!(strpos($vall, 'E') === False)) $line_style .= "background-color:lightslategrey;";
			if (!(strpos($vall, 'A') === False)) $line_style .= "background-color:lightslategrey;";
			if (!(strpos($vall, 'C') === False)) $line_style .= "background-color:lightslategrey;";
			if (!(strpos($vall, 'D') === False)) $line_style .= "text-align:right;";
			if (!(strpos($vall, 'Z') === False)) $line_style .= "text-align:center;";
		  }
		}
		if ($line_style <> '') fputs($handlew, '<tr style="' . $line_style . '>'); 
		else if ($ilinhas_html %2 == 0) fputs($handlew, '<tr bgcolor="#e0e0ff">'); else fputs($handlew, '<tr bgcolor="#e8e8ff">');
		if ($ilinhas_html % $this->options['limit_html'] == 0) {
		  // gera página seguinte do arquivo html
		  $pag_html = floor($ilinhas_html / $this->options['limit_html']) + 1;
		  if ($pag_html > $this->options['limit_arqs_html']) {
			fputs($handlew, "</tr>\n");
			if ($pag_html > 2) $html_linha_final .= "<a href='{$planilha}.html'>Primeira Página</a>&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($pag_html > 3) $html_linha_final .= "<a href='{$planilha}_p" . ($pag_html - 2) . ".html'>Página Anterior (número " . ($pag_html - 2) . ")</a>&nbsp;&nbsp;&nbsp;&nbsp;";
			$html_linha_final .= "<br>Observação: Ainda há mais resultados, mas a consulta fica limitada até aqui, conforme limite máximo de número de arquivos .html definido em Opções...\n";
			break; // sai fora do while, parando de gerar arquivos .html se passou o limite máximo de número de arquivos
		  }
		  fputs($handlew, "</table>");
		  if ($pag_html > 2) fputs($handlew, "<a href='{$planilha}.html'>Primeira Página</a>&nbsp;&nbsp;&nbsp;&nbsp;");
		  if ($pag_html > 3) fputs($handlew, "<a href='{$planilha}_p" . ($pag_html - 2) . ".html'>Página Anterior (número " . ($pag_html - 2) . ")</a>&nbsp;&nbsp;&nbsp;&nbsp;");
		  fputs($handlew, "<a href='{$planilha}_p{$pag_html}.html'>Próxima Página (número {$pag_html})</a>");
		  fputs($handlew, "</font></body></html>");
		  fclose($handlew);
		  if (!$handlew = fopen(PR_TMP . "/{$this->nomarq}/{$planilha}_p{$pag_html}.html", 'w')) {
			werro_die("Nao foi possivel a gravacao do arquivo " . PR_TMP . "/{$planilha}.html - Feche o programa ou janela que está o usando<br><br>");
		  }
		  fputs($handlew, $htmlinicio);
		  if ($ilinhas_html++ %2 == 0) fputs($handlew, '<tr bgcolor="#e0e0ff">'); else fputs($handlew, '<tr bgcolor="#e8e8ff">');
		}
		foreach($campos as $key => $value) {
		  $value = htmlentities($value);
		  fputs($handlew, "<td align='center'>{$value}</td>");
		}
		fputs($handlew, "</tr>\n");
		if ($ilinhas_html % 11000 == 0)  fputs($handlew, '</table><table border="0" align="center" >'); // para acelerar o carregamento no Browser
	  }
	}
	fputs($handlew, "</table>{$html_linha_final}</font></body></html>");
	fclose($handle);
	fclose($handlew);

	

  }

  // Seção Formatação de Excel
  
  public function excel_margens($left, $right, $header, $footer, $top = '', $bottom = '') {
	// excel_margens($left, $right, $header, $footer, [$top], [$bottom])
	//	define as margens da folha, em polegadas. Valores sugeridos: margens_excel(0.49, 0.26, 0.33, 0.26);
	//	$top e $bottom são opcionais, usados para dar espaço ao cabeçalho e rodapé. Se não for definido, serão iguais a $header e $footer
	if ($top    == '') $top    = $header;
	if ($bottom == '') $bottom = $footer;
	$this->excel->ActiveSheet->PageSetup->LeftMargin   = $this->excel->Application->InchesToPoints($left);
	$this->excel->ActiveSheet->PageSetup->RightMargin  = $this->excel->Application->InchesToPoints($right);
	$this->excel->ActiveSheet->PageSetup->HeaderMargin = $this->excel->Application->InchesToPoints($header);
	$this->excel->ActiveSheet->PageSetup->FooterMargin = $this->excel->Application->InchesToPoints($footer);
	$this->excel->ActiveSheet->PageSetup->TopMargin    = $this->excel->Application->InchesToPoints($top);
	$this->excel->ActiveSheet->PageSetup->BottomMargin = $this->excel->Application->InchesToPoints($bottom);
  }

  public function excel_imprime_linhas_grade($logico) {
	// excel_imprime_linhas_grade($logico)
	//	define se deve ser impressa linha de grade, podendo ser True ou False
	if (!($logico == True || $logico == False)) $logico = True;
	$this->excel->ActiveSheet->PageSetup->PrintGridlines = $logico; 
  }

  public function excel_orientacao($tipo) {
	// excel_orientacao($tipo)
	//	define a orientação da folha, podendo ser: 1 - retrato (padrão)  2 - paisagem
	if (!($tipo == 1 || $tipo == 2)) $tipo = 1;
	$this->excel->ActiveSheet->PageSetup->Orientation = $tipo; // Retrato - Landscape
  }

  public function excel_zoom($zoom) {
	// excel_zoom($zoom)
	//	define o ajuste de zoom para a impressão
	if (!is_int($zoom)) $zoom = 100;
	$this->excel->ActiveSheet->PageSetup->Zoom = $zoom;
  }

  public function excel_ultima_linha() {
	// excel_ultima_linha()
	//	retorna o número da última linha atuamente ocupada com dados
	$this->excel->ActiveCell->SpecialCells(11)->Select; // vai até a última linha, última coluna
	$linha_final = $this->excel->ActiveCell->Row;
	$this->excel->Range("A1:A1")->Select; // volta para a primeira linha, primeira coluna
	return $linha_final;
  }

  public function excel_ajuste_pagina($largura, $altura = '') {
	// excel_ajuste_pagina($largura, $altura)
	//	define o zoom automaticamente para a planilha caber em $largura páginas de largura e $altura páginas de altura
	//	altura é opcional. Se não for definido, calcula a altura automaticamente
	if ($altura == '') {
	  $orienta = $this->excel->ActiveSheet->PageSetup->Orientation; // Retrato - 1   Paisagem - 2
	  $linha_final = $this->excel_ultima_linha();
	  $altura = floor($orienta == 1 ? $linha_final / 60 : $linha_final / 40) + 1;
	}
	// echo "Altura={$altura} Orienta ={$orienta} Linha_final={$linha_final}\n"; //debug
	$this->excel->ActiveSheet->PageSetup->Zoom = False;
	$this->excel->ActiveSheet->PageSetup->FitToPagesWide = $largura;
	$this->excel->ActiveSheet->PageSetup->FitToPagesTall = $altura;
  } 

  public function excel_largura_coluna($range, $largura) {
	// excel_largura_coluna($range, $largura)
	//	define a largura de uma ou mais colunas. Exemplo: excel_largura_coluna("A:A", 12);
	$r = $this->excel->ActiveSheet->Range($range)->Columns; 
	$r->Cells->ColumnWidth = $largura;
  }

  public function excel_zoom_visualizacao($porcentagem) {
	// excel_zoom_visualizacao($porcentagem)
	//	Define a porcentagem de zoom para visualização. O default é 100 (cem por cento). 
	// Não insira porcentagem na função - o excel já sabe que é percentual.
	// exemplo de utilização correta, para 75%: $pr->excel_zoom_visualizacao(75);  
	$this->excel->ActiveWindow->Zoom = $porcentagem; 
  }

  public function excel_coluna_comentario($nro_coluna, $linha_inicial) {
	// excel_coluna_comentario($nro_coluna, $linha_inicial);
	// 	cria comentário na coluna, com os dados do valor de cada célula. Exemplo : excel_coluna_comentario(1, 3);
	// atenção... a linha de cabeçalho já tem comentário... se tentar colocar novamente, dá erro. começar da linha 2 ou 3, se houver título !
	// atenção2 - o primeiro parâmetro é o número da coluna, não o nome
	$linha_final = $this->excel_ultima_linha();
	for($i=$linha_inicial;$i<=$linha_final;$i++) {
	  $cell=$this->excel->ActiveSheet->Cells($i,$nro_coluna);
	  $valor = $cell->value;
	  if($valor != "") {
		$cell->AddComment;
		$cell->Comment->Text($valor);
		if(strlen($valor) > 80) {
		  if(strlen($valor) < 200) {
			$cell->Comment->Shape->ScaleWidth(1.5, 0, 0);		// Duplica a largura do comentário
			$cell->Comment->Shape->ScaleHeight(1.5, 0, 0);	// 50% maior a altura do comentário
		  } else {
			if(strlen($valor) < 800) {
			  $cell->Comment->Shape->ScaleWidth(2, 0, 0);		// Duplica a largura do comentário
			  $cell->Comment->Shape->ScaleHeight(4, 0, 0);	// Quadruplica a altura do comentário
			} else {
			  $cell->Comment->Shape->ScaleWidth(3.5, 0, 0);
			  $cell->Comment->Shape->ScaleHeight(7, 0, 0);
			}
		  }
		}
	  }
	} 
  }

  public function excel_grade_externa($range) {
	// cria uma grade_externa de linha grossa em volta de $range (exemplo: "B2:C4")
	$this->excel->Range($range)->Select;
	$this->excel->Selection->Borders(5)->LineStyle = -4142;   	// 5 = xlDiagonalDown		-4142 = xlNone
	$this->excel->Selection->Borders(6)->LineStyle = -4142;   	// 6 = xlDiagonalUp			-4142 = xlNone
	$this->excel->Selection->Borders(7)->LineStyle = 1;       	// 7 = xlEdgeLeft			1 = xlContinuous
	$this->excel->Selection->Borders(7)->Weight = -4138;      	// 7 = xlEdgeLeft			-4138 = xlMedium
	$this->excel->Selection->Borders(7)->ColorIndex = -4105;  	// 7 = xlEdgeLeft			-4105 = xlAutomatic
	$this->excel->Selection->Borders(8)->LineStyle = 1;       	// 8 = xlEdgeTop			1 = xlContinuous
	$this->excel->Selection->Borders(8)->Weight = -4138;      	// 8 = xlEdgeTop			-4138 = xlMedium
	$this->excel->Selection->Borders(8)->ColorIndex = -4105;  	// 8 = xlEdgeTop			-4105 = xlAutomatic
	$this->excel->Selection->Borders(9)->LineStyle = 1;       	// 9 = xlEdgeBottom			1 = xlContinuous
	$this->excel->Selection->Borders(9)->Weight = -4138;      	// 9 = xlEdgeBottom			-4138 = xlMedium
	$this->excel->Selection->Borders(9)->ColorIndex = -4105;  	// 9 = xlEdgeBottom			-4105 = xlAutomatic
	$this->excel->Selection->Borders(10)->LineStyle = 1;      	// 10 = xlEdgeRight			1 = xlContinuous
	$this->excel->Selection->Borders(10)->Weight = -4138;     	// 10 = xlEdgeRight			-4138 = xlMedium
	$this->excel->Selection->Borders(10)->ColorIndex = -4105; 	// 10 = xlEdgeRight			-4105 = xlAutomatic
	$this->excel->Selection->Borders(11)->LineStyle = -4142;  	// 5 = xlInsideVertical		-4142 = xlNone
	$this->excel->Selection->Borders(12)->LineStyle = -4142;  	// 6 = xlInsideHorizontal	-4142 = xlNone

	$this->excel->Range("A1:A1")->Select; // volta para a primeira linha, primeira coluna
  }
  
}


class PrMenu {
  // Registra uma opção no menu auditoria
  // $callback -> nome da função a ser chamada no caso de escolha do item no menu
  // $menu -> nome do item no menu
  // $submenu -> nome do item no submenu
  // $use (opcional)-> bancos de dados necessários	Exemplo: "p32, nfe, gia" -> Abre p32 e attach nfe e gia
  public $callback, $menu, $submenu, $use;

  function __construct( $callback, $menu, $submenu, $use = '') {
	$this->callback = $callback;
	$this->menu 	= $menu;
	// o submenu é "aperfeiçoado" automaticamente com os bancos de dados que ele usa, entre colchetes
	$this->submenu = '[' . str_replace(',', '][', $use) . '] ' . $submenu;
	// não coloca no submenu os bancos de dados [ies] e [gia5156]
	$this->submenu = str_replace('[ies]', '', $this->submenu);
	$this->submenu = str_replace('[gia5156]', '', $this->submenu);
	$this->use		= $use;
  }
}


?>