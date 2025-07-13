<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {		
        // CARREFOUR
        if(!$this->db->field_exists('credito', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `credito` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('data_gatilho', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `data_gatilho` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('data_recebida', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `data_recebida` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('data_transacao', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `data_transacao` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('numero_fatura', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `numero_fatura` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('numero_transacao', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `numero_transacao` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('rotulo_categoria', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `rotulo_categoria` varchar(255) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('descricao', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `descricao` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('tipo', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `tipo` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('status_pagamento', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `status_pagamento` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('debito', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `debito` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('moeda', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `moeda` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('referencia_pedido_cliente', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `referencia_pedido_cliente` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('referencia_pedido_loja', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `referencia_pedido_loja` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('data_ciclo_faturamento', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `data_ciclo_faturamento` varchar(90) AFTER `valor_unitario_promocional`');
		}
        if(!$this->db->field_exists('valor_extrato', 'conciliacao_carrefour_xls')){
        	$this->db->query('ALTER TABLE `conciliacao_carrefour_xls` ADD COLUMN `valor_extrato` varchar(90) AFTER `valor_unitario_promocional`');
		}
      
	 }

	public function down()	{
		// CARREFOUR
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'credito');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'data_gatilho');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'data_recebida');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'data_transacao');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'numero_fatura');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'rotulo_categoria');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'descricao');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'tipo');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'status_pagamento');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'debito');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'moeda');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'referencia_pedido_cliente');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'referencia_pedido_loja');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'data_ciclo_faturamento');
        $this->dbforge->drop_column("conciliacao_carrefour_xls", 'valor_extrato');
    
	}
};