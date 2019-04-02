<?php
// Splash Screen
$windowsplash = new GtkWindow();
$windowsplash->set_position(Gtk::WIN_POS_CENTER);
$windowsplash->set_default_size(300, 100);
$windowsplash->connect_simple('destroy', array('Gtk','main_quit'));
$labelsplash = new GtkLabel("Abrindo Conversor3 ! Aguarde...");
$windowsplash->add($labelsplash);
$windowsplash->set_decorated(false);
$windowsplash->show_all();
while (Gtk::events_pending()) {  // redraw de splash screen
	Gtk::main_iteration();
}

require __DIR__ . '/lib/base.inc.php';
require __DIR__ . '/lib/classes.inc.php';
$pr = new Pr;	// classe principal, global

clean_prError();															// em base.inc.php
error_warning_logs(PR_LOG . '/LeituraError.log', PR_LOG . '/LeituraWarning.log'); 	// em base.inc.php


$aviso_inicial = "";
if (isset($argv[1]) && strtolower($argv[1]) == 'console') {
  $aviso_inicial .= "\nExecutando em modo Console !!\n\n\n";
  $pr->options['console'] = True;
} else {
  error_reporting(E_ERROR);
  $pr->options['console'] = False;
}

if  ((isset($argv[1]) && strtolower($argv[1]) == 'reinicializa') || (isset($argv[2]) && strtolower($argv[2]) == 'reinicializa')) {
  $pr->options['reinicializa'] = True;
} else {
  $pr->options['reinicializa'] = False;
}

if ($pr->options['ldebug']) {
	// algumas auditorias internas
	$pr->aud_abre_db_e_attach('common');
	$resultados = $pr->aud_sql2array("Select count(*) AS linhas from cadesp;");
	$aviso_inicial .= "Quantidade de registros em common.db3 tabela cadesp: " . $resultados[0]['linhas'] . "\r\n\r\n";
	$pr->db->close();
}

// Verificação de travamento anterior
// Motivo 1 (por enquanto, a única verificação...)
if (file_exists(PR_LOG . '/gerando_excel.log')) {

  $dialog = new GtkMessageDialog($wnd, Gtk::DIALOG_MODAL,
            Gtk::MESSAGE_WARNING, Gtk::BUTTONS_YES_NO, 
			"** Atenção **
Tudo indica que na última vez em que foi executado, o Conversor apresentou erro e travou inesperadamente durante a geração de um arquivo Excel...
Por isso, muito provavelmente há uma 'instância' aberta do programa Excel, mas em modo invisível, ou seja, não aparece na barra inferior do Windows.
Assim, por haver haver janelas do Excel escondidas, travadas, é necessário fechá-las.
Se você clicar no botão Sim abaixo, Forçará o Encerramento de TODAS as janelas Excel abertas, inclusive as que estão em modo invisível.
Por isso, ANTES DE CLICAR NO BOTÃO, salve todas as planilhas do Excel abertas antes...
Se você clicar em Não e o problema persistir, poderá executar o procedimento posteriormente, na opção 'Forçar encerramento de todas as janelas do Excel' do menu Utilitários."
			);
  $answer = $dialog->run();
  $dialog->destroy();

  if ($answer == Gtk::RESPONSE_YES) {
    $shell = new COM('WScript.Shell');
    $shell->Run(PR_PATH . '/src/cv3/_KillExcel.vbs');
    unset($shell);
	$aviso_inicial .= "\n\n\n     **** Enviado sinal de encerramento para todas as janelas Excel ! ****\n\n\n";
	if (file_exists(PR_LOG . '/gerando_excel.log')) unlink(PR_LOG . '/gerando_excel.log');
  }

}

if (file_exists('xtras/ArqDiet.php')) {
  include 'xtras/ArqDiet.php';
}
//if (file_exists('xtras/JuntaPDFs.php')) {
//  include 'xtras/JuntaPDFs.php';
//}
if (file_exists('xtras/ConsultasWeb.php')) {
  include 'xtras/ConsultasWeb.php';
}
//if (file_exists('../_xtras/LeNFe.php')) {
//  include '../_xtras/LeNFe.php';
//}

// Auditorias 
$aud_params = array(); // parâmetros que serão preenchidos com a função aud_registra, dentro dos arquivos .php em audit

$audits = opendir(__DIR__ . '/audit');
while(($file = readdir($audits)) !== false) {
  if (strtolower(substr($file, -4)) == ".php" && ($file <> 'desenvAud.php')) include __DIR__ . '/audit/' . $file;
}
//if (is_dir('../_xtras/audit')) {
//  $audits = opendir('../_xtras/audit');
//  while(($file = readdir($audits)) !== false) {
//	if (strtolower(substr($file, -4)) == ".php" && ($file <> 'desenvAud.php')) include '../_xtras/audit/' . $file;
//  }
//}

$pr->zera_tempo();

if (!class_exists('gtk')) {
	werro_die("Erro - Não foi possível carregar o GTK\r\n");
}

// Construção da Janela Principal
$wnd = new GtkWindow();
$wnd->set_title('Conversor3');
$wnd->set_default_size(1000, 650);
$wnd->set_position(Gtk::WIN_POS_CENTER);
$wnd->connect_simple('destroy', array('gtk', 'main_quit'));	// Fechando o X superior direito, chama main_quit do GTK
 
