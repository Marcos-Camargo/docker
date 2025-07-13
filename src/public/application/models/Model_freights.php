<?php 
/*

Model de Acesso ao BD para tabela de fretes de pedidos  

*/  

class Model_freights extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getFreightsDataByOrderId($order_id, $orderBy = 'item_id')
	{
		$sql = "SELECT * FROM freights WHERE order_id = ? AND codigo_rastreio IS NOT NULL ORDER BY {$orderBy}";
		$query = $this->db->query($sql, array($order_id));
		return $query->result_array();
	}
	
	public function getFreightsData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM freights WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM freights";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('freights', $data);
			return ($insert == true) ? true : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('freights', $data);
            return $update == true;
		}
        return false;
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('freights');
			return ($delete == true) ? true : false;
		}
	}
    
    public function anonymizeByOrderId($order_id)
	{
		if($order_id) {

			$data = array(
				'codigo_rastreio' 	=> '***********'
			);

            $this->db->where('order_id', $order_id);
            $update = $this->db->update('freights', $data);
            return ($update == true) ? true : false;
        }	
	}

	public function updateRastreio($order_id, $rastreio) {
		$data = array(
			'codigo_rastreio' => $rastreio
			 );
		$this->db->where('order_id', $order_id);
		$update = $this->db->update('freights', $data);
		return ($update == true) ? true : false;
    }

    public function getFreightsOpen()
    {
        $sql = "SELECT freights.*, orders.integration_logistic FROM freights ";
        $sql.= " LEFT JOIN orders ON orders.id = freights.order_id ";
        $sql.= " WHERE freights.codigo_rastreio != '' AND orders.paid_status in (53,4,5,58)";
        $sql.= " ORDER BY freights.order_id";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('freights', $data);
			return ($insert == true) ? true : false;
		}
	}
	
	public function getFreightsSemEtiqueta()
	{
		//$yesterday = date("Y-m-d H:i:s", time() - 60 * 60 * 24);
		//$yesterday = date("Y-m-d H:i:s", time() - 60 * 60 * 2);

		//$sql = "SELECT * FROM freights WHERE codigo_rastreio IS NOT NULL AND (link_etiqueta_a4 IS NULL OR link_etiqueta_termica IS NULL)";
		//$sql = " SELECT freights.* FROM freights LEFT JOIN orders ON orders.id=freights.order_id WHERE orders.paid_status=4 and codigo_rastreio is not null and (data_etiqueta < ? OR data_etiqueta is null)";
		$sql = " SELECT freights.* FROM freights LEFT JOIN orders ON orders.id=freights.order_id WHERE 
			(orders.paid_status=51 OR orders.paid_status=52 OR orders.paid_status=53 OR orders.paid_status=4) 
			and codigo_rastreio is not null and (link_etiqueta_a4 IS NULL OR link_etiqueta_termica IS NULL)";
		
		$query = $this->db->query($sql);
		
		return $query->result_array();
	}
	
	public function getFreightsHasLabel($order_id)
	{
		//$yesterday = date("Y-m-d H:i:s", time() - 60 * 60 * 24);
		//$yesterday = date("Y-m-d H:i:s", time() - 60 * 60 * 2);

		//$sql = "SELECT * FROM freights WHERE codigo_rastreio IS NOT NULL AND (link_etiqueta_a4 IS NULL OR link_etiqueta_termica IS NULL)";
		//$sql = " SELECT freights.* FROM freights LEFT JOIN orders ON orders.id=freights.order_id WHERE orders.paid_status=4 and codigo_rastreio is not null and (data_etiqueta < ? OR data_etiqueta is null)";
		$sql = " SELECT * FROM freights  WHERE order_id = ? AND
			     codigo_rastreio IS NOT NULL AND (link_etiqueta_a4 IS NOT NULL OR link_etiqueta_termica IS NOT NULL)";
		
		$query = $this->db->query($sql,array($order_id));
		
		return $query->row_array();
	}

    public function updateFreightsOrderId($order_id, $data, $inResend = null)
    {
        $where = array('order_id' => $order_id);
        if ($inResend !== null) $where = array_merge($where, array('in_resend_active' => $inResend));
        $this->db->where($where);
        return $this->db->update('freights', $data);
    }

    public function removeForOrderId($order_id, $is_admin = false, $inResend = false)
    {
        $more   = $is_admin ? "" : (($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? "AND company_id = ".$this->data['usercomp'] : "AND store_id = ".$this->data['userstore']));

        $where = array();
        if ($inResend) $where['in_resend_active'] = true;

        $sql    = "SELECT * FROM orders WHERE id = ? {$more}";
        $query  = $this->db->query($sql,array($order_id));
        $count  = $query->num_rows();
        if($count == 0) return false;

        $where  = array_merge(array('order_id' => $order_id), $where);
        $this->db->where($where);
        $delete = $this->db->delete('freights');
        return ($delete == true) ? true : false;
    }

    public function insertRequestPLP($data)
    {
        return $this->db->insert('correios_plps', $data);
    }

    public function getRequestPLP($offset = 0, $procura = '',$orderby = '')
    {
        if ($offset=='') {$offset=0;}
        if (isset($this->data['ordersfilter'])) {
            $filter = $this->data['ordersfilter'];
        } else {
            $filter="";
        }

        if ($orderby == '') {
            $orderby =  "ORDER BY correios_plps.date_created asc ";
        }

        $sql = "SELECT correios_plps.number_plp, correios_plps.status, stores.name as store_name, company.name as company_name, correios_plps.date_created 
                FROM correios_plps 
                JOIN stores ON correios_plps.store_id = stores.id 
                JOIN company ON correios_plps.company_id = company.id 
                WHERE 1=1 {$filter}
                GROUP BY correios_plps.number_plp {$orderby} LIMIT 200 OFFSET {$offset}";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCountRequestPLP($filter = "")
    {
        $sql = "SELECT * 
                FROM correios_plps 
                JOIN stores ON correios_plps.store_id = stores.id 
                JOIN company ON correios_plps.company_id = company.id 
                WHERE 1=1 {$filter} 
                GROUP BY correios_plps.number_plp";

        $query = $this->db->query($sql);
        return $query->num_rows();
    }

    public function getDataPLP($number_plp = 0, $agruparOrder = false, $is_admin = true, $not_view_date_exp = false, $view_date_exp = false, $paid_status = array())
    {
        $number_plp  = $number_plp == 0 ? "" : " AND correios_plps.number_plp = '{$number_plp}'";
        $more       = $is_admin ? "" : (($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND correios_plps.company_id = ".$this->data['usercomp'] : " AND correios_plps.store_id = ".$this->data['userstore']));
        // Verifica se a plp expirou, caso passe os dois parametro terá prioridade para esconder as expiradas
        $date_exp   = $view_date_exp ? " AND correios_plps.date_expiration < '" . date('Y-m-d') . "'" : "";
        $date_exp   = $not_view_date_exp ? " AND correios_plps.date_expiration >= '" . date('Y-m-d') . "'" : $date_exp;
        $filter_status = count($paid_status) ? " AND orders.paid_status in (" . implode(',', $paid_status) . ")" : '';

        $groupBy = $agruparOrder ? "freights.order_id" : "freights.codigo_rastreio";

        $sql = "SELECT freights.order_id, 
                freights.codigo_rastreio, 
                correios_plps.number_plp, 
                correios_plps.date_expiration, 
                correios_plps.status, 
                freights.link_plp, 
                freights.link_etiqueta_a4, 
                freights.in_resend_active, 
                freights.link_etiqueta_termica, 
                correios_plps.store_id, 
                correios_plps.link_etiquetas_a4, 
                correios_plps.link_etiquetas_termica,
                orders.comments_adm
                FROM correios_plps 
                JOIN freights ON correios_plps.order_id = freights.order_id 
                JOIN orders ON correios_plps.order_id = orders.id 
                WHERE 1=1 {$number_plp} {$more} {$date_exp} {$filter_status}
                GROUP BY {$groupBy}";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getDataTranspPLP($number_plp = 0)
    {
        $sql = "SELECT freights.id, 
                    freights.order_id, 
                    freights.codigo_rastreio,
                    freights.link_etiqueta_a4,
                    freights.link_etiqueta_termica,
                    freights.link_etiquetas_zpl,
                    freights.link_plp
                FROM orders 
                left JOIN freights ON freights.order_id = orders.id 
                WHERE orders.id = '{$number_plp}'";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function updatePlpAfterUpload($plp, $data)
    {
        $this->db->where('number_plp', $plp);
        return $this->db->update('correios_plps', $data);

    }

    public function removePlp($number_plp, $is_admin = false, $order_id = NULL)
    {
        $where = array('number_plp' => $number_plp);

        if ($order_id) {
            $where['order_id'] = $order_id;
        }

        if(!$is_admin) {
            if ($this->data['usercomp'] != 1) {
                if ($this->data['userstore'] == 0) $where["company_id"] = $this->data['usercomp'];
                else $where["store_id"] = $this->data['userstore'];
            }
        }

        if($number_plp) {
            $this->db->where($where);
            $delete = $this->db->delete('correios_plps');
            return ($delete == true) ? true : false;
        }
    }

    public function getDataPLPForplp($number_plp, $agruparOrder = false, $is_admin = false)
    {
        $more    = $is_admin ? "" : (($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND correios_plps.company_id = ".$this->data['usercomp'] : " AND correios_plps.store_id = ".$this->data['userstore']));
        $groupBy = $agruparOrder ? "freights.order_id" : "freights.codigo_rastreio";

        $sql = "SELECT freights.idservico
                FROM correios_plps 
                JOIN freights ON correios_plps.order_id = freights.order_id 
                WHERE correios_plps.number_plp = '{$number_plp}' AND correios_plps.status = 1 {$more}
                GROUP BY {$groupBy}";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCountObjetosOrder($order_id, $inResend = false)
    {
        $whereComplement = '';
        if ($inResend) $whereComplement = ' AND (in_resend_active = 1)';

        $sql = "SELECT codigo_rastreio, data_etiqueta FROM freights WHERE order_id = {$order_id} {$whereComplement} GROUP BY codigo_rastreio";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getDataFreightsOrderId($order_id, $is_admin = true, $groupBy  = true)
    {
        $groupBy = $groupBy ? " group by freights.codigo_rastreio" : "";
        $more  = $is_admin ? "" : (($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND orders.company_id = ".$this->data['usercomp'] : " AND orders.store_id = ".$this->data['userstore']));

        $sql = "SELECT freights.*, orders.comments_adm FROM orders JOIN freights ON orders.id = freights.order_id WHERE freights.order_id = {$order_id} {$more} {$groupBy}";
        $query = $this->db->query($sql);

        return $query->result_array();
    }

    public function getFreightForCodeTracking($order_id, $rastreio)
    {
        $sql = "SELECT * FROM freights WHERE order_id = {$order_id} AND codigo_rastreio = '{$rastreio}'";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function updateCodeTracking($orderId, $codeTrackingReal, $codeTrackingNew, $history)
    {
        $date = date('Y-m-d H:i:s');
        $sql = "UPDATE freights SET history_update = ?, codigo_rastreio = ?, updated_date = ? WHERE codigo_rastreio = ? AND order_id = ?";
        return $this->db->query($sql, array($history, $codeTrackingNew, $date, $codeTrackingReal, $orderId));
    }

    public function getOrderIdForCodeTracking($rastreio, $first = true)
    {
        $sql = "SELECT * FROM freights WHERE codigo_rastreio = '{$rastreio}'";
        $query = $this->db->query($sql);
        return $first ? $query->row_array() : $query->result_array();
    }

    public function updateNumberPlpForOrderId($order_id, $data)
    {
        $this->db->where(['order_id' => $order_id, 'number_plp' => NULL]);
        return $this->db->update('correios_plps', $data);
    }

    public function removeOrderPlp($order_id)
    {
        if($order_id) {
            $this->db->where(array('order_id' => $order_id));
            $delete = $this->db->delete('correios_plps');
            return ($delete == true) ? true : false;
        }
        return false;
    }

    public function getOrderByCodeTracking($rastreio)
    {
        $sql = "SELECT orders.*, freights.id as freight_id FROM freights JOIN orders ON freights.order_id = orders.id WHERE freights.codigo_rastreio = ?";
        $query = $this->db->query($sql, array($rastreio));
        return $query->result_array();
    }

    public function updateDataEntrega($order_id, $data)
    {  
        $this->db->where('order_id', $order_id);
        return $this->db->update('freights', $data);
	}

    public function getFreightsByShippingOrder($shippingOrder)
    {
        $sql = "SELECT * FROM freights WHERE shipping_order_id = ? ORDER BY id";
        $query = $this->db->query($sql, array($shippingOrder));
        return $query->result_array();
    }

    public function getTagsPlpActive($number_plp)
    {
        $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND correios_plps.company_id = ".$this->data['usercomp'] : " AND correios_plps.store_id = ".$this->data['userstore']);

        $sql = "SELECT freights.order_id, 
                freights.codigo_rastreio, 
                correios_plps.number_plp, 
                correios_plps.date_expiration, 
                correios_plps.status, 
                freights.link_plp, 
                freights.link_etiqueta_a4, 
                freights.in_resend_active, 
                freights.link_etiqueta_termica, 
                freights.data_etiqueta, 
                correios_plps.store_id, 
                correios_plps.link_etiquetas_a4, 
                correios_plps.link_etiquetas_termica,
                orders.integration_logistic,
                orders.comments_adm
                FROM correios_plps 
                JOIN freights ON correios_plps.order_id = freights.order_id 
                JOIN orders ON correios_plps.order_id = orders.id 
                WHERE correios_plps.number_plp = '{$number_plp}' AND correios_plps.in_resend_active = freights.in_resend_active {$more}
                GROUP BY freights.codigo_rastreio";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getDataPlpTransmit(array $status = [1,2])
    {
        $this->db->select('correios_plps.number_plp, 
            correios_plps.date_expiration, 
            correios_plps.status, 
            correios_plps.in_resend_active, 
            correios_plps.store_id, 
            correios_plps.link_etiquetas_a4,
            correios_plps.order_id, 
            correios_plps.link_etiquetas_termica,
            freights.link_plp'
        )->join('freights', 'correios_plps.order_id = freights.order_id');

        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $this->db->where('correios_plps.company_id', $this->data['usercomp']);
            } else {
                $this->db->where('correios_plps.store_id', $this->data['userstore']);
            }
        }

        return $this->db->where('correios_plps.date_expiration >=', dateNow()->format(DATE_INTERNATIONAL))
            ->where('correios_plps.in_resend_active = freights.in_resend_active')
            ->where_in('correios_plps.status', $status)
            ->group_by('freights.order_id,correios_plps.number_plp')
            ->get('correios_plps')
            ->result_array();
    }

    public function removePlpOrder($plp, $order_id = null, $in_resend_active = null)
    {
        $sql = "SELECT * FROM correios_plps WHERE number_plp = ?";

        if ($order_id) {
            $sql .= " AND order_id = '{$order_id}'";
        }

        if ($in_resend_active) {
            $sql .= " AND in_resend_active = '{$in_resend_active}'";
        }

        foreach( $this->db->query($sql, array($plp))->result_array() as $dataPlp) {
            if (!$this->db->query("DELETE FROM freights WHERE order_id = ? AND in_resend_active = ?", array($dataPlp['order_id'], $dataPlp['in_resend_active'])))
                return false;
        }
        return true;
    }

    public function getDataPlpExpired()
    {
        $sql = "SELECT * FROM correios_plps 
                JOIN orders ON orders.id = correios_plps.order_id 
                WHERE date_expiration < ? 
                AND orders.paid_status in ? 
                AND correios_plps.in_resend_active = orders.in_resend_active";
        $query = $this->db->query($sql, array(date('Y-m-d'), array(50,51,52,53,4,40,43)));
        return $query->result_array();
    }

    public function updateFreightsForResendFalse($order_id)
    {
        $this->db->where('order_id', $order_id)->update('freights', array('in_resend_active' => false));
    }

    public function updateFreights($order_id, $codigo_rastreio, $data)
    {  
        $this->db->where(array('order_id' => $order_id, 'codigo_rastreio' => $codigo_rastreio));
        return $this->db->update('freights', $data);
	}

    public function getFreightsToGetLabelDataByOrderId($order_id, $orderBy = 'item_id')
	{
		$sql = "SELECT * FROM freights WHERE order_id = ? AND codigo_rastreio IS NOT NULL
        AND link_etiqueta_a4 IS NULL AND link_etiqueta_termica IS NULL ORDER BY {$orderBy}";
		$query = $this->db->query($sql, array($order_id));
		return $query->result_array();
	}

    public function getFreightsWithLabelByOrder($orders)
    {
        $sql = "SELECT order_id, link_etiqueta_a4 as link_a4 FROM freights WHERE order_id IN ? AND codigo_rastreio IS NOT NULL AND link_etiqueta_a4 IS NOT NULL AND link_etiqueta_a4 <> '' GROUP BY link_etiqueta_a4";
        $query = $this->db->query($sql, array($orders));
        return $query->result_array();
    }

    public function getAllShippingCompany()
    {
        $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? "WHERE o.company_id = ".$this->data['usercomp'] : "WHERE o.store_id = ".$this->data['userstore']);

        $sql = "SELECT ship_company 
                FROM freights 
                JOIN orders as o ON freights.order_id= o.id 
                {$more}
                GROUP BY freights.ship_company";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getEtiquetasCarrierExport(array $stores = array(), array $shipCompany = array())
    {
        $where = '';
        if (count($stores) && !empty($stores[0]))
            $where .= ' AND o.store_id IN (' . implode(',', $stores).')';
        if (count($shipCompany) && !empty($shipCompany[0]))
            $where .= " AND f.ship_company IN ('" . implode("','", $shipCompany)."')";

        $sql = "SELECT 
                    o.id, 
                    f.data_etiqueta, 
                    f.codigo_rastreio,
                    s.name as store_name,
                    f.ship_company,
                    o.store_id,
                    o.freight_accepted_generation
                FROM freights f
                JOIN orders o ON o.id = f.order_id
                JOIN stores s ON s.id = o.store_id
                JOIN nfes n ON n.order_id = o.id
                WHERE f.sgp <> 1
                AND o.paid_status in (4,5,40,43,51,53,55,58,59)
                {$where} GROUP BY f.codigo_rastreio";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function updateByVolumeAndId(array $data, int $order_id, int $volume): bool
    {
        return (bool)$this->db->where(array('order_id' => $order_id, 'volume' => $volume))->update('freights', $data);
    }


    public function getStore_idCorreios_plps($plp)
    {
        $sql = "SELECT store_id FROM correios_plps  WHERE number_plp = ?";
        $query = $this->db->query($sql, array($plp));
        $row = $query->row_array();
		if ($row) {
			return $row['store_id'];
		} else {
			return false;
		};
    }

    public function getFreightsDataByOrderIds(array $order_id): array
    {
        return $this->db->where_in('order_id', $order_id)
            ->where('codigo_rastreio IS NOT NULL', NULL, FALSE)
            ->order_by('item_id')
            ->get('freights')
            ->result_array();
    }

    /**
     * @param   string $date_start
     * @param   string $date_end
     * @param   string $int_to
     * @param   string $tracking_url
     * @param   string $stores
     * @return  array
     */
    public function getDataFreightToFixTrackingUl(string $date_start, string $date_end, string $int_to, string $tracking_url, string $stores): array
    {
        return $this->db->select('o.id, f.id as freight_id, o.numero_marketplace, n.nfe_num')
            ->join('orders o', 'o.id = f.order_id')
            ->join('nfes n', 'o.id = n.order_id')
            ->where('o.date_time >=', $date_start)
            ->where('o.date_time <=', $date_end)
            ->where('o.origin', $int_to)
            ->where('f.url_tracking !=', $tracking_url)
            ->where_in('o.store_id', explode('-', $stores))
            ->where_not_in('o.paid_status', array(6, 95, 96, 97, 99, 51))
            ->get('freights f')
            ->result_array();
    }

}
