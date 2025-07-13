<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->dbforge->drop_table('withdrawal_times', true);

        ## Create Table pickup_points
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'pickup_point_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
                'unsigned' => TRUE,
            ),
            'day_of_week' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
            ),
            'start_hour' => array(
                'type' => 'VARCHAR',
                'constraint' => ('8'),
                'null' => TRUE
            ),
            'end_hour' => array(
                'type' => 'VARCHAR',
                'constraint' => ('8'),
                'null' => TRUE
            ),
            'closed_store' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("withdrawal_times", TRUE);

        $this->db->query("CREATE INDEX idx_withdrawal_times_pickup_point_id ON withdrawal_times (pickup_point_id);");
        $this->db->query('ALTER TABLE `withdrawal_times` ADD CONSTRAINT `withdrawal_times_pickup_point_id_fk` FOREIGN KEY (`pickup_point_id`) REFERENCES `pickup_points` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
	}

	public function down()	{
        if ($this->dbforge->index_exists('idx_withdrawal_times_pickup_point_id', 'withdrawal_times')) {
            $this->db->query('DROP INDEX idx_withdrawal_times_pickup_point_id ON withdrawal_times');
        }
        $this->db->query('ALTER TABLE withdrawal_times DROP FOREIGN KEY withdrawal_times_pickup_point_id_fk;');
        $this->dbforge->drop_table("withdrawal_times", TRUE);
	}
};
