<?php /** @noinspection PhpUndefinedFieldInspection */

require APPPATH . "controllers/Api/V1/API.php";

/**
 * @property Model_billet model_billet
 * @property Model_parametrosmktplace model_parametrosmktplace
 */
class Financial extends API
{
    public $change_date_fiscal_panel = '2';
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_parametrosmktplace');
        $this->load->model('model_billet');
        $this->load->model('model_iugu_repasse');
        $this->load->model('model_payment');
        $this->load->model('model_orders_conciliation_installments');
        $this->load->model('model_campaigns_v2');
        $this->load->model('model_parametrosmktplace');
        $this->load->model('model_settings');
        $this->lang->load('application', "portuguese_br");

        $this->change_date_fiscal_panel = $this->model_settings->getStatusbyName('change_date_fiscal_panel');
    }

    public function index_get(string $type, $code = null): void
    {
        $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_operation_not_accepted'), "W");
        $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_operation_not_accepted'))), REST_Controller::HTTP_BAD_REQUEST);
    }

    public function conciliation_get($code = null): void
    {
        $code = xssClean($code);
        if (!$code) {
            $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_operation_not_accepted'), "W");
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_operation_not_accepted'))), REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        $this->getConciliation($code);
    }

    public function conciliationstore_get($code = null): void
    {
        $code = xssClean($code);
        if (!$code) {
            $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_operation_not_accepted'), "W");
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_operation_not_accepted'))), REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->getConciliationStores($code);
    }

    public function conciliationlote_get($code = null): void
    {
        $this->getConciliationLote($code);
    }

    private function getConciliation(string $code): void
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        $sellerIndex = $this->_args['x-store-seller-key'];

        $storeKey = $this->getStoreKey();

        $result = [
            'success'           => true,
            "registers_count"   => 0,
            "pages_count"       => 0,
            "page"              => 0,
            "total_registers"   => 0,
            'result'            => []
        ];

        $itens = $this->getItensConciliationSellerCenter($code, $sellerIndex, $storeKey);

        if(!$itens){
            $result['success'] = false;
        }

        $result['result']['batch'] = $code;
        $result['result']['itens'] = $itens['data'];
        $result['registers_count']  = $itens['registers_count'];
        $result['pages_count']      = $itens['pages_count'];
        $result['page']             = $itens['page'];
        $result['total_registers']  = $itens['total_registers'];

        // Verifica se foram encontrado resultados
        if (!$itens) {
            $this->response($this->returnError($result['result']), REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->response($result, REST_Controller::HTTP_OK);

    }

    private function getItensConciliationSellerCenter(string $batch, int $sellerIndex = null, string $storeKey = null): array
    {
        //filtros page e qtd por pagina
        $page       = (int)($this->params_filter['page'] ?? 1);
        $per_page   = (int)($this->params_filter['per_page'] ?? 100);

        // Valor minímo da página.
        if ($page <= 0) {
            $page = 1;
        }
        // Valor minímo da quantidade por página.
        if ($per_page <= 0) {
            $per_page = 1;
        }

        $page_filter = $page - 1;
        $limit  = $per_page;
        $offset = $page_filter*$per_page;

        if ($sellerIndex == 1) {
            $sellerIndex = null;
        }

        $data = $this->model_billet->getConciliacaoSellerCenter($batch, null, $sellerIndex, $limit, $offset, null, $storeKey);

        $itens = [];

        if (!$data) {
            return $itens;
        }

        foreach ($data as $conciliation) {

            $item = $this->processConciliationItem($conciliation);
            $itens[] = $item;

        }

        $registers = count($data);
        $totalPages = $registers / $per_page;

        return [
            "registers_count"   => $registers,
            "pages_count"       => $totalPages === (int)$totalPages ? $totalPages : (int)$totalPages + 1,
            "page"              => $page,
            "total_registers"   => $registers,
            "data"              => $itens
        ];
    }

    private function processConciliationItem($conciliation): array
    {

        // checa se o pedido tem valor antecipado
        $valor_antecipado = 0;

        $n_mkt = isset($conciliation['numero_marketplace']) && $conciliation['numero_marketplace'] ? $conciliation['numero_marketplace'] : null;
        if(!is_null($n_mkt)){
            $valor_antecipado = isset($conciliation['valor_antecipado']) && $conciliation['valor_antecipado'] ? $conciliation['valor_antecipado'] : 0;
        }

        $valor_repasse = 0.00;
        if($conciliation['valor_repasse_ajustado'] <> 0.00){
            $valor_repasse = $conciliation['valor_repasse_ajustado'];
        }else{
            $valor_repasse = $conciliation['valor_repasse'];
        }

        $valor_percentual_produto = $conciliation['valor_percentual_produto']."%";
        $valor_percentual_produto .= (isset($conciliation['comission_reduction']) && floatVal($conciliation['comission_reduction']) > 0) ? '*' : '';

        $campaigns_pricetags                    = $conciliation['total_pricetags'] ?? 0;
        $campaigns_campaigns                    = $conciliation['total_campaigns'] ?? 0;
        $campaigns_mktplace                     = $conciliation['total_channel'] ?? 0;
        $campaigns_seller                       = $conciliation['total_seller'] ?? 0;
        $campaigns_promotions                   = $conciliation['total_promotions'] ?? 0;
        $campaigns_rebate                       = $conciliation['total_rebate'] ?? 0;
        $campaigns_comission_reduction          = $conciliation['comission_reduction'] ?? 0;
        $campaigns_comission_reduction_products = $conciliation['comission_reduction_products'] ?? 0;

        $campaigns_promotions                   = (is_array($campaigns_promotions)) ? $campaigns_promotions['total'] : $campaigns_promotions;

        $valor_repasse          = (is_numeric($conciliation['valor_repasse'])) ? $conciliation['valor_repasse'] : 0;
        $valor_repasse_ajustado = (is_numeric($conciliation['valor_repasse_ajustado'])) ? $conciliation['valor_repasse_ajustado'] : 0;

        //braun refund
        if ($this->setting_api_comission != "1"){
            $campaigns_refund = (abs($valor_repasse_ajustado - $valor_repasse) > 0) ? round((abs($valor_repasse_ajustado - $valor_repasse)),2) : (0);
        }else{
            $campaigns_refund = (abs($valor_repasse_ajustado - $valor_repasse - $campaigns_comission_reduction_products) > 0) ? round((abs($valor_repasse_ajustado - $valor_repasse - $campaigns_comission_reduction_products)),2) : (0);
        }

        $item = [];
        $item['orderId'] = (int)$conciliation['order_id'];
        $item['marketplaceOrderCode'] = (string)$conciliation['numero_marketplace'];
        $item['store_id'] = (string)$conciliation['store_id'];
        $item['sellerName'] = (string)$conciliation['seller_name'];
        $item['cnpj'] = (string)$conciliation['cnpj'];
        $item['orderDate'] = (string)$conciliation['data_pedido'];
        $item['orderDeliveryDate'] = (string)$conciliation['data_entrega'];
        $item['date_report'] = (string)$conciliation['data_report'];
        $item['orderPaymentDate'] = (string)date('Y-m-d h:i:s', strtotime($conciliation['data_ciclo']));
        $item['conciliationStatus'] = (string)$conciliation['status_conciliacao'];
        $item['calculatedExpectation']['orderTotalValue'] = (float)$conciliation['valor_pedido'];
        $item['calculatedExpectation']['paidAdvance'] = (float)$valor_antecipado;
        $item['calculatedExpectation']['productsTotalValue'] = (float)$conciliation['valor_produto'];
        $item['calculatedExpectation']['shippingValue'] = (float)$conciliation['valor_frete'];
        $item['calculatedExpectation']['commissionPercentageProduct'] = (float)$conciliation['valor_percentual_produto'];
        $item['calculatedExpectation']['commissionPercentageShipping'] = (float)$conciliation['valor_percentual_frete'];
        $item['calculatedExpectation']['paymentType'] = (string)$conciliation['tipo_pagamento'];
        $item['calculatedExpectation']['mdr'] = (string)$conciliation['taxa_cartao_credito'];
        $item['calculatedExpectation']['creditCardNumber'] = (string)$conciliation['digitos_cartao'];
        $item['installments']['current_installment'] = (string)$conciliation['current_installment'];
        $item['installments']['total_installments'] = (string)$conciliation['total_installments'];
        $item['sellerCenter']['comissionTotal'] = (float)$conciliation['valor_comissao'];
        $item['sellerCenter']['productComission'] = (float)$conciliation['valor_comissao_produto'];
        $item['sellerCenter']['shippingComission'] = (float)$conciliation['valor_comissao_frete'];
        $item['seller']['transferAmount'] = (float)$valor_repasse;
        $item['seller']['productTransferAmount'] = (float)$conciliation['valor_repasse_produto'];
        $item['seller']['shippingTransferAmount'] = (float)$conciliation['valor_repasse_frete'];
        $item['campaign']['campaignsPricetags'] = (float)$campaigns_pricetags;
        $item['campaign']['campaignsCampaigns'] = (float)$campaigns_campaigns;
        $item['campaign']['campaignsMktplace'] = (float)$campaigns_mktplace;
        $item['campaign']['campaignsSeller'] = (float)$campaigns_seller;
        $item['campaign']['campaignsPromotions'] = (float)$campaigns_promotions;
        $item['campaign']['campaignsComissionReduction'] = (float)$campaigns_comission_reduction;
        $item['campaign']['campaignsRebate'] = (float)$campaigns_rebate;
        $item['campaign']['campaignsRefund'] = (float)$campaigns_refund;

        return $item; // Retorne o item processado.

    }

    public function index_post($type = null): void
    {

        if ($type == 'conciliation') {
            $this->generateConciliation();
            return;
        }
        if ($type == 'service-invoice') {
            $this->insertServiceInvoice();
            return;
        }

        $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_operation_not_accepted'), "W");
        $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_operation_not_accepted'))), REST_Controller::HTTP_BAD_REQUEST);

    }

    public function insertServiceInvoice(): void
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        $result = ['success' => false, 'result' => []];

        $contents = file_get_contents('php://input');
        $data = json_decode($contents, true);

        $paramsRequired = [
            'nfse',
        ];

        foreach ($paramsRequired as $paramRequired) {
            if (!isset($data[$paramRequired]) || !$data[$paramRequired]) {
                $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, " - payload: " . $contents . "" . PHP_EOL, "W");
                $this->response($this->returnError($this->lang->line('api_parameter_not_provider') . $paramRequired . $this->lang->line('api_parameter_not_provider_end')), REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
        }

        $nfse = $data['nfse'];

        $paramsRequired = [
            'seller',
            'month',
            'year',
            'day',
            'url',
        ];

        foreach ($paramsRequired as $paramRequired) {
            foreach ($nfse as $nfseItem){
                if (!isset($nfseItem[$paramRequired]) || !$nfseItem[$paramRequired]) {
                    $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, " - payload: " . $contents . "" . PHP_EOL, "W");
                    $this->response($this->returnError($this->lang->line('api_parameter_not_provider') . $paramRequired . $this->lang->line('api_parameter_not_provider_end')), REST_Controller::HTTP_BAD_REQUEST);
                    return;
                }
            }
        }

        $lote = date('YmdHis') . rand(1, 1000000);

        $storeIdsInserted = [];

        foreach ($nfse as $nfseItem){

            $transferDate = $nfseItem['year'].'-'.$nfseItem['month'].'-'.$nfseItem['day'];

            $valid_date = checkdate($nfseItem['month'], $nfseItem['day'], $nfseItem['year']);

            if (isset($this->change_date_fiscal_panel) && $this->change_date_fiscal_panel != '1')
            {
                if (!$this->model_iugu_repasse->cycleExistsByStoreIdCycleDay($transferDate, (int)$nfseItem['seller'])){
                    $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, " - payload: " . $contents . "" . PHP_EOL, "W");
                    $this->response($this->returnError($this->lang->line('api_cycle_not_found') . json_encode($nfseItem)), REST_Controller::HTTP_BAD_REQUEST);
                    return;
                }
            }
            else if (!$valid_date)
            {
                $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, " - payload: " . $contents . "" . PHP_EOL, "W");
                $this->response($this->returnError($this->lang->line('api_cycle_invalide_date') . json_encode($nfseItem)), REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $nfsurl = [];
            $nfsurl['lote'] = $lote;
            $nfsurl['store_id'] = (int)$nfseItem['seller'];
            $nfsurl['data_ciclo'] = $transferDate;
            $nfsurl['url'] = $nfseItem['url'];
            $nfsurl['ativo'] = 1;

            if (!$this->model_payment->insertNFSUrl($nfsurl)){
                $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, " - payload: " . $contents . "" . PHP_EOL, "W");
                $this->response($this->returnError($this->lang->line('api_internal_error_url') . json_encode($nfseItem)), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }

            if (!isset($storeIdsInserted[$nfseItem['seller']][$transferDate])){

                $nfsGroup = [];
                $nfsGroup['lote'] = $lote;
                $nfsGroup['store_id'] = (int)$nfseItem['seller'];
//                $nfsGroup['data_ciclo'] = dateBrazil($transferDate);
                $nfsGroup['data_ciclo'] = $transferDate;
                $nfsGroup['ativo'] = 1;

                if (!$this->model_payment->insertNFSGroupData($nfsGroup)){
                    $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, " - payload: " . $contents . "" . PHP_EOL, "W");
                    $this->response($this->returnError($this->lang->line('api_internal_error_group') . json_encode($nfseItem)), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    return;
                }

            }

            $storeIdsInserted[$nfseItem['seller']][$transferDate] = true;

        }

        $result['success'] = true;
        $result['result']['batch'] = $lote;

        $this->response($result, REST_Controller::HTTP_CREATED);

    }

    private function generateConciliation(): void
    {

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        $result = ['success' => false, 'result' => []];

        $contents = file_get_contents('php://input');
        $data = json_decode($contents, true);

        $paramsRequired = [
            'marketplaceName',
            'month',
            'year',
            'day',
        ];

        foreach ($paramsRequired as $paramRequired) {
            if (!isset($data[$paramRequired]) || !$data[$paramRequired]) {
                $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, " - payload: " . $contents . "" . PHP_EOL, "W");
                $this->response($this->returnError($this->lang->line('api_parameter_not_provider') . $paramRequired . $this->lang->line('api_parameter_not_provider_end')), REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
        }

        //Validating if has valid payment cycle
        $checkCycle = $this->model_parametrosmktplace->getReceivablesDataCicloByMarketplaceNameAndPaymentDay($data['marketplaceName'], $data['day']);

        if ($checkCycle) {

            $batch = date('YmdHis') . rand(1, 1000000);

            $inputs = [
                'txt_ano_mes' => "{$data['month']}-{$data['year']}",
                'hdnLote' => $batch,
                'data_ciclo' => "{$data['month']}/{$data['year']}",
            ];

			if($data['day'] < 10){
				$inputs['data_ciclo'] = "0".$data['day']."/".$inputs['data_ciclo'] ;
			}else{
				$inputs['data_ciclo'] = $data['day']."/".$inputs['data_ciclo'] ;
			}

            $this->db->trans_begin();

            //Removendo os registros de parcelamento inválido ao iniciar
            $this->model_orders_conciliation_installments->clearInvalidBatchesInstallments();

            list($geraConciliacao, $conciliation_array) = $this->model_billet->generateInstallmentsByInputs(
                $inputs,
                $this->setting_api_comission,
                'API'
            );

            $currentConciliationInstallments = $this->model_orders_conciliation_installments->findAllToBeReconciled(dateBrazilToDateInternational($inputs['data_ciclo']));

            if ($currentConciliationInstallments){

                foreach ($currentConciliationInstallments as $conciliationInstallment){

                    //Salvando o lote da conciliação a ser gerada agora na parcela em questão
                    $conciliationInstallment['lote'] = $inputs['hdnLote'];

                    $this->model_orders_conciliation_installments->update($conciliationInstallment['id'], $conciliationInstallment);

                    //Salvando a conciliação
                    $conciliation_array = $this->model_orders_conciliation_installments->convertConciliationInstallmentToConciliationSellerCenter($conciliationInstallment);

                    //braun -> recuperando o formato de data que era utilizado anteriormente.
                    $conciliation_array['data_ciclo'] = $inputs['data_ciclo'];

                    $geraConciliacao[] = $this->model_billet->createConciliacaoSellerCenter($inputs, $_SESSION['username'], $conciliation_array);

                }

            }

            $this->model_billet->createConciliacaoSellerCenterPainelLegal($inputs, 'API');

            $saveConciliacao['hdnLote'] = $batch;
            $saveConciliacao['slc_mktplace'] = $checkCycle['integ_id'];
            $saveConciliacao['slc_ciclo'] = $checkCycle['id'];
            $saveConciliacao['slc_ano_mes'] = "{$data['month']}-{$data['year']}";

            $geraConciliacao = $this->model_billet->criaconsiliacao($saveConciliacao);

            $this->db->trans_commit();

            if($geraConciliacao){
                $result['success'] = true;
            }else{
                $result['success'] = $geraConciliacao;
            }

            $itens = $this->getItensConciliationSellerCenter($batch);

            if ($itens) {

                $result['result']['batch'] = $batch;
                $result['result']['itens'] = $itens;

            } else {
                $result['message'] = $this->lang->line('api_no_order_generated');
            }

        }

        if (!$result['success']) {
            $result['message'] = $this->lang->line('api_conciliation_not_generated');
        }

        $this->response($result, REST_Controller::HTTP_OK);

    }

    private function getConciliationStores(string $code): void
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $sellerIndex = $this->_args['x-store-seller-key'];

        $storeKey = $this->getStoreKey();

        $result = [
            'success'           => true,
            "registers_count"   => 0,
            "pages_count"       => 1,
            "page"              => 1,
            "total_registers"   => 0,
            'result'            => []
        ];

        $batchTratado = explode("-",$code);
        $lote = $batchTratado[0];

        $this->params_filter['page'] = 1;
        $this->params_filter['per_page'] = 1000000;

        // $itens = $this->getItensConciliationSellerCenter($batchTratado[0], $sellerIndex, true);
        $itens = $this->model_billet->getcomissionbylotestore($lote);

        $dataConciliacao = $this->model_billet->getConciliacaoGridData($lote, $storeKey);
        $dataPagamentoCiclo = null;
        if($dataConciliacao){
            $conciliation = $dataConciliacao[0];
            $retornoParametroCiclo = $this->model_parametrosmktplace->getReceivablesDataCiclo(null, $conciliation['id_mkt']);
            $dataPagamentoCiclo = substr($conciliation['ano_mes'],3,4)."-".substr($conciliation['ano_mes'],0,2)."-".$retornoParametroCiclo['data_pagamento']." 00:00:00";
        }


        if(!$itens){
            $result['success'] = false;
            $this->response($this->returnError($result['result']), REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $arraySoma = array();

        foreach($itens as $item){
            if(!is_null($storeKey) && $storeKey != $item['store_id']){
                continue;
            };
            $arraySoma[$item['store_id']]['batch'] = $lote;
            $arraySoma[$item['store_id']]['batch_id'] = $lote."-".$item['store_id'];
            $arraySoma[$item['store_id']]['emissor_ordem'] = str_replace("-","",str_replace("/","",str_replace(".", "", $item['cnpj'])));//$item['cnpj'];
            $arraySoma[$item['store_id']]['valor'] = round($arraySoma[$item['store_id']]['valor']+$item['comissionTotal'],2);
            if(!is_null($dataPagamentoCiclo)){
                $arraySoma[$item['store_id']]['ciclo'] = $dataPagamentoCiclo;
            }else{
                $arraySoma[$item['store_id']]['ciclo'] = $item['orderPaymentDate'];
            }

        }

        sort($arraySoma);

        $arrayRetorno = array();
        $count = 0;

        foreach($arraySoma as $somaSaida){
            $saida = [];
            $check = false;

            if (array_key_exists('1', $batchTratado)) {
                if($code == $somaSaida['batch_id']){
                    $check = true;
                }
            }else{
                $check = true;
            }

            if($check){
                $saida['batch_id'] = $somaSaida['batch_id'];
                $saida['emissor_ordem'] = $somaSaida['emissor_ordem'];
                $saida['valor'] = round($somaSaida['valor'],2);
                $saida['ciclo'] = $somaSaida['ciclo'];
                $count++;
                $arrayRetorno[] = $saida;
            }
        }

        $result['result']['batch'] = $lote;
        $result['result']['itens'] = $arrayRetorno;
        $result['registers_count']  = $count;
        $result['total_registers']  = $count;

        $this->response($result, REST_Controller::HTTP_OK);

    }

    private function getConciliationLote(string $code = null): void
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        $sellerIndex = $this->_args['x-store-seller-key'];

        $storeKey = $this->getStoreKey();

        $result = [
            'success'           => true,
            "registers_count"   => 0,
            "pages_count"       => 1,
            "page"              => 1,
            "total_registers"   => 0,
            'result'            => []
        ];

        $batchTratado = explode("-",$code);
        $lote = $batchTratado[0];

        $itens = $this->getItensConciliationLote($batchTratado[0], $sellerIndex, true, $storeKey);

        if(!$itens){
            $result['success'] = false;
        }

        $result['result']['itens'] = $itens['data'];
        $result['registers_count']  = $itens['registers_count'];
        $result['pages_count']      = $itens['pages_count'];
        $result['page']             = $itens['page'];
        if(!is_null($storeKey)){
            $result['total_registers']  = $itens['registers_count'];
        }else{
            $result['total_registers']  = $itens['total_registers'];
        }

        // Verifica se foram encontrado resultados
        if (!$itens) {
            $this->response($this->returnError($result['result']), REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->response($result, REST_Controller::HTTP_OK);

    }

    public function getItensConciliationLote(string $batch, int $sellerIndex = null, bool $chamada = false, $storeKey = null): array
    {
        //filtros page e qtd por pagina
        $page       = (int)($this->params_filter['page'] ?? 1);
        $per_page   = (int)($this->params_filter['per_page'] ?? 100);

        // Valor minímo da página.
        if ($page <= 0) {
            $page = 1;
        }
        // Valor minímo da quantidade por página.
        if ($per_page <= 0) {
            $per_page = 1;
        }

        $page_filter = $page - 1;
        $limit  = $per_page;
        $offset = $page_filter*$per_page;

        if ($sellerIndex == 1) {
            $sellerIndex = null;
        }

        $data = $this->model_billet->getConciliacaoGridData($batch, $storeKey);

        $itens = [];

        if (!$data) {
            return $itens;
        }

        foreach ($data as $conciliation) {

            $intervaloDias = "";
            $dataPagamentoCiclo = "";
            $status = "";
            $statusPagamento = "";

            $retornoParametroCiclo = $this->model_parametrosmktplace->getReceivablesDataCiclo(null, $conciliation['id_mkt']);
            $dataPagamentoCiclo = substr($conciliation['ano_mes'],3,4)."-".substr($conciliation['ano_mes'],0,2)."-".$retornoParametroCiclo['data_pagamento']." 00:00:00";
            $intervaloDias = "Do dia ".$conciliation['data_inicio']." até o dia ".$conciliation['data_fim'];
            $status = str_replace("Conciliação", "Liberação de pagamento", $conciliation['status']);

            $statusPagamento = "";
            if($conciliation['conciliacao_id'] == 0) {

                $statusPagamento = $this->lang->line('application_payment_release_unpaid');
            }else{

                $statusPagamento = $this->lang->line('application_payment_release_paid');
            }

            // $statusPagamento = str_replace("Conciliação", "Repasse", $conciliation['pagamento_conciliacao']);

            $item = [];
            $item['id'] = (string)$conciliation['id_con'];
            $item['lote'] = (string)$conciliation['lote'];
            $item['date_created'] = (string)date('Y-m-d H:i:s', strtotime($conciliation['data_criacao']));
            $item['ano_mes'] = (string)$conciliation['ano_mes'];
            $item['marketplace'] = (string)$conciliation['descloja'];
            $item['dateCycle'] = (string)$intervaloDias;
            $item['paymentdateCycle'] = (string)$dataPagamentoCiclo;
            $item['status'] = (string)$status;
            $item['payment'] = (string)$statusPagamento;

            $itens[] = $item;
        }

        $registers = count($this->model_billet->getConciliacaoGridData($batch));
        $totalPages = $registers / $per_page;

        return [
            "registers_count"   => count($data),
            "pages_count"       => $totalPages === (int)$totalPages ? $totalPages : (int)$totalPages + 1,
            "page"              => $page,
            "total_registers"   => $registers,
            "data"              => $itens
        ];

    }

    private function getStoreKey(){

        $argsLowerCase = array_change_key_case($this->_args, CASE_LOWER);

        if (isset($argsLowerCase['x-store-key'])) {
            $storeKey = $argsLowerCase['x-store-key'];
        }

        return $storeKey;
    }
}