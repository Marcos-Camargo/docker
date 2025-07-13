<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no ShopHUb SellerCenter
Utilizado pelo parceiro.shophub.com.br
Vertem
 */
require APPPATH . "controllers/Api/queue/ProductsConectalaSellerCenter.php";

class Products_ShopHub extends ProductsConectalaSellerCenter {
    public function __construct() {
      parent::__construct();
      $this->int_to = 'ShopHub';
      $this->int_to_SC = 'ShopHub';
      $this->hasAuction = FALSE;
      $this->skuformat = 'store_skuoriginal';
      $this->reserve_to_b2W = 0;  
      
    }
}