<?php /** @noinspection DuplicatedCode */

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . "libraries/GatewayPaymentLibrary.php";

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;

class Magalupaylibrary extends GatewayPaymentLibrary
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
        $this->_CI->load->model('model_parametrosmktplace');

        $this->gateway_name = Model_gateway::MAGALUPAY;
        $this->recarregacredenciais();

    }

    public function getaccesstokengestaocarteira(){

        $payload = array(
            'json' => array(
                'grant_type' => $this->grant_type,
                'client_secret' => $this->client_secret,
                'client_id' => $this->client_id,
                'scope' => $this->scope
            ),
            'headers' =>  [
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ]
        );

        return $this->postRequestGuzzle($this->url_autenticar_api_gestao_carteira . 'oauth/token', $payload);

    }

    public function getaccesstokenapionboarding(){
        
        $payload = array(
            'json' => [
                'grant_type' => $this->grant_type,
                'client_secret' => $this->client_secret,
                'client_id' => $this->client_id
            ],
            'headers' =>  [
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ]
        );

        return $this->postRequestGuzzle($this->url_autenticar_api_onboarding . 'auth/realms/B2B/protocol/openid-connect/token', $payload);

    }

    public function recarregacredenciais(){
        $this->loadSettings();
    }

    public function createRecipient(array $storeCompany): array
    {
        
        $arrayCadastro = $this->payloadcreateupdatestores($storeCompany, array() );

        $payload = array(
            'json' => $arrayCadastro,
            'headers' =>  [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->access_token_gc
            ]
        );

        return $this->postRequestGuzzle($this->url_access_api_onboarding . 'v1/onboarding/marketplace/'.$this->product.'/pj', $payload);

    }

    public function updateRecipient(array $storeCompany): array
    {
        
        $arrayCadastro = $this->payloadcreateupdatestores($storeCompany, array() );

        $payload = array(
            'json' => $arrayCadastro,
            'headers' =>  [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->access_token_gc
            ]
        );

        return $this->postRequestGuzzle($this->url_access_api_onboarding . 'v1/onboarding/marketplace/'.$this->product.'/update/pj/'. $arrayCadastro['document_number'], $payload);

    }

    private function payloadcreateupdatestores(array $store, array $gatewaySubaccount){

        $sellercenterAmbiente = $this->_CI->model_settings->getSettingDatabyName('sellercenter');

        $payload = [];
        
        $payload['document_number'] = str_replace("\\","",str_replace("/","",str_replace("-","",str_replace(".","",$store['store']['CNPJ']))));

        //Monta a informação bancária
        $arrayInfoBancarias = array();

        $arr_agency = explode('-', $store['store']['agency']);
        $arr_account = explode('-', $store['store']['account']);

        if(strpos($store['store']['account'],"-") === false){
            $arr_account[1] = 0;
        }

        $tipoConta = $this->getAccountTypeByStore($store['store']);

        $bank_number = $this->_CI->model_banks->getBankNumber($store['store']['bank']);

        $conta = $arr_account[0];
        if($bank_number == "104"){
            $conta = $arr_account[0];
            $zeros = 8 - strlen($conta);
            if($zeros > 0){
                for($i=1;$i<=$zeros;$i++){
                    $conta = "0".$conta;
                }
            }

            if($tipoConta == "C"){
                $conta = "3".$conta;
            }else{
                $conta = "22".$conta;
            }

        }

        $arrayInfoBancarias['document_number'] = str_replace("\\","",str_replace("/","",str_replace("-","",str_replace(".","",$store['store']['CNPJ']))));
        
        $arrayInfoBancarias['bank_code'] = $bank_number;
        $arrayInfoBancarias['bank_agency'] = $arr_agency[0];
        if(array_key_exists(1,$arr_agency)){
            $arrayInfoBancarias['bank_agency_digit'] = $arr_agency[1];
        }
        $arrayInfoBancarias['account'] = $conta;
        $arrayInfoBancarias['account_digit'] = $arr_account[1];
        $arrayInfoBancarias['bank_account_type'] = $tipoConta;

        $payload['bank_account'] = $arrayInfoBancarias;

        //Monta as informações de configuração de conta e recebimentos
        $array_recipient_config = array();

        $subsellerid_ext = "";

        $variavelAmbiente = $this->_CI->model_settings->getSettingDatabyName('vtex_seller_prefix');

        if($store['store']['id'] < 10){
            if($variavelAmbiente['status'] == "1"){
                $subsellerid_ext = $variavelAmbiente['value']."00".$store['store']['id'];
            }else{
                $subsellerid_ext = "00".$store['store']['id'];
            }
        }elseif($store['store']['id'] < 100){
            if($variavelAmbiente['status'] == "1"){
                $subsellerid_ext = $variavelAmbiente['value']."0".$store['store']['id'];
            }else{
                $subsellerid_ext = "0".$store['store']['id'];
            }
        }else{
            if($variavelAmbiente['store']['status'] == "1"){
                if($variavelAmbiente['status'] == "1"){
                    $subsellerid_ext = $variavelAmbiente['value']."".$store['store']['id'];
                }else{
                    $subsellerid_ext = "".$store['store']['id'];
                }
            }
        }

        $subsellerid_ext = preg_replace("/[^A-Za-z0-9 ]/", '', $subsellerid_ext);

        $arrayCiclos = explode(",",$this->_CI->model_parametrosmktplace->getDatasPagamentoMktplace()['data_pagamento']);

        if($store['store']['allow_payment_reconciliation_installments'] == 1 ){
            $array_recipient_config['auto_anticipate'] = false;
        }else{
            $array_recipient_config['auto_anticipate'] = true;
        }

        $array_recipient_config['auto_transfer'] = true;
        $array_recipient_config['transfer_periodicity'] = 'monthly';
        $array_recipient_config['transfer_days'] = $arrayCiclos;
        $array_recipient_config['reference_key'] = $subsellerid_ext;

        $payload['recipient_config'] = $array_recipient_config;

        //Monta as informações de Termos e condições
        $array_terms_conditions = array();

        $array_terms_conditions['accept'] = true;

        $payload['terms_conditions'] = $array_terms_conditions;

        $payload['create_bank_account'] = false;
        $payload['create_bank_account'] = false;
        

        //Monta as informações de PJ
        $array_info_pj = array();
        $array_info_pj_address = array();
        $array_info_pj_legal_person = array();

        if($store['store']['responsable_birth_date'] == "0000-00-00"){
            $responsable_birth_date = "1990-01-01";
        }else{
            $responsable_birth_date = $store['store']['responsable_birth_date'];
        }
        
        if($store['store']['inscricao_estadual'] == "0"){
            $inscricao_estadual = "ISENTO";
        }else{
            $inscricao_estadual = str_replace("\\","",str_replace("/","",str_replace("-","",str_replace(".","",$store['store']['inscricao_estadual']))));
        }

        $array_info_pj['name'] = $store['store']['name'];
        $array_info_pj['company_name'] = $store['store']['raz_social'];
        $array_info_pj['trading_name'] = $store['store']['name'];
        $array_info_pj['phone_number'] = "55".str_replace("\\","",str_replace("-","",str_replace(")","",str_replace("(","",$store['store']['phone_1']))));
        $array_info_pj['email'] = $store['store']['responsible_email'];
        $array_info_pj['register_number'] = $inscricao_estadual;

        $array_info_pj_address['zipcode'] = $store['store']['zipcode'];
        $array_info_pj_address['street'] = $store['store']['address'];
        $array_info_pj_address['complement'] = $store['store']['addr_compl'];
        $array_info_pj_address['number'] = $store['store']['addr_num'];
        $array_info_pj_address['city'] = $store['store']['addr_city'];
        $array_info_pj_address['district'] = $store['store']['addr_neigh'];
        $array_info_pj_address['state'] = $store['store']['addr_uf'];

        $array_info_pj_legal_person['full_name'] = $store['store']['responsible_name'];
        $array_info_pj_legal_person['document_number'] = str_replace("\\","",str_replace("/","",str_replace("-","",str_replace(".","",$store['store']['responsible_cpf']))));
        $array_info_pj_legal_person['birth_date'] = $responsable_birth_date;
        $array_info_pj_legal_person['mother_full_name'] = $store['store']['responsible_mother_name'];
        $array_info_pj_legal_person['phone_number'] = "55".str_replace("\\","",str_replace("-","",str_replace(")","",str_replace("(","",$store['store']['phone_1']))));
        $array_info_pj_legal_person['email'] = $store['store']['responsible_email'];
        $array_info_pj_legal_person['position'] = $store['store']['responsible_position'];
        
        $array_info_pj['address'] = $array_info_pj_address;
        $array_info_pj['legal_person'] = $array_info_pj_legal_person;

        $payload['info_pj'] = $array_info_pj;
        
        return $payload;

    }

    private function getAccountTypeByStore(array $store): string
    {
        if ($store['account_type'] == 'Conta Corrente') {
            return 'CHECKING_ACCOUNT';
        }
        return 'SAVINGS_ACCOUNT';
    }

    public function checkstatussubaccount($publicId): array
    {
        
        $payload = array(
            'headers' =>  [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->access_token_gc
            ]
        );
        
        return $this->getRequestGuzzle($this->url_access_api_onboarding . 'v1/onboarding/marketplace/'.$this->product.'/'.$publicId, $payload);

    }

    public function desbloqueiapedidomagalupay(array $transferArray): array
    {
        

        $payload = array(
            'json' => array(
                'order_reference_key' => $transferArray['order_reference_key'],
                'recipient_reference_key' => $transferArray['recipient_reference_key']
                
            ),
            'headers' =>  [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->access_token_gc
            ]
        );

        return $this->postRequestGuzzle($this->url_access_payment_api_gestao_carteira . 'v1/orders/unblock', $payload);

    }


    public function extratopedidomagalupay(){

        $payload = array(
            'headers' =>  [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer '.$this->access_token_gc,
                'x-tenant-id' => $this->tenant_id
            ]
        );
        
        return $this->getRequestGuzzle($this->url_access_getinfo_api_gestao_carteira . 'v3/order?page_size=100', $payload);

    }

    


    public function postRequestGuzzle($url,$payload){

        try {
            $client = new GuzzleHttp\Client();
            $response = $client->request('POST', $url, $payload);

            $header['httpcode']   = $response->getStatusCode();
            $header['content'] = json_decode($response->getBody()->getContents());
            $header['payload_request'] = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        } catch (ClientException | InvalidArgumentException | GuzzleException $e) {

            $exception = $e->getResponse();
            $header['httpcode']   = $exception->getStatusCode();
            $header['content'] = json_decode($exception->getBody()->getContents());
            $header['payload_request'] = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        }


        return $header;

    }

    public function getRequestGuzzle($url,$payload){

        try {
            $client = new GuzzleHttp\Client();
            $response = $client->request('GET', $url, $payload);

            $header['httpcode']   = $response->getStatusCode();
            $header['content'] = json_decode($response->getBody()->getContents());
            $header['payload_request'] = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        } catch (ClientException | InvalidArgumentException | GuzzleException $e) {

            $exception = $e->getResponse();
            $header['httpcode']   = $exception->getStatusCode();
            $header['content'] = json_decode($exception->getBody()->getContents());
            $header['payload_request'] = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        }

        return $header;

    }

    

    
    
    /**********************************************************************************************************************/

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

        $newdate=date("Y-m-d H:i:s", strtotime("+1 days"));
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

        $this->gerapagamentoPayloadOracle($transferencia);

        return $this->postRequest($this->url_api_v2 . 'v1/marketplace/payments/'.$transferencia['payment_id'].'/release', $payload, $headers);

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

        $newdate=date("Y-m-d H:i:s", strtotime("+1 days"));
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
        echo $this->url_api_v2 . 'v1/marketplace/payments/'.$transferencia['payment_id'].'/release';
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