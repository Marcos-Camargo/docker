<?php
class Model_orders_invoice_items extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createItems(array $items)
    {
        if (empty($items)) {
            return false;
        }
        return $this->db->insert_batch('orders_invoice_items', $items);
    }

    public function getInvoicedQuantities(int $orderId): array
    {
        $this->db->select('oii.sku, SUM(oii.quantity) as quantity');
        $this->db->from('orders_invoice_items oii');
        $this->db->join('orders_invoices oi', 'oi.id = oii.invoice_id');
        $this->db->where('oi.order_id', $orderId);
        $this->db->group_by('oii.sku');
        return $this->db->get()->result_array();
    }
}
