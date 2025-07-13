<?php
/*

Verifica como estão as ocorrência de frete  no frete rápido

*/

require_once "application/libraries/CalculoFrete.php";

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_DB_driver $db
 * @property CI_Router $router
 *
 * @property Model_orders $model_orders
 * @property Model_freights $model_freights
 * @property Model_stores $model_stores
 * @property Model_settings $model_settings
 *
 * @property CalculoFrete $calculofrete
 */

class FreteRastrear extends BatchBackground_Controller {

    public $frete;
    public $sellerCenter;

    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');
        
        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        
        // carrega os módulos necessários para o Job.
        $this->load->model('model_orders');
        $this->load->model('model_freights');
        $this->load->model('model_stores');
        $this->load->model('model_settings');

        $this->load->library('calculoFrete');
    }
    
    public function run($id=null,$params=null)
    {
        /* inicia o job */
        $log_name = __CLASS__.'/'.__FUNCTION__;
        $this->setIdJob($id);
        if (!$this->gravaInicioJob(__CLASS__,__FUNCTION__)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params));
        
        // pego somente o fretes em aberto
        $fretes =$this->model_freights->getFreightsOpen();
        //var_dump($fretes);
        if (!isset($fretes)) {
            $this->log_data('batch',$log_name,'Nenhum frete em andamento');
            $this->gravaFimJob();
            return;
        }

        $arrCodesSgpweb = array();
        $arrCodesCorreios = array();
        $type_contract_logistic_by_seller = [];

        foreach ($fretes as $frete) {
            $integration_logistic = $frete['integration_logistic'];

            //pego as informações do pedido.
            $order = $this->model_orders->getOrdersData(0,$frete['order_id']);

            if (!$order) {
                echo "Pedido $frete[order_id] não encontrado.\n";
                continue;
            }

            // pego as informações da loja.
            $store = $this->model_stores->getStoresData($order['store_id']);

            if (!$store) {
                echo "Loja $order[store_id] do pedido $frete[order_id] não encontrado.\n";
                continue;
            }

            // loja usa logística própria.
            $logistic = $this->calculofrete->getLogisticStore(array(
                'freight_seller' 		=> $store['freight_seller'],
                'freight_seller_type' 	=> $store['freight_seller_type'],
                'store_id'				=> $store['id']
            ));

            $type_contract_logistic_by_seller[$order['store_id']] = ($logistic['seller'] ?? false) ? 'seller' : 'sellercenter';

            // A logística ainda é a mesma, não precisa trocar.
            if ($logistic['type'] == $integration_logistic && $logistic['sellercenter']) {
                $integration_logistic = null;
            }

            if (is_null($integration_logistic) && in_array($logistic['type'], $this->calculofrete->getTypesLogisticERP())) { // loja usa logística própria
                echo "pedido {$order['id']} usa logística da integradora: {$logistic['type']}, deve mudar para status de aguardando dado externo\n";

                if ($order['paid_status'] == 53 || $order['paid_status'] == 4) {
                    $this->model_orders->updatePaidStatus($order['id'], 43);
                } elseif ($order['paid_status'] == 5) {
                    $this->model_orders->updatePaidStatus($order['id'], 45);
                }
                continue;

            }

            echo 'fretes da ordem id '.$frete['order_id'].' rastreio '.$frete['codigo_rastreio']."\n";

            // Frete do Sgpweb. Apenas guardamos o rastreio para fazer apenas uma única consulta de todos os objetos.
            if ($frete['sgp'] == 1) {
                if (strlen($frete['codigo_rastreio']) == 13) {
                    $arrCodesSgpweb[] = $frete['codigo_rastreio'];
                }
                continue;
            }
            if ($frete['sgp'] == 7) {
                if (strlen($frete['codigo_rastreio']) == 13) {
                    $arrCodesCorreios[$order['store_id']][] = $frete['codigo_rastreio'];
                }
                continue;
            }
          
            // Frete avulso, sem rastreio.
            if (is_null($integration_logistic) && $frete['sgp'] == 3) {
                if ($order['paid_status']==53)  { // Se estiver ainda no estado de início de rastreio mudo para aguardando coleta.
                    $order['paid_status'] = 4;  // Aguardando Coleta
                    $this->model_orders->updateByOrigin($order['id'],$order);
                }
                continue;
            }

            // Panex ainda não está configurada como uma integradora logística, foi feita apenas rastreamento.
            if ($frete['sgp'] == 6) {
                $logistic['type'] = 'panex';
            }

            $logistic_type   = $logistic['type'];
            $logistic_seller = $logistic['seller'];

            // A logística é do seller center
            // Se está diferente da atual
            // Deve rastrear pela transportadora do pedido.
            if (!is_null($integration_logistic)) {
                $logistic_type   = $integration_logistic;
                $logistic_seller = false;
            }

            $attempt_limit = 0;
            while (true) {
                $attempt_limit++;
                if ($attempt_limit > 5) {
                    echo "Tentou mais que 5 vezes, será interrompido. Pedido: {$order['id']}. Rastreio: {$frete['codigo_rastreio']}. Integrador: $logistic_type.\n";
                    break;
                }
                try {
                    echo "Frete será rastreado por $logistic_type. Pedido: {$order['id']}. Rastreio: {$frete['codigo_rastreio']}\n";

                    $this->calculofrete->instanceLogistic($logistic_type, $store['id'], $store, $logistic_seller);
                    // Se existe valor em integration_logistic na tabela orders
                    // Deve rastrear por essa integração e usar as credenciais dela.
                    if (!is_null($integration_logistic)) {
                        $this->calculofrete->logistic->setCredentialsSellerCenter();
                    }
                    $this->calculofrete->logistic->tracking($order, $frete);
                    break;
                } catch (InvalidArgumentException $exception) {
                    $message = $exception->getMessage();
                    if (likeText('%Too Many Requests%', $message)) {
                        echo "Too Many Requests. Esperar 10s para a próxima tentativa. Pedido: {$order['id']}. Rastreio: {$frete['codigo_rastreio']}. Integrador: $logistic_type. $message\n";
                        sleep(10);
                        continue;
                    }

                    echo "Ocorreu um problema para realizar o rastreio do pedido: {$order['id']}. Rastreio: {$frete['codigo_rastreio']}. Integrador: $logistic_type\n$message\n";
                    break;
                }
            }
        }


        // sgpweb, pego todos os objetos e rastreio tudo junto
        if (count($arrCodesSgpweb)) {
            try {
                $this->calculofrete->instanceLogistic('sgpweb', 0, array(), false);
                $this->calculofrete->logistic->tracking($arrCodesSgpweb);
            } catch (InvalidArgumentException $exception) {
                echo "Ocorreu um problema para realizar o rastreio do sgpweb. \nRastreios:".json_encode($arrCodesSgpweb)."\n";
            }
        }

        // correios, pego todos os objetos e rastreio tudo junto
        if (count($arrCodesCorreios)) {
            foreach ($arrCodesCorreios as $store_id => $arrCodesCorreio) {
                $store = $this->model_stores->getStoresData($store_id);
                try {
                    $this->calculofrete->instanceLogistic('correios', $store_id, $store, $type_contract_logistic_by_seller[$store['id']] == 'seller');
                    $this->calculofrete->logistic->tracking($arrCodesCorreio);
                } catch (InvalidArgumentException $exception) {
                    echo "Ocorreu um problema para realizar o rastreio do correios. \nRastreios:" . json_encode($arrCodesCorreios) . ". {$exception->getMessage()}\n";
                }
            }
        }

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish');
        $this->gravaFimJob();
    }
}