$lblHello 	= new GtkLabel("Conversor3 - Versão " . versao());
$lblHello->modify_font(new PangoFontDescription('Arial Bold 10'));
$lblHello2 	= new GtkLabel("Tipos de Bancos de Dados disponíveis: " . $pr->carrega_db_disponiveis());
$lblHello2->modify_font(new PangoFontDescription('Arial 9'));
$vbox_hello = new GtkVBox();
$hbox_hello = new GtkHBox();
$vbox_hello->pack_start($lblHello, false, false, 3);
$vbox_hello->pack_start($lblHello2, false, false, 3);
$hbox_hello->pack_start($vbox_hello, true, true, 3);

$textBuffer = new GtkTextBuffer();
$scrolledwindow = new GtkScrolledWindow();
$view1 = new GtkTextView();
$view1->set_wrap_mode(Gtk::WRAP_WORD_CHAR);
$view1->drag_dest_set(Gtk::DEST_DEFAULT_ALL, array( array( 'text/uri-list', 0, 0)), Gdk::ACTION_COPY); 
$view1->connect('drag-data-received', 'on_drop_view1', $view); // note 1 
$scrolledwindow->viewer = $view1;
$scrolledwindow->set_policy(Gtk::POLICY_NEVER,Gtk::POLICY_ALWAYS); 
 
