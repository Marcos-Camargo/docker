<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/Vtex/CreateProduct run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/tiny/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\tiny\ToolsProduct;

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
        $pages                  = 1;
        $urlGetProduct          = "produto.obter.php";
        $urlGetProducts         = 'produtos.pesquisa.php';
        $queryGetProduct        = array('query' => array('id' => null));
        $queryGetProducts       = array('query' => array('pagina' => 1, 'situacao' => 'A'));
        $arrayProductErroCheck  = array();

        for ($page = 1; $page <= $pages; $page++) {

            $queryGetProducts['query']['pagina'] = $page;

            // consulta a lista de produtos
            try {
                $request = $this->toolProduct->request('GET', $urlGetProducts, $queryGetProducts);
            } catch (GuzzleHttp\Exception\ClientException | InvalidArgumentException $exception) {
                echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                return false;
            }

            $bodyRequest = Utils::jsonDecode($request->getBody()->getContents());
            $regProducts = $bodyRequest->retorno->produtos;

            // define o total de páginas a serem lidas
            if ($pages === $page && $bodyRequest->retorno->numero_paginas != 1) {
                $pages = $bodyRequest->retorno->numero_paginas;
            }

            ECHO "\n##### INÍCIO PÁGINA: ($page até $pages) ".date('H:i:s')."\n\n";

            foreach ($regProducts as $register) {
                echo  "------------------------------------------------------------\n";

                $id_produto = $register->registro->id_produto ?? $register->produto->id;
                $this->toolProduct->setUniqueId($id_produto);

                // recupera dados do produto
                try {
                    $queryGetProduct['query']['id'] = $id_produto;
                    $request = $this->toolProduct->request('GET', $urlGetProduct, $queryGetProduct);
                } catch (GuzzleHttp\Exception\ClientException | InvalidArgumentException $exception) {
                    echo "[ERRO][LINE: " . __LINE__ . "] {$exception->getMessage()}\n";
                    continue;
                }

                // Descodificar content recuperado
                $product = Utils::jsonDecode($request->getBody()->getContents());
                $product = $product->retorno->produto ?? false;

                // Produto não encontrado
                if ($product === false) {
                    echo "[ERRO][LINE:".__LINE__."] Produto $id_produto não encontrado.\n";
                    continue;
                }

                /**
                 * Um produto que contém variações, será mostrado apenas as suas variações NA LISTA DE PREÇO, o pai não será mostrado
                 * Deverá fazer um get na variação e obter o produto para cadastra-lo
                 * Consequentemente posteriormente, caso chegue outra variação, não deverá ler novamente
                 */

                $existVariation = in_array($product->tipoVariacao, ['V', 'P']);
                $skuProduct     = trim($product->codigo);

                // Recupera o código do produto pai/normal. Variação não considero
                $idProduct = $existVariation ? $product->idProdutoPai : $product->id;

                // valida se o produto já foi lido anteriormente
                if (in_array($idProduct, $arrayProductErroCheck)) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto $idProduct já tentou integrar e deu erro/sucesso.\n";
                    continue;
                }
                $arrayProductErroCheck[] = $idProduct;

                // É uma variação! Preciso pegar os dados do produto pai
                if($existVariation) {
                    // Consulta o produto pai
                    $queryGetProduct['query']['id'] = $idProduct;
                    try {
                        $request = $this->toolProduct->request('GET', $urlGetProduct, $queryGetProduct);
                    } catch (GuzzleHttp\Exception\ClientException | InvalidArgumentException $exception) {
                        echo "[ERRO][LINE: " . __LINE__ . "] {$exception->getMessage()}\n";
                        continue;
                    }

                    $product = Utils::jsonDecode($request->getBody()->getContents());
                    $product = $product->retorno->produto ?? false;

                    // Produto não encontrado
                    // Pedro Henrique =D
                    if ($product === false) {
                        echo "[ERRO][LINE:".__LINE__."] Produto $id_produto não encontrado.\n";
                        continue;
                    }

                    $skuProduct = trim($product->codigo);
                }

                $this->toolProduct->setUniqueId($skuProduct);

                if ($existVariation) {
                    $variation = property_exists($product, 'variacoes') ? $product->variacoes : array();
                    if (is_string($variation)) {
                        $variation = [];
                        $this->log_data('batch', 'CREATEPRODUCT/TINY/VARIATION', json_encode([
                            'store_id' => $this->toolProduct->store,
                            'tiny_product_id' => $id_produto,
                            'product_data' => $product
                        ], JSON_UNESCAPED_UNICODE), "E");
                    }
                    $variationLog = array_map(function ($item){
                        return $item->variacao->codigo;
                    }, $variation);
                    echo "[PROCESS][LINE:".__LINE__."] Pegando $skuProduct. Contêm variação. Variação: " . Utils::jsonEncode($variationLog) . "\n";
                } else {
                    echo "[PROCESS][LINE:".__LINE__."] Pegando $skuProduct. Não contêm variação.\n";
                }

                $this->formatProduct($existVariation, $product);
            }
            echo "\n##### FIM PÁGINA: ($page até $pages) ".date('H:i:s')."\n";
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
        $skuProductPai  = trim($product->codigo);
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
        // É variação, então precisa ler os dados do produto e em seguida ler os skus para cadastrar na variação
        else {
            // Produto pai não localizado na loja. Deve tentar cadastrar
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
                    foreach ($product->variacoes as $variation) {
                        $variation = $variation->variacao;
                        $this->toolProduct->updateProductIdIntegration($skuProductPai, $variation->id, $variation->codigo);
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