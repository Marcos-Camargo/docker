<?php

require APPPATH . "controllers/Api/queue/ProductsMosaico.php";

class Products_Default_Mosaico extends ProductsMosaico
{

    public function __construct()
    {
        parent::__construct();

        $data = json_decode($this->security->xss_clean($this->input->raw_input_stream), true);

        $this->int_to = $data["int_to"];
    }
}
