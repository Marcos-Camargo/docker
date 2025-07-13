<?php
/*
    Realiza a busca de produtos sem categoria nos Seller Centers usado parâmetro criado no settings
*/   
class JobBuscarProdutosSemCategoria extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();

        $logged_in_sess = [
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'logged_in' => TRUE
        ];

		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
        $this->load->model('model_omnilogic');
        $this->load->model('model_omnilogic_channel_mkt');
	$this->load->model('model_products');
	$this->load->model('model_settings');
    }

	function run($id=null,$params=null,$method='POST')
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

        /* filtra o ambiente que será usado. */
        $url_center_name = $this->model_settings->getUrlCategorizador();
        echo PHP_EOL;
        echo 'URL Categorizador: ' . $url_center_name['value'] . PHP_EOL . PHP_EOL;

        /* filtra o seller center passado por parâmetro */
        $seller_center_name = $this->model_products->getSellerCenterActive();
        echo 'Seller Center: '. json_encode($seller_center_name). PHP_EOL . PHP_EOL;

        /* buscando todos os produtos do seller center via parâmetro */
        $products = $this->model_products->getProductsWithoutCategory();
        echo 'Quantidade de produtos sem categoria no Seller Center ' . $seller_center_name['value'] . ': ' . count($products) .  PHP_EOL . PHP_EOL;
        echo 'Produtos: '. json_encode($products).  PHP_EOL . PHP_EOL;           

        /* monta estrutura para enviar para o categorizador */
        if($products){
            $payload = [];
            foreach($products as $product){

                $payload['products_to_categorizer'][] = [
                    'title'       => $product['name'],
                    'external_id' => $product['id'],
                    'origin'      => $seller_center_name['value']
                ];   

                $data = json_encode($payload);
                echo 'Payload Enviado: ' . $data . PHP_EOL;

                /* curl para envio no categorizador */
                $headers = [
                    'Content-Type: application/json'
                    // 'Authorization: Bearer '.$this->app_token
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url_center_name['value']);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                if ($method == 'POST')
                {
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
                $this->result       = curl_exec($ch);
                $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

                if (curl_errno($ch))
                {
                    echo 'Error:' . curl_error($ch);
                }
                
                curl_close ($ch);	

                /* Altera o omnilogic_status pra SENT */ 
                if($this->responseCode == 204 || $this->responseCode == 200)
                {
                    echo '. Produto adicionado a fila com sucesso \☺/' . PHP_EOL;
                    echo 'Response code: ' .  $this->responseCode . PHP_EOL . PHP_EOL;

                    $update_omnilogic_status = $this->model_products->updateOmnilogicStatus($product['id']);
                    $payload = [];
                }
            }
        }else{
            echo 'Nenhum produto sem categoria encontrado.' . PHP_EOL;
        }

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
        
}
