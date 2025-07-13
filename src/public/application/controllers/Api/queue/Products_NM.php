<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo SellerCenter
 */
require APPPATH . "controllers/Api/queue/ProductsConectalaSellerCenter.php";


class Products_NM extends ProductsConectalaSellerCenter {
    public function __construct() {
        parent::__construct('NM', 'NovoMundo');
    }
}