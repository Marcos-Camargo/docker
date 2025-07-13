<?php 

require APPPATH . "controllers/Api/queue/ProductsWake.php";

class Products_Default_Wake extends ProductsWake {
	
     public function __construct() {
        parent::__construct();

         $data = json_decode($this->security->xss_clean($this->input->raw_input_stream), true);

         $this->int_to = $data["int_to"];
    }
}