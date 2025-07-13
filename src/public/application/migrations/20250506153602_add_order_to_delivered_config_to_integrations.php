<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => TRUE,
                'auto_increment' => TRUE
            ],
            'marketplace' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => FALSE
            ],
            'dias_para_atualizar' => [
                'type' => 'INT',
                'null' => FALSE,
                'default' => 0
            ],
            'url_consulta' => [
                'type'       => 'TEXT',
                'null'       => TRUE
            ],
            'sequencial_nfe' => [
                'type' => 'TINYINT',
                'null' => FALSE,
                'default' => 0
            ],
            'transportadora' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE
            ],
            'metodo_envio' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE
            ],
            'codigo_rastreio' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE
            ],
            'url_rastreio' => [
                'type' => 'TEXT',
                'null' => TRUE
            ],
            'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('order_to_delivered_config', TRUE);
    
	}

	public function down()	{
		$this->dbforge->drop_table('order_to_delivered_config', TRUE);
	}
};