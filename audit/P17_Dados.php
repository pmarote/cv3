<?php

$pr->aud_registra(new PrMenu("p17_dados", "P_17", "Dados dos arquivos Portaria CAT 17/99", "p17"));

function p17_dados() {

  global $pr;

  $pr->inicia_excel('P17_Dados');

  $form_final = '
	$this->excel_orientacao(2);		// paisagem
	$this->excel_zoom_visualizacao(100);
';

  $sql = "SELECT * FROM R05";
  $col_format = array(
"D:D" => "#.##0,00");
  $cabec = array(
		'Código' => "Código da mercadoria",
		'Data Inicial' => "A data do início do período de acordo com a alíquota de ICMS vigente para a mercadoria",
		'Data Final' => "A data do fim do período de acordo com a alíquota de ICMS vigente para a mercadoria",
		'Alíquota de ICMS' => "Alíquota de ICMS vigente para as operações internas com a mercadoria (com 2 decimais)",
		'Arq' => "Nome e Pasta do Arquivo onde foi lido o registro");
  $pr->abre_excel_sql('R05', 'Registros 05', $sql, $col_format, $cabec, $form_final);

  $sql = "SELECT * FROM R04";
  $col_format = array(
		"A:A" => "");
  $cabec = array(
		'Código' => "Código da mercadoria",
		'Descrição' => "Descrição da Mercadoria",
		'Unidade' => "Unidade de medida da mercadoria",
		'Arq' => "Nome e Pasta do Arquivo onde foi lido o registro");
  $pr->abre_excel_sql('R04', 'Registros 04', $sql, $col_format, $cabec, $form_final);

  $sql = "SELECT * FROM R03P";
  $col_format = array(
		"C:C" => "#.##0", 
		"D:D" => "#.##0,000", 
		"E:F" => "#.##0,00");
  $cabec = array(
		'Mest/Analit/Merc(P)' => "Mestre / Analítico / Mercadoria (P) ",
		'DtaEmi' => "Data de emissão dos cupons fiscais",
		'NumOrd' => "Número atribuído pelo estabelecimento ao equipamento",
		'QtdPro_Dia' => "Quantidade diária de saída da mercadoria (informado em CodPro) no presente equipamento (informado no campo NumOrd)",
		'VTBCSTRet_Dia' => "Valor total da base de cálculo do ICMS de retenção na substituição tributária para a mercadoria no dia
Corresponde ao valor resultante da multiplicação da quantidade diária pelo valor unitário da base de cálculo da retenção",
		'Valor de Confronto' => "Base de Cálculo Efetiva para CONFRONTO nas Saídas Destinadas a Consumidor ou Usuário Final (coluna 15 do controle de estoque Mod.3)

P.Cat 17, Art.4º, § 1º - O valor de confronto previsto na alínea e do inciso IV (colunas 15 e 16) será registrado, conforme o caso, como segue:
1 - na coluna Base de Cálculo Efetiva na Saída a Consumidor ou Usuário Final (15), o valor da correspondente operação de saída:
a) realizada com consumidor ou usuário final;
b) na hipótese em que a parcela do imposto retido a ser ressarcido corresponder à saída subseqüente amparada por isenção ou não incidência, nos termos da alínea b do item 2 do § 4º do artigo 248 do Regulamento do ICMS;",
		'CodPro' => "Código do Produto",
		'Arq' => "Nome e Pasta do Arquivo onde foi lido o registro");
  $pr->abre_excel_sql('R03P', 'Registros 03P', $sql, $col_format, $cabec, $form_final);

  $sql = "SELECT * FROM R03A";
  $col_format = array(
		"C:C" => "#.##0", 
		"D:E" => "#.##0,00");
  $cabec = array(
		'Mest/Analit/Merc(P)' => "Mestre / Analítico / Mercadoria (P) ",
		'DtaEmi' => "Data de emissão dos cupons fiscais",
		'NumOrd' => "Número atribuído pelo estabelecimento ao equipamento",
		'Sit Trib Aliq' => "Identificador da Situação Tributária / Alíquota do ICMS ou:
F - Substituição Tributária
I - Isento
N - Não Incidência
CANC - Cancelamento
DESC - Desconto",
		'Vl.Acum.Tot.Parc.' => "Valor acumulado no final do dia no totalizador parcial da situação tributária / alíquota indicada no campo 05 (com 2 decimais)",
		'Arq' => "Nome e Pasta do Arquivo onde foi lido o registro");
  $pr->abre_excel_sql('R03A', 'Registros 03A', $sql, $col_format, $cabec, $form_final);

  $sql = "SELECT * FROM R03M";
  $col_format = array(
		"C:H" => "#.##0", 
		"I:J" => "#.##0,00");
  $cabec = array(
		'Mest/Analit/Merc(P)' => "Mestre / Analítico / Mercadoria (P) ",
		'DtaEmi' => "Data de emissão dos cupons fiscais",
		'NumOrd' => "Número atribuído pelo estabelecimento ao equipamento",
		'NumSer' => "Número de série de fabricação do equipamento",
		'Modelo' => "Código do modelo do documento fiscal",
		'NdiCOO' => "Número do primeiro documento fiscal emitido no dia (Número do Contador de Ordem de Operação - COO)",
		'NdfCOO' => "Número do último documento fiscal emitido no dia (Número do Contador de Ordem de Operação - COO)",
		'NroCrz' => "Número do Contador de Redução Z (CRZ)",
		'ValTGi' => "Valor do Grande Total ou Totalizador Geral no início do dia Valor do GT no início do dia (com 2 decimais)",
		'ValTGf' => "Valor do Grande Total ou Totalizador Geral no fim do dia Valor do GT no final do dia constante da leitura Z ou Redução (com 2 decimais)",
		'Arq' => "Nome e Pasta do Arquivo onde foi lido o registro");
  $pr->abre_excel_sql('R03M', 'Registros 03M', $sql, $col_format, $cabec, $form_final);

  $sql = "SELECT * FROM R02";
  $col_format = array(
		"A:B" => "0", 
		"E:G" => "#.##0", 
		"H:H" => "00", 
		"I:I" => "#.##0,000",
		"J:K" => "#.##0,00",
		"N:N" => "#.##0,00");
  $cabec = array(
		'CNPJ' => "CNPJ do remetente nas entradas e do destinatário nas saídas",
		'Inscrição Estadual' => "Inscrição Estadual do remetente nas entradas e do destinatário nas saídas",
		'UF' => "Sigla da unidade da Federação do remetente  nas entradas e do destinatário nas saídas",
		'Dta de emissão' => "Data de emissão na saída ou de recebimento na entrada",
		'Sér/SK' => "Série da Nota Fiscal ou Estoque Inicial (SK)",
		'Número' => "Número da Nota Fiscal",
		'CFOP' => "Código Fiscal de Operação e Prestação. Poderá ser:
1 - Lançamento de Não Realização de Fato Gerador Presumido (um por dia)
2 - Lançamento de Estoque Inicial
1.403 / 2.403 Compra para comercialização em operação com mercadoria sujeita ao regime de substituição tributária.
1.409 / 2.409 Transferência para comercialização em operação com mercadoria sujeita ao regime de substituição tributária.
1.411 / 2.411 Devolução de venda de mercadoria adquirida ou recebida de terceiros em operação com mercadoria sujeita ao regime de substituição tributária.
1.949 /2.949 Outra entrada de mercadoria ou prestação de serviço não especificada.
6.404 Venda de mercadoria sujeita ao regime de substituição tributária, cujo imposto já tenha sido retido anteriormente.
5.405 Venda de mercadoria adquirida ou recebida de terceiros em operação com mercadoria sujeita ao regime de substituição tributária, na condição de contribuinte substituído.
5.409 / 6.409 Transferência de mercadoria adquirida ou recebida de terceiros em operação com mercadoria sujeita ao regime de substituição tributária.
5.411 / 6.411 Devolução de compra para comercialização em operação com mercadoria sujeita ao regime de substituição tributária.
5.929 Lançamento efetuado em decorrência de emissão de documento fiscal relativo a operação ou prestação também registrada em equipamento Emissor de Cupom Fiscal - ECF.
5.949 / 6.949 Outra saída de mercadoria ou prestação de serviço não especificado.
Combustível e lubrificante, também 1.652 / 2.652, 1.659 / 2.659, 1.661 / 2.661, 1.662 / 2.662,
 5.655 / 6.655, 5.656 / 6.656, 5.659 / 6.659 , 5.661 / 6.661, 5.662 / 6.662",
		'CodOpe' => "CÓDIGO COMPLEMENTAR DA OPERAÇÃO:
00 O campo assumirá o conteúdo '00' para todas as operações que não as especificadas a seguir. Também Saldo Inicial.
01 Deve complementar as operações de devolução de venda, cuja saída tenha sido para comercialização subseqüente.
02 Deve complementar as operações de devolução de venda, cuja saída tenha sido destinada a usuário ou consumidor final.
03 Deve complementar as operações de saída destinada a comercialização subseqüente ou transferência de mercadoria, quando essas operações próprias estiverem amparadas por isenção ou não incidência. Este código complementar deve ser utilizado também nas correspondentes devoluções de venda.
04 Deve complementar as operações de saída destinada a consumidor ou usuário final, quando essas operações próprias estiverem amparadas por isenção ou não incidência. Este código complementar deve ser utilizado também nas correspondentes devoluções de venda.
05 Deve complementar as saídas de mercadorias adquiridas ou recebidas de terceiros em operação sujeita ao regime de substituição tributária, na condição de contribuinte substituído, cuja saída tenha sido destinada à comercialização subseqüente ou transferência de mercadoria, quando a operação subseqüente estiver amparada por isenção ou não incidência, exceto a isenção da microempresa. Este código complementar deve ser utilizado também nas correspondentes devoluções de venda.
06 Deve complementar as operações de saída de mercadorias adquiridas ou recebidas de terceiros em operação sujeita ao regime de substituição tributária, na condição de contribuinte substituído, cuja saída tenha sido destinada à comercialização subseqüente.
07 Deve complementar o lançamento efetuado em decorrência de emissão de documento fiscal relativo à operação ou prestação também registrada em equipamento Emissor de Cupom Fiscal - ECF, quando a saída destinar-se a contribuintes do imposto e a comercialização subseqüente. 
",
		'QtdPro' => "Quantidade da Mercadoria",
		'VTBCSTRet' => "VALOR TOTAL DA BASE DE CALCULO DO ICMS DE RETENÇÃO NA SUBSTITUIÇÃO TRIBUTARIA
Corresponde ao valor resultante da multiplicação da quantidade pelo valor unitário da base de cálculo da retenção",
		'Valor de Confronto' => "Não utilizado nas Entradas (exceto devolução, conforme §6º do Art.4º da Cat 17/99.
Base de Cálculo Efetiva para CONFRONTO nas Saídas Destinadas a Consumidor ou Usuário Final (coluna 15 do controle de estoque Mod.3) OU
Base de Cálculo Efetiva para CONFRONTO nas Entradas, nas demais hipóteses (coluna 16 do controle de estoque Mod.3). 
Não utilizado em Saídas para Comercialização Subsequente (lançar zero), porque estas não geram ressarcimento.

P.Cat 17, Art.4º, § 1º - O valor de confronto previsto na alínea e do inciso IV (colunas 15 e 16) será registrado, conforme o caso, como segue:
1 - na coluna Base de Cálculo Efetiva na Saída a Consumidor ou Usuário Final (15), o valor da correspondente operação de saída:
a) realizada com consumidor ou usuário final;
b) na hipótese em que a parcela do imposto retido a ser ressarcido corresponder à saída subseqüente amparada por isenção ou não incidência, nos termos da alínea b do item 2 do § 4º do artigo 248 do Regulamento do ICMS;
2 - na coluna Base de Cálculo Efetiva da Entrada nas Demais Hipóteses (16), o valor da base de cálculo da operação própria do sujeito passivo por substituição do qual a mercadoria tenha sido recebida diretamente ou o valor da base de cálculo que seria atribuída à operação própria do contribuinte substituído do qual a mercadoria tenha sido recebida, caso estivesse submetida ao regime comum de tributação, observado o disposto no § 5º.

