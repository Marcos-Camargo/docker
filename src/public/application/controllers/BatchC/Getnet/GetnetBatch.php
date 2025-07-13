<?php
/** @noinspection PhpUndefinedFieldInspection */

require APPPATH . "controllers/BatchC/GenericBatch.php";

/**
 * Class GetnetBatch
 */
class GetnetBatch extends GenericBatch
{

    /**
     * @var GetnetLibrary $integration
     */
    private $integration;

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

        //Models
        $this->load->model('model_gateway');
        $this->load->model('model_banks');
        $this->load->model('model_conciliation');
        $this->load->model('model_stores');
        $this->load->model('model_payment_gateway_store_logs');
        $this->load->model('model_payment');
        $this->load->model('model_gateway_settings');
        $this->load->model('model_settings');
        $this->load->model('model_transfer');
        $this->load->model('model_orders');

        //Libraries
        $this->load->library('Getnetlibrary');

        //Starting Pagar.me integration library
        $this->integration = new GetnetLibrary();

    }

    /**
     * @param null $id
     * @param null $params
     */
    public function getaccesstokens($id = null): void
    {
        if ($id) {
            $this->startJob(__FUNCTION__, $id);
        }
        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job getaccesstokens\n";
        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = "getaccesstokens";

        $response = $this->integration->getaccesstokensoob();

        if (!($response['httpcode'] == "200")) {  // created

            $responseContent = json_decode($response['content']);
            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $msg = "Erro ao gerar token na Getnet, Loja: " . 0
                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                "Resposta da Getnet: " . PHP_EOL
                . $responseContent . ' ' . PHP_EOL .
                'Dados Fornecidos: ' . PHP_EOL
                . '' . PHP_EOL;

            // $this->log_data('batch', $log_name, $msg, "E");

            $this->model_payment_gateway_store_logs->insertLog(
                0,
                $gatewayId,
                $msg
            );

            echo "[".date("Y-m-d H:i:s")."] - Erro ao gerar credencial oob\n";

        } else {

            $responseContent = json_decode($response['content'], true);

            $this->model_payment_gateway_store_logs->insertLog(
                0,
                $gatewayId,
                'Token oob gerado com sucesso: ' . $responseContent['access_token'],
                "W"
            );

            $this->model_gateway_settings->updateSettings('access_token_oob',$responseContent['access_token']);

            echo "[".date("Y-m-d H:i:s")."] - Credencial oob atualizada\n";

        }

        $response = array();

        $response = $this->integration->getaccesstokensmgm();

        if (!($response['httpcode'] == "200")) {  // created

            $responseContent = json_decode($response['content']);
            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $msg = "Erro ao gerar token na Getnet, Loja: " . 0
                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                "Resposta da Getnet: " . PHP_EOL
                . $responseContent . ' ' . PHP_EOL .
                'Dados Fornecidos: ' . PHP_EOL
                . '' . PHP_EOL;

            // $this->log_data('batch', $log_name, $msg, "E");

            $this->model_payment_gateway_store_logs->insertLog(
                0,
                $gatewayId,
                $msg
            );

            echo "[".date("Y-m-d H:i:s")."] - Erro ao gerar credencial mgm\n";

        } else {

            $responseContent = json_decode($response['content'], true);

            $this->model_payment_gateway_store_logs->insertLog(
                0,
                $gatewayId,
                'Token oob gerado com sucesso: ' . $responseContent['access_token'],
                "W"
            );

            $this->model_gateway_settings->updateSettings('access_token_mgm',$responseContent['access_token']);

            echo "[".date("Y-m-d H:i:s")."] - Credencial mgm atualizada\n";

        }

        $this->integration->recarregacredenciais();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job getaccesstokens\n";
        if($id){
        $this->endJob();
        }
    }

    public function runSyncStoresUpdated($id = null):void{

        $this->startJob(__FUNCTION__, $id);
        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job de atualização de lojas\n";
        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'runSyncStoresUpdated';

        $stores = $this->model_gateway->getStoresForUpdatesSubAccounts($gateway_name);

        if($stores){
            foreach ($stores as $key => $store) {
                echo "[".date("Y-m-d H:i:s")."] - Atualizando Loja ".$store['name']."\n";

                $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($store['id']);


                $response = $this->integration->updatesSubAccounts($store, $subaccount);

                if (!($response['httpcode'] == "200")) {  // created

                    $responseContent = json_decode($response['content']);
                    $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    $msg = "Erro ao atualizar loja na Getnet, Loja: " . $store['id']
                        . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                        "Resposta da Getnet: " . PHP_EOL
                        . $responseContent . ' ' . PHP_EOL . PHP_EOL .
                        "Payload enviado: " . PHP_EOL
                        . $response['payload_request'];

                    //  $this->log_data('batch', $log_name, $msg, "E");

                    $this->model_payment_gateway_store_logs->insertLog(
                        $store['id'],
                        $gatewayId,
                        $msg
                    );

                    echo "[".date("Y-m-d H:i:s")."] - Erro ao atualizar loja ".$store['id']."\n";

                } else {

                    $responseContent = json_decode($response['content']);
                    $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    $msg = "Loja atualizada com sucesso, Loja: " . $store['id']
                        . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                        "Resposta da Getnet: " . PHP_EOL
                        . $responseContent . ' ' . PHP_EOL . PHP_EOL .
                        "Payload enviado: " . PHP_EOL
                        . $response['payload_request'];

                    $this->model_payment_gateway_store_logs->insertLog(
                        $store['id'],
                        $gatewayId,
                        $msg,
                        Model_payment_gateway_store_logs::STATUS_SUCCESS
                    );

                    echo "[".date("Y-m-d H:i:s")."] - Loja ".$store['id']." atualizada com sucesso\n";

                }


            }
        }else{

            echo "[".date("Y-m-d H:i:s")."] - Nenhuma Loja a atualizar\n";

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";
        $this->endJob();
    }

    /**
     * @param null $id
     * @param null $params
     */
    public function runSyncStoresWithoutSubaccount($id = null, $params = null): void
    {
        $this->startJob(__FUNCTION__, $id, $params);
        $this->syncSubAccounts(true, $id, $params);
        $this->endJob();
    }

    public function callbacksubaccount($id = null){
        $this->getaccesstokens();

        $this->startJob(__FUNCTION__, $id);
        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job\n";
        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'callbacksubaccount';

        $stores = $this->model_stores->getStoresCallbackSubAccountsGetnet($gateway_name);

        if($stores){
            foreach ($stores as $key => $store) {
                echo "[".date("Y-m-d H:i:s")."] - Gerando callback na Loja ".$store['name']."\n";

                $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($store['id']);


                $response = $this->integration->callbacksubaccount($store, $subaccount);



                if (!($response['httpcode'] == "200")) {  // created

                    $responseContent = json_decode($response['content']);
                    $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    $msg = "Erro ao atualizar loja na Getnet, Loja: " . $store['id']
                        . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                        "Resposta da Getnet: " . PHP_EOL
                        . $responseContent . ' ' . PHP_EOL ;

                    //  $this->log_data('batch', $log_name, $msg, "E");

                    $this->model_payment_gateway_store_logs->insertLog(
                        $store['id'],
                        $gatewayId,
                        $msg
                    );

                    echo "[".date("Y-m-d H:i:s")."] - Erro ao atualizar loja ".$store['id']."\n";

                } else {

                    $responseContent = json_decode($response['content'], true);
                    echo "\n msg da loja ".$store['id'].":".$responseContent['status']."\n";
                    if($responseContent['status'] == "Aprovado Transacionar" ||
                        strtoupper(trim($responseContent['status'])) == strtoupper(trim("Aprovado Transacionar e Antecipar")) ||
                        $responseContent['status'] == "Este cadastro foi aprovado para antecipar." ||
                        $responseContent['status'] == "Este cadastro foi aprovado para antecipar" ||
                        strtoupper(trim($responseContent['status'])) == strtoupper(trim("Este cadastro foi aprovado para antecipar."))){

                        $msg = "Sucesso ao aprovar loja na Getnet, Loja: " . $store['id']
                            . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                            "Resposta da Getnet: " . PHP_EOL
                            . $responseContent['report']['summary'] . ' ' . PHP_EOL ;

                        $this->model_payment_gateway_store_logs->insertLog(
                            $store['id'],
                            $gatewayId,
                            $msg,
                            Model_payment_gateway_store_logs::STATUS_SUCCESS
                        );

                        $data = array(
                            "store_id" => $store['id'],
                            "gateway_account_id" => $responseContent['subseller_id'],
                            "gateway_id" => $gatewayId,
                            "bank_account_id" => 0
                        );

                        $this->model_gateway->createSubAccounts($data);

                        echo "[".date("Y-m-d H:i:s")."] - Loja ".$store['id']." atualizada com sucesso\n";

                    }else{

                        if($responseContent['status'] == "O cadastro foi rejeitado durante o enriquecimento"){

                            $msg = "Erro de aprovação loja na Getnet, Loja: " . $store['id']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da Getnet: " . PHP_EOL
                                . $responseContent['report']['summary'] . ' ' . PHP_EOL ;

                            // $this->log_data('batch', $log_name, $msg, "W");

                            $this->model_payment_gateway_store_logs->insertLog(
                                $store['id'],
                                $gatewayId,
                                $msg
                            );

                            echo "[".date("Y-m-d H:i:s")."] - Loja ".$store['id']." atualizada com sucesso\n";

                        }else{

                            $msg = "Aguardando aprovação loja na Getnet, Loja: " . $store['id']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da Getnet: " . PHP_EOL
                                . $responseContent['report']['summary'] . ' ' . PHP_EOL ;

                            //  $this->log_data('batch', $log_name, $msg, "W");

                            $this->model_payment_gateway_store_logs->insertLog(
                                $store['id'],
                                $gatewayId,
                                $msg,
                                "W"
                            );

                            echo "[".date("Y-m-d H:i:s")."] - Loja ".$store['id']." atualizada com sucesso\n";

                        }

                    }

                }

            }
        }else{

            echo "[".date("Y-m-d H:i:s")."] - Nenhuma Loja a rodar o callback\n";

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";
        $this->endJob();
    }

    public function geraajustes(){

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job\n";
        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'geraajustes';

        $stores = $this->model_gateway->getStoresForUpdatesSubAccounts($gateway_name);

        if($stores){
            foreach ($stores as $key => $store) {
                echo "[".date("Y-m-d H:i:s")."] - Atualizando Loja ".$store['name']."\n";

                $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($store['id']);


                $response = $this->integration->geraajustes($store, $subaccount);

                dd($response);
            }
        }else{

            echo "[".date("Y-m-d H:i:s")."] - Nenhuma Loja a atualizar\n";

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";

    }

    public function getdatasmesatualeanterior(){

        $ret = array();
        /* PREPARA DATAS */
        $anoAtual =  date("Y", time());
        $anoMes =  date("m", time());

        $dataInicioMesAtual = $anoAtual."-".$anoMes."-01";
        $dataFimMesAtual = date("Y-m-t", strtotime($dataInicioMesAtual));

        $ret['dataMesAtualInicioLoop'] = date_create($dataInicioMesAtual);
        $ret['dataMesAtualFimLoop'] = date_create($dataFimMesAtual);

        $ret['dataInicioMesAtual'] = $dataInicioMesAtual."T00:00:00Z";
        $ret['dataFimMesAtual'] = $dataFimMesAtual."T23:59:59Z";

        if($anoMes == "1" or $anoMes == "01"){
            $anoMes = 12;
            $anoAtual--;
        }else{
            $anoMes--;
            if($anoMes<10){
                $anoMes = "0".$anoMes;
            }
        }

        $dataMesAnterior = $anoAtual."-".$anoMes."-01";
        $dataFimMesAnterior = date("Y-m-t", strtotime($dataMesAnterior));

        $ret['dataMesAnteriorInicioLoop'] = date_create($dataMesAnterior);
        $ret['dataMesAnteriorFimLoop'] = date_create($dataFimMesAnterior);

        $ret['dataMesAnterior'] = $dataMesAnterior."T00:00:00Z";
        $ret['dataFimMesAnterior'] = $dataFimMesAnterior."T23:59:59Z";

        return $ret;

    }

    public function geraextratov2($id = null){
        $this->startJob(__FUNCTION__, $id);
        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Gera Extrato\n";

        $dateInicioJob = new DateTime();

        $this->getaccesstokens();

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'geraextratov2';

        echo "[".date("Y-m-d H:i:s")."] - Buscando extrato atualizado por dias\n";

        /* PREPARA DATAS */
        $datas = $this->getdatasmesatualeanterior();

        $this->db->trans_begin();

        for($i=1;$i<=2;$i++){

            if($i == "1"){
                //Tratando o mês atual
                echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês atual ".date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T00:00:00Z"." - ".date_format($datas['dataMesAtualFimLoop'], 'Y-m-d')."T23:59:59Z"."\n";
                for($j=1;$datas['dataMesAtualInicioLoop']<=$datas['dataMesAtualFimLoop'];date_add($datas['dataMesAtualInicioLoop'], date_interval_create_from_date_string('1 days'))){

                    $date = new DateTime();

                    $dataInicio = date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T00:00:00Z";
                    $dataFim = date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T23:59:59Z";
                    $saida = $this->geraextratopordatas($dataInicio,$dataFim);

                    $date2 = new DateTime();
                    $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                    echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";
                }

            }else{

                echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês atual ".date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T00:00:00Z"." - ".date_format($datas['dataMesAnteriorFimLoop'], 'Y-m-d')."T23:59:59Z"."\n";
                $dataInicio = "";
                $dataFim = "";
                for($j=1;$datas['dataMesAnteriorInicioLoop']<=$datas['dataMesAnteriorFimLoop'];date_add($datas['dataMesAnteriorInicioLoop'], date_interval_create_from_date_string('1 days'))){

                    $date = new DateTime();

                    $dataInicio = date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T00:00:00Z";
                    $dataFim = date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T23:59:59Z";
                    $saida = $this->geraextratopordatas($dataInicio,$dataFim);

                    $date2 = new DateTime();
                    $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                    echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";

                }

            }

        }

        $this->db->trans_commit();

        $dateFimJob = new DateTime();
        $diffInSecondsJobs = $dateFimJob->getTimestamp() - $dateInicioJob->getTimestamp();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job - Tempo de execução: ".$diffInSecondsJobs." segundo(s)\n";
        $this->endJob();
    }

    public function geraextratoliquidacaov2(){

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Gera Extrato\n";

        $dateInicioJob = new DateTime();

        $this->getaccesstokens();

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'geraextratov2';

        echo "[".date("Y-m-d H:i:s")."] - Buscando extrato atualizado por dias\n";

        /* PREPARA DATAS */
        $datas = $this->getdatasmesatualeanterior();

        $this->db->trans_begin();

        for($i=2;$i<=2;$i++){

            if($i == "1"){
                //Tratando o mês atual
                echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês atual ".date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T00:00:00Z"." - ".date_format($datas['dataMesAtualFimLoop'], 'Y-m-d')."T23:59:59Z"."\n";
                for($j=1;$datas['dataMesAtualInicioLoop']<=$datas['dataMesAtualFimLoop'];date_add($datas['dataMesAtualInicioLoop'], date_interval_create_from_date_string('1 days'))){

                    $date = new DateTime();

                    $dataInicio = date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T00:00:00Z";
                    $dataFim = date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T23:59:59Z";
                    $saida = $this->geraextratoliquidacaopordatas($dataInicio,$dataFim);

                    $date2 = new DateTime();
                    $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                    echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";
                }

            }else{

                echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês atual ".date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T00:00:00Z"." - ".date_format($datas['dataMesAnteriorFimLoop'], 'Y-m-d')."T23:59:59Z"."\n";
                $dataInicio = "";
                $dataFim = "";
                for($j=1;$datas['dataMesAnteriorInicioLoop']<=$datas['dataMesAnteriorFimLoop'];date_add($datas['dataMesAnteriorInicioLoop'], date_interval_create_from_date_string('1 days'))){

                    $date = new DateTime();

                    $dataInicio = date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T00:00:00Z";
                    $dataFim = date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T23:59:59Z";
                    $saida = $this->geraextratoliquidacaopordatas($dataInicio,$dataFim);

                    $date2 = new DateTime();
                    $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                    echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";

                }

            }

        }

        $this->db->trans_commit();

        $dateFimJob = new DateTime();
        $diffInSecondsJobs = $dateFimJob->getTimestamp() - $dateInicioJob->getTimestamp();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job - Tempo de execução: ".$diffInSecondsJobs." segundo(s)\n";

    }

    /*********/

    public function geraajustev2(){

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Gera Extrato\n";

        $dateInicioJob = new DateTime();

        $this->getaccesstokens();

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'geraextratov2';

        echo "[".date("Y-m-d H:i:s")."] - Buscando extrato atualizado por dias\n";

        /* PREPARA DATAS */
        $datas = $this->getdatasmesatualeanterior();

        $this->db->trans_begin();

        for($i=2;$i<=2;$i++){

            if($i == "1"){
                //Tratando o mês atual
                echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês atual ".date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T00:00:00Z"." - ".date_format($datas['dataMesAtualFimLoop'], 'Y-m-d')."T23:59:59Z"."\n";
                for($j=1;$datas['dataMesAtualInicioLoop']<=$datas['dataMesAtualFimLoop'];date_add($datas['dataMesAtualInicioLoop'], date_interval_create_from_date_string('1 days'))){

                    $date = new DateTime();

                    $dataInicio = date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T00:00:00Z";
                    $dataFim = date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T23:59:59Z";
                    $saida = $this->geraajustepordatas($dataInicio,$dataFim);

                    $date2 = new DateTime();
                    $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                    echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";
                }

            }else{

                echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês atual ".date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T00:00:00Z"." - ".date_format($datas['dataMesAnteriorFimLoop'], 'Y-m-d')."T23:59:59Z"."\n";
                $dataInicio = "";
                $dataFim = "";
                for($j=1;$datas['dataMesAnteriorInicioLoop']<=$datas['dataMesAnteriorFimLoop'];date_add($datas['dataMesAnteriorInicioLoop'], date_interval_create_from_date_string('1 days'))){

                    $date = new DateTime();

                    $dataInicio = date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T00:00:00Z";
                    $dataFim = date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T23:59:59Z";
                    $saida = $this->geraajustepordatas($dataInicio,$dataFim);

                    $date2 = new DateTime();
                    $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                    echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";

                }

            }

        }

        $this->db->trans_commit();

        $dateFimJob = new DateTime();
        $diffInSecondsJobs = $dateFimJob->getTimestamp() - $dateInicioJob->getTimestamp();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job - Tempo de execução: ".$diffInSecondsJobs." segundo(s)\n";

    }

    public function geraajusteliquidacaov2(){

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Gera Extrato\n";

        $dateInicioJob = new DateTime();

        $this->getaccesstokens();

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'geraextratov2';

        echo "[".date("Y-m-d H:i:s")."] - Buscando extrato atualizado por dias\n";

        /* PREPARA DATAS */
        $datas = $this->getdatasmesatualeanterior();

        $this->db->trans_begin();

        for($i=2;$i<=2;$i++){

            if($i == "1"){
                //Tratando o mês atual
                echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês atual ".date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T00:00:00Z"." - ".date_format($datas['dataMesAtualFimLoop'], 'Y-m-d')."T23:59:59Z"."\n";
                for($j=1;$datas['dataMesAtualInicioLoop']<=$datas['dataMesAtualFimLoop'];date_add($datas['dataMesAtualInicioLoop'], date_interval_create_from_date_string('1 days'))){

                    $date = new DateTime();

                    $dataInicio = date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T00:00:00Z";
                    $dataFim = date_format($datas['dataMesAtualInicioLoop'], 'Y-m-d')."T23:59:59Z";
                    $saida = $this->geraajusteliquidacaopordatas($dataInicio,$dataFim);

                    $date2 = new DateTime();
                    $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                    echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";
                }

            }else{

                echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês atual ".date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T00:00:00Z"." - ".date_format($datas['dataMesAnteriorFimLoop'], 'Y-m-d')."T23:59:59Z"."\n";
                $dataInicio = "";
                $dataFim = "";
                for($j=1;$datas['dataMesAnteriorInicioLoop']<=$datas['dataMesAnteriorFimLoop'];date_add($datas['dataMesAnteriorInicioLoop'], date_interval_create_from_date_string('1 days'))){

                    $date = new DateTime();

                    $dataInicio = date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T00:00:00Z";
                    $dataFim = date_format($datas['dataMesAnteriorInicioLoop'], 'Y-m-d')."T23:59:59Z";
                    $saida = $this->geraajusteliquidacaopordatas($dataInicio,$dataFim);

                    $date2 = new DateTime();
                    $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                    echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";

                }

            }

        }

        $this->db->trans_commit();

        $dateFimJob = new DateTime();
        $diffInSecondsJobs = $dateFimJob->getTimestamp() - $dateInicioJob->getTimestamp();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job - Tempo de execução: ".$diffInSecondsJobs." segundo(s)\n";

    }

    private function geraextratopordatas($dataInicio, $dataFim, $pageInicial = 1){

        echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim." para a página ".$pageInicial."\n";
        $response = array();

        $response = $this->integration->geraextratoajustePaginado($dataInicio,$dataFim,$pageInicial);
        $anoMes = str_replace("-","",substr($dataInicio,0,7));

        if (!($response['httpcode'] == "200")) {  // created

            $responseContent = json_decode($response['content']);
            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $msg = "Erro ao puxar extrato na getnet: "
                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                "Resposta da Getnet: " . PHP_EOL
                . $responseContent . ' ' . PHP_EOL ;

            echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
            print_r($response);

            echo "[".date("Y-m-d H:i:s")."] - Chamando de forma recursiva a função para a mesma data e página\n";
            $this->geraextratopordatas($dataInicio, $dataFim, $pageInicial);

        } else {

            $paginasTotais = json_decode($response['content'], true)['page_amount'];
            $responseContent = json_decode($response['content'], true);

            if($responseContent["list_transactions"]){
                foreach($responseContent['list_transactions'] as $saidaExtrato){

                    foreach($saidaExtrato['details'] as $saidaExtratoDetalhes){

                        $md5Chave = md5($saidaExtrato['summary']['order_id'].$saidaExtrato['summary']['marketplace_subsellerid'].$saidaExtratoDetalhes['item_id'].$saidaExtratoDetalhes['installment_amount'].$saidaExtratoDetalhes['subseller_rate_amount']);

                        $data = array(
                            "order_id_json" => $saidaExtrato['summary']['order_id'],
                            "seller_id_json" => $saidaExtratoDetalhes['subseller_id'],
                            "marketplace_subsellerid" => $saidaExtrato['summary']['marketplace_subsellerid'],
                            "item_id" => $saidaExtratoDetalhes['item_id'],
                            "installment_amount" => $saidaExtratoDetalhes['installment_amount'],
                            "payment_date" => $saidaExtratoDetalhes['payment_date'],
                            "json_retorno" => json_encode($saidaExtrato, JSON_UNESCAPED_UNICODE),
                            "release_status" => $saidaExtratoDetalhes['release_status'],
                            "subseller_rate_confirm_date" => $saidaExtratoDetalhes['subseller_rate_closing_date'],
                            "transaction_sign" => $saidaExtratoDetalhes['transaction_sign'],
                            "subseller_rate_amount" => $saidaExtratoDetalhes['subseller_rate_amount'],
                            "reference_number" => $saidaExtratoDetalhes['reference_number'],
                            "chave_md5" => $md5Chave,
                        );

                        $retorno = $this->model_gateway->saveextractgetnet($data);

                        if(!$retorno){
                            echo "[".date("Y-m-d H:i:s")."] - Item pedido Extrato atualizado com erro - ".$saidaExtrato['summary']['order_id']."\n";
                        }

                    }

                }
            }

            if($paginasTotais>1){

                for($paginaAtual = $pageInicial+1;$paginaAtual<=$paginasTotais;$paginaAtual++){

                    echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim." Pagina ".$paginaAtual." de ".$paginasTotais."\n";

                    $response = $this->integration->geraextratoajustePaginado($dataInicio,$dataFim,$paginaAtual);
                    $anoMes = str_replace("-","",substr($dataInicio,0,7));

                    if (!($response['httpcode'] == "200")) {  // created

                        $responseContent = json_decode($response['content']);
                        $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        $msg = "Erro ao puxar extrato na getnet: "
                            . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                            "Resposta da Getnet: " . PHP_EOL
                            . $responseContent . ' ' . PHP_EOL ;

                        echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
                        print_r($response);

                        echo "[".date("Y-m-d H:i:s")."] - Chamando de forma recursiva a função para a mesma data e página\n";
                        $this->geraextratopordatas($dataInicio, $dataFim, $paginaAtual);

                    } else {

                        $responseContent = json_decode($response['content'], true);

                        if($responseContent["list_transactions"]){
                            foreach($responseContent['list_transactions'] as $saidaExtrato){

                                foreach($saidaExtrato['details'] as $saidaExtratoDetalhes){

                                    $md5Chave = md5($saidaExtrato['summary']['order_id'].$saidaExtrato['summary']['marketplace_subsellerid'].$saidaExtratoDetalhes['item_id'].$saidaExtratoDetalhes['installment_amount'].$saidaExtratoDetalhes['transaction_sign'].$saidaExtratoDetalhes['subseller_rate_amount']);

                                    $data = array(
                                        "order_id_json" => $saidaExtrato['summary']['order_id'],
                                        "seller_id_json" => $saidaExtratoDetalhes['subseller_id'],
                                        "marketplace_subsellerid" => $saidaExtrato['summary']['marketplace_subsellerid'],
                                        "item_id" => $saidaExtratoDetalhes['item_id'],
                                        "installment_amount" => $saidaExtratoDetalhes['installment_amount'],
                                        "payment_date" => $saidaExtratoDetalhes['payment_date'],
                                        "json_retorno" => json_encode($saidaExtrato, JSON_UNESCAPED_UNICODE),
                                        "release_status" => $saidaExtratoDetalhes['release_status'],
                                        "subseller_rate_confirm_date" => $saidaExtratoDetalhes['subseller_rate_closing_date'],
                                        "transaction_sign" => $saidaExtratoDetalhes['transaction_sign'],
                                        "subseller_rate_amount" => $saidaExtratoDetalhes['subseller_rate_amount'],
                                        "reference_number" => $saidaExtratoDetalhes['reference_number'],
                                        "chave_md5" => $md5Chave,
                                    );

                                    $retorno = $this->model_gateway->saveextractgetnet($data);

                                    if(!$retorno){
                                        echo "[".date("Y-m-d H:i:s")."] - Item pedido Extrato atualizado com erro - ".$saidaExtrato['summary']['order_id']."\n";
                                    }

                                }

                            }
                        }

                    }

                }

            }

        }

    }

    private function geraextratoliquidacaopordatas($dataInicio, $dataFim){

        echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim."\n";
        $response = array();
        $response = $this->integration->geraextratoajuste2($dataInicio,$dataFim);
        $anoMes = str_replace("-","",substr($dataInicio,0,7));

        if (!($response['httpcode'] == "200")) {  // created

            $responseContent = json_decode($response['content']);
            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $msg = "Erro ao puxar extrato na getnet: "
                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                "Resposta da Getnet: " . PHP_EOL
                . $responseContent . ' ' . PHP_EOL ;

            // $this->log_data('batch', $log_name, $msg, "E");


            echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
            print_r($response);

        } else {

            $responseContent = json_decode($response['content'], true);

            foreach($responseContent['list_transactions'] as $saidaExtrato){

                foreach($saidaExtrato['details'] as $saidaExtratoDetalhes){

                    //$md5Chave = md5(json_encode($saidaExtrato, JSON_UNESCAPED_UNICODE).$saidaExtratoDetalhes['item_id'].$saidaExtratoDetalhes['subseller_rate_amount']);

                    $md5Chave = md5($saidaExtrato['summary']['order_id'].$saidaExtrato['summary']['marketplace_subsellerid'].$saidaExtratoDetalhes['item_id'].$saidaExtratoDetalhes['installment_amount'].$saidaExtratoDetalhes['transaction_sign'].$saidaExtratoDetalhes['subseller_rate_amount']);

                    $data = array(
                        "order_id_json" => $saidaExtrato['summary']['order_id'],
                        "seller_id_json" => $saidaExtratoDetalhes['subseller_id'],
                        "marketplace_subsellerid" => $saidaExtrato['summary']['marketplace_subsellerid'],
                        "item_id" => $saidaExtratoDetalhes['item_id'],
                        "installment_amount" => $saidaExtratoDetalhes['installment_amount'],
                        "payment_date" => $saidaExtratoDetalhes['payment_date'],
                        "json_retorno" => json_encode($saidaExtrato, JSON_UNESCAPED_UNICODE),
                        "release_status" => $saidaExtratoDetalhes['release_status'],
                        "subseller_rate_confirm_date" => $saidaExtratoDetalhes['subseller_rate_closing_date'],
                        "transaction_sign" => $saidaExtratoDetalhes['transaction_sign'],
                        "subseller_rate_amount" => $saidaExtratoDetalhes['subseller_rate_amount'],
                        "reference_number" => $saidaExtratoDetalhes['reference_number'],
                        "chave_md5" => $md5Chave,
                    );

                    $retorno = $this->model_gateway->saveextractgetnet2($data);

                    if(!$retorno){
                        echo "[".date("Y-m-d H:i:s")."] - Item pedido Extrato atualizado com erro - ".$saidaExtrato['summary']['order_id']."\n";
                    }

                }

            }

        }

    }

    /******/

    private function geraajustepordatas($dataInicio, $dataFim, $pageInicial = 1){

        echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim." para a página ".$pageInicial."\n";

        $response = $this->integration->geraextratoajustePaginado($dataInicio,$dataFim,$pageInicial);

        $anoMes = str_replace("-","",substr($dataInicio,0,7));

        if (!($response['httpcode'] == "200")) {  // created

            $responseContent = json_decode($response['content']);
            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $msg = "Erro ao puxar extrato na getnet: "
                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                "Resposta da Getnet: " . PHP_EOL
                . $responseContent . ' ' . PHP_EOL ;

            // $this->log_data('batch', $log_name, $msg, "E");


            echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
            print_r($response);
            echo "[".date("Y-m-d H:i:s")."] - Chamando de forma recursiva a função para a mesma data e página\n";
            $this->geraajustepordatas($dataInicio, $dataFim,$pageInicial);

        } else {

            $paginasTotais = json_decode($response['content'], true)['page_amount'];
            $responseContent = json_decode($response['content'], true);

            //Trata a primeira Pagina normal
            if($responseContent["adjustments"]){

                foreach($responseContent['adjustments'] as $saidaExtratoAjuste){

                    // $md5Chave = md5(json_encode($saidaExtratoAjuste, JSON_UNESCAPED_UNICODE));
                    //$md5Chave = md5($saidaExtratoAjuste['marketplace_schedule_id'].$saidaExtratoAjuste['adjustment_id'].$saidaExtratoAjuste['reference_number']);
                    $md5Chave = md5($saidaExtratoAjuste['marketplace_schedule_id'].$saidaExtratoAjuste['adjustment_id']);

                    $data = array(

                        "cpfcnpj_subseller" => $saidaExtratoAjuste['cpfcnpj_subseller'],
                        "adjustment_date" => $saidaExtratoAjuste['adjustment_date'],
                        "adjustment_reason" => $saidaExtratoAjuste['adjustment_reason'],
                        "adjustment_amount" => $saidaExtratoAjuste['adjustment_amount'],
                        "adjustment_type" => $saidaExtratoAjuste['adjustment_type'],
                        "marketplace_subsellerid" => $saidaExtratoAjuste['marketplace_subsellerid'],
                        "transaction_sign" => $saidaExtratoAjuste['transaction_sign'],
                        "payment_date" => $saidaExtratoAjuste['payment_date'],
//                         "ano_mes" => $saidaExtratoAjuste['marketplace_schedule_id'],
                        "subseller_id" => $saidaExtratoAjuste['subseller_id'],
                        "json_retorno" => json_encode($saidaExtratoAjuste, JSON_UNESCAPED_UNICODE),
                        "reference_number" => $saidaExtratoAjuste['reference_number'],
                        "chave_md5" => $md5Chave

                    );

                    $retorno = $this->model_gateway->saveextractgetnetajuste($data);

                    if(!$retorno){
                        echo "[".date("Y-m-d H:i:s")."] - Erro ao atualizar na base - ".$saidaExtratoAjuste['cpfcnpj_subseller']." - ".$saidaExtratoAjuste['transaction_sign'].$saidaExtratoAjuste['adjustment_amount']."\n";
                    }/*else{
                        echo "[".date("Y-m-d H:i:s")."] - Item ajuste Extrato atualizado com sucesso - ".$saidaExtratoAjuste['cpfcnpj_subseller']." - ".$saidaExtratoAjuste['transaction_sign'].$saidaExtratoAjuste['adjustment_amount']."\n";
                    }*/

                }

            }

            if($paginasTotais>1){
                //Se houver mais páginas entra no loop para executar todas
                for($paginaAtual = $pageInicial+1;$paginaAtual<=$paginasTotais;$paginaAtual++){

                    echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim." Pagina ".$paginaAtual." de ".$paginasTotais."\n";

                    $response = $this->integration->geraextratoajustePaginado($dataInicio,$dataFim,$paginaAtual);

                    $anoMes = str_replace("-","",substr($dataInicio,0,7));

                    if (!($response['httpcode'] == "200")) {  // created

                        $responseContent = json_decode($response['content']);
                        $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        $msg = "Erro ao puxar extrato na getnet: "
                            . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                            "Resposta da Getnet: " . PHP_EOL
                            . $responseContent . ' ' . PHP_EOL ;

                        echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
                        print_r($response);

                        echo "[".date("Y-m-d H:i:s")."] - Chamando de forma recursiva a função para a mesma data e página\n";
                        $this->geraajustepordatas($dataInicio, $dataFim,$paginaAtual);

                    } else {

                        $responseContent = json_decode($response['content'], true);

                        //Trata a primeira Pagina normal
                        if($responseContent["adjustments"]){

                            foreach($responseContent['adjustments'] as $saidaExtratoAjuste){

                               // $md5Chave = md5(json_encode($saidaExtratoAjuste, JSON_UNESCAPED_UNICODE));

                                //$md5Chave = md5($saidaExtratoAjuste['marketplace_schedule_id']);
                                //$md5Chave = md5($saidaExtratoAjuste['marketplace_schedule_id'].$saidaExtratoAjuste['adjustment_id'].$saidaExtratoAjuste['reference_number']);
                                $md5Chave = md5($saidaExtratoAjuste['marketplace_schedule_id'].$saidaExtratoAjuste['adjustment_id']);

                                $data = array(

                                    "cpfcnpj_subseller" => $saidaExtratoAjuste['cpfcnpj_subseller'],
                                    "adjustment_date" => $saidaExtratoAjuste['adjustment_date'],
                                    "adjustment_reason" => $saidaExtratoAjuste['adjustment_reason'],
                                    "adjustment_amount" => $saidaExtratoAjuste['adjustment_amount'],
                                    "adjustment_type" => $saidaExtratoAjuste['adjustment_type'],
                                    "marketplace_subsellerid" => $saidaExtratoAjuste['marketplace_subsellerid'],
                                    "transaction_sign" => $saidaExtratoAjuste['transaction_sign'],
                                    "payment_date" => $saidaExtratoAjuste['payment_date'],
//                                     "ano_mes" => $saidaExtratoAjuste['marketplace_schedule_id'],
                                    "subseller_id" => $saidaExtratoAjuste['subseller_id'],
                                    "json_retorno" => json_encode($saidaExtratoAjuste, JSON_UNESCAPED_UNICODE),
                                    "reference_number" => $saidaExtratoAjuste['reference_number'],
                                    "chave_md5" => $md5Chave

                                );

                                $retorno = $this->model_gateway->saveextractgetnetajuste($data);

                                if(!$retorno){
                                    echo "[".date("Y-m-d H:i:s")."] - Erro ao atualizar na base - ".$saidaExtratoAjuste['cpfcnpj_subseller']." - ".$saidaExtratoAjuste['transaction_sign'].$saidaExtratoAjuste['adjustment_amount']."\n";
                                }
                            }

                        }

                    }


                }
            }

        }

    }

    private function geraextratpagamentoopordatas($dataInicio, $dataFim, $pageInicial = 1){

        echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim." para a página ".$pageInicial."\n";
        $response = array();

        $response = $this->integration->geraextratoajustePaginado($dataInicio,$dataFim,$pageInicial);
        $anoMes = str_replace("-","",substr($dataInicio,0,7));

        if (!($response['httpcode'] == "200")) {  // created

            $responseContent = json_decode($response['content']);
            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $msg = "Erro ao puxar extrato na getnet: "
                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                "Resposta da Getnet: " . PHP_EOL
                . $responseContent . ' ' . PHP_EOL ;

            echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
            print_r($response);

            echo "[".date("Y-m-d H:i:s")."] - Chamando de forma recursiva a função para a mesma data e página\n";
            $this->geraextratpagamentoopordatas($dataInicio, $dataFim, $pageInicial);

        } else {

            $paginasTotais = json_decode($response['content'], true)['page_amount'];
            $responseContent = json_decode($response['content'], true);

            // função para a tabela de payment_summaries
            if($responseContent["payment_summaries"]){
                // chama função para salvar o payment
                $this->extractpaymentgetnet($responseContent["payment_summaries"]);
            }

            if($paginasTotais>1){

                for($paginaAtual = $pageInicial+1;$paginaAtual<=$paginasTotais;$paginaAtual++){

                    echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim." Pagina ".$paginaAtual." de ".$paginasTotais."\n";

                    $response = $this->integration->geraextratoajustePaginado($dataInicio,$dataFim,$paginaAtual);
                    $anoMes = str_replace("-","",substr($dataInicio,0,7));

                    if (!($response['httpcode'] == "200")) {  // created

                        $responseContent = json_decode($response['content']);
                        $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        $msg = "Erro ao puxar extrato na getnet: "
                            . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                            "Resposta da Getnet: " . PHP_EOL
                            . $responseContent . ' ' . PHP_EOL ;

                        echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
                        print_r($response);

                        echo "[".date("Y-m-d H:i:s")."] - Chamando de forma recursiva a função para a mesma data e página\n";
                        $this->geraextratpagamentoopordatas($dataInicio, $dataFim, $paginaAtual);

                    } else {

                        $responseContent = json_decode($response['content'], true);

                        // função para a tabela de payment_summaries
                        if($responseContent["payment_summaries"]){
                            // chama função para salvar o payment
                            $this->extractpaymentgetnet($responseContent["payment_summaries"]);
                        }

                    }

                }

            }

        }

    }

    private function geraajusteliquidacaopordatas($dataInicio, $dataFim){

        echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim."\n";
        $response = $this->integration->geraextratoajuste2($dataInicio,$dataFim);
        $anoMes = str_replace("-","",substr($dataInicio,0,7));

        if (!($response['httpcode'] == "200")) {  // created

            $responseContent = json_decode($response['content']);
            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $msg = "Erro ao puxar extrato na getnet: "
                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                "Resposta da Getnet: " . PHP_EOL
                . $responseContent . ' ' . PHP_EOL ;

            // $this->log_data('batch', $log_name, $msg, "E");


            echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
            print_r($response);

        } else {

            $responseContent = json_decode($response['content'], true);

            if($responseContent["adjustments"]){

                foreach($responseContent['adjustments'] as $saidaExtratoAjuste){

                    $md5Chave = md5(json_encode($saidaExtratoAjuste, JSON_UNESCAPED_UNICODE));

                    $data = array(

                        "cpfcnpj_subseller" => $saidaExtratoAjuste['cpfcnpj_subseller'],
                        "adjustment_date" => $saidaExtratoAjuste['adjustment_date'],
                        "adjustment_reason" => $saidaExtratoAjuste['adjustment_reason'],
                        "adjustment_amount" => $saidaExtratoAjuste['adjustment_amount'],
                        "adjustment_type" => $saidaExtratoAjuste['adjustment_type'],
                        "marketplace_subsellerid" => $saidaExtratoAjuste['marketplace_subsellerid'],
                        "transaction_sign" => $saidaExtratoAjuste['transaction_sign'],
                        "payment_date" => $saidaExtratoAjuste['payment_date'],
                        "json_retorno" => json_encode($saidaExtratoAjuste, JSON_UNESCAPED_UNICODE),
//                        "ano_mes" => $saidaExtratoAjuste['marketplace_schedule_id'],
                        "subseller_id" => $saidaExtratoAjuste['subseller_id'],
                        "reference_number" => $saidaExtratoDetalhes['reference_number'],
                        "chave_md5" => $md5Chave

                    );

                    $retorno = $this->model_gateway->saveextractgetnetajuste3($data);

                    if(!$retorno){
                        echo "[".date("Y-m-d H:i:s")."] - Erro ao atualizar na base - ".$saidaExtratoAjuste['cpfcnpj_subseller']." - ".$saidaExtratoAjuste['transaction_sign'].$saidaExtratoAjuste['adjustment_amount']."\n";
                    }/*else{
                        echo "[".date("Y-m-d H:i:s")."] - Item ajuste Extrato atualizado com sucesso - ".$saidaExtratoAjuste['cpfcnpj_subseller']." - ".$saidaExtratoAjuste['transaction_sign'].$saidaExtratoAjuste['adjustment_amount']."\n";
                    }*/

                }

            }

        }

    }

    /***** */

    private function geraextratomdrpordatas($dataInicio, $dataFim){

        echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim."\n";
        $response = array();
        $response = $this->integration->geraextratoajuste3($dataInicio,$dataFim);
        $anoMes = str_replace("-","",substr($dataInicio,0,7));

        if (!($response['httpcode'] == "200")) {  // created

            $responseContent = json_decode($response['content']);
            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $msg = "Erro ao puxar extrato na getnet: "
                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                "Resposta da Getnet: " . PHP_EOL
                . $responseContent . ' ' . PHP_EOL ;

            // $this->log_data('batch', $log_name, $msg, "E");


            echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato mdr\n";

        } else {

            $responseContent = json_decode($response['content'], true);

            if($responseContent['commissions'] && $responseContent['list_transactions']){

                foreach($responseContent['list_transactions'] as $saidaPedido){
                    $dadosMDRPedido = $this->model_gateway->insertmdrpedidopayment($saidaPedido['summary']['order_id'],strtoupper(str_replace("-","",$saidaPedido['summary']['payment_id'])));
                }

                foreach($responseContent['commissions'] as $saidaExtrato){
                    $dadosMDR = $this->model_gateway->insertmdrpayment(round($saidaExtrato['mdr_rate_ammount']/100,2),strtoupper(str_replace("-","",$saidaExtrato['payment_id'])));
                }

            }
        }

    }

    public function geraextrato(){

        /* PREPARA DATAS */
        $anoAtual =  date("Y", time());
        $anoMes =  date("m", time());

        $dataInicioMesAtual = $anoAtual."-".$anoMes."-01";
        $dataFimMesAtual = date("Y-m-t", strtotime($dataInicioMesAtual));

        $dataInicioMesAtual = $dataInicioMesAtual."T00:00:00Z";
        $dataFimMesAtual = $dataFimMesAtual."T23:59:59Z";

        if($anoMes == "1" or $anoMes == "01"){
            $anoMes = 12;
            $anoAtual--;
        }else{
            $anoMes--;
        }

        $dataMesAnterior = $anoAtual."-".$anoMes."-01";
        $dataFimMesAnterior = date("Y-m-t", strtotime($dataMesAnterior));

        $dataMesAnterior = $dataMesAnterior."T00:00:00Z";
        $dataFimMesAnterior = $dataFimMesAnterior."T23:59:59Z";


        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";

    }

    public function geraextratoajuste(){

        /* PREPARA DATAS */
        $anoAtual =  date("Y", time());
        $anoMes =  date("m", time());

        $dataInicioMesAtual = $anoAtual."-".$anoMes."-01";
        $dataFimMesAtual = date("Y-m-t", strtotime($dataInicioMesAtual));

        $dataInicioMesAtual = $dataInicioMesAtual."T00:00:00Z";
        $dataFimMesAtual = $dataFimMesAtual."T23:59:59Z";

        if($anoMes == "1" or $anoMes == "01"){
            $anoMes = 12;
            $anoAtual--;
        }else{
            $anoMes--;
        }

        $dataMesAnterior = $anoAtual."-".$anoMes."-01";
        $dataFimMesAnterior = date("Y-m-t", strtotime($dataMesAnterior));

        $dataMesAnterior = $dataMesAnterior."T00:00:00Z";
        $dataFimMesAnterior = $dataFimMesAnterior."T23:59:59Z";

        $this->getaccesstokens();

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job\n";
        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'geraextrato';

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";

    }

    public function geramdr($id = null){
       $this->startJob(__FUNCTION__, $id);
        $dateInicioJob = new DateTime();
        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job geramdrv3\n";
        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'geramdrv3';
        $this->getaccesstokens();

        echo "[".date("Y-m-d H:i:s")."] - Buscando pedidos com o MDR nulo\n";

        //  $this->db->trans_begin();

        $pedidosSemMDR = $this->model_payment->buscapedidossemmdr();

        $contador = 0;

        foreach($pedidosSemMDR as $pedido){

            $date = new DateTime();

            //Tratando o mês anterior
            echo "[".date("Y-m-d H:i:s")."] - Buscando dados do pedido ".$pedido['id']." - ".$pedido['numero_marketplace']."\n";
            $dataInicio = "";
            $dataFim = "";

            $response = $this->integration->geraextratoporpedido($pedido['numero_marketplace_api']);
            if (!($response['httpcode'] == "200")) {  // created

              /*  $responseContent = json_decode($response['content']);
                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                $msg = "Erro ao puxar extrato na getnet: "
                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                    "Resposta da Getnet: " . PHP_EOL
                    . $responseContent . ' ' . PHP_EOL ;

                $this->log_data('batch', $log_name, $msg, "E");


                echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato mdr do pedido ".$pedido['id']." - ".$pedido['numero_marketplace']."\n".$responseContent."\n"; */

                echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato mdr do pedido ".$pedido['id']." - ".$pedido['numero_marketplace']."\n".$responseContent."\n";
                echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." não encontrado na base da Getnet\n";

                //FIN-722 ->neste bloco eu preciso inserir os codigos que dao update na orders_payment com o mdr da tabela
                $default_mdr = $this->integration->getDefaultMDR($pedido['id']);
                $order_mdr_default = $this->model_payment->updatemdrorderspayment($pedido['id'], $default_mdr);
                if($order_mdr_default){
                    echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso MDR ".$default_mdr."\n";
                }else{
                    echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." não atualizado MDR ".$default_mdr."\n";
                }

            } else {

                $responseContent = json_decode($response['content'], true);
                
                if($responseContent['list_transactions']){
                    if($responseContent['commissions']){

                        foreach($responseContent['commissions'] as $saidaExtrato){

                            $pedidosSemMDR = $this->model_payment->updatemdrorderspayment($pedido['id'],round($saidaExtrato['mdr_rate_ammount']/100,2));
                            if($pedidosSemMDR){
                                echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso MDR ".round($saidaExtrato['mdr_rate_ammount']/100,2)."\n";
                            }else{
                                echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com erro MDR ".round($saidaExtrato['mdr_rate_ammount']/100,2)."\n";
                            }

                        }
                    }else{

                        echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." encontrado mas sem informação de Comissions\n";
                        $default_mdr = $this->integration->getDefaultMDR($pedido['id']);
                        $pedidosSemMDR = $this->model_payment->updatemdrorderspayment($pedido['id'], $default_mdr);
                        // $pedidosSemMDR = $this->model_payment->updatemdrorderspayment($pedido['id'],'0.00');
                        if($pedidosSemMDR){
                            echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso para  ".$default_mdr."\n";
                        }else{
                            echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." não atualizado para  ".$default_mdr."\n";
                        }

                    }
		}else{
			if($pedido['transaction_id'] == null){
			 $pedido['transaction_id'] = 'semidtransaction';
			}
                    $response2 = $this->integration->geraextratoporpedido($pedido['transaction_id']);

                    if (!($response2['httpcode'] == "200")) {  // created

                        /*$responseContent = json_decode($response2['content']);
                        $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        $msg = "Erro ao puxar extrato na getnet: "
                            . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                            "Resposta da Getnet: " . PHP_EOL
                            . $responseContent . ' ' . PHP_EOL ;

                        $this->log_data('batch', $log_name, $msg, "E");

                        echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato mdr do pedido ".$pedido['id']." - ".$pedido['numero_marketplace']."\n".$responseContent."\n";*/

                        echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato mdr do pedido ".$pedido['id']." - ".$pedido['numero_marketplace']."\n".$responseContent."\n";
                        echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." não encontrado na base da Getnet\n";

                        //FIN-722 ->neste bloco eu preciso inserir os codigos que dao update na orders_payment com o mdr da tabela
                        $default_mdr = $this->integration->getDefaultMDR($pedido['id']);
                        $order_mdr_default = $this->model_payment->updatemdrorderspayment($pedido['id'], $default_mdr);
                        if($order_mdr_default){
                            echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso MDR ".$default_mdr."\n";
                        }else{
                            echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." não atualizado MDR ".$default_mdr."\n";
                        }
                        

                    } else {

                        $responseContent = json_decode($response2['content'], true);

                        if($responseContent['list_transactions']){

                            if($responseContent['commissions']){

                                foreach($responseContent['commissions'] as $saidaExtrato){

                                    $pedidosSemMDR = $this->model_payment->updatemdrorderspayment($pedido['id'],round($saidaExtrato['mdr_rate_ammount']/100,2));
                                    if($pedidosSemMDR){
                                        echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso MDR ".round($saidaExtrato['mdr_rate_ammount']/100,2)."\n";
                                    }else{
                                        echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." não atualizado MDR ".round($saidaExtrato['mdr_rate_ammount']/100,2)."\n";
                                    }
                                }

                            }else{
                                echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." encontrado mas sem informação de Comissions\n";
                                $default_mdr = $this->integration->getDefaultMDR($pedido['id']);
                                $pedidosSemMDR = $this->model_payment->updatemdrorderspayment($pedido['id'], $default_mdr);
                                // $pedidosSemMDR = $this->model_payment->updatemdrorderspayment($pedido['id'],'0.00');
                                if($pedidosSemMDR){
                                    echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso para ".$default_mdr."\n";
                                }else{
                                    echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." não atualizado para ".$default_mdr."\n";
                                }
                            }

                        }else{
                            
                            echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." não encontrado na base da Getnet\n";

                            //FIN-722 ->neste bloco eu preciso inserir os codigos que dao update na orders_payment com o mdr da tabela
                            $default_mdr = $this->integration->getDefaultMDR($pedido['id']);
                            $order_mdr_default = $this->model_payment->updatemdrorderspayment($pedido['id'], $default_mdr);
                            if($order_mdr_default){
                                echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso MDR ".$default_mdr."\n";
                            }else{
			                    echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." não atualizado MDR ".$default_mdr."\n";
                            }
                        }

                    }

                }

            }

            $contador++;

            if($contador == "200"){
                $contador = 0;
                $this->getaccesstokens();
            }

            $date2 = new DateTime();
            $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();
            echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." tratado em ".$diffInSeconds." segundo(s) \n";

        }

        // $this->db->trans_commit();

        $dateFimJob = new DateTime();
        $diffInSecondsJobs = $dateFimJob->getTimestamp() - $dateInicioJob->getTimestamp();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job geramdrv3 - Tempo de execução: ".$diffInSecondsJobs." segundo(s)\n";
        $this->endJob();

    }

    public function gerapagamento(){

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job\n";

        $this->getaccesstokens();
        $contador = 0;

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'gerapagamento';

        $conciliations = $this->model_conciliation->getOpenConciliations(false);

        if($conciliations){

            $variavelAmbiente = $this->model_settings->getSettingDatabyName('vtex_seller_prefix');
            $sellercenterAmbiente = $this->model_settings->getSettingDatabyName('sellercenter');

            $paramGetnet = $this->model_settings->getSettingDatabyNameEmptyArray('getnet_billet_cancel');
            if($paramGetnet['status'] == "2"){
                $boletoCanceladoGetnet = false;
            }else{
                $boletoCanceladoGetnet = true;
            }

            $sellercenterAmbiente = $sellercenterAmbiente['name'];

            foreach ($conciliations as $key => $conciliation) {

                $current_day = date("j");

                //Processando apenas pagamentos que devem ser feitos no mesmo dia ou o status do repasse for 25
                if (!($conciliation['data_pagamento'] == $current_day || $conciliation['status_repasse'] == 25)) {
                    continue;
                }

                echo "[".date("Y-m-d H:i:s")."] - Processando conciliação: {$conciliation['conciliacao_id']}" . PHP_EOL;

                //Busca as linhas que serão pagas ou ajustadas
                $transferencias = $this->model_transfer->getTransfersConciliacao($conciliation['lote']);

                foreach($transferencias as $transferencia){

                    //  if($transferencia['numero_marketplace'] == '1206662156012-02'){

                    // Tratamento para não gatilhar ou ajustar pedidos Zema
                    if ($transferencia["seller_name"] == "Zema Marketplace"){
                        continue;
                    }

                    //Busca dados da conta
                    $store = $this->model_stores->getStoreById($transferencia['store_id']);
                    $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($transferencia['store_id']);

                    if($transferencia['repasse_tratado'] < 0){

                        //Chama API de Ajuste para descontar do seller
                        echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $transferencia['repasse_tratado']. PHP_EOL;

                        $motivo = "Ajuste de pedido descontado por ajuste ".$transferencia['numero_marketplace'];
                        $response = array();
                        $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$transferencia['repasse_tratado']),2,$motivo);

                        if (!($response['httpcode'] == "200")) {  // created

                            $responseContent = json_decode($response['content']);
                            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            $msg = "Erro ao gerar ajuste ao seller na getnet: " . $store['id']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da Getnet: " . PHP_EOL
                                . $responseContent . ' ' . PHP_EOL ;

                            // $this->log_data('batch', $log_name, $msg, "E");

                            echo "[".date("Y-m-d H:i:s")."] - Erro ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $transferencia['repasse_tratado']. PHP_EOL;
                            print_r($response);
                        } else {

                            $responseContent = json_decode($response['content']);
                            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            $msg = "Sucesso ao gerar ajuste ao seller na getnet: " . $store['id']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da Getnet: " . PHP_EOL
                                . $responseContent . ' ' . PHP_EOL ;

                            // $this->log_data('batch', $log_name, $msg, "S");

                            echo "[".date("Y-m-d H:i:s")."] - Sucesso ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $transferencia['repasse_tratado']. PHP_EOL;

                        }
                    }else{
                        //Chama API de transferencias para repassar do seller

                        $dadosTransacao = $this->model_orders_payment->getByOrderId($transferencia['order_id']);
                        $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($transferencia['numero_marketplace_buscatrt']);

                        if(!$dadosExtrato){
                            $transactionArray = $this->model_transfer->getOrdersTransactionId($transferencia['order_id']);
                            if($transactionArray){
                                $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($transactionArray[0]['transaction_id']);
                            }
                        }

                        if($transferencia['order_id'] == 0){
                            $dadosPedidos['numero_marketplace'] = "999";
                            $dadosPedidos['date_time'] = 'NOW()';
                        }else{
                            $dadosPedidos = $this->model_orders->getOrdersData(0,$transferencia['order_id']);
                        }

                        if($dadosExtrato){

                            $encontrou = false;
                            foreach($dadosExtrato as $item){

                                $jsonArray = json_decode($item['json_retorno']);

                                $idVtex = "";

                                if($transferencia['store_id'] < 10){
                                    if($variavelAmbiente['status'] == "1"){
                                        $idVtex = $variavelAmbiente['value']."00".$transferencia['store_id'];
                                    }else{
                                        $idVtex = "00".$transferencia['store_id'];
                                    }
                                }elseif($transferencia['store_id'] < 100){
                                    if($variavelAmbiente['status'] == "1"){
                                        $idVtex = $variavelAmbiente['value']."0".$transferencia['store_id'];
                                    }else{
                                        $idVtex = "0".$transferencia['store_id'];
                                    }
                                }else{
                                    if($variavelAmbiente['status'] == "1"){
                                        if($variavelAmbiente['status'] == "1"){
                                            $idVtex = $variavelAmbiente['value']."".$transferencia['store_id'];
                                        }else{
                                            $idVtex = "".$transferencia['store_id'];
                                        }
                                    }
                                }

                                $idVtex = preg_replace("/[^A-Za-z0-9 ]/", '', $idVtex);

                                if($idVtex == $item['item_id']){

                                    $valorDiferencaRepasse = 0;
                                    $valorDiferencaRepasse = round( $transferencia['repasse_tratado'] - ($item['subseller_rate_amount']/100) ,2);

                                    echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;

                                    $encontrou = true;
                                    $dadosTransferencia['item_id'] = $item['item_id'];
                                    $dadosTransferencia['installment_amount'] = $item['installment_amount'];

                                    /*if(strtoupper(str_replace("-","",$jsonArray->summary->payment_id))){
                                        $dadosTransferencia['payment_id'] =  strtoupper(str_replace("-","",$jsonArray->summary->payment_id));
                                    }else{
                                        $dadosTransferencia['payment_id'] = $dadosTransacao[0]['payment_id'];
                                    }*/

                                    if(isset($jsonArray->summary->payment_id)){
                                        if(strtoupper(str_replace("-","",$jsonArray->summary->payment_id))){
                                            $dadosTransferencia['payment_id'] =  strtoupper(str_replace("-","",$jsonArray->summary->payment_id));
                                        }
                                    }else{
                                        $dadosTransferencia['payment_id'] = $dadosTransacao[0]['payment_id'];
                                    }

                                    $response = array();
                                    $response = $this->integration->gerapagamento($dadosTransferencia);

                                    print_r($response);

                                    if (!($response['httpcode'] == "200")) {  // created

                                        $responseContent = json_decode($response['content']);
                                        $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                        $msg = "Erro ao pagar item ao seller na getnet: " . $dadosTransferencia['item_id'] . " - " . $dadosTransferencia['installment_amount']
                                            . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                            "Resposta da Getnet: " . PHP_EOL
                                            . $responseContent . ' ' . PHP_EOL ;

                                        // $this->log_data('batch', $log_name, $msg, "E");

                                        echo "[".date("Y-m-d H:i:s")."] - Erro ao liberar o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;

                                        $gatilhoLiberacao = strpos($response['content'], "Transaction not found for this payment_id.");
                                        if ($gatilhoLiberacao === false) {
                                            echo "[".date("Y-m-d H:i:s")."] - BUSCAR AQUI O Pedido ". $transferencia['numero_marketplace'] ." não será tratado por liberação ". PHP_EOL;
                                            echo $response['content']."\n";
                                        }else{

                                            echo "[".date("Y-m-d H:i:s")."] - Gerando o pagamendo do Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet que deu erro por ajuste". $transferencia['repasse_tratado']. PHP_EOL;

                                            // Paga o pedido que não consta no extrato da getnet
                                            // Chama API de Ajuste para descontar do seller
                                            $motivo = "Ajuste de pedido pago fora do conector ".$transferencia['numero_marketplace'];

                                            $response2 = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$transferencia['repasse_tratado']),1,$motivo);
                                            if (!($response2['httpcode'] == "200")) {  // created

                                                $responseContent2 = json_decode($response2['content']);
                                                $responseContent2 = json_encode($responseContent2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                                $msg = "Erro ao pagar item ao seller na getnet: " . $dadosTransferencia['item_id'] . " - " . $dadosTransferencia['installment_amount']
                                                    . " httpcode: " . $response2['httpcode'] . " " . PHP_EOL .
                                                    "Resposta da Getnet: " . PHP_EOL
                                                    . $responseContent2 . ' ' . PHP_EOL ;

                                                // $this->log_data('batch', $log_name, $msg, "E");

                                                echo "[".date("Y-m-d H:i:s")."] - Erro ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet MAS QUE DEU ERRO NA LIBERAÇÃO". $transferencia['repasse_tratado']. PHP_EOL;
                                                print_r($response2);

                                            }else{

                                                $responseContent2 = json_decode($response2['content']);
                                                $responseContent2 = json_encode($responseContent2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                                $msg = "Sucesso ao gerar ajuste ao seller na getnet: " . $store['id']
                                                    . " httpcode: " . $response2['httpcode'] . " " . PHP_EOL .
                                                    "Resposta da Getnet: " . PHP_EOL
                                                    . $responseContent2 . ' ' . PHP_EOL ;

                                                //  $this->log_data('batch', $log_name, $msg, "S");

                                                $dadosIuguRepasse['order_id'] = $transferencia['order_id'];
                                                $dadosIuguRepasse['numero_marketplace'] = $dadosPedidos['numero_marketplace'];
                                                $dadosIuguRepasse['data_split'] = $dadosPedidos['date_time'];
                                                $dadosIuguRepasse['valor_parceiro'] = str_replace("-","",$transferencia['repasse_tratado']);
                                                $dadosIuguRepasse['conciliacao_id'] = $transferencia['conciliacao_id'];

                                                $this->model_repasse->saveStatement($dadosIuguRepasse);

                                                echo "[".date("Y-m-d H:i:s")."] - Sucesso ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet MAS QUE DEU ERRO NA LIBERAÇÃO". $transferencia['repasse_tratado']. PHP_EOL;

                                            }

                                        }

                                    } else {

                                        $responseContent = json_decode($response['content']);
                                        $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                        $msg = "Sucesso ao pagar item ao seller na getnet: " . $store['id']
                                            . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                            "Resposta da Getnet: " . PHP_EOL
                                            . $responseContent . ' ' . PHP_EOL ;

                                        // $this->log_data('batch', $log_name, $msg, "S");

                                        $dadosIuguRepasse['order_id'] = $transferencia['order_id'];
                                        $dadosIuguRepasse['numero_marketplace'] = $dadosPedidos['numero_marketplace'];
                                        $dadosIuguRepasse['data_split'] = $dadosPedidos['date_time'];
                                        $dadosIuguRepasse['valor_parceiro'] = $item['installment_amount']/100;
                                        $dadosIuguRepasse['conciliacao_id'] = $transferencia['conciliacao_id'];

                                        $this->model_repasse->saveStatement($dadosIuguRepasse);

                                        if($valorDiferencaRepasse > 1){

                                            $motivo = "Ajuste de saldo pós liberação do pedido".$transferencia['numero_marketplace'];

                                            $response = array();
                                            $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$valorDiferencaRepasse),1,$motivo);

                                            if (!($response['httpcode'] == "200")) {  // created

                                                $responseContent = json_decode($response['content']);
                                                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                                $msg = "Erro ao gerar ajuste ao seller na getnet: " . $store['id']
                                                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                                    "Resposta da Getnet: " . PHP_EOL
                                                    . $responseContent . ' ' . PHP_EOL ;

                                                // $this->log_data('batch', $log_name, $msg, "E");

                                                echo "[".date("Y-m-d H:i:s")."] - Sucesso ao liberar o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet ". $transferencia['repasse_tratado']." e ERRO AO AJUSTAR o valor em ".$valorDiferencaRepasse. PHP_EOL;
                                                print_r($response);

                                            } else {
                                                echo "[".date("Y-m-d H:i:s")."] - Sucesso ao liberar o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet ". $transferencia['repasse_tratado']." e ajustado o valor em ".$valorDiferencaRepasse. PHP_EOL;
                                            }

                                        }else{
                                            echo "[".date("Y-m-d H:i:s")."] - Sucesso ao liberar o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;
                                        }


                                    }
                                }
                            }

                        }else{

                            echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." NÃO encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;

                            // Paga o pedido que não consta no extrato da getnet
                            // Chama API de Ajuste para descontar do seller
                            $motivo = "Ajuste de pedido pago fora do conector ".$transferencia['numero_marketplace'];

                            $response = array();
                            $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$transferencia['repasse_tratado']),1,$motivo);

                            if (!($response['httpcode'] == "200")) {  // created

                                $responseContent = json_decode($response['content']);
                                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                $msg = "Erro ao gerar ajuste ao seller na getnet: " . $store['id']
                                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                    "Resposta da Getnet: " . PHP_EOL
                                    . $responseContent . ' ' . PHP_EOL ;

                                // $this->log_data('batch', $log_name, $msg, "E");

                                echo "[".date("Y-m-d H:i:s")."] - Erro ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." NÃO encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;
                                print_r($response);

                            } else {

                                $responseContent = json_decode($response['content']);
                                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                $msg = "Sucesso ao gerar ajuste ao seller na getnet: " . $store['id']
                                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                    "Resposta da Getnet: " . PHP_EOL
                                    . $responseContent . ' ' . PHP_EOL ;

                                //   $this->log_data('batch', $log_name, $msg, "S");

                                $dadosIuguRepasse['order_id'] = $transferencia['order_id'];
                                $dadosIuguRepasse['numero_marketplace'] = $dadosPedidos['numero_marketplace'];
                                $dadosIuguRepasse['data_split'] = $dadosPedidos['date_time'];
                                $dadosIuguRepasse['valor_parceiro'] = str_replace("-","",$transferencia['repasse_tratado']);
                                $dadosIuguRepasse['conciliacao_id'] = $transferencia['conciliacao_id'];

                                $this->model_repasse->saveStatement($dadosIuguRepasse);

                                echo "[".date("Y-m-d H:i:s")."] - Sucesso ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." NÃO encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;


                            }

                        }

                    }

                    //Trata o Pedido cancelado por Boleto para realizar o gatilho e ajuste negativo
                    if($boletoCanceladoGetnet){
                    
                        if($transferencia['status_conciliacao'] == 'Conciliação Cancelamento' && $transferencia['tipo_pagamento'] == 'Boleto Bancário'){

                            echo "[".date("Y-m-d H:i:s")."] - Início do gatilho/ajuste do pedido Cancelado por boleto ". $transferencia['numero_marketplace'] . PHP_EOL;

                            $dadosTransacao = $this->model_orders_payment->getByOrderId($transferencia['order_id']);
                            $transactionArray = $this->model_transfer->getOrdersTransactionId($transferencia['order_id']);

                            $idVtex = "";

                            if($transferencia['store_id'] < 10){
                                if($variavelAmbiente['status'] == "1"){
                                    $idVtex = $variavelAmbiente['value']."00".$transferencia['store_id'];
                                }else{
                                    $idVtex = "00".$transferencia['store_id'];
                                }
                            }elseif($transferencia['store_id'] < 100){
                                if($variavelAmbiente['status'] == "1"){
                                    $idVtex = $variavelAmbiente['value']."0".$transferencia['store_id'];
                                }else{
                                    $idVtex = "0".$transferencia['store_id'];
                                }
                            }else{
                                if($variavelAmbiente['status'] == "1"){
                                    if($variavelAmbiente['status'] == "1"){
                                        $idVtex = $variavelAmbiente['value']."".$transferencia['store_id'];
                                    }else{
                                        $idVtex = "".$transferencia['store_id'];
                                    }
                                }
                            }

                            $idVtex = preg_replace("/[^A-Za-z0-9 ]/", '', $idVtex);

                            $dadosExtrato = $this->model_gateway->getExtratoGetnetORdersAndStoreId($transferencia['numero_marketplace_buscatrt'],$transactionArray[0]['transaction_id'],$idVtex);

                            // Encontrado no extrato getnet, realizando o gatilho de pagamento
                            if($dadosExtrato){

                                foreach($dadosExtrato as $gatilhoPedido){

                                    echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;

                                        $dadosTransferencia['item_id'] = $gatilhoPedido['item_id'];
                                        $dadosTransferencia['installment_amount'] = $gatilhoPedido['installment_amount'];
                                        $jsonArray = json_decode($gatilhoPedido['json_retorno']);

                                        if(isset($jsonArray->summary->payment_id)){
                                            if(strtoupper(str_replace("-","",$jsonArray->summary->payment_id))){
                                                $dadosTransferencia['payment_id'] =  strtoupper(str_replace("-","",$jsonArray->summary->payment_id));
                                            }
                                        }else{
                                            $dadosTransferencia['payment_id'] = $dadosTransacao[0]['payment_id'];
                                        }

                                        $response = array();
                                        $response = $this->integration->gerapagamento($dadosTransferencia);

                                        print_r($response);

                                        if (!($response['httpcode'] == "200")) {  // created
                                            echo "[".date("Y-m-d H:i:s")."] - Erro ao gatilhar pedido Cancelado por boleto ". $transferencia['numero_marketplace'] ." e por conta disso não será feito o ajuste ".$response['content']. PHP_EOL;
                                        }else{
                                            echo "[".date("Y-m-d H:i:s")."] - Sucesso ao gatilhar pedido Cancelado por boleto ". $transferencia['numero_marketplace'] ." gerando o ajuste ".PHP_EOL;
                                        }
                                            //Chama API de Ajuste para descontar do seller

                                            $motivo = "Ajuste de pedido Boleto Cancelado por ajuste ".$transferencia['numero_marketplace'];
                                            $response = array();
                                            $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",round($gatilhoPedido['installment_amount']/100,2)),2,$motivo);

                                            if (!($response['httpcode'] == "200")) {  // created

                                                $responseContent = json_decode($response['content']);
                                                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                                $msg = "Erro ao gerar ajuste ao seller na getnet: " . $store['id']
                                                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                                    "Resposta da Getnet: " . PHP_EOL
                                                    . $responseContent . ' ' . PHP_EOL ;

                                                echo "[".date("Y-m-d H:i:s")."] - Erro ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $gatilhoPedido['installment_amount']. PHP_EOL;
                                                print_r($response);
                                            } else {

                                                $responseContent = json_decode($response['content']);
                                                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                                $msg = "Sucesso ao gerar ajuste ao seller na getnet: " . $store['id']
                                                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                                    "Resposta da Getnet: " . PHP_EOL
                                                    . $responseContent . ' ' . PHP_EOL ;

                                                echo "[".date("Y-m-d H:i:s")."] - Sucesso ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $gatilhoPedido['installment_amount']. PHP_EOL;

                                            }


                                        

                                }

                            }else{
                                echo "[".date("Y-m-d H:i:s")."] - Erro ao gatilhar pedido Cancelado por boleto ". $transferencia['numero_marketplace'] ." pois NÃO foi encontrado no extrato getnet ". PHP_EOL;
                            }


                        }
                    }

                    $contador++;
                    if($contador == "200"){
                        $this->getaccesstokens();
                        $contador = 0;
                    }

                }


                //Muda para paga a conciliação
                $this->model_conciliation->updateConciliationStatus($conciliation['conciliacao_id'],'23');

            }
        }else{
            echo "[".date("Y-m-d H:i:s")."] - Nenhuma conciliação para efetuar\n";
        }
        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";

    }



    public function gerapagamentodecathlonajuste(){

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job\n";

        $this->getaccesstokens();
        $contador = 0;

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'gerapagamento';

        $conciliations = $this->model_conciliation->getOpenConciliations(false);

        if($conciliations){

            $variavelAmbiente = $this->model_settings->getSettingDatabyName('vtex_seller_prefix');

            foreach ($conciliations as $key => $conciliation) {

                $current_day = date("j");

                //Processando apenas pagamentos que devem ser feitos no mesmo dia ou o status do repasse for 25
                if (!($conciliation['data_pagamento'] == $current_day || $conciliation['status_repasse'] == 25)) {
                    continue;
                }

                echo "[".date("Y-m-d H:i:s")."] - Processando conciliação: {$conciliation['conciliacao_id']}" . PHP_EOL;

                //Busca as linhas que serão pagas ou ajustadas
                $transferencias = $this->model_transfer->getTransfersConciliacao($conciliation['lote']);

                foreach($transferencias as $transferencia){

                    //  if($transferencia['numero_marketplace'] == '1206662156012-02'){

                    //Busca dados da conta
                    $store = $this->model_stores->getStoreById($transferencia['store_id']);
                    $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($transferencia['store_id']);

                    if($transferencia['repasse_tratado'] < 0){

                        //Chama API de Ajuste para descontar do seller
                        echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $transferencia['repasse_tratado']. PHP_EOL;

                        // $motivo = "Ajuste de pedido descontado por ajuste ".$transferencia['numero_marketplace'];
                        $motivo = "Pagamento de Sellers  ".$transferencia['numero_marketplace'];
                        $response = array();
                        $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$transferencia['repasse_tratado']),2,$motivo);

                        if (!($response['httpcode'] == "200")) {  // created

                            $responseContent = json_decode($response['content']);
                            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            $msg = "Erro ao gerar ajuste ao seller na getnet: " . $store['id']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da Getnet: " . PHP_EOL
                                . $responseContent . ' ' . PHP_EOL ;

                            // $this->log_data('batch', $log_name, $msg, "E");

                            echo "[".date("Y-m-d H:i:s")."] - Erro ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $transferencia['repasse_tratado']. PHP_EOL;
                            print_r($response);
                        } else {

                            $responseContent = json_decode($response['content']);
                            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            $msg = "Sucesso ao gerar ajuste ao seller na getnet: " . $store['id']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da Getnet: " . PHP_EOL
                                . $responseContent . ' ' . PHP_EOL ;

                            // $this->log_data('batch', $log_name, $msg, "S");

                            echo "[".date("Y-m-d H:i:s")."] - Sucesso ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $transferencia['repasse_tratado']. PHP_EOL;

                        }
                    }else{

                        $dadosTransacao = $this->model_orders_payment->getByOrderId($transferencia['order_id']);
                        $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($transferencia['numero_marketplace_buscatrt']);

                        if(!$dadosExtrato){
                            $transactionArray = $this->model_transfer->getOrdersTransactionId($transferencia['order_id']);
                            if($transactionArray){
                                $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($transactionArray[0]['transaction_id']);
                            }
                        }

                        if($transferencia['order_id'] == 0){
                            $dadosPedidos['numero_marketplace'] = "999";
                            $dadosPedidos['date_time'] = 'NOW()';
                        }else{
                            $dadosPedidos = $this->model_orders->getOrdersData(0,$transferencia['order_id']);
                        }

                        // echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." NÃO encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;
                        echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $transferencia['repasse_tratado']. PHP_EOL;

                        // Paga o pedido que não consta no extrato da getnet
                        // Chama API de Ajuste para descontar do seller
                        // $motivo = "Ajuste de pedido pago fora do conector ".$transferencia['numero_marketplace'];
                        $motivo = "Recuperação de valores ".$transferencia['numero_marketplace'];

                        $response = array();
                        $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$transferencia['repasse_tratado']),1,$motivo);

                        if (!($response['httpcode'] == "200")) {  // created

                            $responseContent = json_decode($response['content']);
                            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            $msg = "Erro ao gerar ajuste ao seller na getnet: " . $store['id']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da Getnet: " . PHP_EOL
                                . $responseContent . ' ' . PHP_EOL ;

                            // $this->log_data('batch', $log_name, $msg, "E");

                            echo "[".date("Y-m-d H:i:s")."] - Erro ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." NÃO encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;
                            print_r($response);

                        } else {

                            $responseContent = json_decode($response['content']);
                            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            $msg = "Sucesso ao gerar ajuste ao seller na getnet: " . $store['id']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da Getnet: " . PHP_EOL
                                . $responseContent . ' ' . PHP_EOL ;

                            //   $this->log_data('batch', $log_name, $msg, "S");

                            $dadosIuguRepasse['order_id'] = $transferencia['order_id'];
                            $dadosIuguRepasse['numero_marketplace'] = $dadosPedidos['numero_marketplace'];
                            $dadosIuguRepasse['data_split'] = $dadosPedidos['date_time'];
                            $dadosIuguRepasse['valor_parceiro'] = str_replace("-","",$transferencia['repasse_tratado']);
                            $dadosIuguRepasse['conciliacao_id'] = $transferencia['conciliacao_id'];

                            $this->model_repasse->saveStatement($dadosIuguRepasse);

                            echo "[".date("Y-m-d H:i:s")."] - Sucesso ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." NÃO encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;


                        }



                    }

                    $contador++;
                    if($contador == "200"){
                        $this->getaccesstokens();
                        $contador = 0;
                    }

                }


                //Muda para paga a conciliação
                $this->model_conciliation->updateConciliationStatus($conciliation['conciliacao_id'],'23');

            }
        }else{
            echo "[".date("Y-m-d H:i:s")."] - Nenhuma conciliação para efetuar\n";
        }
        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";

    }

    public function gerapagamentodecathlongatilho(){

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job\n";

        $this->getaccesstokens();
        $contador = 0;

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'gerapagamento';

        $conciliations = $this->model_conciliation->getOpenConciliations(false);

        if($conciliations){

            $variavelAmbiente = $this->model_settings->getSettingDatabyName('vtex_seller_prefix');

            foreach ($conciliations as $key => $conciliation) {

                $current_day = date("j");

                //Processando apenas pagamentos que devem ser feitos no mesmo dia ou o status do repasse for 25
                if (!($conciliation['data_pagamento'] == $current_day || $conciliation['status_repasse'] == 25)) {
                    continue;
                }

                echo "[".date("Y-m-d H:i:s")."] - Processando conciliação: {$conciliation['conciliacao_id']}" . PHP_EOL;

                //Busca as linhas que serão pagas ou ajustadas
                $transferencias = $this->model_transfer->getTransfersConciliacao($conciliation['lote']);

                foreach($transferencias as $transferencia){

                    // if($transferencia['numero_marketplace'] == '1273116502232-05'){

                    //Busca dados da conta
                    $store = $this->model_stores->getStoreById($transferencia['store_id']);
                    $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($transferencia['store_id']);

                    if($transferencia['repasse_tratado'] < 0){

                        echo "[".date("Y-m-d H:i:s")."] - O pedido ". $transferencia['numero_marketplace'] ." no valor de ". $transferencia['repasse_tratado']." está negativo e não será tratado por ajuste, este job utiliza apenas o gatilho de pagamento". PHP_EOL;

                    }else{
                        //Chama API de transferencias para repassar do seller

                        $dadosTransacao = $this->model_orders_payment->getByOrderId($transferencia['order_id']);
                        $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($transferencia['numero_marketplace_buscatrt']);

                        if(!$dadosExtrato){
                            $transactionArray = $this->model_transfer->getOrdersTransactionId($transferencia['order_id']);
                            if($transactionArray){
                                $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($transactionArray[0]['transaction_id']);
                            }
                        }

                        echo "Buscando order {$transferencia['order_id']}".PHP_EOL;

                        $dadosPedidos = $this->model_orders->getOrdersData(0,$transferencia['order_id']);

                        if($dadosExtrato){

                            $encontrou = false;
                            foreach($dadosExtrato as $item){

                                $jsonArray = json_decode($item['json_retorno']);

                                $idVtex = "";

                                if($transferencia['store_id'] < 10){
                                    if($variavelAmbiente['status'] == "1"){
                                        $idVtex = $variavelAmbiente['value']."00".$transferencia['store_id'];
                                    }else{
                                        $idVtex = "00".$transferencia['store_id'];
                                    }
                                }elseif($transferencia['store_id'] < 100){
                                    if($variavelAmbiente['status'] == "1"){
                                        $idVtex = $variavelAmbiente['value']."0".$transferencia['store_id'];
                                    }else{
                                        $idVtex = "0".$transferencia['store_id'];
                                    }
                                }else{
                                    if($variavelAmbiente['status'] == "1"){
                                        if($variavelAmbiente['status'] == "1"){
                                            $idVtex = $variavelAmbiente['value']."".$transferencia['store_id'];
                                        }else{
                                            $idVtex = "".$transferencia['store_id'];
                                        }
                                    }
                                }

                                $idVtex = preg_replace("/[^A-Za-z0-9 ]/", '', $idVtex);

                                if($idVtex == $item['item_id']){

                                    $valorDiferencaRepasse = 0;
                                    $valorDiferencaRepasse = round( $transferencia['repasse_tratado'] - ($item['subseller_rate_amount']/100) ,2);

                                    echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;

                                    $encontrou = true;
                                    $dadosTransferencia['item_id'] = $item['item_id'];
                                    $dadosTransferencia['installment_amount'] = $item['installment_amount'];

                                    if(isset($jsonArray->summary->payment_id)){
                                        if(strtoupper(str_replace("-","",$jsonArray->summary->payment_id))){
                                            $dadosTransferencia['payment_id'] =  strtoupper(str_replace("-","",$jsonArray->summary->payment_id));
                                        }
                                    }else{
                                        $dadosTransferencia['payment_id'] = $dadosTransacao[0]['payment_id'];
                                    }

                                    $response = array();
                                    $response = $this->integration->gerapagamento($dadosTransferencia);
                                    
                                    print_r($response);

                                    if (!($response['httpcode'] == "200")) {  // created

                                        echo "[".date("Y-m-d H:i:s")."] - ERRO ao liberar o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;

                                    } else {

                                        echo "[".date("Y-m-d H:i:s")."] - Sucesso ao liberar o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;

                                        $dadosIuguRepasse['order_id'] = $transferencia['order_id'];
                                        $dadosIuguRepasse['numero_marketplace'] = $dadosPedidos['numero_marketplace'];
                                        $dadosIuguRepasse['data_split'] = $dadosPedidos['date_time'];
                                        $dadosIuguRepasse['valor_parceiro'] = $item['installment_amount']/100;
                                        $dadosIuguRepasse['conciliacao_id'] = $transferencia['conciliacao_id'];
    
                                        $this->model_repasse->saveStatement($dadosIuguRepasse);

                                    }
                                }
                            }

                        }else{

                            echo "[".date("Y-m-d H:i:s")."] - ERRO O Pedido ". $transferencia['numero_marketplace'] ." não foi encontrado na getnet e por isso não será gatilhado". PHP_EOL;

                        }

                    }

                    $contador++;
                    if($contador == "200"){
                        $this->getaccesstokens();
                        $contador = 0;
                    }

                    // }

                }

                //Muda para paga a conciliação
                $this->model_conciliation->updateConciliationStatus($conciliation['conciliacao_id'],'23');

            }
        }else{
            echo "[".date("Y-m-d H:i:s")."] - Nenhuma conciliação para efetuar\n";
        }
        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";

    }


    public function pagamentoitensdecathlon(){

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job\n";

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'pagamentoitensdecathlon';

        //busca os pedidos a serem pagos da decathlon
        $pedidos = $this->model_conciliation->getOpenConciliations(false);

        if($conciliations){
            foreach ($conciliations as $key => $conciliation) {

                $current_day = date("j");

                //Processando apenas pagamentos que devem ser feitos no mesmo dia ou o status do repasse for 25
                if (!($conciliation['data_pagamento'] == $current_day || $conciliation['status_repasse'] == 25)) {
                    continue;
                }
                echo "Processando conciliação: {$conciliation['conciliacao_id']}" . PHP_EOL;

                //Busca as linhas que serão pagas ou ajustadas
                $transferencias = $this->model_transfer->getTransfersConciliacao($conciliation['lote']);

                foreach($transferencias as $transferencia){

                    //Busca dados da conta
                    $store = $this->model_stores->getStoreById($transferencia['store_id']);
                    $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($transferencia['store_id']);

                    if($transferencia['repasse_tratado'] < 0){
                        //Chama API de Ajuste para descontar do seller

                        $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$transferencia['repasse_tratado']));

                        if (!($response['httpcode'] == "200")) {  // created

                            $responseContent = json_decode($response['content']);
                            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            $msg = "Erro ao gerar ajuste ao seller na getnet: " . $store['id']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da Getnet: " . PHP_EOL
                                . $responseContent . ' ' . PHP_EOL ;

                            //  $this->log_data('batch', $log_name, $msg, "E");

                            echo "[".date("Y-m-d H:i:s")."] - Erro ao gerar ajuste\n ".$responseContent."\n";

                        } else {

                            $responseContent = json_decode($response['content']);
                            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            $msg = "Sucesso ao gerar ajuste ao seller na getnet: " . $store['id']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da Getnet: " . PHP_EOL
                                . $responseContent . ' ' . PHP_EOL ;

                            //  $this->log_data('batch', $log_name, $msg, "S");

                            echo "[".date("Y-m-d H:i:s")."] - Ajuste gerado com sucesso\n";

                        }
                    }else{
                        //Chama API de transferencias para descontar do seller

                        $dadosTransacao = $this->model_orders_payment->getByOrderId($transferencia['order_id']);
                        $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($transferencia['numero_marketplace_buscatrt']);

                        $dadosPedidos = $this->model_orders->getOrdersData(0,$transferencia['order_id']);
                        print_r($dadosExtrato);
                        if($dadosExtrato){
                            foreach($dadosExtrato as $item){

                                $dadosTransferencia['item_id'] = $item['item_id'];
                                $dadosTransferencia['installment_amount'] = $item['installment_amount'];
                                $dadosTransferencia['payment_id'] = $dadosTransacao[0]['payment_id'];

                                $response = $this->integration->gerapagamento($dadosTransferencia);
                                print_r($response);
                                if (!($response['httpcode'] == "200")) {  // created

                                    $responseContent = json_decode($response['content']);
                                    $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                    $msg = "Erro ao pagar item ao seller na getnet: " . $dadosTransferencia['item_id'] . " - " . $dadosTransferencia['installment_amount']
                                        . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                        "Resposta da Getnet: " . PHP_EOL
                                        . $responseContent . ' ' . PHP_EOL ;

                                    // $this->log_data('batch', $log_name, $msg, "E");


                                    echo "[".date("Y-m-d H:i:s")."] - Erro ao pagar item\n";

                                } else {

                                    $responseContent = json_decode($response['content']);
                                    $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                    $msg = "Sucesso ao pagar item ao seller na getnet: " . $store['id']
                                        . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                        "Resposta da Getnet: " . PHP_EOL
                                        . $responseContent . ' ' . PHP_EOL ;

                                    //  $this->log_data('batch', $log_name, $msg, "S");

                                    echo "[".date("Y-m-d H:i:s")."] - Item pago com sucesso\n";

                                    $dadosIuguRepasse['order_id'] = $transferencia['order_id'];
                                    $dadosIuguRepasse['numero_marketplace'] = $dadosPedidos['numero_marketplace'];
                                    $dadosIuguRepasse['data_split'] = $dadosPedidos['date_time'];
                                    $dadosIuguRepasse['valor_parceiro'] = $item['installment_amount']/100;
                                    $dadosIuguRepasse['conciliacao_id'] = $transferencia['conciliacao_id'];

                                    $this->model_repasse->saveStatement($dadosIuguRepasse);

                                }

                            }
                        }else{

                            // Paga o pedido que não consta no extrato da getnet
                            // Chama API de Ajuste para descontar do seller

                            $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$transferencia['repasse_tratado']),1,'Ajuste de pedido pago fora do conector');

                            if (!($response['httpcode'] == "200")) {  // created

                                $responseContent = json_decode($response['content']);
                                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                $msg = "Erro ao gerar ajuste ao seller na getnet: " . $store['id']
                                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                    "Resposta da Getnet: " . PHP_EOL
                                    . $responseContent . ' ' . PHP_EOL ;

                                // $this->log_data('batch', $log_name, $msg, "E");

                                echo "[".date("Y-m-d H:i:s")."] - Erro ao gerar ajuste\n".$responseContent."\n";

                            } else {

                                $responseContent = json_decode($response['content']);
                                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                                $msg = "Sucesso ao gerar ajuste ao seller na getnet: " . $store['id']
                                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                    "Resposta da Getnet: " . PHP_EOL
                                    . $responseContent . ' ' . PHP_EOL ;

                                //  $this->log_data('batch', $log_name, $msg, "S");

                                echo "[".date("Y-m-d H:i:s")."] - Ajuste gerado com sucesso\n".$response['httpcode']."\n";

                                $dadosIuguRepasse['order_id'] = $transferencia['order_id'];
                                $dadosIuguRepasse['numero_marketplace'] = $dadosPedidos['numero_marketplace'];
                                $dadosIuguRepasse['data_split'] = $dadosPedidos['date_time'];
                                $dadosIuguRepasse['valor_parceiro'] = str_replace("-","",$transferencia['repasse_tratado']);

                                $this->model_repasse->saveStatement($dadosIuguRepasse);

                            }


                        }


                    }

                }

            }
        }else{

            echo "[".date("Y-m-d H:i:s")."] - Nenhuma conciliação para efetuar\n";

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";



    }


    private function syncSubAccounts(bool $onlyNotCreatedAccount = true, $id = null, $params = null): void
    {
        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job\n";


        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'syncSubAccounts';

        $stores = $this->model_stores->getStoresWithoutGatewaySubAccountsGetnet($gateway_name);

        foreach ($stores as $key => $store) {

            echo "[".date("Y-m-d H:i:s")."] - Cadastrando Loja ".$store['id']." - ".$store['name']."\n";

            // echo $key . ' - ' . $store['name'] . ' (' . $store['id'] . ')' . PHP_EOL;

            $errors = [];

            if ($store["bank"] == "") {
                $errors[] = 'Banco não encontrado';
            }

            if ($store["agency"] == "") {
                $errors[] = 'Agência não encontrada';
            }

            if ($store["account"] == "") {
                $errors[] = 'Conta Bancária não encontrada';
            }

            if ($errors) {

                echo "[".date("Y-m-d H:i:s")."] - Erro ao cadastrar Loja ".$store['id']." - ".$store['name']."\n";

                $error = implode(', ', $errors);

                /* $this->log_data(
                     'batch',
                     $log_name,
                     "Não foi possível integrar a loja: {$store['id']} a Getnet. $error",
                     "E"
                 ); */

                $this->model_payment_gateway_store_logs->insertLog(
                    $store['id'],
                    $gatewayId,
                    $error
                );

                continue;

            }

            $store['bank_number'] = $this->model_banks->getBankNumber($store['bank']);

            $response = $this->integration->createRecipient($store);

            /* if($store['id']== "167"){
                 print_r($store);
                 print_r($response);
             } */

            $responseContent = json_decode($response['content']);
            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if (!($response['httpcode'] == "200")) {  // created

                echo "[".date("Y-m-d H:i:s")."] - Erro ao cadastrar Loja ".$store['id']." - ".$store['name']."\n";

                $msg = "Erro ao cadastrar recebedor na Getnet, Loja: " . $store['id']
                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                    "Resposta da Getnet: " . PHP_EOL
                    . $responseContent . ' ' . PHP_EOL . PHP_EOL .
                    "Payload enviado: " . PHP_EOL
                    . $response['payload_request'];

                // $this->log_data('batch', $log_name, $msg, "E");

                $this->model_payment_gateway_store_logs->insertLog(
                    $store['id'],
                    $gatewayId,
                    $msg
                );

            } elseif ($this->model_gateway->countStoresWithGatewayIdDifferentFromOne($store['id'], PaymentGatewayEnum::GETNET, $responseContent['subseller_id']) > 0) {

                echo "[" . date("Y-m-d H:i:s") . "] - Erro ao cadastrar Loja " . $store['id'] . " - " . $store['name'] . "\n";

                $msg = "Erro ao cadastrar recebedor na Getnet, Loja: " . $store['id'] .
                    "ID de subconta já registrado: " . $responseContent['subseller_id'] . PHP_EOL .
                    "Payload enviado: " . PHP_EOL
                    . $response['payload_request'];

                $this->model_payment_gateway_store_logs->insertLog(
                    $store['id'],
                    $gatewayId,
                    $msg
                );
            }
            else 
            {
                echo "[".date("Y-m-d H:i:s")."] - Sucesso ao cadastrar Loja ".$store['id']." - ".$store['name']."\n";

                $responseContent = json_decode($response['content'], true);

                $this->model_payment_gateway_store_logs->insertLog(
                    $store['id'],
                    $gatewayId,
                    'Loja cadastrada com sucesso, aguardando a validação na Getnet '. PHP_EOL . PHP_EOL. "Payload enviado: " . PHP_EOL . $response['payload_request'],
                    "W"
                );

                $data = array(
                    "store_id" => $store['id'],
                    "subseller_id" => $responseContent['subseller_id'],
                    "subsellerid_ext" => $responseContent['subsellerid_ext'],
                    "legal_document_number" => $responseContent['legal_document_number'],
                    "fiscal_type" => $responseContent['fiscal_type'],
                    "enabled" => $responseContent['enabled'],
                    "status" => $responseContent['status'],
                    "payment_plan" => $responseContent['payment_plan'],
                    "capture_payments_enabled" => $responseContent['capture_payments_enabled'],
                    "anticipation_enabled" => $responseContent['anticipation_enabled'],
                    "accepted_contract" => $responseContent['accepted_contract'],
                    "lock_schedule" => $responseContent['lock_schedule'],
                    "lock_capture_payments" => $responseContent['lock_capture_payments'],
                    "create_date" => $responseContent['create_date']
                );

                $this->model_gateway->createSubAccountsGetnet($data);

            }

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim Job do Job\n";
    }

    public function geraajustepontual(){

        echo '<pre>';

        echo "\n Início da chama com o type 1";

        /*$primeiroAjuste[1] = "6-330.3";
        $primeiroAjuste[2] = "1-12606.56";
        $primeiroAjuste[3] = "18-1344.58";
        $primeiroAjuste[4] = "49-15123.1";
        $primeiroAjuste[5] = "36-3864.38";
        $primeiroAjuste[6] = "23-1380.54";
        $primeiroAjuste[7] = "48-238.36";
        $primeiroAjuste[8] = "42-2192.9";
        $primeiroAjuste[9] = "19-1385.18";
        $primeiroAjuste[10] = "4-523.64";*/

        foreach($primeiroAjuste as $contaSaida){

            $valores = explode("-",$contaSaida);

            $idLoja = $valores[0];
            $valor = $valores[1];

            $store = $this->model_stores->getStoreById($idLoja);
            $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($idLoja);

            $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$valor),1,"Ajuste de liberação DecXGetXConecta");

            print_r($response);
            echo "\n";

        }

        echo "\n Início da chama com o type 2";

        /*$segundoAjuste[1] = "6-152.12";
        $segundoAjuste[2] = "1-582.84";
        $segundoAjuste[3] = "18-125.46";
        $segundoAjuste[4] = "49-139.12";
        $segundoAjuste[5] = "23-60.22";
        $segundoAjuste[6] = "9-185.08";
        $segundoAjuste[7] = "11-22.56";
        $segundoAjuste[8] = "42-82.2";
        $segundoAjuste[9] = "19-145.38";
        $segundoAjuste[10] = "16-26.88";
        $segundoAjuste[11] = "4-122.32";*/


        foreach($segundoAjuste as $contaSaida){

            $valores = explode("-",$contaSaida);

            $idLoja = $valores[0];
            $valor = $valores[1];

            $store = $this->model_stores->getStoreById($idLoja);
            $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($idLoja);

            $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$valor),2,"Ajuste de liberação DecXGetXConecta");

            print_r($response);
            echo "\n";

        }

        echo "\nFim dos ajustes";


    }

    public function geraajustepontualdecathlon(){

        $this->getaccesstokens();

        echo '<pre>';

        echo "[".date("Y-m-d H:i:s")."] - Início da chamada com o type 1\n";

        /* $primeiroAjuste[1]  = "125-119.69";
         $primeiroAjuste[2]  = "57-193.09";
         $primeiroAjuste[3]  = "89-344.82";
         $primeiroAjuste[4]  = "6-688.58";
         $primeiroAjuste[5]  = "95-66.99";
         $primeiroAjuste[6]  = "68-1293.83";
         $primeiroAjuste[7]  = "115-279.58";
         $primeiroAjuste[8]  = "1-25.31";
         $primeiroAjuste[9]  = "187-34.69";
         $primeiroAjuste[10] = "54-121.20";
         $primeiroAjuste[11] = "48-418.67";
         $primeiroAjuste[12] = "4-87.43";*/
        // $primeiroAjuste[13] = "13-6816.53";

        foreach($primeiroAjuste as $contaSaida){

            $valores = explode("-",$contaSaida);

            $idLoja = $valores[0];
            $valor = $valores[1];

            $store = $this->model_stores->getStoreById($idLoja);
            $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($idLoja);

            $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$valor),1,"Liberação de pagamento 20-07-2022 - Cicloway");

            print_r($response);
            echo "\n";

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim da chamada com o type 1\n";
        echo "[".date("Y-m-d H:i:s")."] - Início da chamada com o type 2\n";

        /*$segundoAjuste[1] = "8-1664.73";
        $segundoAjuste[5] = "62-19826.5";
        $segundoAjuste[14] = "14-66.59";
        $segundoAjuste[21] = "133-108.86";
        $segundoAjuste[22] = "83-3751.77";
        $segundoAjuste[24] = "150-201.25";
        $segundoAjuste[28] = "110-299.78";
        $segundoAjuste[33] = "113-121.39";
        $segundoAjuste[35] = "98-400.44";
        $segundoAjuste[47] = "18-33342.73";
        $segundoAjuste[50] = "28-412.51";
        $segundoAjuste[55] = "117-8397.4";
        $segundoAjuste[59] = "21-23755.22";
        $segundoAjuste[61] = "23-606.13";
        $segundoAjuste[78] = "16-20.61";
        $segundoAjuste[68] = "39-32830.37";*/

        /*$segundoAjuste[41] = "1-50000.00";
        $segundoAjuste[48] = "49-50000.00";*/
        //$segundoAjuste[54] = "41-50000.00";
        //$segundoAjuste[71] = "42-50000.00";

        /*$segundoAjuste[41] = "1-50000.00";
        $segundoAjuste[54] = "41-3835.27";
        $segundoAjuste[71] = "42-39577.82";
        $segundoAjuste[48] = "49-942.19";*/

        // $segundoAjuste[41] = "1-17206.72";

        // $segundoAjuste[13] = "5-6816.53";

        foreach($segundoAjuste as $contaSaida){

            $valores = explode("-",$contaSaida);

            $idLoja = $valores[0];
            $valor = $valores[1];

            $store = $this->model_stores->getStoreById($idLoja);
            $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($idLoja);

            $response = $this->integration->geraajustes($store, $subaccount, str_replace("-","",$valor),2,"Estorno da liberação 07-07-2022");

            print_r($response);
            echo "\n";

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim da chamada com o type 2\n";
        echo "[".date("Y-m-d H:i:s")."] - Fim dos ajustes\n";
        echo "[".date("Y-m-d H:i:s")."] - Início da liberação de pedidos\n";

        /*
         $liberacao[19] = "1213193158839|18246";
         $liberacao[20] = "1213390624488|18384";
         $liberacao[21] = "1213391278795|18385";
         $liberacao[22] = "1213703154024|18621";
         $liberacao[23] = "1213751216736|18632";
         $liberacao[24] = "1213642515616|18564";
         $liberacao[25] = "1213612902931|18528";
         $liberacao[26] = "1213083143027|18062";
         $liberacao[27] = "1215031759032|19788";
         $liberacao[28] = "1218442196460|23946";
         $liberacao[29] = "1218442196460|23945";
         $liberacao[30] = "1373415614364362AB0AB488F4269044|12650";
         $liberacao[203] = "0E9511558218480787584A82E3C96E11|21595";
 */

        /* $liberacao[1] = "1207381754026|13379";
         $liberacao[2] = "1213143594479|18163";
         $liberacao[3] = "1208540660394|14461";
         $liberacao[4] = "1208893344399|14758";
         $liberacao[5] = "1208902860958|14770";
         $liberacao[6] = "1209563152768|15334";
         $liberacao[7] = "1209563579137|15335";
         $liberacao[8] = "1210741068861|16150";
         $liberacao[9] = "1211451218381|16688";
         $liberacao[10] = "1212102431317|17111";
         $liberacao[11] = "1212202358242|17208";
         $liberacao[12] = "1212732770607|17752";
         $liberacao[13] = "1212921874361|17934";
        foreach($liberacao as $contaSaida){
         $valores = explode("|",$contaSaida);
         $dadosTransacao = $this->model_orders_payment->getByOrderId($valores[1]);
         // Dados Getnet
         $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($valores[0]);
         if(!$dadosExtrato){
              $transactionArray = $this->model_transfer->getOrdersTransactionId($valores[1]);
              if($transactionArray){
                  $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($transactionArray[0]['transaction_id']);
              }
         }
         if($dadosExtrato){
              foreach($dadosExtrato as $item){
                  if($item['item_id'] == "DEC021"){
                     $dadosTransferencia['item_id'] = $item['item_id'];
                     $dadosTransferencia['installment_amount'] = $item['installment_amount'];
                     $dadosTransferencia['payment_id'] = $dadosTransacao[0]['payment_id'];
                     $response = $this->integration->gerapagamento($dadosTransferencia);
                     print_r($dadosTransferencia);
                     print_r($response);
                 }
              }
         }
        }
        */

        echo "[".date("Y-m-d H:i:s")."] - Fim da liberação de pedidos\n";

    }

    public function geradatasretroativas($data = "2021-01-01"){

        if($data == null){
            $data = "2021-01-01";
        }

        $indice = 0;
        $arrayDatas = array();
        $check = true;

        $data = strtotime($data);

        while($check){

            if($indice == 0){
                $arrayDatas[$indice]['dataInicioMesAtual'] = date('Y-m-d', $data);
            }else{
                $arrayDatas[$indice]['dataInicioMesAtual'] = date('Y-m-01', $data);
            }
            
            $arrayDatas[$indice]['dataFimMesAtual'] = date('Y-m-t', $data);
            
            $indice++;

            $data = strtotime("+1 months", $data);
            
            if( date("Y-m-t") < date("Y-m-t", $data) ){
                $check = false;
            }

        }

        return $arrayDatas;

    }

    public function testeextratocompleto($tipo = "extrato", $data = null){
        ini_set('memory_limit', '3048M');

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Gera Extrato - $tipo\n";

        $dateInicioJob = new DateTime();

        $this->getaccesstokens();

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'testeextratocompleto';

        echo "[".date("Y-m-d H:i:s")."] - Buscando extrato atualizado por dias\n";

        /* PREPARA DATAS */
        $arrayDatas = $this->geradatasretroativas($data);
        
        foreach($arrayDatas as $datasExec){

            $dataMesAtualInicioLoop = date_create($datasExec['dataInicioMesAtual']);
            $dataMesAtualFimLoop = date_create($datasExec['dataFimMesAtual']);


            //Tratando o mês atual
            echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês ".date_format($dataMesAtualInicioLoop, 'Y-m-d')."T00:00:00Z"." - ".date_format($dataMesAtualFimLoop, 'Y-m-d')."T23:59:59Z"." com o parâmetro $tipo\n";
            for($j=1;$dataMesAtualInicioLoop<=$dataMesAtualFimLoop;date_add($dataMesAtualInicioLoop, date_interval_create_from_date_string('1 days'))) {

                $this->getaccesstokens();

                $this->db->trans_begin();

                $date = new DateTime();

                $dataInicio = date_format($dataMesAtualInicioLoop, 'Y-m-d') . "T00:00:00Z";
                $dataFim = date_format($dataMesAtualInicioLoop, 'Y-m-d') . "T23:59:59Z";
                
                if ($tipo == "extrato") {
                    $saida = $this->geraextratopordatas($dataInicio, $dataFim);
                } elseif ($tipo == "extrato_liquidacao") {
                    $saida = $this->geraextratoliquidacaopordatas($dataInicio, $dataFim);
                } elseif ($tipo == "ajuste") {
                    $saida = $this->geraajustepordatas($dataInicio, $dataFim);
                } elseif ($tipo == "ajuste_liquidacao") {
                    $saida = $this->geraajusteliquidacaopordatas($dataInicio, $dataFim);
                } elseif ($tipo == "mdr") {
                    $saida = $this->geraextratomdrpordatas($dataInicio, $dataFim);
                } elseif ($tipo == "pagamento") {
                    $saida = $this->geraextratpagamentoopordatas($dataInicio, $dataFim);
                }

                $date2 = new DateTime();
                $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";

                $this->db->trans_commit();

            }

        }


        $dateFimJob = new DateTime();
        $diffInSecondsJobs = $dateFimJob->getTimestamp() - $dateInicioJob->getTimestamp();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job - Tempo de execução: ".$diffInSecondsJobs." segundo(s)\n";
    }


    public function testeextratocompletoloja($tipo = "extrato", $idLoja = null){

        ini_set('memory_limit', '2048M');

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Gera Extrato\n";

        $dateInicioJob = new DateTime();

        $this->getaccesstokens();

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'testeextratocompleto';

        echo "[".date("Y-m-d H:i:s")."] - Buscando extrato atualizado por dias\n";

        /* PREPARA DATAS */
        $arrayDatas[1]['dataInicioMesAtual'] = '2021-09-01';
        $arrayDatas[1]['dataFimMesAtual'] = '2021-09-30';
        $arrayDatas[2]['dataInicioMesAtual'] = '2021-10-01';
        $arrayDatas[2]['dataFimMesAtual'] = '2021-10-31';
        $arrayDatas[3]['dataInicioMesAtual'] = '2021-11-01';
        $arrayDatas[3]['dataFimMesAtual'] = '2021-11-30';
        $arrayDatas[4]['dataInicioMesAtual'] = '2021-12-01';
        $arrayDatas[4]['dataFimMesAtual'] = '2021-12-31';
        $arrayDatas[5]['dataInicioMesAtual'] = '2022-01-01';
        $arrayDatas[5]['dataFimMesAtual'] = '2022-01-31';
        $arrayDatas[6]['dataInicioMesAtual'] = '2022-02-01';
        $arrayDatas[6]['dataFimMesAtual'] = '2022-02-28';
        $arrayDatas[7]['dataInicioMesAtual'] = '2022-03-01';
        $arrayDatas[7]['dataFimMesAtual'] = '2022-03-31';
        $arrayDatas[8]['dataInicioMesAtual'] = '2022-04-01';
        $arrayDatas[8]['dataFimMesAtual'] = '2022-04-30';
        $arrayDatas[9]['dataInicioMesAtual'] = '2022-05-01';
        $arrayDatas[9]['dataFimMesAtual'] = '2022-05-31';
        $arrayDatas[10]['dataInicioMesAtual'] = '2022-06-01';
        $arrayDatas[10]['dataFimMesAtual'] = '2022-06-30';
        $arrayDatas[11]['dataInicioMesAtual'] = '2022-07-01';
        $arrayDatas[11]['dataFimMesAtual'] = '2022-07-31';
        $arrayDatas[12]['dataInicioMesAtual'] = '2022-08-01';
        $arrayDatas[12]['dataFimMesAtual'] = '2022-08-31';
        $arrayDatas[13]['dataInicioMesAtual'] = '2022-09-01';
        $arrayDatas[13]['dataFimMesAtual'] = '2022-09-30';

        $arrayData = array();
        $i = 0;
        echo '<pre>';
        if($idLoja <> null){

            foreach($arrayDatas as $datasExec){

                $dataMesAtualInicioLoop = date_create($datasExec['dataInicioMesAtual']);
                $dataMesAtualFimLoop = date_create($datasExec['dataFimMesAtual']);

                //Tratando o mês atual
                echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês ".date_format($dataMesAtualInicioLoop, 'Y-m-d')."T00:00:00Z"." - ".date_format($dataMesAtualFimLoop, 'Y-m-d')."T23:59:59Z"." com o parâmetro $tipo\n";
                for($j=1;$dataMesAtualInicioLoop<=$dataMesAtualFimLoop;date_add($dataMesAtualInicioLoop, date_interval_create_from_date_string('1 days'))){

                    $date = new DateTime();

                    $dataInicio = date_format($dataMesAtualInicioLoop, 'Y-m-d')."T00:00:00Z";
                    $dataFim = date_format($dataMesAtualInicioLoop, 'Y-m-d')."T23:59:59Z";

                    if($tipo == "extrato"){

                        $response = $this->integration->geraextrato3($dataInicio,$dataFim,$idLoja,"PS");

                        if (!($response['httpcode'] == "200")) {  // created
                            echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
                            print_r($response);

                        } else {

                            $responseContent = json_decode($response['content'], true);

                            if(array_key_exists("payment_summaries",$responseContent)){
                                if($responseContent['payment_summaries']){
                                    foreach($responseContent['payment_summaries'] as $saidaExtrato){
                                        $arrayData[$i]['type_register'] = $saidaExtrato['type_register'];
                                        $arrayData[$i]['cpfcnpj_subseller'] = $saidaExtrato['cpfcnpj_subseller'];
                                        $arrayData[$i]['subseller_id'] = $saidaExtrato['subseller_id'];
                                        $arrayData[$i]['marketplace_subsellerid'] = $saidaExtrato['marketplace_subsellerid'];
                                        $arrayData[$i]['amount_paid'] = $saidaExtrato['amount_paid'];
                                        $arrayData[$i]['contract_number'] = $saidaExtrato['contract_number'];
                                        $arrayData[$i]['nu_liquid'] = $saidaExtrato['nu_liquid'];
                                        $arrayData[$i]['account_type'] = $saidaExtrato['account_type'];
                                        $arrayData[$i]['beneficiary_document_number'] = $saidaExtrato['beneficiary_document_number'];
                                        $arrayData[$i]['bank'] = $saidaExtrato['bank'];
                                        $arrayData[$i]['agency'] = $saidaExtrato['agency'];
                                        $arrayData[$i]['account_number'] = $saidaExtrato['account_number'];
                                        $arrayData[$i]['subseller_rate_confirm_date'] = $saidaExtrato['subseller_rate_confirm_date'];
                                        $arrayData[$i]['operation_type'] = $saidaExtrato['operation_type'];
                                        $arrayData[$i]['product_id'] = $saidaExtrato['product_id'];
                                        $arrayData[$i]['reference_number'] = $saidaExtrato['reference_number'];
                                        $i++;
                                    }
                                }
                            }

                            if(array_key_exists("list_transactions",$responseContent)){

                                foreach($responseContent['list_transactions'] as $saidaExtrato){
                                    dd($response);
                                    foreach($saidaExtrato['details'] as $saidaExtratoDetalhes){

                                        $arrayData[$i]['type_register'] = $saidaExtrato['summary']['type_register'];
                                        $arrayData[$i]['order_id'] = $saidaExtrato['summary']['order_id'];
                                        $arrayData[$i]['seller_id'] = $saidaExtrato['summary']['seller_id'];
                                        $arrayData[$i]['marketplace_subsellerid'] = $saidaExtrato['summary']['marketplace_subsellerid'];
                                        $arrayData[$i]['merchand_id'] = $saidaExtrato['summary']['merchand_id'];
                                        $arrayData[$i]['cnpj_marketplace'] = $saidaExtrato['summary']['cnpj_marketplace'];
                                        $arrayData[$i]['marketplace_transaction_id'] = $saidaExtrato['summary']['marketplace_transaction_id'];
                                        $arrayData[$i]['transaction_date'] = $saidaExtrato['summary']['transaction_date'];
                                        $arrayData[$i]['confirmation_date'] = $saidaExtrato['summary']['confirmation_date'];
                                        $arrayData[$i]['product_id'] = $saidaExtrato['summary']['product_id'];
                                        $arrayData[$i]['transaction_type'] = $saidaExtrato['summary']['transaction_type'];
                                        $arrayData[$i]['number_installments'] = $saidaExtrato['summary']['number_installments'];
                                        $arrayData[$i]['nsu_host'] = $saidaExtrato['summary']['nsu_host'];
                                        $arrayData[$i]['acquirer_transaction_id'] = $saidaExtrato['summary']['acquirer_transaction_id'];
                                        $arrayData[$i]['card_payment_amount'] = $saidaExtrato['summary']['card_payment_amount'];
                                        $arrayData[$i]['sum_details_card_payment_amount'] = $saidaExtrato['summary']['sum_details_card_payment_amount'];
                                        $arrayData[$i]['marketplace_original_transaction_id'] = $saidaExtrato['summary']['marketplace_original_transaction_id'];
                                        $arrayData[$i]['transaction_status_code'] = $saidaExtrato['summary']['transaction_status_code'];
                                        $arrayData[$i]['transaction_sign'] = $saidaExtrato['summary']['transaction_sign'];
                                        $arrayData[$i]['terminal_nsu'] = $saidaExtrato['summary']['terminal_nsu'];
                                        $arrayData[$i]['reason_message'] = $saidaExtrato['summary']['reason_message'];
                                        $arrayData[$i]['authorization_code'] = $saidaExtrato['summary']['authorization_code'];
                                        $arrayData[$i]['payment_id'] = $saidaExtrato['summary']['payment_id'];
                                        $arrayData[$i]['terminal_identification'] = $saidaExtrato['summary']['terminal_identification'];
                                        $arrayData[$i]['nsu_tef'] = $saidaExtrato['summary']['nsu_tef'];
                                        $arrayData[$i]['entry_mode'] = $saidaExtrato['summary']['entry_mode'];
                                        $arrayData[$i]['transaction_channel'] = $saidaExtrato['summary']['transaction_channel'];
                                        $arrayData[$i]['capture'] = $saidaExtrato['summary']['capture'];
                                        $arrayData[$i]['payment_tag'] = $saidaExtrato['summary']['payment_tag'];

                                        $arrayData[$i]['det_type_register'] = $saidaExtratoDetalhes['type_register'];
                                        $arrayData[$i]['bank'] = $saidaExtratoDetalhes['bank'];
                                        $arrayData[$i]['agency'] = $saidaExtratoDetalhes['agency'];
                                        $arrayData[$i]['account_number'] = $saidaExtratoDetalhes['account_number'];
                                        $arrayData[$i]['account_type'] = $saidaExtratoDetalhes['account_type'];
                                        $arrayData[$i]['marketplace_schedule_id'] = $saidaExtratoDetalhes['marketplace_schedule_id'];
                                        $arrayData[$i]['det_marketplace_subsellerid'] = $saidaExtratoDetalhes['marketplace_subsellerid'];
                                        $arrayData[$i]['nu_liquid'] = $saidaExtratoDetalhes['nu_liquid'];
                                        $arrayData[$i]['release_status'] = $saidaExtratoDetalhes['release_status'];
                                        $arrayData[$i]['det_merchand_id'] = $saidaExtratoDetalhes['merchand_id'];
                                        $arrayData[$i]['cpfcnpj_subseller'] = $saidaExtratoDetalhes['cpfcnpj_subseller'];
                                        $arrayData[$i]['cancel_custom_key'] = $saidaExtratoDetalhes['cancel_custom_key'];
                                        $arrayData[$i]['cancel_request_id'] = $saidaExtratoDetalhes['cancel_request_id'];
                                        $arrayData[$i]['det_marketplace_transaction_id'] = $saidaExtratoDetalhes['marketplace_transaction_id'];
                                        $arrayData[$i]['det_cnpj_marketplace'] = $saidaExtratoDetalhes['cnpj_marketplace'];
                                        $arrayData[$i]['det_transaction_date'] = $saidaExtratoDetalhes['transaction_date'];
                                        $arrayData[$i]['det_confirmation_date'] = $saidaExtratoDetalhes['confirmation_date'];
                                        $arrayData[$i]['item_id'] = $saidaExtratoDetalhes['item_id'];
                                        $arrayData[$i]['det_number_installments'] = $saidaExtratoDetalhes['number_installments'];
                                        $arrayData[$i]['installment'] = $saidaExtratoDetalhes['installment'];
                                        $arrayData[$i]['installment_date'] = $saidaExtratoDetalhes['installment_date'];
                                        $arrayData[$i]['installment_amount'] = $saidaExtratoDetalhes['installment_amount'];
                                        $arrayData[$i]['subseller_rate_amount'] = $saidaExtratoDetalhes['subseller_rate_amount'];
                                        $arrayData[$i]['subseller_rate_percentage'] = $saidaExtratoDetalhes['subseller_rate_percentage'];
                                        $arrayData[$i]['payment_date'] = $saidaExtratoDetalhes['payment_date'];
                                        $arrayData[$i]['subseller_rate_closing_date'] = $saidaExtratoDetalhes['subseller_rate_closing_date'];
                                        $arrayData[$i]['subseller_rate_confirm_date'] = $saidaExtratoDetalhes['subseller_rate_confirm_date'];
                                        $arrayData[$i]['subseller_id'] = $saidaExtratoDetalhes['subseller_id'];
                                        $arrayData[$i]['det_seller_id'] = $saidaExtratoDetalhes['seller_id'];
                                        $arrayData[$i]['det_transaction_sign'] = $saidaExtratoDetalhes['transaction_sign'];
                                        $arrayData[$i]['item_id_mgm'] = $saidaExtratoDetalhes['item_id_mgm'];
                                        $arrayData[$i]['det_payment_id'] = $saidaExtratoDetalhes['payment_id'];
                                        $arrayData[$i]['det_payment_tag'] = $saidaExtratoDetalhes['payment_tag'];
                                        $arrayData[$i]['item_split_tag'] = $saidaExtratoDetalhes['item_split_tag'];

                                        $i++;

                                    }

                                }
                            }

                        }

                    }elseif($tipo == "rejeitados"){
                        $dataInicio = date_format($dataMesAtualInicioLoop, 'Y-m-d');
                        $dataFim = date_format($dataMesAtualInicioLoop, 'Y-m-d');

                        $response = $this->integration->gerapagamentorejeitado($dataInicio,$dataFim,$idLoja);

                        if (!($response['httpcode'] == "200" || $response['httpcode'] == "204")) {  // created
                            echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
                            // print_r($response);

                        } else {

                            $responseContent = json_decode($response['content'], true);

                            if($responseContent){
                                foreach($responseContent as $dadosPagamento){
                                    if(array_key_exists("paymentSummaries",$dadosPagamento)){
                                        if( $dadosPagamento["subseller"]["id"] == $idLoja){
                                            // if( $dadosPagamento["subseller"]["id"] == 700484754){
                                            print_r($dadosPagamento);
                                        }
                                    }

                                }
                            }
                        }

                    }

                    $date2 = new DateTime();
                    $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                    echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";

                }

            }

            if($arrayData){
                if($arrayData){
                    echo "\n";
                    $j = 1;
                    foreach($arrayData as $dados){
                        if($j == 1){
                            echo "\n";
                            foreach($dados as $idx => $valor){
                                echo "|".$idx;
                            }

                            echo "\n";
                            foreach($dados as $idx => $valor){
                                echo "|".$valor;
                            }
                        }else{
                            echo "\n";
                            foreach($dados as $idx => $valor){
                                echo "|".$valor;
                            }
                        }
                        $j++;
                    }
                    echo "\n";
                }
            }

        }else{
            echo "[".date("Y-m-d H:i:s")."] - Nenhuma loja foi informada \n";
        }

        $dateFimJob = new DateTime();
        $diffInSecondsJobs = $dateFimJob->getTimestamp() - $dateInicioJob->getTimestamp();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job - Tempo de execução: ".$diffInSecondsJobs." segundo(s)\n";

    }

    public function montapayloadpedidos(){


        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Payload\n";

        $this->getaccesstokens();
        $contador = 0;

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'Payload';

        $conciliations[0]['conciliacao_id'] = '0';
        $conciliations[0]['data_pagamento'] = '02/12/2022';
        $conciliations[0]['status_repasse'] = '25';
        $conciliations[0]['lote'] = 'teste';

        if($conciliations){

            $variavelAmbiente = $this->model_settings->getSettingDatabyName('vtex_seller_prefix');

            foreach ($conciliations as $key => $conciliation) {

                $current_day = date("j");

                //Processando apenas pagamentos que devem ser feitos no mesmo dia ou o status do repasse for 25
                if (!($conciliation['data_pagamento'] == $current_day || $conciliation['status_repasse'] == 25)) {
                    continue;
                }

                echo "[".date("Y-m-d H:i:s")."] - Processando conciliação teste: {$conciliation['conciliacao_id']}" . PHP_EOL;


                /*$pedidosBuscar[0]['pedido'] = "1274326522674-01";
                $pedidosBuscar[0]['conciliacao'] = "34";*/

                /*$pedidosBuscar[1]['pedido'] = "1254462304565-02";
                $pedidosBuscar[1]['conciliacao'] = "32";
                $pedidosBuscar[2]['pedido'] = "1254462487921-01";
                $pedidosBuscar[2]['conciliacao'] = "32";
                $pedidosBuscar[3]['pedido'] = "1255083276150-01";
                $pedidosBuscar[3]['conciliacao'] = "32";*/

                /*$pedidosBuscar[4]['pedido'] = "1272696497466-02";
                $pedidosBuscar[4]['conciliacao'] = "34";
                $pedidosBuscar[5]['pedido'] = "1260940273875-01";
                $pedidosBuscar[5]['conciliacao'] = "31";
                $pedidosBuscar[6]['pedido'] = "1260940273875-01";
                $pedidosBuscar[6]['conciliacao'] = "34";
                $pedidosBuscar[7]['pedido'] = "1261433570806-01";
                $pedidosBuscar[7]['conciliacao'] = "31";
                $pedidosBuscar[8]['pedido'] = "1261433570806-01";
                $pedidosBuscar[8]['conciliacao'] = "34";
                $pedidosBuscar[9]['pedido'] = "1263506333638-01";
                $pedidosBuscar[9]['conciliacao'] = "32";
                $pedidosBuscar[10]['pedido'] = "1263506333638-01";
                $pedidosBuscar[10]['conciliacao'] = "33";
                $pedidosBuscar[11]['pedido'] = "1269736453050-01";
                $pedidosBuscar[11]['conciliacao'] = "33";
                $pedidosBuscar[12]['pedido'] = "1269736453050-02";
                $pedidosBuscar[12]['conciliacao'] = "33";*/


                $pedidosBuscar[10]['pedido'] = "1270466465862-01";
                $pedidosBuscar[10]['conciliacao'] = "34";

                $pedidosBuscar[11]['pedido'] = "1270556467909-02";
                $pedidosBuscar[11]['conciliacao'] = "34";

                $pedidosBuscar[12]['pedido'] = "1271056474984-01";
                $pedidosBuscar[12]['conciliacao'] = "34";

                $i = 0;
                //Busca as linhas que serão pagas ou ajustadas
                foreach($pedidosBuscar as $pedidoBuscar){
                    $transferencias[$i] = $this->model_transfer->getTransfersConciliacaoPedido($pedidoBuscar['pedido'],$pedidoBuscar['conciliacao']);
                    $i++;
                }

                foreach($transferencias as $transferencia){

                    //Busca dados da conta
                    $store = $this->model_stores->getStoreById($transferencia['store_id']);
                    $subaccount = $this->model_gateway->getSubAccountsInformationsGetnet($transferencia['store_id']);

                    // Print Payload ajuste negativo
                    if($transferencia['repasse_tratado'] < 0){

                        //Chama API de Ajuste para descontar do seller
                        echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $transferencia['repasse_tratado']. PHP_EOL;

                        $motivo = "Ajuste de pedido descontado por ajuste ".$transferencia['numero_marketplace'];
                        $response = array();
                        $response = $this->integration->geraajustesPayload($store, $subaccount, str_replace("-","",$transferencia['repasse_tratado']),2,$motivo);

                    }else{
                        //Chama API de transferencias para repassar do seller

                        $dadosTransacao = $this->model_orders_payment->getByOrderId($transferencia['order_id']);
                        $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($transferencia['numero_marketplace_buscatrt']);

                        if(!$dadosExtrato){
                            $transactionArray = $this->model_transfer->getOrdersTransactionId($transferencia['order_id']);
                            if($transactionArray){
                                $dadosExtrato = $this->model_gateway->getExtratoGetnetORders($transactionArray[0]['transaction_id']);
                            }
                        }

                        $dadosPedidos = $this->model_orders->getOrdersData(0,$transferencia['order_id']);

                        if($dadosExtrato){

                            $encontrou = false;
                            foreach($dadosExtrato as $item){

                                $jsonArray = json_decode($item['json_retorno']);

                                $idVtex = "";

                                if($transferencia['store_id'] < 10){
                                    if($variavelAmbiente['status'] == "1"){
                                        $idVtex = $variavelAmbiente['value']."00".$transferencia['store_id'];
                                    }else{
                                        $idVtex = "00".$transferencia['store_id'];
                                    }
                                }elseif($transferencia['store_id'] < 100){
                                    if($variavelAmbiente['status'] == "1"){
                                        $idVtex = $variavelAmbiente['value']."0".$transferencia['store_id'];
                                    }else{
                                        $idVtex = "0".$transferencia['store_id'];
                                    }
                                }else{
                                    if($variavelAmbiente['status'] == "1"){
                                        if($variavelAmbiente['status'] == "1"){
                                            $idVtex = $variavelAmbiente['value']."".$transferencia['store_id'];
                                        }else{
                                            $idVtex = "".$transferencia['store_id'];
                                        }
                                    }
                                }

                                $idVtex = preg_replace("/[^A-Za-z0-9 ]/", '', $idVtex);

                                if($idVtex == $item['item_id']){

                                    $valorDiferencaRepasse = 0;
                                    $valorDiferencaRepasse = round( $transferencia['repasse_tratado'] - ($item['subseller_rate_amount']/100) ,2);

                                    echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;

                                    $encontrou = true;
                                    $dadosTransferencia['item_id'] = $item['item_id'];
                                    $dadosTransferencia['installment_amount'] = $item['installment_amount'];

                                    if(strtoupper(str_replace("-","",$jsonArray->summary->payment_id))){
                                        $dadosTransferencia['payment_id'] =  strtoupper(str_replace("-","",$jsonArray->summary->payment_id));
                                    }else{
                                        $dadosTransferencia['payment_id'] = $dadosTransacao[0]['payment_id'];
                                    }

                                    $response = array();
                                    $response = $this->integration->gerapagamentoPayload($dadosTransferencia);


                                    if($valorDiferencaRepasse > 1){

                                        $motivo = "Ajuste de saldo pós liberação do pedido".$transferencia['numero_marketplace'];

                                        $response = array();
                                        $response = $this->integration->geraajustesPayload($store, $subaccount, str_replace("-","",$valorDiferencaRepasse),1,$motivo);


                                    }
                                }

                            }

                        }else{

                            echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." NÃO encontrado no extrato getnet ". $transferencia['repasse_tratado']. PHP_EOL;

                            // Paga o pedido que não consta no extrato da getnet
                            // Chama API de Ajuste para descontar do seller
                            $motivo = "Ajuste de pedido pago fora do conector ".$transferencia['numero_marketplace'];

                            $response = array();
                            $response = $this->integration->geraajustesPayload($store, $subaccount, str_replace("-","",$transferencia['repasse_tratado']),1,$motivo);

                        }


                    }

                    $contador++;
                    if($contador == "200"){
                        $this->getaccesstokens();
                        $contador = 0;
                    }

                }

            }

        }else{
            echo "[".date("Y-m-d H:i:s")."] - Nenhuma conciliação para efetuar\n";
        }
        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";

    }


	public function gerasaldossubcontasgetnet($jobId = null, $storeId = null, $startDate = null, $endDate = null){

        $this->startJob(__FUNCTION__, $jobId);
        error_reporting(E_ALL ^ E_NOTICE);  
        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job Saldos Subcontas Getnet\n";
        $gateway_name = Model_gateway::GETNET;

        $this->getaccesstokens();

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'gerasaldossubcontasgetnet';

        $stores = $this->model_stores->getStoresSubAccountsGetnet($storeId);

        if($startDate == null){
            $startDate = date("Y-m-d");
        }
        
        if($endDate == null){
            $endDate = date("Y-m-d",strtotime('+30 days',strtotime(date("Y-m-d"))));
        }
        
        foreach($stores as $store){

            $response = $this->integration->gerasaldossubcontasgetnet($store["subseller_id"],$startDate, $endDate);

            $responseContent = json_decode($response['content']);
           // $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (!($response['httpcode'] == "200")) {  // created

                echo "[".date("Y-m-d H:i:s")."] - Loja ".$store["name"]." - ".$store["store_id"]." não atualizada com erro:  ".$responseContent->message."\n";

            } else {

                //Limpa os saldos antes de inserir os novos
                $this->model_payment->deletegetnetsaldos($store["subseller_id"]);

                $responseContent = json_decode($response['content'], true);

                if(is_array($responseContent["balances"]["current_balances"][0]["balances"])){
                    
                    foreach($responseContent["balances"]["current_balances"][0]["balances"] as $saidaExtratoSaldo){
                        $arrayDados = array();

                        $arrayDados['tipo_saldo'] = 'Saldo Atual';
                        $arrayDados['store_id'] = $store['store_id'];
                        $arrayDados['subseller_id'] = $store['subseller_id'];
                        $arrayDados['data_saldo'] = $saidaExtratoSaldo["balance_date"];
                        $arrayDados['valor_disponivel'] = $saidaExtratoSaldo["amount_paid"];

                        $insereatualizasaldogetnet = $this->model_payment->insertupdategetnetsaldos($arrayDados);
                        
                        if($insereatualizasaldogetnet){
                            echo "[".date("Y-m-d H:i:s")."] - Loja ".$store["name"]." - ".$store["store_id"]." atualizada com sucesso: ".$saidaExtratoSaldo["balance_date"]." - ".$saidaExtratoSaldo["amount_paid"]."\n";
                        }else{
                            echo "[".date("Y-m-d H:i:s")."] - Loja ".$store["name"]." - ".$store["store_id"]." erro ao atualizar: ".$saidaExtratoSaldo["balance_date"]." - ".$saidaExtratoSaldo["amount_paid"]."\n";
                        }

                    }


                }   

                if(is_array($responseContent["balances"]["future_balances"][0]["balances"])){
                        foreach($responseContent["balances"]["future_balances"][0]["balances"] as $saidaExtratoSaldo){
                            $arrayDados = array();

                            $arrayDados['tipo_saldo'] = 'Saldo Futuro';
                            $arrayDados['store_id'] = $store['store_id'];
                            $arrayDados['subseller_id'] = $store['subseller_id'];
                            $arrayDados['data_saldo'] = $saidaExtratoSaldo["balance_date"];
                            $arrayDados['valor_disponivel'] = $saidaExtratoSaldo["released_balances"]["sale_value"];

                            $insereatualizasaldogetnet = $this->model_payment->insertupdategetnetsaldos($arrayDados);
                            
                            if($insereatualizasaldogetnet){
                                echo "[".date("Y-m-d H:i:s")."] - Loja ".$store["name"]." - ".$store["store_id"]." atualizada com sucesso: ".$saidaExtratoSaldo["balance_date"]." - ".$saidaExtratoSaldo["released_balances"]["sale_value"]."\n";
                            }else{
                                echo "[".date("Y-m-d H:i:s")."] - Loja ".$store["name"]." - ".$store["store_id"]." erro ao atualizar: ".$saidaExtratoSaldo["balance_date"]." - ".$saidaExtratoSaldo["released_balances"]["sale_value"]."\n";
                            }

                        }
                }

            }

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job  Saldos Subcontas Getnet\n";
        $this->endJob();
    }
    
    public function  tratapedidosextratogetnetnopedidovtex(){

        echo "[".date("Y-m-d H:i:s")."] - Início do Job tratapedidosextratogetnetnopedido\n";
        echo "[".date("Y-m-d H:i:s")."] - Buscando Pedidos a serem tratdos\n";

        $sql = "SELECT ge.id, ge.order_id_json, gs.store_id FROM getnet_extrato ge
        inner join getnet_subaccount gs on gs.subseller_id  = ge.seller_id_json
        where ge.item_id not like 'decathlon%' and ge.order_id is null";
        $query = $this->db->query($sql);
        $resultado =  $query->result_array();
        $indice = 0;
        $arrayQuerys = array();
        
        foreach($resultado as $pedidoGetnet){

            $sql2 = "select o.id, o.numero_marketplace, op.transaction_id from orders o
            inner join orders_payment op on op.order_id = o.id
            where (op.transaction_id  = '".$pedidoGetnet["order_id_json"]."' or left(o.numero_marketplace,13) = '".$pedidoGetnet["order_id_json"]."') and store_id = ".$pedidoGetnet["store_id"];
           
            $query2 = $this->db->query($sql2);
            $result =  $query2->row();
            
            if($result){
                $arrayQuerys[$indice] = "update getnet_extrato set numero_marketplace ='".$result->numero_marketplace."', order_id = ".$result->id ." where id = ".$pedidoGetnet["id"].";\n";
                $indice++;
                $sql3 = "update getnet_extrato set numero_marketplace = ?, order_id = ? where id = ?";

		        $update = $this->db->query($sql3, [$result->numero_marketplace, $result->id, $pedidoGetnet["id"]]);
                if($update){
                    echo "[".date("Y-m-d H:i:s")."] - "."Atualizado o pedido ".$result->numero_marketplace." com sucesso \n";
                }

            }else{

                $arrayQuerys[$indice] = "update getnet_extrato set numero_marketplace = 0, order_id = 0 where id = ".$pedidoGetnet["id"].";\n";
                $indice++;

                $sql3 = "update getnet_extrato set numero_marketplace = ?, order_id = ? where id = ?";

		        $update = $this->db->query($sql3, [0, 0, $pedidoGetnet["id"]]);
                if($update){
                    echo "[".date("Y-m-d H:i:s")."] - "."Atualizado o extrato ".$pedidoGetnet["id"]." com sucesso para 0 pois não encontramos os pedido associados a pedido extrato\n";
                }

            }

        }

        echo "[".date("Y-m-d H:i:s")."] - Buscando Pedidos iguais que estão duplicados (por erro na getnet) e retirando o MENOR id para 0\n";

        $sql10 = "select order_id, numero_marketplace, order_id_json, item_id, transaction_sign, installment_amount  , min(id) as id, count(*) from getnet_extrato ge
                    where order_id is not null
                    group by order_id, numero_marketplace, order_id_json, item_id, transaction_sign, installment_amount  
                    having count(*) > 1";
        $query10 = $this->db->query($sql10);
        $resultado10 =  $query10->result_array();
        $indice10 = 0;
        $arrayQuerys10 = array();
        
        foreach($resultado10 as $pedidoGetnet10){

            $arrayQuerys10[$indice10] = "update getnet_extrato set numero_marketplace = 0, order_id = 0 where id = ".$pedidoGetnet10["id"].";\n";
            $indice10++;

            $sql11 = "update getnet_extrato set numero_marketplace = ?, order_id = ? where id = ?";

            $update10 = $this->db->query($sql11, [0, 0, $pedidoGetnet10["id"]]);
            $update10 = true;
            if($update10){
                echo "[".date("Y-m-d H:i:s")."] - "."Atualizado o extrato ".$pedidoGetnet10["id"]." com sucesso para 0 pois estava duplicado no id conecta\n";
            }

        }

        foreach($arrayQuerys as $queries){
            echo $queries;
        }

        foreach($arrayQuerys10 as $queries10){
            echo $queries10;
        }

        echo "[".date("Y-m-d H:i:s")."] - Total Pedidos atualizados em tratamento: $indice\n";
        echo "[".date("Y-m-d H:i:s")."] - Total Pedidos atualizados que estavam duplicados: $indice10\n";

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job tratapedidosextratogetnetnopedido\n";
        
    }

    public function  tratapedidosextratogetnetnopedidoajustevtex(){

        echo "[".date("Y-m-d H:i:s")."]"." - Início do Job tratapedidosextratogetnetnopedido\n";
        echo "[".date("Y-m-d H:i:s")."]"." - Buscando Pedidos a serem tratdos\n";

        $sql = "SELECT ge.id, ge.adjustment_reason as order_id_json , gs.store_id FROM getnet_extrato_ajustes ge
        inner join getnet_subaccount gs on gs.subseller_id  = ge.subseller_id
        where  ge.order_id is null";
        $query = $this->db->query($sql);
        $resultado =  $query->result_array();
        $indice = 0;
        $arrayQuerys = array();
        
        foreach($resultado as $pedidoGetnet){

            $sql2 = "select o.id, o.numero_marketplace, op.transaction_id from orders o
            inner join orders_payment op on op.order_id = o.id
            where ('".$pedidoGetnet["order_id_json"]."' like concat('%',op.transaction_id,'%')  or '".$pedidoGetnet["order_id_json"]."' like concat('%',left(o.numero_marketplace,13),'%') ) and store_id = ".$pedidoGetnet["store_id"];
            // $sql2 = "select o.id, o.numero_marketplace, op.transaction_id from orders o inner join orders_payment op on op.order_id = o.id where o.id = 1";

            $query2 = $this->db->query($sql2);
            $result =  $query2->row();
            
            if($result){
                $arrayQuerys[$indice] = "update getnet_extrato_ajustes set numero_marketplace ='".$result->numero_marketplace."', order_id = ".$result->id ." where id = ".$pedidoGetnet["id"].";\n";
                $indice++;
                $sql3 = "update getnet_extrato_ajustes set numero_marketplace = ?, order_id = ? where id = ?";

		        $update = $this->db->query($sql3, [$result->numero_marketplace, $result->id, $pedidoGetnet["id"]]);
                if($update){
                    echo "[".date("Y-m-d H:i:s")."] - "."Atualizado o pedido ".$result->numero_marketplace." com sucesso \n";
                }

            }else{

                $arrayQuerys[$indice] = "update getnet_extrato_ajustes set numero_marketplace = 0, order_id = 0 where id = ".$pedidoGetnet["id"].";\n";
                $indice++;

                $sql3 = "update getnet_extrato_ajustes set numero_marketplace = ?, order_id = ? where id = ?";

		        $update = $this->db->query($sql3, [0, 0, $pedidoGetnet["id"]]);
                if($update){
                    echo "[".date("Y-m-d H:i:s")."] - "."Atualizado o ajuste ".$pedidoGetnet["id"]." com sucesso para 0 pois não encontramos os lançamento associados a pedido\n";
                }

            }

        }

        foreach($arrayQuerys as $queries){
            echo $queries;
        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job tratapedidosextratogetnetnopedido\n";
        
    }


    public function  tratapedidosextratogetnetnopedidooracle(){

        echo "[".date("Y-m-d H:i:s")."] - Início do Job tratapedidosextratogetnetnopedidoOracle\n";
        echo "[".date("Y-m-d H:i:s")."] - Buscando Pedidos a serem tratdos\n";

        $sql = "SELECT ge.id, ge.order_id_json, gs.store_id, ge.item_id FROM getnet_extrato ge
                inner join getnet_subaccount gs on gs.subseller_id  = ge.seller_id_json
                where ge.item_id not like 'decathlon%' and ge.order_id is null
                order by ge.id";

        $query = $this->db->query($sql);
        $resultado =  $query->result_array();
        $indice = 0;
        $arrayQuerys = array();
        
        foreach($resultado as $pedidoGetnet){

            $sql2 = "select o.id, o.numero_marketplace, op.transaction_id from orders o
            inner join orders_payment op on op.order_id = o.id
            inner join orders_item oi on oi.order_id = o.id
            where SUBSTRING(o.numero_marketplace, 1, position(\"-\" in o.numero_marketplace)-1) = '".$pedidoGetnet["order_id_json"]."' and oi.skumkt = '".$pedidoGetnet["item_id"]."'";

            $query2 = $this->db->query($sql2);
            $result =  $query2->row();
            
            if($result){
                $arrayQuerys[$indice] = "update getnet_extrato set numero_marketplace ='".$result->numero_marketplace."', order_id = ".$result->id ." where id = ".$pedidoGetnet["id"].";\n";
                $indice++;
                $sql3 = "update getnet_extrato set numero_marketplace = ?, order_id = ? where id = ?";

		        $update = $this->db->query($sql3, [$result->numero_marketplace, $result->id, $pedidoGetnet["id"]]);
                if($update){
                    echo "[".date("Y-m-d H:i:s")."] - "."Atualizado o pedido ".$result->numero_marketplace." com sucesso \n";
                }

            }

        }

        foreach($arrayQuerys as $queries){
            echo $queries;
        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job tratapedidosextratogetnetnopedidoOracle\n";
        
    }
    
    public function  tratapedidosextratogetnetnopedidoajusteoracle(){

        echo "[".date("Y-m-d H:i:s")."]"." - Início do Job tratapedidosextratogetnetnopedido\n";
        echo "[".date("Y-m-d H:i:s")."]"." - Buscando Pedidos a serem tratdos\n";

        $sql = "SELECT ge.id, ge.adjustment_reason as order_id_json , gs.store_id FROM getnet_extrato_ajustes ge
        inner join getnet_subaccount gs on gs.subseller_id  = ge.subseller_id
        where  ge.order_id is null";
        $query = $this->db->query($sql);
        $resultado =  $query->result_array();
        $indice = 0;
        $arrayQuerys = array();
        
        foreach($resultado as $pedidoGetnet){

            $sql2 = "select o.id, o.numero_marketplace, op.transaction_id from orders o
            inner join orders_payment op on op.order_id = o.id
            where ('".$pedidoGetnet["order_id_json"]."' like concat('%',op.transaction_id,'%')  or '".$pedidoGetnet["order_id_json"]."' like concat('%',left(o.numero_marketplace,13),'%') ) and store_id = ".$pedidoGetnet["store_id"];
            // $sql2 = "select o.id, o.numero_marketplace, op.transaction_id from orders o inner join orders_payment op on op.order_id = o.id where o.id = 1";

            $query2 = $this->db->query($sql2);
            $result =  $query2->row();
            
            if($result){
                $arrayQuerys[$indice] = "update getnet_extrato_ajustes set numero_marketplace ='".$result->numero_marketplace."', order_id = ".$result->id ." where id = ".$pedidoGetnet["id"].";\n";
                $indice++;
                $sql3 = "update getnet_extrato_ajustes set numero_marketplace = ?, order_id = ? where id = ?";

		        $update = $this->db->query($sql3, [$result->numero_marketplace, $result->id, $pedidoGetnet["id"]]);
                if($update){
                    echo "[".date("Y-m-d H:i:s")."] - "."Atualizado o pedido ".$result->numero_marketplace." com sucesso \n";
                }

            }else{

                $arrayQuerys[$indice] = "update getnet_extrato_ajustes set numero_marketplace = 0, order_id = 0 where id = ".$pedidoGetnet["id"].";\n";
                $indice++;

                $sql3 = "update getnet_extrato_ajustes set numero_marketplace = ?, order_id = ? where id = ?";

		        $update = $this->db->query($sql3, [0, 0, $pedidoGetnet["id"]]);
                if($update){
                    echo "[".date("Y-m-d H:i:s")."] - "."Atualizado o ajuste ".$pedidoGetnet["id"]." com sucesso para 0 pois não encontramos os lançamento associados a pedido\n";
                }

            }

        }

        foreach($arrayQuerys as $queries){
            echo $queries;
        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job tratapedidosextratogetnetnopedido\n";
        
    }

    public function  tratatabelagetnetextratogatilho(){

        echo "[".date("Y-m-d H:i:s")."] - Início do Job tratatabelagetnetextratogatilho\n";
        echo "[".date("Y-m-d H:i:s")."] - Limpando a tabela atual Pedidos a serem tratdos\n";

        $sql = "truncate table getnet_gatilho_lancamento";

        $turuncate = $this->db->query($sql);

        if($turuncate){

            echo "[".date("Y-m-d H:i:s")."] - "."Sucesso ao truncar a tabela getnet_gatilho_lancamento\n";
            echo "[".date("Y-m-d H:i:s")."] - "."Inserindo o extrato na tabela getnet_gatilho_lancamento\n";
            
            $sql2 = "insert into getnet_gatilho_lancamento(order_id, numero_marketplace, tipo_lancamento, id_lancamento, status_liberacao, data_lancamento, valor_lancamento, reference_number, nu_liquid)
                    select ge.order_id , ge.numero_marketplace , 'Liberação' as tipo_lancamento, ge.chave_md5 , ge.release_status , 
                    case when gp.reference_number is not null then gp.subseller_rate_confirm_date else null end as subseller_rate_confirm_date, 
                    case when ge.transaction_sign = '-' then round(ge.subseller_rate_amount*-1 / 100,2) else round(ge.subseller_rate_amount / 100,2) end as valor_lancamento,
                    ge.reference_number , gp.nu_liquid
                    from getnet_extrato ge
                    left join getnet_payment gp on gp.reference_number = ge.reference_number 
                    where ge.order_id is not null and ge.order_id <> 0";

            $insertextrato = $this->db->query($sql2);

            if($insertextrato){

                echo "[".date("Y-m-d H:i:s")."] - "."Sucesso ao inserir o extrato na tabela getnet_gatilho_lancamento\n";
                echo "[".date("Y-m-d H:i:s")."] - "."Inserindo o ajuste na tabela getnet_gatilho_lancamento\n";

                $sql3 = "insert into getnet_gatilho_lancamento(order_id, numero_marketplace, tipo_lancamento, id_lancamento, status_liberacao, data_lancamento, valor_lancamento, reference_number, nu_liquid )
                        select ge.order_id , ge.numero_marketplace , 'Ajuste' as tipo_lancamento, ge.chave_md5 , 'S' as release_status , 
                        case when gp.reference_number is not null then gp.subseller_rate_confirm_date else null end as subseller_rate_confirm_date,  
                        case when ge.transaction_sign = '-' then round(ge.adjustment_amount*-1 / 100,2) else round(ge.adjustment_amount / 100,2) end as valor_lancamento,
                        gp.reference_number, gp.nu_liquid 
                        from getnet_extrato_ajustes ge
                        left join getnet_payment gp on gp.reference_number = ge.reference_number 
                        where ge.order_id is not null and ge.order_id <> 0 and ge.cpfcnpj_subseller <> '2314041003870'";

                $insertajuste = $this->db->query($sql3);

                if($insertajuste){
                    echo "[".date("Y-m-d H:i:s")."] - "."Sucesso ao inserir o ajuste na tabela getnet_gatilho_lancamento\n";
                }else{
                    echo "[".date("Y-m-d H:i:s")."] - "."Erro ao inserir o ajuste na tabela getnet_gatilho_lancamento\n";
                }

            }else{
                echo "[".date("Y-m-d H:i:s")."] - "."Erro ao inserir o extrato na tabela getnet_gatilho_lancamento\n";
            }

        }else{
            echo "[".date("Y-m-d H:i:s")."] - "."Erro ao truncar a tabela getnet_gatilho_lancamento\n";
        }
        
        echo "[".date("Y-m-d H:i:s")."] - Fim do Job tratatabelagetnetextratogatilho\n";
        
    }

    public function geramdrpontual(){

        $dateInicioJob = new DateTime();
        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job geramdrpontual\n";
        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'geramdrpontual';
        $this->getaccesstokens();

        echo "[".date("Y-m-d H:i:s")."] - Buscando pedidos com nulo\n";
        
        $pedidosExts = $this->pedidosaprocessar();
        
        $contador = 0;
        $indice = 1;
        $arraySaida = array();
        $arraySaida[0] = "| Numero Do Pedido | MDR |";
        foreach($pedidosExts as $pedidosExt){
            
            $pedido = $this->model_payment->buscapedidosparamdr($pedidosExt);

            if(!$pedido){
                $arraySaida[$indice] = "| ".$pedido['numero_marketplace']." | Não Encontrado |";
                $indice++;
                continue;
            }
            
            $date = new DateTime();

            //Tratando o mês anterior
            echo "[".date("Y-m-d H:i:s")."] - Buscando dados do pedido ".$pedido['id']." - ".$pedido['numero_marketplace']."\n";
            $dataInicio = "";
            $dataFim = "";

            $response = $this->integration->geraextratoporpedido($pedido['numero_marketplace_api']);

            if (!($response['httpcode'] == "200")) {  // created

                $responseContent = json_decode($response['content']);
                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                $msg = "Erro ao puxar extrato na getnet: "
                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                    "Resposta da Getnet: " . PHP_EOL
                    . $responseContent . ' ' . PHP_EOL ;

                $arraySaida[$indice] = "| ".$pedido['numero_marketplace']." | Erro ao puxar |";
                
            } else {

                $responseContent = json_decode($response['content'], true);

                if($responseContent['list_transactions']){
                    if($responseContent['commissions']){

                        $checkGetnet = false;
                        foreach($responseContent['commissions'] as $saidaExtrato){

                            if($saidaExtrato['item_id'] == $pedido["idVtex"]){
                                $checkGetnet = true;
                                $arraySaida[$indice] = "| ".$pedido['numero_marketplace']." | ".round($saidaExtrato['mdr_rate_ammount'],2)." |";
                                echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso\n";
                            }
                            
                        }

                        if($checkGetnet == false){
                            $arraySaida[$indice] = "| ".$pedido['numero_marketplace']." | 0.0 |";
                            echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso para zero\n";
                        }

                    }else{

                        $arraySaida[$indice] = "| ".$pedido['numero_marketplace']." | 0.0 |";
                        echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso para zero\n";
                        
                    }
                }else{

                    $response2 = $this->integration->geraextratoporpedido($pedido['transaction_id']);

                    if (!($response2['httpcode'] == "200")) {  // created

                        $responseContent = json_decode($response2['content']);
                        $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        $arraySaida[$indice] = "| ".$pedido['numero_marketplace']." | Erro ao puxar |";

                        echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato mdr do pedido ".$pedido['id']." - ".$pedido['numero_marketplace']."\n";

                    } else {

                        $responseContent = json_decode($response2['content'], true);

                        if($responseContent['list_transactions']){

                            if($responseContent['commissions']){
                                $checkGetnet = false;
                                foreach($responseContent['commissions'] as $saidaExtrato){

                                    if($saidaExtrato['item_id'] == $pedido["idVtex"]){
                                        $checkGetnet = true;
                                        $arraySaida[$indice] = "| ".$pedido['numero_marketplace']." | ".round($saidaExtrato['mdr_rate_ammount']/100,2)." |";
                                        echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso\n";
                                    }
                                    
                                }
        
                                if($checkGetnet == false){
                                    $arraySaida[$indice] = "| ".$pedido['numero_marketplace']." | 0.0 |";
                                    echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso para zero\n";
                                }

                            }else{
                                
                                $arraySaida[$indice] = "| ".$pedido['numero_marketplace']." | 0.0 |";
                                
                                echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." atualizado com sucesso para zero\n";
                            }

                        }
                    }

                }

            }

            $contador++;
            $indice++;

            if($contador == "200"){
                $contador = 0;
                $this->getaccesstokens();
            }

            $date2 = new DateTime();
            $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();
            echo "[".date("Y-m-d H:i:s")."] - Pedido ".$pedido['id']." - ".$pedido['numero_marketplace']." tratado em ".$diffInSeconds." segundo(s) \n";

        }

        // $this->db->trans_commit();

        $dateFimJob = new DateTime();
        $diffInSecondsJobs = $dateFimJob->getTimestamp() - $dateInicioJob->getTimestamp();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job geramdrv3 - Tempo de execução: ".$diffInSecondsJobs." segundo(s)\n";

        echo "\n";
        foreach($arraySaida as $resultadoFInal){
            echo $resultadoFInal."\n";
        }
        die;


    }

    public function pedidosaprocessar(){

       // $arrayPedido[1] = '1325027298579-01';

        return $arrayPedido;


    }

    public function extractpaymentgetnet($responseContent){

        foreach($responseContent as $saidaPagamentos){
            
            $data = array(
                "type_register" => $saidaPagamentos['type_register'],
                "cpfcnpj_subseller" => $saidaPagamentos['cpfcnpj_subseller'],
                "subseller_id" => $saidaPagamentos['subseller_id'],
                "marketplace_subsellerid" => $saidaPagamentos['marketplace_subsellerid'],
                "amount_paid" => $saidaPagamentos['amount_paid'],
                "contract_number" => $saidaPagamentos['contract_number'],
                "nu_liquid" => $saidaPagamentos['nu_liquid'],
                "account_type" => $saidaPagamentos['account_type'],
                "beneficiary_document_number" => $saidaPagamentos['beneficiary_document_number'],
                "bank" => $saidaPagamentos['bank'],
                "agency" => $saidaPagamentos['agency'],
                "account_number" => $saidaPagamentos['account_number'],
                "subseller_rate_confirm_date" => $saidaPagamentos['subseller_rate_confirm_date'],
                "operation_type" => $saidaPagamentos['operation_type'],
                "product_id" => $saidaPagamentos['product_id'],
                "reference_number" => $saidaPagamentos['reference_number'],
                "json_retorno" => json_encode($saidaPagamentos, JSON_UNESCAPED_UNICODE),
                
            );
            
            $retorno = $this->model_gateway->saveextractgetnetpayment($data);
            
            if(!$retorno){
                echo "[".date("Y-m-d H:i:s")."] - Item pedido Extrato atualizado com erro - ".$saidaExtrato['summary']['order_id']."\n";
            }

        }

    }

    public function  gerapagamentozemaseparado($conciliacao_id){

        echo "[".date("Y-m-d H:i:s")."] - Início do Job gerapagamentozemaseparado\n";
        echo "[".date("Y-m-d H:i:s")."] - Buscando Pedidos a serem tratdos\n";
        echo "[".date("Y-m-d H:i:s")."] - Processando conciliação: {$conciliacao_id}" . PHP_EOL;
        $this->getaccesstokens();

        $sql = "select c.id, o.date_time, o.numero_marketplace, c.lote,o.id as order_id, o.bill_no, oi.skumkt, op.payment_id, gs.subseller_id,ge.installment_amount,oi.skumkt, o.store_id
                from conciliacao_sellercenter cs 
                inner join conciliacao c on c.lote = cs.lote 
                inner join orders o on o.id = cs.order_id
                inner join orders_item oi on oi.order_id = o.id
                inner join getnet_subaccount gs on gs.store_id = cs.store_id 
                inner join orders_payment op on op.order_id = o.id
                left join getnet_extrato ge on ge.order_id_json  = o.bill_no and ge.item_id = oi.skumkt 
                where c.id in (?) and cs.store_id <> 1 and status_conciliacao = 'Conciliação Ciclo'
                order by c.id, o.id";
       
        $query = $this->db->query($sql, array($conciliacao_id));
        $resultado =  $query->result_array();
        $indice = 0;
        $arrayQuerys = array();
        
        foreach($resultado as $pedidoGetnet){

            echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $pedidoGetnet['numero_marketplace'] ." encontrado no extrato getnet valor: R$ ". round($pedidoGetnet['installment_amount']/100,2). PHP_EOL;

            if($pedidoGetnet['skumkt'] == null || $pedidoGetnet['installment_amount'] == null || $pedidoGetnet['subseller_id'] == null || $pedidoGetnet['payment_id'] == null ){ 
                echo "[".date("Y-m-d H:i:s")."] - Erro ao liberar o Pedido ". $pedidoGetnet['numero_marketplace'] ." encontrado no extrato getnet POIS OS CAMPOS NÃO ESTÃO VALIDOS valor: R$ ". round($pedidoGetnet['installment_amount']/100,2). PHP_EOL;
                continue;

            }
           
            $dadosTransferencia['item_id'] = $pedidoGetnet['skumkt'];
            $dadosTransferencia['installment_amount'] = $pedidoGetnet['installment_amount'];
            $dadosTransferencia['subseller_id'] = $pedidoGetnet['subseller_id'];
            $dadosTransferencia['payment_id'] = $pedidoGetnet['payment_id'];

            $response = $this->integration->gerapagamentooracle($dadosTransferencia);
            print_r($response);

            if (!($response['httpcode'] == "200")) {  // created

                $responseContent = json_decode($response['content']);
                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                $msg = "Erro ao pagar item ao seller na getnet: " . $dadosTransferencia['item_id'] . " - " . $dadosTransferencia['installment_amount']
                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                    "Resposta da Getnet: " . PHP_EOL
                    . $responseContent . ' ' . PHP_EOL ;

                // $this->log_data('batch', $log_name, $msg, "E");

                echo "[".date("Y-m-d H:i:s")."] - Erro ao liberar o Pedido ". $pedidoGetnet['numero_marketplace'] ." encontrado no extrato getnet valor: R$ ". round($pedidoGetnet['installment_amount']/100,2). PHP_EOL;

            }else{

                $responseContent = json_decode($response['content']);
                $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                $msg = "Sucesso ao pagar item ao seller na getnet: " . $pedidoGetnet['store_id']
                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                    "Resposta da Getnet: " . PHP_EOL
                    . $responseContent . ' ' . PHP_EOL ;

                // $this->log_data('batch', $log_name, $msg, "S");

                $dadosIuguRepasse['order_id'] = $pedidoGetnet['order_id'];
                $dadosIuguRepasse['numero_marketplace'] = $pedidoGetnet['numero_marketplace'];
                $dadosIuguRepasse['data_split'] = $pedidoGetnet['date_time'];
                $dadosIuguRepasse['valor_parceiro'] = $pedidoGetnet['installment_amount']/100;
                $dadosIuguRepasse['conciliacao_id'] = $pedidoGetnet['id'];

                $iugu_repasse = $this->model_repasse->saveStatement($dadosIuguRepasse);

                print_r($iugu_repasse);

                echo "[".date("Y-m-d H:i:s")."] - Sucesso ao liberar o Pedido ". $pedidoGetnet['numero_marketplace'] ." encontrado no extrato getnet valor: R$ ". round($pedidoGetnet['installment_amount']/100,2). PHP_EOL;

            }

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job gerapagamentozemaseparado\n";
        
    }

    public function relatorioconsilidadovtex($id = null,$data = null){
        $this->startJob(__FUNCTION__, $id);

        if($data == null || $data == 'null'){
            $data = date('Y-m-01');
            $data = strtotime($data);
            $data = strtotime("-1 months", $data);
            $data = date('Y-m-01',$data);
        }

        echo "[".date("Y-m-d H:i:s")."] - Iniício do Job relatorioconsilidadovtex\n";

        //Chama a função getnet para atualização do extrato
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função de extrato Getnet -  Extrato\n";
        $this->testeextratocompleto('extrato', $data);
          
        //Chama a função getnet para atualização do ajuste
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função de extrato Getnet -  Extrato\n";
        $this->testeextratocompleto('ajuste', $data);

        //Chama a função getnet para atualização do pagamento
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função de extrato Getnet -  Extrato\n";
        $this->testeextratocompleto('pagamento', $data);
        
        //Chama a função para tratamento da tabela extrato getnet - vtex
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função para tratamento da tabela de extrato vtex\n";
        $this->tratapedidosextratogetnetnopedidovtex();
        
        //Chama a função para tratamento da tabela ajuste getnet - vtex
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função para tratamento da tabela de ajuste vtex\n";
        $this->tratapedidosextratogetnetnopedidoajustevtex();

        //Chama a função para tratamento da tabela ajuste getnet - vtex
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função para tratamento da tabela consolidada\n";
        $this->tratatabelagetnetextratogatilho();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job relatorioconsilidadovtex\n";

        $this->endJob();

    }

    public function relatorioconsilidadooracle($id = null,$data = null){
        $this->startJob(__FUNCTION__, $id);
        if($data == null || $data == 'null'){
            $data = date('Y-m-01');
            $data = strtotime($data);
            $data = strtotime("-1 months", $data);
            $data = date('Y-m-01',$data);
        }

        echo "[".date("Y-m-d H:i:s")."] - Iniício do Job relatorioconsilidadooracle\n";

        //Chama a função getnet para atualização do extrato
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função de extrato Getnet -  Extrato\n";
        $this->testeextratocompleto('extrato', $data);
          
        //Chama a função getnet para atualização do ajuste
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função de extrato Getnet -  Extrato\n";
        $this->testeextratocompleto('ajuste', $data);

        //Chama a função getnet para atualização do pagamento
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função de extrato Getnet -  Extrato\n";
        $this->testeextratocompleto('pagamento', $data);
         
        //Chama a função para tratamento da tabela extrato getnet - vtex
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função para tratamento da tabela de extrato vtex\n";
        $this->tratapedidosextratogetnetnopedidooracle();
        
        //Chama a função para tratamento da tabela ajuste getnet - vtex
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função para tratamento da tabela de ajuste vtex\n";
        $this->tratapedidosextratogetnetnopedidoajusteoracle();

        //Chama a função para tratamento da tabela ajuste getnet - vtex
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função para tratamento da tabela consolidada\n";
        $this->tratatabelagetnetextratogatilho();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job relatorioconsilidadooracle\n";

        $this->endJob();

    }

    public function relatorioconsilidadovtexv2($id = null,$data = null){
        $this->startJob(__FUNCTION__, $id);

        if($data == null || $data == 'null'){
            $data = date('Y-m-01');
            $data = strtotime($data);
            $data = strtotime("-1 months", $data);
            $data = date('Y-m-01',$data);
        }

        echo "[".date("Y-m-d H:i:s")."] - Iniício do Job relatorioconsilidadovtex\n";

        //Chama a função getnet para atualização do extrato
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função de extrato Getnet -  Extrato\n";
        $this->testeextratocompletov2('extrato', $data);
          
        //Chama a função getnet para atualização do ajuste
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função de extrato Getnet -  Extrato\n";
        $this->testeextratocompleto('ajuste', $data);

        //Chama a função getnet para atualização do pagamento
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função de extrato Getnet -  Extrato\n";
        $this->testeextratocompleto('pagamento', $data);
        
        //Chama a função para tratamento da tabela extrato getnet - vtex
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função para tratamento da tabela de extrato vtex\n";
        $this->tratapedidosextratogetnetnopedidovtexv2();
        
        //Chama a função para tratamento da tabela ajuste getnet - vtex
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função para tratamento da tabela de ajuste vtex\n";
        $this->tratapedidosextratogetnetnopedidoajustevtex();

        //Chama a função para tratamento da tabela ajuste getnet - vtex
        echo "[".date("Y-m-d H:i:s")."] - Chamando a função para tratamento da tabela consolidada\n";
        //$this->tratatabelagetnetextratogatilho();
        echo "[".date("Y-m-d H:i:s")."] - Skipped a função para tratamento da tabela consolidada\n";

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job relatorioconsilidadovtex\n";

        $this->endJob();

    }

    public function testeextratocompletov2($tipo = "extrato", $data = null){
        
        ini_set('memory_limit', '3048M');

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Gera Extrato - $tipo\n";

        $dateInicioJob = new DateTime();

        $this->getaccesstokens();

        $gateway_name = Model_gateway::GETNET;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'testeextratocompleto';

        echo "[".date("Y-m-d H:i:s")."] - Buscando extrato atualizado por dias\n";

        /* PREPARA DATAS */
        $arrayDatas = $this->geradatasretroativas($data);
        
        foreach($arrayDatas as $datasExec){

            $dataMesAtualInicioLoop = date_create($datasExec['dataInicioMesAtual']);
            $dataMesAtualFimLoop = date_create($datasExec['dataFimMesAtual']);


            //Tratando o mês atual
            echo "[".date("Y-m-d H:i:s")."] - Buscando dados mês ".date_format($dataMesAtualInicioLoop, 'Y-m-d')."T00:00:00Z"." - ".date_format($dataMesAtualFimLoop, 'Y-m-d')."T23:59:59Z"." com o parâmetro $tipo\n";
            for($j=1;$dataMesAtualInicioLoop<=$dataMesAtualFimLoop;date_add($dataMesAtualInicioLoop, date_interval_create_from_date_string('1 days'))) {

                $this->getaccesstokens();

                $this->db->trans_begin();

                $date = new DateTime();

                $dataInicio = date_format($dataMesAtualInicioLoop, 'Y-m-d') . "T00:00:00Z";
                $dataFim = date_format($dataMesAtualInicioLoop, 'Y-m-d') . "T23:59:59Z";
                
                if ($tipo == "extrato") {
                    $saida = $this->geraextratopordatasv2($dataInicio, $dataFim);
                } elseif ($tipo == "extrato_liquidacao") {
                    $saida = $this->geraextratoliquidacaopordatas($dataInicio, $dataFim);
                } elseif ($tipo == "ajuste") {
                    $saida = $this->geraajustepordatas($dataInicio, $dataFim);
                } elseif ($tipo == "ajuste_liquidacao") {
                    $saida = $this->geraajusteliquidacaopordatas($dataInicio, $dataFim);
                } elseif ($tipo == "mdr") {
                    $saida = $this->geraextratomdrpordatas($dataInicio, $dataFim);
                } elseif ($tipo == "pagamento") {
                    $saida = $this->geraextratpagamentoopordatas($dataInicio, $dataFim);
                }

                $date2 = new DateTime();
                $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();

                echo "[".date("Y-m-d H:i:s")."] - Extrato atualizado em ".$diffInSeconds." segundo(s) \n";

                $this->db->trans_commit();

            }

        }


        $dateFimJob = new DateTime();
        $diffInSecondsJobs = $dateFimJob->getTimestamp() - $dateInicioJob->getTimestamp();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job - Tempo de execução: ".$diffInSecondsJobs." segundo(s)\n";
        
    }

    private function geraextratopordatasv2($dataInicio, $dataFim, $pageInicial = 1){
        echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim." para a página ".$pageInicial."\n";
        $response = array();

        $response = $this->integration->geraextratoajustePaginado($dataInicio,$dataFim,$pageInicial);
        $anoMes = str_replace("-","",substr($dataInicio,0,7));

        if (!($response['httpcode'] == "200")) {  // created

            $responseContent = json_decode($response['content']);
            $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $msg = "Erro ao puxar extrato na getnet: "
                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                "Resposta da Getnet: " . PHP_EOL
                . $responseContent . ' ' . PHP_EOL ;

            echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
            print_r($response);

            echo "[".date("Y-m-d H:i:s")."] - Chamando de forma recursiva a função para a mesma data e página\n";
            $this->geraextratopordatasv2($dataInicio, $dataFim, $pageInicial);

        } else {

            $paginasTotais = json_decode($response['content'], true)['page_amount'];
            $responseContent = json_decode($response['content'], true);
            if($responseContent["list_transactions"]){
                foreach($responseContent['list_transactions'] as $saidaExtrato){

                    if($saidaExtrato['summary']['order_id'] == '1405180593169'){
                        print_r($saidaExtrato);
                    }

                     foreach($saidaExtrato['details'] as $saidaExtratoDetalhes){

                        $md5Chave = md5($saidaExtrato['summary']['order_id'].$saidaExtrato['summary']['marketplace_subsellerid'].$saidaExtratoDetalhes['item_id'].$saidaExtratoDetalhes['number_installments'].$saidaExtratoDetalhes['installment'].$saidaExtrato['summary']['transaction_type']);

                        $data = array(
                            "summary_type_register" => $saidaExtrato['summary']['type_register'],
                            "summary_order_id" => $saidaExtrato['summary']['order_id'],
                            "summary_marketplace_subsellerid" => $saidaExtrato['summary']['marketplace_subsellerid'],
                            "summary_marketplace_transaction_id" => $saidaExtrato['summary']['marketplace_transaction_id'],
                            "summary_transaction_date" => $saidaExtrato['summary']['transaction_date'],
                            "summary_confirmation_date" => $saidaExtrato['summary']['confirmation_date'],
                            "summary_product_id" => $saidaExtrato['summary']['product_id'],
                            "summary_transaction_type" => $saidaExtrato['summary']['transaction_type'],
                            "summary_number_installments" => $saidaExtrato['summary']['number_installments'],
                            "summary_nsu_host" => $saidaExtrato['summary']['nsu_host'],
                            "summary_acquirer_transaction_id" => $saidaExtrato['summary']['acquirer_transaction_id'],
                            "summary_card_payment_amount" => $saidaExtrato['summary']['card_payment_amount'],
                            "summary_sum_details_card_payment_amount" => $saidaExtrato['summary']['sum_details_card_payment_amount'],
                            "summary_marketplace_original_transaction_id" => $saidaExtrato['summary']['marketplace_original_transaction_id'],
                            "summary_transaction_status_code" => $saidaExtrato['summary']['transaction_status_code'],
                            "summary_transaction_sign" => $saidaExtrato['summary']['transaction_sign'],
                            "summary_terminal_nsu" => $saidaExtrato['summary']['terminal_nsu'],
                            "summary_reason_message" => $saidaExtrato['summary']['reason_message'],
                            "summary_authorization_code" => $saidaExtrato['summary']['authorization_code'],
                            "summary_payment_id" => $saidaExtrato['summary']['payment_id'],
                            "summary_terminal_identification" => $saidaExtrato['summary']['terminal_identification'],
                            "summary_nsu_tef" => $saidaExtrato['summary']['nsu_tef'],
                            "summary_entry_mode" => $saidaExtrato['summary']['entry_mode'],
                            "summary_transaction_channel" => $saidaExtrato['summary']['transaction_channel'],
                            "summary_capture" => $saidaExtrato['summary']['capture'],
                            "summary_payment_tag" => $saidaExtrato['summary']['payment_tag'],
                            "summary_truncated_card_number" => $saidaExtrato['summary']['truncated_card_number'],
                            "summary_prepaid_card" => $saidaExtrato['summary']['prepaid_card'],
                            "details_type_register" => $saidaExtratoDetalhes['type_register'],
                            "details_marketplace_schedule_id" => $saidaExtratoDetalhes['marketplace_schedule_id'],
                            "details_marketplace_subsellerid" => $saidaExtratoDetalhes['marketplace_subsellerid'],
                            "details_release_status" => $saidaExtratoDetalhes['release_status'],
                            "details_cpfcnpj_subseller" => $saidaExtratoDetalhes['cpfcnpj_subseller'],
                            "details_cancel_custom_key" => $saidaExtratoDetalhes['cancel_custom_key'],
                            "details_cancel_request_id" => $saidaExtratoDetalhes['cancel_request_id'],
                            "details_marketplace_transaction_id" => $saidaExtratoDetalhes['marketplace_transaction_id'],
                            "details_transaction_date" => $saidaExtratoDetalhes['transaction_date'],
                            "details_confirmation_date" => $saidaExtratoDetalhes['confirmation_date'],
                            "details_item_id" => $saidaExtratoDetalhes['item_id'],
                            "details_number_installments" => $saidaExtratoDetalhes['number_installments'],
                            "details_installment" => $saidaExtratoDetalhes['installment'],
                            "details_installment_date" => $saidaExtratoDetalhes['installment_date'],
                            "details_installment_amount" => $saidaExtratoDetalhes['installment_amount'],
                            "details_subseller_rate_amount" => $saidaExtratoDetalhes['subseller_rate_amount'],
                            "details_subseller_rate_percentage" => $saidaExtratoDetalhes['subseller_rate_percentage'],
                            "details_payment_date" => $saidaExtratoDetalhes['payment_date'],
                            "details_subseller_rate_closing_date" => $saidaExtratoDetalhes['subseller_rate_closing_date'],
                            "details_subseller_id" => $saidaExtratoDetalhes['subseller_id'],
                            "details_seller_id" => $saidaExtratoDetalhes['seller_id'],
                            "details_transaction_sign" => $saidaExtratoDetalhes['transaction_sign'],
                            "details_item_id_mgm" => $saidaExtratoDetalhes['item_id_mgm'],
                            "details_payment_id" => $saidaExtratoDetalhes['payment_id'],
                            "details_payment_tag" => $saidaExtratoDetalhes['payment_tag'],
                            "details_item_split_tag" => $saidaExtratoDetalhes['item_split_tag'],
                            "details_payment_plan_name" => $saidaExtratoDetalhes['payment_plan_name'],
                            "details_boleto_id" => $saidaExtratoDetalhes['boleto_id'],
                            "details_our_number" => $saidaExtratoDetalhes['our_number'],
                            "details_boleto_payment_date" => $saidaExtratoDetalhes['boleto_payment_date'],
                            "details_reference_number" => $saidaExtratoDetalhes['reference_number'],
                            "json_retorno" => json_encode($saidaExtrato, JSON_UNESCAPED_UNICODE),
                            "chave_md5" => $md5Chave,
                        );
                        $retorno = $this->model_gateway->saveextractgetnetv2($data);

                        if(!$retorno){
                            echo "[".date("Y-m-d H:i:s")."] - Item pedido Extrato atualizado com erro - ".$saidaExtrato['summary']['order_id']."\n";
                        }

                    }
                }
            }

            if($paginasTotais>1){

                for($paginaAtual = $pageInicial+1;$paginaAtual<=$paginasTotais;$paginaAtual++){

                    echo "[".date("Y-m-d H:i:s")."] - Rodando o dia ".$dataInicio." - ".$dataFim." Pagina ".$paginaAtual." de ".$paginasTotais."\n";

                    $response = $this->integration->geraextratoajustePaginado($dataInicio,$dataFim,$paginaAtual);
                    $anoMes = str_replace("-","",substr($dataInicio,0,7));

                    if (!($response['httpcode'] == "200")) {  // created

                        $responseContent = json_decode($response['content']);
                        $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        $msg = "Erro ao puxar extrato na getnet: "
                            . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                            "Resposta da Getnet: " . PHP_EOL
                            . $responseContent . ' ' . PHP_EOL ;

                        echo "[".date("Y-m-d H:i:s")."] - Erro ao puxar o extrato\n";
                        print_r($response);

                        echo "[".date("Y-m-d H:i:s")."] - Chamando de forma recursiva a função para a mesma data e página\n";
                        $this->geraextratopordatasv2($dataInicio, $dataFim, $paginaAtual);

                    } else {

                        $responseContent = json_decode($response['content'], true);

                        if($responseContent["list_transactions"]){
                            foreach($responseContent['list_transactions'] as $saidaExtrato){

                                foreach($saidaExtrato['details'] as $saidaExtratoDetalhes){

                                    $md5Chave = md5($saidaExtrato['summary']['order_id'].$saidaExtrato['summary']['marketplace_subsellerid'].$saidaExtratoDetalhes['item_id'].$saidaExtratoDetalhes['number_installments'].$saidaExtratoDetalhes['installment'].$saidaExtrato['summary']['transaction_type']);

                                    $data = array(
                                        "summary_type_register" => $saidaExtrato['summary']['type_register'],
                                        "summary_order_id" => $saidaExtrato['summary']['order_id'],
                                        "summary_marketplace_subsellerid" => $saidaExtrato['summary']['marketplace_subsellerid'],
                                        "summary_marketplace_transaction_id" => $saidaExtrato['summary']['marketplace_transaction_id'],
                                        "summary_transaction_date" => $saidaExtrato['summary']['transaction_date'],
                                        "summary_confirmation_date" => $saidaExtrato['summary']['confirmation_date'],
                                        "summary_product_id" => $saidaExtrato['summary']['product_id'],
                                        "summary_transaction_type" => $saidaExtrato['summary']['transaction_type'],
                                        "summary_number_installments" => $saidaExtrato['summary']['number_installments'],
                                        "summary_nsu_host" => $saidaExtrato['summary']['nsu_host'],
                                        "summary_acquirer_transaction_id" => $saidaExtrato['summary']['acquirer_transaction_id'],
                                        "summary_card_payment_amount" => $saidaExtrato['summary']['card_payment_amount'],
                                        "summary_sum_details_card_payment_amount" => $saidaExtrato['summary']['sum_details_card_payment_amount'],
                                        "summary_marketplace_original_transaction_id" => $saidaExtrato['summary']['marketplace_original_transaction_id'],
                                        "summary_transaction_status_code" => $saidaExtrato['summary']['transaction_status_code'],
                                        "summary_transaction_sign" => $saidaExtrato['summary']['transaction_sign'],
                                        "summary_terminal_nsu" => $saidaExtrato['summary']['terminal_nsu'],
                                        "summary_reason_message" => $saidaExtrato['summary']['reason_message'],
                                        "summary_authorization_code" => $saidaExtrato['summary']['authorization_code'],
                                        "summary_payment_id" => $saidaExtrato['summary']['payment_id'],
                                        "summary_terminal_identification" => $saidaExtrato['summary']['terminal_identification'],
                                        "summary_nsu_tef" => $saidaExtrato['summary']['nsu_tef'],
                                        "summary_entry_mode" => $saidaExtrato['summary']['entry_mode'],
                                        "summary_transaction_channel" => $saidaExtrato['summary']['transaction_channel'],
                                        "summary_capture" => $saidaExtrato['summary']['capture'],
                                        "summary_payment_tag" => $saidaExtrato['summary']['payment_tag'],
                                        "summary_truncated_card_number" => $saidaExtrato['summary']['truncated_card_number'],
                                        "summary_prepaid_card" => $saidaExtrato['summary']['prepaid_card'],
                                        "details_type_register" => $saidaExtratoDetalhes['type_register'],
                                        "details_marketplace_schedule_id" => $saidaExtratoDetalhes['marketplace_schedule_id'],
                                        "details_marketplace_subsellerid" => $saidaExtratoDetalhes['marketplace_subsellerid'],
                                        "details_release_status" => $saidaExtratoDetalhes['release_status'],
                                        "details_cpfcnpj_subseller" => $saidaExtratoDetalhes['cpfcnpj_subseller'],
                                        "details_cancel_custom_key" => $saidaExtratoDetalhes['cancel_custom_key'],
                                        "details_cancel_request_id" => $saidaExtratoDetalhes['cancel_request_id'],
                                        "details_marketplace_transaction_id" => $saidaExtratoDetalhes['marketplace_transaction_id'],
                                        "details_transaction_date" => $saidaExtratoDetalhes['transaction_date'],
                                        "details_confirmation_date" => $saidaExtratoDetalhes['confirmation_date'],
                                        "details_item_id" => $saidaExtratoDetalhes['item_id'],
                                        "details_number_installments" => $saidaExtratoDetalhes['number_installments'],
                                        "details_installment" => $saidaExtratoDetalhes['installment'],
                                        "details_installment_date" => $saidaExtratoDetalhes['installment_date'],
                                        "details_installment_amount" => $saidaExtratoDetalhes['installment_amount'],
                                        "details_subseller_rate_amount" => $saidaExtratoDetalhes['subseller_rate_amount'],
                                        "details_subseller_rate_percentage" => $saidaExtratoDetalhes['subseller_rate_percentage'],
                                        "details_payment_date" => $saidaExtratoDetalhes['payment_date'],
                                        "details_subseller_rate_closing_date" => $saidaExtratoDetalhes['subseller_rate_closing_date'],
                                        "details_subseller_id" => $saidaExtratoDetalhes['subseller_id'],
                                        "details_seller_id" => $saidaExtratoDetalhes['seller_id'],
                                        "details_transaction_sign" => $saidaExtratoDetalhes['transaction_sign'],
                                        "details_item_id_mgm" => $saidaExtratoDetalhes['item_id_mgm'],
                                        "details_payment_id" => $saidaExtratoDetalhes['payment_id'],
                                        "details_payment_tag" => $saidaExtratoDetalhes['payment_tag'],
                                        "details_item_split_tag" => $saidaExtratoDetalhes['item_split_tag'],
                                        "details_payment_plan_name" => $saidaExtratoDetalhes['payment_plan_name'],
                                        "details_boleto_id" => $saidaExtratoDetalhes['boleto_id'],
                                        "details_our_number" => $saidaExtratoDetalhes['our_number'],
                                        "details_boleto_payment_date" => $saidaExtratoDetalhes['boleto_payment_date'],
                                        "details_reference_number" => $saidaExtratoDetalhes['reference_number'],
                                        "json_retorno" => json_encode($saidaExtrato, JSON_UNESCAPED_UNICODE),
                                        "chave_md5" => $md5Chave,
                                    );

                                    $retorno = $this->model_gateway->saveextractgetnetv2($data);

                                    if(!$retorno){
                                        echo "[".date("Y-m-d H:i:s")."] - Item pedido Extrato atualizado com erro - ".$saidaExtrato['summary']['order_id']."\n";
                                    }

                                }

                            }
                        }

                    }

                }

            }

        }

    }


    public function  tratapedidosextratogetnetnopedidovtexv2(){

        echo "[".date("Y-m-d H:i:s")."] - Início do Job tratapedidosextratogetnetnopedidovtexv2\n";
        echo "[".date("Y-m-d H:i:s")."] - Buscando Pedidos a serem tratdos\n";

        /*$sql = "SELECT ge.id, ge.summary_order_id as order_id_json, gs.store_id  FROM getnet_extrato_v2 ge
        inner join getnet_subaccount gs on gs.store_id  = cast(replace(replace(ge.details_item_id, 'PRV',''),'teste','') as signed) 
        where ge.details_item_id not like 'decathlon%' and ge.order_id is null";*/
         /*$sql = "SELECT ge.id, ge.summary_order_id as order_id_json, gs.store_id  FROM getnet_extrato_v2 ge
        inner join getnet_subaccount gs on gs.subseller_id  = ge.details_subseller_id
        where ge.details_item_id not like 'decathlon%' and ge.order_id is null";*/
        $sql = "SELECT ge.id, ge.summary_order_id as order_id_json, cast(replace(replace(ge.details_item_id, 'PRV',''),'teste','') as signed)  as store_id
                FROM getnet_extrato_v2 ge
        where ge.details_item_id not like 'decathlon%' and ge.order_id is null";

        $query = $this->db->query($sql);
        $resultado =  $query->result_array();
        $indice = 0;
        $arrayQuerys = array();
        
        foreach($resultado as $pedidoGetnet){

            $sql2 = "select o.id, o.numero_marketplace, op.transaction_id from orders o
            inner join orders_payment op on op.order_id = o.id
            where (op.transaction_id  = '".$pedidoGetnet["order_id_json"]."' or left(o.numero_marketplace,13) = '".$pedidoGetnet["order_id_json"]."') and store_id = ".$pedidoGetnet["store_id"];
           
            $query2 = $this->db->query($sql2);
            $result =  $query2->row();
            
            if($result){
                $arrayQuerys[$indice] = "update getnet_extrato_v2 set numero_marketplace ='".$result->numero_marketplace."', order_id = ".$result->id ." where id = ".$pedidoGetnet["id"].";\n";
                $indice++;
                $sql3 = "update getnet_extrato_v2 set numero_marketplace = ?, order_id = ? where id = ?";

		        $update = $this->db->query($sql3, [$result->numero_marketplace, $result->id, $pedidoGetnet["id"]]);
                if($update){
                    echo "[".date("Y-m-d H:i:s")."] - "."Atualizado o pedido ".$result->numero_marketplace." com sucesso \n";
                }

            }else{

                $arrayQuerys[$indice] = "update getnet_extrato_v2 set numero_marketplace = 0, order_id = 0 where id = ".$pedidoGetnet["id"].";\n";
                $indice++;

                $sql3 = "update getnet_extrato_v2 set numero_marketplace = ?, order_id = ? where id = ?";

		        $update = $this->db->query($sql3, [0, 0, $pedidoGetnet["id"]]);
                if($update){
                    echo "[".date("Y-m-d H:i:s")."] - "."Atualizado o extrato ".$pedidoGetnet["id"]." com sucesso para 0 pois não encontramos os pedido associados a pedido extrato\n";
                }

            }

        }

        echo "[".date("Y-m-d H:i:s")."] - Total Pedidos atualizados em tratamento: $indice\n";

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job tratapedidosextratogetnetnopedido\n";
        
    }


}
