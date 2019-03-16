<?php

$pr->aud_registra(new PrMenu("p17_File_Fitness", "P_17", "File Fitness", "p17"));

function p17_File_Fitness() {

  global $pr;

  $arqs = listdir("../Fontes"); // se descompactou arquivo SPED ou ZIP, lê tudo novamente...
  foreach ($arqs as $key => $entry) {
	if (strtolower(substr($entry, -4)) != '.xml') {
	  // é arquivo Sped ?
	if (!$handle = fopen("{$entry}", 'r')) {
		wecho("Erro... Nao foi possivel a leitura do arquivo $entry - possivelmente foi deletado durante o processamento\n");
	  } else { // abertura do arquivo com sucesso
		$linha = fgets($handle);
		$linha2 = fgets($handle);
		if (substr($linha, 0, 2) == '01' && substr($linha2, 0, 2) == '02') {
		  // é arquivo Ressarcimento ! (Port Cat 17/99)
		  wecho("\n-->File Fitness in (P.Cat 17/99) " . str_replace('../', '', $entry) . " ");

		  $filewname = substr($entry, mb_strrpos($entry, '/') + 1);
		  if (!$handlew = fopen("../Resultados/{$filewname}", 'w')) {
			werro_die("Nao foi possivel a gravacao do arquivo ../Resultados/{$filewname} - Feche o programa ou janela que está o usando<br><br>");
		  }	
		  fputs($handlew, $linha);
		  fputs($handlew, $linha2);
		  while(!feof($handle)) {
			$linha = fgets($handle);
			if (substr($linha, 0, 2) == '02' && substr($linha, 48, 4) > '5000' && substr($linha, 48, 4) < '9999' && substr($linha, 52, 2) == '00')
				$linha = substr($linha, 0, 52) . '06' . substr($linha, 54);
			fputs($handlew, $linha);
		  }
		  fclose($handlew);		
		}
		fclose($handle);		
	  }
	}
  }  
  
}


?>