// Add some text to the buffer.
$textBuffer->set_text($aviso_inicial . "Clique no botão 'Inicia Conversão' para converter os arquivos presentes na pasta Fontes para o formato SQL.\nApós o final da conversão, gere arquivos Excel a partir do menu 'Auditorias'.\n\nArrastando arquivos para esta janela automaticamente os copia para a pasta Fontes.

Fontes atuais que geram auditoria:
03 - Cv2_GIA_NFe v0.12 NFe_Emit, Dest, Dest_UFs, Danfe_Emit, Danfe_Dest, Danfe_UFs .txt
03 - CTe_CNPJ_Emitente.txt (arrume a o cabeçalho para ficar apenas em uma linha)
05 - Cv2_Cadesp v0.2.txt
SPED_EFD
LADCA (Crédito Acumulado - Custeio)
CAT 17/99
Ressarcimento SPED (Usar as NFes de 03 - Cv2_NFe_Ressarc Nfe_...)
CAT 42/18

No mais, pode ser colocado qualquer arquivo texto, qualquer tamanho, separado por TABs ou .csv, sendo que a primeira linha deve ser cabeçalho (banco de dados TXT)
\n\n");

// Add the buffer to the view and make sure no one edits the text.
$scrolledwindow->viewer->set_buffer($textBuffer);
$scrolledwindow->viewer->set_editable(false);
$scrolledwindow->add($scrolledwindow->viewer); 

$btnOk		= new GtkButton('Inicia _Conversão');
$btnCancel	= new GtkButton('_Sair !');
$btnDB3s	= new GtkButton('_Db3s e Tmp');
$btnModelos	= new GtkButton('_Modelos');
$btnFontes	= new GtkButton('_Fontes');
$btnResult	= new GtkButton('_Resultados');
$btnAjuda	= new GtkButton('_Ajuda');
$btnOptions	= new GtkButton('_Opções');
//Add the buttons to a button box
$bbox = new GtkHButtonBox();
$bbox->set_layout(Gtk::BUTTONBOX_SPREAD);
$bbox->set_spacing(2);
$bbox->add($btnAjuda);
$bbox->add($btnFontes);
$bbox->add($btnResult);
$bbox->add($btnDB3s);
$bbox->add($btnModelos);
$bbox->add($btnOptions);
$bbox->add($btnOk);
$bbox->add($btnCancel);

$btnCancel->connect_simple('clicked', array($wnd, 'destroy'));
$btnOk->connect_simple('clicked', 'clickok', $wnd);
$btnAjuda->connect_simple('clicked', 'clickAjuda', $wnd);
$btnFontes->connect_simple('clicked', 'clickFontes', $wnd);
$btnResult->connect_simple('clicked', 'clickResult', $wnd);
$btnDB3s->connect_simple('clicked', 'clickDb3_Tmp', $wnd);
$btnModelos->connect_simple('clicked', 'clickModelos', $wnd);
$btnOptions->connect_simple('clicked', 'clickOptions', $wnd);

$vbox = new GtkVBox();
setup_menu($vbox);
$vbox->pack_start($hbox_hello, false, false, 3);
$vbox->pack_start(new GtkHSeparator(), false, false, 3);
$vbox->pack_start($scrolledwindow);
$vbox->pack_start($bbox, false, false, 3);
 
//Add the vbox to the window
$wnd->add($vbox);

$windowsplash->hide();
$wnd->show_all();

if ($pr->options['reinicializa'] == True) {
  while (Gtk::events_pending()) {  // desenha o window normalmente, mas não para...
	Gtk::main_iteration();
  }
  principal();
}

Gtk::main();

exit;


function clickNovo(GtkWindow $wnd)
{
  global $pr;

  $mensagem = "Tem certeza que deseja apagar todos os arquivos da pasta Fontes e da pasta Resultados ?";
  $dialog = new GtkMessageDialog($wnd, Gtk::DIALOG_MODAL,
            Gtk::MESSAGE_WARNING, Gtk::BUTTONS_YES_NO, $mensagem);
  $answer = $dialog->run();
  $dialog->destroy();

  if ($answer == Gtk::RESPONSE_YES) {
	wecho("\n\nApagando todos os arquivos da pasta Fontes e da pasta Resultados... Aguarde...\n");
	recursiveDelete(PR_FONTES);
	if (is_dir(PR_FONTES)) 
	  werro("Falha ao apagar a pasta Fontes... Possivelmente há arquivos abertos nessa pasta...\n\n");
	else mkdir(PR_FONTES);

	recursiveDelete(PR_RESULTADOS);
	if (is_dir(PR_RESULTADOS)) 
	  werro("Falha ao apagar a pasta Resultados... Possivelmente há arquivos abertos nessa pasta...\n\n");
	else mkdir(PR_RESULTADOS);
   
	// Talvez o código abaixo seja repetitivo... é que ocorreram alguns erros ao clicar em Novo. Então, lá vai a criação mais uma vez...
	if (!is_dir(PR_FONTES)) 
	 if (!mkdir(PR_FONTES)) 
		werro_die('Pasta Fontes faltando. E também não foi possível criá-la. Está protegida contra gravação ?');
	if (!is_dir(PR_RESULTADOS)) 
	  if (!mkdir(PR_RESULTADOS)) 
		werro_die('Pasta Resultados faltando. E também não foi possível criá-la. Está protegida contra gravação ?');

	wecho("\nFinalizado !\n");

  }
}


function clickok(GtkWindow $wnd)
{
	reinicializa();
}

function clickAjuda(GtkWindow $wnd) {
    $shell = new COM('WScript.Shell');
    $shell->Run('"' . str_replace('/', '\\', PR_RES) . '\ajuda\Manual do Usuario.docx"');
    unset($shell);
}

function clickSobre(GtkWindow $wnd) {

    $dialog = new GtkAboutDialog();

    $dialog->set_name('Conversor3');
    $dialog->set_version(versao());	// Em base.inc.php

	$dialog->set_comments("email de suporte:\npaulomarote@hotmail.com"); 
	$dialog->set_copyright("Origem: Grupo de Estudos NFe/SPED - DRT/13 - Guarulhos\nAutor: AFR Paulo Marote\n\n"); 
	$dialog->set_license("Licenciamento - Esta ferramenta foi desenvolvida com base em softwares livres, de domínio público, podendo ser usada sem restrições. A base utilizada é a seguinte:\n- PHP (http://www.php.net/)\n- GTK (http://www.gtk.org/)\n- SQlite (http://www.sqlite.org/)\n\n"); 
	$dialog->set_authors(array("AFR Paulo Marote"));

    $dialog->run();
    $dialog->destroy();
}

function clickDesenv_aud(GtkWindow $wnd) {
  $shell = new COM('WScript.Shell');
  $shell->Run('php-win xtras/DesenvAud.php');
  unset($shell);
}

function clickSublimeText(GtkWindow $wnd) {
  $shell = new COM('WScript.Shell');
  $shell->Run(str_replace('/', '\\', PR_USR) . '\Sublime\sublime_text.exe');
  unset($shell);
}


function clickNotepadPlus(GtkWindow $wnd) {
  $shell = new COM('WScript.Shell');
  $shell->Run(str_replace('/', '\\', PR_USR) . '\npp\Notepad++Portable.exe');
  unset($shell);
}

function clickVisualizaLogs(GtkWindow $wnd) {
  
  global $textBufferVisualizaLogs; 	// para poder alterar na próxima função, clickVisualizaLogsComboChanged($combobox)
  
  $dialog = new GtkDialog('Logs (conforme pasta ' . PR_LOG . '):', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(1000, 500);

  $arqs_log = listdir(PR_LOG);
  $hboxcombo = new GtkHBox();
  $labelcombo = new GtkLabel('Selecione o Arquivos de Log desejado:');
  $combobox = new GtkComboBox();
  if (defined("GObject::TYPE_STRING")) {
	$model = new GtkListStore(GObject::TYPE_STRING);
  } else {
	$model = new GtkListStore(Gtk::TYPE_STRING);
  }
  $combobox->set_model($model);
  $cellrenderer = new GtkCellRendererText();
  $combobox->pack_start($cellrenderer);
  $combobox->set_attributes($cellrenderer, 'text', 0);
  $indcombo = 0;
  $comboativou = False;
  foreach($arqs_log as $inda => $vala) {
	$model->append(array('_sistema' . substr($vala, 1)));
	if (substr($vala, -11) == 'prError.log') {
	  $combobox->set_active($indcombo);
	  $comboativou = True;
	}
	$indcombo++;
  }
  if (! $comboativou) $combobox->set_active(0);
  $combobox->connect('changed', 'clickVisualizaLogsComboChanged');
  $hboxcombo->pack_start($labelcombo, false, false, 3);
  $hboxcombo->pack_start($combobox, false, false, 3);
  
  $textBufferVisualizaLogs = new GtkTextBuffer();
  $scrolledwindow = new GtkScrolledWindow();
  $textView = new GtkTextView();
  $textView->set_editable(false);
  $textView->modify_font(new PangoFontDescription('Courier New 8'));
  $scrolledwindow->viewer = $textView;
  $scrolledwindow->set_policy(Gtk::POLICY_AUTOMATIC,Gtk::POLICY_ALWAYS);
  $selection = str_replace('_sistema/', './', $combobox->get_active_text());
  if (file_exists($selection)) $textBufferVisualizaLogs->set_text(file_get_contents($selection));
  else $textBufferVisualizaLogs->set_text("Arquivo de Log {$selection} não encontrado...");
  $scrolledwindow->viewer->set_buffer($textBufferVisualizaLogs);
  $scrolledwindow->add($scrolledwindow->viewer); 

  $dialog->vbox->pack_start($hboxcombo, false, false, 3);
  $dialog->vbox->pack_start(new GtkHSeparator(), false, false, 3);
  $dialog->vbox->pack_start($scrolledwindow, true, true, 3);
  $dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);

  $dialog->set_has_separator(True);
  $dialog->show_all();
  $dialog->run();
  $dialog->destroy();

}

function clickVisualizaLogsComboChanged($combobox) {

  global $textBufferVisualizaLogs; 	// definido na função clickVisualizaLogs
  
  $model = $combobox->get_model();
  $selection = str_replace('_sistema/', './', $combobox->get_active_text());
  $textBufferVisualizaLogs->set_text(file_get_contents($selection));
}

function clickKillExcel(GtkWindow $wnd) {
  $dialog = new GtkMessageDialog($wnd, Gtk::DIALOG_MODAL,
            Gtk::MESSAGE_WARNING, Gtk::BUTTONS_YES_NO, 
			"Esta opção tem a finalidade de resolver problemas de travamento repentino do Conversor em virtude de haver janelas do Excel escondidas, travadas. Tem certeza que deseja Forçar o Encerramento de TODAS as janelas EXCEL abertas, inclusive as que estão em modo invisível ? Salve todas as planilhas abertas antes..."
			);
  $answer = $dialog->run();
  $dialog->destroy();

  if ($answer == Gtk::RESPONSE_YES) {
    $shell = new COM('WScript.Shell');
    $shell->Run(__DIR__ . '_KillExcel.vbs');
    unset($shell);
	wecho("\n\n\nEnviado sinal de encerramento para todas as janelas Excel !\n");
  }
}  

function clickFontes(GtkWindow $wnd) {
	wecho("\nPodem ser colocados os seguintes arquivos em Fontes:
NFes (.xml)
Arquivos .txt
SPED Fiscal
SPED Contábil
Sintegra (Portaria Cat 32/96)
Ressarcimento (Portaria Cat 17/99)
Tem mais coisas também...
");
    $shell = new COM('WScript.Shell');
    $shell->Run('explorer "' . str_replace('/', '\\', PR_FONTES) . '"');
    unset($shell);
}

function clickResult(GtkWindow $wnd) {
    $shell = new COM('WScript.Shell');
    $shell->Run('explorer "' . str_replace('/', '\\', PR_RESULTADOS) . '"');
    unset($shell);
}

function clickDb3_Tmp(GtkWindow $wnd) {
  $shell = new COM('WScript.Shell');
  $shell->Run('explorer "' . str_replace('/', '\\', PR_TMP) . '"');
  unset($shell);
}

function clickModelos(GtkWindow $wnd) {
  $shell = new COM('WScript.Shell');
  $shell->Run('explorer "' . str_replace('/', '\\', PR_RES) . '\rep"');
  unset($shell);
}

function clickTabelas(GtkWindow $wnd) {
  $shell = new COM('WScript.Shell');
  $shell->Run('explorer "' . str_replace('/', '\\', PR_RES) . '\tabelas"');
  unset($shell);
}

function clickSQLiteMan(GtkWindow $wnd) {
  $shell = new COM('WScript.Shell');
  $shell->CurrentDirectory = PR_TMP;
  $fh = opendir('.');
  //if(($file = readdir($fh)) === false) $file = '';
  //if($file == '.') { if(($file = readdir($fh)) === false) $file = ''; }
  //if($file == '..') { if(($file = readdir($fh)) === false) $file = ''; }
  //if($file == 'cad.db3') { if(($file = readdir($fh)) === false) $file = 'cad.db3'; }
  //$shell->Run(PR_USR . '\Sqliteman-1.2.2\sqliteman.exe ' . $file);
  $shell->Run(str_replace('/', '\\', PR_USR) . '\Sqliteman-1.2.2\sqliteman.exe');
  usleep(1); // Será que isto garante que o SQLiteMan vai abrir mesmo com o CurrentDirectory db3 ?
  $shell->CurrentDirectory = PR_PATH;
  unset($shell);
}

function clickPonto_para_Virgula_Clipboard(GtkWindow $wnd) {
//	wecho("\nEfetuando a conversão do conteúdo numerico do csv do clipboard de ponto para virgula. Aguarde...");
//	if (file_exists('tmp/ponto_para_virgula.txt')) unlink('tmp/ponto_para_virgula.txt');

//	$shell = new COM('WScript.Shell');
//	$shell->Run('xtras\nircmd\nircmd.exe clipboard writefile "tmp/ponto_para_virgula.txt" ');
//	unset($shell);
	
//	wecho('*');

//	$filesize = 0;
//	do {
//	  sleep(2);
//	  if (filesize('tmp/ponto_para_virgula.txt') == $filesize) break;
//	  else $filesize = filesize('tmp/ponto_para_virgula.txt'); 
//	  wecho('*');
//	} while(True);

//	wecho('*');

//    if (!$handle = fopen('tmp\ponto_para_virgula.txt', 'r')) {
//     werro_die("Nao foi possivel a leitura do arquivo tmp/ponto_para_virgula.txt - possivelmente foi deletado durante o processamento");
//    }
//    if (!$handlew = fopen("tmp/ponto_para_virgulaw.txt", 'w')) {
//     werro_die("Nao foi possivel a gravação do arquivo tmp/ponto_para_virgulaw.txt - possivelmente foi deletado durante o processamento");
//    }

//	while(!feof($handle)) {
//	  $campos = fgetcsv($handle, 700, ',', '"');
//	  if (is_array($campos)) {
//		$linha = '';
//		$prim_campo = True;
//		foreach($campos as $key => $value) {
//		  if(is_numeric(str_replace(',','',$value))) $campos[$key] = str_replace('.',',',str_replace(',','',$value));
//		  if (! $prim_campo) $linha .= "\t";
//		  $prim_campo = False;
//		  $linha .= $campos[$key];
//		}
//		$linha .= "\r\n";
//		fputs($handlew, $linha);
//	  }
//	}
//	fclose($handle);
//	fclose($handlew);

//	wecho('*');

//	$shell = new COM('WScript.Shell');
//	$shell->Run('nircmd\nircmd.exe clipboard readfile "tmp/ponto_para_virgulaw.txt" ');
//	unset($shell);
	
//	wecho("Conversão Finalizada !\n");
}

function clickParametros(GtkWindow $wnd) {

  global $pr;
  
  $pr->le_todos_sql_params();
  
  $dialog = new GtkDialog('Ajuste os Parâmetros:', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 200);

  $lbl_obs1 	= new GtkLabel("Os parâmetros são específicos para cada banco de dados. Estão presentes os seguintes:" . $pr->carrega_db_disponiveis());

  $dialog->vbox->pack_start($lbl_obs1, false, false, 3);
  $dialog->vbox->pack_start(new GtkHSeparator(), false, false, 3);

  $scrolledwindow = new GtkScrolledWindow();
  $scrolledwindow->set_policy(Gtk::POLICY_NEVER,Gtk::POLICY_AUTOMATIC); 
  
  $vboxscroll = new GtkVBox();
  $hboxes = array();
  $labels = array();
  $entrys = array();
  
  $i = 0;
  foreach ($pr->sql_params as $indice => $valor) {
    foreach ($pr->sql_params[$indice] as $indice2 => $valor2) {
	  $hboxes[] = new GtkHBox();
	  $labels[] = new GtkLabel("[{$indice}] - {$indice2}");
	  $entrys[] = new GtkEntry($valor2);
	  $hboxes[$i]->pack_start($labels[$i]);
	  $hboxes[$i]->pack_start($entrys[$i]);
	  $vboxscroll->pack_start($hboxes[$i]);
	  $i++;
	}
  }
  $scrolledwindow->add_with_viewport($vboxscroll);
  $dialog->vbox->pack_start($scrolledwindow, true, true, 0);

  $dialog->add_button(Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL);
  $dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);

  $dialog->set_has_separator(True);
  $dialog->show_all();
  $response_id = $dialog->run();
  $dialog->destroy();

  if ($response_id == Gtk::RESPONSE_OK) {
    $i = 0;
	foreach ($pr->sql_params as $indice => $valor) {
	  foreach ($pr->sql_params[$indice] as $indice2 => $valor2) {
	    $pr->sql_params[$indice][$indice2] = $entrys[$i]->get_text();
		$i++;
	  }
	  $pr->salva_sql_params($indice);
	}
  }
}

function clickOptions(GtkWindow $wnd) {
  global $pr;
  
  $dialog = new GtkDialog('Selecione as opções:', null, Gtk::DIALOG_MODAL);
  $dialog->set_position(Gtk::WIN_POS_CENTER_ALWAYS);
  $dialog->set_default_size(400, 400);

  $scrolledwindow = new GtkScrolledWindow();
  $scrolledwindow->set_policy(Gtk::POLICY_NEVER,Gtk::POLICY_AUTOMATIC); 
  
  $vboxscroll = new GtkVBox();
  $hboxes = array();
  $labels = array();
  $entrys = array();
  $chkbuttons = array();

  // os três abaixo só para fazer comboboxes...
  $comboboxes = array();
  $models = array();
  $cellrenderers = array();
  
  $ind = 0;		// controle do índice dos objetos
  foreach ($pr->options as $indice => $valor) {
    if (!($indice == 'label' || $indice == 'tipo' || $indice == 'alist')) {
	  if (strtolower($pr->options['tipo'][$indice]) == 'checkbutton') {
		$chkbuttons[$ind] = new GtkCheckButton($pr->options['label'][$indice]);
		$chkbuttons[$ind]->set_active($valor);
		$vboxscroll->pack_start($chkbuttons[$ind], false, false, 3);
	  }
	  if (strtolower($pr->options['tipo'][$indice]) == 'entry' || strtolower($pr->options['tipo'][$indice]) == 'entryint') {
		$hboxes[$ind] = new GtkHBox();
		$labels[$ind] = new GtkLabel(' ' . $pr->options['label'][$indice]);
		$entrys[$ind] = new GtkEntry($valor);
		$hboxes[$ind]->pack_start($labels[$ind], false, false, 0);
		$hboxes[$ind]->pack_start($entrys[$ind], false, false, 0);
		$vboxscroll->pack_start($hboxes[$ind], false, false, 3);
	  }
	  if (strtolower($pr->options['tipo'][$indice]) == 'combobox') {
		$hboxes[$ind] = new GtkHBox();
		$labels[$ind] = new GtkLabel(' ' . $pr->options['label'][$indice]);
		$comboboxes[$ind] = new GtkComboBox();
		// Create a model 
		if (defined("GObject::TYPE_STRING")) {
		  $models[$ind] = new GtkListStore(GObject::TYPE_STRING);
		} else {
		  $models[$ind] = new GtkListStore(Gtk::TYPE_STRING);
		}
		$comboboxes[$ind]->set_model($models[$ind]);
		$cellrenderers[$ind] = new GtkCellRendererText();
		$comboboxes[$ind]->pack_start($cellrenderers[$ind]);
		$comboboxes[$ind]->set_attributes($cellrenderers[$ind], 'text', 0);
		// a listagem está em $this->options['alist']['nivdetmes']
		$indcombo = 0;
		foreach($pr->options['alist'][$indice] as $indl => $vall) {
		  $models[$ind]->append(array("Tipo: [{$indl}]: {$vall}"));
		  if ($indl == $valor) $comboboxes[$ind]->set_active($indcombo);
		  $indcombo++;
		}

		$hboxes[$ind]->pack_start($labels[$ind], false, false, 0);
		$hboxes[$ind]->pack_start($comboboxes[$ind], false, false, 0);
		$vboxscroll->pack_start($hboxes[$ind], false, false, 3);
	  }
	  $ind++;
	}
  }
  $scrolledwindow->add_with_viewport($vboxscroll);
  $dialog->vbox->pack_start($scrolledwindow, true, true, 0);

  $dialog->add_button(Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL);
  $dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);

  $dialog->set_has_separator(True);
  $dialog->show_all();
  $response_id = $dialog->run();

  if ($response_id == Gtk::RESPONSE_OK) {
	$ind = 0;
	foreach ($pr->options as $indice => $valor) {
	  if (!($indice == 'label' || $indice == 'tipo' || $indice == 'alist')) {
		if (strtolower($pr->options['tipo'][$indice]) == 'checkbutton') {
		  $pr->options[$indice] = $chkbuttons[$ind]->get_active();
		}
		if (strtolower($pr->options['tipo'][$indice]) == 'entry') {
		  $pr->options[$indice] = $entrys[$ind]->get_text();
		}
		if (strtolower($pr->options['tipo'][$indice]) == 'entryint') {
		  $pr->options[$indice] = $entrys[$ind]->get_text() + 0;
		}
		if (strtolower($pr->options['tipo'][$indice]) == 'combobox') {
		  $saux = $models[$ind]->get_value($comboboxes[$ind]->get_active_iter(),0);
		  // echo $saux . "/n-->" . substr($saux, strpos($saux, '[') + 1, 1); // debug
		  $pr->options[$indice] = substr($saux, strpos($saux, '[') + 1, 1) + 0;
		}
		$ind++;
	  }
	}
	if ($pr->options['savopt']) $pr->save_options();
	//else if (file_exists(PR_RES . '/options.conf')) unlink(PR_RES . '/options.conf');
  }

  $dialog->destroy();
  
}

