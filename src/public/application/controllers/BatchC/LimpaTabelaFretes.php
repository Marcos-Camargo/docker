<?php
/*

Verifica quais ordens precisam de frete e contrata no frete rápido

*/    
require APPPATH . '/libraries/REST_Controller.php';

class LimpaTabelaFretes extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
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
			
		$this->limparDiretorio();

		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function recuperaTabelasParaExclusao() {
		return $this->db->query("SELECT idfile_table_shipping, directory, file_table_shippingcol FROM file_table_shipping where dt_create_file <= (now() - interval 6 month) and status = 0 and deleted is null")->result_array();
	}

	function limparDiretorio() {
		$tables = $this->recuperaTabelasParaExclusao();

		foreach ($tables as $ind => $val) {
			unlink($val['directory'] . $val['file_table_shippingcol']);

			$this->db->query("UPDATE file_table_shipping set deleted = NOW() WHERE idfile_table_shipping = " . $val['idfile_table_shipping']);
		}
	}
}
?>
