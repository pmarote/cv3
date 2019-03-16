<?php

require __DIR__ . '/lib/base.inc.php';
error_warning_logs(PR_LOG . '/ReinicializaError.log', PR_LOG . '/ReinicializaWarning.log'); 	// em base.inc.php

if (isset($argv[1]) && strtolower($argv[1]) == 'console') {
  $ldebug = True;
} else {
  //error_reporting(E_ERROR);
  $ldebug = False;
}

$seg_tent = False;
if (isset($argv[1]) && strtolower($argv[1]) == 'segunda_tentativa') $seg_tent = True;
if (isset($argv[2]) && strtolower($argv[2]) == 'segunda_tentativa') $seg_tent = True;

// Splash Screen
$windowsplash = new GtkWindow();
$windowsplash->set_position(Gtk::WIN_POS_CENTER);
$windowsplash->set_default_size(300, 100);
$windowsplash->connect_simple('destroy', array('Gtk','main_quit'));
if (! $seg_tent) {
  if (isset($argv[1]) && strtolower($argv[1]) == 'console') $labelsplash = new GtkLabel("(Modo Console)  Preparando a Conversão ! Aguarde...");
  else $labelsplash = new GtkLabel("Preparando a Conversão ! Aguarde...");
} else {
  if (isset($argv[1]) && strtolower($argv[1]) == 'console') $labelsplash = new GtkLabel("(Modo Console)  *ATENÇÃO* Não está sendo possível deletar a pasta " . PR_TMP . " ... Há arquivos abertos nelas ?");
  else $labelsplash = new GtkLabel("*ATENÇÃO* Não está sendo possível deletar a pasta  " . PR_TMP . " ... Há arquivos abertos nelas ?");
}
$windowsplash->add($labelsplash);
$windowsplash->set_decorated(false);
$windowsplash->show_all();
while (Gtk::events_pending()) {  // redraw de splash screen
	Gtk::main_iteration();
}

sleep(2);

$tentativas = 0;
do {
  recursiveDelete(PR_TMP);
  if (is_dir(PR_TMP)) $b_pronto = False; else $b_pronto = True;
  if ($b_pronto) {
	mkdir(PR_TMP); 
  } else {
    if ($tentativas++ < 4) { 
	  sleep(2);
	} else {
	  if (! $seg_tent) {
		$shell = new COM('WScript.Shell');
		if (isset($argv[1]) && strtolower($argv[1]) == 'console') $shell->Run(__DIR__ . '/_KillProcesses.bat');
		else $shell->Run(__DIR__ . '/_KillProcesses.vbs');
		unset($shell);
		exit;
	  } else {
	    $b_pronto = True;
	  }
	}
  }
} while (!$b_pronto);

$windowsplash->hide();

$shell = new COM('WScript.Shell');
if ($ldebug) $shell->Run(__DIR__ . '/_Reinicializa.bat');
else $shell->Run(__DIR__ . '/_Reinicializa.vbs');
unset($shell);

exit;

?>