<?php
require_once APPPATH . "controllers/BatchC/Integration/Integration.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Parsers/ProductParser.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/LoadApiKeys.trait.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/LoadIntegrationUser.php";

class CreateProduct extends Integration
{
    use LoadApiKey, LoadIntegrationUser;
    private $url;
    private $url_conectala = "http://nginx";
    private $params = "";
    private $product_unset = ["data_criacao", "data_modificacao", "id_externo", "url_video_youtube"];
    private $variation_unset = ["data_criacao", "data_modificacao", "id_externo", "descricao_completa", "resource_uri", "url", "apelido", "categorias", "gtin", "marca", "nome", "pai", "tipo", "url_video_youtube"];
    private $grade_unset = ["resource_uri", "id_externo"];


    const RETRY_REQUEST_LIMIT = 15;
    private $retryRequest;

    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => true,
        );
        $this->load->library('form_validation');
        $this->load->model('model_api_integrations');
        $this->load->model('model_job_integration');
        $this->load->model('model_settings');
        $this->session->set_userdata($logged_in_sess);
        $this->setTypeIntegration("LojaIntegrada");
       
		$this->setJob('CreateProduct');
		$this->retryRequest = 0;
    }
    // php index.php BatchC/Integration/LojaIntegrada/Product/CreateProduct run null 63
    public function run($id = null, $store)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        $limite_imagens_aceitas_api = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if(!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) { $limite_imagens_aceitas_api = 6;}

        if (!$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        /* inicia o job */
        $this->setIdJob($id);
		
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

	
        /* faz o que o job precisa fazer */
        echo "Iniciando teste do parser.\n";
        try {
            $this->loadApiKey($store);
        } catch (Exception $e) {
            echo ("Sistema não configurado para os parametros usados por este sistema.");
            echo ($e->getMessage());
            $this->log_integration("Erro ao carregar configurações de integração", $e->getMessage(), 'E');
            return;
        }
		
		$this->setDateStartJob();
        $this->setLastRun();
		
        $this->store = $store;
        $store = $this->model_stores->getStoresData($store);
        $this->company = $store['company_id'];
        $user = $this->loadApiIntegrationUserByStore($store);
        $job_integration = $this->model_job_integration->find([
            'integration' => 'lojaintegrada',
            'job_path' => 'Integration/LojaIntegrada/Product/CreateProduct',
            'store_id' => $store['id']
        ]);
        $header_opt = [
            'x-user-email: ' . $user['email'],
            'x-api-key: ' . $store["token_api"],
            'x-store-key: ' . $store['id'],
        ];
        $data_string = date('Y-m-d%20H:i:s', strtotime('-365 days'));
        if (!empty($job_integration['last_run'])) {
            $data_string = date('Y-m-d%20H:i:s', strtotime('-2 days', strtotime($job_integration['last_run'])));
        }

        try {
            $response_product = $this->getDataByUrl("/v1/produto", 'data_modificacao__gte=' . $data_string);
            $sucess = true;
            while (true) {
                $response_product = json_decode($response_product["content"], true);
                if ($response_product["objects"] && !is_array($response_product["objects"])) {
                    break;
                }
                foreach ($response_product["objects"] as $key => $object) {
                    echo("Pegando produto numero: {$object['id']}\n");
                    $this->setUniqueId($object['id']);
                    // ativo=true&removido=false&
                    // if ($object["removido"] || !$object["removido"]) {
                    //     continue;
                    // }
                    try {
                        $product = $this->getProducts($object);
                        if ($product != null) {
                            $save_product = $this->model_products->getByProductIdErpAndStore($product["id"], $store['id']);
                            if(!$save_product) {
                                echo("Produto não encontrado com idERP\n");
                                $productSkuStore = $this->model_products->getProductBySkuAndStore(
                                    $product['sku'],
                                    $store['id']
                                );
                                echo("Pesquisado produto por SKU e loja\n");
                                $save_product = empty($productSkuStore['product_id_erp']) ? $productSkuStore : $save_product;
                            }
                            if (!$save_product) {
                                $productLojaIntegrada_id = $product['id'];
                                $categoriaLojaIntegrada = $product["categorias"];
                                $product["category"] = $product["categorias"];
                                $productLojaIntegrada = $product;

                                $product = ProductParser::productIn($product, $product['preco'], $product['estoque']);

                                if ($product['active'] === 'disabled') {
                                    echo("Produto inativado ou removido na plantaforma, não devo salvar.\n");
                                    continue;
                                }
                                if (count($product['images']) > $limite_imagens_aceitas_api) {   // removo as imagens a mais
                                    $this->log_integration("Quantidade de imagens acima de ".$limite_imagens_aceitas_api." no produto " . $product['sku'], "Atençao: o produto SKU: " . $product['sku'] . " possui mais do que ".$limite_imagens_aceitas_api." imagens, somente as primeiras ".$limite_imagens_aceitas_api." imagens serão importadas.", 'W');
                                    $tot = count($product['images']);
                                    for ($x = $limite_imagens_aceitas_api; $x <= $tot; $x++) {
                                        unset($product['images'][$x]);
                                    }
                                }
                                $response = $this->sendRequest($this->url_conectala . '/Api/V1/Products', json_encode(['product' => $product]), 'POST', true, $header_opt);
                                $content = json_decode($response["content"], true);
                                if (!$content["success"]) {
                                    echo("Falha na criação do produto com o codigo: {$response["httpcode"]}\nResposta: " . $response["content"] . "\n");
                                    $sucess = false;
                                    if (array_key_exists("message", $content)) {
                                        $this->log_integration("Falha na criação do produto " . $product['sku'], "Erro na criação do produto SKU: " . $product['sku'] . "<BR>Erro: " . $content["message"] . "<BR>Retorno:" . $response["content"], 'E');
                                    } else {
                                        $this->log_integration("Falha na criação do produto", "Falha na criação do produto com o codigo: {$response["httpcode"]}<BR>Resposta: " . $response["content"] . "<BR>" . $response["content"], 'E');
                                    }
                                } else {
                                    echo("Cadastro realizado com sucesso: {$response["httpcode"]}\nE a resposta: " . $response["content"] . "\n");
                                    $save_product = $this->model_products->getProductBySkuAndStore($product["sku"], $store['id']);
                                    echo("Produto persistido com a id: {$save_product['id']}\n");
                                    if ($productLojaIntegrada['variacoes']) {
                                        foreach ($productLojaIntegrada['variacoes'] as $variacao) {
                                            $updated_var = $this->model_products->updateVarBySku(['variant_id_erp' => $variacao["id"]], $save_product['id'], $variacao['sku']);
                                            if ($updated_var) {
                                                echo("Atualizaçao para inserção da id_erp feita com sucesso para variação {$variacao['sku']}.\n");
                                                // $this->log_integration("Sucesso na inserção do produto.", "Dados do produto inserido com sucesso.<br>Codigo http: {$response["httpcode"]}<br>E resposta: <br>" . $response["content"], 'S');
                                            } else {
                                                $this->log_integration("Erro na inserção do produto.", "Atualizaçao para inserção da id_erp resultou em erro para variação {$variacao['sku']}.", 'E');
                                                echo("Atualizaçao para inserção da id_erp resultou em erro para variação {$variacao['sku']}.\n");
                                            }
                                        }
                                    }
                                    $updated = $this->model_products->update(['product_id_erp' => $productLojaIntegrada_id, 'category_imported' => $categoriaLojaIntegrada], $save_product['id']);
                                    if ($updated) {
                                        echo("Atualizaçao para inserção da id_erp feita com sucesso.\n");
                                        $this->log_integration("Produto inserido com sucesso {$save_product['sku']}.", "Produto({$save_product['name']}-{$save_product['sku']}) cadadastrado com sucesso.", 'S');
                                    } else {
                                        echo("Atualizaçao para inserção da id_erp resultou em erro.\n");
                                        $this->log_integration("Falha ao atualizar o produto {$save_product['sku']}.", "Produto({$save_product['name']}-{$save_product['sku']}) houve um erro interno ao gravar.", 'E');
                                    }

                                }
                            } else {
                                echo("Atualização de produto com a id: {$save_product['id']}\n");
                                $productLojaIntegrada_id = $product['id'];
                                $productLojaIntegrada = $product;
                                $product = ProductParser::productIn($product, $product['preco'], $product['estoque']);
                                // dd($product);
                                if (isset($product['types_variations'])) {
                                    foreach ($product['product_variations'] as $variant_loja) {
                                        $data_to_update = [
                                            'price' => $variant_loja['price'],
                                            'list_price' => $variant_loja['list_price'],
                                            'qty' => $variant_loja['qty'],
                                        ];
                                        $this->model_products->updateVarBySku($data_to_update, $save_product['id'], $variant_loja['sku']);
                                    }
                                }
                                unset($product['types_variations']);
                                unset($product['product_variations']);
                                $response = $this->sendRequest($this->url_conectala . '/Api/V1/Products/' . $product['sku'], json_encode(['product' => $product]), 'PUT', true, $header_opt);
                                $content = json_decode($response["content"], true);
                                if (!$content["success"]) {
                                    if ($content['message'] == "Product already integrated with marketplace, cannot receive updates. Only stock and price allowed.") {
                                        //  já foi integrado, faço só preço e estoque
                                        $product_price = array(
                                            'extra_operating_time' => $product['extra_operating_time'],
                                            'price' => $product['price'],
                                            'list_price' => $product['list_price'],
                                            'qty' => $product['qty']
                                        );
                                        $response = $this->sendRequest($this->url_conectala . '/Api/V1/Products/' . $product['sku'], json_encode(['product' => $product_price]), 'PUT', true, $header_opt);
                                        $content = json_decode($response["content"], true);
                                        if (!$content["success"]) {
                                            echo("Falha na atualização do produto com o codigo: {$response["httpcode"]}\nE resposta: " . $response["content"] . "\n");
                                            $this->log_integration("Erro na atualização do produto {$product['sku']}.", "Falha na atualização do produto({$product['name']}-{$product['sku']}) com o codigo: {$response["httpcode"]}<br>E resposta:<br> " . $response["content"] . "\n", 'E');
                                            $sucess = false;
                                        } else {
                                            $updated = $this->model_products->update(['product_id_erp' => $productLojaIntegrada_id], $save_product['id']);
                                            echo("Atualização de preço e estoque realizado com sucesso: {$response["httpcode"]}\nE a resposta: " . $response["content"] . "\n");
                                            $this->log_integration("Sucesso na atualização de preço e estoque do produto {$product['sku']}.", "O produto({$product['name']}-{$product['sku']}) foi sincronizado com os dados que existem na loja integrada.", 'S');
                                            if ($productLojaIntegrada['variacoes']) {
                                                foreach ($productLojaIntegrada['variacoes'] as $variacao) {
                                                    echo "UPDATE VAR: \n";
                                                    print_r(['variant_id_erp' => $variacao["id"], 'sku' => $variacao['sku']]);
                                                    $updated_var = $this->model_products->updateVarBySku(['variant_id_erp' => $variacao["id"]], $save_product['id'], $variacao['sku']);
                                                }
                                            }
                                        }
                                    } else {
                                        echo("Falha na atualização do produto com o codigo: {$response["httpcode"]}\nE resposta: " . $response["content"] . "\n");
                                        $this->log_integration("Erro na atualização do produto {$product['sku']}.", "Falha na atualização do produto({$product['name']}-{$product['sku']}) com o codigo: {$response["httpcode"]}<br>E resposta:<br> " . $response["content"] . "\n", 'E');
                                        $sucess = false;
                                    }
                                } else {
                                    $updated = $this->model_products->update(['product_id_erp' => $productLojaIntegrada_id], $save_product['id']);
                                    echo("Atualização realizado com sucesso: {$response["httpcode"]}\nE a resposta: " . $response["content"] . "\n");
                                    $this->log_integration("Sucesso na atualização do produto {$product['sku']}.", "O produto({$product['name']}-{$product['sku']}) foi sincronizado com os dados que existem na loja integrada.", 'S');
                                    if ($productLojaIntegrada['variacoes']) {
                                        foreach ($productLojaIntegrada['variacoes'] as $variacao) {
                                            echo "UPDATE VAR 2: \n";
                                            print_r(['variant_id_erp' => $variacao["id"], 'sku' => $variacao['sku']]);
                                            $updated_var = $this->model_products->updateVarBySku(['variant_id_erp' => $variacao["id"]], $save_product['id'], $variacao['sku']);
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        echo("Erro encontrado. \n" . $e->getMessage() . "\n");
                        $this->log_integration("Erro no Cadastro.", "Erro encontrado. \n" . $e->getMessage() . "\n", 'E');
                    }
                }
                if (!$response_product["meta"]["next"]) {
                    break;
                }
                echo("Proxima pagina\n\n\n");
                $response_product = $this->getDataByFullUrl($response_product["meta"]["next"]);
            }
            if ($sucess) {
                $data = new DateTime();
                $data_string = $data->format("Y-m-d H:i:s");
                $this->model_job_integration->update($job_integration['id'], ['last_run' => $data_string]);
            }
        } catch (\Exception $e) {
            echo "Erro: " . $e->getMessage();
        }

		$this->saveLastRun();
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }
    public function getProducts($product)
    {
        $response_product = $this->getDataByUrl($product["resource_uri"]);
        $product = json_decode($response_product["content"], true);
        foreach ($this->product_unset as $unset_field) {
            unset($product[$unset_field]);
        }
        if (isset($product['pai'])) {
            // echo ("Produto com variação... aguardar para desenvolver.\n");
            return;
        } else {
            $response_estoque = $this->getDataByUrl("/api/v1/produto_estoque/" . $product['id']);
            $estoque = json_decode($response_estoque["content"], true);
            $response_price = $this->getDataByUrl("/api/v1/produto_preco/" . $product['id']);
            $preco = json_decode($response_price["content"], true);
            if (!empty($product['marca'])) {
                $response_brand = $this->getDataByUrl($product['marca']);
                $brand = json_decode($response_brand["content"], true);
                $product['marca'] = $brand;
            }
            $product['preco'] = $preco;
            $product['estoque'] = $estoque;
            $filhos = [];
            $categorias = "";
            foreach ($product["categorias"] as &$category) {
                $descCateg = "";
                while ($category) {
                    $response_categoria = $this->getDataByUrl($category);
                    $categoria = json_decode($response_categoria["content"], true);
                    $category = $categoria["categoria_pai"];
                    $descCateg = $descCateg != "" ? $categoria["nome"] . " > " . $descCateg : $categoria["nome"] . $descCateg;
                }
                $categorias = empty($categorias) ? $descCateg : $categorias . "," . $descCateg;
            }
            $product['categorias'] = $categorias;
            if (!empty($product['filhos'])) {
                foreach ($product['filhos'] as $filho) {
                    $response_filho = $this->getDataByUrl($filho);
                    $filho = json_decode($response_filho["content"], true);
                    if ($filho["usado"]) {
                        continue;
                    }
                    foreach ($this->variation_unset as $unset_field) {
                        unset($filho[$unset_field]);
                    }
                    $response_estoque = $this->getDataByUrl("/api/v1/produto_estoque/" . $filho['id']);
                    $filho['estoque'] = json_decode($response_estoque["content"], true);
                    $response_preco = $this->getDataByUrl("/api/v1/produto_preco/" . $filho['id']);
                    $filho['preco'] = json_decode($response_preco["content"], true);
                    $response_imagem = $this->getDataByUrl("/api/v1/produto_imagem/", "produto=" . $filho['id']);
                    $response_imagem = json_decode($response_imagem["content"], true);
                    echo (json_encode($response_imagem) . "\n");
                    $variacoes = [];
                    foreach ($filho["variacoes"] as $variacao) {
                        $response_variacao = $this->getDataByUrl($variacao);
                        $variacao = json_decode($response_variacao["content"], true);
                        // dd($filho, $response_imagem);
                        foreach ($this->grade_unset as $unset_field) {
                            unset($filho[$unset_field]);
                        }
                        $response_grade = $this->getDataByUrl($variacao["grade"]);
                        $grade = json_decode($response_grade["content"], true);
                        if ($response_grade['httpcode'] != 200) {
                            if (strpos($variacao["grade"], '8948') !== false) { // tmp
                                $grade['nome_visivel'] = 'Cor';
                            } else {
                                continue;
                            }
                        }
                        unset($grade['variacoes']);
                        unset($variacao["grade"]);
                        $variacao["tipo"] = !empty(trim($grade["nome_visivel"])) ? $grade["nome_visivel"] : $grade['nome'];
                        array_push($variacoes, $variacao);
                    }
                    $filho['variacoes'] = $variacoes;
                    array_push($filhos, $filho);
                }
            }
            $product["variacoes"] = $filhos;
            return $product;
        }
    }
    private function getDataByUrl($endpoint, $aditionalParams = '')
    {
        $url = $this->url . $endpoint . "?" . $this->params . '&' . $aditionalParams;
        echo ($url . "\n");
        $response = $this->sendREST($url);
        if ($response['httpcode'] != 200) {
            $objResponse = json_decode($response['content'], true);
            if ($objResponse && $objResponse['msg']
                && ($response['httpcode'] == 429 ||
                    (strpos(strtolower($objResponse['msg']), 'throttling') !== false)
                    || (strpos(strtolower($objResponse['msg']), 'elevada') !== false))
                && ($this->retryRequest <= self::RETRY_REQUEST_LIMIT)
            ) {
                $this->retryRequest++;
                echo "{$this->retryRequest} retentativa de requisição.\n";
                sleep(30);
                return $this->getDataByUrl($endpoint, $aditionalParams);
            }
            $this->log_integration(
                "Falha ao executar a requisição ({$response['httpcode']})",
                "{$response['content']}",
                'E'
            );
            if (!empty(array_intersect(['chave', 'plano'], explode(' ', strtolower($response['content']))))) {
                throw new Exception("Falha na requisição para \n{$url}\nStatus de retorno: {$response['httpcode']}\nDados de retorno: {$response['content']}\n");
            }
        }
        $this->retryRequest = 0;
        return $response;
    }
    private function getDataByFullUrl($endpoint)
    {
        $url = $this->url . $endpoint;
        echo ($url . "\n");
        $response = $this->sendREST($url);
        if ($response['httpcode'] != 200) {
            $objResponse = json_decode($response['content'], true);
            if ($objResponse && $objResponse['msg']
                && (strpos(strtolower($objResponse['msg']), 'throttling') !== false)
                && ($this->retryRequest <= self::RETRY_REQUEST_LIMIT)
            ) {
                $this->retryRequest++;
                echo "{$this->retryRequest} retentativa de requisição.\n";
                sleep(5);
                return $this->getDataByFullUrl($endpoint);
            }
            $this->log_integration(
                "Falha ao executar a requisição ({$response['httpcode']})",
                "{$response['content']}",
                'E'
            );
            if (!empty(array_intersect(['chave', 'plano'], explode(' ', strtolower($response['content']))))) {
                throw new Exception("Falha na requisição para \n{$url}\nStatus de retorno: {$response['httpcode']}\nDados de retorno: {$response['content']}\n");
            }
        }
        $this->retryRequest = 0;
        return $response;
    }
    public function sendRequest($url, $data = '', $method = 'GET', $newRequest = true, $header_opt = array())
    {
        echo ($url . "\n");
        // $url="Api/V1/Products";
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_VERBOSE, false);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 1000);
        if ($method == "GET") {
            curl_setopt($curl_handle, CURLOPT_URL, $url);
        } elseif ($method == "POST" || $method == "PUT") {
            if ($method == "PUT") {
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
            }

            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
        }
        $content = array(
            "Content-Type: application/json",
            "accept: application/json;charset=UTF-8",
        );
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array_merge($content, $header_opt));
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

        curl_close($curl_handle);

        $header['httpcode'] = $httpcode;
        $header['content'] = $response;

        return $header;
    }
}
