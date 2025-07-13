<?php

class Model_csv_import_attributes_products extends CI_Model
{
    const TABLE = 'csv_import_attributes_products';
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
        $sql = "select * from csv_import_attributes_products
        where checked = ? order by date_create  ";

        $query = $this->db->query($sql, array($checked));
        return $query->result_array();
    }

    public function getNotSent() {
        $sql = "select * from csv_import_attributes_products
        where sent_email = 0 and checked = 1 order by date_create  ";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getErrorsTransformation($id) {
        $sql = "select * from csv_error_transformation
        where csv_import_attributes_products_id = ?
        order by id ";

        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }

    public function markChecked($id, $valid) {
        $data = array("checked" => true, "valid" => $valid);
        $this->db->where('id', $id);
        $update = $this->db->update('csv_import_attributes_products', $data);
        return ($update == true) ? true : false;
    }

    public function markSent($id) {
        $data = array("sent_email" => true);
        $this->db->where('id', $id);
        $update = $this->db->update('csv_import_attributes_products', $data);
        return ($update == true) ? true : false;
    }

    public function insertErrorTransformation($csv_import_attributes_products_id, $message) {
        $data = array(
            "csv_import_attributes_products_id" => $csv_import_attributes_products_id,
            "message" => $message
        );
        $insert = $this->db->insert('csv_error_transformation', $data);
        return ($insert == true) ? $this->db->insert_id() : false;
    }

    public function insertCsvImported($data) {
        $insert = $this->db->insert('csv_import_attributes_products', $data);
        return ($insert == true) ? $this->db->insert_id() : false;
    }
}
