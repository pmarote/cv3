<?php

require __DIR__ . '/../conv/Conv_Audit.php';
require __DIR__ . '/../conv/Conv_DFe.php';

function gera_tabelas_auxiliares() {

  global $pr;

  $db = abredb3_audit();	// em Conv_Audit.php . Para criar, caso não exista. Em seguida, fecha
  $db->close();				// ou seja, audit.db3 sempre existirá, mesmo que esteja vazio
  
  $pr->carrega_db_disponiveis();  
  
  if (strpos($pr->db_disponiveis, 'txt') > 0) gera_tabelas_auxiliares_txt();
  if (strpos($pr->db_disponiveis, 'xml') > 0) gera_tabelas_auxiliares_xml();
  if (strpos($pr->db_disponiveis, 'ecd') > 0) gera_tabelas_auxiliares_ecd();
  if (strpos($pr->db_disponiveis, 'efd') > 0) gera_tabelas_auxiliares_efd();
  //if (strpos($pr->db_disponiveis, 'nfe') > 0) gera_tabelas_auxiliares_nfe();
  //if (strpos($pr->db_disponiveis, 'p32') > 0) gera_tabelas_auxiliares_p32();
  if (strpos($pr->db_disponiveis, 'p17') > 0) gera_tabelas_auxiliares_p17();
  if (strpos($pr->db_disponiveis, 'cat42') > 0) gera_tabelas_auxiliares_cat42();

}



