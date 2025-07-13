<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/anymarket/UpdateProduct run {ID} {STORE}
 *
 */

require_once APPPATH . "libraries/Integration_v2/anymarket/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use Integration\Integration_v2\anymarket\ApiException;
use Integration\Integration_v2\anymarket\ToolsProduct;

/**
 * Class UpdateAllProducts
 *
 * @property ToolsProduct $toolsProduct
 */
class UpdateAllProducts extends BatchBackground_Controller
{
    /**
     * Instantiate a new UpdateAllProducts instance.
     */
    public function __construct()
    {
        parent::__construct();

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
    public function run($id = null, int $store = null): bool
    {
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

        $this->toolsProduct->setLastRun();

        try {
            $this->getProductToUpdate();
        } catch (Exception $exception) {
            echo "[ ERROR ][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    /**
     * Recupera os produtos e variações para atualização
     *
     * @throws  Exception
     */
    public function getProductToUpdate()
    {
        $perPage    = 200;
        $regStart   = 0;
        $regEnd     = $perPage;

        while (true) {
            $products = $this->toolsProduct->getProductsByInterval($regStart, $perPage);

            // não foi mais encontrado produtos
            if (count($products) === 0) {
                echo "[  INFO ][LINE:" . __LINE__ . "] Nenhum produto encontrado para inicio de $regStart e fim de $perPage.\n";
                break;
            }

            foreach ($products as $productDB) {
                echo "[  INFO ][LINE:" . __LINE__ . "] Produto (id={$productDB['id']} | sku={$productDB['sku']}).\n";

                $skus = array();

                // existe variação, vou criar o array buscando os skus da variação
                if (!empty($productDB['has_variants'])) {
                    $variations = $this->toolsProduct->getVariationByIdProduct($productDB['id']);
                    $skus = array_map(function($variation){
                        return [
                            'sku'       => $variation['sku'],
                            'qty'       => $variation['qty'],
                            'active'    => $variation['status']
                        ];
                    }, $variations);
                } // não existe variação, vou criar o array buscando o sku do produto
                else {
                    $skus[] = [
                        'sku'       => $productDB['sku'],
                        'qty'       => $productDB['qty'],
                        'active'    => $productDB['status']
                    ];
                }

                foreach ($skus as $data_sku) {
                    $sku    = $data_sku['sku'];
                    $qty    = (int)$data_sku['qty'];
                    $active = $data_sku['active'];

                    try {
                        $response = $this->toolsProduct->getProductsBySku($sku);
                    } catch (ApiException $exception) {
                        echo "[ ERROR ] {$exception->getMessage()}\n";
                        continue;
                    }

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

                    try {
                        $this->toolsProduct->sendProductToNotification($body);
                        echo "[SUCCESS] Notificação enviada para $sku\n";
                    } catch (ApiException $exception) {
                        echo "[ ERROR ] {$exception->getMessage()}\n";
                        continue;
                    }
                }
            }
            echo "\n##### FIM PÁGINA: ($regStart até $regEnd) ".date('H:i:s')."\n";
            $regStart += $perPage;
            $regEnd += $perPage;
        }
    }
}