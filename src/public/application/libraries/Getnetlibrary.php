<?php /** @noinspection DuplicatedCode */

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . "libraries/GatewayPaymentLibrary.php";

class Getnetlibrary extends GatewayPaymentLibrary
{

    public $_CI;
    public $url_api_v1 = '';
    public $app_key = '';

    private $transferCost;
    private $minimunTransferValue = 100;

    public function __construct()
    {
        $this->_CI = &get_instance();

        $this->_CI->load->model('model_transfer');
        $this->_CI->load->model('model_gateway');
        $this->_CI->load->model('model_gateway_transfers');
        $this->_CI->load->model('model_repasse');
        $this->_CI->load->model('model_iugu_repasse');
        $this->_CI->load->model('model_conciliation');
        $this->_CI->load->model('model_banks');
        $this->_CI->load->model('model_gateway_settings');
        $this->_CI->load->model('model_settings');
        $this->_CI->load->model('model_orders_payment');

        $this->gateway_name = Model_gateway::GETNET;
        $this->recarregacredenciais();

    }

    public function getaccesstokensoob(){

        $resultado = base64_encode($this->client_id_oob.":".$this->client_secret_id_oob);

        $this->_CI->model_gateway_settings->updateSettings('app_key_oob',$resultado);

        $payload = [
            'grant_type' => 'client_credentials',
            'scope' => 'oob'
        ];

        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: '.'Basic '.$resultado
        );

