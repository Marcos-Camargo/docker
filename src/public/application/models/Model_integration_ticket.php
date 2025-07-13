<?php

class Model_integration_ticket extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Cria um ticket da integração e retorna seu ID caso inserido com sucesso.
	 * 
	 * @param	 array{
	 *     			id: int,            
	 *     			ticket: string,        
	 * 			 } $data Dados do ticket a ser criado.
	 * 
	 * @return	 int|bool
	 */
	public function createTicket($data)
	{
		if (!empty($data)) {
			$insert = $this->db->insert('integration_tickets', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

	/**
	 * Cria uma entrada na fila de tickets.
	 * 
	 * @param	 array{
	 *     			ticket_id: int,            
	 *     			prd_id: string,        
	 * 				sku_mkt: string,
	 * 				queue_id: int,
	 * 				status: string,
	 * 				finished: bool,
	 * 			 } $data Dados a inserir.
	 * 
	 * @return	 bool
	 */
	public function createTicketHistoryEntry($data)
	{
		if (!empty($data)) {
			$insert = $this->db->replace('integration_ticket_history', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

	/**
	 * Remove um ticket baseado nas condições fornecidas.
	 * 
	 * @param	 array{
	 *     			id?: int,            
	 *     			ticket?: string,        
	 * 			 } $data Dados a remover.
	 * 
	 * @return	 bool
	 */
	public function deleteTicket($data)
	{
		if (!empty($data)) {
			$delete = $this->db->where($data)->delete('integration_ticket_history');
			return $delete;
		}
	}

	/**
	 * Busca o último Ticket enviado para um determinado Sku_mkt.
	 * 
	 * @return	 array{
	 *			     ticket_id: int,
	 *			     prd_id: int,
	 *			     sku_mkt: string,
	 *			     status: string,
	 *			     finished: bool,
	 *			     id: int,
	 *			     ticket: string,
	 *			     created_at: string
	 *			 } 
	 */
	public function getSkuLatestTicketHistory($sku)
	{
		if (!empty($sku)) {
			return $this->db
				->select("*")
				->from("integration_ticket_history itq")
				->join("integration_tickets it", "itq.ticket_id = it.id")
				->where("itq.sku_mkt", $sku)
				->order_by("it.id", "DESC")
				->limit(1)
				->get()
				->row_array();
		}
	}

	/**
	 * Busca um ticket baseado nas condições fornecidas.
	 * 
	 * @param	 array{
	 *     			id?: int,            
	 *     			ticket?: string,        
	 * 			 } $data Dados de busca.
	 * 
	 * @return	 array{
	 *     			id: int,            
	 *     			ticket: string,        
	 * 			 }
	 */
	public function getTicket($data)
	{
		if (!empty($data)) {
			return $this->db
				->where($data)
				->get('integration_tickets')
				->row_array();
		}
	}

	/**
	 * Busca o histórico de entries baseado nas condições fornecidas.
	 * 
	 * @param	  array{
	 *     			ticket_id?: int,            
	 *     			prd_id?: string,        
	 * 				sku_mkt?: string,
	 * 				queue_id?: int,
	 * 				status?: string,
	 * 				finished?: bool,
	 * 			 } $data Dados de filtro.
	 * 
	 * @return	  array{
	 *     			ticket_id: int,            
	 *     			prd_id: string,        
	 * 				sku_mkt: string,
	 * 				queue_id: int,
	 * 				status: string,
	 * 				finished: bool,
	 * 			 } $data Dados do history.
	 */
	public function getTicketHistoryEntries($data)
	{
		if (!empty($data)) {
			return $this->db
				->where($data)
				->get('integration_ticket_history')
				->row_array();
		}
	}


	/**
	 * Altera uma determinada entrada para finalizado.
	 * 
	 * @param	 array{
	 *     			ticket_id?: int,            
	 *     			prd_id?: string,        
	 * 				sku_mkt?: string,
	 * 				queue_id?: int,
	 * 				status?: string,
	 * 				finished?: bool,
	 * 			 } $where Dados de filtro. 
	 * 
	 * @return	 bool
	 */
	public function setFinishedHistoryEntry($where, $newMessage)
	{
		if (!empty($where)) {
			$updated = $this->db->update("integration_ticket_history", ["finished" => 1,"status"=>$newMessage], $where);
			return $updated;
		}
	}

	/**
	 * Altera uma determinada entrada para finalizado.
	 * 
	 * @param	 array{
	 *     			ticket_id?: int,            
	 *     			prd_id?: string,        
	 * 				sku_mkt?: string,
	 * 				queue_id?: int,
	 * 				status?: string,
	 * 				finished?: bool,
	 * 			 } $where Dados de filtro. 
	 * 
	 * @return	 bool
	 */
	public function updateHistoryEntry($where, $data)
	{
		if (!empty($data)) {
			$updated = $this->db->update("integration_ticket_history", $data, $where);
			return $updated;
		}
	}
}
