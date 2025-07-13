<?php 
/*
SW Serviços de Informática 2019
 
Model de Acesso ao BD para Recebimentos

*/  

class Model_mercadolivre extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	
	

	/************************** FUNÇÕES ML ***********************/
	
	public function logMLexecucao( $httpcodeLog, $erronoLog, $errmsgLog, $contentLog, $store, $billet ){
	    
	    if($errmsgLog == ""){
	        $errmsgLog = " ";
	    }
	    
	    $data['httpcode']  = $httpcodeLog;
	    $data['erro']      = $erronoLog;
	    $data['errmsg']    = $errmsgLog;
	    $data['content']   = $contentLog;
	    $data['store_id']  = $store;
	    $data['billet_id']  = $billet;
	    
	    $insert = $this->db->insert('mercadolivre_log', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	
	public function buscadadosmercadolivre($ambiente="Produção", $id = null){
	    
	    $sql = "select * from auth_mercadolivre where tipo_usuario = '$ambiente'";
	    
	    if( $id <> null){
	        
	        $sql .= " and id = $id";
	        
	    }
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function salvarCredenciaisML($dadosAplicacao, $retornoWS){
	    
	    $data['nome_aplicacao']    = $dadosAplicacao['nome_aplicacao'];
	    $data['id_aplicacao']      = $dadosAplicacao['id_aplicacao'];
	    $data['secret_key']        = $dadosAplicacao['secret_key'];
	    $data['tipo_usuario']      = $dadosAplicacao['tipo_usuario'];
	    $data['access_token']      = $retornoWS['access_token'];
	    $data['refresh_code']      = $retornoWS['refresh_token'];
	    
	    $insert = $this->db->insert('auth_mercadolivre', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	    
	}
	
	public function atualizaCredenciaisML($dadosAplicacao, $retornoWS){
	    
	    $data['ativo'] = 0;
	    
	    $this->db->where('id', $dadosAplicacao['id']);
	    return $update = $this->db->update('auth_mercadolivre', $data);
	    
	}
	
	
}