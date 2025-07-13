<?php

class UpdateOrder
{
    public $_this;
    private $order;    
    public $access_token = '';

    public function __construct($_this)
    {        
        $this->_this = $_this;   
        header('Integration: v1');
    }

    public function create($orderId, $user, $changes, $access_token)
    {
        $this->_this->setJob('WeebHook-createOrder-PluggTo');
        $log_name = 'pluggto/createOrder';

        // Pedido ainda não foi integrado
        $integratedOrder = $this->getIntegratedOrder($orderId);
        if ($integratedOrder) {
            $msgError = "Pedido já está integrado. PEDIDO_CONECTA={$orderId}.";
            $this->_this->log_data('batch', $log_name, $msgError, "W");           
            return null;
        }

        // Obter dados do pedido
        $url          = "https://api.plugg.to/orders/$orderId?access_token={$access_token}";
        $data         = "";                
        $dataOrder    = json_decode(json_encode($this->_this->sendREST($url, $data)));

        if ($dataOrder->httpcode != 200) {            
            $this->_this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados do pedido {$orderId}</h4>", "E");
            return null;
        }

        $this->access_token = $access_token;
        
        $registro = json_decode($dataOrder->content);
        $registro = $registro->Order;

        $this->_this->setUniqueId($orderId);
        $order = $this->createOrder($registro);
        if(!$order){
            $this->_this->log_integration("Erro ao criar pedido {$orderId}", "<h4>Não foi possível cadastrar o pedido {$orderId}</h4>", "E");
            return null;
        }
        return true;
        
    }

