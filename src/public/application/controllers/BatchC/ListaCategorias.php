<?php
/*
 
Verifica quais ordens receberam Nota Fiscal e Envia para o Bling 

*/   
class ListaCategorias extends BatchBackground_Controller {
		
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
		$this->load->model('model_orders','myorders');
		$this->load->model('model_nfes','mynfes');
		$this->load->model('model_quotes_ship','myquotesship');
		$this->load->model('Model_freights','myfreights');
		$this->load->model('model_clients','myclients');
    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			get_instance()->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		get_instance()->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		// $this->listCategoriasML();
		$this->listCategoriasVia();
		
		/* encerra o job */
		get_instance()->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
		
	function listCategoriasML() 
	{
		$sql = "select * from categories_mkts_linked where id_loja like '%ML%'";
		$query = $this->db->query($sql);
		$catslink = $query->result_array();

		$myfile = fopen("categorias_ml.csv", "w");
		fwrite($myfile, "ID;Descricao;ID MErcado Livre;Descricao Mercado Livre\n"); 
		foreach ($catslink as $catlink) {
			// var_dump($catlink);
			
			$id_ml = $catlink['id_loja']; 
			$id_local = $catlink['id_mkt'];
			
			$sql = "select * from categorias_todos_marketplaces where id = ?";
			$query = $this->db->query($sql,array($id_ml));
			$catsML = $query->row_array();
			
			$categoria = $this->montaCategoria(array($id_local)); 
			
			$sql = "select * from categories where name  = '".$categoria."'";
			$query = $this->db->query($sql);
			$categoriafinal = $query->row_array();
			if ($categoriafinal) {
				fwrite($myfile, $categoriafinal['id'].";".utf8_decode($categoriafinal['name']).";".$id_ml.";".utf8_decode($catsML['nome'])."\n");  
			} 
			else {
				fwrite($myfile, ";".utf8_decode($categoria).";".$id_ml.";".utf8_decode($catsML['nome'])."\n");
			}
			
		}
		fclose($myfile);
	}
	
	function listCategoriasVia() 
	{
		$sql = "select * from categories_mkts_linked where id_loja not like '%ML%'";
		$query = $this->db->query($sql);
		$catslink = $query->result_array();

		$myfile = fopen("categorias_via.csv", "w");
		fwrite($myfile, "ID;Descricao;ID VIA;Descricao Via\n"); 
		foreach ($catslink as $catlink) {
			// var_dump($catlink);
			
			$id_ml = $catlink['id_loja']; 
			$id_local = $catlink['id_mkt'];
			
			$sql = "select * from categorias_todos_marketplaces where id = ?";
			$query = $this->db->query($sql,array($id_ml));
			$catsML = $query->row_array();
			
			$categoria = $this->montaCategoria(array($id_local)); 
			
			$sql = "select * from categories where name  = '".$categoria."'";
			$query = $this->db->query($sql);
			$categoriafinal = $query->row_array();
			if ($categoriafinal) {
				fwrite($myfile, $categoriafinal['id'].";".utf8_decode($categoriafinal['name']).";".$id_ml.";".utf8_decode($catsML['nome'])."\n");  
			} 
			else {
				fwrite($myfile, ";".utf8_decode($categoria).";".$id_ml.";".utf8_decode($catsML['nome'])."\n");
			}
			
		}
		fclose($myfile);
	}
	
	function montaCategoria($id)
	{
		$sql = "select * from cat_bling where id = ?";
		$query = $this->db->query($sql, array($id));
		$cat_link = $query->row_array();
		if ($cat_link['id_pai'] != 0) {
			$resposta = $this->montaCategoria($cat_link['id_pai']).' > '.$cat_link['cat'];
			return ($resposta);
		} 
		else
			return ($cat_link['cat']);

	}
}
?>
