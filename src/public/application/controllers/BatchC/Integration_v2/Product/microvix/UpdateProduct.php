<?php

require APPPATH . "libraries/Integration_v2/microvix/ToolsProduct.php";

use Integration\Integration_v2\microvix\ToolsProduct;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;

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

        $this->toolProduct->setDateStartJob();
        $this->toolProduct->setLastRun();
        $this->toolProduct->formatDateFilterBling();

        $success = false;
        // Recupera os produtos
        try {
            // Recupera os produtos
            $success = $this->getProductToUpdate();
        } catch (Exception | GuzzleException $exception) {
            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
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
     * @return  bool            Retorna se o get para atualização de produtos ocorreu como esperado e poderá atualizar a data da última vez que buscou.
     * @throws  GuzzleException
     */
    public function getProductToUpdate(): bool
    {
        $perPage    = 200;
        $regStart   = 0;
        $regEnd     = $perPage;

        while (true) {
            $products = $this->toolProduct->getProductsByInterval($regStart, $perPage);

            // não foi mais encontrado produtos
            if (count($products) === 0) {
                break;
            }

            foreach ($products as $productDB) {
                echo "[PROCESS][LINE:".__LINE__."] Produto (id={$productDB['id']} | sku={$productDB['sku']}).\n";

                // produto está na lixeira não precisa atualizar o preço e estoque
                if ($productDB['status'] == 3) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto (id={$productDB['id']} | sku={$productDB['sku']}) está no lixo\n";
                    continue;
                }

                // produto não tem o vínculo com a integradora
                if (empty($productDB['product_id_erp'])) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto ({$productDB['id']}) não contem o campo 'product_id_erp'. Foi perdido ou não existe na integradora\n";
                    continue;
                }

                $product = $this->toolProduct->getDataProductIntegration($productDB['product_id_erp']);

                // não encontrou o produto
                if ($product === false || is_null($product)) {
                    echo "[PROCESS][LINE:".__LINE__."] SKU {$productDB['sku']} não encontrado na integradora\n";
                    continue;
                }

                // atualiza o produto
                try {
                    $dataProductFormatted = $this->toolProduct->getDataFormattedToIntegration($product);

                    if (empty($dataProductFormatted)) {
                        $this->toolProduct->log_integration(
                            "Alerta para integrar o produto com id ($product[codigoproduto])",
                            "<h4>Existem algumas pendências no cadastro do produto, para corrigir na integradora</h4><p>Produto/variação não contém código SKU</p><br><strong>Descrição</strong>: " . $product['nome_produto'],
                            "E");
                        echo "[ERRO][LINE:".__LINE__."] (Produto $product[codigoproduto]) não foi atualizado. Existem algumas pendências no cadastro do produto, para corrigir na integradora\n";
                        continue;
                    }

                    $update = $this->toolProduct->updateProduct($dataProductFormatted);
                } catch (InvalidArgumentException $exception) {
                    $this->toolProduct->log_integration(
                        "Erro ao criar formatação do produto: " . $product['codigoproduto'],
                        $exception->getMessage(),
                        "E"
                    );

                    echo "[ERROR][LINE: " . __LINE__ . "] Produto " . $product['codigoproduto'] . " erro ao formatar.\n";
                    continue;
                }

                echo "[PROCESS][LINE:".__LINE__."] Atualização do produto ({$productDB['sku']}).Status da atualização:".Utils::jsonEncode($update)."\n";

                echo "------------------------------------------------------------\n";
            }
            echo "\n##### FIM PÁGINA: ($regStart até $regEnd) ".date('H:i:s')."\n";
            $regStart += $perPage;
            $regEnd += $perPage;
        }

        return true;
    }
}