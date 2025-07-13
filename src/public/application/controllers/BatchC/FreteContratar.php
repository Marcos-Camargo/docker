<?php
/*

Verifica quais ordens precisam de frete e contrata no frete rápido

*/

require_once "application/libraries/CalculoFrete.php";

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_DB_driver $db
 * @property CI_Router $router
 *
 * @property Model_orders $model_orders
 * @property Model_quotes_ship $model_quotes_ship
 * @property Model_clients $model_clients
 * @property Model_freights $model_freights
 * @property Model_settings $model_settings
 * @property Model_stores $model_stores
 * @property Model_orders_with_problem $model_orders_with_problem
 * @property Model_products_catalog $model_products_catalog
 * @property Model_products $model_products
 * @property Model_category $model_category
 * @property Model_order_items_cancel $model_order_items_cancel
 *
 * @property CalculoFrete $calculofrete
 */

class FreteContratar extends BatchBackground_Controller
{

    /**
     * @var LogisticTypesWithAutoFreightAcceptedGeneration
     */
	private $logisticAutoApproval;

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
		$this->load->model('model_orders');
		$this->load->model('model_quotes_ship');
		$this->load->model('model_clients');
		$this->load->model('model_freights');
		$this->load->model('model_settings');
		$this->load->model('model_stores');
		$this->load->model('model_orders_with_problem');
        $this->load->model('model_products_catalog');
        $this->load->model('model_products');
        $this->load->model('model_category');
        $this->load->model('model_order_items_cancel');

        $this->load->library('calculoFrete');

        $this->logisticAutoApproval = (new LogisticTypesWithAutoFreightAcceptedGeneration(
            $this->db
        ))->setEnvironment(
            $this->model_settings->getValueIfAtiveByName('sellercenter')
        );
    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name = __CLASS__.'/'.__FUNCTION__;
		if (!$this->gravaInicioJob(__CLASS__,__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		
		// $this->acertaFeightsCost(); 
		
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params));
		
		/* faz o que o job precisa fazer */
		//leio os pedidos com status paid_status = 50 que são as ordens que já foram faturadas
		$paid_status = [50, 80];
		
