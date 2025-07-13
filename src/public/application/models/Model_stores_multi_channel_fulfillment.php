<?php
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

 */
class Model_stores_multi_channel_fulfillment extends CI_Model
{
    public $table = 'stores_multi_channel_fulfillment';
    public $type_store = [
        'store'                          => 0,
        'main_multi_channel_fulfillment' => 1,
        'cd_multi_channel_fulfillment'   => 2
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data): bool
    {
        if ($data) {
            $insert = $this->db->insert($this->table, $data);
            return $insert == true;
        }

        return false;
    }

    public function create_batch(array $data): bool
    {
        if ($data) {
            $insert = $this->db->insert_batch($this->table, $data);
            return $insert == true;
        }

        return false;
    }

    public function remove(int $store_id_cd): bool
    {
        return $this->db->delete($this->table, array('store_id_cd' => $store_id_cd));
    }

    public function checkAvailabilityRangeZipcode(int $store_id_cd, int $store_id_principal, string $zipcode_start, string $zipcode_end): bool
    {
        return $this->db
            ->where(
                array(
                    'store_id_cd !='        => $store_id_cd,
                    'store_id_principal'    => $store_id_principal
                )
            )
            ->group_start()
                ->or_group_start()
                ->where([
                    'zipcode_start >='  =>  $zipcode_start,
                    'zipcode_end <='    =>  $zipcode_end
                ])
                ->group_end()
                ->or_group_start()
                ->where([
                    'zipcode_end >='    =>  $zipcode_start,
                    'zipcode_start <='  =>  $zipcode_end
                ])
                ->group_end()
            ->group_end()
            ->limit(1)
            ->get($this->table)
            ->num_rows() == 0;
    }

    public function getRangeZipcode(int $store_id_cd, int $company_id, int $limit = null): array
    {

        $this->db
            ->where(
                array(
                    'store_id_cd'   => $store_id_cd,
                    'company_id'    => $company_id
                )
            );
        if (!is_null($limit)){
            $this->db->limit($limit); 
        }
        return $this->db->get($this->table)->result_array();

    }

    public function getStoresCD(int $store_id_principal, int $company_id): array
    {
        $this->db->distinct()
            ->select('store_id_cd')
            ->where(
                array(
                    'store_id_principal'    => $store_id_principal,
                    'company_id'            => $company_id
                )
            );
        return $this->db->get($this->table)->result_array();       

    }

    public function getMainStoreByCDStore(int $store_id_cd): array
    {
        return $this->db->where('store_id_cd', $store_id_cd)->get($this->table)->row_array();
    }
    
}
