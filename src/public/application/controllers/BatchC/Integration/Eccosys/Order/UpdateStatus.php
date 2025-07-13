<?php

/**
 * Class UpdateStatus
 *
 * php index.php BatchC/Eccosys/Order/UpdateStatus run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Eccosys/Main.php";

class UpdateStatus extends Main
{
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

        $this->setJob('UpdateStatus');
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
        echo "Pegando pedidos para atualizar... \n";

        // Define a loja, para recuperar os dados para integração
        
        $this->setDataIntegration($store);

        // Recupera os pedidos
        $this->updateOrders();

        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Atualizaçao dos pedidos já integrados
     */
    private function updateOrders()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }


        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $ECCOSYS_URL = '';
        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $ECCOSYS_URL = $credentials->url_eccosys;
        }
        
        $orders = $this->getOrdersForUpdate();
        $iNFCount = 1;

        foreach ($orders as $orderIntegration) {
            $bolProblema = false;
            $idIntegration  = $orderIntegration['id'];
            $orderId        = $orderIntegration['order_id'];
            $paidStatus     = $orderIntegration['paid_status'];
           // $numeroPedido   =$orderIntegration['order_id_integration']; 
            $removeList     = true;
            $this->setUniqueId($orderId); // define novo unique_id
            $dataStatus = null;
            //echo "\n L_103_paidStatus: ".$paidStatus. " -orderId: ".$orderId;
           
            // Código do pedido integrado na Eccosys
            $idEccosys = $this->getOrderIdEccosys($orderId);

            // Não encontrou o código do pedido integrado
            if (!$idEccosys) {              
                $msgError = "Não foi possível localizar o código do pedido integrador. PEDIDO_CONECTA={$orderId}, idEccosys={$idEccosys} para atualizar! ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                
                //$this->log_data('batch', $log_name, $msgError, "E");
                //$this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível atualizar o pedido {$orderId}</h4> <ul><li>Não foi possível localizar o pedido para integrar.</li></ul>", "E");
                $bolProblema = true;
                continue;
            }

            // Pedido ainda não foi integrado
            $integratedOrder = $this->getIntegratedOrder($orderId);
            if (!$integratedOrder) {
                $msgError = "Pedido ainda não integrado. PEDIDO_CONECTA={$orderId}. ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "W");
                $bolProblema = true;
                continue;
            }

            // Pedido para cancelar
            $orderCancel = $this->getOrderCancel($orderId);
            if ($orderCancel) {
                //cancelar na eccosys
                $msgError = "Pedido deve ser cancelado. PEDIDO_CONECTA={$orderId}. ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "I");
                $paidStatus = 2;
            }

            $status = $this->getStatusIntegration($paidStatus);

            // Ignorar status, não deve ser alterado na Eccosys
            if ($status['status'] === null) {
                $msgError = "\nChegou status={$paidStatus}, não deve ser integrado, apenas remover da fila!  ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}";
                $this->removeOrderIntegration($idIntegration);
                $this->log_data('batch', $log_name, "Remove da fila status nulo, Pedido: {$orderId}, paidStatus = {$paidStatus}, status = {$status['status']}", "I");
                $bolProblema = true;
                continue;
            }

            // Aguardar pedido ser faturado na Eccosys Status = 3 pronto_para_picking
            if ($status['status'] == 3) {
                // consulta nfe Eccosys
                //echo "Aguardar pedido ser faturado na Eccosys\n";

                // Pedido já tem uma NF-e, atualizar o status
                $orderWithNfe = $this->getOrderWithNfe($orderId);
                //$orderWithNfe = false;  // retirar após teste
                //echo '<br>L_161_$orderWithNfe '.$orderWithNfe;

                if ($orderWithNfe) {
                    $msgError = "Pedido já tem uma NF-e. Será atualizado apenas seu status para 52. PEDIDO_CONECTA={$orderId} para atualizar! ORDER_INTEGRATION=".json_encode($orderIntegration);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    // passar pedido para status 50
                    // $this->updateStatusForOrder($orderId, 50, 3); // FLUXO ANTIGO
					$this->updateStatusForOrder($orderId, 52, 3);
                    $this->removeOrderIntegration($idIntegration);
                    $bolProblema = true;
                    continue;
                }

                // Obter dados do pedido
                $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
                $ECCOSYS_URL = '';
                if ($dataIntegrationStore) {
                    $credentials = json_decode($dataIntegrationStore['credentials']);
                    $ECCOSYS_URL = $credentials->url_eccosys;
                }
                // faz o que o job precisa fazer

                // começando a pegar os pedidos para criar
                $url = $ECCOSYS_URL.'/api/pedidos/'.$idEccosys;
                $data = "";
             
                $dataOrder = json_decode(json_encode($this->sendREST($url, $data)));
               
                if ($dataOrder->httpcode != 200) {
                    $msgError = "Não foi possível localizar o pedido {$orderId}, idEccosys: {$idEccosys}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados do pedido {$orderId}</h4>", "E");
                    $bolProblema = true;
                    continue;
                }
                
                $registro = json_decode($dataOrder->content);
                $registro = $registro[0];

                $historico = $registro->situacaoDescricao;

                //$registro->situacao = 3;  //Teste forçado Diogo colocado para deixar como gerou nota fiscal / pronto para picking fluxo normal, faturado.; 
                //$registro->situacao = 1;  //Teste como atendido no eccosy.
                //$registro->situacao = -1;  //Teste como aguardando pagamento no eccosy.
                //$registro->situacao = 4;  //Teste como pagamento em análise no eccosy.
                //$registro->situacao = 'D';  //Teste como pagamento em análise no eccosy.
                //$registro->situacao = 2;  //Teste como Cancelado no eccosy.
                
                //echo "\n L_205_registro->situacao: ".$registro->situacao."\n";
                // Verifica situação do pedido, para atualizar situação para aprovado, antes de criar a nfe, só irá fazer o fluxo certo pronto para picking situacao == 3;
                if ($registro->situacao == 0) {
                    $removeList = false;
                    $bolProblema = true; //coloco para não apresentar novamente a msg do log inegration
                    echo "\n Aguardar pedido ser faturado na Eccosys, Pedido {$orderId}, idEccosys: {$idEccosys}, situacao: {$registro->situacao}";
                    $msgError = "Aguardar pedido ser faturado na Eccosys, Pedido {$orderId}, idEccosys: {$idEccosys}, situacao: {$registro->situacao} - RETORNO=" . json_encode($registro);
                    $this->log_data('batch', $log_name, $msgError, "I");
                    continue;
                } else {
                    // Recupera código da nfe na Eccosys
                   $idNfeOrder = $registro->idNotaFiscalRef;

/*                   if($orderId == 517002713){
                    $idNfeOrder = 7885422;
                   } else {
                    $idNfeOrder = 000015;
                   } */
                   
                    //echo '<br>L_228_idIntegration: '.$idIntegration. ' -idNfeOrder: '.$idNfeOrder."\n";
                    // Se for zero, não existe nfe ainda não vai prosseguir.
                    if ($idIntegration == 0 || $idNfeOrder <= 0) {
                        echo "Pedido ainda não faturado, Pedido {$orderId}, idEccosys: {$idEccosys}, situacao: {$registro->situacao}\n";
                        $msgError = "Pedido ainda não faturado, Pedido {$orderId}, idEccosys: {$idEccosys}, situacao: {$registro->situacao} - RETORNO=" . json_encode($registro);
                        $this->log_data('batch', $log_name, $msgError, "I");
                        continue;
                    }

                    // Obter dados NF-e                        
                    $url = $ECCOSYS_URL.'/api/nfes/'.$idNfeOrder;
                    $data = "";
                    $dataNfe = json_decode(json_encode($this->sendREST($url, $data)));

                    if ($dataNfe->httpcode != 200) {
                        if ($dataNfe->httpcode == 32) {
                            // Ignora e continua, aguardando nfe ser gerada
                            $msgError = "Pedido ainda não faturado. PEDIDO={$orderId}, RETORNO=" . json_encode($dataNfe);
                            echo "{$msgError}\n";
                            //$this->log_data('batch', $log_name, $msgError, "W");
                            $bolProblema = true;
                            continue;
                        }
                        
                        $msgError = "Não foi possível localizar o pedido {$orderId}, nfe: {$idNfeOrder}! ORDER_INTEGRATION=" . json_encode($orderIntegration) . ", RETORNO=" . json_encode($dataNfe);
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados de faturamento do pedido {$orderId}</h4>", "E");
                        $bolProblema = true;
                        continue;
                        //$status["status"] = 0; //Fica somente
                    }

                    // Dados da NF-e
                    $arrNfe = json_decode($dataNfe->content);

/*
                    $objArrNfe = '[{"idNotaFiscal":"148708855","id":"148708855","numero":"000008","idUnidadeNegocio":"0","idContato":"148372850", ';
                    $objArrNfe .=' "idTipoNota":"129661787","idOrigem":"148372843","contato":"JUDIANE DALL AGNOL","cnpj":"91906105049","identificadorEstrangeiro":"",';
                    $objArrNfe .=' "ie":"","identificadorIE":"9","tipoPessoa":"F","endereco":"Rua Hon\u00f3rio Silveira Dias","enderecoNro":"890",';
                    $objArrNfe .=' "bairro":"S\u00e3o Jo\u00e3o","cep":"90550150","idMunicipio":"4314902","municipio":"Porto Alegre","fone":"51 33727225","uf":"RS",';
                    $objArrNfe .=' "cfop":"6108","natureza":"[S] Venda de mercadoria adq ou rec de terceiros destinada a","cfop_servicos":"","natureza_servicos":"",';
                    $objArrNfe .=' "tipo":"S","dataEmissao":"2021-01-06","dtEmissaoDANFE":"2021-01-06 14:07:02","dataSaidaEntrada":"2021-01-06","horaSaidaEntrada":"14:06","baseICMS":"25.70","valorICMS":"3.09","baseICMSSubst":"0.00","valorICMSSubst":"0.00",';
                    $objArrNfe .=' "frete":"0.00","seguro":"0.00","outrasDespesas":"0.00","valorIPI":"0.00","valorProdutos":"25.70","valorNota":"25.70","transportador":"CARRIERS LOGISTICA E TRANSPORTE LTDA","fretePorConta":"T","placa":"","ufVeiculo":"",';
                    $objArrNfe .=' "cnpjTransportador":"10.520.136\/0001-86","enderecoTransportador":"Avenida Maria Coelho Aguiar","municipioTransportador":"S\u00e3o Paulo","ufTransportador":"SP","ieTransportador":"","qtdVolumes":"1","especie":"","marca":"","nroDosVolumes":"","pesoBruto":"0.000","pesoLiquido":"14.380",';
                    $objArrNfe .=' "situacao":"7","desconto":"0,00",';
                    $objArrNfe .=' "tipoDesconto":"","valorDesconto":"0.00","obsSistema":"Val Aprox Tributos R$ Fed: 2,00 (7,78%) Est: 2,29 (8,91%) Mun: 0,00 (0,00%) Fonte: IBPT Vers\u00e3o 20.2.C \nValores totais do ICMS Interestadual: DIFAL da UF destino R$1,40 + FCP R$0,00; DIFAL da UF Origem R$0,00. \n N\u00famero do Pedido de Venda: 21962\n N\u00famero da Ordem De Compra: Lojas Americanas-281086673505","observacoes":"Celular: 51 984006507","pedidos":"","representante":"","portador":"","condicao":"","baseISS":"0.00","baseCalculoISS":"0.00","percentualISS":"0.00","valorISS":"0.00","valorServicos":"0.00","serie":"21","totalFaturado":"25.70","inscricaoSuframa":"","complemento":"Ape 604","valorISSQN":"0.00","tipoPagamento":"",';
                    $objArrNfe .=' "finalidade":"1","idPais":"0","nomePais":"","sisdeclaraOperacao":"0","sisdeclaraTipoNota":"0","sisdeclaraNumeroGuia":"0",';
                    $objArrNfe .=' "sisdeclaraCadastroViticultor":"","valorII":"0.00","notaTipo":"N","diNumero":"","diData":"0000-00-00","diLocalDesembaraco":"","diUFDesembaraco":"","diDataDesembaraco":"0000-00-00","diCodigoExportador":"","diNumeroAdicao":"0","diSequencialAdicao":"0","diCodigoFabricante":"","diValorDescontoAdicao":"0.00","diTipoTransporte":"0","diTipoIntermedio":"0","diNFCI":"","diCnpjAdquirinte":"","diUfAdquirinte":"","valorDespesaAduaneira":"0.00","localEmbarque":"","ufEmbarque":"","valorBaseDiferimento":"0.00","valorPresumido":"0.00","simples":"N","alqSimples":"0.0000","valorSimples":"0.00","valorFunrural":"0.00","email":"cliente@teste.com.br","idCategoria":"0","idVendedor":"319","tipoAmbiente":"1","nfe_msg":"","calculaImpostos":"N","idMagento":"","valorRetPIS":"0.00","valorRetCOFINS":"0.00","valorRetCSLL":"0.00",';
                    $objArrNfe .=' "valorRetBaseIR":"0.00","valorRetIR":"0.00","valorMinimoParaRetencao":"0.00","valorUnitarioComII":"N","crt":"3","custoAtualizado":"","pvFrete":"0.00","hashXML":"","pickingRealizado":"S","idOrdemColeta":"0","opcEnderecoDiferente":"S","opcEnderecoRetirada":"","integradoLogistica":"N","confirmacaoLogistica":"S","dapiXREF":"","idDeposito":"127257897","considerarNoEstoqueDisponivel":"S","protocoloLogistica":"","servicoContratado":"0","tipoEntrega":"0","formaFrete":"0","codigoRastreamento":"EMB0000904994","dataCodigoRastreamento":"0000-00-00 00:00:00","valorAproximadoImpostosTotal":"4.29","pesoTransportadora":"15.660","avisoRecebimento":"N","maoPropria":"N","possuiValorDeclarado":"N","valorDeclarado":"25.7000000000","possuiListaPostagem":"N","tipoObjeto":"","dimensaoDiametro":"0","dimensaoAltura":"0","dimensaoLargura":"0","dimensaoComprimento":"0","valorTotalICMSPartilhaDestino":"1.40","valorTotalICMSPartilhaOrigem":"0.00",';
                    $objArrNfe .=' "percentualICMSPartilhaDestino":"100.0000","valorTotalICMSFCPDestino":"0.00","valorTotalICMSDesonerado":"0.00","exportacaoNumeroRegistro":"","idReferenciaExportacao":"0","valorTotalFCP":"0.00","totalIpiDevolvido":"0.00","id_lote":"148708897","chave_acesso":"35210119112842000163550210000000081487088558","numero_protocolo":"135210014670775","dataHoraEnvio":"2021-01-06 14:06:56","_OutroEndereco":[{"id":"148708894","idEmpresa":"127257873","idNotaFiscal":"148708855","nome":"JUDIANE DALL AGNOL","endereco":"Rua Hon\u00f3rio Silveira Dias","enderecoNro":"890","bairro":"S\u00e3o Jo\u00e3o","complemento":"Ape 604","cep":"90550150","cidade":"Porto Alegre","idMunicipio":"4314902","uf":"RS","idPais":"0","nomePais":"","tipo":"Entrega"}],"_LinkUrl":"https:\/\/condor.eccosys.com.br\/doc.view.php?id=6d1c428366d617165dbd73520731161c",';
                    $objArrNfe .=' "xml_nfe":"<NFe xmlns=\"http:\/\/www.portalfiscal.inf.br\/nfe\"><infNFe Id=\"NFe35210119112842000163550210000000081487088558\" versao=\"4.00\"><ide><cUF>35<\/cUF><cNF>48708855<\/cNF><natOp>[S] Venda de mercadoria adq ou rec de terceiros destinada a<\/natOp><mod>55<\/mod><serie>21<\/serie><nNF>8<\/nNF><dhEmi>2021-01-06T14:06:00-03:00<\/dhEmi><dhSaiEnt>2021-01-06T14:06:00-03:00<\/dhSaiEnt><tpNF>1<\/tpNF><idDest>2<\/idDest><cMunFG>3515004<\/cMunFG><tpImp>1<\/tpImp><tpEmis>1<\/tpEmis><cDV>8<\/cDV><tpAmb>1<\/tpAmb><finNFe>1<\/finNFe><indFinal>1<\/indFinal><indPres>2<\/indPres><procEmi>0<\/procEmi><verProc>ERP Eccosys<\/verProc><\/ide><emit><CNPJ>19112842000163<\/CNPJ><xNome>Tatix Com Participacoes Ltda<\/xNome><xFant>Condor<\/xFant><enderEmit><xLgr>AVENIDA HELIO OSSAMU DAIKUARA<\/xLgr><nro>01445<\/nro><xCpl>MODULO 12<\/xCpl><xBairro>JARDIM VISTA ALEGRE<\/xBairro><cMun>3515004<\/cMun><xMun>Embu das Artes<\/xMun><UF>SP<\/UF><CEP>06807000<\/CEP><cPais>1058<\/cPais><xPais>BRASIL<\/xPais><\/enderEmit><IE>298221581114<\/IE><IEST>9000023756<\/IEST><CRT>3<\/CRT><\/emit><dest><CPF>91906105049<\/CPF><xNome>JUDIANE DALL AGNOL<\/xNome><enderDest><xLgr>Rua Honorio Silveira Dias<\/xLgr><nro>890<\/nro><xCpl>Ape 604<\/xCpl><xBairro>Sao Joao<\/xBairro><cMun>4314902<\/cMun><xMun>Porto Alegre<\/xMun><UF>RS<\/UF><CEP>90550150<\/CEP><cPais>1058<\/cPais><xPais>BRASIL<\/xPais><fone>5133727225<\/fone><\/enderDest><indIEDest>9<\/indIEDest><email>cliente@teste.com.br<\/email><\/dest><entrega><CPF>91906105049<\/CPF><xNome>JUDIANE DALL AGNOL<\/xNome>';
                    $objArrNfe .=' <xLgr>Rua Honorio Silveira Dias<\/xLgr><nro>890<\/nro><xCpl>Ape 604<\/xCpl><xBairro>Sao Joao<\/xBairro><cMun>4314902<\/cMun><xMun>Porto Alegre<\/xMun><UF>RS<\/UF><CEP>90550150<\/CEP><cPais>1058<\/cPais><xPais>BRASIL<\/xPais><\/entrega><det nItem=\"1\"><prod><cProd>972140<\/cProd><cEAN>7891055394243<\/cEAN><xProd>Enxaguante Bucal Barbie<\/xProd><NCM>33069000<\/NCM><CEST>2002500<\/CEST><CFOP>6108<\/CFOP><uCom>CX<\/uCom><qCom>1.0000<\/qCom><vUnCom>9.90<\/vUnCom><vProd>9.90<\/vProd><cEANTrib>17891055394240<\/cEANTrib><uTrib>CX<\/uTrib><qTrib>1.0000<\/qTrib><vUnTrib>9.90<\/vUnTrib><indTot>1<\/indTot><xPed>Lojas American<\/xPed><nItemPed>1<\/nItemPed><\/prod><imposto><vTotTrib>1.11<\/vTotTrib><ICMS><ICMS00><orig>0<\/orig><CST>00<\/CST><modBC>3<\/modBC><vBC>9.90<\/vBC><pICMS>12.00<\/pICMS><vICMS>1.19<\/vICMS><\/ICMS00><\/ICMS><IPI><cEnq>999<\/cEnq><IPINT><CST>53<\/CST><\/IPINT><\/IPI><PIS><PISNT><CST>06<\/CST><\/PISNT><\/PIS><COFINS><COFINSNT><CST>06<\/CST><\/COFINSNT><\/COFINS><ICMSUFDest><vBCUFDest>9.90<\/vBCUFDest><vBCFCPUFDest>9.90<\/vBCFCPUFDest><pFCPUFDest>0.00<\/pFCPUFDest><pICMSUFDest>17.50<\/pICMSUFDest><pICMSInter>12.00<\/pICMSInter><pICMSInterPart>100.00<\/pICMSInterPart><vFCPUFDest>0.00<\/vFCPUFDest><vICMSUFDest>0.54<\/vICMSUFDest><vICMSUFRemet>0.00<\/vICMSUFRemet><\/ICMSUFDest><\/imposto><\/det><det nItem=\"2\"><prod><cProd>972146<\/cProd><cEAN>7891055816028<\/cEAN><xProd>Kit Escova + Gel Dental Infantil com Fluor Tutti Frutti Barbie Condor Junior 50g Leve Mais Pague Menos<\/xProd><NCM>96032100<\/NCM><CEST>2005800<\/CEST><CFOP>6108<\/CFOP><uCom>CX<\/uCom><qCom>1.0000<\/qCom><vUnCom>9.90<\/vUnCom><vProd>9.90<\/vProd><cEANTrib>87891055816024<\/cEANTrib><uTrib>CX<\/uTrib><qTrib>1.0000<\/qTrib><vUnTrib>9.90<\/vUnTrib><indTot>1<\/indTot><xPed>Lojas American<\/xPed><nItemPed>2<\/nItemPed><\/prod><imposto><vTotTrib>2.52<\/vTotTrib><ICMS><ICMS00><orig>5<\/orig><CST>00<\/CST><modBC>3<\/modBC><vBC>9.90<\/vBC><pICMS>12.00<\/pICMS><vICMS>1.19<\/vICMS><\/ICMS00><\/ICMS><IPI><cEnq>999<\/cEnq><IPINT><CST>53<\/CST><\/IPINT><\/IPI><PIS><PISNT><CST>04<\/CST><\/PISNT><\/PIS><COFINS><COFINSNT><CST>04<\/CST><\/COFINSNT><\/COFINS><ICMSUFDest><vBCUFDest>9.90<\/vBCUFDest><vBCFCPUFDest>9.90<\/vBCFCPUFDest><pFCPUFDest>0.00<\/pFCPUFDest><pICMSUFDest>17.50<\/pICMSUFDest><pICMSInter>12.00<\/pICMSInter><pICMSInterPart>100.00<\/pICMSInterPart><vFCPUFDest>0.00<\/vFCPUFDest><vICMSUFDest>0.54<\/vICMSUFDest><vICMSUFRemet>0.00<\/vICMSUFRemet><\/ICMSUFDest><\/imposto><\/det><det nItem=\"3\"><prod><cProd>972141<\/cProd><cEAN>7891055539408<\/cEAN><xProd>Fio Dental Barbie<\/xProd><NCM>33062000<\/NCM><CEST>2002400<\/CEST><CFOP>6108<\/CFOP><uCom>CX<\/uCom><qCom>1.0000<\/qCom><vUnCom>5.90<\/vUnCom><vProd>5.90<\/vProd><cEANTrib>27891055539402<\/cEANTrib><uTrib>CX<\/uTrib><qTrib>1.0000<\/qTrib><vUnTrib>5.90<\/vUnTrib><indTot>1<\/indTot><xPed>Lojas American<\/xPed><nItemPed>3<\/nItemPed><\/prod><imposto><vTotTrib>0.66<\/vTotTrib><ICMS><ICMS00><orig>0<\/orig><CST>00<\/CST><modBC>3<\/modBC><vBC>5.90<\/vBC><pICMS>12.00<\/pICMS><vICMS>0.71<\/vICMS><\/ICMS00><\/ICMS><IPI><cEnq>999<\/cEnq><IPINT><CST>53<\/CST><\/IPINT><\/IPI><PIS><PISNT><CST>06<\/CST><\/PISNT><\/PIS><COFINS><COFINSNT><CST>06<\/CST><\/COFINSNT><\/COFINS><ICMSUFDest><vBCUFDest>5.90<\/vBCUFDest><vBCFCPUFDest>5.90<\/vBCFCPUFDest><pFCPUFDest>0.00<\/pFCPUFDest><pICMSUFDest>17.50<\/pICMSUFDest><pICMSInter>12.00<\/pICMSInter><pICMSInterPart>100.00<\/pICMSInterPart><vFCPUFDest>0.00<\/vFCPUFDest><vICMSUFDest>0.32<\/vICMSUFDest><vICMSUFRemet>0.00<\/vICMSUFRemet><\/ICMSUFDest><\/imposto><\/det><total><ICMSTot><vBC>25.70<\/vBC><vICMS>3.09<\/vICMS><vICMSDeson>0.00<\/vICMSDeson><vFCPUFDest>0.00<\/vFCPUFDest><vICMSUFDest>1.40<\/vICMSUFDest><vICMSUFRemet>0.00<\/vICMSUFRemet><vFCP>0<\/vFCP><vBCST>0.00<\/vBCST><vST>0.00<\/vST><vFCPST>0<\/vFCPST><vFCPSTRet>0<\/vFCPSTRet><vProd>25.70<\/vProd><vFrete>0.00<\/vFrete><vSeg>0.00<\/vSeg><vDesc>0.00<\/vDesc><vII>0.00<\/vII><vIPI>0.00<\/vIPI><vIPIDevol>0.00<\/vIPIDevol><vPIS>0.00<\/vPIS><vCOFINS>0.00<\/vCOFINS><vOutro>0.00<\/vOutro><vNF>25.70<\/vNF><vTotTrib>4.29<\/vTotTrib><\/ICMSTot><retTrib\/><\/total><transp><modFrete>2<\/modFrete><transporta><CNPJ>10520136000186<\/CNPJ><xNome>CARRIERS LOGISTICA E TRANSPORTE LTDA<\/xNome><xEnder>Avenida Maria Coelho Aguiar<\/xEnder><xMun>Sao Paulo<\/xMun><UF>SP<\/UF><\/transporta><vol><qVol>1<\/qVol><pesoL>14.380<\/pesoL><pesoB>0.000<\/pesoB><\/vol><\/transp><pag><detPag><tPag>99<\/tPag><vPag>25.70<\/vPag><\/detPag><\/pag><infAdic><infCpl>Val Aprox Tributos R$ Fed: 2,00 (7,78%) Est: 2,29 (8,91%) Mun: 0,00 (0,00%) Fonte: IBPT Versao 20.2.C Valores totais do ICMS Interestadual: DIFAL da UF destino R$1,40 + FCP R$0,00; DIFAL da UF Origem R$0,00.  Numero do Pedido de Venda: 21962 Numero da Ordem De Compra: Lojas Americanas-281086673505 Celular: 51 984006507<\/infCpl><\/infAdic><\/infNFe><Signature xmlns=\"http:\/\/www.w3.org\/2000\/09\/xmldsig#\"><SignedInfo><CanonicalizationMethod Algorithm=\"http:\/\/www.w3.org\/TR\/2001\/REC-xml-c14n-20010315\"\/><SignatureMethod Algorithm=\"http:\/\/www.w3.org\/2000\/09\/xmldsig#rsa-sha1\"\/><Reference URI=\"#NFe35210119112842000163550210000000081487088558\"><Transforms><Transform Algorithm=\"http:\/\/www.w3.org\/2000\/09\/xmldsig#enveloped-signature\"\/><Transform Algorithm=\"http:\/\/www.w3.org\/TR\/2001\/REC-xml-c14n-20010315\"\/><\/Transforms><DigestMethod Algorithm=\"http:\/\/www.w3.org\/2000\/09\/xmldsig#sha1\"\/><DigestValue>q+78pPKX8pus\/O28J4mw+kpJKf4=<\/DigestValue><\/Reference><\/SignedInfo><SignatureValue>ogHNwzD8hyRSC8Jtxl5MbFQbflr8QVUUvlC2Q2bAS\/3sJIZB58mMcgnQN28qi2vwG3iyquEWgq0u4UnGRSpiuyZkpvjRcS3WdP+Hyg78T9eYV5GKo+ReLYUX8cpoQSkkw5E0PTIvOCetdPPxeoThL891DfgACjq0NmrigxNAkVbPvipCbO411wommBQTeo5eyvnEVKOFTeherkGUHwfvupHNnLoclHyKbPB+2QAfS\/5piz7MTBOsu0L5Nr5lmeyBIGKCb2jhWDWozStEf\/rSr+InCG86dbu\/Iga7gMPv6rQ6unxSB08JqWlU1m5iZ0EtlzdxYya+7gblMnv9kOQ7gA==<\/SignatureValue><KeyInfo><X509Data><X509Certificate>MIIIHjCCBgagAwIBAgIIb0RCBcaYXKIwDQYJKoZIhvcNAQELBQAwdTELMAkGA1UEBhMCQlIxEzARBgNVBAoMCklDUC1CcmFzaWwxNjA0BgNVBAsMLVNlY3JldGFyaWEgZGEgUmVjZWl0YSBGZWRlcmFsIGRvIEJyYXNpbCAtIFJGQjEZMBcGA1UEAwwQQUMgU0VSQVNBIFJGQiB2NTAeFw0yMDEwMTkxMzM2MDBaFw0yMTEwMTkxMzM2MDBaMIIBODELMAkGA1UEBhMCQlIxCzAJBgNVBAgMAlNQMRcwFQYDVQQHDA5FTUJVIERBUyBBUlRFUzETMBEGA1UECgwKSUNQLUJyYXNpbDEYMBYGA1UECwwPMDAwMDAxMDA5ODg2NDI4MTYwNAYDVQQLDC1TZWNyZXRhcmlhIGRhIFJlY2VpdGEgRmVkZXJhbCBkbyBCcmFzaWwgLSBSRkIxFjAUBgNVBAsMDVJGQiBlLUNOUEogQTExGTAXBgNVBAsMEEFDIFNFUkFTQSBSRkIgdjUxFzAVBgNVBAsMDjE0NjAyMjY5MDAwMTUyMRMwEQYDVQQLDApQUkVTRU5DSUFMMTswOQYDVQQDDDJUQVRJWCBDT01FUkNJTyBFIFBBUlRJQ0lQQUNPRVMgTFREQToxOTExMjg0MjAwMDE2MzCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALKMzOwvqmbF1a2O0DfFQtrjg8OR4\/R5whBWipnNJKUx2YIcCnV9PcV4DxcijeIhGU88x9chnm02b8nuE2Z2cZL39tSeToNb7OSYkNR1Qf9j6D4\/gHsiqSgdyJxTdWozDPfJz3f0HocyRXHdBhlf\/U3SCCLJLIhAR44uqXBvchF5o85NDG\/LtWu5ZeKjKcr49mUFMgzzk1uYbNUTfMdcHXxWF8DuSdtVLXGYDPSBVtsY4qQgQerp9GVI1mTQ5C4KehnkJjD5GEte43LuxTDLwDWCcLU5gM9TkSExp62WnqiEzMXSoBjP26N5LttT\/UMwTms\/cmhOpLQfeskY2YJAtDsCAwEAAaOCAuswggLnMAkGA1UdEwQCMAAwHwYDVR0jBBgwFoAU7PFBUVeo5jrpXrOgIvkIirU6h48wgZkGCCsGAQUFBwEBBIGMMIGJMEgGCCsGAQUFBzAChjxodHRwOi8vd3d3LmNlcnRpZmljYWRvZGlnaXRhbC5jb20uYnIvY2FkZWlhcy9zZXJhc2FyZmJ2NS5wN2IwPQYIKwYBBQUHMAGGMWh0dHA6Ly9vY3NwLmNlcnRpZmljYWRvZGlnaXRhbC5jb20uYnIvc2VyYXNhcmZidjUwgbsGA1UdEQSBszCBsIEZTFVJU0ZFUk5BTkRPQFRBVElYLkNPTS5CUqAlBgVgTAEDAqAcExpMVUlTIEZFUk5BTkRPIFNJTUFPIE1JTExFUqAZBgVgTAEDA6AQEw4xOTExMjg0MjAwMDE2M6A4BgVgTAEDBKAvEy0wNTA0MTk3MTAwNzA3NTI1NzU2MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDCgFwYFYEwBAwegDhMMMDAwMDAwMDAwMDAwMHEGA1UdIARqMGgwZgYGYEwBAgENMFwwWgYIKwYBBQUHAgEWTmh0dHA6Ly9wdWJsaWNhY2FvLmNlcnRpZmljYWRvZGlnaXRhbC5jb20uYnIvcmVwb3NpdG9yaW8vZHBjL2RlY2xhcmFjYW8tcmZiLnBkZjAdBgNVHSUEFjAUBggrBgEFBQcDAgYIKwYBBQUHAwQwgZ0GA1UdHwSBlTCBkjBKoEigRoZEaHR0cDovL3d3dy5jZXJ0aWZpY2Fkb2RpZ2l0YWwuY29tLmJyL3JlcG9zaXRvcmlvL2xjci9zZXJhc2FyZmJ2NS5jcmwwRKBCoECGPmh0dHA6Ly9sY3IuY2VydGlmaWNhZG9zLmNvbS5ici9yZXBvc2l0b3Jpby9sY3Ivc2VyYXNhcmZidjUuY3JsMB0GA1UdDgQWBBQNpn1ofa4lfOy0q4gq6CzCpAjejTAOBgNVHQ8BAf8EBAMCBeAwDQYJKoZIhvcNAQELBQADggIBAE5ejZfWjipvj3OuwdSq\/TephzsOtdJEi1AtHr1jGAiX7RTDXMI3keeZZ33JuawIkQuq0tnvYOreT7fkQGB1N11IXo+g91gvFGnGe+bmlWHP2y8baD\/HUrfjnSJqucX2cj\/SKGeuR0sQwekMSL3SBWdWm\/qKzwUPyL8x5RzJEAXi+vmLwM0ZNq2UpxoGciWFRy5xsbcBzwDS2UxC2E1jJuFpJhLowtOLIAyz1R9CdXCLzNTQhX9E9sXMuKZGGLrJiT3QTUFfJ73EuZAMaqkIFltl2SU2OuXxK\/gDBlXb\/FnoT75Vu3bX7X\/NhHGrXc9g4L9yPpHx87TlAxvNUc73x\/\/uWrHFkQZL7kOJeONp+YSeBM0leX1HodtOwk14h0xRY\/6pFKGuGJSkjjwfIV1vET041H0g7uTqM3dEa\/P39mAT0LcqyYJZ68sAMgTpQpf3eIYuB78RdsI3hNO75+2h+xjtoWz+sYGlahbgPzTeIvRKSqxaIO0K81cm3mS5JMK4iOkYwbKUMWrOfdF1PUDCOhB8ULoa4\/5wW1635rjhJxLEg3vua+KKS0k2tadz4ux3WtxSRZrrMff9k8i78\/29ok1le+da+TvdSIrkpaAUIAFOXEf7Zw7DCKr31wXlsQRX39DNFs4eBghnp3knI8BEjLiImkXH0v48o3p+oOtrOhDg<\/X509Certificate><\/X509Data><\/KeyInfo><\/Signature><\/NFe>","xml_prot_nfe":"<protNFe versao=\"4.00\"><infProt><tpAmb>1<\/tpAmb><verAplic>SP_NFE_PL009_V4<\/verAplic><chNFe>35210119112842000163550210000000081487088558<\/chNFe><dhRecbto>2021-01-06T14:06:56-03:00<\/dhRecbto><nProt>135210014670775<\/nProt><digVal>q+78pPKX8pus\/O28J4mw+kpJKf4=<\/digVal><cStat>100<\/cStat><xMotivo>Autorizado o uso da NF-e<\/xMotivo><\/infProt><\/protNFe>","xml_nfe_completo":"<?xml version=\"1.0\" encoding=\"UTF-8\"?><nfeProc xmlns=\"http:\/\/www.portalfiscal.inf.br\/nfe\" versao=\"4.00\"><NFe xmlns=\"http:\/\/www.portalfiscal.inf.br\/nfe\"><infNFe Id=\"NFe35210119112842000163550210000000081487088558\" versao=\"4.00\"><ide><cUF>35<\/cUF><cNF>48708855<\/cNF><natOp>[S] Venda de mercadoria adq ou rec de terceiros destinada a<\/natOp><mod>55<\/mod><serie>21<\/serie><nNF>8<\/nNF><dhEmi>2021-01-06T14:06:00-03:00<\/dhEmi><dhSaiEnt>2021-01-06T14:06:00-03:00<\/dhSaiEnt><tpNF>1<\/tpNF><idDest>2<\/idDest><cMunFG>3515004<\/cMunFG><tpImp>1<\/tpImp><tpEmis>1<\/tpEmis><cDV>8<\/cDV><tpAmb>1<\/tpAmb><finNFe>1<\/finNFe><indFinal>1<\/indFinal>';
                    $objArrNfe .=' <indPres>2<\/indPres><procEmi>0<\/procEmi><verProc>ERP Eccosys<\/verProc><\/ide><emit><CNPJ>19112842000163<\/CNPJ><xNome>Tatix Com Participacoes Ltda<\/xNome><xFant>Condor<\/xFant><enderEmit><xLgr>AVENIDA HELIO OSSAMU DAIKUARA<\/xLgr><nro>01445<\/nro><xCpl>MODULO 12<\/xCpl><xBairro>JARDIM VISTA ALEGRE<\/xBairro><cMun>3515004<\/cMun><xMun>Embu das Artes<\/xMun><UF>SP<\/UF><CEP>06807000<\/CEP><cPais>1058<\/cPais><xPais>BRASIL<\/xPais><\/enderEmit><IE>298221581114<\/IE><IEST>9000023756<\/IEST><CRT>3<\/CRT><\/emit><dest><CPF>91906105049<\/CPF><xNome>JUDIANE DALL AGNOL<\/xNome><enderDest><xLgr>Rua Honorio Silveira Dias<\/xLgr><nro>890<\/nro><xCpl>Ape 604<\/xCpl><xBairro>Sao Joao<\/xBairro><cMun>4314902<\/cMun><xMun>Porto Alegre<\/xMun><UF>RS<\/UF><CEP>90550150<\/CEP><cPais>1058<\/cPais><xPais>BRASIL<\/xPais><fone>5133727225<\/fone><\/enderDest><indIEDest>9<\/indIEDest><email>cliente@teste.com.br<\/email><\/dest><entrega><CPF>91906105049<\/CPF><xNome>JUDIANE DALL AGNOL<\/xNome><xLgr>Rua Honorio Silveira Dias<\/xLgr><nro>890<\/nro><xCpl>Ape 604<\/xCpl><xBairro>Sao Joao<\/xBairro><cMun>4314902<\/cMun><xMun>Porto Alegre<\/xMun><UF>RS<\/UF><CEP>90550150<\/CEP><cPais>1058<\/cPais><xPais>BRASIL<\/xPais><\/entrega><det nItem=\"1\"><prod><cProd>972140<\/cProd><cEAN>7891055394243<\/cEAN><xProd>Enxaguante Bucal Barbie<\/xProd><NCM>33069000<\/NCM><CEST>2002500<\/CEST><CFOP>6108<\/CFOP><uCom>CX<\/uCom><qCom>1.0000<\/qCom><vUnCom>9.90<\/vUnCom><vProd>9.90<\/vProd><cEANTrib>17891055394240<\/cEANTrib><uTrib>CX<\/uTrib><qTrib>1.0000<\/qTrib><vUnTrib>9.90<\/vUnTrib><indTot>1<\/indTot><xPed>Lojas American<\/xPed><nItemPed>1<\/nItemPed><\/prod><imposto><vTotTrib>1.11<\/vTotTrib><ICMS><ICMS00><orig>0<\/orig><CST>00<\/CST><modBC>3<\/modBC><vBC>9.90<\/vBC><pICMS>12.00<\/pICMS><vICMS>1.19<\/vICMS><\/ICMS00><\/ICMS><IPI><cEnq>999<\/cEnq><IPINT><CST>53<\/CST><\/IPINT><\/IPI><PIS><PISNT><CST>06<\/CST><\/PISNT><\/PIS><COFINS><COFINSNT><CST>06<\/CST><\/COFINSNT><\/COFINS><ICMSUFDest><vBCUFDest>9.90<\/vBCUFDest><vBCFCPUFDest>9.90<\/vBCFCPUFDest><pFCPUFDest>0.00<\/pFCPUFDest><pICMSUFDest>17.50<\/pICMSUFDest><pICMSInter>12.00<\/pICMSInter><pICMSInterPart>100.00<\/pICMSInterPart><vFCPUFDest>0.00<\/vFCPUFDest><vICMSUFDest>0.54<\/vICMSUFDest><vICMSUFRemet>0.00<\/vICMSUFRemet><\/ICMSUFDest><\/imposto><\/det><det nItem=\"2\"><prod><cProd>972146<\/cProd><cEAN>7891055816028<\/cEAN><xProd>Kit Escova + Gel Dental Infantil com Fluor Tutti Frutti Barbie Condor Junior 50g Leve Mais Pague Menos<\/xProd><NCM>96032100<\/NCM><CEST>2005800<\/CEST><CFOP>6108<\/CFOP><uCom>CX<\/uCom><qCom>1.0000<\/qCom><vUnCom>9.90<\/vUnCom><vProd>9.90<\/vProd><cEANTrib>87891055816024<\/cEANTrib><uTrib>CX<\/uTrib><qTrib>1.0000<\/qTrib><vUnTrib>9.90<\/vUnTrib><indTot>1<\/indTot><xPed>Lojas American<\/xPed><nItemPed>2<\/nItemPed><\/prod><imposto><vTotTrib>2.52<\/vTotTrib><ICMS><ICMS00><orig>5<\/orig><CST>00<\/CST><modBC>3<\/modBC><vBC>9.90<\/vBC><pICMS>12.00<\/pICMS><vICMS>1.19<\/vICMS><\/ICMS00><\/ICMS><IPI><cEnq>999<\/cEnq><IPINT><CST>53<\/CST><\/IPINT><\/IPI><PIS><PISNT><CST>04<\/CST><\/PISNT><\/PIS><COFINS><COFINSNT><CST>04<\/CST><\/COFINSNT><\/COFINS><ICMSUFDest><vBCUFDest>9.90<\/vBCUFDest><vBCFCPUFDest>9.90<\/vBCFCPUFDest><pFCPUFDest>0.00<\/pFCPUFDest><pICMSUFDest>17.50<\/pICMSUFDest><pICMSInter>12.00<\/pICMSInter><pICMSInterPart>100.00<\/pICMSInterPart><vFCPUFDest>0.00<\/vFCPUFDest><vICMSUFDest>0.54<\/vICMSUFDest><vICMSUFRemet>0.00<\/vICMSUFRemet><\/ICMSUFDest><\/imposto><\/det><det nItem=\"3\"><prod><cProd>972141<\/cProd><cEAN>7891055539408<\/cEAN><xProd>Fio Dental Barbie<\/xProd><NCM>33062000<\/NCM><CEST>2002400<\/CEST><CFOP>6108<\/CFOP><uCom>CX<\/uCom><qCom>1.0000<\/qCom><vUnCom>5.90<\/vUnCom><vProd>5.90<\/vProd><cEANTrib>27891055539402<\/cEANTrib><uTrib>CX<\/uTrib><qTrib>1.0000<\/qTrib><vUnTrib>5.90<\/vUnTrib><indTot>1<\/indTot><xPed>Lojas American<\/xPed><nItemPed>3<\/nItemPed><\/prod><imposto><vTotTrib>0.66<\/vTotTrib><ICMS><ICMS00><orig>0<\/orig><CST>00<\/CST><modBC>3<\/modBC><vBC>5.90<\/vBC><pICMS>12.00<\/pICMS><vICMS>0.71<\/vICMS><\/ICMS00><\/ICMS><IPI><cEnq>999<\/cEnq><IPINT><CST>53<\/CST><\/IPINT><\/IPI><PIS><PISNT><CST>06<\/CST><\/PISNT><\/PIS><COFINS><COFINSNT><CST>06<\/CST><\/COFINSNT><\/COFINS><ICMSUFDest><vBCUFDest>5.90<\/vBCUFDest><vBCFCPUFDest>5.90<\/vBCFCPUFDest><pFCPUFDest>0.00<\/pFCPUFDest><pICMSUFDest>17.50<\/pICMSUFDest><pICMSInter>12.00<\/pICMSInter><pICMSInterPart>100.00<\/pICMSInterPart><vFCPUFDest>0.00<\/vFCPUFDest><vICMSUFDest>0.32<\/vICMSUFDest><vICMSUFRemet>0.00<\/vICMSUFRemet><\/ICMSUFDest><\/imposto><\/det><total><ICMSTot><vBC>25.70<\/vBC><vICMS>3.09<\/vICMS><vICMSDeson>0.00<\/vICMSDeson><vFCPUFDest>0.00<\/vFCPUFDest><vICMSUFDest>1.40<\/vICMSUFDest><vICMSUFRemet>0.00<\/vICMSUFRemet><vFCP>0<\/vFCP><vBCST>0.00<\/vBCST><vST>0.00<\/vST><vFCPST>0<\/vFCPST><vFCPSTRet>0<\/vFCPSTRet><vProd>25.70<\/vProd><vFrete>0.00<\/vFrete><vSeg>0.00<\/vSeg><vDesc>0.00<\/vDesc><vII>0.00<\/vII><vIPI>0.00<\/vIPI><vIPIDevol>0.00<\/vIPIDevol><vPIS>0.00<\/vPIS><vCOFINS>0.00<\/vCOFINS><vOutro>0.00<\/vOutro><vNF>25.70<\/vNF><vTotTrib>4.29<\/vTotTrib><\/ICMSTot><retTrib\/><\/total><transp><modFrete>2<\/modFrete><transporta><CNPJ>10520136000186<\/CNPJ><xNome>CARRIERS LOGISTICA E TRANSPORTE LTDA<\/xNome><xEnder>Avenida Maria Coelho Aguiar<\/xEnder><xMun>Sao Paulo<\/xMun><UF>SP<\/UF><\/transporta><vol><qVol>1<\/qVol><pesoL>14.380<\/pesoL><pesoB>0.000<\/pesoB><\/vol><\/transp><pag><detPag><tPag>99<\/tPag><vPag>25.70<\/vPag><\/detPag><\/pag><infAdic><infCpl>Val Aprox Tributos R$ Fed: 2,00 (7,78%) Est: 2,29 (8,91%) Mun: 0,00 (0,00%) Fonte: IBPT Versao 20.2.C Valores totais do ICMS Interestadual: DIFAL da UF destino R$1,40 + FCP R$0,00; DIFAL da UF Origem R$0,00.  Numero do Pedido de Venda: 21962 Numero da Ordem De Compra: Lojas Americanas-281086673505 Celular: 51 984006507<\/infCpl><\/infAdic><\/infNFe><Signature xmlns=\"http:\/\/www.w3.org\/2000\/09\/xmldsig#\"><SignedInfo><CanonicalizationMethod Algorithm=\"http:\/\/www.w3.org\/TR\/2001\/REC-xml-c14n-20010315\"\/><SignatureMethod Algorithm=\"http:\/\/www.w3.org\/2000\/09\/xmldsig#rsa-sha1\"\/><Reference URI=\"#NFe35210119112842000163550210000000081487088558\"><Transforms><Transform Algorithm=\"http:\/\/www.w3.org\/2000\/09\/xmldsig#enveloped-signature\"\/><Transform Algorithm=\"http:\/\/www.w3.org\/TR\/2001\/REC-xml-c14n-20010315\"\/><\/Transforms><DigestMethod Algorithm=\"http:\/\/www.w3.org\/2000\/09\/xmldsig#sha1\"\/><DigestValue>q+78pPKX8pus\/O28J4mw+kpJKf4=<\/DigestValue><\/Reference><\/SignedInfo><SignatureValue>ogHNwzD8hyRSC8Jtxl5MbFQbflr8QVUUvlC2Q2bAS\/3sJIZB58mMcgnQN28qi2vwG3iyquEWgq0u4UnGRSpiuyZkpvjRcS3WdP+Hyg78T9eYV5GKo+ReLYUX8cpoQSkkw5E0PTIvOCetdPPxeoThL891DfgACjq0NmrigxNAkVbPvipCbO411wommBQTeo5eyvnEVKOFTeherkGUHwfvupHNnLoclHyKbPB+2QAfS\/5piz7MTBOsu0L5Nr5lmeyBIGKCb2jhWDWozStEf\/rSr+InCG86dbu\/Iga7gMPv6rQ6unxSB08JqWlU1m5iZ0EtlzdxYya+7gblMnv9kOQ7gA==<\/SignatureValue><KeyInfo><X509Data><X509Certificate>MIIIHjCCBgagAwIBAgIIb0RCBcaYXKIwDQYJKoZIhvcNAQELBQAwdTELMAkGA1UEBhMCQlIxEzARBgNVBAoMCklDUC1CcmFzaWwxNjA0BgNVBAsMLVNlY3JldGFyaWEgZGEgUmVjZWl0YSBGZWRlcmFsIGRvIEJyYXNpbCAtIFJGQjEZMBcGA1UEAwwQQUMgU0VSQVNBIFJGQiB2NTAeFw0yMDEwMTkxMzM2MDBaFw0yMTEwMTkxMzM2MDBaMIIBODELMAkGA1UEBhMCQlIxCzAJBgNVBAgMAlNQMRcwFQYDVQQHDA5FTUJVIERBUyBBUlRFUzETMBEGA1UECgwKSUNQLUJyYXNpbDEYMBYGA1UECwwPMDAwMDAxMDA5ODg2NDI4MTYwNAYDVQQLDC1TZWNyZXRhcmlhIGRhIFJlY2VpdGEgRmVkZXJhbCBkbyBCcmFzaWwgLSBSRkIxFjAUBgNVBAsMDVJGQiBlLUNOUEogQTExGTAXBgNVBAsMEEFDIFNFUkFTQSBSRkIgdjUxFzAVBgNVBAsMDjE0NjAyMjY5MDAwMTUyMRMwEQYDVQQLDApQUkVTRU5DSUFMMTswOQYDVQQDDDJUQVRJWCBDT01FUkNJTyBFIFBBUlRJQ0lQQUNPRVMgTFREQToxOTExMjg0MjAwMDE2MzCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALKMzOwvqmbF1a2O0DfFQtrjg8OR4\/R5whBWipnNJKUx2YIcCnV9PcV4DxcijeIhGU88x9chnm02b8nuE2Z2cZL39tSeToNb7OSYkNR1Qf9j6D4\/gHsiqSgdyJxTdWozDPfJz3f0HocyRXHdBhlf\/U3SCCLJLIhAR44uqXBvchF5o85NDG\/LtWu5ZeKjKcr49mUFMgzzk1uYbNUTfMdcHXxWF8DuSdtVLXGYDPSBVtsY4qQgQerp9GVI1mTQ5C4KehnkJjD5GEte43LuxTDLwDWCcLU5gM9TkSExp62WnqiEzMXSoBjP26N5LttT\/UMwTms\/cmhOpLQfeskY2YJAtDsCAwEAAaOCAuswggLnMAkGA1UdEwQCMAAwHwYDVR0jBBgwFoAU7PFBUVeo5jrpXrOgIvkIirU6h48wgZkGCCsGAQUFBwEBBIGMMIGJMEgGCCsGAQUFBzAChjxodHRwOi8vd3d3LmNlcnRpZmljYWRvZGlnaXRhbC5jb20uYnIvY2FkZWlhcy9zZXJhc2FyZmJ2NS5wN2IwPQYIKwYBBQUHMAGGMWh0dHA6Ly9vY3NwLmNlcnRpZmljYWRvZGlnaXRhbC5jb20uYnIvc2VyYXNhcmZidjUwgbsGA1UdEQSBszCBsIEZTFVJU0ZFUk5BTkRPQFRBVElYLkNPTS5CUqAlBgVgTAEDAqAcExpMVUlTIEZFUk5BTkRPIFNJTUFPIE1JTExFUqAZBgVgTAEDA6AQEw4xOTExMjg0MjAwMDE2M6A4BgVgTAEDBKAvEy0wNTA0MTk3MTAwNzA3NTI1NzU2MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDCgFwYFYEwBAwegDhMMMDAwMDAwMDAwMDAwMHEGA1UdIARqMGgwZgYGYEwBAgENMFwwWgYIKwYBBQUHAgEWTmh0dHA6Ly9wdWJsaWNhY2FvLmNlcnRpZmljYWRvZGlnaXRhbC5jb20uYnIvcmVwb3NpdG9yaW8vZHBjL2RlY2xhcmFjYW8tcmZiLnBkZjAdBgNVHSUEFjAUBggrBgEFBQcDAgYIKwYBBQUHAwQwgZ0GA1UdHwSBlTCBkjBKoEigRoZEaHR0cDovL3d3dy5jZXJ0aWZpY2Fkb2RpZ2l0YWwuY29tLmJyL3JlcG9zaXRvcmlvL2xjci9zZXJhc2FyZmJ2NS5jcmwwRKBCoECGPmh0dHA6Ly9sY3IuY2VydGlmaWNhZG9zLmNvbS5ici9yZXBvc2l0b3Jpby9sY3Ivc2VyYXNhcmZidjUuY3JsMB0GA1UdDgQWBBQNpn1ofa4lfOy0q4gq6CzCpAjejTAOBgNVHQ8BAf8EBAMCBeAwDQYJKoZIhvcNAQELBQADggIBAE5ejZfWjipvj3OuwdSq\/TephzsOtdJEi1AtHr1jGAiX7RTDXMI3keeZZ33JuawIkQuq0tnvYOreT7fkQGB1N11IXo+g91gvFGnGe+bmlWHP2y8baD\/HUrfjnSJqucX2cj\/SKGeuR0sQwekMSL3SBWdWm\/qKzwUPyL8x5RzJEAXi+vmLwM0ZNq2UpxoGciWFRy5xsbcBzwDS2UxC2E1jJuFpJhLowtOLIAyz1R9CdXCLzNTQhX9E9sXMuKZGGLrJiT3QTUFfJ73EuZAMaqkIFltl2SU2OuXxK\/gDBlXb\/FnoT75Vu3bX7X\/NhHGrXc9g4L9yPpHx87TlAxvNUc73x\/\/uWrHFkQZL7kOJeONp+YSeBM0leX1HodtOwk14h0xRY\/6pFKGuGJSkjjwfIV1vET041H0g7uTqM3dEa\/P39mAT0LcqyYJZ68sAMgTpQpf3eIYuB78RdsI3hNO75+2h+xjtoWz+sYGlahbgPzTeIvRKSqxaIO0K81cm3mS5JMK4iOkYwbKUMWrOfdF1PUDCOhB8ULoa4\/5wW1635rjhJxLEg3vua+KKS0k2tadz4ux3WtxSRZrrMff9k8i78\/29ok1le+da+TvdSIrkpaAUIAFOXEf7Zw7DCKr31wXlsQRX39DNFs4eBghnp3knI8BEjLiImkXH0v48o3p+oOtrOhDg<\/X509Certificate><\/X509Data><\/KeyInfo><\/Signature><\/NFe><protNFe versao=\"4.00\"><infProt><tpAmb>1<\/tpAmb><verAplic>SP_NFE_PL009_V4<\/verAplic><chNFe>35210119112842000163550210000000081487088558<\/chNFe>';
                    $objArrNfe .=' <dhRecbto>2021-01-06T14:06:56-03:00<\/dhRecbto><nProt>135210014670775<\/nProt><digVal>q+78pPKX8pus\/O28J4mw+kpJKf4=<\/digVal><cStat>100<\/cStat><xMotivo>Autorizado o uso da NF-e<\/xMotivo><\/infProt><\/protNFe><\/nfeProc>","xml_cancelamento_envio":"","xml_cancelamento":"","xml_cancelamento_completo":""}]';
                    $iNFCount++;

                    $arrNfe = json_decode($objArrNfe); */

                    foreach ($arrNfe as $nfe){
                        // Se for diferente de 6,7 e 8, ainda nao foi realmente
                        // faturado, essa verificaçao existe, pois pode existir
                        // uma nfe, mas ainda não foi autorizada na sefaz
                        // 1 - Pendente
                        // 2 - Emitida
                        // 3 - Cancelada
                        // 4 - Enviada - Aguardando recibo
                        // 5 - Rejeitada
                        // 6 - Autorizada
                        // 7 - Emitida DANFE
                        // 8 - Registrada
                        // 9 - Enviada - Aguardando protocolo
                        // 10 - Denegada
                        if ($nfe->situacao != 6 && $nfe->situacao != 7) {
                            // Ignora e continua, aguardando nfe ser autorizada

                            $arrDadosNfe = array(
                                // o que eu devo alimentar do retorno
                                'order_id' => $orderId,
                                'company_id' => $this->company,
                                'store_id' => $this->store,
                                'date_emission' => "{$nfe->dataEmissao} {$nfe->horaSaidaEntrada}:00",
                                'nfe_value' => $nfe->valorNota,
                                'nfe_serie' => $nfe->serie,
                                'nfe_num' => $nfe->numero,
                                'chave' => $nfe->chave_acesso,
                                'situacao' => $nfe->situacao
                            );

                            $msgError = "Pedido ainda não faturado. NF-e criada mas não autorizada. PEDIDO={$orderId}, RETORNO=" . json_encode($arrDadosNfe);
                            echo "{$msgError}\n";
                            $this->log_data('batch', $log_name, $msgError, "W");
                            $bolProblema = true;
                            continue;
                        }

                        // Dados para inserir a NF-e
                        $arrNfe = array(
                            // o que eu devo alimentar do retorno
                            'order_id' => $orderId,
                            'company_id' => $this->company,
                            'store_id' => $this->store,
                            'date_emission' => "{$nfe->dataEmissao} {$nfe->horaSaidaEntrada}:00",
                            'nfe_value' => $nfe->valorNota,
                            'nfe_serie' => $nfe->serie,
                            'nfe_num' => $nfe->numero,
                            'chave' => $nfe->chave_acesso
                        );


                        // Inserir NF-e
                        $insertNfe = $this->createNfe($arrNfe);

                        // Erro para iserir a NF-e
                        if (!$insertNfe) {
                            $msgError = "Não foi possível inserir dados de faturamento do pedido {$orderId}! DATA_NFE_INSERT=" . json_encode($arrNfe) . ", DATA_NFE_ECCOSYS=" . json_encode($dataNfe) . " RETORNO=" . json_encode($insertNfe);
                            echo "{$msgError}\n";
                            $this->log_data('batch', $log_name, $msgError, "W");
                            $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível atualizar dados de faturamento do pedido {$orderId}</h4>", "E");
                            $bolProblema = true;
                            continue;
                        }

                        // Remover pedido da fila
                        $this->removeOrderIntegration($idIntegration);

                        // Salvar XML
                        try {
                            $this->saveXML($idNfeOrder, $orderId);
                        } catch (\Exception $e) {
                            $msgError = "Não foi possível inserir dados Xml do pedido {$orderId}, idNfeOrder: {$idNfeOrder}!";
                            $this->log_data('batch', $log_name, "Erro para salvar o XML: {$e->getMessage()}", "W");
                            $bolProblema = true;
                        }

                        // volta pro formato de retorno JSON
                        $this->formatReturn = 'json';

                        $nfeDataEmissao = date('d/m/Y', strtotime($nfe->dataEmissao));
                        $this->log_integration("Pedido {$orderId} atualizado",
                        "<h4>Foi atualizado dados de faturamento do pedido {$orderId}</h4> 
                            <ul>
                                <li><strong>Chave:</strong> {$nfe->chave_acesso}</li>
                                <li><strong>Número:</strong> {$nfe->numero}</li>
                                <li><strong>Série:</strong> {$nfe->serie}</li>
                                <li><strong>Data de Emissão:</strong> {$nfeDataEmissao} {$nfe->horaSaidaEntrada}:00</li>
                                <li><strong>Valor:</strong> " . number_format($nfe->valorNota, 2, ',', '.') . "</li>
                            </ul>", "S");
                        $msgOk = "Pedido {$orderId} atualizado <h4>Foi atualizado dados de faturamento do pedido {$orderId}</h4> ";
                        $msgOk .= "<ul> ";
                        $msgOk .= "<li><strong>Chave:</strong> {$nfe->chave_acesso}</li> ";
                        $msgOk .= "<li><strong>Número:</strong> {$nfe->numero}</li> ";
                        $msgOk .= "<li><strong>Série:</strong> {$nfe->serie}</li> ";
                        $msgOk .= "<li><strong>Data de Emissão:</strong> {$nfeDataEmissao} {$nfe->horaSaidaEntrada}:00</li> ";
                        $msgOk .= "<li><strong>Valor:</strong> ".number_format($nfe->valorNota, 2, ',', '.')." </li> ";
                        $msgOk .= "</ul> ";
                        $this->log_data('batch', $log_name, $msgOk, "I");
                    
                        $status = $this->getStatusIntegration(52);
                        echo "Pedido {$orderId}, idNfeOrder: {$idNfeOrder} atualizado com sucesso!\n";
                        //$bolProblema = false;
                        $bolProblema = true; //coloco para não apresentar novamente a msg do log inegration
                        // ir para o próximo pedido;
                        continue;
                    }
                }
            } else {
                
                $statusRet =   $status["status"];
                $historicoRet = $status["historico"];

                $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
                $ECCOSYS_URL = '';

                if ($dataIntegrationStore) {
                    $credentials = json_decode($dataIntegrationStore['credentials']);
                    $ECCOSYS_URL = $credentials->url_eccosys;
                }

                //echo "\n paidStatus: ".$paidStatus. ' - $statusRet: '.$statusRet. ' - $historicoRet: '.$historicoRet;
                if ($paidStatus == 5) { //(enviado para o cliente)
                    $loja = $this->model_stores->getStoresData($this->store);
                    $logisticaProria = $loja['freight_seller'];

                    //echo '<br>L_387_logisticaProria: '.$logisticaProria;
                    if($logisticaProria == 0){
                        // Data envio do pedido integrado no conectala
                        $dataEnvio = $this->getDataEnvioOrderIdConectala($orderId);

                        $freights = $this->getDataPrevisaoEntregaOrderIdConectala($orderId)[0];
                        $codigoRastreio = $freights['codigo_rastreio'];

                        $data_array_pedido =  array(
                            'numeroPedido' => $orderId,
                            'codigoRastreamento' =>$codigoRastreio,
                            'dataEnvioLogistica' => $dataEnvio );

                        $url = $ECCOSYS_URL.'/api/pedidos';
                        $data =json_encode($data_array_pedido);
                        $dataStatus = json_decode(json_encode($this->sendREST($url, $data, 'PUT')));
                        $bolProblema = false;

                        if(!empty($dataStatus)){
                            if ($dataStatus->httpcode != 200) {
                                $msgError = "Não foi possível integrar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=".json_encode($dataStatus);
                                echo "{$msgError}\n";
                                $this->log_data('batch', $log_name, $msgError, "W");
        
                                if($dataStatus->httpcode != 400) {
                                    // formatar mensagens de erro para log integration
                                    $arrErrors = array();
                                    $errors = $dataStatus->retorno->erros;
                                    if (!is_array($errors)) $errors = (array)$errors;
                                    
                                    foreach ($errors as $error) {
                                        $msgErrorIntegration = $error->erro ?? "Erro desconhecido";
                                        array_push($arrErrors, $msgErrorIntegration);
                                    }
        
                                    $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                                    $bolProblema = true;
                                    continue;
                                }
                            } else {
                                $bolProblema = false;
                            }
                        }

                        if ($removeList) { // deve ser romivido da fila
                            // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
                            if ($orderCancel)
                                $this->removeAllOrderIntegration($orderId);
                            else
                                $this->removeOrderIntegration($idIntegration);
                        }
            
                        if($bolProblema == false){
                            if(isset($data)){
                                $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li> O status do pedido {$orderId}, foi atualizado para {$historicoRet}</li></ul>", "S");
                                $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=".json_encode($data) . ', recebido='.json_encode($dataStatus), "I");
                            }
                            
                            echo "Pedido {$orderId} atualizado com sucesso!\n";
                        }
                    } else {
                        $msgError = "\nChegou status={$paidStatus}, Pedido: {$orderId}, logisticaProria = {$logisticaProria}, não deve ser integrado, apenas remover da fila!  ORDER_INTEGRATION=".json_encode($orderIntegration);
                        echo "{$msgError}";
                        $this->removeOrderIntegration($idIntegration);
                        $this->log_data('batch', $log_name, "Remove da fila, é logistica própria, Pedido: {$orderId}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, status = {$status['status']}", "I");
                    }
                } elseif ($paidStatus == 6) { //(Pedido entregue ao cliente)
                    $loja = $this->model_stores->getStoresData($this->store);
                    $logisticaProria = $loja['freight_seller'];

                    if($logisticaProria == 0){
                        // Data entrega do pedido integrado no conectala
                        //$dataEntrega = $this->getDataEntregaOrderIdConectala($orderId);
                        $dataEntrega = $this->getDataEntregue($orderId);
                        $data_array_pedido =  array(
                            'numeroPedido' => $orderId,
                            'dataEntrega'=>$dataEntrega
                        );

                        $url = $ECCOSYS_URL.'/api/pedidos';
                        $data =json_encode($data_array_pedido);         
                        $dataStatus = json_decode(json_encode($this->sendREST($url, $data, 'PUT')));

                        //somente vai passar para Atendido quanto o caso for esse de Logistica não for própria.
                        $data_array =  array(
                            'situacao' => 1,
                            'historico'=>$historicoRet//$historico==null ? "" : $historico //'Confirmação de pagamento'
                        );

                        $url = $ECCOSYS_URL.'/api/pedidos/'. $orderId.'/situacao';
                        $data =json_encode($data_array);
                        $dataStatus = json_decode(json_encode($this->sendREST($url, $data, 'POST')));
                        $bolProblema = false;

                        if(!empty($dataStatus)){
                            if ($dataStatus->httpcode != 200) {
                                $msgError = "Não foi possível integrar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=".json_encode($dataStatus);
                                echo "{$msgError}\n";
                                $this->log_data('batch', $log_name, $msgError, "W");
        
                                if($dataStatus->httpcode != 400) {
                                    // formatar mensagens de erro para log integration
                                    $arrErrors = array();
                                    $errors = $dataStatus->retorno->erros;
                                    if (!is_array($errors)) $errors = (array)$errors;
                                    
                                    foreach ($errors as $error) {
                                        $msgErrorIntegration = $error->erro ?? "Erro desconhecido";
                                        array_push($arrErrors, $msgErrorIntegration);
                                    }
        
                                    $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                                    $bolProblema = true;
                                    continue;
                                }
                            } else {
                                $bolProblema = false;
                            }
                        }

                        if ($removeList) { // deve ser romivido da fila
                            // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
                            if ($orderCancel)
                                $this->removeAllOrderIntegration($orderId);
                            else
                                $this->removeOrderIntegration($idIntegration);
                        }
            
                        if($bolProblema == false){
                            if(isset($data)){
                                $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li> O status do pedido {$orderId}, foi atualizado para {$historicoRet}</li></ul>", "S");
                                $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=".json_encode($data) . ', recebido='.json_encode($dataStatus), "I");
                            }
                            
                            echo "Pedido {$orderId} atualizado com sucesso!\n";
                        }
                    } else {
                        $msgError = "\nChegou status={$paidStatus}, Pedido: {$orderId}, logisticaProria = {$logisticaProria}, não deve ser integrado, apenas remover da fila!  ORDER_INTEGRATION=".json_encode($orderIntegration);
                        echo "{$msgError}";
                        $this->removeOrderIntegration($idIntegration);
                        $this->log_data('batch', $log_name, "Remove da fila, é logistica própria, Pedido: {$orderId}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, status = {$status['status']}", "I");
                    }
                } else if ($paidStatus == 53) { //(Enviar dados de tracking)
                    $loja = $this->model_stores->getStoresData($this->store);
                    $logisticaProria = $loja['freight_seller'];

                    if($logisticaProria == 0){
                        // Data previsao de entrega do pedido integrado no conectala
                        $freights = $this->getDataPrevisaoEntregaOrderIdConectala($orderId)[0];
                        $codigoRastreio         = $freights['codigo_rastreio'];
                        $dataPrevisaoEntrega    = $freights['prazoprevisto'];
                        $transportadora         = $freights['ship_company'];
                        $data_array_pedido =  array(
                            'numeroPedido' => $orderId,
                            'codigoRastreamento' =>$codigoRastreio,
                            'dataPrevista'=>$dataPrevisaoEntrega,
                            'transportador' => $transportadora
                        );

                        $url = $ECCOSYS_URL.'/api/pedidos';
                        $data =json_encode($data_array_pedido);
                        $dataStatus = json_decode(json_encode($this->sendREST($url, $data, 'PUT')));
                        $bolProblema = false;

                        if(!empty($dataStatus)){
                            if ($dataStatus->httpcode != 200) {
                                $msgError = "Não foi possível integrar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=".json_encode($dataStatus);
                                echo "{$msgError}\n";
                                $this->log_data('batch', $log_name, $msgError, "W");
        
                                if($dataStatus->httpcode != 400) {
                                    // formatar mensagens de erro para log integration
                                    $arrErrors = array();
                                    $errors = $dataStatus->retorno->erros;
                                    if (!is_array($errors)) $errors = (array)$errors;
                                    
                                    foreach ($errors as $error) {
                                        $msgErrorIntegration = $error->erro ?? "Erro desconhecido";
                                        array_push($arrErrors, $msgErrorIntegration);
                                    }
        
                                    $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                                    $bolProblema = true;
                                    continue;
                                }
                            } else {
                                $bolProblema = false;
                            }
                        }

                        if ($removeList) { // deve ser romivido da fila
                            // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
                            if ($orderCancel)
                                $this->removeAllOrderIntegration($orderId);
                            else
                                $this->removeOrderIntegration($idIntegration);
                        }
            
                        if($bolProblema == false){
                            if(isset($data)){
                                $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li> O status do pedido {$orderId}, foi atualizado para {$historicoRet}</li></ul>", "S");
                                $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=".json_encode($data) . ', recebido='.json_encode($dataStatus), "I");
                            }
                            
                            echo "Pedido {$orderId} atualizado com sucesso!\n";
                        }
                    } else {
                        $msgError = "\nChegou status={$paidStatus}, Pedido: {$orderId}, logisticaProria = {$logisticaProria}, não deve ser integrado, apenas remover da fila!  ORDER_INTEGRATION=".json_encode($orderIntegration);
                        echo "{$msgError}";
                        $this->removeOrderIntegration($idIntegration);
                        $this->log_data('batch', $log_name, "Remove da fila, é logistica própria, Pedido: {$orderId}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, status = {$status['status']}", "I");
                    }
                } else if($paidStatus == 40){
                    $loja = $this->model_stores->getStoresData($this->store);
                    $logisticaProria = $loja['freight_seller'];

                    if($logisticaProria == 1){
                        // dados do pedido do Eccosys
                        $url = $ECCOSYS_URL.'/api/pedidos/'.$idEccosys;
                        $data = "";

                        $dataOrder = json_decode(json_encode($this->sendREST($url, $data)));

                        if ($dataOrder->httpcode != 200) {
                            $msgError = "Não foi possível localizar o pedido {$orderId}, idEccosys: {$idEccosys}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                            echo "{$msgError}\n";
                            $this->log_data('batch', $log_name, $msgError, "W");
                            $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados do pedido {$orderId}</h4>", "E");
                            $bolProblema = true;
                            continue;
                        }
                        
                        $arrObjDadosPedido = json_decode($dataOrder->content);
                        $objDadosPedido = $arrObjDadosPedido[0];

                        $numeroPedidoEccosys = $idEccosys;
                        $numeroPedido       = $orderId;
                        $codigoRastreamento = empty($objDadosPedido->codigoRastreamento) ? null : $objDadosPedido->codigoRastreamento;

                        //Veririca se houve algum envio de mercadoria, senão não irá fazer o bloco abaixo e passará para o próximo pedido.
                        $url = $ECCOSYS_URL.'/api/pedidos/'.$idEccosys.'/rastreamento';
                        $data = "";
                        $objDadosRastreamento = json_decode(json_encode($this->sendREST($url, $data)));

                        if ($objDadosRastreamento->httpcode != 200) {
                        $msgError = "Não foi possível conectar com o Rastreamento do Pedido {$orderId}, idEccosys: {$idEccosys}!  ORDER_INTEGRATION='Problema ao buscar informações Rastreamento do Pedido {$orderId}',  RETORNO='Problema ao buscar informações Rastreamento do Pedido {$orderId}'";
                        echo "{$msgError}\n";
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $bolProblema = true;
                            continue;
                        }
                        
                        $objDadosRastreio = json_decode($objDadosRastreamento->content);
                        
                        //teste diogo aa
/*                        $objDadosRastreio = new \StdClass;
                        $objDadosRastreio->urlRastreio = 'www.google.teste.com.br';
                        //$objDadosRastreio->urlRastreio = null;
                        $objDadosRastreio->numeroDaOrdemDeCompra = null;
                        $objDadosRastreio->numeroColeta = null;
                        //$objDadosRastreio->transportador = null;
                        $objDadosRastreio->transportador = 'Teste transporte L_660';
                        $objDadosRastreio->rastreio = 'AB123456789CD';  */

                        $msgInfEndPointRastreio = "Informações do rastreio do endpoint. Pedido {$orderId}, idEccosys: {$idEccosys}!";
                        $msgInfEndPointRastreio .= ' RETORNO= '.json_encode($objDadosRastreio);
                        $this->log_data('batch', $log_name, $msgInfEndPointRastreio, "I");

                        if(empty($objDadosRastreio->urlRastreio)){
                            //Se não tiver informações de Rastreio não irá fazer nada e irá ficar no log, senão irá buscar dados e colocar em suas respectivas tabelas order e freights
                            echo "Não foi possível buscar o rastreamento por não haver informações URL Rastreio. Pedido {$orderId}! \n";
                            //$msgError =  'Não foi possível buscar o rastreamento por não haver informações, Pedido: '.$orderId.' Dados rastreio, objDadosRastreio: '.json_encode($objDadosRastreio);
                            //$this->log_data('batch', $log_name, $msgError, "W");
                            $bolProblema = true;
                            continue;
                        }

                        $strUrlRastreio             = $objDadosRastreio->urlRastreio;
                        $numNumeroDaOrdemDeCompra   = $objDadosRastreio->numeroDaOrdemDeCompra;
                        $numNumeroColeta            = $objDadosRastreio->numeroColeta;
                        $strTransportador           = $objDadosRastreio->transportador;
                        $strRastreio                = $objDadosRastreio->rastreio;

                        //Necessário ter essas 3 informações preeenchidas para passar o rastreio. Confirmado com Pedro - 13/01/21 a 10:14hrs.
                        if(empty($strUrlRastreio) || empty($strRastreio) || empty($strTransportador)){
                            //Se não tiver informações de Rastreio não irá fazer nada e irá ficar no log, senão irá buscar dados e colocar em suas respectivas tabelas order e freights
                            echo "Não foi possível buscar o rastreamento por não haver informações. Pedido {$orderId}! \n";
                            $msgError =  'Não foi possível buscar o rastreamento por não haver informações, Pedido: '.$orderId.' Dados rastreio, objDadosRastreio: '.json_encode($objDadosRastreio);
                            $this->log_data('batch', $log_name, $msgError, "W");
                            $bolProblema = true;
                            continue;
                        } else {                    
                            $url_label_a4       = '';
                            $url_label_thermic  = '';
                            $url_label_zpl      = '';
                            $url_plp            = '';

                            $dadosProdutoItemPedido = $this->consultaItemPedidoProduto($numeroPedido)[0];

                            if($dadosProdutoItemPedido == false){
                                $msgError = "Não foi possível encontrar itens de Produto do Pedido {$orderId}, idEccosys: {$idEccosys}! 
                                ORDER_INTEGRATION='Problema ao buscar informações do Pedido {$orderId}', 
                                RETORNO='Problema ao buscar informações do Produto, do Pedido {$orderId}'";
                                //echo "{$msgError}\n";
                                $this->log_data('batch', $log_name, $msgError, "W");
                                $this->log_integration("Erro para encontrar itens de Produto do pedido {$orderId}", 
                                    "<h4>Não foi possível ao buscar informações itens do Produto do pedido {$orderId}</h4>", "E");
                                $bolProblema = true;
                                continue;
                            }

                            $product_id = $dadosProdutoItemPedido['product_id'];
                            $transportadora     = empty($strTransportador) ? $objDadosPedido->transportador : $strTransportador;
                            $dataEnvioLogistica = $objDadosPedido->dataEnvioLogistica;
                            $codigoRastreamento = empty($strRastreio) ? $codigoRastreamento : $strRastreio;
                            $data_etiqueta = empty($dataCodigoRastreamento) ? date('Y-m-d H:i:s') : $objDadosPedido->dataCodigoRastreamento;
                            //$frete    = empty($objDadosPedido->frete) ? '' : $objDadosPedido->frete; //não é o valor do Pedido, e sim do envio da mercadoria , pergutnado pro Lucas pelo Slack
                            $frete = '';
                            $idSevico           = empty($numNumeroColeta) ? '' : $numNumeroColeta;

                            if($objDadosPedido->dataPrevista != '0000-00-00' && !empty($objDadosPedido->dataPrevista)){
                                $dataPrevista = $objDadosPedido->dataPrevista;
                            } else {
                                $dataPrevista = '';
                            }
                    
                            if (!$product_id){
                                $msgError = "Não foi possível encontrar informações do produto {$product_id} do Pedido {$orderId}, idEccosys: {$idEccosys}! 
                                ORDER_INTEGRATION='Problema ao buscar informações do Produto {$product_id}', 
                                RETORNO='Problema ao buscar informações do Produto, do Pedido {$orderId}'";
                                //echo "{$msgError}\n";
                                $this->log_data('batch', $log_name, $msgError, "W");
                                $this->log_integration("Erro para ao buscar informações do Produto {$product_id} do pedido {$orderId}", 
                                    "<h4>Não foi possível ao buscar informações do Produto {$product_id} do pedido {$orderId}</h4>", "E");
                                $bolProblema = true;
                                continue;
                            }

                            $url = $ECCOSYS_URL.'/api/pedidos/'.$idEccosys.'/transportadora';
                            $data = "";
                            $dataTransportadora = json_decode(json_encode($this->sendREST($url, $data)));

                            if ($dataTransportadora->httpcode != 200) {
                                $msgError = "Não foi possível localizar Transportadora do pedido {$orderId}, idEccosys: {$idEccosys}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataTransportadora);
                                echo "{$msgError}\n";
                                $this->log_data('batch', $log_name, $msgError, "W");
                                $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar dados do pedido {$orderId}</h4>", "E");
                                $bolProblema = true;
                                continue;
                            } else {
                                if($dataTransportadora != null){
                                    $dataTransportadora = json_decode($dataTransportadora->content);
                                    $carrier_cnpj = empty($dataTransportadora->cnpj) ? '' : $dataTransportadora->cnpj;
                                    $bolProblema = false;
                                }
                            }

                            $sgp = 0; //transportadora
                            $solicitou_plp = 0;
                            $method = '';

                            //Se for enviado por correios, vai verificar se tiver plp, vai informar
                            if(strpos($transportadora, 'CORREIOS')){
                                $data15DiasAntes = date('Y-m-d', strtotime('-15 days'));
                                $data15DiasApos = date('Y-m-d', strtotime('+15 days'));
                                $url = $ECCOSYS_URL.'/api/lista-postagem?$fromDate='.$data15DiasAntes.'&$toDate='.$data15DiasApos;
                                $data = "";
                                $dadosListagemPostagem = json_decode(json_encode($this->sendREST($url, $data)));

                                if ($dadosListagemPostagem->httpcode != 200) {
                                    $msgError = "Não foi possível integrar a Listagem de Postagem do pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=".json_encode($dadosListagemPostagem);
                                    echo "{$msgError}\n";
                                    $this->log_data('batch', $log_name, $msgError, "W");
                                } else {
                                    $dadosArrayListagemPostagem = json_decode($dadosListagemPostagem->content);
                                    
                                    //Teste por não ter dados dos correios, necessário contrato - Diogo Teste
        /*                            $dadosTesteListagemPostagem = '[ {"id": "1234567", "numeroPLP": "2168426", "tipo": "E", "dataCriacao": "2020-12-07 15:23:46"},
                                                {"id": "1234568", "numeroPLP": "PD1001BR", "tipo": "E", "dataCriacao": "2020-12-15 13:31:02"}]';
                                    
                                    $dadosArrayListagemPostagem = json_decode($dadosTesteListagemPostagem); */

                                    foreach($dadosArrayListagemPostagem as $dadosListagemPostagem){
                                        $url = $ECCOSYS_URL.'/api/lista-postagem/'.$dadosListagemPostagem->id.'?detalhes=S';
                                        $data = "";
                                        $obterListaPostagem = json_decode(json_encode($this->sendREST($url, $data)));

                                        if ($obterListaPostagem->httpcode != 200) {
                                            $msgError = "Não foi possível Obter Lista de Postagem do pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=".json_encode($obterListaPostagem);
                                            echo "{$msgError}\n";
                                            $this->log_data('batch', $log_name, $msgError, "W");
                                            $bolProblema = true;
                                            continue;
                                        } else {
                                            $dadosObterListaPostagem = json_decode($obterListaPostagem->content);

                                            //dados ambiente de teste
        /*                                    $dadosObterListaPostagem = '[
                                                {
                                                "id":"1234568",
                                                "numeroPLP":"PD1001BR",
                                                "tipo":"N",
                                                "dataCriacao":"2020-12-15 13:31:46",
                                                "_Itens":[
                                                    [
                                                        {
                                                            "origem":"P",
                                                            "numero":"517002709",
                                                            "serie":"1",
                                                            "cep":"68.447-000",
                                                            "codigoRastreamento":"PD1001BR",
                                                            "possuiValorDeclarado":"S",
                                                            "valorDeclarado":"228.0000000000",
                                                            "volume":"1",
                                                            "maoPropria":"N",
                                                            "avisoRecebimento":"N",
                                                            "peso":"1.822",
                                                            "nomeDestinatario":"Cliente teste",
                                                            "tipoServico":"PAC",
                                                            "enderecoDestinatario":"Rua exemplo",
                                                            "enderecoNroDestinatario":"36",
                                                            "complementoDestinatario":"Quadra 56. lote 36",
                                                            "bairroDestinatario":"Centro",
                                                            "cidadeDestinatario":"Bento Gonçalves",
                                                            "ufDestinatario":"RS"
                                                        }
                                                    ]
                                                ]
                                                }
                                            ]';

                                            $dadosObterListaPostagem = json_decode($dadosObterListaPostagem); */


                                            foreach($dadosObterListaPostagem as $dadoObterListaPostagem){
                                                if($dadoObterListaPostagem->_Itens[0][0]->origem == 'P' &&
                                                $dadoObterListaPostagem->_Itens[0][0]->numero == $orderId){
                                                    $dadoObterListaPostagem->numeroPLP;
                                                    $codigoRastreamento = $dadoObterListaPostagem->_Itens[0][0]->codigoRastreamento;
                                                    $method = $dadoObterListaPostagem->_Itens[0][0]->tipoServico;
                                                    $idSevico = $dadoObterListaPostagem->id;
                                                    $sgp = 1; //correios
                                                    $solicitou_plp = 1;
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $this->load->model('model_freights');

                            $this->db->trans_begin();   // Inicia transação
                            
    //                            $VerDados =                         array(
                            $this->model_freights->create(array(
                                    'order_id'              => $numeroPedido,
                                    'item_id'               => $product_id,
                                    'company_id'            => $this->company,
                                    'ship_company'          => $transportadora,
                                    'status_ship'           => 0,
                                    'date_delivered'        => '',
                                    'ship_value'            => (float) $frete,
                                    'prazoprevisto'         => $dataPrevista,
                                    'idservico'             => $idSevico,  //$iten->service_id, verificar após informarem se tem em algum lugar
                                    'codigo_rastreio'       => $codigoRastreamento,
                                    'link_etiqueta_a4'      => !isset($url_label_a4) || empty($url_label_a4) ? null : $url_label_a4,
                                    'link_etiqueta_termica' => !isset($url_label_thermic) || empty($url_label_thermic) ? null : $url_label_thermic,
                                    'link_etiquetas_zpl'    => !isset($url_label_zpl) || empty($url_label_zpl) ? null : $url_label_zpl,
                                    'link_plp'              => !isset($url_plp) || empty($url_plp) ? null : $url_plp,
                                    'data_etiqueta'         => $data_etiqueta,
                                    'CNPJ'                  => !isset($carrier_cnpj) || empty($carrier_cnpj) ? null : $carrier_cnpj,
                                    'method'                => $method,
                                    'solicitou_plp'         => $solicitou_plp,
                                    'sgp'                   => $sgp,
                                    'url_tracking'          => $strUrlRastreio,
                                    'updated_date'          => date('Y-m-d H:i:s')
                                )
                            );

    /*echo 'VerDados: ';
    print_r($VerDados);
    die('Debug'); */
        
                            $statusRet = 51;
                            $historicoRet = 'Pedido enviado pela transportadora';
                            $data = $historicoRet;
                            $dataStatus = 200;

                            $this->load->model('model_orders');
                            $this->model_orders->updatePaidStatus($numeroPedido, 51);

                            if ($this->db->trans_status() === FALSE){
                                $bolProblema = true;
                                $this->db->trans_rollback();
                                return array('error' => true, 'data' => "Failure to communicate to the database");
                            }
                    
                            $this->db->trans_commit();
                            $bolProblema = false;
        
                            if ($removeList) { // deve ser romivido da fila
                                // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
                                if ($orderCancel)
                                    $this->removeAllOrderIntegration($orderId);
                                else
                                    $this->removeOrderIntegration($idIntegration);
                            }
                
                            if($bolProblema == false){
                                if(isset($data)){
                                    $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li> O status do pedido {$orderId}, foi atualizado para {$historicoRet}</li></ul>", "S");
                                    $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=".json_encode($data) . ', recebido='.json_encode($dataStatus), "I");
                                }
                                
                                echo "Pedido {$orderId} atualizado com sucesso!\n";
                            }
                        } //fim do bloco só irá entrar se houver infomrações de dados de Rastreio do produto.
                    } else {
                        $msgError = "\nChegou status={$paidStatus}, Pedido: {$orderId}, logisticaProria = {$logisticaProria}, não deve ser integrado, apenas remover da fila!  ORDER_INTEGRATION=".json_encode($orderIntegration);
                        echo "{$msgError}";
                        $this->removeOrderIntegration($idIntegration);
                        $this->log_data('batch', $log_name, "Remove da fila, não é logistica própria, Pedido: {$orderId}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, status = {$status['status']}", "I");
                    }
                } else if($paidStatus == 43){
                    $loja = $this->model_stores->getStoresData($this->store);
                    $logisticaProria = $loja['freight_seller'];

                    if($logisticaProria == 1){
                        // dados do pedido do Eccosys
                        $url = $ECCOSYS_URL.'/api/pedidos/'.$idEccosys;
                        $data = "";
    
                        $dataOrder = json_decode(json_encode($this->sendREST($url, $data)));
    
                        if ($dataOrder->httpcode != 200) {
                            $msgError = "Não foi possível localizar a Data Prevista do Pedido: {$orderId}, idEccosys: {$idEccosys}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                            echo "{$msgError}\n";
                            $this->log_data('batch', $log_name, $msgError, "W");
                            $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar a Data Prevista do pedido {$orderId}</h4>", "E");
                            $bolProblema = true;
                            continue;
                        }
                        
                        $arrObjDadosPedido = json_decode($dataOrder->content);
                        $objDadosPedido = $arrObjDadosPedido[0];
    
                        $dataEnvioLogistica = $objDadosPedido->dataEnvioLogistica;

                        //teste diogo 
                        //$dataEnvioLogistica = '2021-01-25 16:37:42';
                        if(empty($dataEnvioLogistica) || $dataEnvioLogistica == '0000-00-00 00:00:00'){
                            echo "\nNão existe data de envio para o transporte, Pedido {$orderId},  paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, dataEnvioLogistica = {$dataEnvioLogistica} \n";
                            $this->log_data('batch', $log_name, "Não existe data de envio para o transporte, Pedido {$orderId} paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, dataEnvioLogistica = {$dataEnvioLogistica}", "I");
                            continue;
                        }

                        // Inicia transação
                        $this->db->trans_begin();

                        $statusRet = 55;
                        $historicoRet = 'Pedido confirmado a data de envio pela transportadora';
                        $data = $historicoRet;
                        $dataStatus = 200;

                        $this->load->model('model_orders');
                        $this->model_orders->updateDataEnvioStatus55($orderId, $dataEnvioLogistica, $statusRet);

                        if ($this->db->trans_status() === FALSE){
                            $this->db->trans_rollback();
                            $bolProblema = true;
                            return array('error' => true, 'data' => "Failure to communicate to the database");
                        }
                
                        $this->db->trans_commit();
                        $bolProblema = false;

                        if ($removeList) { // deve ser romivido da fila
                            // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
                            if ($orderCancel)
                                $this->removeAllOrderIntegration($orderId);
                            else
                                $this->removeOrderIntegration($idIntegration);
                        }
            
                        if($bolProblema == false){
                            if(isset($data)){
                                $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li> O status do pedido {$orderId}, foi atualizado para {$historicoRet}</li></ul>", "S");
                                $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=".json_encode($data) . ', recebido='.json_encode($dataStatus), "I");
                            }
                            
                            echo "Pedido {$orderId} atualizado com sucesso!\n";
                        }
                    } else {
                        $msgError = "\nChegou status={$paidStatus}, Pedido: {$orderId}, logisticaProria = {$logisticaProria}, não deve ser integrado, apenas remover da fila!  ORDER_INTEGRATION=".json_encode($orderIntegration);
                        echo "{$msgError}";
                        $this->removeOrderIntegration($idIntegration);
                        $this->log_data('batch', $log_name, "Remove da fila, não é logistica própria, Pedido: {$orderId}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, status = {$status['status']}", "I");
                    }
                } else if($paidStatus == 45){
                    $loja = $this->model_stores->getStoresData($this->store);
                    $logisticaProria = $loja['freight_seller'];

                    if($logisticaProria == 1){
                        // dados do pedido do Eccosys
                        $url = $ECCOSYS_URL.'/api/pedidos/'.$idEccosys;
                        $data = "";

                        $dataOrder = json_decode(json_encode($this->sendREST($url, $data)));

                        if ($dataOrder->httpcode != 200) {
                            $msgError = "Não foi possível localizar a Data Prevista do Pedido: {$orderId}, idEccosys: {$idEccosys}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=" . json_encode($dataOrder);
                            echo "{$msgError}\n";
                            $this->log_data('batch', $log_name, $msgError, "W");
                            $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível localizar a Data Prevista do pedido {$orderId}</h4>", "E");
                            $bolProblema = true;
                            continue;
                        }
                        
                        $arrObjDadosPedido = json_decode($dataOrder->content);
                        $objDadosPedido = $arrObjDadosPedido[0];

                        $dataEntrega       = $objDadosPedido->dataEntrega;

                        //$dataEntrega = '2020-12-25 16:53:20';
                        if(empty($dataEntrega) || $dataEntrega == '0000-00-00 00:00:00'){
                            echo "\nNão existe data de entrega para o transporte, Pedido {$orderId}, dataEntrega = {$dataEntrega}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria} \n";
                            $this->log_data('batch', $log_name, "Não existe, dataEntrega, dados:  Pedido {$orderId}, dataEntrega = {$dataEntrega}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}", "W");
                            continue;
                        }

                        // Inicia transação
                        $this->db->trans_begin();

                        $statusRet = 60;
                        $historicoRet = 'Pedido confirmado a data de entrega pela transportadora';
                        $data = $historicoRet;
                        $dataStatus = 200;

                        $this->load->model('model_freights');
                        $this->model_freights->updateDataEntrega($orderId, array('date_delivered' => $dataEntrega,
                                                                                'updated_date' => date('Y-m-d H:i:s')));

                        $this->load->model('model_orders');
                        $this->model_orders->updateDataEntregaStatus60($orderId, $dataEntrega, $statusRet);

                        if ($this->db->trans_status() === FALSE){
                            $this->db->trans_rollback();
                            return array('error' => true, 'data' => "Failure to communicate to the database");
                        }
                
                        $this->db->trans_commit();
                        $bolProblema = false;

                        if ($removeList) { // deve ser romivido da fila
                            // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
                            if ($orderCancel)
                                $this->removeAllOrderIntegration($orderId);
                            else
                                $this->removeOrderIntegration($idIntegration);
                        }
            
                        if($bolProblema == false){
                            if(isset($data)){
                                $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li> O status do pedido {$orderId}, foi atualizado para {$historicoRet}</li></ul>", "S");
                                $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=".json_encode($data) . ', recebido='.json_encode($dataStatus), "I");
                            }
                            
                            echo "Pedido {$orderId} atualizado com sucesso!\n";
                        }
                    } else {
                        $msgError = "\nChegou status={$paidStatus}, Pedido: {$orderId}, logisticaProria = {$logisticaProria}, não deve ser integrado, apenas remover da fila!  ORDER_INTEGRATION=".json_encode($orderIntegration);
                        echo "{$msgError}";
                        $this->removeOrderIntegration($idIntegration);
                        $this->log_data('batch', $log_name, "Remove da fila, não é logistica própria, Pedido: {$orderId}, paidStatus = {$paidStatus}, logisticaProria = {$logisticaProria}, status = {$status['status']}", "I");
                    }
                } else {
                    if ($paidStatus == 2) {  //somente altera se for cancelado, senão não faz nenhuma atualização no Eccosys.
                        $data_array =  array(
                            'situacao' =>$statusRet,
                            'historico'=>$historicoRet//$historico==null ? "" : $historico //'Confirmação de pagamento'
                        );

                        $url = $ECCOSYS_URL.'/api/pedidos/'. $idEccosys.'/situacao';
                        $data =json_encode($data_array);
                        $dataStatus = json_decode(json_encode($this->sendREST($url, $data, 'POST')));
                        $bolProblema = false;

                        if(!empty($dataStatus)){
                            if ($dataStatus->httpcode != 200) {
                                $msgError = "Não foi possível integrar o pedido {$orderId}! ORDER_INTEGRATION=".json_encode($orderIntegration).", RETORNO=".json_encode($dataStatus);
                                echo "{$msgError}\n";
                                $this->log_data('batch', $log_name, $msgError, "W");
        
                                if($dataStatus->httpcode != 400) {
                                    // formatar mensagens de erro para log integration
                                    $arrErrors = array();
                                    $errors = $dataStatus->retorno->erros;
                                    if (!is_array($errors)) $errors = (array)$errors;
                                    
                                    foreach ($errors as $error) {
                                        $msgErrorIntegration = $error->erro ?? "Erro desconhecido";
                                        array_push($arrErrors, $msgErrorIntegration);
                                    }
        
                                    $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                                    $bolProblema = true;
                                    continue;
                                }
                            } else {
                                $bolProblema = false;
                            }
                        }
                            
            
                        if ($removeList) { // deve ser romivido da fila
                            // Verifica se precisa remover todos os registros da fila(Pedido Cancelado)
                            if ($orderCancel)
                                $this->removeAllOrderIntegration($orderId);
                            else
                                $this->removeOrderIntegration($idIntegration);
                        }
            
                        if($bolProblema == false){
                            if(isset($data)){
                                $this->log_integration("Pedido {$orderId} atualizado", "<h4>Status de pedido atualizado com sucesso</h4> <ul><li> O status do pedido {$orderId}, foi atualizado para {$historicoRet}</li></ul>", "S");
                                $this->log_data('batch', $log_name, "Pedido {$orderId} atualizado com sucesso! enviado=".json_encode($data) . ', recebido='.json_encode($dataStatus), "I");
                            }
                            
                            echo "Pedido {$orderId} atualizado com sucesso!\n";
                        }
                    } else {
                        //echo "\n L_1138_paidStatus: ".$paidStatus. " -orderId: ".$orderId;
                        $msgError = "\nChegou Pedido {$orderId}, status={$paidStatus}, não deve ser integrado!  ORDER_INTEGRATION=".json_encode($orderIntegration);
                        echo "{$msgError}";
                        $this->log_data('batch', $log_name, "L_1142_status nulo, Pedido: {$orderId}, paidStatus = {$paidStatus}, status = {$status['status']}", "I");
                    }
                }
            }
        }
    }

    /**
     * Recupera data do envio do pedido na Conectala
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna data de envio
     */
    private function getDataEnvioOrderIdConectala($orderId)
    {
        $order = $this->db
            ->get_where('orders',
                array(
                    'store_id'  => $this->store,
                    'id'        => $orderId,
                )
            )->row_array();

        if (!$order) return false;

        return $order['data_envio'];
    }

    /**
     * Recupera data do entrega do pedido na Conectala
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna data de entrega
     */
    private function getDataEntregaOrderIdConectala($orderId)
    {
        $order = $this->db
            ->get_where('orders',
                array(
                    'store_id'  => $this->store,
                    'id'        => $orderId,
                )
            )->row_array();

        if (!$order) return false;

        return $order['data_entrega'];
    }

     /**
     * Recupera data previsao da entrega do pedido na Conectala
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna data de previsao da entrega
     */
    private function getDataPrevisaoEntregaOrderIdConectala($orderId)
    {
        $freights = $this->db
            ->select(array('prazoprevisto',
                            'codigo_rastreio',
                            'ship_company'))
            ->from('freights')
            ->where(array(
                    'company_id'  => $this->company,
                    'order_id'    => $orderId,
            ))
            ->get()
            ->result_array();

        if (!$freights) return false;

        return $freights;
    }

    private function getDataEntregue($orderId)
    {
        $freights = $this->db
            ->get_where('freights',
                array(
                    'company_id'  => $this->company,
                    'order_id'    => $orderId,
                    )
            )->row_array();

        if (!$freights) return false;

        return $freights['date_delivered'];
    }

    /**
     * Recupera os pedidos para integração
     *
     * @return array Retorno os pedidos na fila para integrar
     */
    private function getOrdersForUpdate()
    {
        
        return $this->db
            ->from('orders_to_integration')
            ->where(
                array(
                    'store_id'  => $this->store,
                    'new_order' => 0
                )
            )
            ->order_by('id', 'asc')
            ->group_by("order_id")
            ->get()
            ->result_array();
    }

    public function getStatusIntegration($paidStatus){
        // Status do pedido
        switch ($paidStatus) {
            case 1:
                $historico = "Aguardando Pagamento(Não foi pago ainda)";
                $situacaoEccosys = null;  //-1;
                break;
            case 3:
                $historico = "Aguardando Faturamento"; //(Pedido foi pago, está aguardando ser faturado)
                $situacaoEccosys = 3;
                break;
            case 4:
                $historico = "Aguardando Coleta/Envio"; //(Aguardando o seller enviar ou transportadora coletar)
                $situacaoEccosys = null; //3(antes) Pronto para picking
                break;
            case 5:
                $historico = "Em Transporte"; //(Pedido já foi enviado ao cliente)
                $situacaoEccosys = 0; //1;
                break;
            case 6:
                $historico = "Entregue"; //(Pedido entregue ao cliente)
                $situacaoEccosys = 0; // 1; //'D', descontinuado o Entregue, agora vai ser Atendido com data de entrega.
                break;
            case 40:
                $historico = "Aguardando Rastreio"; //(Pedido faturado, aguardando envio de rastreio)
                $situacaoEccosys = 40; //manda dados do transporte, não existe esses status fica igual conectala.
                break;
            case 43:
                $historico = "Aguardando Coleta/Envio"; //(Pedido com rastreio, aguardando ser coletado/enviado)
                $situacaoEccosys = 43;
                break;
            case 45:
                $historico = "Em Transporte"; //(Pedido já foi enviado ao cliente)
                $situacaoEccosys = 45;  //41
                break;
            case 50:
                $historico = "Aguardando Seller Emitir Etiqueta"; //(Pedido faturado, contratando frete)
                $situacaoEccosys = null; //50;
                break;
            case 51:
                $historico = "PLP gerada"; //(Enviar rastreio para o marketplace)
                $situacaoEccosys = null; //51;
                break;
            case 52:
                $historico = "Pedido faturado"; //(Enviar NF-e para o marketplace)
                $situacaoEccosys = null; //1; deixa null -> para remover da fila, por não ter que fazer nada, só remover da fila.
                //$situacaoEccosys = 0;  //1; //faturou, gerou nota fiscal, vai deixar no ERP, como Atentido., somente Eccosys irá colocar como atendido
                break;
            case 53:
                $historico = "Aguardando Coleta/Envio"; //(Aguardando pedido ser postado/coletado para rastrear)
                $situacaoEccosys = 53;
                break;
            case 55:
                $historico = "Em Transporte"; //(Avisaro marketplace que o pedido foi enviado)
                $situacaoEccosys = null; //55; passa para 55, após status 43 que recuperou dados do pedido enviado.
                break;
            case 56:
                $historico = "Processando Nota Fiscal"; //(Processando NF aguardando envio (módulo faturador))
                $situacaoEccosys = null; //4;
                break;
            case 57:
                $historico = "Nota Fiscal Com Erro"; //(Problema para faturar o pedido (módulo faturador))
                $situacaoEccosys = null; //4;
                break;
            case 60:
                $historico = "Entregue"; //(Avisar ao marketplace que foi entregue)
                $situacaoEccosys = null; //'D'; passa para 60, após passar do status 45 de pedido entregue.
                break;
            case 96:
                $historico = ' status = 96'; //$historico = "Cancelado"; //(Cancelado antes de realizar o pagamento)
                $situacaoEccosys = null; //96;
                break;
            case 95:
            case 97:
                $historico = "Cancelado"; //(Cancelado após o pagamento)
                $situacaoEccosys = 2;
                break;
            case 98:
                $historico = "Cancelar na Transportadora"; //(Cancelar rastreio na transportadora (não correios))
                $situacaoEccosys = null; //2;
                break;
            case 99:
                $historico = "Cancelar no Marketplace"; //(Avisar o cancelamento para o marketplace)
                $situacaoEccosys = null; //2;
                break;
            case 101:
                $historico = "Sem Cotação de Frete"; //(Deve fazer a contratação do frete manual (não correios))
                $situacaoEccosys = null; //0;
                break;
            default:
                $historico = 'Não foi encontrato o status';
                $situacaoEccosys = null;
                break;
        }

        $arrRetorno = array('status' => $situacaoEccosys,
                            'historico' => $historico);

        return $arrRetorno;
    }

    /**
     * Recupera código do pedido na Eccosys
     *
     * @param   int         $orderId    Código do pedido
     * @return  int|bool                Retorna código do pedido na Eccosys
     */
    private function getOrderIdEccosys($orderId)
    {
        $order = $this->db
            ->get_where('orders',
                array(
                    'store_id'  => $this->store,
                    'id'        => $orderId,
                )
            )->row_array();

        if (!$order) return false;

        return $order['order_id_integration'];
    }

    /**
     * Remove o pedido da fila de integração
     *
     * @param   int     $id Código da integração
     * @return  bool        Retornar o status da exclusão
     */
    private function removeOrderIntegration($id)
    {
        return $this->db->delete(
            'orders_to_integration',
            array(
                'store_id'  => $this->store,
                'id'        => $id,
                'new_order' => 0
            )
        ) ? true : false;
    }

    /**
     * Remove todos os pedidos da fila de integração
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retornar o status da exclusão
     */
    private function removeAllOrderIntegration($orderId)
    {
        return $this->db->delete(
            'orders_to_integration',
            array(
                'store_id'  => $this->store,
                'order_id'  => $orderId
            )
        ) ? true : false;
    }

    /**
     * Cria dados de faturamento do pedido e atualiza o status do pedido para 52
     *
     * @param   array   $data   Dados da nfe para inserir
     * @return  bool            Retorna o status da criação
     */
    private function createNfe($data)
    {
        $sqlNfe     = $this->db->insert_string('nfes', $data);
        $insertNfe  = $this->db->query($sqlNfe) ? true : false;

        if (!$insertNfe) return false;
		
		//return $this->updateStatusForOrder($data['order_id'], 50, 3);    //FLUXO ANTIGO
        return $this->updateStatusForOrder($data['order_id'], 52, 3);
    }

    /**
     * Recupera se o pedido precisa ser cancelado
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se existe cancelamento
     */
    private function getOrderCancel($orderId)
    {
        $orderCancel = $this->db
            ->from('orders_to_integration')
            ->where(
                array(
                    'order_id'      => $orderId,
                    'store_id'      => $this->store
                )
            )->where_in('paid_status', array(95, 97))
            ->get()->row_array();

        if (!$orderCancel) return false;

        return true;
    }

    /**
     * Recupera se o pedido já foi integrado
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se o pedido já foi integrado
     */
    private function getIntegratedOrder($orderId)
    {
        $orderCreate = $this->db
            ->get_where('orders_to_integration',
                array(
                    'order_id'      => $orderId,
                    'store_id'      => $this->store,
                    'new_order'     => 1
                )
            )->row_array();

        return $orderCreate ? false : true;
    }

    /**
     * Recupera se o pedido já tem uma NF-e
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se o pedido tem NF-e
     */
    private function getOrderWithNfe($orderId)
    {
        return $this->db
            ->get_where('nfes',
                array(
                    'order_id'      => $orderId
                )
            )->num_rows() == 0 ? false : true;
    }

    /**
     * Atualiza status de um pedido
     *
     * @param   int     $orderId        Código do pedido
     * @param   int     $status         Código do status
     * @param   int     $verifyStatus   Código do status para verificação
     * @return  bool                    Retorna o status da atualização
     */
    private function updateStatusForOrder($orderId, $status, $verifyStatus = null)
    {
        $where = array(
            'id'        => $orderId,
            'store_id'  => $this->store,
        );
        if ($verifyStatus) $where['paid_status'] = $verifyStatus;

        return $this->db->where($where)->update('orders', array('paid_status' => $status)) ? true : false;
    }

    /**
     * Salva um arquivo XML da NFe
     *
     * @param   int     $idEccosys     Código da NFe na Eccosys
     * @param   int     $orderId    Código do pedido na Conecta Lá
     * @return  bool                Retorna o status da importação do xml
     */
    private function saveXML($idEccosys, $orderId)
    {
        $this->formatReturn = 'xml';
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $ECCOSYS_URL = '';
        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $ECCOSYS_URL = $credentials->url_eccosys;
        }
    
        $url = $ECCOSYS_URL.'/api/xml-nfes/'.$idEccosys;
        $data = "";
        $dataXml = json_decode(json_encode($this->sendREST($url, $data)));

        if ($dataXml->httpcode != 200) {
            $msgError = "XML da Nota Fiscal {$idEccosys} não foi encontrado ou não está autorizado.";
            echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "E");
            $this->log_integration("Erro para obter o XML do pedido {$orderId}", "<h4>Não foi possível obter o XML do pedido  {$orderId}</h4>", "E");
            return false;
        }

/*
        $dataXml = '<NFe xmlns="http://www.portalfiscal.inf.br/nfe">
        <infNFe Id="NFe35080599999090910270550010000000015180051273" versao="1.10">
        <ide> <cUF>35</cUF>
        <cNF>518005127</cNF><natOp>Venda a vista</natOp> <indPag>0</indPag>
        <mod>55</mod> <serie>1</serie> <nNF>1</nNF> <dEmi>2008-05-06</dEmi> <dSaiEnt>2008-05-06</dSaiEnt> <tpNF>0</tpNF>
        <cMunFG>3550308</cMunFG> <tpImp>1</tpImp> <tpEmis>1</tpEmis> <cDV>3</cDV> <tpAmb>2</tpAmb> <finNFe>1</finNFe> <procEmi>0</procEmi>
        <verProc>NF-eletronica.com</verProc> </ide> <emit> <CNPJ>99999090910270</CNPJ> <xNome>NF-e Associacao NF-e</xNome>
        <xFant>NF-e</xFant> <enderEmit> <xLgr>Rua Central</xLgr> <nro>100</nro> <xCpl>Fundos</xCpl>
        <xBairro>Distrito Industrial</xBairro> 
        <cMun>3502200</cMun>
        <xMun>Angatuba</xMun> <UF>SP</UF> <CEP>17100171</CEP> <cPais>1058</cPais> <xPais>Brasil</xPais>
        <fone>1733021717</fone> </enderEmit> <IE>123456789012</IE> </emit> <dest> <CNPJ>00000000000191</CNPJ>
        <xNome>DISTRIBUIDORA DE AGUAS MINERAIS</xNome> <enderDest> <xLgr>AV DAS FONTES</xLgr>
        <nro>1777</nro> <xCpl>10 ANDAR</xCpl> <xBairro>PARQUE FONTES</xBairro> <cMun>5030801</cMun>
        <xMun>Sao Paulo</xMun> <UF>SP</UF> <CEP>13950000</CEP> <cPais>1058</cPais>
        <xPais>BRASIL</xPais> <fone>1932011234</fone> </enderDest> 
        <IE> </IE> </dest> <retirada>
        <CNPJ>99171171000194</CNPJ> <xLgr>AV PAULISTA</xLgr> <nro>12345</nro> <xCpl>TERREO</xCpl>
        <xBairro>CERQUEIRA CESAR</xBairro>
        <cMun>3550308</cMun>
        <xMun>SAO PAULO</xMun>
        <UF>SP</UF>
        </retirada> <entrega> <CNPJ>99299299000194</CNPJ>  <xLgr>AV FARIA LIMA</xLgr> <nro>1500</nro> <xCpl>15 ANDAR</xCpl>
        <xBairro>PINHEIROS</xBairro> <cMun>3550308</cMun> <xMun>SAO PAULO</xMun> <UF>SP</UF>
        </entrega> <det nItem="1"> <prod> <cProd>00001</cProd>
        <cEAN></cEAN>
        <xProd>Agua Mineral</xProd>
        <CFOP>5101</CFOP> <uCom>dz</uCom> <qCom>1000000.0000</qCom> <vUnCom>1</vUnCom> <vProd>10000000.00</vProd>
        <cEANTrib></cEANTrib> <uTrib>und</uTrib> <qTrib>12000000.0000</qTrib> <vUnTrib>1</vUnTrib>
        </prod> <imposto> <ICMS> <ICMS00> <orig>0</orig> <CST>00</CST> <modBC>0</modBC> <vBC>10000000.00</vBC> <pICMS>18.00</pICMS>
        <vICMS>1800000.00</vICMS> </ICMS00> </ICMS> <PIS> <PISAliq> <CST>01</CST>
        <vBC>10000000.00</vBC> <pPIS>0.65</pPIS> <vPIS>65000</vPIS> </PISAliq>
        </PIS> <COFINS> <COFINSAliq> <CST>01</CST> <vBC>10000000.00</vBC> <pCOFINS>2.00</pCOFINS>
        <vCOFINS>200000.00</vCOFINS> </COFINSAliq> </COFINS> </imposto>
        </det> <det nItem="2"> <prod> <cProd>00002</cProd> <cEAN></cEAN>
        <xProd>Agua Mineral</xProd> <CFOP>5101</CFOP>
        <uCom>pack</uCom> <qCom>5000000.0000</qCom> <vUnCom>2</vUnCom> <vProd>10000000.00</vProd>
        <cEANTrib></cEANTrib> <uTrib>und</uTrib> <qTrib>3000000.0000</qTrib> <vUnTrib>0.3333</vUnTrib>
        </prod> <imposto> <ICMS> <ICMS00> <orig>0</orig> <CST>00</CST> <modBC>0</modBC>
        <vBC>10000000.00</vBC> <pICMS>18.00</pICMS> <vICMS>1800000.00</vICMS> </ICMS00>
        </ICMS> <PIS> <PISAliq> <CST>01</CST> <vBC>10000000.00</vBC> <pPIS>0.65</pPIS>
        <vPIS>65000</vPIS> </PISAliq> </PIS> <COFINS> <COFINSAliq>
        <CST>01</CST> <vBC>10000000.00</vBC> <pCOFINS>2.00</pCOFINS> <vCOFINS>200000.00</vCOFINS> </COFINSAliq>
        </COFINS> </imposto> </det> <total> <ICMSTot> <vBC>20000000.00</vBC> <vICMS>18.00</vICMS> <vBCST>0</vBCST>
        <vST>0</vST> <vProd>20000000.00</vProd> <vFrete>0</vFrete> <vSeg>0</vSeg>
        <vDesc>0</vDesc> <vII>0</vII> <vIPI>0</vIPI> <vPIS>130000.00</vPIS> <vCOFINS>400000.00</vCOFINS> <vOutro>0</vOutro>
        <vNF>20000000.00</vNF> </ICMSTot> </total> <transp> <modFrete>0</modFrete> <transporta> <CNPJ>99171171000191</CNPJ>
        <xNome>Distribuidora de Bebidas Fazenda de SP Ltda.</xNome>
        <IE>171999999119</IE> <xEnder>Rua Central 100 - Fundos - Distrito Industrial</xEnder>
        <xMun>SAO PAULO</xMun> <UF>SP</UF> </transporta> <veicTransp> <placa>BXI1717</placa> <UF>SP</UF>
        <RNTC>123456789</RNTC> </veicTransp> <reboque> <placa>BXI1818</placa> <UF>SP</UF>
        <RNTC>123456789</RNTC> </reboque> <vol> <qVol>10000</qVol>
        <esp>CAIXA</esp>
        <marca>LINDOYA</marca> <nVol>500</nVol> <pesoL>1000000000.000</pesoL> <pesoB>1200000000.000</pesoB>
        <lacres> <nLacre>XYZ10231486</nLacre> </lacres> </vol> </transp> <infAdic>
        <infAdFisco>Nota Fiscal de exemplo NF-eletronica.com</infAdFisco>
        </infAdic></infNFe>
        <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
        <SignedInfo>
        <CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></CanonicalizationMethod>
        <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod>
        <Reference URI="#NFe35080599999090910270550010000000015180051273">
        <Transforms>
        <Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"></Transform>
        <Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></Transform>
        </Transforms>
        <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></DigestMethod>
        <DigestValue>xhTSDMH61e9uqe04lnoHT4ZzLSY=</DigestValue>
        </Reference>
        </SignedInfo>
        <SignatureValue>Iz5Z3PLQbzZt9jnBtr6xsmHZMOu/3plXG9xxfFjRCQYGnD1rjlhzBGrqt026Ca2VHHM/bHNepi6FuFkAi595GScKVuHREUotzifE2OIjgavvTOrMwbXG7+0LYgkwPFiPCao2S33UpZe7MneaxcmKQGKQZw1fP8fsWmaQ4cczZT8=</SignatureValue>
        <KeyInfo> <X509Data>
        <X509Certificate>MIIEuzCCA6OgAwIBAgIDMTMxMA0GCSqGSIb3DQEBBQUAMIGSMQswCQYDVQQGEwJCUjELMAkGA1UECBMCUlMxFTATBgNVBAcTDFBvcnRvIEFsZWdyZTEdMBsGA1UEChMUVGVzdGUgUHJvamV0byBORmUgUlMxHTAbBgNVBAsTFFRlc3RlIFByb2pldG8gTkZlIFJTMSEwHwYDVQQDExhORmUgLSBBQyBJbnRlcm1lZGlhcmlhIDEwHhcNMDgwNDI4MDkwMTAyWhcNMDkwNDMwMjM1OTU5WjCBnjELMAkGA1UECBMCUlMxHTAbBgNVBAsTFFRlc3RlIFByb2pldG8gTkZlIFJTMR0wGwYDVQQKExRUZXN0ZSBQcm9qZXRvIE5GZSBSUzEVMBMGA1UEBxMMUE9SVE8gQUxFR1JFMQswCQYDVQQGEwJCUjEtMCsGA1UEAxMkTkZlIC0gQXNzb2NpYWNhbyBORi1lOjk5OTk5MDkwOTEwMjcwMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDh6RRv0bj4RYX+tDQrZRb5opa77LBVVs+6LphIfSF3TSWPfnKh0+xLlBFdmnB5YGgbbW9Uon6pZQTfaC8jZhRhI5eFRRofY/Ugoeo0NGt6PcIQNZQd6lLQ/ASd1qWwjqJoEa7udriKjy3h351Mf1bng1VxS1urqC3Dn39ZWIEwQIDAQABo4IBjjCCAYowIgYDVR0jAQEABBgwFoAUPT5TqhNWAm+ZpcVsvB7malDBjEQwDwYDVR0TAQH/BAUwAwEBADAPBgNVHQ8BAf8EBQMDAOAAMAwGA1UdIAEBAAQCMAAwgbwGA1UdEQEBAASBsTCBrqA4BgVgTAEDBKAvBC0wNzA4MTk1MTE1MTk0NTMxMDg3MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDCgHQYFYEwBAwKgFAQSRmVybmFuZG8gQ2FudG8gQWx0oBkGBWBMAQMDoBAEDjk5OTk5MDkwOTEwMjcwoBcGBWBMAQMHoA4EDDAwMDAwMDAwMDAwMIEfZmVybmFuZG8tYWx0QHByb2NlcmdzLnJzLmdvdi5icjAgBgNVHSUBAf8EFjAUBggrBgEFBQcDAgYIKwYBBQUHAwQwUwYDVR0fAQEABEkwRzBFoEOgQYY/aHR0cDovL25mZWNlcnRpZmljYWRvLnNlZmF6LnJzLmdvdi5ici9MQ1IvQUNJbnRlcm1lZGlhcmlhMzguY3JsMA0GCSqGSIb3DQEBBQUAA4IBAQCNPpaZ3Byu3/70nObXE8NiM53j1ddIFXsb+v2ghCVd4ffExv3hYc+/a3lfgV8H/WfQsdSCTzS2cHrd4Aasr/eXfclVDmf2hcWz+R7iysOHuT6B6r+DvV3JcMdJJCDdynR5REa+zViMnVZo1G3KuceQ7/y5X3WFNVq4kwHvonJ9oExsWyw8rTwUK5bsjz0A2yEwXkmkJIngnF41sP31+9jCImiqkXcmsesFhxzX7iurAQAQCZOm7iwMWxQKcAjXCZrgSZWRQy6mU224sX3HTArHahmLJ9Iw+WYAua5qBJsiN6PC7v5tfhrEQFpcG39yMnOecxvkkPolDUyBa7d7xwgm</X509Certificate>
        </X509Data>  </KeyInfo> </Signature>
        </NFe>';
*/
//        $dadosXml = json_decode($dataXml);
        $dadosXml = json_decode($dataXml->content);
        $dadosXml = $dadosXml->xmls[0]; 

        $xmlNfe     = simplexml_load_string($dadosXml);
        $jsonEncode = json_encode($xmlNfe);
        $arrayXml   = json_decode($jsonEncode,TRUE);

        $chNFe = $arrayXml['protNFe']['infProt']['chNFe'];

        $namePathStore = date('m-Y');

        $targetDir = 'assets/images/xml/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $targetDir .= $this->store.'/';
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

        $arquivo = fopen($targetDir . $chNFe . ".xml",'w');
//        $arquivo = fopen($targetDir . 'nfeTeste_01'. ".xml",'w');

        if ($arquivo == false) return false;

        fwrite($arquivo, $dadosXml);

        fclose($arquivo);

        return true;
    }


    /**
     * Recupera dados do item do pedido, informações do produto para transportadora
     *
     * @param   int         $orderId    Código do pedido
     * @return  dados_pedido            Retorna dados do intem do pedido para o envio do produto, transportadora
     */
    private function consultaItemPedidoProduto($orderId)
    {
        $itemsPedido = $this->db
            ->select('oi.id, oi.order_id, oi.product_id, oi.sku')
            ->from('orders_item oi')
            ->where(array(
                    'store_id'  => $this->store,
                    'order_id'  => $orderId,
            ))
            ->get()
            ->result_array();

        if (!$itemsPedido) return false;

        return $itemsPedido;
    }
}
