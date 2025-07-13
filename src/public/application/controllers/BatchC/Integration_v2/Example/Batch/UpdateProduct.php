<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/Vtex/UpdateProduct run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/NEW_INTEGRATION/ToolsProduct.php";

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\NEW_INTEGRATION\ToolsProduct;

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
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->toolProduct->setJob(__CLASS__);
    }

    /**
     * Método responsável pelo "start" da aplicação.
     *
     * @param  string|int|null  $id     Código do job (job_schedule.id).
     * @param  int|null         $store  Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params).
     * @param  string|null      $prdUnit    SKU do produto.
     * @return bool                     Estado da execução.
     */
    public function run($id = null, int $store = null, string $prdUnit = null): bool
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

        $success = false;
        // Recupera os produtos
        try {
            // Recupera os produtos
            $success = $this->getProductToUpdate($prdUnit);
        } catch (Exception | GuzzleException $exception) {
            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
        }

        // Grava a última execução
        $this->toolProduct->saveLastRun($success);

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    /**
     * Recupera os produtos e variações para atualização de preço e estoque.
     *
     * @param   string|null  $prdUnit   SKU do produto
     * @return  bool                    Retorna se o get para atualização de produtos ocorreu como esperado e poderá atualizar a data da última vez que buscou.
     * @throws  GuzzleException
     */
    public function getProductToUpdate(string $prdUnit = null): bool
    {
        $perPage    = 200;
        $regStart   = 0;
        $regEnd     = $perPage;

        while (true) {
            $products = $this->toolProduct->getProductsByInterval($regStart, $perPage, $prdUnit);

            // Não foi encontrado mais produtos. Fim da página.
            if (count($products) === 0) {
                break;
            }

            foreach ($products as $productDB) {
                echo "[PROCESS][LINE:".__LINE__."] Produto (id={$productDB['id']} | sku={$productDB['sku']}).\n";

                // Produto não tem o vínculo com a integradora.
                if (empty($productDB['product_id_erp'])) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto ({$productDB['id']}) não contem o campo 'product_id_erp'. Foi perdido ou não existe na integradora\n";
                    continue;
                }

                // Consulta a lista de produtos.
                try {
                    // Geralmente usado o ID integrado, mas também podemos usar o SKU para algumas integrações.
                    $product = $this->toolProduct->getDataProductIntegration($productDB['product_id_erp']);

                    // SKU não localizado.
                    if ($product === null) {
                        echo "[ERRO][LINE:".__LINE__."] SKU ({$productDB['product_id_erp']}) não localizado.\n";
                        continue;
                    }
                } catch (ClientException | InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                    continue;
                }

                // Atualizar o produto.
                $dataProductFormatted = $this->toolProduct->getDataFormattedToIntegration($product);
                $update = $this->toolProduct->updateProduct($dataProductFormatted);
                echo "[PROCESS][LINE:".__LINE__."] Atualização do produto ({$productDB['sku']}).Status da atualização:".Utils::jsonEncode($update)."\n";

                // Existe variação. Ver se há necessidade de atualiza-la também.
                foreach ($dataProductFormatted['variations']['value'] as $variation) {
                    if ($this->toolProduct->getVariationForSkuAndSkuVar($productDB['sku'], $variation['sku'])) {
                        $update = $this->toolProduct->updateVariation($variation, $productDB['sku']);
                        echo "[PROCESS][LINE:" . __LINE__ . "] Atualização da variação ({$variation['sku']}) do produto pai ({$productDB['sku']}). Status da atualização:" . Utils::jsonEncode($update) . "\n";
                    } else {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Variação ({$variation['sku']}) do produto pai ({$productDB['sku']}). Não existe no produto\n";
                    }
                }
            }

            $regStart   += $perPage;
            $regEnd     += $perPage;
        }

        return true;
    }
}