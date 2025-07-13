<?php
/**
 * Class Model_control_sync_skuseller_skumkt
 */
class Model_control_sync_skuseller_skumkt extends CI_Model
{
    public function __construct() {
		parent::__construct();
    }

    public function create(array $data): bool
    {
        return $this->db->insert('control_sync_skuseller_skumkt', $data);
    }
    public function create_batch(array $data): bool
    {
        return $this->db->insert_batch('control_sync_skuseller_skumkt', $data);
    }

    public function removeAllVariation(int $prd_id, string $int_to)
    {
        return $this->db->where(array(
            'prd_id' => $prd_id,
            'int_to' => $int_to
        ))->where('variant IS NOT NULL', null, false)
        ->delete('control_sync_skuseller_skumkt');
    }

    public function remove(int $store_id, string $skuseller, string $int_to)
    {
        return $this->db->where(array(
            'store_id' => $store_id,
            'skuseller' => $skuseller,
            'int_to' => $int_to
        ))->delete('control_sync_skuseller_skumkt');
    }

    public function getByStoreSkuIntTo(int $store_id, string $skuseller, string $int_to): ?array
    {
        return $this->db->where(array(
            'store_id'  => $store_id,
            'skuseller' => $skuseller,
            'int_to'    => $int_to
        ))
            ->order_by('id', 'DESC')
            ->get('control_sync_skuseller_skumkt')
            ->row_array();
    }

    public function checkSkuAvaibility(int $store_id, string $skuseller, string $int_to, string $skumkt): ?array
    {
        return $this->db->where(array(
                'store_id'  => $store_id,
                'skumkt'    => $skumkt,
                'int_to'    => $int_to
            ))
            ->where('skuseller !=', $skuseller)
            ->order_by('id', 'DESC')
            ->get('control_sync_skuseller_skumkt')
            ->row_array();
    }

    public function checkSkuMktAvaibilityInEveryStores(string $int_to, string $skumkt): ?array
    {
        return $this->db->where(array(
                'skumkt' => $skumkt,
                'int_to' => $int_to
            ))
            ->order_by('id', 'DESC')
            ->get('control_sync_skuseller_skumkt')
            ->row_array();
    }

    public function checkSkuWithoutSkumkt(array $products): bool
    {
        foreach ($products as $product) {
            $product_data = $this->db->get_where('products', ['id' => $product])->row_array();

            if (empty($product_data['has_variants'])) {
                if ($this->db->get_where('control_sync_skuseller_skumkt', array(
                    'store_id' => $product_data['store_id'],
                    'skuseller' => $product_data['sku']
                ))->num_rows() == 0) {
                    return true;
                }
            } else {
                $variants = $this->db->get_where('prd_variants', array(
                    'prd_id' => $product
                ))->result_array();

                foreach ($variants as $variant) {
                    if ($this->db->get_where('control_sync_skuseller_skumkt', array(
                        'store_id' => $product_data['store_id'],
                        'skuseller' => $variant['sku']
                    ))->num_rows() == 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}