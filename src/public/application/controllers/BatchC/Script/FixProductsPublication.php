<?php

use Integration\Integration_v2\anymarket\AnyMarketApiException;
use Integration\Integration_v2\anymarket\ApiException;
require_once APPPATH . "libraries/Publish/Publishing.php";

use Publish\Publishing;

/**
 * @property Model_products $model_products
 * @property Model_stores $model_stores
 * @property Model_api_integrations $model_api_integrations
 * @property Model_users $model_users
 * @property CSV_Validation $csv_validation
 * @property Publishing $publishing
 */
class FixProductsPublication extends BatchBackground_Controller {
    private $toolsProduct;
    private $time_file_name = '';
    private $store_id = null;
    private $save_in_file = false;
    private $last_product_removed = 0;

    public function __construct()
    {
        parent::__construct();
        error_reporting(-1);
        ini_set('display_errors', 1);

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_products');
        $this->load->model('model_api_integrations');
        $this->load->model('model_users');
        $this->load->model('model_stores');
        $this->load->library('CSV_Validation');
        $this->time_file_name = dateNow()->format('His');
        $this->load->library("Publish\\Publishing", array(), 'publishing');

    }

    public function run($id, int $store_id, string $id_check = null, string $save_in_file = '0')
    {
        $this->setIdJob($id);
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store_id)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }

        if (empty($id_check)) {
           $id_check = null;
        }

        $this->save_in_file = (bool)$save_in_file;
        $this->store_id = $store_id;

        echo "[ INFO  ] START=".dateNow()->format(DATETIME_INTERNATIONAL)."\n";
        echo "[ INFO  ] IP=".exec('hostname -I')."\n";
        $data_integration = $this->model_api_integrations->getIntegrationByStore($store_id);
        $data_store = $this->model_stores->getStoresById($store_id);

        if($data_store["active"] != "1"){
            echo "Loja não está ativa $store_id. (stores)\n";
            return;
        }

        if (!$data_integration) {
            echo "Integração não encontrada para a loja $store_id. (api_integrations)\n";
            return;
        }

        if (likeText('viavarejo_b2b%', $data_integration['integration'])) {
            $data_integration['integration'] = 'viavarejo_b2b';
        }

        if ($data_integration['integration'] != 'anymarket') {
            echo "Módulo construído somente para anymarket\n";
            return;
        }

        require APPPATH . "libraries/Integration_v2/$data_integration[integration]/ToolsProduct.php";
        $instance = "Integration\Integration_v2\\$data_integration[integration]\ToolsProduct";
        $this->toolsProduct = new $instance($this);

        $user = $this->model_users->getUserByEmail('pedrohenrique@conectala.com.br');
        if (!empty($user[0])) {
            $this->toolsProduct->user_id_to_debug = $user[0]['id'];
        }

        $this->toolsProduct->startRun($store_id);

        if (!method_exists($this->toolsProduct, 'getProductsBySku')) {
            echo "Método 'getProductsBySku' inexistente\n";
            return;
        }

        $limit = 5000;
        $last_id = 0;

        while(true) {
            $products = $this->db->query("SELECT p.id, p.status, pti.status, pti.int_to, a.prd_id  
            from anymarket_log_fix_id a 
            join prd_to_integration pti on a.prd_id = pti.prd_id 
            join products p on a.prd_id = p.id 
            where pti.status = ? and p.status != ? and p.store_id = ? and p.id > ?
            group by pti.prd_id, pti.int_to 
            limit ?", [0, 3, $store_id, $last_id, $limit])->result_array();
            echo "[ INFO  ] LAST_ID=$last_id | ".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

            if (count($products) == 0) {
                break;
            }

            foreach ($products as $product) {
                if (!is_null($id_check) && $id_check != $product['id']) {
                    echo "[WARNING] ID=$product[id] Ignorar\n";
                    continue;
                }
                echo "ativando a publicação: ".$product['id']."\n";
                $last_id = $product['id'];
                $this->publishing->setPublish($product['id'], [$product['int_to']], 1, True);
            }
        }
        echo "[ INFO  ] END=".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

        $this->gravaFimJob();
    }
  
}