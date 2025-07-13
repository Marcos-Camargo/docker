<?php

class Model_query_seller_index extends CI_Model
{
    protected $table = 'query_seller_index';

    public function __construct()
    {
        parent::__construct();
    }

    public function insert($query)
    {
        return $this->db->insert($this->table, [
            'query'      => $query
        ]);
    }

    public function get_all($limit = 100)
    {
        return $this->db
            ->order_by('id', 'ASC')
            ->limit($limit)
            ->get($this->table)
            ->result_array();
    }

    public function update(
        $id,
        $storeReputarion,
        $cancelationEvaluation,
        $shippingDelayAssessment,
        $deliveryDelayAssessment
    ){
        return $this->db
            ->where('id', $id)
            ->update($this->table, [
                'store_reputation'      => $storeReputarion,
                'cancellation_evaluation'      => $cancelationEvaluation,
                'shipping_delay_assessment'      => $shippingDelayAssessment,
                'delivery_delay_assessment'      => $deliveryDelayAssessment,
            ]);
    }

    public function get_by_id($id)
    {
        return $this->db
            ->where('id', $id)
            ->get($this->table)
            ->result_array();
    }

    public function execute_query($sql)
    {
        return $this->db->query($sql)->result_array();
    }
}