<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/bling_v3/CreateProduct run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/bling_v3/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\bling_v3\ToolsProduct;
use Integration\Integration_v2\CredentialValidationException;

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
            $success = $this->getProductsToCreate();
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
     * Recupera os produtos para cadastro
     *
     * @return  bool            Retorna se o get para criação de produtos ocorreu tudo certo e poderá atualizar a data da última vez que foi buscar
     * @throws  GuzzleException
     */
    public function getProductsToCreate(): bool
    {
        $log_name = $this->toolProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        echo  "------------------------------------------------------------\n";

        $page               = 1;
        $queryGetProducts   = array(
            'query' => array(
                'tipo'      => 'P',
                'pagina'    => $page,
                'limite'    => 100,
                'criterio'  => 2
            )
        );

        if ($this->toolProduct->dateStartJob && $this->toolProduct->dateLastJob) {
            $queryGetProducts['query']['dataAlteracaoInicial'] = $this->toolProduct->dateLastJob;
            $queryGetProducts['query']['dataAlteracaoFinal'] = $this->toolProduct->dateStartJob;
        }

        while (true) {
            $queryGetProducts['query']['pagina'] = $page;

            $urlGetProducts = "produtos";

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

            if (is_object($regProducts) && property_exists($regProducts, 'error')) {
                echo "[ERRO][LINE:".__LINE__."] {$regProducts->error->erros->message} - " . Utils::jsonEncode($regProducts, JSON_UNESCAPED_UNICODE) . "\n";
                $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$regProducts->error->erros->message} - " . Utils::jsonEncode($regProducts), "E");
                $this->toolProduct->log_integration(
                    "Produtos não integrados",
                    "<h4>Não foi possível integrar os produtos</h4> <p>{$regProducts->error->erros->message} - " . Utils::jsonEncode($regProducts, JSON_UNESCAPED_UNICODE) . "</p>",
                    "E"
                );
                return false;
            }

            $regProducts = $regProducts->data;

            // Não tem produto na listagem, fim da lista
            if (empty($regProducts)) {
                break;
            }

            foreach ($regProducts as $product) {
                $product_id = $product->id;

                // Produto não está na multiloja
                $product_loja = null;
                if (!empty($this->toolProduct->credentials->loja_bling)) {
                    $product_loja = $this->toolProduct->getProductIntegrationByLojaAndProduct($product_id);
                    if (!$product_loja) {
                        echo "[PROCESS][LINE: " . __LINE__ . "] Produto $product->codigo não está na multiloja\n";
                        continue;
                    }
                }

                $product = $this->toolProduct->getDataProductIntegration($product_id);

                if (!$product) {
                    echo "[ERROR][LINE: " . __LINE__ . "] Produto $product_id não encontrado\n";
                    continue;
                }

                $existVariation = !empty($product->variacoes);
                $skuProduct     = trim($product->codigo);

                if (empty($skuProduct)) {
                    $this->toolProduct->log_integration(
                        "Alerta para integrar o produto com id ($product_id)",
                        "<h4>Existem algumas pendências no cadastro do produto, para corrigir na integradora</h4><p>Produto/variação não contém código SKU</p><br><strong>Descrição</strong>: $product->descricaoCurta",
                        "E");
                    echo "[PROCESS][LINE: " . __LINE__ . "] Produto $product_id não contém um código SKU\n";
                    continue;
                }

                $this->toolProduct->setUniqueId($skuProduct);

                if ($existVariation) {
                    echo "[PROCESS][LINE:".__LINE__."] Pegando $skuProduct. Contêm variação.\n";
                } else {
                    echo "[PROCESS][LINE:".__LINE__."] Pegando $skuProduct. Não contêm variação.\n";
                }

                $this->formatProduct($existVariation, $product, $product_loja);
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
     * @param  bool         $existVariation Existe variação?
     * @param  object       $product        Dados do produto, vindo do ERP
     * @param  object|null  $product_loja   Dados do produto em loja
     * @return void                         Retorna estado da criação do produto
     */
    private function formatProduct(bool $existVariation, object $product, object $product_loja = null): void
    {
        $skuProductPai = trim($product->codigo);
        $idProductPai = $product->id;

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
                    if (!empty($attributes)) {
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
                    if (!empty($attributes)) {
                        $this->toolProduct->setAttributeProduct($verifyProduct['id'], $attributes);
                    }

                    $this->toolProduct->updateProductIdIntegration($skuProductPai, $idProductPai);
                    foreach ($product->variacoes as $variation) {
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

                    $product_variation = getArrayByValueIn($product->variacoes, $variation['sku'], 'codigo');
                    $variation_id = null;
                    if ($product) {
                        $variation_id = $product_variation->id;
                    }

                    // variação não localizada cadastrada no produto pai
                    if (!$verifyVariation) {
                        try {
                            $this->toolProduct->sendVariation($dataProductFormatted, $variation['sku'], $skuProductPai);
                            $this->toolProduct->updateProductIdIntegration($skuProductPai, $variation_id, $variation['sku']);
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
                            $this->toolProduct->updateProductIdIntegration($skuProductPai, $variation_id, $variation['sku']);
                            echo "[PROCESS][LINE:".__LINE__."] Atualizado código de integração da variação ({$variation['sku']}) do Pai ($skuProductPai)\n";
                        }
                    }
                }

                // produto atualizado com código da integradora
                if ($verifyProduct['product_id_erp'] != $idProductPai) {
                    $this->toolProduct->updateProductIdIntegration($skuProductPai, $idProductPai);
                    echo "[PROCESS][LINE:".__LINE__."] Atualizado código de integração do produto ($skuProductPai)\n";
                } else {
                    echo "[PROCESS][LINE:" . __LINE__ . "] Produto $skuProductPai já cadastrado\n";
                }
            }
        }
    }
}