		$orders = $this->model_orders->getOrdensByPaidStatus($paid_status);
		if (!isset($orders)) {
			$this->log_data('batch',$log_name,'Nenhuma ordem 50 em andamento');
		}
		$this->hireFreight($orders);

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish');
		$this->gravaFimJob();
	}

    /**
     * Contratar Frete
     *
     * @param $orders
     */
	private function hireFreight($orders)
    {
		$log_name =__CLASS__.'/'.__FUNCTION__;

		foreach ($orders as $order) {
            $integration_logistic = $order['integration_logistic'];

			// pego as informações da loja
			$store = $this->model_stores->getStoresData($order['store_id']);
            $logisticStore = $this->calculofrete->getLogisticStore(array(
                'freight_seller' 		=> $store['freight_seller'],
                'freight_seller_type' 	=> $store['freight_seller_type'],
                'store_id'				=> $store['id']
            ));

            // A logística ainda é a mesma, não precisa trocar.
            if (!is_null($integration_logistic) && $logisticStore['type'] == $integration_logistic && $logisticStore['sellercenter']) {
                $integration_logistic = null;
            }

            if (!Model_orders::isFreightAcceptedGeneration($order) &&
                !$this->logisticAutoApproval->isLogisticTypeWithAutoFreightAcceptedGeneration(
                    $integration_logistic ?: $logisticStore['type']
                )
            ) {
                echo "pedido {$order['id']} é por transportadora ou getaway logístico e ainda não foi autorizado a emissão\n";
                continue;
            }

			//echo 'ordem id '.$order['id'];
			$invoices = $this->model_orders->getOrdersNfes($order['id']);
			if (count($invoices) == 0) {
				echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
				$this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
				// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50
				$this->setProblemWithOrder($order['id'], 'Pedido não contém nota fiscal.');
				continue;
			}
			$invoice = $invoices[0];
			$freight = $this->model_orders->getItemsFreights($order['id']);
			$orderIntelipostWaitTrackingCode = false;
			$orderFRtWaitLabel = false;
			if (count($freight) > 0) {
				// se for pelo sgp, não mostra um erro no log
				$orderWithFreight = false;
				$orderWithFreightResend = false;
				foreach ($this->model_freights->getDataFreightsOrderId($order['id']) as $freight_v) {
					if ($order['in_resend_active'] && $freight_v['in_resend_active']) {
						$orderWithFreightResend = true;
					}
					else if(!$order['in_resend_active'] && $freight_v['link_etiqueta_a4'] != null && in_array($freight_v['sgp'], array(1,7))) {
						echo "Pedido={$order['id']} já com etiqueta, aguardando PLP\n";
						// não crio log, pois é um fluxo normal
						$orderWithFreight = true;
					}
					else if ($freight_v['sgp'] == 4 && $freight_v['codigo_rastreio'] == null) {
						$orderIntelipostWaitTrackingCode = true;
					}
					else if ($freight_v['sgp'] == 2 && ($freight_v['link_etiqueta_a4'] == null || $freight_v['link_etiqueta_termica'] == null)) {
						$orderFRtWaitLabel = true;
					}
				}

				if ($orderWithFreightResend) {
					echo "Pedido={$order['id']} (REENVIO) já com etiqueta, aguardando PLP\n";
					continue;
				}
				else if ($orderWithFreight) {
                    continue;
                }
			}

			echo 'ordem precisando de frete '.$order['id']."\n";

			//pego o cliente
			$client = $this->model_clients->getClientsData($order['customer_id']);
			if (!(isset($client))) {
				$this->log_data('batch',$log_name,'ERRO: pedido '.$order['id'].' sem o cliente '.$order['customer_id'],"E");
				$this->setProblemWithOrder($order['id'], 'Pedido não contém cliente.');
				continue;
			}

            $logistic_type          = $logisticStore['type'];
            $logistic_seller        = $logisticStore['seller'];
            $logistic_type_real     = $logisticStore['type'];
            $logistic_seller_real   = $logisticStore['seller'];
            // A logística é do seller center
            // Se está diferente da atual
            // Deve rastrear pela transportadora do pedido.
            if (!is_null($integration_logistic)) {
                $logistic_type   = $integration_logistic;
                $logistic_seller = false;
            }

            if ($logistic_type) {
                try {
                    $this->calculofrete->instanceLogistic($logistic_type, $store['id'], $store, $logistic_seller);
                    // Se existe valor em integration_logistic na tabela orders
                    // Deve contratar o frete por essa integração e usar as credenciais dela.
                    if (!is_null($integration_logistic)) {
                        $this->calculofrete->logistic->setCredentialsSellerCenter();
                    }
                } catch (InvalidArgumentException | Exception $exception) {
                    // Se falhou para definir as credenciais do marketplace, tenta pelo seller.
                    if (
                        !is_null($integration_logistic) &&
                        in_array($exception->getMessage(), array(
                            'Falha para obter as credenciais do seller center.',
                            'Falha para obter as credenciais da loja.'
                        ))
                    ) {
                        $logistic_type   = $logistic_type_real;
                        $logistic_seller = $logistic_seller_real;
                        try {
                            $this->calculofrete->instanceLogistic($logistic_type, $store['id'], $store, $logistic_seller);
                        } catch (InvalidArgumentException | Exception $exception) {
                            if ($exception->getMessage() === 'Logística  não configurada') {
                                $logistic_type = null;
                            } else {
                                // informa para o pedido se aconteceu algum problema
                                $this->setProblemWithOrder($order['id'], $exception->getMessage());
                                continue;
                            }
                        }
                    } else {
                        // informa para o pedido se aconteceu algum problema
                        $this->setProblemWithOrder($order['id'], $exception->getMessage());
                        continue;
                    }
                }
            }

            try {
                // métodos para contratação do frete
                if ($logistic_type === 'intelipost') {
                    echo "frete do pedido {$order['id']} deverá ser contratado pela Intelipost\n";
                    $this->calculofrete->logistic->hireFreight($order, $store, $invoice, $client, $orderIntelipostWaitTrackingCode);
                }
                elseif ($logistic_type === 'freterapido') {
                    echo "frete do pedido {$order['id']} deverá ser contratado pela Freterapido\n";
                    $this->calculofrete->logistic->hireFreight($order, $store, $invoice, $client, $orderFRtWaitLabel);
                }
                elseif (!is_null($logistic_type) && in_array($logistic_type, $this->calculofrete->getTypesLogisticERP())) { // loja usa logística própria
                    echo "pedido {$order['id']} usa logística do ERP $logistic_type, vai pro status 40\n";
                    $this->model_orders->updatePaidStatus($order['id'], 40);
                }
                // Não encontrou uma integração com parâmetro diferentes.
                else {
                    // Não está configurado alguma logística e não vai pelos correios.
                    if (!$logistic_type) {
                        echo "Não tem integração, precisa contratar manualmente \n";
                        $this->model_orders->updatePaidStatus($order['id'], 101);
                    } else {
                        echo "frete do pedido {$order['id']} deverá ser contratado pela integração $logistic_type\n";
                        $this->calculofrete->logistic->hireFreight($order, $store, $invoice, $client);
                    }
                }
                // deixo os problema que tem inativo
                $this->model_orders_with_problem->inactiveProblemsOrder($order['id']);
            } catch (InvalidArgumentException $exception) {
                echo "Erro para contratar o frete do pedido $order[id]. Erro={$exception->getMessage()}\n";
                // informa para o pedido se aconteceu algum problema
                $this->setProblemWithOrder($order['id'], $exception->getMessage());
            }
		}
	}

    /**
     * Cria um erro para o pedido e deixo o pedido na situação de "Problema Contratação Frete".
     *
     * @param   int     $order_id       Código do pedido (orders.id)
     * @param   string  $description    Descrição do erro.
     */
    private function setProblemWithOrder(int $order_id, string $description)
    {
        $this->model_orders_with_problem->createProblem(array('order_id' => $order_id, 'description' => $description), 80);
    }
}
