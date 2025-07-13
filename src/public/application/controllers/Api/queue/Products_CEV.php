<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no CasaeVideo SellerCenter
 */
require APPPATH . "controllers/Api/queue/ProductsConectalaSellerCenter.php";


class Products_CEV extends ProductsConectalaSellerCenter {
    public function __construct() {
      parent::__construct('CEV', 'CasaeVideo');
      // removido em 29/7 $this->hasAuction = TRUE;
    }
}