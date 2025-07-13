<?php

require APPPATH . "libraries/Integration_v2/microvix/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use Integration\Integration_v2\microvix\ToolsProduct;

class CreateProduct extends BatchBackground_Controller
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
            $this->toolProduct->setDateStartJob();
            $this->toolProduct->setLastRun();
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
            $success = $this->getProductsToCreate();
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
     * Recupera os produtos para cadastro
     *
     * @return bool
     * @throws Exception|GuzzleException
     */
    public function getProductsToCreate(): bool
    {
        $response = $this->toolProduct->getAllDataProductIntegration($this->toolProduct->dateLastJob);

        foreach ($response as $product) {
            try {
                $productFormatted = $this->toolProduct->getDataFormattedToIntegration($product);
            } catch (InvalidArgumentException $exception) {
                $this->toolProduct->log_integration(
                    "Erro ao criar formatação do produto: " . $product['codigoproduto'],
                    $exception->getMessage(),
                    "E"
                );

                echo "[ERROR][LINE: " . __LINE__ . "] Produto " . $product['codigoproduto'] . " erro ao formatar.\n";
                continue;
            }

            if (empty($productFormatted)) {
                $this->toolProduct->log_integration(
                    "Alerta para integrar o produto com id ($product[codigoproduto])",
                    "<h4>Existem algumas pendências no cadastro do produto, para corrigir na integradora</h4><p>Produto/variação não contém código SKU</p><br><strong>Descrição</strong>: $productFormatted[name]",
                    "E");
                echo "[ERROR][LINE: " . __LINE__ . "] Produto $product[codigoproduto] não contém um código SKU\n";
                continue;
            }

            $skuProduct     = $productFormatted['sku']['value'];

            if (empty($skuProduct)) {
                $this->toolProduct->log_integration(
                    "Alerta para integrar o produto com id ($skuProduct)",
                    "<h4>Existem algumas pendências no cadastro do produto, para corrigir na integradora</h4><p>Produto/variação não contém código SKU</p><br><strong>Descrição</strong>: $productFormatted[name]",
                    "E");
                echo "[ERROR][LINE: " . __LINE__ . "] Produto $skuProduct não contém um código SKU\n";
                continue;
            }

            $this->toolProduct->setUniqueId($skuProduct);
            $this->createProduct($productFormatted);
        }

        return true;
    }

    /**
     * Validação para cadastro do produto
     *
     * @param  bool     $existVariation Existe variação?
     * @param  object   $product        Dados do produto, vindo do ERP
     * @return void                     Retorna estado da criação do produto
     */
    private function createProduct(array $product): void
    {
        $skuProductPai  = trim($product['sku']['value']);

        // verificar se esse sku já existe na loja
        $verifyProduct = $this->toolProduct->getProductForSku($skuProductPai);

        // não vai trabalhar com variação, cadastrar como produto simples.
        // SKU não localizado na loja. Deve tentar cadastrar
        if (!$verifyProduct) {
            try {
                $this->toolProduct->sendProduct($product);
                echo "[SUCCESS][LINE:" . __LINE__ . "] Produto $skuProductPai cadastrado com sucesso\n";

                // produto criado, recuperei o ID do sku e definirei os atributos para a categoria do produto
                // muitas vezes o produto chegará não categorizado então esse cenário não acontecerá
                $verifyProduct = $this->toolProduct->getProductForSku($skuProductPai);
                $attributes = $this->toolProduct->getAttributeProduct($verifyProduct["id"], $skuProductPai);
                if (!empty($attribute)) {
                    $this->toolProduct->setAttributeProduct($verifyProduct['id'], $attributes);
                }

                $this->toolProduct->updateProductIdIntegration($skuProductPai, $skuProductPai);
            } catch (InvalidArgumentException $exception) {
                echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
            }
        }
        // SKU localizado na loja
        else {
            // produto atualizado com código da integradora
            if ($verifyProduct['product_id_erp'] != $skuProductPai) {
                $this->toolProduct->updateProductIdIntegration($skuProductPai, $skuProductPai);
                echo "[PROCESS][LINE:".__LINE__."] Atualizado código de integração do produto ($skuProductPai)\n";
            } else {
                echo "[PROCESS][LINE:" . __LINE__ . "] Produto $skuProductPai já cadastrado\n";
            }
        }

    }
}