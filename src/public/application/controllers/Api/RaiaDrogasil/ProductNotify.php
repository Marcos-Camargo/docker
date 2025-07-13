<?php

require APPPATH . "/libraries/REST_Controller.php";

class ProductNotify extends REST_Controller {
    
    public function __construct() {
       parent::__construct();
    }

	public function index_post() 
	{
		$url = $this->model_settings->getValueIfAtiveByName('url_ms_publishing_rd');

		if (!$url) {
            echo 'Parametro url_ms_publishing_rd da tabela settings não foi encontrado '. PHP_EOL;
            return;
        }

		$url = $url . '/api/v1/marketplace/catalogs/products/notify';

		$data = json_decode(file_get_contents('php://input'), true);
		foreach ($data as $item) {
			$this->postRequest($url, $item);
		}
		return ;
	}

	private function postRequest($url, $post_data){
		$options = array(
			CURLOPT_RETURNTRANSFER => true,     // return web page
			CURLOPT_HEADER         => false,    // don't return headers
			CURLOPT_FOLLOWLOCATION => true,     // follow redirects
			CURLOPT_ENCODING       => "",       // handle all encodings
			CURLOPT_USERAGENT      => "conectala", // who am i
			CURLOPT_AUTOREFERER    => true,     // set referer on redirect
			CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> json_encode($post_data)
		);
		
		$ch         = curl_init( $url );
		curl_setopt_array( $ch, $options );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		
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
		$header['reqbody']  = json_encode($post_data);
		
		if ($httpcode == 429) {
			echo ' Status Code: 429 - Waiting... ';
			sleep(62);
			echo ' Resend... ';
			return $this->postRequest($url, $authorization, $post_data);
		}

		return $header;
	}
}