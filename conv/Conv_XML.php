<?php

function leitura_xml() {

  global $pr, $options;

  if (!findxml(PR_FONTES)) return;  // função ao final deste arquivo .php

  wecho("\rProcessando arquivos .xml presentes em Fontes\r");

  $nomarq = "xml";

  if (!file_exists(PR_TMP . "/{$nomarq}.db3")) { 

	if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
	} else {
		werro_die('Falha ao criar Banco de Dados {$nomarq}.db3');
	}  

	$db->query('PRAGMA encoding = "UTF-8";');

	$db->query('CREATE TABLE arqxml (
	  chav_ace TEXT, tipo TEXT, nomarq TEXT, inftag TEXT)
	');

	$db->query('CREATE TABLE tag (
	  arqxmlrowid INT, tag TEXT, inftag TEXT, html TEXT)
	');
	
  } else {
	if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
	} else {
	  werro('Falha ao abrir Banco de Dados {$nomarq}.db3');
	  exit;
	}  
  }

  $ilidos = 1;

  $a_conta_reg = array();		// para gravar no final a quantidade de cada registro no arquivo

  $db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

  $arqs = listdir(PR_FONTES);
  foreach ($arqs as $key => $entry) {
	if (mb_strtolower(mb_substr($entry, -4)) == '.xml') {

      if (!($dadosxml = file_get_contents($entry))) {
        werro_die("Nao foi possivel a leitura do arquivo {$entry} - possivelmente foi deletado durante o processamento");
      }



      $inftag = 'Desconhecido';
      $inftagaux = mb_strpos($dadosxml, '<inf');
      if ($inftagaux !== False) {
      	$posfin_tag = mb_strpos($dadosxml, '>', $inftagaux);
      	$inftag = mb_substr($dadosxml, $inftagaux, $posfin_tag - $inftagaux + 1);
      }

      $tipo = 'Desconhecido';
      $chav_ace = 'Desconhecido';
      if ($inftag <> 'Desconhecido') {
      	$tipo = substr($inftag, 4, mb_strpos($inftag, ' ') - 4);
      	if (mb_strpos($inftag, 'Id=') !== False)
      	  $ch_offset = (($tipo == 'NFe' || $tipo == 'Cte') ? 7 : ($tipo == 'Evento' ? 12 : ($tipo == 'Inut' ? 6 : 0)));
      	  $ch_size = ($tipo == 'Inut' ? 41 : 44);
      	  $chav_ace = mb_substr($inftag, mb_strpos($inftag, 'Id=') + $ch_offset, $ch_size);
      }

      $nomarq = $db->escapeString(mb_substr($entry, mb_strrpos($entry, '/') + 1));
	  $insert_query = <<<EOD
INSERT INTO arqxml VALUES(
'{$chav_ace}', '{$tipo}', '{$nomarq}', '{$inftag}'
);
EOD;
	  $db->query($insert_query);

      if ($inftag <> 'Desconhecido') {
      	$arqxmlrowid = $db->lastInsertRowID();
      	// salva todos os innertags após o $posfin_inftag

        $continua = True;
        while ($continua) {
          $continua = False;

          $posprox_tag = mb_strpos($dadosxml, '<', $posfin_tag + 1);
      	  if ($posprox_tag !== False) {
            $prox_tag = mb_substr($dadosxml, $posprox_tag, mb_strpos($dadosxml, '>', $posprox_tag) - $posprox_tag + 1);

      	    $html = '';
      	    $tagfinal = '</' . mb_substr($prox_tag, 1);
      	    $inftag = '';

      	    // tem atributos?
      	    $iaux = mb_strpos($prox_tag, ' ');
      	    if ($iaux !== False) {
      	      $inftag = mb_substr($prox_tag, $iaux + 1, -1);
      	      $prox_tag = '<'  . mb_substr($prox_tag, 1, $iaux - 1) . '>';
      	      $tagfinal = '</' . mb_substr($prox_tag, 1, $iaux - 1) . '>';
      	      //debug_log("#{$prox_tag}#{$tagfinal}#{$inftag}#");
      	    }

      	    $posfin_tag = mb_strpos($dadosxml, $tagfinal, $posprox_tag);
      	    if ($posfin_tag !== False) {
      	      $html = mb_substr($dadosxml, $posprox_tag, $posfin_tag - $posprox_tag + (mb_strlen($tagfinal)) );
      	      $html = $db->escapeString($html);
     	      $insert_query = <<<EOD
INSERT INTO tag VALUES(
{$arqxmlrowid}, '{$prox_tag}', '{$inftag}', '{$html}'
);
EOD;
	          $db->query($insert_query);
	          $continua = True;
	        }
      	  }
        }
      }
    }  	
 
    if (++$ilidos % 10000 == 0) {
	  if ($pr->ldebug) {
	    wecho("\nLidas {$ilidos} linhas do arquivo {$nomarq} em ");
        wecho($pr->tempo() . " segundos");
	  } else wecho("*");
	  $db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
	  $db->query('BEGIN;'); // Conforme faq do Sqlite, acelera Insert (questao 19)
	}
  }

  $db->query('COMMIT;'); // Conforme faq do Sqlite, acelera Insert (questao 19)

  // Leitura de Arquivo Finalizada

}

function findxml($start_dir='.') {  // retorna True se existe arquivos xml no diretório

  if (is_dir($start_dir)) {
    //debug_log("#findxml_start_dir=#{$start_dir}#\r");
    $fh = opendir($start_dir);
    while (($file = readdir($fh)) !== false) {
      # loop through the files, skipping . and .., and recursing if necessary
      if (strcmp($file, '.')==0 || strcmp($file, '..')==0) continue;
      $filepath = $start_dir . '/' . $file;
      if ( is_dir($filepath) ) {
		if (findxml($filepath)) {
			closedir($fh);
            //debug_log("#Achei um xml->voltou da pasta {$filepath}#\r");
			return True;
		}
	  } else {
		if (substr(strtolower($file), -4) == '.xml') {
			closedir($fh);
            //debug_log("#Achei um xml->voltou do arquivo {$file}#\r");
			return True;
		}
	  }
    }
    closedir($fh);
  }
  return False;
}

  
?>