<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->update('table_shipping_regions', array('migrated_data' => 0, 'migrated_at' => null));

        if ($this->db->where('module_path', 'Logistic/TableFreightMigration')->where('module_method', 'run')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Remigrar tabela de frete para as tabelas por estado",
                'event_type' => '60',
                'start' => '2023-10-07 03:00:00',
                'end' => '2023-12-31 20:00:00',
                'module_path' => 'Logistic/TableFreightMigration',
                'module_method' => 'run',
                'params' => 'null'
            ));
        } else {
            $this->db->update('calendar_events', array(
                'end' => '2023-12-31 20:00:00',
                'event_type' => '60'
            ), array(
                'module_path' => 'Logistic/TableFreightMigration'
            ));
        }

        $setting_enable_table_shipping_regions = $this->db->where('name', 'enable_table_shipping_regions')->get('settings')->row_array();
        if ($setting_enable_table_shipping_regions && $setting_enable_table_shipping_regions['status'] == 1) {
            $this->db->update('csv_to_verification', array('checked' => 0, 'final_situation' => 'wait'), array('module' => 'Shippingcompany', 'final_situation' => 'success', 'created_at >=' => '2023-10-07 00:00:00'));
        }
    }

	public function down()	{
        $this->db->update('calendar_events', array(
            'end' => '2023-10-07 04:00:00',
            'event_type' => '74'
        ), array(
            'module_path' => 'Logistic/TableFreightMigration'
        ));
	}
};