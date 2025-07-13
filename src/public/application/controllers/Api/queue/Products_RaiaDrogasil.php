<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo SellerCenter
 */
require APPPATH . "controllers/Api/queue/ProductsConectala.php";

class Products_RaiaDrogasil extends ProductsConectala {

    public function __construct() {
        parent::__construct();
	   
	    $this->load->model('model_settings');
        $this->load->model('model_category');
        $this->load->model('model_sc_last_post');
        $this->model_sc_last_post->setIntTo('rd');
        $this->int_to = 'RaiaDrogasil';
    }

    public function index_post() 
    {
    	$this->inicio = microtime(true);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		// verifico se quem me chamou mandou a chave certa
		$this->receiveData();
	
		// verifico se é cadastrar, inativar ou alterar o produto
		$this->checkAndProcessProduct();
			
		// Acabou a importação, retiro da fila 
		$this->RemoveFromQueue();

		$fim= microtime(true);
		echo "\nExecutou em: ". ($fim-$this->inicio)*1000 ." ms\n";
		return;
    } 

    private function getTipoVolume() {
        $this->prd['tipo_volume_codigo'] = $this->model_category->getTipoVolumeCategory(json_decode($this->prd['category_id'])[0] ?? 0);
        $this->prd['prd_id'] = $this->prd['id'];
    }

    protected function insertProduct()
	{
        echo "Insert Product" . PHP_EOL;
        $this->getTipoVolume();
        $this->model_integrations->updatePrdToIntegrationByPrdId(array('status_int'=>self::INICIOCADASTRAMENTO, 'date_last_int' => $this->dateLastInt), $this->prd['id']);
        $this->publishingOrUpdate();
    }

    protected function updateProduct()
    {
        echo "Update Product ". $this->int_to . PHP_EOL;
        $this->getTipoVolume();
        $this->prepareProduct();
        $this->updatePriceStock();
    }

    function inactivateProduct($status_int, $disable, $variant = null)
    {
        $this->getTipoVolume();
        $this->prepareProduct();
        $this->updatePriceStock($disable);
    }

    function prepareProduct() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Preparando produto'."\n";
		
		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();
		
		$this->prd['qty_original'] = $this->prd['qty'];
		$this->prd['qty'] = ceil((int)$this->prd['qty'] * $percEstoque / 100); // arredondo para cima 
		
		// Pego o preço do produto
		$this->prd['price'] = $this->getPrice(null);

		if (!isset($this->prd['list_price']) || ($this->prd['list_price'] == null) || ($this->prd['list_price'] == 0)) {
			$this->prd['list_price'] = $this->prd['price']; 
		}

		// se tiver Variação, acerto o estoque de cada variação
    	if ($this->prd['has_variants']!='') {
			// Acerto o estoque
			foreach ($this->variants as $key => $variant) {
                if (!isset($variant['list_price']) || ($variant['list_price'] == null) || ($variant['list_price'] == 0)) {
                    $this->variants[$key]['list_price'] = $variant['price'];
                }

				$this->variants[$key]['qty_original'] =$variant['qty'];
				$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100); // arredondo para cima 
				if ((is_null($variant['price'])) || ($variant['price'] == '') || ($variant['price'] == 0)) {
					$this->variants[$key]['price'] = $this->prd['price'];
				}
				
				$this->variants[$key]['promotional_price'] = $this->getPrice($variant);
				if ($this->variants[$key]['promotional_price'] > $this->variants[$key]['price'] ) {
					$this->variants[$key]['price'] = $this->variants[$key]['promotional_price']; 
				}
			}
		}
		
		return true; 
	}

    private function publishingOrUpdate() {
        $payload = array(
            "prd_id" => $this->prd['id'],
            "tipo_volume_codigo" => is_null($this->prd['tipo_volume_codigo']) ? 0 : $this->prd['tipo_volume_codigo']
        );
        
        $url = $this->getUrlMsPublishing();
        if (!$url) {
            echo 'Parametro url_ms_publishing_rd da tabela settings não foi encontrado '. PHP_EOL;
            return;
        }
        $url = $url . '/api/v1/marketplace/catalogs/products';

        $this->postRequest($url, $payload);
    }

    private function updatePriceStock($inactivate = false){
        $url = $this->getUrlMsPriceStock();
        if (!$url) {
            echo 'Parametro url_ms_price_stock_rd da tabela settings não foi encontrado '. PHP_EOL;
            return;
        }
        $url = $url . '/marketplace/catalogs/products/prices';
        
        if ($this->prd['has_variants'] != '') {
            foreach ($this->variants as $key => $variant) {
                $prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);

                $price = $this->variants[$key]['promotional_price'];
                $list_price = $this->variants[$key]['list_price'];
                $qty = $this->variants[$key]['qty'];

                $body = array(
                    "prd_id" => $this->prd['id'],
                    "skumkt" => $prd_to_integration['skumkt'],
                    "price" => $price,
                    "list_price" => $list_price,
                    "qty" => !$inactivate ? $qty : 0
                );
        
                echo "POST ". json_encode($body) . PHP_EOL;	
                $this->postRequest($url, $body);
            }
        }
        else {
            $price = $this->prd['price'];
            $list_price = $this->prd['list_price'];
            $qty = $this->prd['qty'];

            $body = array(
                "prd_id" => $this->prd_to_integration['prd_id'],
                "skumkt" => $this->prd_to_integration['skumkt'],
                "price" => $price,
                "list_price" => $list_price,
                "qty" => !$inactivate ? $qty : 0
            );
    
            echo "POST ". json_encode($body) . PHP_EOL;	
            $this->postRequest($url, $body);
        }
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

	    echo "RESPONSE ". $httpcode  . "  - " . $errmsg .PHP_EOL;
	    
	    $header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
        $header['content']  = $content;
        $header['reqbody']  = json_encode($post_data);
        
        if ($httpcode == 429) {
            echo ' Status Code: 429 - Waiting... ';
            sleep(62);
            echo ' Resend... ';
            return $this->postRequest($url, $post_data);
        }

        return $header;
    }

    public function getLastPost($prd_id, $int_to, int $variant = null)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }

		return $this->model_sc_last_post->getData(null, $procura);
	}

    public function getUrlMsPublishing() {
        return $this->model_settings->getValueIfAtiveByName('url_ms_publishing_rd');
    }

    public function getUrlMsPriceStock() {
        return $this->model_settings->getValueIfAtiveByName('url_ms_price_stock_rd');
    }
}
