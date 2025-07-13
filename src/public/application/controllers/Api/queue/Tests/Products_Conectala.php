<?php
/*
* recebe a reuisição e cadastra / alterara /inativa na Conectala
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
//require APPPATH . "controllers/Api/queue/ProductsOcc.php";

//class Products_Conectala extends ProductsOcc
class Products_Conectala extends ProductsVtexV2
{
    public function __construct()
    {+-
        parent::__construct();
        /*$this->int_to = 'Zema';
        $this->adlink = 'https://testeocc.com/ccadminui/v1/products';*/

        $this->int_to = 'Conectala';
        $this->tradesPolicies = array('1');
        $this->adlink = 'https://conectala.myvtex.com/';
        $this->auto_approve = true;
        $this->update_product_specifications = true;
        $this->update_sku_specifications = true;
        $this->update_sku_vtex = true;
        $this->update_product_vtex = true;

    }
}