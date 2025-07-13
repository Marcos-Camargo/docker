<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/Vtex/UpdateProduct run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/bling/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\bling\ToolsProduct;

class UpdateProduct extends BatchBackground_Controller
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
            if ($this->getProductToUpdate()) {
                if (!empty($this->toolProduct->credentials->loja_bling)) {
                    if ($this->getProductToUpdate(true)) {
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
     * Recupera os produtos e variações para atualização de preço e estoque
     *
     * @param   bool $filterMultiloja   O filtro será pela data de alteração da multi loja
     * @return  bool                    Retorna se o get para atualização de produtos ocorreu tudo certo e poderá atualizar a data da última vez que foi buscar
     * @throws  GuzzleException
     */
    public function getProductToUpdate(bool $filterMultiloja = false): bool
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
                try {
                    $error = Utils::jsonDecode($exception->getMessage());
                    if (isset($error->retorno->erros->erro->cod) && $error->retorno->erros->erro->cod == 16) {
                        echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()} - " . Utils::jsonEncode($queryGetProducts, JSON_UNESCAPED_UNICODE) . "\n";
                        return false;
                    }
                } catch (Exception $exception) {
                    return false;
                }
                echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()} - " . Utils::jsonEncode($queryGetProducts, JSON_UNESCAPED_UNICODE) . "\n";
                //$this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()} - " . Utils::jsonEncode($queryGetProducts), "E");
                continue;
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
                $skuProduct     = trim($product->codigoPai ?? $product->codigo);

                if (in_array($skuProduct, $this->skusAlreadyRead)) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto ($id_produto) já lido e realizado a tentativa de atualização anteriormente\n";
                    continue;
                }
                $this->skusAlreadyRead[] = $skuProduct;

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

                // Produto não tem um código SKU
                if (empty($skuProduct)) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto ID $id_produto. Não contêm um código SKU.\n";
                    continue;
                }

                // SKU não localizado
                if (!$this->toolProduct->getProductForSku($skuProduct)) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto ID $id_produto. Não localizado.\n";
                    continue;
                }

                $this->toolProduct->setUniqueId($skuProduct);

                // atualiza o produto
                $dataProductFormatted = $this->toolProduct->getDataFormattedToIntegration($product);
                $update = $this->toolProduct->updateProduct($dataProductFormatted);

                if ($existVariation) {
                    echo "[PROCESS][LINE:".__LINE__."] Atualização do produto pai ($skuProduct).Status da atualização:".Utils::jsonEncode($update)."\n";
                    foreach ($dataProductFormatted['variations']['value'] as $variation) {
                        $update = $this->toolProduct->updateVariation($variation, $skuProduct);
                        echo "[PROCESS][LINE:".__LINE__."] Atualização da variação ({$variation['sku']}) do produto pai ($skuProduct). Status da atualização:".Utils::jsonEncode($update)."\n";
                    }
                } else {
                    echo "[PROCESS][LINE:".__LINE__."] Atualização do produto simples ($skuProduct).Status da atualização:".Utils::jsonEncode($update)."\n";
                }
                echo "------------------------------------------------------------\n";
            }
            $page++;
        }

        return true;
    }
}