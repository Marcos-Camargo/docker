<?php
/*

Verifica quais ordens precisam de frete e contrata no frete rápido

*/    
require APPPATH . '/libraries/REST_Controller.php';

class FixNfesDuplicadas extends BatchBackground_Controller {
		
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
			
		$this->DeletarNotasPedidosDuplicado();
        $this->AlteraNotasPedidosDiferentes();

		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function DeletarNotasPedidosDuplicado() {
        
		$notasPedidosRepetidos = $this->db->query("SELECT chave,order_id, COUNT(chave) AS qtd_repeticoes FROM nfes GROUP BY chave, order_id HAVING qtd_repeticoes > 1")->result_array();
        foreach($notasPedidosRepetidos as $nfrepetidas){
            $nfPedidosRepetidos = $this->db->query("SELECT id,chave,order_id FROM nfes where chave = '".$nfrepetidas['chave']."' AND  order_id = '".$nfrepetidas['order_id']."' order by id desc")->result_array();
            foreach($nfPedidosRepetidos as $index => $nfpr){
                //deletar mais antiga
				if($index == 0){
					continue;
				}
				if($nfpr['id']){
					echo "DELETE FROM nfes where chave = '".$nfpr['chave']."' AND  id = '".$nfpr['id']."'";
					echo  "\n";
					$this->db->query("DELETE FROM nfes where chave = '".$nfpr['chave']."' AND  id = '".$nfpr['id']."'");
				}
            }

        }
	}

	function AlteraNotasPedidosDiferentes() {
        $notasPedidosRepetidos = $this->db->query("SELECT chave, COUNT(chave) AS qtd_repeticoes FROM nfes GROUP BY chave HAVING qtd_repeticoes > 1")->result_array();
        foreach($notasPedidosRepetidos as $nfrepetidas){
            $nfPedidosRepetidos = $this->db->query("SELECT id,chave,order_id FROM nfes where chave = '".$nfrepetidas['chave']."' order by id asc")->result_array();
            foreach($nfPedidosRepetidos as $index => $nfpr){
                //alterar registros diferentes -rep
				if($index == 0){
					continue;
				}
				if($nfpr['id']){
					echo "UPDATE nfes SET chave = '".$nfpr['chave']."-rep".$index."' where chave = '".$nfpr['chave']."' AND  id = '".$nfpr['id']."'";
					echo  "\n";
					$this->db->query("UPDATE nfes SET chave = '".$nfpr['chave']."-rep".$index."' where chave = '".$nfpr['chave']."' AND  id = '".$nfpr['id']."'");

				}				
            }

        }
	}
}
?>