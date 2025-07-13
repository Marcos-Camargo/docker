<?php

/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

*/

class Model_attributes extends CI_Model
{
    const ATTR_TYPE_PRODUCT = 'products';
    const ATTR_TYPE_ATTRIBUTE = 'attributes';
    const ATTR_TYPE_INTEGRATION = 'integrations';
    const ATTR_TYPE_PRODUCT_VARIATION = 'products_variation';

    const ACTIVE = 1;
    const INACTIVE = 2;

    const IS_SYSTEM_ATTR = 1;
    const NOT_SYSTEM_ATTR = 0;

    const VISIBLE = 1;
    const INVISIBLE = 0;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_groups');

    }

    // get the active atttributes data
    public function getActiveAttributeData($att_type = null)
    {
        $sql = "SELECT * FROM attributes WHERE active = ? AND visible = ?";
        if ($att_type) {
            $sql .= " AND att_type = '" . $att_type . "'";
        }
        $query = $this->db->query($sql, [self::ACTIVE, self::VISIBLE]);
        return $query->result_array();
    }

    /* get the attribute data */
    public function getAttributeData($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM attributes where id = ? AND active = ? AND visible = ?";
            $query = $this->db->query($sql, [$id, self::ACTIVE, self::VISIBLE]);
            return $query->row_array();
        }

        $sql = "SELECT * FROM attributes WHERE visible = ?";
        $query = $this->db->query($sql, [self::VISIBLE]);
        return $query->result_array();
    }

    public function countAttributeValue($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM attribute_value WHERE attribute_parent_id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->num_rows();
        }
    }

    public function getAttributeDataByAttributeValue($id = null)
    {
        if ($id) {
            $sql = "SELECT a.* FROM attributes a 
                    inner join attribute_value av on av.attribute_parent_id = a.id
                    where av.id = ? ";
            $query = $this->db->query($sql, [$id]);
            return $query->row_array();
        }

        $sql = "SELECT * FROM attributes WHERE visible = ?";
        $query = $this->db->query($sql, [self::VISIBLE]);
        return $query->result_array();
    }

    public function getValueAttr($value)
    {
        if ($value) {
            $sql = "SELECT * FROM attribute_value WHERE value = ?";
            $query = $this->db->query($sql, array($value));

            if ($query->num_rows() === 0) return false;

            $rs = $query->first_row();
            return $rs->id;
        }
    }

    /* get the attribute value data */
    // $id = attribute_parent_id
    public function getAttributeValueData($id = null)
    {
        $sql = "SELECT * FROM attribute_value WHERE attribute_parent_id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }
    
    public function getAttributeHistoryValueData($id = null)
    {
        $sql = "SELECT lav.*, concat(u.firstname,' ',u.lastname,' - ',u.email) as usuario, date_format(lav.date_insert, \"%d/%m/%Y %k:%i:%s\") as date_insert_format FROM log_attributes_value lav 
                inner join users u on u.id = lav.users_id
                WHERE lav.attribute_parent_id = ?
                ORDER BY attribute_value_id ASC, lav.date_insert DESC";
        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }

    public function getAttributeValueDataById($id = null)
    {
        $sql = "SELECT * FROM attribute_value WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

    public function getAttributeValueById($id = null)
    {
        $sql = "SELECT * FROM attribute_value WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        $row = $query->row_array();
        return $row['value'];
    }


    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert('attributes', $data);
            return ($insert == true) ? true : false;
        }
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('attributes', $data);
            return ($update == true) ? true : false;
        }
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('attributes');
            return ($delete == true) ? true : false;
        }
    }

    public function createValue($data)
    {
        if ($data) {
            $insert = $this->db->insert('attribute_value', $data);
            return ($insert == true) ? true : false;
        }
    }
    
    public function createValuelastId()
    {
       
        return $this->db->insert_id();
        
    }

    public function updateValue($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('attribute_value', $data);
            return ($update == true) ? true : false;
        }
    }

    public function updateValueByParentIdDefaultReason($attribute_parent_id)
    {
        if ($attribute_parent_id) {

            $data['default_reason'] = 0;

            $this->db->where('attribute_parent_id', $attribute_parent_id);
            $update = $this->db->update('attribute_value', $data);
            return ($update == true) ? true : false;
        }
    }

    public function insertLogAttributesValues($acao, $id)
    {
        $sql = "INSERT INTO log_attributes_value
        (users_id, attribute_value_id, value, code, enabled, visible, attribute_parent_id, commission_charges, default_reason, active, `action`)
        select ".$this->session->userdata('id').", av.id, av.value, av.code, av.enabled, av.visible, av.attribute_parent_id, av.commission_charges, av.default_reason, av.active, '".$acao."' from attribute_value av
        where av.id = $id";

        return $this->db->query($sql);
    }

    public function getAttributeValueCommissionChargesById($id)
     {
        if ($id) {
        $sql = "SELECT * FROM attribute_value where id = ?";
        $query = $this->db->query($sql, $id);
        return $query->row_array();
        }else{
            return false;
        }
     }



    public function removeValue($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('attribute_value');
            return ($delete == true) ? true : false;
        }
    }

    public function getAttributeValuesByName($name)
    {
        $sql = "SELECT av.value FROM attribute_value av, attributes a WHERE a.id = av.attribute_parent_id AND a.name= ? ORDER BY av.value";
        $query = $this->db->query($sql, array($name));
        return $query->result_array();
    }

    public function getAttributeValuesAndIdByName($name)
    {
        $sql = "SELECT av.id , av.value FROM attribute_value av, attributes a WHERE a.id = av.attribute_parent_id AND a.name= ? ORDER BY av.value";
        $query = $this->db->query($sql, array($name));
        return $query->result_array();
    }

    public function getAttributeValuesAndIdByNameCancelOrders($name)
    {
        // verifica se o usuário loga tem perfil admin para enxegar todas as possibilidades de cancelamento
        $groupId = $this->model_groups->getGroupData($this->session->userdata['group_id']);

        if($groupId['only_admin'] == "1" || $name == "cancel_reasons"){
            //no caso é admin e então vai trazer todas as possibilidades, ordenando pela default primeiro e depois pela ordem em que aparecem no cadastro
            $sql = "SELECT av.id , av.value FROM attribute_value av, attributes a WHERE a.id = av.attribute_parent_id AND av.active = 1 AND a.name= ? ORDER BY av.default_reason desc, av.value asc";
        }else{
            //caso não seja admin retorna apenas a opção default e caso não tenha, a primeira opção pela ordem de cadastro
            $sql = "SELECT av.id , av.value FROM attribute_value av, attributes a WHERE a.id = av.attribute_parent_id AND av.active = 1 AND a.name= ? ORDER BY av.default_reason desc, av.value asc limit 1";
        }

        $query = $this->db->query($sql, array($name));
        return $query->result_array();
    }

    /**
     * @param string $name Nome de controle do atributo (attributes.name)
     * @param string $value Valor pre definido do atributo (attribute_value.value)
     * @return  mixed           Atributo encontrado, se não nulo
     */
    public function getAttributeValueByAttrNameAndAttrValue(string $name, string $value)
    {
        return $this->db
            ->select('attribute_value.*')
            ->from('attributes')
            ->join('attribute_value', 'attribute_value.attribute_parent_id = attributes.id')
            ->where(array(
                'attributes.name' => $name,
                'attribute_value.value' => $value,
                'attributes.active' => 1
            ))
            ->get()
            ->row_array();
    }

    public function getAttribute($data)
    {
        return $this->db->select('attributes.*')
            ->from('attributes')
            ->where($data)->get()->row_array();
    }

    public function getAttributes($data)
    {
        return $this->db->select('attributes.*')
            ->from('attributes')
            ->where($data)->get()->result_array();
    }

    public function getAttributeValue($data)
    {
        return $this->db->select('attribute_value.*')
            ->from('attribute_value')
            ->join('attributes', 'attribute_value.attribute_parent_id = attributes.id')
            ->where($data)->get()->row_array();
    }

    public function getAttributeDataByPrdId($prdId)
    {
        return $this->db->select('ap.name,apv.value')
            ->from('attributes_products_value apv')
            ->join('attributes_products ap', 'apv.id_attr_prd = ap.id')
            ->where("apv.prd_id", $prdId)
            ->get()
            ->result_array();
    }

    public function saveAttribute($attribute)
    {
        $localAttribute = $this->getAttribute([
            'name LIKE' => $attribute['name'],
            'att_type' => $attribute['module']
        ]);
        $attrDb = [
            'name' => $attribute['name'],
            'code' => $attribute['code'],
            'att_type' => $attribute['module'],
            'active' => $attribute['active'] ?? self::ACTIVE,
            'system' => $attribute['system'] ?? self::NOT_SYSTEM_ATTR,
            'visible' => $attribute['visible'] ?? self::VISIBLE,
        ];
        if (empty($localAttribute)) {
            $this->create($attrDb);
            $attribute['id'] = $this->db->insert_id();
        } else {
            $attribute['id'] = $localAttribute['id'];
            $this->update(array_merge($attrDb, [
                'id' => $localAttribute['id']
            ]), $localAttribute['id'] ?? null);
        }
        return $attribute;
    }

    public function saveAttributeValue($value)
    {
        $localAttributeValue = $this->getAttributeValue([
            'attribute_parent_id' => $value['attribute_id'] ?? null,
            'value LIKE' => $value['value']
        ]);
        $attrValueDb = [
            'value' => $value['value'],
            'code' => $value['code'] ?? '',
            'attribute_parent_id' => $value['attribute_id'],
            'enabled' => $value['enabled'] ?? $localAttributeValue['enabled'] ?? self::ACTIVE,
            'visible' => $value['visible'] ?? $localAttributeValue['visible'] ?? self::VISIBLE,
        ];
        if (empty($localAttributeValue)) {
            if ($this->createValue($attrValueDb)) {
                $value['id'] = $this->db->insert_id();
            }
        } else {
            $value['id'] = $localAttributeValue['id'];
            $this->updateValue(array_merge($attrValueDb, [
                'id' => $localAttributeValue['id']
            ]), $localAttributeValue['id'] ?? null);
        }
        return $value;
    }

    public function getAttributeValueUtmParam(int $id = null):array
    {
        $value = 'utm_source';
        $sql = "SELECT value FROM attribute_value WHERE attribute_parent_id";
        $sql .= " in (SELECT id FROM attributes WHERE name = ?)";
        $query = $this->db->query($sql, array($value));
        return $query->result_array();
    }

     /* get the attribute data by id */
     public function getAttributeDataById($id)
     {
        if ($id) {
        $sql = "SELECT * FROM attributes where id = ?";
        $query = $this->db->query($sql, $id);
        return $query->row_array();
        }
     }

    /**
     * @param int $id
     * @return array|null
     */
    public function getAttributeProduct(int $id): ?array
    {
        return $this->db->where('id', $id)->get('attributes_products')->row_array();
    }

    /**
     * @param int $store_id
     * @return array|null
     */
    public function getAttributeCustomStore(int $store_id): array
    {
        $this->db->select('ap.id, ap.name, p.store_id, p.company_id')
                ->join('attributes_products_value apv', 'p.id = apv.prd_id')
                ->join('attributes_products ap', 'ap.id = apv.id_attr_prd')
                ->where('p.store_id', $store_id)
                ->group_by('ap.name');

        return $this->db->get('products p')->result_array();
    }

    /**
     * @param string $attribute_name
     * @return array|null
     */
    public function getAttributeDefaultByAttributeName(string $attribute_name): ?array
    {
        return $this->db->select('av.*')
            ->join('attribute_value av', 'a.id = av.attribute_parent_id')
            ->where(array(
                'a.name' => $attribute_name,
                'av.default_reason' => 1
            ))
            ->get('attributes a')
            ->row_array();
    }

    /**
     * @param string $attribute_name
     * @return array|null
     */
    public function getAttributeWithoutCommission(string $attribute_name): ?array
    {
        return $this->db->select('av.*')
            ->join('attribute_value av', 'a.id = av.attribute_parent_id')
            ->where(array(
                'a.name' => $attribute_name,
                'av.commission_charges' => 0,
                'av.active' => 1,
                'av.enabled' => 1,
                'av.visible' => 1
            ))
            ->order_by('av.id', 'asc')
            ->limit(1)
            ->get('attributes a')
            ->row_array();
    }
}