<?php

require APPPATH . "controllers/Api/V1/API.php";

class Products extends API
{	
    private $int_to ='VS';
	  
	public function __construct()
    {
        parent::__construct();
        $this->load->model('model_vs_products');
    }

	private function verifyHeaders($headers, $store) {
		foreach ($headers as $header => $value) {
			if (($header == 'Authorization') && (preg_match('/^basic/i', $value ))) {
				$user ="loja".$store['id'];
				$pass = substr($store['token_api'],0,12);
				list( $username, $password ) = explode( ':', base64_decode( substr( $value, 6 ) ) );
				
				if (($username == $user) && ($pass == $password)) {
					return true;
				}
				return false;
			}
		}
		return false;
	}
	
    public function index_get()
    {

        // Verificação inicial
        //$verifyInit = $this->verifyInit();
        //if (!$verifyInit[0])
        //    return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        // filtros
        $this->filters = $this->cleanGet($_GET) ?? null;
		
		if (!array_key_exists ('supplier', $this->filters)) {
			$response = array(
                'error'             =>  array (
					'code'			=> 001,
					'message'		=> 'Nenhum suplier informado ou inexisternte a'
				),
                "products"          => null,
            );
			$this->response($response, REST_Controller::HTTP_OK);
			return; 
		}
		$supplier_id   = $this->filters['supplier'] ;
		
	    $store = $this->model_integrations->getStoreByMKTSeller($this->int_to, $supplier_id);
		
		if (!$store) {
			$response = array(
                'error'             =>  array (
					'code'			=> 001,
					'message'		=> 'Nenhum suplier informado ou inexisternte'
				),
                "products"          => null,
            );
			$this->response($response, REST_Controller::HTTP_OK);
			return; 
		} 
		
		if (!$this->verifyHeaders(getallheaders(), $store)) {
			$error =  "No authentication key or invalid";
		 	show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
			die; 
		}
		
		$page       = $this->filters['page_number'] ?? 1;
		$limit      = $this->filters['page_size'] ?? 100;
        $page       = filter_var($page, FILTER_VALIDATE_INT);
        $limit   	= filter_var($limit, FILTER_VALIDATE_INT);

        // validacao de valor minimos e/ou máximos
        if ($page <= 1) $page = 1;
        if ($limit <= 0) $limit = 1;
        if ($limit > 100) $limit = 100;

        $page--; // decremento para realizar a consulta
        $offset = $page*$limit;

		$result = $this->readProducts($supplier_id, $offset, $limit);

        // Verifica se foram encontrado resultados
        if (isset($result['error']) && $result['error']) {
			$response = array(
                'error'             =>  array (
					'code'			=> $result['error'],
					'message'		=> $result['data']
				),
                "products"          => null,
            );
        }
		else {
			$response = array(
                'error'             => null,
                "products"          => $result['products'],
            );
		}

        $this->response($response, REST_Controller::HTTP_OK);
    }

    private function readProducts($supplier_id, $offset, $limit )
    {
    	
		$products = $this->model_vs_products->getDataSeller($supplier_id, $offset, $limit); 
		
		if (count($products) == 0 ) {
			return array('products' => []);
		}
		
		$result = array();
		foreach($products as $product) {
			$result[] = json_decode($product['json']);
		}
		return array('products' => $result);
    }
	
}