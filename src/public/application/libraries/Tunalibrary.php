<?php /** @noinspection DuplicatedCode */

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . "libraries/GatewayPaymentLibrary.php";

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;

class Tunalibrary extends GatewayPaymentLibrary
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
        $this->_CI->load->model('model_integrations');

        $this->gateway_name = Model_gateway::TUNA;
        $this->recarregacredenciais();

    }

    public function geracancelamentotuna($orderArray, $valorEstorno = null){

        $integration = $this->_CI->model_integrations->getIntegrationsbyStoreId($orderArray['store_id']);
        $integration = json_decode($integration[0]['auth_data']);

        $splits[0]['MerchantID'] = $integration->seller_id;

        $amount = $orderArray['gross_amount'];

        if($valorEstorno !== null){
            // $splits[0]['Amount'] = (string)$valorEstorno;
            $splits[0]['Amount'] = $valorEstorno;
            $amount = $valorEstorno;
        }else{
            $splits[0]['Amount'] = $orderArray['gross_amount'];
        }


        $cardsDetail[0] = array('amount' => $amount,
                            'methodId'          => '0',
                            'Splits' => $splits,
                        );
        
        $payload = array(
            'json' => array(
                'partnerUniqueID'   => substr($orderArray['numero_marketplace'],0,13),
                'cardsDetail'       => $cardsDetail,
                'paymentDate'       => substr($orderArray['data_pago'],0,10),
                
            ),
            'headers' =>  [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-tuna-account' => $this->tuna_account,
                'x-tuna-apptoken' => $this->tuna_apptoken,
            ]
        );
        
        return $this->postRequestGuzzle($this->url_endpoint . '/api/Payment/Cancel', $payload);

    }

    public function recarregacredenciais(){
        $this->loadSettings();
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

}