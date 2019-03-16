<?php

function abredb3_audit() {

	$nomarq = "audit";
  
	if (!file_exists(PR_TMP . "/{$nomarq}.db3")) { 

		if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
		} else {
			werro_die("Falha ao criar Banco de Dados {$nomarq}.db3");
		}  

		$db->query('PRAGMA encoding = "UTF-8";');

		cria_tabela_cfopd($db);
		
		// Código do Registro, proc = Processa Sim ou Não, Nível, Descrição
		$createtable = "
CREATE TABLE cfop_entsai (cfop_dfe INT PRIMARY KEY, cfop_res INT, descri_dfe TEXT, descri_res TEXT);
";
		create_table_from_txt($db, $createtable, 'res\tabelas\cfop_entsai.txt', 'cfop_entsai');	
		

		$db->exec("
-- Dados da empresa fiscalizada disponivel tambem em audit, caso necessario
DROP TABLE IF EXISTS o000;
CREATE TABLE o000 ( ord int primary key, cod_ver int, cod_fin int, dt_ini text, dt_fin text, nome text, cnpj int, cpf int, uf text,
	  ie text, cod_mun text, im text, suframa text, ind_perfil text, int_ativ int );
-- Dados complementares de entidade
DROP TABLE IF EXISTS o005;
CREATE TABLE o005 ( ord int primary key, fantasia text, cep int, end text, num text, compl text, bairro text, fone text,
	  fax text, email text );
-- Dados do Contabilista
DROP TABLE IF EXISTS o100;
CREATE TABLE o100 ( ord int primary key, nome text, cpf int, crc text, cnpj int, cep int, end text, num text, compl text, bairro text,
	  fone text, fax text, email text, cod_mun int);
DROP TABLE IF EXISTS modelo;
CREATE TABLE modelo( 
  origem TEXT, tp_origem TEXT, cnpj_origem INT, ie_origem TEXT, aaaamm INT, cod_sit INT, tp_oper, cst INT, cfop INT, cfop_nf INT, 
  valcon REAL, bcicms REAL, alicms REAL, icms REAL, outimp REAL, bcicmsst REAL, alicmsst REAL, icmsst REAL, 
  cnpj INT, ie, uf, razsoc, dtaina TEXT, descina TEXT, 
  dtaentsai TEXT, dtaemi TEXT, modelo TEXT, serie TEXT, numero INT, chav_ace TEXT);

-- A tabela a seguir lista inconsistências que atrapalham o modelo
DROP TABLE IF EXISTS incons; 
CREATE TABLE incons(origem, codigo, linha );
");
	
	} else {
		
		if ($db = new SQLite3(PR_TMP . "/{$nomarq}.db3")) {
		} else {
			werro_die("Falha ao abrir Banco de Dados {$nomarq}.db3");
		}  
	}
	return $db;
}

?>