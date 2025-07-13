<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'uf' => array(
                'type' => 'VARCHAR',
                'constraint' => ('2'),
                'null' => FALSE,
            ),
            'state' => array(
                'type' => 'VARCHAR',
                'constraint' => ('64'),
                'null' => FALSE,
            ),
            'zipcode_start' => array(
                'type' => 'VARCHAR',
                'constraint' => ('8'),
                'null' => TRUE,
            ),
            'zipcode_end' => array(
                'type' => 'VARCHAR',
                'constraint' => ('8'),
                'null' => TRUE,
            ),
            'table' => array(
                'type' => 'VARCHAR',
                'constraint' => ('64'),
                'null' => FALSE,
            ),
            'status' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'default' => 1,
            ),
        ));
        $this->dbforge->add_key("id",true);
        $this->dbforge->create_table("table_shipping_regions", TRUE);
        $this->db->query('ALTER TABLE  `table_shipping_regions` ENGINE = InnoDB');

        $inserts = array(
            ['uf' => 'AC', 'state' => 'Acre',                   'zipcode_start' => '69900000', 'zipcode_end' => '69999999', 'table' => 'table_shipping_ac'],
            ['uf' => 'AL', 'state' => 'Alagoas',                'zipcode_start' => '57000000', 'zipcode_end' => '57999999', 'table' => 'table_shipping_al'],
            ['uf' => 'AP', 'state' => 'Amapá',                  'zipcode_start' => '68900000', 'zipcode_end' => '68999999', 'table' => 'table_shipping_ap'],
            ['uf' => 'AM', 'state' => 'Amazonas',               'zipcode_start' => '69000000', 'zipcode_end' => '69299999', 'table' => 'table_shipping_am'],
            ['uf' => 'AM', 'state' => 'Amazonas',               'zipcode_start' => '69400000', 'zipcode_end' => '69899999', 'table' => 'table_shipping_am'],
            ['uf' => 'BA', 'state' => 'Bahia',                  'zipcode_start' => '40000000', 'zipcode_end' => '48999999', 'table' => 'table_shipping_ba'],
            ['uf' => 'CE', 'state' => 'Ceará',                  'zipcode_start' => '60000000', 'zipcode_end' => '63999999', 'table' => 'table_shipping_ce'],
            ['uf' => 'DF', 'state' => 'Distrito Federal',       'zipcode_start' => '70000000', 'zipcode_end' => '72799999', 'table' => 'table_shipping_df'],
            ['uf' => 'DF', 'state' => 'Distrito Federal',       'zipcode_start' => '73000000', 'zipcode_end' => '73699999', 'table' => 'table_shipping_df'],
            ['uf' => 'ES', 'state' => 'Espírito Santo',         'zipcode_start' => '29000000', 'zipcode_end' => '29999999', 'table' => 'table_shipping_es'],
            ['uf' => 'GO', 'state' => 'Goiás',                  'zipcode_start' => '72800000', 'zipcode_end' => '72999999', 'table' => 'table_shipping_go'],
            ['uf' => 'GO', 'state' => 'Goiás',                  'zipcode_start' => '73700000', 'zipcode_end' => '76799999', 'table' => 'table_shipping_go'],
            ['uf' => 'MA', 'state' => 'Maranhão',               'zipcode_start' => '65000000', 'zipcode_end' => '65999999', 'table' => 'table_shipping_ma'],
            ['uf' => 'MT', 'state' => 'Mato Grosso',            'zipcode_start' => '78000000', 'zipcode_end' => '78899999', 'table' => 'table_shipping_mt'],
            ['uf' => 'MS', 'state' => 'Mato Grosso do Sul',     'zipcode_start' => '79000000', 'zipcode_end' => '79999999', 'table' => 'table_shipping_ms'],
            ['uf' => 'MG', 'state' => 'Minas Gerais',           'zipcode_start' => '30000000', 'zipcode_end' => '39999999', 'table' => 'table_shipping_mg'],
            ['uf' => 'PA', 'state' => 'Pará',                   'zipcode_start' => '66000000', 'zipcode_end' => '68899999', 'table' => 'table_shipping_pa'],
            ['uf' => 'PB', 'state' => 'Paraíba',                'zipcode_start' => '58000000', 'zipcode_end' => '58999999', 'table' => 'table_shipping_pb'],
            ['uf' => 'PR', 'state' => 'Paraná',                 'zipcode_start' => '80000000', 'zipcode_end' => '87999999', 'table' => 'table_shipping_pr'],
            ['uf' => 'PE', 'state' => 'Pernambuco',             'zipcode_start' => '50000000', 'zipcode_end' => '56999999', 'table' => 'table_shipping_pe'],
            ['uf' => 'PI', 'state' => 'Piauí',                  'zipcode_start' => '64000000', 'zipcode_end' => '64999999', 'table' => 'table_shipping_pi'],
            ['uf' => 'RJ', 'state' => 'Rio de Janeiro',         'zipcode_start' => '20000000', 'zipcode_end' => '28999999', 'table' => 'table_shipping_rj'],
            ['uf' => 'RN', 'state' => 'Rio Grande do Norte',    'zipcode_start' => '59000000', 'zipcode_end' => '59999999', 'table' => 'table_shipping_rn'],
            ['uf' => 'RS', 'state' => 'Rio Grande do Sul',      'zipcode_start' => '90000000', 'zipcode_end' => '99999999', 'table' => 'table_shipping_rs'],
            ['uf' => 'RO', 'state' => 'Rondônia',               'zipcode_start' => '76800000', 'zipcode_end' => '76999999', 'table' => 'table_shipping_ro'],
            ['uf' => 'RR', 'state' => 'Roraima',                'zipcode_start' => '69300000', 'zipcode_end' => '69399999', 'table' => 'table_shipping_rr'],
            ['uf' => 'SC', 'state' => 'Santa Catarina',         'zipcode_start' => '88000000', 'zipcode_end' => '89999999', 'table' => 'table_shipping_sc'],
            ['uf' => 'SP', 'state' => 'Sergipe',                'zipcode_start' => '49000000', 'zipcode_end' => '49999999', 'table' => 'table_shipping_sp'],
            ['uf' => 'SE', 'state' => 'São Paulo',              'zipcode_start' => '01000000', 'zipcode_end' => '19999999', 'table' => 'table_shipping_se'],
            ['uf' => 'TO', 'state' => 'Tocantins',              'zipcode_start' => '77000000', 'zipcode_end' => '77999999', 'table' => 'table_shipping_to'],

            ['uf' => 'XX', 'state' => 'Não encontrado',         'zipcode_start' => '00000000', 'zipcode_end' => '00999999', 'table' => 'table_shipping_xx'],
            ['uf' => 'XX', 'state' => 'Não encontrado',         'zipcode_start' => '78900000', 'zipcode_end' => '78999999', 'table' => 'table_shipping_xx'],
        );

        $this->db->insert_batch('table_shipping_regions', $inserts);
	}

	public function down()	{
        ### Drop table table_shipping_regions ##
        $this->dbforge->drop_table("table_shipping_regions", TRUE);
	}
};