function gera_tabelas_auxiliares_ecd($segunda_vez = False) {

  global $pr;

  $pr->aud_abre_db_e_attach('ecd');
  
  // Preenchimento de Parâmetros
  $paraux = $pr->aud_sql2array("
  -- Seção 1 - O que está abaixo é só para descobrir a conta de resultado
SELECT cod_cta, max(contagem) FROM 
      (SELECT diario.cod_cta AS cod_cta, count(diario.cod_cta) AS contagem
            FROM diario
            LEFT OUTER JOIN contas ON contas.cod_cta = diario.cod_cta
            WHERE substr(dt_lcto, 9, 2) = '31' AND contas.cod_nat + 0 = 3
            GROUP BY diario.cod_cta);
  -- Seção 1 - Fim
");
  $pr->sql_params['ecd']['cta_res'] = $paraux[0]['cod_cta'];  // Código da Conta de Patrimônio Líquido utilizada para
                                // Resultado do Período

  if (! $segunda_vez) {

  $paraux = $pr->aud_sql2array("
  SELECT max(nivel) AS max_nivel FROM contas;
");
  $pr->sql_params['ecd']['max_nivel'] = $paraux[0]['max_nivel'];    // Nível máximo do Plano de Contas

  $paraux = $pr->aud_sql2array("
  SELECT min(nivel) AS min_nivel FROM contas WHERE ind_cta = 'A';
");
  $pr->sql_params['ecd']['min_nivel_cta_analit'] = $paraux[0]['min_nivel'];   // Nível mínimo onde há Contas Analiticas

  }

  $paraux = $pr->aud_sql2array("
  SELECT count(*) AS qtd FROM
   (SELECT DISTINCT  num_lcto FROM lancto);
");
  $pr->sql_params['ecd']['qtd_lcto'] = $paraux[0]['qtd'];   // Quantidade de Lançamentos (pode ser maior que count(*) I200,
                                // no caso de lançamentos criados com soluções

  $paraux = $pr->aud_sql2array("
  SELECT count(*) AS qtd FROM
   (SELECT DISTINCT  num_lcto FROM lancto WHERE nro_deb > 1 AND nro_cred > 1);
");
  $pr->sql_params['ecd']['qtd_lcto_4form'] = $paraux[0]['qtd'];   // Quantidade de Lançamentos da 4ª Formula

  $erros = $pr->aud_sql2array("
  SELECT count(*) AS contagem FROM lancto WHERE nro_deb > 1 AND nro_cred > 1;
");
  $pr->sql_params['ecd']['erros_ndeb_mcred'] = $erros[0]['contagem'];   // Quantidade de Erros de lançamentos lançamentos com muitos n débitos por m créditos

  $qtd_i200 = $pr->aud_sql2array("
  SELECT count(*) AS contagem FROM i200;
");

  if ($pr->sql_params['ecd']['erros_ndeb_mcred'] > 0) {
    $alerta = "\n\n**ATENÇÃO**\n\nOriginalmente, este ECD tem {$qtd_i200[0]['contagem']} lançamentos (quantidade de Regs. I200). ";
  if ($pr->sql_params['ecd']['qtd_lcto'] != $qtd_i200[0]['contagem']) 
    $alerta .= "Com os processamentos de Identificação de Contrapartidas, o número de lançamentos aumentou para {$pr->sql_params['ecd']['qtd_lcto']}. ";
  $alerta .= "Mesmo após o processamento, ainda há {$pr->sql_params['ecd']['qtd_lcto_4form']} lançamentos da 4ª Fórmula, ou seja, situação de vários débitos para vários créditos (N Débitos x M Créditos), com um total de {$erros[0]['contagem']} partidas, que foram decodificadas com Solução 0 (contrapartida em débito ou crédito para contas de compensação de código 'NSddddcccc').\n";
    $alerta .= "Soluções podem ser tentadas no menu Auditoria, opção Roteiro Contábil - Decodificação de Lançamentos N Débitos x M Créditos\n";
  wecho($alerta);
  $pr->mens_final_conf .= $alerta;

  $dta_ini = $pr->aud_sql2array("
    SELECT min(dt_alt) AS min_dt_alt FROM contas;
  ");
  
  // Inclui as Contas de Compensação criadas em contasndebmcred na tabela contas
  $cod_cta_n = array();
  $cod_cta_n[1] = "'NSddddcccc'";
  if ($pr->sql_params['ecd']['max_nivel'] > 2) $cod_cta_n[2] = "'NSddddccccN2'"; else $cod_cta_n[2] = "Null";
  if ($pr->sql_params['ecd']['max_nivel'] > 3) $cod_cta_n[3] = "'NSddddccccN3'"; else $cod_cta_n[3] = "Null";
  if ($pr->sql_params['ecd']['max_nivel'] > 4) $cod_cta_n[4] = "'NSddddccccN4'"; else $cod_cta_n[4] = "Null";
  if ($pr->sql_params['ecd']['max_nivel'] > 5) $cod_cta_n[5] = "'NSddddccccN5'"; else $cod_cta_n[5] = "Null";
  if ($pr->sql_params['ecd']['max_nivel'] > 6) $cod_cta_n[6] = "'NSddddccccN6'"; else $cod_cta_n[6] = "Null";
  if ($pr->sql_params['ecd']['max_nivel'] > 7) $cod_cta_n[7] = "'NSddddccccN7'"; else $cod_cta_n[7] = "Null";
  $cod_cta_n[8] = "Null";
  $cod_cta_n[$pr->sql_params['ecd']['max_nivel']] = "cod_cta";
  $sql_cria_cta = "
  INSERT INTO contas
    SELECT cod_cta, '{$dta_ini[0]['min_dt_alt']}', '05', 'A', {$pr->sql_params['ecd']['max_nivel']},
    {$cod_cta_n[$pr->sql_params['ecd']['max_nivel'] - 1]}, cta, Null, Null, Null, cod_cta,
    {$cod_cta_n[1]}, {$cod_cta_n[2]}, {$cod_cta_n[3]}, {$cod_cta_n[4]},
    {$cod_cta_n[5]}, {$cod_cta_n[6]}, {$cod_cta_n[7]}, {$cod_cta_n[8]}
      FROM contasndebmcred GROUP BY cod_cta;
";
  // Preenche as contas superiores, desde o nível máximo até o nível 1
  $cod_cta_n[0] = "Null";
  for($i = $pr->sql_params['ecd']['max_nivel'] - 1; $i >= 1; $i--) {
    $cod_cta_n[$i + 1] = "Null";
    $sql_cria_cta .= "
  INSERT INTO contas VALUES(
      {$cod_cta_n[$i]}, '{$dta_ini[0]['min_dt_alt']}', '05', 'S', {$i},
    {$cod_cta_n[$i - 1]}, 'Conta de CompensaÃ§Ã£o para NÃ£o SoluÃ§Ã£o de Casos de LanÃ§amentos com N DÃ©bitos x M CrÃ©ditos', Null, Null, Null, 'N{$i}',
    {$cod_cta_n[1]}, {$cod_cta_n[2]}, {$cod_cta_n[3]}, {$cod_cta_n[4]},
    {$cod_cta_n[5]}, {$cod_cta_n[6]}, {$cod_cta_n[7]}, {$cod_cta_n[8]} );
";
  }
    // Preenche as saldos das contas de compensação com base em lançamentos
    
    $sql_cria_cta .= "
INSERT INTO saldos
   SELECT Null, Null, mes, cod_cta, Null, 0, 'D', sum(debitos) AS debitos, sum(creditos) AS creditos, 0, 'D'  FROM
      (SELECT substr(dt_lcto, 1, 7) AS mes, cod_cta_d AS cod_cta, 
         sum(valor) AS debitos, 0 AS creditos
         FROM lancto WHERE cod_cta > 'NS00000000' AND cod_cta <= 'NS99999999'
         GROUP BY mes, cod_cta
      UNION ALL
      SELECT substr(dt_lcto, 1, 7) AS mes, cod_cta_c AS cod_cta, 
         0 AS debitos, sum(valor) AS creditos
         FROM lancto WHERE cod_cta > 'NS00000000' AND cod_cta <= 'NS99999999'
         GROUP BY mes, cod_cta)
   GROUP BY mes, cod_cta;
";
  $pr->aud_prepara($sql_cria_cta);
  
  }

  // Preenchimentos de Saldos nas contas sintéticas
  if ($segunda_vez) {
  $pr->db->exec("DELETE FROM saldos WHERE cod_cta IN (SELECT cod_cta FROM contas WHERE ind_cta = 'S');");
  }
  for($is = $pr->sql_params['ecd']['max_nivel'] + 0; $is >= 2; $is--) {
  $pr->aud_prepara("
  INSERT INTO saldos
   SELECT Null, Null, mes, cod_cta_sup, cod_ccus, 
      abs(vl_sld_ini) AS vl_sld_ini, CASE WHEN vl_sld_ini <= 0 THEN 'D' ELSE 'C' END AS ind_dc_ini,
      vl_deb, vl_cred, abs(vl_sld_fin) AS vl_sld_fin, CASE WHEN vl_sld_fin <= 0 THEN 'D' ELSE 'C' END AS ind_dc_fin
      FROM
      (SELECT mes, contas.cod_cta_sup AS cod_cta_sup, saldos.cod_ccus AS cod_ccus,
          sum(CASE WHEN ind_dc_ini = 'D' THEN -vl_sld_ini ELSE vl_sld_ini END) AS vl_sld_ini,
    sum(vl_deb) AS vl_deb, 
    sum(vl_cred) AS vl_cred,
          sum(CASE WHEN ind_dc_fin = 'D' THEN -vl_sld_fin ELSE vl_sld_fin END) AS vl_sld_fin 
          FROM saldos
          LEFT OUTER JOIN contas ON contas.cod_cta = saldos.cod_cta
    WHERE nivel = {$is}
    GROUP BY cod_cta_sup, cod_ccus, mes);
");
  }

  // Preenchimentos de Saldos das Contas de Resultados Antes do Encerramento das contas analíticas e sintéticas
  $pr->aud_prepara("
DROP TABLE IF EXISTS saldos_ant_enc;
CREATE TABLE saldos_ant_enc AS 
SELECT dt_res, cod_cta, cod_ccus, vl_cta, ind_dc
     FROM i355
     LEFT OUTER JOIN i350 ON i355.ordi350 = i350.ord;
DROP INDEX IF EXISTS saldos_ant_enc_chapri;  
CREATE INDEX saldos_ant_enc_chapri ON saldos_ant_enc (cod_cta ASC);
");
  for($is = $pr->sql_params['ecd']['max_nivel'] + 0; $is >= 2; $is--) {
  $pr->aud_prepara("
   INSERT INTO saldos_ant_enc      
   SELECT dt_res, cod_cta_sup AS cod_cta, cod_ccus, 
      abs(vl_cta_2) AS vl_cta, CASE WHEN vl_cta_2 <= 0 THEN 'D' ELSE 'C' END AS ind_dc
      FROM
      (SELECT dt_res, contas.cod_cta_sup AS cod_cta_sup, saldos_ant_enc.cod_ccus AS cod_ccus,
          sum(CASE WHEN ind_dc = 'D' THEN -vl_cta ELSE vl_cta END) AS vl_cta_2
          FROM  saldos_ant_enc    
          LEFT OUTER JOIN contas ON contas.cod_cta = saldos_ant_enc.cod_cta
    WHERE nivel = {$is}
    GROUP BY cod_cta_sup, cod_ccus, dt_res);
");
  }

  // if (! $segunda_vez) {
  $pr->db->createFunction('sqlite_zera_acum', 'sqlite_zera_acum');
  $pr->db->createFunction('sqlite_acum', 'sqlite_acum');
  $pr->db->createFunction('sqlite_final_mes', 'sqlite_final_mes');
  // }

  // Construção dos Razões
  $pr->aud_prepara("
DROP TABLE IF EXISTS razoes;  
CREATE TABLE razoes AS
  SELECT * FROM
  (SELECT cod_cta_d AS cod_cta_busca, cod_cta_c AS cod_ctapart, * FROM lancto
  UNION ALL
  SELECT cod_cta_c AS cod_cta_busca, cod_cta_d AS cod_ctapart, * FROM lancto
  UNION ALL
  SELECT distinct cod_cta AS cod_cta_busca, Null, '', Null, Null, '', Null, Null, Null, 0, 'ZeraAcum', Null, Null, Null, Null, Null FROM saldos
  UNION ALL
  SELECT cod_cta AS cod_cta_busca, Null, '' AS num_lcto, Null, Null, mes || '-01' AS dt_lcto, Null, 
  CASE WHEN ind_dc_ini = 'D' THEN cod_cta ELSE Null END AS cod_cta_d, 
  CASE WHEN ind_dc_ini = 'C' THEN cod_cta ELSE Null END AS cod_cta_c, 
  vl_sld_ini AS valor, '##NT##Saldo Inicial Mensal' AS hist, Null, Null, Null, Null, Null
  FROM saldos
  UNION ALL
  SELECT cod_cta AS cod_cta_busca, Null, '|' AS num_lcto, Null, Null, sqlite_final_mes(mes || '-01') AS dt_lcto, Null, 
  CASE WHEN ind_dc_fin = 'C' THEN cod_cta ELSE Null END AS cod_cta_d, 
  CASE WHEN ind_dc_fin = 'D' THEN cod_cta ELSE Null END AS cod_cta_c, 
  vl_sld_fin AS valor, '##NC##Saldo Final Mensal' AS hist, Null, Null, Null, Null, Null
  FROM saldos)
  ORDER BY cod_cta_busca ASC, dt_lcto ASC, num_lcto ASC;
DROP TABLE IF EXISTS razoes_aux;  
CREATE TABLE razoes_aux AS 
  SELECT cod_cta_busca, num_lcto, nro_deb, nro_cred, dt_lcto, ind_lcto, cod_cta_d, cod_cta_c, valor, hist, padrao_s, padrao_m, padrao_nr,
  contas.cta AS contra_partida,
  round(sqlite_acum(
    CASE WHEN hist = 'ZeraAcum' THEN sqlite_zera_acum(0) ELSE 
    CASE WHEN cod_cta_d = cod_cta_busca THEN -valor ELSE valor END
  END
  ), 2) AS saldo
  FROM razoes
  LEFT OUTER JOIN contas ON cod_ctapart = contas.cod_cta;
");

  // Saldos Diários, médio, mínimo e máximo
  $pr->aud_prepara("
DROP TABLE IF EXISTS salmedminmax;  
CREATE TABLE salmedminmax AS   
  SELECT cod_cta_busca, 
     round(abs(avgsaldo), 2) AS medsaldo,
     CASE WHEN avgsaldo < 0 THEN 'D' ELSE 'C' END AS medsaldo_dc,
     abs(minsaldo) AS minsaldo,
     CASE WHEN minsaldo < 0 THEN 'D' ELSE 'C' END AS minsaldo_dc,
     abs(maxsaldo) AS maxsaldo,
     CASE WHEN maxsaldo < 0 THEN 'D' ELSE 'C' END AS maxsaldo_dc
     FROM
     (SELECT cod_cta_busca, min(saldo) AS minsaldo, max(saldo) AS maxsaldo, avg(saldo) AS avgsaldo FROM
      (SELECT cod_cta_busca, dt_lcto, saldo
        FROM razoes_aux
  LEFT OUTER JOIN contas ON contas.cod_cta = cod_cta_busca
        WHERE  hist <> 'ZeraAcum' AND num_lcto <> '|' AND contas.nivel = 5
          GROUP BY cod_cta_busca, dt_lcto)
   GROUP BY cod_cta_busca);
DROP INDEX IF EXISTS chav_salmedminmax;  
CREATE INDEX chav_salmedminmax on salmedminmax (cod_cta_busca ASC);
");

  if (! $segunda_vez) {

  // Construção dos Movimentos, para Comparação com Saldos (balancetes)
  $pr->aud_prepara("
DROP TABLE IF EXISTS movto;  
DROP INDEX IF EXISTS chav_movto;
CREATE TABLE movto AS
   SELECT substr(dt_lcto, 1, 7) AS mes, cod_cta, 
   sum(CASE WHEN ind_dc = 'D' THEN vl_dc ELSE 0 END) AS debitos,
   sum(CASE WHEN ind_dc = 'C' THEN vl_dc ELSE 0 END) AS creditos,
   sum(CASE WHEN ind_dc = 'D' THEN (CASE WHEN ind_lcto = 'N' THEN vl_dc ELSE 0 END) ELSE 0 END) AS n_debitos,
   sum(CASE WHEN ind_dc = 'C' THEN (CASE WHEN ind_lcto = 'N' THEN vl_dc ELSE 0 END) ELSE 0 END) AS n_creditos,
   sum(CASE WHEN ind_dc = 'D' THEN (CASE WHEN ind_lcto = 'E' THEN vl_dc ELSE 0 END) ELSE 0 END) AS e_debitos,
   sum(CASE WHEN ind_dc = 'C' THEN (CASE WHEN ind_lcto = 'E' THEN vl_dc ELSE 0 END) ELSE 0 END) AS e_creditos 
   FROM diario
   GROUP BY mes, cod_cta;
CREATE INDEX chav_movto on movto (mes ASC, cod_cta ASC);
");
  }
  
  $pr->salva_sql_params('ecd'); // Não esqueça de salvar... senão, se o usuário fechar e depois abrir de novo não estarão lá...

}


function gera_tabelas_auxiliares_xml() {

  global $pr;

  $db = abredb3_dfe();    // em Conv_DFe.php . Para criar, caso não exista. Em seguida, fecha
  $pr->aud_abre_db_e_attach('dfe,xml,common');

      $pr->aud_prepara("
-- É necessário antes tirar repetições de arquivos
DROP INDEX IF EXISTS xml.arqxmlchav_ace;
CREATE INDEX IF NOT EXISTS xml.arqxmlchav_ace ON arqxml (chav_ace ASC, tipo ASC);
DROP TABLE IF EXISTS xml.aux_arqxml;
CREATE TABLE xml.aux_arqxml AS 
SELECT rowid AS id, chav_ace, tipo FROM arqxml GROUP BY chav_ace, tipo;
DROP INDEX IF EXISTS xml.aux_arqxmlid;
CREATE INDEX IF NOT EXISTS xml.aux_arqxmlid ON aux_arqxml (id ASC);
DROP TABLE IF EXISTS xml.aux_det;
CREATE TABLE xml.aux_det AS
SELECT aux_arqxml.chav_ace AS chav_ace, tag.html AS html
            FROM tag
            LEFT OUTER JOIN aux_arqxml ON aux_arqxml.id = tag.arqxmlrowid
            WHERE aux_arqxml.tipo = 'NFe' AND tag.tag = '<det>';
DROP INDEX IF EXISTS xml.aux_det_chav_ace;
CREATE INDEX IF NOT EXISTS xml.aux_det_chav_ace ON aux_det (chav_ace ASC);
");

  $sql = <<<EOD
-- Juntando os principais grupos de NFe, por chave de acesso
SELECT aux1.*, aux_det.html AS det FROM 
    (SELECT chav_ace, group_concat(ide, '') AS ide, group_concat(emit, '') AS emit, group_concat(dest, '') AS dest, 
      group_concat(total, '') AS total, group_concat(transp, '') AS transp, group_concat(infAdic, '') AS infAdic FROM
        (SELECT chav_ace,
           CASE WHEN tag = '<ide>'   THEN html ELSE '' END AS ide,
           CASE WHEN tag = '<emit>'  THEN html ELSE '' END AS emit,
           CASE WHEN tag = '<dest>'  THEN html ELSE '' END AS dest,
           CASE WHEN tag = '<total>'  THEN html ELSE '' END AS total,
           CASE WHEN tag = '<transp>'  THEN html ELSE '' END AS transp,
           CASE WHEN tag = '<infAdic>'  THEN html ELSE '' END AS infAdic
           FROM
           (SELECT aux_arqxml.chav_ace AS chav_ace, tag.tag AS tag, tag.html AS html
                FROM tag
                LEFT OUTER JOIN aux_arqxml ON aux_arqxml.rowid = tag.arqxmlrowid
                WHERE aux_arqxml.tipo = 'NFe' AND tag.tag IN ('<ide>', '<emit>', '<dest>', '<infAdic>', '<total>', '<transp>') ) )
    GROUP BY chav_ace) AS aux1
LEFT OUTER JOIN aux_det ON aux_det.chav_ace = aux1.chav_ace
LIMIT 1;
EOD;

  $res = $pr->query_log($sql);
  $a_parametros = array();
  while ($linha = $res->fetchArray(SQLITE3_ASSOC)) {
    debug_log(print_r($linha, True));
    $xml_ide     = simplexml_load_string($linha['ide']);
    $xml_emit    = simplexml_load_string($linha['emit']);
    $xml_dest    = simplexml_load_string($linha['dest']);
    $xml_total   = simplexml_load_string($linha['total']);
    $xml_transp  = simplexml_load_string($linha['transp']);
    $xml_infAdic = simplexml_load_string($linha['infAdic']);
    $xml_det     = simplexml_load_string($linha['det']);
    debug_log(print_r($xml_ide, True) . print_r($xml_emit, True) . print_r($xml_dest, True) . print_r($xml_total, True) . print_r($xml_transp, True) . print_r($xml_infAdic, True) . print_r($xml_det, True) );
    $dtaemi = substr($xml_ide->dhEmi, 0, 10);
    $dtaentsai = substr($xml_ide->dhSaiEnt, 0, 10);
    $insert_sql = <<<EOD
INSERT INTO nfe_danfe VALUES(
'{$xml_ide->mod}', '{$cnpj_origem}', '{$linha['chav_ace']}', '{$xml_ide->natOp}',
'{$xml_emit->enderEmit->xLgr}', '{$xml_emit->enderEmit->nro}', '{$xml_emit->enderEmit->xCpl}', 
'{$xml_emit->enderEmit->xBairro}', '{$xml_emit->enderEmit->xMun}', '{$xml_emit->enderEmit->CEP}', 
'{$xml_emit->enderEmit->xPais}', '{$xml_emit->enderEmit->fone}', 
'{$xml_dest->enderDest->xLgr}', '{$xml_dest->enderDest->nro}', '{$xml_dest->enderDest->xCpl}', 
'{$xml_dest->enderDest->xBairro}', '{$xml_dest->enderDest->xMun}', '{$xml_dest->enderDest->CEP}', 
'{$xml_dest->enderDest->xPais}', '{$xml_dest->enderDest->fone}', 
'{$xml_transp->vol->marca}', '{$xml_transp->vol->qVol}', 
'{$xml_total->ICMSTot->vFrete}', '{$xml_total->ICMSTot->vSeg}', '{$xml_total->ICMSTot->vDesc}', 
'{$xml_total->ICMSTot->vII}', '{$xml_total->ICMSTot->vIPI}', '{$xml_total->ICMSTot->vOutro}', 
'{$xml_ide->cNF}', '{$xml_transp->modFrete}', 
'{$xml_transp->transporta->IE}', '{$xml_transp->transporta->xNome}', '{$xml_transp->transporta->xEnder}', 
'{$xml_transp->transporta->xMun}', '{$xml_transp->transporta->UF}', '{$xml_transp->transporta->CNPJ}', 
'{$xml_transp->vol->esp}', '{$xml_transp->vol->qVol}', 
'{$xml_transp->vol->pesoL}', '{$xml_transp->vol->pesoB}', 
'{$xml_infAdic->infAdFisco}', '{$xml_infAdic->infCpl}'
);
EOD;

    $insert_sql = <<<EOD
INSERT INTO nfe VALUES(
'{$linha['chav_ace']}', '{$dtaemi}', '{$dtaentsai}', '{$xml_ide->mod}', '{$xml_ide->serie}', '{$xml_ide->nNF}', ''
);

INSERT INTO nfe
   SELECT 
      chav_ace, dtaemi, Null AS dtaentsai, modelo_, serie, numero, item AS nItem,
      modelo AS origem, cnpj_origem, ie_origem, aaaamm, 
      CASE WHEN cod_sit = 1 THEN 2 ELSE cod_sit END AS  cod_sit,
      CASE WHEN cfop + 0 > 5000 THEN 'S' ELSE 'E' END AS tp_oper, cst, cfop,
      valcon, bcicms, CASE WHEN bcicms = 0 THEN 0 ELSE round(icms/bcicms*100, 2) END AS alicms,icms, valipi + valii AS outimp, 
      bcicmsst, CASE WHEN bcicmsst = 0 THEN 0 ELSE round((icms+icmsst)/bcicmsst*100, 2) END AS alicmsst, icmsst, valipi, valii,
      {$indice}.cnpj, {$indice}.ie, {$indice}.uf, {$indice}.razsoc, dtaina, descina,
      codncm, codpro, Null AS cEAN, descri, qtdpro, unimed,
      Null AS vFrete, Null AS vSeg, Null AS vDesc, Null AS vOutro, Null AS nDI, Null AS UFDesemb, 
      Null AS pRedBC, Null AS pMVAST, Null AS pRedBCST, Null AS vBCSTRet, Null AS vICMSSTRet, Null AS vCredICMSSN
      FROM tabela
   LEFT OUTER JOIN cadesp ON cadesp.cnpj = {$indice}.cnpj;
EOD;

    debug_log("\rInsert SQL = {$insert_sql}\r");

  }

  $pr->db->close();

}

function gera_tabelas_auxiliares_txt() {

	global $pr;

	// primeiro vou abrir txt.db3 , jogar tabelas e campos e um array e depois fechar, para não ter conflitos
	$pr->aud_abre_db_e_attach('txt');

	$dic_dados = array();
	$tabelas = db_lista_tabelas($pr->db);
		//debug_log(print_r($tabelas, True));
	foreach($tabelas as $indice => $valor) {
		$campos = db_lista_campos($pr->db, $valor);
		// se $campos[0] for igual a modelo, vamos seguindo!
		if ($campos[0] = 'modelo') {
			// lê somente a primeira linha
			$linha1 = $pr->aud_sql2array("SELECT * FROM {$valor} LIMIT 1");
				//debug_log(print_r($linha1, True));
			$dic_dados[$valor][$campos[0]] = $linha1[0]['modelo'];
		}
	}
	$pr->db->close();
		//debug_log(print_r($dic_dados, True));
	
	// tabela modelo-cadesp SEMPRE será antes de todo mundo
	foreach($dic_dados as $indice => $valor) {
		// modelo CADESP
		if (isset($dic_dados[$indice]['modelo']) && $dic_dados[$indice]['modelo'] == 'Cadesp') {

			$pr->aud_abre_db_e_attach('common,txt');
			$pr->aud_prepara("
INSERT OR REPLACE INTO cadesp
  SELECT 
    modelo AS origem, cnpj, ie, nomfan, razsoc, dtaini, dtaina, descina, desclgr, xlgr, nro, cpl, xbairro, cep, xmun, uf, 
    prot_cetesb, dta_prot_cetesb, lic_cetesb, dta_lic_cetesb, nrocrc, nomcontab
    FROM {$indice};
");
			$pr->db->close();
			//break;		// inicialmente eu havia jogado um break pra ganhar tempo. Mas algumas vezes Cadesp está em mais de um arquivo. Por isso, tirei
		}
	}

	foreach($dic_dados as $indice => $valor) {
		//debug_log(print_r($dic_dados, True));
		// Fonte CTe de 03 - CTe_CNPJ_Emitente_Tomador_Remetente_Destinatario
		if ($indice == "_03__CTe_CNPJ_Emitente") {

			$db = abredb3_dfe();		// em Conv_DFe.php . Para criar, caso não exista. Em seguida, fecha
			$pr->aud_abre_db_e_attach('dfe,txt,common');
			$pr->aud_prepara("
INSERT INTO cte
SELECT chave_acesso_cte_char, codigo_cfop, descr_nat_operacao, serie, num_cte, data_emissao, tipo_cte, 
  descr_modal, descr_servico,
  municipio_inicial, uf_inicial, municipio_final, uf_final, 
  indicador_tomador_servico, 
  replace(replace(replace(cnpj_tomador, '.', ''), '-', ''), '/', '') + 0 AS cnpj_tomador, 
  num_inscr_est_tomador, razao_social_tomador, uf_tomador, cadesp.dtaina AS dtaina, cadesp.descina AS descina,  
  replace(replace(replace(cnpj_emitente, '.', ''), '-', ''), '/', '') + 0 AS cnpj_emitente,
  num_inscr_est_emitente, razao_social_emitente, uf_emitente, 
  replace(replace(replace(cnpj_remetente, '.', ''), '-', ''), '/', '') + 0 AS cnpj_remetente,
  '' AS rIE, razao_social_remetente, uf_remetente, 
  replace(replace(replace(cnpj_destinatario, '.', ''), '-', ''), '/', '') + 0 AS cnpj_destinatario,
  '' AS dIE, razao_social_destinatario, uf_destinatario, 
  replace(replace(replace(cnpj_expedidor, '.', ''), '-', ''), '/', '') + 0 AS cnpj_expedidor,
  uf_expedidor, 
  replace(replace(replace(cnpj_recebedor, '.', ''), '-', ''), '/', '') + 0 AS cnpj_recebedor,
  uf_recebedor, 
  indsn, descr_cst, 
  valor_bc_icms, perc_reducao_bc, aliquota_icms, valor_icms,
  valor_bc_st_retido, 0 AS pICMSSTRet,valor_icms_st_retido, 
  valor_icms_outrasuf, valor_credito_outorgadopresumido, valor_total_prest_servico, 0 AS vRec
  FROM _03__CTe_CNPJ_Emitente
  LEFT OUTER JOIN cadesp ON cadesp.cnpj = replace(replace(replace(cnpj_tomador, '.', ''), '-', ''), '/', '') + 0;
");			
			$pr->db->close();

			$pr->aud_abre_db_e_attach('dfe, audit, common');
			$pr->aud_prepara("
INSERT INTO modelo
SELECT 'CTe' AS origem, 'DFe' AS tp_origem, eCNPJ AS cnpj_origem, eIE AS ie_origem, substr(dhEmi, 1, 4) || substr(dhEmi, 6, 2) AS aaaamm, 
      0 AS cod_sit ,
      CASE WHEN cfop + 0 > 5000 THEN 'S' ELSE 'E' END AS tp_oper, substr(descr_cst, 1, 2) AS cst, cfop, 'UFIni=' || UFIni AS cfop_nf, 
      vTPrest AS valcon, 
      CASE WHEN UFIni = 'SP' THEN vBC ELSE Null END AS bcicms, 
      CASE WHEN UFIni = 'SP' THEN pICMS ELSE Null END AS alicms, 
      CASE WHEN UFIni = 'SP' THEN vICMS ELSE Null END AS icms, 0 AS outimp, vBCSTRet AS bcicmsst, pICMSSTRet AS alicmsst, vICMSSTRet AS icmsst, 
      tCNPJ AS cnpj, tIE AS ie, tUF AS uf, txNome AS razsoc, cadesp.dtaina AS dtaina, cadesp.descina AS descina,
      dhEmi AS dtaentsai, dhEmi AS dtaemi, 57 AS modelo_, serie AS serie, nCT AS numero, chav_ace
      FROM cte
      LEFT OUTER JOIN cadesp ON cadesp.cnpj = tCNPJ;
");			
			$pr->db->close();

			continue;		// este índice foi processado, tenta o próximo
		}
	
		// se $campos[0] for igual a modelo, vamos seguindo!
		
		// modelo GIA_CFOP
		if (isset($dic_dados[$indice]['modelo']) && strtoupper($dic_dados[$indice]['modelo']) == 'GIA_CFOP') {

			$pr->aud_abre_db_e_attach('audit,txt');
			$pr->aud_prepara("
INSERT INTO modelo
SELECT modelo AS origem, 'GIA' AS tp_origem, cnpj_origem, ie_origem, aaaamm, 0 AS cod_sit,
  CASE WHEN cfop + 0 > 5000 THEN 'S' ELSE 'E' END AS tp_oper, Null AS cst, cfop, cfop AS cfop_nf, 
  valcon, bcicms, 
  CASE WHEN bcicms = 0 THEN 0 ELSE round(icms/bcicms*100, 2) END AS alicms, 
  icms, outimp, Null AS bcicmsst, Null AS alicmsst, icmsst_tituto AS icmsst, 
  Null AS cnpj, Null AS ie, Null AS uf, Null AS razsoc, Null AS dtaina, Null AS descina, 
  Null AS dtaentsai, Null AS dtaemi, Null AS modelo, Null AS serie, Null AS numero, Null AS chav_ace
  FROM {$indice};
");
			$pr->db->close();
			continue;		// este índice foi processado, tenta o próximo
		}

		// modelo NFe_Emit
		if (isset($dic_dados[$indice]['modelo']) && strtoupper($dic_dados[$indice]['modelo']) == 'NFE_EMIT') {
			$pr->aud_abre_db_e_attach('audit,txt,common');
			$pr->aud_prepara("
-- Em DFe, converter o situação 1 (cancelado) para 2 (cancelado conforme tabela 4.1.2). NFe com situação 2 já é cancelado
-- Em DFe destinatário, calcular cfop e calcular cfop_nf, onde necessário
CREATE INDEX IF NOT EXISTS txt.{$indice}_chav_cnpj ON {$indice} (chav_ace ASC, cfop ASC);
INSERT INTO modelo
SELECT 
  modtmp.origem, tp_origem, cnpj_origem, ie_origem, aaaamm, 
  cod_sit, tp_oper, cst, cfop, cfop_nf, 
  valcon, bcicms, 
  CASE WHEN bcicms = 0 THEN 0 ELSE round(icms/bcicms*100, 2) END AS alicms,
  icms, outimp, bcicmsst, 
  CASE WHEN bcicmsst = 0 THEN 0 ELSE round((icms+icmsst)/bcicmsst*100, 2) END AS alicmsst, 
  icmsst, modtmp.cnpj, modtmp.ie, modtmp.uf, modtmp.razsoc, cadesp.dtaina, cadesp.descina, dtaentsai, dtaemi, modelo_ AS modelo, serie, numero, chav_ace
  FROM
    (SELECT modelo AS origem, 'DFe' AS tp_origem, cnpj_origem, ie_origem, aaaamm, 
      CASE WHEN cod_sit = 1 THEN 2 ELSE cod_sit END AS  cod_sit,
      CASE WHEN cfop + 0 > 5000 THEN 'S' ELSE 'E' END AS tp_oper, cst, cfop, cfop AS cfop_nf, 
      sum(valcon) AS valcon, sum(bcicms) AS bcicms, Null AS alicms, 
      sum(icms) AS icms, sum(valipi) + sum(valii) AS outimp, sum(bcicmsst) AS bcicmsst, Null AS alicmsst, sum(icmsst) AS icmsst, 
      cnpj, ie, uf, razsoc, Null AS dtaina, Null AS descina, 
      dtaemi AS dtaentsai, dtaemi, modelo_, serie, numero, chav_ace
      FROM {$indice}
      GROUP BY chav_ace, cfop) AS modtmp
   LEFT OUTER JOIN cadesp ON cadesp.cnpj = modtmp.cnpj;
");
			$pr->db->close();

			$db = abredb3_dfe();		// em Conv_DFe.php . Para criar, caso não exista. Em seguida, fecha
			$db->close();				
			$pr->aud_abre_db_e_attach('dfe,txt,common');
			$pr->aud_prepara("
INSERT INTO nfe
   SELECT 
      chav_ace, dtaemi, Null AS dtaentsai, modelo_, serie, numero, item AS nItem,
      modelo AS origem, cnpj_origem, ie_origem, aaaamm, 
      CASE WHEN cod_sit = 1 THEN 2 ELSE cod_sit END AS  cod_sit,
      CASE WHEN cfop + 0 > 5000 THEN 'S' ELSE 'E' END AS tp_oper, cst, cfop,
      valcon, bcicms, CASE WHEN bcicms = 0 THEN 0 ELSE round(icms/bcicms*100, 2) END AS alicms,icms, valipi + valii AS outimp, 
      bcicmsst, CASE WHEN bcicmsst = 0 THEN 0 ELSE round((icms+icmsst)/bcicmsst*100, 2) END AS alicmsst, icmsst, valipi, valii,
      {$indice}.cnpj, {$indice}.ie, {$indice}.uf, {$indice}.razsoc, dtaina, descina,
      codncm, codpro, Null AS cEAN, descri, qtdpro, unimed,
      Null AS vFrete, Null AS vSeg, Null AS vDesc, Null AS vOutro, Null AS nDI, Null AS UFDesemb, 
      Null AS pRedBC, Null AS pMVAST, Null AS pRedBCST, Null AS vBCSTRet, Null AS vICMSSTRet, Null AS vCredICMSSN
      FROM {$indice}
   LEFT OUTER JOIN cadesp ON cadesp.cnpj = {$indice}.cnpj;
");
			$pr->db->close();
			
			continue;		// este índice foi processado, tenta o próximo
		}

		// modelo NFe_Dest e NFe_Dest_ufs
		if (isset($dic_dados[$indice]['modelo']) && (strtoupper($dic_dados[$indice]['modelo']) == 'NFE_DEST_SP' || strtoupper($dic_dados[$indice]['modelo']) == 'NFE_DEST_UFS')) {
			$pr->aud_abre_db_e_attach('audit,txt,common');
			$pr->aud_prepara("
-- Em DFe, converter o situação 1 (cancelado) para 2 (cancelado conforme tabela 4.1.2). NFe com situação 2 já é cancelado
-- Em DFe destinatário, calcular cfop e calcular cfop_nf, onde necessário
CREATE INDEX IF NOT EXISTS txt.{$indice}_chav_cnpj ON {$indice} (chav_ace ASC, cfop_nf ASC);
INSERT INTO modelo
SELECT 
  modtmp.origem, tp_origem, cnpj_origem, ie_origem, aaaamm, 
  cod_sit, tp_oper, cst, cfop_res, cfop_nf, 
  valcon, bcicms, 
  CASE WHEN bcicms = 0 THEN 0 ELSE round(icms/bcicms*100, 2) END AS alicms,
  icms, outimp, bcicmsst, 
  CASE WHEN bcicmsst = 0 THEN 0 ELSE round((icms+icmsst)/bcicmsst*100, 2) END AS alicmsst, 
  icmsst, modtmp.cnpj, modtmp.ie, modtmp.uf, modtmp.razsoc, cadesp.dtaina, cadesp.descina, dtaentsai, dtaemi, modelo_, serie, numero, chav_ace
  FROM
    (SELECT modelo AS origem, 'DFe' AS tp_origem, cnpj_origem, ie_origem, aaaamm, 
      CASE WHEN cod_sit = 1 THEN 2 ELSE cod_sit END AS  cod_sit,
      CASE WHEN cfop_nf + 0 > 5000 THEN 'E' ELSE 'D' END AS tp_oper, cst, cfop_nf AS cfop, cfop_nf, 
      sum(valcon) AS valcon, sum(bcicms) AS bcicms, Null AS alicms, 
      sum(icms) AS icms, sum(valipi) + sum(valii) AS outimp, sum(bcicmsst) AS bcicmsst, Null AS alicmsst, sum(icmsst) AS icmsst, 
      cnpj, ie, uf, razsoc, Null AS dtaina, Null AS descina, 
      dtaemi AS dtaentsai, dtaemi, modelo_, serie, numero, chav_ace
      FROM {$indice}
      GROUP BY chav_ace, cfop_nf) AS modtmp
  LEFT OUTER JOIN cadesp ON cadesp.cnpj = modtmp.cnpj
  LEFT OUTER JOIN cfop_entsai ON cfop_entsai.cfop_dfe = cfop_nf;
");
			$pr->db->close();		

			$db = abredb3_dfe();		// em Conv_DFe.php . Para criar, caso não exista. Em seguida, fecha
			$db->close();				
			$pr->aud_abre_db_e_attach('dfe,txt,common');
			
			// Em NFe_Dest_SP, pode haver os seguintes campos de ressarcimento, que serão jogados nos seguites campos de nfe:
			// 		valor_credito_simples_nacional		valor_base_calculo_icms_st_retido_operacao_anterior		valor_icms_st_retido_operacao_anterior
			//		vCredICMSSN							vBCSTRet												vICMSSTRet
			$campos = db_lista_campos($pr->db, $indice);
			$vCredICMSSN 	= 'Null';
			if (in_array('valor_credito_simples_nacional',$campos) !== False) $vCredICMSSN = 'valor_credito_simples_nacional';
			$vBCSTRet		= 'Null';
			if (in_array('valor_base_calculo_icms_st_retido_operacao_anterior',$campos) !== False) $vBCSTRet = 'valor_base_calculo_icms_st_retido_operacao_anterior';
			$vICMSSTRet		= 'Null';
			if (in_array('valor_icms_st_retido_operacao_anterior',$campos) !== False) $vICMSSTRet = 'valor_icms_st_retido_operacao_anterior';
			//	debug_log(print_r($campos, True));
			
	
			$sql = "
INSERT INTO nfe
   SELECT 
      chav_ace, dtaemi, Null AS dtaentsai, modelo_, serie, numero, item AS nItem,
      modelo AS origem, cnpj_origem, ie_origem, aaaamm, 
      CASE WHEN cod_sit = 1 THEN 2 ELSE cod_sit END AS  cod_sit,
      CASE WHEN cfop_nf + 0 > 5000 THEN 'E' ELSE 'D' END AS tp_oper, cst, cfop_nf,
      valcon, bcicms, CASE WHEN bcicms = 0 THEN 0 ELSE round(icms/bcicms*100, 2) END AS alicms,icms, valipi + valii AS outimp, 
      bcicmsst, CASE WHEN bcicmsst = 0 THEN 0 ELSE round((icms+icmsst)/bcicmsst*100, 2) END AS alicmsst, icmsst, valipi, valii,
      {$indice}.cnpj, {$indice}.ie, {$indice}.uf, {$indice}.razsoc, dtaina, descina,
      codncm, codpro, Null AS cEAN, descri, qtdpro, unimed,
      Null AS vFrete, Null AS vSeg, Null AS vDesc, Null AS vOutro, Null AS nDI, Null AS UFDesemb, 
      Null AS pRedBC, Null AS pMVAST, Null AS pRedBCST, {$vBCSTRet} AS vBCSTRet, {$vICMSSTRet} AS vICMSSTRet, {$vCredICMSSN} AS vCredICMSSN
      FROM {$indice}
   LEFT OUTER JOIN cadesp ON cadesp.cnpj = {$indice}.cnpj;
";
			$pr->aud_prepara($sql);
				// debug_log("#TabAux.php-206#{$sql}#");
			$pr->db->close();

			continue;		// este índice foi processado, tenta o próximo
		}

		// modelo DANFE
		if (isset($dic_dados[$indice]['modelo']) && strtoupper($dic_dados[$indice]['modelo']) == 'DANFE') {
			$db = abredb3_dfe();		// em Conv_DFe.php . Para criar, caso não exista. Em seguida, fecha
			$db->close();				

			$pr->aud_abre_db_e_attach('dfe,txt');
			$pr->aud_prepara("
INSERT OR REPLACE INTO nfe_danfe
SELECT 
  modelo, cnpj_origem, chav_ace, natOp, exLgr, enro, exCpl, exBairro, exMun, eCep, exPais, eTel, dxLgr, dnro, dxCpl, dxBairro,
  dxMun, dCep, dxPais, dTel, tmarca, qVol, vFrete, vSeg, vDesc, vII, vIPI, vOutro, Null AS cNf, 
  modFrete, tIE, txNome, txEnder, txMun, tUF, tCNPJ, tesp, tnVol, tpesoL, tpesoB, infAdFisco, infCpl
  FROM {$indice};
");
			$pr->db->close();		
			continue;		// este índice foi processado, tenta o próximo
		}

	}

}

function cria_nf_itens() {

  global $pr;

  $pr->aud_prepara("
DROP TABLE IF EXISTS nf;
CREATE TABLE nf( cnpj, ie, cadrazsoc, qtdies, cadie, dtaabecnpj, dtacancnpj, dtaemi, municipio, uf, modelo, serie, numero INT, emit,
  valtot REAL, bcicms REAL, valicms REAL, isenta REAL, outras REAL, bcicmsst REAL, icmsst REAL, despaces REAL, codant, valipi REAL,
  cadcnae, cnaedescri, chav_ace TEXT, tpOper);
CREATE INDEX IF NOT EXISTS 'nf_chav_ace' ON nf (chav_ace ASC);
DROP TABLE IF EXISTS itensnf;
CREATE TABLE itensnf( cnpj, ie, uf, dtaemi, modelo, serie, numero INT, cfop INT, cst, item INT,
  codpro TEXT, descri TEXT, unimed TEXT, codncm,
  qtdpro REAL, valpro REAL, valdda REAL, valprolq REAL, rdbcic REAL, bcicms REAL, icms REAL, IVA REAL, aliquota REAL,
  bcicmsst REAL, icmsst REAL, valipi REAL, chav_ace TEXT, cfop_nf INT);
CREATE INDEX IF NOT EXISTS 'itensnf_chav_ace_item' ON itensnf (chav_ace ASC, item ASC);
");

  // Observação sobre o campo chav_ace
  // a partir da versão 0.9.9.5 o relacionamento entre itens e nf é sempre feito por chave de acesso
  // se não se tratar de Nota Fiscal Eletrônica, a chave de acesso vai ser criada pelo Conversor automaticamente, no seguinte formato:
  // 	 Código da UF	 AAMM da emissão	 CNPJ do Emitente	 Modelo	 Série	 Número da NF-e	 Código Numérico	 DV
  // 				'ChavAc'	CNPJ do Rem/Dest	Modelo	Serie	Numero	'GeradPConv'
  // Qtd de caract.	   6		      14			   2	  3		9		     10
  // Obs: CNPJ do remetente nas entradas ou do destinatário nas saídas
  // Exemplo: ChavAc5425004800018701001000000406GeradPConv  -> NF emitida por 54250048000187, Mod 1, Série 1, Nro 406
  // O SQL para gerar isto é:
  // 'ChavAc' || substr('00000000000000' || trim(cnpj), -14) || substr('00' || trim(modelo), -2) || substr('000' || trim(serie), -3) || substr('000000000' || trim(numero), -9) || 'GeradPConv' AS chav_ace

}

function gera_tabelas_auxiliares_nfe() {

  global $pr;

  $pr->aud_abre_db_e_attach('nfe, cad, ies');

  // Começo antes com alguns ajustes que não puderam ser feitos em Conv_NFe, relativos a nfe_bo_infoview...
	// Ajustando tpOper -> Tipo de Operação na Empresa Fiscalizada: Entrada ou Saída ?
	// Não há o campo tpxNF (NF de Entrada ou Saída) em NFe destinatário do BO... Então foi assumido como tpxNF = Saída e tpOper = Entrada
	// Caso tenha sido lido itens, corrige agora com UPDATE os casos em que tpxNF = Entrada e tpOper = Saída
	  $pr->aud_prepara("UPDATE nfe_bo_infoview SET tpxNF = 'Entrada', tpOper = 'SaÃ­da' WHERE chav_ace IN (SELECT chav_ace FROM det_bo_infoview2 WHERE cfop_entsai > 5000 AND cfop < 5000);");
	// Para ver as NFes do caso acima, utilize: 
	// SELECT * FROM nfe_bo_infoview WHERE chav_ace IN (SELECT chav_ace FROM det_bo_infoview2 WHERE  cfop_entsai > 5000 AND cfop < 5000);
	  
	// ** OBSERVAÇÃO **
	// Atualmente o caso acima não existe na prática
	// Exemplos de Notas Fiscais de entrada emitidos por Terceiros: N.Fiscais de Devolução e Notas Fiscais de Entradas de Sucata, emitidas por força da legislação
	// Nestes casos, emitente = terceiro, destinatário = Null e remetente a empresa fiscalizada
	// Por isso não aparece no BO como NFe Destinatário...
	// Mas no BO Destinatário Itens, essas NFs aparecem...
	// Para visualizar esses casos, utilize:
	// SELECT  * FROM det_bo_infoview2 
	//		LEFT OUTER JOIN nfe_bo_infoview
    //		ON nfe_bo_infoview.chav_ace = det_bo_infoview2.chav_ace
    //		WHERE  det_bo_infoview2.cfop_entsai > 5000 AND det_bo_infoview2.cfop < 5000 AND nfe_bo_infoview.chav_ace IS NULL;
	
	// Para corrigir isso, vamos criar manualmente os nfe_bo_infoview, com os dados possíveis a partir de det_bo_infoview2
	  $pr->aud_prepara("
DROP TABLE IF EXISTS cnpj_razsoc_nfes_aux;
DROP TABLE IF EXISTS cnpj_razsoc_nfes_aux2;
CREATE TABLE cnpj_razsoc_nfes_aux (cnpj int, razsoc);
CREATE TABLE cnpj_razsoc_nfes_aux2 (cnpj int, razsoc);
INSERT INTO cnpj_razsoc_nfes_aux2
      SELECT eCNPJ AS cnpj, exNome AS razsoc FROM nfe_bo_infoview
      UNION ALL
      SELECT dCNPJ AS cnpj, dxNome AS razsoc FROM nfe_bo_infoview;      
CREATE INDEX cnpj_razsoc_nfes_aux_chapri2 ON cnpj_razsoc_nfes_aux2 (cnpj ASC);
INSERT INTO cnpj_razsoc_nfes_aux
      SELECT cnpj, razsoc FROM cnpj_razsoc_nfes_aux2 GROUP BY cnpj;
CREATE INDEX cnpj_razsoc_nfes_aux_chapri ON cnpj_razsoc_nfes_aux (cnpj ASC);
DROP TABLE IF EXISTS cnpj_razsoc_nfes_aux2;
INSERT INTO nfe_bo_infoview
  SELECT nfe_ins_aux.chav_ace, nfe_ins_aux.serie, nfe_ins_aux.nNF, nfe_ins_aux.dEmi, nfe_ins_aux.eCNPJ, nfe_ins_aux.eUF,
    CASE WHEN cnpj_razsoc_nfes_aux.razsoc IS NULL THEN nfe_ins_aux.exNome ELSE cnpj_razsoc_nfes_aux.razsoc END AS exNome,
    nfe_ins_aux.eIE, nfe_ins_aux.dCNPJ, nfe_ins_aux.dUF, 
    CASE WHEN cnpj_razsoc_nfes_aux_d.razsoc IS NULL THEN nfe_ins_aux.dxNome ELSE cnpj_razsoc_nfes_aux_d.razsoc END AS dxNome,
    nfe_ins_aux.vBC, nfe_ins_aux.vICMS, nfe_ins_aux.vBCST, nfe_ins_aux.vST, nfe_ins_aux.vNF, 
    nfe_ins_aux.tpxNF, nfe_ins_aux.tpOper, nfe_ins_aux.infAdFisco, nfe_ins_aux.infCpl FROM
      (SELECT  det_bo_infoview2.chav_ace AS chav_ace,  det_bo_infoview2.serie AS serie, det_bo_infoview2.nNF AS nNF, det_bo_infoview2.dEmi AS dEmi,
            	det_bo_infoview2.eCNPJ AS eCNPJ, det_bo_infoview2.eUF AS eUF, 'Nome NÃ£o DisponÃ­vel... Dados de NFe criados com base em NFe_DestinatÃ¡rio-Itens, porque nÃ£o vieram em NFe_DestinatÃ¡rio' AS exNome, 
            	det_bo_infoview2.eIE AS eIE, det_bo_infoview2.dCNPJ AS dCNPJ, det_bo_infoview2.dUF AS dUF, 'Nome NÃ£o DisponÃ­vel... Dados de NFe criados com base em NFe_DestinatÃ¡rio-Itens, porque nÃ£o vieram em NFe_DestinatÃ¡rio' AS dxNome,
            	sum (det_bo_infoview2.vBC) AS vBC, sum(det_bo_infoview2.vICMS) AS vICMS, sum(det_bo_infoview2.vBCST) AS vBCST, sum(det_bo_infoview2.vICMSST) AS vST,
            	sum(det_bo_infoview2.vProd - det_bo_infoview2.valdda + det_bo_infoview2.despaces + det_bo_infoview2.vIPI + det_bo_infoview2.vICMSST) AS vNF,
            	'Entrada' AS tpxNF, 'SaÃ­da' AS tpOper, 'Dados de NFe criados com base em NFe_DestinatÃ¡rio-Itens, porque nÃ£o vieram em NFe_DestinatÃ¡rio' AS infAdFisco, '' AS infCpl
           FROM det_bo_infoview2 
           LEFT OUTER JOIN nfe_bo_infoview
           ON nfe_bo_infoview.chav_ace = det_bo_infoview2.chav_ace
           WHERE  det_bo_infoview2.cfop_entsai > 5000 AND det_bo_infoview2.cfop < 5000 AND nfe_bo_infoview.chav_ace IS NULL
           GROUP BY det_bo_infoview2.chav_ace) AS nfe_ins_aux
   LEFT OUTER JOIN cnpj_razsoc_nfes_aux ON cnpj_razsoc_nfes_aux.cnpj = nfe_ins_aux.eCNPJ
   LEFT OUTER JOIN cnpj_razsoc_nfes_aux AS cnpj_razsoc_nfes_aux_d ON cnpj_razsoc_nfes_aux_d.cnpj = nfe_ins_aux.dCNPJ;
");
  

  cria_nf_itens();
  
  // Insere dados a partir de nfe
  $pr->aud_prepara("
INSERT INTO nf 
   SELECT cnpj, ie, cadrazsoc, qtdies, cadie, dtaabecnpj, dtacancnpj, dtaemi, municipio, uf, modelo, serie, numero, emit, 
   valtot, bcicms, valicms, isenta, outras, bcicmsst, icmsst, despaces, codant, valipi, cadcnae, cnaedescri, chav_ace, tpOper FROM
   (SELECT cnpj, ie, cadrazsoc, qtdies, cadie, dtaabecnpj, dtacancnpj, dtaemi, round(ie / 1000000000 - 0.5) AS mun, uf, modelo, serie, numero, emit, 
   valtot, bcicms, valicms, null AS isenta, null AS outras, bcicmsst, icmsst, null AS despaces,
   null AS codant, null AS valipi, cadcnae, cad.cnae.descri AS cnaedescri, chav_ace, tpOper
   FROM
   (SELECT cnpj, 
   CASE WHEN ie2 = '' THEN cadie ELSE ie2 END AS ie,
   cadrazsoc2 AS cadrazsoc, qtdies, cadie, dtaabecnpj, dtacancnpj,
   cadcnae,
   dtaemi, uf, modelo, serie, numero, emit, valtot, bcicms, valicms, bcicmsst, icmsst, chav_ace, tpOper
   FROM
   (SELECT
   CASE WHEN  tpOper = 'Entrada' THEN eCNPJ ELSE dCNPJ END AS cnpj,
   CASE WHEN  tpOper = 'Entrada' THEN eIE ELSE '' END AS ie2,
   CASE WHEN  tpOper = 'Entrada' THEN exNome ELSE dxNome END AS cadrazsoc2,
   dEmi AS dtaemi, 
   CASE WHEN  tpOper = 'Entrada' THEN eUF ELSE dUF END AS uf,
   55 AS modelo, serie, nNF AS numero,
   'T' AS emit, vNF AS valtot,
   vBC AS bcicms, vICMS AS valicms, vBCST AS bcicmsst, vST AS icmsst, chav_ace, 
   CASE WHEN  tpOper = 'Entrada' THEN 'E' ELSE 'S' END AS tpOper
   FROM nfe)
   LEFT OUTER JOIN ies.cadcnpj ON cnpj = ies.cadcnpj.cadcnpj)
   LEFT OUTER JOIN cad.cnae ON cadcnae = cad.cnae.cod
   ORDER BY dtaemi) AS tabaux
   LEFT OUTER JOIN cad.municipios_ie ON mun = (cad.municipios_ie.cod + 0);
");

  // Insere dados a partir de nfe_bo_infoview
  $pr->aud_prepara("
INSERT INTO nf 
   SELECT cnpj, ie, cadrazsoc, qtdies, cadie, dtaabecnpj, dtacancnpj, dtaemi, municipio, uf, modelo, serie, numero, emit, 
   valtot, bcicms, valicms, isenta, outras, bcicmsst, icmsst, despaces, codant, valipi, cadcnae, cnaedescri, chav_ace, tpOper FROM
   (SELECT cnpj, ie, cadrazsoc, qtdies, cadie, dtaabecnpj, dtacancnpj, dtaemi, round(ie / 1000000000 - 0.5) AS mun, uf, modelo, serie, numero, emit, 
   valtot, bcicms, valicms, null AS isenta, null AS outras, bcicmsst, icmsst, null AS despaces,
   null AS codant, null AS valipi, cadcnae, cad.cnae.descri AS cnaedescri, chav_ace, tpOper
   FROM
   (SELECT cnpj, 
   CASE WHEN ie2 = '' THEN cadie ELSE ie2 END AS ie,
   cadrazsoc2 AS cadrazsoc, qtdies, cadie, dtaabecnpj, dtacancnpj, 
   cadcnae,
   dtaemi, uf, modelo, serie, numero, emit, valtot, bcicms, valicms, bcicmsst, icmsst, chav_ace, tpOper
   FROM
   (SELECT
   CASE WHEN  tpOper = 'Entrada' THEN eCNPJ ELSE dCNPJ END AS cnpj,
   CASE WHEN  tpOper = 'Entrada' THEN eIE ELSE '' END AS ie2,
   CASE WHEN  tpOper = 'Entrada' THEN exNome ELSE dxNome END AS cadrazsoc2,
   dEmi AS dtaemi, 
   CASE WHEN  tpOper = 'Entrada' THEN eUF ELSE dUF END AS uf,
   55 AS modelo, serie, nNF AS numero,
   CASE WHEN tpOper = tpxNF THEN 'P' ELSE 'T' END AS emit, vNF AS valtot,
   vBC AS bcicms, vICMS AS valicms, vBCST AS bcicmsst, vST AS icmsst, chav_ace, 
   CASE WHEN  tpOper = 'Entrada' THEN 'E' ELSE 'S' END AS tpOper
   FROM nfe_bo_infoview)
   LEFT OUTER JOIN ies.cadcnpj ON cnpj = ies.cadcnpj.cadcnpj)
   LEFT OUTER JOIN cad.cnae ON cadcnae = cad.cnae.cod
   ORDER BY dtaemi) AS tabaux
   LEFT OUTER JOIN cad.municipios_ie ON mun = (cad.municipios_ie.cod + 0);
");

  // Criação da Tabela itensnf
  // Se houver os XMLs, a tabela det tem dados... então itensnf serão extraídos de det
  // Se não houver os XMLs, a tabela det não tem dados... então itensnf serão extraídos de det_bo_infoview2
  $result = $pr->db->query("SELECT count(*) AS contagem FROM det;");
  $row = $result->fetchArray(SQLITE3_ASSOC);
  if ($row['contagem'] > 0) {
	wecho('Construindo tabela de itens (itensnf) a partir dos arquivos XML');
	$pr->aud_prepara("
INSERT INTO itensnf 
   SELECT 
   CASE WHEN  tpOper = 'Entrada' THEN eCNPJ ELSE dCNPJ END AS cnpj,
   CASE WHEN  tpOper = 'Entrada' THEN eIE   ELSE Null  END AS ie,
   CASE WHEN  tpOper = 'Entrada' THEN eUF ELSE dUF END AS uf, dEmi AS dtaemi, 55 AS modelo, nfe_bo_infoview.serie, 
   nfe_bo_infoview.nNF AS numero, CFOP AS cfop, CST AS cst, nItem AS item, cProd AS codpro, xProd AS descri,
   uTrib AS unimed, NCM AS codncm, qTrib AS qtdpro, vProd AS valpro, 0 AS valdda, vProd AS valprolq, pRedBC AS rdbcic,
   det.vBC AS bcicms, det.vICMS AS icms, pMVAST AS IVA, pICMS AS aliquota, det.vBCST AS bcicmsst, vICMSST AS icmsst,
   vIPI AS valipi, nfe_bo_infoview.chav_ace AS chav_ace, CFOP as cfop_nf
   FROM nfe_bo_infoview, det WHERE nfe_bo_infoview.chav_ace = det.chav_ace;
");
  } else {
	wecho('*');
	$pr->aud_prepara("
-- No cálculo do IVA não está sendo considerado ainda vFrete e vSeguro, que estão em NFe
INSERT INTO itensnf 
SELECT 
   CASE WHEN  tpOper = 'Entrada' THEN det_bo_infoview2.eCNPJ ELSE det_bo_infoview2.dCNPJ END AS cnpj, 
   CASE WHEN  tpOper = 'Entrada' THEN det_bo_infoview2.eIE ELSE det_bo_infoview2.dIE END AS ie, 
   CASE WHEN  tpOper = 'Entrada' THEN det_bo_infoview2.eUF ELSE det_bo_infoview2.dUF END AS uf, 
   det_bo_infoview2.dEmi AS dtaemi, 55 AS modelo, det_bo_infoview2.serie AS serie, 
   det_bo_infoview2.nNF AS numero, det_bo_infoview2.cfop_entsai AS cfop, det_bo_infoview2.CST AS cst, det_bo_infoview2.nItem AS item,
   det_bo_infoview2.cProd AS codpro, det_bo_infoview2.xProd AS descri,
   det_bo_infoview2.uCom AS unimed, det_bo_infoview2.NCM AS codncm, det_bo_infoview2.qCom AS qtdpro, det_bo_infoview2.vProd AS valpro,
   det_bo_infoview2.valdda AS valdda, det_bo_infoview2.vProd - det_bo_infoview2.valdda + det_bo_infoview2.despaces AS valprolq,
   CASE WHEN vProd - valdda + despaces > 0 AND vBC <> '' AND vBC > 0 THEN (1 - round(vBC / (vProd - valdda + despaces + vIPI), 4)) * 100 ELSE '' END AS rdbcic,
   det_bo_infoview2.vBC AS bcicms, det_bo_infoview2.vICMS AS icms,
   CASE WHEN det_bo_infoview2.vBCST <> '' AND det_bo_infoview2.vBCST > 0 THEN round(det_bo_infoview2.vBCST * 100 / (vProd - valdda + despaces + vIPI) - 100,2) ELSE '' END AS IVA,
   det_bo_infoview2.pICMS AS aliquota,
   det_bo_infoview2.vBCST AS bcicmsst,
   det_bo_infoview2.vICMSST AS icmsst,
   det_bo_infoview2.vIPI AS valipi, det_bo_infoview2.chav_ace AS chav_ace, CFOP as cfop_nf
   FROM det_bo_infoview2;
");  
  }

}

function gera_tabelas_auxiliares_efd() {

	global $pr;

	$pr->aud_abre_db_e_attach('audit,efd,common');

	$pr->aud_prepara("
-- Dados da empresa fiscalizada disponivel tambem em audit, caso necessario
INSERT INTO main.o000 SELECT * FROM efd.o000;
INSERT INTO main.o005 SELECT * FROM efd.o005;
INSERT INTO main.o100 SELECT * FROM efd.o100;
");

  // modelo C190
  $pr->aud_prepara("
-- Inserindo os dados de c190 do efd.db3 em modelo
INSERT INTO modelo
    SELECT pt1.origem AS origem, tp_origem, cnpj_origem, ie_origem,
      aaaamm, cod_sit, tp_oper, cst,cfop, cfop_nf,
      valcon, bcicms, alicms, icms, outimp, bcicmsst, 
      CASE WHEN bcicmsst = 0 THEN 0 ELSE round(icmsst/bcicmsst*100, 2) END AS alicmsst, 
      icmsst, 
      o150.cnpj AS cnpj, o150.ie AS ie, tab_munic.uf AS uf, o150.nome AS razsoc,
      cadesp.dtaina AS dtaina, cadesp.descina AS descina,
      dtaentsai, dtaemi, modelo, serie, numero, chav_ace
      FROM 
       (SELECT round(c190.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(c190.ord / 10000000 + 0.5) * 10000000 AS ordmax, cod_part,
            'EFD_C190' AS origem, 'RES' AS tp_origem, (SELECT DISTINCT cnpj FROM o000) AS cnpj_origem, (SELECT DISTINCT ie FROM o000) AS ie_origem, 
            '20' || substr(c190.ord, 1, 4) AS aaaamm, cod_sit AS cod_sit, CASE WHEN cfop < 5000 THEN 'E' ELSE 'S' END AS tp_oper, cfop AS cfop, cfop AS cfop_nf,
            vl_opr AS valcon, c190.vl_bc_icms AS bcicms, c190.aliq_icms AS alicms, c190.vl_icms AS icms, c190.vl_icms_st AS icmsst, 
            c190.vl_ipi AS outimp, c190.vl_bc_icms_st AS bcicmsst, cst_icms AS cst,
            dt_e_s AS dtaentsai, dt_doc AS dtaemi, cod_mod AS modelo, ser AS serie, num_doc AS numero, chv_nfe AS chav_ace
            FROM c190
            LEFT OUTER JOIN c100 ON c100.ord = c190.ordC100) AS pt1
      LEFT OUTER JOIN o150 ON o150.cod_part = pt1.cod_part  AND o150.ord > pt1.ordmin AND o150.ord < pt1.ordmax
      LEFT OUTER JOIN tab_munic ON tab_munic.cod = o150.cod_mun
      LEFT OUTER JOIN cadesp ON cadesp.cnpj = o150.cnpj;
");

  // modelo C490
  $pr->aud_prepara("
-- Inserindo os dados de c490 do efd.db3 em modelo
INSERT INTO modelo
    SELECT pt1.origem AS origem, tp_origem, cnpj_origem, ie_origem,
      aaaamm, cod_sit, tp_oper, cst,cfop, cfop_nf,
      valcon, bcicms, alicms, icms, outimp, bcicmsst, 
      CASE WHEN bcicmsst = 0 THEN 0 ELSE round(icmsst/bcicmsst*100, 2) END AS alicmsst, 
      icmsst, 
      Null AS cnpj, Null AS ie, 'SP' AS uf, Null AS razsoc,
      Null AS dtaina, Null AS descina,
      dtaentsai, dtaemi, modelo, serie, numero, Null AS chav_ace
      FROM 
       (SELECT round(c490.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(c490.ord / 10000000 + 0.5) * 10000000 AS ordmax, 
            'EFD_C490' AS origem, 'RES' AS tp_origem, (SELECT DISTINCT cnpj FROM o000) AS cnpj_origem, (SELECT DISTINCT ie FROM o000) AS ie_origem, 
            '20' || substr(c490.ord, 1, 4) AS aaaamm, 0 AS cod_sit, CASE WHEN cfop < 5000 THEN 'E' ELSE 'S' END AS tp_oper, cfop AS cfop, cfop AS cfop_nf,
            vl_opr AS valcon, c490.vl_bc_icms AS bcicms, c490.aliq_icms AS alicms, c490.vl_icms AS icms, 0 AS icmsst, 
            0 AS outimp, 0 AS bcicmsst, cst_icms AS cst,
            dt_doc AS dtaentsai, dt_doc AS dtaemi, 'ECF' AS modelo, Null AS serie, num_coo_fin AS numero
            FROM c490
            LEFT OUTER JOIN c405 ON c405.ord = c490.ordC405) AS pt1;
");

  // modelo C590
  $pr->aud_prepara("
-- Inserindo os dados de c590 do efd.db3 em modelo
INSERT INTO modelo
    SELECT pt1.origem AS origem, tp_origem, cnpj_origem, ie_origem,
      aaaamm, cod_sit, tp_oper, cst,cfop, cfop_nf,
      valcon, bcicms, alicms, icms, outimp, bcicmsst, 
      CASE WHEN bcicmsst = 0 THEN 0 ELSE round(icmsst/bcicmsst*100, 2) END AS alicmsst, 
      icmsst, 
      o150.cnpj AS cnpj, o150.ie AS ie, tab_munic.uf AS uf, o150.nome AS razsoc,
      cadesp.dtaina AS dtaina, cadesp.descina AS descina,
      dtaentsai, dtaemi, modelo, serie, numero, Null AS chav_ace
      FROM 
       (SELECT round(c590.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(c590.ord / 10000000 + 0.5) * 10000000 AS ordmax, cod_part,
            'EFD_C590' AS origem, 'RES' AS tp_origem, (SELECT DISTINCT cnpj FROM o000) AS cnpj_origem, (SELECT DISTINCT ie FROM o000) AS ie_origem, 
            '20' || substr(c590.ord, 1, 4) AS aaaamm, cod_sit AS cod_sit, CASE WHEN cfop < 5000 THEN 'E' ELSE 'S' END AS tp_oper, cfop AS cfop, cfop AS cfop_nf,
            vl_opr AS valcon, c590.vl_bc_icms AS bcicms, c590.aliq_icms AS alicms, c590.vl_icms AS icms, c590.vl_icms_st AS icmsst, 
            0 AS outimp, c590.vl_bc_icms_st AS bcicmsst, cst_icms AS cst,
            dt_e_s AS dtaentsai, dt_doc AS dtaemi, cod_mod AS modelo, ser AS serie, num_doc AS numero
            FROM c590
            LEFT OUTER JOIN c500 ON c500.ord = c590.ordC500) AS pt1
      LEFT OUTER JOIN o150 ON o150.cod_part = pt1.cod_part  AND o150.ord > pt1.ordmin AND o150.ord < pt1.ordmax
      LEFT OUTER JOIN tab_munic ON tab_munic.cod = o150.cod_mun
      LEFT OUTER JOIN cadesp ON cadesp.cnpj = o150.cnpj;
");

  // modelo C850
  $pr->aud_prepara("
-- Inserindo os dados de c850 do efd.db3 em modelo
INSERT INTO modelo
    SELECT pt1.origem AS origem, tp_origem, cnpj_origem, ie_origem,
      aaaamm, cod_sit, tp_oper, cst,cfop, cfop_nf,
      valcon, bcicms, alicms, icms, outimp, bcicmsst, 
      CASE WHEN bcicmsst = 0 THEN 0 ELSE round(icmsst/bcicmsst*100, 2) END AS alicmsst, 
      icmsst, 
      cnpj_cpf AS cnpj, cadesp.ie AS ie, cadesp.uf AS uf, cadesp.razsoc AS razsoc,
      cadesp.dtaina AS dtaina, cadesp.descina AS descina,
      dtaentsai, dtaemi, modelo, serie, numero, chav_ace
      FROM 
       (SELECT round(c850.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(c850.ord / 10000000 + 0.5) * 10000000 AS ordmax, c800.cnpj_cpf AS cnpj_cpf,
            'EFD_C850' AS origem, 'RES' AS tp_origem, (SELECT DISTINCT cnpj FROM o000) AS cnpj_origem, (SELECT DISTINCT ie FROM o000) AS ie_origem, 
            '20' || substr(c850.ord, 1, 4) AS aaaamm, 0 AS cod_sit, CASE WHEN cfop < 5000 THEN 'E' ELSE 'S' END AS tp_oper, cfop AS cfop, cfop AS cfop_nf,
            vl_opr AS valcon, c850.vl_bc_icms AS bcicms, c850.aliq_icms AS alicms, c850.vl_icms AS icms, 0 AS icmsst, 
            0 AS outimp, 0 AS bcicmsst, cst_icms AS cst,
            dt_doc AS dtaentsai, dt_doc AS dtaemi, cod_mod AS modelo, Null AS serie, num_cfe AS numero, chv_cfe AS chav_ace
            FROM c850
            LEFT OUTER JOIN c800 ON c800.ord = c850.ordC800) AS pt1
      LEFT OUTER JOIN cadesp ON cadesp.cnpj = pt1.cnpj_cpf;
");

  // modelo D190
  $pr->aud_prepara("
-- Inserindo os dados de d190 do efd.db3 em modelo
INSERT INTO modelo
    SELECT pt1.origem AS origem, tp_origem, cnpj_origem, ie_origem,
      aaaamm, cod_sit, tp_oper, cst,cfop, cfop_nf,
      valcon, bcicms, alicms, icms, outimp, bcicmsst, 
      CASE WHEN bcicmsst = 0 THEN 0 ELSE round(icmsst/bcicmsst*100, 2) END AS alicmsst, 
      icmsst, 
      o150.cnpj AS cnpj, o150.ie AS ie, tab_munic.uf AS uf, o150.nome AS razsoc,
      cadesp.dtaina AS dtaina, cadesp.descina AS descina,
      dtaentsai, dtaemi, modelo, serie, numero, chav_ace
      FROM 
       (SELECT round(d190.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(d190.ord / 10000000 + 0.5) * 10000000 AS ordmax, cod_part,
            'EFD_D190' AS origem, 'RES' AS tp_origem, (SELECT DISTINCT cnpj FROM o000) AS cnpj_origem, (SELECT DISTINCT ie FROM o000) AS ie_origem, 
            '20' || substr(d190.ord, 1, 4) AS aaaamm, cod_sit AS cod_sit, CASE WHEN cfop < 5000 THEN 'E' ELSE 'S' END AS tp_oper, cfop AS cfop, cfop AS cfop_nf,
            vl_opr AS valcon, d190.vl_bc_icms AS bcicms, d190.aliq_icms AS alicms, d190.vl_icms AS icms, 0 AS icmsst, 
            0 AS outimp, 0 AS bcicmsst, cst_icms AS cst,
            dt_a_p AS dtaentsai, dt_doc AS dtaemi, cod_mod AS modelo, 
			CASE WHEN sub <> '' THEN ser || '-' || sub ELSE ser END AS serie, num_doc AS numero, chv_cte AS chav_ace
            FROM d190
            LEFT OUTER JOIN d100 ON d100.ord = d190.ordD100) AS pt1
      LEFT OUTER JOIN o150 ON o150.cod_part = pt1.cod_part  AND o150.ord > pt1.ordmin AND o150.ord < pt1.ordmax
      LEFT OUTER JOIN tab_munic ON tab_munic.cod = o150.cod_mun
      LEFT OUTER JOIN cadesp ON cadesp.cnpj = o150.cnpj;
");

  // modelo D590
  $pr->aud_prepara("
-- Inserindo os dados de d590 do efd.db3 em modelo
INSERT INTO modelo
    SELECT pt1.origem AS origem, tp_origem, cnpj_origem, ie_origem,
      aaaamm, cod_sit, tp_oper, cst,cfop, cfop_nf,
      valcon, bcicms, alicms, icms, outimp, bcicmsst, 
      CASE WHEN bcicmsst = 0 THEN 0 ELSE round(icmsst/bcicmsst*100, 2) END AS alicmsst, 
      icmsst, 
      o150.cnpj AS cnpj, o150.ie AS ie, tab_munic.uf AS uf, o150.nome AS razsoc,
      cadesp.dtaina AS dtaina, cadesp.descina AS descina,
      dtaentsai, dtaemi, modelo, serie, numero, Null AS chav_ace
      FROM 
       (SELECT round(d590.ord / 10000000 - 0.49) * 10000000 AS ordmin, round(d590.ord / 10000000 + 0.5) * 10000000 AS ordmax, cod_part,
            'EFD_D590' AS origem, 'RES' AS tp_origem, (SELECT DISTINCT cnpj FROM o000) AS cnpj_origem, (SELECT DISTINCT ie FROM o000) AS ie_origem, 
            '20' || substr(d590.ord, 1, 4) AS aaaamm, cod_sit AS cod_sit, CASE WHEN cfop < 5000 THEN 'E' ELSE 'S' END AS tp_oper, cfop AS cfop, cfop AS cfop_nf,
            vl_opr AS valcon, d590.vl_bc_icms AS bcicms, d590.aliq_icms AS alicms, d590.vl_icms AS icms, 0 AS icmsst, 
            0 AS outimp, 0 AS bcicmsst, cst_icms AS cst,
            dt_a_p AS dtaentsai, dt_doc AS dtaemi, cod_mod AS modelo, ser AS serie, num_doc AS numero
            FROM d590
            LEFT OUTER JOIN d500 ON d500.ord = d590.ordD500) AS pt1
      LEFT OUTER JOIN o150 ON o150.cod_part = pt1.cod_part  AND o150.ord > pt1.ordmin AND o150.ord < pt1.ordmax
      LEFT OUTER JOIN tab_munic ON tab_munic.cod = o150.cod_mun
      LEFT OUTER JOIN cadesp ON cadesp.cnpj = o150.cnpj;
");

}

function gera_tabelas_auxiliares_cat42() {

  global $pr;

  $pr->aud_abre_db_e_attach('cat42');

  $pr->db->createFunction('sqlite_customedio_qtd', 'sqlite_customedio_qtd');
  $pr->db->createFunction('sqlite_customedio_valor', 'sqlite_customedio_valor');
  $pr->db->createFunction('sqlite_customedio_custo', 'sqlite_customedio_custo');
  
  $pr->aud_prepara("
CREATE TABLE aux1 AS
SELECT r02aux.aamm AS aamm, r02aux.cod_item AS codpro, r02aux.reg AS reg,
o200.descr_item AS descri, o200.unid_inv AS unimed, 
r02aux.data AS data, r02aux.cfop AS cfop, r02aux.ind_oper AS ind_oper,
r02aux.chv_doc AS chv_doc, r02aux.num_item AS num_item,
CASE WHEN r02aux.ind_oper = 0 THEN r02aux.qtd ELSE '' END AS qtd_ent,
CASE WHEN r02aux.ind_oper = 0 THEN r02aux.icms_tot ELSE '' END AS icms_tot_ent,
CASE WHEN r02aux.ind_oper = 1 THEN r02aux.qtd ELSE '' END AS qtd_sai,
CASE WHEN r02aux.ind_oper = 1 THEN round(r02aux.icms_tot / r02aux.qtd, 2) ELSE '' END AS val_uni_sai,
CASE WHEN r02aux.ind_oper = 1 THEN r02aux.icms_tot ELSE '' END AS icms_tot_sai,
CASE WHEN r02aux.ind_oper = 1 AND r02aux.cod_legal < 1 AND r02aux.cod_legal > 4 THEN r02aux.icms_tot ELSE '' END AS cl_d_sai,
CASE WHEN r02aux.ind_oper = 1 AND (r02aux.cod_legal = 1 OR r02aux.cod_legal = 3) THEN r02aux.vl_confr ELSE '' END conf_20,
CASE WHEN r02aux.ind_oper = 1 AND (r02aux.cod_legal = 2 OR r02aux.cod_legal = 4) THEN r02aux.vl_confr ELSE '' END conf_21,
CASE WHEN r02aux.ind_oper = 0 THEN -r02aux.qtd ELSE r02aux.qtd END AS qtdliq,
r02aux.cod_legal AS cod_legal
    FROM (
        SELECT substr(ord, 1, 4) AS aamm, cod_item, 1050 AS reg, '20' || substr(ord, 1, 2) || '-' || substr(ord, 3, 2) || '-01' AS data, 0 AS ind_oper, 2 AS cfop,  'Saldo Inicial' AS chv_doc, 0 AS num_item, qtd_ini AS qtd, icms_tot_ini AS icms_tot, 0 AS vl_confr, 'sk' AS cod_legal,
        round(ord / 10000000 - 0.49) * 10000000 AS ordmin, round(ord / 10000000 + 0.5) * 10000000 AS ordmax FROM l050
	UNION ALL
        SELECT substr(ord, 1, 4) AS aamm, cod_item, 1100 AS reg, data, ind_oper, cfop,  chv_doc, num_item, qtd, icms_tot, vl_confr, cod_legal,
        round(ord / 10000000 - 0.49) * 10000000 AS ordmin, round(ord / 10000000 + 0.5) * 10000000 AS ordmax FROM l100
    UNION ALL
        SELECT substr(ord, 1, 4) AS aamm, cod_item, 1200 AS reg,data, ind_oper, cfop, 
             'R1200#' || cod_part || '#'  || cod_mod  || '#'  || ecf_fab  || '#'  || ser  || '#'  ||  num_doc AS chv_doc, num_item, qtd, icms_tot, vl_confr, cod_legal,
	    round(ord / 10000000 - 0.49) * 10000000 AS ordmin, round(ord / 10000000 + 0.5) * 10000000 AS ordmax FROM l200
	UNION ALL
        SELECT substr(ord, 1, 4) AS aamm, cod_item, 1050 AS reg, '20' || substr(ord, 1, 2) || '-' || substr(ord, 3, 2) || '-32' AS data, 1 AS ind_oper, 6 AS cfop,  'Saldo Final' AS chv_doc, 0 AS num_item, qtd_fim AS qtd, icms_tot_fim AS icms_tot, 0 AS vl_confr, 'z' AS cod_legal,
        round(ord / 10000000 - 0.49) * 10000000 AS ordmin, round(ord / 10000000 + 0.5) * 10000000 AS ordmax FROM l050
    ) AS r02aux
LEFT OUTER JOIN o200 ON o200.cod_item = r02aux.cod_item AND o200.ord > r02aux.ordmin AND o200.ord < r02aux.ordmax
;
CREATE INDEX IF NOT EXISTS aux1_chapri ON aux1 (aamm, codpro, data);
CREATE TABLE aux2 AS
  SELECT * FROM aux1 ORDER BY aamm, codpro, data;
DROP TABLE aux1;
CREATE TABLE aux1 AS
  SELECT *,
	sqlite_customedio_qtd(qtdliq, conf_21) AS saldo, sqlite_customedio_valor(0) AS valor, sqlite_customedio_custo(0) AS customedio
	FROM aux2;
");  
}

function gera_tabelas_auxiliares_p17() {

  global $pr;

  $pr->aud_abre_db_e_attach('p17');
  
  $pr->db->createFunction('sqlite_customedio_qtd', 'sqlite_customedio_qtd');
  $pr->db->createFunction('sqlite_customedio_valor', 'sqlite_customedio_valor');
  $pr->db->createFunction('sqlite_customedio_custo', 'sqlite_customedio_custo');

  
/*
CÓDIGO COMPLEMENTAR DA OPERAÇÃO:
00 O campo assumirá o conteúdo '00' para todas as operações que não as especificadas a seguir. Também Saldo Inicial.
01 Deve complementar as operações de devolução de venda, cuja saída tenha sido para comercialização subseqüente.
02 Deve complementar as operações de devolução de venda, cuja saída tenha sido destinada a usuário ou consumidor final.
03 Deve complementar as operações de saída destinada a comercialização subseqüente ou transferência de mercadoria, quando essas operações próprias estiverem amparadas por isenção ou não incidência. Este código complementar deve ser utilizado também nas correspondentes devoluções de venda.
04 Deve complementar as operações de saída destinada a consumidor ou usuário final, quando essas operações próprias estiverem amparadas por isenção ou não incidência. Este código complementar deve ser utilizado também nas correspondentes devoluções de venda.
05 Deve complementar as saídas de mercadorias adquiridas ou recebidas de terceiros em operação sujeita ao regime de substituição tributária, na condição de contribuinte substituído, cuja saída tenha sido destinada à comercialização subseqüente ou transferência de mercadoria, quando a operação subseqüente estiver amparada por isenção ou não incidência, exceto a isenção da microempresa. Este código complementar deve ser utilizado também nas correspondentes devoluções de venda.
06 Deve complementar as operações de saída de mercadorias adquiridas ou recebidas de terceiros em operação sujeita ao regime de substituição tributária, na condição de contribuinte substituído, cuja saída tenha sido destinada à comercialização subseqüente.
07 Deve complementar o lançamento efetuado em decorrência de emissão de documento fiscal relativo à operação ou prestação também registrada em equipamento Emissor de Cupom Fiscal - ECF, quando a saída destinar-se a contribuintes do imposto e a comercialização subseqüente. 
// Vai gerar Ressarcimento Outras UFs apenas os codope 00 e 06 ! 
// Codope 03, 04 ou 05 gera Ressarcimento Isenção ou Não Incidência
// Saídas outras UFs e Fato Gerador não realizado - confronto com ICMS das entradas;
// Saídas consumidor final (out/2016 >) ou Saídas Isentas/Não Incidentes - confrontom com ICMS das saídas;
*/
  $pr->aud_prepara("
-- para simplificar o proximo select, vou criar r02aux que e os campos utilizados de r02 adicionado das linhas de r03p
-- os campos de r02 utilizados sao: codpro, dtaemi, cfop, numero, serie, qtdpro, vtbcstrt, vtbcstef, arq
-- os correspondentes r03p     sao: codpro, dtaemi, 5405, numord,    '', qtddia, vtbcstrt, vtbcstef, arq
-- bcstefent vai ser corrigido no proximo select
-- Alteração 1902 - Como o período da Cat 17-99 não envolve ressarcimento com consumidor final, foi retirada a hipótese quando vem do R02 (ainda calcula do R03)
-- Alteração 1902 - Codope 01 não dá ressarcimento
CREATE TABLE aux1 AS
SELECT auxpt1.codpro AS codpro, descri, unimed, r05.alqicm AS alqicm, dtaemi, cfop,
  nument, serent, qtdent, vtbcstrtent,
  numsai, sersai, qtdsai, valunisai, saicuf, saifnr, saiise, saiouf, saicom,
  bcstefsai, bcstefent, qtdliq
  FROM (SELECT r02aux.codpro AS codpro, r04.descri AS descri, r04.unimed AS unimed, r02aux.dtaemi AS dtaemi, r02aux.cfop AS cfop,
    CASE WHEN cfop < 5000 THEN r02aux.numero ELSE '' END AS nument,
    CASE WHEN cfop < 5000 THEN r02aux.serie ELSE '' END AS serent,
    CASE WHEN cfop < 5000 THEN r02aux.qtdpro ELSE '' END AS qtdent,
    CASE WHEN cfop < 5000 THEN r02aux.vtbcstrt ELSE '' END AS vtbcstrtent,
    CASE WHEN cfop >= 5000 THEN r02aux.numero ELSE '' END AS numsai,
    CASE WHEN cfop >= 5000 THEN r02aux.serie ELSE '' END AS sersai,
    CASE WHEN cfop >= 5000 THEN r02aux.qtdpro ELSE '' END AS qtdsai,
    CASE WHEN cfop >= 5000 THEN round(r02aux.vtbcstrt / r02aux.qtdpro, 2) ELSE '' END AS valunisai,
    CASE WHEN cfop >= 5000 AND cfop < 6000 AND ie = 'ISENTOCF' THEN r02aux.vtbcstrt ELSE '' END AS saicuf,
	CASE WHEN cfop = 1     THEN r02aux.vtbcstrt ELSE '' END AS saifnr,
	'' AS saiise,
    CASE WHEN cfop IN (6404, 6409) AND r02aux.codope IN ('00', '06') THEN r02aux.vtbcstrt ELSE '' END AS saiouf,
    CASE WHEN cfop >= 5000 AND cfop < 6000 AND ie <> 'ISENTOCF' THEN r02aux.vtbcstrt ELSE '' END AS saicom,
    CASE WHEN cfop >= 5000 AND cfop < 6000 AND ie = 'ISENTOCF' THEN r02aux.vtbcstef ELSE '' END AS bcstefsai,
    CASE WHEN cfop >= 5000 AND r02aux.codope IN ('00', '06') THEN r02aux.vtbcstef ELSE '' END AS bcstefent,
    CASE WHEN cfop >= 5000 THEN -r02aux.qtdpro ELSE r02aux.qtdpro END AS qtdliq,
    r02aux.arq AS arq
    FROM (
		SELECT ie, codpro, dtaemi, cfop, codope, numero, serie, qtdpro, vtbcstrt, vtbcstef, arq FROM r02
		UNION ALL
		SELECT 'ISENTOCF', codpro, dtaemi, 5405, '00' AS codope, 'ecf' || numord,    '', qtddia, vtbcstrt, vtbcstef, arq FROM r03p
	) AS r02aux
    LEFT OUTER JOIN r04 ON r04.arq = r02aux.arq AND r04.codpro = r02aux.codpro) AS auxpt1
  LEFT OUTER JOIN r05 ON r05.arq = auxpt1.arq AND r05.codpro = auxpt1.codpro AND auxpt1.dtaemi >= r05.dtaini AND auxpt1.dtaemi <= r05.dtafin
  ORDER BY codpro, dtaemi;
-- Ha casos em que o saldo inicial (SK) não está com dia 01. Exemplo: 29/02/2011 - A linha abaixo muda para dia 01 - 01/02/2011
UPDATE aux1 SET dtaemi = substr(dtaemi, 1, 8) || '01' WHERE cfop = 2 AND substr(dtaemi, 9, 2) <> '01';
-- Continuando - Em tres partes - primeiro saldo inicial, depois entradas por fim saídas - esta é a ordem dentro de cada codpro e dtaemi
CREATE TABLE aux2 AS
SELECT * FROM aux1 WHERE cfop = 2
UNION ALL
SELECT * FROM aux1 WHERE cfop < 5000 AND cfop <> 2
UNION ALL
SELECT * FROM aux1 WHERE cfop > 5000
UNION ALL
SELECT codpro, 'Zerador de Saldo' AS descri, '' AS unimed, 0 AS alqicm, mes || '-32' AS dtaemi, 0 AS cfop, 
    0 AS nument, '' AS serent, 0 AS qtdent, 0 AS vtbcstrtent, 
    0 AS numsai, '' AS sersai, 0 AS qtdsai, 0 AS valunisai, 0 AS saicuf, 0 AS saifnr, 0 AS saiise, 0 AS saiouf, 0 AS saicom, 
	0 AS bcstefsai, 0 AS bcstefent, 'z' AS qtdliq
    FROM (SELECT codpro, substr(dtaemi, 1, 7) AS mes FROM aux1 GROUP BY codpro, mes)
ORDER BY codpro, dtaemi;
DROP TABLE aux1;
CREATE TABLE aux1 AS
  SELECT codpro, descri, unimed, alqicm, dtaemi, cfop, serent, nument, qtdent, vtbcstrtent, 
    sersai, numsai, qtdsai, valunisai, saicuf, saifnr, saiise, saiouf, saicom,
	bcstefsai, 
	CASE WHEN bcstefsai <> '' OR saicom <> '' THEN '' ELSE bcstefent END AS bcstefent,
	qtdliq, 
	sqlite_customedio_qtd(qtdliq, vtbcstrtent) AS saldo, sqlite_customedio_valor(0) AS valor, sqlite_customedio_custo(0) AS customedio
	FROM aux2;
CREATE TABLE back1 AS SELECT * FROM aux1;
DROP TABLE aux2;
DELETE FROM aux1 WHERE qtdliq = 'z';
CREATE TABLE mod3 AS
 SELECT codpro, descri, unimed, alqicm, dtaemi, cfop, serent, nument, qtdent, vtbcstrtent, 
    sersai, numsai, qtdsai, valunisai, saicuf, saifnr, saiise, saiouf, saicom,
	bcstefsai, bcstefent,
	saldo, round(valor / saldo, 4) AS vubcst, valor AS vtbcst,
        CASE WHEN saicuf <> '' THEN round(qtdsai * customedio, 2) ELSE '' END AS saicuf_calc,
        CASE WHEN saifnr <> '' THEN round(qtdsai * customedio, 2) ELSE '' END AS saifnr_calc,
        CASE WHEN saiise <> '' THEN round(qtdsai * customedio, 2) ELSE '' END AS saiise_calc,
        CASE WHEN saiouf <> '' THEN round(qtdsai * customedio, 2) ELSE '' END AS saiouf_calc,
        CASE WHEN saicom <> '' THEN round(qtdsai * customedio, 2) ELSE '' END AS saicom_calc
	FROM aux1;
DROP TABLE aux1;
CREATE TABLE mod1 AS
SELECT codpro, descri, unimed, alqicm, mes, 
    CASE WHEN saicuf = 0 THEN '' ELSE saicuf END AS saicuf, 
    CASE WHEN saifnr = 0 THEN '' ELSE saifnr END AS saifnr, 
    CASE WHEN saiise = 0 THEN '' ELSE saiise END AS saiise, 
    CASE WHEN saiouf = 0 THEN '' ELSE saiouf END AS saiouf,
    CASE WHEN bcstefsai = 0 THEN '' ELSE bcstefsai END AS bcstefsai, 
    CASE WHEN bcstefent = 0 THEN '' ELSE bcstefent END AS bcstefent,
    bccompl, CASE WHEN icmscompl = 0 THEN '' ELSE icmscompl END AS icmscompl,
    bcressar, CASE WHEN icmsressar = 0 THEN '' ELSE icmsressar END  AS icmsressar, 
    CASE WHEN saicuf_calc = 0 THEN '' ELSE saicuf_calc END AS saicuf_calc, 
    CASE WHEN saifnr_calc = 0 THEN '' ELSE saifnr_calc END AS saifnr_calc, 
    CASE WHEN saiise_calc = 0 THEN '' ELSE saiise_calc END AS saiise_calc, 
    CASE WHEN saiouf_calc = 0 THEN '' ELSE saiouf_calc END AS saiouf_calc,
    bccompl_calc, CASE WHEN icmscompl_calc = 0 THEN '' ELSE icmscompl_calc END AS icmscompl_calc,
    bcressar_calc, CASE WHEN icmsressar_calc = 0 THEN '' ELSE icmsressar_calc END  AS icmsressar_calc, 
    icmsresult, icmsresult_calc
  FROM
  (SELECT codpro, descri, unimed, alqicm, mes, 
    saicuf, saifnr, saiise, saiouf,
    bcstefsai, bcstefent,
    bccompl, round(bccompl * alqicm /100, 2) AS icmscompl, bcressar, round(bcressar * alqicm /100, 2) AS icmsressar, 
    saicuf_calc, saifnr_calc, saiise_calc, saiouf_calc,
    bccompl_calc, round(bccompl_calc * alqicm/100, 2) AS icmscompl_calc, bcressar_calc, round(bcressar_calc * alqicm /100, 2) AS icmsressar_calc, 
    round((bccompl - bcressar) * alqicm /100, 2) AS icmsresult,
    round((bccompl_calc - bcressar_calc) * alqicm /100, 2) AS icmsresult_calc
  FROM
  (SELECT codpro, descri, unimed, alqicm, mes, 
    saicuf, saifnr, saiise, saiouf,
    bcstefsai, bcstefent,
    CASE WHEN saicuf + saifnr + saiise + saiouf - bcstefsai - bcstefent < 0 THEN -(saicuf + saifnr + saiise + saiouf - bcstefsai - bcstefent) ELSE '' END AS bccompl,
    CASE WHEN saicuf + saifnr + saiise + saiouf - bcstefsai - bcstefent > 0 THEN saicuf + saifnr + saiise + saiouf - bcstefsai - bcstefent ELSE '' END AS bcressar,
    saicuf_calc, saifnr_calc, saiise_calc, saiouf_calc,
    CASE WHEN saicuf_calc + saifnr_calc + saiise_calc + saiouf_calc - bcstefsai - bcstefent < 0 THEN -(saicuf_calc + saifnr_calc + saiise_calc + saiouf_calc - bcstefsai - bcstefent) ELSE '' END AS bccompl_calc,
    CASE WHEN saicuf_calc + saifnr_calc + saiise_calc + saiouf_calc - bcstefsai - bcstefent > 0 THEN saicuf_calc + saifnr_calc + saiise_calc + saiouf_calc - bcstefsai - bcstefent ELSE '' END AS bcressar_calc
    FROM
  (SELECT codpro, descri, unimed, alqicm, substr(dtaemi, 1, 7) AS mes, 
    sum(saicuf) AS saicuf, sum(saifnr) AS saifnr, sum(saiise) AS saiise, sum(saiouf) AS saiouf,
    sum(bcstefsai) AS bcstefsai, sum(bcstefent) AS bcstefent,
    sum(saicuf_calc) AS saicuf_calc, sum(saifnr_calc) AS saifnr_calc, sum(saiise_calc) AS saiise_calc, sum(saiouf_calc) AS saiouf_calc
    FROM mod3
    GROUP BY codpro, mes)));
");

}

?> 