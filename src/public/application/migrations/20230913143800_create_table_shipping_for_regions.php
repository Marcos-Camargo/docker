<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        $tables = array(
            'table_shipping_ac',
            'table_shipping_al',
            'table_shipping_ap',
            'table_shipping_am',
            'table_shipping_ba',
            'table_shipping_ce',
            'table_shipping_df',
            'table_shipping_es',
            'table_shipping_go',
            'table_shipping_ma',
            'table_shipping_mt',
            'table_shipping_ms',
            'table_shipping_mg',
            'table_shipping_pa',
            'table_shipping_pb',
            'table_shipping_pr',
            'table_shipping_pe',
            'table_shipping_pi',
            'table_shipping_rj',
            'table_shipping_rn',
            'table_shipping_rs',
            'table_shipping_ro',
            'table_shipping_rr',
            'table_shipping_sc',
            'table_shipping_sp',
            'table_shipping_se',
            'table_shipping_to',
            'table_shipping_xx'
        );

        foreach ($tables as $table) {
            $this->dbforge->add_field(array(
                'idtable_shipping' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,
                    'auto_increment' => TRUE
                ),
                'idproviders_to_seller' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,

                ),
                'id_file' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,

                ),
                'dt_envio' => array(
                    'type' => 'DATETIME',
                    'null' => FALSE,

                ),
                'region' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('45'),
                    'null' => FALSE,

                ),
                'CEP_start' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('8'),
                    'null' => FALSE,

                ),
                'CEP_end' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('8'),
                    'null' => FALSE,

                ),
                'weight_minimum' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('15,3'),
                    'null' => FALSE,

                ),
                'weight_maximum' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('15,3'),
                    'null' => FALSE,

                ),
                'shipping_price' => array(
                    'type' => 'DECIMAL',
                    'constraint' => ('15,2'),
                    'null' => FALSE,

                ),
                'qtd_days' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,

                ),
                'status' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => TRUE,
                    'default' => '1',

                ),
            ));
            $this->dbforge->add_key("idtable_shipping",true);
            $this->dbforge->create_table($table, TRUE);
            $this->db->query("ALTER TABLE  $table ENGINE = InnoDB");
            $this->db->query("CREATE INDEX index_by_idproviders_to_seller_status_cep_weight ON $table (`idproviders_to_seller`,`status`,`CEP_start`,`CEP_end`,`weight_minimum`,`weight_maximum`);");
        }

	}

	public function down()	{
        $tables = array(
            'table_shipping_ac',
            'table_shipping_al',
            'table_shipping_ap',
            'table_shipping_am',
            'table_shipping_ba',
            'table_shipping_ce',
            'table_shipping_df',
            'table_shipping_es',
            'table_shipping_go',
            'table_shipping_ma',
            'table_shipping_mt',
            'table_shipping_ms',
            'table_shipping_mg',
            'table_shipping_pa',
            'table_shipping_pb',
            'table_shipping_pr',
            'table_shipping_pe',
            'table_shipping_pi',
            'table_shipping_rj',
            'table_shipping_rn',
            'table_shipping_rs',
            'table_shipping_ro',
            'table_shipping_rr',
            'table_shipping_sc',
            'table_shipping_sp',
            'table_shipping_se',
            'table_shipping_to',
            'table_shipping_xx'
        );

        foreach ($tables as $table) {
            $this->dbforge->drop_table($table, TRUE);
        }
	}
};