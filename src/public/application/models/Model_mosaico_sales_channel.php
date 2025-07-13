<?php

class Model_mosaico_sales_channel extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Cria uma entrada de sales channel.
	 * 
	 * @param	array{mosaico_id:int,mosaico_value:string} $data
	 */
	public function create($data)
	{
		if ($data) {
			$insert = $this->db->insert('mosaico_sales_channel', $data);
			return ($insert == true) ? true : false;
		}
	}

	/**
	 * Cria as entradas de sales channel na tabela pivot.
	 * 
	 * @param	array{store_id:int,sc_id:int} $data
	 */
	public function createStorePivot($data)
	{
		if ($data) {
			$insert = $this->db->insert('mosaico_sc_store', $data);
			return ($insert == true) ? true : false;
		}
	}
	/**
	 * Deleta todos sales channels na qual uma loja está disponível.
	 * 
	 * @param	mixed	$storeId Id da loja.
	 */
	public function deletePivotAllByStoreId($storeId){
		if($storeId){
			$this->db->where('store_id', $storeId);
			return $this->db->delete('mosaico_sc_store');
		}
	}

	/**
	 * Busca um determinado aggregate merchant pelo ID de cadastro.
	 * 
	 * @param	int $id
	 */
	public function getById($id)
	{
		if ($id) {
			$this->db->where('id', $id);
			return $this->db->get('mosaico_sales_channel')->row_array();
		}
	}

	/**
	 * Busca todos os sales_channels.
	 * 
	 * @return array{array{id:int,mosaico_id:int,mosaico_value:string}}
	 */
	public function getAll()
	{
		return $this->db->get("mosaico_sales_channel")->result_array();
	}

	/**
	 * Busca todos os sales channels para uma determinada loja.
	 * Retorna o ID 
	 */
	public function getStoreSalesChannelsConectaId($storeId)
	{
		$this->db->select('mss.sc_id');
		$this->db->from('mosaico_sc_store mss');
		$this->db->where("mss.store_id", $storeId);
		$query = $this->db->get();
		
		return $query->result_array();
	}

	/**
	 * Busca todos os sales channels para uma determinada loja.
	 * Retorna o ID do sales channel na Mosaico.
	 */
	public function getStoreSalesChannelsMscId($storeId)
	{
		$this->db->select('msc.mosaico_id');
		$this->db->from('mosaico_sc_store mss');
		$this->db->join('mosaico_sales_channel msc', 'msc.id = mss.sc_id');
		$this->db->where("mss.store_id", $storeId);
		$query = $this->db->get();
		
		return $query->result_array();
	}
}
