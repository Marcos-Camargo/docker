<?php
require APPPATH . "libraries/Traits/VerifyFieldsProduct.trait.php";
class VariantValidator extends CI_Model
{
    use VerifyFieldsProduct;
    function __construct($store)
    {
        $this->store = $store[0];
        $this->product_id = $store[1];
        parent::__construct();
    }
    public function validateOnScreen($product, $config = [])
    {
        $product['semvar'] = 'off';
        $require_ean = ($this->model_settings->getStatusbyName('products_require_ean') == 1);
        if (isset($product['semvar']) && $product['semvar'] != "on") {
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
            $config[] = array(
                'field' => 'SKU_V[]',
                'label' => 'lang:application_sku',
                'rules' => array(
                    'trim',
                    'required',
                    array(
                        'valid_sku', array($this, 'validateSkuSpace')
                    )
                ), 'errors' => array(
                    'valid_sku' => $this->getMessagemSkuFormatInvalid()
                )
            );
            $config[] = array(
                'field' => 'T[]',
                'label' => 'lang:application_sku',
                'rules' => array(
                    'trim',
                    'required'
                )
            );
            $config[] = array(
                'field' => 'V[]',
                'label' => 'lang:application_sku',
                'rules' => array(
                    'trim',
                    'required'
                )
            );
            $config[] = array(
                'field' => 'Q[]',
                'label' => 'lang:application_sku',
                'rules' => array(
                    'trim',
                    'required'
                )
            );
        } else {
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
}
