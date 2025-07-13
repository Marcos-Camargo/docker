<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/bling_v3/UpdateProduct run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/bling_v3/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\bling_v3\ToolsProduct;

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
     * Recupera os produtos e variações para atualização de preço e estoque
     *
     * @return  bool            Retorna se o get para atualização de produtos ocorreu tudo certo e poderá atualizar a data da última vez que foi buscar
     * @throws  GuzzleException
     */
    public function getProductToUpdate(): bool
    {
        $log_name = $this->toolProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;
        echo  "------------------------------------------------------------\n";

        $page               = 1;
        $queryGetProducts   = array(
            'query' => array(
                'tipo'      => 'P',
                'pagina'    => $page,
                'limite'    => 100,
                'criterio'  => 5
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

                // SKU não localizado
                if (!$this->toolProduct->getProductForSku($skuProduct)) {
                    echo "[PROCESS][LINE:".__LINE__."] Produto ID $skuProduct. Não localizado.\n";
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