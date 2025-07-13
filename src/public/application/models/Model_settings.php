<?php 
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Configuracoes personalizados de usuarios

*/  

class Model_settings extends CI_Model
{

    public static $SETTINGS_GET_BY_NAME = [];
    public static $SETTINGS_GET_VALUE_IF_ACTIVE_BY_NAME = [];

	public function __construct()
	{
		parent::__construct();
	}

	/*get the active settings information*/
	public function getActivesettings()
	{
		$sql = "SELECT * FROM settings WHERE active = ?";
		$query = $this->db->query($sql, array(1));
		return $query->result_array();
	}

	/* get the Setting data */
	public function getSettingData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM settings WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM settings WHERE name != 'payment_gateway_id' order by name";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	/*get the active settings information*/
	public function getSettingbyName($name)
	{
		$sql = "SELECT * FROM settings WHERE name = ?";
		$query = $this->db->query($sql, array($name));
		$row = $query->row_array();
		if ($row) {
			return $row['id'];
		} else {
			return false;
		}	
	}
	
	public function getSettingDatabyName($name)
	{
		$sql = "SELECT * FROM settings WHERE name = ?";
		$query = $this->db->query($sql, array($name));
		$row = $query->row_array();
		if ($row) {
			return $row;
		} else {
			return false;
		}	
	}

	public function getSettingDatabyNameEmptyArray($name)
	{
		$sql = "SELECT * FROM settings WHERE name = ?";
		$query = $this->db->query($sql, array($name));
		$row = $query->row_array();
		
		if ($row) {
			return $row;
		} else {
			
			$sql2 = "SELECT * FROM settings LIMIT 1";
			$query2 = $this->db->query($sql2);
			$row2 = $query2->row_array();
		
			foreach($row2 as $linha => $key){

				$row2[$linha] = "";
				
				if($linha == "name" || $linha == "value"){
					$row2[$linha] = $name;	
				}

				if($linha == "status"){
					$row2[$linha] = 2;	
				}

			}
			return $row2;


		}	
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('settings', $data);
            get_instance()->log_data(__CLASS__, __FUNCTION__, json_encode($data), "I");
			return ($insert) ? $this->db->insert_id() : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('settings', $data);
            get_instance()->log_data(__CLASS__, __FUNCTION__, json_encode($data), "I");
			return ($update) ? true : false;
		}
	}

    public function updateByName($data, $name)
	{
		if($data && $name) {
			$this->db->where('name', $name);
			$update = $this->db->update('settings', $data);
            get_instance()->log_data(__CLASS__, __FUNCTION__, json_encode($data), "I");
			return ($update) ? true : false;
		}
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			try {
				$delete = $this->db->delete('settings');
                get_instance()->log_data(__CLASS__, __FUNCTION__, $id, "I");
				return ($delete) ? true : false;
			} catch (\Exception $e) {
				return false; 
			}
		}
		
	}
	
	public function getStatusbyName($name)
	{

        if (isset(self::$SETTINGS_GET_BY_NAME[$name])){
            return self::$SETTINGS_GET_BY_NAME[$name];
        }

		$sql = "SELECT * FROM settings WHERE name = ?";
		$query = $this->db->query($sql, array($name));
		$row = $query->row_array();
		if ($row) {
            return self::$SETTINGS_GET_BY_NAME[$name] = $row['status'];
        }
        return self::$SETTINGS_GET_BY_NAME[$name] = false;
	}
	
	public function getValueIfAtiveByName($name)
	{

        if (isset(self::$SETTINGS_GET_VALUE_IF_ACTIVE_BY_NAME[$name])){
            return self::$SETTINGS_GET_VALUE_IF_ACTIVE_BY_NAME[$name];
        }

		$sql = "SELECT * FROM settings WHERE name = ?";
		$query = $this->db->query($sql, array($name));
		$row = $query->row_array();
		if ($row) {
			if ($row['status'] ==1) {
				return self::$SETTINGS_GET_VALUE_IF_ACTIVE_BY_NAME[$name] = $row['value'];
			}
		}
		return self::$SETTINGS_GET_VALUE_IF_ACTIVE_BY_NAME[$name] = false;
	}

    public function getFlavorActive()
    {
        $flavor = 'variacao_sabor';
        $sql = "SELECT name FROM settings WHERE name = ? AND status = ?";
        $query = $this->db->query($sql, array($flavor,1));
        return $query->result_array();

    }

	public function getDegreeActive()
    {
        $degree = 'variacao_grau';
        $sql = "SELECT name FROM settings WHERE name = ? AND status = ?";
        $query = $this->db->query($sql, array($degree,1));
        return $query->result_array();

    }

	public function getSideActive()
    {
        $side = 'variacao_lado';
        $sql = "SELECT name FROM settings WHERE name = ? AND status = ?";
        $query = $this->db->query($sql, array($side,1));
        return $query->result_array();

    }

    public function getPermissionPilot($nameParam)
    {
        $sql = "SELECT * FROM settings WHERE name = ?";
        $query = $this->db->query($sql, array($nameParam));
        $row = $query->row_array();
        if ($row) {
            return $row;
        } else {
            return false;
        }
    }


