## Postman collection

https://www.getpostman.com/collections/5a0cc43ae6eca1660d81

## telefone Joao Paulo Ramineli

    41996404861

## Aplicação web Bseller

https://backstg.bseller.com.br/web/

### Usuario e senha

    usuario: LEO_CARLOS
    senha: leo_carlos3


## Documentações

https://bseller.zendesk.com/hc/pt-br/sections/206257008-Documenta%C3%A7%C3%A3o

http://api.bseller.com.br/swagger-ui.html#/

Erros conhecidos.
<!-- * erro no pedido -->
- [ ] erro na atualização do estoque de produto. coluna do banco(variant_id_erp) sendo apagada em algum ponto do codigo.

- [x] Campo situation indo ao banco com valor 1 mesmo que a imagem estiver presente. quando roda com o if pra validar a informação estoura um erro.

- [ ] Erro que o produto não tem _preço cadastrado_ ao atualizar variação.

- [ ] produto segue normal até o ponto que ele está vinculado a um produto pai que não está cadastrado pai que não está linkado para integração conectaLa.


http://backstg.bseller.com.br/forms/frmservlet?config=bseller



João Paulo Raminelli15:40
https://bseller.zendesk.com/hc/pt-br/articles/228208928
João Paulo Raminelli15:46
php index.php BatchC/Integration/Bseller/Order/UpdateStatus

php index.php BatchC/Integration/Bseller/Product/UpdateStock

php index.php BatchC/Integration/Bseller/Product/UpdatePrice

php index.php BatchC/Integration/Bseller/Product/CreateProduct

# duvidas.
## duvidas para a bseller
Quando o produto tiver um preço para a interface conectala ele automaticamente se torna disponivel a conectala? caso seja isto não está acontecendo. Ex: https://backstg.bseller.com.br/web/#/produtos/876941
(Nosso sistema não aceita perço igual a zero, então só pegará os produtos com preço ou trará os sem preço e dará uma tratativa aqui no nosso sistema?)

O que fazer quando for removidaa variação do produto na bseller?

# para reunião interna
Verificar fluxo de criação de pedido para enviar para a bseller.

Produto está ok. Falta pedido pedido não tá indo para lá, mas tambem não está indo para tabela integration to.


## Comandos para criação de pedido

    INSERT INTO orders_payment
    (id, order_id, parcela, bill_no, data_vencto, valor, forma_id, forma_desc, forma_cf, `method`, autorization_id, card_issuer, description, parcels, name_card_issuer, name_payment)
    VALUES(null, 517002950, 1, 'TEST-1617904133', '2021-08-11T12:38:15.000-04:00', '1004.45', 'hipercard', 'credit_card', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

Para atualização dos status.

    UPDATE orders set paid_status = 3, data_pago = NOW() where id = 517002950; 
    UPDATE orders_to_integration SET paid_status = 3 where order_id = 517002950;