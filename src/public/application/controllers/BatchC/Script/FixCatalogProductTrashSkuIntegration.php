<?php

use Integration\Integration_v2\anymarket\AnyMarketApiException;
use Integration\Integration_v2\anymarket\ApiException;

/**
 * @property Model_products $model_products
 * @property Model_api_integrations $model_api_integrations
 * @property Model_users $model_users
 * @property CSV_Validation $csv_validation
 */
class FixCatalogProductTrashSkuIntegration extends BatchBackground_Controller {
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
        $this->load->library('CSV_Validation');
        $this->time_file_name = dateNow()->format('His');
    }

    public function run($id, int $store_id, string $sku_check = null, string $save_in_file = '0')
    {
        $this->setIdJob($id);
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store_id)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }

        if (empty($sku_check)) {
            $sku_check = null;
        }

        $this->save_in_file = (bool)$save_in_file;
        $this->store_id = $store_id;

        echo "[ INFO  ] START=".dateNow()->format(DATETIME_INTERNATIONAL)."\n";
        echo "[ INFO  ] IP=".exec('hostname -I')."\n";
        $data_integration = $this->model_api_integrations->getIntegrationByStore($store_id);

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

        $this->job1($store_id);
//        $this->job2($store_id);

        echo "[ INFO  ] END=".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

        $this->gravaFimJob();
    }

    private function job1(int $store_id)
    {
        echo "[ INFO  ] job1 | ".dateNow()->format(DATETIME_INTERNATIONAL)."\n";
        $limit = 500;
        $last_id = 0;

        while(true) {
            $products = $this->db->select('alfi.*, p.sku as product_sku')
                ->join('products AS p', 'p.id = alfi.prd_id')
                ->where('alfi.copied', 1)
                ->where('p.status !=', 3)
                ->where('p.image !=', 'trash')
                ->where('alfi.id >', $last_id)
                ->where('alfi.store_id', $store_id)
                ->where('alfi.created_at <', '2025-06-24 00:00:00')
                ->get('anymarket_log_fix_id AS alfi', $limit)
                ->result_array();

            echo "[ INFO  ] LAST_ID=$last_id | ".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

            if (count($products) == 0) {
                break;
            }

            foreach ($products as $product) {
                $last_id = $product['id'];
                $this->deleteSku($product);
            }
        }
    }

    private function job2(int $store_id)
    {
        echo "[ INFO  ] job2 | ".dateNow()->format(DATETIME_INTERNATIONAL)."\n";
        $limit = 500;
        $last_id = 0;

        while(true) {
            $products = $this->db->select('alfi.id, p.id as prd_id, p.sku as product_sku')
                ->join('products AS p', 'p.id = alfi.prd_id')
                ->join('api_integrations AS ai', "ai.store_id = p.store_id AND ai.integration = 'anymarket'")
                ->where('p.product_catalog_id IS NULL', null, false)
                ->where('p.status !=', 3)
                ->where('p.image !=', 'trash')
                ->where('p.id >', $last_id)
                ->where('p.store_id', $store_id)
                ->where('p.date_update >', '2025-06-24 00:00:00')
                ->where('alfi.copied', 1)
                ->group_by('alfi.prd_id')
                ->order_by('p.id')
                ->get('anymarket_log_fix_id AS alfi', $limit)
                ->result_array();

            echo "[ INFO  ] LAST_ID=$last_id | ".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

            if (count($products) == 0) {
                break;
            }

            foreach ($products as $product) {
                $last_id = $product['id'];
                $this->deleteSku($product);
            }
        }
    }

    private function deleteSku(array $product_data)
    {
        try {
            $this->toolsProduct->trashProduct($product_data['product_sku']);
            $data = array(
                'prd_id'                => $product_data['prd_id'],
                'sku'                   => $product_data['product_sku'],
                'sku_pai'               =>  '',
                'id_any_integration'    =>  '',
                'id_any_sellercenter'   =>  '',
                'variant'               =>  '',
                'error'                 => "Imagem com erro do nome da pasta",
                'store_id'              => $this->store_id
            );
            $this->db->insert('anymarket_log_fix_id', $data);
            echo "[SUCCESS] $product_data[prd_id]\n";
        } catch (InvalidArgumentException $exception) {
            $error_message = $exception->getMessage();
            echo "Não foi possível enviar o produto pra lixeira: ".$product_data['prd_id'].". $error_message\n";
            $data = array(
                'prd_id'                => $product_data['prd_id'],
                'sku'                   => $product_data['product_sku'],
                'sku_pai'               =>  '',
                'id_any_integration'    =>  '',
                'id_any_sellercenter'   =>  '',
                'variant'               =>  '',
                'error'                 => "Não foi possível enviar o produto pra lixeira: ".$product_data['prd_id'] . ". $error_message",
                'store_id'              => $this->store_id
            );
            $this->db->insert('anymarket_log_fix_id', $data);
        }
    }
}