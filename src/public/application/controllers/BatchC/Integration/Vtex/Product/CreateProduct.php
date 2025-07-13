<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration/Vtex/Product/CreateProduct run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Vtex/Main.php";
require APPPATH . "controllers/BatchC/Integration/Vtex/Product/Product.php";

class CreateProduct extends Main
{
    private $product;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);

        $this->setJob('CreateProduct');
    }
    // php index.php BatchC/Integration/Vtex/Product/CreateProduct run null 66
    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        /* inicia o job */
        $this->setIdJob($id);

        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id='.$id.' store_id='.$store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Pegando produtos para cadastrar \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        // Recupera os produtos
        $this->getProducts();

        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Recupera os produtos
     *
     * @return bool
     */
    public function getProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        $perPage = 49;
        $regStart = 0;
        $regEnd = $perPage;
        $haveProductList = true;

        while ($haveProductList) {
            $url = "api/catalog_system/pvt/products/GetProductAndSkuIds?_from={$regStart}&_to={$regEnd}&fq=isAvailablePerSalesChannel_{$this->salesChannel}:1";
            $dataProducts = $this->sendREST($url);

            if (!in_array($dataProducts['httpcode'], [200, 206])) {
                echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "E");
                $haveProductList = false;
                continue;
            }

            $regProducts = json_decode($dataProducts['content']);
            $regProducts->data = (array)$regProducts->data;

            if (!count($regProducts->data)) {
                $haveProductList = false;
                continue;
            }
            ECHO "\n##### INÍCIO PÁGINA: ({$regStart} até {$regEnd})\n\n";

            foreach ($regProducts->data as $productId => $sku) {
                //$url = "api/catalog_system/pub/products/search?fq=productId:{$productId}&fq=isAvailablePerSalesChannel_{$this->salesChannel}:1";
                $url = "api/catalog_system/pub/products/search?fq=productId:{$productId}";
                $dataProducts = $this->sendREST($url);

                if (!in_array($dataProducts['httpcode'], [200, 206])) {
                    echo "Erro para buscar o produto {$productId} de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    $this->log_data('batch', $log_name, "Erro para buscar o produto {$productId} de url={$url}, retorno=" . json_encode($dataProducts), "E");
                    continue;
                }

                $product = json_decode($dataProducts['content'])[0] ?? false;

                if ($product === false) continue;

                $existVariation = false;
                $id_produto     = $product->productId;
                $skuProductPai  = $product->productId;
                $nameProduct    = $product->productName;

                foreach ($product->items as $keySku => $skus) {

                    if (
                        ($existVariation && !isset($skus->variations)) ||
                        (!$existVariation && isset($skus->variations) && $keySku != 0)
                    ) {
                        echo "Foram encontrados variações, mas nem todos os SKUs contem variação.\n";
                        //$this->db->trans_rollback();
                        $this->log_integration("Erro para integrar produto - {$product->productId}", "Foram encontrados variações para o produto, mas nem todos os SKUs contem variação.<br><br>Produto: {$product->productId}<br>Nome: {$nameProduct}", "E");
                        continue 2;
                    }

                    if (isset($skus->variations)) $existVariation = true;
                }

                if ($existVariation)
                    $skuProductPai = 'P_'.$skuProductPai;

                $this->setUniqueId($id_produto); // define novo unique_id
                echo "Pegando {$skuProductPai}. Variação: ".json_encode($existVariation)."...\n";

                $this->createProduct($existVariation, $product);
                echo  "------------------------------------------------------------\n";
            }
            ECHO "\n##### FIM PÁGINA: ({$regStart} até {$regEnd})\n";
            $regStart += $perPage;
            $regEnd += $perPage;
        }
    }

    /**
     * Validação para cadastro do produto
     *
     * @param  bool     $existVariation Existe variação?
     * @param  object   $product        Dados do produto, vindo do ERP
     * @return bool                     Retorna estado da criação do produto
     */
    private function createProduct(bool $existVariation, object $product): bool
    {
        $skuProductPai  = $product->productId;

        if (!$existVariation) {
            for ($countSku = 0; $countSku < count($product->items); $countSku++) {

                $verifyProduct = $this->product->getProductForSku($product->items[$countSku]->itemId);
                if (!$verifyProduct) {

                    if (!$this->sendProduct($product, $countSku)) continue;

                    $verifyProduct = $this->product->getProductForSku($product->items[$countSku]->itemId);
                    $this->createSpecification($verifyProduct["id"], $product->productId);
                    
                    // $this->createSpecification($verifyProduct["id"], $product->items[$countSku]->itemId);

                    echo "Produto {$product->items[$countSku]->itemId} cadastrado com sucesso\n";
                } else // encontrou o produto pelo código da vtex
                    echo "Produto {$product->items[$countSku]->itemId} já cadastrado\n";
            }
        } else {
            $verifyProduct = $this->product->getProductForSku('P_'.$product->productId);

            // não encontrou o produto pelo SKU
            if (!$verifyProduct) {
                if (!$this->sendProduct($product)) return false;
                echo "Produto {$skuProductPai} cadastrado com sucesso\n";

                $verifyProduct = $this->product->getProductForSku('P_'.$product->productId);
                $this->createSpecification($verifyProduct["id"], $product->productId);

            // encontrou o produto pelo código da vtex
            } else {

                // consultar variações
                for ($countSku = 0; $countSku < count($product->items); $countSku++) {

                    $verifyVariation = $this->product->getVariationForSkuAndSkuVar('P_'.$product->productId, $product->items[$countSku]->itemId);
                    if (!$verifyVariation) {
                        if (!$this->sendVariation($product->items[$countSku], 'P_'.$product->productId)) continue;

                        echo "Variação {$product->items[$countSku]->itemId} cadastrada com sucesso\n";
                    } else // encontrou o produto pelo código da vtex
                        echo "Variação {$product->items[$countSku]->itemId} já cadastrada\n";
                }

                //echo "Produto {$skuProductPai} já cadastrado\n";
            }
        }

        return true;
    }

    /**
     * Enviar produto para cadastro
     *
     * @param  object   $product    Dados do produto, vindo do ERP
     * @param  null|int $countSku   Posição do sku para cadastro
     * @return bool                 Situação do cadastro
     */
    private function sendProduct(object $product, int $countSku = null): bool
    {
        $this->db->trans_begin();

        $id_produto     = $product->productId;
        $skuProductPai  = $countSku !== null ? $product->items[$countSku]->itemId : $product->productId;
        $nameProduct    = $countSku !== null ? $product->items[$countSku]->nameComplete : $product->productName;
        $productCreate = $this->product->createProduct($product, $countSku);

        if ($productCreate['success'] === null) {
            echo "Produto={$id_produto} não está na politica comercial {$this->salesChannel}\n";
            $this->db->trans_rollback();
            return false;
        }

        if ($productCreate['success'] === false) {
            echo "Não foi possível cadastrar o produto={$id_produto} encontrou um erro, retorno=" . json_encode($productCreate) . "\n";
            $this->db->trans_rollback();
            $this->log_integration("Erro para integrar produto - {$id_produto}", 'Existem algumas pendências no cadastro do produto na plataforma de integração <ul><li>' . implode('</li><li>', $productCreate['message']) . "</li></ul> <br> <strong>SKU</strong>: {$skuProductPai}<br><strong>Descrição</strong>: {$nameProduct}", "E");
            return false;
        }

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            echo "ocorreu um erro\n";
            return false;
        }
        $this->db->trans_commit();

        $this->log_integration("Produto {$skuProductPai} integrado", "<h4>Novo produto integrado com sucesso</h4> <ul><li>O produto {$skuProductPai}, foi criado com sucesso</li></ul><br><strong>SKU</strong>: {$skuProductPai}<br><strong>Nome do Produto</strong>: {$nameProduct}", "S");

        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        $this->log_data('batch', $log_name, "Produto CRIADO!!! payload=" . json_encode($product));

        return true;
    }

    /**
     * Enviar variação para cadastro
     *
     * @param  object   $variation  Dados da variação, vindo do ERP
     * @param  string   $skuPai     Posição do sku para cadastro
     * @return bool                 Situação do cadastro
     */
    private function sendVariation(object $variation, string $skuPai): bool
    {
        $this->db->trans_begin();

        $id_variation   = $variation->itemId;
        $nameVariation    = $variation->nameComplete;

        $variationCreate = $this->product->createVariation($variation, $skuPai);

        if ($variationCreate['success'] === null) {
            echo "Variação={$id_variation} não está na politica comercial {$this->salesChannel}\n";
            $this->db->trans_rollback();
            return false;
        }

        if ($variationCreate['success'] === false) {
            echo "Não foi possível cadastrar a variação={$id_variation} encontrou um erro, dados=" . json_encode($variation) . " retorno=" . json_encode($variationCreate) . "\n";
            $this->db->trans_rollback();
            $this->log_integration("Erro para integrar variação - {$id_variation}", "Existem algumas pendências no cadastro da variação na plataforma de integração <ul><li>{$variationCreate['message']}</li></ul> <br> <strong>SKU</strong>: {$skuPai}<br><strong>Descrição</strong>: {$nameVariation}", "E");
            return false;
        }

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            echo "ocorreu um erro\n";
            return false;
        }
        $this->db->trans_commit();

        $this->log_integration("Variação {$id_variation} do produto {$skuPai} integrada", "<h4>Nova variação integrada com sucesso</h4> <ul><li>A variação {$id_variation} do produto {$skuPai}, foi criada com sucesso</li></ul><br><strong>SKU</strong>: {$id_variation}<br><strong>SKU Variação</strong>: {$id_variation}<br><strong>Descrição</strong>: {$nameVariation}", "S");

        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        $this->log_data('batch', $log_name, "Produto CRIADO!!! payload=" . json_encode($variation));

        return true;
    }

    private function createSpecification($prd_id, $productId)
    {
        $url = "api/catalog_system/pvt/products/{$productId}/specification";
        $dataSpecification = $this->sendREST($url);

        if (!in_array($dataSpecification['httpcode'], [200, 206])) {
            echo "Erro para buscar as especificações do produto {$productId} de url={$url}, retorno=" . json_encode($dataSpecification) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar as especificações do produto {$productId} de url={$url}, retorno=" . json_encode($dataProducts), "E");
            return ;
        }

        $specifications = json_decode($dataSpecification['content'], true);

        if (count($specifications) > 0) 
        {
            foreach ($specifications as $specification)
            {
                $value = '';
                foreach ($specification["Value"] as $v) 
                {
                    $value .= ($value != '' ? ', ' : '') . $v;
                }
                if ($value != '') {
                    $this->model_products->insertAttributesCustomProduct($prd_id, $specification["Name"], $value, $user = null);
                }
            }
        }
    }
}