    public function update($orderId, $user, $changes, $access_token)
    {
        $this->_this->setJob('WeebHook-updateOrder-PluggTo');
        $log_name = 'pluggto/updateOrder';

        // Pedido ainda não foi integrado
        $integratedOrder = $this->getIntegratedOrder($orderId);

        if (!$integratedOrder) {
            $msgError = "Pedido ainda não integrado. PEDIDO_CONECTA={$orderId}.";
            $this->_this->log_data('api', $log_name, $msgError, "W");           
            return null;
        }
        
        $integratedOrder = $integratedOrder[0];
        $this->_this->setCompany($integratedOrder['company_id']);
        $this->_this->setUniqueId($orderId);
        $this->_this->setStore($integratedOrder['store_id']);

        // Pedido foi cancelado
        $orderCancel = $this->getOrderCancel($orderId);
        if ($orderCancel) {            
            $msgError = "Pedido foi cancelado. PEDIDO_CONECTA={$integratedOrder['id']} - PEDIDO_PLUGGTO={$orderId}. ORDER_INTEGRATION=".json_encode($integratedOrder);           
            $this->_this->log_data('api', $log_name, $msgError, "I");
            return null;
        }

        // Obter dados do pedido
        $url          = "https://api.plugg.to/orders/$orderId?access_token={$access_token}";
        $data         = "";                
        $dataOrder    = json_decode(json_encode($this->_this->sendREST($url, $data)));
        
        if ($dataOrder->httpcode != 200) {            
            $this->_this->log_integration("Erro para atualizar o pedido {$orderId}", "<p>Não foi possível localizar dados do pedido Id PluggTo {$orderId} - Id Conecta {$integratedOrder['id']}</p>", "E");
            return null;
        }
        
        $registro = json_decode($dataOrder->content);
        $registro = $registro->Order; 

        $status    = $registro->status;
        $shipments = $registro->shipments;
        $payments  = $registro->payments;
        $this->_this->setUniqueId($orderId);

        $loja = $this->_this->model_stores->getStoresData($integratedOrder['store_id']);


        $logisticaProria = $loja['freight_seller'] ?? 0;     

        if($status == 'invoiced')
        {
            if($integratedOrder['paid_status'] != 3)
            {
                $this->_this->log_data('api', $log_name, "Pedido não atualizado, pedido conecta {$integratedOrder['id']} com status <> 3", "I");
                return null;
            }
            
            $orderWithNfe = $this->getOrderWithNfe($integratedOrder['id']);                
            if ($orderWithNfe) 
            {                
                $msgError = "Pedido já tem uma NF-e. Será atualizado seu status para 52. PEDIDO_CONECTA={$integratedOrder['id']}";                
                $this->_this->log_data('api', $log_name, $msgError, "W");
                $this->_this->log_integration("Erro para atualizar o pedido {$integratedOrder['id']}", $msgError, "W");
                //Atualiza pedido para status 52
                $this->_this->load->model('model_orders');
                $this->_this->model_orders->updatePaidStatus($integratedOrder['id'], 52);
                return true;                
            }

            

            foreach($registro->shipments as $shipment){

                $this->_this->log_integration("api ", $log_name, "inserindo nfe", "E");

                $nfe_number = $shipment->nfe_number ?? null;

            // Dados da NF-e
                $nfe_number     = $shipment->nfe_number ?? null;                
                $nfe_key        = $shipment->nfe_key ?? null;
                //$nfe_link       = $shipment->nfe_link ?? null;                
                $nfe_serie      = $shipment->nfe_serie ?? null;
                $nfe_date       = $shipment->nfe_date ?? null;
                $nfe_valorNota  = $registro->total;        
        
                if(empty($nfe_number) || empty($nfe_key) || empty($nfe_serie) || 
                empty($nfe_date) || empty($nfe_valorNota))
                {
                    $this->_this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Erro - Dados da NFE do pedido {$orderId}, estão incompletos.</h4>", "E");            
                    return null;
                }
                
                // Dados para inserir a NF-e
                $arrNfe = array(                        
                    'order_id'          => $integratedOrder['id'],
                    'company_id'        => $this->_this->company,
                    'store_id'          => $this->_this->store,
                    'date_emission'     => date('d/m/Y H:i:s', strtotime($nfe_date)),
                    'nfe_value'         => number_format($nfe_valorNota, 2,".","") ?? 0,
                    'nfe_serie'         => $nfe_serie,
                    'nfe_num'           => $nfe_number,
                    'chave'             => str_replace(' ', '', $nfe_key),
                    'id_nf_marketplace' => $orderId,
                );

                // Inserir NF-e
                $insertNfe = $this->createNfe($arrNfe);
                
                if(!$insertNfe){
                    $this->_this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Erro - Dados da NFE do pedido {$orderId}, não foram salvos.</h4>", "E");            
                    return null;
                }

                $nfeDataEmissao = date('d/m/Y', strtotime($nfe_date));
                $this->_this->log_integration("Pedido {$integratedOrder['id']} atualizado",
                    "<h4>Foi atualizado dados de faturamento do pedido {$integratedOrder['id']}</h4> 
                    <ul>
                        <li><strong>Chave:</strong> {$nfe_key}</li>
                        <li><strong>Número:</strong> {$nfe_number}</li>
                        <li><strong>Série:</strong> {$nfe_serie}</li>
                        <li><strong>Data de Emissão:</strong> {$nfeDataEmissao}</li>
                        <li><strong>Valor:</strong> " . number_format($nfe_valorNota, 2, ',', '.') . "</li>
                    </ul>", "S");

                $msgOk = "Pedido {$integratedOrder['id']} atualizado <h4>Foi atualizado dados de faturamento do pedido {$integratedOrder['id']}</h4> ";
                $msgOk .= "<ul> ";
                $msgOk .= "<li><strong>Chave:</strong> {$nfe_key}</li> ";
                $msgOk .= "<li><strong>Número:</strong> {$nfe_number}</li> ";
                $msgOk .= "<li><strong>Série:</strong> {$nfe_serie}</li> ";
                $msgOk .= "<li><strong>Data de Emissão:</strong> {$nfeDataEmissao}</li> ";
                $msgOk .= "<li><strong>Valor:</strong> ".number_format($nfe_valorNota, 2, ',', '.')." </li> ";
                $msgOk .= "</ul> ";
            }
            //Atualiza pedido para status 52
            $this->_this->load->model('model_orders');
            $this->_this->model_orders->updatePaidStatus($integratedOrder['id'], 52);
            $this->_this->log_data('api', $log_name, $msgOk, "I");
            return true;

        }
        elseif($status == 'shipping_informed'){            
           
        }
        elseif($status == 'shipped')
        {           
           
            if($logisticaProria != 1)
            {
                return true;
            }
            
            if($integratedOrder['paid_status'] != 43){
                $this->_this->log_data('api', $log_name, "Pedido não atualizado, pedido conecta {$integratedOrder['id']} com status <> 43", "I");
                return null;
            }

            $dataEnvioLogistica = $registro->shipments[0]->date_shipped ?? '';
            

            if(empty($dataEnvioLogistica) || $dataEnvioLogistica == '')
            {
                $this->_this->log_integration("Erro ao atualizar o pedido pluggto = {$orderId}!", "<h4>Ocorreu um erro ao atualizar o pedido PluggTo!</h4><p>Dados da data da postagem estão incompletos!!! Id Conecta = {$integratedOrder['id']}, Id PluggTo = $orderId</p>", "E");
                return null;
            }

            $statusRet = 55;
            $historicoRet = 'Pedido confirmado a data de envio pela transportadora';
            $data = $historicoRet;
            
            $this->_this->load->model('model_orders');
            $this->_this->model_orders->updateDataEnvioStatus55($integratedOrder['id'], $dataEnvioLogistica, $statusRet);                

            $dataEnvioLogistica = date("d-m-Y H:i:s", strtotime($dataEnvioLogistica));
            $this->_this->log_integration("Pedido pluggto = {$orderId} atualizado com sucesso!", "<h4>Pedido PluggTo atualizado com sucesso!</h4><p>Pedido enviando pela transportadora id_conecta = {$integratedOrder['id']}, id_pluggto = $orderId, data de envio = {$dataEnvioLogistica}</p>", "S");
            return true;            
        }
        elseif($status == 'delivered')
        {           
            if($logisticaProria != 1)
            {
                return true;
            }

            if($integratedOrder['paid_status'] != 45){
                $this->_this->log_data('api', $log_name, "Pedido não atualizado, pedido conecta {$integratedOrder['id']} com status <> 45", "I");
               return null;
            }

            $dataEntrega = $registro->shipments[0]->date_delivered ?? date('Y-m-d H:i:s');
            
            $statusRet = 60;
            $historicoRet = 'Pedido confirmado a data de entrega pela transportadora';

            $this->_this->load->model('model_freights');
            $this->_this->model_freights->updateDataEntrega($integratedOrder['id'], array('date_delivered' => $dataEntrega,
                                                                        'updated_date' => date('Y-m-d H:i:s')));

            $this->_this->load->model('model_orders');
            $this->_this->model_orders->updateDataEntregaStatus60($integratedOrder['id'], $dataEntrega, $statusRet);
            $this->_this->log_integration("Pedido pluggto = {$orderId} entregue com sucesso!", "<h4>Pedido PluggTo entregue com sucesso!</h4><ul><li>Id_Conecta = {$integratedOrder['id']}</li><li>Id_PluggTo = $orderId</li><li>Data da Entrega = {$dataEntrega}</li></ul>", "S");
            return true;
        }

        return null;
    }


    /**
     * Recupera se o pedido já foi integrado
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se o pedido já foi integrado
     */
    private function getIntegratedOrder($orderId)
    {
        $sql = "select * from orders o 
                where o.order_id_integration = '$orderId' ";

		$query = $this->_this->db->query($sql);
		$orderCreate = $query->result_array();       
        

        return $orderCreate ? $orderCreate : false;
    }


    /**
     * Recupera se o pedido precisa ser cancelado
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se existe cancelamento
     */
    private function getOrderCancel($orderId)
    {
        $orderCancel = $this->_this->db
            ->from('orders')
            ->where(
                array(
                    'order_id_integration'  => $orderId,
                    'store_id'              => $this->_this->store
                )
            )->where_in('paid_status', array(95, 97))
            ->get()->row_array();

        if (!$orderCancel) return false;

        return true;
    }


     /**
     * Recupera se o pedido já tem uma NF-e
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se o pedido tem NF-e
     */
    private function getOrderWithNfe($orderId)
    {
        return $this->_this->db
            ->get_where('nfes',
                array(
                    'order_id' => $orderId
                )
            )->num_rows() == 0 ? false : true;
    }

    public function createOrder($order)
	{   
        $this->_this->db->trans_begin();     
        $status    = $order->status;
        $shipments = $order->shipments;
        $payments  = $order->payments;
        $items     = $order->items;

		$user_id = $order->user_id;//$this->_this->session->userdata('id');
		$bill_no = 'BILPR-'.strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
    	$data = array(
    		'bill_no' => $bill_no,
    		'customer_name' => $order->receiver_name." ".$order->receiver_lastname,
    		'customer_address' => $order->receiver_address. " Numero - ".$order->receiver_address_number." - ".$order->receiver_city,
            'customer_address_num' => $order->receiver_address_number,
    		'customer_phone' => $order->receiver_phone,
            'customer_phone_area' => $order->receiver_phone_area,
            'customer_address_compl' => $order->receiver_address_complement,
            'customer_address_neigh' => $order->receiver_neighborhood,
            'customer_address_city' => $order->receiver_city,
            'customer_address_uf' => $order->receiver_state,
            'customer_address_zip' => $order->receiver_zipcode,
            'customer_reference' => $order->receiver_additional_info,
    		'date_time' => strtotime(date('Y-m-d h:i:s a')),
    		'gross_amount' => number_format($order->total, 2,".","") ?? 0,
    		//'service_charge_rate' => $this->input->post('service_charge_rate'),    		
    		//'vat_charge_rate' => $this->input->post('vat_charge_rate'),    		
    		'net_amount' => number_format($order->subtotal, 2,".","") ?? 0,
    		'discount' => number_format($order->discount, 2,".","") ?? 0,
            'numero_marketplace' => $order->original_id,
            'origin' => 'PluggTo',
            'order_id_integration' => $order->id,
            'total_ship' => number_format($order->shipping, 2,".","") ?? 0,
            'total_order' => number_format($order->total_paid, 2,".","") ?? 0,
    		'paid_status' => 1,
            'store_id' => $this->_this->store,
    		'company_id' => $this->_this->company,
    		'user_id' => $user_id
    	);

		$insert = $this->_this->db->insert('orders', $data);
		$order_id = $this->_this->db->insert_id();
        if(empty($order_id) || ($order_id <= 0)){            
            $this->_this->db->trans_rollback();
            return false;
        }
		//get_instance()->log_data('api', $log_name, json_encode($data),"I");
		$this->_this->load->model('model_products');
       

		foreach($items as $item)
        {   
            $verifyProduct = $this->getProductForSku($item->sku);

            ///é um produto pai
            if(!empty($verifyProduct)){               
                $items_order = array(
                    'order_id' => $order_id,    			
                    'qty' => $item->quantity,
                    'name' => $item->name,
                    'discount' => number_format($item->discount, 2,".","") ?? 0,
                    'sku' => $item->sku,
                    'product_id' => $verifyProduct['id'],
                    'amount' => number_format($item->total, 2,".","") ?? 0,
                    'store_id' => $this->_this->store,
    		        'company_id' => $this->_this->company,
                    'un' => 'Un',
                );
    
                try {
                    $this->_this->db->insert('orders_item', $items_order);

                    // now decrease the stock from the product    		        
    		        $qty = (int) $verifyProduct['qty'] - (int) $item->quantity;

                    //$idProductPai, $skuProduct, $stockNew, $skuPai = null
    		        $updateStock = $this->_this->updateProduct->updateStock($verifyProduct['id'],$item->sku,$qty);

                    if ($updateStock == false) {
                        $this->_this->db->trans_rollback();
                        return false;
                    }
                }
                catch (PDOException $exc)
                {
                    $this->_this->db->trans_rollback();
                    return false;
                }
            }else{               
                
                $verifyProductVar = $this->getVariationForSku($item->sku);
                ///é um produto pai                
                if(!empty($verifyProductVar)){              
                    $items_order = array(
                        'order_id' => $order_id,    			
                        'qty' => $item->quantity,
                        'name' => $item->name,
                        'discount' => number_format($item->discount, 2,".","") ?? 0,
                        'sku' => $item->sku,
                        'product_id' => $verifyProductVar['id'],
                        'amount' => number_format($item->total, 2,".","") ?? 0,
                        'store_id' => $this->_this->store,
    		            'company_id' => $this->_this->company,
                        'un' => 'Un',
                    );
        
                    try {
                        $this->_this->db->insert('orders_item', $items_order);

                        // now decrease the stock from the product    		        
                        $qty = (int) $verifyProductVar['qty'] - (int) $item->quantity;
                        $prodPai = $this->getProductForId($verifyProductVar['prd_id']);
                       
                        $updateStock = $this->_this->updateProduct->updateStock($verifyProductVar['id'],$item->sku,$qty, $prodPai['sku']);

                        if ($updateStock == false) {
                            $this->_this->db->trans_rollback();
                            return false;
                        }
                        continue;
                    }
                    catch (PDOException $exc)
                    {
                        $this->_this->db->trans_rollback();
                        return false;
                    }
                } 
                
                //sku não encontrado na tabela products e não encontrado na tabela prd_variants
                $this->_this->db->trans_rollback();
                return false;
            } 		
    	}

        if ($this->_this->db->trans_status() === false) {
            $this->_this->db->trans_rollback();
            echo "ocorreu um erro\n";
        }         

        $this->_this->db->trans_commit();

		return ($order_id) ? $order_id : false;
	}

    /**
     * Cria dados de faturamento do pedido e atualiza o status do pedido para 52
     *
     * @param   array   $data   Dados da nfe para inserir
     * @return  bool            Retorna o status da criação
     */
    private function createNfe($data)
    {
        $sqlNfe     = $this->_this->db->insert_string('nfes', $data);
        $insertNfe  = $this->_this->db->query($sqlNfe) ? true : false;

        if (!$insertNfe) return false;	
		
        return $this->updateStatusForOrder($data['order_id'], 52, 3);
    }


    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForSku($sku)
    {
        return $this->_this->db->get_where('products use index (store_sku)',
            array(
                'store_id'  => $this->_this->store,
                'sku'       => $sku
            )
        )->row_array();
    }


    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForId($prod_id)
    {
        return $this->_this->db->get_where('products',
            array(
                'store_id'  => $this->_this->store,
                'id'        => $prod_id
            )
        )->row_array();
    }

    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getVariationForSku($sku)
    {
        return $this->_this->db
            ->select('prd_variants.*')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(                    
                    'prd_variants.sku' => $sku,
                    'products.store_id'  => $this->_this->store,
                )
            )
            ->get()
            ->row_array();
    }
    
     /**
     * Atualiza status de um pedido
     *
     * @param   int     $orderId        Código do pedido
     * @param   int     $status         Código do status
     * @param   int     $verifyStatus   Código do status para verificação
     * @return  bool                    Retorna o status da atualização
     */    

    private function updateStatusForOrder($orderId, $status, $verifyStatus = null)
    {
        $where = array(
            'id'        => $orderId,
            'store_id'  => $this->_this->store,
        );
        if ($verifyStatus) $where['paid_status'] = $verifyStatus;

        return $this->_this->db->where($where)->update('orders', array('paid_status' => $status)) ? true : false;
    }

}