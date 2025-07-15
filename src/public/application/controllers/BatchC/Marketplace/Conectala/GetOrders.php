<?php
/*
 
Atualiza pedidos que chegaram na Novo Mundo SC

*/   

// require 'Marketplace/Conectala/Integration.php';
require APPPATH . "controllers/BatchC/Marketplace/Conectala/Integration.php";

/**
 * @property CI_Loader $load
 * @property CI_Router $router
 *
 * @property Model_orders $model_orders
 * @property Model_products $model_products
 * @property Model_stores $model_stores
 * @property Model_clients $model_clients
 * @property Model_integrations $model_integrations
 * @property Model_integration_last_post $model_integration_last_post
 * @property Model_sellercenter_last_post $model_sellercenter_last_post
 * @property Model_promotions $model_promotions
 * @property Model_category $model_category
 * @property Model_freights $model_freights
 * @property Model_log_integration_order_marketplace $model_log_integration_order_marketplace
 * @property Model_orders_payment $model_orders_payment
 * @property Model_orders_item $model_orders_item
 * @property Model_settings $model_settings
 * @property Model_quotes_ship $model_quotes_ship
 * @property Model_log_quotes $model_log_quotes
 * @property OrdersMarketplace $ordersmarketplace
 * @property CalculoFrete $calculofrete
 */

class GetOrders extends BatchBackground_Controller 
{
	public $int_to 					= '';
	public $int_to_id				= '1';
	public $url_api 				= null;
	public $api_keys 				= '';
	protected $integration 			= null;
	protected $integration_data 	= null;
	protected $cant_cancel_status 	= array(5,6,45,55,60,96,97,98,99);
    private $enable_multiseller_operation = false;
    /**
     * Cache de cotações multiseller
     * @var array
     */
    private array $multiseller_quotes_cache = [];

    /**
     * Dados de validação da cotação
     * @var array|null
     */
    private ?array $quote_validation_data = null;

    /**
     * Contador de pedidos quebrados
     * @var int
     */
    private int $broken_order_counter = 0;
	public function __construct()
	{
		parent::__construct();
			
		// Carrega os módulos necessários para o Job.
		$this->load->model('model_orders');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_clients');
		$this->load->model('model_integrations');
		$this->load->model('model_integration_last_post');
		$this->load->model('model_sellercenter_last_post');
		$this->load->model('model_promotions');
		$this->load->model('model_category');
		$this->load->model('model_freights');
        $this->load->model('model_log_integration_order_marketplace');
        $this->load->model('model_orders_payment');
        $this->load->model('model_orders_item');
        $this->load->model('model_settings');
        $this->load->model('model_quotes_ship');
        $this->load->model('model_log_quotes');
                $this->load->library('ordersMarketplace');
        $this->load->library('calculoFrete');

		$this->integration = new Integration();
	}

	// php index.php BatchC/Marketplace/Conectala/GetOrders run null NM
    public function run($id = null, $params = null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =__CLASS__.'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return ;
		}

		$this->log_data('batch', $log_name, 'start '.trim($id." ".$params));
		
