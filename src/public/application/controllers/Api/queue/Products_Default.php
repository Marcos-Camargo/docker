<?php 

require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";

class Products_Default extends ProductsVtexV2 {
	
     public function __construct() {
        parent::__construct();

         $data = json_decode($this->security->xss_clean($this->input->raw_input_stream), true);

         $this->int_to = $data["int_to"];
    }
}