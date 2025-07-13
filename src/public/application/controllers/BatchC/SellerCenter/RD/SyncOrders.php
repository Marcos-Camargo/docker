<?php

require APPPATH . "controllers/BatchC/SellerCenter/RD/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

// php index.php BatchC/SellerCenter/RD/SyncOrders run null RaiaDrogasil 
class SyncOrders extends Main
{
    const STATUS_SC_NEW = 1;
    const STATUS_SC_PAYMENT_ACCEPT = 3;
    const STATUS_SC_CANCELED = 90;
    const STATUS_MKT_NEW = 'CRIADO';
    const STATUS_MKT_PAYMENT_ACCEPT = 'PAGAMENTO_CONFIRMADO';
    const STATUS_MKT_CANCELED = 'CANCELADO'; 
    
    var $auth_data;
    var $order_db = null;
    var $credential = null;
    var $auth = null;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_integrations');
        $this->load->model('model_orders');
        $this->load->model('model_sc_last_post');
        $this->load->model('model_stores');
        $this->load->model('model_clients');
        $this->load->model('model_promotions');
        $this->load->model('model_category');
        $this->load->model('model_log_integration_order_marketplace');
        $this->load->library('calculoFrete');
        $this->load->library('ordersMarketplace');
    }

    function run($id = null, $params=null)
    {
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)){
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id),"I");

        if(is_null($params)){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
            echo PHP_EOL . "FIM SYNC ORDERS" . PHP_EOL;
            $this->log_data('batch',$log_name,'finish',"I");
            $this->gravaFimJob();
            die;
        }

        $this->model_sc_last_post->setIntTo("rd");
        $integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
        if($integration){
            $this->credential = json_decode($integration['auth_data']);
            $this->auth = $this->auth($this->credential->api_url, $this->credential->grant_type, $this->credential->client_id, $this->credential->client_secret);
            
            echo 'Sync: '. $integration['int_to']."\n";
            $this->syncIntTo($this->credential, $this->auth, $integration['int_to']);
        }

        echo PHP_EOL . "FIM SYNC ORDERS" . PHP_EOL;
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();

    }

    function getOrder($credential, $auth) {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $endPoint   = '/marketplace/orders/queue';
        $this->auth_data = $credential;

        $this->process($credential, $auth, $endPoint);

        if ($this->responseCode >= 500) {
            return $this->getOrder($credential, $auth);
        }

        if ($this->responseCode >= 300) {
            $erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para pegar pedidos. Msg: " . $this->errorMensagem;
            echo $erro . "\n";
            $this->log_data('batch', $log_name, $erro, "E");
            return false;
        }

        if ($this->responseCode == 204) {
            $fim = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para pegar pedidos. Msg: Fila zerada";
            echo $fim . "\n";
            $this->log_data('batch', $log_name, $fim, "I");
            return false;
        }

        $order = json_decode($this->result, true);
        if (!is_null($order)) {
            if (array_key_exists('id', $order)) {
                return $order;
            }
        }
        return false;
    }

    function syncIntTo($credential, $auth, $int_to)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $count = 1;
        while ($order = $this->getOrder($credential, $auth)) {
            echo $count++ . " - " . json_encode($order) . PHP_EOL;

	    try {
            $success = false;
            if ($paid_status = $this->getOrderPaidStatus($order)) {
                $success = $this->updateOrder($order, $paid_status, $int_to);
            }
	        else {
		        echo "Verificar se o pedido é valido" . PHP_EOL;
                $arr = $this->checkOrderIsValid($order);
                $is_valid = $arr[0];
                $store_id = $arr[1];

                echo "Valido: ". json_encode($arr) . PHP_EOL;
                echo "Store ID: ". $store_id . PHP_EOL;
                if ($is_valid) {
                    $store = $this->model_stores->getStoresById($store_id);
                    $orderExist = $this->model_orders->getOrderBynumeroMarketplaceAndOrigin($order['orderNumber']);
                    if ($orderExist) {
                        $this->log_data('batch', $log_name, 'O Pedido ('.$order['orderNumber'].') já criado no Seller Center com id: '.$orderExist['id'], "I");
                        return $success = true;
                    }
                    $success = $this->createOrder($order, $store, $int_to);
                    if ($success) {
                        if ($paid_status = $this->getOrderPaidStatus($order)) {
                            $success = $this->updateOrder($order, $paid_status, $int_to);
                        }
                        else $success = false;
                    }
                }
                else $success = $this->cancelOrder($order);
            }

            if ($success) {
                $this->removeQueue($credential, $auth, $order);
	    }
	    } catch (Exception $e) {
	    	echo 'Caught exception: ',  $e->getMessage(), "\n";
	    }
        }

        return ;
    }

    private function checkOrderIsValid($order) {
        $is_valid = true;
        $store_id = false;
        foreach ($order['items'] as $item) {
            $last_post = $this->model_sc_last_post->getBySkuMkt($item['sellerSku']);
            if ($last_post) {
                if ($item['qty'] > $last_post['qty']) {
                    $is_valid = false;
                }
                $store_id = $last_post['store_id'];
            }
            else {
                $is_valid = false;
            }
        }

        if (count($order['items']) <= 0)
        {
            $is_valid = false;
        }

        return [$is_valid, $store_id];
    }

    private function getOrderPaidStatus($order) {
        if ($this->order_db = $this->model_orders->getOrdersDatabyNumeroMarketplace($order['orderNumber'])) {
            return $this->order_db['paid_status'];
        }
        return false;
    }

    private function createOrder($order, $store_db, $int_to) {
        echo 'CREATE ORDER '. $order["id"] . PHP_EOL;

        $this->db->trans_begin();

        $success = false;
        $client_db = $this->saveClient($order, $int_to);
        if ($client_db !== false) {
            $order_db = $this->saveOrder($order, $client_db, $store_db, $int_to);
            if ($order_db !== false) {
                $order_items_db = $this->saveOrderItems($order, $order_db['id'], $int_to);
                if ($order_items_db !== false)
                {
                    $success = true;
                }
            }
        }
        
        if (($success === false) || ($this->db->trans_status() === FALSE))
	    {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();

        if (isset($order_db['id'])) {
            // Gravando o log do pedido
            $data_log = array(
                'int_to' 	=> $int_to,
                'order_id'	=> $order_db['id'],
                'received'	=> json_encode($order)
            );
            $this->model_log_integration_order_marketplace->create($data_log);

            $this->calculofrete->updateShipCompanyPreview($order_db['id']);
        }

    	return true;
    }

    private function updateOrder($order, $paid_status, $int_to) {
        $success = false;
        switch ($order['status']) {
            case self::STATUS_MKT_NEW:
                $success = true;
                break;
            case self::STATUS_MKT_PAYMENT_ACCEPT:
                if ($paid_status == self::STATUS_SC_NEW) {
                    $success = $this->updatePaymentAccept($order, $int_to);
                }
                else $success = true;
                break;
            //TODO - IMPLEMENTAR O CANCELAMENTO PRÉ
            case self::STATUS_MKT_CANCELED:
                    $success = $this->updateCanceled($order);
                break;
            default:
                $success = true;
                break;
        }
        
        return $success;
    }

    private function updatePaymentAccept($order, $int_to) {
        echo PHP_EOL . "UPDATE PAYMENT: ". $order['orderNumber'] . PHP_EOL;
        $expedition_limit_date = null;
        $cross_docking_default = 0;
        $cross_docking = $cross_docking_default;
        $new_cross_docking = null;

        foreach($order['items'] as $item) {
            echo PHP_EOL . json_encode($item) . PHP_EOL;	
            $prf = $this->model_sc_last_post->getBySkuMktIntTo($item['sellerSku'], $int_to);

            $prd = $this->model_products->getProductData(0, $prf['prd_id']);

            // Pego a categoria para ver se existe exceção nesse item para adicionar cross docking
            $new_cross_docking = $this->getCrossDocking($prd['category_id'], $new_cross_docking);

            // Tempo de crossdocking
            if (isset($prf['crossdocking'])) {  // pega o pior tempo de crossdocking dos produtos
                if ($prf['crossdocking'] == '0' ) {
                    $prf['crossdocking'] = 1;
                }
                if (((int)$prf['crossdocking'] + $cross_docking_default) > $cross_docking) {
                    $cross_docking = $cross_docking_default + (int)$prf['crossdocking'];
                };
            }
        }
        
        if ($expedition_limit_date === null && $new_cross_docking) $cross_docking = $new_cross_docking; // irá usar o cross docking da categoria

        $this->db->trans_begin();

        $success = $this->model_orders->updatePaidStatus($this->order_db['id'], self::STATUS_SC_PAYMENT_ACCEPT);

        if ($success) {
            $result = $this->model_orders->updateByOrigin($this->order_db['id'], array('data_pago' => date("Y-m-d H:i:s"), 'data_limite_cross_docking' => $expedition_limit_date !== null ? $expedition_limit_date : $this->somar_dias_uteis(date("Y-m-d"), $cross_docking, '')));
            $success = $result !== false;
        }
        
        if (($success === false) || ($this->db->trans_status() === FALSE))
	    {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
    	return true;
    }

	function getCrossDocking($category_id, $new_cross_docking)
	{
		// Pego a categoria para ver se existe exceção nesse item para adicionar cross docking
		$category = filter_var($category_id, FILTER_SANITIZE_NUMBER_INT);
		$dataCategory = $this->model_category->getCategoryData($category);
		if ($dataCategory && $dataCategory['days_cross_docking']) {

			$limit_cross_docking_category = (int)$dataCategory['days_cross_docking'];

			if ($new_cross_docking && $limit_cross_docking_category < $new_cross_docking)
				$new_cross_docking = $limit_cross_docking_category;

			if (!$new_cross_docking)
				$new_cross_docking = $limit_cross_docking_category;
		}
		return $new_cross_docking;
	}

    private function updateCanceled($order) {

        if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($order['orderNumber'])) {
			if (!in_array($order_exist['paid_status'], [95, 96, 97, 98])) {
                $this->ordersmarketplace->cancelOrder($order_exist['id'], false, false);
                return true;
			}
		}
        else return false;

    }

    private function removeQueue($credential, $auth, $order) {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $endPoint   = '/marketplace/orders/queue/'. $order['orderNumber'];
        $this->auth_data = $credential;

        $this->process($credential, $auth, $endPoint, 'DELETE');

        if ($this->responseCode >= 300) {
            $erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para remover os pedidos da fila. Msg: " . $this->errorMensagem;
            echo $erro . "\n";
            $this->log_data('batch', $log_name, $erro, "E");
            return false;
        }
        else {
            $fim = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para remover o pedido da fila.";
            echo $fim . "\n";
            $this->log_data('batch', $log_name, $fim, "I");
            return true;
        }
        
    }

    private function cancelOrder($order) {
        return ;
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $endPoint   = '/marketplace/orders/'. $order['orderNumber'] .'/cancel';
        $this->process($this->credential, $this->auth, $endPoint, 'PUT');

        if ($this->responseCode >= 300) {
            $erro = "httpcode = " . $this->responseCode . " ao chamar endpoint " . $endPoint . " para pegar pedidos";
            echo $erro . "\n";
            $this->log_data('batch', $log_name, $erro, "E");
            return false;
        }

        return true;
    }

    private function saveOrder($order, $client, $store, $int_to) {
        $orders = Array();

        $orders['bill_no'] = $order['id'];
        $orders['numero_marketplace'] = $order['orderNumber']; // numero do pedido no marketplace 
        $orders['date_time'] = $order['creationDate'];
        $orders['customer_id'] = $client['id'];

		$order['itemsTotalValue'] = (float)$order['itemsTotalValue']-(float)$order['discountValue'];	
        $orders['total_order'] = (float)$order['itemsTotalValue'];
			
        $orders['service_charge_rate'] = $store['service_charge_value'];  
        $orders['service_charge_freight_value'] = $store['service_charge_freight_value'];  
        $orders['service_charge'] = $order['itemsTotalValue'] * $store['service_charge_value'] / 100;  
        $orders['vat_charge_rate'] = 0;
        $orders['vat_charge'] = $order['itemsTotalValue'] * $orders['vat_charge_rate'] / 100; //pegar na tabela de empresa - Não está sendo usado.....
        $orders['gross_amount'] = (float)$order['itemsTotalValue']+(float)$order['freightValue'];
        $orders['total_ship'] = $order['freightValue'];

        $orders['discount'] = 0;

        $orders['net_amount'] = $orders['gross_amount'] - $orders['discount'] - $orders['service_charge'] - $orders['vat_charge'] - $orders['total_ship'];
		$orders['data_pago'] = null;
        $orders['paid_status'] = 1; 
        $orders['company_id'] = $store['company_id'];
        $orders['store_id'] = $store['id'];
        $orders['origin'] = $int_to;
        $orders['user_id'] = 1;   // ID DO SYSTEM USER

        $orders['ship_service_preview'] = $order['shippingMethod'];

        if (!empty($order['shippingEstimateDays']) && is_numeric($order['shippingEstimateDays'])) {
            $orders['ship_time_preview'] = $order['shippingEstimateDays'];
        }

        if (isset($order['shippingAddress'])) {
            if (is_null($order['shippingAddress']['complement']) || ($order['shippingAddress']['complement']=='null')) {
                $order['shippingAddress']['complement'] = '';
            }
            $orders['customer_address'] 		= $order['shippingAddress']['street'];
            $orders['customer_name'] 			= $order['shippingAddress']['fullName'];
            $orders['customer_address_num'] 	= $order['shippingAddress']['number'];
            $orders['customer_address_compl'] 	= $order['shippingAddress']['complement'];
            $orders['customer_address_neigh'] 	= $order['shippingAddress']['neighborhood'];
            $orders['customer_address_city'] 	= $order['shippingAddress']['city'];
            $orders['customer_address_uf'] 	    = $order['shippingAddress']['region'];
            $orders['customer_phone'] 	        = $client['phone_1'];
            $orders['customer_address_zip'] 	= preg_replace("/[^0-9]/", "",$order['shippingAddress']['zipcode']);
            $orders['customer_reference'] 		= '';
        } else {
            $orders['customer_address'] 		= $client['customer_address'];
            $orders['customer_name'] 			= $client['customer_name'] ;
            $orders['customer_address_num']   	= $client['addr_num'];
            $orders['customer_address_compl'] 	= $client['addr_compl'];
            $orders['customer_address_neigh']	= $client['addr_neigh']; 
            $orders['customer_address_city'] 	= $client['addr_city'];
            $orders['customer_address_zip'] 	= preg_replace("/[^0-9]/", "", $client['zipcode']);
            $orders['customer_address_uf'] 	    = $client['addr_uf'];
            $orders['customer_phone'] 	        = $client['phone_1'];
        }

        $order_id = $this->model_orders->insertOrder($orders);
        echo "Inserido:".$order_id."\n";
        if (!$order_id) {
            $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
            $this->log_data('batch',$log_name,'Erro ao incluir pedido',"E");
            return false;
        }
        $orders['id'] = $order_id;
        return $orders;
    }

    private function saveOrderItems($order, $order_id, $int_to) {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $itensIds = [];
        foreach($order['items'] as $item) {
            echo PHP_EOL . json_encode($item) . PHP_EOL;	
            $prf = $this->model_sc_last_post->getBySkuMktIntTo($item['sellerSku'], $int_to);
            $sku = 	$prf['sku'];

            $prd = $this->model_products->getProductData(0, $prf['prd_id']);

            if ($prd['is_kit'] == 0) {
                $items = array();
                $items['order_id'] = $order_id; // ID da order incluida
                $items['skumkt'] = $item['sellerSku'];
                $items['product_id'] = $prd['id'];
                $items['sku'] = $sku;
                $variant='';
                if ($prd['has_variants'] != '') {
                    $variant = substr($item['sellerSku'],strrpos($item['sellerSku'], "V")+1);	
                    $items['sku'] = $this->getSkuVariant($prd['id'], $variant, $sku.'-'.$variant); 
                }
                $items['variant'] = $variant;
                $items['name'] = $prd['name'];
                $items['qty'] = $item['qty'];
                $items['rate'] = $item['unitPrice'];
                $items['amount'] = (float)$item['unitPrice'] * (float)$item['qty'];
                $items['discount'] = (float)$item['discount']; 
                $items['company_id'] = $prd['company_id']; 
                $items['store_id'] = $prd['store_id']; 
                $items['un'] = 'Un' ;
                $items['pesobruto'] = $prd['peso_bruto'];  // Não tem na RD
                $items['largura'] = $prd['largura']; // Não tem na RD
                $items['altura'] = $prd['altura']; // Não tem na RD
                $items['profundidade'] = $prd['profundidade']; // Não tem na RD
                $items['unmedida'] = 'cm'; // não tem na RD
                $items['kit_id'] = null;

                $item_id = $this->model_orders->insertItem($items);
		if (!$item_id) {
		    echo 'Erro ao incluir item. removendo pedido '.$order_id."\n";
                    $this->log_data('batch', $log_name, 'Erro ao incluir item. pedido mkt = '.$order['orderNumber'].' order_id ='.$order['id'].' removendo para receber novamente',"E");
                    return false; 
                }
                $itensIds[] = $item_id; 
                // Acerto o estoque do produto 
                
        		$this->model_products->reduzEstoque($prd['id'], $item['qty'], $variant, $order['id']);
                $this->model_sc_last_post->reduzEstoque($prd['id'], $item['qty'], $int_to);

                // vejo se o produto estava com promoção de estoque e vejo se devo terminar 
                $this->model_promotions->updatePromotionByStock($prd['id'], $item['qty'], $item['unitPrice']); 
                
            }
            else { // é um kit,  
                echo "O item é um KIT id=". $prd['id']."\n";
                $productsKit = $this->model_products->getProductsKit($prd['id']);
                foreach ($productsKit as $productKit){
                    $prd_kit = $this->model_products->getProductData(0,$productKit['product_id_item']);
                    echo "Produto item =".$prd_kit['id']."\n";
                    $items = array();
                    $items['order_id'] = $order_id; // ID da order incluida
                    $items['skumkt'] = $item['sellerSku'];
                    $items['kit_id'] = $productKit['product_id'];
                    $items['product_id'] = $prd_kit['id'];
                    $items['sku'] = $prd_kit['sku'];
                    $variant = '';
                    $items['variant'] = $variant;  // Kit não pega produtos com variantes
                    $items['name'] = $prd_kit['name'];
                    $items['qty'] = $item['qty'] * $productKit['qty'];
                    $items['rate'] = $productKit['price'] ;  // pego o preço do KIT em vez do item
                    $items['amount'] = (float)$items['rate'] * (float)$items['qty'];
                    $items['discount'] = 0; // Não sei de quem tirar se houver desconto. 
                    $items['company_id'] = $prd_kit['company_id']; 
                    $items['store_id'] = $prd_kit['store_id']; 
                    $items['un'] = 'Un' ; // Não tem na SkyHub
                    $items['pesobruto'] = $prd_kit['peso_bruto'];  // Não tem na SkyHub
                    $items['largura'] = $prd_kit['largura']; // Não tem na SkyHub
                    $items['altura'] = $prd_kit['altura']; // Não tem na SkyHub
                    $items['profundidade'] = $prd_kit['profundidade']; // Não tem na SkyHub
                    $items['unmedida'] = 'cm'; // não tem na skyhub
                    //var_dump($items);
		    echo 'Insert Item ' . json_encode($items) . PHP_EOL . PHP_EOL;
		    $item_id = $this->model_orders->insertItem($items);
                    if (!$item_id) {
                        echo 'Erro ao incluir item. removendo pedido '.$order_id."\n";
                        // $this->model_orders->remove($order_id);
                        // $this->model_clients->remove($client_id);
                        $this->log_data('batch', $log_name, 'Erro ao incluir item. pedido mkt = '.$order['orderNumber'].' order_id ='.$order['id'].' removendo para receber novamente',"E");
                        return false; 
                    }
                    $itensIds[]= $item_id; 
                    // Acerto o estoque do produto filho
                    echo "Reduzir estoque produto ". $prd_kit['id'] . ' - ' . $order['id'] . PHP_EOL;
                    $this->model_products->reduzEstoque($prd_kit['id'], $items['qty'], $variant, $order['id']);
                    
                }
                echo "Reduzir estoque last post ". $prd_kit['id'] . ' - ' . $order['id'] . PHP_EOL;
                $this->model_sc_last_post->reduzEstoque($prd['id'], $item['qty'], $int_to);

                
            }
        }

        return $itensIds;
    }

    private function saveClient($order, $int_to) {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $clients = array();
        $clients['customer_name']       = $order['customer']['name'];	
        $clients['customer_address']    = $order['billingAddress']['street'];
        $clients['addr_num']            = $order['billingAddress']['number'];

        $clients['addr_compl']          = $order['billingAddress']['complement'];
        if ((is_null($clients['addr_compl'])) || $clients['addr_compl'] ='NULL'){
            $clients['addr_compl'] ='';
        }
        
        $clients['addr_neigh']          = $order['billingAddress']['neighborhood'];
        $clients['addr_city']           = $order['billingAddress']['city'];
        $clients['addr_uf']             = $order['billingAddress']['region'];
        $clients['country']             = $order['billingAddress']['country'];
        $clients['zipcode']             = $order['billingAddress']['zipcode'];
        $clients['phone_1']             = $order['customer']['phone'] ?: '';
        $clients['phone_2']             = '';
        $clients['origin']              = $int_to;
        $clients['origin_id']           = $order['customer']['id'];
        $clients['email']               = $order['customer']['email'] ?: '';
        
        $clients['cpf_cnpj']            = $order['customer']['documentNumber'];  // nao tem esta informação no skyhub
        $clients['ie']                  = '';
        $clients['rg']                  = '';

	if (is_null($clients['cpf_cnpj'])) {
		echo 'Cliente sem cpf ou cnpj não será importado' . PHP_EOL;
		$this->log_data('batch',$log_name,'Erro ao incluir cliente (sem cpf ou cnpj)',"E");
		return false;
	}

        $client_id = $this->model_clients->insert($clients);
        if ($client_id == false) {
            echo 'Não consegui incluir o cliente'."\n";
            $this->log_data('batch',$log_name,'Erro ao incluir cliente',"E");
            return false;
        }
        $clients['id'] = $client_id;

        return $clients;
    }

    function getSkuVariant($prd_id, $variant, $sku) {
		$var = $this->model_products->getVariants($prd_id, $variant);
		if ($var) {
			if ($var['sku'] != '')	{
				return $var['sku'];
			}
		}
		return $sku;
	}
}