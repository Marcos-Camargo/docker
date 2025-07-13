<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Empresas
 
 */

class Model_company extends CI_Model
{

    protected $results;

    public function __construct()
    {
        parent::__construct();
    }
    
    /* get the brand data */
    public function getCompanyData($id = null, $admin = false)
    {
        if($id) {
            $sql = "SELECT * FROM company WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
        $not1 = '';
        if ($admin) {
            $not1 = ' AND c.id !=1 ';
        }
        if ($this->data['usercomp'] == 1) {
            if (($this->data['user_group_id'] == 1) || ($this->data['only_admin'] ==1)){
                $sql = "SELECT c.*,j.name as pai FROM company c, company j where c.parent_id >= ? and c.parent_id = j.id ".$not1." ORDER BY c.name";
            }
            else {
                $sql = "SELECT c.*,j.name as pai FROM company c, company j where c.id !=1 AND c.parent_id >= ? and c.parent_id = j.id ORDER BY c.name";
            }
            
        } else {
            $sql = "SELECT c.*,j.name as pai FROM company c, company j where j.id = 1 AND c.id = ? and c.parent_id = j.id ORDER BY c.name";
        }
        $query = $this->db->query($sql, array($this->data['usercomp']));
        return $query->result_array();
    }

    public function getFirstCompanyLogo()
    {
        $sql = "SELECT logo from `company` order by id limit 1";
        return $this->db->query($sql)->result_array()[0]['logo'];
    }
    
    public function getCompanyDataByImportSellerId($int_to, $importSellerId)
    {
        $sql = "SELECT * FROM company WHERE import_seller_id = ?";
        $query = $this->db->query($sql, array($importSellerId));
        return $query->row_array();
    }
    
    public function getAllCompanyData()
    {
        $sql = "SELECT * FROM company  order by name";
        $query = $this->db->query($sql);
        return $query->result_array();
        
    }
    public function getMyCompanyData($id = null)
    {
        if($id) {
            $sql = "SELECT * FROM company WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
        if ($this->data['usercomp'] == 1) {
            $sql = "SELECT c.*,j.name as pai FROM company c, company j where c.parent_id >= ? and c.parent_id = j.id ORDER BY c.name";
            $query = $this->db->query($sql, array($this->data['usercomp']));
        } else {
            $sql = "SELECT c.*,j.name as pai FROM company c, company j where (c.parent_id = ? OR c.id = ?) AND c.parent_id = j.id ORDER BY c.name";
            $query = $this->db->query($sql, array($this->data['usercomp'],$this->data['usercomp']));
        }
        return $query->result_array();
    }
    
    
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('company', $data);
            return ($update) ? $id : false;
        }
    }
    public function create($data = '')
    {
        $create = $this->db->insert('company', $data);
        return ($create) ? $this->db->insert_id() : false;
    }
    
    public function countTotalCompanies()
    {
        $sql = "SELECT * FROM company WHERE active = ?";
        $query = $this->db->query($sql, array(1));
        return $query->num_rows();
    }
    public function CompanyLogo($id = 1)
    {
        $sql = "SELECT * FROM company WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        $row = $query->row_array();
        return $row['logo'];
    }

    public function getCompaniesIndicator($company_id = null)
    {
        $sql = "SELECT id, name FROM company WHERE associate_type = 5";
        if ($company_id) { $sql .= " AND id = {$company_id}"; }
        $query = $this->db->query($sql);
        return $query->result_array();
    }
	
	public function getCompaniesDataView($offset = 0, $procura = '',$orderby = '', $limit=200)
	{
		if ($offset == '') {$offset =0;}
		if ($limit == '') {$limit =200;}
	
	 	if ($this->data['usercomp'] == 1) {
            if (($this->data['user_group_id'] == 1) || ($this->data['only_admin'] ==1)){
                $sql = "SELECT c.*,j.name as pai FROM company c, company j where c.parent_id >= ? and c.parent_id = j.id ";
            }
            else {
                $sql = "SELECT c.*,j.name as pai FROM company c, company j where c.id !=1 AND c.parent_id >= ? and c.parent_id = j.id ";
            }
            
        } else {
            $sql = "SELECT c.*,j.name as pai FROM company c, company j where j.id = 1 AND c.id = ? and c.parent_id = j.id ";
        }
        $sql.= $procura.$orderby." LIMIT ".$limit." OFFSET ".$offset;
        $query = $this->db->query($sql, array($this->data['usercomp']));
        return $query->result_array();
	}
	
