<?php
/*
SW Serviços de Informática 2019

Atualiza pedidos que chegaram no BLING

//php index.php BatchC/TinyInvoice run

*/
class TinyInvoice extends BatchBackground_Controller {

    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_orders','myorders');
        $this->load->model('model_products','myproducts');
        $this->load->model('model_company','mycompany');
        $this->load->model('model_stores','mystores');
        $this->load->model('model_clients','myclients');
        $this->load->model('model_nfes', 'mynfes');
        $this->load->model('Model_freights','myfreights');
        $this->load->model('model_settings','mysettings');
        $this->load->model('model_quotes_ship','myquotesship');
        $this->load->model('model_shipping_company');

    }

    function run($id=null,$params=null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        /* faz o que o job precisa fazer */
        echo "Pegando ordens para faturar \n";
        $this->sendOrdersForInvoice();

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    function sendOrdersForInvoice()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $urlIncluir = 'https://api.tiny.com.br/api2/nota.fiscal.incluir.php';
        $urlEmitir  = 'https://api.tiny.com.br/api2/nota.fiscal.emitir.php';
        $urlXML     = 'https://api.tiny.com.br/api2/nota.fiscal.obter.xml.php';

        echo "---------------------------\n";
        echo "Consultando pedidos para faturar\n";
        $nfesToIntegration = $this->mynfes->getNfesForIntegrationEmission();

        if(count($nfesToIntegration) === 0){
            echo "Nao tem pedidos para faturar\n";
        }

        foreach($nfesToIntegration as $nfe) {
            $order_id   = $nfe['order_id']; // código pedido
            $company_id = $nfe['company_id']; // código empresa
            $store_id   = $nfe['store_id']; // códiog loja

            $token = $this->mystores->getTokenInvoice($store_id);

            if($token == false){
                $msgError = "Não foi encontrado um token para a loja";
                echo "{$msgError} \n";
                $this->log_data('batch',$log_name,$msgError,"E");
                $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                $this->myorders->updatePaidStatus($order_id, 57);
                continue;
            }

            $apikey     = $token; // RECUPERAR TOKEN DA LOJA

            echo "Começando faturamento do pedido: {$order_id} \n";
            echo "Dados empresa: {$company_id}, loja: {$store_id}\n";

            $dataSend['nota_fiscal'] = array(); // Array com os dados para envio

            // Consultas
            $dataOrder      = $this->myorders->getOrdersData(0, $order_id);
            $dataClient     = $this->myclients->getClientsData($dataOrder['customer_id']);
            $dataItemsOrder = $this->myorders->getOrdersItemData($order_id);
            $existNfe       = $this->mynfes->getCountNfe($order_id, $company_id);
            $verifyProduct  = $this->myorders->getErrorsProductsForInvoice($order_id, $store_id, $company_id);

            // Remove pontuação do cnpj/cpf
            $cpf_cnpj = $dataClient == null ? null : preg_replace('/[^\d\+]/', '', $dataClient['cpf_cnpj']);

            /** INICIO VALIDAÇÕES */

            // VALIDAR SEM TRANSPORTE
            // E MANDAR PARA O STATUS DE CANCELAR PEDIDO | 98 - Cancelar no Marketplace

            echo "Inicio validaçoes\n";
            if($existNfe != 0) {
                $msgError = "Pedido ja faturado, pedido: {$order_id}";
                echo "{$msgError} \n";
                $this->log_data('batch',$log_name,$msgError,"E");
                $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                $this->myorders->updatePaidStatus($order_id, 57);
                continue;
            }
            if($dataOrder == null || count($dataOrder) == 0){
                $msgError = "Nao foi encontrado o pedido: {$order_id}";
                echo "{$msgError} \n";
                $this->log_data('batch',$log_name,$msgError,"E");
                $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                $this->myorders->updatePaidStatus($order_id, 57);
                continue;
            }
            if($dataClient == null || count($dataClient) == 0){
                $msgError = "Nao foi encontrado cliente do pedido: {$order_id}";
                echo "{$msgError} \n";
                $this->log_data('batch',$log_name,$msgError,"E");
                $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                $this->myorders->updatePaidStatus($order_id, 57);
                continue;
            }
            if($dataItemsOrder == null || count($dataItemsOrder) == 0){
                $msgError = "Nao foi encontrado itens do pedido: {$order_id}";
                echo "{$msgError} \n";
                $this->log_data('batch',$log_name,$msgError,"E");
                $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                $this->myorders->updatePaidStatus($order_id, 57);
                continue;
            }
            if($verifyProduct = null || count($verifyProduct) > 0){
                $msgError = json_encode($verifyProduct);
                echo "{$msgError} \n";
                $this->log_data('batch',$log_name,$msgError,"E");
                $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                $this->myorders->updatePaidStatus($order_id, 57);
                continue;
            }

            $ship_company = $dataOrder['ship_company_preview'];
            $ship_service = $dataOrder['ship_service_preview'];

            if (!$ship_company) {
                $msgError = "Não encontrou a empresa para transporte. ship_company={$ship_company} ship_service={$ship_service}";
                echo "{$msgError} \n";
                $this->log_data('batch',$log_name,$msgError,"E");
                $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                $this->myorders->updatePaidStatus($order_id, 57);
                continue;
            }

            $cnpjTransportadora = null;

            if ($ship_company == "CORREIOS") { // correios

                $cnpjTransportadora = "34028316000103";

            } elseif ($ship_company == "Transportadora" && $ship_service == "Conecta Lá") { // romoaldo

                $dataStore = $this->mystores->getStoresData($store_id);

                switch ($dataStore['addr_uf']) {
                    case "SC":
                        $cnpjTransportadora = "05813363000160";
                        break;
                    case "SP":
                        $cnpjTransportadora = "21341720000190";
                        break;
                    case "MG":
                        $cnpjTransportadora = "86479268000254";
                        break;
                    case "RJ":
                    case "ES":
                        $cnpjTransportadora = "24566736000351";
                        break;
                    default:
                        $msgError = "Não foi encontrado transportadora para realizar o envio.";
                        echo "{$msgError} \n";
                        $this->log_data('batch',$log_name,$msgError,"E");
                        $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                        $this->myorders->updatePaidStatus($order_id, 57);
                        continue 2;
                }

            } elseif ($ship_company == "Conecta Lá" && $ship_service == "Jamef") { // jamef

                $cnpjTransportadora = "20147617000656";

            } elseif ($ship_company == "Destak" && $ship_service == "Transportadora") {

                $cnpjTransportadora = "05813363000160";

            } elseif ($ship_company == "FreteRápido") { // frete rapido

                // Recuperar dados da cotação ou cria caso não exista
                $dataContratacao = $this->contrataCotacao($dataOrder, true);
                if (!$dataContratacao['success']) {
                    $msgError = $dataContratacao['data'];
                    echo "{$msgError} \n";
                    $this->log_data('batch', $log_name, $msgError, "E");
                    $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                    $this->myorders->updatePaidStatus($order_id, 57);
                    continue;
                }

                $cnpjTransportadora = $dataContratacao['cnpj'];
            }
            elseif ($ship_company == "Bradex" && $ship_service == "Transportadora") // Bradex
                $cnpjTransportadora = "24566736000351";
            elseif ($ship_company == "Prime" && $ship_service == "Transportadora") // Prime
                $cnpjTransportadora = "10642884000301";

			if (!empty($cnpjTransportadora)) {
                $dataProvider = $this->model_shipping_company->getShippingCompanyByCnpjAndStore($cnpjTransportadora, $order_id);
            }

            if($cnpjTransportadora == null || $dataProvider == null){
                $msgError = "Não foi encontrado um fornecedor. CNPJ: {$cnpjTransportadora}";
                echo "{$msgError} \n";
                $this->log_data('batch',$log_name,$msgError,"E");
                $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                $this->myorders->updatePaidStatus($order_id, 57);
                continue;
            }

            /** FIM VALIDAÇÕES */

            /** ------------------------------------------------------------ */

            /** DADOS NFE */
            $dataSend['nota_fiscal']["tipo"] = "S"; // E - Entrada, S - Saída
            $dataSend['nota_fiscal']["natureza_operacao"] = "Venda de Mercadorias";
            $dataSend['nota_fiscal']["data_emissao"] = date('d/m/Y');
            $dataSend['nota_fiscal']["data_entrada_saida"] = date('d/m/Y');
            $dataSend['nota_fiscal']["hora_entrada_saida"] = date('H:i');

            $dataSend['nota_fiscal']["valor_desconto"] = $dataOrder['discount'];
            $dataSend['nota_fiscal']["valor_frete"] = $dataOrder['total_ship'];
//            $dataSend['nota_fiscal']["valor_seguro"] = "0";
//            $dataSend['nota_fiscal']["valor_despesas"] = "0";
            $dataSend['nota_fiscal']["numero_pedido_ecommerce"] = $order_id; // Número de identificação do pedido no e-commerce (ou no seu sistema)
            $dataSend['nota_fiscal']["obs"] = $dataOrder['additional_data_nfe'] ?? ''; // Dados opcionais
            $dataSend['nota_fiscal']["finalidade"] = "1";
//            $dataSend['nota_fiscal']["refNFe"] = ""; //Chave de acesso da NF-e referenciada - apenas para as finalidades com valor 3, 4 ou 9
            /** FIM DADOS NFE */

            /** ------------------------------------------------------------ */

            /** DADOS EMITENTE */
            $cep = preg_replace('/[^\d\+]/', '',$dataClient['zipcode']);
            $dataSend['nota_fiscal']["cliente"] = array(
                "codigo"        => $dataClient['id'],
                "nome"          => $dataClient['customer_name'],
                "tipo_pessoa"   => strlen($cpf_cnpj) === 11 ? 'F' : 'J',
                "cpf_cnpj"      => $cpf_cnpj,
                "ie"            => preg_replace('/[^\d\+]/', '', $dataClient['ie']),
                "rg"            => preg_replace('/[^\d\+]/', '', $dataClient['rg']),
                "fone"          => preg_replace('/[^\d\+]/', '', $dataClient['phone_1']),
                "email"         => $dataClient['email'],
                "complemento"   => $dataClient['addr_compl'],
                "numero"        => $dataClient['addr_num'],
                "cep"           => $cep
            );

            $dataAddress = $this->getDataAddress($cep);
            if ($dataAddress) {
                $dataSend['nota_fiscal']["cliente"]["endereco"] = empty($dataAddress['address']) ? $dataClient['customer_address'] : $dataAddress['address'];
                $dataSend['nota_fiscal']["cliente"]["bairro"]   = empty($dataAddress['neighborhood']) ? $dataClient['addr_neigh'] : $dataAddress['neighborhood'];
                $dataSend['nota_fiscal']["cliente"]["cidade"]   = empty($dataAddress['city']) ? $dataClient['addr_city'] : $dataAddress['city'];
                $dataSend['nota_fiscal']["cliente"]["uf"]       = empty($dataAddress['state']) ? $dataClient['addr_uf'] : $dataAddress['state'];
            } else {
                $dataSend['nota_fiscal']["cliente"]["endereco"] = $dataClient['customer_address'];
                $dataSend['nota_fiscal']["cliente"]["bairro"]   = $dataClient['addr_neigh'];
                $dataSend['nota_fiscal']["cliente"]["cidade"]   = $dataClient['addr_city'];
                $dataSend['nota_fiscal']["cliente"]["uf"]       = $dataClient['addr_uf'];
            }

            /** FIM DADOS EMITENTE */

            /** ------------------------------------------------------------ */

            $cep_entrega = preg_replace('/[^\d\+]/', '',$dataOrder['customer_address_zip']);
            /** DADOS ENTREGA */
            $dataSend['nota_fiscal']["endereco_entrega"] = array(
                "tipo_pessoa"       => strlen($cpf_cnpj) === 11 ? 'F' : 'J',
                "cpf_cnpj"          => $cpf_cnpj,
                "endereco"          => $dataOrder['customer_address'],
                "complemento"       => $dataOrder['customer_address_compl'],
                "numero"            => $dataOrder['customer_address_num'],
                "cep"               => $cep_entrega,
                "fone"              => preg_replace('/[^\d\+]/', '', $dataOrder['customer_phone']),
                "nome_destinatario" => $dataOrder['customer_name'],
                "ie"                => '',
            );

            $dataAddressEntrega = $this->getDataAddress($cep_entrega);
            if ($dataAddressEntrega) {
                $dataSend['nota_fiscal']["endereco_entrega"]["endereco"] = empty($dataAddressEntrega['address']) ? $dataOrder['customer_address'] : $dataAddressEntrega['address'];
                $dataSend['nota_fiscal']["endereco_entrega"]["bairro"]   = empty($dataAddressEntrega['neighborhood']) ? $dataOrder['customer_address_neigh'] : $dataAddressEntrega['neighborhood'];
                $dataSend['nota_fiscal']["endereco_entrega"]["cidade"]   = empty($dataAddressEntrega['city']) ? $dataOrder['customer_address_city'] : $dataAddressEntrega['city'];
                $dataSend['nota_fiscal']["endereco_entrega"]["uf"]       = empty($dataAddressEntrega['state']) ? $dataOrder['customer_address_uf'] : $dataAddressEntrega['state'];
            } else {
                $dataSend['nota_fiscal']["endereco_entrega"]["endereco"] = $dataOrder['customer_address'];
                $dataSend['nota_fiscal']["endereco_entrega"]["bairro"]   = $dataOrder['customer_address_neigh'];
                $dataSend['nota_fiscal']["endereco_entrega"]["cidade"]   = $dataOrder['customer_address_city'];
                $dataSend['nota_fiscal']["endereco_entrega"]["uf"]       = $dataOrder['customer_address_uf'];
            }
            /** FIM DADOS ENTREGA */

            /** ------------------------------------------------------------ */

            /** DADOS ITENS */
            $dataSend['nota_fiscal']["itens"] = array();
            foreach ($dataItemsOrder as $iten){
                echo "Consultando item do pedido, id_iten: {$iten['id']}\n";

                $dataProduct = $this->myproducts->getProductData(0, $iten['product_id']);

                // Não encontrou o produto
                if(count($dataProduct) == 0){
                    $msgError = "Nao foi encontrado produto do item do pedido, product_id: {$iten['product_id']}";
                    echo "{$msgError} \n";
                    $this->log_data('batch',$log_name,$msgError,"E");
                    $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                    $this->myorders->updatePaidStatus($order_id, 57);
                    continue 2;
                }
                if(strlen(preg_replace('/[^\d\+]/', '',$dataProduct['NCM'])) != 8){
                    $msgError = "NCM incorreto, product_id: {$iten['product_id']}";
                    echo "{$msgError} \n";
                    $this->log_data('batch',$log_name,$msgError,"E");
                    $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                    $this->myorders->updatePaidStatus($order_id, 57);
                    continue 2;
                }

                array_push($dataSend['nota_fiscal']["itens"], array('item' => array(
                    "codigo"        => $iten['product_id'],
                    "descricao"     => $iten['name'],
                    "unidade"       => mb_strtoupper($iten['un']),
                    "quantidade"    => (float)$iten['qty'],
                    "valor_unitario"=> (float)$iten['amount'],
                    "tipo"          => "P", // produto("P") ou serviço("S")
                    "origem"        => (int)$dataProduct['origin'],
//                    "numero_fci"    => $dataProduct['FCI'],
                    "ncm"           => $dataProduct['NCM'],
                    "peso_bruto"    => (float)$dataProduct['peso_bruto'],
                    "peso_liquido"  => (float)$dataProduct['peso_liquido'],
                    "gtin_ean"      => $dataProduct['EAN']
                )));
            }
            /** FIM DADOS ITENS */

            /** ------------------------------------------------------------ */

            /** DADOS PAGAMENTO */
            $dataSend['nota_fiscal']["parcelas"] = array();

            array_push($dataSend['nota_fiscal']["parcelas"], array('parcela' => array(
                "dias"              => 0,
                "data"              => date('d/m/Y'),
                "valor"             => (float)$dataOrder['gross_amount'],
                "obs"               => "",
                "forma_pagamento"   => "multiplas"
            )));

            // Forma de pagamento, caso usar formas diferente definir como multiplas
            $dataSend['nota_fiscal']["forma_pagamento"] = "multiplas";
            /** FIM DADOS PAGAMENTO */

            /** ------------------------------------------------------------ */

            /** DADOS TRANSPORTE */
            $dataSend['nota_fiscal']["transportador"] = array(
                "codigo"        => $dataProvider['id'],
                "nome"          => $dataProvider['razao_social'],
                "tipo_pessoa"   => strlen($cnpjTransportadora) == 14 || strlen($cnpjTransportadora) == 18 ? "J" : "F",
                "cpf_cnpj"      => $cnpjTransportadora,
//                "ie"            => "254399851",
                "endereco"      => "{$dataProvider['address']}, nº {$dataProvider['addr_num']}, {$dataProvider['addr_compl']}",
                "cidade"        => $dataProvider['addr_city'],
                "uf"            => $dataProvider['addr_uf']
            );
            $dataSend['nota_fiscal']["frete_por_conta"] = "T"; // "R"-Remetente, "D"-Destinatário, "T"-Terceiros
//            $dataSend['nota_fiscal']["placa_veiculo"] = "";
//            $dataSend['nota_fiscal']["uf_veiculo"] = "";
            $dataSend['nota_fiscal']["quantidade_volumes"] = "1";
            $dataSend['nota_fiscal']["especie_volumes"] = "Volume";
//            $dataSend['nota_fiscal']["marca_volumes"] = "";
//            $dataSend['nota_fiscal']["numero_volumes"] = "1";
            $dataSend['nota_fiscal']["forma_envio"] = strtolower($ship_company) == 'correios' ? 'C' : 'T';
            // PESO BRUTO
            // PESO LÍQUDO
            /** FIM DADOS TRANSPORTE */

            /** ------------------------------------------------------------ */

            /** INCLUIR NFE */
            $dataIncluir = "token={$apikey}&nota=" . json_encode($dataSend) ."&formato=json";
            $this->log_data('batch',$log_name,json_encode($dataSend),"I");

            try {
                $retornoIncluir = $this->executeSendInvoice($urlIncluir, $dataIncluir);
                echo $retornoIncluir . "\n";
                $retornoIncluir = json_decode($retornoIncluir);
                if($retornoIncluir->retorno->status_processamento == 3 && $retornoIncluir->retorno->status == "OK"){
                    echo "Não encontrou erros para incluir a nota\n";

                    $regitro = $retornoIncluir->retorno->registros->registro;

                    $sequencia_nota = $regitro->sequencia;
                    $status_nota    = $regitro->status;
                    $id_nota        = $regitro->id;
                    $serie_nota     = $regitro->serie;
                    $numero_nota    = $regitro->numero;

                    $dataUpdateNfeInteg = array(
                        'sequencia_nota'=> $sequencia_nota,
                        'status_nota'   => $status_nota,
                        'id_nota'       => $id_nota,
                        'serie_nota'    => $serie_nota,
                        'numero_nota'   => $numero_nota
                    );
                    echo "Recuperou os dados com sucesso\n";

                    $this->mynfes->updateNfeForIntegration($dataUpdateNfeInteg, $order_id);
                    echo "Atualizou a tabela de integração\n";

                }else{
                    $arrErros = array();

                    if($retornoIncluir->retorno->status_processamento == 1){
                        foreach($retornoIncluir->retorno->erros as $erro) {
                            array_push($arrErros, $erro->erro);
                        }
                    }
                    if($retornoIncluir->retorno->status_processamento == 2){
                        if (isset($retornoIncluir->retorno->registros->registro->erros)) {
                            foreach ($retornoIncluir->retorno->registros->registro->erros as $erro) {
                                array_push($arrErros, $erro->erro);
                            }
                        } else {
                            array_push($arrErros, "Erro desconhecido");
                        }
                    }
                    $strErros = implode(' | ', $arrErros);

                    $msgError = "Erro para incluir nota fiscal, status_processamento: {$retornoIncluir->retorno->status_processamento}, erros: {$strErros}. ENVIADO=". json_encode($dataSend);
                    echo "{$msgError} \n";
                    $this->log_data('batch',$log_name,$msgError,"E");
                    $this->mynfes->updateNfeForIntegration(array('error_message' => $strErros), $order_id);

//                    $this->mynfes->updateNfeForIntegration(array('error_message' => implode(' | ', $strErros)), $order_id);

                    $this->myorders->updatePaidStatus($order_id, 57);

                    continue;
                }
            }
            catch (Exception $e){
                $msgError = "Erro para incluir nota fiscal, mensagem: " . $e->getMessage() . "\n";
                echo "{$msgError} \n";
                $this->log_data('batch',$log_name,$msgError,"E");
                $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                $this->myorders->updatePaidStatus($order_id, 57);
                continue;
            }
            /** FIM INCLUIR NFE */

            /** ------------------------------------------------------------ */

            /** EMITIR NFE */
            echo "Nota incluida com sucesso \n";
            $data = "token={$apikey}&id={$id_nota}&formato=json";
            try {
                $retornoEmitir = $this->executeSendInvoice($urlEmitir, $data);
                echo $retornoEmitir . "\n";
                $retornoEmitir = json_decode($retornoEmitir);
                if($retornoEmitir->retorno->status_processamento == 3 && $retornoEmitir->retorno->status == "OK"){
                    echo "Não encontrou erros para emitir a nota\n";

                    $regitro        = $retornoEmitir->retorno->nota_fiscal;

                    $chave_acesso   = $regitro->chave_acesso;
                    $id_nota        = $regitro->id;

                    // Consultar nota fiscal na tiny para pegar os dados para inserir no sistema

                    $dataInsertNfe = array(
                        'order_id'      => $order_id,
                        'company_id'    => $company_id,
                        'store_id'      => $store_id,
                        'date_emission' => date('d/m/Y H:i:s'),
                        'nfe_value'     => $dataOrder['gross_amount'],
                        'nfe_serie'     => $serie_nota,
                        'nfe_num'       => $numero_nota,
                        'chave'         => $chave_acesso,
                        'id_nota_tiny'  => $id_nota
                    );
                    echo "Recuperou os dados com sucesso\n";

                    $this->mynfes->create($dataInsertNfe);
                    echo "Crio NFE na tabela\n";

                    $this->mynfes->deleteInvoiceIntegration($order_id, $store_id, $company_id);

                    if($this->myorders->verifyStatus($order_id) != 54) {
                        echo "Atualizou status para 52\n";
                        $this->myorders->updatePaidStatus($order_id, 52);
                    } else {
                        echo "Nao atualizou status porque foi feito cotacao por API - manteve no status 54\n";
                    }

                } else {
                    $arrErros = array();

                    if($retornoEmitir->retorno->status_processamento == 1 || $retornoEmitir->retorno->status_processamento == 2){
                        foreach($retornoEmitir->retorno->erros as $erro) {
                            array_push($arrErros, $erro->erro);
                        }
                    }

                    $strErros = implode(' | ', $arrErros);

                    $msgError = "Erro para emitir nota fiscal, status_processamento: {$retornoEmitir->retorno->status_processamento}, erros: {$strErros}, retorno:".json_encode($retornoEmitir). ", enviado=".json_encode($dataSend);
                    echo "{$msgError} \n";
                    $this->log_data('batch',$log_name,$msgError,"E");

                    $this->mynfes->updateNfeForIntegration(array('error_message' => $strErros), $order_id);
                    $this->myorders->updatePaidStatus($order_id, 57);

                    continue;
                }
            }
            catch (Exception $e) {
                $msgError = "Erro para emitir nota fiscal, mensagem: " . $e->getMessage() . "\n";
                echo "{$msgError} \n";
                $this->log_data('batch',$log_name,$msgError,"E");
                $this->mynfes->updateNfeForIntegration(array('error_message' => $msgError), $order_id);
                $this->myorders->updatePaidStatus($order_id, 57);
                continue;
            }

            /** FIM EMITIR NFE */

            /** ------------------------------------------------------------ */

            /** RECUEPRAR LINK ACESSO NFE */
            echo "Recuperar link acesso \n";
            $linkAcesso = "";
            $urlObter = 'https://api.tiny.com.br/api2/nota.fiscal.obter.link.php';
            $data = "token={$apikey}&id={$id_nota}&formato=json";
            try {
                $retornoObter = $this->executeSendInvoice($urlObter, $data);
                $retornoObter = json_decode($retornoObter);

                if($retornoObter->retorno->status_processamento == 3 && $retornoObter->retorno->status == "OK") {
                    $linkAcesso = $retornoObter->retorno->link_nfe;
                    $this->mynfes->updateForOrderId(array('link_tiny' => $linkAcesso), $order_id);
                    echo "Gravou link acesso \n";
                }
            }
            catch (Exception $e) {
                echo "Erro recuperar link_acesso".$e->getMessage()."\n";
            }

            /** GRAVA XML */

            echo "Nota incluida com sucesso \n";
            $data = "token={$apikey}&id={$id_nota}";
            try {
                $xml = $this->executeSendInvoice($urlXML, $data);
                $this->createFileXml($xml, $chave_acesso, $store_id);
                echo "XML salvo\n";
            } catch (Exception $e) {
                echo "Erro ao gravar o XML: " . $e->getMessage() . "\n";
            }
        }
    }

    private function createFileXml($xml, $chave, $store)
    {
        $namePathStore = date('m-Y');

        $targetDir = 'assets/images/xml/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $targetDir .= $store.'/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $targetDir .= $namePathStore.'/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }


        $arquivo = fopen($targetDir . $chave . ".xml",'w');

        if ($arquivo == false) return false;

        $xml = str_replace('<retorno><status_processamento>3</status_processamento><status>OK</status><xml_nfe>', '', $xml);
        $xml = str_replace('</xml_nfe></retorno>', '', $xml);
        fwrite($arquivo, $xml);

        fclose($arquivo);

        return true;
    }

    function executeSendInvoice($url, $data){

        $params = array('http' => array(
            'method' => 'POST',
            'content' => $data
        ));

        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp)
            return '{"retorno":{"status_processamento":1,"status":"Erro","codigo_erro":"99","erros":[{"erro":"Nao foi possivel acessar a URL(fopen): '.$url.' "}]}}';

        $response = @stream_get_contents($fp);
        if ($response === false)
            return '{"retorno":{"status_processamento":1,"status":"Erro","codigo_erro":"99","erros":[{"erro":"Nao foi possivel acessar a URL(stream_get_contents): '.$url.' "}]}}';

        return $response;
    }

    private function contrataCotacao($order, $normal)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $transportadora_cnpj = ""; // CNPJ da transportadora

        //echo 'ordem id '.$order['id'];
        $order['origin'] = $this->origin2Marketplace($order['origin']);
        $frete = $this->myorders->getItemsFreights($order['id']);
        if (count($frete) > 0) {
            echo 'ERRO: pedido '.$order['id'].' já tem frete'."\n";
            // Possivelmente, Frete colocado na mão, tem que avançar o status para enviar para o bling.
            // Possivelmente, pode não ter sido possível enviar para o Bling
            $this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' já frete',"E");
            return array('success' => false, 'data' => 'O pedido já tem frete!');
        }
        $skus = array();
        echo 'pedido precisando de frete '.$order['id']."\n";
        $itens = $this->myorders->getOrdersItemData($order['id']);
        foreach($itens as $item) {
            // pego o produto do  prd_to_integration
            $sql = "SELECT * FROM prd_to_integration WHERE prd_id = ? AND int_to = ?";
            $query = $this->db->query($sql, array($item['product_id'], $order['origin']));
            $prodint = $query->row_array();
            if (!isset($prodint)) {
                echo 'Ordem '.$order['id'].' tem item '.$item['id'].' que não foi enviado para o Marketplace '."\n";
                $this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' tem item '.$item['id'].' que não foi enviado para o Marketplace ',"E");
                // Não deveria aconter.
                return array('success' => false, 'data' => "O item {$item['name']} não foi enviado para o Marketplace!");
            }
            //var_dump($prodint);
            // monto os SKUS
            $skus[] = $prodint['skumkt'];
        }

        //pego o cliente
        $cliente = $this->myclients->getClientsData($order['customer_id']);
        if (!(isset($cliente))) {
            $this->log_data('batch',$log_name,'ERRO: pedido '.$order['id'].' sem o cliente '.$order['customer_id'],"E");
            return array('success' => false, 'data' => 'O pedido não tem um cliente');
        }

        // pego a cotação
        if ($normal) {		// se for uma ordem normal, uso o valor que veio como frete pago
            $quote =  $this->myquotesship->getQuoteShipByKey($order['origin'], $cliente['zipcode'], $skus, $order['total_ship']);
            echo "vou procurar por ".json_encode(array('Origin' => $order['origin'], 'zip' => $cliente['zipcode'], 'sku' => $skus, 'cost' => $order['total_ship']))."\n";
        } else {          // se não for, uso o valor do frete real que foi gravado quando pediu a cotaçao manual
            $quote =  $this->myquotesship->getQuoteShipByKey($order['origin'], $cliente['zipcode'], $skus, $order['frete_real']);
            echo "vou procurar por ".json_encode(array('Origin' => $order['origin'], 'zip' => $cliente['zipcode'], 'sku' => $skus, 'cost' => $order['frete_real']))."\n";
        }

        // se não existir cotação -
        if ($quote == false) {
            echo "não existir cotação, vou criar \n";
            $criaQuotes = $this->criaCotacao($order['id']);

            if($criaQuotes['erro']){
                $this->log_data('batch', $log_name, 'Não criou cotação. Erro: ' . $order['id'], "E");
                echo 'Não criou cotação. Erro: ' . json_encode($criaQuotes['data']) . "\n";
                $order['paid_status'] = 101; // Precisa contratar o frete manualmente
                $this->myorders->updateByOrigin($order['id'], $order);
                return array('success' => false, 'data' => 'Ocorreu um problema para fazer a cotação ' . json_encode($criaQuotes['data']));
            }

            $transportadora_cnpj = $criaQuotes['transportadora_cnpj'];
            echo "Atualizou preço frete para: {$criaQuotes['preco_frete']} e status para 54\n";
            $this->myorders->updateNovoFrete($order['id'], 54, $criaQuotes['preco_frete']);
        } else {
            $transportadora_cnpj = json_decode($quote['retorno'])->transportadoras[0]->cnpj;
            // $volumeTransporte   = json_decode($quote['retorno'])->volumes;
            // $quantidade = $volumeTransporte[0]->quantidade;
        }
        return array('success' => true, 'cnpj' => $transportadora_cnpj);
    }

    function origin2Marketplace($origin) {
        if 	    ($origin == 'B2W-SkyHub') { return('B2W'); }
        else if ($origin == 'MercadoLivre') { return('ML'); }
        else if ($origin == 'Amazon') { return('AMZ'); }
        else if ($origin == 'MagazineLuiza') { return('MAGALU'); }
        else if ($origin == 'Via Varejo') { return('VIA'); }
        else if ($origin == 'Carrefour') { return('CAR'); }
        else return $origin;
    }

    public function criaCotacao($orderId)
    {

        if ($orderId == '') return false;

        $CNPJ = '30120829000199'; // CNPJ fixo do ConectaLa
        // Pego o Token pro frete Rápido
        $sql = "SELECT * FROM settings WHERE name = ?";
        $query = $this->db->query($sql, array('token_frete_rapido_master'));
        $row = $query->row_array();
        if ($row) {
            $token_fr = $row['value'];
        } else {
            $retorno = Array();
            $retorno['erro'] = true;
            $retorno['data'] = 'Falta o cadastro do parametro token_frete_rapido_master';

            return $retorno;
        }

        $orders_data = $this->myorders->getOrdersData(0,$orderId);

        if (is_null($orders_data['store_id'])) {
            $stores = $this->mystores->getCompanyStores($orders_data['company_id']);
            $store = $stores[0];
        } else  {
            $store = $this->mystores->getStoresData($orders_data['store_id']);
        }
        // var_dump($store);

        $orders_item = $this->myorders->getOrdersItemData($orders_data['id']);

        foreach($orders_item as $item) {

            $sql = "SELECT * FROM bling_ult_envio WHERE int_to = ? and prd_id= ?";
            $query = $this->db->query($sql, array($orders_data['origin'], $item['product_id']));
            $row_ult = $query->row_array();
            if (empty($row_ult)) {
                $retorno = Array();
                $retorno['erro'] = true;
                $retorno['data'] = 'O produto '.$item['product_id'].' não foi enviado para o marketplace '.$orders_data['origin'];

                return $retorno;

            }
            $sku = $row_ult['skumkt'];
            if ($sku =="") {
                $sku = $row_ult['skubling'];
            }

            $tipo_volume_codigo = intval($row_ult['tipo_volume_codigo']);
            if (is_null($row_ult['tipo_volume_codigo'])) {
                $tipo_volume_codigo = 999;
            }

            $sql = "SELECT * FROM products WHERE id= ?";
            $query = $this->db->query($sql, $item['product_id']);
            $prd = $query->row_array();

            $skus_key[] = $sku;
            $vl = Array (
                "tipo" => $tipo_volume_codigo,
                "sku" => $sku,
                "quantidade" => (int) $item['qty'],
                "altura" => (float) $prd['altura'] / 100,
                "largura" => (float) $prd['largura'] /100,
                "comprimento" => (float) $prd['profundidade'] /100,
                "peso" => (float) $prd['peso_bruto'],
                "valor" => (float) $item['amount'],
                "volumes_produto" => 1,
                "consolidar" => false,
                "sobreposto" => false,
                "tombar" => false);
            $fr['volumes'][] = $vl;
        }

        $fr["destinatario"] = Array (
            "tipo_pessoa" => 1,
            "endereco" => Array (
                "cep" => preg_replace('/\D/', '',$orders_data['customer_address_zip']))
        );

        $fr["remetente"] = Array (
            "cnpj" => $CNPJ
        );

        $fr["expedidor"] = Array (
            "cnpj" =>preg_replace('/\D/', '', $store['CNPJ']),
            "endereco" => Array( 'cep' => $store['zipcode'])
        );
        $fr["codigo_plataforma"] = "nyHUB56ml";
        // $fr["token"] = "5d1c7889ff8789959cb39eb151a3698e";  // Rick pegar o Token do Parceiro., talvez colcoar na bling_ult_envio
        $fr["token"] = $token_fr;
        $fr["retornar_consolidacao"] = true;
        //var_dump($fr);
        $json_data = json_encode($fr,JSON_UNESCAPED_UNICODE);
        $json_data = stripslashes($json_data);

        $url = "https://freterapido.com/api/external/embarcador/v1/quote-simulator";

        $data = $this->get_web_page( $url,$json_data);

        if (!($data['httpcode']=="200")) {
            //echo 'ERRO - httpcode: '.$data['httpcode'].' RESPOSTA FR: '.$data['content'].' DADOS ENVIADOS:'.$json_data;
            $retorno = Array();
            $retorno['erro'] = true;
            $retorno['data'] = 'ERRO - httpcode: '.$data['httpcode'].' RESPOSTA FR: '.$data['content'].' DADOS ENVIADOS:'.$json_data;

            return $retorno;

        }

        $retorno_fr = $data['content'];

        $data = json_decode($data['content'],true);
        $transp = $data['transportadoras'];
        if (count($transp) == 0) {
            // Não voltou transportadora.
            //echo 'SEM TRANSPORTADORA: DADOS ENVIADOS:'.print_r($json_data,true).' RECEBIDOS '.print_r($retorno_fr,true);
            $retorno = Array();
            $retorno['erro'] = true;
            $retorno['data'] = 'Sem transporte.';

            return $retorno;
        }
        // Adiciono a taxa de frete ao valor retornado
        $sql = 'SELECT av.value FROM attribute_value av, attributes a WHERE a.name ="frete_taxa" and a.id = av.attribute_parent_id';
        $query = $this->db->query($sql);
        $row_taxa = $query->row_array();
        // Não faz sentido aumentar com a taxa $transp[0]['preco_frete'] += (float) $row_taxa['value'];

        $retorno = Array();
        $retorno['erro'] = false;
        $retorno['preco_frete'] = $transp[0]['preco_frete'];
        $retorno['prazo_entrega'] = $transp[0]['prazo_entrega'];
        $retorno['transportadora'] = $transp[0]['nome'];
        $retorno['transportadora_cnpj'] = $transp[0]['cnpj'];

        sort($skus_key);
        $quotes = Array();
        $quotes['marketplace'] = $orders_data['origin'];
        $quotes['zip'] = preg_replace('/\D/', '',$orders_data['customer_address_zip']);
        $quotes['sku'] =  json_encode($skus_key);
        $quotes['cost'] = $transp[0]['preco_frete'];
        $quotes['id'] = $data['token_oferta'];
        $quotes['oferta'] = $transp[0]['oferta'];
        $quotes['validade'] = $transp[0]['validade'];
        $quotes['retorno'] = $retorno_fr;
        $quotes['frete_taxa'] = 0;  // Por enquanto, não tem taxa. Será calculado quando contratar o frete $row_taxa['value'];
        $this->db->replace('quotes_ship', $quotes);

        return $retorno;
    }

    function get_web_page( $url,$post_data )
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_USERAGENT      => "conectala", // who am i
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_POST		=> true,
            CURLOPT_POSTFIELDS	=> $post_data,
            CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
        );
        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );
        $header['httpcode']   = $httpcode;
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return $header;
    }

    function getDataAddress($cep)
    {
        $sql = "SELECT * FROM cep WHERE zipcode = '{$cep}'";
        $query = $this->db->query($sql);
        return $query->row_array();
    }
}

?>
