<?php

class Model_csv_import_rd_sku extends CI_Model
{
    const TABLE = 'csv_import_rd_sku';
    public function __construct()
    {
        parent::__construct();
    }

    public function create($data)
    {
        $create = $this->db->insert(self::TABLE, $data);
        return $create;
    }

    public function getList($checked = false) {
        $sql = "SELECT * FROM csv_import_rd_sku WHERE checked = ? ORDER BY date_create  ";

        $query = $this->db->query($sql, array($checked));
        return $query->result_array();
    }

    public function getNotSent() {
        $sql = "SELECT * FROM csv_import_rd_sku WHERE sent_email = 0 AND checked = 1 ORDER BY date_create ";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getErrorsTransformation($id) {
        $sql = "SELECT * FROM csv_import_rd_sku WHERE csv_import_attributes_products_id = ? ORDER BY id ";

        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }

    public function markChecked($id, $valid) {
        $data = array("checked" => true, "valid" => $valid);
        $this->db->where('id', $id);
        $update = $this->db->update('csv_import_rd_sku', $data);
        return ($update == true) ? true : false;
    }

    public function markSent($id) {
        $data = array("sent_email" => true);
        $this->db->where('id', $id);
        $update = $this->db->update('csv_import_rd_sku', $data);
        return ($update == true) ? true : false;
    }

    public function insertErrorTransformation($csv_import_rd_sku_id, $message) {
        $data = array(
            "csv_import_rd_sku_id" => $csv_import_rd_sku_id,
            "message" => $message
        );
        $insert = $this->db->insert('csv_error_transformation', $data);
        return ($insert == true) ? $this->db->insert_id() : false;
    }

    public function insertCsvImported($data) {
        $insert = $this->db->insert('csv_import_rd_sku', $data);
        return ($insert == true) ? $this->db->insert_id() : false;
    }
}
