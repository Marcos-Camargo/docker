<?php

class Model_mosaico_aggregate_merchant extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Cria uma entrada de aggregate merchant.
	 * 
	 * @param	 array{name:string,aggregate_merchant:string} $data
	 */
	public function create($data)
	{
		if ($data) {
			$insert = $this->db->insert('mosaico_aggregate_merchant', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

	/**
	 * Realiza o update do aggregate merchant.
	 * 
	 * @param	 array{name:string,aggregate_merchant:string} $data
	 * @param	 int $id
	 */
	public function update($data, $id)
	{
		if ($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('mosaico_aggregate_merchant', $data);
			return ($update == true) ? true : false;
		}
	}

	/**
	 * Busca um determinado aggregate merchant pelo ID de cadastro.
	 * 
	 * @param	 int $id
	 */
	public function getById($id)
	{
		if ($id) {
			$this->db->where('id', $id);
			return $this->db->get('mosaico_aggregate_merchant')->row_array();
		}
	}

	/**
	 * Busca todos aggregate_merchants.
	 * 
	 * @return	 array{array{id:int,name:string,aggregate_merchant:string}}
	 */
	public function getAll()
	{
		return $this->db->get("mosaico_aggregate_merchant")->result_array();
	}

	/**
	 * Busca todos aggregate_merchants não cadastrados na Mosaico.
	 * Criados manualmente pelo front.
	 * 
	 * @return	 array{array{id:int,name:string,aggregate_merchant:mixed}}
	 */
	public function getAllNonRegistered()
	{
		$this->db->where("(aggregate_merchant IS NULL OR aggregate_merchant = '')");
		return $this->db->get("mosaico_aggregate_merchant")->result_array();
	}

	/**
	 * Pesquisa os aggregate merchants cadastrados.
	 * @param	 string		$search Parâmetro da pesquisa.	  
	 * 
	 * @return	 array{array{id:int,name:string,aggregate_merchant:mixed}}
	 */
	public function searchAggregateMerchant($search)
	{
		$this->db->like('name', $search, 'after');
		$this->db->limit(20);
		$query = $this->db->get('mosaico_aggregate_merchant');
		return $query->result_array();
	}
}
