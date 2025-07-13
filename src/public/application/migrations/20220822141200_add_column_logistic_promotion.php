<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
		## Create column logistic_promotion.segment
        if ($this->db->where(['TABLE_NAME' => 'logistic_promotion', 'COLUMN_NAME' => 'segment'])->get('INFORMATION_SCHEMA.COLUMNS')->num_rows() === 0) {
            $fields = array(
                'segment' => array(
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE
                )
            );
            $this->dbforge->add_column("logistic_promotion", $fields);
        }

        ## Create column logistic_promotion.promotion_sellercenter
        if ($this->db->where(['TABLE_NAME' => 'logistic_promotion', 'COLUMN_NAME' => 'promotion_sellercenter'])->get('INFORMATION_SCHEMA.COLUMNS')->num_rows() === 0) {
            $fields = array(
                'promotion_sellercenter' => array(
                    'type' => 'TINYINT',
                    'constraint' => ('1'),
                    'null' => FALSE,
                    'default' => '1'
                )
            );
            $this->dbforge->add_column("logistic_promotion", $fields);
        }
	}

	public function down()	{
        ### Drop column logistic_promotion.segment ##
        $this->dbforge->drop_column("logistic_promotion", 'segment');
        ### Drop column logistic_promotion.promotion_sellercenter ##
        $this->dbforge->drop_column("logistic_promotion", 'promotion_sellercenter');
	}
};