// constrói o menu
function setup_menu($vbox) {
  // definição de menu
  global $pr;
  
  $menus = array(
    '_Arquivo' => array('_Novo', '<hr>', '_Converter', '<hr>', '_Fontes', '_Resultados', '_Db3s e tmp', '_Modelos', '<hr>', '_Sair'),
    '_Utilitários' => array('_Tabelas', '_Opções', 'Arq_Diet', '_Junta PDFs', '<hr>',
							'Desenvolvimento de _Auditorias', '<hr>',
							'_Sublime Text', 'SQLite_Man', 'NoteP_ad++', '<hr>', 'Visualiza _Logs',
							'_Forçar Encerramento de todas as janelas do Excel'),
    'Au_ditorias' => array('_Parâmetros'),
    'A_juda' => array('_Ajuda', 'Visualiza _Histórico de Versões', '_Sobre...')
  );
  
  // Opções adicionais - phps na pasta xtras - caso exista um ou mais, insere também um separador (<hr>)
  $inseriu_hr = False;
  if (file_exists('xtras/nircmd/nircmd.exe')) {
    if (!$inseriu_hr) {
	  $inseriu_hr = True;
	  $menus['_Utilitários'][] = '<hr>';
	}
	$menus['_Utilitários'][] = '_Ponto->Virgula Clipboard';
  }
  if (file_exists('../_xtras/LeNFe.php')) {
    if (!$inseriu_hr) {
	  $inseriu_hr = True;
	  $menus['_Utilitários'][] = '<hr>';
	}
	$menus['_Utilitários'][] = 'Lê _NFe';
  }
  if (file_exists('xtras/ConsultasWeb.php')) {
    if (!$inseriu_hr) {
	  $inseriu_hr = True;
	  $menus['_Utilitários'][] = '<hr>';
	}
	$menus['_Utilitários'][] = 'Consultas _Web';
  }

  // Continua a preencher o menu _Auditorias com os dados de $aud_params
  if (count($pr->aud_params) > 0) {
    $menu_aud = array();
	foreach ($pr->aud_params as $indice => $valor) {
	  if (!array_key_exists($valor->menu, $menu_aud )) $menu_aud[$valor->menu] = array();
	  $menu_aud[$valor->menu][] = $valor->submenu;
	}
  }
  foreach($menu_aud as $indice => $valor) {
    foreach($valor as $indice2 => $valor2) $menus['Au_ditorias'][$indice][] = $valor2;
  }
  $menubar = new GtkMenuBar();
  $vbox->pack_start($menubar, 0, 0);
  foreach($menus as $toplevel => $sublevels) {
    $menubar->append($top_menu = new GtkMenuItem($toplevel));
	$menu = new GtkMenu();
	$top_menu->set_submenu($menu);
	foreach($sublevels as $submenuindex => $submenu) {
	  if ($submenu=='<hr>') {
	    $menu->append(new GtkSeparatorMenuItem());
	  } else {
		if (is_array($submenu)) {
		  $menu->append($menu_item = new GtkMenuItem($submenuindex));
		  $menu_item->set_submenu($submenu_s  = new GtkMenu());
		  foreach($submenu as $submenuval) {
			$submenu_s->append($pr->asubmenu_item[$submenuval] = new GtkMenuItem($submenuval));
			$pr->asubmenu_item[$submenuval]->connect('activate', 'on_menu_select');

		  }
		} else {
		  $menu->append($menu_item = new GtkMenuItem($submenu));
		  $menu_item->connect('activate', 'on_menu_select');
		}
	  }
	}
  }
  habilita_submenus();
}

