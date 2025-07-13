<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/anymarket/UpdateProduct run {ID} {STORE}
 *
 */

require_once APPPATH . "libraries/Integration_v2/anymarket/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use Integration\Integration_v2\anymarket\ToolsProduct;

/**
 * Class UpdateProduct
 * @property CI_Loader $load
 * @property Model_products $model_products
 * @property ToolsProduct $toolsProduct
 * @property Model_job_schedule $model_job_schedule
 * @property Model_settings $model_settings
 */
class UpdateProduct extends BatchBackground_Controller
{

    private $page;
    private $limit;
    /**
     * Instantiate a new CreateProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_products');
        $this->load->model('model_job_schedule');
        $this->load->model('model_settings');

        $this->toolsProduct = new ToolsProduct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->toolsProduct->setJob(__CLASS__);
    }

    /**
     * Método responsável pelo "start" da aplicação
     *
     * @param string|int|null $id Código do job (job_schedule.id)
     * @param int|null $store Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
     * @return bool                     Estado da execução
     */
    public function run($id = null, int $store = null, ?int $page = null, ?int $limit = 10000): bool
    {
        $this->page = $page == 'null' ? null : $page;
        $this->limit = $limit == 'null' ? null : $limit;
        $log_name = $this->toolsProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        if (!$this->checkStartRun(
            $log_name,
            $this->router->directory,
            __CLASS__,
            $id,
            $store
        )) {
            return false;
        }

        // é um job sem paginação
        if ($page === 'null' || $page === null) {
            $server_async_job_quantity_to_update_products_anymarket = $this->model_settings->getValueIfAtiveByName('server_async_job_quantity_to_update_products_anymarket');

            if ($server_async_job_quantity_to_update_products_anymarket) {
                // {"all": 10, "8": 30} - quando for all é todas as lojas, se existir uma exceção, considera ela
                $decode_server_job_quantity = json_decode($server_async_job_quantity_to_update_products_anymarket, true);

                if ($decode_server_job_quantity) {
                    $this->limit = 500;
                    $server_job_quantity = $decode_server_job_quantity['all'];

                    if (array_key_exists($store, $decode_server_job_quantity)) {
                        $server_job_quantity = $decode_server_job_quantity[$store];
                    }

                    $server_job_quantity -= 1;

                    $jobs_schedule = $this->model_job_schedule->getByModulePathAndStatus('Integration_v2/Product/anymarket/UpdateProduct', [
                        $this->model_job_schedule::WAITING_FOR_APPROVAL,
                        $this->model_job_schedule::IN_PROGRESS,
                        $this->model_job_schedule::PULLING_FROM_CRONTAB,
                        $this->model_job_schedule::IN_QUEUE,
                    ]);

                    $jobs_check = array_filter($jobs_schedule, function ($job) use ($store) {
                        return count(explode(' ', $job['params'])) > 1 && likeTextNew("$store %", $job['params']);
                    });

                    if (!empty($jobs_check)) {
                        echo "Ainda existe algum job de anymarket/UpdateProduct em execução\n";
                        echo json_encode($jobs_check) . "\n";
                        $this->gravaFimJob();
                        return true;
                    }

                    for ($x = 0; $x <= $server_job_quantity; $x++) {
                        $this->db->insert('job_schedule', array(
                            'module_path' => 'Integration_v2/Product/anymarket/UpdateProduct',
                            'module_method' => 'run',
                            'params' => "$store $x",
                            'status' => '0',
                            'finished' => '0',
                            'error' => null,
                            'error_count' => '0',
                            'error_msg' => null,
                            'date_start' => date(DATETIME_INTERNATIONAL, strtotime('+1 minute', strtotime(dateNow()->format(DATETIME_INTERNATIONAL)))),
                            'date_end' => null,
                            'server_id' => '0'
                        ));
                        echo "Criado anymarket/UpdateProduct para página $x\n";
                    }
                    $this->gravaFimJob();
                    return true;
                } else {
                    echo "Parâmetro 'server_async_job_quantity_to_update_products_anymarket', mal informado: $server_async_job_quantity_to_update_products_anymarket\n";
                }
            }
        }
        
        // realiza algumas validações iniciais antes de iniciar a rotina
        try {
            $this->toolsProduct->startRun($store);
        } catch (Throwable $exception) {
            $this->toolsProduct->log_integration(
                "Erro para executar a integração",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                "E"
            );
            $this->gravaFimJob();
            return true;
        }

        $this->toolsProduct->setDateStartJob();
        $this->toolsProduct->setLastRun();

        $success = false;
        // Recupera os produtos
        try {
            // Recupera os produtos
            $success = $this->getProductToUpdate();
        } catch (Exception | GuzzleException $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
        }

        // Grava a última execução
        $this->toolsProduct->saveLastRun($success);

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    /**
     * Recupera os produtos e variações para atualização de preço e estoque
     *
     * @param bool $filterMultiloja O filtro será pela data de alteração da multi loja
     * @return  bool                    Retorna se o get para atualização de produtos ocorreu tudo certo e poderá atualizar a data da última vez que foi buscar
     * @throws  GuzzleException
     */
    public function getProductToUpdate(bool $filterMultiloja = false): bool
    {
        $log_name = $this->toolsProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        echo "[  INFO ][LINE:" . __LINE__ . "] Filtrando por produtos alterados no cadastro\n";
        echo "------------------------------------------------------------\n";
        $data = [
            'checked' => 0,
            'integration_id' => $this->toolsProduct->integrationData['id'],
        ];
        $this->db->select()
            ->from('anymarket_queue')
            ->where($data)
            ->group_by('idSku')
            ->order_by('date_update', 'ASC');
        if ($this->page >= 0) {
            $this->db->limit($this->limit, $this->page * $this->limit);
        }
        $items = $this->db->get()->result_array();
        foreach ($items as $item) {
            $body = json_decode($item["received_body"], true);
            $body['idSkuMarketplace'] = $body['idSkuMarketplace'] ?? $item['idSkuMarketplace'];
            if (!empty($body['transmission'] ?? '')) {
                $transmission = $body['transmission'] ?? [];
                echo "[  INFO ][LINE:" . __LINE__ . "] Produto já atualizado via webhook #{$body['idSkuMarketplace']}\n";
                echo "[  INFO ][LINE:" . __LINE__ . "] Enviando transmissão #{$body['idSkuMarketplace']}\n";
                echo json_encode($transmission) . "\n";
                $this->toolsProduct->sendTransmission($body['idSkuMarketplace'], $transmission);
                $response = $this->toolsProduct->getRequestResponse();
                if ($response) {
                    echo "[  INFO ][LINE:" . __LINE__ . "] Resultado da transmissão do produto#{$body['idSkuMarketplace']}\n";
                    if (is_string($response)) {
                        echo "ERROR: $response\n";
                    } elseif (is_object($response) && method_exists($response, 'getStatusCode')) {
                        echo "HTTP CODE: " . $response->getStatusCode() . "\n";
                        echo "BODY: " . $response->getBody()->getContents() . "\n";
                    }
                }
                echo "------------------------------------------------------------\n";
                echo "[  INFO ][LINE:" . __LINE__ . "] Removendo item {$item['id']} da fila #{$body['idSkuMarketplace']}\n";
                $this->db->delete('anymarket_queue', [
                        'id' => $item['id']
                    ]
                );
                echo "------------------------------------------------------------\n";
                continue;
            }
            $body['idProduct'] = $body['idProduct'] ?? $item['idProduct'];
            $body['idSku'] = $body['idSku'] ?? $item['idSku'];
            $this->toolsProduct->setUniqueId($body['idSkuMarketplace']);
            try {
                echo "[  INFO ][LINE:" . __LINE__ . "] Formatando produto {$body['idSkuMarketplace']}\n";
                echo "------------------------------------------------------------\n";

                $parsedProduct = $this->toolsProduct->getDataFormattedToIntegration($body);
                $normalizedProduct = $this->toolsProduct->normalizedFormattedData($parsedProduct);
                $body['marketplaceStatus'] = $normalizedProduct['status'] == Model_products::ACTIVE_PRODUCT ? 'ATIVO' : 'INATIVO';
                $productSku = $parsedProduct['sku']['value'];
                $productIdErp = $parsedProduct['_product_id_erp']['value'];
                $parsedVariations = $this->toolsProduct->getParsedVariations();

                echo "[  INFO ][LINE:" . __LINE__ . "] Produto {$productSku} - #{$body['idSkuMarketplace']} formatado:\n";
                echo json_encode($normalizedProduct) . "\n";
                echo "------------------------------------------------------------\n";
                if (($normalizedProduct['id'] ?? 0) > 0) {
                    unset($parsedProduct['variations']);
                    $body['status'] = $normalizedProduct['status'] == Model_products::ACTIVE_PRODUCT ? 'ACTIVE' : 'PAUSED';
                    echo "[  INFO ][LINE:" . __LINE__ . "] Atualizando produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                    //if (!$normalizedProduct['_published'])
                    $this->toolsProduct->updateProduct($parsedProduct);
                    if (empty($normalizedProduct['variations'])) $this->toolsProduct->updateStockProduct($normalizedProduct['sku'], $normalizedProduct['stock']);
                    $this->toolsProduct->updatePriceProduct($normalizedProduct['sku'], $normalizedProduct['price'], $normalizedProduct['list_price']);
                } else {
                    // Produto simles na anymarket
                    $sku_prd = $normalizedProduct['sku'];
                    $current_product_id_erp = $this->toolsProduct->getRealValueNormalized($normalizedProduct, '_current_product_id_erp');
                    $product_id_erp         = $normalizedProduct['_product_id_erp'] ?? null;
                    if (empty($current_product_id_erp) && !empty($product_id_erp)) {
                        echo "[  INFO ][LINE:" . __LINE__ . "] SKU Produto $sku_prd atualizado para {$normalizedProduct['_product_id_erp']}\n";
                        $this->toolsProduct->updateProductIdIntegration($sku_prd, $normalizedProduct['_product_id_erp']);
                    }

                    if (!empty($parsedVariations)) {
                        $sku_var                = $parsedVariations[0]['sku'];
                        $variant_id_erp         = $parsedVariations[0]['_variant_id_erp'] ?? null;
                        $current_variant_id_erp = $parsedVariations[0]['_current_variant_id_erp'] ?? null;

                        if (empty($current_variant_id_erp) && !empty($variant_id_erp)) {
                            echo "[  INFO ][LINE:" . __LINE__ . "] SKU Variação $sku_var do Produto $sku_prd atualizado para {$parsedVariations[0]['_variant_id_erp']}\n";
                            $this->toolsProduct->updateProductIdIntegration($sku_prd, $parsedVariations[0]['_variant_id_erp'], $sku_var);
                        }
                    }

                    $body['status'] = $normalizedProduct['status'] == Model_products::ACTIVE_PRODUCT ? 'ACTIVE' : 'PAUSED';
                    echo "[  INFO ][LINE:" . __LINE__ . "] Criando produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                    $this->toolsProduct->sendProduct($parsedProduct, true);
                }
                echo "------------------------------------------------------------\n";
                $response = $this->toolsProduct->getRequestResponse();
                if ($response) {
                    echo "[  INFO ][LINE:" . __LINE__ . "] Resultado da criação/atualização do produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                    if (is_string($response)) {
                        echo "ERROR: $response\n";
                    } elseif (is_object($response) && method_exists($response, 'getStatusCode')) {
                        echo "HTTP CODE: " . $response->getStatusCode() . "\n";
                        echo "BODY: " . $response->getBody()->getContents() . "\n";
                    }
                } else {
                    echo "[  INFO ][LINE:" . __LINE__ . "] Nenhuma alteração processada no produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                }
                echo "------------------------------------------------------------\n";
                echo "[  INFO ][LINE:" . __LINE__ . "] Atualizado vínculo idERP ({$productIdErp}) produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                $productId = $parsedProduct['id']['value'] ?? $this->toolsProduct->getProductIdBySku($productSku);
                $is_variation_grouped = false;
                if ($productId) {
                    $product_data = $this->toolsProduct->getProductById($productId);
                    if ($product_data && $product_data['is_variation_grouped']) {
                        $is_variation_grouped = true;
                    }
                }
                if (!$is_variation_grouped) {
                    $current_product_id_erp = $this->toolsProduct->getRealValueNormalized($normalizedProduct, '_current_product_id_erp');
                    $product_id_erp         = $normalizedProduct['_product_id_erp'] ?? null;
                    if (empty($current_product_id_erp) && !empty($product_id_erp)) {
                        echo "[  INFO ][LINE:" . __LINE__ . "] SKU Produto $productSku atualizado para $productIdErp\n";
                        $this->toolsProduct->updateProductIdIntegration(
                            $productSku,
                            $productIdErp
                        );
                    }
                }
                $varProductId = $productId;
                $varProductSku = $productSku;
                if (!empty($parsedVariations)) {
                    echo "------------------------------------------------------------\n";
                    echo "[  INFO ][LINE:" . __LINE__ . "] variações {$productSku} - #{$body['idSkuMarketplace']} formatadas:\n";
                    echo json_encode($parsedVariations) . "\n";
                    echo "------------------------------------------------------------\n";
                    echo "[  INFO ][LINE:" . __LINE__ . "] Enviando variações do produto (id: {$productIdErp}) produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                    foreach ($parsedVariations as $parsedVariation) {
                        if ($parsedVariation['id'] > 0) {
                            echo "[  INFO ][LINE:" . __LINE__ . "] Atualizando variação {$parsedVariation['sku']} do produto (id: {$productIdErp}) produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                            if(!$parsedVariation['_published']) {
                                $this->toolsProduct->updateVariation($parsedVariation, $productSku);
                            }
                            $this->toolsProduct->updatePriceProduct(
                                $normalizedProduct['sku'],
                                $parsedVariation['price'],
                                $parsedVariation['list_price'] ?? null,
                                $parsedVariation['sku']
                            );
                            $this->toolsProduct->updateStockProduct(
                                $normalizedProduct['sku'],
                                $parsedVariation['stock'],
                                $parsedVariation['sku']
                            );
                        } else {
                            if (($normalizedProduct['id'] ?? 0) > 0) {
                                echo "[  INFO ][LINE:" . __LINE__ . "] Criando variação {$parsedVariation['sku']} do produto (id: {$productIdErp}) produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                                if (!$this->toolsProduct->createVariation($parsedVariation, $productSku)) {
                                    throw new Exception($this->toolsProduct->getStringRequestResponse());
                                }
                            }
                        }
                        $response = $this->toolsProduct->getRequestResponse();
                        if ($response) {
                            echo "[  INFO ][LINE:" . __LINE__ . "] Resultado da criação/atualização da variação {$parsedVariation['sku']} do produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                            if (is_string($response)) {
                                echo "ERROR: $response\n";
                            } elseif (is_object($response) && method_exists($response, 'getStatusCode')) {
                                echo "HTTP CODE: " . $response->getStatusCode() . "\n";
                                echo "BODY: " . $response->getBody()->getContents() . "\n";
                            }
                        } else {
                            echo "[  INFO ][LINE:" . __LINE__ . "] Nenhuma alteração processada para a variação {$parsedVariation['sku']} do produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                        }
                        echo "[  INFO ][LINE:" . __LINE__ . "] Atualizado vínculo idERP ({$parsedVariation['_variant_id_erp']}) da variação {$parsedVariation['sku']} do produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                        $varProductId = $this->toolsProduct->getVariationIdBySku($productSku, $parsedVariation['sku']);
                        if (empty($varProductId)) {
                            throw new Exception("Ocorreu um problema ao criar/atualizar a variação: {$parsedVariation['sku']}");
                        }

                        $variant_id_erp         = $parsedVariation['_variant_id_erp'] ?? null;
                        $current_variant_id_erp = $parsedVariation['_current_variant_id_erp'] ?? null;
                        if (empty($current_variant_id_erp) && !empty($variant_id_erp)) {
                            echo "[  INFO ][LINE:" . __LINE__ . "] SKU Variação {$parsedVariation['sku']} do Produto $productSku atualizado para {$parsedVariation['_variant_id_erp']}\n";
                            $this->toolsProduct->updateProductIdIntegration(
                                $productSku,
                                $parsedVariation['_variant_id_erp'],
                                $parsedVariation['sku']
                            );
                        }
                        $varProductSku = $parsedVariation['sku'];
                    }
                }
                echo "------------------------------------------------------------\n";
                echo "[  INFO ][LINE:" . __LINE__ . "] Mapeando atributos/características do produto (id: {$productIdErp}) produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                $parsedAttributes = $this->toolsProduct->getAttributeProduct($productId, $body['idSkuMarketplace']);
                echo json_encode($parsedAttributes) . "\n";
                if (!empty($parsedAttributes)) {
                    echo "[  INFO ][LINE:" . __LINE__ . "] Salvando atributos/características do produto (id: {$productIdErp}) produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                    $this->toolsProduct->setAttributeProduct($productId, $parsedAttributes);
                }
                echo "------------------------------------------------------------\n";
                echo "[  INFO ][LINE:" . __LINE__ . "] Enviando transmissão de SUCESSO (id: {$productIdErp}) produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                $this->toolsProduct->sendSuccessTransmission(array_merge($body, [
                    'id' => $varProductId,
                    'productId' => $productId,
                    'sku' => $varProductSku ?? $productSku,
                ]));
                $response = $this->toolsProduct->getRequestResponse();
                if ($response) {
                    echo "[  INFO ][LINE:" . __LINE__ . "] Resultado de SUCESSO da transmissão do produto {$productSku} - #{$body['idSkuMarketplace']}\n";
                    if (is_string($response)) {
                        echo "ERROR: $response\n";
                    } elseif (is_object($response) && method_exists($response, 'getStatusCode')) {
                        echo "HTTP CODE: " . $response->getStatusCode() . "\n";
                        echo "BODY: " . $response->getBody()->getContents() . "\n";
                    }
                }
                echo "------------------------------------------------------------\n";
            } catch (\Integration\Integration_v2\anymarket\ApiException $e) {
                echo "[  INFO ][LINE:" . __LINE__ . "] Erro ao consultar produto #{$body['idSkuMarketplace']}. {$e->getMessage()}\n";
                if ($e->getCode() == 404) {
                    echo "[  INFO ][LINE:" . __LINE__ . "] Erro ao consultar produto #{$body['idSkuMarketplace']}. Removendo #{$item['id']} da fila...\n";
                    echo "------------------------------------------------------------\n";
                    $this->db->delete('anymarket_queue', [
                            'id' => $item['id']
                        ]
                    );
                }
                continue;
            } catch (Throwable $e) {
                $error_product = $e->getMessage();
                if ($error_product == 'SKU atualizado via direcionamento por ser produto de agrupamento.') {
                    echo "[  INFO ][LINE:" . __LINE__ . "] $error_product {$body['idProduct']} #{$body['idSkuMarketplace']}.\n";
                    continue;
                }
                $logParsedProduct = !empty($parsedProduct) ? json_encode($parsedProduct) : 'empty';
                $this->log_data(__CLASS__, __FUNCTION__, 'store='.$this->toolsProduct->store."\n".json_encode($body)."\n".$error_product."\nline=".$e->getLine()."\ntrace=".json_encode($e->getTrace(), JSON_UNESCAPED_UNICODE)."\nrequest_response=".json_encode($this->toolsProduct->getRequestResponse(), JSON_UNESCAPED_UNICODE)."\n".$logParsedProduct);
                $this->toolsProduct->log_integration(
                    "Ocorreu um erro ao importar o produto {$body['idSkuMarketplace']}",
                    $error_product,
                    'E'
                );
                echo "[  INFO ][LINE:" . __LINE__ . "] Erro processar produto #{$body['idSkuMarketplace']}. Enviando ERRO de transmissão.\n";
                echo $error_product . "\n";
                $errorData = $this->toolsProduct->sendErrorTransmission($body, $error_product);
                echo json_encode($errorData) . "\n";
                $response = $this->toolsProduct->getRequestResponse();
                if ($response) {
                    echo "[  INFO ][LINE:" . __LINE__ . "] Resultado de ERRO da transmissão do produto #{$body['idSkuMarketplace']}\n";
                    if (is_string($response)) {
                        echo "ERROR: $response\n";
                    } elseif (is_object($response) && method_exists($response, 'getStatusCode')) {
                        echo "HTTP CODE: " . $response->getStatusCode() . "\n";
                        echo "BODY: " . $response->getBody()->getContents() . "\n";
                    }
                }
                echo "------------------------------------------------------------\n";
            }

            echo "[  INFO ][LINE:" . __LINE__ . "] Removendo produto da fila {$body['idProduct']} #{$body['idSkuMarketplace']}.\n";
            echo "------------------------------------------------------------\n";
            $this->db->update('anymarket_queue',
                [
                    'checked' => 1,
                    'idSkuMarketplace' => $body['idSkuMarketplace'],
                    'idProduct' => $body['idProduct']
                ], ['id' => $item['id']]);
        }

        return true;
    }
}