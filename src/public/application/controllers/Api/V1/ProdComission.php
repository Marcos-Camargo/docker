<?php

require APPPATH . "controllers/Api/V1/API.php";

class ProdComission extends API
{

    public $setting_api_comission;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_orders_item');
        $this->load->model('model_campaigns_v2');        

        $this->setting_api_comission = $this->model_settings->getSettingDatabyName('api_comission');

        if (!$this->setting_api_comission || $this->setting_api_comission['status'] != 1)
            return $this->response(array('success' => false, "message" => $this->lang->line('api_resource_unavailable')), REST_Controller::HTTP_UNAUTHORIZED);        

        $this->header = array_change_key_case(getallheaders());
        $check_auth = $this->checkAuth($this->header); 
        
        if (!$check_auth[0])
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
    }


    public function index_post()
    {       
        
        $body_array = $this->cleanGet(json_decode(file_get_contents('php://input'), true));

        if (!is_array($body_array) || empty($body_array))
            return $this->response($this->lang->line('api_payload_not_valid'), REST_Controller::HTTP_BAD_REQUEST);

        $result_array = [];

        foreach ($body_array['items'] as $key => $val)
        {
            $product_data       = $this->model_orders_item->getItemDataBySkuMkt($val['sku']);
            $store_comissions   = $this->model_stores->getServiceChargeValue($product_data['product_id']);
            $has_new_comission  = $this->model_campaigns_v2->getNewComission($product_data['product_id'], $product_data['int_to']);
            
            $result_array['items'][$key] = array
            (
                'sku'               => $val['sku'],
                'seller'            => $val['seller'],
                'comission_product' => floatVal($store_comissions['service_charge_value'])
            );

            if (false !== $has_new_comission && $this->setting_api_comission['status'] == '1')
            {
                $new_comission              = floatVal($has_new_comission['new_comission']);
                $maximum_share_sale_price   = $this->to_cents($has_new_comission['maximum_share_sale_price']);
                
                if ($val['price'] <= $maximum_share_sale_price)
                    $result_array['items'][$key]['comission_product'] = $new_comission;               
            }

            $result_array['items'][$key]['comission_freight'] = (!empty($store_comissions['service_charge_freight_value'])) ? floatVal($store_comissions['service_charge_freight_value']) : 0;
        }

        $this->response(array('success' => true, 'result' => $result_array), REST_Controller::HTTP_OK);
    }


    function to_cents($value)
    {
        return intval(
            strval(floatval(
                preg_replace("/[^0-9.]/", "", $value)
            ) * 100)
        );
    }
    
}