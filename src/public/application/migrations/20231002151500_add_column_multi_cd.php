<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_driver $db
 */

return new class extends CI_Migration
{

	public function up() {

        $fieldsStore = array(
            'type_store' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => false,
                'default' => 0,
                'comment' => '0=Normal|1=Principal Multi CD|2=CD Multi CD'
            ),
            'max_time_to_invoice_order' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            )
        );

        $fieldCompany = array(
            'multi_channel_fulfillment' => array(
                'type' => 'TINYINT',
                'null' => false,
                'default' => false
            )
        );

        foreach ($fieldsStore as $column => $fieldStore) {
            if (!$this->dbforge->column_exists($column, 'stores')) {
                $this->dbforge->add_column('stores', array($column => $fieldStore));
            }
        }

        if (!$this->dbforge->column_exists('multi_channel_fulfillment', 'company')){
            $this->dbforge->add_column('company', $fieldCompany);
        }
	}

	public function down()	{
		### Drop table stores.type_store ##
		$this->dbforge->drop_column("stores", 'type_store');
        ### Drop table company.multi_channel_fulfillment ##
        $this->dbforge->drop_column("company", 'multi_channel_fulfillment');

	}
};