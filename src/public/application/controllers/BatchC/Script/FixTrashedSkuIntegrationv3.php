<?php

use Integration\Integration_v2\anymarket\AnyMarketApiException;
use Integration\Integration_v2\anymarket\ApiException;

/**
 * @property Model_products $model_products
 * @property Model_api_integrations $model_api_integrations
 * @property Model_users $model_users
 * @property CSV_Validation $csv_validation
 */
class FixTrashedSkuIntegrationv3 extends BatchBackground_Controller {
    private $toolsProduct;
    private $products_processed = [];

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

        $this->job1($store_id, $sku_check);
        $this->job2($store_id, $sku_check);

        $this->gravaFimJob();
    }

    private function job1(int $store_id, $sku_check)
    {
        echo "[ START  ] Job 1 END=".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

        $last_id = 0;

        while(true) {
            $products = $this->db->select('alfi.*, p.has_variants, p.image')
                ->join('products AS p', 'p.id = alfi.prd_id')
                ->where('p.sku', '')
                ->where('p.status !=', 3)
                ->where('alfi.sku !=', '')
                ->where('alfi.store_id', $store_id)
                ->group_by('alfi.prd_id')
                ->get('anymarket_log_fix_id AS alfi')
                ->result_array();

            echo "[ INFO  ] LAST_ID=$last_id | ".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

            if (count($products) == 0) {
                break;
            }

            foreach ($products as $product) {
                if (in_array($product['prd_id'], $this->products_processed)) {
                    echo "[WARNING] ID=$product[id] | PRD_ID=$product[prd_id] já processado\n";
                    continue;
                }
                $this->products_processed[] = $product['prd_id'];

                $skus_transmission = [];
                $last_id = $product['id'];
                $sku     = $product['sku_pai'] ?: $product['sku'];
                $sku = $this->getCleanSkuTrashed($sku);

                if (!is_null($sku_check) && $sku_check != $sku) {
                    echo "[WARNING] ID=$product[id] Ignorar\n";
                    continue;
                }

                echo "[ INFO  ] ID=$product[id] | SKU=$sku \n";

                $existing_product = $this->model_products->getProductData(0, $product['prd_id']);

                if (!$existing_product) {
                    echo "[ ERROR ] ID=$product[id] produto não encontrado com PRD_ID=$product[prd_id]\n";
                    continue;
                }

                if ($this->model_products->getProductBySkuAndStore($sku, $store_id)) {
                    echo "[ ERROR ] ID=$product[id] produto com o sku $sku já existe para o PRD_ID=$product[prd_id]\n";
                    continue;
                }

                // Atualiza sku do pai
                $this->model_products->update([
                    'status' => $this->model_products::ACTIVE_PRODUCT,
                    'sku'   => $this->getCleanSkuTrashed($sku)
                ], $product['prd_id']);

                $variations = $this->model_products->getVariantsByProd_id($existing_product['id']);

                if (empty($trashed_product['has_variants'])) {
                    $skus_transmission[] = $sku;
                }

                foreach ($variations as $variation) {
                    if (!empty($variation['sku'])) {
                        continue;
                    }

                    $var_sku = str_replace('-PRD', '', $this->getCleanSkuTrashed($product['sku']));
                    $existing_variation = $this->model_products->getProductByVarSkuAndStore($var_sku, $store_id);

                    // Variação existe
                    if ($existing_variation) {
                        echo "[ ERROR ] ID=$product[id] variação com o sku $var_sku já existe para o PRD_ID=$product[prd_id]\n";
                    } else {
                        $skus_transmission[] = $var_sku;
                        $this->model_products->updateVariationData($variation['id'], $product['prd_id'], ['sku' => $var_sku]);
                    }
                }

                $error_found = $this->sendNotification($skus_transmission);

                if ($product['image'] == 'trash') {
                    try {
                        $this->toolsProduct->trashProduct($sku);
                        echo "[SUCCESS] ID=$product[id] Produto $product[prd_id] enviado para a lixeira\n";
                    } catch (InvalidArgumentException $exception) {
                        $error_message = $exception->getMessage();
                        echo "[ ERROR ] ID=$product[id] Não foi possível enviar o produto pra lixeira: $product[prd_id]. $error_message\n";
                    }
                }

                if ($error_found) {
                    echo "[WARNING] ID=$product[id] Erro encontrado $error_found\n";
                }
                echo "[SUCCESS] ID=$product[id] Bem sucedido\n";
            }
        }
        echo "[ INFO  ] Job 1 END=".dateNow()->format(DATETIME_INTERNATIONAL)."\n";
    }

    private function job2(int $store_id, $sku_check)
    {
        echo "[ INFO  ] Job 2 START=".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

        $last_id = 0;

        while(true) {
            $products = $this->db->select('alfi.*, p.has_variants, p.image')
                ->join('products AS p', 'p.id = alfi.existing')
                ->where('p.sku', '')
                ->where('p.status !=', 3)
                ->where('alfi.sku !=', '')
                ->where('alfi.store_id', $store_id)
                ->where('alfi.existing is not null', null, false)
                ->group_by('alfi.existing')
                ->get('anymarket_log_fix_id AS alfi')
                ->result_array();

            echo "[ INFO  ] LAST_ID=$last_id | ".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

            if (count($products) == 0) {
                break;
            }

            foreach ($products as $product) {
                if (in_array($product['existing'], $this->products_processed)) {
                    echo "[WARNING] ID=$product[id] | PRD_ID=$product[existing] já processado\n";
                    continue;
                }
                $this->products_processed[] = $product['existing'];

                $skus_transmission = [];
                $last_id = $product['id'];
                $sku     = $product['sku_pai'] ?: $product['sku'];
                $sku = $this->getCleanSkuTrashed($sku);

                if (!is_null($sku_check) && $sku_check != $sku) {
                    echo "[WARNING] ID=$product[id] Ignorar\n";
                    continue;
                }

                echo "[ INFO  ] ID=$product[id] | SKU=$sku \n";

                $existing_product = $this->model_products->getProductData(0, $product['existing']);

                if (!$existing_product) {
                    echo "[ ERROR ] ID=$product[id] produto não encontrado com PRD_ID=$product[existing]\n";
                    continue;
                }

                if ($this->model_products->getProductBySkuAndStore($sku, $store_id)) {
                    echo "[ ERROR ] ID=$product[id] produto com o sku $sku já existe para o PRD_ID=$product[existing]\n";
                    continue;
                }

                // Atualiza sku do pai
                $this->model_products->update([
                    'status' => $this->model_products::ACTIVE_PRODUCT,
                    'sku'   => $this->getCleanSkuTrashed($sku)
                ], $product['existing']);

                $variations = $this->model_products->getVariantsByProd_id($existing_product['id']);

                if (empty($trashed_product['has_variants'])) {
                    $skus_transmission[] = $sku;
                }

                foreach ($variations as $variation) {
                    if (!empty($variation['sku'])) {
                        continue;
                    }

                    $var_sku = str_replace('-PRD', '', $this->getCleanSkuTrashed($product['sku']));
                    $existing_variation = $this->model_products->getProductByVarSkuAndStore($var_sku, $store_id);

                    // Variação existe
                    if ($existing_variation) {
                        echo "[ ERROR ] ID=$product[id] variação com o sku $var_sku já existe para o PRD_ID=$product[existing]\n";
                    } else {
                        $skus_transmission[] = $var_sku;
                        $this->model_products->updateVariationData($variation['id'], $product['prd_id'], ['sku' => $var_sku]);
                    }
                }

                $error_found = $this->sendNotification($skus_transmission);

                if ($product['image'] == 'trash') {
                    try {
                        $this->toolsProduct->trashProduct($sku);
                        echo "[SUCCESS] ID=$product[id] Produto $product[existing] enviado para a lixeira\n";
                    } catch (InvalidArgumentException $exception) {
                        $error_message = $exception->getMessage();
                        echo "[ ERROR ] ID=$product[id] Não foi possível enviar o produto pra lixeira: $product[existing]. $error_message\n";
                    }
                }

                if ($error_found) {
                    echo "[WARNING] ID=$product[id] Erro encontrado $error_found\n";
                }
                echo "[SUCCESS] ID=$product[id] Bem sucedido\n";
            }
        }
        echo "[ INFO  ] Job 2 END=".dateNow()->format(DATETIME_INTERNATIONAL)."\n";
    }

    private function sendNotification(array $skus_transmission)
    {
        $total_attempt = 5;
        $error_found = false;
        foreach ($skus_transmission as $sku_transmission) {
            $count_attempt = 1;
            while (true) {
                try {
                    $response = $this->toolsProduct->getProductsBySku($sku_transmission);
                    try {
                        $body = [
                            "idSkuMarketplace" => $response['id'],
                            "idSkuMarketplaceMain" => $response['id'],
                            "status" => 'ACTIVE',
                            "onlySync" => true,
                            "idSku" => $response['sku']['id'],
                            "availableAmount" => $response['stock']['availableAmount'],
                            "idAccount" => $response['idAccount'],
                            "idProduct" => $response['sku']['product']['id'],
                            'sent_from_trash' => true
                        ];

                        $this->toolsProduct->sendProductToNotification($body);
                    } catch (ApiException $exception) {
                        throw new Exception("Erro ao enviar notificação: {$exception->getMessage()}");
                    }
                    break;
                } catch (AnyMarketApiException|ApiException|InvalidArgumentException|Exception|Error $exception) {
                    $error_message = $exception->getMessage();
                    $notification_error = likeTextNew('%401 Unauthorized%', $error_message) && likeTextNew('%conectala.tec.br%', $error_message);

                    if (
                        $count_attempt > $total_attempt ||
                        likeTextNew('%Não retornou conteúdo%', $exception->getMessage()) ||
                        ($notification_error && $count_attempt > 2)
                    ) {
                        $error_found = $exception->getMessage();
                        break;
                    }
                    echo "[ INFO  ] Tentativa de envio de notificação de [$count_attempt/$total_attempt]. {$exception->getMessage()}\n";
                    $count_attempt++;
                    sleep(2);
                }
            }

            return $error_found;
        }
    }

    private function getCleanSkuTrashed(string $sku): string
    {
        if (
            likeTextNew('DEL_%', $sku) &&
            count(explode('_', $sku)) >= 4
        ) {
            $exp_partner_id = explode('_', $sku);
            array_pop($exp_partner_id);
            array_pop($exp_partner_id);
            array_shift($exp_partner_id);
            return implode('_', $exp_partner_id);
        }

        return $sku;
    }
}