        return $this->postRequest($this->url_api_v2 . 'auth/oauth/v2/token?grant_type=client_credentials&scope=oob', $payload, $headers);

    }

    public function recarregacredenciais(){
        $this->loadSettings();
    }

    public function getaccesstokensmgm(){

        $resultado = base64_encode($this->client_id_mgm.":".$this->client_secret_id_mgm);

        $this->_CI->model_gateway_settings->updateSettings('app_key_mgm',$resultado);

        $payload = [
            'grant_type' => 'client_credentials',
            'scope' => 'mgm'
        ];

        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: '.'Basic '.$resultado
        );

        //return $this->postRequest($this->getUrlAPI_V1() . 'credenciamento/auth/oauth/v2/token?grant_type=client_credentials&scope=mgm', $payload, $headers);
        return $this->postRequest($this->getUrlAPI_V1() . 'credenciamento/auth/oauth/v2/token?grant_type=client_credentials&scope=mgm', $payload, $headers);

    }

    public function geraajustes($store, $gatewaySubaccount, $valor, $tipoAjuste = null, $description = null){

        if($tipoAjuste == null){
            $tipoAjuste  = 2;
        }

        if($description == null){
            $description  = 'Ajuste Painel Juridico';
        }

        $newdate=date("Y-m-d H:i:s", strtotime("+1 days")); 
        $dataTratada = explode(" ",$newdate);
        $datafinal = $dataTratada[0]."T".$dataTratada[1]."Z";
        //  'date_adjustment' => '2021-10-15T14:30:00Z',

        $payload = [
            'seller_id' => $this->seller_id,
            'subseller_id' => $gatewaySubaccount['subseller_id'],
            'merchant_id' => $this->merchant_id,
            'type_adjustment' => $tipoAjuste,
            'amount' => intval($valor * 100),
            'date_adjustment' => $datafinal,
            'description' => $description
        ];

        $resultado = 'Basic '.$this->access_token_mgm;

        $headers = array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer '.$this->access_token_mgm
        );

        $this->geraajustesPayload($store, $gatewaySubaccount, $valor, $tipoAjuste , $description);

        return $this->postRequest($this->getUrlAPI_V1() . 'v1/mgm/adjustment/request-adjustments', $payload, $headers);

    }

    public function gerapagamento($transferencia){

        $order_item_release  = array(
            'id' => $transferencia['item_id'],
            'amount' => $transferencia['installment_amount']
        );

        $newdate=date("Y-m-d H:i:s", strtotime("+0 days"));
        $dataTratada = explode(" ",$newdate);
        $datafinal = $dataTratada[0]."T".$dataTratada[1]."Z";

        $payload = [
            'subseller_id' => $transferencia['item_id'],
            'release_payment_date' => $datafinal,
            'order_item_release' => $order_item_release ,
        ];

        $resultado = 'Basic '.$this->access_token_oob;

        $headers = array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer '.$this->access_token_oob
        );

        $this->gerapagamentoPayload($transferencia);

        return $this->postRequest($this->url_api_v2 . 'v2/payments/'.$transferencia['payment_id'].'/release', $payload, $headers);
        // return $this->postRequest($this->url_api_v2 . 'v2/marketplace/payments/'.$transferencia['payment_id'].'/release', $payload, $headers);

    }

    public function gerapagamentooracle($transferencia){

        $order_item_release  = array(
            'id' => $transferencia['item_id'],
            'amount' => $transferencia['installment_amount']
        );

        $newdate=date("Y-m-d H:i:s", strtotime("+0 days"));
        $dataTratada = explode(" ",$newdate);
        $datafinal = $dataTratada[0]."T".$dataTratada[1]."Z";

        $payload = [
            'subseller_id' => $transferencia['subseller_id'],
            'release_payment_date' => $datafinal,
            'order_item_release' => $order_item_release ,
        ];

        $resultado = 'Basic '.$this->access_token_oob;

        $headers = array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer '.$this->access_token_oob
        );

        $this->gerapagamentoPayloadOracle($transferencia);

        // return $this->postRequest($this->url_api_v2 . 'v1/marketplace/payments/'.$transferencia['payment_id'].'/release', $payload, $headers);
        return $this->postRequest($this->url_api_v2 . 'v2/payments/'.$transferencia['payment_id'].'/release', $payload, $headers);

    }

    public function geraextrato($dataInicio, $dataFim){

        $headers = array(
            'Authorization: Bearer '.$this->access_token_mgm
        );

        return $this->getRequest($this->getUrlAPI_V1() . 'v1/mgm/statement?seller_id='.$this->seller_id.'&schedule_date_init='.$dataInicio.'&schedule_date_end='.$dataFim.'', null, $headers);
    }

    public function geraextrato2($dataInicio, $dataFim){

        $headers = array(
            'Authorization: Bearer '.$this->access_token_mgm
        );

        return $this->getRequest($this->getUrlAPI_V1() . 'v1/mgm/statement?seller_id='.$this->seller_id.'&liquidation_date_init='.$dataInicio.'&liquidation_date_end='.$dataFim.'', null, $headers);

    }

    public function geraextrato3($dataInicio, $dataFim, $subsellerid, $block_payments = null){

        $headers = array(
            'Authorization: Bearer '.$this->access_token_mgm
        );

        $blocoPagamento = "";

        if($block_payments <> null){
            $blocoPagamento = "&blocks_codes=".$block_payments;
        }

        return $this->getRequest($this->getUrlAPI_V1() . 'v2/mgm/statement?seller_id='.$this->seller_id.$blocoPagamento.'&subseller_id='.$subsellerid.'&schedule_date_init='.$dataInicio.'&schedule_date_end='.$dataFim.'', null, $headers);
    }

    public function geraextratoajuste2($dataInicio, $dataFim){

        $headers = array(
            'Authorization: Bearer '.$this->access_token_mgm
        );

        return $this->getRequest($this->getUrlAPI_V1() . 'v2/mgm/statement?seller_id='.$this->seller_id.'&liquidation_date_init='.$dataInicio.'&liquidation_date_end='.$dataFim.'', null, $headers);

    }

    public function geraextratoajuste3($dataInicio, $dataFim){

        $headers = array(
            'Authorization: Bearer '.$this->access_token_mgm
        );

        return $this->getRequest($this->getUrlAPI_V1() . 'v2/mgm/statement?seller_id='.$this->seller_id.'&schedule_date_init='.$dataInicio.'&schedule_date_end='.$dataFim.'', null, $headers);

    }

    public function geraextratoajustePaginado($dataInicio, $dataFim, $page){

        $headers = array(
            'Authorization: Bearer '.$this->access_token_mgm
        );

        return $this->getRequest($this->getUrlAPI_V1() . 'v2/mgm/statement/get-paginated-statement?seller_id='.$this->seller_id.'&schedule_date_init='.$dataInicio.'&schedule_date_end='.$dataFim.'&page='.$page.'&rows_amount=1000', null, $headers);

    }

    public function geraextratoTransactionDate($dataInicio, $dataFim, $page){

        $headers = array(
            'Authorization: Bearer '.$this->access_token_mgm
        );

        return $this->getRequest($this->getUrlAPI_V1() . 'v2/mgm/statement/get-paginated-statement?seller_id='.$this->seller_id.'&transaction_date_init='.$dataInicio.'&transaction_date_end='.$dataFim.'&page='.$page.'&rows_amount=1000', null, $headers);

    }

    public function gerasaldossubcontasgetnet($subsellerid, $startDate, $endDate){

        if($subsellerid == null){
            return false;
        }

        $headers = array(
            'Authorization: Bearer '.$this->access_token_mgm
        );
        
        return $this->getRequest($this->getUrlAPI_V1() . 'v1/mgm/balances/balance/summary?seller_id='.$this->seller_id.'&subseller_id='.$subsellerid.'&balance_start_date='.$startDate.'&balance_end_date='.$endDate, null, $headers);
    }

    

    public function geraextratoporpedido($order_id){

        $headers = array(
            'Authorization: Bearer '.$this->access_token_mgm
        );

        return $this->getRequest($this->getUrlAPI_V1() . 'v2/mgm/statement?seller_id='.$this->seller_id.'&order_id='.$order_id.'', null, $headers);

    }

    public function gerapagamentorejeitado($dataInicio, $dataFim, $subsellerid){

        $headers = array(
            'Authorization: Bearer '.$this->access_token_mgm
        );

        return $this->getRequest($this->getUrlAPI_V1() . 'v1/mgm/rejected-payments?MerchantId='.$this->merchant_id.'&subseller_id='.$subsellerid.'&status=rejected&date=between('.$dataInicio.','.$dataFim.')&status=rejected', null, $headers);
        // return $this->getRequest($this->getUrlAPI_V1() . 'v1/mgm/rejected-payments?MerchantId=9687496&subseller_id=700538929&status=rejected&date=between(2022-03-14,2022-03-14)&status=rejected', null, $headers);
    }


    public function callbacksubaccount(array $store, array $gatewaySubaccount){

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->access_token_mgm
        );

        return $this->getRequest($this->getUrlAPI_V1() . 'v1/mgm/pj/callback/'.$this->merchant_id.'/'.$store['CNPJ'], null, $headers);

    }

    /**
     * @param array $store
     * @param array $gatewaySubaccount
     * @return array
     */
    public function createRecipient(array $store): array
    {

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->access_token_mgm
        );

        $payload = $this->payloadcreateupdatestores($store, array() );

        return $this->postRequest($this->getUrlAPI_V1() . 'v1/mgm/pj/create-presubseller', $payload, $headers);

    }

    /**
     * @param array $store
     * @param array $gatewaySubaccount
     * @return array
     */
    public function updatesSubAccounts(array $store, array $gatewaySubaccount): array
    {

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->access_token_mgm
        );

        $payload = $this->payloadcreateupdatestores( $store,  $gatewaySubaccount);

        return $this->putRequest($this->getUrlAPI_V1() . 'v1/mgm/pj/update-subseller', $payload, $headers);

    }

    /**
     * @param array $store
     * @return string
     */
    private function getAccountTypeByStore(array $store): string
    {
        if ($store['account_type'] == 'Conta Corrente') {
            return 'C';
        }
        return 'P';
    }


    private function payloadcreateupdatestores(array $store, array $gatewaySubaccount){

        $IdVTEXParametro = $this->_CI->model_settings->getSettingDatabyName('desativa_parametro_idvtex_getnet');
        $parametroGetnetPaymentPlan = $this->_CI->model_settings->getSettingDatabyNameEmptyArray('getnet_payment_plan');
        $sellercenterAmbiente = $this->_CI->model_settings->getSettingDatabyName('sellercenter');

        if($parametroGetnetPaymentPlan['status'] == 1 && !empty($parametroGetnetPaymentPlan['value']) && is_numeric($parametroGetnetPaymentPlan['value'])){
            $paymentPlan = $parametroGetnetPaymentPlan['value'];
        }else{
            $paymentPlan = 8;
        }

        //Alteração pontual para Privalia de alguns sellers serem
        if($sellercenterAmbiente['value'] == 'privalia'){
            $lojasAntecipadasPrivalia = $this->_CI->model_settings->getSettingDatabyNameEmptyArray('antecipated_stores_privalia');
            if($lojasAntecipadasPrivalia['status'] == 1){
                $lojasValidas = explode(";",$lojasAntecipadasPrivalia['value']);
                foreach($lojasValidas as $lojasValida){
                    if($lojasValida == $store['id']){
                        $paymentPlan = 2;
                    }
                }
            }
        }

        $arr_agency = explode('-', $store['agency']);
        $arr_account = explode('-', $store['account']);

        if($store['flag_bloqueio_repasse'] == "N"){
            $block_payments = "N";
        }else{
            $block_payments = "S";
        }

        if(strpos($store['account'],"-") === false){
            $arr_account[1] = 0;
        }

        $tipoConta = $this->getAccountTypeByStore($store);

        if($store['responsable_birth_date'] == "0000-00-00"){
            $responsable_birth_date = "1990-01-01";
        }else{
            $responsable_birth_date = $store['responsable_birth_date'];
        }

        if($store['inscricao_estadual'] == "0"){
            $inscricao_estadual = "ISENTO";
        }else{
            $inscricao_estadual = str_replace("\\","",str_replace("/","",str_replace("-","",str_replace(".","",$store['inscricao_estadual']))));
        }

        $endereco = array(
            'street' => $store['address'],
            'number' => $store['addr_num'],
            'district' => $store['addr_neigh'],
            'city' => $store['addr_city'],
            'state' => $store['addr_uf'],
            'postal_code' => $store['zipcode'],
            'suite' => $store['addr_compl'],
            'country' => $store['country']
        );

        $telefone = array(
            'area_code' => substr($store['phone_1'], 0, 2),
            'phone_number' => substr($store['phone_1'], 2, strlen($store['phone_1'])-2)
        );

        $tipoTelefone = "";

        if(strlen($store['phone_1']) == 10){
            $tipoTelefone = 'phone';
        }else{
            $tipoTelefone = 'cellphone';
        }

        $bank_number = $this->_CI->model_banks->getBankNumber($store['bank']);

        if($bank_number == "104"){
            $conta = $arr_account[0];
            $zeros = 8 - strlen($conta);
            if($zeros > 0){
                for($i=1;$i<=$zeros;$i++){
                    $conta = "0".$conta;
                }
            }

            if($tipoConta = "C"){
                $conta = "3".$conta;
            }else{
                $conta = "22".$conta;
            }

            $unique_account = array(
                'bank' => $bank_number,
                'agency' => $arr_agency[0],
                'account' => $conta,
                'account_type' => $tipoConta,
                'account_digit' => $arr_account[1]
            );

            $bankAccount = array(
                'type_accounts' => 'unique',
                'unique_account' => $unique_account
            );

        }else{
            $unique_account = array(
                'bank' => $bank_number,
                'agency' => $arr_agency[0],
                'account' => $arr_account[0],
                'account_type' => $tipoConta,
                'account_digit' => $arr_account[1]
            );

            $bankAccount = array(
                'type_accounts' => 'unique',
                'unique_account' => $unique_account
            );
        }

        $legal_representative = array(
            'name' => $store['responsible_name'],
            'birth_date' => $responsable_birth_date,
            'legal_document_number' => str_replace("-","",str_replace(".","",$store['responsible_cpf'])),
            'ppe_indication' => 'not_applied'
        );

        $subsellerid_ext = "";

        $variavelAmbiente = $this->_CI->model_settings->getSettingDatabyName('vtex_seller_prefix');

        if($store['id'] < 10){
            if($variavelAmbiente['status'] == "1"){
                $subsellerid_ext = $variavelAmbiente['value']."00".$store['id'];
            }else{
                $subsellerid_ext = "00".$store['id'];
            }
        }elseif($store['id'] < 100){
            if($variavelAmbiente['status'] == "1"){
                $subsellerid_ext = $variavelAmbiente['value']."0".$store['id'];
            }else{
                $subsellerid_ext = "0".$store['id'];
            }
        }else{
            if($variavelAmbiente['status'] == "1"){
                if($variavelAmbiente['status'] == "1"){
                    $subsellerid_ext = $variavelAmbiente['value']."".$store['id'];
                }else{
                    $subsellerid_ext = "".$store['id'];
                }
            }
        }

        $subsellerid_ext = preg_replace("/[^A-Za-z0-9 ]/", '', $subsellerid_ext);

        // Parâmetro ID VTEX - FIN-910
        if($IdVTEXParametro){
            if($IdVTEXParametro['status'] == 1){
                $subsellerid_ext =  null;
            }
        }

        if(empty($gatewaySubaccount)){
            $payload = [
                'merchant_id' => $this->merchant_id,
                'subsellerid_ext' => $subsellerid_ext,
                'legal_document_number' => str_replace("\\","",str_replace("/","",str_replace("-","",str_replace(".","",$store['CNPJ'])))),
                'legal_name' => $store['raz_social'],
                'trade_name' => $store['raz_social'],
                'state_fiscal_document_number' => $inscricao_estadual,
                'email' => $store['responsible_email'],
                'business_address' => $endereco,
                $tipoTelefone => $telefone,
                'bank_accounts' => $bankAccount,
                'accepted_contract' => 'S',
                'marketplace_store' => 'N',
                'payment_plan' => $paymentPlan,
                'block_payments' => $block_payments,
                // 'federal_registration_status' => 'active',
            ];
        }else{
            $payload = [
                'merchant_id' => $this->merchant_id,
                'subsellerid_ext' => $subsellerid_ext,
                'legal_document_number' => str_replace("\\","",str_replace("/","",str_replace("-","",str_replace(".","",$store['CNPJ'])))),
                'subseller_id' => $gatewaySubaccount['subseller_id'],
                'legal_name' => $store['raz_social'],
                'trade_name' => $store['raz_social'],
                'state_fiscal_document_number' => $inscricao_estadual,
                'email' => $store['responsible_email'],
                'business_address' => $endereco,
                $tipoTelefone => $telefone,
                'bank_accounts' => $bankAccount,
                'accepted_contract' => 'S',
                'marketplace_store' => 'N',
                'payment_plan' => $paymentPlan,
                'block_payments' => $block_payments,
                // 'federal_registration_status' => 'active',
            ];
        }

        print_r($payload);

        return $payload;

    }

    /**
     * @param $url
     * @param $post_data
     * @return array
     */
    private function putRequest($url, $post_data, $headers): array
    {

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($curl, CURLOPT_ENCODING ,"");
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err     = curl_errno( $curl );
        $errmsg  = curl_error( $curl );
        $header  = curl_getinfo( $curl );
        $header['httpcode']   = $response_code;
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $response;
        $header['payload_request'] = json_encode($post_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $header;

    }

    /**
     * @return string
     */
    private function getUrlAPI_V1(): string
    {
//        return (ENVIRONMENT === 'production') ? $this->url_api_v1 : $this->url_api_v1;
        return $this->url_api_v1;
    }

    /**
     * @param $url
     * @param $post_data
     * @return array
     */
    private function postRequest($url, $post_data, $headers): array
    {

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_ENCODING ,"");
        $response = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err     = curl_errno( $curl );
        $errmsg  = curl_error( $curl );
        $header  = curl_getinfo( $curl );
        $header['httpcode']   = $response_code;
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $response;
        $header['payload_request'] = json_encode($post_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $header;

    }

    /**
     * @param $url
     * @return array
     */
    private function getRequest($url, $payload, $headers): array
    {

        array_push($headers, "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36");

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_ENCODING ,"");
        $response = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err     = curl_errno( $curl );
        $errmsg  = curl_error( $curl );
        $header  = curl_getinfo( $curl );
        $header['httpcode']   = $response_code;
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $response;
        $header['payload_request'] = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $header;

        //return curlGet($url, $payload);

    }

    /**
     * @param int $conciliation_id
     * @param string $transfer_type
     */
    public function processTransfers(int $conciliation_id, string $transfer_type = 'BANK'): void
    {

        $transfer_error = 0;
        $conciliation_status = 23; // code for 100% success

        $transfers_array = $this->_CI->model_transfer->getTransfers($conciliation_id);

        if (!$transfers_array) {
            return;
        }

        foreach ($transfers_array as $transfer) {

            $receiver = $this->_CI->model_gateway->getSubAccountByStoreId($transfer['store_id'])['gateway_account_id'];

            if (!$receiver) {
                echo "Subconta não encontrada para store_id: {$transfer['store_id']}" . PHP_EOL;
                $transfer_error++;
                continue;
            }

            $amount = moneyToInt($transfer['valor_seller']);

            //Não podemos transferir valor negativo nem zerado
            if ($amount <= 0) {

                $this->saveOrdersStatements($transfer, $amount);

                $this->_CI->model_repasse->updateTransferStatus(false, $transfer['id']);

                echo "Valor $amount inválido, não iremos processar esse valor" . PHP_EOL;

                continue;

            }

            $update_transfer_status = true;

            if ($this->createTransfer($transfer, $receiver, $amount, $transfer_type)) {

                //Salvando na tabela iugu_repasse antes de alterar o status, pois só salvamos lá enquanto estiver no status repasse 21
                $this->saveOrdersStatements($transfer, $amount);

            } else {

                $update_transfer_status = false;

                $transfer_error++;

            }

            $this->_CI->model_repasse->updateTransferStatus($update_transfer_status, $transfer['id']);

        }

        if ($transfer_error > 0) {
            $conciliation_status = 27; //code for processed with some errors
        }

        $this->_CI->model_conciliation->updateConciliationStatus($conciliation_id, $conciliation_status);

    }

    /**
     * @param array $transfer
     * @param string $receiver
     * @param int $amount
     * @param string $transfer_type
     * @return int|null
     */
    public function createTransfer(array $transfer, string $receiver, int $amount, string $transfer_type = 'BANK'): ?int
    {

        //Subtraindo o custo da transferência da conta no valor a ser transferido
        $amount -= $this->getTransferCost();

        /**
         * @var Model_gateway_transfers $modelGatewayTransfers
         */
        $modelGatewayTransfers = $this->_CI->model_gateway_transfers;

        $gatewayId = $this->_CI->model_gateway->getGatewayId('pagarme');

        $transferId = $modelGatewayTransfers->createPreTransfer($transfer['id'], $gatewayId, $receiver, $amount, $transfer_type, $receiver);

        /**
         * Validando se o valor a ser transferido é >= que o minimo permitido pelo gateway
         */
        if ($amount < $this->minimunTransferValue && $transfer_type == 'BANK') {

            echo $error = "Transferência até " . intToMoney($this->getTransferCost(), 'R$ ') . " não permitida, " .
                "Valor a transferir: " . intToMoney($amount, 'R$ ') . " " .
                "(Valor a sacar: " . intToMoney($amount, 'R$ ') . " - taxa transferência: " . intToMoney($this->getTransferCost(), 'R$ ') . ") " .
                "- recipient_id: $receiver" . PHP_EOL;

            $transfer_data = [
                'result_status' => 0,
                'status' => 'ERROR',
                'result_message' => $error,
                'result_number' => 0,
            ];

            $this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);

            return null;

        }

        $transferDepositArray = [
            "amount" => $amount,
            "recipient_id" => $receiver,
            "metadata" => [
                "contiliation_id" => $transfer['conciliacao_id'],
                "transfer_id" => $transferId,
            ]
        ];

        $url = $this->getUrlAPI_V1() . '/transfers';

        $transfer_response_json = $this->postRequest($url, $transferDepositArray);

        $responseCode = $transfer_response_json["httpcode"];
        $transfer_response_array = json_decode($transfer_response_json['content'], true);

        //Se tivemos resposta na faixa do 200 a transação ocorreu com sucesso
        if ($responseCode >= 200 && $responseCode < 300) {

            echo "Transferencia " . $transferId . ", conciliação id: {$transfer['conciliacao_id']}, recipient_id: $receiver, " .
                "valor: $amount efetuada com sucesso. Data estimada do pagamento: {$transfer_response_array['funding_estimated_date']}" . PHP_EOL;

            $transfer_data = [
                'result_status' => 1,
                'sender_id' => $transfer_response_array['source_id'],
                'fee' => $transfer_response_array['fee'],
                'amount' => $transfer_response_array['amount'],
                'transfer_gateway_id' => $transfer_response_array['id'],
                'status' => $transfer_response_array['status'],
                'funding_estimated_date' => $transfer_response_array['funding_estimated_date'],
                'result_number' => $responseCode
            ];

            //Salvando os dados da transferência na base
            $this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);

            return $transfer_response_array['id'];

            //Ocorreu erro na transação
        } else {

            $error = json_encode($transfer_response_array['errors']);

            $transfer_data = [
                'result_status' => 0,
                'status' => 'ERROR',
                'result_message' => $error,
                'result_number' => $responseCode,
            ];

            $balance = $this->getBalance($receiver);

            echo "Transferencia " . $transferId . ", conciliação id: {$transfer['conciliacao_id']}, " .
                "valor $amount, Saldo restante: {$balance->available->amount} - retornou erro: " . json_encode($transfer_response_array) . PHP_EOL;

            $this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);

            echo $error . PHP_EOL;

        }

        return null;

    }

    /**
     * @return int
     */
    public function getTransferCost(): int
    {
        if (is_null($this->transferCost)) {
            $configurations = $this->getConfigurations();
            $this->transferCost = (int)$configurations['pricing']['transfers']['doc'];
        }
        return $this->transferCost;
    }

    /**
     * @return array
     */
    public function getConfigurations(): array
    {
        return json_decode($this->getRequest($this->getUrlAPI_V1() . '/company')['content'], true);
    }

    /**
     * @param string $recipientId
     * @return stdClass
     */
    public function getBalance(string $recipientId): stdClass
    {

        $url = $this->getUrlAPI_V1() . '/recipients/' . $recipientId . '/balance';

        $result = $this->getRequest($url);

        if ($result['httpcode'] != 200 || !$result['content']) {
            echo "Resposta código {$result["httpcode"]}, body: {$result['content']} na consulta do balanço do recipient_id: $recipientId" . PHP_EOL;
        }

        return json_decode($result['content']);

    }

    /**
     * @param string $recipientId
     * @return array
     */
    public function getTransferHistory(string $recipientId): array
    {

        $url = $this->getUrlAPI_V1() . '/recipients/' . $recipientId . '/balance/operations';

        $response = $this->getRequest($url);

        return json_decode($response['content']);

    }

    public function generateTestCreditToRecipientId(float $amount, string $recipiendId): bool
    {

        $payload = '{
            "amount": ' . moneyToInt($amount) . ',
            "payment_method": "boleto",
            "customer": {
                "type": "individual",
                "country": "br",
                "name": "Daenerys Targaryen",
                "documents": [
                    {
                        "type": "cpf",
                        "number": "00000000000"
                    }
                ]
            },
            "split_rules": [
                {
                    "percentage": "100",
                    "recipient_id": "' . $recipiendId . '"
                }
            ]
        }';

        $payload = json_decode($payload, true);

        $result = $this->postRequest($this->getUrlAPI_V1() . '/transactions', $payload);

        //Se retornou qualquer coisa diferente de 200 é por que teve algum problema e não foi possível processar
        if ($result["httpcode"] != 200) {
            echo "Resposta código {$result["httpcode"]} na geração do boleto no valor de $amount. Erro: " . json_encode($result['content']) . PHP_EOL;
            return false;
        }

        $response_array = json_decode($result['content'], true);

        //Confirmando o pagamento do boleto para que entre o crédito
        $payloadPut = [];
        $payloadPut['status'] = 'paid';

        $url_put = $this->getUrlAPI_V1() . '/transactions/' . $response_array['id'];

        $response_put = $this->putRequest($url_put, $payloadPut);

        //Se retornou qualquer coisa diferente de 200 é por que teve algum problema e não foi possível processar
        if ($response_put["httpcode"] != 200) {
            echo "Resposta código {$response_put["httpcode"]} na confirmação do pagamento do boleto id {$response_array['id']}" . PHP_EOL;
            return false;
        }

        return true;

    }


    public function geraajustesPayload($store, $gatewaySubaccount, $valor, $tipoAjuste = null, $description = null){

        if($tipoAjuste == null){
            $tipoAjuste  = 2;
        }

        if($description == null){
            $description  = 'Ajuste Painel Juridico';
        }

        $newdate=date("Y-m-d H:i:s", strtotime("+1 days"));
        $dataTratada = explode(" ",$newdate);
        $datafinal = $dataTratada[0]."T".$dataTratada[1]."Z";
        //  'date_adjustment' => '2021-10-15T14:30:00Z',

        $payload = [
            'seller_id' => $this->seller_id,
            'subseller_id' => $gatewaySubaccount['subseller_id'],
            'merchant_id' => $this->merchant_id,
            'type_adjustment' => $tipoAjuste,
            'amount' => intval($valor * 100),
            'date_adjustment' => $datafinal,
            'description' => $description
        ];

        $resultado = 'Basic '.$this->access_token_mgm;

        $headers = array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer '.$this->access_token_mgm
        );

        echo "Header:\n";
        print_r($headers);
        echo "URL:\n";
        echo $this->getUrlAPI_V1() . 'v1/mgm/adjustment/request-adjustments';
        echo "\n";
        echo "Payload\n";
        print_r($payload);

        return true;

    }

    public function gerapagamentoPayload($transferencia){

        $order_item_release  = array(
            'id' => $transferencia['item_id'],
            'amount' => $transferencia['installment_amount']
        );

        $newdate=date("Y-m-d H:i:s", strtotime("+0 days"));
        $dataTratada = explode(" ",$newdate);
        $datafinal = $dataTratada[0]."T".$dataTratada[1]."Z";

        $payload = [
            'subseller_id' => $transferencia['item_id'],
            'release_payment_date' => $datafinal,
            'order_item_release' => $order_item_release ,
        ];

        $resultado = 'Basic '.$this->access_token_oob;

        $headers = array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer '.$this->access_token_oob
        );

        echo "Header:\n";
        print_r($headers);
        echo "URL:\n";
        echo $this->url_api_v2 . 'v1/marketplace/payments/'.$transferencia['payment_id'].'/release';
        echo "\n";
        echo "Payload\n";
        print_r($payload);

        return true;

    }

    public function gerapagamentoPayloadOracle($transferencia){

        $order_item_release  = array(
            'id' => $transferencia['item_id'],
            'amount' => $transferencia['installment_amount']
        );

        $newdate=date("Y-m-d H:i:s", strtotime("+1 days"));
        $dataTratada = explode(" ",$newdate);
        $datafinal = $dataTratada[0]."T".$dataTratada[1]."Z";

        $payload = [
            'subseller_id' => $transferencia['subseller_id'],
            'release_payment_date' => $datafinal,
            'order_item_release' => $order_item_release ,
        ];

        $resultado = 'Basic '.$this->access_token_oob;

        $headers = array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer '.$this->access_token_oob
        );

        echo "Header:\n";
        print_r($headers);
        echo "URL:\n";
        echo $this->url_api_v2 . 'v2/payments/'.$transferencia['payment_id'].'/release';
        echo "\n";
        echo "Payload\n";
        print_r($payload);

        return true;

    }


    //metodo para puxar dados da tabela de mdr padrao
    public function getDefaultMDR($order_id): float
    {
        //FIN-722
        return $this->_CI->model_orders_payment->getDefaultMDR($order_id);
    }

    public function validateAuthData()
    {

        $accessTokens_oob = $this->getaccesstokensoob();
        $accessTokens_mgm = $this->getaccesstokensmgm();

        if ($accessTokens_oob['httpcode'] !== 200){
            return [
                'result' => 'error',
                'message' => 'Dados de autenticação OOB Inválidos'
            ];
        }
        $response_oob = json_decode($accessTokens_oob['content'], true);
        $this->_CI->model_gateway_settings->updateSettings('access_token_oob',$response_oob['access_token']);

        if ($accessTokens_mgm['httpcode'] !== 200){
            return [
                'result' => 'error',
                'message' => 'Dados de autenticação MGM Inválidos'
            ];
        }
        $response_mgm = json_decode($accessTokens_mgm['content'], true);
        $this->_CI->model_gateway_settings->updateSettings('access_token_mgm',$response_mgm['access_token']);

        $accessTokensMgm = json_decode($accessTokens_mgm['content'], true);

        //Validando seller_id
        $headers = array(
            'Authorization: Bearer '.$accessTokensMgm['access_token']
        );

        $seller_id_response = $this->getRequest($this->getUrlAPI_V1() . 'v1/mgm/balances/balance/summary?seller_id='.$this->seller_id.'&balance_start_date=2020-01-01&balance_end_date=2020-01-02', null, $headers);

        if ($seller_id_response['httpcode'] !== 200){
            return [
                'result' => 'error',
                'message' => 'Dados de autenticação válidados, mas o código do Seller ID não foi encontrado'
            ];
        }

        $merchant_id_response = $this->getRequest($this->getUrlAPI_V1() . 'v1/mgm/pj/consult/paymentplans/'.$this->merchant_id, null, $headers);

        if ($merchant_id_response['httpcode'] !== 200){
            return [
                'result' => 'error',
                'message' => 'Dados de autenticação válidados, mas o código do Merchant ID não foi encontrado'
            ];
        }

        return [
            'result' => 'success',
            'message' => ''
        ];

    }

}