<?php

use Integration\Integration_v2\anymarket\AnyMarketApiException;
use Integration\Integration_v2\anymarket\ApiException;

/**
 * @property Model_products $model_products
 * @property Model_api_integrations $model_api_integrations
 * @property Model_users $model_users
 * @property CSV_Validation $csv_validation
 */
class FixTrashedSkuIntegration extends BatchBackground_Controller {
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

        $limit = 5000;
        $last_id = 0;

        while(true) {
            $products = $this->model_products->getTrashedProductByStoreId($store_id, $last_id, $limit);
            echo "[ INFO  ] LAST_ID=$last_id | ".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

            if (count($products) == 0) {
                break;
            }

            foreach ($products as $product) {
                $skus_transmission = [];
                $last_id = $product['id'];
                $sku     = $product['sku_pai'] ?: $product['sku'];

                if (!is_null($sku_check) && $sku_check != $sku) {
                    echo "[WARNING] ID=$product[id] Ignorar\n";
                    continue;
                }

                echo "[ INFO  ] ID=$product[id] | SKU=$sku \n";

                $existing_product = $this->model_products->getProductBySkuAndStore($sku, $store_id);

                if ($existing_product) {
                    echo "[ INFO  ] ID=$product[id] produto existente com ID=$existing_product[id]\n";
                    $this->db->update('anymarket_log_fix_id', ['existing' => $existing_product['id']], ['id' => $product['id']]);
                    continue;
                }

                $trashed_product = $this->model_products->getProductData(0, $product['prd_id']);

                if (!$trashed_product) {
                    echo "[ ERROR ] Produto do lixo não encontrado ({$product['prd_id']})\n";
                    continue;
                }

                $variations = $this->model_products->getVariantsByProd_id($trashed_product['id']);

                foreach ($variations as $variation) {
                    $existing_variation = $this->model_products->getProductByVarSkuAndStore($this->getCleanSkuTrashed($variation['sku']), $store_id);

                    // Variação existe
                    if ($existing_variation) {
                        echo "[ INFO  ] ID=$product[id] variação existente com PRD_ID=$existing_variation[id]\n";
                        $this->db->update('anymarket_log_fix_id', ['existing' => $existing_variation['id']], ['id' => $product['id']]);
                        continue 2;
                    }
                }

                // alterar produto pai para ativo.
                $this->model_products->update([
                    'status' => $this->model_products::ACTIVE_PRODUCT,
                    'sku'   => $this->getCleanSkuTrashed($trashed_product['sku'])
                ], $trashed_product['id']);

                if (!empty($trashed_product['has_variants'])) {
                    $variations = $this->model_products->getVariantsByProd_id($trashed_product['id']);

                    foreach ($variations as $variation) {
                        $sku_variation = $this->getCleanSkuTrashed($variation['sku']);
                        $this->model_products->updateVariationData($variation['id'], $trashed_product['id'], [
                            'status' => $this->model_products::ACTIVE_PRODUCT,
                            'sku'   => $sku_variation
                        ]);

                        $skus_transmission[] = $sku_variation;
                    }
                } else {
                    $skus_transmission[] = $sku;
                }

                $this->db->update('anymarket_log_fix_id', ['copied' => true], ['id' => $product['id']]);

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
                                $this->db->update('anymarket_log_fix_id', ['copied_error' => $exception->getMessage()], ['id' => $product['id']]);
                                $error_found = $exception->getMessage();
                                break;
                            }
                            echo "[ INFO  ] Tentativa de envio de notificação de [$count_attempt/$total_attempt]. {$exception->getMessage()}\n";
                            $count_attempt++;
                            sleep(2);
                        }
                    }
                }

                if ($error_found) {
                    echo "[WARNING] ID=$product[id] Erro encontrado $error_found\n";
                }
                echo "[SUCCESS] ID=$product[id] Bem sucedido\n";
            }
        }
        echo "[ INFO  ] END=".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

        $this->gravaFimJob();
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