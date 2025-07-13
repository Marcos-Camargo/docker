<?php
/*
 
Verifica quais ordens receberam Nota Fiscal e Envia para o Bling 

*/   
class Leprodutosjson extends BatchBackground_Controller {
		
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
		/* inicia o job 
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			get_instance()->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		get_instance()->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		*/
		/* faz o que o job precisa fazer */
		$this->lerprodutos();
		
		/* encerra o job 
		get_instance()->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
		*/
	}	
	

	
	function lerprodutos()
	{
		$fn = fopen("/home/ricardo/Product1.jl","r");
		$campos= array();
		$i=0;
		while (! feof($fn)) {
			$linha = fgets($fn);
			
			$lido = json_decode ($linha, true);
			//echo print_r($lido,true);
			foreach ($lido as $key1 => $val1) {
				$key1 = preg_replace('/\s+/', ' ',$key1);
				if (!array_key_exists($key1,$campos)) {
					$campos[$key1] = array();
				}
				if (is_array($val1)) {
					foreach ($val1 as $key2 => $val2) {
						$key2 = preg_replace('/\s+/', ' ',$key2);
						if (!array_key_exists($key2,$campos[$key1])) {
							$campos[$key1][$key2] = array();
						}
						if (is_array($val2)) {
							
							foreach ($val2 as $key3 => $val3) {
								$key3 = preg_replace('/\s+/', ' ',$key3);
								if (!array_key_exists($key3,$campos[$key1][$key2])) {
									$campos[$key1][$key2][$key3] = array();
								}
								if (is_array($val3)) {

									foreach ($val3 as $key4 => $val4) {
										$key4 = preg_replace('/\s+/', ' ',$key4);
										if (!array_key_exists($key4,$campos[$key1][$key2][$key3])) {
											$campos[$key1][$key2][$key3][$key4] = array();
										}
										
									}
									
								}
							}
						
						}
					}
				}
				
			}
			
						
			$i++;
			if ($i > 30) {break;}
		}
		fclose($fn);
		var_dump($campos);
		//echo $this->imprimeKeys($campos, "");
		
	}

}
?>