§ 5º - O valor da base de cálculo efetiva da entrada (coluna 16), será obtido no documento fiscal correspondente à entrada ou, na impossibilidade de sua identificação, pela ordem:
1 - no controle comum de estoque, se mantido pelo contribuinte para identificação do custo da mercadoria saída;
2 - nos documentos fiscais relativos às entradas mais recentes, suficientes para comportar a quantidade envolvida.",
		'CodPro' => "Código do Produto",
		'Chassi' => "Número do Chassi do Veículo",
		'BCVeic' => "BASE DE CALCULO DA OPERAÇÃO PRÓPRIA  DO SUBSTITUTO NAS OPERAÇÕES COM VEÍCULOS OU MOTOS",
		'Arq' => "Nome e Pasta do Arquivo onde foi lido o registro");
  $pr->abre_excel_sql('R02', 'Registros 02', $sql, $col_format, $cabec, $form_final);

  $sql = "SELECT * FROM R01";
  $col_format = array(
		"A:B" => "0");
  $cabec = array(
		'CNPJ/MF' => "CNPJ do estabelecimento informante",
		'Inscrição Estadual' => "Inscrição estadual do estabelecimento informante",
		'CNAE' => "CÓDIGO NACIONAL DE ATIVIDADES ECONÔMICAS - FISCAL",
		'Nome do Contribuinte' => "Nome comercial (razão social / denominação) do contribuinte",
		'Município' => "Município onde está domiciliado o estabelecimento informante",
		'Unidade da Federação' => "Unidade da Federação referente ao Município",
		'Data Inicial' => "A data do início do período referente às informações prestadas",
		'Data Final' => "A data do fim do período referente às informações prestadas",
		'Logradouro' => "NOME DO LOGRADOURO",
		'Numero' => "Numero",
		'Complemento' => "Complemento",
		'Bairro' => "Bairro",
		'CEP' => "CEP",
		'Contato' => "Nome do Contato",
		'Fax' => "Fax",
		'Telefone' => "Telefone",
		'Email' => "Email",
		'Site' => "Site",
		'Arq' => "Nome e Pasta do Arquivo onde foi lido o registro");
  $pr->abre_excel_sql('R01', 'Registros 01', $sql, $col_format, $cabec, $form_final);

  $pr->finaliza_excel();
}


?>