function habilita_submenus() {
  global $pr;

  foreach($pr->aud_params as $indice => $valor) {
	if ($pr->aud_disponivel($valor->use)) {
	  $pr->asubmenu_item[$valor->submenu]->set_sensitive(True);
	} else {
	  $pr->asubmenu_item[$valor->submenu]->set_sensitive(False);
	}
  }
}

// chama as funções de cada item do menu
function on_menu_select($menu_item) {
  global $wnd, $pr, $aud_params;
  $item = $menu_item->child->get_label();
  if ($item == '_Novo') clickNovo($wnd);
  if ($item == '_Converter') clickok($wnd);
  if ($item == '_Fontes') clickFontes($wnd);
  if ($item == '_Resultados') clickResult($wnd);
  if ($item == '_Db3s e tmp') clickDb3_Tmp($wnd);
  if ($item == '_Modelos') clickModelos($wnd);
  if ($item == '_Tabelas') clickTabelas($wnd);
  if ($item == '_Opções') clickOptions($wnd);
  if (file_exists('xtras/ArqDiet.php')) { if ($item == 'Arq_Diet') arq_diet($wnd); }
  if (file_exists('xtras/DesenvAud.php')) { if ($item == 'Desenvolvimento de _Auditorias') clickDesenv_aud($wnd); }
  if (file_exists('xtras/JuntaPDFs.php')) { if ($item == '_Junta PDFs') juntaPDFs($wnd); }
  if ($item == '_Ponto->Virgula Clipboard') clickPonto_para_Virgula_Clipboard($wnd);
  if ($item == 'SQLite_Man') clickSQLiteMan($wnd);
  if ($item == '_Sublime Text') clickSublimeText($wnd);
  if ($item == 'NoteP_ad++') clickNotepadPlus($wnd);
  if ($item == 'Visualiza _Logs') clickVisualizaLogs($wnd);
  if ($item == '_Forçar Encerramento de todas as janelas do Excel') clickKillExcel($wnd);
  if (file_exists('xtras/ConsultasWeb.php')) { if ($item == 'Consultas _Web') consultasWeb($wnd); }
  if (file_exists('../_xtras/LeNFe.php')) { if ($item == 'Lê _NFe') leNFe($wnd); }
  if ($item == '_Parâmetros') clickParametros($wnd);
  if (file_exists('xtras/sql.php')) { if ($item == '_SQL') sql($wnd); };
  if ($item == '_Ajuda') clickAjuda($wnd);
  if ($item == 'Visualiza _Histórico de Versões') clickVisualizaHistVers($wnd);
  if ($item == '_Sobre...') clickSobre($wnd);
  if ($pr->options['ldebug']) wecho("\nMenu selecionado: $item \n\n");
  if ($item == '_Sair') Gtk::main_quit();
  
  // chama as funções de auditoria, sendo o caso
  foreach($pr->aud_params as $indice => $valor) {
    if ($item == $valor->submenu) $pr->aud_executa($valor);
  }
  wecho("\n\nFinalizado !\n\n");
}

