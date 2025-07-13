<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Create Table frete_ocorrencias_history
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => FALSE
            ),
            'freights_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'codigo' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'tipo' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'nome' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => FALSE
            ),
            'data_ocorrencia' => array(
                'type' => 'VARCHAR',
                'constraint' => ('20'),
                'null' => TRUE
            ),
            'data_atualizacao' => array(
                'type' => 'VARCHAR',
                'constraint' => ('20'),
                'null' => TRUE
            ),
            'data_reentrega' => array(
                'type' => 'VARCHAR',
                'constraint' => ('20'),
                'null' => TRUE
            ),
            'prazo_devolucao' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'mensagem' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'avisado_marketplace' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'default' => 0
            ),
            'avisado_erp' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 0
            ),
            'addr_place' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'addr_name' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'addr_num' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'addr_cep' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'addr_neigh' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'addr_city' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'addr_state' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            'action' => array(
                'type' => 'VARCHAR',
                'constraint' => ('25'),
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("frete_ocorrencias_history", TRUE);
	}

	public function down()	{
        $this->dbforge->drop_table("frete_ocorrencias_history", TRUE);
	}
};
