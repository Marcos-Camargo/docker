<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration/Vtex/Product/UpdateProduct run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Vtex/Main.php";
require APPPATH . "controllers/BatchC/Integration/Vtex/Product/Product.php";

class UpdateProduct extends Main
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

        $this->setJob('UpdateProduct');
    }

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
        echo "Pegando produtos para atualizar \n";

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
				
				// Leio  o produto para ver se está ativo na Vtex - // Start BUGS-682
				$url = "api/catalog/pvt/product/{$productId}";
       			$dataProducts = $this->sendREST($url);
				if (!in_array($dataProducts['httpcode'], [200, 206])) {
                    echo "Erro para buscar o produto {$productId} de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    $this->log_data('batch', $log_name, "Erro para buscar o produto {$productId} de url={$url}, retorno=" . json_encode($dataProducts), "E");
                    continue;
                }
				$product_vtex = json_decode($dataProducts['content']);
				if ($product_vtex === false) {
					echo "Erro do decode\n";
					continue;
				}
				$product->is_active = $product_vtex->IsActive; // End BUGS-682
				
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
                        $this->db->trans_rollback();
                        $this->log_integration("Erro para atualizar produto - {$product->productId}", "Produto está configurado para receber variações, mas nem todos os SKUs contem variação.<br><br>Produto: {$product->productId}<br>Nome: {$nameProduct}", "E");
                        continue 2;
                    }

                    if (isset($skus->variations)) $existVariation = true;
                }

                if ($existVariation)
                    $skuProductPai = 'P_'.$skuProductPai;

                $this->setUniqueId($id_produto); // define novo unique_id
                echo "Pegando {$skuProductPai}. Variação: ".json_encode($existVariation)."...\n";

                $this->updateProduct($existVariation, $product);
                echo  "------------------------------------------------------------\n";
            }
            ECHO "\n##### FIM PÁGINA: ({$regStart} até {$regEnd})\n";
            $regStart += $perPage;
            $regEnd += $perPage;
        }
        return true;
    }

	function checkStatusChange(object $product, $product_local) // BUGS-682
	{
		// Muda o status de acordo com a integração
		if (isset($product->is_active)) { 
			$status = $product->is_active ? 1 : 2;
			$status_desc = $product->is_active ? "Ativo" : "Inativo";
			if ($status!= $product_local['status']) {
				echo 'Atualizando o status do produto '.$product_local['id'].' de '.$product_local['status'].' para '.$status ."\n";
				$this->model_products->update(array('status' =>$status), $product_local['id'], 'Alterando status que veio da Interagção Vtex');
				$this->log_integration("Mudança de status do produto {$product->productId} para {$status_desc}", "<h4>Produto atualizado com sucesso</h4> <ul><li>O produto {$product->productId} alterou para  {$status_desc}", "S");
			}
		}
	}

    /**
     * Validação para atualização do produto
     *
     * @param  bool     $existVariation Existe variação?
     * @param  object   $product        Dados do produto, vindo do ERP
     * @return bool                     Retorna estado da atualização do produto
     */
    private function updateProduct(bool $existVariation, object $product): bool
    {
        $skuProductPai  = $product->productId;

        if (!$existVariation) {
            for ($countSku = 0; $countSku < count($product->items); $countSku++) {

                $verifyProduct = $this->product->getProductForSku($product->items[$countSku]->itemId);
                if (!$verifyProduct) {
                    echo "Produto {$skuProductPai} não está cadastrado ainda\n";
                } else { // encontrou o produto pelo código da vtex
					$this->checkStatusChange($product, $verifyProduct); // BUGS-682
                    if (!$this->sendProduct($product, $countSku)) continue;

                    echo "Produto {$product->items[$countSku]->itemId} atualizado com sucesso\n";
                }
            }
        } else {
            $verifyProduct = $this->product->getProductForSku('P_'.$product->productId);

            // não encontrou o produto pelo SKU
            if (!$verifyProduct)
                echo "Produto {$skuProductPai} não está cadastrado ainda\n";
            else { // encontrou o produto pelo código da vtex
            	$this->checkStatusChange($product, $verifyProduct); // BUGS-682
                if (!$this->sendProduct($product)) return false;
		
                echo "Produto {$skuProductPai} atualizado com sucesso\n";
            }
        }

        return true;
    }

    /**
     * Enviar produto para atualizar
     *
     * @param  object   $product    Dados do produto, vindo do ERP
     * @param  null|int $countSku   Posição do sku para atualizar
     * @return bool                 Situação da atualização
     */
    private function sendProduct(object $product, int $countSku = null): bool
    {
        $this->db->trans_begin();

        $id_produto     = $product->productId;
        $skuProductPai  = $countSku ? $product->items[$countSku]->itemId : $product->productId;
        $nameProduct    = $countSku ? $product->items[$countSku]->nameComplete : $product->productName;

        $productUpdate = $this->product->updateProduct($product, $countSku);

        if ($productUpdate['success'] === 'SalesChannels') {
            echo "Produto={$id_produto} não está na politica comercial {$this->salesChannel}\n".json_encode($productUpdate)."\n";
            $this->db->trans_rollback();
            return false;
        }

        if ($productUpdate['success'] === null) {
            $this->db->trans_rollback();
            return false;
        }

        if ($productUpdate['success'] === false) {
            echo "Não foi possível atualizar o produto={$id_produto} encontrou um erro, dados=" . json_encode($product) . " retorno=" . json_encode($productUpdate) . "\n";
            $this->db->trans_rollback();
            $this->log_integration("Erro para atualizar produto - {$id_produto}", 'Existem algumas pendências no cadastro do produto na plataforma de integração <ul><li>' . implode('</li><li>', $productUpdate['message']) . "</li></ul> <br> <strong>SKU</strong>: {$skuProductPai}<br><strong>Descrição</strong>: {$nameProduct}", "E");
            return false;
        }

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            echo "ocorreu um erro\n";
            return false;
        }
        $this->db->trans_commit();

        $this->log_integration("Produto {$skuProductPai} atualizado", "<h4>Produto atualizado com sucesso</h4> <ul><li>O produto {$skuProductPai}, foi atualizado com sucesso</li></ul><br><strong>SKU</strong>: {$skuProductPai}<br><strong>Nome do Produto</strong>: {$nameProduct}", "S");

        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        $this->log_data('batch', $log_name, "Produto atualizado!!! payload=" . json_encode($product));

        return true;
    }
}