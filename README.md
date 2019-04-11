# Conversor3 - cv3

## Links

[Manual da EFD](https://github.com/pmarote/cv3/blob/master/res/GUIA%20PR%C3%81TICO%20EFD%20ICMS%20IPI%20-%20Vers%C3%A3o%203.0.pdf) 

[Manual da NFe](https://github.com/pmarote/cv3/blob/master/res/NFe_v_6.00.pdf) 

[Php-GTk](https://www.kksou.com/php-gtk2/category/Sample-Codes/) 

## Convenções

### Tudo está em UTF-8.
- Mas, atenção, o sistema de arquivos do Windows é todo em ISO-8859-1 (ANSI). Por isso, não custa nada TODA A VEZ que trabalhar com arquivos fazer o utf8_encode e utf_decode;
- Os arquivos SPED vem, por padrão, em 	ISO-8859-1 (ANSI)

### Não mude nada de internacionalização do PHP original! 
- Ou seja, echo 3/2;  tem que resultar 1.5 (ponto) e não 1,5 (vírgula)
- Todo mundo trabalha assim, formato original, americano. É padrão. Conversor2 está assim. Só que o conversor 1 original está no formato brasileiro, então estou reescrevendo.

- *cst* e *cfop* são **sempre** campos *int* !
- datas são **sempre** campos *text* e no formato aaaa-mm-dd
- *cnpj* e *cpf* são **sempre** campos *int* e *ie* é sempre campos *text* (padrão EFD)
- *modelo* e *série* são **sempre** campos *text*; *número* e *item*, sempre *int*

Se o campo não tiver descrição de tipo, SQLITE **sempre** coloca como *text*, mesmo se o dado for inserido sem aspas!
Exemplo: cfop. Veja quadro abaixo
...
--cfop foi inserido sem aspas em _03__Cv2_GIA_NFe_v012_GIA 
CREATE TABLE gia (modelo, cnpj_origem, ie_origem, aaaamm, cfop INT, valcon, bcicms, icms, icmsst_tuido, icmsst_tituto, outimp);
INSERT INTO gia SELECT * FROM _03__Cv2_GIA_NFe_v012_GIA;
--o select abaixo funciona corretamente
SELECT 
  CASE WHEN cfop < 5000 THEN 'E' ELSE 'S' END AS tp_oper,
  cfop 
  FROM GIA;
--o select abaixo não funciona corretamente, sempra dá saída, porque cfop está como texto!
  SELECT 
  CASE WHEN cfop < 5000 THEN 'E' ELSE 'S' END AS tp_oper,
  cfop 
  FROM _03__Cv2_GIA_NFe_v012_GIA;
...

- após acabar tudo, os bancos de dados ficam em *db3*

- Tudo é automático e tem correção automática de erro, caso tenha dado problemas anteriormente. A correção é meio burralda, mas é assim:   - todo o processamento é feito dentro de _sistema\tmp e _sistema\db3 . Cada vez que é iniciada uma conversão, primeiro é chamado Reinicializa.php que vai providenciar que _sistema\tmp e _sistema\db3 sejam apagados. Como assim?
	- Ao clicar em Inicia Conversão é chamado function clickok(), que simplesmente chama function reinicializa()
	- function reinicializa() chama o thread php-win Reinicializa.php e encerra o aplicativo principal Leitura.php
  - Reinicializa.php basicamente fica tentando dar recursiveDelete('tmp') e  recursiveDelete('db3') e, se não conseguir sucesso, em algumas tentativas, chama , _KillProcesses.vbs que, basicamente é taskkill /f /im de php-win.exe, php.exe e sqliteman.exe, chamando em seguida novamente Reinicializa.php
  - Encerrando Reinicializa.php , vai agora a _Reinicializa.bat (ou .vbs), que simplesmente chama Leitura.php console reinicializa ou seja, volta ao início mas no modo reinicializa
  - Reiniciando Leitura.php  no modo reinicializa,ou seja, após clicar em Inicia Conversão, após reinicializar, ele printa a tela mas não para, vai direto a function principal(), que basicamente chama o thread php-win ThreadConv.php  e fica aguardando encerrar esse thread, simplesmente verificando se o thread gerou logs/ThreadConvPronto.log

### O que faz ThreadConv.php, em suma ?

Em suma
- Joga todos os arquivos SPEDs EFDs em efd.db3
- Joga todos os arquivos SPEDs ECDs em ecd.db3
- Joga todos os arquivos cat 17/99 em p17.db3
- Joga todos os demais arquivos .txt em txt.db3
		Arquivos .txt:
		- A primeira linha é o nome dos campos! Se não houver, crie na mão
- Quem define o número de campos é a primeira linha! Se quiser mudar no número de campos, edite a primeira linha e insira ou remova tabs... simples assim
- Se, nas linhas seguintes:
a) Houver mais campos: serão inseridas duas linhas, sendo que a primeira é uma linha de erro, no primeiro campo com a mensagem: 
'#ERRO#Linha{$ilidos}_Abaixo_Com_". count($campos) . "_Campos
b) Houver menos campos: o software tentará descobrir se os demais campos estão na linha a seguir ou nas seguintes. Isso pode acontecer quando um campo tem \r\n, como por exemplo campo observação
b1- Se nas próximas linhas ele conseguir completar exatamente o número de campos correto, ele completa;
b2- Se não conseguir, desiste (na última linha), completa com espaços e no último campo avisando:
'#ERRO#Linha{$ilidos}_Abaixo_Com_". count($campos) . "_Campos

Em seguida roda TabAux.php, que:
	- tenta criar dfe.db3 com base nos melhores arquivos possíveis dentro de txt.db3 
	- tenta criar audit.db3 que contém a tabela modelo, para preencher dados de verso da GIA, com base nos melhores arquivos possíveis dentro de txt.db3 
	- criado nfe.db3 e gia.db3, se possível, tenta criar tabelas auxiliares combinando dados.
		Exemplo: c170_total em efd.db3, que contém os itens das NFes de emissão própria
	- por fim, criará audit.db3 que contém as conciliações e modelos
		Exemplos de tabelas: aud_modelo conc_dfe_res

### O novo Modelo

Ao contrário do Conversor2,o novo Modelo será simples, parecido com a GIA (aberto, no máximo, por CFOP, CST e Alíquota).
Se for necessário mais dados, basta relacionar com os arquivos originais.
