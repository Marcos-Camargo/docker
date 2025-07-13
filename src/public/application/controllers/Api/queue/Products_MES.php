<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa produtos na Mesbla SellerCenter
 */
require APPPATH . "controllers/Api/queue/ProductsConectalaSellerCenter.php";

class Products_MES extends ProductsConectalaSellerCenter {
    public function __construct() {
      parent::__construct('MES', 'Mesbla');
      $this->hasAuction = false;
    }
}