<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . "controllers/Api/queue/ProductsConectala.php";

class Products_Modelo extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $auth_data;
	var $int_to_principal ;
	var $integration;

    public function __construct() {
        parent::__construct();
	   
	    $this->load->model('model_blingultenvio');
	    
	    $this->load->model('model_brands');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
	  	$this->load->model('model_atributos_categorias_marketplaces'); 	   
		$this->load->model('model_marketplace_prd_variants'); 
		$this->load->model('model_ml_ult_envio'); 	
		$this->load->model('model_settings'); 	
		
    }
	
	public function index_post() 
    {
    	$this->inicio = microtime(true);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// verifico se quem me chamou mandou a chave certa
		$this->receiveData();
	
		// verifico se é cadastrar, inativar ou alterar o produto
	//	$this->checkAndProcessProduct();
			
		// Acabou a importação, retiro da fila 
		$this->RemoveFromQueue();

		$fim= microtime(true);
		echo "\nExecutou em: ". ($fim-$this->inicio)*1000 ." ms\n";
		return;
    } 
	
	public function checkAndProcessProduct()
	{
		
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}
	
 	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
	
	}
	
	function updateProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Update"."\n";
	
		return true;

	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando\n";
		
	}
	
	function hasShipCompany()
    {
    	return true;
    }

	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }
		return $this->model_blingultenvio->getData(null, $procura);
	}
}