<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('getFetchDataTables')) {
    /**
     * @param CI_DB_query_builder $db
     * @param array $query
     * @param array $data
     * @param array|null $data_filter_store_company 'store' => 'store_id', 'company' => 'company_id'
     * @param int|null $offset
     * @param int|null $limit
     * @param array $order_by
     * @param string|null $group_by
     * @param string|null $search_text
     * @param array $filters
     * @param bool $return_count
     * @param array $fields_order
     * @return array|array[]|int
     */
    function getFetchDataTables(
        CI_DB_query_builder $db,
        array               $query,
        array               $data = array(),
        ?array              $data_filter_store_company = null,
        ?int                $offset = null,
        ?int                $limit = null,
        array               $order_by = array(),
        ?string             $group_by = null,
        string              $search_text = null,
        array               $filters = array(),
        bool                $return_count = false,
        array               $fields_order = array()
    )
    {
        doQuery($db, $query, $return_count, $order_by);

        if (!empty($search_text) && strlen($search_text) >= 2) {
            $arr_filter_search_text = array();
            foreach ($fields_order as $field_order) {
                if (!empty($field_order)) {
                    $arr_filter_search_text[$field_order] = $search_text;
                }
            }

            if (!empty($arr_filter_search_text)) {
                $db->group_start();
                $db->or_like($arr_filter_search_text);
                $db->group_end();
            }
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
        foreach ($filters as $filters_) {
            foreach ($filters_ as $type_filter => $filter) {
                // vai agrupar a query.
                if (in_array($type_filter, ['group_start', 'group_end', 'or_group_start'])) {
                    $db->$type_filter();
                    continue;
                }
                foreach ($filter as $column => $value) {
                    // usar campos como nulos. IS NOT NULL / IS NULL.
                    if (is_null($value)) {
                        $db->$type_filter(
                            likeText('%!=%', $column) ?
                                str_replace('!=', 'IS NOT NULL', $column) :
                                "$column IS NULL"
                            , null, false
                        );
                        continue;
                    } else if ($column === 'escape') {
                        $db->$type_filter($value, null, false);
                        continue;
                    }

                    $db->$type_filter($column, $value);
                }
            }
        }

        // Filtro para recuperar somente os dados da empresa ou loja.
        // usercomp igual a 1, empresa administradora.
        // usercomp diferente de 1 e userstore diferente de 0, usuário gerencia somente uma loja.
        // usercomp diferente de 1 e userstore igual de 0, usuário gerencia todas as lojas de uma empresa.
        if ($data_filter_store_company && $data['usercomp'] != 1) {
            if ($data['userstore'] == 0) {
                $db->where($data_filter_store_company['company'], $data['usercomp']);
            } else {
                $db->where($data_filter_store_company['store'], $data['userstore']);
            }
        }

        // Existe agrupamento.
        if (!empty($group_by)) {
            $db->group_by($group_by);
        }

        // Existe ordenação.
        if (!empty($order_by)) {
            $db->order_by($order_by[0], $order_by[1]);
        }

        // Existe limite e deslocamento.
        if (!is_null($limit) && !is_null($offset)) {
            $db->limit($limit, $offset);
        }

        return $return_count ? $db->get()->num_rows() : $db->get()->result_array();
    }
}

if (!function_exists('fetchDataTable')) {
    /**
     * Consulta as informações para retornar ao datatable.
     *
     * @param   array               $query
     * @param   array|null          $data_filter_store_company  'store' => 'store_id', 'company' => 'company_id'
     * @param   array               $order_by                   Ordem padrão. [field, direction]
     * @param   string|null         $group_by                   Campo a ser agrupado.
     * @param   array               $permissions                Permissões a serem validadas pelo usuário. Ex.: ['createCarrierRegistration', 'updateCarrierRegistration]. Envie 'admin_group' para grupo de administradores.
     * @param   array               $filters                    Filtros adicionais para a consulta. Ex.: ['where_in' => ['csv_to_verification.store_id' => [10,20,30]], 'where' => ['csv_to_verification.module' => 'Shippingcompany', 'csv_to_verification.final_stuation' => 'success']]
     * @param   array               $fields_order               Campos pertencente a tabela no front para realizar a ordenagem dos resultados. Ex.: ['csv_to_verification.id', 'csv_to_verification.upload_file', 'csv_to_verification.created_at', ...]
     * @param   array               $filter_default             Filtros que devem ser considerados na contagem total de registros.
     * @return  array                                           Será retornado
     * @throws  Exception
     */
    function fetchDataTable(
        array $query,
        array $order_by,
        ?array $data_filter_store_company = null,
        ?string $group_by = null,
        array $permissions = array(),
        array $filters = array(),
        array $fields_order = array(),
        array $filter_default = array()
    ): array
    {
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                // A permissão é somente para quem é administrador.
                if ($permission == 'admin_group' && get_instance()->data['only_admin']) {
                    continue;
                }
                if (!in_array($permission, get_instance()->permission)) {
                    throw new Exception("Sem autorização para fazer essa ação.", 403);
                }
            }
        }

        $db         = get_instance()->db;
        $body_post  = get_instance()->postClean();
        $data       = get_instance()->data;

        $start  = $body_post['start'];
        $length = $body_post['length'];
        $search = $body_post['search'];

        if (empty($start)) {
            $start = 0;
        }

        if (empty($length)) {
            $length = 200;
        }

        if (!empty($search) && isset($search['value'])) {
            $search_text = $search['value'];
        } else {
            $search_text = null;
        }

        if (!empty($body_post['order'])) {
            if ($body_post['order'][0]['dir'] == "asc") {
                $direction = "asc";
            } else {
                $direction = "desc";
            }
            $field = $fields_order[$body_post['order'][0]['column']] ?? '';
            if ($field != "") {
                $order_by = array($field, $direction);
            }
        }

        $filters = array_merge_recursive($filter_default, $filters);

        try {
            $registers = getFetchDataTables(
                $db,
                $query,
                $data,
                $data_filter_store_company,
                $start,
                $length,
                $order_by,
                $group_by,
                $search_text,
                $filters,
                false,
                $fields_order
            );

            $count_filtered = getFetchDataTables(
                $db,
                $query,
                $data,
                $data_filter_store_company,
                null,
                null,
                $order_by,
                $group_by,
                $search_text,
                $filters,
                true,
                $fields_order
            );

            $count_total = getFetchDataTables(
                $db,
                $query,
                $data,
                $data_filter_store_company,
                null,
                null,
                $order_by,
                $group_by,
                null,
                $filter_default,
                true,
                $fields_order
            );
        } catch (Exception $exception) {
            throw new Exception("Não foi possível realizar a consulta.", 400);
        }

        return array(
            'data'              => $registers,
            'recordsFiltered'   => $count_filtered,
            'recordsTotal'      => $count_total
        );
    }
}

if (!function_exists('doQuery')) {
    /**
     * @param CI_DB_query_builder   $db
     * @param array                 $query
     * @param bool                  $return_count
     * @param array                 $order_by
     */
    function doQuery(CI_DB_query_builder $db, array $query, bool $return_count, array $order_by)
    {
        foreach ($query as $type_field => $fields) {
            foreach ($fields as $value) {
                switch ($type_field) {
                    case 'select':
                        if ($return_count) {
                            $db->select($order_by[0] ?? 'id');
                        } else {
                            $db->select($value);
                        }
                        break;
                    case 'from':
                        $db->from($value);
                        break;
                    case 'join':
                        $db->join($value[0], $value[1], $value[2] ?? null);
                        break;
                }
            }
        }
    }
}