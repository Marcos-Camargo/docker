<?php

use Integration\Integration_v2\anymarket\AnyMarketApiException;
use Integration\Integration_v2\anymarket\ApiException;

/**
 * @property Model_products $model_products
 * @property Model_api_integrations $model_api_integrations
 * @property Model_users $model_users
 * @property CSV_Validation $csv_validation
 */
class UpdateSkuInIntegration extends BatchBackground_Controller {
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

    public function run($id, int $store_id, string $sku = null, string $save_in_file = '0')
    {
        $this->setIdJob($id);
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store_id)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }

        if (empty($sku)) {
            $sku = null;
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
            $products = $this->model_products->getProductsActiveByStoreAndLastId($store_id, $last_id, $limit, $sku);
            echo "[ INFO  ] LAST_ID=$last_id | ".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

            if (count($products) == 0) {
                break;
            }

            foreach ($products as $product) {
                $last_id = $product['id'];
                $this->validateSku($product, $product, true);
                if (!empty($product['has_variants'])) {
                    $variations = $this->model_products->getVariantsByProd_id($product['id']);
                    foreach ($variations as $variation) {
                        if ($variation['status'] != 3) {
                            $this->validateSku($product, $variation, false);
                        }
                    }
                }
            }
        }
        echo "[ INFO  ] END=".dateNow()->format(DATETIME_INTERNATIONAL)."\n";

        $this->gravaFimJob();
    }

    private function validateSku(array $product_data, array $variant_data, bool $is_product)
    {
        $id_erp_in_sellercenter = $is_product ? $variant_data['product_id_erp'] : $variant_data['variant_id_erp'];
        $id_erp_in_integration = '';
        $request_secondary_successfuly = false;
        try {
            $response = $this->toolsProduct->getProductsBySku($variant_data['sku']);
            try {
                $sku_integration = $this->toolsProduct->getDataFormattedToIntegration($response);
            } catch (AnyMarketApiException | ApiException | InvalidArgumentException | Exception | Error $exception) {
                $error_message = $exception->getMessage();

                if (!empty($error_message) && likeTextNew('%Erro ao obter o%', $error_message)) {
                    try {
                        sleep(5);
                        echo "[ INFO  ] Aguardando 5s para tentar consulta o sku novamente. [$error_message]\n";
                        $sku_integration = $this->toolsProduct->getDataFormattedToIntegration($response);
                        $request_secondary_successfuly = true;
                    } catch (AnyMarketApiException|ApiException|InvalidArgumentException|Exception|Error $exception) {
                        $error_message = $exception->getMessage();
                    }
                }
                if (!$request_secondary_successfuly) {
                    if (!likeTextNew('%existe um produto com sku igual no Seller Center%', $error_message)) {
                        throw new Exception($error_message);
                    }
                    $sku_integration = array(
                        'variations' => array(),
                        '_product_id_erp' => array('value' => $response['sku']['product']['id'])
                    );

                    if (!empty($response['sku']['product']['hasVariations'])) {
                        $sku_integration['variations']["value"][0]['_variant_id_erp'] = $response['sku']['id'];
                    }
                }
            }

            if (property_exists($this->toolsProduct, 'parsedFullProduct') && !empty($this->toolsProduct->parsedFullProduct)) {
                $sku_integration = $this->toolsProduct->parsedFullProduct;
            }

            if (!$is_product && empty($sku_integration['variations']['value'][0])) {
                throw new Exception("Não identificou as variações da variação $variant_data[sku] e Produto $product_data[sku]");
            }

            $id_erp_in_integration = $is_product ? $sku_integration['_product_id_erp']['value'] : $sku_integration['variations']["value"][0]['_variant_id_erp'];

            $skuInMarketplace = null;
            if ($id_erp_in_sellercenter == $id_erp_in_integration) {
                $sku_check = str_replace('-PRD', '', $variant_data['sku']);
                if ($this->toolsProduct->announcementData->skuInMarketplace != $sku_check) {
                    $skuInMarketplace = $this->toolsProduct->announcementData->skuInMarketplace;
                    echo "[ INFO  ] Skus divergentes: Any={$this->toolsProduct->announcementData->skuInMarketplace} | SC=$sku_check\n";
                    /*try {
                        $body = [
                            "idSkuMarketplace"      => $response['id'],
                            "idSkuMarketplaceMain"  => $response['id'],
                            "status"                => 'ACTIVE',
                            "onlySync"              => true,
                            "idSku"                 => $response['sku']['id'],
                            "availableAmount"       => $response['stock']['availableAmount'],
                            "idAccount"             => $response['idAccount'],
                            "idProduct"             => $response['sku']['product']['id']
                        ];

                        $this->toolsProduct->sendProductToNotification($body);
                    } catch (ApiException $exception) {}*/
                } else {
                    return;
                }
            }

            try {
                $this->saveToFile($product_data, $variant_data, $id_erp_in_integration, $id_erp_in_sellercenter, $is_product, 'divergent_codes', $skuInMarketplace);
            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }

            /*if ($is_product) {
                $this->toolsProduct->updateProductIdIntegration($product_data['sku'], $id_erp_in_integration);
            } else {
                $this->toolsProduct->updateProductIdIntegration($product_data['sku'], $id_erp_in_integration, $variant_data['sku']);
            }*/

            try {
                $body = [
                    "idSkuMarketplace"      => $response['id'],
                    "idSkuMarketplaceMain"  => $response['id'],
                    "status"                => 'ACTIVE',
                    "onlySync"              => true,
                    "idSku"                 => $response['sku']['id'],
                    "availableAmount"       => $response['stock']['availableAmount'],
                    "idAccount"             => $response['idAccount'],
                    "idProduct"             => $response['sku']['product']['id']
                ];

                $this->toolsProduct->sendProductToNotification($body);
            } catch (ApiException $exception) {
                throw new Exception("Erro ao enviar notificação: {$exception->getMessage()}");
            }

            echo "[SUCCESS] {$variant_data['sku']}\n";
        } catch (AnyMarketApiException | ApiException | InvalidArgumentException | Exception | Error $exception) {
            $error_message = $exception->getMessage();

            try {
                $this->saveToFile($product_data, $variant_data, $id_erp_in_integration, $id_erp_in_sellercenter, $is_product, $error_message);
            } catch (Exception $exception) {
                $error_message .= " | {$exception->getMessage()}";
            }
            echo "[ ERROR ][$variant_data[sku]] $error_message\n";
        }
    }

    /**
     * @param array $product_data
     * @param array $variant_data
     * @param string|null $id_erp_in_integration
     * @param string|null $id_erp_in_sellercenter
     * @param bool $is_product
     * @param string $error_message
     * @param string|null $skuInMarketplace
     * @return void
     * @throws Exception
     */
    private function saveToFile(array $product_data, array $variant_data, ?string $id_erp_in_integration, ?string $id_erp_in_sellercenter, bool $is_product, string $error_message = '', string $skuInMarketplace = null)
    {
        $data = array(
            'prd_id'                => $product_data['id'],
            'sku'                   => $variant_data['sku'],
            'sku_pai'               => !$is_product ? $product_data['sku'] : '',
            'id_any_integration'    => $id_erp_in_integration,
            'id_any_sellercenter'   => $id_erp_in_sellercenter,
            'variant'               => !$is_product ? $variant_data['variant'] : '',
            'error'                 => $error_message,
            'store_id'              => $this->store_id
        );

        $this->deleteSku($product_data, $variant_data, $data);

        if (!empty($skuInMarketplace)) {
            $new_product_to_delete = $this->model_products->getProductBySkuAndStore($skuInMarketplace, $this->store_id);
            if (empty($new_product_to_delete)) {
                $new_variant_to_delete = $this->model_products->getProductsBySkuVariantAndStore($skuInMarketplace, (int)$this->store_id);
                if ($new_variant_to_delete) {
                    $this->deleteSku($new_variant_to_delete, $new_variant_to_delete, $data);
                    $data['error'] .= "|REMOVE_DUPLICATED_PRODUCT=$new_variant_to_delete[sku]";
                    echo "[ INFO  ] REMOVE_DUPLICATED_PRODUCT=$new_variant_to_delete[sku]\n";
                }
            } else {
                $this->deleteSku($new_product_to_delete, $new_product_to_delete, $data);
                $data['error'] .= "|REMOVE_DUPLICATED_PRODUCT=$new_product_to_delete[sku]";
                echo "[ INFO  ] REMOVE_DUPLICATED_PRODUCT=$new_product_to_delete[sku]\n";
            }
        }

        $this->db->insert('anymarket_log_fix_id', $data);

        if ($this->save_in_file) {
            $result = array(
                $data
            );
            $dir_name = "assets/files/fix_integration_id/$product_data[store_id]";
            $file_name = "$dir_name/$this->time_file_name.csv";

            if (!file_exists($file_name)) {
                checkIfDirExist($dir_name);
                $this->csv_validation->createNewFileCsv($file_name, $result);
            } else {
                $this->csv_validation->insertLinesInTheFile($file_name, $result);
            }
        }
    }

    private function deleteSku(array $product_data, array $variant_data, array &$data)
    {
        try {
            if ($this->last_product_removed != $product_data['id']) {
                $this->toolsProduct->trashProduct($product_data['sku']);
                $this->last_product_removed = $product_data['id'];
            }
        } catch (InvalidArgumentException $exception) {
            $error_message = $exception->getMessage();
            $data['error'] .= "|ERROR_TO_REMOVE_PRODUCT=$error_message";
            echo "[ ERROR ][ERROR_TO_REMOVE_PRODUCT][$variant_data[sku]] $error_message\n";
        }
    }
}