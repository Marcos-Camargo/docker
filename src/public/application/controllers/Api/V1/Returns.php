<?php

require APPPATH . "controllers/Api/V1/API.php";

/**
 * @property CI_DB_driver $db
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Security $security
 * @property CI_Output $output
 *
 * @property Model_settings $model_settings
 * @property Model_orders_to_integration $model_orders_to_integration
 * @property Model_orders $model_orders
 * @property Model_freights $model_freights
 * @property Model_products $model_products
 * @property Model_requests_cancel_order $model_requests_cancel_order
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_groups $model_groups
 * @property Model_billet $model_billet
 * @property Model_orders_item $model_orders_item
 * @property Model_clients $model_clients
 * @property Model_stores $model_stores
 * @property Model_providers $model_providers
 * @property Model_integrations $model_integrations
 * @property Model_orders_pickup_store $model_orders_pickup_store
 * @property Model_commissionings $model_commissionings
 * @property Model_commissioning_orders_items $model_commissioning_orders_items
 * @property Model_legal_panel $model_legal_panel
 * @property Model_product_return $model_product_return
 * @property Model_order_itens_cancel $model_order_items_cancel
 *
 * @property OrdersMarketplace $ordersmarketplace
 * @property CalculoFrete $calculofrete
 */

class Returns extends API
{

    public $PAID_STATUS = [
        'allowed_status' => [6,60,110,7,81,111]
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_orders');
        $this->load->model('model_freights');
        $this->load->model('model_products');
        $this->load->model('model_groups');
        $this->load->model('model_orders_item');
        $this->load->model('model_clients');
        $this->load->model('model_stores');
        $this->load->model('model_providers');
        $this->load->model('model_integrations');
        $this->load->model('model_product_return');
        $this->load->model('model_order_items_cancel');
    }

    public function index_post()
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
       
        $this->header = array_change_key_case(getallheaders());
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $body = $this->cleanGet(json_decode($this->security->xss_clean($this->input->raw_input_stream), true));

        if (!array_key_exists('sellerId', $body) || 
            !array_key_exists('marketplaceId', $body) || 
            !array_key_exists('devolutionStatus', $body) || 
            !array_key_exists('devolutionType', $body) || 
            !array_key_exists('items', $body) || 
            empty($body['items'])) {

            return $this->response(array(
                'success' => false, 
                'message' => "Corpo da requisição em um formato inválido. Certifique-se de que os campos 'sellerId', 'marketplaceId', 'devolutionStatus', 'devolutionType' e 'items' estão presentes e que 'items' contém pelo menos um item com os campos obrigatórios."
            ), REST_Controller::HTTP_BAD_REQUEST);
        }

        // Verificar o devolutionType no primeiro momento vamos tratar somente o devolution mas já vou deixar os proximos mapeados
        //$validDevolutionTypes = ['cancel', 'devolution', 'trade'];
        $validDevolutionTypes = ['devolution'];
        if (!in_array($body['devolutionType'], $validDevolutionTypes)) {
            return $this->response(array(
                'success' => false, 
                'message' => "O campo devolutionType está incorreto."
            ), REST_Controller::HTTP_BAD_REQUEST);
        }

        $validDevolutionStatus = ['created','sended','returned'];
        if (!in_array($body['devolutionStatus'], $validDevolutionStatus)) {
            return $this->response(array(
                'success' => false, 
                'message' => "O campo devolutionStatus está incorreto."
            ), REST_Controller::HTTP_BAD_REQUEST);
        }
      
        foreach ($body['items'] as $item) {
            if (!isset($item['skumarketplace']) || 
                !isset($item['refundValue']) || 
                !isset($item['refundFreightValue']) || 
                !isset($item['reason'])) {

                return $this->response(array(
                    'success' => false, 
                    'message' => "Cada item deve conter 'skumarketplace', 'refundValue', 'refundFreightValue' e 'reason'."
                ), REST_Controller::HTTP_BAD_REQUEST);
            }
        }
      
