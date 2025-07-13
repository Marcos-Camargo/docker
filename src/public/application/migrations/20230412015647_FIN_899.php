<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query('DROP TRIGGER IF EXISTS `tgr_insert_mktplace_ciclo`');
		$this->db->query('CREATE TRIGGER `tgr_insert_mktplace_ciclo` AFTER INSERT 
							ON `integrations` FOR EACH ROW 
							BEGIN
							DECLARE mktrLinked INT;
							SET mktrLinked = (SELECT count(*) as total FROM stores_mkts_linked WHERE apelido = NEW.int_to); 
							IF NEW.store_id = 0 and mktrLinked = 0 THEN
								INSERT INTO `stores_mkts_linked`(`id_integration`,`id_mkt`,`id_loja`,`descloja`,`apelido`,`apikey`) VALUES 
								( 13, ROUND(RAND()*(100-200)+200,0), ROUND(RAND()*(999999999-123456789)+999999999,0),  concat(\'Marketplace \',NEW.name), NEW.int_to,  NEW.int_to );
							END IF;
							END');
	}

	public function down()	{
	}
};