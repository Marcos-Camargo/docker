<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Marcas/Fabricantes
 
 */

class Model_catalogs_associated extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function create(array $data)
    {
        $insert = $this->db->insert('catalogs_associated', $data);
        return ($insert) ? $this->db->insert_id() : false;
    }
    
    public function update(array $data, int $id): bool
    {
        try {
            return $this->db->where('id', $id)->update('catalogs_associated', $data);
        }
        catch ( Exception $e ) {
            return false;
        }
    }
    
    public function remove(int $id)
    {
        try {
            return $this->db->where('id', $id)->delete('catalogs_associated');
        } catch (Exception $e) {
            return false;
        }
    }

    public function removeByCatalogFrom(int $catalog_id_from)
    {
        try {
            return $this->db->where('catalog_id_from', $catalog_id_from)->delete('catalogs_associated');
        } catch (Exception $e) {
            return false;
        }
    }

    public function getCatalogIdToByCatalogFrom(int $catalog_id_from)
    {
        try {
            return array_map(
                function($catalog){
                    return $catalog['catalog_id_to'];
                },
                $this->db->get_where('catalogs_associated', array('catalog_id_from' => $catalog_id_from))->result_array()
            );
        } catch (Exception $e) {
            return false;
        }
    }
}