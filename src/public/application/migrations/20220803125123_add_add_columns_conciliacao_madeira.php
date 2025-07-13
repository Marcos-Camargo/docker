<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        // MADEIRA
        if(!$this->db->field_exists('detalhamento', 'conciliacao_madeira')){
        	$this->db->query('ALTER TABLE `conciliacao_madeira` ADD COLUMN `detalhamento` varchar(90) AFTER `valor`');
		}
        if(!$this->db->field_exists('pedido_mm', 'conciliacao_madeira')){
        	$this->db->query('ALTER TABLE `conciliacao_madeira` ADD COLUMN `pedido_mm` varchar(90) AFTER `valor`');
		}
        if(!$this->db->field_exists('pedido', 'conciliacao_madeira')){
        	$this->db->query('ALTER TABLE `conciliacao_madeira` ADD COLUMN `pedido` varchar(90) AFTER `valor`');
		}
        if(!$this->db->field_exists('descricao', 'conciliacao_madeira')){
        	$this->db->query('ALTER TABLE `conciliacao_madeira` ADD COLUMN `descricao` varchar(90) AFTER `valor`');
		}
        if(!$this->db->field_exists('data', 'conciliacao_madeira')){
        	$this->db->query('ALTER TABLE `conciliacao_madeira` ADD COLUMN `data` varchar(90) AFTER `valor`');
		}
             
	 }

	public function down()	{
        // MADEIRA
        $this->dbforge->drop_column("conciliacao_madeira", 'detalhamento');
        $this->dbforge->drop_column("conciliacao_madeira", 'pedido_mm');
        $this->dbforge->drop_column("conciliacao_madeira", 'pedido');
        $this->dbforge->drop_column("conciliacao_madeira", 'descricao');
        $this->dbforge->drop_column("conciliacao_madeira", 'data');

	}
};