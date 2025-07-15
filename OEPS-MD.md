https://conectala.atlassian.net/browse/OEP-1921
[Vertem] Permitir integrar pedidos com mais de um seller no carrinho

Adicionar

Apps
Geral
Estimativa
Informações adicionais

Descrição

Texto normal














Problema

Solução

Remover validações e bloqueios

Remover a restrição de cotação de frete no seller center para permitir que cotações de frete que sejam feitas com mais de um seller sigam o processo de cotação de frete normal (assim como acontecem as cotações de frete para um seller unico)

Também deve ser removida qualquer restrição que impeça a cotação de mais de um item diferente do mesmo seller no carrinho. 

Configuração da Feature

Criar um parâmetro para que seja definido o nome da transportadora e método de envio que serão retornados nas cotações de frete quando houverem mais de um seller no carrinho

Nome do parametro: multiseller_freight_results

Nome amigável: Nome do método de envio Multi Seller

Descrição: Este parametro precisa estar ativo para que a cotação de frete multi seller funcione corretamente. Neste parametro devem ser configurados o nome da transportadora e método de envio que serão utilizados quando houver a cotação de frete de um carrinho com mais de um seller. O campo deve ser preenchido obrigatoriamente da seguinte forma: “Nome da transportadora/Método de Envio“

Valor:Transportadora/método de envio

Status: iniciar inativo

