<?php

$pr->aud_registra(new PrMenu("audit_File_Fitness1", "_Audit", "File Fitness1 - <TR> para NewLine", "audit"));
$pr->aud_registra(new PrMenu("audit_File_Fitness2", "_Audit", "File Fitness2 - Gerador de Tabs", "audit"));

function audit_File_Fitness1() {

  global $pr;

  $arqs = listdir(PR_FONTES); // se descompactou arquivo SPED ou ZIP, lê tudo novamente...
  foreach ($arqs as $key => $entry) {
	if (strtolower(substr($entry, -4)) != '.xml') {
	  // é arquivo Sped ?
	if (!$handle = fopen("{$entry}", 'r')) {
		wecho("Erro... Nao foi possivel a leitura do arquivo $entry - possivelmente foi deletado durante o processamento\n");
	  } else { // abertura do arquivo com sucesso
		  wecho("-->File Fitness1 - <TR> para NewLine em" . str_replace('../', '', $entry) . " \r\n");

		  $filewname = substr($entry, mb_strrpos($entry, '/') + 1);
		  if (!$handlew = fopen(PR_RESULTADOS . "/{$filewname}", 'w')) {
			werro_die("Nao foi possivel a gravacao do arquivo " . PR_RESULTADOS . "/{$filewname} - Feche o programa ou janela que está o usando");
		  }	
		  while(!feof($handle)) {
			$linha = fgets($handle, 512);
			$linha = str_replace('<TR>', "\r\n", $linha);
			fputs($handlew, $linha);
		  }
		  fclose($handlew);		
		}
		fclose($handle);		
	}
  }  
  
}

function audit_File_Fitness2() {

  global $pr;

  $arqs = listdir(PR_FONTES); // se descompactou arquivo SPED ou ZIP, lê tudo novamente...
  foreach ($arqs as $key => $entry) {
	if (strtolower(substr($entry, -4)) != '.xml') {
	  // é arquivo Sped ?
	if (!$handle = fopen("{$entry}", 'r')) {
		wecho("Erro... Nao foi possivel a leitura do arquivo $entry - possivelmente foi deletado durante o processamento\n");
	  } else { // abertura do arquivo com sucesso
		  wecho("File Fitness2 - Gerador de Tabs em " . str_replace('../', '', $entry) . "\r\n");

		  $filewname = substr($entry, mb_strrpos($entry, '/') + 1);
		  if (!$handlew = fopen(PR_RESULTADOS . "/{$filewname}", 'w')) {
			werro_die("Nao foi possivel a gravacao do arquivo " . PR_RESULTADOS . "/{$filewname} - Feche o programa ou janela que está o usando");
		  }	
		  while(!feof($handle)) {
			$linha = fgets($handle);
			$linha = str_replace('<TR>', "\r\n", $linha);	// vai que ainda restou algum TR que não pegou antes, porque estava no meio do corte
			$linha = str_replace(' CLASS="s10"', "", $linha);
			$linha = str_replace(' CLASS="s11"', "", $linha);
			$linha = str_replace(' CLASS="s12"', "", $linha);
			$linha = str_replace(' CLASS="s13"', "", $linha);
			$linha = str_replace(' CLASS="s14"', "", $linha);
			$linha = str_replace(' CLASS="s15"', "", $linha);
			$linha = str_replace(' CLASS="s1"', "", $linha);
			$linha = str_replace(' CLASS="s2"', "", $linha);
			$linha = str_replace(' CLASS="s3"', "", $linha);
			$linha = str_replace(' CLASS="s4"', "", $linha);
			$linha = str_replace(' CLASS="s5"', "", $linha);
			$linha = str_replace(' CLASS="s6"', "", $linha);
			$linha = str_replace(' CLASS="s7"', "", $linha);
			$linha = str_replace(' CLASS="s8"', "", $linha);
			$linha = str_replace(' CLASS="s9"', "", $linha);
			$linha = str_replace('</TD><TD>', "\t", $linha);
			$linha = str_replace('<TD>', "", $linha);
			$linha = str_replace('</TD></TR>', "", $linha);
			$linha = html_entity_decode($linha);
			fputs($handlew, $linha);
		  }
		  fclose($handlew);		
		}
		fclose($handle);		
	}
  }  
  
}


?>