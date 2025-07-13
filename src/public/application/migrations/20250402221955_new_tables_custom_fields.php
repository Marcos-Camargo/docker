<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        // tabela campos adicionais
        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'store_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true
            ],
            'tid' => [
                'type'    => 'TINYINT',
                'default' => 0
            ],
            'nsu' => [
                'type'    => 'TINYINT',
                'default' => 0
            ],
            'authorization_id' => [
                'type'    => 'TINYINT',
                'default' => 0
            ],
            'first_digits' => [
                'type'    => 'TINYINT',
                'default' => 0
            ],
            'last_digits' => [
                'type'    => 'TINYINT',
                'default' => 0
            ]
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('store_id', true);
        $this->dbforge->create_table('fields_orders_add', true);

        // tabela campos obrigatórios
        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'store_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true
            ],
            'tid' => [
                'type'    => 'TINYINT',
                'default' => 0
            ],
            'nsu' => [
                'type'    => 'TINYINT',
                'default' => 0
            ],
            'authorization_id' => [
                'type'    => 'TINYINT',
                'default' => 0
            ],
            'first_digits' => [
                'type'    => 'TINYINT',
                'default' => 0
            ],
            'last_digits' => [
                'type'    => 'TINYINT',
                'default' => 0
            ]
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('store_id', true);
        $this->dbforge->create_table('fields_orders_mandatory', true);

	}

	public function down()	{
		$this->dbforge->drop_table('fields_orders_add', true);
        $this->dbforge->drop_table('fields_orders_mandatory', true);
	}
};