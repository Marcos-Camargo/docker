<?php

require APPPATH . "controllers/Api/V1/API.php";

class Omnilogic extends API
{
    private $prefix;
    private $id;
    private $variant;
    private $url = 'https://integration.oppuz.com/store/conectala/notification' ;

    public function __construct() {
        parent::__construct();
        
        $this->load->model('model_omnilogic');
    }

    public function verifyInit($validateStoreProvider = true)
    {
        // Verifica se foram enviados todos os headers
        $headers = $this->verifyHeader(array_change_key_case(getallheaders()));

        // Não foram enviado todos os headers
        if(!$headers[0]){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,$this->lang->line('api_not_headers') . $headers[1],"W");
            return array(false, array('success' => false, 'message' => $this->lang->line('api_not_headers') . $headers[1]));
        }

        $headers = $headers[1];

        // Decodifica token
        $decodeKeyAPI = $this->decodeKeyAPI($headers['x-api-key']);

        // Não possível decodificar a key_api
        if(is_array($decodeKeyAPI)) return array(false, $decodeKeyAPI);

        return array(true);
    }

    public function verifyHeader($header_request, $validateStoreProvider = true)
    {
        $headers = array();

        // Headers obrigatórios para requisição
        $headers_valid = array(
            "x-api-key",
            "accept",
            "content-type",
        );

        // headers recuperado na solicitação
        foreach ($header_request as $header => $value)
            $headers[strtolower($header)] = $value;

        // Verifica se não foram enviados todos os headers
        foreach ($headers_valid as $header_valid)
            if(!array_key_exists($header_valid , $headers)) return array(false, $header_valid);

        return array(true, $headers);
    }

    public function index_get($id = null)
    {
        $log = array(
            'origin_api' => 'omni',
            'method' => 'GET',
            'offer_id' => $id
        );
        $this->model_omnilogic->save_log($log);
        // if (!$this->app_authorized)
        //     return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        if(!$id){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_id_code_not_informed'),"W");
            return $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_id_code_not_informed'))), REST_Controller::HTTP_NOT_FOUND);
        }
        
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        $this->explodeId($id);

        // Verifica se o id informado está em branco
        $result = !(empty($this->id));

        if ($result !== false) 
        {
            $result = $this->createItem();
        }

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error']){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->returnError()['message'] . " - sku: {$this->id}","W");
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_NOT_FOUND);
        }

        $this->response(array('success' => true, 'result' => $result), REST_Controller::HTTP_OK);
    }

    public function notify_post() {
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        $product_id = $this->getDataProductRandom_Id();

        $response_omnilogic = $this->notify($this->$url, $product_id);
        
        echo PHP_EOL . $response_omnilogic['httpcode'] . PHP_EOL;

        $this->response(array('success' => true, 'result' => $product_id), REST_Controller::HTTP_OK);
    }

    public function index_post()
    {
        $body = file_get_contents('php://input');

        $log = array(
            'origin_api' => 'omni',
            'method' => 'POST',
            'json' => $body
        );
        $this->model_omnilogic->save_log($log);
        // if (!$this->app_authorized)
        //     return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        // Recupera dados enviado pelo body

        $result = $this->insert($body);

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error']){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - payload: " . json_encode($data),"W");
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_NOT_FOUND);
        }

        $this->response(array('success' => true, "message" => $this->lang->line('api_offer_successfully')), REST_Controller::HTTP_CREATED);
    }

    private function explodeId($id)
    {
        $id_exploded = explode('_', $id);
        $this->prefix = $id_exploded[0];
        $this->id = $id_exploded[1];
    }

    private function hasVariant()
    {
        return !is_null($this->variant);
    }

    private function createItem()
    {
        // Consulta
        $query = $this->getDataProduct();

        // Verifica se foi encontrado resultados
        if($query->num_rows() === 0) return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));

        $product = $query->row_array();

        // Criar array
        $product_return = array(
            "sku" => $product['sku'],
            "seller_id" => $product['seller_id'],
            "seller_offer_id" => $this->prefix .'_'. $product['seller_offer_id'],
            "title" => $product['title'],
            "description" => $product['description'],
            "price" => $product['list_price'],
            "list_price" => $product['list_price'],
            "ean" => $product['ean'],
            "active" => ($product['active'] == 1),
            //"images" => $this->getImages($product)
        );

        return $product_return;
    }

    private function insert($offer_json)
    {
        $offer = json_decode($offer_json, true);

        $arr = $this->explodeId($offer['seller_offer_id']);
        $seller_offer_id = $this->id;
        $offer['seller_offer_id'] = $seller_offer_id;

        $this->db->trans_begin();

        $offer_db = array(
            'seller_offer_id' => $seller_offer_id,
            'channel' => $offer['channel'],
            'body' => json_encode($offer)
        );
        $this->model_omnilogic->create($offer_db);

        $this->model_omnilogic->received($seller_offer_id);
        
        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }

        $this->db->trans_commit();

        return array('error' => false);
    }

    private function getDataProduct()
    {
        $sql = "SELECT
                    p.sku,
                    p.store_id as seller_id,
                    p.id as seller_offer_id,
                    p.name as title,
                    p.description,
                    p.price as list_price,
                    p.ean,
                    p.status as active,
                    p.image as images
                    
                FROM products as p 
                    LEFT JOIN brands as b ON b.id = left(substr(p.brand_id,3),length(p.brand_id)-4)

                WHERE p.id = {$this->id} ";

        return $this->db->query($sql);
    }

    private function getDataProductRandom_Id()
    {
        // Consulta
        $query = $this->getDataProduct_Id();

        // Verifica se foi encontrado resultados
        if($query->num_rows() === 0) return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));

        $products = $query->result_array();

        $array_key = array_rand($products);
        $product = $products[$array_key];

        return $product['id'] . (is_null($product['variant']) ? '' : '-'.$product['variant']);
    }

    private function notify($url, $id) {
        return $this->postRequest($url, 'conectala', $id);
    }

    private function getDataProduct_Id()
    {
        $sql = "SELECT p.id, null as variant from products p 
            where p.status = 1 and
            not exists(select 1 from omnilogic_sent os where os.prd_id = p.id) and
            not exists(select 1 from prd_to_integration pti where pti.prd_id  = p.id and pti.status_int = 2)";

        return $this->db->query($sql);
    }

    private function getImages($prd) 
    {
        $imagens = array();
		if ($prd['images']!="") {
			$numft = 0;
			if (strpos("..".$prd['images'],"http")>0) {
				$fotos = explode(",", $prd['images']);	
				foreach($fotos as $foto) {
					$imagens[$numft++] = $foto;
				}
			} else {
				$fotos = scandir(FCPATH . 'assets/images/product_image/' . $prd['images']);	
				foreach($fotos as $foto) {
					if (($foto!=".") && ($foto!="..")) {
                        $imagens[$numft++] = base_url('assets/images/product_image/' . $prd['images'].'/'. $foto);
                        $imagens[$numft - 1] = str_replace('http://localhost:8888/' , 'https://conectala.com.br/', $imagens[$numft - 1]);
					}
				}
			}	
        }
        return $imagens;
    }

    private function postRequest($url, $store, $product_id){
        $payload = array(
            'store' => $store,
            'id' => $product_id
        );

        $post_data = json_encode($payload);

		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER  => array(
                    'content-type: application/json', 
                    'Authorization: 55a2b36baa2bac74367addcc6135ab41'
				)
	    );
        
        $ch         = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content    = curl_exec( $ch );
		$httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err        = curl_errno( $ch );
	    $errmsg     = curl_error( $ch );
	    $header     = curl_getinfo( $ch );
        
        curl_close( $ch );
        
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
        
        return $header;
	}
}