        try {
            $order = $this->model_orders->getOrdersDatabyNumeroMarketplace($body['marketplaceId']);
         
            //Validando se o pedido esta com o status entregue ou esta em algum status de devolução
           if(!in_array($order['paid_status'], $this->PAID_STATUS['allowed_status'])){
                return $this->response(array(
                    'success' => false, 
                    'message' => "Pedido {$item['order_id']} ainda não consta como entregue no seller center. Aguarde e tente novamente mais tarde pedido."
                ), REST_Controller::HTTP_BAD_REQUEST);
           }
            
            //Antes de fazer qualquer coisa, verifico se o provider tem acesso a essa loja que esta sendo enviada
            $this->validateProviderinStorebyOrder($this->header['x-provider-key'],$order['store_id']);

            $itens = $this->model_orders_item->getItensByOrderIdWithSkumkt($order['id']);

            $body_items = $body['items'];

            // Criar um array para contar as ocorrências de cada SKU no payload
            $body_sku_count = [];
            foreach ($body_items as $body_item) {
                if (!isset($body_sku_count[$body_item['skumarketplace']])) {
                    $body_sku_count[$body_item['skumarketplace']] = 0;
                }
                $body_sku_count[$body_item['skumarketplace']]++;
            }

            // Calcular a quantidade total de itens no pedido
            $total_items_pedido = array_sum(array_column($itens, 'qty'));
        
            // Evitar divisão por zero
            $return_shipping_value = ($total_items_pedido > 0) ? round($order['total_ship'] / $total_items_pedido, 2) : 0;
           
            // Validar se há mais itens no payload do que disponíveis no pedido
            foreach ($itens as $item) {
                $sku = $item['skumkt'];
                $available_quantity = $item['qty']; 

                // Verificar se o SKU existe no payload
                if (isset($body_sku_count[$sku])) {
                    $requested_quantity = $body_sku_count[$sku];

                    if ($requested_quantity > $available_quantity) {
                        return $this->response(array(
                            'success' => false,
                            'message' => "Não foi possível realizar a devolução pois a quantidade de itens para o SKU '{$sku}' ultrapassa a quantidade disponível no pedido  {$item['order_id']}."
                        ), REST_Controller::HTTP_BAD_REQUEST);
                    }
                }
            }
          
            //lista para armazenar apenas os itens correspondentes
            $filtered_itens = [];
          
            foreach ($itens as &$item) {
                $item['qtdItem'] = 0;
                $item['refundValue'] = 0;
                $item['refundFreightValue'] = 0;
        
                $found_in_body = false;

                foreach ($body_items as $body_item) {
                    // Se os SKUs forem iguais, insere ou incrementa os valores de refund e qtdItem
                    if ($item['skumkt'] === $body_item['skumarketplace']) {
                        $item['qtdItem'] += 1;
                        $item['refundValue'] += $body_item['refundValue'];
                        $item['refundFreightValue'] =  $return_shipping_value;
                        $item['reason'] = $body_item['reason'];
                        $item['skumkt'] = $body_item['skumarketplace'];

                        $found_in_body = true;
                    }
                }

                if ($found_in_body) {
                    $filtered_itens[] = $item;
                }

            }
            
            // $filtered_itens contem apenas os itens presentes no JSON enviado
            $itens = $filtered_itens;

            if (empty($filtered_itens)) {
                return $this->response(array(
                    'error' => true,
                    'data' => "Nenhum item correspondente foi encontrado para a ordem {$order['id']}. Verifique o sku dos itens enviados."
                ));
            }

            //Já verificamos que o provider tem acesso a loja e já recuperamos a order e os itens da order
            switch ($body['devolutionStatus']) {
                case 'created':
                    $result = $this->DevolutionCreated($body, $order, $itens);
                    break;

                case 'sended':
                    $result = $this->DevolutionSended($body,$order,$itens);
                    break;

                case 'returned':
                    $result =$this->DevolutionReturned($body,$order,$itens);
                    break;

                default:
                    return $this->response(array(
                        'success' => false, 
                        'message' => "O devolutionStatus fornecido é inválido."
                    ), REST_Controller::HTTP_BAD_REQUEST);
            }


        } catch (Exception | Error $exception) {
            return $this->response(array('success' => false, 'message' => $exception->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }
        $type = $body['devolutionType'];

        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, "type=$type - payload: " . json_encode($body));

        // Verifica se foram encontrados algum erro
        if (isset($result['error']) && $result['error']){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - payload: " . json_encode($body),"W");
            $this->response($this->returnError($result['data']), $result['http_code'] ?? REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,json_encode($body));
        $this->response(array('success' => true,"message" => $result['data'] ?? $this->lang->line('api_order_updated')), REST_Controller::HTTP_OK);
    }

    private function DevolutionCreated($body,$order,$itens = null){

         $log_name = __CLASS__ . '/' . __FUNCTION__;

        // Buscar itens já cancelados no banco
        $cancelled_items = $this->model_order_items_cancel->getByOrderId($order['id']);
        //Criar array para usar na contagem de itens devolvidos no final
        $cancelled_sku_count = [];
        foreach ($cancelled_items as $cancelled_item) {
            if (!isset($cancelled_sku_count[$cancelled_item['item_id']])) {
                $cancelled_sku_count[$cancelled_item['item_id']] = 0;
            }
            $cancelled_sku_count[$cancelled_item['item_id']] += $cancelled_item['qty'];
        }

        //valida se o order id já foi enviado com esse status, para não receber registros repetidos
        foreach ($itens as $item){
             
            // Verifica se o item foi cancelado, permitindo devoluções apenas de itens não totalmente cancelados
            $cancelled_qty = $cancelled_sku_count[$item['id']] ?? 0;
            $total_qty_in_order = $item['qty']; // Quantidade total do item no pedido
            $available_qty = $total_qty_in_order - $cancelled_qty; // Quantidade disponível para devolução
            // Se o SKU foi completamente cancelado, bloqueia a devolução
            if ($available_qty <= 0) {
                return [
                    'error' => true,
                    'data' => "O item '{$item['skumkt']}' já foi totalmente cancelado e não pode ser devolvido."
                ];
            }
            
            // Verificar se o cliente está tentando devolver mais itens do que o permitido
            $cancelled_qty = $cancelled_sku_count[$item['id']] ?? 0;
            $available_qty = $item['qty'] - $cancelled_qty; // Quantidade disponível para devolução
            if ($available_qty < $item['qtdItem']) {
                return ['error' => true, 'data' => "Não foi possível realizar a devolução pois a quantidade de itens ({$item['qtdItem']}) para o SKU '{$item['skumkt']}' ultrapassa o permitido ({$available_qty})."];
            }

            //Verificando primeiro se já não existe algum item devolvido do pedido, isso foi criado para evitar que dois fluxos juridicos sejam criados no final do processo
            $verify_order_ID_exists = $this->model_product_return->getByOrderId($item['order_id']);
            if($verify_order_ID_exists) {
                $this->log_data('api', $log_name, "Já existe um registro para a order {$item['order_id']} com o status de Devolução Solicitada.\n\n body=".json_encode($item)."\n\n header=".json_encode(getallheaders()), "E");
                return array('error' => true, 
                'data' => "Não foi possível realizar a devolução pois já existe uma devolução cadastrada para o pedido {$item['order_id']} com o item {$verify_order_ID_exists[0]['sku_marketplace']}");
            }

            //Aqui verifico se já não existe um registro desse mesmo skumkt na tabela product_return
            $verify_order_ID = $this->model_product_return->getOrderId($item['order_id'], $item['skumkt']);
            if($verify_order_ID) {
                $this->log_data('api', $log_name, "Já existe um registro para o item {$item['sku']} com o mesmo status.\n\n body=".json_encode($item)."\n\n header=".json_encode(getallheaders()), "E");
                return array('error' => true, 
                'data' => "Não foi possível realizar a devolução pois já existe uma devolução cadastrada para esse pedido {$item['order_id']}");
            }
        }     
      
      
        foreach ($itens as $item) {
                $product_devolution_data = array(
                    'status' => 'a_contratar',
                    'order_id' => $item['order_id'],
                    'return_total_value' => $item['rate'] * $item['qtdItem'], // valor do produto pela qtd de itens do pedido a serem devolvidos
                    'return_shipping_value' => $item['refundFreightValue'] * $item['qtdItem'], // valor do frete calculado pela qtd de itens do pedido a serem devolvidos
                    'sku_marketplace' => $item['skumkt'],
                    'quantity_requested' => $item['qtdItem'],
                    'quantity_in_order' => $item['qty'],
                    'motive' => $item['reason'],
                    'product_id' => $item['product_id'],
                    'store_id' => $item['store_id'],
                    'devolution_invoice_number' => $this->generateNfeNumber(),
                    'devolution_contract_date' => date('Y-m-d H:i:s'),
                    'devolution_request_date' => date('Y-m-d'),
                    'return_nfe_emission_date' => date('Y-m-d H:i:s'),
               	    'variant' => isset($item['variant']) && trim($item['variant']) !== '' ? $item['variant'] : null
		        );
    
                $create_data = $this->model_product_return->create($product_devolution_data);
    
                if(!$create_data)
                    $this->log_data('api', $log_name, "Não foi possível criar o registro na tabela.\n\n body=" . json_encode($product_devolution_data) . "\n\n header=" . json_encode(getallheaders()), "E");

                if ($create_data == False) {
                    $this->log_data('api', $log_name, "Não foi possível criar o registro na tabela.\n\n body=" . json_encode($product_devolution_data) . "\n\n header=" . json_encode(getallheaders()), "E");
                    return $this->response(array(
                        'error' => true,
                        'data' => 'Não foi possível criar o registro na tabela do banco de dados'
                    ));
                }
    
                if ($create_data) {
                    $this->model_order_items_cancel->create(array(
                        'order_id' => $item['order_id'],
                        'item_id' => $item['id'],
                        'qty' => $item['qtdItem']
                    ));
                   $this->log_data('api', $log_name, "Criado o registro na tabela .\n\n body=" . json_encode($product_devolution_data) . "\n\n header=" . json_encode(getallheaders()), "E");
                }

                unset($product_devolution_data);
        }
        
        //Se chegou até aqui mudo o status para Devolução solicitada
        $this->model_orders->updatePaidStatus($order['id'], 110);
        
        return array('error' => false, 
        'data' => "Registro criado para a order {$order['id']} com o status de Devolução Solicitada");
       
    }

    private function DevolutionSended($body,$order,$itens = null){

        $log_name = __CLASS__ . '/' . __FUNCTION__;

        // Buscar os itens cancelados da tabela `order_items_cancel` para o pedido
        $items_in_cancel = $this->model_order_items_cancel->getByOrderId($order['id']);
        
        // Crie um array com os IDs dos itens cancelados
        $cancelled_item_ids = array_column($items_in_cancel, 'item_id');

       //preciso verificar se os itens enviados estão no products_return
       foreach ($itens as $item){

            // Verifique se o item está presente na lista de itens cancelados
            if (!in_array($item['id'], $cancelled_item_ids)) {
                continue; // Pula o item se ele não estiver na lista de cancelados
            }

           $rowProduct_return = $this->model_product_return->getOrderId($item['order_id'], $item['skumkt']);
       

	   if (empty($rowProduct_return)) {
  		  $this->log_data('api', $log_name, "Nenhum registro encontrado para o item {$item['sku']}.\n\n body=" . json_encode($item) . "\n\n header=" . json_encode(getallheaders()), "E");
    		return array('error' => true, 'data' => "Nenhum registro encontrado para o item {$item['sku']} do pedido {$item['order_id']}.");
        	}


 
           if($rowProduct_return['status'] == 'coletado') {
               $this->log_data('api', $log_name, "Já existe um registro para o item {$item['sku']} com o mesmo status.\n\n body=".json_encode($item)."\n\n header=".json_encode(getallheaders()), "E");
               return array('error' => true, 
               'data' => "Não foi possível realizar a devolução pois já existe uma devolução cadastrada para esse pedido {$item['order_id']}");
           }else if($rowProduct_return['status'] == 'devolvido'){
                $this->log_data('api', $log_name, "Já existe um registro para o item {$item['sku']} com o status Devolvido.\n\n body=".json_encode($item)."\n\n header=".json_encode(getallheaders()), "E");
                return array('error' => true, 
                'data' => "Já existe um registro para o item {$item['sku']} do pedido {$item['order_id']} com o status Devolvido");
           }
           
           $product_devolution_data = array(
               'status'                    => 'coletado', 
               'order_id'                  => $item['order_id'],
               //'sku_marketplace'           => $item['sku'],
               'product_id'                => $item['product_id'],
               'store_id'                  => $item['store_id'],  
           );

           $create_data = $this->model_product_return->updateById($rowProduct_return['id'] ,$product_devolution_data); 
           
           if($create_data == False) {
               $this->log_data('api', $log_name, "Não foi possível criar o registro na tabela.\n\n body=".json_encode($product_devolution_data)."\n\n header=".json_encode(getallheaders()), "E");
               return $this->response(array(
                   'error' => true, 
                   'data' => 'Não foi possível criar o registro na tabela do banco de dados'));
           }
   

         }  
           
        //Se chegou até aqui mudo o status para Em Devolução
       $this->model_orders->updatePaidStatus($order['id'], 7);
       
       return array('error' => false, 
       'data' => "Registro alterado. Pedido {$order['id']} com o status de Em Devolução");
      
   }

   private function DevolutionReturned($body, $order, $itens = null) {
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $total_items = count($itens);  // Número total de itens no pedido
        $devolvidos_completamente = 0;  // Contador de itens devolvidos completamente
        $devolvidos_parcialmente = 0;  // Contador de itens devolvidos parcialmente

        // Obter todos os itens do pedido
        $order_items = $this->model_orders_item->getItensByOrderId($order['id']);

        // Obter itens cancelados do pedido
        $cancelled_items = $this->model_order_items_cancel->getByOrderId($order['id']);
        $cancelled_items_by_sku = [];
        foreach ($cancelled_items as $cancelled_item) {
            $cancelled_items_by_sku[$cancelled_item['sku']] = $cancelled_item['qty'];
        }

        // Verifica se os itens enviados estão no `product_return` e faz a validação
        foreach ($itens as $item) {
            $rowProduct_return = $this->model_product_return->getOrderId($item['order_id'], $item['skumkt']);

		if (empty($rowProduct_return)) {
 		   $this->log_data('api', $log_name, "Nenhum registro encontrado para o item {$item['sku']}.\n\n body=" . json_encode($item) . "\n\n header=" . json_encode(getallheaders()), "E");
    		return array('error' => true, 'data' => "Nenhum registro encontrado para o item {$item['sku']} do pedido {$item['order_id']}.");
		}

            if (!$rowProduct_return['status']) {
                $this->log_data('api', $log_name, "Não existe um registro para o item {$item['sku']}.\n\n body=" . json_encode($item) . "\n\n header=" . json_encode(getallheaders()), "E");
                return array('error' => true, 'data' => "Não existe um registro para o item {$item['sku']} do pedido {$item['order_id']}");
            }

            // Verifica se o item enviado está entre os itens da ordem
            foreach ($order_items as $order_item) {
                if ($order_item['sku'] == $item['sku']) {
                    // Valida quantidade devolvida
                    $cancelled_qty = isset($cancelled_items_by_sku[$item['sku']]) ? $cancelled_items_by_sku[$item['sku']] : 0;
                    $remaining_qty = $item['qty'] - $cancelled_qty;

                    if ($item['qtdItem'] == $remaining_qty) {
                        // Item totalmente devolvido
                        $product_devolution_data = array(
                            'status' => 'devolvido',
                            'order_id' => $item['order_id'],
                          //  'sku_marketplace' => $item['sku'],
                            'product_id' => $item['product_id'],
                            'store_id' => $item['store_id'],
                        );

                        $create_data = $this->model_product_return->updateById($rowProduct_return['id'], $product_devolution_data);

                        if (!$create_data) {
                            $this->log_data('api', $log_name, "Não foi possível atualizar o registro na tabela.\n\n body=" . json_encode($product_devolution_data), "E");
                            return $this->response(array('error' => true, 'data' => 'Não foi possível atualizar o registro na tabela do banco de dados'));
                        }

                        $devolvidos_completamente++;
                    } elseif ($item['qtdItem'] < $remaining_qty) {
                        // Item devolvido parcialmente
                        $product_devolution_data = array(
                            'status' => 'devolvido parcialmente',
                            'order_id' => $item['order_id'],
                           // 'sku_marketplace' => $item['sku'],
                            'product_id' => $item['product_id'],
                            'store_id' => $item['store_id'],
                        );

                        $create_data = $this->model_product_return->updateById($rowProduct_return['id'], $product_devolution_data);

                        if (!$create_data) {
                            $this->log_data('api', $log_name, "Não foi possível atualizar o registro na tabela.\n\n body=" . json_encode($product_devolution_data), "E");
                            return $this->response(array('error' => true, 'data' => 'Não foi possível atualizar o registro na tabela do banco de dados'));
                        }

                        $devolvidos_parcialmente++;
                    }
                }
            }
        }

        // Verifica o status do pedido com base nas devoluções
        if ($devolvidos_completamente + $devolvidos_parcialmente === $total_items) {
            // Se todos os itens foram devolvidos
            if ($devolvidos_completamente === $total_items) {
                // Todos os itens foram totalmente devolvidos
                $this->model_orders->updatePaidStatus($order['id'], 81);  // Status de "Devolvido"
                $this->model_orders->updateOrderById($order['id'], array("product_return_status" => 3));
                $status_message = "Pedido {$order['id']} com o status de Devolvido.";
            } else {
                // Alguns itens foram devolvidos parcialmente
                $this->model_orders->updatePaidStatus($order['id'], 111);  // Status de "Devolvido Parcialmente"
                $this->model_orders->updateOrderById($order['id'], array("product_return_status" => 2));
                $status_message = "Pedido {$order['id']} com o status de Devolvido Parcialmente.";
            }
            $this->log_data('api', $log_name, "Produto atualizado na tabela tabela.\n\n body=" . json_encode($status_message), "E");
        } else {
            // Ainda há itens que não foram devolvidos
            $this->model_orders->updatePaidStatus($order['id'], 111);  // Status de "Devolvido Parcialmente"
            $status_message = "Pedido {$order['id']} com o status de Devolvido Parcialmente.";
        }

        return array('error' => false, 'data' => $status_message);
    }


        /**
     * Valida se o provider realmente existe, se fornecedor existe e se o forncedor por operar com a store.
     *
     * @param   string      $providerId
     *  @param  string      $store_id
     * @throws  Exception
     */
    private function validateProviderinStorebyOrder(string $providerId, string $store_id)
    {
        // fornecedor não encontrado.
        if (!$provider = $this->model_providers->getProviderData($providerId)) {
            throw new Exception("Fornecedor '$provider' não localizado.");
        }
        $stores =  $this->model_stores->getProviderinStores($store_id,$providerId);
    
        // fornecedor não pode publicar no marketplace.
        if ($provider['id'] != $stores['provider_id']) {
            throw new Exception("Fornecedor '$providerId' sem permissão para gerenciar  a store " .  $stores['id'] );
        }
    }

    private function generateNfeNumber() {
        $nfe_number = '';
        for ($i = 0; $i < 44; $i++) {
            $nfe_number .= mt_rand(0, 9);
        }
        return $nfe_number;
    }
}
