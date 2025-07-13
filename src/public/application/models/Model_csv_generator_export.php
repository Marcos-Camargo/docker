<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Marcas/Fabricantes
 
 */

/**
 * Class Model_csv_generator_export
 */
class Model_csv_generator_export extends CI_Model
{
    const TABLE_NAME = 'csv_generator_export';
    public function __construct()
    {
        parent::__construct();
    }
    public function exportProduct($postdata)
    {
        $group_by = ['p.id'];
        $select_fields = [
            'p.*',
            's.name as store_name'
        ];
        $query = $this->db->from("products p");
        $busca = isset($postdata['search']) ? $postdata['search'] : [];
        if (isset($busca['value'])) {
            if (strlen($busca['value']) > 2) {  // Garantir no minimo 3 letras
                $data = [];
                $data['p.sku'] = $busca['value'];
                $data['p.name'] = $busca['value'];
                $data['s.name'] = $busca['value'];
                $data['p.id'] = $busca['value'];
                $data['p.EAN'] = $busca['value'];
                $query = $query->group_start()->or_like($data)->group_end();
                // $this->data['ordersfilter'] .= " AND ( sku like '%" . $busca['value'] . "%' OR p.name like '%" . $busca['value'] . "%' OR s.name like '%" . $busca['value'] . "%' OR p.id like '%" . $busca['value'] . "%' OR p.EAN like '%" . $busca['value'] . "%')";
            }
        }
        if (isset($postdata['sku']) && strlen(trim($postdata['sku'])) > 0) {
            $query = $query->like('p.sku', $postdata['sku']);
        }
        if (isset($postdata['product']) && strlen(trim($postdata['product'])) > 0) {
            $query = $query->like('p.name', $postdata['product']);
        }
        if (isset($postdata['status'])) {
            $query = $query->where('p.status', $postdata['status']);
        } else {
            $query = $query->where('p.status !=', 4); // DELETED_PRODUCT
        }
        if (isset($postdata['situation'])) {
            $query = $query->where('p.situacao', $postdata['situation']);
        }

        if (isset($postdata['estoque'])) {
            switch ($postdata['estoque']) {
                case 1:
                    $query = $query->where('p.qty>', "0");
                    break;
                case 2:
                    $query = $query->where('p.qty<', "1");
                    break;
            }
        }
        if (isset($postdata['kit'])) {
            switch ($postdata['kit']) {
                case 1:
                    $query = $query->where('p.is_kit!=', "true");
                    break;
                case 2:
                    $query = $query->where('p.is_kit', "true");
                    break;
            }
        }

        if (isset($postdata['lojas'])) {
            if (is_array($postdata['lojas'])) {
                $query = $query->where_in('s.id', $postdata['lojas']);
            }
        }
        if (isset($postdata['marketplace'])) {
            $query = $query->join('prd_to_integration pti', ' pti.prd_id = p.id', 'left');
            $query = $query->join('errors_transformation et', 'et.prd_id = p.id', 'left');
            $query = $query->where_in('pti.int_to', $postdata['marketplace']);
            $select_fields[] = 'GROUP_CONCAT(DISTINCT(pti.int_to)) as Integrations';
            if (strlen(trim($postdata['integration'])) > 0 && $postdata['integration'] != 999) {
                switch ($postdata['integration']) {
                    case 30:
                        $query = $query->where('et.status', 0);
                        break;
                    case 40:
                        $query = $query->where('pti.ad_link !=', null);
                        break;
                    default:
                        $query = $query->where('pti.status_int', $postdata['integration']);
                        break;
                }
            }
        }
        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $query = $query->where(' p.company_id', $this->data['usercomp']);
            } else {
                $query = $query->where('p.store_id', $this->data['userstore']);
            }
        }
        if ($postdata['variation'] === 'true') {
            $group_by[] = 'pv.id';
            $query = $query->join('prd_variants pv', 'pv.prd_id=p.id', 'left');
            $select_fields[] = 'pv.id as variant_id';
            $select_fields[] = 'pv.sku as variant_sku';
            $select_fields[] = 'pv.name as variant_name';
            $select_fields[] = 'pv.qty as variant_qty';
            $select_fields[] = 'pv.price as variant_price';
            $select_fields[] = 'pv.image as variant_image';
            $select_fields[] = 'pv.status as variant_status';
        }

        $query = $query->join('stores s', 's.id=p.store_id', 'left');
        $query = $query->group_by($group_by);
        $query = $query->select($select_fields);
        $date = new DateTime();
        $sql = $query->get_compiled_select();
        $data = [
            'sql_genereted' => $sql,
            'file_name' => 'Product_exports_' . $date->getTimestamp() . ".csv",
            'user_id' => $this->session->userdata['id'],
        ];
        if ($this->db->query($sql)->num_rows() <= 0) {
            return ['sucess' => false, 'message' => $this->lang->line('application_not_export_csv_empty')];
        }
        if ($postdata['variation'] === 'true') {
            $data['type'] = 'Variation';
        } else {
            $data['type'] = 'Product';
        }
        $insert = $this->db->insert(self::TABLE_NAME, $data);

        if ($this->db->query($sql)->num_rows() >= 10000) {
            return ['warning' => (bool)$insert, 'message' => $this->lang->line('application_csv_scheduled_for_export_massive')];
        } else {
            return ['sucess' => (bool)$insert, 'message' => $this->lang->line('application_csv_scheduled_for_export')];
        }
    }

    public function save(int $id = 0, array $data = [])
    {
        if (empty($id)) {
            return $this->db->insert(self::TABLE_NAME, $data);
        }
        return $this->update($id, $data);
    }

    public function findAllIfFile_create_dateIsNull()
    {
        return $this->db->select()->from(self::TABLE_NAME)->where(['file_create_date' => null])->get();
    }
    public function findToDelete($limitDays = 3)
    {
        return $this->db
            ->select(['*', 'DATEDIFF(now(),file_create_date) as file_time'])
            ->from(self::TABLE_NAME)
            ->where(['file_create_date!=' => null, 'DATEDIFF(now(),file_create_date)>=' => $limitDays, 'file_delete_date' => null])
            ->get();
    }
    public function update($id, $data)
    {
        return $this->db->update(self::TABLE_NAME, $data, array('id' => $id));
    }

    /**
     * Método para ser utilizado no fetch table.
     * Busca todos os csvs gerados e seus respectivos arquivos e usuários solicitantes..
     * @param   int|null    $offset
     * @param   int|null    $limit
     * @param   array       $order_by
     * @param   string|null $search_text
     * @param   array       $filters        $filters = ['where' => ['column' => 'value']]
     * @param   bool        $return_count
     * @return  array|array[]|int
     */
    public function getGeneratedCsv(?int $offset = 0, ?int $limit = 200, array $order_by = array(), string $search_text = null, array $filters = [], bool $return_count = false, array $fields_order = array())
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

        $columns = $this->db->list_fields(SELF::TABLE_NAME);

        // Apenas as colunas que devem ser selecionadas.
        $select_columns = [];

        // Apenas impede de retornar o sql gerado.
        foreach ($columns as $column) {
            if ($column == 'sql_genereted')
                continue;
            $select_columns[] = SELF::TABLE_NAME . ".$column";
        }

        // Monta o select.
        $this->db->select(implode(',', $select_columns) . ", users.username");
        $this->db->from(SELF::TABLE_NAME);
        $this->db->join('users', SELF::TABLE_NAME . '.user_id = users.id');

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

        if (!$return_count) {
        }

        // Retorna o count ou o resultado em si.
        return $return_count ? $this->db->get()->num_rows() : $this->db->get()->result_array();
    }
}
