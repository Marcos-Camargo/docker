<?php

use GuzzleHttp\Utils;

class Freterapido extends Logistic
{
    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);
        $this->setEndpoint('https://freterapido.com/api/external/embarcador/v1');
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {
        $auth = array();

        $platformCode = $this->dbReadonly->where('name', 'plataform_code_freterapido')->get('settings')->row_object();

        $auth['json']['token'] = $this->credentials['token'];
        $auth['json']['codigo_plataforma'] = $platformCode->value ?? '';

        $auth['query']['token'] = $this->credentials['token'];

        $this->authRequest = $auth;
    }

    /**
     * Efetua a criação/alteração do centro de distribuição na integradora, quando usar contrato seller center.
     */
    public function setWarehouse() {}

    /**
     * Cotação.
     *
     * @param   array   $dataQuote      Dados para realizar a cotação.
     * @param   bool    $moduloFrete    Dados de envio do produto por módulo frete.
     * @return  array
     */
    public function getQuote(array $dataQuote, bool $moduloFrete = false): array
    {
        $arrQuote = array(
            'retornar_consolidacao' => true,
            'canal' => $this->sellerCenter,
            "remetente" => array(
                "cnpj" => $dataQuote['dataInternal'][$dataQuote['items'][0]['sku']]['CNPJ']
            ),
            "destinatario" => array(
                "tipo_pessoa" => 1,
                "endereco" => array(
                    "cep" => onlyNumbers($dataQuote['zipcodeRecipient'])
                )
            ),
            'volumes' => array()
        );

        $arrSkuProductId = array();

        foreach ($dataQuote['items'] as $sku) {
            $dataProduct =  array(
                "tipo" 			    => (int) (empty($sku['tipo']) ? 999 : $sku['tipo']),
                "sku" 				=> $sku['skuseller'],
                "quantidade" 		=> (int) $sku['quantidade'],
                "altura" 			=> (float) $sku['altura'],
                "largura" 			=> (float) $sku['largura'],
                "comprimento" 		=> (float) $sku['comprimento'],
                "peso"				=> (float) $sku['peso'],
                "valor" 			=> (float) $sku['valor'] / $sku['quantidade'],
                "volumes_produto" 	=> 1,
                "consolidar" 		=> false,
                "sobreposto"		=> false,
                "tombar"			=> false
            );

            $arrSkuProductId[] = array(
                'skumkt' => $sku['sku'],
                'prd_id' => $dataQuote['dataInternal'][$sku['sku']]['prd_id']
            );

            $arrQuote['volumes'][] = $dataProduct;
        }

        try {
            $services = $this->getQuoteUnit($arrQuote, $arrSkuProductId, $dataQuote['crossDocking']);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $services
            )
        );
    }

    /**
     * Recupera a cotação para contratação.
     *
     * @param $skus
     * @param $cross_docking
     * @return array
     */
    public function getQuoteForFreightContracting($skus, $cross_docking): array
    {
        $platformCode = $this->dbReadonly->where('name', 'plataform_code_freterapido')->get('settings')->row_object();

        //$skus['codigo_plataforma'] = $platformCode->value;
        $skus['retornar_consolidacao'] = true;
        $skus['canal'] = $this->sellerCenter;
        
        try {
            $response = $this->request('POST', "/quote-simulator", array('json' => $skus));
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (empty($contentOrder->transportadoras)) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Frete Rápido\n" . Utils::jsonEncode($contentOrder));
        }

        $services = array();

        //incluir aqui a lógica para receber todas as cotações e enviar no array de saída
        foreach ($contentOrder->transportadoras as $service) {
            $services[] = array(
                'quote_id'     => $service->oferta,
                'token_oferta' => $contentOrder->token_oferta,
                'method_id'    => 0,
                'value'        => $service->preco_frete,
                'deadline'     => $service->prazo_entrega + $cross_docking,
                'method'       => $service->servico,
                'provider'     => $service->nome,
                'provider_cnpj'=> $service->cnpj,
                'custo_frete'  => $service->custo_frete,
                'quote_json'   => Utils::jsonEncode($contentOrder)
            );
        }

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $services
            )
        );
    }

    /**
     * @param   array   $body               Corpo da requisição.
     * @param   array   $arrSkuProductId    Código de SKU e PRD_ID para gerar o retorno.
     * @param   int     $crossDocking       Dias de cross docking.
     * @return  array
     * @throws  InvalidArgumentException
     */
    private function getQuoteUnit(array $body, array $arrSkuProductId, int $crossDocking): array
    {
        try {
            $response = $this->request('POST', "/quote-simulator", array('json' => $body));
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (empty($contentOrder->transportadoras)) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Frete Rápido\n" . Utils::jsonEncode($contentOrder));
        }

        $services = array();

        foreach ($contentOrder->transportadoras as $service) {
            $services = array_merge_recursive($services, $this->formatShippingMethod($arrSkuProductId, array(
                'quote_id'      => $service->oferta,
                'token_oferta'  => $contentOrder->token_oferta,
                'method_id'     => 0,
                'value'         => $service->preco_frete,
                'deadline'      => $service->prazo_entrega + $crossDocking,
                'method'        => $service->servico . '_' . $service->oferta,
                'provider'      => $service->nome,
                'provider_cnpj' => $service->cnpj,
                'custo_frete'   => $service->custo_frete,
                'quote_json'    => Utils::jsonEncode($contentOrder)
            )));
        }

        return $services;
    }

    /**
     * @param   object   $content_response  Dados da requisição.
     * @param   array    $skumkt_product    Dados skumkt e product_id.
     * @param   int|null $crossDocking      Tempo de crossdocking do produto.
     * @return  array
     */
    private function getQuoteUnitAsync(object $content_response, array $skumkt_product, ?int $crossDocking): array
    {
        if (empty($content_response->transportadoras)) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Frete Rápido\n" . Utils::jsonEncode($content_response));
        }

        $services = array();

        foreach ($content_response->transportadoras as $service) {
            $services[] = array(
                'prd_id'        => $skumkt_product[1],
                'skumkt'        => $skumkt_product[0],
                'quote_id'      => $service->oferta,
                'token_oferta'  => $content_response->token_oferta,
                'method_id'     => 0,
                'value'         => $service->preco_frete,
                'deadline'      => $service->prazo_entrega + $crossDocking,
                'method'        => $service->servico . '_' . $service->oferta,
                'provider'      => $service->nome,
                'provider_cnpj' => $service->cnpj,
                'custo_frete'   => $service->custo_frete,
                'quote_json'    => Utils::jsonEncode($content_response)
            );
        }

        return $services;
    }

    /**
     * Contrata o frete.
     *
     * @param  array	$order	Dados do pedido.
     * @param  array	$store	Dados da loja.
     * @param  array	$nfe	Dados de nota fiscal.
     * @param  array 	$client	Dados do client.
     */
    public function hireFreight(array $order, array $store, array $nfe, array $client, bool $orderFRtWaitLabel)
    {
        $log_name = __CLASS__.'/'.__FUNCTION__;

        $this->load->model('model_orders');
        $this->load->model('model_settings');
        $this->load->model('model_quotes_ship');
        $this->load->model('model_products_catalog');

        $orders_item = $this->model_orders->getOrdersItemData($order['id']);

        $quote = array();
        $quote['volumes'] = array();
        $quote['destinatario']['endereco']['cep'] = onlyNumbers($order['customer_address_zip']);

        //Se o pedido estiver aguardando etiqueta ele vira com $orderFRtWaitLabel = true
        if ($orderFRtWaitLabel == true){
            $freights = $this->model_freights->getFreightsToGetLabelDataByOrderId($order['id']);
            $gerou_etiquetas = true;

            //foreach errado - corrigir
            foreach($freights as $freight){
                $get_labels = $this->getLabel($freight['codigo_rastreio'], $order);
                if ($get_labels['success'] == false){
                    $gerou_etiquetas = false;
                }

            }
            if($gerou_etiquetas == true) {
                $this->model_orders->updatePaidStatus($order['id'], 51);
                echo "Frete do pedido ( {$order['id']} ) contratado na Frete Rápido.\n";
                return;
            }
            else{
                echo "Frete do pedido ( {$order['id']} ) não foi possível gerar etiqueta.\n";
                throw new InvalidArgumentException("Ocorreu um problema para obter a etiqueta do pedido ( {$order['id']} ) na frete rápido. Não foi possível gerar a etiqueta.");
            }

        }

        echo 'vou buscar na tabela quotes_ship o json da antiga cotação com order_id ='.$order['id'] ;
        $quote_ship_data =  $this->model_quotes_ship->getQuoteShipByOrderId($order['id']) ;

        $initialQuoteId     = null;
        $initialTokenOffer  = null;
        $initialShipPrice   = null;
        $initialDeadline    = null;
        $initialMethod      = null;
        $initialProvider    = null;
        $initialCnpj        = null;

        if ($quote_ship_data != false) {
            $initialTokenOffer     = $quote_ship_data['token_oferta'];
            $initialQuoteId        = $quote_ship_data['oferta'];
            $initialShipPrice      = $quote_ship_data['cost'];
            $initialDeadline	   = $quote_ship_data['prazo_entrega'];
            $initialMethod 		   = $quote_ship_data['service_method'];
            $initialProvider	   = $quote_ship_data['provider'];
            $initialCnpj		   = $quote_ship_data['provider_cnpj'];
        }
        else {
            echo "Não achou valor da cotação inicial para o pedido".$order['id'].". Acessando novamente a Frete Rápido para cotar";
        }

        $cross_docking = 0;
        foreach($orders_item as $item) {
            $items_cancel = $this->model_order_items_cancel->getByOrderIdAndItem($order['id'], $item['id']);
            if ($items_cancel) {
                if ($items_cancel['qty'] != $item['qty']) {
                    $item['qty'] = $item['qty'] - $items_cancel['qty'];
                } else {
                    // O cancelamento é total, não deve considerar o produto.
                    continue;
                }
            }

            if ((float)$item['prazo_operacional_extra'] > $cross_docking) {
                $cross_docking = (float)$item['prazo_operacional_extra'];
            }

            $vl = array(
                "tipo" 			    => 999,
                "sku" 				=> $item['sku'],
                "quantidade" 		=> (int) $item['qty'],
                "altura" 			=> (float) $item['altura'] / 100,
                "largura" 			=> (float) $item['largura'] /100,
                "comprimento" 		=> (float) $item['profundidade'] /100,
                "peso"				=> (float) $item['pesobruto'],
                "valor" 			=> (float) $item['amount'],
                "volumes_produto" 	=> 1,
                "consolidar" 		=> false,
                "sobreposto"		=> false,
                "tombar"			=> false);
            $quote['volumes'][] = $vl;
        }

        $quote["destinatario"] = array(
            "tipo_pessoa" => 1,
            "endereco" => array(
                "cep" => onlyNumbers($order['customer_address_zip'])
            )
        );

        $quote["remetente"] = array(
            "cnpj" =>  onlyNumbers( $store['CNPJ']),
        );

        try {
            $quoteFreteRapido = $this->getQuoteForFreightContracting($quote, $cross_docking);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Ocorreu um problema para realizar a cotação do frete. Não foi possível realizar a cotação do frete do pedido.\n".$exception->getMessage());
        }

        $quoteFreteRapido = $quoteFreteRapido['data']['services'][0];

        $valorFrete = (float)$order['total_ship'];
        $valorFreteAntigaCotacao = $initialShipPrice !== null ? (float)$initialShipPrice : null;
        $valorFreteNovaCotacao = (float)$quoteFreteRapido['value'];

        //Situação 2 - Não existe cotação antiga na tabela quotes_ship.
        //Nesse caso, comparo o valor da nova cotação com o valor pago pelo frete.
        //Se o valor da nova cotação for menor que o valor pago, realizo a contratação.
        // verificar logica dos 4 reais.
        if ($valorFreteAntigaCotacao === null && $this->freightSeller) {
            if ($valorFreteNovaCotacao <= $valorFrete) {
                echo 'O valor da nova cotação = '.$valorFreteNovaCotacao.' foi menor ou igual ao valor pago '.$valorFrete."\n";
            }
            else {
                echo 'O custo do frete da nova cotação é maior que o valor pago no frete, problema na contratação do pedido';
                $this->model_orders->updatePaidStatus($order['id'], 101);
                return;
            }
        }

        if ($valorFreteAntigaCotacao === null && !$this->freightSeller) {
            //logística do marketplace - aceita uma diferença de até 4 reais.
            $diff = $valorFreteNovaCotacao - $valorFrete;
            echo "preço pago = ".$valorFrete;
            echo " novo preço = ".$valorFreteNovaCotacao;
            echo " diferença = ".$diff."\n";
            $difAceita = 4;

            if ($valorFreteNovaCotacao > ($valorFrete + $difAceita)) {
                echo 'Novo valor do frete '.$valorFreteNovaCotacao.' foi maior que o valor pago '.$valorFrete.' mais o valor aceitável '.$difAceita."\n";
                $this->model_orders->updatePaidStatus($order['id'], 101);
                return;
            }
            else {
                echo 'O valor da nova cotação = '.$valorFreteNovaCotacao.' foi menor ou igual ao valor pago '.$valorFrete.' mais o valor aceitável '.$difAceita."\n";
            }
        }

        //Situação 3 - Quote already exists.
        //Cotação antiga > nova cotação => contrato usando a nova cotação;
        //Cotação antiga < nova cotação => contrato usando a antiga cotação;
        if ($valorFreteAntigaCotacao !== null) {
            $actualDeadline = $quoteFreteRapido['deadline'];
            $actualMethod 	= $quoteFreteRapido['method'];
            //Verifico se a nova cotação possui menor valor e/ou menor prazo de entrega, considerando o mesmo método de entrega
            if (
                $valorFreteNovaCotacao <= $valorFreteAntigaCotacao &&
                $actualDeadline <= $initialDeadline &&
                $actualMethod == $initialMethod
            ) {
                echo 'O custo do frete da nova cotação é menor que o valor da antiga cotação para o pedido '.$order['id'];
            }
            else{
                //Usa a antiga cotação
                $quoteFreteRapido = array(
                    'quote_id'     => $initialQuoteId,
                    'token_oferta' => $initialTokenOffer,
                    'value'        => $initialShipPrice,
                    'deadline'     => $initialDeadline,
                    'method'       => $initialMethod,
                    'provider'     => $initialProvider,
                    'provider_cnpj'=> $initialCnpj,
                );
            }
        }

        $dataHire = Array();
        $dataHire["remetente"] = array(
            "cnpj" => onlyNumbers($this->dataQuote['CNPJ'])
        );

        $dataHire["destinatario"] = array(
            "cnpj_cpf" => onlyNumbers($client['cpf_cnpj']),
            "nome" => $order['customer_name'],
            "telefone" => onlyNumbers($order['customer_phone']),
            "endereco" => array(
                "cep" => onlyNumbers($order['customer_address_zip']),
                "rua" => $order['customer_address'],
                "numero" => $order['customer_address_num'],
                "bairro" => $order['customer_address_neigh'],
                "complemento" => $order['customer_address_compl'],
            ),
        );
        $dataHire["numero_pedido"] = $order['id'];
        $dateFormat = DateTime::createFromFormat("d/m/Y H:i:s", $nfe['date_emission']);
        $dataHire['nota_fiscal'] = Array(
            "numero" => $nfe['nfe_num'],
            "serie" => $nfe['nfe_serie'],
            "quantidade_volumes" => "1",
            "chave_acesso" => $nfe['chave'],
            "valor" => $order['total_order'],
            "valor_itens" => $order['total_order'],
            "data_emissao" =>$dateFormat->format('Y-m-d H:i:s')
        );

        $dateColeta = null;
        if (trim($order['data_coleta'])!= '') {
            $dateColeta = DateTime::createFromFormat("d/m/Y", $order['data_coleta']);
            $tomorrow = new DateTime('tomorrow');
            if ($dateColeta < $tomorrow) {
                $dataHire['data_coleta'] = $tomorrow->format('Y-m-d') ;
            }
            else {
                $dataHire['data_coleta'] = $dateColeta->format('Y-m-d') ;
            }
        }

        try {
            $response = $this->request('POST', "/quote/ecommerce/{$quoteFreteRapido['token_oferta']}/offer/{$quoteFreteRapido['quote_id']}", array('json' => $dataHire));
            $contentHire = Utils::jsonDecode($response->getBody()->getContents());

            if(isset($contentA4[0]->erro)){
                throw new InvalidArgumentException('Ocorreu um problema para obter a etiqueta A4 do pedido na frete rápido. Não foi possível gerar a etiqueta A4.');
            }
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        get_instance()->log_data('batch',$log_name, "Pedido ( {$order['id']} ) criado na Frete Rápido. \ncontent=" . Utils::jsonEncode($contentHire));

        // prazo previsto a partir do dia de hoje + o prazo de entrega da transportadora
        $expectedDeadline = get_instance()->somar_dias_uteis(date("Y-m-d"),$quoteFreteRapido['deadline'],'');
        if (!empty($dateColeta)) {
            // se tem prazo previsto conta a partir do dia da coleta + o prazo de entrega da transportadora
            $expectedDeadline = get_instance()->somar_dias_uteis($dateColeta->format('Y-m-d'),$quoteFreteRapido['deadline'],'');
        }

        $tracker_code = $contentHire->id_frete;

        foreach ($orders_item as $order_item) {
            $items_cancel = $this->model_order_items_cancel->getByOrderIdAndItem($order['id'], $order_item['id']);
            if ($items_cancel) {
                if ($items_cancel['qty'] != $order_item['qty']) {
                    $order_item['qty'] = $order_item['qty'] - $items_cancel['qty'];
                } else {
                    // O cancelamento é total, não deve considerar o produto.
                    continue;
                }
            }
            $freight = array(
                "order_id" 	        => $order['id'],
                "item_id"	        => $order_item['id'],
                "company_id"        => $order['company_id'],
                "ship_company"      => $quoteFreteRapido['provider'],
                "method"	        => $quoteFreteRapido['method'],
                "CNPJ" 		        => $quoteFreteRapido['provider_cnpj'],
                "status_ship"       =>  0,
                "ship_value"        => $quoteFreteRapido['value'], // mostro o que foi pago e não o valor real da contratação.
                "prazoprevisto"     => $expectedDeadline,
                "codigo_rastreio"   => $contentHire->id_frete,
                "data_etiqueta"		=> date("Y-m-d H:i:s"),
                "url_tracking"		=> $contentHire->rastreio,
                'sgp' 				=> 2, // marca que é frete rápido. Importante para o FreteRastrear
                'in_resend_active'	=> $order['in_resend_active'],
            );

            if (!$this->model_freights->create($freight)) {
                return;
            }
        }

        try {
            $this->getLabel($tracker_code, $order);
            $this->model_orders->updatePaidStatus($order['id'], 51);
            echo "Frete do pedido ( {$order['id']} ) contratado na Frete Rápido.\n";
            return;
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Ocorreu um problema para obter a etiqueta do pedido na frete rápido. Não foi possível gerar a etiqueta.\n".$exception->getMessage());
        }
    }

    /**
     * Salvar e atualizar pedido com os dados de rastreio de transportadora da Frete Rápido.
     *
     * @param   string  $tracker_code   Código do rastreio
     * @param   array   $order          Dados do pedido
     * @return  bool
     */
    public function getLabel(string $tracker_code, array $order): bool
    {
        $pathEtiquetas = $this->getPathLabel();

        $etiquetaA4      = base_url("$pathEtiquetas/P_{$order['id']}_A4_{$order['in_resend_active']}.pdf");
        $etiquetaThermal = base_url("$pathEtiquetas/P_{$order['id']}_Termica_{$order['in_resend_active']}.pdf");
        
        try {
            $response = $this->request('POST', "/labels", array('query' => array('layout' => 1)));
            $contentA4 = Utils::jsonDecode($response->getBody()->getContents());

            if(isset($contentA4[0]->erro)){
                throw new InvalidArgumentException('Ocorreu um problema para obter a etiqueta A4 do pedido na frete rápido. Não foi possível gerar a etiqueta A4.');
            }
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        try {
            $response = $this->request('POST', "/labels", array('query' => array('layout' => 2)));
            $contentThermal = Utils::jsonDecode($response->getBody()->getContents());

            if(isset($contentThermal[0]->erro)){
                throw new InvalidArgumentException('Ocorreu um problema para obter a etiqueta Térmica do pedido na frete rápido. Não foi possível gerar a etiqueta Térmica.');
            }
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }


        $getEtiquetaA4 		= $contentA4[0]->etiqueta ?? null;
        $getEtiquetaThermal = $contentThermal[0]->etiqueta ?? null;

        if ($getEtiquetaA4) {
            copy($getEtiquetaA4, FCPATH . "$pathEtiquetas/P_{$order['id']}_A4_{$order['in_resend_active']}.pdf");
        }
        if ($getEtiquetaThermal) {
            copy($getEtiquetaThermal, FCPATH . "$pathEtiquetas/P_{$order['id']}_Termica_{$order['in_resend_active']}.pdf");
        }

        $labels = array(
            'link_etiqueta_a4' 	    => $etiquetaA4,
            'link_etiqueta_termica' => $etiquetaThermal
        );

        $this->load->model('model_freights');
        $this->model_freights->updateFreights($order['id'], $tracker_code, $labels);
        return true;
    }

    /**
     * Consultar as ocorrências do rastreio para FreteRapido.
     *
     * @param   array   $order  Dados do pedido.
     * @param   array   $frete  Dados do frete.
     * @return  void            Retorna o status do rastreio.
     */
    public function tracking(array $order, array $frete): bool
    {
        try {
            $response = $this->request('GET', "/quotes/{$frete['codigo_rastreio']}/occurrences");
            $historyVolume = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        /**  Tabela de ocorrências Frete Rápido.
         *
         *   0	Solicitado / Aguardando aceitação
         *   1	Aceito / Aguardando coleta
         *   2	Em trânsito
         *   3	Entregue
         *   4	Recusado
         *   5	Entrega não realizada
         *   6	Reentrega / Nova tentativa de entrega
         *   7	Devolução / Retorno
         *   8	Em trânsito para devolução
         *   9	Devolvido ao remetente
         *   10	Recusado 72h
         *   11	Entrega parcial
         *   12	Devolução parcial
         *   13	Em trânsito para devolução parcial
         *   14	Devolvido parcialmente
         *   15	Coletado
         *   16	Em transferência
         *   17	Em rota para entrega
         *   18	Sinistro ou Extravio
         *   19	Disponível para retirada
         *   20	Entrega agendada
         *   97	Problemas com endereço de entrega
         *   98	Problemas com a carga
         *   99 Cancelado
         */

        // Incluindo as novas alterações.
        foreach ($historyVolume as $history) {
            $dataOccurrence = array(
                'description'       => $history->descricao_ocorrencia,
                'name'              => $history->nome,
                'code'              => $history->codigo,
                'code_name'         => NULL,
                'type'              => NULL,
                'date'              => date('Y-m-d H:i:s', strtotime($history->data_ocorrencia)),
                'statusOrder'       => $order['paid_status'],
                'freightId'         => $frete['id'],
                'orderId'           => $order['id'],
                'trackingCode'      => $frete['codigo_rastreio'],
                'address_place'     => NULL,
                'address_name'      => NULL,
                'address_number'    => NULL,
                'address_zipcode'   => NULL,
                'address_neigh'     => NULL,
                'address_city'      => NULL,
                'address_state'     => NULL
            );

            $this->setNewRegisterOccurrence($dataOccurrence);
        }

        return true;
    }
}