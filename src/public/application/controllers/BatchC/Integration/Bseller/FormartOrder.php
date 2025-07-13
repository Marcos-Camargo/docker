<?php

/**
 * Class CreateOrder
 *
 * php index.php BatchC/Integration/Bseller/Order/CreateOrder run
 *
 */
// require APPPATH . "controllers/BatchC/Integration/Bseller/Main.php";

class FormatOrder
{

    public function __construct($model_products,$model_orders,$model_orders_item)
    {
        
        $this->model_products=$model_products;
        $this->model_orders=$model_orders;
        $this->model_orders_item=$model_orders_item;
    }
    public function formatToBseller($pedido){
        $pedidoParaIntegrar = $pedido;
        $frete = ($pedidoParaIntegrar['total_ship'] > 0) ? doubleval($pedidoParaIntegrar['total_ship']) : 0;
        $valorQueFoiPago = 0;

        $arrayPedidoCompleto = array(
            "canalVenda" => 'CNL', //de acordo com documentação do bseler enviado para nós
            "codigoBox" => null,
            "codigoListaCompras" => null,
            "codigoVendedor" => null,
            "dataEmissao" => date('Y-m-d\TH:i:sP', strtotime($pedidoParaIntegrar['date_created'])),
            "dataInclusao" => date('Y-m-d\TH:i:sP', strtotime($pedidoParaIntegrar['date_created'])),
            "entregasQuebradas" => null,
            "idCotacao" => null,
            "idFulfillment" => null,
            "numeroPedido" => $pedidoParaIntegrar['order_id'],
            "numeroPedidoExterno" => null,
            "numeroPedidoLoja" => null,
            "observacoesEtiqueta" => null,
            "origemPedido" => 'LJ', // fixo de acordo com documentação bseller
            "pedidoParaConsumo" => null,
            "tipoFrete" => 'F',
            "tipoPedido" => 'N',
            "unidadeNegocio" => 8, //de acordo com documentação do bseler enviado para nós
            "entrega" => array(
                "dataEntregaAgendada" => null,
                "periodoEntregaAgendada" => null,
                "tipoEntrega" => 1,
            ),
            "publicidade" => array(
                "campanha" => null,
                "fonte" => null,
                "midia" => null,
            ),
            "valores" => array(
                "valorDespesasFinanceiras" => 0,
                "valorDespesasItem" => 0,
                "valorFrete" => doubleval($frete),
                "valorIcmsDesonerado" => null,
                "valorIcmsSt" => null,
                "valorIpi" => null,
                "valorTotalProdutos" => $pedidoParaIntegrar['total_order'] + $pedidoParaIntegrar['discount_order'],
            )
        );
        $itensPedido['itens'] = [];
        $orders_itens = $this->model_orders_item->getItensByOrderId($pedido['order_id']);
        foreach ($orders_itens as $key => $item) {
            $arraySkuItemPedido = explode('-', $item['sku']);
            $variantItemPedido = count($arraySkuItemPedido) == 1 ? $arraySkuItemPedido[0] : $arraySkuItemPedido[1];
            $variacao = $item;
            $product = $this->model_products->getProductData(0, $item['product_id']);
            if (!$product) {
                continue;
            }
            $itensPedido['itens'][] = array(
                'cnpjFilial' => 13126134000103, //de acordo com documentação do bseler enviado para nós
                'codigoAgrupamentoProduzido' => null,
                'codigoEstabelecimentoEstoque' => 1, //de acordo com documentação do bseler enviado para nós
                'codigoEstabelecimentoSaida' => 1, //de acordo com documentação do bseler enviado para nós
                'codigoItem' => $product["product_id_erp"],
                'codigoItemGarantido' => null,
                'codigoItemKit' => null,
                'codigoItemPai' => null,
                'codigoPromocional' => null,
                'descontoCondicionalUnitario' => ($pedido["discount_order"] > 0) ? $item['discount'] : 0, //Campo Obrigatorio
                'descontoIncondicionalUnitario' => 0, //Campo Obrigatorio
                'idContratoTransportadora' => 'NORMAL', //de acordo com documentação do bseler enviado para nós
                'idTransportadora' => 5886614000136, //de acordo com documentação do bseler enviado para nós
                'itemBonificado' => false,
                'prazoCentroDistribuicao' => 2, // https://bseller.zendesk.com/hc/pt-br/articles/360051067891
                'prazoFornecedor' => 0,
                'prazoTransitTime' => (isset($pedido['ship_time_preview']) && $pedido['ship_time_preview'] > 0) ? $pedido['ship_time_preview'] : 0, //documentacao nao tem campo obrigatorio                            
                'precoUnitario' => $item['rate'], //campo obrigatorio
                'quantidade' => $item['qty'], //campo obrigatorio
                'sequencial' => $key + 1, //campo obrigatorio
                'sequencialAgrupamentoProduzido' => null,
                'sequencialItemgarantido' => null,
                'tipoEstoque' => 'P',
                'tipoItem' => 'P', //campo obrigatorio
                'valorCustoGarantiaEstendida' => null,
                'valorDespesas' => 0, //Respodata e Pedro nao tem esse campo no conectalar
                'valorFrete' => ($key == 0) ? $frete : 0,
                'valorIcmsDesonerado' => null,
                'valorIcmsSt' => null,
                'valorIpi' => null,
                'desconto' => ($item['discount'] > 0) ? $item['discount_product'] : 0
            );
        }


        $valor = str_replace(".", "", $pedidoParaIntegrar['cpf_cnpj_client']);
        $valor = str_replace("-", "", $valor);
        $cpfCnpj = $valor;
        $tipoPessoa = strlen($cpfCnpj) > 11 ? 1 : 2; //1=Pessoa Juridica, 2=Pessoa Física
        if (empty($pedidoParaIntegrar['ie_client']) && $tipoPessoa == 1) {
            $tipoPessoa = 5;
        }
        $valorPedido = 0;
        foreach ($itensPedido['itens'] as $key => $value) {
            $precoUnitario = $value['precoUnitario'];
            $qtdProduto = $value['quantidade'];
            $valorPedido += ($valorPedido + doubleval(floatval($precoUnitario) * floatval($qtdProduto))) - $value['desconto'];
        }
        $pedidoParaIntegrar['valor_payment']=str_replace("R$", "", $pedidoParaIntegrar['valor_payment']);
        $pedidoParaIntegrar['valor_payment']=str_replace(",", ".", $pedidoParaIntegrar['valor_payment']);
        $valorQueFoiPago = doubleval($pedidoParaIntegrar['valor_payment']);

        $clienteEntrega = array(
            "clienteEntrega" => array(
                "classificacao" => 0, //normal
                "crt" => ($tipoPessoa == 2) ? null : 1,
                "dataNascimento" => null,
                "email" => null,
                "fax" => null,
                "id" => null,
                "inscricaoEstadual" => $pedidoParaIntegrar['ie_client'],
                "nome" => $pedidoParaIntegrar['name_order'],
                "rg" => null,
                "sexo" => null,
                "suframa" => null,
                "telefoneCelular" => $pedidoParaIntegrar['phone_order'],
                "telefoneComercial" => null,
                "telefoneResidencial" => $pedidoParaIntegrar['phone_order'],
                "tipoCliente" => $tipoPessoa,
                "endereco" => array(
                    "bairro" => $pedidoParaIntegrar['customer_address_neigh'],
                    "cep" => $pedidoParaIntegrar['customer_address_zip'],
                    "cidade" => $pedidoParaIntegrar['customer_address_city'],
                    "complemento" => $pedidoParaIntegrar['customer_address_compl'],
                    "estado" => $pedidoParaIntegrar['customer_address_uf'],
                    "logradouro" => $pedidoParaIntegrar['address_order'],
                    "numero" => $pedidoParaIntegrar['customer_address_num'],
                    "pais" => 'Brasil',
                    "pontoReferencia" => $pedidoParaIntegrar['customer_reference'],
                    "zipCode" => $pedidoParaIntegrar['customer_address_zip']
                ),
            )
        );


        //------------------------------------------------------------------
        //CLIENTE FATURAMENTO
        //------------------------------------------------------------------
        $clienteFaturamento = array(
            "clienteFaturamento" => array(
                "classificacao" => 0,
                "crt" => ($tipoPessoa == 2) ? null : 1,
                "dataNascimento" => '2000-01-01',
                "email" => $pedidoParaIntegrar['email_client'],
                "fax" => null,
                "id" => $pedidoParaIntegrar['cpf_cnpj_client'],
                "inscricaoEstadual" => $pedidoParaIntegrar['ie_client'],
                "nome" => $pedidoParaIntegrar['name_client'],
                "rg" => $pedidoParaIntegrar['rg_client'],
                "sexo" => null,
                "suframa" => null,
                "telefoneCelular" => $pedidoParaIntegrar['phone_client_1'],
                "telefoneComercial" => null,
                "telefoneResidencial" => (empty($pedidoParaIntegrar['phone_client_2'])) ? $pedidoParaIntegrar['phone_client_1'] : $pedidoParaIntegrar['phone_client_2'],
                "tipoCliente" => $tipoPessoa,
                "endereco" => array(
                    "bairro" => $pedidoParaIntegrar['neigh_client'],
                    "cep" => $pedidoParaIntegrar['cep_client'],
                    "cidade" => $pedidoParaIntegrar['city_client'],
                    "complemento" => $pedidoParaIntegrar['compl_client'],
                    "estado" => $pedidoParaIntegrar['uf_client'],
                    "logradouro" => $pedidoParaIntegrar['address_client'],
                    "numero" => $pedidoParaIntegrar['num_client'],
                    "pais" => 'Brasil',
                    "pontoReferencia" => null,
                    "zipCode" => $pedidoParaIntegrar['cep_client']
                ),
            )
        );

        //----------------------------------------
        //ITENS DO PEDIDO
        //----------------------------------------






        //------------------------------------------------
        //PAGAMENTOS
        //------------------------------------------------
        $codigoAgencia = null;
        $codigoBanco = null;
        $codigoCondicaoPagamento = null;
        $dataVencimentoBoleto = null;
        $numeroConta = null;
        $codigoCupom = 0;

        $dadosCartao = array(
            "bandeira" => null,
            "codigoSeguranca" => null,
            "cpfTitular" => null,
            "dataVencimento" => null,
            "numero" => null,
            "numeroParcelas" => null,
            "percentualJuros" => null,
            "primeiros6digitos" => null,
            "situacaoCodigoSeguranca" => null,
            "titular" => null,
            "valorJuros" => null,
            "valorJurosAdministradora" => null
        );
        //validação forma de pagamento
        switch ($pedidoParaIntegrar['forma_payment']) {
            case 'credit_card':
                $codigoMeioPagamento = 43;
                $codigoCupom = null;
                $dadosCartao = array(
                    "bandeira" => 2,
                    "codigoSeguranca" => '123',
                    "cpfTitular" => '123456',
                    "dataVencimento" => '01/01/2050',
                    "numero" => '0980980980',
                    "numeroParcelas" => 2,
                    "percentualJuros" => 0,
                    "primeiros6digitos" => '123456',
                    "situacaoCodigoSeguranca" => 1,
                    "titular" => 'USUARIO TESTE',
                    "valorJuros" => 0,
                    "valorJurosAdministradora" => 0
                );
                break;

            case 'Dinheiro':
                $codigoMeioPagamento = 37;
                $codigoCupom = null;
                $codigoBanco = 341;
                $codigoAgencia = 81;
                $numeroConta = 133134;
                $dataVencimentoBoleto = date('Y') . "-12-31T11:28:50.060Z";
                break;

            default:
                $codigoMeioPagamento = 83; // transferencia
                break;
        }
        if (strpos($valorQueFoiPago, '.') !== false) {
            $valorQueFoiPago = substr($valorQueFoiPago, 0, ((strpos($valorQueFoiPago, '.') + 1) + 2));
        }

        $pagamentos["pagamentos"][] = array(
            "codigoAgencia" => null,
            "codigoBanco" => null,
            "codigoCondicaoPagamento" => null,
            "codigoCupom" => $codigoCupom,
            "codigoMeioPagamento" => $codigoMeioPagamento,
            "codigoVale" => null,
            "dataVencimentoBoleto" => null,
            "nossoNumero" => null,
            "numeroConta" => null,
            "sequencial" => 1, //numeros de produtos que estão sendo pagos nesta forma de pagamento
            "valor" => $valorQueFoiPago,
            "cartaoCredito" => $dadosCartao,
            "codigoCondicaoPagamento" => $codigoCondicaoPagamento,
            "dataVencimentoBoleto" => $dataVencimentoBoleto,
            "codigoBanco" => $codigoBanco,
            "codigoAgencia" => $codigoAgencia,
            "numeroConta" => $numeroConta,
            "endereco" => array(
                "bairro" => null,
                "cep" => null,
                "cidade" => null,
                "complemento" => null,
                "estado" => null,
                "logradouro" => null,
                "numero" => null,
                "pais" => null,
                "pontoReferencia" => null,
                "zipCode" => null,
            ),
        );



        //juntando todos os arrays
        $arrayFinalPedido = array_merge($arrayPedidoCompleto, $itensPedido);
        $arrayFinalPedido = array_merge($arrayFinalPedido, $pagamentos);
        $arrayFinalPedido = array_merge($arrayFinalPedido, $clienteEntrega);
        $arrayFinalPedido = array_merge($arrayFinalPedido, $clienteFaturamento);

        return $arrayFinalPedido;
    }
}