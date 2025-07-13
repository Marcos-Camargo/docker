<?php

/**
 * Class Model_transfer
 */
class Model_transfer extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param int $conciliacao_id
     * @return array
     */
    public function getTransfers(int $conciliacao_id, string $stores = null): array
    {
		$where = " and status_repasse IN (21,25,43) ";

		if ($stores)
		{
			$where = " and store_id IN (".$stores.") ";
		}

		$sql = "
				select 
					* 
				from 
					repasse 
				where 
					conciliacao_id = ?  
				".$where;

        $query = $this->db->query($sql, array($conciliacao_id));
        return $query->result_array();
    }

    /**
     * @return array
     */
    public function getAll(): array
    {

        $sql = "SELECT * FROM repasse";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    public function getTransfersConciliacao($conciliacao_id): array
    {

        $sql = "select c.*, case when valor_repasse_ajustado = '0.00' then valor_repasse else valor_repasse_ajustado end as repasse_tratado, left(c.numero_marketplace,position('-' in c.numero_marketplace)-1) as numero_marketplace_buscatrt, cc.id as conciliacao_id from conciliacao_sellercenter c inner join conciliacao cc on cc.lote = c.lote where c.lote = ? order by case when valor_repasse_ajustado = '0.00' then valor_repasse else valor_repasse_ajustado end desc" ;

        $query = $this->db->query($sql, array($conciliacao_id));

        return $query->result_array();

    }

    public function getTransfersConciliacaoOracle($conciliacao_id): array
    {

        $sql = "select c.*, case when valor_repasse_ajustado = '0.00' then valor_repasse else valor_repasse_ajustado end as repasse_tratado, 
                left(c.numero_marketplace,position('-' in c.numero_marketplace)-1) as numero_marketplace_buscatrt, cc.id as conciliacao_id, gs.subseller_id, oi.skumkt  
                from conciliacao_sellercenter c 
                inner join conciliacao cc on cc.lote = c.lote 
                inner join orders_item oi on oi.order_id = c.order_id 
                inner join getnet_subaccount gs on gs.store_id = c.store_id 
                where c.lote = ? and c.store_id <> 1
                order by case when valor_repasse_ajustado = '0.00' then valor_repasse else valor_repasse_ajustado end desc" ;

        $query = $this->db->query($sql, array($conciliacao_id));

        return $query->result_array();

    }

    public function getOrdersTransactionId($order_id): array
    {

        $sql = "select distinct transaction_id from orders_payment where order_id = ? limit 1" ;

        $query = $this->db->query($sql, array($order_id));

        return $query->result_array();

    }

    public function getTransfersConciliacaoPedido($pedido,$conciliacao): array
    {

        $sql = "select c.*, case when valor_repasse_ajustado = '0.00' then valor_repasse else valor_repasse_ajustado end as repasse_tratado, left(c.numero_marketplace,13) as numero_marketplace_buscatrt, cc.id as conciliacao_id from conciliacao_sellercenter c inner join conciliacao cc on cc.lote = c.lote where c.numero_marketplace = ? and cc.id = ? order by case when valor_repasse_ajustado = '0.00' then valor_repasse else valor_repasse_ajustado end desc" ;

        $query = $this->db->query($sql, array($pedido, $conciliacao));

        $saida = $query->result_array();

        if($saida){
            return $saida[0];
        }else{
            return array();
        }

    }

    public function getTransfersFiscal(int $conciliacao_id, string $stores = null): array
    {
		$where = " and status_repasse IN (21,25,43) ";

		if ($stores)
		{
			$where = " and store_id IN (".$stores.") ";
		}

		$sql = "
				select 
					* 
				from 
					repasse_fiscal 
				where 
					conciliacao_id = ?  
				".$where."
				#and 
					#valor_seller <> 0";

        $query = $this->db->query($sql, array($conciliacao_id));

        return $query->result_array();
    }

}