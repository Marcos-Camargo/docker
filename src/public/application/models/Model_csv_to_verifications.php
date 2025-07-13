<?php
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

 */
load_class('Model', 'core');

/**
 * Class Model_csv_to_verifications
 * @property CI_DB_query_builder $db
 */
class Model_csv_to_verifications extends CI_Model
{
    const TABLE_NAME = 'csv_to_verification';

    public function __construct()
    {
        parent::__construct();
    }

    public function getInsertId()
    {
        return $this->db->insert_id();
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert(self::TABLE_NAME, $data);
            return $insert == true;
        }
        return false;
    }

    public function createBatch(array $data): bool
    {
        return $data && $this->db->insert_batch(self::TABLE_NAME, $data);
    }


    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $data['update_at'] = date('Y-m-d H:i:s');

            if(strlen($data['processing_response']) > 60000){
                $data['processing_response'] = substr($data['processing_response'], 0, 60000);
            }

            $update = $this->db->update(self::TABLE_NAME, $data);
            return $update == true;
        }

        return false;
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete(self::TABLE_NAME);
            return $delete == true;
        }

        return false;
    }

    public function getByCriteria($criteria = [])
    {
        $whereIn = array_filter($criteria, function ($f) {
            return is_array($f);
        });
        $db = $this->db->select(['*'])
            ->from(self::TABLE_NAME);
        foreach ($whereIn as $f => $v) {
            $db = $db->where_in($f, $v);
            unset($criteria[$f]);
        }
        return $db->where($criteria)->get()->row_array();
    }

    public function getAllByCriteria($criteria = [], bool $ignore_file_json = false)
    {
        $whereIn = array_filter($criteria, function ($f) {
            return is_array($f);
        });
        $db = $this->db->select(['*'])
            ->from(self::TABLE_NAME);
        foreach ($whereIn as $f => $v) {
            $db = $db->where_in($f, $v);
            unset($criteria[$f]);
        }

        if ($ignore_file_json) {
            $db->not_like('upload_file', '.json');
        }

        return $db->where($criteria)->get()->result_array();
    }

    public function getDontChecked($checked = false, $module = 'Products', int $store_id = null): array
    {
        $where = array('checked' => 0, 'module' => $module);
        if (!is_null($store_id)) {
            $where['store_id'] = $store_id;
        }
        return  $this->db->where($where)->order_by('update_at', 'ASC')->get(self::TABLE_NAME)->result_array();
    }

    public function setChecked($id = null, $situation, string $processing_response = null)
    {
        $data = ['final_situation' => $situation, 'checked' => '1'];

        if (!is_null($processing_response)) {
            $data['processing_response'] = $processing_response;
        }

        return $this->db->update(self::TABLE_NAME, $data, ['id' => $id]);
        // $query = $this->db->query($sql, array($situation, $id));
    }

    /**
     * @param   int|null    $offset
     * @param   int|null    $limit
     * @param   array       $orderby
     * @param   string|null $search_text
     * @param   array       $filters        $filters = ['where' => ['column' => 'value']]
     * @param   bool        $return_count
     * @return  array|array[]|int
     */
    public function getFetchFileProcessData(?int $offset = 0, ?int $limit = 200, array $orderby = array(), string $search_text = null, array $filters = [], bool $return_count = false)
    {
        if (!empty($search_text) && strlen($search_text) >= 2) {
            try {
                $value_date =  DateTime::createFromFormat('d/m/Y H:i', $search_text)->format('Y-m-d H:i');
            } catch (Exception | Error $exception) {
                $value_date = $search_text;
            }

            $this->db->group_start();
            $this->db->or_like(
                [
                    self::TABLE_NAME . '.id'            => $search_text,
                    self::TABLE_NAME . '.user_email'    => $search_text,
                    self::TABLE_NAME . '.upload_file'   => $search_text,
                    self::TABLE_NAME . '.created_at'    => $value_date
                ]
            );
            $this->db->group_end();
        }

        /**
         *
         * $filters = [
         *  'where' => [
         *      'column' => 'value'
         *  ]
         * ]
         *
         */
        foreach ($filters as $type_filter => $filter) {
            foreach ($filter as $column => $value) {
                $this->db->$type_filter($column, $value);
            }
        }

        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $this->db->where(self::TABLE_NAME . '.usercomp', $this->data['usercomp']);
            } else {
                $this->db->where(self::TABLE_NAME . '.store_id', $this->data['userstore']);
            }
        }

        $this->db->select(self::TABLE_NAME . ".*, stores.name AS name_store, shipping_company.name as name_provider")
            ->join('stores', "stores.id = " . self::TABLE_NAME . ".store_id", 'left')
            ->join('shipping_company', "shipping_company.id = left(substr(form_data,23),length(form_data)-48)");

        if (!empty($orderby)) {
            $this->db->order_by($orderby[0], $orderby[1]);
        }

        if (!is_null($limit) && !is_null($offset)) {
            $this->db->limit($limit, $offset);
        }

        return $return_count ? $this->db->get(self::TABLE_NAME)->num_rows() : $this->db->get(self::TABLE_NAME)->result_array();
    }

    /**
     * @param   int|null    $offset
     * @param   int|null    $limit
     * @param   array       $order_by
     * @param   string|null $search_text
     * @param   array       $filters        $filters = ['where' => ['column' => 'value']]
     * @param   bool        $return_count
     * @return  array|array[]|int
     */
    public function getFetchFileProcessProductsLoadData(?int $offset = 0, ?int $limit = 200, array $order_by = array(), string $search_text = null, array $filters = [], bool $return_count = false, array $fields_order = array())
    {
        if (!empty($search_text) && strlen($search_text) >= 2) {
            $arr_filter_search_text = array();
            foreach ($fields_order as $field_order) {
                if (!empty($field_order)) {
                    $arr_filter_search_text[$field_order] = $search_text;
                }
            }

            $this->db->group_start();
            $this->db->or_like($arr_filter_search_text);
            $this->db->group_end();
        }

        /**
         *
         * $filters = [
         *  'where' => [
         *      'column' => 'value'
         *  ]
         * ]
         *
         */
        foreach ($filters as $type_filter => $filter) {
            foreach ($filter as $column => $value) {
                $this->db->$type_filter($column, $value);
            }
        }

        if (!empty($order_by)) {
            $this->db->order_by($order_by[0], $order_by[1]);
        }

        if (!is_null($limit) && !is_null($offset)) {
            $this->db->limit($limit, $offset);
        }

        return $return_count ? $this->db->get(self::TABLE_NAME)->num_rows() : $this->db->get(self::TABLE_NAME)->result_array();
    }

    public function getResponseFile(string $module, int $fileId)
    {
        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE id = ? AND `module` = ?";
        $query = $this->db->query($sql, array($fileId, $module));
        return $query->row_array();
    }

    public function removeFileJsonByModuleAndStore(string $module, int $store_id)
    {
        $this->db->where(array('module' => $module, 'store_id' => $store_id, 'checked' => 0))
            ->like('upload_file', '.json')
            ->update(self::TABLE_NAME, array('checked' => 2, 'final_situation' => 'cancelled'));
    }

    public function getlastRowsXmlAndJsonFile(string $module, int $store_id): ?array
    {
        $where = array(
            'checked' => 0,
            'module' => $module,
            'store_id' => $store_id
        );

        $query1 = $this->db->select('MAX(id) as type')
        ->where($where)
        ->where('form_data IS NULL', NULL, FALSE)
        ->get_compiled_select(self::TABLE_NAME);

        $query2 = $this->db->select('MAX(id) as type')
            ->where($where)
            ->where('form_data IS NOT NULL', NULL, FALSE)
            ->get_compiled_select(self::TABLE_NAME);

        $this->db->select("($query1) as xml, ($query2) as json");

        return $this->db->get()->row_array();
    }
}
