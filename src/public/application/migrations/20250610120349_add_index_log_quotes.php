<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Create index filter_edit_order ##
        $date_backup_log_table = date('Ymd');
        $this->db->query("RENAME TABLE log_quotes TO log_quotes_$date_backup_log_table;");

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'quote_id' => array(
                'type' => 'VARCHAR',
                'constraint' => ('36'),
                'null' => FALSE
            ),
            'marketplace' => array(
                'type' => 'VARCHAR',
                'constraint' => ('32'),
                'null' => FALSE
            ),
            'zipcode' => array(
                'type' => 'VARCHAR',
                'constraint' => ('8'),
                'null' => FALSE
            ),
            'product_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'skumkt' => array(
                'type' => 'VARCHAR',
                'constraint' => ('32'),
                'null' => FALSE
            ),
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'seller_id' => array(
                'type' => 'VARCHAR',
                'constraint' => ('16'),
                'null' => FALSE
            ),
            'is_multiseller' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => 0
            ),
            'integration' => array(
                'type' => 'VARCHAR',
                'constraint' => ('32'),
                'null' => TRUE
            ),
            'success' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE
            ),
            'contingency' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE
            ),
            'response_total_time' => array(
                'type' => 'DECIMAL',
                'constraint' => ('15,3'),
                'null' => FALSE,
            ),
            'response_details_time' => array(
                'type' => 'TEXT',
                'null' => FALSE
            ),
            'response_total_time_quote' => array(
                'type' => 'DECIMAL',
                'constraint' => ('15,3'),
                'null' => FALSE,
            ),
            'response_slas' => array(
                'type' => 'TEXT',
                'null' => FALSE
            ),
            'error_message' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));

        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("log_quotes", TRUE);

        $this->db->query('CREATE INDEX idx_log_quotes_product_id ON log_quotes (product_id);');
    }

	public function down()	{
		### Drop index filter_edit_order ##
        $this->db->query('DROP INDEX idx_log_quotes_product_id ON log_quotes;');

	}
};