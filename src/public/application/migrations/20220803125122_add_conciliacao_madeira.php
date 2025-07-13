<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if(!$this->db->table_exists('conciliacao_madeira')) {

            ## Create Table conciliacao_madeira
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,
                    'auto_increment' => TRUE
                ),
                'lote' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                '`data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
                'nome_arquivo' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                'seller' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE,

                ),
                'ref_pedido' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE,

                ),
                'data_pedido' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE,

                ),
                'data_pagamento' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE,

                ),
                'data_liberacao' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE,

                ),
                'data_prevista_pgto' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE,

                ),
                'status' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE,

                ),
                'tipo' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE,

                ),
                'valor' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE,

                ),
                'data' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('90'),
                    'null' => TRUE,

                ),
                'descricao' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('90'),
                    'null' => TRUE,

                ),
                'pedido' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('90'),
                    'null' => TRUE,

                ),
                'pedido_mm' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('90'),
                    'null' => TRUE,

                ),
                'detalhamento' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('90'),
                    'null' => TRUE,

                ),
            ));
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table("conciliacao_madeira", TRUE);
            $this->db->query('ALTER TABLE  `conciliacao_madeira` ENGINE = InnoDB');

        }

	 }

	public function down()	{
		### Drop table conciliacao_madeira ##
		$this->dbforge->drop_table("conciliacao_madeira", TRUE);

	}
};