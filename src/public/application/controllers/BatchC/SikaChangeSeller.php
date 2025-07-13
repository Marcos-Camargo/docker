<?php

require APPPATH . "libraries/CalculoFrete.php";

class SikaChangeSeller extends BatchBackground_Controller
{
    private $calculofrete;

    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_orders');
        $this->load->model('model_freights');
        $this->load->model('model_integrations');
        $this->load->model('model_frete_ocorrencias');
        $this->load->model('model_stores');
        $this->load->model('model_settings');
        $this->load->model('model_vtex_ult_envio');
        $this->load->model('model_products');
        $this->load->model('model_promotions');
        $this->load->model('model_company');
        $this->load->model('model_catalogs');
        $this->load->model('model_products_catalog');

        $this->calculofrete = new CalculoFrete();
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

        $this->changeSeller();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();

    }

    private function changeSeller()
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        // Pego a loja SIKA, que será a loja "master"
        $storeSika = $this->getDataStoreSika();
        if (!$storeSika) return false;
        // Pego os pedidos da loja "master" SIKA
        $orders = $this->model_orders->getOrdersByFilter("store_id = {$storeSika['id']} AND paid_status in (1,3) AND incidence_user is null");

        foreach ($orders as $order) {

            // Inicia transação
            $this->db->trans_begin();

            echo "\n--------------------------------\n\n";
            echo 'Trocar o pedido ' . $order['id'] . " de seller \n";

            // monta matriz com os dados para achar o novo seller, com os dados para consultar o frete
            $arrOrder = array();
            $arrOrder['cep']            = preg_replace('/[^0-9]/', '', $order['customer_address_zip']);
            $arrOrder['service']        = $order['ship_service_preview'];
            $arrOrder['ship_company']   = $order['ship_company_preview'];
            $arrOrder['state_customer'] = $order['customer_address_uf'];
            $arrOrder['store_id']       = $order['store_id'];
            $arrOrder['origin']         = $order['origin'];
            $arrOrder['id']             = $order['id'];
            $arrOrder['items']          = array();
            $itens = $this->model_orders->getOrdersItemData($order['id']);
            foreach ($itens as $itemIndex => $item) {
                array_push($arrOrder['items'], array(
                    "prd_catalog_id"=> $item['product_catalog_id'],
                    "prd_id"        => $item['product_id'],
                    "sku"           => $item['sku'],
                    "skumkt"        => $item['skumkt'],
                    "qty"           => (int)$item['qty'],
                    "rate"          => $item['rate'],
                    "pesobruto"     => $item['pesobruto'],
                    "largura"       => $item['largura'],
                    "altura"        => $item['altura'],
                    "profundidade"  => $item['profundidade'],
                    "category_id"   => $item['category_id'],
                    "variant"       => $item['variant']
                ));
            }

            // recuperar o seller com o melhor preço(prioridade) e a entrega mais rápida
            $sellersFreight = $this->getSellerWithFastDelivery($arrOrder);

            $newSellers      = $sellersFreight['arrayResult'];
            $storeNotFreight = $sellersFreight['storeNotFreight'];
            $withoutQuote    = false;

            echo "newSellers: ";
            echo json_encode($newSellers)."\n";

            if (!$newSellers) {
                echo "Encontrou erro para processar o pedido {$order['id']}\n";
                $this->db->trans_rollback();
                continue;
            }

            if (count($newSellers) === 1 && count($storeNotFreight) == 0) { // encontrou somente um seller
                $store_id = $newSellers[0]['store_id'];
                echo "encontrou somente 1 seller \n";
            } else { // encontrou mais que um seller, definir reputação com Caio

                echo "encontrou mais que 1 seller \n";

                $sellersWin = array();
                $tempIndicador = 0;

                if (count($newSellers) === 1 && $newSellers[0]['store_id'] === null) {
                    $newSellers = $storeNotFreight;
                    $withoutQuote = true;
                }

                echo 'newSellers='.json_encode($newSellers)."\n";

                foreach ($newSellers as $key => $newSeller) {

                    $store_id = $newSeller['store_id'];
                    $indicador = $this->getSellerIndex($store_id);

                    echo "seller: {$store_id}, indicador: {$indicador} \n";

                    if ($indicador > $tempIndicador) {
                        $sellersWin = array();
                        array_push($sellersWin, $store_id);
                        $tempIndicador = $indicador;
                    } elseif ($indicador == $tempIndicador)
                        array_push($sellersWin, $store_id);
                }

                if (!count($sellersWin)) {
                    echo "Não encontrou seller ganhador entre todos os sellers (".json_encode($newSellers)."\n";
                    $this->db->trans_rollback();
                    $this->log_data('batch', $log_name, "Não encontrou seller ganhador entre todos os sellers (".json_encode($newSellers)." - PEDIDO={$order['id']} - dataNewSeller=" . json_encode($newSellers), "E");
                    continue;
                }
                elseif (count($sellersWin) > 1) {
                    $rand = rand(0, count($sellersWin) - 1);
                    $store_id = $sellersWin[$rand] ?? null;

                    echo "teve empate nos indicadores rand: {$rand}, seller_win: {$store_id} \n";
                }
                elseif (count($sellersWin) === 1)
                    $store_id = $sellersWin[0] ?? null;
            }

            if ($store_id) { // encontrou um seller

                echo "seller_win: {$store_id} \n";

                foreach ($itens as $item) {

                    $prd = $this->model_products_catalog->getProductByProductCatalogIdAndStoreId($item['product_catalog_id'], $store_id);

                    // reduz estoque do novo seller
                    $this->model_products->reduzEstoque($prd['id'], $item['qty'], $item['variant'], $order['id']); // reduz estoque produto

                    // trocar item de seller
                    $this->model_orders->updateItenByOrderAndId($item['id'], [
                            'sku'        => $prd['sku'],
                            'product_id' => $prd['id'],
                            'company_id' => $prd['company_id'],
                            'store_id'   => $store_id
                        ]
                    );

                    echo "trocou o item de seller: ".json_encode([
                            'sku'        => $prd['sku'],
                            'product_id' => $prd['id'],
                            'company_id' => $prd['company_id'],
                            'store_id'   => $store_id
                        ])."\n";
                }

                //trocar pedido de seller
                $this->model_orders->updateByOrigin($order['id'], ['store_id' => $store_id, 'company_id' => $prd['company_id']]);
                // deixar o pedido com o new_order=1, para ser integrado no erp do novo seller, caso use integração
                $this->model_orders->updateOrderToIntegrationByOrderAndStatus($order['id'], $store_id, $order['paid_status'], ['new_order' => 1]);
                echo "Trocou o seller do pedido {$order['id']}, para o seller {$store_id}\n";
                $this->log_data('batch', $log_name, "Trocou o seller do pedido {$order['id']}, para o seller {$store_id} - dataNewSeller=" . json_encode($newSellers) . ' - order='.json_encode($order), "I");

            } elseif (count($newSellers) == 1) { // não encontrou seller para atender
                echo "Não encontrou seller para o pedido {$order['id']}, precisamos passar para operações cancelar \n";
                $this->createIncidence($order, 'Não encontrou seller para atender o pedido SIKA!');
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->log_data('batch', $log_name, "Erro para executar as queries de troca de seller. PEDIDO={$order['id']} - dataNewSeller=" . json_encode($newSellers), "E");
                continue;
            }

            $this->db->trans_commit();
        }
    }

    private function getSellerWithFastDelivery($order)
    {
        $arrServicos = array();
        $itemsToMeet = array();
        $arrTestaServico = array();
        $servico_nome = array(
            "pac"   => "03298",
            "sedex" => "03220",
            "mini"  => "04227"
        );
        $qtyProduct = array();
        $serviceSend = array("03298", "03220", "04227");
        $isCorreios = true;
        $itemsCatalog = array();

        $altura_correios = 0;
        $largura_correios = 0;
        $comprimento_correios = 0;
        $data_freight = array();
        $menor_medida_correios = array();
        $count_etiqueta_correios = 0;
        $cepDestino = $order['cep'];
        foreach ($order['items'] as $index => $item) {

            $qty_etiqueta = 0;

            $sql = "SELECT products_package FROM products WHERE id = ?";
            $query = $this->db->query($sql, array($item['prd_id']));
            $product = $query->row_array();
            $qtd_embalado_iten = $product['products_package'] ?? 1;

            $qtd_calulo = ceil($item['qty'] / $qtd_embalado_iten);
            $rate = $item['rate'];
            $profundidade_iten = $item['profundidade'];
            $largura_iten = $item['largura'];
            $altura_iten = $item['altura'];
            $productId = $item['prd_id'];
            $prdCatalogId = $item['prd_catalog_id'];

            array_push($itemsToMeet, $item['skumkt']);
            array_push($itemsCatalog, $prdCatalogId);

            for ($qty_iten = 1; $qty_iten <= $qtd_calulo; $qty_iten++) {

                $quantidade_iten = ($item['qty'] - ($qty_iten * $qtd_embalado_iten));

                if ($quantidade_iten >= 0) $quantidade_iten = $qtd_embalado_iten;
                else $quantidade_iten = $item['qty'] - (($qty_iten-1) * $qtd_embalado_iten);

                $comprimento_correios += $profundidade_iten;
                $largura_correios += $largura_iten;
                $altura_correios += $altura_iten;
                $peso_iten = $item['pesobruto'];

                if ($comprimento_correios > 70 || $largura_correios > 70 || $altura_correios > 70) {
                    $count_etiqueta_correios++;
                    $comprimento_correios = $profundidade_iten;
                    $largura_correios = $largura_iten;
                    $altura_correios = $altura_iten;
                }

                if ($largura_correios < $comprimento_correios) $menor_medida_correios[$count_etiqueta_correios] = "L";
                if ($altura_correios < $comprimento_correios) $menor_medida_correios[$count_etiqueta_correios] = "A";

                if ($comprimento_correios <= $largura_correios && $comprimento_correios <= $altura_correios) $menor_medida_correios[$count_etiqueta_correios] = "C";
                if ($largura_correios <= $comprimento_correios && $largura_correios <= $altura_correios) $menor_medida_correios[$count_etiqueta_correios] = "L";
                if ($altura_correios <= $comprimento_correios && $altura_correios <= $largura_correios) $menor_medida_correios[$count_etiqueta_correios] = "A";
                if ($altura_correios == $comprimento_correios && $altura_correios == $largura_correios) $menor_medida_correios[$count_etiqueta_correios] = "A";

                $peso_iten *= $quantidade_iten;
                $rate_iten = $rate * $quantidade_iten;

                if(isset($data_freight[$count_etiqueta_correios][$productId])) {
                    $rate_iten 			+= $data_freight[$count_etiqueta_correios][$productId]['rate'];
                    $peso_iten 			+= $data_freight[$count_etiqueta_correios][$productId]['peso_bruto'];
                    $tipo_volume_codigo = $data_freight[$count_etiqueta_correios][$productId]['tipo_volume_codigo'];

                    $qty_etiqueta++;
                } else {
                    // Consultar o Tipo_volume do produto para saber se pode ser enviado pelos correios
                    $sqlPrd 			= "SELECT category_id FROM products WHERE id = " . $productId;
                    $queryProd 			= $this->db->query($sqlPrd);
                    $category_id_array 	= $queryProd->row_array();  //Category_id esta como caracter no products
                    $cat_id 			= json_decode($category_id_array['category_id']);
                    $sqlVol 			= "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id = '{$cat_id[0]}')";
                    $queryVol 			= $this->db->query($sqlVol);
                    $rsVol 				= $queryVol->row_array();
                    $tipo_volume_codigo = $rsVol['codigo'];
                    $qty_etiqueta 		= 1;
                }

                $data_freight[$count_etiqueta_correios][$productId] = array(
                    'altura' 			 => $altura_iten,
                    'largura' 			 => $largura_iten,
                    'profundidade' 		 => $profundidade_iten,
                    'peso_bruto' 		 => $peso_iten,
                    'qty' 				 => $qty_etiqueta,
                    "rate" 				 => (float)$rate_iten,
                    "tipo_volume_codigo" => $tipo_volume_codigo,
                    "prd_id"             => $productId
                );

                if (!$this->calculofrete->verificaCorreios($data_freight[$count_etiqueta_correios][$productId]))
                    $isCorreios = false;
            }
        }

        echo "CORREIOS? ".json_encode($isCorreios)."\n";

        if ($isCorreios) {

            foreach ($data_freight as $index => $etiqueta) {

                $peso = 0;
                $comprimento = 0;
                $largura = 0;
                $altura = 0;
                $valor_declarado = 0;

                $arrServicos[$index] = array();
                foreach($etiqueta as $id_product => $product) {

                    if($menor_medida_correios[$count_etiqueta_correios] == null)
                        $menor_medida_correios[$count_etiqueta_correios] = "A";

                    if($product['profundidade'] == 0 || $product['altura'] == 0 || $product['largura'] == 0 || $product['peso_bruto'] == 0) {

                        echo 'Foi encontrado medidas zerada. Pedido=' . $order['id'] . " Produto={$id_product} Medidas=" . print_r(array('profundidade' => $product['profundidade'], 'altura' => $product['altura'], 'largura' => $product['largura'], 'peso_bruto' => $product['peso_bruto']), true) . "\n";
                        $this->db->trans_rollback();
                        $this->log_data('batch', 'ChangeSeller', 'Foi encontrado medidas zerada. Pedido=' . $order['id'] . " Produto={$id_product} Medidas=" . print_r(array('profundidade' => $product['profundidade'], 'altura' => $product['altura'], 'largura' => $product['largura'], 'peso_bruto' => $product['peso_bruto']), true), "E");
                        return false;
                    }

                    switch ($menor_medida_correios[$index]) {
                        case "L":
                            $comprimento = $comprimento < (float)$product['profundidade'] ? (float)$product['profundidade'] : $comprimento;
                            $altura = $altura < (float)$product['altura'] ? (float)$product['altura'] : $altura;

                            $largura += (float)$product['largura'] * (int)$product['qty'];
                            break;
                        case "C":
                            $altura = $altura < (float)$product['altura'] ? (float)$product['altura'] : $altura;
                            $largura = $largura < (float)$product['largura'] ? (float)$product['largura'] : $largura;

                            $comprimento += $product['profundidade'] * (int)$product['qty'];
                            break;
                        case "A":
                            $largura = $largura < (float)$product['largura'] ? (float)$product['largura'] : $largura;
                            $comprimento = $comprimento < (float)$product['profundidade'] ? (float)$product['profundidade'] : $comprimento;

                            $altura += (float)$product['altura'] * (int)$product['qty'];
                            break;
                    }

                    $peso += (float)$product['peso_bruto'];
                    $valor_declarado += (float)$product['rate'];
                }

                $arrTestaServico[$index][$id_product] = array();
                $arrTestaServico[$index][$id_product]['identificador'] = $id_product;
                $arrTestaServico[$index][$id_product]['cep_origem'] = '';
                $arrTestaServico[$index][$id_product]['cep_destino'] = $cepDestino;
                $arrTestaServico[$index][$id_product]['formato'] = "1";
                $arrTestaServico[$index][$id_product]['peso'] = number_format($peso, 3, ',', '');
                $arrTestaServico[$index][$id_product]['comprimento'] = number_format($this->calculofrete->ajustaComprimento($comprimento), 2, ',', '');
                $arrTestaServico[$index][$id_product]['altura'] = number_format($this->calculofrete->ajustaAltura($altura), 2, ',', '');
                $arrTestaServico[$index][$id_product]['largura'] = number_format($this->calculofrete->ajustaLargura($largura), 2, ',', '');
                $arrTestaServico[$index][$id_product]['valor_declarado'] = number_format($this->calculofrete->ajustaValorDeclarado($valor_declarado), 2, ',', '');
                $arrTestaServico[$index][$id_product]['mao_propria'] = "N";
                $arrTestaServico[$index][$id_product]['aviso_recebimento'] = "N";
                $arrTestaServico[$index][$id_product]['servicos'] = $serviceSend;
            }
            echo "arrTestaServico: ";
            echo json_encode($arrTestaServico)."\n";
        }

        $setting_sgp = $this->model_settings->getSettingDatabyName('token_sgp_correios');
        $token_sgp = $setting_sgp['value'];
        $arrayResult = array(array(
            'store_id'  => null,
            'deadline'  => null,
            'value'     => null
        ));

        // rj - 08532410
        // sc - 88049445
        // ba - 41500620
        // sp - 01034902

        $stores = $this->getDataStoreSika(false);

        if (!$stores || count($stores) === 0) {
            $erro = 'Não existe nenhuma loja vinculada a empresa '.'SIKA'.' ou tem mais de uma. Garanta que tenha uma loja a Sika.';
            echo $erro."\n";
            $this->log_data('batch','ChangeSeller', $erro ,"E");
            return false;
        }

        $storesHaveItems = array();
        foreach ($stores as $store) {

            $storeHaveCatalog = true;
            foreach ($order['items'] as $iten) {
                $qtyItemPrd   = $iten['qty'];
                $prdCatalogId = $iten['prd_catalog_id'];
                $sku          = $iten['sku'];
                $variant      = $iten['variant'];

                $prd = $this->model_products_catalog->getProductByProductCatalogIdAndStoreId($prdCatalogId, $store['id']);
                $qtyNewPrd = $prd['qty'];
                if (!empty($prd['has_variants'])) {
                    $rowVar = $this->model_products->getVariants($prd['id'], $variant);
                    $qtyNewPrd = $rowVar['qty'];
                    echo "É variação: {$variant}, pegar estoque da variação.\n";
                }
                if (!$prd || $qtyNewPrd < $qtyItemPrd) {

                    echo "store {$store['id']} não pode vender o item do catálogo:{$prdCatalogId}, id_prod:{$prd['id']} \n";

                    $storeHaveCatalog = false;
                    break;
                }
                echo "store {$store['id']} pode vender o item do catálogo:{$prdCatalogId}, id_prod:{$prd['id']} \n";

                if (!key_exists($store['id'], $storesHaveItems)) $storesHaveItems[$store['id']] = array();
                array_push($storesHaveItems[$store['id']], ['qty' => $qtyItemPrd, 'skumkt' => $sku, 'zipcode' => $store['zipcode'], 'cnpj' => $store['CNPJ'], 'state' => $store['addr_uf']]);
            }
            if (!$storeHaveCatalog) unset($storesHaveItems[$store['id']]);
        }

        $tempDeadLine = null;
        $tempValue = null;

        $storeNotFreight = array();

        foreach ($storesHaveItems as $store_id => $store) {

            foreach ($store as $dataStore)
                $cepOrigem = $dataStore['zipcode'];

            if ($isCorreios) {

                $valor = 0;
                $prazo = 0;

                foreach ($arrTestaServico as $freights) {

                    foreach ($freights as $freight) {

                        $freight['cep_origem'] = $cepOrigem;

                        // consulta no sgp preço e prazo com o serviço
                        $url = "https://gestaodeenvios.com.br/sgp_login/v/2.2/api/consulta-precos-prazos?chave_integracao={$token_sgp}";
                        $data_json = json_encode($freight);
                        $data_retorno = $this->restVtex($url, $data_json, array(), 'POST');
                        $retorno_decode = json_decode($data_retorno['content']);

                        echo "response_correios= ".$data_retorno['content']."\n\n";

                        if (!isset($retorno_decode->servicos)) continue;

                        $_valor = null;
                        $_prazo = null;

                        foreach ($retorno_decode->servicos as $service) {

                            if (isset($service->Valor) && isset($service->PrazoEntrega) && $service->Valor != "0,00" && $service->PrazoEntrega != 0) {

                                $_valor_ = (float)number_format($this->fmtNum($service->Valor), 2, '.', '');
                                $_prazo_ = (int)$service->PrazoEntrega;

                                if ($_valor === null) {
                                    $_valor = $_valor_;
                                    $_prazo = $_prazo_;
                                } else {
                                    if ($_valor_ < $_valor) {
                                        $_valor = $_valor_;
                                        $_prazo = $_prazo_;
                                    }
                                    if ($_valor == $_valor_ && $_prazo_ < $_prazo) {
                                        $_valor = $_valor_;
                                        $_prazo = $_prazo_;
                                    }
                                }
                            }
                        }
                        $valor += $_valor;
                        $prazo = $_prazo > $prazo ? $_prazo : $prazo;
                    }
                }

                if (!$tempDeadLine && $valor) {
                    $tempValue = $valor;
                    $tempDeadLine = $prazo;
                    $arrayResult = array();
                }
                if ($valor && $valor < $tempValue) {
                    $tempValue = $valor;
                    $tempDeadLine = $prazo;
                    $arrayResult = array();
                }
                if ($valor == $tempValue && $prazo < $tempDeadLine) {
                    $tempValue = $valor;
                    $tempDeadLine = $prazo;
                    $arrayResult = array();
                }
                if ($valor && $prazo == $tempDeadLine && $valor == $tempValue) {
                    array_push($arrayResult, array(
                        'value'     => $valor,
                        'deadline'  => $prazo,
                        'store_id'  => $store_id
                    ));
                }
                if ($valor === 0 && $prazo === 0) {
                    array_push($storeNotFreight, array(
                        'value'     => null,
                        'deadline'  => null,
                        'store_id'  => $store_id
                    ));
                }

                echo "arrayResult(correios): ";
                echo json_encode($arrayResult)."\n";
            } else { // transportadora

                $arrServicosTrasp = array();
                $destino = $this->calculofrete->lerCep($cepDestino);

                foreach ($order['items'] as $index => $iten) {

                    $CNPJ = '30120829000199'; // CNPJ fixo do ConectaLa
                    $fr = array();
                    $fr['destinatario'] = array(
                        'tipo_pessoa' => 1,
                        'endereco' => array('cep' => $cepDestino)
                    );

                    $cat_id 			= json_decode($iten['category_id']);
                    $sqlVol 			= "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id = '{$cat_id[0]}')";
                    $queryVol 			= $this->db->query($sqlVol);
                    $rsVol 				= $queryVol->row_array();
                    $tipo_volume_codigo = $rsVol['codigo'];
                    if (!$tipo_volume_codigo) $tipo_volume_codigo = 999;

                    $CNPJ_seller = $store[0]['cnpj'];
                    $origem = $this->calculofrete->lerCep($cepOrigem);

                    $vl = array(
                        'tipo' => $tipo_volume_codigo,
                        'sku' => $iten['skumkt'],
                        'quantidade' => (int)$iten['qty'],
                        'altura' => (float)$iten['altura'] / 100,
                        'largura' => (float)$iten['largura'] / 100,
                        'comprimento' => (float)$iten['profundidade'] / 100,
                        'peso' => (float)$iten['pesobruto'],
                        'valor' => (float)$iten['rate'] * $iten['qty'],
                        'volumes_produto' => 1,
                        'consolidar' => false,
                        'sobreposto' => false,
                        'tombar' => false,
                        'skuseller' => $iten['skumkt']); // Precode
                    $fr['volumes'][] = $vl;

                    $todos_tipo_volume = $this->calculofrete->verificaTipoVolume(array('tipo_volume_codigo' => $tipo_volume_codigo), $store[0]['state'], $order['state_customer']);
                    $todos_por_peso = $this->calculofrete->verificaPorPeso(array('tipo_volume_codigo' => $tipo_volume_codigo, 'peso_bruto' => $iten['pesobruto']), $order['state_customer']);

                    $fr['remetente'] = array(
                        'cnpj' => $CNPJ
                    );
                    $fr['expedidor'] = array(
                        'cnpj' => $CNPJ_seller,
                        'endereco' => array('cep' => $cepOrigem)
                    );

                    if ($todos_tipo_volume) {
                        $resposta = $this->calculofrete->calculaTipoVolume($fr, $origem, $destino);
                        $servicos = $this->respTransportadora($resposta, $fr, 0, $iten['rate']);
                    } elseif ($todos_por_peso) {
                        $resposta = $this->calculofrete->calculaPorPeso($fr, $origem, $destino);
                        $servicos = $this->respTransportadora($resposta, $fr, 0, $iten['rate']);
                    } else { // não encontrou cotação
                        echo "Não encontrou cotação para o item ( {$iten['prd_id']} ), da loja ( {$store_id} ) \n";

                        array_push($storeNotFreight, array(
                            'value'     => null,
                            'deadline'  => null,
                            'store_id'  => $store_id
                        ));

                        continue 2;
                    }
                    echo "response_transp_resp= ".json_encode($resposta)."\n";
                    echo "response_transp_service= ".json_encode($servicos)."\n";

                    if ($servicos) array_push($arrServicosTrasp, $servicos);
                }

                echo 'arrServicosTrasp= '.json_encode($arrServicosTrasp)."\n";

                if (empty($arrServicosTrasp)) {
                    array_push($storeNotFreight, array(
                        'value'     => null,
                        'deadline'  => null,
                        'store_id'  => $store_id
                    ));
                } else {
                    foreach ($arrServicosTrasp as $index => $services) {
                        foreach ($services['shippingQuotes'] as $service) {

                            $valor = $service['shippingCost'];
                            $prazo = (int)$service['deliveryTime']['total'];

                            if (!$tempDeadLine && $valor) {
                                $tempValue = $valor;
                                $tempDeadLine = $prazo;
                                $arrayResult = array();
                            }
                            if ($valor && $prazo < $tempDeadLine && $valor < $tempValue) {
                                $tempValue = $valor;
                                $tempDeadLine = $prazo;
                                $arrayResult = array();
                            }
                            if ($prazo == $tempDeadLine && $valor < $tempValue) {
                                $tempValue = $valor;
                                $tempDeadLine = $prazo;
                                $arrayResult = array();
                            }
                            if ($prazo == $tempDeadLine && $valor == $tempValue) {
                                array_push($arrayResult, array(
                                    'value' => $valor,
                                    'deadline' => $prazo,
                                    'store_id' => $store_id
                                ));
                            }
                        }
                    }
                }
                echo "arrayResult(trasp): ";
                echo json_encode($arrayResult)."\n";
            }
        }

        return [
            'arrayResult'       => $arrayResult,
            'storeNotFreight'   => $storeNotFreight
        ];
    }

    private function createIncidence($order, $reason)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        echo "Criou incidencia para o pedido {$order['id']}\n";

        $order_id   = $order['id'];
        $incidence  = "Não foi encontrado seller para o pedido.";
        $user_id    = 1;

        $comment = "ADD incidencia: {$incidence}";
        $arrComment = array();

        $dataOrder = $this->model_orders->getOrdersData(0, $order_id);

        if($dataOrder['comments_adm'])
            $arrComment = json_decode($dataOrder['comments_adm']);

        array_push($arrComment, array(
            'order_id'  => $order_id,
            'comment'   => $comment,
            'user_id'   => $user_id,
            'user_name' => 'admin_batch',
            'date'      => date('Y-m-d H:i:s')
        ));

        $sendComment = json_encode($arrComment);

        $this->model_orders->createCommentOrderInProgress($sendComment, $order_id);
        $this->model_orders->updateOrderIncidence($order_id, $user_id, $incidence);
        $this->log_data('batch', $log_name, "Criou incidencia para o pedido {$order['id']} - order=".json_encode($order), "I");

    }

    private function restVtex($url, $data, $httpHeader = array(), $method = 'GET'){

        $httpHeader = array_merge(array(
            'Accept: application/json',
            'Content-Type: application/json'
        ), $httpHeader);

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING 	   => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER 	   => $httpHeader
        );
        $ch       = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content  = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_errno( $ch );
        $errmsg   = curl_error( $ch );
        $header   = curl_getinfo( $ch );
        curl_close( $ch );
        $header['httpcode'] = $httpcode;
        $header['errno']    = $err;
        $header['errmsg']   = $errmsg;
        $header['content']  = $content;
        return $header;
    }

    private function respTransportadora($resposta, $fr,$cross_docking,$total_price)
    {
        if (array_key_exists('erro',$resposta )) {
            return $this->returnError($resposta['erro'], $resposta['calculo'].' '.$resposta['erro'],'E');
        }
        if (!array_key_exists('servicos',$resposta )) {
            return $this->returnError('No momento não é possível atender com estes itens', $resposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr),'W');
        }
        if (empty($resposta['servicos'] )) {
            return $this->returnError('No momento não é possível atender com estes itens', $resposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr), 'W');
        }
        // var_dump($resposta);
        $key = key($resposta['servicos']);
        $preco = $resposta['servicos'][$key]['preco'];
        $prazo = $resposta['servicos'][$key]['prazo'];
        $transportadora = $resposta['servicos'][$key]['empresa'];
        $servico =  $resposta['servicos'][$key]['servico'];

        // $taxa = $this->calculofrete->calculaTaxa($total_price);
        $taxa = 0;
        $preco += $taxa;

        //versão Skybub ....
        // https://desenvolvedores.skyhub.com.br/homologacao-de-frete/processo-de-homologacao
        $ret = Array();
        $ret['shippingCost'] = (float)$preco;
        $ret['deliveryTime'] = array(
            'total' => $prazo + $cross_docking,
            'transit' => (int)$prazo ,
            'expedition' => $cross_docking,
        ) ;
        $ret['shippingMethodId'] = $transportadora;
        $ret['shippingMethodName'] = $servico;
        $ret['shippingMethodDisplayName'] = $servico;
        $retorno = Array();
        $retorno['shippingQuotes'] = [$ret];

        return $retorno;
    }

    private function returnError($msg, $msg_log, $type = 'W') {
        $this->log_data('batch', 'FreteSIKA Consulta Frete', $msg_log, $type);
        $json_msg = json_encode([
            'message' => $msg
        ],JSON_UNESCAPED_UNICODE);

        return false;
    }

    private function getSellerIndex($order_id)
    {
        return $this->model_stores->getSellerIndexByStore($order_id);
    }

    private function getDataStoreSika($main = true)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        // pego a empresa
        $comp = $this->model_company->getCompaniesByName('SIKA');
        if (count($comp) != 1) {
            $erro = 'Não existe nenhuma empresa chamada '.'SIKA'.' ou tem mais de uma. Garanta que só tenha uma empresa chamada SIKA.';
            echo $erro."\n";
            $this->log_data('batch',$log_name, $erro ,"E");
            return false;
        }
        $companySika = $comp[0];

        //pego a loja
        $stores = $this->model_stores->getMyCompanyStores($companySika['id'], true);

        // retorno todas as lojas, caso não queira pegar a loja principal
        if (!$main) {
            unset($stores[0]);
            return $stores;
        }

        $storeSika = null;
        foreach($stores as $store) {
            if ($store['CNPJ'] == $companySika['CNPJ']) {
                $storeSika = $store;
                break;
            }
        }
        if (is_null($storeSika)) {
            $erro = 'Não encontrei nenhuma loja com o mesmo CNPJ da empresa '.$companySika['name'].' garanta que a loja franqueadora master tenha o CNPJ'.$companySika['CNPJ']."\n";
            echo $erro."\n";
            $this->log_data('batch',$log_name, $erro ,"E");
            return false;
        }

        return $storeSika;
    }
}