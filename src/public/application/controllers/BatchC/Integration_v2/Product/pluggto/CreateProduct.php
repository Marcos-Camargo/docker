<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/Vtex/CreateProduct run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/pluggto/ToolsProduct.php";

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\pluggto\ToolsProduct;

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
        $this->toolProduct->formatDateFilter();

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
        $lastProductRead    = null;
        $urlGetProducts     = 'products';
        $queryGetProducts   = array('query' => array('limit' => 100, 'supplier_id' => $this->toolProduct->credentials->user_id));

        while (true) {

            // pegar filtro de data do dia
            if ($this->toolProduct->dateStartJob && $this->toolProduct->dateLastJob) {
                $queryGetProducts['query']['modified'] = "{$this->toolProduct->dateLastJob}to{$this->toolProduct->dateStartJob}";
            }

            // Começa a nova requisição a partir do último produto lido
            if ($lastProductRead !== null) {
                $queryGetProducts['query']['next'] = $lastProductRead;
            }

            // consulta a lista de produtos
            try {
                $request = $this->toolProduct->request('GET', $urlGetProducts, $queryGetProducts);
            } catch (ClientException | InvalidArgumentException $exception) {
                echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                break;
            }

            $bodyRequest = Utils::jsonDecode($request->getBody()->getContents());

            // não tem resultado ou não foram mais encontrados
            if ((int)$bodyRequest->total === 0 || (int)$bodyRequest->showing === 0) {
                break;
            }

            ECHO "\n##### INÍCIO PÁGINA: (mostrando: $bodyRequest->showing de $bodyRequest->total, limite de $bodyRequest->limit) ".date('H:i:s')."\n\n";

            foreach ($bodyRequest->result as $index => $product) {
                $product = $product->Product;

                /*
                 * PEDRO
                if (ENVIRONMENT === 'development') {
                    echo "=============[DEBUG=ON]\n";
                    if ($index !== 0) {
                        dd('==================== EXIT');
                    }

                    // consulta a lista de produtos
                    try {
                        $request     = $this->toolProduct->request('GET', 'skus/3157-TAPRAINBOW');
                        $bodyRequest = Utils::jsonDecode($request->getBody()->getContents());
                        $product     = $bodyRequest->Product;
                    } catch (ClientException | InvalidArgumentException $exception) {
                        echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                        return false;
                    }
                }*/

                echo  "------------------------------------------------------------\n";


                $id_produto     = $product->id;
                $lastProductRead= $id_produto;
                $skuProduct     = $product->sku;
                $existVariation = property_exists($product, 'variations') && count($product->variations);
                $this->toolProduct->setUniqueId($skuProduct);
                
                if (empty($skuProduct)) {
                    $this->toolProduct->log_integration(
                        "Alerta para integrar o produto com id ($id_produto)",
                        "<h4>Existem algumas pendências no cadastro do produto, para corrigir na integradora</h4><p>Produto/variação não contém código SKU</p><br><strong>Descrição</strong>: $product->name",
                        "E");
                    echo "[PROCESS][LINE: " . __LINE__ . "] Produto $id_produto não contém um código SKU\n";
                    continue;
                }

                if ($existVariation) {
                    $variationLog = array_map(function ($item){
                        return $item->sku;
                    }, $product->variations);
                    echo "[PROCESS][LINE:".__LINE__."] Pegando $skuProduct. Contêm variação. Variação: " . Utils::jsonEncode($variationLog) . "\n";
                } else {
                    echo "[PROCESS][LINE:".__LINE__."] Pegando $skuProduct. Não contêm variação.\n";
                }

                $this->formatProduct($existVariation, $product);
            }
            ECHO "\n##### FIM PÁGINA: (mostrados: $bodyRequest->showing de $bodyRequest->total, limite de $bodyRequest->limit) ".date('H:i:s')."\n\n";
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
    private function formatProduct(bool $existVariation, object $product): void
    {
        $skuProductPai  = trim($product->sku);
        $idProductPai   = trim($product->id);

        // verificar se esse sku já existe na loja
        $verifyProduct = $this->toolProduct->getProductForSku($skuProductPai);

        // não é variação, cadastrar como produto simples.
        if (!$existVariation) {
            // SKU não localizado na loja. Deve tentar cadastrar
            if (!$verifyProduct) {
                try {
                    $this->toolProduct->sendProduct($this->toolProduct->getDataFormattedToIntegration($product));
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Produto $skuProductPai cadastrado com sucesso\n";

                    // produto criado, recuperei o ID do sku e definirei os atributos para a categoria do produto
                    // muitas vezes o produto chegará não categorizado então esse cenário não acontecerá
                    $verifyProduct = $this->toolProduct->getProductForSku($skuProductPai);
                    $attributes = $this->toolProduct->getAttributeProduct($verifyProduct["id"], $skuProductPai);
                    if (!empty($attribute)) {
                        $this->toolProduct->setAttributeProduct($verifyProduct['id'], $attributes);
                    }

                    $this->toolProduct->updateProductIdIntegration($skuProductPai, $idProductPai);
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                }
            }
            // SKU localizado na loja
            else {
                // produto atualizado com código da integradora
                if ($verifyProduct['product_id_erp'] != $idProductPai) {
                    $this->toolProduct->updateProductIdIntegration($skuProductPai, $idProductPai);
                    echo "[PROCESS][LINE:".__LINE__."] Atualizado código de integração do produto ($skuProductPai)\n";
                } else {
                    echo "[PROCESS][LINE:" . __LINE__ . "] Produto $skuProductPai já cadastrado\n";
                }
            }
        }
        // É variação, então precisa ler os dados do produto e em seguida ler os skus para cadastrar na variação.
        else {
            // Produto pai não localizado na loja. Deve tentar cadastrar.
            if (!$verifyProduct) {
                try {
                    $this->toolProduct->sendProduct($this->toolProduct->getDataFormattedToIntegration($product));
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Produto $skuProductPai cadastrado com sucesso\n";

                    // produto criado, recuperei o ID do sku e definirei os atributos para a categoria do produto
                    // muitas vezes o produto chegará não categorizado então esse cenário não acontecerá
                    $verifyProduct = $this->toolProduct->getProductForSku($skuProductPai);
                    $attributes = $this->toolProduct->getAttributeProduct($verifyProduct["id"], $skuProductPai);
                    if (!empty($attribute)) {
                        $this->toolProduct->setAttributeProduct($verifyProduct['id'], $attributes);
                    }

                    $this->toolProduct->updateProductIdIntegration($skuProductPai, $idProductPai);
                    foreach ($product->variations as $variation) {
                        $this->toolProduct->updateProductIdIntegration($skuProductPai, $variation->id, $variation->sku);
                    }
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                }
            }
            // sku do produto pai encontrado na loja, precisa ver se todos os skus estão cadastrados nas variações
            else {
                $dataProductFormatted = $this->toolProduct->getDataFormattedToIntegration($product);

                // ler todos os skus, para saber se todas as variações estão cadastradas
                foreach ($dataProductFormatted['variations']['value'] as $variation) {
                    $verifyVariation = $this->toolProduct->getVariationForSkuAndSkuVar($skuProductPai, $variation['sku']);
                    // variação não localizada cadastrada no produto pai
                    if (!$verifyVariation) {
                        try {
                            $this->toolProduct->sendVariation($dataProductFormatted, $variation['sku'], $skuProductPai);
                            $this->toolProduct->updateProductIdIntegration($skuProductPai, $variation['id'], $variation['sku']);
                            echo "[SUCCESS][LINE:".__LINE__."] Variação {$variation['sku']} cadastrada com sucesso no produto ($skuProductPai)\n";
                        } catch (InvalidArgumentException $exception) {
                            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                        }
                    }
                    // sku localizada, cadastrada como variação no produto
                    else {
                        echo "[PROCESS][LINE:".__LINE__."] Variação {$variation['sku']} já cadastrada no produto ({$verifyVariation['prd_id']})\n";
                        // Variação atualizada com código da integradora
                        if ($verifyVariation['variant_id_erp'] != $variation['id']) {
                            $this->toolProduct->updateProductIdIntegration($skuProductPai, $variation['id'], $variation['sku']);
                            echo "[PROCESS][LINE:".__LINE__."] Atualizado código de integração da variação ({$variation['sku']}) do Pai ($skuProductPai)\n";
                        }
                    }
                }
            }
        }
    }
}