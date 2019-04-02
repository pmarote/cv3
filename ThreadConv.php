<?php

require __DIR__ . '/lib/base.inc.php';
require __DIR__ . '/lib/classes.inc.php';
$pr = new Pr;	// classe principal, global
$pr->inicia_log(PR_LOG . '/ThreadConv.log');

require __DIR__ . '/lib/TabAux.php';
require __DIR__ . '/conv/Conv_EFD.php';
require __DIR__ . '/conv/Conv_LADCA.php';
require __DIR__ . '/conv/Conv_LASIMCA.php';
require __DIR__ . '/conv/Conv_CAT42.php';
//require 'Conv_ECD.php';
//require 'Conv_P32.php';
//require 'Conv_NFe.php';
//require 'Conv_Cad.php';
//require 'Conv_IEs.php';
//require 'Conv_GIA.php';
require __DIR__ . '/conv/Conv_TXT.php';	// se não for nenhuma opção acima e for arquivo txt, vai criar uma tabela e tentar ler, da forma que der
//if (file_exists('../_xtras/Conv_LogFileMocha.php')) {
//  include '../_xtras/Conv_LogFileMocha.php';
//}
include __DIR__ . '/conv/Conv_P17.php';
//if (file_exists('../_xtras/LeNFe.php')) {
//  include '../_xtras/LeNFe.php';
//}

error_warning_logs(PR_LOG . '/ThreadConvError.log', PR_LOG . '/ThreadConvWarning.log'); 	// em base.inc.php

// Splash Screen
$windowsplash = new GtkWindow();
$windowsplash->set_position(Gtk::WIN_POS_CENTER);
$windowsplash->set_default_size(600, 600);
$windowsplash->connect_simple('destroy', array('Gtk','main_quit'));

$textBuffer = new GtkTextBuffer();
$scrolledwindow = new GtkScrolledWindow();
$view1 = new GtkTextView();
$view1->set_wrap_mode(Gtk::WRAP_WORD_CHAR);
$scrolledwindow->viewer = $view1;
$scrolledwindow->set_policy(Gtk::POLICY_NEVER,Gtk::POLICY_ALWAYS); 
 // Add some text to the buffer.
$textBuffer->set_text("Ao final da conversão, esta janela fechará automaticamente\n\n");
// Add the buffer to the view and make sure no one edits the text.
$scrolledwindow->viewer->set_buffer($textBuffer);
$scrolledwindow->viewer->set_editable(false);
$scrolledwindow->add($scrolledwindow->viewer); 

$vbox = new GtkVBox();
$vbox->pack_start($scrolledwindow, true, true, 3);

$frame = new GtkFrame('Efetuando a Conversão dos Arquivos em Fontes para Bancos de Dados SQL. Aguarde...');
$frame->set_shadow_type(Gtk::SHADOW_ETCHED_OUT);
$frame->modify_bg(Gtk::STATE_NORMAL, GdkColor::parse('#0000ff')); 
$frame->add($vbox);

$hbox = new GtkHBox();
$hbox->pack_start($frame, true, true, 10);
$frame_out = new GtkFrame();
$frame_out->add($hbox);

