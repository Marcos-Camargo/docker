<?php
require APPPATH . "libraries/Traits/VerifyFieldsProduct.trait.php";
class ProductValidator extends CI_Model
{
    use VerifyFieldsProduct;
    private $store = null;
    function __construct($store)
    {
        $this->store = $store[0];
        $this->product_id = $store[1];
        parent::__construct();
        $this->load->library('rest_request');
        // $this->load->library('form_validation');
        $this->product_length_name = 100;
        $this->product_length_description = 3000;
        $this->product_length_sku = 100;
        $this->product_length_sku_min = 6;
        $this->loadLengthSettings();
    }
    public function validate($product, $config = [])
    {
        $require_ean = ($this->model_settings->getStatusbyName('products_require_ean') == 1);
        $config[] = array(
            'field' => 'sku',
            'label' => 'lang:application_sku',
            'rules' => array(
                'trim',
                'required',
                array(
                    'validate_sku', array($this, 'validate_sku')
                )
            ), 'errors' => array(
                'validate_sku' => $this->getMessagemSkuFormatInvalid()
            )
        );
        $config[] = array(
            'field' => 'price',
            'label' => 'lang:application_price',
            'rules' => array(
                'trim',
                'required',
                array(
                    'verifyPrice', array($this, 'verifyPrice')
                )
            ),
        );
        $config[] = array(
            'field' => 'ean',
            'label' => 'lang:application_ean',
            'rules' => array(
                'trim',
                $require_ean ? 'required' : '',
                array(
                    'verifyEan', array($this, 'verifyEan')
                )
            ),
        );
        $config[] = array(
            'field' => 'store',
            'label' => 'lang:application_store',
            'rules' => array('trim', 'required'),
        );
        $config[] = array(
            'field' => 'status',
            'label' => 'lang:application_status',
            'rules' => array('trim', 'required', array('valid_status', array($this, 'valid_status'))),
        );
        if (
            (isset($product['has_integration']) && $product['has_integration']) ||
            (isset($product['status']) && $product['status'] != 1)
        ) {
            $config[] = array(
                'field' => 'product_name',
                'label' => 'lang:application_product_name',
                'rules' => array('trim', 'required'),
            );
            $config[] = array(
                'field' => 'description',
                'label' => 'lang:application_description',
                'rules' => array('trim', 'required'),
            );
        } else {
            $config[] = array(
                'field' => 'product_name',
                'label' => 'lang:application_product_name',
                'rules' => array('trim', 'required', 'max_length[' . $this->product_length_name . ']'),
            );
            $config[] = array(
                'field' => 'description',
                'label' => 'lang:application_description',
                'rules' => array('trim', 'required', 'max_length[' . $this->product_length_description . ']'),
            );
        }
        if (isset($product['semvar']) && $product['semvar'] == "on") {
            $config[] = array(
                'field' => 'qty',
                'label' => 'lang:application_item_qty',
                'rules' => array('trim', 'required'),
            );
        }
        $config[] = array(
            'field' => 'peso_liquido',
            'label' => 'lang:application_net_weight',
            'rules' => array('trim', 'required', 'min_length[' . 0 . ']'),
        );
        $config[] = array(
            'field' => 'peso_bruto',
            'label' => 'lang:application_weight',
            'rules' => array('trim', 'required', 'min_length[' . 0 . ']'),
        );
        $config[] = array(
            'field' => 'largura',
            'label' => 'lang:application_width',
            'rules' => array('trim', 'required', 'greater_than_equal_to[' . 0 . ']'),
        );
        $config[] = array(
            'field' => 'altura',
            'label' => 'lang:application_height',
            'rules' => array('trim', 'required', 'greater_than_equal_to[' . 1 . ']'),
        );
        $config[] = array(
            'field' => 'profundidade',
            'label' => 'lang:application_depth',
            'rules' => array('trim', 'required', 'greater_than_equal_to[' . 0 . ']'),
        );
        $config[] = array(
            'field' => 'products_package',
            'label' => 'lang:application_depth',
            'rules' => array('trim', 'required', 'greater_than_equal_to[' . 1 . ']'),
        );
        $config[] = array(
            'field' => 'garantia',
            'label' => 'lang:application_garanty',
            'rules' => array('trim', 'required'),
        );
        $config[] = array(
            'field' => 'origin',
            'label' => 'lang:application_origin_product',
            'rules' => array('trim', 'required', 'numeric'),
        );
        $config[] = array(
            'field' => 'category[]',
            'label' => 'lang:application_category',
            'rules' => array('trim', 'required'),
        );
        $config[] = array(
            'field' => 'brands[]',
            'label' => 'lang:application_brands',
            'rules' => array('trim', 'required'),
        );
        if ($this->input->post('semvar') !== "on") {
            $config[] = array(
                'field' => 'EAN_V[]',
                'label' => 'lang:application_ean',
                'rules' => array(
                    'trim',
                    $require_ean ? 'required' : '',
                    array(
                        'verifyEan', array($this, 'verifyEan')
                    )
                ),
            );
        }
        return $config;
    }
    public function validate_sku($value)
    {
        $response = $this->verifyFieldsProduct('sku', $value, true, 'S', false, $this->product_id);
        if (!$response[0]) {
            $this->form_validation->set_message('validateSkuSpace', $response[1]);
            return false;
        }
        return true;
    }
    public function verifyPrice($value)
    {
        $response = $this->verifyFieldsProduct('preco', $value, true, 'F');
        if (!$response[0]) {
            $this->form_validation->set_message('verifyPrice', $response[1]);
            return false;
        }
        return true;
    }
    public function verifyEan($value)
    {
        $response = $this->verifyFieldsProduct('ean', $value, true, 'S');
        if (!$response[0]) {
            $this->form_validation->set_message('verifyEan', $response[1]);
            return false;
        }
        return true;
    }
    public function valid_store($value)
    {
        $this->form_validation->set_message('valid_store', '');
        // $response = $this->verifyFieldsProduct('ean', $value, true, 'S');
        // if (!$response[0]) {
        //     $this->form_validation->set_message('verifyEan', $response[1]);
        return false;
        // }
        return true;
    }
    public function valid_status($value)
    {
        $this->form_validation->set_message('valid_store', '');
        $valid_status = [0, 1, 2, 3];
        if (!in_array((float)$value, $valid_status)) {
            $this->form_validation->set_message('valid_status', sprintf("Status invalidos (%s).", implode(',', $valid_status)));

            return false;
        }
        return true;
    }
}
