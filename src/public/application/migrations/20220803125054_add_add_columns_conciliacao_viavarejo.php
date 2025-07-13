<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {		      

        // VIA VAREJO
		if(!$this->db->field_exists('data_gatilho', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE  `conciliacao_viavarejo` ADD COLUMN `data_gatilho` VARCHAR(50) NULL');
		}
        if(!$this->db->field_exists('id_entrega', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `id_entrega` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('marca', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `marca` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('data_pedido_incluido', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `data_pedido_incluido` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('data_pedido_entregue', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `data_pedido_entregue` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('data_liberacao', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `data_liberacao` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('data_prevista_repasse', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `data_prevista_repasse` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('data_repasse', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `data_repasse` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('data_antecipacao', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `data_antecipacao` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('numero_liquidacao', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `numero_liquidacao` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('sku_marketplace', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `sku_marketplace` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('sku_lojista', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `sku_lojista` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('valor_produto_sem_desconto', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `valor_produto_sem_desconto` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('desconto_onus_via', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `desconto_onus_via` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('desconto_onus_lojista', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `desconto_onus_lojista` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('tipo_do_frete', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `tipo_do_frete` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('frete_promocional_onus_via', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `frete_promocional_onus_via` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('frete_promocional_onus_lojista', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `frete_promocional_onus_lojista` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('valor_da_transacao', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `valor_da_transacao` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('comissao_contratual', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `comissao_contratual` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('comissao_aplicada_porcentagem', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `comissao_aplicada_porcentagem` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('comissao_aplicada_reais', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `comissao_aplicada_reais` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('parcela_atual', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `parcela_atual` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('valor_bruto_repasse', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `valor_bruto_repasse` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('valor_antecipacao', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `valor_antecipacao` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('taxa_antecipacao', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `taxa_antecipacao` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('valor_liquido_repasse', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `valor_liquido_repasse` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('motivo_ajuste', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `motivo_ajuste` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('observacao', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `observacao` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('origem_repasse', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `origem_repasse` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('tipo_campanha', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `tipo_campanha` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('ajuste_realizado_outro_ciclo', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `ajuste_realizado_outro_ciclo` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('valor_ajuste_ciclos_anteriores', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `valor_ajuste_ciclos_anteriores` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('data_ajuste', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `data_ajuste` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('nf_repasse', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `nf_repasse` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('nf_cliente', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `nf_cliente` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('descricao_produto', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `descricao_produto` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('departamento', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `departamento` varchar(90) AFTER `data_gatilho`');
		}
        if(!$this->db->field_exists('categoria', 'conciliacao_viavarejo')){
        	$this->db->query('ALTER TABLE `conciliacao_viavarejo` ADD COLUMN `categoria` varchar(90) AFTER `data_gatilho`');
		}

	 }

	public function down()	{
		
        //VIA VAREJO
        $this->dbforge->drop_column("conciliacao_viavarejo", 'data_gatilho');
		$this->dbforge->drop_column("conciliacao_viavarejo", 'id_entrega');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'marca');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'data_pedido_incluido');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'data_pedido_entregue');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'data_liberacao');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'data_prevista_repasse');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'data_repasse');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'data_antecipacao');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'numero_liquidacao');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'sku_marketplace');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'sku_lojista');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'valor_produto_sem_desconto');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'desconto_onus_via');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'desconto_onus_lojista');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'tipo_do_frete');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'frete_promocional_onus_via');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'frete_promocional_onus_lojista');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'valor_da_transacao');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'comissao_contratual');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'comissao_aplicada_porcentagem');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'comissao_aplicada_reais');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'parcela_atual');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'valor_bruto_repasse');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'valor_antecipacao');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'taxa_antecipacao');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'valor_liquido_repasse');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'motivo_ajuste');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'observacao');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'origem_repasse');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'tipo_campanha');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'ajuste_realizado_outro_ciclo');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'valor_ajuste_ciclos_anteriores');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'data_ajuste');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'nf_repasse');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'nf_cliente');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'descricao_produto');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'departamento');
        $this->dbforge->drop_column("conciliacao_viavarejo", 'categoria');
	}
};