A feature deverá funciona apenas se, dentro do cadastro de regras de frete (https://parceiro.shophub.com.br/app/auction/addRulesAuction) as regras de menor preço ou menor prazo estejam configuradas. Caso contrário, o sistema deverá seguir com o comportamento atual de não retornar frete para o carrinho

Cotações de frete

Permitir cotar frete de todos os itens de um carrinho, mesmo que os itens pertençam a sellers diferentes

Ao receber uma cotação de frete que tenham itens de diferentes sellers, o sistema deverá realizar a cotação de todos os carrinhos internos (todos os itens de cada seller deve ser cotado junto) em paralelo

Se o tempo de resposta de algum dos seller não for atendido, tanto em integração quanto em contingencia, todo o carrinho deve ser invalidado informando que não há cotação de frete disponível para este carrinho (carrinho geral)

Se algum dos seller não tiver frete disponível, todo o carrinho deve ser invalidado informando que não há cotação de frete disponível para este carrinho (carrinho geral)

Permitir cotar frete de todos os itens de um carrinho, mesmo que os itens de um mesmo seller sejam diferentes entre si (Obs: essa ação já deveria estar acontecendo hoje)

As cotações de frete deverão ser retornadas de acordo com a seguinte regra

Nome da transportadora e método de envio - Utilizar valores configurados no parâmetro multiseller_freight_results criado no item 2 deste requisito

para escolher preço e prazo o sistema deverá utilizar a regra logística cadastrada no sistema de menor preço ou menor prazo

Se a regra configurada for de menor preço o sistema deverá preencher os campos de prazo e preço da seguinte forma:

Encontrar qual a melhor opção de preço de cada carrinho interno (carrinho de cada seller) e somar os valores para ter o preço final de frete. Para o valor de prazo, deverá ser utilizado o pior prazo entre todas as melhores opções de preço selecionadas.

Se a regra configurada for de menor prazo sistema deverá preencher os campos de prazo e preço da seguinte forma:

Encontrar qual a melhor opção de prazo de cada carrinho interno (carrinho de cada seller) e utilizar o pior prazo entre todas as melhores opções de prazo. Em seguida, deverá somar os valores de preço das opções encontradas em cada carrinho interno.

Guardar cotações de frete

O sistema deverá continuar a guardar todas as cotações de frete recebidas pelo seller

O sistema deverá guardar se a cotação de frete recebida pelo seller estava em um carrinho misto para que o valor possa ser identificado no ato da integração do pedido

A tela de consultar log de cotação de frete no botão Cotações da tela de produtos não deve ser alterada

Integrar Pedidos em mais de um seller

O receber um pedido no seller center que possua mais de um seller, o sistema deverá identificar quem são os sellers do pedido para realizar a divisão de forma correta

Cada pedido quebrado deverá ter um id de pedido e id marketplace unico. O id conecta lá deverá ser um id sequencial enquanto cada id marketplace receber um id após os ultimos numeros do pedido. Ex: O pedido na Vtex do marketplace tem o id MVG2371283719-04 e os itens desse pedido pertencem a 2 sellers. O id dos pedidos será alterado para MVG2371283719-04-01 e MVG2371283719-04-02

A regra para definir qual seller recebe o id -01, -02, -N no pedido deve seguir a ordem de cadastro da loja no seller center (menor id de seller -01, segundo menor -02, etc)

Ao realizar o split do pedido o seller center deverá identificar a partir dos logs do item 4 qual o valor de frete correto o pedido para cada seller, verificando o valor informado no pedido e encontrando a cotação correspondente.

Se o valor do frete no pedido for igual a 0, realizar a divisão sem levar em consideração a cotação de frete

Se o valor do pedido não for encontrado para realizar a divisão corretamente o pedido não deve ser integrado. Nesses casos, deve ser exibida uma mensagem no histórico de integração na conta do seller informando que o pedido não pode ser integrado devido a não ter encontrado valor de frete correspondente.

Obs: se os itens a e b do item 2 não estiverem configurados corretamente a feature de cotação de frete deve manter o comportamento atual de não realizar cotações para sellers diferentes no carrinho

ALTERAÇÕES PÓS INTEGRAÇÃO CORREIOS - VERTEM

PARAMETRO PARA MENOR PREÇO E PRAZO NOMES

DEVOLVER VALORES POR SKU NA COTAÇÃO DE FRETE




Salvar

Cancelar
Motivo do Impedimento

Nenhum

Atividade

Tudo

Comentários

Histórico

Registro de atividades

Checklist history


Pedro Marcel Braga

21 de maio de 2025 às 11:42
@Marcos Rubens Camargo … Padrão de sku na vertem

P+IDSKU+S+IDSELLER+INTTO



thumbs up
1



Marcos Rubens Camargo

29 de maio de 2025 às 12:56
Parâmetros criados:
marketplace_replace_shipping_method - utilizado para que o marketplace correios diga qual é o nome dos métodos de envio mais barato e mais rápido no retorno das cotações de frete para a vtex. Os dados devem estar configurados iguais no ambiente da Shophub e do Mais Correios
enable_multiseller_operation - Quando ativo, será possível mais que uma loja compartilhar o mesmo pedido (utilizando os padrões fixos do sistema de identificação de mais de um seller no mesmo pedido utilizando o id que o sistema inclui no sku do produto ao publicar do marketplace Shophub para outros canais)
marketplace_multiseller_operation - Marketplaces que serão possíveis mais que uma loja compartilhar o mesmo pedido (como o correios só tem um canal ligado por enquanto, o marketplace Mais Correios já foi configurado)

Cotações de frete:

No marketplace dos correios, o sistema identifica o seller através do sku do produto (padrão que o sistema aplica hoje na publicação de produtos do parceiro.shophub para outros marketplaces). Identificados os produtos o proprio marketplace dos correios realiza a quebra do carrinho vindo da vtex para "carrinhos" por seller na Shophub

As cotações de frete utilizam o parametro marketplace_replace_shipping_method para poder padronizar os métodos de envio que serão devolvidos para a Vtex por conta de como a Vtex espera o retorno dos frete.

OBS: A Vtex obrigatoriamente precisa que todos os produtos do carrinho tenham ao menos 1 método de envio em comum entre os itens, por exemplo. Um celular que será entregue pelo Seller A tem as opções Correios Pac e Sedex. Já o notebook do Seller B será entregue pela Jadlog Normal ou expressa. Se enviarmos para a Vtex o retorno da cotação de frete com as opções de cada seller, a Vtex entende que esse carrinho não pode ser atendido. Nesse caso, o parametro marketplace_replace_shipping_method padroniza para o como o marketplace espera responder os valores de mais rápido e mais barato.

Caso algum dos sellers não atenda no tempo esperado pelo seller center, é possível retornar entrega para os itens disponíveis é indisponibilidade para os itens não disponíveis.

No seller center da shophub, o sistema deve obrigatoriamente responder sempre apenas 2 opções (barata e rápida) para seja possível realizar a utilização da feature

A cotação de frete como é realizada já exibe no carrinho que os itens serão entregues em mais de um pacote.

Integração de pedidos:

Para integrar pedidos no seller center parceiro.shophub, o seller center da shophub consulta o pedido no seller center dos correios e realiza a quebra dos pedidos identificando quais itens pertencem a quais sellers seguindo a mesma regra da cotação de frete, verificando o id da loja que consta no sku do produto

Antes de integrar os pedidos, o sistema realiza uma nova cotação de frete para preencher o método de envio corretamente no pedido.

O valor de frete do pedido também é identificado na nova cotação de frete para identificar de forma mais próxima o valor selecionado no pedido.

As atualizações de status vão acontecer quando o primeiro seller que estiver no pedido iniciar a mudança de status. Exemplo: em um pedido de 3 sellers, quem faturar primeiro irá enviar a nota fiscal para o pedido no marketplace Mais Correios. O mesmo vale para mais de um rastreio e entrega do pedido.

Em tempo, é importante ressaltar que essas ações foram tomadas para viabilizar o Go Live bem sucedido no dia 19/05 e que a solução para esse tipo de cliente virá com a entrega que iremos realizar na Squad Shophub (hoje em desenvolvimento com o Marcos). 







Marcos Rubens Camargo

16 de junho de 2025 às 13:59
Checklist de Implementação Correso:
📝 Código Implementado:
Método processMultisellerOrder() adicionado

Método breakOrderBySeller() adicionado

Método extractSellerFromSku() adicionado

Método recalculateShippingForMultiseller() adicionado

Modificação no processOrder() implementada

Logs específicos adicionados

Propriedade $enable_multiseller_operation declarada

🔧 Configurações:
enable_multiseller_operation = 1 no banco

marketplace_multiseller_operation contém marketplace correto

Coluna order_mkt_multiseller existe na tabela orders

Testes Específicos:
Teste 1: Pedido Single Seller (Baseline)
bash



# Executar GetOrders com pedido normal
php index.php BatchC/Marketplace/Conectala/GetOrders run null NM
Validações:

Pedido processado normalmente

Log: "Multiseller operation ENABLED/DISABLED"

Nenhum erro de propriedade undefined

Campo order_mkt_multiseller = null

Teste 2: Pedido Multiseller
Dados de teste necessários:

json



{
  "marketplace_number": "TEST-123456",
  "items": [
    {"sku": "PROD123S1001", "quantity": 1},
    {"sku": "PROD456S2002", "quantity": 1}
  ]
}
Validações:

Log: "Pedido multiseller detectado: TEST-123456 com 2 sellers"

Log: "Pedido quebrado para seller 1001 com 1 itens"

Log: "Pedido quebrado para seller 2002 com 1 itens"

2 pedidos criados no banco

Campo order_mkt_multiseller = "TEST-123456" em ambos

Itens corretos em cada pedido

Teste 3: Extração de Seller do SKU
Casos de teste:

php



// Adicionar temporariamente para debug
echo "Teste SKU: PROD123S1001 -> Seller: " . $this->extractSellerFromSku("PROD123S1001") . "\n";
echo "Teste SKU: PROD456S2002-V1 -> Seller: " . $this->extractSellerFromSku("PROD456S2002-V1") . "\n";
echo "Teste SKU: PROD789 -> Seller: " . $this->extractSellerFromSku("PROD789") . "\n";
Resultados esperados:

"PROD123S1001" → "1001"

"PROD456S2002-V1" → "2002"

"PROD789" → "1" (fallback)

Teste 4: Recálculo de Frete
Validações:

Log: "Frete recalculado para seller 1001: Normal - R$ XX.XX"

Log: "Frete recalculado para seller 2002: Normal - R$ XX.XX"

Valores de frete diferentes para cada seller

Métodos de envio padronizados (Normal/Expressa)

Teste 5: Configuração Dinâmica
Teste A: Multiseller Desabilitado

sql



UPDATE settings SET status = 0 WHERE name = 'enable_multiseller_operation';
Log: "Multiseller operation DISABLED"

Pedidos multiseller processados como single seller

Teste B: Multiseller Habilitado

sql



UPDATE settings SET status = 1 WHERE name = 'enable_multiseller_operation';
Log: "Multiseller operation ENABLED"

Pedidos multiseller quebrados corretamente

Comandos de Teste:
1. Teste Manual Completo:
bash



# 1. Verificar configurações
mysql -e "SELECT name, value, status FROM settings WHERE name LIKE '%multiseller%';"

# 2. Executar GetOrders
php index.php BatchC/Marketplace/Conectala/GetOrders run null NM

# 3. Verificar logs
tail -f /path/to/logs/batch.log | grep multiseller

# 4. Verificar pedidos criados
mysql -e "SELECT id, bill_no, order_mkt_multiseller FROM orders WHERE origin = 'NM' ORDER BY id DESC LIMIT 10;"
2. Debug Específico:
php



// Adicionar temporariamente no método processMultisellerOrder()
echo "=== DEBUG MULTISELLER ===\n";
echo "Enable multiseller: " . ($this->enable_multiseller_operation ? 'YES' : 'NO') . "\n";
echo "Total items: " . count($content['items']) . "\n";
echo "Sellers found: " . count($sellers_items) . "\n";
foreach ($sellers_items as $seller => $items) {
    echo "Seller $seller: " . count($items) . " items\n";
}
echo "========================\n";
Validação de Performance:
Teste de Carga:
bash



# Simular múltiplos pedidos
for i in {1..10}; do
    php index.php BatchC/Marketplace/Conectala/GetOrders run null NM &
done
wait
Validações:

Sem deadlocks no banco

Logs consistentes

Performance aceitável

Checklist Final:
Funcionalidades Core:
✅ Detecção automática de multiseller

✅ Quebra correta por seller

✅ Criação de pedidos separados

✅ Recálculo de frete por seller

✅ Logs específicos funcionando

Compatibilidade:
✅ Pedidos single seller inalterados

✅ Configuração dinâmica funcionando

✅ Sem regressões no fluxo existente

Qualidade:
✅ Tratamento de erros adequado

✅ Logs suficientes para debug

✅ Performance aceitável







Marcos Rubens Camargo

23 de junho de 2025 às 12:47
Checklist de Validação e Testes
PRÉ-REQUISITOS 🔧
Configurações do Sistema
enable_multiseller_operation = 1 (ativo)

marketplace_multiseller_operation contém marketplace dos Correios

marketplace_replace_shipping_method = {"lowest_price":"Normal","lowest_deadline":"Expressa"}

Coluna order_mkt_multiseller existe na tabela orders

Ambiente de desenvolvimento configurado
FeatureFlag ativo para a imlementação da feature

Dados de Teste Preparados
SKUs de teste com padrão multiseller (ex: SKU123S1001, SKU456S2002)

CEP de teste válido (ex: 01310-100)

Sellers de teste configurados (ex: 1001, 2002)

FASE 1: TESTES DE COTAÇÃO MULTISELLER 🚚
Teste 1.1: Cotação Single Seller (Baseline)
Endpoint: /Api/SellerCenter/Vtex/{sellercenter}/pvt/orderForms/simulation

Payload:

JSON



{
    "destinationZip": "72879274",
    "volumes": [
        {
            "sku": "P002TROPMARCOSTeste",
            "quantity": 1,
            "price": 19.90,
            "height": 5,
            "length": 22,
            "width": 11,
            "weight": 0.08
        }
    ]
}
Validar: Retorna cotação normal (não multiseller)

Validar: Métodos de envio originais mantidos

Validar: Performance normal

Teste 1.2: Cotação Multiseller (2 Sellers)
Endpoint: /Api/SellerCenter/Vtex/{sellercenter}/pvt/orderForms/simulation

Payload:

JSON



{
    "destinationZip": "72879274",
    "volumes": [
        {
            "sku": "P002TROPMARCOSTeste",
            "quantity": 1,
            "price": 19.90,
            "height": 5,
            "length": 22,
            "width": 11,
            "weight": 0.08
        },
        {
            "sku": "P0000023525CNLMARCOSTeste",
            "quantity": 1,
            "price": 19.90,
            "height": 5,
            "length": 22,
            "width": 11,
            "weight": 0.08
        }
    ]
}
Validar: has_multiseller = true detectado

Validar: Quebra automática por seller

Validar: Requisições assíncronas executadas

Validar: Métodos padronizados (Normal/Expressa)

Validar: Apenas 2 opções por SKU retornadas

Validar: Logs multiseller gerados

Teste 1.3: Cotação Multiseller (3+ Sellers)
Payload: Adicionar terceiro seller

Validar: Escalabilidade mantida

Validar: Performance aceitável

Teste 1.4: Cenários de Erro
Teste: Seller sem estoque

Teste: Seller sem transportadora

Teste: CEP inválido

Validar: Fallbacks funcionando

Validar: Mensagens de erro apropriadas

FASE 2: TESTES DE INTEGRAÇÃO DE PEDIDOS 📦
Teste 2.1: Integração Single Seller (Baseline)
Processo: Criar pedido com 1 seller

Validar: Integração normal mantida

Validar: Campo order_mkt_multiseller = null

Teste 2.2: Integração Multiseller
Processo: Criar pedido com múltiplos sellers

Validar: Quebra automática executada

Validar: Pedidos separados criados

Validar: Campo order_mkt_multiseller preenchido com bill_no original

Validar: Itens corretos em cada pedido

Validar: Dados do cliente replicados

Teste 2.3: Cotação de Frete na Integração
Validar: Nova cotação executada antes da integração

Validar: Método de envio correto preenchido

Validar: Valor de frete identificado corretamente

FASE 3: TESTES DE ATUALIZAÇÕES DE STATUS 📊
Teste 3.1: Faturamento (NFE)
Cenário: Primeiro seller envia NFE

Validar: NFE enviada para marketplace

Validar: Status atualizado para 50

Cenário: Segundo seller tenta enviar NFE

Validar: Detecta NFE já enviada

Validar: Status sincronizado automaticamente

Validar: Log "pedido compartilhado já contém nota fiscal"

Teste 3.2: Rastreamento
Cenário: Primeiro seller envia rastreio

Validar: Rastreio enviado para marketplace

Validar: Status atualizado para 53

Cenário: Segundo seller tenta enviar rastreio

Validar: Detecta rastreio já enviado

Validar: Status sincronizado automaticamente

Teste 3.3: Envio
Cenário: Primeiro seller marca como enviado

Validar: Status enviado para marketplace

Validar: Status atualizado para 5

Cenário: Segundo seller tenta marcar como enviado

Validar: Detecta já enviado

Validar: Status sincronizado

Teste 3.4: Entrega
Cenário: Primeiro seller marca como entregue

Validar: Status entregue para marketplace

Validar: Status atualizado para 6

Cenário: Segundo seller tenta marcar como entregue

Validar: Detecta já entregue

Validar: Status sincronizado

Teste 3.5: Cancelamento
Cenário: Tentativa de cancelar pedido compartilhado

Validar: Cancelamento bloqueado

Validar: Log "pedido compartilhado não deve ser cancelado"

Cenário: Cancelar pedido single seller

Validar: Cancelamento normal executado

FASE 4: TESTES DE API 🔌
Teste 4.1: API Orders - Consulta
Endpoint: GET /Api/Orders/{order_id}

Validar: Campo order_mkt_multiseller retornado

Validar: Valor correto (bill_no original ou null)

Teste 4.2: API Orders - Criação
Endpoint: POST /Api/Orders

Payload: Incluir order_mkt_multiseller

Validar: Campo salvo corretamente

Validar: Retorno inclui campo

Teste 4.3: API Orders - Lista
Endpoint: GET /Api/Orders

Validar: Campo incluído na listagem

Validar: Filtros funcionando

FASE 5: TESTES DE PERFORMANCE ⚡
Teste 5.1: Performance Cotação
Baseline: Tempo cotação single seller

Multiseller: Tempo cotação 2 sellers

Validar: Overhead < 50% do baseline

Validar: Requisições assíncronas funcionando

Teste 5.2: Performance Integração
Baseline: Tempo integração single seller

Multiseller: Tempo integração multiseller

Validar: Overhead aceitável

Teste 5.3: Carga
Teste: 10 cotações simultâneas multiseller

Teste: 50 cotações simultâneas multiseller

Validar: Sistema estável

Validar: Sem degradação significativa

FASE 6: TESTES DE LOGS E MONITORAMENTO 📊
Teste 6.1: Logs Multiseller
Validar: Log de detecção multiseller

Validar: Log de quebra por seller

Validar: Log de sincronização de status

Validar: Logs com informações suficientes para debug

Teste 6.2: Logs de Erro
Validar: Erros de cotação logados

Validar: Erros de integração logados

Validar: Contexto suficiente nos logs

Teste 6.3: Métricas
Validar: Contadores de cotações multiseller

Validar: Tempo médio de processamento

Validar: Taxa de sucesso

FASE 7: TESTES DE CONFIGURAÇÃO ⚙️
Teste 7.1: Ativação/Desativação
Cenário: enable_multiseller_operation = 0

Validar: Comportamento single seller mantido

Cenário: enable_multiseller_operation = 1

Validar: Multiseller ativado

Teste 7.2: Configuração de Marketplace
Cenário: Marketplace não na lista

Validar: Multiseller desabilitado

Cenário: Marketplace na lista

Validar: Multiseller habilitado

Teste 7.3: Configuração de Métodos
Cenário: JSON inválido em marketplace_replace_shipping_method

Validar: Fallback para métodos originais

Cenário: JSON válido

Validar: Padronização aplicada

CHECKLIST FINAL DE ENTREGA 🎯
Funcionalidades Core
✅ Cotação multiseller funcionando

✅ Quebra automática por seller

✅ Padronização de métodos (Normal/Expressa)

✅ Integração de pedidos multiseller

✅ Sincronização de status

✅ API com suporte multiseller

Qualidade e Monitoramento

 Logs específicos implementados

Tratamento de erros adequado

Performance aceitável

Configurações validadas
Documentação

Fluxo multiseller documentado

Configurações documentadas

Troubleshooting guide criado
Deploy

 Testes em desenvolvimento passando

 Configurações de produção validadas

 Rollback plan definido

 Monitoramento ativo
CRITÉRIOS DE ACEITAÇÃO FINAL 🏆
Mínimo Viável (MVP)
Cotação: Multiseller detectado e processado corretamente

Integração: Pedidos quebrados e integrados por seller

Status: Sincronização funcionando (primeiro seller atualiza)

API: Suporte básico ao campo multiseller

Ideal
Performance: Overhead < 50% vs single seller

Logs: Monitoramento completo ativo

Configuração: Ativação/desativação dinâmica

Documentação: Guias completos disponíveis

Melhorias
Otimização: Cache implementar

Métricas: Dashboard de monitoramento

Automação: Testes automatizados

Escalabilidade: Suporte a 5+ sellers

🚀 Plano de Execução dos Testes
Sequência Recomendada:
1.Configurações → Validar ambiente

2.Cotação Single → Baseline funcionando

3.Cotação Multi → Core multiseller

4.Integração → Fluxo completo

5.Status → Sincronização

6.API → Endpoints

7.Performance → Validação final

_________________________________________________
https://conectala.atlassian.net/browse/OEP-2009

Permitir Faturamento Parcial de pedidos no seller center

Adicionar

Apps
Geral
Estimativa
Informações adicionais

Descrição

Solução

Criar status de Faturado Parcialmente

Quando qualquer item do pedido estiver no status de faturamento, e todos os demais itens ainda não tiverem recebido nota fiscal, envio ou entrega, o pedido deverá ser atualizado para o novo status “Faturado Parcialmente”

Quando todos os skus de um pedido forem faturados o pedido será alterado para o status de “Faturado“ atual do sistema

Alterar api de faturamento para receber as notas fiscais por SKU

Incluir na api de inserção de nota fiscal o objeto para que o integrador envie os skus que serão faturados



{
    "nfe": [
        {
            "order_number": 517003022,
            "invoice_number": 211071,
            "price": 119.90,
            "serie": 1,
            "access_key": "41191075086785000166550010002110711997889290",
            "emission_datetime": "15/10/2019 17:24:59",
            "collection_date": "23/04/2020",
            "link_nfe": "https://conectala.com.br/"


            "skus":[
            {"123_abc","456_def","789_ghi"}
            ]


        }
    ]
}'
Caso todos os skus sejam enviados na chamada, atualizar a nota fiscal diretamente no pedido

Caso nenhum sku seja enviado na chamada, atualizar a nota fiscal diretamente no pedido

Caso nem todos os skus sejam enviados na chamada mas seja enviado pelo menos 1, faturar o pedido parcialmente

Alterar a tela de pedidos para permitir incluir a nota fiscal por item

Criar ao lado dos itens do pedido um botão para que seja possível visualizar os dados de faturamento por item

Ao clicar no botão deve ser exibido um popup com as informações de faturamento

Na tela de faturamento de pedidos, incluir um botão para faturar parcialmente as vendas

Quando o usuário clicar no botão deverão ser exibidos todos os itens do pedido para que o usuário escolha quais itens serão faturados

Quando clicar para faturar individualmente deverá ser solicitado apenas os campos de dados de faturamento

image-20250709-140811.png
image-20250709-140652.png
image-20250709-125038.png
image-20250709-125448.png
image-20250709-125455.png
Enquanto houver ao menos 1 item que não esteja faturado, o botão de faturamento deve permanecer habilitado em tela 

Alterar programa de faturamento Vtex

Alterar o programa de envio de faturamento para o marketplace Vtex para enviar as notas fiscais apenas dos skus faturados

Alterar programa de faturamento Vertem

Alterar programa de atualização de pedidos Vertem para faturar na integração Conecta Lá sempre enviando os skus na chamada da nota

Alterar Gets de pedidos na API

Incluir nos Gets por Id e geral da api de pedidos, o objeto invoice dentro do objeto items



,
      "items": [
        {
          "remote_store_id": "10",
          "qty": 1,
          "product_id": "9369",
          "original_price": 25,
          "total_price": 25,
          "name": "Shampoo",
          "sku": "12345",
          "discount": 0,
          "unity": "Un",
          "gross_weight": 0.2,
          "width": 10,
          "height": 30,
          "depth": 4,
          "measured_unit": "cm",
          "variant_order": null,
          "sku_variation": null,
          "freight_service_fee": 0,
          "product_fee": 0,
          "sku_integration": "88658229",
          "campaigns": [],
          "sku_marketplace": null,
          "return": [],
          "commission_hierarchy_value": null,
          "commission_hierarchy_level": null,
          "commission_hierarchy_id": null
          
          
        "invoice": {
        "date_emission": "2025-03-04 14:27:00",
        "value": 194.35,
        "serie": 5,
        "num": 693198,
        "key": "35250374261884001146550050006931981008488537",
        "link": null
      },
          
          
          
          
        }
Motivo do Impedimento
--------------------------------------------------------------------------------------

https://conectala.atlassian.net/browse/OEP-2013


Projetos
Squad Onboarding & P...

OEP-2008

OEP-2013


Permitir Mais de uma devolução por pedido

Adicionar

Apps
Geral
Estimativa
Informações adicionais

Descrição

Solução

Permitir mais de uma devolução por pedido

Permitir que os pedidos possam receber mais de uma devolução desde que sejam realizadas para itens diferentes

Quando o usuário realizar a segunda devolução do pedido, o sistema deverá realizar as mesmas etapas de devolução já existentes no sistema (Criação de devolução com preenchimento de dados de transporte, atualização de status, abertura de jurídico, etc)

As devoluções poderão acontecer tanto via api quanto via tela de pedidos desde que o pedido e os itens dos pedidos estejam entegues

Não será permitido devolver itens e pedidos que não estejam entregues ou que já foram devolvidos previamente.

Sempre deve ser validado se a quantidade enviada na devolução está disponível no pedido.

Refletir devoluções no programa de integração Vertem

Ao realizar uma devolução de pedidos no marketplace Vertem o sistema deverá refletir a devolução via api para o marketplace do pedido recebido

As devoluções criadas deverão atualizar todos os status de devolução dentro da plataforma marketplace Vertem e do marketplace do pedido mantendo o mesmo status de devolução entre as plataformas.

As devoluções deverão ser refletidas no marketplace (enviando para a Vtex quando for o caso) para realizar a devolução do pedido também no marketplace (comportamento atual do sistema)

OBS: sugiro utilizar a api de returns criada para a genius

OBS2: validar se o comportamento do parâmetro cancellation_commission_calculate_campaign permanece funcionando com as demais devoluções

Motivo do Impedimento


____________________________________________________________________________________________________________________
https://conectala.atlassian.net/browse/OEP-1814
Projetos
Squad Onboarding & P...

OEP-2008

OEP-2014


Permitir Cancelamento Parcial de pedidos via Integração

Adicionar

Apps
Geral
Estimativa
Informações adicionais

Descrição

Solução

Alterar regra de cancelamento parcial de pedidos para permitir cancelamento de skus de pedidos não gatilhados no financeiro

Remover a trava da feature de cancelamento parcial, para que seja possível cancelar pedidos parcialmente que estejam no status de faturado e enviado (assim como já é possivel realizar para pedidos pagos), caso o usuário tenha permissão de cancelamento parcial do pedido.

O botão de cancelamento parcial deve ser exibido apenas se a data de pagamento do pedido no financeiro não estiver preenchida (OEP-1814: Impedir cancelamento pós status de gatilho (Entregue e enviado)
PRONTO PRA PRODUÇÃO
), caso contrario o botão não estará disponível em tela e o usuário deverá realizar uma devolução

A tela deverá exibir os mesmos campos para que o lojista ou o marketplace realizem o cancelamento apenas de 1 ou mais itens do pedido e não do pedido todo.

image-20250709-150457.png
image-20250709-150505.png
Para skus ou pedidos que já estiverem faturados e enviados, após o usuário clicar em confirmar, abrir um popup para que seja confirmada a ação informando que esta ação será realizada imediatamente e que é irreversível. O pedido deverá ser atualizado no seller center e notificará a vtex do cancelamento do item (OEP-1561: Cancelamento Parcial de Pedidos
EM PRODUÇÃO
).

SKus e pedidos que ainda não estiverem faturados poderão ter seu cancelamento parcial revertido enquanto não forem faturados (conforme feature ja implementada OEP-1561: Cancelamento Parcial de Pedidos
EM PRODUÇÃO
 )

Permitir cancelamento parcial de pedidos via integração

Ao realizar um cancelamento total ou parcial de pedidos na Vertem, refletir via programa de integração o cancelamento no marketplace do pedido



{
    "order": {
        "date": "2020-02-19 11:52:09",
        "reason": "Pedido cancelado por falta de estoque"
        
        "skus":[
            {"123_abc","456_def","789_ghi"}
            ]
        
    }
}
Caso todos os skus do pedido na Vertem sejam cancelados, enviar a atualização com todos os skus para o marketplace do pedido original

Caso apenas alguns skus sejam cancelados na Vertem (Cancelamento parcial), atualizar enviar o cancelamento parcial via api

_________________________________________________________________________________________________________________________________________________________
Projetos
https://conectala.atlassian.net/browse/OEP-2010
Squad Onboarding & P...

OEP-2008

OEP-2010


Permitir Atualização de Envio Parcial de pedidos no Seller Center

Adicionar

Apps
Geral
Estimativa
Informações adicionais

Descrição

Solução

Criar status de Enviado Parcialmente

Quando qualquer item do pedido estiver no status de enviado, e todos os demais itens ainda não tiverem recebido nota envio ou entrega, o pedido deverá ser atualizado para o novo status “Enviado Parcialmente”

Quando todos os skus de um pedido forem faturados o pedido será alterado para o status de “Enviado“ atual do sistema

Até que o primeiro sku ou todos os skus sejam enviados o pedido deverá permanecer no status de faturado atual (Aguardando Coleta/envio)

Alterar api de criar rastreamento para receber o rastreio e os envios por SKU

Incluir na api de inserção de rastreamento o campo para que o integrador envie se o rastreio será criado apenas para o sku enviado



{
    "tracking": {
        "date_tracking": "2020-01-16 10:15:37",



        "individual_tracking": true,



        "items": [
            {
                "sku": "GG007",
                "qty": 1,
                "code": "AA123456789BB",
                "method": "SEDEX",
                "service_id": "03220",
                "value": 10.90,
                "delivery_date": "",
                "url_label_a4": "",
                "url_label_thermic": "",
                "url_label_zpl": "",
                "url_plp": ""
            }
        ],
        "track": {   
            "carrier": "CORREIOS",
            "carrier_cnpj": "",
            "url": " https://www2.correios.com.br/sistemas/rastreamento/"
        }
    }
}'
Caso o campo individual_tracking seja enviado como true, criar o rastreamento apenas para os skus enviados atualizando os skus para Aguardando coleta - Rastreio externo

Caso o campo individual_tracking seja enviado como false, ou não seja enviado, criar o rastreamento para o pedido todo atualizando o pedido para Aguardando coleta - Rastreio externo

Alterar api de envio de pedidos para permitir enviar skus individualmente

Incluir o objeto skus na chamada de atualização de pedidos para enviado



{
    "shipment": {
        "shipped_date": "2021-11-24 13:47:23",
        
        "skus":[
            {"123_abc","456_def","789_ghi"}
            ]
        
        
    }
}
Caso todos os skus sejam enviados na chamada, atualizar o status diretamente no pedido

Caso nenhum sku seja enviado na chamada, atualizar o status diretamente no pedido

Caso nem todos os skus sejam enviados na chamada mas seja enviado pelo menos 1, enviar o pedido parcialmente e atualizar o sku para enviado

Alterar a tela de fretes a contratar para permitir incluir rastreio por skus

Na tela de Fretes a Contratar incluir um botão para que o usuário possa contratar os fretes dos skus parcialmente

Visualização indisponível
Visualização indisponível
Os dados de rastreio e envio devem ser possíveis de visualizar no botão de status do item na tela de pedidos

Visualização indisponível
Visualização indisponível
Alterar a tela de pedidos para permitir coletar itens por sku

Na tela de Marcar como Coletado, incluir um botão para coletar parcialmente as vendas

Quando o usuário clicar no botão deverão ser exibidos todos os itens do pedido para que o usuário escolha quais itens serão faturados

Quando clicar para coletar individualmente deverá ser solicitado apenas a data de coleta

Visualização indisponível
Visualização indisponível
Visualização indisponível
também deverá ser possível marcar o item como coletado clicando no botão Coletar na linha do item no pedido

Alterar programa de faturamento Vtex

Alterar o programa de envio de rastreamento para o marketplace Vtex para enviar os códigos de rastreio apenas dos skus enviados

Alterar programa de atualização de pedidos Vertem

Alterar programa de atualização de pedidos Vertem para enviar na integração Conecta Lá sempre os skus na chamada de criar rastreamento para os itens alterados

Alterar programa de atualização de pedidos Vertem para enviar na integração Conecta Lá sempre os skus na chamada de atualização de envio para os itens alterados

Alterar Gets de pedidos na API

Incluir nos Gets por Id e geral da api de pedidos, o objeto tracking e label dentro do objeto items



,
      "items": [
        {
          "remote_store_id": "10",
          "qty": 1,
          "product_id": "9369",
          "original_price": 25,
          "total_price": 25,
          "name": "Shampoo",
          "sku": "12345",
          "discount": 0,
          "unity": "Un",
          "gross_weight": 0.2,
          "width": 10,
          "height": 30,
          "depth": 4,
          "measured_unit": "cm",
          "variant_order": null,
          "sku_variation": null,
          "freight_service_fee": 0,
          "product_fee": 0,
          "sku_integration": "88658229",
          "campaigns": [],
          "sku_marketplace": null,
          "return": [],
          "commission_hierarchy_value": null,
          "commission_hierarchy_level": null,
          "commission_hierarchy_id": null
          
        "tracking": {
            "date_label": "2020-01-16 10:15:37",
            "file_a4": null,
            "file_thermal": null,
            "file_zpl": null,
            "file_plp": null,
            "tracking_code": [
                "AA123456789BB"
            ],
            "number_plp": null,
            "tracking_url": "https://www2.correios.com.br/sistemas/rastreamento/"
        },
        "label": [
            {
                "file_a4": "https://teste.conectala.com.br/app/Tracking/printLabel/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJvcmRlcnMiOlsiNTE3MDA0NzA5Il0sImlhdCI6MTc1MjA2ODgwNCwiZXhwIjoxNzUyMTU1MjA0fQ.XS5w-rzdH8v10amiofTf6UeUHPMVBur6XMZDPzUtP4k",
                "file_thermal": null,
                "file_zpl": null,
                "file_plp": null,
                "tracking_code": "AA123456789BB",
                "number_plp": null,
                "tracking_url": "https://www2.correios.com.br/sistemas/rastreamento/"
            }
        ],
          
          
            
        }
