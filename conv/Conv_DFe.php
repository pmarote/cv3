<?php

function abredb3_dfe() {

	$nomarq = "dfe";
  
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
DROP TABLE IF EXISTS nfe_danfe;
CREATE TABLE nfe_danfe(
  mod_origem, cnpj_origem, chav_ace, natOp, exLgr, enro, exCpl, exBairro, exMun, eCep INT, exPais, eTel, dxLgr, dnro, dxCpl, dxBairro,
  dxMun, dCep INT, dxPais, dTel, tmarca, qVol, vFrete REAL, vSeg REAL, vDesc REAL, vII REAL, vIPI REAL, vOutro REAL, cNf, 
  modFrete, tIE, txNome, txEnder, txMun, tUF, tCNPJ INT, tesp, tnVol, tpesoL, tpesoB, infAdFisco, infCpl,
  PRIMARY KEY (chav_ace)
);
CREATE TABLE nfe (
	chav_ace TEXT, dtaemi TEXT, dtaentsai TEXT, modelo TEXT, serie TEXT, nNF INT, nItem INT,
	origem TEXT, cnpj_origem INT, ie_origem TEXT, aaaamm INT, cod_sit INT, tp_oper, cst INT, cfop INT,
	valcon REAL, bcicms REAL, alicms REAL, icms REAL, outimp REAL, bcicmsst REAL, alicmsst REAL, icmsst REAL, 
	valipi TEXT, valii TEXT, 
	cnpj INT, ie, uf, razsoc, dtaina TEXT, descina TEXT, 
	codncm INT, codpro TEXT, cEAN TEXT, descri TEXT, qtdpro REAL, unimed TEXT, 
	vFrete REAL, vSeg REAL, vDesc REAL, vOutro REAL, nDI, UFDesemb, 
	pRedBC REAL, pMVAST REAL, pRedBCST REAL, vBCSTRet REAL, vICMSSTRet REAL, vCredICMSSN REAL
);
-- Na verdade, é melhor deixar pra criar os índices em auditorias, depois das tabelas preenchidas. É mais rápido
-- CREATE INDEX `nfe_chapri`   ON nfe (chav_ace ASC, nItem ASC);
-- CREATE INDEX `nfe_sernumit` ON nfe (serie ASC, nNF ASC, nItem ASC);
CREATE TABLE cte (
		chav_ace TEXT, cfop INT, natOp TEXT, serie INT, nCT INT, dhEmi TEXT, tpCTe INT,
		modal INT, tpServ INT, xMunIni, UFIni, xMunFim, UFFim, 
		toma TEXT,
		tCNPJ INT, tIE TEXT, txNome TEXT, tUF TEXT, dtaina TEXT, descina TEXT, 
		eCNPJ INT, eIE TEXT, exNome TEXT, eUF TEXT, 
		rCNPJ INT, rIE TEXT, rxNome TEXT, rUF TEXT,
		dCNPJ INT, dIE TEXT, dxNome TEXT, dUF TEXT, 
		xCNPJ INT, xUF TEXT,
		rcCNPJ INT, rcUF TEXT,
		indSN TEXT, descr_cst TEXT,
		vBC REAL, pRedBC REAL, pICMS REAL, vICMS REAL,
		vBCSTRet REAL, pICMSSTRet REAL, vICMSSTRet REAL, 
		vICMSOutraUF, vCred_Out_Pres REAL, vTPrest REAL, vRec REAL
);
-- CREATE INDEX `cte_chav_ace` ON cte (chav_ace ASC);
CREATE TABLE cte_nf (
		chav_ace TEXT, arq TEXT, 
		chav_ace_nf TEXT, mod TEXT, serie INT, nDoc INT, dEmi TEXT, 
		vBC REAL, vICMS REAL, vBCST REAL, vST REAL,
		vProd REAL, vNF REAL, nCFOP INT
);
-- CREATE INDEX `cte_nf_chav_ace` ON cte (chav_ace ASC);
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