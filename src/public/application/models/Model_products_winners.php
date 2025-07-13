<?php
/*
 
 Model de Acesso ao BD para MicroLeilao
 
 */

class Model_products_winners extends CI_Model
{
    public function __construct()
    {
        parent::__construct();		
    }
	

    public function getWinner($ean = false, $int_to = false)
    {
        if(!$ean || !$int_to)
            return false;

        $sql = "select * from products_winners where ean = ? and int_to = ?";
        $query = $this->db->query($sql, array($ean, $int_to));
		return $query->row_array();
    }


    public function getAllAuctions($int_to = false)
    {
        if(!$int_to)
            return false;

        $sql = "select * from products_winners order by ean";
        $query = $this->db->query($sql);
		return $query->result_array();
    }


    public function saveNewWinner($data = false)
    {
        if(!$data)
            return false;
        
        $insert = $this->db->insert('products_winners', $data);
        
        if(!$insert)
            $error = $this->db->error();

        return $insert;        
    }


    public function getProducts($ean = false, $product_id = null)
    {
        if(!$ean)
            return false;

        $avoid = '';

        if($product_id)   
            $avoid = "and p.id not in (".$product_id.") ";
        
        $sql = "select 
                (select count(o.id) FROM orders o WHERE o.store_id = p.store_id) as total_orders,
                p.id, p.store_id, p.price
                from 
                products p 
                left join company c on p.company_id = c.id
                where 
                p.EAN = ? and p.qty > 0 and p.status = 1 and p.situacao = 2 and p.is_kit = 0 and p.dont_publish = 0 
                ".$avoid."
                order by
                p.price asc,
                field(c.addr_uf, 'SP') desc,
                total_orders desc,
                p.store_id asc
                ";

        $query = $this->db->query($sql, array( (string)$ean));

        if(!$query)
            $error = $this->db->error();

		return $query->row_array();
    }


    public function updateWinner($ean = false, $winner = false, $int_to = false)
    {
        if(!$ean || !$winner || !$int_to)
            return false;

        $sql = "update products_winners set 
                store_id_2 = store_id_1,
                product_id_2 = product_id_1,
                store_id_1 = ?,
                product_id_1 = ?
                where ean = ? and int_to = ?";

        $query = $this->db->query($sql, array($winner['store_id'], $winner['id'], $ean, $int_to));

        return $query;
    }


    public function isAvailable($product_id_1 = false)
    {
        if(!$product_id_1)
            return false;

        $sql = "select id from products p where 
                p.id = ? and p.qty > ? and p.status = ? and p.situacao = ? and p.is_kit = ? and p.dont_publish = ? AND p.store_id > ?";
            
        $query = $this->db->query($sql, array($product_id_1, 0, 1, 2, 0, 0, 0));

        if(!$query)
            $error = $this->db->error();

		return ($query->row_array() > 0) ? true : false;
    }
	

                    
                    
    public function updateLastPost($ean = false, $product_id = false)
    {
        if(!$product_id)
            return false;

        $sql = "select p.*, 
                s.CNPJ, s.zipcode, s.freight_seller, s.freight_seller_end_point, s.freight_seller_type
                from 
                products p left join stores s on p.store_id = s.id
                where
                p.id = ?";
               
        $query = $this->db->query($sql, array($product_id));

        if(!$query)
            $error = $this->db->error();

		$data = $query->row_array();

        if($data)
        {
            if(!$ean)
                $ean = $data['EAN'];

            $sql = "update integration_last_post set
            company_id                  = '".$data['company_id']."',
            prd_id                      = '".$product_id."',
            price                       = '".$data['price']."',
            qty                         = '".$data['qty']."',
            sku                         = '".$data['sku']."',
            skulocal                    = '".$data['sku']."',
            qty_atual                   = '".$data['qty']."',
            largura                     = '".$data['largura']."',
            altura                      = '".$data['altura']."',
            profundidade                = '".$data['profundidade']."',
            peso_bruto                  = '".$data['peso_bruto']."',
            store_id                    = '".$data['store_id']."',
            crossdocking                = '".$data['prazo_operacional_extra']."',
            CNPJ                        = '".preg_replace('/\D/', '', $data['CNPJ'])."',
            zipcode                     = '".$data['zipcode']."',
            freight_seller              = '".$data['freight_seller']."',
            freight_seller_end_point    = '".$data['freight_seller_end_point']."',
            freight_seller_type         = '".$data['freight_seller_type']."'
            where EAN = ?";

            $query = $this->db->query($sql, array($ean));
            
            if(!$query)
                $error = $this->db->error();

            return $query;
        }
        else
        {
            return false;
        }
    }


    public function updatePrdToIntegration($product_new = false, $product_old = false)
    {
        if(!$product_new || !$product_old)
            return false;

        $sql = "update prd_to_integration set status_int = 2 where prd_id = ?";

        $query1 = $this->db->query($sql, array($product_new));
            
        if(!$query1)
            $error = $this->db->error();

        $sql = "update prd_to_integration set status_int = 11 where prd_id = ?";

        $query2 = $this->db->query($sql, array($product_old));
            
        if(!$query2)
            $error = $this->db->error();

        return ($query1 && $query2) ? true : false;
            
    }


    public function updateLastPostValues($skumkt = null, $price = null, $quantity = null)
    {
        if(!$skumkt || !$price || !$quantity)
            return false;

        $sql = "update integration_last_post set price = ?, qty = ? where skumkt = ?";

        $query = $this->db->query($sql, array($price, $quantity, $skumkt));

        if(!$query)
            $error = $this->db->error();

		return $query;
    }
	
	 public function remove($current_product_id, $int_to, $ean)
    {
		$this->db->where('current_product_id', $current_product_id);
		$this->db->where('int_to', $int_to);
		$this->db->where('ean', $ean);
		$delete = $this->db->delete('current_product_id');
		return ($delete == true) ? true : false;

    }

}