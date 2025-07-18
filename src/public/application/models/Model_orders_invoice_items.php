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

        // Items must contain invoice_id, order_item_id and qty_invoiced fields
        return $this->db->insert_batch('orders_invoice_items', $items);
    }

    public function getInvoicedQuantities(int $orderId): array
    {
        $this->db->select('oi.sku, SUM(oii.qty_invoiced) as quantity');
        $this->db->from('orders_invoice_items oii');
        $this->db->join('orders_invoices inv', 'inv.id = oii.invoice_id');
        $this->db->join('orders_item oi', 'oi.id = oii.order_item_id');
        $this->db->where('inv.order_id', $orderId);
        $this->db->group_by('oi.sku');

        return $this->db->get()->result_array();
    }
}
