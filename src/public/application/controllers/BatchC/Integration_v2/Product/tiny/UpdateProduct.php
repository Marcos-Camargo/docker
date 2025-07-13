<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/Vtex/UpdateProduct run {ID} {STORE}
 *
 */

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/tiny/BaseProductTinyBatch.php";

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils;

class UpdateProduct extends BaseProductTinyBatch
{

    /**
     * @var array Skus já lidos
     */
    private $skusAlreadyRead = array();

    /**
     * Método responsável pelo "start" da aplicação
     *
     * @param string|int|null $id Código do job (job_schedule.id)
     * @param int|null $store Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
     * @return bool                     Estado da execução
     */
    public function run($id = null, int $store = null, $pagination = null): bool
    {
        return parent::run($id, $store, null, $pagination);
    }

    protected function handler(array $args = []): bool
    {
        return $this->getProductToUpdate();
    }

    /**
     * Recupera os produtos e variações para atualização de preço e estoque
     *
     * @return  bool            Retorna se o get para atualização de produtos ocorreu tudo certo e poderá atualizar a data da última vez que foi buscar
     * @throws  GuzzleException
     */
    public function getProductToUpdate(): bool
    {
        $log_name = $this->toolProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        $perPage = 100;

        if ($this->pagination == null) {
            $countProds = $this->toolProduct->countProductsByInterval() ?? 0;
            if ($countProds > 0) {
                $this->updateJobSchedules([
                    'module_path' => 'Integration_v2/Product/tiny/UpdateProduct',
                ], (int)ceil($countProds / $perPage), 10);
            }
        }

        $regStart = (int)(((int)$this->pagination ?? 0) * $perPage);
        $regEnd = $perPage;
        $urlGetProduct = "produto.obter.php";
        $queryGetProduct = array('query' => array('id' => null));
        $skusAlreadyRead = array();

        while (true) {
            $products = $this->toolProduct->getProductsByInterval($regStart, $perPage);

            // não foi mais encontrado produtos
            if (count($products) === 0) {
                break;
            }

            foreach ($products as $productDB) {
                try {
                    $this->toolProduct->setUniqueId($productDB['sku']);
                    echo "[PROCESS][LINE:" . __LINE__ . "] Produto (id={$productDB['id']} | sku={$productDB['sku']}).\n";

                    // produto está na lixeira não precisa atualizar o preço e estoque
                    if ($productDB['status'] == 3) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Produto (id={$productDB['id']} | sku={$productDB['sku']}) está no lixo\n";
                        continue;
                    }

                    // produto não tem o vínculo com a integradora
                    if (empty($productDB['product_id_erp'])) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Produto ({$productDB['id']}) não contem o campo 'product_id_erp'. Foi perdido ou não existe na integradora\n";
                        continue;
                    }

                    if (in_array($productDB['id'], $skusAlreadyRead)) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Produto ({$productDB['id']}) já lido e realizado a tentativa de atualização anteriormente\n";
                        continue;
                    }
                    $skusAlreadyRead[] = $productDB['id'];

                    // novos valores de filtro para pegar mais produtos
                    $queryGetProduct['query']['id'] = $productDB['product_id_erp'];

                    // consulta a lista de produtos
                    try {
                        $request = $this->toolProduct->request('GET', $urlGetProduct, $queryGetProduct);
                    } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                        echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
                        $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
                        continue;
                    }

                    try {
                        $product = Utils::jsonDecode($request->getBody()->getContents());
                        $product = $product->retorno->produto ?? false;
                    } catch (InvalidArgumentException $exception) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] SKU {$productDB['sku']} não encontrado na integradora\n";
                        continue;
                    }

                    // não encontrou o produto
                    if ($product === false) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] SKU {$productDB['sku']} não encontrado na integradora\n";
                        continue;
                    }

                    // atualiza o produto
                    $dataProductFormatted = $this->toolProduct->getDataFormattedToIntegration($product);
                    $update = $this->toolProduct->updateProduct($dataProductFormatted);
                    echo "[PROCESS][LINE:" . __LINE__ . "] Atualização do produto ({$productDB['sku']}).Status da atualização:" . Utils::jsonEncode($update) . "\n";

                    foreach ($dataProductFormatted['variations']['value'] as $variation) {
                        if ($this->toolProduct->getVariationForSkuAndSkuVar($productDB['sku'], $variation['sku'])) {
                            $update = $this->toolProduct->updateVariation($variation, $productDB['sku']);
                            echo "[PROCESS][LINE:" . __LINE__ . "] Atualização da variação ({$variation['sku']}) do produto pai ({$productDB['sku']}). Status da atualização:" . Utils::jsonEncode($update) . "\n";
                        } else {
                            echo "[PROCESS][LINE:" . __LINE__ . "] Variação ({$variation['sku']}) do produto pai ({$productDB['sku']}). Não existe no produto\n";
                        }
                    }

                    echo "------------------------------------------------------------\n";
                } catch (Throwable $e) {
                    echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
                }
            }
            echo "\n##### FIM PÁGINA: ($regStart até $regEnd) " . date('H:i:s') . "\n";
            if ($this->pagination != null) {
                break;
            }
            $regStart += $perPage;
            $regEnd += $perPage;
        }

        return true;
    }
}