		/* faz o que o job precisa fazer */
		$this->int_to = $params;
		if ($this->getKeys(1, 0)) {
            if ($this->model_settings->getValueIfAtiveByName('enable_multiseller_operation')) {
                $setting_marketplace_multiseller_operation = $this->model_settings->getValueIfAtiveByName('marketplace_multiseller_operation');
                if ($setting_marketplace_multiseller_operation) {
                    $marketplace_multiseller_operation = explode(',', $setting_marketplace_multiseller_operation);
                    if (in_array($this->int_to, $marketplace_multiseller_operation)) {
                        $this->enable_multiseller_operation = true;
                    }
                }
            }

			$this->getOrders();
			$this->syncCancelled();
		}
		   
		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish');
		$this->gravaFimJob();
	}

    public function getInt_to(): string
    {
		return $this->int_to;
	}

    public function getDays($date_start = false, $date_end = false)
    {
        if (!$date_start || !$date_end) {
            return false;
        }

        try {
            $date_today         = new DateTime($date_start);
            $date_estimated     = new DateTime($date_end);
            $date_interval      = $date_today->diff($date_estimated);
        } catch (Exception $exception) {
            return false;
        }
        
        return $date_interval->days;
    }

	

	public function getKeys($company_id, $store_id): bool
    {
        $log_name = __CLASS__.'/'.__FUNCTION__;

		// Pega os dados da integração. Por enquanto só a Conecta Lá só efetua integração direta.
		$this->integration_data = $this->model_integrations->getIntegrationsbyCompIntType($company_id, $this->int_to, "CONECTALA", "DIRECT", $store_id);

		if (!is_array($this->integration_data) || empty($this->integration_data)) {
            $message = "Não foi possível recuperar os dados de integração";
            echo "$message\n";
            $this->log_data('batch', $log_name, $message, "E");
            return false;
        }

        $this->api_keys = @json_decode($this->integration_data['auth_data'], true);
		
        if (is_array($this->api_keys)) {
			$this->integration->setUrlApi($this->api_keys['api_url']);
			return true;
		}
        else {
            return false;
        }
	}

    /**
     * Valida os itens do pedido e retornar o código da loja.
     *
     * @param   array   $items              Itens do pedido.
     * @param   string  $orderMarketplace   Código do marketplace.
     * @return  int
     * @throws  Exception
     */
    private function validarItensPedido(array $items, string $orderMarketplace): int
    {
        $log_name   = __CLASS__.'/'.__FUNCTION__;
        $store_id   = null;

        foreach ($items as $item) {
            try {
                $integration_last_post = $this->validarItemPedido($item, $orderMarketplace);
            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }

            if ($store_id == null) {
                $store_id = $integration_last_post['store_id'];
            }

            if ($store_id != $integration_last_post['store_id'] && !$this->enable_multiseller_operation) {
                $message = "Pedido $orderMarketplace com produtos de lojas diferente lojas=($store_id e {$integration_last_post['store_id']}). Cancelar Pedido.";
                echo "$message\n";
                $this->log_data('batch ',$log_name, $message, "E");

                throw new Exception($message);
            }
        }

        if ($store_id === null) {
            throw new Exception("Não foi localizado o código loja do pedido $orderMarketplace");
        }

        return $store_id;
    }

    /**
     * Realiza a validação dos itens do pedido.
     *
     * @param   array       $item               Itens do pedido.
     * @param   string      $orderMarketplace   Código do marketplace.
     * @return  array
     * @throws  Exception
     */
    private function validarItemPedido(array $item, string $orderMarketplace): array
    {
        $log_name   = __CLASS__.'/'.__FUNCTION__;
        $sku        = $item['sku_variation'] ?? $item['sku'];

        $integration_last_post = $this->model_sellercenter_last_post->getDataBySkulocalAndIntto($sku, $this->int_to);
		
		if (is_null($integration_last_post)) {
			$integration_last_post = $this->model_integration_last_post->getDataBySkulocalAndIntto($sku, $this->int_to);
		}
		
        if (is_null($integration_last_post)) {
            $message = "Produto=$sku não localizado para o pedido=$orderMarketplace";
            echo "$message\n";
            $this->log_data('batch ',$log_name, $message, "E");
            throw new Exception($message);
        }

        return $integration_last_post;
    }

    /**
     * Cancela o pedido no marketplace.
     *
     * @param   string      $bill_no        Código do marketplace.
     * @param   string      $cancelMessage  Motivo do cancelamento.
     * @return  void
     * @throws  Exception
     */
    private function cancelarPedido(string $bill_no, string $cancelMessage)
    {
        $log_name = __CLASS__.'/'.__FUNCTION__;

        $cancel = $this->integration->cancelSCOrder($this->api_keys, $bill_no, $cancelMessage);


        $response = json_decode($cancel['content'], true);

        if ($cancel['http_code'] != 200) {
            $message = "Pedido: $bill_no não conseguiu ser cancelado no Seller Center";
            if (isset($response['message'])) {
                $message .= "\n{$response['message']}";
            }
            echo "$message\n";
            $this->log_data('batch ',$log_name, $message, "E");
            throw new Exception($message);
        }
    }

	public function syncCancelled()
	{
		$log_name =__CLASS__.'/'.__FUNCTION__;

		echo "Verificando os pedidos cancelados\n";

		$response = $this->integration->getOrdersCanceled($this->api_keys);

		if ($response['httpcode'] == 404) {
			echo " Lista acabou \n";
			return;
		}

		if ($response['httpcode'] != 200) {
			$message = "Erro httpcode={$response['httpcode']}\nresponse={$response['content']}\nmkt=$this->int_to\n";
            echo $message;
			$this->log_data('batch',$log_name, $message,"E");
			return;
		}

		$response = json_decode($response['content'], true);

		if ($response['success']) {
			foreach ($response["result"] as $order) {
                $can_remove_queue = false;
                $existing_orders = $this->model_orders->getOrdersByOriginAndBillNo($this->int_to, $order['order_code']);

                if (empty($existing_orders)) {
                    echo "Order {$order['order_code']} não localizada... \n";
                    continue;
                }

                foreach ($existing_orders as $order_exist) {
                    if ($order_exist) {
                        echo 'Cancelando pedido ' . $order_exist['id'] . ' bill no. ' . $order['order_code'] . ' status ' . $order['status']['code'] . "\n";
                        $this->ordersmarketplace->cancelOrder($order_exist['id'], false);
                        $can_remove_queue = true;
                    }
                }

                if ($can_remove_queue) {
                    $remove_from_line = $this->integration->removeFromLine($order['order_code'], $this->api_keys);

                    if ($remove_from_line['http_code'] != 200) {
                        $message = " Erro REMOÇÃO DA FILA " . $remove_from_line['httpcode'] . "\nRESPOSTA: " . print_r($remove_from_line, true);
                        echo "$message\n";
                        $this->log_data('batch', $log_name, $message, "E");
                    } else {
                        echo "Pedido " . $order['order_code'] . " removido com sucesso. \n";
                    }
                }
			}
		}
	}

    public function getOrders(): bool
    {
		$log_name =__CLASS__.'/'.__FUNCTION__;

        $orders_line = array();
		
        //confere a fila         
		$orderQueue = $this->integration->getNewOrders($this->api_keys);

        if ($orderQueue['http_code'] == 200) {
			$line_array = json_decode($orderQueue['content'], true);

			if ($line_array['success'] == true && is_array($line_array['result']) && !empty($line_array['result'])) {
				foreach ($line_array['result'] as $k => $v) {
                    $orders_line[$k]['order_code'] 	= $v['order_code'];
                    $orders_line[$k]['paid_status'] = $v['status']['code'];
                    $orders_line[$k]['updated_at'] 	= $v['updated_at'];
                    $orders_line[$k]['new_order'] 	= $v['new_order'];
				}
			} else {
                $message = "Fila de Pedidos retornou: quantidade: ".count($line_array['result'])." | resposta API: ".$line_array['success'];
                echo "$message\n";
                $this->log_data('batch', $log_name, $message, "E");
                return false;
            }
		} else {
			if ($orderQueue['http_code'] == 404) {
				return false;
			}

            $message = "Erro ao conectar com a API de pedidos, array serializado: ".serialize($orderQueue);
            echo "$message\n";
            $this->log_data('batch', $log_name, $message, "E");
			return false;
		}

		if (!empty($orders_line)) {
			//inicio do fluxo completo
			foreach($orders_line as $line_item) {
				try {
                    $this->processOrder($line_item);
                } catch (Exception $exception) {
                    $this->log_data('batch', $log_name, $exception->getMessage(), "E");
                    continue;
                }

                $removeFromQueue = $this->integration->removeFromLine($line_item['order_code'], $this->api_keys);

                if ($removeFromQueue['http_code'] != 200) {
                    $message = "Erro REMOÇÃO DA FILA {$removeFromQueue['httpcode']}\nRESPOSTA: ".print_r($removeFromQueue, true);
                    echo "$message\n";
                    $this->log_data('batch', $log_name, $message, "E");
                } else {
                    echo "Pedido {$line_item['order_code']} removido com sucesso.\n";
                }
			}
		} else {
			return false;
        }

        return true;
	}
    /**
     * Processa o pedido para inclusão ou cancelamento, caso precise.
     * Inclui lógica de quebra para pedidos multiseller.
     *
     * @param   array       $line_item Dados do pedido na fila.
     * @return  void
     * @throws  Exception
     */
    private function processOrder(array $line_item): void
    {
        $log_name = __CLASS__.'/'.__FUNCTION__;
        
        // Pego os dados do produto para adição nas tabelas
        $order = $this->integration->getOrderItem($this->api_keys, $line_item['order_code']);

        if ($order['http_code'] == 200) {
            $content = json_decode($order['content'], true);

            if (!$content['success']) {
                throw new Exception($content['message'] ?? "Não foi possível consultar o pedido {$line_item['order_code']}");
            }

            $content = $content['result']['order'];
            $status = (int)$line_item['paid_status'];

            if ($line_item['new_order']) {
                if ($content['items'] && is_array($content['items'])) {
                    try {
                        $storeId = $this->validarItensPedido($content['items'], $content['marketplace_number']);
                    } catch (Exception $exception) {
                        if (in_array($content['status']['code'], array(95,96,97,98,99))) {
                            return;
                        }

                        try {
                            $this->cancelarPedido($line_item['order_code'], $exception->getMessage());
                        } catch (Exception $exception) {
                            throw new Exception($exception->getMessage());
                        }

                        return;
                    }

                    if (empty($this->model_orders->getOrdersDatabyBill($this->int_to, $line_item['order_code']))) {
                        try {
                            // ✅ NOVA LÓGICA MULTISELLER AQUI:
                            $this->processNewOrderWithMultisellerSupport($content);
                        } catch (Exception $exception) {
                            throw new Exception($exception->getMessage());
                        }
                        return;
                    }
                }
            }

            // Loop nos itens para ver se houve split ou nao existe na tabela
            if ($status == 3) {
                $parcels = $content['payments']['parcels'];
                $parcel_number = 1;
                $existing_orders = $this->model_orders->getOrdersByOriginAndBillNo($this->int_to, $line_item['order_code']);

                if (empty($existing_orders)) {
                    throw new Exception("Pedido ( {$line_item['order_code']} ) não existente. Pode ter sido cancelado antes de integrar.");
                }

                foreach ($existing_orders as $existing_order) {
                    if (is_array($parcels) && !empty($parcels)) {
                        // Já que temos parcelas, removerei o que já foi registrado previamente no primeiro cadastro.
                        $this->model_orders_payment->remove($existing_order['id']);

                        foreach ($parcels as $parcel) {
                            $parcel_data = array(
                                'order_id' 	    => $existing_order['id'],
                                'parcela' 	    => $parcel_number++,
                                'bill_no' 	    => $existing_order['bill_no'],
                                'data_vencto'   => date('Y-m-d', strtotime($parcel['due_date'])),
                                'valor' 		=> $parcel['value'],
                                'forma_id'	    => $parcel['payment_type'],
                                'forma_desc' 	=> $parcel['payment_method'],
                            );

                            $parcel_id = $this->model_orders->insertParcels($parcel_data);

                            if (!$parcel_id) {
                                $this->log_data('batch', $log_name, "Erro ao incluir parcelas no pedido {$existing_order['id']}", "E");
                                throw new Exception("Erro ao incluir parcelas no pedido {$existing_order['id']}");
                            }
                        }
                    }

                    // Só atualiza o status para 3 se estiver no 1.
                    if ($existing_order['paid_status'] == 1) {

                        if (empty($existing_order['data_limite_cross_docking'])) {

                            $data_cross_docking = array(
                                'data_limite_cross_docking' => $content['shipping']['cross_docking_deadline']
                            );

                            $this->model_orders->updateOrderById($existing_order['id'], $data_cross_docking);
                        }

                        if (!$this->model_orders->updatePaidStatus($existing_order['id'], $status)) {
                            echo "Pedido {$content['marketplace_number']} não sofreu atualização para faturamento\n";
                            throw new Exception("Pedido {$existing_order['id']} não sofreu atualização para faturamento\n");
                        } else {
                            echo "Pedido {$content['marketplace_number']} marcado para faturamento\n";
                        }
                    }
                }

                return;
            }

            throw new Exception("Status $status do pedido {$content['marketplace_number']} não mapeado\n");
        }

        throw new Exception($content['message'] ?? "Não foi possível consultar o pedido {$line_item['order_code']}");
    }
    /**
     * Processa pedido com suporte multiseller
     * @param array $content
     * @return void
     * @throws Exception
     */
    private function processNewOrderWithMultisellerSupport(array $content): void
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        // Verificar se feature flag está habilitada feature-OEP-1921-order-breaking
        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-muiltiseller-freight-results')) {
            // Feature flag desabilitada - usar lógica original
            $this->newOrder($content);
            return;
        }
        
        try {
            // Verificar se é pedido multiseller
            if ($this->isMultisellerOrder($content['items'])) {
                $this->log_data('batch', $log_name, "Pedido multiseller detectado: " . $content['marketplace_number'], "I");
                
                // Quebrar pedido em múltiplos pedidos
                $brokenOrders = $this->createBrokenOrdersWithUniqueIds($content);
                
                $this->log_data('batch', $log_name, "Pedido " . $content['marketplace_number'] . " quebrado em " . count($brokenOrders) . " pedidos", "I");
                
                // Processar cada pedido quebrado
                foreach ($brokenOrders as $brokenOrder) {
                    $this->newOrder($brokenOrder);
                    $this->log_data('batch', $log_name, "Pedido quebrado criado: " . $brokenOrder['marketplace_number'], "I");
                }
            } else {
                // Pedido normal - usar lógica original
                $this->newOrder($content);
            }
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, "Erro ao processar pedido multiseller " . $content['marketplace_number'] . ": " . $e->getMessage(), "E");
            
            // Fallback: tentar criar pedido normal
            try {
                $this->log_data('batch', $log_name, "Tentando fallback para pedido normal: " . $content['marketplace_number'], "W");
                $this->newOrder($content);
            } catch (Exception $fallbackException) {
                throw new Exception("Erro no processamento multiseller e fallback: " . $fallbackException->getMessage());
            }
        }
    }
    // /**
    //  * Processa o pedido para inclusão ou cancelamento, caso precise.
    //  *
    //  * @param   array       $line_item Dados do pedido na fila.
    //  * @return  void
    //  * @throws  Exception
    //  */
    // private function processOrder(array $line_item): void
    // {
    //     $log_name =__CLASS__.'/'.__FUNCTION__;
    //     //pego os dados do produto para adição nas tabelas
    //     $order = $this->integration->getOrderItem($this->api_keys, $line_item['order_code']);

    //     if ($order['http_code'] == 200) {
    //         $content = json_decode($order['content'], true);

    //         if (!$content['success']) {
    //             throw new Exception($content['message'] ?? "Não foi possível consultar o pedido {$line_item['order_code']}");
    //         }

    //         $content    = $content['result']['order'];
    //         $status     = (int)$line_item['paid_status'];

    //         if ($line_item['new_order']) {
    //             if ($content['items'] && is_array($content['items'])) {
    //                 try {
    //                     $storeId = $this->validarItensPedido($content['items'], $content['marketplace_number']);
    //                 } catch (Exception $exception) {
    //                     if (in_array($content['status']['code'], array(95,96,97,98,99))) {
    //                         return;
    //                     }

    //                     try {
    //                         $this->cancelarPedido($line_item['order_code'], $exception->getMessage());
    //                     } catch (Exception $exception) {
    //                         throw new Exception($exception->getMessage());
    //                     }

    //                     return;
    //                 }

    //                 if (empty($this->model_orders->getOrdersDatabyBill($this->int_to, $line_item['order_code']))) {
    //                     try {
    //                         $this->newOrder($content);
    //                     } catch (Exception $exception) {
    //                         throw new Exception($exception->getMessage());
    //                     }
    //                     return;
    //                 }
    //             }
    //         }

    //         // Loop nos itens para ver se houve split ou nao existe na tabela
    //         if ($status == 3) {
    //             $parcels        = $content['payments']['parcels'];
    //             $parcel_number  = 1;
    //             $existing_orders = $this->model_orders->getOrdersByOriginAndBillNo($this->int_to, $line_item['order_code']);

    //             if (empty($existing_orders)) {
    //                 throw new Exception("Pedido ( {$line_item['order_code']} ) não existente. Pode ter sido cancelado antes de integrar.");
    //             }

    //             foreach ($existing_orders as $existing_order) {
    //                 if (is_array($parcels) && !empty($parcels)) {
    //                     // Já que temos parcelas, removerei o que já foi registrado previamente no primeiro cadastro.
    //                     $this->model_orders_payment->remove($existing_order['id']);

    //                     foreach ($parcels as $parcel) {
    //                         $parcel_data = array(
    //                             'order_id' 	    => $existing_order['id'],
    //                             'parcela' 	    => $parcel_number++,
    //                             'bill_no' 	    => $existing_order['bill_no'],
    //                             'data_vencto'   => date('Y-m-d', strtotime($parcel['due_date'])),
    //                             'valor' 		=> $parcel['value'],
    //                             'forma_id'	    => $parcel['payment_type'],
    //                             'forma_desc' 	=> $parcel['payment_method'],
    //                         );

    //                         $parcel_id = $this->model_orders->insertParcels($parcel_data);

    //                         if (!$parcel_id) {
    //                             $this->log_data('batch', $log_name, "Erro ao incluir parcelas no pedido {$existing_order['id']}","E");
    //                             throw new Exception("Erro ao incluir parcelas no pedido {$existing_order['id']}");
    //                         }
    //                     }
    //                 }

    //                 // Só atualiza o status para 3 se estiver no 1.
    //                 if ($existing_order['paid_status'] == 1) {

    //                     if(empty($existing_order['data_limite_cross_docking'])){

    //                         $data_cross_docking = array(
    //                             'data_limite_cross_docking' => $content['shipping']['cross_docking_deadline']
    //                         );

    //                         $this->model_orders->updateOrderById($existing_order['id'], $data_cross_docking);
    //                     }

    //                     if (!$this->model_orders->updatePaidStatus($existing_order['id'], $status)) {
    //                         echo "Pedido {$content['marketplace_number']} não sofreu atualização para faturamento\n";
    //                         throw new Exception("Pedido {$existing_order['id']} não sofreu atualização para faturamento\n");
    //                     } else {
    //                         echo "Pedido {$content['marketplace_number']} marcado para faturamento\n";
    //                     }
    //                 }
    //             }

    //             return;
    //         }

    //         throw new Exception("Status $status do pedido {$content['marketplace_number']} não mapeado\n");
    //     }

    //     throw new Exception($content['message'] ?? "Não foi possível consultar o pedido {$line_item['order_code']}");
    // }

    /**
     * Criar pedido.
     * 
     * @param   array   $content    Dados do pedido.
     * @return  void
     * @throws  Exception
     */
    protected function newOrder(array $content): void
    {
        $log_name                       = __CLASS__.'/'.__FUNCTION__;
        $datas_created                  = array();
        $all_items_to_create            = $this->getDataItemsToCreate($content);
        $discounts                      = $all_items_to_create['discount'];
        $total_orders                   = $all_items_to_create['total_order'];
        $items                          = $this->getItemsByStore($all_items_to_create['items']);
        $stores                         = array_keys($items);
        $total_ship_order               = 0;
        $enable_multiseller_operation   = $this->enable_multiseller_operation;

        foreach ($stores as $key => $store_id) {
            $total_order    = $content['payments']['total_products'];
            $discount       = $content['payments']['discount'];
            $total_ship     = roundDecimal($content['shipping']['seller_shipping_cost'] / count($stores));

            if (count($stores) == ($key + 1)) {
                $total_ship = $content['shipping']['seller_shipping_cost'] - $total_ship_order;
            } else {
                $total_ship_order += $total_ship;
            }

            $ship_company_preview   = $content['shipping']['shipping_carrier'];
            $ship_service_preview   = $content['shipping']['service_method'];
            $ship_time_preview      = $content['shipping']['estimated_delivery_days'];
            $zipcode                = $content['shipping']['shipping_address']['postcode'];
            if ($enable_multiseller_operation && count($stores) > 1) {
                $quote_service_to_create = $this->calculofrete->getShipCompanyPreviewToCreateOrder($store_id, $this->int_to, $items[$store_id], $zipcode, $ship_service_preview);
                if ($quote_service_to_create) {
                    $total_ship = $quote_service_to_create['value'];
                    $ship_company_preview = $quote_service_to_create['provider'];
                    $ship_service_preview = $quote_service_to_create['method'];
                    $ship_time_preview = $quote_service_to_create['deadline'];
                }
                $discount    = $discounts[$store_id];
                $total_order = $total_orders[$store_id];
            }

            $ship_company_preview   = in_array(strtoupper($ship_company_preview), array('PAC', 'SEDEX', 'MINI')) ? 'CORREIOS' : 'Transportadora';
            $ship_company_preview   = preg_match('/' . str_replace('%', '.*?', '%correios%') . '/', strtolower($ship_company_preview)) > 0 ||
            preg_match('/' . str_replace('%', '.*?', '%pac_%') . '/', strtolower($ship_company_preview)) > 0 ||
            preg_match('/' . str_replace('%', '.*?', '%sedex_%') . '/', strtolower($ship_company_preview)) > 0 ||
            preg_match('/' . str_replace('%', '.*?', '%mini_%') . '/', strtolower($ship_company_preview)) > 0
                ? 'CORREIOS' : $ship_company_preview;

            $store = $this->model_stores->getStoresData($store_id);
            //registrando o cliente para resgatar o ID
            $customer = array(
                'customer_name' 	=> $content['customer']['name'],
                'customer_address'  => $content['billing_address']['street'],
                'addr_num' 			=> $content['billing_address']['number'],
                'addr_compl' 		=> $content['billing_address']['complement'],
                'addr_neigh'		=> $content['billing_address']['neighborhood'],
                'addr_city' 		=> $content['billing_address']['city'],
                'addr_uf' 			=> $content['billing_address']['region'],
                'country' 			=> $content['billing_address']['country'],
                'zipcode' 			=> $content['billing_address']['postcode'],
                'origin' 			=> $this->int_to,
                'origin_id' 		=> $this->int_to_id,
                'phone_1' 			=> $content['customer']['phones'][0],
                'phone_2' 			=> $content['customer']['phones'][1],
                'email' 			=> $this->getEmailClient($content['customer']['email'], $content['marketplace_number'], $this->int_to),
                'cpf_cnpj' 			=> $content['customer']['cpf'] ?? $content['customer']['cnpj'],
                'ie' 				=> $content['customer']['ie'],
                'rg' 				=> $content['customer']['rg']
            );

            if (!$customer_id = $this->model_clients->insert($customer)) {
                //nao foi possivel cadastrar o cliente, entao nao adianta continuar com o pedido
                $message = "Pedido:  {$content['marketplace_number']} | SC: $this->int_to | Store=$store_id nao foi possível cadastrar o cliente. Abortando o registro";
                echo "$message\n";
                $this->log_data('batch ',$log_name, $message, "E");
                throw new Exception($message);
            }

            $datas_created[$store_id]['customer_id'] = $customer_id;

            $serviceCharge = number_format(($total_order * $store['service_charge_value'] / 100) + ($total_ship * $store['service_charge_freight_value'] / 100), 2, '.', '');

            $order_data = array(
                'bill_no' 				        => $content['code'],
                'numero_marketplace'       	    => $content['marketplace_number'],
                'service_charge' 		        => $serviceCharge,
                'customer_id' 			        => $customer_id,
                'customer_phone' 		        => $content['shipping']['shipping_address']['phone'],
                'date_time' 			        => $content['created_at'],
                'total_order' 		        	=> $total_order,
                'discount' 		        	    => $discount,
                'net_amount' 		        	=> $total_order + $total_ship - $serviceCharge, // total produto + frete + comissão
                'total_ship' 		        	=> $total_ship,
                'gross_amount' 		            => $total_order + $total_ship, // total produto + frete
                'service_charge_rate'           => $store['service_charge_value'],
                'vat_charge_rate' 		        => 0,
                'vat_charge' 		        	=> 0,
                'paid_status' 		        	=> 1,
                'user_id' 			        	=> 1,
                'company_id' 		        	=> $store['company_id'],
                'origin' 			        	=> $this->int_to,
                'store_id' 		        	    => $store['id'],
                'customer_name' 		        => $content['shipping']['shipping_address']['full_name'],
                'customer_address' 	            => $content['shipping']['shipping_address']['street'],
                'customer_address_num'     	    => $content['shipping']['shipping_address']['number'],
                'customer_address_compl'   	    => $content['shipping']['shipping_address']['complement'],
                'customer_reference'   	        => $content['shipping']['shipping_address']['reference'],
                'customer_address_neigh'	    => $content['shipping']['shipping_address']['neighborhood'],
                'customer_address_city'	        => $content['shipping']['shipping_address']['city'],
                'customer_address_uf'	        => $content['shipping']['shipping_address']['region'],
                'customer_address_zip'	        => $zipcode,
                'service_charge_freight_value'  => $store['service_charge_freight_value'],
                'ship_company_preview'          => $ship_company_preview,
                'ship_service_preview'          => $ship_service_preview,
                'ship_time_preview'             => $ship_time_preview,
                'data_limite_cross_docking'     => null,
                'order_mkt_multiseller'         => $enable_multiseller_operation ? $content['marketplace_number'] : null
            );

            //finalmente gravo o pedido na tabela order
            $order_id = $this->model_orders->insertOrder($order_data);
            echo "Inserido:$order_id\n";
            $datas_created[$store_id]['order_id'] = $order_id;

            if (!$order_id) {
                $this->log_data('batch',$log_name,'Erro ao incluir pedido',"E");
                $this->model_clients->remove($customer_id);
                throw new Exception("Não foi possível incluir o pedido {$content['marketplace_number']}");
            }

            //cadastro as parcelas
            $parcels = $content['payments']['parcels'];
            $counter = 1; //primeira parcela

            if (is_array($parcels) && !empty($parcels)) {
                foreach ($parcels as $parcel) {
                    $parcel_data = [];
                    $parcel_data['order_id'] 	= $order_id;
                    $parcel_data['parcela'] 	= $counter++;
                    $parcel_data['bill_no'] 	= $order_data['bill_no'];
                    $parcel_data['data_vencto'] = date('Y-m-d', strtotime($parcel['due_date']));
                    $parcel_data['valor']       = $parcel['value'];
                    $parcel_data['forma_id']	= $parcel['payment_type'];
                    $parcel_data['forma_desc']  = $parcel['payment_method'];

                    $parcs_id = $this->model_orders->insertParcels($parcel_data);

                    if (!$parcs_id) {
                        $message = "Erro ao incluir parcelas do pedido $order_id ".json_encode($parcs_id);
                        echo "$message\n";
                        $this->log_data('batch', $log_name, $message, "E");
                    }
                }
            }

            foreach ($items[$store_id] as $item) {
                $item['order_id'] = $order_id;

                // Gravando o log do pedido.
                $data_log = array(
                    'int_to' => $this->int_to,
                    'order_id' => $order_id,
                    'received' => json_encode($content)
                );
                $this->model_log_integration_order_marketplace->create($data_log);

                $this->model_products->reduzEstoque($item['product_id'], $item['qty'], $item['variant'], $order_id);
                $this->model_sellercenter_last_post->reduzEstoque($this->int_to, $item['product_id'], $item['qty']);
                $this->model_integration_last_post->reduzEstoque($this->int_to, $item['product_id'], $item['qty']);
                $this->model_promotions->updatePromotionByStock($item['product_id'], $item['qty'], $item['rate']);

                $item_id = $this->model_orders->insertItem($item);

                if (!$item_id) {
                    foreach ($datas_created as $data_created) {
                        if (!empty($data_created['order_id'])) {
                            $this->model_orders->remove($data_created['order_id']);
                        }
                        if (!empty($data_created['customer_id'])) {
                            $this->model_clients->remove($data_created['customer_id']);
                        }
                        if (!empty($data_created['order_id'])) {
                            $this->model_orders_payment->remove($data_created['order_id']);
                        }
                        if (!empty($data_created['order_id'])) {
                            $this->model_orders_item->remove($data_created['order_id']);
                        }
                    }
                    $message = "SKU=$item[skumkt] do MKT=$this->int_to não foi possível incluir o item no pedido={$content['marketplace_number']}";
                    echo "$message\n";
                    $this->log_data('batch', $log_name, $message, "E");

                    throw new Exception($message);
                }
            }

            $this->calculofrete->updateShipCompanyPreview($order_id);
        }
    }
    /**
     * Processa faturamento parcial de pedido multiseller
     * @param string $billNo
     * @param array $items
     * @return array
     */
    public function processPartialInvoicing(string $billNo, array $items = []): array
    {
        // Verificar se a feature flag está habilitada feature-OEP-2009-partial-invoicing
        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-muiltiseller-freight-results')) {
            return ['success' => false, 'message' => 'Feature não habilitada'];
        }
        
        $log_name = __CLASS__.'/'.__FUNCTION__;
        
        try {
            // Buscar pedido
            $order = $this->model_orders->getOrdersDatabyBill($this->int_to, $billNo);
            
            if (!$order) {
                return ['success' => false, 'message' => 'Pedido não encontrado'];
            }
            
            // Verificar se é pedido multiseller
            if (empty($order['order_mkt_multiseller'])) {
                return ['success' => false, 'message' => 'Pedido não é multiseller'];
            }
            
            // Validar itens
            if (!empty($items)) {
                $validationResult = $this->validateItemsForPartialOperation($order['id'], $items);
                if (!$validationResult['valid']) {
                    return ['success' => false, 'message' => $validationResult['message']];
                }
            }
            
            // Processar faturamento
            $invoiceData = $this->generateInvoiceData($order, $items);
            $invoiceId = $this->model_orders->createInvoice($invoiceData);
            
            if (!$invoiceId) {
                return ['success' => false, 'message' => 'Erro ao gerar nota fiscal'];
            }
            
            // Atualizar status do pedido
            $this->model_orders->updateOrderStatus($order['id'], 5); // Status: Faturado
            
            // Notificar marketplace
            $this->notifyMarketplaceInvoicing($order, $invoiceData);
            
            $this->log_data('batch', $log_name, "Faturamento parcial processado: $billNo", "I");
            
            return [
                'success' => true,
                'invoice_id' => $invoiceId,
                'message' => 'Faturamento processado com sucesso'
            ];
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, "Erro no faturamento parcial $billNo: " . $e->getMessage(), "E");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    /**
     * Processa envio parcial de pedido multiseller
     * @param string $billNo
     * @param array $shippingData
     * @return array
     */
    public function processPartialShipping(string $billNo, array $shippingData): array
    {
        // Verificar se a feature flag está habilitada feature-OEP-2009-partial-invoicing
        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-muiltiseller-freight-results')) {
            return ['success' => false, 'message' => 'Feature não habilitada'];
        }
        
        $log_name = __CLASS__.'/'.__FUNCTION__;
        
        try {
            // Buscar pedido
            $order = $this->model_orders->getOrdersDatabyBill($this->int_to, $billNo);
            
            if (!$order) {
                return ['success' => false, 'message' => 'Pedido não encontrado'];
            }
            
            // Validar dados de envio
            $requiredFields = ['tracking_code', 'carrier', 'shipping_date'];
            foreach ($requiredFields as $field) {
                if (empty($shippingData[$field])) {
                    return ['success' => false, 'message' => "Campo obrigatório: $field"];
                }
            }
            
            // Atualizar dados de envio
            $updateData = [
                'tracking_code' => $shippingData['tracking_code'],
                'shipping_carrier' => $shippingData['carrier'],
                'shipping_date' => $shippingData['shipping_date'],
                'estimated_delivery' => $shippingData['estimated_delivery'] ?? null
            ];
            
            $this->model_orders->updateShippingData($order['id'], $updateData);
            
            // Atualizar status do pedido
            $this->model_orders->updateOrderStatus($order['id'], 6); // Status: Enviado
            
            // Notificar marketplace
            $this->notifyMarketplaceShipping($order, $shippingData);
            
            $this->log_data('batch', $log_name, "Envio parcial processado: $billNo", "I");
            
            return [
                'success' => true,
                'tracking_code' => $shippingData['tracking_code'],
                'message' => 'Envio processado com sucesso'
            ];
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, "Erro no envio parcial $billNo: " . $e->getMessage(), "E");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    private function getDataItemsToCreate(array $content): array
    {
        $log_name = __CLASS__.'/'.__FUNCTION__;
        $total_order = array();
        $discount = array();
        $items = array();
        //agora passo ao cadastro dos itens
        if ($content['items'] && is_array($content['items'])) {
            foreach ($content['items'] as $item) {


                $variant = '';
                $skuMkt = $item['sku_variation'] ?? $item['sku'];
                $integration_last_post = $this->model_sellercenter_last_post->getDataBySkulocalAndIntto($skuMkt, $this->int_to);
                if (!$integration_last_post) {
                    $integration_last_post = $this->model_integration_last_post->getDataBySkulocalAndIntto($skuMkt, $this->int_to);
                }

                if (!$integration_last_post) {
                    throw new Exception("SKU=$skuMkt do MKT=$this->int_to não localizado para o pedido={$content['marketplace_number']}");
                }

                $product = $this->model_products->getProductData(0, $integration_last_post['prd_id']);

                if (empty($product)) {
                    $message = "Erro para localizar o produto {$integration_last_post['prd_id']} - Pedido mkt = {$content['marketplace_number']}\n";
                    echo "$message\n";
                    $this->log_data('batch', $log_name, $message, "E");
                    throw new Exception("SKU=$skuMkt do MKT=$this->int_to não localizado para o pedido={$content['marketplace_number']}");
                }

                $store_id = $product['store_id'];

                if (!array_key_exists($store_id, $discount)) {
                    $discount[$store_id] = 0;
                }
                if (!array_key_exists($store_id, $total_order)) {
                    $total_order[$store_id] = 0;
                }
                $discount[$store_id]    += floatVal($item['discount']);
                $total_order[$store_id] += $item['total_price'];

                if ($product['is_kit'] == 0) {
                    $variant = $integration_last_post['variant'];
                    $items[] = array(
                        'order_id'       => NULL,
                        'skumkt'         => $skuMkt,
                        'product_id'     => $product['id'],
                        'sku'            => $product['sku'],
                        'variant'        => $variant,
                        'name'           => $product['name'],
                        'qty'            => $item['qty'],
                        'rate'           => $item['original_price'],
                        'amount'         => $item['total_price'],
                        'discount'       => floatVal($item['discount']),
                        'company_id'     => $product['company_id'],
                        'store_id'       => $product['store_id'],
                        'un'             => $item['unity'],
                        'pesobruto'      => $integration_last_post['gross_weight'],
                        'largura'        => $integration_last_post['width'],
                        'altura'         => $integration_last_post['height'],
                        'profundidade'   => $integration_last_post['length'],
                        'unmedida'       => $item['measured_unit'],
                        'kit_id'         => null
                    );

                } else {
                    echo "O item é um KIT id={$product['id']}\n";

                    $productsKit = $this->model_products->getProductsKit($product['id']);

                    foreach ($productsKit as $productKit) {
                        $kit_item = $this->model_products->getProductData(0,$productKit['product_id_item']);
                        echo "Produto item =".$kit_item['id']."\n";

                        $qty = $item['qty'] * $productKit['qty'];
                        $rate = $productKit['price'];
                        $items[] = array(
                            'order_id'       => null,
                            'skumkt'         => $skuMkt,
                            'product_id'     => $kit_item['id'],
                            'sku'            => $kit_item['sku'],
                            'variant'        => '',
                            'name'           => $kit_item['name'],
                            'qty'            => $qty,
                            'rate'           => $rate,
                            'amount'         => floatVal($rate) * floatVal($qty),
                            'discount'       => 0,
                            'company_id'     => intVal($kit_item['company_id']),
                            'store_id'       => intVal($kit_item['store_id']),
                            'un'             => $item['unity'],
                            'pesobruto'      => $kit_item['peso_bruto'],
                            'largura'        => $kit_item['largura'],
                            'altura'         => $kit_item['altura'],
                            'profundidade'   => $kit_item['profundidade'],
                            'unmedida'       => $item['measured_unit'],
                            'kit_id'         => $productKit['product_id']
                        );
                    }
                }
            }

            //cadastro na tabela de integrations.
            //$this->model_orders->createOrderToIntegration($order_id, $company_id, $store_id, $paid_status, $new_order);
        } else {
            // Se não existem itens no pedido não posso registrar.
            $message = "Erro ao incluir item(s). Pedido mkt ={$content['marketplace_number']} deve receber novamente\n";
            echo "$message\n";
            $this->log_data('batch', $log_name, $message, "E");
            throw new Exception("Erro ao incluir item(s). Pedido mkt ={$content['marketplace_number']}");
        }

        return array(
            'items'         => $items,
            'discount'      => $discount,
            'total_order'   => $total_order
        );
    }

    private function getItemsByStore(array $items): array
    {
        $new_items = array();

        foreach ($items as $item) {

            if (!array_key_exists($item['store_id'], $new_items)) {
                $new_items[$item['store_id']] = array();
            }

            $new_items[$item['store_id']][] = $item;
        }

        return $new_items;
    }
    /**
     * Verifica se o pedido é multiseller
     * @param array $items
     * @return bool
     */
    private function isMultisellerOrder(array $items): bool
    {
        if (!$this->enable_multiseller_operation) {
            return false;
        }
        
        $sellers = [];
        foreach ($items as $item) {
            $seller = $this->extractSellerFromSku($item['sku']);
            if ($seller) {
                $sellers[$seller] = true;
            }
        }
        
        return count($sellers) > 1;
    }

    /**
     * Extrai seller do SKU
     * @param string $sku
     * @return string|null
     */
    private function extractSellerFromSku(string $sku): ?string
    {
        // Padrão: P123S1001NM -> Seller: 1001
        if (preg_match('/S(\d+)/', $sku, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    /**
    * Recupera cotação multiseller do log_quotes
    * @param string $marketplaceNumber
    * @return array|null
    */
    private function retrieveMultisellerQuote(string $marketplaceNumber): ?array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        try {
            // Buscar cotação no log_quotes
            $quoteData = $this->model_log_quotes->getQuoteByMarketplaceNumber($marketplaceNumber, $this->int_to);
            
            if (!$quoteData) {
                $this->log_data('batch', $log_name, 
                    "Cotação não encontrada para pedido: " . $marketplaceNumber, "W");
                return null;
            }
            
            // Decodificar dados da cotação
            $quoteResult = json_decode($quoteData['quote_result'], true);
            
            if (!$quoteResult || !isset($quoteResult['sellers'])) {
                $this->log_data('batch', $log_name, 
                    "Dados de cotação inválidos para pedido: " . $marketplaceNumber, "W");
                return null;
            }
            
            $this->log_data('batch', $log_name, 
                "Cotação recuperada com sucesso para pedido: " . $marketplaceNumber . 
                " - Sellers: " . implode(', ', array_keys($quoteResult['sellers'])), "I");
            
            return $quoteResult;
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, 
                "Erro ao recuperar cotação para pedido " . $marketplaceNumber . ": " . $e->getMessage(), "E");
            return null;
        }
    }
    /**
     * Valida correspondência entre frete do pedido e cotação
     * @param array $content Dados do pedido
     * @param array|null $quoteData Dados da cotação
     * @return array
     */
    private function validateFreightCorrespondence(array $content, ?array $quoteData): array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        // Verificar se feature flag está habilitada
        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-muiltiseller-freight-results')) {
            return ['valid' => true, 'message' => 'Feature flag desabilitada - validação ignorada'];
        }
        
        try {
            $originalFreight = $content['shipping']['seller_shipping_cost'] ?? 0;
            
            // Se frete é zero, sempre válido
            if ($originalFreight == 0) {
                $this->log_data('batch', $log_name, "Frete zero - validação aprovada automaticamente", "I");
                return ['valid' => true, 'message' => 'Frete zero - sempre válido'];
            }
            
            // Se não há cotação, usar divisão proporcional
            if (!$quoteData || !isset($quoteData['sellers'])) {
                $this->log_data('batch', $log_name, "Sem cotação disponível - usando divisão proporcional", "W");
                return ['valid' => true, 'message' => 'Sem cotação - divisão proporcional'];
            }
            
            // Calcular frete total da cotação
            $quoteTotalFreight = 0;
            foreach ($quoteData['sellers'] as $sellerId => $sellerQuote) {
                $quoteTotalFreight += $sellerQuote['shipping_cost'] ?? 0;
            }
            
            // Verificar correspondência (tolerância de 5%)
            $tolerance = 0.05; // 5%
            $difference = abs($originalFreight - $quoteTotalFreight);
            $maxDifference = $originalFreight * $tolerance;
            
            if ($difference <= $maxDifference) {
                $this->log_data('batch', $log_name, 
                    "Validação aprovada - Pedido: R$ " . number_format($originalFreight, 2, ',', '.') . 
                    ", Cotação: R$ " . number_format($quoteTotalFreight, 2, ',', '.') . 
                    ", Diferença: R$ " . number_format($difference, 2, ',', '.'), "I");
                
                return ['valid' => true, 'message' => 'Frete corresponde à cotação'];
            } else {
                $this->log_data('batch', $log_name, 
                    "Validação rejeitada - Pedido: R$ " . number_format($originalFreight, 2, ',', '.') . 
                    ", Cotação: R$ " . number_format($quoteTotalFreight, 2, ',', '.') . 
                    ", Diferença: R$ " . number_format($difference, 2, ',', '.') . 
                    " (máx: R$ " . number_format($maxDifference, 2, ',', '.') . ")", "W");
                
                return [
                    'valid' => false, 
                    'message' => "Frete não corresponde à cotação - Pedido: R$ " . 
                            number_format($originalFreight, 2, ',', '.') . 
                            ", Cotação: R$ " . number_format($quoteTotalFreight, 2, ',', '.')
                ];
            }
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, "Erro na validação: " . $e->getMessage(), "E");
            return ['valid' => true, 'message' => 'Erro na validação - usando fallback'];
        }
    }
    /**
     * Divide pedido usando cotação específica de frete
     * @param array $content
     * @param array $quoteData
     * @return array
     */
    private function divideOrderWithFreight(array $content, array $quoteData): array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        $sellerGroups = $this->groupItemsBySeller($content['items']);
        $brokenOrders = [];
        $orderSuffix = 1;
        
        // Ordenar por ID de seller (menor ID = -01)
        ksort($sellerGroups);
        
        foreach ($sellerGroups as $sellerId => $sellerItems) {
            // Criar ID único para o pedido quebrado
            $newMarketplaceNumber = $content['marketplace_number'] . '-' . str_pad($orderSuffix, 2, '0', STR_PAD_LEFT);
            
            // Criar pedido quebrado
            $brokenOrder = $content;
            $brokenOrder['marketplace_number'] = $newMarketplaceNumber;
            $brokenOrder['order_mkt_multiseller'] = $content['marketplace_number'];
            $brokenOrder['items'] = $sellerItems;
            
            // Usar cotação específica do seller
            if (isset($quoteData['sellers'][$sellerId])) {
                $sellerQuote = $quoteData['sellers'][$sellerId];
                
                $brokenOrder['shipping']['seller_shipping_cost'] = $sellerQuote['shipping_cost'] ?? 0;
                $brokenOrder['shipping']['shipping_carrier'] = $sellerQuote['carrier'] ?? $content['shipping']['shipping_carrier'];
                $brokenOrder['shipping']['service_method'] = $sellerQuote['method'] ?? $content['shipping']['service_method'];
                $brokenOrder['shipping']['estimated_delivery_days'] = $sellerQuote['deadline'] ?? $content['shipping']['estimated_delivery_days'];
                
                $this->log_data('batch', $log_name, 
                    "Usando cotação específica para seller " . $sellerId . 
                    " - Frete: R$ " . number_format($sellerQuote['shipping_cost'] ?? 0, 2, ',', '.'), "I");
            } else {
                $this->log_data('batch', $log_name, 
                    "Cotação não encontrada para seller " . $sellerId . " - usando valores padrão", "W");
                $brokenOrder['shipping']['seller_shipping_cost'] = 0;
            }
            
            // Calcular valores proporcionais dos produtos
            $brokenOrder = $this->calculateProportionalValues($brokenOrder, $content, $quoteData, $sellerId);
            
            $brokenOrders[] = $brokenOrder;
            $orderSuffix++;
        }
        
        $this->log_data('batch', $log_name, 
            "Pedido " . $content['marketplace_number'] . " dividido em " . count($brokenOrders) . 
            " pedidos usando cotação específica", "I");
        
        return $brokenOrders;
    }
    /**
     * Divide pedido sem usar cotação (frete zero ou proporcional)
     * @param array $content
     * @return array
     */
    private function divideOrderWithoutFreight(array $content): array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        $sellerGroups = $this->groupItemsBySeller($content['items']);
        $brokenOrders = [];
        $orderSuffix = 1;
        
        // Ordenar por ID de seller (menor ID = -01)
        ksort($sellerGroups);
        
        $originalFreight = $content['shipping']['seller_shipping_cost'] ?? 0;
        
        foreach ($sellerGroups as $sellerId => $sellerItems) {
            // Criar ID único para o pedido quebrado
            $newMarketplaceNumber = $content['marketplace_number'] . '-' . str_pad($orderSuffix, 2, '0', STR_PAD_LEFT);
            
            // Criar pedido quebrado
            $brokenOrder = $content;
            $brokenOrder['marketplace_number'] = $newMarketplaceNumber;
            $brokenOrder['order_mkt_multiseller'] = $content['marketplace_number'];
            $brokenOrder['items'] = $sellerItems;
            
            if ($originalFreight == 0) {
                // Frete zero: manter zero para todos
                $brokenOrder['shipping']['seller_shipping_cost'] = 0;
                
                $this->log_data('batch', $log_name, 
                    "Frete zero mantido para seller " . $sellerId, "I");
            } else {
                // Calcular valores proporcionais (incluindo frete)
                $brokenOrder = $this->calculateProportionalValues($brokenOrder, $content, null, $sellerId);
                
                $this->log_data('batch', $log_name, 
                    "Divisão proporcional aplicada para seller " . $sellerId . 
                    " - Frete: R$ " . number_format($brokenOrder['shipping']['seller_shipping_cost'], 2, ',', '.'), "I");
            }
            
            $brokenOrders[] = $brokenOrder;
            $orderSuffix++;
        }
        
        $this->log_data('batch', $log_name, 
            "Pedido " . $content['marketplace_number'] . " dividido em " . count($brokenOrders) . 
            " pedidos sem usar cotação específica", "I");
        
        return $brokenOrders;
    }
    /**
 * Executa nova cotação multiseller
 * @param array $items
 * @param string $zipcode
 * @return array|null
 */
