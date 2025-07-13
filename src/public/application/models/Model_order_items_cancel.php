<?php 

/**
 * Class Model_order_items_cancel
 *
 * @property CI_DB_query_builder $db
 */
class Model_order_items_cancel extends CI_Model
{
    protected $table = 'order_items_cancel';

    public function __construct() {
		parent::__construct();
    }

    public function create($data): bool
    {
        if ($data) {
            $insert = $this->db->insert($this->table, $data);
            return $insert == true;
        }
        return false;
    }

    public function getByOrderId(int $order_id): array
    {
        return $this->db->get_where($this->table, array('order_id' => $order_id))->result_array();
    }

    public function getByOrderIdAndItem(int $order_id, int $item_id): ?array
    {
        return $this->db->get_where($this->table, array('order_id' => $order_id, 'item_id' => $item_id))->row_array();
    }

    public function removeByOrderid(int $order_id)
    {
        return $this->db->where('order_id', $order_id)->delete($this->table);
    }

    public function getItemsCanceledProductsByOrder(int $order_id = null)
    {
        if (!$order_id) {
            return false;
        }

        return $this->db->select('orders_item.*, order_items_cancel.qty as qty_cancel, order_items_cancel.total_amount_canceled_mkt')
            ->join('orders_item', "$this->table.item_id = orders_item.id")
            ->where('orders_item.order_id', $order_id)
            ->get($this->table)
            ->result_array();
    }

    public function updateByItemId(int $item_id, array $data): bool
    {
        return $this->db->where('item_id', $item_id)->update($this->table, $data);
    }

    public function updateById(int $id, array $data): bool
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }
}