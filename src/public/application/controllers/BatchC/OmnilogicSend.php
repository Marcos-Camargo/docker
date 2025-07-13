<?php
/*
 
Realiza a atualização de preço e estoque da VIA Varejo

*/   

class OmnilogicSend extends BatchBackground_Controller {

	private $omnilogic = null;
	private $count = 0;
	private $url = 'https://integration.oppuz.com/store/conectala/notification' ;

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

    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		$this->load->model('model_omnilogic');

		$this->notify_all();

		echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	private function notify_all() {
        $query = $this->getDataProduct_Id();
        $products = $query->result_array();

        $amount = count($products); 
        foreach ($products as $key => $product) {
            $product_id = $product['id'];
            echo $key . "/" . $amount . " - prd_id: ". $product_id . PHP_EOL;
            $response_omnilogic = $this->notify($this->url, $product_id);
            $this->model_omnilogic->sent($product_id);
        }
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
                    'Authorization: b1d4da9a97418aef0f1041c35c332f59'
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
?>