$windowsplash->add($frame_out);
$windowsplash->set_decorated(false);
$windowsplash->show_all();
while (Gtk::events_pending()) {  // redraw de splash screen
	Gtk::main_iteration();
}


  //abredb3_cad(); // do arquivo Conv_Cad.php, cria desde o início o banco de dados de cadastro, com tabelas auxiliares também
  
  $pr->zera_tempo();
  $pr->mens_final_conf = '';
  
  // Leituras
  $numarqs = 0;
  wecho("\n\nLeitura dos arquivos presentes na pasta Fontes e subpastas:\n");
  $arqs = listdir_semConsultaNFes(PR_FONTES); // em base.inc.php - \Fontes\ConsultaNFes, caso exista, é lido em leituraConsultaNFes() mais abaixo
  // primeiro unzip todos os arquivos .sped ou .zip
  $zip = new ZipArchive;
  $novolistdir = False;
  foreach ($arqs as $key => $entry) {
	if (strtolower(substr($entry, -5)) == ".sped"  || strtolower(substr($entry, -4)) == ".zip") {
	  if ($zip->open($entry) === TRUE) {
		mkdir($entry . 'dir');
		if ($zip->extractTo($entry . 'dir') === TRUE) {
		  $zip->close();
		  unlink($entry);
		  if (strtolower(substr($entry, -5)) == ".sped") wecho("\nArquivo sped {$entry} descompactado para a pasta {$entry}dir");
		  else wecho("\nArquivo zip {$entry} descompactado para a pasta {$entry}dir");
		  $novolistdir = True;
		} else {
		  $zip->close();
		  if (strtolower(substr($entry, -5)) == ".sped") wecho("\nFalha ao descompactar o arquivo sped {$entry}");
		  else wecho("\nFalha ao descompactar o arquivo zip {$entry}");
		}
	  } else {
		$zip->close();
		if (strtolower(substr($entry, -5)) == ".sped") wecho("\nFalha ao descompactar o arquivo sped {$entry}");
		else wecho("\nFalha ao descompactar o arquivo zip {$entry}");
	  }
	}
  }
  
  // Leituras dos arquivos... os que estavam zipados, já foram descompactados
  if ($novolistdir) $arqs = listdir(PR_FONTES); // se descompactou arquivo SPED ou ZIP, lê tudo novamente...
  foreach ($arqs as $key => $entry) {
	if (strtolower(substr($entry, -4)) != '.xml') {
	  // é arquivo Sped ?
	  if (!$handle = fopen("$entry", 'r')) {
		wecho("Erro... Nao foi possivel a leitura do arquivo $entry - possivelmente foi deletado durante o processamento\n");
	  } else { // abertura do arquivo com sucesso
		$linha = substr(fgets($handle), 0, -2);
		$linha2 = substr(fgets($handle), 0, -2);
		fclose($handle);
		if (substr($linha, 0, 6) == '|0000|') {
		  //é arquivo Sped !
		  if (substr($linha, 0, 11) == '|0000|LECD|') {
			// é Sped Contábil
			wecho("\n-->Leitura do arquivo Sped Contabil " . str_replace('../', '', $entry) . " ");
			leitura_ecd($entry);
			if ($options['arqs_sep']) gera_excel_ecd($entry);
		  } else {
			wecho("\n-->Leitura do arquivo Sped Fiscal " . str_replace('../', '', $entry) . " ");
  			leitura_efd($entry);
			if ($options['arqs_sep']) gera_excel_efd($entry);
		  }
		} else {
		  if ((substr($linha, 0, 2) == '10' && substr($linha2, 0, 2) == '11')
				|| (substr($linha, 0, 4) == '7420')) {
				// Esta verificação acima porque tem gente que entrega o 74 separado... então começa com 742010, por exemplo...
			// é arquivo Sintegra ! (Port Cat 32/96)
			wecho("\n-->Leitura do arquivo Sintegra (P.Cat 32/96) " . str_replace('../', '', $entry) . " ");
			leitura_p32($entry);
		  } else {
			if (substr($linha, 0, 2) == '01' && substr($linha2, 0, 2) == '02') {
			  // é arquivo Ressarcimento ! (Port Cat 17/99)
			  if (function_exists('leitura_p17')) {
				leitura_p17($entry);
				wecho("\n-->Leitura do arquivo Ressarcimento (P.Cat 17/99) " . str_replace('../', '', $entry) . " ");
			  }
			} else {

			  if (substr($linha, 0, 5) == '0000|') {
			    if (substr($linha, 0, 11) == '0000|LADCA|') {
				  // é Crédito Acumulado
				  wecho("\n-->Leitura do arquivo Crédito Acumulado Custeio Cat83 " . str_replace('../', '', $entry) . " hash=" . hash_file('md5', $entry) . " ");
				  leitura_ladca($entry);
				} elseif (substr($linha, 0, 13) == '0000|LASIMCA|') { 
				  wecho("\n-->Leitura do arquivo Crédito Acumulado Simplificado Cat207 " . str_replace('../', '', $entry) . " hash=" . hash_file('md5', $entry) . " ");
				  leitura_lasimca($entry);
				} else {
				  // deve ser Portaria Cat 42 (e-ressarcimento) (ela não tem identificador, após 0000| vem o período. exemplo: 0000|012019|)
				  wecho("\n-->Leitura do arquivo Cat 42 (e-ressarcimento) " . str_replace('../', '', $entry) . " ");
				  leitura_cat42($entry);
				}
			  } else {
			
				if (strtolower(substr($entry, -4)) == ".csv" || strtolower(substr($entry, -4)) == ".txt") {
				  $aarq = explode('/', $entry);
				  // não me pergunte o porquê, mas em alguns computadores, windows, o espaço no nome do arquivo é substituído por '_'
				  $nomarq = str_replace(' ', '_', $aarq[count($aarq) - 1]);
				  if (strtoupper(substr($nomarq, 0, 11)) == "CFOP_POR_IE") {
					wecho("\n-->Leitura do arquivo Gia " . str_replace('../', '', $entry) . " ");
					le_cfop_por_ie($entry); // em Conv_GIA.php
				  }
				  elseif (strtoupper(substr($nomarq, 0, 7)) == "LOGFILE") {
					if (function_exists('leitura_logfile_mocha')) {
					  wecho("\n-->Leitura do arquivo Log do Mocha " . str_replace('../', '', $entry) . " ");
					  leitura_logfile_mocha($entry); // em xtras/Conv_LogfileMocha.php
					}
				  }
				  elseif (strtoupper(substr($nomarq, 0, 15)) == "CADREGIMES_SAFI") {
					wecho("\n-->Leitura do arquivo CadRegimes_SAFI.txt " . str_replace('../', '', $entry) . " ");
					le_db3ies_regimes($entry); // em Conv_IEs.php
				  }
				  elseif (strtoupper(substr($nomarq, 0, 13)) == "CADSEFAZ_SAFI") {
					wecho("\n-->Leitura do arquivo CadSefaz_SAFI.txt " . str_replace('../', '', $entry) . " ");
					le_db3ies_cadastro($entry); // em Conv_IEs.php
				  }
				
				  // Leituras .txt genéricas
				  elseif (1==1) {
					wecho("\n-->Leitura do arquivo txt genérico " . str_replace('../', '', $entry) . " ");
					leitura_txt($entry); // em Conv_TXT.php
				  }
				
				} else {
				  // Desisti de abrir .rep... se quiser, tente primeiro abrir o arquivo com zip. Depois, tente entender, eu não consegui
				  //if (strtolower(substr($entry, -4)) == ".rep") {
				  //	wecho("\n-->Leitura do arquivo BO (.rep) " . str_replace('../', '', $entry) . " ");
				  //	leitura_rep($entry); // em Conv_REP.php
				  //} else {
					$numarqs++;
					if ($pr->options['ldebug']) wecho("\n  {$entry} não é arquivo Sped, nem Sintegra, nem .xml (P.Cat 32/96) !");
					else if ($numarqs % 1000 == 0) wecho("*");
				  //}
				}
			  }
			}
		  }
		}
	  }
	}
  }

  $existenfe = False;
  if (findxml(PR_FONTES)) {  // função em base.inc.php
	$existenfe = True;
	leitura_nfe();
	wecho("\n\n-->Gerando nfe.xls ");
  } 

  //wecho("\nFinalizando... Gerando agora tabelas auxiliares...\n");
  gera_tabelas_auxiliares(); 	// função em TabAux.php

  file_put_contents(PR_LOG . '/ThreadConvPronto.log', $pr->mens_final_conf . ' ');

exit;

?>