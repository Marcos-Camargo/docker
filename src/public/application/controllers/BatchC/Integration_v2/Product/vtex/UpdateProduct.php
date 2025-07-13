<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/Vtex/UpdateProduct run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/vtex/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\vtex\ToolsProduct;

class UpdateProduct extends BatchBackground_Controller
{
    /**
     * @var ToolsProduct
     */
    private $toolProduct;

    /**
     * Instantiate a new CreateProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->toolProduct = new ToolsProduct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->toolProduct->setJob(__CLASS__);
    }

    /**
     * Método responsável pelo "start" da aplicação
     *
     * @param  string|int|null  $id     Código do job (job_schedule.id)
     * @param  int|null         $store  Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
     * @return bool                     Estado da execução
     */
    public function run($id = null, int $store = null): bool
    {
        $log_name = $this->toolProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

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
            $this->toolProduct->startRun($store);
        } catch (InvalidArgumentException $exception) {
            $this->toolProduct->log_integration(
                "Erro para executar a integração",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                "E"
            );
            $this->gravaFimJob();
            return true;
        }

        // Recupera os produtos para atualizar preço e estoque
        try {
            $this->getProductToUpdate();
        } catch (Exception | GuzzleException $exception) {
            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
        }

        // Grava a última execução
        $this->toolProduct->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    /**
     * Recupera os produtos e variações para atualização de preço e estoque
     *
     * @return bool
     * @throws Exception
     */
    public function getProductToUpdate(): bool
    {
        $log_name = $this->toolProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        $perPage            = 200;
        $regStart           = 0;
        $regEnd             = $perPage;
        $urlGetProduct      = "api/catalog_system/pub/products/search";
        $queryGetProduct    = array('query' => array('fq' => "skuId:0"));
        $skusAlreadyRead    = array();

        while (true) {
            $products = $this->toolProduct->getProductsByInterval($regStart, $perPage);

            // não foi mais encontrado produtos
            if (count($products) === 0) {
                break;
            }

            foreach ($products as $productDB) {

                echo "[PROCESS][LINE:".__LINE__."] Produto (id={$productDB['id']} | sku={$productDB['sku']}).\n";

                $skus           = array();
                $checkSkuLost   = array();

                // produto está na lixeira não precisa atualizar o preço e estoque
                if ($productDB['status'] == 3) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto (id={$productDB['id']} | sku={$productDB['sku']}) está no lixo\n";
                    continue;
                }

                // existe variação, vou criar o array buscando os skus da variação
                if (!empty($productDB['has_variants'])) {
                    $existVariation = true;
                    $variations = $this->toolProduct->getVariationByIdProduct($productDB['id']);
                    foreach ($variations as $variation) {
                        $skus[] = [
                            'sku' => $variation['sku'],
                            'variations' => explode(';', $variation['name'])
                        ];
                        $checkSkuLost[] = $variation['sku'];
                    }
                }
                // não existe variação, vou criar o array buscando o sku do produto
                else {
                    $existVariation = false;
                    $skus[] = [
                        'sku' => $productDB['sku'],
                        'variations' => array()
                    ];
                    $checkSkuLost[] = $productDB['sku'];
                }

                // novos valores de filtro para pegar mais produtos
                $queryGetProduct['query']['fq'] = "skuId:{$skus[0]['sku']}";
                $queryGetProduct['query']['sc'] = $this->toolProduct->credentials->sales_channel_vtex;

                // consulta a lista de produtos
                try {
                    $request = $this->toolProduct->request('GET', $urlGetProduct, $queryGetProduct);
                } catch (GuzzleHttp\Exception\ClientException | InvalidArgumentException | GuzzleException $exception) {
                    echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                    $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
                    continue;
                }

                $product = Utils::jsonDecode($request->getBody()->getContents());
                $product = $product[0] ?? false;

                if ($product === false) {
                    echo "[PROCESS][LINE:".__LINE__."] SKU {$skus[0]['sku']} não encontrado na integradora\n";
                    continue;
                }

                if ($existVariation) {
                    if (in_array("P_$product->productId", $skusAlreadyRead)) {
                        echo "[PROCESS][LINE:".__LINE__."] Produto (P_$product->productId) já lido e realizado a tentativa de atualização anteriormente\n";
                        continue;
                    }

                    foreach ($product->items as $item) {
                        if (!property_exists($item, 'variations')) {
                            echo "[ERROR][LINE:".__LINE__."] Produto está com variação no seller center, mas não tem variação na integradora\n";

                            $this->toolProduct->log_integration(
                                "Erro para atualizar o produto (P_$product->productId)",
                                "<h4>Não foi possível atualizar o produto (P_$product->productId)</h4> <p>Produto está com variação no seller center, mas não tem variação na integradora.</p>",
                                "E"
                            );
                            continue 2;
                        }
                    }

                    // atualiza o produto
                    $dataProductFormatted = $this->toolProduct->getDataFormattedToIntegration($product);
                    unset($dataProductFormatted['extra_operating_time']); // remover atualizacao do prazo operacional fixo 0

                    $update = $this->toolProduct->updateProduct($dataProductFormatted);

                    $attributesProduct = $this->toolProduct->getAttributeProduct($productDB['id'], $product->productId);
                    $attributesSku = array();
                    foreach ($dataProductFormatted['variations']['value'] as $variation) {
                        $attributesSku = array_merge($attributesSku, $this->toolProduct->getAttributeSku($productDB['id'], $variation['sku']));
                    }
                    $attributes = array_merge($attributesProduct, $attributesSku);
                    if (!empty($attributes)) {
                        $this->toolProduct->setAttributeProduct($productDB['id'], $attributes, true);
                    }

                    echo "[PROCESS][LINE:".__LINE__."] Atualização do produto pai (P_$product->productId).Status da atualização:".Utils::jsonEncode($update)."\n";
                    array_push($skusAlreadyRead, "P_$product->productId");

                    foreach ($dataProductFormatted['variations']['value'] as $variation) {
                        $update = $this->toolProduct->updateVariation($variation, "P_$product->productId");
                        echo "[PROCESS][LINE:".__LINE__."] Atualização da variação ({$variation['sku']}) do produto pai (P_$product->productId). Status da atualização:".Utils::jsonEncode($update)."\n";
                    }
                } else {
                    for ($countSku = 0; $countSku < count($product->items); $countSku++) {
                        if (in_array($product->items[$countSku]->itemId, $skusAlreadyRead)) {
                            echo "[PROCESS][LINE:".__LINE__."] Produto ({$product->items[$countSku]->itemId}) já lido e realizado a tentativa de atualização anteriormente\n";
                            continue;
                        }

                        // atualiza o produto
                        $dataProductFormatted = $this->toolProduct->getDataFormattedToIntegration($product,$countSku);
                        unset($dataProductFormatted['extra_operating_time']); // remover atualizacao do prazo operacional fixo 0
                        $update = $this->toolProduct->updateProduct($dataProductFormatted);

                        // Atualiza os atributos
                        $attributesProduct  = $this->toolProduct->getAttributeProduct($productDB['id'], $product->productId);
                        $attributesSku      = $this->toolProduct->getAttributeSku($productDB['id'], $product->items[$countSku]->itemId);
                        $attributes         = array_merge($attributesProduct, $attributesSku);
                        if (!empty($attributes)) {
                            $this->toolProduct->setAttributeProduct($productDB['id'], $attributes, true);
                        }

                        echo "[PROCESS][LINE:".__LINE__."] Atualização do produto simples ({$product->items[$countSku]->itemId}).Status da atualização:".Utils::jsonEncode($update)."\n";
                        array_push($skusAlreadyRead, $product->items[$countSku]->itemId);
                    }
                }
                echo "------------------------------------------------------------\n";
            }
            echo "\n##### FIM PÁGINA: ($regStart até $regEnd) ".date('H:i:s')."\n";
            $regStart += $perPage;
            $regEnd += $perPage;
        }

        return true;
    }
}