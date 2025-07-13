<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query("ALTER TABLE commissionings MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");

        // Trigger para commissioning_trade_policies
        $this->db->query(" CREATE TRIGGER trigger_updated_at BEFORE UPDATE ON commissionings FOR EACH ROW BEGIN IF NEW.name != OLD.name OR NEW.type != OLD.type OR NEW.int_to != OLD.int_to OR NEW.start_date != OLD.start_date OR NEW.end_date != OLD.end_date OR NEW.created_at != OLD.created_at THEN SET NEW.updated_at = NOW(); END IF; END");

	}

	public function down()	{
        $this->db->query("DROP TRIGGER IF EXISTS trigger_updated_at;");
	}
};