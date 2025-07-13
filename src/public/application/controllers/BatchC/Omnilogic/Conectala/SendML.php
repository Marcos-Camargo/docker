<?php
/*
 
Realiza o enriquecimento de produtos recebido da Omnilogic

*/   

class SendML extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_omnilogic');

    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
        $this->send();
        
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

    private function send() {
        $list = $this->model_omnilogic->getToSend('MLC');
        $count = 0;
        foreach ($list as $offer) {
            echo ++$count . "/" . count($list) . " - Product_id: " . $offer['prd_id'] . " - ";

            $this->model_omnilogic->markApproved($offer['id_pti']);

            $url = "http://localhost/app/Api/queue/Products_MLC_Salvo";
            $authorization = array(
                'x-local-appkey' => "32322rwerwefwr2343qefasfsfa312e4rfwedsdf"
            );
            $payload = array(
                "queue_id" => "3939393",
                "product_id" => $offer['prd_id']
            );
            $sent = false;
            try {
                $response = $this->postRequest($url, $authorization, $payload);
                echo 'Status Code: '. $response['httpcode'];
                $sent = $response['httpcode'] < 300;
            }
            finally {
                if ($sent)
                    echo 'Sent';
                    // $this->model_omnilogic->markSentMkt($offer['id']);
                echo PHP_EOL;
            }
        }
    }

    private function postRequest($url, $authorization, $post_data){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
            CURLOPT_POSTFIELDS	=> json_encode($post_data),
			CURLOPT_HTTPHEADER => $this->getHttpHeader()
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
        $header['reqbody']  = json_encode($post_data);
        
        if ($httpcode == 429) {
            echo ' Status Code: 429 - Waiting... ';
            sleep(62);
            echo ' Resend... ';
            return $this->postRequest($url, $authorization, $post_data);
        }

        return $header;
    }

    private function getHttpHeader() 
    {
        return array(
            'content-type: application/json', 
            'x-local-appkey: 32322rwerwefwr2343qefasfsfa312e4rfwedsdf'
        );
    }
}
?>
