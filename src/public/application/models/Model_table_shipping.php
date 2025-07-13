<?php
/*

Model de Acesso ao BD para a tabela table shipping

*/

class Model_table_shipping extends CI_Model
{
    public function __construct()
	{
		parent::__construct();
	}

    public function getShippingPriceQtdDays($provider_ID, $weight, $cep)
    {
        if ($provider_ID && $weight && $cep) {
            $sql = 'SELECT shipping_price, qtd_days FROM table_shipping ts WHERE status = 1 '.
                'AND idproviders_to_seller = ? '.
                'AND weight_minimum <= ? AND weight_maximum >= ? '.
                'AND cep_start <= ? AND cep_end >= ?';
            $query = $this->db->query($sql, array($provider_ID, $weight, $weight, $cep, $cep));
            return $query->result_array();
        }

    }

    public function createTemporary(array $data): bool
    {
        return $this->db->insert_batch('table_shipping_temporary', $data) == true;
        //return $this->db->insert('table_shipping_temporary', $data) == true;
    }

    public function getTemporaryByFile(int $file): array
    {
        return $this->db->where(array('file_id' => $file))->group_by('cep_start,cep_end', 'ASC')->order_by('id', 'ASC')->get('table_shipping_temporary')->result_array();
    }

    public function getTemporaryByFileAndRangeZip(int $file, string $zipcodeStart, string $zipcodeEnd): array
    {
        return $this->db->select('id,cep_start,cep_end,weight_start,weight_end,line_file')->where(array('file_id' => $file, 'cep_start' => $zipcodeStart, 'cep_end' => $zipcodeEnd))->order_by('id', 'ASC')->get('table_shipping_temporary')->result_array();
    }

    public function deleteTemporary(int $fileId): bool
    {
        return $this->db->where('file_id', $fileId)->delete('table_shipping_temporary') == true;
    }
    public function transferDataTemporaryToReal(int $fileTempId, int $fileId): bool
    {
        $data = $this->db->select("
            shipping_company_id AS idproviders_to_seller,
            $fileId             AS id_file,
            date_created        AS dt_envio,
            region              AS region,
            cep_start           AS CEP_start,
            cep_end             AS CEP_end,
            weight_start        AS weight_minimum,
            weight_end          AS weight_maximum,
            price               AS shipping_price,
            deadline            AS qtd_days,
            1                   AS status
        ")->where(array('file_id' => $fileTempId))->get('table_shipping_temporary')->result_array();

        return $this->db->insert_batch('table_shipping', $data);
    }

    public function transferDataTemporaryToTableRegions(int $fileTempId, int $fileId): bool
    {
        $this->load->model('model_table_shipping_regions');
        $table_regions = $this->model_table_shipping_regions->getAllActive();

        foreach ($table_regions as $table_region) {
            $data = $this->db->select("
                shipping_company_id AS idproviders_to_seller,
                $fileId             AS id_file,
                date_created        AS dt_envio,
                region              AS region,
                cep_start           AS CEP_start,
                cep_end             AS CEP_end,
                weight_start        AS weight_minimum,
                weight_end          AS weight_maximum,
                price               AS shipping_price,
                deadline            AS qtd_days,
                1                   AS status
            ")->where('file_id', $fileTempId)
            ->group_start()
                ->or_where("'$table_region[zipcode_start]' between cep_start and cep_end", NULL, FALSE)
                ->or_where("'$table_region[zipcode_end]' between cep_start and cep_end", NULL, FALSE)
                ->or_where("cep_start between '$table_region[zipcode_start]' and '$table_region[zipcode_end]'", NULL, FALSE)
                ->or_where("cep_end between '$table_region[zipcode_start]' and '$table_region[zipcode_end]'", NULL, FALSE)
            ->group_end()
            ->get('table_shipping_temporary')
            ->result_array();

            // Salva os dados na nova tabela de região.
            if (count($data)) {
                $this->db->insert_batch($table_region['table'], $data);

                $registers_duplicated = $this->db->select('id_file, idproviders_to_seller, CEP_start, CEP_end, weight_minimum, weight_maximum, COUNT(*) as duplicated')
                    ->where('id_file',  $fileId)
                    ->group_by('id_file, idproviders_to_seller, CEP_start, CEP_end, weight_minimum, weight_maximum')
                    ->having('COUNT(*) >', 1)
                    ->get($table_region['table'])->result_array();

                foreach ($registers_duplicated as $register_duplicated) {
                    $quantity_duplicated = (int)$register_duplicated['duplicated'];
                    $this->db->delete($table_region['table'], array(
                        'idproviders_to_seller' => $register_duplicated['idproviders_to_seller'],
                        'CEP_start'             => $register_duplicated['CEP_start'],
                        'CEP_end'               => $register_duplicated['CEP_end'],
                        'weight_minimum'        => $register_duplicated['weight_minimum'],
                        'weight_maximum'        => $register_duplicated['weight_maximum'],
                        'id_file'               => $fileId
                    ), $quantity_duplicated - 1);
                }
            }
        }

        return true;
    }

    public function getActivesByRangeZipcodeAndLimitOffset(string $zipcodeStart, string $zipcodeEnd, int $limit, int $offset): array
    {
        return $this->db
            ->select('
                idproviders_to_seller,
                id_file,
                dt_envio,
                region,
                CEP_start,
                CEP_end,
                weight_minimum,
                weight_maximum,
                shipping_price,
                qtd_days,
                status
            ')
            ->limit($limit, $offset)
            ->where('status', true)
            ->group_start()
                ->or_where("'$zipcodeStart' between cep_start and cep_end", NULL, FALSE)
                ->or_where("'$zipcodeEnd' between cep_start and cep_end", NULL, FALSE)
                ->or_where("cep_start between '$zipcodeStart' and '$zipcodeEnd'", NULL, FALSE)
                ->or_where("cep_end between '$zipcodeStart' and '$zipcodeEnd'", NULL, FALSE)
            ->group_end()
            ->get('table_shipping')
            ->result_array();
    }
}