<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/Vtex/CreateProduct run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/bling/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\bling\ToolsProduct;

class CreateProduct extends BatchBackground_Controller
{
    /**
     * @var ToolsProduct
     */
    private $toolProduct;

    /**
     * @var array Skus já lidos
     */
    private $skusAlreadyRead = array();

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
            if ($this->getProductsToCreate()) {
                if (!empty($this->toolProduct->credentials->loja_bling)) {
                    if ($this->getProductsToCreate(true)) {
                        $success = true;
                    }
                } else {
                    $success = true;
                }
            }
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
     * @param   bool $filterMultiloja   O filtro será pela data de alteração da multi loja
     * @return  bool                    Retorna se o get para criação de produtos ocorreu tudo certo e poderá atualizar a data da última vez que foi buscar
     * @throws  GuzzleException
     */
    public function getProductsToCreate(bool $filterMultiloja = false): bool
    {
        $log_name = $this->toolProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        if ($filterMultiloja) {
            echo "[PROCESS][LINE:".__LINE__."] Filtrando por produtos alterados na multiloja\n";
        } else {
            echo "[PROCESS][LINE:".__LINE__."] Filtrando por produtos alterados no cadastro\n";
        }
        echo  "------------------------------------------------------------\n";

        $filterDate         = $filterMultiloja ? 'dataAlteracaoLoja' : 'dataAlteracao';
        $page               = 1;
        $queryGetProducts   = array(
            'query' => array(
                'estoque'   => 'S',
                'imagem'    => 'S',
                'filters'   => 'situacao[A];tipo[P]'
            )
        );

        if ($this->toolProduct->dateStartJob && $this->toolProduct->dateLastJob) {
            $queryGetProducts['query']['filters'] .= ";$filterDate"."[{$this->toolProduct->dateLastJob} TO {$this->toolProduct->dateStartJob}]";
        }

        while (true) {

            $urlGetProducts = "produtos/page=$page";

            // consulta a lista de produtos
            try {
                $request = $this->toolProduct->request('GET', $urlGetProducts, $queryGetProducts);
            } catch (GuzzleHttp\Exception\ClientException | InvalidArgumentException $exception) {
                echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()} - " . Utils::jsonEncode($queryGetProducts, JSON_UNESCAPED_UNICODE) . "\n";
                $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()} - " . Utils::jsonEncode($queryGetProducts), "E");
                $this->toolProduct->log_integration(
                    "Produtos não integrados",
                    "<h4>Não foi possível integrar os produtos</h4> <p>{$exception->getMessage()} - " . Utils::jsonEncode($queryGetProducts, JSON_UNESCAPED_UNICODE) . "</p>",
                    "E"
                );
                return false;
            }

            $regProducts = Utils::jsonDecode($request->getBody()->getContents());

            $codError = $regProducts->retorno->erros[0]->erro->cod ?? $regProducts->retorno->erros->erro->cod ?? 0;
            /**
             * 14 - Fim das páginas de produtos
             *
             * https://ajuda.bling.com.br/hc/pt-br/articles/360046940653-Respostas-de-erros-para-Desenvolvedores-API
             */
            if ($codError == 14) {
                echo "[PROCESS][LINE:".__LINE__."] não encontrou resultados para o filtro informados (" . Utils::jsonEncode($queryGetProducts) . ")\n";
                break;
            }

            $regProducts = $regProducts->retorno->produtos;

            // Não tem produto na listagem, fim da lista
            if (!count($regProducts)) {
                break;
            }