	public function getCompaniesDataCount($procura = '')
	{
		if ($procura =='') {
			$sql = "SELECT count(*) as qtd FROM company ";
		} else {
			if ($this->data['usercomp'] == 1) {
	            if (($this->data['user_group_id'] == 1) || ($this->data['only_admin'] ==1)){
	                $sql = "SELECT count(*) as qtd FROM company c, company j where c.parent_id >= ? and c.parent_id = j.id ";
	            }
	            else {
	                $sql = "SELECT count(*) as qtd FROM company c, company j where c.id !=1 AND c.parent_id >= ? and c.parent_id = j.id ";
	            }
	            
	        } else {
	            $sql = "SELECT count(*) as qtd FROM company c, company j where j.id = 1 AND c.id = ? and c.parent_id = j.id ";
	        }
			$sql.= $procura;
		}
		
		$query = $this->db->query($sql, array($this->data['usercomp']));
		$row = $query->row_array();
		return $row['qtd'];
	}
	
	public function getCompaniesByName($name)
    {
        $sql = "SELECT * FROM company WHERE name = ?";
        $query = $this->db->query($sql, array($name));
        return $query->result_array();
    }

    public function inactive($id)
    {
        $activeValue = 1;
        $inactiveValue = 2;
        $sql = "update company c set active=" . $inactiveValue . " WHERE c.id = ? and c.active=" . $activeValue . ";";
        $this->db->query($sql, array($id));
        $sql = "update users u set active=" . $inactiveValue . " WHERE u.company_id = ? and u.active=" . $activeValue . ";";
        $this->db->query($sql, array($id));
        $sql = "update stores s set active=" . $inactiveValue . " WHERE s.company_id = ? and s.active=" . $activeValue . ";";
        $this->db->query($sql, array($id));
    }
    public function active($id)
    {
        $activeValue = 1;
        $inactiveValue = 2;
        $sql = "update company c set active=" . $activeValue . " WHERE c.id = ? and c.active=" . $inactiveValue . ";";
        $this->db->query($sql, array($id));
        $sql = "update users u set active=" . $activeValue . " WHERE u.company_id = ? and u.active=" . $inactiveValue . ";";
        $this->db->query($sql, array($id));
        $sql = "update stores s set active=" . $activeValue . " WHERE s.company_id = ? and s.active=" . $inactiveValue . ";";
        $this->db->query($sql, array($id));
    }

    /**
     * Função utilizada para verificar se uma empresa está cadastrada na tabela company através do CNPJ;
     * $cnpj - Deve conter somente os números do cnpj (remover pontos, traço e barra)
     * Exemplo de tratamento para remover caracteres especiais $cnpj = preg_replace('/\D/', '', $data["cnpj"]);
     * $sql - Tratamento para desconsiderar caracteres especiais dos registros no banco, a busca acontece apenas entre os números.
     * Se o CNPJ existir, retorna todos os dados da empresa.
     */
    public function getCompanyByCNPJ($cnpj)
    {
        $sql = "SELECT * FROM company where replace(replace(replace(CNPJ, '/', ''),'.','' ),'-','' ) = ?";
        $query = $this->db->query($sql, array($cnpj));
        return $query->row_array();    
    }

    public function checkUniqueCNPJ($cnpj, $companyId = 0)
    {
        $params[] = preg_replace('/[^0-9]/', '', $cnpj);

        $sql = "SELECT comp.id, comp.name FROM company AS comp 
                WHERE REPLACE(REPLACE(REPLACE(comp.CNPJ, '.', ''), '-', ''), '/', '') = ?";
        if ($companyId > 0) {
            $params[] = $companyId;
            $sql .= " AND comp.id != ?";
        }
        $query = $this->db->query($sql, $params);
        $this->results = $query->result_array();
        return !(count($this->results) > 0);
    }

    public function getResults()
    {
        return $this->results;
    }

    public function getCompanyDataById($id)
    {
        if($id) {
            if ($id == 1) {$sql = "SELECT * FROM company";}
            else {$sql = "SELECT * FROM company WHERE id = ?";}

            $query = $this->db->query($sql, array($id));
            return $query->result_array();
        }
    }
}