private function executeNewMultisellerQuote(array $items, string $zipcode): ?array
{
    try {
        $result = $this->calculofrete->formatQuote([$this->int_to], $items, $zipcode);
        
        // Forçar conversão para array se for string JSON
        if (is_string($result)) {
            $result = json_decode($result, true) ?: null;
        }
        
        if (is_array($result) && isset($result['services']) && !empty($result['services'])) {
            return $result;
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Erro ao executar nova cotação multiseller: " . $e->getMessage());
        return null;
    }
}

    /**
     * Verifica se cotação ainda é válida
     * @param array $quoteData
     * @return bool
     */
    private function isQuoteValid(array $quoteData): bool
    {
        $createdAt = strtotime($quoteData['created_at']);
        $expirationTime = $createdAt + (24 * 60 * 60); // 24 horas
        
        return time() < $expirationTime;
    }
    /**
     * Cria pedidos quebrados com IDs únicos e recuperação de cotação
     * @param array $content
     * @return array
     */
    protected function createBrokenOrdersWithUniqueIds(array $content): array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        try {
            // Recuperar cotação multiseller
            $quoteData = $this->retrieveMultisellerQuote($content['marketplace_number']);
            
            // Validar correspondência de frete
            $validation = $this->validateFreightCorrespondence($content, $quoteData);
            
            if (!$validation['valid']) {
                // Se validação falhar, registrar erro mas continuar com divisão proporcional
                $this->log_data('batch', $log_name, 
                    "Validação de frete falhou: " . $validation['message'] . " - usando divisão proporcional", "W");
                
                return $this->divideOrderWithoutFreight($content);
            }
            
            // Se frete é zero ou não há cotação, usar divisão sem cotação
            $originalFreight = $content['shipping']['seller_shipping_cost'] ?? 0;
            if ($originalFreight == 0 || !$quoteData) {
                return $this->divideOrderWithoutFreight($content);
            }
            
            // Usar cotação específica para divisão
            return $this->divideOrderWithFreight($content, $quoteData);
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, 
                "Erro ao criar pedidos quebrados: " . $e->getMessage() . " - usando fallback", "E");
            
            // Fallback: divisão sem cotação
            return $this->divideOrderWithoutFreight($content);
        }
    }

    /**
     * Agrupa itens por seller
     * @param array $items
     * @return array
     */
   private function groupItemsBySeller($items) {
        $sellerGroups = [];
        
        foreach ($items as $item) {
            $seller = $this->extractSellerFromSku($item['sku']);
            if ($seller) {
                $sellerGroups[$seller][] = $item;
            }
        }
        ksort($sellerGroups); // Ordena por seller ID (menor primeiro)
        
        return $sellerGroups;
    }

    /**
     * Calcula valores proporcionais para pedido quebrado
     * @param array $brokenOrder
     * @param array $originalOrder
     * @param array|null $quoteData
     * @param string $sellerId
     * @return array
     */
    private function calculateProportionalValues(array $brokenOrder, array $originalOrder, ?array $quoteData, string $sellerId): array
    {
        // Calcular valor dos produtos do seller
        $sellerProductValue = 0;
        foreach ($brokenOrder['items'] as $item) {
            $sellerProductValue += $item['price'] * $item['quantity'];
        }
        
        // Calcular proporção
        $totalProductValue = $originalOrder['payments']['total_products'];
        $proportion = $totalProductValue > 0 ? $sellerProductValue / $totalProductValue : 0;
        
        // Aplicar proporção aos valores
        $brokenOrder['payments']['total_products'] = $sellerProductValue;
        $brokenOrder['payments']['discount'] = $originalOrder['payments']['discount'] * $proportion;
        
        // Buscar frete específico do seller na cotação
        if ($quoteData && isset($quoteData['sellers'][$sellerId])) {
            $sellerQuote = $quoteData['sellers'][$sellerId];
            $brokenOrder['shipping']['seller_shipping_cost'] = $sellerQuote['shipping_cost'] ?? 0;
            $brokenOrder['shipping']['shipping_carrier'] = $sellerQuote['carrier'] ?? $originalOrder['shipping']['shipping_carrier'];
            $brokenOrder['shipping']['service_method'] = $sellerQuote['method'] ?? $originalOrder['shipping']['service_method'];
            $brokenOrder['shipping']['estimated_delivery_days'] = $sellerQuote['deadline'] ?? $originalOrder['shipping']['estimated_delivery_days'];
        } else {
            // Fallback: dividir frete proporcionalmente
            $brokenOrder['shipping']['seller_shipping_cost'] = $originalOrder['shipping']['seller_shipping_cost'] * $proportion;
        }
        
        return $brokenOrder;
    }

    /**
     * Valida itens para operação parcial
     * @param int $orderId
     * @param array $items
     * @return array
     */
    private function validateItemsForPartialOperation(int $orderId, array $items): array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        // Verificar se feature flag está habilitada feature-OEP-2009-partial-invoicing
        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-muiltiseller-freight-results')) {
            return ['valid' => false, 'message' => 'Feature flag de operações parciais desabilitada'];
        }
        
        try {
            if (empty($items)) {
                return ['valid' => true, 'message' => 'Nenhum item específico - operação em todo o pedido'];
            }
            
            // Buscar itens do pedido
            $orderItems = $this->model_orders_item->getItemsByOrderId($orderId);
            
            if (empty($orderItems)) {
                return ['valid' => false, 'message' => 'Pedido não possui itens'];
            }
            
            // Validar se todos os itens solicitados existem no pedido
            $orderSkus = array_column($orderItems, 'sku');
            
            foreach ($items as $item) {
                if (!isset($item['sku'])) {
                    return ['valid' => false, 'message' => 'SKU não informado em um dos itens'];
                }
                
                if (!in_array($item['sku'], $orderSkus)) {
                    return ['valid' => false, 'message' => "SKU {$item['sku']} não encontrado no pedido"];
                }
                
                // Validar quantidade se informada
                if (isset($item['quantity'])) {
                    $orderItem = array_filter($orderItems, function($oi) use ($item) {
                        return $oi['sku'] === $item['sku'];
                    });
                    
                    $orderItem = reset($orderItem);
                    
                    if ($item['quantity'] > $orderItem['quantity']) {
                        return ['valid' => false, 'message' => "Quantidade solicitada para SKU {$item['sku']} maior que disponível"];
                    }
                }
            }
            
            $this->log_data('batch', $log_name, 
                "Validação de itens aprovada para pedido " . $orderId . " - " . count($items) . " itens", "I");
            
            return ['valid' => true, 'message' => 'Itens validados com sucesso'];
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, "Erro na validação: " . $e->getMessage(), "E");
            return ['valid' => false, 'message' => 'Erro interno na validação'];
        }
    }

    /**
     * Gera dados da nota fiscal
     * @param array $order
     * @param array $items
     * @return array
     */
    private function generateInvoiceData(array $order, array $items): array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        // Verificar se feature flag está habilitada feature-OEP-2009-partial-invoicing
        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-muiltiseller-freight-results')) {
            return [];
        }
        
        try {
            // Calcular valor da nota fiscal
            $invoiceValue = 0;
            
            if (empty($items)) {
                // Faturar pedido completo
                $invoiceValue = $order['total_order'] ?? 0;
            } else {
                // Faturar apenas itens específicos
                $orderItems = $this->model_orders_item->getItemsByOrderId($order['id']);
                
                foreach ($items as $item) {
                    $orderItem = array_filter($orderItems, function($oi) use ($item) {
                        return $oi['sku'] === $item['sku'];
                    });
                    
                    $orderItem = reset($orderItem);
                    
                    if ($orderItem) {
                        $quantity = $item['quantity'] ?? $orderItem['quantity'];
                        $invoiceValue += $orderItem['price'] * $quantity;
                    }
                }
            }
            
            $invoiceData = [
                'order_id' => $order['id'],
                'invoice_value' => $invoiceValue,
                'invoice_date' => date('Y-m-d H:i:s'),
                'invoice_number' => null, // Será gerado automaticamente
                'invoice_key' => null,    // Será preenchido pela integração fiscal
                'invoice_xml' => null,    // Será preenchido pela integração fiscal
                'invoice_pdf' => null     // Será preenchido pela integração fiscal
            ];
            
            $this->log_data('batch', $log_name, 
                "Dados de nota fiscal gerados para pedido " . $order['bill_no'] . 
                " - Valor: R$ " . number_format($invoiceValue, 2, ',', '.'), "I");
            
            return $invoiceData;
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, "Erro ao gerar dados da NF: " . $e->getMessage(), "E");
            return [];
        }
    }
    // Localização: GetOrders.php
    private function generateInvoiceNumber() {
        return 'INV-' . date('Ymd') . '-' . uniqid();
    }

    /**
     * Notifica marketplace sobre faturamento
     * @param array $order
     * @param array $invoiceData
     * @return array
     */
    private function notifyMarketplaceInvoicing(array $order, array $invoiceData): array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        try {
            // Verificar se feature flag está habilitada feature-OEP-2009-partial-invoicing
            if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-muiltiseller-freight-results')) {
                $this->log_data('batch', $log_name, 
                    "Feature flag de faturamento parcial desabilitada - notificação ignorada", "W");
                return ['success' => true, 'message' => 'Feature flag desabilitada'];
            }
            
            // Preparar dados da notificação
            $notificationData = [
                'order_code' => $order['bill_no'],
                'marketplace_number' => $order['order_mkt_multiseller'] ?? $order['bill_no'],
                'invoice_number' => $invoiceData['invoice_number'],
                'invoice_key' => $invoiceData['invoice_key'],
                'invoice_date' => $invoiceData['invoice_date'],
                'invoice_value' => $invoiceData['invoice_value'],
                'status' => 'invoiced',
                'notification_type' => 'partial_invoicing'
            ];
            
            // Enviar notificação via Integration
            $result = $this->integration->notifyInvoicing($this->api_keys, $notificationData);
            
            if ($result['success']) {
                $this->log_data('batch', $log_name, 
                    "Notificação de faturamento enviada com sucesso - Pedido: " . $order['bill_no'] . 
                    ", NF: " . $invoiceData['invoice_number'], "I");
            } else {
                $this->log_data('batch', $log_name, 
                    "Erro ao enviar notificação de faturamento - Pedido: " . $order['bill_no'] . 
                    ", Erro: " . $result['message'], "E");
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, 
                "Exceção ao notificar faturamento - Pedido: " . $order['bill_no'] . 
                ", Erro: " . $e->getMessage(), "E");
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Notifica marketplace sobre envio
     * @param array $order
     * @param array $shippingData
     * @return array
     */
    private function notifyMarketplaceShipping(array $order, array $shippingData): array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        try {
            // Verificar se feature flag está habilitada feature-OEP-2010-partial-shipping
            if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-muiltiseller-freight-results')) {
                $this->log_data('batch', $log_name, 
                    "Feature flag de envio parcial desabilitada - notificação ignorada", "W");
                return ['success' => true, 'message' => 'Feature flag desabilitada'];
            }
            
            // Preparar dados da notificação
            $notificationData = [
                'order_code' => $order['bill_no'],
                'marketplace_number' => $order['order_mkt_multiseller'] ?? $order['bill_no'],
                'tracking_code' => $shippingData['tracking_code'] ?? null,
                'shipping_carrier' => $shippingData['shipping_carrier'] ?? $order['shipping_carrier'],
                'service_method' => $shippingData['service_method'] ?? $order['service_method'],
                'shipping_date' => $shippingData['shipping_date'] ?? date('Y-m-d H:i:s'),
                'estimated_delivery_date' => $shippingData['estimated_delivery_date'] ?? null,
                'status' => 'shipped',
                'notification_type' => 'partial_shipping'
            ];
            
            // Enviar notificação via Integration
            $result = $this->integration->notifyShipping($this->api_keys, $notificationData);
            
            if ($result['success']) {
                $this->log_data('batch', $log_name, 
                    "Notificação de envio enviada com sucesso - Pedido: " . $order['bill_no'] . 
                    ", Tracking: " . ($shippingData['tracking_code'] ?? 'N/A'), "I");
            } else {
                $this->log_data('batch', $log_name, 
                    "Erro ao enviar notificação de envio - Pedido: " . $order['bill_no'] . 
                    ", Erro: " . $result['message'], "E");
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, 
                "Exceção ao notificar envio - Pedido: " . $order['bill_no'] . 
                ", Erro: " . $e->getMessage(), "E");
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Verifica se deve disparar processo financeiro
     * @param array $order
     * @return bool
     */
    private function shouldTriggerFinancialProcess(array $order): bool
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        // Verificar se feature flag está habilitada
        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('feature-OEP-2012-financial-trigger')) {
            return false;
        }
        
        // Verificar se é pedido multiseller
        if (empty($order['order_mkt_multiseller'])) {
            return false;
        }
        
        // Verificar se é a primeira entrega do grupo multiseller
        return $this->isFirstDeliveryForMultisellerOrder($order['order_mkt_multiseller']);
    }

    /**
     * Verifica se é a primeira entrega de um pedido multiseller
     * @param string $multisellerNumber
     * @return bool
     */
    private function isFirstDeliveryForMultisellerOrder(string $multisellerNumber): bool
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        try {
            // Buscar todos os pedidos do grupo multiseller
            $orders = $this->model_orders->getOrdersByMultisellerNumber($multisellerNumber);
            
            if (empty($orders)) {
                return false;
            }
            
            // Verificar quantos já foram entregues (status 7 = entregue)
            $deliveredCount = 0;
            foreach ($orders as $order) {
                if ($order['paid_status'] == 7) { // Status entregue
                    $deliveredCount++;
                }
            }
            
            // É a primeira entrega se nenhum foi entregue ainda
            $isFirst = ($deliveredCount == 0);
            
            $this->log_data('batch', $log_name, 
                "Verificação primeira entrega - Multiseller: " . $multisellerNumber . 
                ", Total pedidos: " . count($orders) . ", Entregues: " . $deliveredCount . 
                ", É primeira: " . ($isFirst ? 'Sim' : 'Não'), "I");
            
            return $isFirst;
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, 
                "Erro ao verificar primeira entrega - Multiseller: " . $multisellerNumber . 
                ", Erro: " . $e->getMessage(), "E");
            return false;
        }
    }

    /**
     * Dispara processo financeiro para primeira entrega
     * @param array $order
     * @return array
     */
    private function triggerFinancialProcessForFirstDelivery(array $order): array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        try {
            // Verificar se feature flag está habilitada feature-OEP-2012-financial-trigger
            if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-muiltiseller-freight-results')) {
                return ['success' => false, 'message' => 'Feature flag desabilitada'];
            }
            
            // Buscar todos os pedidos do grupo multiseller
            $orders = $this->model_orders->getOrdersByMultisellerNumber($order['order_mkt_multiseller']);
            
            // Calcular valor total do grupo
            $totalValue = 0;
            foreach ($orders as $groupOrder) {
                $totalValue += $groupOrder['total_order'] ?? 0;
            }
            
            // Preparar dados do processo financeiro
            $financialData = [
                'multiseller_number' => $order['order_mkt_multiseller'],
                'trigger_order' => $order['bill_no'],
                'total_orders' => count($orders),
                'total_value' => $totalValue,
                'trigger_date' => date('Y-m-d H:i:s'),
                'process_type' => 'first_delivery_trigger'
            ];
            
            // Aqui você pode integrar com o sistema financeiro
            // Por exemplo: $this->financialSystem->processMultisellerPayment($financialData);
            
            $this->log_data('batch', $log_name, 
                "Processo financeiro disparado - Multiseller: " . $order['order_mkt_multiseller'] . 
                ", Pedido gatilho: " . $order['bill_no'] . 
                ", Valor total: R$ " . number_format($totalValue, 2, ',', '.'), "I");
            
            return ['success' => true, 'message' => 'Processo financeiro disparado com sucesso'];
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, 
                "Erro ao disparar processo financeiro - Pedido: " . $order['bill_no'] . 
                ", Erro: " . $e->getMessage(), "E");
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    /**
     * Recupera se o seller usa logística própria do ERP.
     *
     * @return bool
     */
    public function getStoreOwnLogisticERP(): bool
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        // Usar store_id dos dados da integração
        $storeId = $this->integration_data['store_id'] ?? null;
        
        if (!$storeId) {
            $this->log_data('batch', $log_name, "Store ID não encontrado nos dados da integração", "E");
            return false;
        }
        
        $store = $this->model_stores->getStoresData($storeId);
        
        if (!$store) {
            $this->log_data('batch', $log_name, "Dados da loja não encontrados para store_id: " . $storeId, "E");
            return false;
        }

        $logistic = $this->calculofrete->getLogisticStore(array(
            'freight_seller' 		=> $store['freight_seller'],
            'freight_seller_type'   => $store['freight_seller_type'],
            'store_id'				=> $store['id']
        ));

        $typeIntegration = $this->integration_data['integration_type'] ?? 'CONECTALA';
        
        $result = $logistic['seller'] && $logistic['type'] == $typeIntegration;
        
        $this->log_data('batch', $log_name, 
            "Verificação logística própria - Store: " . $storeId . 
            ", Resultado: " . ($result ? 'SIM' : 'NÃO'), "I");
        
        return $result;
    }
    private function getEmailClient(string $email, string $idPedido, string $intTo): string
    {
        if (empty($email)) {
            return '';
        }

        $hide_marketplace_email = $this->model_settings->getSettingDatabyName('hide_marketplace_email');

        if ($hide_marketplace_email['status'] == 1) {
            $orderIdClean = preg_replace('/[^a-zA-Z0-9]/', '', $idPedido);
            return  $intTo . $orderIdClean . '@example.com';
        } else {
            return $email;
        }
    }
}