            foreach ($regProducts as $product) {

                $product = $product->produto;

                // Produto não está na multiloja
                if (!empty($this->toolProduct->credentials->loja_bling) && !property_exists($product, 'produtoLoja')) {
                    echo "[PROCESS][LINE: " . __LINE__ . "] Produto $product->codigo não está na multiloja\n";
                    continue;
                }

                $existVariation = property_exists($product, 'variacoes');
                $id_produto     = $product->id;
                $skuProduct     = trim($product->codigo);

                if (empty($skuProduct)) {
                    $this->toolProduct->log_integration(
                        "Alerta para integrar o produto com id ($id_produto)",
                        "<h4>Existem algumas pendências no cadastro do produto, para corrigir na integradora</h4><p>Produto/variação não contém código SKU</p><br><strong>Descrição</strong>: $product->descricao",
                        "E");
                    echo "[PROCESS][LINE: " . __LINE__ . "] Produto $id_produto não contém um código SKU\n";
                    continue;
                }

                // É uma variação, preciso pegar o produto pai e ver se todas as variações estão cadastradas
                if (property_exists($product, 'codigoPai')) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto ID $id_produto. É uma variação. Preciso ler o Pai\n";

                    try {
                        $product = $this->toolProduct->getDataProductIntegration($product->codigoPai);
                    } catch (GuzzleHttp\Exception\ClientException | InvalidArgumentException $exception) {
                        echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()} - " . Utils::jsonEncode($queryGetProducts, JSON_UNESCAPED_UNICODE) . "\n";
                        continue;
                    }

                    // Produto não está na multiloja
                    if (!empty($this->toolProduct->credentials->loja_bling) && !property_exists($product, 'produtoLoja')) {
                        echo "[PROCESS][LINE: " . __LINE__ . "] Produto $product->codigo não está na multiloja\n";
                        continue;
                    }

                    $existVariation = property_exists($product, 'variacoes');
                    $id_produto     = $product->id;
                    $skuProduct     = trim($product->codigo);
                }

                if (in_array($skuProduct, $this->skusAlreadyRead)) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto ($id_produto) já lido e realizado a tentativa de atualização anteriormente\n";
                    continue;
                }
                array_push($this->skusAlreadyRead, $skuProduct);

                // Produto não tem um código SKU
                if (empty($skuProduct)) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto ID $id_produto. Não contêm um código SKU.\n";
                    continue;
                }

                $this->toolProduct->setUniqueId($skuProduct);

                if ($existVariation) {
                    echo "[PROCESS][LINE:".__LINE__."] Pegando $skuProduct. Contêm variação. Variação: " . Utils::jsonEncode($product->variacoes) . "\n";
                } else {
                    echo "[PROCESS][LINE:".__LINE__."] Pegando $skuProduct. Não contêm variação.\n";
                }

                $this->formatProduct($existVariation, $product);
                echo  "------------------------------------------------------------\n";
            }
            echo  "------------------------------------------------------------\n";
            $page++;
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
        $skuProductPai = trim($product->codigo);

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

                    $this->toolProduct->updateProductIdIntegration($skuProductPai, $skuProductPai);
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                }
            }
            // SKU localizado na loja
            else {
                // produto atualizado com código da integradora
                if ($verifyProduct['product_id_erp'] != $product->codigo) {
                    $this->toolProduct->updateProductIdIntegration($skuProductPai, $skuProductPai);
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

                    $this->toolProduct->updateProductIdIntegration($skuProductPai, $skuProductPai);
                    foreach ($product->variacoes as $variation) {
                        $variation = $variation->variacao;
                        $this->toolProduct->updateProductIdIntegration($skuProductPai, $variation->codigo, $variation->codigo);
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
                            $this->toolProduct->updateProductIdIntegration($skuProductPai, $variation['sku'], $variation['sku']);
                            echo "[SUCCESS][LINE:".__LINE__."] Variação {$variation['sku']} cadastrada com sucesso no produto ($skuProductPai)\n";
                        } catch (InvalidArgumentException $exception) {
                            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                        }
                    }
                    // sku localizada, cadastrada como variação no produto
                    else {
                        echo "[PROCESS][LINE:".__LINE__."] Variação {$variation['sku']} já cadastrada no produto ({$verifyVariation['prd_id']})\n";
                        // Variação atualizada com código da integradora
                        if ($verifyVariation['variant_id_erp'] != $variation['sku']) {
                            $this->toolProduct->updateProductIdIntegration($skuProductPai, $variation['sku'], $variation['sku']);
                            echo "[PROCESS][LINE:".__LINE__."] Atualizado código de integração da variação ({$variation['sku']}) do Pai ($skuProductPai)\n";
                        }
                    }
                }
            }
        }
    }
}