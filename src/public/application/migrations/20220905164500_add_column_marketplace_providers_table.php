<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        ## Create column providers.marketplace
        $newField = array(
            'marketplace' => array(
                'type' => 'INT',
                'unsigned' => FALSE,
                'constraint' => 11,
                'null' => TRUE
            )
        );
        $this->dbforge->add_column("providers", $newField);
        $this->db->query('ALTER TABLE `providers` ADD CONSTRAINT `providers_marketplace` FOREIGN KEY (`marketplace`) REFERENCES `integrations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
	 }

	public function down()	{
        $this->db->query('ALTER TABLE providers DROP FOREIGN KEY providers_marketplace;');
        $this->dbforge->drop_column("providers", 'marketplace');
	}
};