//     public function activeFilterDescription()
//     {
//         $param = 'active_description_vtex_v2';
//         $sql = "SELECT name FROM settings WHERE name = ? AND status = ?";
//         $query = $this->db->query($sql, array($param, 1));
//         return $query->result_array();
//     }
  
	public function getUrlMs()
    {
        $sql = "SELECT * FROM settings WHERE name = 'url_ms' and status = 1";
        $query = $this->db->query($sql);
        $row = $query->row_array();
        if ($row) {
            return $row;
        } else {
            return false;
        }
    }

	public function getUrlCategorizador()
	{
		return $this->db->select('value')
        ->from('settings')
        ->where(['name' => 'url_categorizador'])
        ->get()
        ->row_array();
	}
	
	public function settingRedirectOut()
	{
		return $this->db->select('*')
        ->from('settings')
        ->where([
			'name' => 'button_redirect_out_rd',
			])
        ->get()
        ->row_array();
	}

	public function getActivePersonalize()
	{
		return $this->db->select('status')
		->from('settings')
		->where([
				'name' => 'customization_theme',
				'status' => 1
				])
		->get()
		->row_array();
	}

    public function getAllSettings()
    {
        $sql = "SELECT s.id, s.name, value, status, description, friendly_name, s.setting_category_id as category, sc.name as category_name FROM settings s 
        JOIN settings_categories sc ON sc.id = s.setting_category_id WHERE s.name != 'payment_gateway_id'";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getAllSettingsBySubcategory($subcategory)
    {
        $sql = "SELECT s.id, s.name, value, status, description, friendly_name, setting_category_id as category, sc.name as category_name FROM settings s 
        JOIN settings_categories sc ON sc.id = s.setting_category_id
         WHERE setting_subcategory_id = ? AND s.name != 'payment_gateway_id'";
        $query = $this->db->query($sql, array($subcategory));
        return $query->result_array();
    }

    public function getSettingsByCategory($category)
    {
        $sql = "SELECT s.id, s.name, value, status, description, friendly_name, setting_category_id as category, sc.name as category_name FROM settings s 
        JOIN settings_categories sc ON sc.id = s.setting_category_id
        WHERE sc.name LIKE ? AND s.name != 'payment_gateway_id'";
        $query = $this->db->query($sql, array($category));
        return $query->result_array();
    }

    public function getCategoriesSettings()
    {
        $sql = "SELECT * FROM settings_categories WHERE name NOT LIKE ?";
        $query = $this->db->query($sql, array('uncategorized'));
        return $query->result_array();
    }

    public function getSettingCategoryByName(string $category_name)
    {
        $this->db->where('name', $category_name);
        return $this->db->get('settings_categories')->row();

    }

	public function getLimiteVariationActive()
    {
        $limit = 'variacao_limite';
        $sql = "SELECT name FROM settings WHERE name = ? AND status = ?";
        $query = $this->db->query($sql, array($limit,1));
        return $query->result_array();

    }

}