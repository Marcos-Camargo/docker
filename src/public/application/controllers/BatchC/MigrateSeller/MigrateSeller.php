<?php

require_once APPPATH . "libraries/Helpers/StringHandler.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";
require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

/**
 * Class SellerV2
 * @property CI_Loader $load
 * @property Microservices\v1\Logistic\Shipping $ms_shipping
 * @property Model_settings $model_settings
 * @property Model_integrations $model_integrations
 * @property Model_stores $model_stores
 * @property Model_seller_migration_register $model_seller_migration_register
 * @property CI_Session $session
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 */
class MigrateSeller extends Main
{

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_stores');
        $this->load->model('model_settings');
        $this->load->model('model_integrations');
        $this->load->model('model_settings');
        $this->load->model('model_seller_migration_register');
        $this->load->model('model_queue_products_marketplace');

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );

        $this->session->set_userdata($logged_in_sess);

    }

    function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

        $stores_to_migrate = $this->model_seller_migration_register->getStoresToMigrate();
        if ($stores_to_migrate) {
            foreach ($stores_to_migrate as $store) {
                if (!is_null($store->store_id) && !is_null($store->int_to)) {
                    $this->syncEndIntegration($store->store_id, $store->int_to);
                    $this->sendProductsToQeue($store->store_id);
                }
            }
        }

        echo "Fim da rotina\n";

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    function syncEndIntegration($store_id, $int_to)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $store_integration_data = $this->model_integrations->getIntegrationbyStoreIdAndInto($store_id, $int_to);
        $integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $int_to);

        $data = array(
            'active' => 1,
            'auto_approve' => (int)$integration['auto_approve'],
        );
        $this->model_integrations->update($data, $store_integration_data['id']);
        $this->model_stores->update(['date_update' => date("Y-m-d H:i:s")], $store_integration_data['store_id']);
        $data = [
            'finish_date' => date("Y-m-d H:i:s"),
            'status' => 1
        ];
        $procura = " WHERE store_id = $store_id";
        $started_migration = $this->model_seller_migration_register->getData(null, $procura);
        if ($started_migration) {
            $this->model_seller_migration_register->update($data, $started_migration['id']);
        }
        echo PHP_EOL . "Loja: " . $store_id . " teve sua integração ativada" . PHP_EOL;

        return true;
    }

    public function sendProductsToQeue($store_id)
    {

        $limit = 400;
        $offset = 0;

        while (true) {
            $rowsToSendQueue = $this->db->select('0 as status, id as prd_id')
                ->where(['store_id' => $store_id, 'status !=' => 3])
                ->limit($limit, $offset)->get('products')->result_array();

            // Acabou os registros;
            if (empty($rowsToSendQueue)) {
                break;
            }

            $offset += $limit;

            while ($this->model_queue_products_marketplace->countQueue()['qtd'] > 400) {
                echo "Fila com muitos produtos, aguardar 30 segundos para checar novamente\n";
                sleep(30);
            }

            // Add os produtos na fila.
            foreach ($rowsToSendQueue as $queue_create) {
                $this->model_queue_products_marketplace->create($queue_create);
            }

            echo "Adicionado " . count($rowsToSendQueue) . " registros na fila.\n";
        }

    }

}