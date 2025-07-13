<?php
defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
//        if ($this->db->table_exists('legal_panel_copia_duplicados') )
//        {
//            $this->db->query("DROP TABLE `legal_panel_copia_duplicados`;");
//        }

        //gerar copia da tabela legal_panel
        $this->db->query("
				CREATE TABLE `legal_panel_copia_duplicados` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`notification_type` VARCHAR(255) NOT NULL,
					`notification_title` VARCHAR(255) NULL DEFAULT NULL,
					`orders_id` INT(11) NOT NULL,
					`store_id` INT(11) NOT NULL,
					`notification_id` VARCHAR(255) NOT NULL,
					`status` VARCHAR(255) NULL DEFAULT NULL,
					`description` TEXT NULL DEFAULT NULL,
					`balance_paid` DECIMAL(11,2) NOT NULL,
					`balance_debit` DECIMAL(11,2) NOT NULL,
					`attachment` VARCHAR(255) NULL DEFAULT NULL,
					`creation_date` DATETIME NOT NULL,
					`update_date` DATETIME NULL DEFAULT current_timestamp(),
					`accountable_opening` VARCHAR(255) NOT NULL,
					`accountable_update` VARCHAR(255) NULL DEFAULT NULL,
					`conciliacao_id` INT(11) NULL DEFAULT NULL,
					`lote` VARCHAR(255) NULL DEFAULT NULL,
					PRIMARY KEY (`id`) USING BTREE
				)
				");

        $this->db->query("INSERT INTO `legal_panel_copia_duplicados` 
				(`id`, `notification_type`, `notification_title`, `orders_id`, `store_id`, `notification_id`, `status`, `description`, 
				`balance_paid`, `balance_debit`, `attachment`, `creation_date`, `update_date`, `accountable_opening`, `accountable_update`, 
				`conciliacao_id`, `lote`) 
				SELECT 
				`id`, `notification_type`, `notification_title`, `orders_id`, `store_id`, `notification_id`, `status`, `description`, 
				`balance_paid`, `balance_debit`, `attachment`, `creation_date`, `update_date`, `accountable_opening`, `accountable_update`, 
				`conciliacao_id`, `lote` 
				FROM `legal_panel`;");

        $duplicated = [];

        $duplicated = $this->db->query("
		SELECT 
			orders_id 
			,notification_title
			,notification_id
			,COUNT(*) AS total
			,(SELECT COUNT(*) FROM legal_panel tempf WHERE status = 'Chamado Fechado' and tempf.orders_id = lp.orders_id) AS fechados 
			,(SELECT COUNT(*) FROM legal_panel tempa WHERE status = 'Chamado Aberto' and tempa.orders_id = lp.orders_id) AS abertos 
			,(SELECT GROUP_CONCAT(id) FROM legal_panel tempi WHERE status = 'Chamado Aberto' and tempi.orders_id = lp.orders_id) AS ids_abertos
		FROM 
			legal_panel lp 
		WHERE 
			notification_id = 'Devolução de produto.'
		group BY 
			orders_id, notification_title
		HAVING 
			count(*) > 1;
		");

        $duplicated = $duplicated->result_array();

        if (!empty($duplicated))
        {
            foreach ($duplicated as $row)
            {
                if (!$row['abertos'] > 0)
                {
                    continue;
                }

                $closing_ids = explode(',', $row['ids_abertos']);

                if ($row['fechados'] == 0)
                {
                    array_shift($closing_ids);
                }

                if (!empty($closing_ids))
                {
                    $update_ids = implode(',',$closing_ids);
                    $sql = "update legal_panel set status = 'Chamado Fechado' where id in (".$update_ids.")";
                    $this->db->query($sql);
                }
            }
        }
    }

    public function down()
    {
        $this->db->query("RENAME TABLE `legal_panel` TO `legal_panel_copia_duplicados_rollback;");
        $this->db->query("RENAME TABLE `legal_panel_copia_duplicados` TO `legal_panel`;");
    }
};