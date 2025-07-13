<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Model_iugu $model_iugu
 */

class IuguLibrary extends Admin_Controller
{
    private $result;
    private $responseCode;
    private $billing_days;

    public function __construct()
	{
        $this->load->model('model_iugu');
        $this->load->model('model_gateway');
        $this->load->model('model_gateway_settings');

        $this->gateway_name     = Model_gateway::IUGU;
        $this->gateway_id       = $this->model_gateway->getGatewayId($this->gateway_name);

        $api_settings = $this->model_gateway_settings->getSettings($this->gateway_id);

        if (!empty($api_settings) && is_array($api_settings))
        {
            foreach ($api_settings as $key => $setting)
            {
                $this->{$setting['name']} = $setting['value'];
            }            
        }

        $billing_days = $this->model_settings->getValueIfAtiveByName('iugu_plans_billing_days');
        $this->billing_days = ($billing_days) ? intVal($billing_days) : 7; //evita erro com a api caso esqueçam de ativar ou configurar o parametro
        //Não esquecer de adicionar a URL da API na tabela de payment_gateway_settings api_url | https://api.iugu.com/v1 | 5
    }


    /**
     * Método mágico para utilização do CI_Controller.
     *
     * @param   string  $var    Propriedade para consulta.
     * @return  mixed           Objeto da propriedade.
     */
    public function __get(string $var)
    {
        return get_instance()->$var;
    }


    public function saveIuguPlan($new_plan, $saved_plan): ?array
    {
        $max_cycles = 0;

        if ($new_plan['plan_type'] == 'financed')
            $max_cycles = $new_plan['plan_installments'];

        $new_plan_array = [
           "payable_with"   => ["all"],
           "name"           => $new_plan['plan_title'],
           "identifier"     => "".$saved_plan."",
           "interval"       => 1,
           "interval_type"  => "months",
           "value_cents"    => $new_plan['installment_value'],
           "billing_days"   => $this->billing_days,
           "max_cycles"     => $max_cycles
        ];

        $new_plan_endpoint  = $this->api_url.'/plans';
        $new_plan_method    = 'POST';
        $new_plan_body      = json_encode($new_plan_array, JSON_UNESCAPED_UNICODE);

        $iugu_transaction_data = $this->iuguTransactions($new_plan_endpoint, $new_plan_method, $new_plan_body, false, $saved_plan);

        if ($this->responseCode == '200')
        {
            $this->log_data('general', __FUNCTION__, $this->result, 'I');

            $edit_plan = json_decode($this->result, true);

            $this->model_iugu->editPlan($saved_plan, $edit_plan);
        }
        else
        {
            $this->log_data('general', __FUNCTION__, $this->result, 'E');
        }

        return $iugu_transaction_data;
    }


    public function createSubscription($plan_id, $store_id, $iugu_plan_stores_id, $plan_date)
    {
        $iugu_subscription_data = [];

        $plan_data = $this->model_iugu->getPlanData($plan_id);
        $store_data = $this->model_iugu->getSubaccountData($store_id);

        $new_subscription_array = [
                "payable_with" => "all",
                "suspend_on_invoice_expired" => true, 
                "only_charge_on_due_date" => false, 
                "plan_identifier" => $plan_data['id'], 
                "customer_id" => $store_data['account_id'], 
                "expires_at" => $plan_date, 
                "ignore_due_email" => false                 
        ];

        $new_subscription_endpoint  = $this->api_url.'/subscriptions';
        $new_subscription_method    = 'POST';
        $new_subscription_body      = json_encode($new_subscription_array, JSON_UNESCAPED_UNICODE);

        $iugu_subscription_data = $this->iuguTransactions($new_subscription_endpoint, $new_subscription_method, $new_subscription_body, false, $store_id);
        
        if ($this->responseCode == '200')
        {
            $this->log_data('general', __FUNCTION__, $this->result, 'I');

            $edit_subscription = json_decode($this->result, true);
            $this->model_iugu->editSubscription($edit_subscription['id'], $iugu_plan_stores_id);
        }
        else
        {
            $this->log_data('general', __FUNCTION__, $this->result, 'E');
        }

        return $iugu_subscription_data;
    }


    public function removeSubscription($toggle_id, $store_id): ?array
    {
        $subscription_data = $this->model_iugu->getSubscriptionData($toggle_id);

        if (!empty($subscription_data))
        {
            $remove_subscription_endpoint  = $this->api_url.'/subscriptions/'.$subscription_data['subscription_id'];
            $remove_subscription_method    = 'DELETE';

            $remove_subscription_data = $this->iuguTransactions($remove_subscription_endpoint, $remove_subscription_method, null, false, $store_id);

            return (!empty($remove_subscription_data)) ? $remove_subscription_data : null;
        }
    }

    
    protected function iuguTransactions($url, $method = 'GET', $data = null, $headers = false, $store_id = null): ?array
    {
        if (!$headers || !is_array($headers))
        {
            $keys = $this->model_iugu->buscaChaveNoBanco();
            // $headers = array(
            //     "Accept: application/json",
            //     "Content-Type: application/json",
            //     "Authorization: Basic ".base64_encode(trim($keys['chave']).":")
            // );

            $headers = array(
                "Accept: application/json",
                "Content-Type: application/json",
                "Authorization: Basic ".trim($keys['chave'])
            );
        }

        $ch  = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT')
        {          
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
		
		if ($method == 'DELETE')
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');        

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);

        return ['url' => $url, 'method' => $method, 'data' => $data, 'headers' => $headers, 'store_id' => $store_id, 'result' => $this->result, 'response_code' => $this->responseCode];
    }


}