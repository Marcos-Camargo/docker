<?php

require APPPATH . "controllers/Api/V1/API.php";

class Subscriptions extends API
{
    private $header_user = 'iugu_plans';
    private $header_pass = 'XS6MnTMCSKstZqel2A4zkAm';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('model_iugu');		
	}

    public function index_get()
    {
        return $this->response(array('success' => false, "message" => 'GET not accepted'), REST_Controller::HTTP_BAD_REQUEST);
    }


    //braun
    public function index_post()
    {
        $iugu_response = $iugu_billing_array = $seller_plan_data = [];

        if (!$this->checkHeaderAuth())
        {
            return $this->response(array('success' => false, "message" => 'Not authorized'), REST_Controller::HTTP_UNAUTHORIZED);
        }

        parse_str(file_get_contents('php://input'), $iugu_response);

        if (!empty($iugu_response))
        {
            $plan_data = $iugu_response['data'];

            $seller_plan_data = $this->model_iugu->getSellerPlanData($plan_data['subscription_id']);

            if(empty($seller_plan_data))
            {
                // return $this->response(array('success' => false, "message" => 'Subscription not Found'), REST_Controller::HTTP_BAD_REQUEST);
                $seller_plan_data['store_id'] = null;
                $seller_plan_data['plan_id'] = null;
            }

            $iugu_billing_array = [
                'store_id' => $seller_plan_data['store_id'],
                'plan_id' => $seller_plan_data['plan_id'],
                'invoice_id' => $plan_data['id'],
                'subscription_id' => $plan_data['subscription_id'],
                'iugu_status' => $plan_data['status'],
            ];

            if ($iugu_response['event'] == 'invoice.created')
            {
                $iugu_billing_array = array_merge($iugu_billing_array, [
                    'event' => $iugu_response['event']
                ]);
            }
            else if ($iugu_response['event'] == 'invoice.released')
            {
                $iugu_billing_array = array_merge($iugu_billing_array, [
                    'status' => 'success',
                    'installments' => $plan_data['number_of_installments'],
                    'amount' => ($plan_data['amount'] * 100),
                    'payment_method' => $plan_data['payment_method'],
                    'event' => $iugu_response['event']
                ]);
            }
            else if ($iugu_response['event'] == 'invoice.status_changed')
            {
                if ($plan_data['status'] == 'paid')
                {
                    $iugu_billing_array = array_merge($iugu_billing_array, [                    
                        'status' => 'success',
                    ]);
                }

                $iugu_billing_array = array_merge($iugu_billing_array, [                    
                    'payment_method' => $plan_data['payment_method'],
                    'paid_at' => $plan_data['paid_at'],
                    'payer_cpf_cnpj' => $plan_data['payer_cpf_cnpj'],
                    'pix_end_to_end_id' => $plan_data['pix_end_to_end_id'],
                    'paid_cents' => $plan_data['paid_cents'],
                    'event' => $iugu_response['event']
                ]);
            }
            else
            {
                $iugu_billing_array = array_merge($iugu_billing_array, [  
                    'installments' => $plan_data['number_of_installments'],
                    'amount' => ($plan_data['amount'] * 100),
                    'payment_method' => $plan_data['payment_method'],
                    'paid_at' => $plan_data['paid_at'],
                    'payer_cpf_cnpj' => $plan_data['payer_cpf_cnpj'],
                    'pix_end_to_end_id' => $plan_data['pix_end_to_end_id'],
                    'paid_cents' => $plan_data['paid_cents'],
                    'event' => $iugu_response['event']
                ]);
            }

            if ($this->model_iugu->saveBillingData($iugu_billing_array))
            {
                return $this->response(array('success' => true, 'result' => 'Event Saved'), REST_Controller::HTTP_OK);
            }
            else
            {
                return $this->response(array('success' => false, "message" => 'Error Saving the Event'), REST_Controller::HTTP_BAD_REQUEST);
            }
        }
    }


    //braun
    public function checkHeaderAuth()
    {
        if ($_SERVER['PHP_AUTH_USER'] == $this->header_user && $_SERVER['PHP_AUTH_PW'] == $this->header_pass)
        {
            return true;
        }
        
        return false;
    }

}