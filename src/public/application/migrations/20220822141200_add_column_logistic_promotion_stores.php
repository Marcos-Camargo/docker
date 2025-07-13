<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
		## Create column logistic_promotion_stores.seller_accepted
        if ($this->db->where(['TABLE_NAME' => 'logistic_promotion_stores', 'COLUMN_NAME' => 'seller_accepted'])->get('INFORMATION_SCHEMA.COLUMNS')->num_rows() === 0) {
            $fields = array(
                'seller_accepted' => array(
                    'type' => 'TINYINT',
                    'constraint' => ('1'),
                    'null' => FALSE,
                    'default' => '0'
                )
            );
            $this->dbforge->add_column("logistic_promotion_stores", $fields);
        }

        ## Create column logistic_promotion_stores.date_seller_accepted
        if ($this->db->where(['TABLE_NAME' => 'logistic_promotion_stores', 'COLUMN_NAME' => 'date_seller_accepted'])->get('INFORMATION_SCHEMA.COLUMNS')->num_rows() === 0) {
            $fields = array(
                'date_seller_accepted' => array(
                    'type' => 'TIMESTAMP',
                    'null' => TRUE
                )
            );
            $this->dbforge->add_column("logistic_promotion_stores", $fields);
        }
	}

	public function down()	{
        ### Drop column logistic_promotion_stores.seller_accepted ##
        $this->dbforge->drop_column("logistic_promotion_stores", 'seller_accepted');
        ### Drop column logistic_promotion_stores.date_seller_accepted ##
        $this->dbforge->drop_column("logistic_promotion_stores", 'date_seller_accepted');
	}
};