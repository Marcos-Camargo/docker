<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->dbforge->drop_table('pickup_points', true);

        ## Create Table pickup_points
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'status' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => 0
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'cep' => array(
                'type' => 'VARCHAR',
                'constraint' => ('8'),
                'null' => FALSE
            ),
            'street' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'state' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'number' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'complement' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'district' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'city' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'country' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("pickup_points", TRUE);

        $this->db->query("CREATE INDEX idx_pickup_points_store_id ON pickup_points (store_id);");
	}

	public function down()	{
        if ($this->dbforge->index_exists('idx_pickup_points_store_id', 'pickup_points')) {
            $this->db->query('DROP INDEX idx_pickup_points_store_id ON pickup_points');
        }
        $this->dbforge->drop_table("pickup_points", TRUE);
	}
};
