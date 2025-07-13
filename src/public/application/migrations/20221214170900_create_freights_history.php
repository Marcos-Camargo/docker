<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Create Table freights_history
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => FALSE
            ),
            'order_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'item_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'ship_company' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE,
                'default' => NULL
            ),
            'status_ship' => array(
                'type' => 'VARCHAR',
                'constraint' => ('30'),
                'null' => TRUE,
                'default' => NULL
            ),
            'date_delivered' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE,
                'default' => NULL
            ),
            'ship_value' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            'prazoprevisto' => array(
                'type' => 'VARCHAR',
                'constraint' => ('25'),
                'null' => FALSE
            ),
            'idservico' => array(
                'type' => 'VARCHAR',
                'constraint' => ('50'),
                'null' => FALSE
            ),
            'codigo_rastreio' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'link_etiqueta_a4' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'link_etiqueta_termica' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'link_etiquetas_zpl' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE,
                'default' => NULL
            ),
            'link_plp' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            '`data_etiqueta` timestamp NULL DEFAULT NULL',
            'CNPJ' => array(
                'type' => 'VARCHAR',
                'constraint' => ('20'),
                'null' => TRUE,
                'default' => NULL
            ),
            'method' => array(
                'type' => 'VARCHAR',
                'constraint' => ('100'),
                'null' => TRUE,
                'default' => NULL
            ),
            'cte' => array(
                'type' => 'VARCHAR',
                'constraint' => ('100'),
                'null' => TRUE,
                'default' => NULL
            ),
            'solicitou_plp' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 0
            ),
            'sgp' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 0
            ),
            '`updated_date` datetime NULL DEFAULT NULL',
            'history_update' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'url_tracking' => array(
                'type' => 'VARCHAR',
                'constraint' => ('1024'),
                'null' => TRUE,
                'default' => NULL
            ),
            'shipping_order_id' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE,
                'default' => NULL
            ),
            'in_resend_active' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 0
            ),
            'volume' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 0
            ),
            'action' => array(
                'type' => 'VARCHAR',
                'constraint' => ('25'),
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("freights_history", TRUE);
	}

	public function down()	{
        $this->dbforge->drop_table("freights_history", TRUE);
	}
};
