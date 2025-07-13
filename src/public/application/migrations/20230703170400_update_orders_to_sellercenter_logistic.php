<?php defined('BASEPATH') or exit('No direct script access allowed');


return new

/**
 * @property Model_orders $model_orders
 */

class extends CI_Migration {

    public function up()
    {
        $this->load->model('model_orders');
        $integrations = $this->db->select('integration, store_id')->where('credentials is null', NULL, FALSE)->get('integration_logistic')->result_array();

        foreach ($integrations as $integration) {
            $this->db->where('store_id', $integration['store_id'])
                ->where_not_in('paid_status', $this->model_orders->PAID_STATUS['finished_order'])
                ->update('orders', array('integration_logistic' => $integration['integration']));
        }
    }

    public function down()
    {
        $this->db->where('integration_logistic is not null', NULL, FALSE)
            ->update('orders', array('integration_logistic' => null));

    }
};