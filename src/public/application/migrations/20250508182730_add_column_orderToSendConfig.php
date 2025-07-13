<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fields = [
            'has_order_to_send_config' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'comment'    => '0 = não configurada, 1 = configurada'
            ]
        ];

        $this->dbforge->add_column('stores', $fields);

        $this->db->query("
            UPDATE stores s
            SET has_order_to_send_config = 1
            WHERE EXISTS (
                SELECT 1 FROM integrations i
                JOIN order_to_delivered_config c ON c.marketplace = i.name
                WHERE i.store_id = s.id
            )
        ");
	}

	public function down()	{
        $this->dbforge->drop_column('stores', 'has_marketplace_config');
	}
};