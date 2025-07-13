<?php
/* 
 Model de Acesso ao BD para Templates de Email
 
 */

class Model_template_email extends CI_Model
{
    protected $results;

    public function __construct()
    {
        parent::__construct();
    }

    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('template_email', $data);
            return ($update == true) ? true : false;
        }
    }

    public function create($data = '')
    {
        // print_r($data); exit;
        $create = $this->db->insert('template_email', $data);
        return ($create == true) ? $this->db->insert_id() : false;
    }
	
	public function getTemplatesDataView($offset = 0, $procura = '',$orderby = '', $limit=200)
	{
		if ($offset == '') {$offset =0;}
		if ($limit == '') {$limit =200;}
	
	 	if ($this->data['usercomp'] == 1) {
            $sql = "SELECT title, subject, id, status from template_email where 1 = 1 ";
        } else {
            $sql = "SELECT title, subject, id, status from template_email where 1 = 1 ";
        }
        $sql.= $procura.$orderby." LIMIT ".$limit." OFFSET ".$offset;
        $query = $this->db->query($sql);
        return $query->result_array();
	}
	
	public function getTemplatesDataCount($procura = '')
	{
		if ($procura =='') {
			$sql = "SELECT count(*) as qtd FROM template_email ";
		} else {
			if ($this->data['usercomp'] == 1) {
                $sql = "SELECT count(*) as qtd FROM template_email where 1 = 1 ";
	        } else {
	            $sql = "SELECT count(*) as qtd FROM template_email where 1 = 1 ";
	        }
			$sql.= $procura;
		}
		
		$query = $this->db->query($sql);
		$row = $query->row_array();
		return $row['qtd'];
	}

    public function getTemplateData($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM template_email WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
        
	$sql = "SELECT * FROM template_email ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    public function getTemplates()
    {
        return $this->db->select('*')->from('template_email')->where('status', '1')->get()->result_array();
    }
}
