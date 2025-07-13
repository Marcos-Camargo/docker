<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query('
            CREATE TABLE `control_sequential_skumkts` (
                `id` BIGINT DEFAULT NULL,
                `prd_id` INT(11) DEFAULT NULL,
                `variant` INT(11) DEFAULT NULL,
                `int_to` VARCHAR(255) NOT NULL,
                `created_at` timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
                `updated_at` timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0),
                UNIQUE KEY (`id`, `int_to`)
            );'
        );

        $this->db->query('
            CREATE TRIGGER `control_sequential_skumkts_trigger_before` BEFORE INSERT ON `control_sequential_skumkts` FOR EACH ROW BEGIN
                if (ISNULL(NEW.id)) then
                    set NEW.id = (SELECT IFNULL(MAX(id), 0) + 1 FROM control_sequential_skumkts WHERE int_to  = NEW.int_to);
                end if;
            END;
        ');

        $this->db->query('CREATE INDEX idx_control_sequential_skumkts_prd_var_intto ON control_sequential_skumkts (`prd_id`,`variant`,`int_to`);');
	}

	public function down()	{
        $this->db->query('DROP INDEX idx_control_sequential_skumkts_prd_var_intto ON control_sequential_skumkts');
        $this->db->query('DROP TRIGGER `control_sequential_skumkts_trigger_before`');
        $this->dbforge->drop_table("control_sequential_skumkts", TRUE);
    }
};