<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa na GPA
 */
require APPPATH . "controllers/Api/queue/ProductsMirakl.php";

//curl -X POST 'http://localhost/app/Api/queue/Products_GPA' -H 'Content-Type: application/json' -H 'x-local-appkey: 32322rwerwefwr2343qefasfsfa312e4rfwedsdf' -d '{"queue_id":10874772,"product_id":"569"}'
	
class Products_GPA extends ProductsMirakl {
	
   public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'GPA';
		$this->mandatory_category = true; 	// tem que ter tido o mapeamento de categorias
 		$this->mandatory_attributes = true; // tem atributos obrigatórios
 		$this->mandatory_ean = true;		// ean é mandatório
		$this->no_variations = true;        // GPA não tem produtos com variação então as variações caem no título do produto
 		$this->load->model( 'model_gpa_last_post');
 		
		$this->model_last_post = $this->lastPostModel();
    }
   
	function lastPostModel() {
   		return $this->model_gpa_last_post; 
   	}

	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }
		return $this->lastPostModel()->getData(null, $procura);
	}

}