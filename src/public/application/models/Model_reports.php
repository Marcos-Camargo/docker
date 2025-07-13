<?php 
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Relatorios

*/  

class Model_reports extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/*getting the total months*/
	private function months()
	{
		return array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
	}

	/* getting the year of the orders */
	public function getOrderYear()
	{
		$sql = "SELECT * FROM orders WHERE paid_status > ?";
		$query = $this->db->query($sql, array(1));
		$result = $query->result_array();
		
		$return_data = array();
		foreach ($result as $k => $v) {
			$date = date('Y', strtotime($v['date_time']));
			$return_data[] = $date;
		}

		$return_data = array_unique($return_data);

		return $return_data;
	}

	public function getFilters($source)
	{
		$sql = "SELECT field,nicename,operators FROM filters WHERE source = ?";
		$query = $this->db->query($sql, array($source));
		$result = $query->result_array();
		
		$return_data = array();
		foreach ($result as $k => $v) {
			$filtro['nm'] = $v['nicename'];
			$filtro['op'] = json_decode($v['operators'], true);
			$return_data[$v['field']] = $filtro;
		}
		return $return_data;
	}


	// getting the order reports based on the year and moths
	public function getOrderData($year)
	{	
		if($year) {
			$months = $this->months();
			
			$sql = "SELECT * FROM orders WHERE paid_status > ?";
			$query = $this->db->query($sql, array(1));
			$result = $query->result_array();

			$final_data = array();
			foreach ($months as $month_k => $month_y) {
				$get_mon_year = $year.'-'.$month_y;	

				$final_data[$get_mon_year][] = '';
				foreach ($result as $k => $v) {
					$month_year = date('Y-m', strtotime($v['date_time']));

					if($get_mon_year == $month_year) {
						$final_data[$get_mon_year][] = $v;
					}
				}
			}	


			return $final_data;
			
		}
	}

    public function getReportByName($name)
    {
        $sql = "SELECT * FROM reports_metabase WHERE name = ?";
        $query = $this->db->query($sql, array($name));
        return $query->row_array();
    }

    public function getReport($id)
    {
        $sql = "SELECT * FROM reports_metabase WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

    public function getReports($offset = 0, $limit = 200)
    {
        if ($offset == '') $offset = 0;

        $filter = isset($this->data['ordersfilter']) ? $this->data['ordersfilter'] : '';

        if ($filter != "") $filter = "WHERE {$filter}";

        $order_by = "";
        if (isset($this->data['orderby'])) {
            $order_by = $this->data['orderby'];
        }

        if ($limit)
            $limit = "LIMIT ". (int)$limit . "  OFFSET {$offset}";
        else $limit = "";


        $sql = "SELECT * FROM reports_metabase {$filter} {$order_by} {$limit}";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getReportsCount($filter="")
    {
        if ($filter!="")
            $filter = "WHERE {$filter}";

        $sql = "SELECT count(*) as qtd FROM reports_metabase {$filter}";
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getNameReportByAdmin($name, $admin, $report_id = 0)
    {
        $sql = "SELECT * FROM reports_metabase WHERE name = ? AND admin = ? AND id <> ?";
        $query = $this->db->query($sql, array($name, $admin, $report_id));
        return $query->row_array();
    }

    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('reports_metabase', $data);
            return ($insert == true) ? true : false;
        }
    }

    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('reports_metabase', $data);
            return ($update == true) ? true : false;
        }
    }

    public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('reports_metabase');
            return ($delete == true) ? true : false;
        }
    }
}