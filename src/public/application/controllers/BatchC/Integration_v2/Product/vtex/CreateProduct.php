<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/Vtex/CreateProduct run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/vtex/ToolsProduct.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\vtex\ToolsProduct;

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
        $log_name = $this->toolProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        if (!property_exists($this->toolProduct->credentials, 'sales_channel_vtex') || empty($this->toolProduct->credentials->sales_channel_vtex)) {
            $this->toolProduct->log_integration(
                "Ocorreu um erro para importar o catálogo",
                "O canal de vendas para a VTEX não está configurado.",
                "E"
            );
            return false;
        }
        $perPage            = 49;
        $regStart           = 0;
        $regEnd             = $perPage;
        $urlGetProduct      = "api/catalog_system/pub/products/search";
        $urlGetProducts     = "api/catalog_system/pvt/products/GetProductAndSkuIds";
        $queryGetProduct    = array('query' => array('fq' => "productId:0"));
        $queryGetProducts   = array(
            'query' => array(
                '_from' => 0,
                '_to'   => 0,
                'fq'    => "isAvailablePerSalesChannel_{$this->toolProduct->credentials->sales_channel_vtex}:1"
            )
        );

        while (true) {

            // novos valores de filtro para pegar mais produtos
            $queryGetProducts['query']['_from'] = $regStart;
            $queryGetProducts['query']['_to'] = $regEnd;

            // consulta a lista de produtos
            try {
                $request = $this->toolProduct->request('GET', $urlGetProducts, $queryGetProducts);
            } catch (GuzzleHttp\Exception\ClientException | InvalidArgumentException $exception) {
                $error_message = $error_message_log = $exception->getMessage();
                if (ENVIRONMENT !== 'production') {
                    $error_message_log .= "<br><b>Endpoint:</b> $urlGetProducts<br><b>Options:</b> ".json_encode($queryGetProducts);
                }
                echo "[ERRO][LINE:".__LINE__."] {$error_message}\n";
                if ($error_message == 'Too Many Requests') {
                    $rnd = rand(10,40);
                    echo "Dormindo ".$rnd." segundos e vou tentar novamente\n";
                    sleep($rnd);
                    continue; 
                }
                $this->toolProduct->log_integration(
                    "Ocorreu um erro para importar o catálogo",
                    $error_message_log,
                    "E");
                return false;
            }

            $regProducts = Utils::jsonDecode($request->getBody()->getContents());
            $regProducts->data = (array)$regProducts->data;

            // Não tem produto na listagem, fim da lista
            if (!count($regProducts->data)) {
                break;
            }
            ECHO "\n##### INÍCIO PÁGINA: ($regStart até $regEnd) ".date('H:i:s')."\n\n";

            foreach ($regProducts->data as $productId => $sku) {
                $queryGetProduct['query']['fq'] = "productId:$productId";
                $queryGetProduct['query']['sc'] = $this->toolProduct->credentials->sales_channel_vtex;

                // recupera dados do produto
                try {
                    $request = $this->toolProduct->request('GET', $urlGetProduct, $queryGetProduct);
                } catch (GuzzleHttp\Exception\ClientException | InvalidArgumentException $exception) {
                    $error_message = $error_message_log = $exception->getMessage();
                    if (ENVIRONMENT !== 'production') {
                        $error_message_log .= "<br><b>Endpoint:</b> $urlGetProduct<br><b>Options:</b> ".json_encode($queryGetProduct);
                    }
                    echo "[ERRO][LINE: " . __LINE__ . "] $error_message\n";
                    $this->toolProduct->log_integration(
                        "Ocorreu um erro para importar o produto com ID $productId",
                        $error_message_log,
                        "E");
                    continue;
                }

                // Descodificar content recuperado
                $product = Utils::jsonDecode($request->getBody()->getContents());
                $product = $product[0] ?? false;

                // Produto não encontrado
                // Foi aberto chamado para esse cenário
                // https://support.vtex.com/hc/pt-br/requests/485032
                if ($product === false) {
                    echo "[ERRO][LINE:".__LINE__."] Produto $productId não encontrado.\n";
                    continue;
                }

                $existVariation = false;
                $id_produto     = $product->productId;
                $skuProductPai  = $product->productId;
                $nameProduct    = $product->productName;
                $arrVariations  = array();
                $checkVarLogErr = array();

                $this->toolProduct->setUniqueId("P_$id_produto");

                foreach ($product->items as $keySku => $skus) {

                    array_push($arrVariations, $skus->itemId);
                    array_push($checkVarLogErr, "SKU ($skus->itemId) possui os tipos de: " . implode(',', $skus->variations ?? array()));

                    // se um sku tem variação, todos os skus precisam ter variação.
                    if (
                        ($existVariation && !isset($skus->variations)) ||
                        (!$existVariation && isset($skus->variations) && $keySku != 0)
                    ) {
                        echo "[ERRO][LINE:".__LINE__."] Foram encontrados variações, mas nem todos os SKUs do produto ($id_produto) contem variação.\n[ERRO][LINE:".__LINE__."] " . implode("\n[ERRO][LINE:".__LINE__."] ", $checkVarLogErr) . "\n";
                        echo  "------------------------------------------------------------\n";
                        $this->toolProduct->log_integration(
                            "Erro para integrar produto com ID $product->productId",
                            "<h4>Foram encontrados variações para o produto $product->productId, mas nem todos os SKUs contem variação.</h4><p>Se um SKU contém variação, os demais SKUs do produto também deve conter variação</p><p>" . implode('<br>', $checkVarLogErr) . "</p><strong>ID Produto:</strong> $product->productId<br><strong>Nome:</strong> $nameProduct",
                            "E");
                        continue 2;
                    }

                    if (isset($skus->variations)) {
                        $existVariation = true;
                    }
                }

                if ($existVariation) {
                    $skuProductPai = "P_$skuProductPai";
                    echo "[PROCESS][LINE:".__LINE__."] Pegando $skuProductPai. Contêm variação. Variação: " . Utils::jsonEncode($arrVariations) . "\n";
                } else {
                    echo "[PROCESS][LINE:".__LINE__."] Pegando $skuProductPai. Não contêm variação.\n";
                }

                $this->formatProduct($existVariation, $product);
                echo  "------------------------------------------------------------\n";
            }
            echo "\n##### FIM PÁGINA: ($regStart até $regEnd) ".date('H:i:s')."\n";
            $regStart += $perPage;
            $regEnd += $perPage;
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
        $skuProductPai = $product->productId;

        // não é variação, então precisa ler todos os skus desse produto e cadastrar como produto simples.
        if (!$existVariation) {
            // ler todos os skus, para cadastrar como produtos simples
            for ($countSku = 0; $countSku < count($product->items); $countSku++) {
                $skuIntegration = $product->items[$countSku]->itemId;
                $this->toolProduct->setUniqueId($skuIntegration);

                // verificar se esse sku já existe na loja
                $verifyProduct = $this->toolProduct->getProductForSku($skuIntegration);
                // SKU não localizado na loja. Deve tentar cadastrar
                if (!$verifyProduct) {
                    try {
                        $this->toolProduct->sendProduct($this->toolProduct->getDataFormattedToIntegration($product, $countSku));
                        echo "[SUCCESS][LINE:".__LINE__."] Produto $skuIntegration cadastrado com sucesso\n";

                        // produto criado, recuperei o ID do sku e definirei os atributos para a categoria do produto
                        // muitas vezes o produto chegará não categorizado então o atributo entrará como customizado.
                        $verifyProduct      = $this->toolProduct->getProductForSku($skuIntegration);
                        $attributesProduct  = $this->toolProduct->getAttributeProduct($verifyProduct["id"], $product->productId);
                        $attributesSku      = $this->toolProduct->getAttributeSku($verifyProduct["id"], $skuIntegration);
                        $attributes         = array_merge($attributesProduct, $attributesSku);
                        if (!empty($attributes)) {
                            $this->toolProduct->setAttributeProduct($verifyProduct['id'], $attributes);
                        }

                        $this->toolProduct->updateProductIdIntegration($skuIntegration, $skuIntegration);
                    } catch (InvalidArgumentException $exception) {
                        echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                    }
                }
                // SKU localizado na loja
                else {
                    // produto atualizado com código da integradora
                    if ($verifyProduct['product_id_erp'] != $skuIntegration) {
                        $this->toolProduct->updateProductIdIntegration($skuIntegration, $skuIntegration);
                        echo "[PROCESS][LINE:".__LINE__."] Produto $skuProductPai já cadastrado\n";
                    }
                    echo "[PROCESS][LINE:".__LINE__."] Produto $skuIntegration já cadastrado\n";
                }
            }
        }
        // É variação, então precisa ler os dados do produto e em seguida ler os skus para cadastrar na variação
        else {
            $skuIntegration = "P_$product->productId";
            // verificar se esse sku já existe na loja
            $verifyProduct = $this->toolProduct->getProductForSku($skuIntegration);

            $this->toolProduct->setUniqueId($skuIntegration);

            // Produto pai não localizado na loja. Deve tentar cadastrar
            if (!$verifyProduct) {
                try {
                    $this->toolProduct->sendProduct($this->toolProduct->getDataFormattedToIntegration($product));
                    echo "[SUCCESS][LINE:".__LINE__."] Produto $skuProductPai cadastrado com sucesso\n";

                    // produto criado, recuperei o ID do sku e definirei os atributos para a categoria do produto
                    // muitas vezes o produto chegará não categorizado então o atributo entrará como customizado.
                    $verifyProduct = $this->toolProduct->getProductForSku($skuIntegration);

                    $attributesProduct = $this->toolProduct->getAttributeProduct($verifyProduct["id"], $product->productId);
                    $attributesSku = array();
                    foreach ($product->items as $variation) {
                        $attributesSku = array_merge($attributesSku, $this->toolProduct->getAttributeSku($verifyProduct["id"], $variation->itemId));
                    }
                    $attributes = array_merge($attributesProduct, $attributesSku);
                    if (!empty($attributes)) {
                        $this->toolProduct->setAttributeProduct($verifyProduct['id'], $attributes);
                    }

                    $this->toolProduct->updateProductIdIntegration($skuIntegration, $skuIntegration);
                    foreach ($product->items as $variation) {
                        $this->toolProduct->updateProductIdIntegration($skuIntegration, $variation->itemId, $variation->itemId);
                    }
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                }
            }
            // sku do produto pai encontrado na loja, precisa ver se todos os skus estão cadastrados nas variações
            else {
                // ler todos os skus, para saber se todas as variações estão cadastradas
                for ($countSku = 0; $countSku < count($product->items); $countSku++) {
                    $skuVar = $product->items[$countSku]->itemId;

                    $verifyVariation = $this->toolProduct->getVariationForSkuAndSkuVar($skuIntegration, $skuVar);
                    // variação não localizada cadastrada no produto pai
                    if (!$verifyVariation) {
                        try {
                            $dataProductFormatted = $this->toolProduct->getDataFormattedToIntegration($product);
                            $this->toolProduct->sendVariation($dataProductFormatted, $product->items[$countSku]->itemId, $skuIntegration);
                            $this->toolProduct->updateProductIdIntegration($skuIntegration, $skuVar, $skuVar);
                            echo "[SUCCESS][LINE:".__LINE__."] Variação $skuVar cadastrada com sucesso no produto ($skuIntegration)\n";
                        } catch (InvalidArgumentException $exception) {
                            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
                        }
                    }
                    // sku localizada, cadastrada como variação no produto
                    else {
                        echo "[PROCESS][LINE:".__LINE__."] Variação $skuVar já cadastrada no produto ({$verifyVariation['prd_id']})\n";
                        // Variação atualizada com código da integradora
                        if ($verifyVariation['variant_id_erp'] != $skuVar) {
                            $this->toolProduct->updateProductIdIntegration($skuIntegration, $skuVar, $skuVar);
                            echo "[PROCESS][LINE:".__LINE__."] Atualizado código de integração da variação ($skuVar) do Pai ($skuIntegration)\n";
                        }
                    }
                }
            }
        }
    }
}