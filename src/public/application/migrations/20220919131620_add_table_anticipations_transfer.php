<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table vtex_payment_methods
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('10'),
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'order_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
			),			
      'valor_antecipado' => array(
				'type' => 'DOUBLE',				
        'default' => 0				
			),	
      'data_antecipacao' => array(
				'type' => 'timestamp',				
				'null' => true,
			),	
			'`date_insert` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
			'`date_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("anticipation_transfer", TRUE);		
	 }

	public function down()	{
		### Drop table vtex_payment_methods ##
		$this->dbforge->drop_table("anticipation_transfer", TRUE);

	}
};