// Faz a cópia de arquivos em Fontes, no caso de Drag & Drop
function on_drop_view1($widget, $context, $x, $y, $data, $info, $time, $view) {
    $uri_list = explode("\n",$data->data);
	wecho("\n");
	foreach($uri_list as $indice => $valor) {
	  $filename = $valor;
	  $filename = str_replace("file:///", "", $filename); 
	  $filename = utf8_decode(urldecode(str_replace("\r", "", $filename)));

	  if (strlen($filename) > 2) {
		wecho("Copiando o arquivo " . $filename . " para a pasta Fontes...");
		$caminho = explode('/', $filename);
		$newfile = PR_FONTES . '/' . $caminho[count($caminho)-1];
		if (!copy($filename, $newfile)) wecho(" FALHA NA CÓPIA !!!\n"); else wecho(" Sucesso !\n");
	  }
	}
}

function reinicializa() {

  global $pr;

  $shell = new COM('WScript.Shell');
  if ($pr->options['console'] == True) $shell->Run('php-win ' . __DIR__ . '/Reinicializa.php console');
  else $shell->Run('php-win ' . __DIR__ . '/Reinicializa.php');
  unset($shell);
  Gtk::main_quit();
}

// Abaixo, quando click em Inicia Conversão, após reinicializar
function principal() {

  global $pr, $btnOk, $btnOptions, $btnCancel, $lblHello2;
  
//	Ao iniciar conversão não estava conseguindo apagar tudo...
//	Tornou-se necessário sair fora do programa, zerar tmp e db3 e reinicializar diretamente aqui
  $pr->options['reinicializa'] = False;

  $btnOk->set_sensitive(False);
  $btnOptions->set_sensitive(False);
  $btnCancel->set_sensitive(False);
  
  if (file_exists(PR_LOG . '/ThreadConvPronto.log')) unlink(PR_LOG . '/ThreadConvPronto.log');

  $itam_prerror_log = tam_prerror_log(); 	// qualquer erro, como de compilação, é jogado em logs/prError.log, conforme definido em php-gtk2/php-cli.ini
											// este log é completo... ou outros logs de erro não abrangem 100%

  $shell = new COM('WScript.Shell');
  $shell->Run('php-win ' . __DIR__ . '/ThreadConv.php');
  unset($shell);

  do {
	sleep(2);
	wecho ('*');
	if (file_exists(PR_LOG . '/ThreadConvError.log')) {
	  wecho("\n\nOcorreu erro ao converter os arquivos...\nUm ou mais deles podem estar com problemas\n");
	  wecho("O erro poder ser visualizado no menu Utilitários - Visualiza Log, selecionando o arquivo 'ThreadConvError.log'\n");
	  wecho("Recomenda-se reiniciar a conversão com menos arquivos, inserindo-os de forma alternada, até descobrir o(s) que gera(m) problema(s)...\n\n");
	  break;
	}
	if (($itam_prerror_log <> tam_prerror_log())) {
	  wecho("\n\nOcorreu erro ao converter os arquivos...\nUm ou mais deles podem estar com problemas\n");
	  wecho("O erro poder ser visualizado no menu Utilitários - Visualiza Log, selecionando o arquivo 'prError.log'\n");
	  wecho("Recomenda-se reiniciar a conversão com menos arquivos, inserindo-os de forma alternada, até descobrir o(s) que gera(m) problema(s)...\n\n");
	  break;
	}
  } while (! file_exists(PR_LOG . '/ThreadConvPronto.log'));
	if (file_exists(PR_LOG . '/ThreadConvWarning.log')) {
	  wecho("\n\nFoi gerado um aviso (warning) ao converter os arquivos, provavelmente por erro em algum comando SQL...\nUm ou mais deles podem estar com problemas\n");
	  wecho("O aviso (warning) pode ser visualizado no menu Utilitários - Visualiza Log, selecionando o arquivo 'ThreadConvWarning.log'\n");
	  wecho("Recomenda-se reiniciar a conversão com menos arquivos, inserindo-os de forma alternada, até descobrir o(s) que gera(m) problema(s)...\n\n");
	}
  
  $btnOk->set_sensitive(True);
  $btnOptions->set_sensitive(True);
  $btnCancel->set_sensitive(True);
  habilita_submenus();
  $lblHello2->set_label("Tipos de Bancos de Dados disponíveis: " . $pr->carrega_db_disponiveis());

  wecho(file_get_contents(PR_LOG . '/ThreadConvPronto.log'));
  wecho("\n\nConversão Finalizada. Utilize SQL a partir do menu Utilitários ou gere auditorias em Excel (ou texto ou ainda html, conforme definido em Opções) a partir do menu Auditorias.");

  wecho("\nTempo Total de Conversão: " . $pr->tempo() . " segundos\n\n\n");
}

function tam_prerror_log() {
  if (file_exists(PR_LOG . '/prError.log')) {
	clearstatcache( False, PR_LOG . '/prError.log');	// senão filesize não detectará mudanças de tamanho...
	return filesize(PR_LOG . '/prError.log');
  } else {
	return 0;
  }
}
?>